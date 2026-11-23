<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/AuthService.php';

if (!function_exists('excepciones_horario_tipos')) {
    function excepciones_horario_tipos(): array
    {
        return [
            'CAMBIO_HORARIO' => 'Cambio de horario',
            'DIA_LIBRE' => 'Dia libre',
            'ASISTENCIA_EXTRAORDINARIA' => 'Asistencia extraordinaria',
            'AUSENCIA_JUSTIFICADA' => 'Ausencia justificada',
            'FERIADO' => 'Feriado',
            'OTRO' => 'Otro',
        ];
    }
}

if (!function_exists('excepciones_horario_requiere_horario')) {
    function excepciones_horario_requiere_horario(string $tipo): bool
    {
        return in_array($tipo, ['CAMBIO_HORARIO', 'ASISTENCIA_EXTRAORDINARIA'], true);
    }
}

if (!function_exists('excepciones_horario_fecha_valida')) {
    function excepciones_horario_fecha_valida(?string $fecha): bool
    {
        if ($fecha === null || $fecha === '') {
            return false;
        }

        $objetoFecha = DateTimeImmutable::createFromFormat('Y-m-d', $fecha);

        return $objetoFecha !== false && $objetoFecha->format('Y-m-d') === $fecha;
    }
}

if (!function_exists('excepciones_horario_normalizar_hora')) {
    function excepciones_horario_normalizar_hora(mixed $valor): ?string
    {
        $hora = trim((string) $valor);

        if ($hora === '') {
            return null;
        }

        return preg_match('/^\d{2}:\d{2}$/', $hora) === 1 ? $hora : null;
    }
}

if (!function_exists('excepciones_horario_minutos')) {
    function excepciones_horario_minutos(string $hora): int
    {
        [$horas, $minutos] = array_map('intval', explode(':', $hora));

        return ($horas * 60) + $minutos;
    }
}

if (!function_exists('excepciones_horario_horas_planificadas')) {
    function excepciones_horario_horas_planificadas(string $entrada, string $salida, ?string $colacionInicio, ?string $colacionFin): float
    {
        $minutos = excepciones_horario_minutos($salida) - excepciones_horario_minutos($entrada);

        if ($colacionInicio !== null && $colacionFin !== null) {
            $minutos -= excepciones_horario_minutos($colacionFin) - excepciones_horario_minutos($colacionInicio);
        }

        return round(max(0, $minutos) / 60, 2);
    }
}

if (!function_exists('excepciones_horario_vigente')) {
    function excepciones_horario_vigente(int $idHorario, string $fecha): ?array
    {
        $statement = db()->prepare(
            'SELECT *
             FROM excepciones_horario_practicante
             WHERE id_horario = :id_horario
               AND fecha = :fecha
               AND activo = 1
             ORDER BY
                CASE tipo_excepcion
                    WHEN "CAMBIO_HORARIO" THEN 1
                    WHEN "ASISTENCIA_EXTRAORDINARIA" THEN 2
                    WHEN "DIA_LIBRE" THEN 3
                    WHEN "AUSENCIA_JUSTIFICADA" THEN 4
                    WHEN "FERIADO" THEN 5
                    ELSE 6
                END,
                id_excepcion DESC
             LIMIT 1'
        );
        $statement->execute([
            'id_horario' => $idHorario,
            'fecha' => $fecha,
        ]);
        $excepcion = $statement->fetch();

        return is_array($excepcion) ? $excepcion : null;
    }
}

if (!function_exists('excepciones_horario_listar')) {
    function excepciones_horario_listar(int $idHorario): array
    {
        $statement = db()->prepare(
            'SELECT *
             FROM excepciones_horario_practicante
             WHERE id_horario = :id_horario
             ORDER BY fecha DESC, id_excepcion DESC
             LIMIT 20'
        );
        $statement->execute(['id_horario' => $idHorario]);

        return $statement->fetchAll();
    }
}

if (!function_exists('excepciones_horario_para_asistencia')) {
    function excepciones_horario_para_asistencia(array $excepcion): ?array
    {
        $tipo = (string) ($excepcion['tipo_excepcion'] ?? '');

        if (in_array($tipo, ['DIA_LIBRE', 'AUSENCIA_JUSTIFICADA', 'FERIADO'], true)) {
            return null;
        }

        if (!excepciones_horario_requiere_horario($tipo)) {
            return null;
        }

        return [
            'id_horario' => (int) $excepcion['id_horario'],
            'id_excepcion' => (int) $excepcion['id_excepcion'],
            'tipo_excepcion' => $tipo,
            'fecha' => (string) $excepcion['fecha'],
            'entrada_esperada' => $excepcion['entrada_esperada'],
            'salida_esperada' => $excepcion['salida_esperada'],
            'colacion_inicio' => $excepcion['colacion_inicio'],
            'colacion_fin' => $excepcion['colacion_fin'],
            'horas_planificadas' => $excepcion['horas_planificadas'],
            'observacion_excepcion' => $excepcion['observacion'],
        ];
    }
}

