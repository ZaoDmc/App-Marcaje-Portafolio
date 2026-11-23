<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/AuthService.php';
require_once __DIR__ . '/ExcepcionesHorarioService.php';
// Agregué políticas de recuperación sin reutilizar aprobación de extras.
require_once __DIR__ . '/RecuperacionesService.php';
// Agregué crédito excepcional independiente de extras históricas y recuperaciones.
require_once __DIR__ . '/TiempoExcepcionalService.php';
// Agregué superposición diaria de colación aprobada, sin escribir el horario semanal.
require_once __DIR__ . '/ColacionService.php';

/*
|--------------------------------------------------------------------------
| Reglas de negocio para asistencia
|--------------------------------------------------------------------------
|
| Este archivo concentra validaciones de secuencia de marcas, cálculo base de
| horas, estados de jornada y anulación lógica granular. No renderiza vistas
| ni decide permisos de pantalla; esas capas consumirán estas funciones.
|
*/

if (!function_exists('asistencia_tipos_marca')) {
    function asistencia_tipos_marca(): array
    {
        return [
            'ENTRADA',
            'ENTRADA_COLACION',
            'SALIDA_COLACION',
            'SALIDA',
            // Agregué interrupciones por permiso sin reutilizar la colación.
            'SALIDA_PERMISO',
            'REGRESO_PERMISO',
        ];
    }
}


if (!function_exists('asistencia_estados_jornada')) {
    function asistencia_estados_jornada(): array
    {
        // estado_jornada es VARCHAR y se valida en backend según la decisión oficial.
        return [
            'VALIDA',
            'OBSERVADA',
            'INCOMPLETA',
            'ANULADA',
            'CORREGIDA',
            // Incluí REVISADA en los estados y sus horas reconocidas, conservando extras aprobadas.
            'REVISADA',
            'PENDIENTE_REVISION',
        ];
    }
}

if (!function_exists('asistencia_estados_suman_horas')) {
    function asistencia_estados_suman_horas(): array
    {
        return [
            'VALIDA',
            'OBSERVADA',
            'CORREGIDA',
            // Incluí REVISADA en los estados y sus horas reconocidas, conservando extras aprobadas.
            'REVISADA',
        ];
    }
}

// Agregué criterios comunes para que el historial no reabra observaciones ya resueltas.
if (!function_exists('asistencia_jornada_revision_resuelta')) {
    function asistencia_jornada_revision_resuelta(array $jornada): bool
    {
        return in_array($jornada['estado_jornada'] ?? '', ['CORREGIDA', 'REVISADA'], true);
    }
}

if (!function_exists('asistencia_jornada_observacion_pendiente')) {
    function asistencia_jornada_observacion_pendiente(array $jornada): bool
    {
        return !asistencia_jornada_revision_resuelta($jornada)
            && (in_array($jornada['estado_jornada'] ?? '', ['OBSERVADA', 'PENDIENTE_REVISION'], true)
                || trim((string) ($jornada['observacion'] ?? '')) !== '');
    }
}

if (!function_exists('asistencia_estado_valido')) {
    function asistencia_estado_valido(string $estado): bool
    {
        return in_array($estado, asistencia_estados_jornada(), true);
    }
}

if (!function_exists('asistencia_estado_suma_horas')) {
    function asistencia_estado_suma_horas(string $estado): bool
    {
        return in_array($estado, asistencia_estados_suman_horas(), true);
    }
}

// Agregué el criterio común de horas reconocidas: los estados no acumulables
// no suman y las extras se incluyen únicamente cuando están aprobadas.
if (!function_exists('asistencia_horas_reconocidas')) {
    function asistencia_horas_reconocidas(array $jornada): float
    {
        return asistencia_estado_suma_horas((string) ($jornada['estado_jornada'] ?? ''))
            ? round((float) ($jornada['horas_calculadas'] ?? 0) + (float) ($jornada['horas_extra_aprobadas'] ?? 0), 2)
            : 0.0;
    }
}

// Agregué la misma regla como expresión SQL para usarla en agregaciones sin
// duplicar estados ni omitir extras. El alias solo admite identificadores.
if (!function_exists('asistencia_sql_horas_reconocidas')) {
    function asistencia_sql_horas_reconocidas(string $alias = ''): string
    {
        if ($alias !== '' && !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $alias)) {
            throw new InvalidArgumentException('Alias de asistencia no válido.');
        }
        $prefix = $alias !== '' ? $alias . '.' : '';
        $states = "'" . implode("', '", asistencia_estados_suman_horas()) . "'";
        return "CASE WHEN {$prefix}estado_jornada IN ({$states}) THEN COALESCE({$prefix}horas_calculadas, 0) + COALESCE({$prefix}horas_extra_aprobadas, 0) ELSE 0 END";
    }
}

if (!function_exists('asistencia_siguientes_marcas_permitidas')) {
    function asistencia_siguientes_marcas_permitidas(?string $ultimaMarca): array
    {
        return match ($ultimaMarca) {
            null => ['ENTRADA'],
            'ENTRADA' => ['ENTRADA_COLACION', 'SALIDA', 'SALIDA_PERMISO'],
            'ENTRADA_COLACION' => ['SALIDA_COLACION', 'SALIDA'],
            'SALIDA_COLACION' => ['SALIDA', 'SALIDA_PERMISO'],
            'SALIDA_PERMISO' => ['REGRESO_PERMISO'],
            'REGRESO_PERMISO' => ['ENTRADA_COLACION', 'SALIDA'],
            'SALIDA' => [],
            default => [],
        };
    }
}

if (!function_exists('asistencia_ultima_marca_vigente')) {
    function asistencia_ultima_marca_vigente(array $marcas): ?string
    {
        $vigentes = array_values(array_filter($marcas, static function (array $marca): bool {
            return (int) ($marca['activo'] ?? 1) === 1 && (int) ($marca['anulada'] ?? 0) === 0;
        }));

        if ($vigentes === []) {
            return null;
        }

        usort($vigentes, static function (array $a, array $b): int {
            return strcmp((string) ($a['fecha_hora'] ?? ''), (string) ($b['fecha_hora'] ?? ''));
        });

        $ultima = $vigentes[array_key_last($vigentes)];

        return isset($ultima['codigo']) ? (string) $ultima['codigo'] : null;
    }
}

if (!function_exists('asistencia_validar_secuencia')) {
    function asistencia_validar_secuencia(array $marcasVigentes, string $codigoNuevaMarca): array
    {
        if (!in_array($codigoNuevaMarca, asistencia_tipos_marca(), true)) {
            return [
                'ok' => false,
                'mensaje' => 'Tipo de marca no reconocido.',
                'permitidas' => [],
            ];
        }

        $ultimaMarca = asistencia_ultima_marca_vigente($marcasVigentes);
        $permitidas = asistencia_siguientes_marcas_permitidas($ultimaMarca);
        // Excluí códigos ya registrados para conservar una marca vigente por tipo y día.
        $permitidas = array_values(array_filter($permitidas, static fn(string $codigo): bool => asistencia_marca_por_codigo($marcasVigentes, $codigo) === null));

        return [
            'ok' => in_array($codigoNuevaMarca, $permitidas, true),
            'mensaje' => in_array($codigoNuevaMarca, $permitidas, true)
                ? 'Secuencia válida.'
                : 'La marca solicitada no corresponde a la secuencia permitida.',
            'permitidas' => $permitidas,
            'ultima_marca' => $ultimaMarca,
        ];
    }
}

if (!function_exists('asistencia_marca_por_codigo')) {
    function asistencia_marca_por_codigo(array $marcas, string $codigo): ?array
    {
        foreach ($marcas as $marca) {
            if ((string) ($marca['codigo'] ?? '') === $codigo
                && (int) ($marca['activo'] ?? 1) === 1
                && (int) ($marca['anulada'] ?? 0) === 0) {
                return $marca;
            }
        }

        return null;
    }
}

if (!function_exists('asistencia_horas_entre')) {
    function asistencia_horas_entre(string $inicio, string $fin): float
    {
        $inicioTimestamp = strtotime($inicio);
        $finTimestamp = strtotime($fin);

        if ($inicioTimestamp === false || $finTimestamp === false || $finTimestamp <= $inicioTimestamp) {
            return 0.0;
        }

        return round(($finTimestamp - $inicioTimestamp) / 3600, 2);
    }
}

