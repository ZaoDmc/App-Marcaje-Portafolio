<?php

declare(strict_types=1);

// Incluí REVISADA en los acumulados para conservar las horas y extras históricas aprobadas.

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once SERVICES_PATH . '/ReglasAsistenciaService.php';

/*
|--------------------------------------------------------------------------
| Mi historial - PRACTICANTE
|--------------------------------------------------------------------------
|
| Vista de solo lectura para consultar prácticas, jornadas, marcas vigentes y
| avance de horas del practicante autenticado. No modifica asistencia ni datos
| de práctica; esas acciones pertenecen a flujos separados.
|
*/

auth_requiere_rol('PRACTICANTE');

if (!function_exists('historial_practicante_id')) {
    function historial_practicante_id(): ?int
    {
        $idPracticante = auth_practicante_actual_id();

        return $idPracticante !== null && $idPracticante > 0 ? $idPracticante : null;
    }
}

if (!function_exists('historial_fecha_valida')) {
    function historial_fecha_valida(?string $fecha): bool
    {
        if ($fecha === null || $fecha === '') {
            return false;
        }

        $objetoFecha = DateTimeImmutable::createFromFormat('Y-m-d', $fecha);

        return $objetoFecha !== false && $objetoFecha->format('Y-m-d') === $fecha;
    }
}

if (!function_exists('historial_fecha_formato')) {
    function historial_fecha_formato(?string $fecha): string
    {
        if ($fecha === null || $fecha === '') {
            return 'No registrada';
        }

        $timestamp = strtotime($fecha);

        return $timestamp === false ? $fecha : date('d-m-Y', $timestamp);
    }
}

if (!function_exists('historial_fecha_hora_formato')) {
    function historial_fecha_hora_formato(?string $fechaHora): string
    {
        if ($fechaHora === null || $fechaHora === '') {
            return 'No registrada';
        }

        $timestamp = strtotime($fechaHora);

        return $timestamp === false ? $fechaHora : date('d-m-Y H:i', $timestamp);
    }
}

if (!function_exists('historial_estado_badge')) {
    function historial_estado_badge(?string $estado): string
    {
        return match ($estado) {
            'VALIDA' => 'success',
            'OBSERVADA' => 'warning',
            'INCOMPLETA' => 'secondary',
            'PENDIENTE_REVISION' => 'info',
            'ANULADA' => 'dark',
            // Agregué el tono de las jornadas cerradas por revisión.
            'REVISADA' => 'success',
            'CORREGIDA' => 'primary',
            default => 'secondary',
        };
    }
}

if (!function_exists('historial_practicas')) {
    function historial_practicas(int $idPracticante): array
    {
        $statement = db()->prepare(
            'SELECT
                pr.*,
                tp.nombre AS tipo_practica_nombre,
                ep.nombre AS estado_practica_nombre
             FROM practicas pr
             INNER JOIN tipos_practica tp ON tp.id_tipo_practica = pr.id_tipo_practica
             INNER JOIN estados_practica ep ON ep.id_estado_practica = pr.id_estado_practica
             WHERE pr.id_practicante = :id_practicante
               AND pr.activo = 1
             ORDER BY pr.fecha_inicio_practica DESC, pr.id_practica DESC'
        );
        $statement->execute(['id_practicante' => $idPracticante]);

        return $statement->fetchAll();
    }
}

if (!function_exists('historial_practica_por_id')) {
    function historial_practica_por_id(array $practicas, int $idPractica): ?array
    {
        foreach ($practicas as $practica) {
            if ((int) $practica['id_practica'] === $idPractica) {
                return $practica;
            }
        }

        return null;
    }
}

if (!function_exists('historial_filtros')) {
    function historial_filtros(): array
    {
        $desde = query_param('desde');
        $hasta = query_param('hasta');

        $desde = is_string($desde) && historial_fecha_valida($desde) ? $desde : date('Y-m-d', strtotime('-30 days'));
        $hasta = is_string($hasta) && historial_fecha_valida($hasta) ? $hasta : date('Y-m-d');

        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        return [
            'desde' => $desde,
            'hasta' => $hasta,
        ];
    }
}

if (!function_exists('historial_jornadas')) {
    function historial_jornadas(int $idPractica, string $desde, string $hasta): array
    {
        $statement = db()->prepare(
            'SELECT *
             FROM jornadas_asistencia
             WHERE id_practica = :id_practica
               AND fecha BETWEEN :desde AND :hasta
             ORDER BY fecha DESC, id_jornada DESC'
        );
        $statement->execute([
            'id_practica' => $idPractica,
            'desde' => $desde,
            'hasta' => $hasta,
        ]);

        return $statement->fetchAll();
    }
}

