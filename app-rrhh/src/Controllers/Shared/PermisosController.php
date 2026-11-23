<?php

declare(strict_types=1);

// Trasladé la autorización, POST y preparación de datos fuera del servicio/modelo.
// Este controlador compartido se ejecuta antes del layout, con parámetros del controlador por rol.
auth_requiere_rol($rolEsperado);

$idUsuario   = auth_usuario_actual_id();
$errores     = [];
$mensajeExito = '';

// Procesar acciones de aprobación o rechazo
if (is_post()) {
    $csrfToken = (string) input('csrf_token', '');
    if (!csrf_validar($csrfToken)) {
        $errores[] = 'La sesión del formulario expiró. Por favor recarga e intenta nuevamente.';
        $esAjax = (strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest');
        if ($esAjax) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'errores' => $errores], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } else {
        $accion    = (string) input('accion', '');
        $idPermiso = (int) input('id_permiso', 0);

        if($accion==='revisar_colacion') {
            // El servicio vuelve a comprobar alcance, autosupervisión, estado y horario efectivo.
            $decision=(string)input('decision_colacion','');
            $resultado=in_array($decision,['APROBAR','RECHAZAR'],true)
                ?colacion_revisar((int)input('id_solicitud',0),$decision==='APROBAR',(string)input('motivo_colacion',''))
                :['ok'=>false,'errores'=>['Selecciona una decisión válida.']];
            if($resultado['ok']){$mensajeExito=$resultado['mensaje'];}else{$errores=$resultado['errores'];}
        } elseif(in_array($accion,['revisar_tiempo','revisar_compensacion'],true)) {
            // La aprobación excepcional documenta manualmente el motivo indicado por el jefe.
            $resultado=tiempo_procesar_accion($accion);
            if($resultado['ok']){$mensajeExito=$resultado['mensaje'];}else{$errores=$resultado['errores'];}
        } elseif ($accion === 'aprobar_permiso') {
            $comentario = (string) input('comentario_aprobacion', '');
            // Pasé consentimiento explícito para feriados; el servidor vuelve a comprobarlo.
            $resultado  = permisos_gestion_aprobar($idPermiso, $idUsuario, $comentario, input('autorizar_feriados','')==='1');
            if ($resultado['ok']) {
                $mensajeExito = '¡Permiso aprobado con éxito! Se autorizó el plan; cada tramo requiere validación posterior de sus marcas.';
            } else {
                $errores = $resultado['errores'];
            }
        } elseif ($accion === 'autorizar_recuperacion_adicional') {
            // Autorización nueva y expresa para un tramo que reemplaza saldo no trabajado.
            $resultado=recuperaciones_gestionar_adicional($idPermiso,[],(string)input('motivo_adicional',''),(int)input('id_recuperacion',0),input('autorizar_feriados','')==='1');
            if($resultado['ok']){$mensajeExito='Tramo adicional autorizado; todavía requiere validar marcas.';}else{$errores=$resultado['errores'];}
        } elseif ($accion === 'validar_recuperacion') {
            // Separé autorización del plan de validación de minutos efectivamente trabajados.
            // Asigné motivo automático predeterminado para validar con un solo clic sin inputs manuales.
            $motivoValidacion = trim((string) input('motivo_validacion', ''));
            if ($motivoValidacion === '') {
                $motivoValidacion = 'Jornada de recuperación validada conforme a marcas registradas.';
            }
            $idRecuperacion = (int) input('id_recuperacion', 0);
            $resultado = recuperaciones_validar($idRecuperacion, $motivoValidacion);
            if ($resultado['ok']) {
                $mensajeExito = $resultado['mensaje'] ?? 'Recuperación validada según marcas registradas.';
            } else {
                $errores = $resultado['errores'];
            }

            // Atendí solicitudes AJAX para validar en vivo sin recargar la página ni perder filtros
            $esAjax = (strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest');
            if ($esAjax) {
                header('Content-Type: application/json; charset=utf-8');
                if ($resultado['ok']) {
                    $stmtTramo = db()->prepare('SELECT estado, minutos_validados, minutos_planificados, fecha, tipo FROM recuperaciones_permiso WHERE id_recuperacion = ?');
                    $stmtTramo->execute([$idRecuperacion]);
                    $tramoActualizado = $stmtTramo->fetch() ?: [];

                    $idPermisoActual = (int) input('id_permiso', 0);
                    $totalValidados = 0;
                    $horasAusencia = 0.0;
                    $totalTramos = 1;
                    $primeraFechaCorto = '';
                    if ($idPermisoActual > 0) {
                        $stmtP = db()->prepare('SELECT horas_ausencia FROM permisos_practicante WHERE id_permiso = ?');
                        $stmtP->execute([$idPermisoActual]);
                        $horasAusencia = (float) ($stmtP->fetchColumn() ?: 0.0);

                        $stmtSum = db()->prepare('SELECT COALESCE(SUM(minutos_validados), 0) AS validados, COUNT(*) AS cantidad, MIN(fecha) AS primera_fecha FROM recuperaciones_permiso WHERE id_permiso = ?');
                        $stmtSum->execute([$idPermisoActual]);
                        $resPlan = $stmtSum->fetch() ?: [];
                        $totalValidados = (int) ($resPlan['validados'] ?? 0);
                        $totalTramos = max(1, (int) ($resPlan['cantidad'] ?? 1));
                        if (!empty($resPlan['primera_fecha'])) {
                            $primeraFechaCorto = date('d-m', strtotime((string) $resPlan['primera_fecha']));
                        }
                    }

                    $minutosAusencia = (int) round($horasAusencia * 60);
                    $minutosPendientes = max(0, $minutosAusencia - $totalValidados);
                    $horasValidadasFmt = number_format($totalValidados / 60, ($totalValidados % 60 === 0 ? 0 : 1));
                    $horasTotalesFmt = number_format($minutosAusencia / 60, ($minutosAusencia % 60 === 0 ? 0 : 1));

                    echo json_encode([
                        'ok' => true,
                        'mensaje' => $mensajeExito,
                        'id_recuperacion' => $idRecuperacion,
                        'id_permiso' => $idPermisoActual,
                        'estado_tramo' => (string) ($tramoActualizado['estado'] ?? 'VALIDADA'),
                        'minutos_validados' => (int) ($tramoActualizado['minutos_validados'] ?? 0),
                        'total_validados' => $totalValidados,
                        'minutos_pendientes' => $minutosPendientes,
                        'horas_validadas_fmt' => $horasValidadasFmt,
                        'horas_totales_fmt' => $horasTotalesFmt,
                        'total_tramos' => $totalTramos,
                        'primera_fecha_corto' => $primeraFechaCorto,
                        'fecha_tramo' => !empty($tramoActualizado['fecha']) ? date('d-m-Y', strtotime((string)$tramoActualizado['fecha'])) : '',
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    http_response_code(422);
                    echo json_encode([
                        'ok' => false,
                        'errores' => $errores,
                    ], JSON_UNESCAPED_UNICODE);
                }
                exit;
            }
        } elseif ($accion === 'rechazar_permiso') {
            $motivo    = (string) input('motivo_rechazo', '');
            $resultado = permisos_gestion_rechazar($idPermiso, $idUsuario, $motivo);
            if ($resultado['ok']) {
                $mensajeExito = 'Solicitud de permiso rechazada correctamente.';
            } else {
                $errores = $resultado['errores'];
            }
        }
    }
}

// Filtros y listado
$filtros = [
    'estado' => (string) query_param('estado', ''),
    'desde'  => (string) query_param('desde',  ''),
    'hasta'  => (string) query_param('hasta',  ''),
    'q'      => trim((string) query_param('q', '')),
];

$limitesPermitidos  = [5, 10, 20, 25, 50];
$limiteSeleccionado = (int) query_param('limite', 5);
if (!in_array($limiteSeleccionado, $limitesPermitidos, true)) {
    $limiteSeleccionado = 5;
}

$paginaActual = max(1, (int) query_param('pagina', 1));
// Fijé 5 registros por defecto y habilité selección controlada de tamaño de página.
$datos        = permisos_gestion_listar_todos($filtros, $rolEsperado, $limiteSeleccionado, $paginaActual);

$permisos     = $datos['items'];
$totalPaginas = $datos['paginas'];
$token        = csrf_token();
// Preparé historial y revisiones de tiempo sin consultas desde las plantillas.
$tiempos=tiempo_listar();$jornadasTiempo=[];
// Compartí categorías entre ADMIN y SUPERVISOR, sin confiar en nombres enviados por el cliente.
$categoriaPermisos=(string)query_param('categoria','ausencias');
if(!in_array($categoriaPermisos,['ausencias','tiempo','compensacion','colacion'],true)) { $categoriaPermisos='ausencias'; }
$bandejaTiempo=tiempo_bandeja($tiempos,$categoriaPermisos,$filtros,$limiteSeleccionado,$paginaActual);
// Preparé filtros y paginación de colaciones autorizadas antes de emitir HTML.
$solicitudesColacion=colacion_listar();$totalColaciones=count($solicitudesColacion);
$solicitudesColacion=array_values(array_filter($solicitudesColacion,static function(array $r) use ($filtros):bool {
    return ($filtros['estado']==='' || $r['estado']===$filtros['estado'])
        && ($filtros['desde']==='' || $r['fecha']>=$filtros['desde']) && ($filtros['hasta']==='' || $r['fecha']<=$filtros['hasta'])
        && ($filtros['q']==='' || str_contains(mb_strtolower($r['nombre_1'].' '.$r['apellido_paterno'].' '.$r['email']),mb_strtolower($filtros['q'])));
}));
$colacionTotalFiltrado=count($solicitudesColacion);$colacionPaginas=max(1,(int)ceil($colacionTotalFiltrado/$limiteSeleccionado));
$colacionPagina=min($paginaActual,$colacionPaginas);
$solicitudesColacion=array_slice($solicitudesColacion,($colacionPagina-1)*$limiteSeleccionado,$limiteSeleccionado);