// Agregué tramos efectivamente trabajados: las pausas de permiso y colación no se confunden.
if (!function_exists('asistencia_tramos_trabajados')) {
    function asistencia_tramos_trabajados(array $marcas, ?string $hasta = null): array
    {
        $marcas = array_values(array_filter($marcas, static fn(array $m): bool => (int) ($m['activo'] ?? 1) === 1 && (int) ($m['anulada'] ?? 0) === 0));
        usort($marcas, static fn(array $a, array $b): int => strcmp($a['fecha_hora'], $b['fecha_hora']));
        $inicio = null;
        $tramos = [];
        foreach ($marcas as $marca) {
            $hora = strtotime((string) $marca['fecha_hora']);
            if ($hora === false) { continue; }
            if (in_array($marca['codigo'], ['ENTRADA', 'SALIDA_COLACION', 'REGRESO_PERMISO'], true)) {
                $inicio ??= $hora;
            } elseif (in_array($marca['codigo'], ['SALIDA', 'ENTRADA_COLACION', 'SALIDA_PERMISO'], true) && $inicio !== null) {
                if ($hora > $inicio) { $tramos[] = [$inicio, $hora]; }
                $inicio = null;
            }
        }
        // Cerré provisionalmente una marca abierta solo para detectar cruces al revisar una solicitud.
        $fin = $hasta !== null ? strtotime($hasta) : false;
        if ($inicio !== null && $fin !== false && $fin > $inicio) { $tramos[] = [$inicio, $fin]; }
        return $tramos;
    }
}

// Agregué consulta del permiso diario; una solicitud pendiente no equivale a autorización.
if (!function_exists('asistencia_permiso_del_dia')) {
    function asistencia_permiso_del_dia(int $idPractica, string $fecha): ?array
    {
        $stmt = db()->prepare('SELECT * FROM permisos_practicante WHERE id_practica = :practica AND fecha_ausencia = :fecha AND estado IN ("PENDIENTE", "APROBADO") ORDER BY id_permiso DESC LIMIT 1');
        $stmt->execute(['practica' => $idPractica, 'fecha' => $fecha]);
        $permiso = $stmt->fetch();
        return is_array($permiso) ? $permiso : null;
    }
}

// Agregué detección de tiempo trabajado dentro de la ausencia, incluyendo una entrada aún abierta.
if (!function_exists('asistencia_permiso_cruza_marcas')) {
    function asistencia_permiso_cruza_marcas(array $permiso, array $marcas, ?string $hasta = null): bool
    {
        // Una secuencia incoherente no demuestra ausencia; exigí resolverla antes de aprobar.
        $ordenadas = array_values(array_filter($marcas, static fn(array $m): bool => (int) ($m['activo'] ?? 1) === 1 && (int) ($m['anulada'] ?? 0) === 0));
        usort($ordenadas, static fn(array $a, array $b): int => strcmp($a['fecha_hora'], $b['fecha_hora']));
        $previas = [];
        foreach ($ordenadas as $marca) {
            if (!asistencia_validar_secuencia($previas, $marca['codigo'])['ok']) { return true; }
            $previas[] = $marca;
        }
        $fecha = (string) $permiso['fecha_ausencia'];
        $esParcial = ($permiso['tipo_ausencia'] ?? 'COMPLETA') === 'PARCIAL';
        $inicio = strtotime($fecha . ' ' . ($esParcial ? $permiso['hora_inicio_ausencia'] : '00:00:00'));
        $fin = strtotime($fecha . ' ' . ($esParcial ? $permiso['hora_fin_ausencia'] : '23:59:59'));
        foreach (asistencia_tramos_trabajados($marcas, $hasta ?? date('Y-m-d H:i:s')) as [$a, $b]) {
            if ($esParcial) {
                // Trunqué al minuto en ausencias parciales porque las marcas en vivo guardan segundos que la interfaz no muestra.
                $a = intdiv($a, 60) * 60;
                $b = intdiv($b, 60) * 60;
            }
            if (min($b, $fin) > max($a, $inicio)) { return true; }
        }
        return false;
    }
}

// Agregué opciones de marcaje según solicitud, horario autorizado y marcas vigentes; la misma regla protege el POST.
if (!function_exists('asistencia_marcas_disponibles')) {
    function asistencia_marcas_disponibles(array $marcas, ?array $permiso, ?string $ahora = null): array
    {
        $ahoraTimestamp = strtotime($ahora ?? date('Y-m-d H:i:s'));
        $opciones = asistencia_siguientes_marcas_permitidas(asistencia_ultima_marca_vigente($marcas));
        return array_values(array_filter($opciones, static function (string $codigo) use ($marcas, $permiso, $ahoraTimestamp): bool {
            if (asistencia_marca_por_codigo($marcas, $codigo) !== null) { return false; }
            if ($codigo === 'SALIDA_PERMISO') {
                // Solo habilité la pausa durante una ausencia parcial aprobada; la salida final usa SALIDA.
                if ($permiso === null || ($permiso['estado'] ?? '') !== 'APROBADO' || ($permiso['tipo_ausencia'] ?? '') !== 'PARCIAL') {
                    return false;
                }
                $fecha = (string) ($permiso['fecha_ausencia'] ?? '');
                $inicio = strtotime($fecha . ' ' . (string) ($permiso['hora_inicio_ausencia'] ?? ''));
                $fin = strtotime($fecha . ' ' . (string) ($permiso['hora_fin_ausencia'] ?? ''));

                // Oculté el botón antes de la hora de permiso y después de su ventana autorizada.
                return $inicio !== false && $fin !== false && $ahoraTimestamp !== false
                    && $ahoraTimestamp >= $inicio && $ahoraTimestamp < $fin
                    && ($permiso['nuevo_horario_inicio'] ?? '') < ($permiso['hora_inicio_ausencia'] ?? '')
                    && ($permiso['hora_fin_ausencia'] ?? '') < ($permiso['nuevo_horario_fin'] ?? '');
            }
            // Siempre permití regresar de una pausa ya marcada, aunque se rechace la solicitud después.
            if ($codigo === 'REGRESO_PERMISO') { return true; }
            return !($codigo === 'ENTRADA' && ($permiso['estado'] ?? '') === 'APROBADO' && ($permiso['tipo_ausencia'] ?? '') === 'COMPLETA');
        }));
    }
}