if (!function_exists('excepciones_horario_obtener')) {
    function excepciones_horario_obtener(int $idExcepcion): ?array
    {
        $statement = db()->prepare(
            'SELECT *
             FROM excepciones_horario_practicante
             WHERE id_excepcion = :id_excepcion
             LIMIT 1'
        );
        $statement->execute(['id_excepcion' => $idExcepcion]);
        $excepcion = $statement->fetch();

        return is_array($excepcion) ? $excepcion : null;
    }
}

if (!function_exists('excepciones_horario_guardar')) {
    function excepciones_horario_guardar(int $idHorario, string $fecha, string $tipo, ?string $entrada, ?string $salida, ?string $colacionInicio, ?string $colacionFin, string $observacion = ''): array
    {
        $tipo = strtoupper(trim($tipo));
        $observacion = trim($observacion);
        $observacion = $observacion !== '' ? substr($observacion, 0, 1000) : null;
        $tipos = array_keys(excepciones_horario_tipos());

        if ($idHorario <= 0) {
            return ['ok' => false, 'errores' => ['Horario no valido para registrar excepcion.']];
        }

        if (!in_array($tipo, $tipos, true)) {
            return ['ok' => false, 'errores' => ['Tipo de excepcion no valido.']];
        }

        if (!excepciones_horario_fecha_valida($fecha)) {
            return ['ok' => false, 'errores' => ['Fecha de excepcion no valida.']];
        }

        $entrada = excepciones_horario_normalizar_hora($entrada);
        $salida = excepciones_horario_normalizar_hora($salida);
        $colacionInicio = excepciones_horario_normalizar_hora($colacionInicio);
        $colacionFin = excepciones_horario_normalizar_hora($colacionFin);
        $horasPlanificadas = null;

        if (excepciones_horario_requiere_horario($tipo)) {
            if ($entrada === null || $salida === null) {
                return ['ok' => false, 'errores' => ['La excepcion requiere entrada y salida esperadas.']];
            }

            if (excepciones_horario_minutos($salida) <= excepciones_horario_minutos($entrada)) {
                return ['ok' => false, 'errores' => ['La salida esperada debe ser posterior a la entrada.']];
            }

            if (($colacionInicio === null && $colacionFin !== null) || ($colacionInicio !== null && $colacionFin === null)) {
                return ['ok' => false, 'errores' => ['Completa inicio y fin de colacion, o deja ambos vacios.']];
            }

            if ($colacionInicio !== null && $colacionFin !== null) {
                if (excepciones_horario_minutos($colacionFin) <= excepciones_horario_minutos($colacionInicio)
                    || excepciones_horario_minutos($colacionInicio) < excepciones_horario_minutos($entrada)
                    || excepciones_horario_minutos($colacionFin) > excepciones_horario_minutos($salida)) {
                    return ['ok' => false, 'errores' => ['La colacion debe estar dentro de la jornada excepcional.']];
                }
            }

            $horasPlanificadas = excepciones_horario_horas_planificadas($entrada, $salida, $colacionInicio, $colacionFin);
        } else {
            $entrada = null;
            $salida = null;
            $colacionInicio = null;
            $colacionFin = null;
        }

        try {
            $statement = db()->prepare(
                'INSERT INTO excepciones_horario_practicante (
                    id_horario,
                    fecha,
                    tipo_excepcion,
                    entrada_esperada,
                    salida_esperada,
                    colacion_inicio,
                    colacion_fin,
                    horas_planificadas,
                    observacion,
                    activo
                 ) VALUES (
                    :id_horario,
                    :fecha,
                    :tipo_excepcion,
                    :entrada_esperada,
                    :salida_esperada,
                    :colacion_inicio,
                    :colacion_fin,
                    :horas_planificadas,
                    :observacion,
                    1
                 )
                 ON DUPLICATE KEY UPDATE
                    entrada_esperada = VALUES(entrada_esperada),
                    salida_esperada = VALUES(salida_esperada),
                    colacion_inicio = VALUES(colacion_inicio),
                    colacion_fin = VALUES(colacion_fin),
                    horas_planificadas = VALUES(horas_planificadas),
                    observacion = VALUES(observacion),
                    activo = 1'
            );
            $statement->execute([
                'id_horario'        => $idHorario,
                'fecha'             => $fecha,
                'tipo_excepcion'    => $tipo,
                'entrada_esperada'  => $entrada,
                'salida_esperada'   => $salida,
                'colacion_inicio'   => $colacionInicio,
                'colacion_fin'      => $colacionFin,
                'horas_planificadas' => $horasPlanificadas,
                'observacion'       => $observacion,
            ]);
        } catch (Throwable $exception) {
            error_log('Error al guardar excepcion de horario: ' . $exception->getMessage());

            return ['ok' => false, 'errores' => ['No fue posible guardar la excepcion de horario.']];
        }

        // Recuperar el id de la excepción creada o actualizada (ON DUPLICATE KEY no siempre retorna lastInsertId)
        $idExcepcion = (int) db()->lastInsertId();
        if ($idExcepcion === 0) {
            $stmtBuscar = db()->prepare(
                'SELECT id_excepcion FROM excepciones_horario_practicante
                 WHERE id_horario = :id_horario AND fecha = :fecha AND tipo_excepcion = :tipo_excepcion
                 LIMIT 1'
            );
            $stmtBuscar->execute([
                'id_horario'     => $idHorario,
                'fecha'          => $fecha,
                'tipo_excepcion' => $tipo,
            ]);
            $idExcepcion = (int) ($stmtBuscar->fetchColumn() ?: 0);
        }

        auditoria_registrar('EDITAR_PRACTICANTE', [
            'tabla_afectada' => 'excepciones_horario_practicante',
            'valores_nuevos' => [
                'id_horario'        => $idHorario,
                'fecha'             => $fecha,
                'tipo_excepcion'    => $tipo,
                'entrada_esperada'  => $entrada,
                'salida_esperada'   => $salida,
                'horas_planificadas' => $horasPlanificadas,
            ],
        ]);

        return ['ok' => true, 'errores' => [], 'id_excepcion' => $idExcepcion];
    }
}

