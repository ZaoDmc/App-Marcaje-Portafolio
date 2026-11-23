<?php

declare(strict_types=1);

// Trasladé el manejador POST desde la capa de negocio; conserva CSRF y autorización.
if (!function_exists('asistencia_gestion_procesar_post')) {
    function asistencia_gestion_procesar_post(string $rolEsperado): array
    {
        if (!is_post()) {
            return ['ok' => null, 'mensaje' => null];
        }

        if (!hash_equals(asistencia_gestion_token(), (string) input('csrf_token', ''))) {
            return ['ok' => false, 'mensaje' => 'La sesion del formulario expiro. Intenta nuevamente.'];
        }

        $accion = (string) input('accion', '');

        if ($accion === 'generar_ausencias_esperadas') {
            // Restringí generación masiva a ADMIN; ocultar el botón no autoriza un POST directo.
            if (auth_rol_activo() !== 'ADMIN') {
                return ['ok' => false, 'mensaje' => 'Solo ADMIN puede generar ausencias esperadas.'];
            }
            if ((string) input('confirmar_ausencias', '') !== '1') {
                return ['ok' => false, 'mensaje' => 'Debe confirmar la generacion de ausencias esperadas.'];
            }

            $resultadoAusencias = ausencias_esperadas_generar(
                (string) input('desde', ''),
                (string) input('hasta', ''),
                auth_usuario_actual_id()
            );

            if ($resultadoAusencias['ok'] ?? false) {
                return [
                    'ok' => true,
                    'mensaje' => 'Ausencias esperadas generadas: '
                        . (int) $resultadoAusencias['insertadas']
                        . '. Omitidas: '
                        . (int) $resultadoAusencias['omitidas']
                        . '.',
                ];
            }

            return [
                'ok' => false,
                'mensaje' => implode(' ', $resultadoAusencias['errores'] ?? ['No fue posible generar ausencias esperadas.']),
            ];
        }

        $idJornada = (int) input('id_jornada', 0);
        $jornadaDetalle = asistencia_gestion_jornada_detalle($idJornada);

        if ($jornadaDetalle === null) {
            return ['ok' => false, 'mensaje' => 'Jornada no encontrada.'];
        }

        if (!asistencia_gestion_puede_operar_jornada($jornadaDetalle)) {
            return ['ok' => false, 'mensaje' => 'No puedes operar sobre esta jornada con el rol activo.'];
        }

        if ($accion === 'anular_marca') {
            $idMarca = (int) input('id_marca', 0);
            $marca = asistencia_marca_por_id($idMarca);

            if ($marca === null || (int) $marca['id_jornada'] !== $idJornada) {
                return ['ok' => false, 'mensaje' => 'Marca no encontrada para la jornada seleccionada.'];
            }

            return asistencia_anular_marca($idMarca, (string) input('motivo', ''), auth_usuario_actual_id());
        }

        if ($accion === 'registrar_marca') {
            return asistencia_gestion_registrar_marca_correctiva(
                $idJornada,
                (string) input('codigo_marca', ''),
                // La vista envía fecha y hora 24 h por separado; se unen en el formato 'Y-m-d H:i' que ya valida el servicio.
                trim((string) input('fecha_hora_fecha', '') . ' ' . (string) input('fecha_hora_hora', '')),
                (string) input('observacion', '')
            );
        }

        if ($accion === 'cambiar_estado') {
            return asistencia_gestion_cambiar_estado(
                $idJornada,
                (string) input('estado_jornada', ''),
                (string) input('motivo', '')
            );
        }

        if ($accion === 'aprobar_horas_extra') {
            return asistencia_gestion_aprobar_horas_extra(
                $idJornada,
                (string) input('horas_extra_aprobadas', ''),
                (string) input('motivo', '')
            );
        }

        if ($accion === 'resolver_ausencia_esperada') {
            return asistencia_gestion_resolver_ausencia_esperada(
                $idJornada,
                (string) input('resultado_ausencia', ''),
                (string) input('motivo', '')
            );
        }

        if ($accion === 'anular_jornada') {
            if ($rolEsperado !== 'ADMIN') {
                return ['ok' => false, 'mensaje' => 'Solo ADMIN puede anular una jornada completa.'];
            }

            if ((string) input('confirmar_anulacion', '') !== '1') {
                return ['ok' => false, 'mensaje' => 'Debe confirmar la anulacion completa de la jornada.'];
            }

            return asistencia_gestion_anular_jornada($idJornada, (string) input('motivo', ''));
        }

        if ($accion === 'registrar_bloque_area') {
            $resultadoBloque = areas_jornada_registrar_bloque(
                $jornadaDetalle,
                (int) input('id_area', 0),
                // Mismo criterio: fecha y hora 24 h llegan separadas desde la vista.
                trim((string) input('hora_inicio_bloque_fecha', '') . ' ' . (string) input('hora_inicio_bloque_hora', '')),
                trim((string) input('hora_fin_bloque_fecha', '') . ' ' . (string) input('hora_fin_bloque_hora', ''))
            );

            if ($resultadoBloque['ok'] ?? false) {
                return ['ok' => true, 'mensaje' => 'Bloque de area registrado.'];
            }

            return [
                'ok' => false,
                'mensaje' => implode(' ', $resultadoBloque['errores'] ?? ['No fue posible registrar el bloque de area.']),
            ];
        }

        return ['ok' => false, 'mensaje' => 'Accion no reconocida.'];
    }
}


// Trasladé la autorización, POST y preparación de datos fuera del servicio/modelo.
// Este controlador compartido se ejecuta antes del layout, con parámetros del controlador por rol.
auth_requiere_rol($rolEsperado);