if (!function_exists('asistencia_calcular_resumen_jornada')) {
    /**
     * Calcula el resumen de una jornada evaluando las marcas registradas y el horario asignado.
     * 
     * Reglas aplicadas:
     * 1. Determina las horas brutas entre ENTRADA y SALIDA.
     * 2. Si no se registró colación, cuenta el tiempo trabajado dentro del turno
     *    sin descontar una pausa supuesta, según la regla confirmada en fase 0.
     * 3. Si hay marcas fuera de la ventana de horario (ej: llega antes o sale después), calcula
     *    las horas base cumplidas dentro de turno y marca el excedente como horas extra pendientes.
     * 
     * @param array $marcasVigentes Lista de marcas no anuladas de la jornada.
     * @param array|null $horario Detalle del horario pactado vigente para el día.
     * @return array Resumen con estado, horas calculadas (base acumulable), horas extra sugeridas y banderas.
     */
    function asistencia_calcular_resumen_jornada(array $marcasVigentes, ?array $horario = null): array
    {
        // Obtenemos las marcas principales del practicante para la jornada
        $entrada = asistencia_marca_por_codigo($marcasVigentes, 'ENTRADA');
        $entradaColacion = asistencia_marca_por_codigo($marcasVigentes, 'ENTRADA_COLACION');
        $salidaColacion = asistencia_marca_por_codigo($marcasVigentes, 'SALIDA_COLACION');
        $salida = asistencia_marca_por_codigo($marcasVigentes, 'SALIDA');
        // Exigí cerrar una salida por permiso; no se inventa una hora de regreso.
        $salidaPermiso = asistencia_marca_por_codigo($marcasVigentes, 'SALIDA_PERMISO');
        $regresoPermiso = asistencia_marca_por_codigo($marcasVigentes, 'REGRESO_PERMISO');

        // Si falta la entrada o la salida, la jornada queda incompleta y no acumula horas
        if ($entrada === null || $salida === null || (($salidaPermiso === null) !== ($regresoPermiso === null))) {
            return [
                'estado_jornada' => 'INCOMPLETA',
                'hora_entrada' => $entrada['fecha_hora'] ?? null,
                'hora_salida' => null,
                'horas_calculadas' => 0.0,
                'horas_extra_sugeridas' => 0.0,
                'flag_atraso' => 0,
                'flag_salida_anticipada' => 0,
                'flag_asistencia_extraordinaria' => $horario === null ? 1 : 0,
                'flag_horas_extra' => 0,
                'observaciones' => ['Falta entrada, salida final o regreso de permiso.'],
            ];
        }

        // Horas brutas transcurridas entre la primera entrada y la última salida
        $horasBrutas = asistencia_horas_entre((string) $entrada['fecha_hora'], (string) $salida['fecha_hora']);
        $horasColacion = 0.0;
        $colacionCompleta = $entradaColacion !== null && $salidaColacion !== null;
        $observaciones = [];
        $estado = 'VALIDA';

        // Verificamos si marcó colación completa o parcial
        if ($colacionCompleta) {
            $horasColacion = asistencia_horas_entre((string) $entradaColacion['fecha_hora'], (string) $salidaColacion['fecha_hora']);
        } elseif ($entradaColacion !== null || $salidaColacion !== null) {
            $estado = 'OBSERVADA';
            $observaciones[] = 'Colación incompleta.';
        }
        // Si la jornada dura 6 horas o más y no marcó colación, se deja observada para revisión de supervisor
        // Excluí la pausa por permiso al evaluar una jornada de seis horas efectivamente trabajadas.
        $duracionParaColacion = $salidaPermiso !== null && $regresoPermiso !== null
            ? max(0.0, $horasBrutas - asistencia_horas_entre($salidaPermiso['fecha_hora'], $regresoPermiso['fecha_hora'])) : $horasBrutas;
        if (!$colacionCompleta && $duracionParaColacion >= 6.0) {
            $estado = 'OBSERVADA';
            $observaciones[] = 'Jornada de 6 horas o más sin colación registrada; requiere validación.';
        }

        // Evaluamos el desfase con respecto al horario pactado
        $evaluacionHorario = asistencia_evaluar_flags_horario(
            (string) $entrada['fecha_hora'],
            (string) $salida['fecha_hora'],
            $horario,
            $horasColacion,
            $colacionCompleta
        );

        // Calculé ambos tramos de trabajo para no sumar la interrupción ni descontar dos veces colación.
        if ($salidaPermiso !== null && $regresoPermiso !== null) {
            $previas = [];
            $ordenadas = $marcasVigentes;
            usort($ordenadas, static fn(array $a, array $b): int => strcmp($a['fecha_hora'], $b['fecha_hora']));
            foreach ($ordenadas as $marca) {
                if ((int) ($marca['activo'] ?? 1) !== 1 || (int) ($marca['anulada'] ?? 0) !== 0) { continue; }
                if (!asistencia_validar_secuencia($previas, $marca['codigo'])['ok']) {
                    $estado = 'INCOMPLETA';
                    $observaciones[] = 'Secuencia de interrupción inválida; requiere corrección.';
                }
                $previas[] = $marca;
            }
            $fecha = substr($entrada['fecha_hora'], 0, 10);
            $inicioTurno = isset($horario['entrada_esperada']) ? strtotime($fecha . ' ' . $horario['entrada_esperada']) : null;
            $finTurno = isset($horario['salida_esperada']) ? strtotime($fecha . ' ' . $horario['salida_esperada']) : null;
            $total = 0;
            $base = 0;
            foreach (asistencia_tramos_trabajados($marcasVigentes) as [$a, $b]) {
                $total += $b - $a;
                $base += $inicioTurno !== null && $finTurno !== null ? max(0, min($b, $finTurno) - max($a, $inicioTurno)) : $b - $a;
            }
            $extra = round(max(0, $total - $base) / 3600, 2);
            $evaluacionHorario['horas_base_cumplidas'] = round($base / 3600, 2);
            $evaluacionHorario['horas_extra_sugeridas'] = $extra > 0.25 ? $extra : 0.0;
            $evaluacionHorario['flag_horas_extra'] = $extra > 0.25 ? 1 : 0;
        }


        if ($evaluacionHorario['flag_atraso'] === 1) {
            $observaciones[] = 'Atraso detectado.';
        }

        if ($evaluacionHorario['flag_salida_anticipada'] === 1) {
            $observaciones[] = 'Salida anticipada detectada.';
        }

        // Regla de horas_extra_sugeridas bajo condición mayor a 0,25 hrs:
        // Si el practicante tiene un excedente menor o igual a 0,25 hrs (15 min, ej: 0,10 hrs),
        // no se activa flag_horas_extra y la jornada permanece como VALIDA.
        // Solo si las horas extra superan 0,25 hrs se marca la jornada como OBSERVADA para revisión del supervisor.
        if ($evaluacionHorario['flag_horas_extra'] === 1 && $evaluacionHorario['horas_extra_sugeridas'] > 0.25) {
            $observaciones[] = 'Horas fuera de horario o sobre lo planificado (> 0.25 hrs) requieren confirmación.';
            // Si hay horas extra pendientes que superan el margen de 15 min, la jornada queda OBSERVADA
            if ($estado === 'VALIDA') {
                $estado = 'OBSERVADA';
            }
        }

        // Horas base reconocidas automáticamente (tope de turno dentro de horario pactado)
        $horasCalculadas = $evaluacionHorario['horas_base_cumplidas'];

        return [
            'estado_jornada' => $estado,
            'hora_entrada' => $entrada['fecha_hora'],
            'hora_salida' => $salida['fecha_hora'],
            'horas_calculadas' => asistencia_estado_suma_horas($estado) ? $horasCalculadas : 0.0,
            'horas_extra_sugeridas' => $evaluacionHorario['horas_extra_sugeridas'],
            'flag_atraso' => $evaluacionHorario['flag_atraso'],
            'flag_salida_anticipada' => $evaluacionHorario['flag_salida_anticipada'],
            'flag_asistencia_extraordinaria' => $evaluacionHorario['flag_asistencia_extraordinaria'],
            'flag_horas_extra' => $evaluacionHorario['flag_horas_extra'],
            'observaciones' => $observaciones,
        ];
    }
}

if (!function_exists('asistencia_evaluar_flags_horario')) {
    /**
     * Evalúa las banderas de horario (atraso, salida anticipada, horas extra) y calcula
     * con precisión las horas base cumplidas dentro del turno y las horas extra detectadas.
     * 
     * @param string $fechaHoraEntrada Fecha y hora de entrada real.
     * @param string $fechaHoraSalida Fecha y hora de salida real.
     * @param array|null $horario Horario pactado para el día.
     * @param float $horasColacion Tiempo real de colación tomado.
     * @param bool $colacionCompleta Indica si marcó salida y regreso de colación.
     * @return array Banderas y desglose de horas base vs horas extra.
     */
    function asistencia_evaluar_flags_horario(
        string $fechaHoraEntrada,
        string $fechaHoraSalida,
        ?array $horario,
        float $horasColacion = 0.0,
        bool $colacionCompleta = false
    ): array {
        // Si no tiene horario configurado, todas las horas se consideran extraordinarias
        if ($horario === null) {
            $horasBrutas = asistencia_horas_entre($fechaHoraEntrada, $fechaHoraSalida);
            $horasNetas = max(0.0, round($horasBrutas - $horasColacion, 2));

            return [
                'flag_atraso' => 0,
                'flag_salida_anticipada' => 0,
                'flag_asistencia_extraordinaria' => 1,
                'flag_horas_extra' => 0,
                'horas_base_cumplidas' => $horasNetas,
                'horas_extra_sugeridas' => 0.0,
            ];
        }

        $fecha = substr($fechaHoraEntrada, 0, 10);
        $entradaEsperada = isset($horario['entrada_esperada']) ? strtotime($fecha . ' ' . $horario['entrada_esperada']) : false;
        $salidaEsperada = isset($horario['salida_esperada']) ? strtotime($fecha . ' ' . $horario['salida_esperada']) : false;
        $entradaReal = strtotime($fechaHoraEntrada);
        $salidaReal = strtotime($fechaHoraSalida);

        // Horas netas totales trabajadas por el practicante
        $horasBrutasTrabajadas = asistencia_horas_entre($fechaHoraEntrada, $fechaHoraSalida);
        $horasTotalesTrabajadas = max(0.0, round($horasBrutasTrabajadas - $horasColacion, 2));

        // Calculamos la intersección o traslape dentro del turno pactado
        $horasBaseCumplidas = $horasTotalesTrabajadas;
        $horasExtraSugeridas = 0.0;
        $flagHorasExtra = 0;

        if ($entradaEsperada !== false && $salidaEsperada !== false && $salidaReal > $entradaReal) {
            // Ventana de turno pactado
            $inicioTurno = max($entradaReal, $entradaEsperada);
            $finTurno = min($salidaReal, $salidaEsperada);

            // Horas brutas dentro del horario pactado
            $horasDentroTurnoBrutas = $finTurno > $inicioTurno ? max(0.0, round(($finTurno - $inicioTurno) / 3600, 2)) : 0.0;

            if ($colacionCompleta || $horasColacion > 0) {
                // Si marcó colación, se descuenta de las horas trabajadas dentro de turno
                $horasBaseCumplidas = max(0.0, round($horasDentroTurnoBrutas - $horasColacion, 2));
            } else {
                // Cambié el tope planificado: sin marcas de colación se reconoce
                // el tiempo dentro del turno, sin descontar una hora ficticia
                // ni convertirla en extra. Los excesos fuera del turno siguen
                // sujetos a la tolerancia y aprobación existentes.
                $horasBaseCumplidas = $horasDentroTurnoBrutas;
            }

            // Horas trabajadas fuera del turno o excedente respecto a las horas base
            $excedenteBruto = max(0.0, round($horasTotalesTrabajadas - $horasBaseCumplidas, 2));

            // Regla de horas_extra_sugeridas bajo condición menor o igual a 0,25 hrs:
            // Margen de gracia / tolerancia: Si el excedente es <= 0.25 hrs (ej: 0.10 hrs / 6 minutos),
            // se considera dentro del margen normal del turno. No se activa flag_horas_extra y no
            // se acumulan como horas extra sin validar, manteniéndose el tope de horas base.
            if ($excedenteBruto > 0.25) {
                // Supera los 15 minutos de tolerancia -> Se activa la bandera para validación de supervisor
                $horasExtraSugeridas = $excedenteBruto;
                $flagHorasExtra = 1;
            } else {
                // Excedente menor o igual a 15 min -> Se tolera, no requiere aprobación ni genera horas extra
                $horasExtraSugeridas = 0.0;
                $flagHorasExtra = 0;
            }
        }

        // Evaluamos atraso (10 min de tolerancia) y salida anticipada
        $flagAtraso = $entradaEsperada !== false && $entradaReal !== false && ($entradaReal - $entradaEsperada) >= 600 ? 1 : 0;
        $flagSalidaAnticipada = $salidaEsperada !== false && $salidaReal !== false && $salidaReal < $salidaEsperada ? 1 : 0;

        return [
            'flag_atraso' => $flagAtraso,
            'flag_salida_anticipada' => $flagSalidaAnticipada,
            'flag_asistencia_extraordinaria' => 0,
            'flag_horas_extra' => $flagHorasExtra,
            'horas_base_cumplidas' => $horasBaseCumplidas,
            'horas_extra_sugeridas' => $horasExtraSugeridas,
        ];
    }
}

