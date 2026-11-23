<?php

declare(strict_types=1);

require_once __DIR__ . '/AreaPracticanteModel.php';

if (!function_exists('areas_jornada_fecha_hora_valida')) {
    function areas_jornada_fecha_hora_valida(?string $fechaHora): bool
    {
        if ($fechaHora === null || $fechaHora === '') {
            return false;
        }

        $normalizada = str_replace('T', ' ', $fechaHora);
        $objetoFecha = DateTimeImmutable::createFromFormat('Y-m-d H:i', $normalizada);

        return $objetoFecha !== false && $objetoFecha->format('Y-m-d H:i') === $normalizada;
    }
}

if (!function_exists('areas_jornada_fecha_hora_sql')) {
    function areas_jornada_fecha_hora_sql(string $fechaHora): string
    {
        return str_replace('T', ' ', $fechaHora) . ':00';
    }
}

if (!function_exists('areas_jornada_horas')) {
    function areas_jornada_horas(string $horaInicioSql, string $horaFinSql): float
    {
        $inicio = strtotime($horaInicioSql);
        $fin = strtotime($horaFinSql);

        if ($inicio === false || $fin === false || $fin <= $inicio) {
            return 0.0;
        }

        return round(($fin - $inicio) / 3600, 2);
    }
}

if (!function_exists('areas_jornada_bloques')) {
    function areas_jornada_bloques(int $idJornada): array
    {
        $statement = db()->prepare(
            'SELECT
                baj.id_bloque_area,
                baj.id_jornada,
                baj.id_area,
                baj.hora_inicio,
                baj.hora_fin,
                baj.horas_asignadas,
                baj.asignado_por,
                baj.created_at,
                ap.nombre AS area_nombre,
                us.email AS asignado_por_email
             FROM bloques_area_jornada baj
             INNER JOIN areas_practica ap ON ap.id_area = baj.id_area
             LEFT JOIN usuarios_sistema us ON us.id_usuario = baj.asignado_por
             WHERE baj.id_jornada = :id_jornada
             ORDER BY baj.hora_inicio ASC, baj.id_bloque_area ASC'
        );
        $statement->execute(['id_jornada' => $idJornada]);

        return $statement->fetchAll();
    }
}

if (!function_exists('areas_jornada_tiene_solape')) {
    function areas_jornada_tiene_solape(int $idJornada, string $horaInicioSql, string $horaFinSql): bool
    {
        $statement = db()->prepare(
            'SELECT COUNT(*)
             FROM bloques_area_jornada
             WHERE id_jornada = :id_jornada
               AND hora_inicio < :hora_fin
               AND COALESCE(hora_fin, hora_inicio) > :hora_inicio'
        );
        $statement->execute([
            'id_jornada' => $idJornada,
            'hora_inicio' => $horaInicioSql,
            'hora_fin' => $horaFinSql,
        ]);

        return (int) $statement->fetchColumn() > 0;
    }
}

if (!function_exists('areas_jornada_registrar_bloque')) {
    function areas_jornada_registrar_bloque(array $jornada, int $idArea, string $horaInicio, string $horaFin): array
    {
        $idJornada = (int) ($jornada['id_jornada'] ?? 0);
        $fechaJornada = (string) ($jornada['fecha'] ?? '');

        if ($idJornada <= 0 || $fechaJornada === '') {
            return ['ok' => false, 'errores' => ['Jornada no valida.']];
        }

        if (areas_practicante_area_por_id($idArea) === null) {
            return ['ok' => false, 'errores' => ['Debe seleccionar un area activa.']];
        }

        if (!areas_jornada_fecha_hora_valida($horaInicio) || !areas_jornada_fecha_hora_valida($horaFin)) {
            return ['ok' => false, 'errores' => ['Debe indicar inicio y fin validos para el bloque.']];
        }

        $horaInicioSql = areas_jornada_fecha_hora_sql($horaInicio);
        $horaFinSql = areas_jornada_fecha_hora_sql($horaFin);

        if (substr($horaInicioSql, 0, 10) !== $fechaJornada || substr($horaFinSql, 0, 10) !== $fechaJornada) {
            return ['ok' => false, 'errores' => ['El bloque debe pertenecer a la fecha de la jornada.']];
        }

        $horasAsignadas = areas_jornada_horas($horaInicioSql, $horaFinSql);

        if ($horasAsignadas <= 0) {
            return ['ok' => false, 'errores' => ['La hora de fin debe ser posterior a la hora de inicio.']];
        }

        if (areas_jornada_tiene_solape($idJornada, $horaInicioSql, $horaFinSql)) {
            return ['ok' => false, 'errores' => ['El bloque se superpone con otro bloque de area de la jornada.']];
        }

        try {
            $statement = db()->prepare(
                'INSERT INTO bloques_area_jornada (
                    id_jornada,
                    id_area,
                    hora_inicio,
                    hora_fin,
                    horas_asignadas,
                    asignado_por
                 ) VALUES (
                    :id_jornada,
                    :id_area,
                    :hora_inicio,
                    :hora_fin,
                    :horas_asignadas,
                    :asignado_por
                 )'
            );
            $statement->execute([
                'id_jornada' => $idJornada,
                'id_area' => $idArea,
                'hora_inicio' => $horaInicioSql,
                'hora_fin' => $horaFinSql,
                'horas_asignadas' => $horasAsignadas,
                'asignado_por' => auth_usuario_actual_id(),
            ]);
            $idBloque = (int) db()->lastInsertId();
        } catch (Throwable $exception) {
            error_log('Error al registrar bloque de area: ' . $exception->getMessage());

            return ['ok' => false, 'errores' => ['No fue posible registrar el bloque de area.']];
        }

        auditoria_registrar('CAMBIAR_AREA', [
            'tabla_afectada' => 'bloques_area_jornada',
            'id_registro_afectado' => $idBloque,
            'valores_nuevos' => [
                'id_jornada' => $idJornada,
                'id_area' => $idArea,
                'hora_inicio' => $horaInicioSql,
                'hora_fin' => $horaFinSql,
                'horas_asignadas' => $horasAsignadas,
            ],
        ]);

        return ['ok' => true, 'errores' => [], 'id_bloque_area' => $idBloque];
    }
}