$token = asistencia_gestion_token();
$filtros = asistencia_gestion_filtros();
$resultadoPost = asistencia_gestion_procesar_post($rolEsperado);
$idJornadaSeleccionada = (int) query_param('id_jornada', (int) input('id_jornada', 0));

$errores = [];
$mensajeExito = null;

if ($resultadoPost['ok'] === true) {
    $mensajeExito = (string) $resultadoPost['mensaje'];
    $idJornadaSeleccionada = (int) input('id_jornada', $idJornadaSeleccionada);
} elseif ($resultadoPost['ok'] === false) {
    $errores[] = (string) $resultadoPost['mensaje'];
    $idJornadaSeleccionada = (int) input('id_jornada', $idJornadaSeleccionada);
}

try {
    $totalJornadas = asistencia_gestion_jornadas_conteo($filtros);
    $paginaActual = max(1, (int) ($filtros['pagina'] ?? 1));
    $totalPaginas = max(1, (int) ceil($totalJornadas / 10));
    $jornadas = asistencia_gestion_jornadas($filtros, 10);

    // Calculé conteos por estado para las tarjetas métricas y pestañas interactivas de asistencia.
    $filtrosBaseConteo = $filtros;
    unset($filtrosBaseConteo['estado'], $filtrosBaseConteo['pagina']);
    $conteosEstado = [
        ''           => asistencia_gestion_jornadas_conteo($filtrosBaseConteo),
        'VALIDA'     => asistencia_gestion_jornadas_conteo(array_merge($filtrosBaseConteo, ['estado' => 'VALIDA'])),
        'OBSERVADA'  => asistencia_gestion_jornadas_conteo(array_merge($filtrosBaseConteo, ['estado' => 'OBSERVADA'])),
        'INCOMPLETA' => asistencia_gestion_jornadas_conteo(array_merge($filtrosBaseConteo, ['estado' => 'INCOMPLETA'])),
        'CORREGIDA'  => asistencia_gestion_jornadas_conteo(array_merge($filtrosBaseConteo, ['estado' => 'CORREGIDA'])),
    ];

    $jornadaSeleccionada = $idJornadaSeleccionada > 0 ? asistencia_gestion_jornada_detalle($idJornadaSeleccionada) : null;
    $marcas = $jornadaSeleccionada !== null ? asistencia_gestion_marcas_jornada((int) $jornadaSeleccionada['id_jornada']) : [];
    $bloquesArea = $jornadaSeleccionada !== null ? areas_jornada_bloques((int) $jornadaSeleccionada['id_jornada']) : [];
    $areasCatalogo = areas_practicante_catalogo();
    $marcasVigentes = array_values(array_filter($marcas, static fn (array $marca): bool => (int) $marca['activo'] === 1 && (int) $marca['anulada'] === 0));
    $ultimaMarca = asistencia_ultima_marca_vigente($marcasVigentes);
    $codigosPermitidos = asistencia_siguientes_marcas_permitidas($ultimaMarca);
    $tiposMarca = asistencia_gestion_tipos_marca();
    $puedeOperar = $jornadaSeleccionada !== null && asistencia_gestion_puede_operar_jornada($jornadaSeleccionada);
    $horasExtraSugeridas = $jornadaSeleccionada !== null ? asistencia_gestion_horas_extra_sugeridas($jornadaSeleccionada) : 0.0;

    // Obtenemos el horario pactado vigente para el día de la jornada seleccionada para mostrarlo en el panel de detalle
    $horarioPactadoDia = $jornadaSeleccionada !== null
        ? asistencia_horario_vigente_dia((int) $jornadaSeleccionada['id_practica'], (string) $jornadaSeleccionada['fecha'])
        : null;

} catch (Throwable $exception) {
    error_log('Error en gestion de asistencia: ' . $exception->getMessage());
    $errores[] = 'No fue posible cargar la gestion de asistencia.';
    $totalJornadas = 0;
    $paginaActual = 1;
    $totalPaginas = 1;
    $jornadas = [];
    $conteosEstado = ['' => 0, 'VALIDA' => 0, 'OBSERVADA' => 0, 'INCOMPLETA' => 0, 'CORREGIDA' => 0];
    $jornadaSeleccionada = null;
    $marcas = [];
    $bloquesArea = [];
    $areasCatalogo = [];
    $marcasVigentes = [];
    $codigosPermitidos = [];
    $tiposMarca = [];
    $puedeOperar = false;
    $horasExtraSugeridas = 0.0;
    $horarioPactadoDia = null;
}

$fechaHoraDefault = $jornadaSeleccionada !== null
    ? (string) $jornadaSeleccionada['fecha'] . 'T09:00'
    : date('Y-m-d\TH:i');

// Preparé los desgloses antes del HTML: el cálculo puede consultar marcas y
// horarios; la plantilla solo consume los resultados, sin acceder a la BBDD.
$horasVistaPorJornada = [];
$vistaHorasSel = null;
// Resolví el corte antes del HTML para que la vista no consulte la base.
$politicaTiempoActiva=tiempo_politica()!==null;
try {
    foreach ($jornadas as $jornadaParaVista) {
        $horasVistaPorJornada[(int) $jornadaParaVista['id_jornada']] = asistencia_gestion_calcular_horas_vista($jornadaParaVista);
    }
    if ($jornadaSeleccionada !== null) {
        $vistaHorasSel = asistencia_gestion_calcular_horas_vista($jornadaSeleccionada);
    }
} catch (Throwable $exception) {
    error_log('Error al preparar desglose de asistencia: ' . $exception->getMessage());
    $errores[] = 'No fue posible cargar el desglose de horas.';
    $jornadas = [];
    $jornadaSeleccionada = null;
}