if (!function_exists('asistencia_tipo_marca_por_codigo')) {
    function asistencia_tipo_marca_por_codigo(string $codigo): ?array
    {
        $statement = db()->prepare(
            'SELECT *
             FROM tipos_marca
             WHERE codigo = :codigo
               AND activo = 1
             LIMIT 1'
        );
        $statement->execute(['codigo' => $codigo]);

        $tipo = $statement->fetch();

        return is_array($tipo) ? $tipo : null;
    }
}

if (!function_exists('asistencia_jornada_por_id')) {
    function asistencia_jornada_por_id(int $idJornada): ?array
    {
        $statement = db()->prepare(
            'SELECT *
             FROM jornadas_asistencia
             WHERE id_jornada = :id_jornada
             LIMIT 1'
        );
        $statement->execute(['id_jornada' => $idJornada]);

        $jornada = $statement->fetch();

        return is_array($jornada) ? $jornada : null;
    }
}

if (!function_exists('asistencia_marcas_vigentes_jornada')) {
    function asistencia_marcas_vigentes_jornada(int $idJornada, bool $bloquear = false): array
    {
        $statement = db()->prepare(
            'SELECT
                ma.*,
                tm.codigo,
                tm.orden
             FROM marcas_asistencia ma
             INNER JOIN tipos_marca tm ON tm.id_tipo_marca = ma.id_tipo_marca
             WHERE ma.id_jornada = :id_jornada
               AND ma.activo = 1
               AND ma.anulada = 0
             /* Conservé el orden de escritura cuando dos marcas comparten el mismo segundo. */
             ORDER BY ma.fecha_hora ASC, ma.id_marca ASC' . ($bloquear ? ' FOR UPDATE' : '')
        );
        $statement->execute(['id_jornada' => $idJornada]);

        return $statement->fetchAll();
    }
}

if (!function_exists('asistencia_horario_vigente_dia')) {
    function asistencia_horario_vigente_dia(int $idPractica, string $fecha): ?array
    {
        // Excluí feriados comunes de la jornada ordinaria; recuperar ese día exige autorización aparte.
        if (recuperaciones_es_feriado($fecha)) { return null; }
        $diaSemana = (int) date('N', strtotime($fecha) ?: time());

        $statement = db()->prepare(
            'SELECT
                hp.id_horario,
                hp.nombre_horario
             FROM horarios_practicante hp
             WHERE hp.id_practica = :id_practica
               AND hp.activo = 1
               AND hp.fecha_inicio <= :fecha_inicio
               AND (hp.fecha_fin IS NULL OR hp.fecha_fin >= :fecha_fin)
             ORDER BY hp.fecha_inicio DESC, hp.id_horario DESC
             LIMIT 1'
        );
        $statement->execute([
            'id_practica' => $idPractica,
            'fecha_inicio' => $fecha,
            'fecha_fin' => $fecha,
        ]);

        $horarioBase = $statement->fetch();

        if (!is_array($horarioBase)) {
            return null;
        }

        // También respeté feriados individuales aunque existan otras excepciones ese día.
        $feriado = db()->prepare('SELECT COUNT(*) FROM excepciones_horario_practicante WHERE id_horario=? AND fecha=? AND tipo_excepcion="FERIADO" AND activo=1');
        $feriado->execute([(int)$horarioBase['id_horario'],$fecha]);
        if ((int)$feriado->fetchColumn()>0) { return null; }

        $excepcion = excepciones_horario_vigente((int) $horarioBase['id_horario'], $fecha);

        if ($excepcion !== null) {
            return colacion_horario_aplicar($idPractica,$fecha,excepciones_horario_para_asistencia($excepcion));
        }

        $statement = db()->prepare(
            'SELECT
                hp.id_horario,
                hp.nombre_horario,
                dhp.dia_semana,
                dhp.entrada_esperada,
                dhp.salida_esperada,
                dhp.colacion_inicio,
                dhp.colacion_fin,
                dhp.horas_planificadas
             FROM horarios_practicante hp
             INNER JOIN detalle_horario_practicante dhp ON dhp.id_horario = hp.id_horario
             WHERE hp.id_horario = :id_horario
               AND dhp.activo = 1
               AND dhp.dia_semana = :dia_semana
             ORDER BY hp.fecha_inicio DESC, hp.id_horario DESC
             LIMIT 1'
        );
        $statement->execute([
            'id_horario' => (int) $horarioBase['id_horario'],
            'dia_semana' => $diaSemana,
        ]);

        $horario = $statement->fetch();

        return colacion_horario_aplicar($idPractica,$fecha,is_array($horario)?$horario:null);
    }
}

if (!function_exists('asistencia_obtener_o_crear_jornada')) {
    function asistencia_obtener_o_crear_jornada(int $idPractica, string $fecha, ?int $creadaPor = null): array
    {
        $statement = db()->prepare(
            'SELECT *
             FROM jornadas_asistencia
             WHERE id_practica = :id_practica
               AND fecha = :fecha
             LIMIT 1'
        );
        $statement->execute([
            'id_practica' => $idPractica,
            'fecha' => $fecha,
        ]);

        $jornada = $statement->fetch();

        if (is_array($jornada)) {
            return $jornada;
        }

        // Agregué una inserción idempotente sobre el índice de práctica/fecha
        // para reutilizar la jornada si otra petición acaba de crearla.
        $insert = db()->prepare(
            'INSERT INTO jornadas_asistencia (
                id_practica,
                fecha,
                estado_jornada,
                politica_recuperacion_v2,
                creada_por
             ) VALUES (
                :id_practica,
                :fecha,
                :estado_jornada,
                1,
                :creada_por
            )
             ON DUPLICATE KEY UPDATE id_jornada = LAST_INSERT_ID(id_jornada)'
        );
        $insert->execute([
            'id_practica' => $idPractica,
            'fecha' => $fecha,
            'estado_jornada' => 'INCOMPLETA',
            'creada_por' => $creadaPor,
        ]);

        return asistencia_jornada_por_id((int) db()->lastInsertId()) ?? [];
    }
}

