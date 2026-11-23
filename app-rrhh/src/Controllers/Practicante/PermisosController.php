<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| PermisosController — Gestión de Permisos (PRACTICANTE)
|--------------------------------------------------------------------------
|
| Permite al practicante solicitar permiso para ausentarse total o
| PARCIALMENTE en una fecha pasada, actual o futura de su práctica.
|
*/

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once SERVICES_PATH . '/PermisosService.php';
require_once SERVICES_PATH . '/ReglasAsistenciaService.php';

auth_requiere_rol('PRACTICANTE');

$idPracticante = auth_practicante_actual_id();
$errores       = [];
$mensajeExito  = '';
$practicaActiva = null;
$solicitudes    = [];
$tiempos=[];$jornadasTiempo=[];
$solicitudesColacion=[];
$estadoSoloLectura = ['solo_lectura' => false, 'meta_cumplida' => false, 'practica_egresada' => false, 'motivo' => null, 'practica' => null];
$soloLectura = false;

if ($idPracticante === null) {
    $errores[] = 'Tu cuenta de usuario no está asociada a un registro de practicante.';
} else {
    try {
        $estadoSoloLectura = asistencia_estado_solo_lectura_practicante($idPracticante);
        $soloLectura = !empty($estadoSoloLectura['solo_lectura']);
        $practicaActiva = permisos_gestion_practica_activa($idPracticante);

        // Si el practicante ya egresó, cargo su última práctica para conservar visible su historial de permisos.
        if ($practicaActiva === null && is_array($estadoSoloLectura['practica'] ?? null)) {
            $practicaActiva = $estadoSoloLectura['practica'];
        }

        if ($practicaActiva === null) {
            $errores[] = 'No cuentas con una práctica activa registrada en el sistema para solicitar permisos.';
        } else {
            $idPractica = (int) $practicaActiva['id_practica'];

            // En modo solo lectura (100% completado o práctica finalizada) se bloquean todas las solicitudes POST.
            if (is_post() && $soloLectura) {
                $errores[] = (string) ($estadoSoloLectura['motivo'] ?? 'Tu perfil está en modo solo lectura y no permite nuevas solicitudes.');
            } else {
            // Mantuve la solicitud de colación separada de ausencia y crédito excepcional, con CSRF.
            if(is_post() && input('accion')==='solicitar_colacion') {
                if(!csrf_validar((string)input('csrf_token',''))){$errores[]='La sesión del formulario expiró.';}
                else {
                    $resultado=colacion_solicitar((string)input('inicio_colacion',''),(string)input('motivo_colacion',''));
                    if($resultado['ok']){$mensajeExito=$resultado['mensaje'];}else{$errores=$resultado['errores'];}
                }
            }

            // Agregué solicitudes excepcionales y canjes separados del permiso con recuperación.
            if(is_post() && in_array(input('accion'),['solicitar_tiempo','solicitar_compensacion'],true)) {
                if(!csrf_validar((string)input('csrf_token',''))) { $errores[]='La sesión del formulario expiró.'; }
                else {
                    $resultado=tiempo_procesar_accion((string)input('accion'));
                    if($resultado['ok']){$mensajeExito=$resultado['mensaje'];}else{$errores=$resultado['errores'];}
                }
            }

            // Procesar envío de formulario
            if (is_post() && in_array(input('accion'),['solicitar_permiso','solicitar_recuperacion_adicional'],true)) {
                $csrfToken = (string) input('csrf_token', '');
                if (!csrf_validar($csrfToken)) {
                    $errores[] = 'La sesión del formulario expiró. Por favor, recarga e intenta de nuevo.';
                } else {
                    // Permití solicitar nuevos tramos sobre el saldo no recuperado, sin alterar la ausencia aprobada.
                    if (input('accion')==='solicitar_recuperacion_adicional') {
                        $fila=input('recuperacion_adicional',[]);
                        $resultado=recuperaciones_gestionar_adicional((int)input('id_permiso',0),is_array($fila)?$fila:[],(string)input('motivo_adicional',''));
                    } else {
                    $resultado = permisos_gestion_solicitar($idPracticante, [
                        'fecha_ausencia'           => input('fecha_ausencia', ''),
                        'tipo_ausencia'            => input('tipo_ausencia', 'COMPLETA'),
                        'hora_inicio_ausencia'     => input('hora_inicio_ausencia', ''),
                        'hora_fin_ausencia'        => input('hora_fin_ausencia', ''),
                        'motivo'                   => input('motivo', ''),
                        'fecha_recuperacion'       => input('fecha_recuperacion', ''),
                        'hora_inicio_recuperacion' => input('hora_inicio_recuperacion', ''),
                        'hora_fin_recuperacion'    => input('hora_fin_recuperacion', ''),
                        // Agregué todos los tramos del formulario sin confiar en su total calculado por JS.
                        'recuperaciones' => input('recuperaciones', null),
                    ]);
                    }

                    if ($resultado['ok']) {
                        $mensajeExito = input('accion')==='solicitar_recuperacion_adicional'
                            ? 'Tramo adicional solicitado. Requiere autorización del supervisor antes de validar horas.'
                            : '¡Solicitud de permiso enviada con éxito! Queda a la espera de revisión por parte de tu supervisor.';
                    } else {
                        $errores = $resultado['errores'];
                    }
                }
            }
            }

            $solicitudes = permisos_gestion_listar_por_practica($idPractica);
            $solicitudesColacion=colacion_listar($idPractica);
            $tiempos=tiempo_listar($idPractica);$jornadasTiempo=tiempo_jornadas_solicitables($idPractica);
        }
    } catch (Throwable $e) {
        error_log('Error en practicante/permisos.php: ' . $e->getMessage());
        $errores[] = 'Ocurrió un error al cargar el módulo de permisos. Intenta nuevamente.';
    }
}

$token     = csrf_token();
$pageTitle = 'Permisos y Recuperación';

// Preparé los datos del layout antes de mostrar la vista.
require SRC_PATH . '/Controllers/Shared/LayoutContext.php';

require_once VIEWS_PATH . '/practicante/permisos.view.php';
