<?php

declare(strict_types=1);

// Incluí REVISADA en los acumulados para conservar las horas y extras históricas aprobadas.

// Trasladé la autorización, POST y preparación de datos fuera del servicio/modelo.
// Este controlador compartido se ejecuta antes del layout, con parámetros del controlador por rol.
auth_requiere_rol($rolEsperado);
// Conservé privado el reporte que incorpora el resumen académico de la práctica.
header('Cache-Control: private, no-store');

$filtros = reportes_gestion_filtros();
$errores = [];
$mensajeExito = session_get('mensaje_exito_reportes');
session_forget('mensaje_exito_reportes');

// Procesé la actualización de horas solicitada desde la ficha del practicante
if (is_post() && input('accion') === 'modificar_horas_practica') {
    $token = (string) input('csrf_token', '');
    if (!csrf_validar($token, 'csrf_reportes_horas')) {
        $errores[] = 'La sesión del formulario expiró. Recarga e intenta nuevamente.';
    } else {
        $idPracticaPost = (int) input('id_practica', 0);
        $horasRequeridas = (int) input('horas_requeridas', 0);
        $horasAcumuladas = (float) input('horas_acumuladas', 0.0);

        if ($horasRequeridas < 1 || $horasRequeridas > 1000) {
            $errores[] = 'Las horas requeridas deben estar entre 1 y 1000 horas.';
        } elseif ($horasAcumuladas < 0 || $horasAcumuladas > 1000) {
            $errores[] = 'Las horas acumuladas históricas deben estar entre 0 y 1000 horas.';
        } else {
            $resultadoModificacion = reportes_actualizar_horas_practica($idPracticaPost, $horasRequeridas, $horasAcumuladas);
            if ($resultadoModificacion['ok']) {
                session_put('mensaje_exito_reportes', 'Horas actualizadas correctamente.');
                $queryRedireccion = '?' . http_build_query(array_filter([
                    'id_practica' => $idPracticaPost,
                    'desde' => (string) $filtros['desde'],
                    'hasta' => (string) $filtros['hasta'],
                    'q' => $filtros['q'] !== '' ? (string) $filtros['q'] : null,
                ]));
                redirect_to($rutaActual . $queryRedireccion);
            } else {
                $errores[] = $resultadoModificacion['error'] ?? 'No fue posible actualizar las horas.';
            }
        }
    }
}
$filas = [];
$estadosPractica = [];
$idPracticaSeleccionada = query_param('id_practica');
$idPracticaSeleccionada = ctype_digit((string) $idPracticaSeleccionada) ? (int) $idPracticaSeleccionada : null;
$detallePractica = null;
$jornadasDetalle = [];
// Preparé el desglose antes del HTML; el saldo vigente no se suma otra vez al avance.
$tiemposDetalle = null;
$ultimaModificacionHoras = null;

try {
    $estadosPractica = reportes_gestion_estados_practica();
    
    // Si el supervisor seleccionó una práctica para ver su detalle de incidencias
    if ($idPracticaSeleccionada !== null && $idPracticaSeleccionada > 0) {
        $detallePractica = reportes_gestion_practica_detalle($idPracticaSeleccionada);
        if ($detallePractica !== null) {
            $ultimaModificacionHoras = reportes_obtener_ultima_modificacion_horas($idPracticaSeleccionada);
            $tiemposDetalle = reportes_gestion_tiempos($idPracticaSeleccionada,(string)$filtros['desde'],(string)$filtros['hasta']);
            $jornadasDetalle = reportes_gestion_jornadas_practicante(
                $idPracticaSeleccionada,
                (string) $filtros['desde'],
                (string) $filtros['hasta']
            );
        } else {
            $errores[] = 'No se encontró la práctica seleccionada.';
        }
    } else {
        $filas = reportes_gestion_practicas($filtros, $rolEsperado);
    }
} catch (Throwable $exception) {
    error_log('Error en reportes de gestion: ' . $exception->getMessage());
    $errores[] = 'No fue posible cargar los reportes.';
}

$resumen = reportes_gestion_resumen($filas);

// Consulté el resumen académico global de la práctica; los filtros de asistencia no alteran la última nota semanal.
require_once SRC_PATH . '/Services/ResumenNotasService.php';
$resumenNotaReporte = null;
if ($detallePractica !== null) {
    try {
        $resumenNotaReporte = notas_resumen_supervision([(int)$idPracticaSeleccionada])[(int)$idPracticaSeleccionada];
    } catch (Throwable $exception) {
        error_log('No fue posible cargar notas en reportes: ' . $exception->getMessage());
    }
}

// Trasladé el cálculo de acumulados e incidencias fuera de la plantilla.
if ($detallePractica !== null) {

    // Cálculos y formateo para la vista de detalle individual
    $nombreCompletoDetalle = trim(implode(' ', array_filter([
        $detallePractica['nombre_1'] ?? '',
        $detallePractica['nombre_2'] ?? '',
        $detallePractica['apellido_paterno'] ?? '',
        $detallePractica['apellido_materno'] ?? '',
    ])));

    $rutCompletoDetalle = 'Sin RUT';
    if (!empty($detallePractica['rut_numero'])) {
        $rutNum = (string) $detallePractica['rut_numero'];
        $rutDv = (string) ($detallePractica['rut_dv'] ?? '');
        $rutCompletoDetalle = number_format((float) preg_replace('/\D/', '', $rutNum), 0, '', '.') . ($rutDv !== '' ? '-' . $rutDv : '');
    }

    $horasReq = (float) ($detallePractica['horas_requeridas'] ?? 0);
    $horasTotalesAcum = round((float) ($detallePractica['horas_totales_acumuladas'] ?? 0) + (float) ($detallePractica['horas_previas'] ?? 0), 2);
    $avanceDetalle = $horasReq > 0 ? min(100.0, round(($horasTotalesAcum / $horasReq) * 100, 1)) : 0.0;

    // Conteo de incidencias en las jornadas del periodo
    $totalJornadasDet = count($jornadasDetalle);
    $jornadasConObs = 0;
    $jornadasAusencias = 0;
    $jornadasAtrasos = 0;
    $jornadasHorasExtra = 0;
    $horasPeriodoDet = 0.0;

    foreach ($jornadasDetalle as $jornadaItem) {
        // Excluí cierres resueltos del contador sin borrar su historial.
        if (asistencia_jornada_observacion_pendiente($jornadaItem)) {
            $jornadasConObs++;
        }
        if ((int) ($jornadaItem['flag_ausencia_esperada'] ?? 0) === 1) {
            $jornadasAusencias++;
        }
        if ((int) ($jornadaItem['flag_atraso'] ?? 0) === 1 || (int) ($jornadaItem['flag_salida_anticipada'] ?? 0) === 1) {
            $jornadasAtrasos++;
        }
        if ((int) ($jornadaItem['flag_horas_extra'] ?? 0) === 1 || (float) ($jornadaItem['horas_extra_aprobadas'] ?? 0) > 0) {
            $jornadasHorasExtra++;
        }
        if (in_array($jornadaItem['estado_jornada'], ['VALIDA', 'OBSERVADA', 'CORREGIDA', 'REVISADA'], true)) {
            $horasPeriodoDet += ((float) $jornadaItem['horas_calculadas'] + (float) ($jornadaItem['horas_extra_aprobadas'] ?? 0));
        }
    }

}