if (!function_exists('asistencia_registrar_marca')) {
    function asistencia_registrar_marca(int $idJornada, string $codigoTipoMarca, ?string $fechaHora = null, ?string $observacion = null, ?int $creadaPor = null, ?array $horario = null): array
    {
        $fechaHora = $fechaHora ?? date('Y-m-d H:i:s');
        $tipoMarca = asistencia_tipo_marca_por_codigo($codigoTipoMarca);

        if ($tipoMarca === null) {
            return ['ok' => false, 'mensaje' => 'Tipo de marca no disponible.'];
        }

        $creadaPor = $creadaPor ?? auth_usuario_actual_id();
        // Leí identidad antes de abrir la transacción para no fijar una instantánea previa al bloqueo padre.
        $objetivo = asistencia_jornada_por_id($idJornada);

        try {
            db()->beginTransaction();

            // Serialicé el marcaje con la revisión de permisos incluso si antes no había jornada.
            $lockPractica = db()->prepare('SELECT id_practica FROM practicas WHERE id_practica = ? FOR UPDATE');
            $lockPractica->execute([(int) ($objetivo['id_practica'] ?? 0)]);

            // Agregué bloqueo de la jornada antes de leer sus marcas: dos
            // solicitudes simultáneas ya no validan la misma secuencia antigua.
            $lock = db()->prepare('SELECT estado_jornada FROM jornadas_asistencia WHERE id_jornada = :id_jornada FOR UPDATE');
            $lock->execute(['id_jornada' => $idJornada]);
            $estado = $lock->fetchColumn();
            if ($estado === false || $estado === 'ANULADA') {
                db()->rollBack();
                return ['ok' => false, 'mensaje' => 'La jornada no existe o está anulada.'];
            }
            $marcasVigentes = asistencia_marcas_vigentes_jornada($idJornada, true);
            // Revalidé la solicitud vigente bajo el bloqueo de jornada, sin confiar en botones anteriores.
            $jornadaActual = asistencia_jornada_por_id($idJornada);
            $permiso = asistencia_permiso_del_dia((int) $jornadaActual['id_practica'], (string) $jornadaActual['fecha']);
            if (!in_array($codigoTipoMarca, asistencia_marcas_disponibles($marcasVigentes, $permiso), true)) {
                db()->rollBack();
                return ['ok' => false, 'mensaje' => 'La marca no está disponible para las marcas y el permiso vigentes.'];
            }
            $validacion = asistencia_validar_secuencia($marcasVigentes, $codigoTipoMarca);
            if (!$validacion['ok']) {
                db()->rollBack();
                return ['ok' => false, 'mensaje' => $validacion['mensaje'], 'permitidas' => $validacion['permitidas']];
            }

            $statement = db()->prepare(
                'INSERT INTO marcas_asistencia (
                    id_jornada,
                    id_tipo_marca,
                    fecha_hora,
                    observacion,
                    creada_por
                 ) VALUES (
                    :id_jornada,
                    :id_tipo_marca,
                    :fecha_hora,
                    :observacion,
                    :creada_por
                 )'
            );
            $statement->execute([
                'id_jornada' => $idJornada,
                'id_tipo_marca' => (int) $tipoMarca['id_tipo_marca'],
                'fecha_hora' => $fechaHora,
                'observacion' => $observacion,
                'creada_por' => $creadaPor,
            ]);

            $idMarca = (int) db()->lastInsertId();
            asistencia_recalcular_jornada($idJornada, $horario);
            db()->commit();

            return ['ok' => true, 'id_marca' => $idMarca, 'mensaje' => 'Marca registrada correctamente.'];
        } catch (Throwable $exception) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }

            error_log('Error al registrar marca de asistencia: ' . $exception->getMessage());

            return ['ok' => false, 'mensaje' => 'No fue posible registrar la marca.'];
        }
    }
}

if (!function_exists('asistencia_recalcular_jornada')) {
    function asistencia_recalcular_jornada(int $idJornada, ?array $horario = null, bool $conservarExtras = false): array
    {
    // Regla para que no sume horas luego de la jornada estipulada    
    if ($horario === null) {
            $jornada = asistencia_jornada_por_id($idJornada);
            if ($jornada !== null) {
                $horario = asistencia_horario_vigente_dia((int) $jornada['id_practica'], (string) $jornada['fecha']);
            }
        }

        // Leí marcas actuales bajo bloqueo cuando recalculo dentro de una operación atómica.
        $marcasVigentes = asistencia_marcas_vigentes_jornada($idJornada, db()->inTransaction());
        $resumen = asistencia_calcular_resumen_jornada($marcasVigentes, $horario);
        // Mantuve visibles las pausas pendientes/rechazadas y los cruces; no se aprueban con un marcaje.
        $jornadaActual = asistencia_jornada_por_id($idJornada);
        // Retiré recuperación que dependía de una ausencia cruzada por marcas corregidas posteriormente.
        $jornadasDependientes=recuperaciones_reabrir_por_ausencia($jornadaActual,$marcasVigentes);
        // Apliqué recuperación después del cálculo ordinario y antes de persistir el acumulado.
        $resumen=recuperaciones_ajustar_resumen($jornadaActual,$marcasVigentes,$horario,$resumen,true);
        // Reabrí créditos alterados y apliqué únicamente compensaciones previamente autorizadas.
        $jornadasTiempo=tiempo_reabrir_por_marcas($jornadaActual);
        $resumen=tiempo_ajustar_resumen($jornadaActual,$marcasVigentes,$horario,$resumen,true);
        $permiso = asistencia_permiso_del_dia((int) $jornadaActual['id_practica'], (string) $jornadaActual['fecha']);
        $pausa = asistencia_marca_por_codigo($marcasVigentes, 'SALIDA_PERMISO') !== null;
        if (($pausa && ($permiso['estado'] ?? '') !== 'APROBADO')
            || ($permiso !== null && asistencia_permiso_cruza_marcas($permiso, $marcasVigentes))) {
            if ($resumen['estado_jornada'] === 'VALIDA') { $resumen['estado_jornada'] = 'OBSERVADA'; }
            $resumen['observaciones'][] = 'Permiso pendiente de revisión o con cruce de tiempo trabajado.';
        }
        $observacion = $resumen['observaciones'] !== [] ? implode(' ', $resumen['observaciones']) : null;
        // Al aprobar un permiso sin cambiar marcas conservé solo extras anteriores compatibles.
        $historica=tiempo_extra_historica($idJornada);
        if ($historica===null && $conservarExtras && (float) ($jornadaActual['horas_extra_aprobadas'] ?? 0) > (float) $resumen['horas_extra_sugeridas']) {
            throw new RuntimeException('La aprobación previa de extras requiere revisión antes de ajustar el permiso.');
        }
        // Preservé el importe aprobado antes del corte; nunca genero una aprobación nueva en esta columna.
        $resetExtras = ($conservarExtras || $historica!==null) ? '' : 'horas_extra_aprobadas = NULL, aprobada_por = NULL,';

        // Invalidé la aprobación previa al recalcular tras cambiar marcas:
        // el tiempo aprobado sobre las marcas anteriores requiere nueva revisión.
        $statement = db()->prepare(
            'UPDATE jornadas_asistencia
             SET estado_jornada = :estado_jornada,
                 ' . $resetExtras . '
                 hora_entrada = :hora_entrada,
                 hora_salida = :hora_salida,
                 horas_calculadas = :horas_calculadas,
                 observacion = :observacion,
                 flag_atraso = :flag_atraso,
                 flag_salida_anticipada = :flag_salida_anticipada,
                 flag_asistencia_extraordinaria = :flag_asistencia_extraordinaria,
                 flag_horas_extra = :flag_horas_extra
             WHERE id_jornada = :id_jornada'
        );
        $statement->execute([
            'estado_jornada' => $resumen['estado_jornada'],
            'hora_entrada' => $resumen['hora_entrada'],
            'hora_salida' => $resumen['hora_salida'],
            'horas_calculadas' => $resumen['horas_calculadas'],
            'observacion' => $observacion,
            'flag_atraso' => $resumen['flag_atraso'],
            'flag_salida_anticipada' => $resumen['flag_salida_anticipada'],
            'flag_asistencia_extraordinaria' => $resumen['flag_asistencia_extraordinaria'],
            'flag_horas_extra' => $resumen['flag_horas_extra'],
            'id_jornada' => $idJornada,
        ]);

        // Recalculé los días afectados para que sus informes no conserven crédito reabierto por el origen.
        foreach($jornadasDependientes as $idDependiente){asistencia_recalcular_jornada($idDependiente,null,true);}
        foreach($jornadasTiempo as $idDependiente){if($idDependiente!==$idJornada){asistencia_recalcular_jornada($idDependiente,null,true);}}
        return $resumen;
    }
}

if (!function_exists('asistencia_marca_por_id')) {
    function asistencia_marca_por_id(int $idMarca): ?array
    {
        $statement = db()->prepare(
            'SELECT
                ma.*,
                tm.codigo,
                tm.nombre AS tipo_marca
             FROM marcas_asistencia ma
             INNER JOIN tipos_marca tm ON tm.id_tipo_marca = ma.id_tipo_marca
             WHERE ma.id_marca = :id_marca
             LIMIT 1'
        );
        $statement->execute(['id_marca' => $idMarca]);

        $marca = $statement->fetch();

        return is_array($marca) ? $marca : null;
    }
}