if (!function_exists('historial_marcas_por_jornada')) {
    function historial_marcas_por_jornada(array $jornadas): array
    {
        if ($jornadas === []) {
            return [];
        }

        $ids = array_map(static fn (array $jornada): int => (int) $jornada['id_jornada'], $jornadas);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $statement = db()->prepare(
            'SELECT
                ma.*,
                tm.codigo,
                tm.nombre AS tipo_marca,
                tm.orden
             FROM marcas_asistencia ma
             INNER JOIN tipos_marca tm ON tm.id_tipo_marca = ma.id_tipo_marca
             WHERE ma.id_jornada IN (' . $placeholders . ')
               AND ma.activo = 1
               AND ma.anulada = 0
             ORDER BY ma.fecha_hora ASC, tm.orden ASC'
        );
        $statement->execute($ids);

        $marcasPorJornada = [];

        foreach ($statement->fetchAll() as $marca) {
            $idJornada = (int) $marca['id_jornada'];
            $marcasPorJornada[$idJornada] ??= [];
            $marcasPorJornada[$idJornada][] = $marca;
        }

        return $marcasPorJornada;
    }
}

if (!function_exists('historial_resumen')) {
    function historial_resumen(int $idPractica, int $horasRequeridas, float $horasPrevias = 0.0): array
    {
        $statement = db()->prepare(
            'SELECT
                COUNT(*) AS total_jornadas,
                /*
                 * Total de horas realizadas: suma las horas base más las horas extra aprobadas
                 * para todas las jornadas en estado VALIDA, OBSERVADA, CORREGIDA o REVISADA.
                 */
                SUM(CASE 
                    WHEN estado_jornada IN ("VALIDA", "OBSERVADA", "CORREGIDA", "REVISADA")
                    THEN (horas_calculadas + COALESCE(horas_extra_aprobadas, 0)) 
                    ELSE 0 
                END) AS horas_realizadas,
                SUM(CASE WHEN estado_jornada = "VALIDA" THEN 1 ELSE 0 END) AS jornadas_validas,
                SUM(CASE WHEN estado_jornada = "OBSERVADA" THEN 1 ELSE 0 END) AS jornadas_observadas,
                SUM(CASE WHEN estado_jornada = "INCOMPLETA" THEN 1 ELSE 0 END) AS jornadas_incompletas
             FROM jornadas_asistencia
             WHERE id_practica = :id_practica'
        );
        $statement->execute(['id_practica' => $idPractica]);
        $resumen = $statement->fetch() ?: [];

        $horasRealizadas = round((float) ($resumen['horas_realizadas'] ?? 0) + $horasPrevias, 2);
        $horasRestantes = max(0.0, round($horasRequeridas - $horasRealizadas, 2));
        $porcentaje = $horasRequeridas > 0 ? min(100.0, round(($horasRealizadas / $horasRequeridas) * 100, 1)) : 0.0;

        return [
            'total_jornadas' => (int) ($resumen['total_jornadas'] ?? 0),
            'horas_realizadas' => $horasRealizadas,
            'horas_restantes' => $horasRestantes,
            'porcentaje_avance' => $porcentaje,
            'jornadas_validas' => (int) ($resumen['jornadas_validas'] ?? 0),
            'jornadas_observadas' => (int) ($resumen['jornadas_observadas'] ?? 0),
            'jornadas_incompletas' => (int) ($resumen['jornadas_incompletas'] ?? 0),
        ];
    }
}

if (!function_exists('historial_marcas_texto')) {
    function historial_marcas_texto(array $marcas): string
    {
        if ($marcas === []) {
            return 'Sin marcas vigentes';
        }

        $partes = [];

        foreach ($marcas as $marca) {
            $partes[] = (string) $marca['tipo_marca'] . ': ' . historial_fecha_hora_formato((string) $marca['fecha_hora']);
        }

        return implode(' | ', $partes);
    }
}

$errores = [];
$idPracticante = historial_practicante_id();
$practicas = [];
$practicaSeleccionada = null;
$filtros = historial_filtros();
$jornadas = [];
$marcasPorJornada = [];
$resumen = null;

if ($idPracticante === null) {
    $errores[] = 'Tu cuenta no está vinculada a un registro de practicante.';
} else {
    try {
        $practicas = historial_practicas($idPracticante);

        if ($practicas === []) {
            $errores[] = 'No existen prácticas activas o históricas disponibles para mostrar.';
        } else {
            $idPracticaSolicitada = query_param('id_practica');
            $idPracticaSolicitada = is_string($idPracticaSolicitada) && ctype_digit($idPracticaSolicitada)
                ? (int) $idPracticaSolicitada
                : (int) $practicas[0]['id_practica'];

            $practicaSeleccionada = historial_practica_por_id($practicas, $idPracticaSolicitada) ?? $practicas[0];
            $jornadas = historial_jornadas((int) $practicaSeleccionada['id_practica'], $filtros['desde'], $filtros['hasta']);
            $marcasPorJornada = historial_marcas_por_jornada($jornadas);
            $resumen = historial_resumen((int) $practicaSeleccionada['id_practica'], (int) $practicaSeleccionada['horas_requeridas'], (float) ($practicaSeleccionada['horas_previas'] ?? 0));
        }
    } catch (Throwable $exception) {
        error_log('Error al cargar historial de asistencia: ' . $exception->getMessage());
        $errores[] = 'No fue posible cargar tu historial. Verifica que la base de datos esté importada.';
    }
}
$pageTitle = 'Mi historial';
// Preparé los datos del layout antes de mostrar la vista.
require SRC_PATH . '/Controllers/Shared/LayoutContext.php';

require_once VIEWS_PATH . '/practicante/mi-historial.view.php';
