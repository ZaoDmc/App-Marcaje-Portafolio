<?php

declare(strict_types=1);

// Trasladé la autorización, POST y preparación de datos fuera del servicio/modelo.
// Este controlador compartido se ejecuta antes del layout, con parámetros del controlador por rol.
auth_requiere_rol($rolPermitido);
// Conservé privada la ficha que incorpora las calificaciones aprobadas.
header('Cache-Control: private, no-store');

$errores = [];
$mensajeExito = null;
$busqueda = trim((string) query_param('q', ''));
$idSeleccionado = query_param('id_practicante');
$idSeleccionado = ctype_digit((string) $idSeleccionado) ? (int) $idSeleccionado : null;
$fechaInicioHorario = '';
$horarioFormulario = horarios_practicante_valores_iniciales();
$areasCatalogo = [];
$historialAreas = [];
$excepcionesHorario = [];

start_secure_session();

if (empty($_SESSION['csrf_gestion_horarios'])) {
    $_SESSION['csrf_gestion_horarios'] = bin2hex(random_bytes(32));
}

try {
    $areasCatalogo = areas_practicante_catalogo();

    if (is_post()) {
        $csrf = (string) input('csrf_token', '');
        $accion = (string) input('accion', 'guardar_horario');

        if (!hash_equals((string) $_SESSION['csrf_gestion_horarios'], $csrf)) {
            $errores[] = 'La sesion del formulario expiro. Vuelve a intentarlo.';
        }

        $idSeleccionadoPost = input('id_practicante');
        $idSeleccionado = ctype_digit((string) $idSeleccionadoPost) ? (int) $idSeleccionadoPost : null;
        $practicantePost = $idSeleccionado !== null
            ? horarios_practicante_obtener_practicante_gestion($idSeleccionado)
            : null;

        if ($practicantePost === null) {
            $errores[] = 'Selecciona un practicante valido.';
        } elseif (auth_es_conflicto_supervisor_practicante((int) $practicantePost['id_practicante'])) {
            $errores[] = 'No puedes modificar tu propio horario desde el rol supervisor.';
        }

        if ($accion === 'guardar_excepcion_horario') {
            $horarioVigente = $practicantePost !== null
                ? horarios_practicante_resumen_vigente((int) $practicantePost['id_practica'])
                : null;

            $resultadoExcepcion = $errores === [] && $practicantePost !== null && $horarioVigente !== null
                ? excepciones_horario_guardar(
                    (int) $horarioVigente['id_horario'],
                    (string) input('fecha_excepcion', ''),
                    (string) input('tipo_excepcion', ''),
                    (string) input('entrada_excepcion', ''),
                    (string) input('salida_excepcion', ''),
                    (string) input('colacion_inicio_excepcion', ''),
                    (string) input('colacion_fin_excepcion', ''),
                    (string) input('observacion_excepcion', '')
                )
                : ['ok' => false, 'errores' => ['El practicante debe tener un horario vigente para registrar excepciones.']];

            if (($resultadoExcepcion['ok'] ?? false) && $practicantePost !== null) {
                $_SESSION['csrf_gestion_horarios'] = bin2hex(random_bytes(32));
                redirect_to($actionPath . '?id_practicante=' . (int) $practicantePost['id_practicante'] . '&excepcion=1');
            }

            $errores = array_merge($errores, $resultadoExcepcion['errores'] ?? ['No fue posible guardar la excepcion.']);
        } elseif ($accion === 'anular_excepcion_horario') {
            $horarioVigente = $practicantePost !== null
                ? horarios_practicante_resumen_vigente((int) $practicantePost['id_practica'])
                : null;

            $resultadoExcepcion = $errores === [] && $practicantePost !== null && $horarioVigente !== null
                ? excepciones_horario_anular(
                    (int) input('id_excepcion', 0),
                    (int) $horarioVigente['id_horario'],
                    (string) input('motivo_anulacion_excepcion', '')
                )
                : ['ok' => false, 'errores' => ['El practicante debe tener un horario vigente para anular excepciones.']];

            if (($resultadoExcepcion['ok'] ?? false) && $practicantePost !== null) {
                $_SESSION['csrf_gestion_horarios'] = bin2hex(random_bytes(32));
                redirect_to($actionPath . '?id_practicante=' . (int) $practicantePost['id_practicante'] . '&excepcion_anulada=1');
            }

            $errores = array_merge($errores, $resultadoExcepcion['errores'] ?? ['No fue posible anular la excepcion.']);
        } elseif ($accion === 'guardar_area') {
            $fechaInicioArea = trim((string) input('fecha_inicio_area', ''));
            $resultadoArea = $errores === [] && $practicantePost !== null
                ? areas_practicante_cambiar(
                    (int) $practicantePost['id_practica'],
                    (int) input('id_area', 0),
                    $fechaInicioArea,
                    (string) input('observacion_area', '')
                )
                : ['ok' => false, 'errores' => ['Selecciona un practicante valido.']];

            if (($resultadoArea['ok'] ?? false) && $practicantePost !== null) {
                $_SESSION['csrf_gestion_horarios'] = bin2hex(random_bytes(32));
                redirect_to($actionPath . '?id_practicante=' . (int) $practicantePost['id_practicante'] . '&area=1');
            }

            $errores = array_merge($errores, $resultadoArea['errores'] ?? ['No fue posible cambiar el area.']);
        } elseif ($accion === 'baja_practicante') {
            if ($rolPermitido !== 'ADMIN') {
                $errores[] = 'Solo ADMIN puede dar de baja practicantes.';
            }

            if ((string) input('confirmar_baja', '') !== '1') {
                $errores[] = 'Confirma la baja logica antes de continuar.';
            }

            if ($errores === [] && $practicantePost !== null) {
                // Mostré el bloqueo de liderazgo sin convertirlo en un error técnico ni perder la ficha seleccionada.
                try {
                    $bajaRealizada = horarios_practicante_baja_logica_practicante((int) $practicantePost['id_practicante']);
                } catch (DomainException $exception) {
                    $errores[] = $exception->getMessage();
                    $bajaRealizada = false;
                }
                if ($bajaRealizada) {
                    $_SESSION['csrf_gestion_horarios'] = bin2hex(random_bytes(32));
                    redirect_to($actionPath . '?baja=1');
                }

                if ($errores === []) { $errores[] = 'No fue posible dar de baja el practicante seleccionado.'; }
            }
        } elseif ($accion === 'reenviar_activacion') {
            // Solo el ADMIN puede reenviar links de activación
            if ($rolPermitido !== 'ADMIN') {
                $errores[] = 'Solo ADMIN puede reenviar links de activación.';
            }

            if ($errores === [] && $practicantePost !== null) {
                // Llamar a la función que invalida el token viejo y genera uno nuevo
                require_once __DIR__ . '/practicante_registro.php';
                $resultadoReenvio = practicante_registro_reenviar_activacion((int) $practicantePost['id_practicante']);

                if ($resultadoReenvio['ok'] ?? false) {
                    // Guardar en sesión la URL por si el email falló, para mostrarla en pantalla
                    $_SESSION['reenvio_url_activacion'] = $resultadoReenvio['url_activacion'];
                    $_SESSION['reenvio_email_enviado']  = $resultadoReenvio['email_enviado'];
                    $_SESSION['csrf_gestion_horarios'] = bin2hex(random_bytes(32));
                    redirect_to($actionPath . '?id_practicante=' . (int) $practicantePost['id_practicante'] . '&reenvio=1');
                }

                $errores = array_merge($errores, $resultadoReenvio['errores'] ?? ['No fue posible reenviar el link de activación.']);
            }
        } else {
            $fechaInicioHorario = trim((string) input('fecha_inicio_horario', ''));
            $horarioFormulario = horarios_practicante_normalizar($_POST);

            $fechaValida = DateTimeImmutable::createFromFormat('Y-m-d', $fechaInicioHorario);
            if ($fechaValida === false || $fechaValida->format('Y-m-d') !== $fechaInicioHorario) {
                $errores[] = 'Ingresa una fecha de inicio de horario valida.';
            }

            $errores = array_merge(
                $errores,
                horarios_practicante_validar($horarioFormulario, true, $fechaInicioHorario)
            );

            if ($errores === [] && $practicantePost !== null) {
                $horarioAnterior = horarios_practicante_obtener_vigente((int) $practicantePost['id_practica']);
                $idHorario = horarios_practicante_guardar(
                    (int) $practicantePost['id_practica'],
                    $fechaInicioHorario,
                    $horarioFormulario
                );

                auditoria_registrar('EDITAR_PRACTICANTE', [
                    'tabla_afectada' => 'horarios_practicante',
                    'id_registro_afectado' => $idHorario,
                    'valores_anteriores' => [
                        'id_practicante' => (int) $practicantePost['id_practicante'],
                        'id_practica' => (int) $practicantePost['id_practica'],
                        'horario' => horarios_practicante_describir($horarioAnterior),
                    ],
                    'valores_nuevos' => [
                        'id_practicante' => (int) $practicantePost['id_practicante'],
                        'id_practica' => (int) $practicantePost['id_practica'],
                        'fecha_inicio' => $fechaInicioHorario,
                        'horario' => horarios_practicante_describir($horarioFormulario),
                    ],
                ]);

                $_SESSION['csrf_gestion_horarios'] = bin2hex(random_bytes(32));
                redirect_to($actionPath . '?id_practicante=' . (int) $practicantePost['id_practicante'] . '&guardado=1');
            }
        }
    }

    if (query_param('guardado') === '1') {
        $mensajeExito = 'Horario pactado actualizado correctamente.';
    }

    if (query_param('baja') === '1') {
        $mensajeExito = 'Practicante dado de baja correctamente.';
    }

    if (query_param('area') === '1') {
        $mensajeExito = 'Area vigente actualizada correctamente.';
    }

    if (query_param('excepcion') === '1') {
        $mensajeExito = 'Excepcion de horario guardada correctamente.';
    }

    if (query_param('excepcion_anulada') === '1') {
        $mensajeExito = 'Excepcion de horario anulada correctamente.';
    }

    // Leer datos del último reenvío de activación desde la sesión (si existe).
    // Se borran de la sesión tras leerlos para que no persistan en recargas.
    $reenvioUrlActivacion = null;
    $reenvioEmailEnviado  = null;
    if (query_param('reenvio') === '1') {
        $mensajeExito         = 'Link de activación regenerado correctamente.';
        $reenvioUrlActivacion = $_SESSION['reenvio_url_activacion'] ?? null;
        $reenvioEmailEnviado  = $_SESSION['reenvio_email_enviado'] ?? null;
        unset($_SESSION['reenvio_url_activacion'], $_SESSION['reenvio_email_enviado']);
    }

    $practicantes = horarios_practicante_listar_practicantes_gestion($busqueda);
    $practicanteSeleccionado = $idSeleccionado !== null
        ? horarios_practicante_obtener_practicante_gestion($idSeleccionado)
        : null;

    if ($practicanteSeleccionado !== null) {
        $historialAreas = areas_practicante_historial((int) $practicanteSeleccionado['id_practica']);
        $resumenExcepciones = horarios_practicante_resumen_vigente((int) $practicanteSeleccionado['id_practica']);
        $excepcionesHorario = $resumenExcepciones !== null
            ? excepciones_horario_listar((int) $resumenExcepciones['id_horario'])
            : [];
    }

    if (!is_post() && $practicanteSeleccionado !== null) {
        $horarioFormulario = horarios_practicante_obtener_vigente((int) $practicanteSeleccionado['id_practica']);
        $resumen = horarios_practicante_resumen_vigente((int) $practicanteSeleccionado['id_practica']);
        $fechaInicioHorario = (string) (
            $resumen['fecha_inicio']
            ?? $practicanteSeleccionado['fecha_inicio_practica']
            ?? date('Y-m-d')
        );
    }
} catch (Throwable $exception) {
    $practicantes = [];
    $practicanteSeleccionado = null;
    $errores[] = APP_DEBUG
        ? 'Error tecnico: ' . $exception->getMessage()
        : 'No fue posible cargar la gestion de horarios.';
}

$rolActivo = auth_rol_activo() ?? $rolPermitido;

// Preparé la última nota aprobada fuera de la plantilla; el servicio vuelve a validar el acceso a la práctica.
require_once SRC_PATH . '/Services/ResumenNotasService.php';
$resumenNotaFicha = null;
if ($practicanteSeleccionado !== null && !auth_es_conflicto_supervisor_practicante((int)$practicanteSeleccionado['id_practicante'])) {
    try {
        $idNotaPractica = (int)$practicanteSeleccionado['id_practica'];
        $resumenNotaFicha = notas_resumen_supervision([$idNotaPractica])[$idNotaPractica];
    } catch (Throwable $exception) {
        error_log('No fue posible cargar notas en la ficha: ' . $exception->getMessage());
    }
}