if (!function_exists('asistencia_anular_marca')) {
    function asistencia_anular_marca(int $idMarca, string $motivo, ?int $anuladaPor = null): array
    {
        $motivo = trim($motivo);

        if ($motivo === '') {
            return ['ok' => false, 'mensaje' => 'Debe indicar un motivo de anulación.'];
        }

        $marca = asistencia_marca_por_id($idMarca);

        if ($marca === null) {
            return ['ok' => false, 'mensaje' => 'Marca no encontrada.'];
        }

        if ((int) $marca['anulada'] === 1 || (int) $marca['activo'] === 0) {
            return ['ok' => false, 'mensaje' => 'La marca ya se encuentra anulada.'];
        }

        $anuladaPor = $anuladaPor ?? auth_usuario_actual_id();

        try {
            db()->beginTransaction();

            // Agregué bloqueo de la jornada y relectura de la marca para que
            // una anulación no compita con marcajes o revisiones de sus horas.
            $lock = db()->prepare('SELECT * FROM jornadas_asistencia WHERE id_jornada = :id_jornada FOR UPDATE');
            $lock->execute(['id_jornada' => (int) $marca['id_jornada']]);
            $jornada = $lock->fetch();
            $marca = asistencia_marca_por_id($idMarca);
            if (!is_array($jornada) || $jornada['estado_jornada'] === 'ANULADA'
                || !auth_puede_supervisar_practica((int) $jornada['id_practica'], $anuladaPor)
                || $marca === null || (int) $marca['activo'] !== 1 || (int) $marca['anulada'] !== 0) {
                db()->rollBack();
                return ['ok' => false, 'mensaje' => 'No puedes anular esta marca o ya fue anulada.'];
            }

            $statement = db()->prepare(
                'UPDATE marcas_asistencia
                 SET activo = 0,
                     anulada = 1,
                     motivo_anulacion = :motivo_anulacion,
                     anulada_por = :anulada_por,
                     fecha_anulacion = NOW()
                 WHERE id_marca = :id_marca'
            );
            $statement->execute([
                'motivo_anulacion' => $motivo,
                'anulada_por' => $anuladaPor,
                'id_marca' => $idMarca,
            ]);

            $resumen = asistencia_recalcular_jornada((int) $marca['id_jornada']);

            // Moví la auditoría dentro de la transacción de anulación.
            if (!auditoria_registrar('ANULAR_MARCA_ASISTENCIA', [
                'tabla_afectada' => 'marcas_asistencia',
                'id_registro_afectado' => $idMarca,
                'valores_anteriores' => ['marca' => $marca, 'jornada' => $jornada],
                'valores_nuevos' => [
                    'activo' => 0,
                    'anulada' => 1,
                    'motivo_anulacion' => $motivo,
                    'anulada_por' => $anuladaPor,
                    'jornada' => asistencia_jornada_por_id((int) $marca['id_jornada']),
                ],
            ])) {
                throw new RuntimeException('No se pudo auditar la anulación de la marca.');
            }
            db()->commit();

            return ['ok' => true, 'mensaje' => 'Marca anulada correctamente.', 'resumen_jornada' => $resumen];
        } catch (Throwable $exception) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }

            error_log('Error al anular marca de asistencia: ' . $exception->getMessage());

            return ['ok' => false, 'mensaje' => 'No fue posible anular la marca.'];
        }
    }
}

if (!function_exists('asistencia_puede_sumar_horas')) {
    function asistencia_puede_sumar_horas(array $jornada): bool
    {
        return asistencia_estado_suma_horas((string) ($jornada['estado_jornada'] ?? ''))
            && (float) ($jornada['horas_calculadas'] ?? 0) > 0;
    }
}

if (!function_exists('asistencia_evaluar_dia_laboral')) {
    /**
     * Evalúa si una fecha está habilitada para que el practicante registre asistencia.
     *
     * Reglas de negocio:
     * 1. Domingo: Estrictamente bloqueado (no laborable).
     * 2. Sábado: Estrictamente bloqueado por defecto. Solo se habilita si el practicante
     *    cuenta con una recuperación de horas programada y aprobada para ese sábado específico.
     * 3. Lunes a Viernes:
     *    - Si es feriado oficial: Bloqueado a menos que cuente con recuperación autorizada.
     *    - Si no tiene turno en detalle_horario_practicante para ese día de la semana: Bloqueado (día libre).
     *    - Si tiene turno asignado: Habilitado con su horario regular.
     */
    function asistencia_evaluar_dia_laboral(int $idPractica, string $fecha): array
    {
        $diaSemana = (int) date('N', strtotime($fecha) ?: time());

        // 1. Domingo: siempre bloqueado.
        if ($diaSemana === 7) {
            return [
                'habilitado' => false,
                'motivo_bloqueo' => 'Los días domingo no son laborables en el portal de asistencia.',
                'tipo_jornada' => 'NO_LABORAL',
                'horario' => null,
                'recuperaciones' => [],
            ];
        }

        // Consultar recuperaciones aprobadas para esta fecha
        $recuperaciones = function_exists('recuperaciones_del_dia')
            ? recuperaciones_del_dia($idPractica, $fecha)
            : [];

        // 2. Sábado: habilitado si cuenta con recuperación aprobada o excepción activa de horario (asistencia extraordinaria / cambio de horario).
        if ($diaSemana === 6) {
            if ($recuperaciones === []) {
                $horarioExcepcion = asistencia_horario_vigente_dia($idPractica, $fecha);
                $tipoExcepcion = is_array($horarioExcepcion) ? (string) ($horarioExcepcion['tipo_excepcion'] ?? '') : '';

                if ($horarioExcepcion !== null && in_array($tipoExcepcion, ['ASISTENCIA_EXTRAORDINARIA', 'CAMBIO_HORARIO'], true)) {
                    return [
                        'habilitado' => true,
                        'motivo_bloqueo' => null,
                        'tipo_jornada' => 'EXCEPCION_SABADO',
                        'horario' => $horarioExcepcion,
                        'recuperaciones' => [],
                    ];
                }

                return [
                    'habilitado' => false,
                    'motivo_bloqueo' => 'Los días sábado no son laborables a menos que cuentes con una recuperación de horas aprobada por tu supervisor.',
                    'tipo_jornada' => 'NO_LABORAL',
                    'horario' => null,
                    'recuperaciones' => [],
                ];
            }

            // Construir horario de trabajo a partir de los tramos aprobados de recuperación
            $inicioEsperado = '23:59:59';
            $finEsperado = '00:00:00';
            $minutosTotales = 0;
            $colacionInicio = null;
            $colacionFin = null;

            foreach ($recuperaciones as $tramo) {
                $inicio = (string) $tramo['hora_inicio'];
                $fin = (string) $tramo['hora_fin'];
                if ($inicio < $inicioEsperado) {
                    $inicioEsperado = $inicio;
                }
                if ($fin > $finEsperado) {
                    $finEsperado = $fin;
                }
                $minutosTotales += (int) ($tramo['minutos_planificados'] ?? 0);
                if (($tramo['tipo'] ?? '') === 'COLACION') {
                    $colacionInicio = $inicio;
                    $colacionFin = $fin;
                }
            }

            $horarioRecuperacion = [
                'id_horario' => 0,
                'nombre_horario' => 'Recuperación de horas (Sábado)',
                'dia_semana' => 6,
                'entrada_esperada' => $inicioEsperado !== '23:59:59' ? $inicioEsperado : '10:00:00',
                'salida_esperada' => $finEsperado !== '00:00:00' ? $finEsperado : '18:00:00',
                'colacion_inicio' => $colacionInicio,
                'colacion_fin' => $colacionFin,
                'horas_planificadas' => round($minutosTotales / 60, 2),
                'es_recuperacion' => true,
            ];

            return [
                'habilitado' => true,
                'motivo_bloqueo' => null,
                'tipo_jornada' => 'RECUPERACION_SABADO',
                'horario' => $horarioRecuperacion,
                'recuperaciones' => $recuperaciones,
            ];
        }

        // 3. Lunes a Viernes
        $esFeriado = function_exists('recuperaciones_es_feriado') && recuperaciones_es_feriado($fecha);
        if ($esFeriado) {
            if ($recuperaciones !== []) {
                $inicioEsperado = '23:59:59';
                $finEsperado = '00:00:00';
                $minutosTotales = 0;
                foreach ($recuperaciones as $tramo) {
                    $inicio = (string) $tramo['hora_inicio'];
                    $fin = (string) $tramo['hora_fin'];
                    if ($inicio < $inicioEsperado) { $inicioEsperado = $inicio; }
                    if ($fin > $finEsperado) { $finEsperado = $fin; }
                    $minutosTotales += (int) ($tramo['minutos_planificados'] ?? 0);
                }
                return [
                    'habilitado' => true,
                    'motivo_bloqueo' => null,
                    'tipo_jornada' => 'RECUPERACION_FERIADO',
                    'horario' => [
                        'id_horario' => 0,
                        'nombre_horario' => 'Recuperación en día feriado',
                        'dia_semana' => $diaSemana,
                        'entrada_esperada' => $inicioEsperado !== '23:59:59' ? $inicioEsperado : '10:00:00',
                        'salida_esperada' => $finEsperado !== '00:00:00' ? $finEsperado : '18:00:00',
                        'colacion_inicio' => null,
                        'colacion_fin' => null,
                        'horas_planificadas' => round($minutosTotales / 60, 2),
                        'es_recuperacion' => true,
                    ],
                    'recuperaciones' => $recuperaciones,
                ];
            }

            return [
                'habilitado' => false,
                'motivo_bloqueo' => 'Hoy es día feriado y no cuentas con una recuperación autorizada para esta fecha.',
                'tipo_jornada' => 'NO_LABORAL',
                'horario' => null,
                'recuperaciones' => [],
            ];
        }

        // Horario regular pactado para este día de la semana
        $horarioVigente = asistencia_horario_vigente_dia($idPractica, $fecha);
        if ($horarioVigente === null) {
            if ($recuperaciones !== []) {
                return [
                    'habilitado' => true,
                    'motivo_bloqueo' => null,
                    'tipo_jornada' => 'RECUPERACION',
                    'horario' => [
                        'id_horario' => 0,
                        'nombre_horario' => 'Recuperación de horas',
                        'dia_semana' => $diaSemana,
                        'entrada_esperada' => (string) ($recuperaciones[0]['hora_inicio'] ?? '10:00:00'),
                        'salida_esperada' => (string) ($recuperaciones[0]['hora_fin'] ?? '18:00:00'),
                        'colacion_inicio' => null,
                        'colacion_fin' => null,
                        'horas_planificadas' => round((int) ($recuperaciones[0]['minutos_planificados'] ?? 0) / 60, 2),
                        'es_recuperacion' => true,
                    ],
                    'recuperaciones' => $recuperaciones,
                ];
            }

            return [
                'habilitado' => false,
                'motivo_bloqueo' => 'Hoy no tienes jornada laboral programada en tu horario pactado.',
                'tipo_jornada' => 'NO_LABORAL',
                'horario' => null,
                'recuperaciones' => [],
            ];
        }

        return [
            'habilitado' => true,
            'motivo_bloqueo' => null,
            'tipo_jornada' => 'REGULAR',
            'horario' => $horarioVigente,
            'recuperaciones' => $recuperaciones,
        ];
    }
}