if (!function_exists('excepciones_horario_anular')) {
    function excepciones_horario_anular(int $idExcepcion, int $idHorario, string $motivo): array
    {
        $motivo = trim($motivo);

        if ($idExcepcion <= 0 || $idHorario <= 0) {
            return ['ok' => false, 'errores' => ['Excepcion no valida.']];
        }

        if ($motivo === '') {
            return ['ok' => false, 'errores' => ['Debe indicar un motivo de anulacion.']];
        }

        $excepcion = excepciones_horario_obtener($idExcepcion);

        if ($excepcion === null || (int) $excepcion['id_horario'] !== $idHorario) {
            return ['ok' => false, 'errores' => ['Excepcion no encontrada para el horario vigente.']];
        }

        if ((int) $excepcion['activo'] !== 1) {
            return ['ok' => false, 'errores' => ['La excepcion ya se encuentra inactiva.']];
        }

        $observaciones = [];

        if (!empty($excepcion['observacion'])) {
            $observaciones[] = trim((string) $excepcion['observacion']);
        }

        $observaciones[] = 'Anulada logicamente: ' . $motivo;
        $observacion = substr(implode(' ', $observaciones), 0, 1000);

        try {
            $statement = db()->prepare(
                'UPDATE excepciones_horario_practicante
                 SET activo = 0,
                     observacion = :observacion
                 WHERE id_excepcion = :id_excepcion
                   AND id_horario = :id_horario'
            );
            $statement->execute([
                'observacion' => $observacion,
                'id_excepcion' => $idExcepcion,
                'id_horario' => $idHorario,
            ]);
        } catch (Throwable $exception) {
            error_log('Error al anular excepcion de horario: ' . $exception->getMessage());

            return ['ok' => false, 'errores' => ['No fue posible anular la excepcion de horario.']];
        }

        auditoria_registrar('EDITAR_PRACTICANTE', [
            'tabla_afectada' => 'excepciones_horario_practicante',
            'id_registro_afectado' => $idExcepcion,
            'valores_anteriores' => $excepcion,
            'valores_nuevos' => [
                'activo' => 0,
                'motivo' => $motivo,
                'observacion' => $observacion,
            ],
        ]);

        return ['ok' => true, 'errores' => []];
    }
}
