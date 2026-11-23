<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../Services/AuthService.php';
// Cargué el control de designaciones para no trasladar un líder sin cerrar su historial.
require_once __DIR__ . '/../Services/LiderazgoService.php';

if (!function_exists('areas_practicante_fecha_valida')) {
    function areas_practicante_fecha_valida(?string $fecha): bool
    {
        if ($fecha === null || $fecha === '') {
            return false;
        }

        $objetoFecha = DateTimeImmutable::createFromFormat('Y-m-d', $fecha);

        return $objetoFecha !== false && $objetoFecha->format('Y-m-d') === $fecha;
    }
}

if (!function_exists('areas_practicante_catalogo')) {
    function areas_practicante_catalogo(): array
    {
        $statement = db()->query(
            'SELECT id_area, nombre, descripcion
             FROM areas_practica
             WHERE activo = 1
             ORDER BY nombre ASC'
        );

        return $statement->fetchAll();
    }
}

if (!function_exists('areas_practicante_area_por_id')) {
    function areas_practicante_area_por_id(int $idArea): ?array
    {
        $statement = db()->prepare(
            'SELECT id_area, nombre, descripcion
             FROM areas_practica
             WHERE id_area = :id_area
               AND activo = 1
             LIMIT 1'
        );
        $statement->execute(['id_area' => $idArea]);
        $area = $statement->fetch();

        return is_array($area) ? $area : null;
    }
}

if (!function_exists('areas_practicante_vigente')) {
    function areas_practicante_vigente(int $idPractica): ?array
    {
        $statement = db()->prepare(
            'SELECT
                hap.id_historial_area,
                hap.id_practica,
                hap.id_area,
                hap.fecha_inicio,
                hap.fecha_fin,
                hap.observacion,
                hap.asignado_por,
                ap.nombre AS area_nombre,
                us.email AS asignado_por_email
             FROM historial_areas_practicante hap
             INNER JOIN areas_practica ap ON ap.id_area = hap.id_area
             LEFT JOIN usuarios_sistema us ON us.id_usuario = hap.asignado_por
             WHERE hap.id_practica = :id_practica
               AND hap.fecha_fin IS NULL
             ORDER BY hap.fecha_inicio DESC, hap.id_historial_area DESC
             LIMIT 1'
        );
        $statement->execute(['id_practica' => $idPractica]);
        $area = $statement->fetch();

        return is_array($area) ? $area : null;
    }
}

if (!function_exists('areas_practicante_historial')) {
    function areas_practicante_historial(int $idPractica): array
    {
        $statement = db()->prepare(
            'SELECT
                hap.id_historial_area,
                hap.fecha_inicio,
                hap.fecha_fin,
                hap.observacion,
                ap.nombre AS area_nombre,
                us.email AS asignado_por_email
             FROM historial_areas_practicante hap
             INNER JOIN areas_practica ap ON ap.id_area = hap.id_area
             LEFT JOIN usuarios_sistema us ON us.id_usuario = hap.asignado_por
             WHERE hap.id_practica = :id_practica
             ORDER BY hap.fecha_inicio DESC, hap.id_historial_area DESC
             LIMIT 20'
        );
        $statement->execute(['id_practica' => $idPractica]);

        return $statement->fetchAll();
    }
}

if (!function_exists('areas_practicante_cambiar')) {
    function areas_practicante_cambiar(int $idPractica, int $idArea, string $fechaInicio, string $observacion = ''): array
    {
        $observacion = trim($observacion);
        $observacion = $observacion !== '' ? substr($observacion, 0, 1000) : null;

        if ($idPractica <= 0) {
            return ['ok' => false, 'errores' => ['Practica no valida.']];
        }

        if (areas_practicante_area_por_id($idArea) === null) {
            return ['ok' => false, 'errores' => ['Debe seleccionar un area activa.']];
        }

        if (!areas_practicante_fecha_valida($fechaInicio)) {
            return ['ok' => false, 'errores' => ['Debe indicar una fecha de inicio valida para el area.']];
        }

        $areaActual = areas_practicante_vigente($idPractica);

        if ($areaActual !== null && (int) $areaActual['id_area'] === $idArea) {
            return ['ok' => false, 'errores' => ['El area seleccionada ya esta vigente para esta practica.']];
        }

        $pdo = db();
        $actor = auth_usuario_actual_id();
        $idHistorialNuevo = null;

        try {
            $pdo->beginTransaction();

            // Coordiné el cambio de pertenencia con cualquier designación simultánea sobre esta práctica.
            liderazgos_bloquear_practica($idPractica);
            liderazgos_exigir_practica_sin_lider($idPractica);

            $statement = $pdo->prepare(
                'UPDATE historial_areas_practicante
                 SET fecha_fin = CASE
                    WHEN fecha_inicio < :fecha_inicio_comparar THEN DATE_SUB(:fecha_inicio_calcular, INTERVAL 1 DAY)
                    ELSE fecha_inicio
                 END
                 WHERE id_practica = :id_practica
                   AND fecha_fin IS NULL'
            );
            $statement->execute([
                'fecha_inicio_comparar' => $fechaInicio,
                'fecha_inicio_calcular' => $fechaInicio,
                'id_practica' => $idPractica,
            ]);

            $statement = $pdo->prepare(
                'INSERT INTO historial_areas_practicante (
                    id_practica,
                    id_area,
                    fecha_inicio,
                    fecha_fin,
                    observacion,
                    asignado_por
                 ) VALUES (
                    :id_practica,
                    :id_area,
                    :fecha_inicio,
                    NULL,
                    :observacion,
                    :asignado_por
                 )'
            );
            $statement->execute([
                'id_practica' => $idPractica,
                'id_area' => $idArea,
                'fecha_inicio' => $fechaInicio,
                'observacion' => $observacion,
                'asignado_por' => $actor,
            ]);
            $idHistorialNuevo = (int) $pdo->lastInsertId();

            $pdo->commit();
        } catch (DomainException $exception) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            return ['ok'=>false, 'errores'=>[$exception->getMessage()]];
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Error al cambiar area de practicante: ' . $exception->getMessage());

            return ['ok' => false, 'errores' => ['No fue posible cambiar el area.']];
        }

        auditoria_registrar('CAMBIAR_AREA', [
            'tabla_afectada' => 'historial_areas_practicante',
            'id_registro_afectado' => $idHistorialNuevo,
            'valores_anteriores' => $areaActual,
            'valores_nuevos' => [
                'id_practica' => $idPractica,
                'id_area' => $idArea,
                'fecha_inicio' => $fechaInicio,
                'observacion' => $observacion,
                'asignado_por' => $actor,
            ],
        ]);

        return ['ok' => true, 'errores' => [], 'id_historial_area' => $idHistorialNuevo];
    }
}