/*
|--------------------------------------------------------------------------
| Reglas de Margen Horario y Marcaje Automático (v2.1.4)
|--------------------------------------------------------------------------
|
| - Anticipación: Máximo 15 minutos antes de la hora esperada para cada marca.
| - Auto-retorno colación: A los 15 minutos tras el fin previsto de colación.
| - Auto-cierre jornada: A los 30 minutos tras la salida esperada.
|
*/

if (!defined('ASISTENCIA_MARGEN_ANTICIPACION_MIN')) {
    define('ASISTENCIA_MARGEN_ANTICIPACION_MIN', 15);
}

if (!defined('ASISTENCIA_MARGEN_AUTO_COLACION_MIN')) {
    define('ASISTENCIA_MARGEN_AUTO_COLACION_MIN', 15);
}

if (!defined('ASISTENCIA_MARGEN_AUTO_SALIDA_MIN')) {
    define('ASISTENCIA_MARGEN_AUTO_SALIDA_MIN', 30);
}

if (!function_exists('asistencia_evaluar_ventana_marca')) {
    /**
     * Evalúa si una marca específica puede registrarse en la fecha y hora indicadas
     * respetando el margen de anticipación de máximo 15 minutos antes de la hora pactada.
     *
     * @param string $codigoMarca Código de tipo de marca (ENTRADA, ENTRADA_COLACION, etc.)
     * @param array|null $horario Detalle de horario del día (entrada_esperada, salida_esperada, etc.)
     * @param string $fecha Fecha en formato YYYY-MM-DD
     * @param string|null $horaActual Hora en formato HH:MM:SS (si es null, usa date('H:i:s'))
     * @return array ['habilitado' => bool, 'es_anticipado' => bool, 'hora_apertura' => ?string, 'hora_planificada' => ?string, 'motivo' => ?string]
     */
    function asistencia_evaluar_ventana_marca(
        string $codigoMarca,
        ?array $horario,
        string $fecha,
        ?string $horaActual = null
    ): array {
        $horaActual = $horaActual ?? date('H:i:s');
        $tsAhora = strtotime("$fecha $horaActual");

        $resultado = [
            'habilitado' => true,
            'es_anticipado' => false,
            'hora_apertura' => null,
            'hora_planificada' => null,
            'motivo' => null,
        ];

        if ($tsAhora === false || $horario === null) {
            return $resultado;
        }

        $horaPlanificada = null;
        $etiquetaAccion = 'tu horario pactado';

        switch ($codigoMarca) {
            case 'ENTRADA':
                $horaPlanificada = !empty($horario['entrada_esperada']) ? (string) $horario['entrada_esperada'] : null;
                $etiquetaAccion = 'tu entrada pactada';
                break;

            case 'ENTRADA_COLACION':
                $horaPlanificada = !empty($horario['colacion_inicio']) ? (string) $horario['colacion_inicio'] : null;
                $etiquetaAccion = 'el inicio de colación';
                if ($horaPlanificada === null) {
                    return [
                        'habilitado' => false,
                        'es_anticipado' => false,
                        'hora_apertura' => null,
                        'hora_planificada' => null,
                        'motivo' => 'Tu horario no contempla periodo de colación programado.',
                    ];
                }
                break;

            case 'SALIDA_COLACION':
                $horaPlanificada = !empty($horario['colacion_fin']) ? (string) $horario['colacion_fin'] : null;
                $etiquetaAccion = 'el regreso de colación';
                if ($horaPlanificada === null) {
                    return [
                        'habilitado' => false,
                        'es_anticipado' => false,
                        'hora_apertura' => null,
                        'hora_planificada' => null,
                        'motivo' => 'Tu horario no contempla periodo de colación programado.',
                    ];
                }
                break;

            case 'SALIDA':
                $horaPlanificada = !empty($horario['salida_esperada']) ? (string) $horario['salida_esperada'] : null;
                $etiquetaAccion = 'el fin de jornada';
                break;

            default:
                return $resultado;
        }

        if ($horaPlanificada === null) {
            return $resultado;
        }

        $tsPlanificado = strtotime("$fecha $horaPlanificada");
        if ($tsPlanificado === false) {
            return $resultado;
        }

        $tsApertura = $tsPlanificado - (ASISTENCIA_MARGEN_ANTICIPACION_MIN * 60);
        $horaAperturaStr = date('H:i', $tsApertura);
        $horaPlanificadaStr = date('H:i', $tsPlanificado);

        $resultado['hora_apertura'] = $horaAperturaStr;
        $resultado['hora_planificada'] = $horaPlanificadaStr;

        if ($tsAhora < $tsApertura) {
            $resultado['habilitado'] = false;
            $resultado['es_anticipado'] = true;
            $resultado['motivo'] = "Disponible a partir de las {$horaAperturaStr} (máximo " . ASISTENCIA_MARGEN_ANTICIPACION_MIN . " minutos antes de {$etiquetaAccion}: {$horaPlanificadaStr}).";
            return $resultado;
        }

        $resultado['habilitado'] = true;
        $resultado['es_anticipado'] = false;

        return $resultado;
    }
}

