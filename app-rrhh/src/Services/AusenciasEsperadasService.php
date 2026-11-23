<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/ReglasAsistenciaService.php';

if (!function_exists('ausencias_esperadas_fecha_valida')) {
    function ausencias_esperadas_fecha_valida(?string $fecha): bool
    {
        if ($fecha === null || $fecha === '') {
            return false;
        }

        $objetoFecha = DateTimeImmutable::createFromFormat('Y-m-d', $fecha);

        return $objetoFecha !== false && $objetoFecha->format('Y-m-d') === $fecha;
    }
}

if (!function_exists('ausencias_esperadas_rango_fechas')) {
    function ausencias_esperadas_rango_fechas(string $desde, string $hasta): array
    {
        $inicio = new DateTimeImmutable($desde);
        $fin = new DateTimeImmutable($hasta);
        $fechas = [];

        while ($inicio <= $fin) {
            $fechas[] = $inicio->format('Y-m-d');
            $inicio = $inicio->modify('+1 day');
        }

        return $fechas;
    }
}

if (!function_exists('ausencias_esperadas_practicas_activas')) {
    function ausencias_esperadas_practicas_activas(): array
    {
        $statement = db()->query(
            'SELECT
                pr.id_practica,
                pr.id_practicante,
                pr.fecha_inicio_practica,
                p.email,
                p.nombre_1,
                p.apellido_paterno
             FROM practicas pr
             INNER JOIN practicantes p ON p.id_practicante = pr.id_practicante
             WHERE pr.activo = 1
               AND p.activo = 1
             ORDER BY pr.id_practica ASC'
        );

        return $statement->fetchAll();
    }
}

if (!function_exists('ausencias_esperadas_jornada_existe')) {
    function ausencias_esperadas_jornada_existe(int $idPractica, string $fecha): bool
    {
        $statement = db()->prepare(
            'SELECT COUNT(*)
             FROM jornadas_asistencia
             WHERE id_practica = :id_practica
               AND fecha = :fecha'
        );
        $statement->execute([
            'id_practica' => $idPractica,
            'fecha' => $fecha,
        ]);

        return (int) $statement->fetchColumn() > 0;
    }
}

if (!function_exists('ausencias_esperadas_generar')) {
    function ausencias_esperadas_generar(string $desde, string $hasta, ?int $actor = null): array
    {
        // Restringí también el servicio para que SUPERVISOR no ejecute la generación por otra entrada.
        if (!auth_check() || auth_rol_activo() !== 'ADMIN') { return ['ok'=>false,'errores'=>['Solo ADMIN puede generar ausencias esperadas.']]; }
        if (!ausencias_esperadas_fecha_valida($desde) || !ausencias_esperadas_fecha_valida($hasta)) {
            return ['ok' => false, 'errores' => ['Debe indicar un rango de fechas valido.']];
        }

        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        $hoy = date('Y-m-d');

        if ($hasta >= $hoy) {
            return ['ok' => false, 'errores' => ['Solo se generan ausencias para fechas anteriores a hoy.']];
        }

        $fechas = ausencias_esperadas_rango_fechas($desde, $hasta);

        if (count($fechas) > 31) {
            return ['ok' => false, 'errores' => ['El rango maximo permitido es de 31 dias.']];
        }

        $practicas = ausencias_esperadas_practicas_activas();
        $insertadas = 0;
        $omitidas = 0;
        $actor = $actor ?? auth_usuario_actual_id();

        try {
            db()->beginTransaction();

            $insert = db()->prepare(
                'INSERT INTO jornadas_asistencia (
                    id_practica,
                    fecha,
                    estado_jornada,
                    horas_calculadas,
                    observacion,
                    creada_por,
                    flag_ausencia_esperada
                 ) VALUES (
                    :id_practica,
                    :fecha,
                    "INCOMPLETA",
                    0.00,
                    :observacion,
                    :creada_por,
                    1
                 )'
            );

            foreach ($practicas as $practica) {
                foreach ($fechas as $fecha) {
                    if (!empty($practica['fecha_inicio_practica']) && $fecha < (string) $practica['fecha_inicio_practica']) {
                        $omitidas++;
                        continue;
                    }

                    if (ausencias_esperadas_jornada_existe((int) $practica['id_practica'], $fecha)) {
                        $omitidas++;
                        continue;
                    }

                    $horario = asistencia_horario_vigente_dia((int) $practica['id_practica'], $fecha);

                    if ($horario === null) {
                        $omitidas++;
                        continue;
                    }

                    $insert->execute([
                        'id_practica' => (int) $practica['id_practica'],
                        'fecha' => $fecha,
                        'observacion' => 'Ausencia esperada generada por dia planificado sin marcas.',
                        'creada_por' => $actor,
                    ]);
                    $insertadas++;
                }
            }

            db()->commit();
        } catch (Throwable $exception) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }

            error_log('Error al generar ausencias esperadas: ' . $exception->getMessage());

            return ['ok' => false, 'errores' => ['No fue posible generar ausencias esperadas.']];
        }

        auditoria_registrar('CORREGIR_ASISTENCIA', [
            'tabla_afectada' => 'jornadas_asistencia',
            'valores_nuevos' => [
                'accion' => 'GENERAR_AUSENCIAS_ESPERADAS',
                'desde' => $desde,
                'hasta' => $hasta,
                'insertadas' => $insertadas,
                'omitidas' => $omitidas,
            ],
        ]);

        return [
            'ok' => true,
            'errores' => [],
            'insertadas' => $insertadas,
            'omitidas' => $omitidas,
        ];
    }
}