if (!function_exists('asistencia_insertar_marca_sistema')) {
    /**
     * Inserta una marca generada automáticamente por el sistema (inactividad o cierre)
     * registrándola con creada_por = NULL y observación descriptiva.
     */
    function asistencia_insertar_marca_sistema(
        int $idJornada,
        string $codigoTipoMarca,
        string $fechaHora,
        string $observacion,
        ?array $horario = null
    ): bool {
        $tipoMarca = asistencia_tipo_marca_por_codigo($codigoTipoMarca);
        if ($tipoMarca === null) {
            return false;
        }

        try {
            db()->beginTransaction();

            $lock = db()->prepare('SELECT estado_jornada FROM jornadas_asistencia WHERE id_jornada = :id_jornada FOR UPDATE');
            $lock->execute(['id_jornada' => $idJornada]);
            $estado = $lock->fetchColumn();
            if ($estado === false || $estado === 'ANULADA') {
                db()->rollBack();
                return false;
            }

            $marcasVigentes = asistencia_marcas_vigentes_jornada($idJornada, true);
            if (asistencia_marca_por_codigo($marcasVigentes, $codigoTipoMarca) !== null) {
                db()->rollBack();
                return false;
            }

            $statement = db()->prepare(
                'INSERT INTO marcas_asistencia (
                    id_jornada,
                    id_tipo_marca,
                    fecha_hora,
                    observacion,
                    creada_por
                 ) VALUES (
                    :id_jornada,
                    :id_tipo_marca,
                    :fecha_hora,
                    :observacion,
                    NULL
                 )'
            );
            $statement->execute([
                'id_jornada' => $idJornada,
                'id_tipo_marca' => (int) $tipoMarca['id_tipo_marca'],
                'fecha_hora' => $fechaHora,
                'observacion' => $observacion,
            ]);

            asistencia_recalcular_jornada($idJornada, $horario);
            db()->commit();

            return true;
        } catch (Throwable $e) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            error_log('Error al insertar marca de sistema: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('asistencia_procesar_marcajes_automaticos_jornada')) {
    /**
     * Revisa y procesa marcas automáticas por inactividad en una jornada:
     * 1. Auto-retorno de colación si transcurrieron 15 minutos tras la hora pactada de regreso.
     * 2. Auto-cierre de jornada (Salida) si transcurrieron 30 minutos tras la hora pactada de salida.
     *
     * @param int $idJornada ID de la jornada
     * @param array|null $horario Detalle del horario pactado
     * @param string $fecha Fecha de la jornada (YYYY-MM-DD)
     * @param string|null $horaActual Hora actual (HH:MM:SS)
     * @return array ['procesado' => bool, 'auto_colacion' => bool, 'auto_salida' => bool]
     */
    function asistencia_procesar_marcajes_automaticos_jornada(
        int $idJornada,
        ?array $horario,
        string $fecha,
        ?string $horaActual = null
    ): array {
        if ($idJornada <= 0) {
            return ['procesado' => false, 'auto_colacion' => false, 'auto_salida' => false];
        }

        $horaActual = $horaActual ?? date('H:i:s');
        $tsAhora = strtotime("$fecha $horaActual");
        if ($tsAhora === false) {
            return ['procesado' => false, 'auto_colacion' => false, 'auto_salida' => false];
        }

        $marcas = asistencia_marcas_vigentes_jornada($idJornada);
        $marcaEntrada = asistencia_marca_por_codigo($marcas, 'ENTRADA');
        $marcaSalida = asistencia_marca_por_codigo($marcas, 'SALIDA');

        if ($marcaEntrada === null || $marcaSalida !== null) {
            return ['procesado' => false, 'auto_colacion' => false, 'auto_salida' => false];
        }

        $autoColacion = false;
        $autoSalida = false;

        // 1. Auto-retorno de colación
        $marcaEntradaColacion = asistencia_marca_por_codigo($marcas, 'ENTRADA_COLACION');
        $marcaSalidaColacion = asistencia_marca_por_codigo($marcas, 'SALIDA_COLACION');

        if ($marcaEntradaColacion !== null && $marcaSalidaColacion === null) {
            $tsColacionFin = null;
            if ($horario !== null && !empty($horario['colacion_fin'])) {
                $tsColacionFin = strtotime("$fecha " . (string) $horario['colacion_fin']);
            }
            if ($tsColacionFin === false || $tsColacionFin === null) {
                $tsEntradaColacion = strtotime((string) $marcaEntradaColacion['fecha_hora']);
                $tsColacionFin = $tsEntradaColacion !== false ? ($tsEntradaColacion + 3600) : null;
            }

            if ($tsColacionFin !== null) {
                $tsLimiteColacion = $tsColacionFin + (ASISTENCIA_MARGEN_AUTO_COLACION_MIN * 60);

                if ($tsAhora >= $tsLimiteColacion) {
                    $tsEntradaColacion = strtotime((string) $marcaEntradaColacion['fecha_hora']);
                    $tsMarcaColacion = max($tsColacionFin, ($tsEntradaColacion !== false ? $tsEntradaColacion + 60 : $tsColacionFin));
                    $fechaHoraAutoColacion = date('Y-m-d H:i:s', $tsMarcaColacion);

                    $insertada = asistencia_insertar_marca_sistema(
                        $idJornada,
                        'SALIDA_COLACION',
                        $fechaHoraAutoColacion,
                        'Marcaje automático de regreso de colación por inactividad (Sistema)',
                        $horario
                    );

                    if ($insertada) {
                        $autoColacion = true;
                        $marcas = asistencia_marcas_vigentes_jornada($idJornada);
                        $marcaSalidaColacion = asistencia_marca_por_codigo($marcas, 'SALIDA_COLACION');
                    }
                }
            }
        }

        // 2. Auto-cierre de jornada (Salida)
        $colacionAbierta = ($marcaEntradaColacion !== null && $marcaSalidaColacion === null);
        if (!$colacionAbierta && $horario !== null && !empty($horario['salida_esperada'])) {
            $tsSalidaEsperada = strtotime("$fecha " . (string) $horario['salida_esperada']);
            if ($tsSalidaEsperada !== false) {
                $tsLimiteSalida = $tsSalidaEsperada + (ASISTENCIA_MARGEN_AUTO_SALIDA_MIN * 60);

                if ($tsAhora >= $tsLimiteSalida) {
                    $tsEntrada = strtotime((string) $marcaEntrada['fecha_hora']);
                    $tsMarcaSalida = max($tsSalidaEsperada, ($tsEntrada !== false ? $tsEntrada + 60 : $tsSalidaEsperada));
                    $fechaHoraAutoSalida = date('Y-m-d H:i:s', $tsMarcaSalida);

                    $insertada = asistencia_insertar_marca_sistema(
                        $idJornada,
                        'SALIDA',
                        $fechaHoraAutoSalida,
                        'Cierre automático de jornada por inactividad (Sistema)',
                        $horario
                    );

                    if ($insertada) {
                        $autoSalida = true;
                    }
                }
            }
        }

        return [
            'procesado' => ($autoColacion || $autoSalida),
            'auto_colacion' => $autoColacion,
            'auto_salida' => $autoSalida,
        ];
    }
}

if (!function_exists('asistencia_estado_solo_lectura_practicante')) {
    /**
     * Evalúa si el perfil del practicante debe operar en modo solo lectura.
     *
     * Se activa dinámicamente en servidor cuando:
     * 1. Completó el 100% de sus horas requeridas (jornadas sumables + extras aprobadas + horas previas).
     * 2. Su práctica ya finalizó/egresó (inactiva, estado distinto a PENDIENTE_INICIO/EN_PROGRESO o fecha de término vencida).
     * Si un supervisor ajusta las horas requeridas o previas por debajo del 100%, se rehabilita automáticamente.
     */
    function asistencia_estado_solo_lectura_practicante(int $idPracticante, ?string $fechaReferencia = null): array
    {
        $vacio = [
            'solo_lectura' => false,
            'meta_cumplida' => false,
            'practica_egresada' => false,
            'motivo' => null,
            'practica' => null,
            'horas_realizadas' => 0.0,
            'horas_requeridas' => 0,
            'porcentaje_avance' => 0.0,
        ];

        if ($idPracticante <= 0) {
            return $vacio;
        }

        $fechaRef = ($fechaReferencia !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaReferencia) === 1)
            ? $fechaReferencia
            : date('Y-m-d');

        // Prioricé la práctica activa vigente; si el practicante ya egresó, tomo la última práctica registrada.
        $stmt = db()->prepare(
            'SELECT
                pr.*,
                ep.nombre AS estado_practica
             FROM practicas pr
             INNER JOIN estados_practica ep ON ep.id_estado_practica = pr.id_estado_practica
             WHERE pr.id_practicante = :id_practicante
             ORDER BY
                CASE WHEN pr.activo = 1 AND ep.nombre IN ("PENDIENTE_INICIO", "EN_PROGRESO") THEN 0 ELSE 1 END,
                pr.fecha_inicio_practica DESC,
                pr.id_practica DESC
             LIMIT 1'
        );
        $stmt->execute(['id_practicante' => $idPracticante]);
        $practica = $stmt->fetch();

        if (!is_array($practica)) {
            return $vacio;
        }

        $idPractica = (int) $practica['id_practica'];
        $horasRequeridas = (int) ($practica['horas_requeridas'] ?? 0);

        $stmtHoras = db()->prepare(
            'SELECT
                COALESCE(SUM(CASE
                    WHEN ja.estado_jornada IN ("VALIDA", "OBSERVADA", "CORREGIDA", "REVISADA")
                    THEN (COALESCE(ja.horas_calculadas, 0) + COALESCE(ja.horas_extra_aprobadas, 0))
                    ELSE 0
                END), 0) AS horas_jornadas,
                COALESCE(p.horas_previas, 0) AS horas_previas
             FROM practicas p
             LEFT JOIN jornadas_asistencia ja ON ja.id_practica = p.id_practica
             WHERE p.id_practica = :id_practica
             GROUP BY p.horas_previas'
        );
        $stmtHoras->execute(['id_practica' => $idPractica]);
        $filaHoras = $stmtHoras->fetch() ?: [];

        $horasRealizadas = round(
            (float) ($filaHoras['horas_jornadas'] ?? 0) + (float) ($filaHoras['horas_previas'] ?? 0),
            2
        );
        $porcentaje = $horasRequeridas > 0
            ? min(100.0, round(($horasRealizadas / $horasRequeridas) * 100, 1))
            : 0.0;

        $metaCumplida = $horasRequeridas > 0 && $horasRealizadas >= $horasRequeridas;
        $estadoNombre = strtoupper(trim((string) ($practica['estado_practica'] ?? '')));
        $fechaTermino = trim((string) ($practica['fecha_termino_practica'] ?? ''));
        $practicaEgresada = ((int) ($practica['activo'] ?? 0) !== 1)
            || !in_array($estadoNombre, ['PENDIENTE_INICIO', 'EN_PROGRESO'], true)
            || ($fechaTermino !== '' && $fechaTermino < $fechaRef);

        $soloLectura = $metaCumplida || $practicaEgresada;
        $motivo = null;

        if ($metaCumplida) {
            $motivo = 'Completaste el 100% de tus horas de práctica. Tu perfil está en modo solo lectura: puedes consultar tu historial, notas, documentos y gestionar tu reseña.';
        } elseif ($practicaEgresada) {
            $motivo = 'Tu práctica se encuentra finalizada. Tu perfil está en modo solo lectura: puedes consultar tu historial, notas, documentos y gestionar tu reseña.';
        }

        return [
            'solo_lectura' => $soloLectura,
            'meta_cumplida' => $metaCumplida,
            'practica_egresada' => $practicaEgresada,
            'motivo' => $motivo,
            'practica' => $practica,
            'horas_realizadas' => $horasRealizadas,
            'horas_requeridas' => $horasRequeridas,
            'porcentaje_avance' => $porcentaje,
        ];
    }
}
