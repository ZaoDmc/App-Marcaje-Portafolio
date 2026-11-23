<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

// Agregué liderazgo por práctica y área, sin convertir al líder en SUPERVISOR.
if (!function_exists('liderazgos_requiere_admin')) {
    function liderazgos_requiere_admin(): void
    {
        if (!auth_check() || auth_rol_activo() !== 'ADMIN') {
            throw new DomainException('Solo ADMIN puede administrar los líderes de área.');
        }
    }
}

// Detecté la migración sin crear tablas al visitar una pantalla ni ocultar fallos de conexión.
if (!function_exists('liderazgos_disponible')) {
    function liderazgos_disponible(): bool
    {
        if (db()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            return db()->query("SELECT name FROM sqlite_master WHERE type='table' AND name='liderazgos_area'")->fetchColumn() !== false;
        }
        return db()->query("SHOW TABLES LIKE 'liderazgos_area'")->fetchColumn() !== false;
    }
}

// Listé las áreas con el líder vigente y el número de integrantes activos.
if (!function_exists('liderazgos_areas')) {
    function liderazgos_areas(): array
    {
        liderazgos_requiere_admin();
        // Usé la fecha PHP del proyecto para no depender de la zona horaria de MariaDB en Hostinger.
        $s = db()->prepare('SELECT a.id_area, a.nombre, a.activo, l.id_liderazgo, l.id_practica,
                p.nombre_1, p.apellido_paterno,
                (SELECT COUNT(DISTINCT h.id_practica) FROM historial_areas_practicante h
                 JOIN practicas pr ON pr.id_practica=h.id_practica
                 JOIN practicantes integrante ON integrante.id_practicante=pr.id_practicante
                 WHERE h.id_area=a.id_area AND h.fecha_fin IS NULL AND h.fecha_inicio<=?
                   AND pr.activo=1 AND integrante.activo=1) AS integrantes
            FROM areas_practica a
            LEFT JOIN liderazgos_area l ON l.id_area=a.id_area AND l.fecha_fin IS NULL
            LEFT JOIN practicas pr_lider ON pr_lider.id_practica=l.id_practica
            LEFT JOIN practicantes p ON p.id_practicante=pr_lider.id_practicante
            ORDER BY a.activo DESC, a.nombre');
        $s->execute([date('Y-m-d')]);
        return $s->fetchAll();
    }
}

if (!function_exists('liderazgos_integrantes')) {
    function liderazgos_integrantes(int $idArea): array
    {
        liderazgos_requiere_admin();
        // Excluí pertenencias futuras y prácticas cerradas de las candidaturas.
        // Incluí nombre completo y RUT para buscar candidaturas sin ampliar el área ni las condiciones de elegibilidad.
        $s = db()->prepare('SELECT DISTINCT pr.id_practica, p.id_practicante, p.nombre_1, p.nombre_2,
                p.apellido_paterno, p.apellido_materno, p.rut_numero, p.rut_dv, p.email, ep.nombre AS estado_practica
            FROM historial_areas_practicante h
            JOIN practicas pr ON pr.id_practica=h.id_practica
            JOIN practicantes p ON p.id_practicante=pr.id_practicante
            JOIN estados_practica ep ON ep.id_estado_practica=pr.id_estado_practica
            WHERE h.id_area=? AND h.fecha_fin IS NULL AND h.fecha_inicio<=?
              AND pr.activo=1 AND p.activo=1
              AND ep.nombre IN ("PENDIENTE_INICIO", "EN_PROGRESO")
            ORDER BY p.apellido_paterno, p.nombre_1, pr.id_practica');
        $s->execute([$idArea, date('Y-m-d')]);
        return $s->fetchAll();
    }
}

// Recuperé responsables, motivos y fechas sin ocultar las designaciones ya finalizadas.
if (!function_exists('liderazgos_historial')) {
    function liderazgos_historial(int $idArea): array
    {
        liderazgos_requiere_admin();
        $s = db()->prepare('SELECT l.*, p.nombre_1, p.apellido_paterno,
                asignador.email AS asignado_email, finalizador.email AS finalizado_email
            FROM liderazgos_area l
            JOIN practicas pr ON pr.id_practica=l.id_practica
            JOIN practicantes p ON p.id_practicante=pr.id_practicante
            JOIN usuarios_sistema asignador ON asignador.id_usuario=l.asignado_por
            LEFT JOIN usuarios_sistema finalizador ON finalizador.id_usuario=l.finalizado_por
            WHERE l.id_area=? ORDER BY l.id_liderazgo DESC');
        $s->execute([$idArea]);
        return $s->fetchAll();
    }
}

// Usé lecturas bloqueantes para coordinar designaciones con cambios de área y bajas.
if (!function_exists('liderazgos_bloquear_practica')) {
    function liderazgos_bloquear_practica(int $idPractica): void
    {
        $s = db()->prepare('SELECT id_practica FROM practicas WHERE id_practica=? FOR UPDATE');
        $s->execute([$idPractica]);
        if ($s->fetchColumn() === false) {
            throw new DomainException('La práctica seleccionada no existe.');
        }
    }
}

// Los flujos existentes siguen funcionando antes de instalar la migración: todavía no puede haber líderes.
if (!function_exists('liderazgos_exigir_practica_sin_lider')) {
    function liderazgos_exigir_practica_sin_lider(int $idPractica): void
    {
        if (!liderazgos_disponible()) { return; }
        $s = db()->prepare('SELECT id_liderazgo FROM liderazgos_area WHERE id_practica=? AND fecha_fin IS NULL FOR UPDATE');
        $s->execute([$idPractica]);
        if ($s->fetchColumn() !== false) {
            throw new DomainException('Retira primero el liderazgo de esta práctica desde ADMIN → Notas, indicando el motivo.');
        }
    }
}

if (!function_exists('liderazgos_exigir_area_sin_lider')) {
    function liderazgos_exigir_area_sin_lider(int $idArea): void
    {
        if (!liderazgos_disponible()) { return; }
        $s = db()->prepare('SELECT id_liderazgo FROM liderazgos_area WHERE id_area=? AND fecha_fin IS NULL FOR UPDATE');
        $s->execute([$idArea]);
        if ($s->fetchColumn() !== false) {
            throw new DomainException('Retira primero al líder desde ADMIN → Notas antes de desactivar el área.');
        }
    }
}

// Guardé reemplazo y cierre junto con su auditoría; el formulario identifica al líder que el usuario vio.
if (!function_exists('liderazgos_guardar')) {
    function liderazgos_guardar(int $idArea, int $idPractica, int $liderEsperado, string $motivo): array
    {
        liderazgos_requiere_admin();
        $motivo = trim($motivo);
        if ($idArea <= 0 || $idPractica < 0 || $liderEsperado < 0 || $motivo === '' || mb_strlen($motivo) > 500) {
            throw new DomainException('Selecciona un área válida e indica un motivo de hasta 500 caracteres.');
        }
        if (!liderazgos_disponible()) {
            // Referencié el nombre fechado y numerado sin cambiar el contenido de la migración.
            throw new DomainException('Falta aplicar database/2026-10-19_patch_liderazgos_area_009.sql.');
        }
        $pdo = db();
        $pdo->beginTransaction();
        try {
            // Conservé el orden práctica → área para las operaciones que pueden competir entre sí.
            if ($idPractica > 0) { liderazgos_bloquear_practica($idPractica); }
            $s = $pdo->prepare('SELECT activo FROM areas_practica WHERE id_area=? FOR UPDATE');
            $s->execute([$idArea]);
            $activa = $s->fetchColumn();
            if ($activa === false || ($idPractica > 0 && (int)$activa !== 1)) {
                throw new DomainException('Selecciona un área activa.');
            }
            $s = $pdo->prepare('SELECT * FROM liderazgos_area WHERE id_area=? AND fecha_fin IS NULL FOR UPDATE');
            $s->execute([$idArea]);
            $anterior = $s->fetch() ?: null;
            if ((int)($anterior['id_liderazgo'] ?? 0) !== $liderEsperado) {
                throw new DomainException('El liderazgo cambió mientras editabas. Recarga la página antes de continuar.');
            }
            if ($idPractica === 0 && $anterior === null) {
                throw new DomainException('Esta área ya no tiene un líder vigente.');
            }
            if ($idPractica > 0) {
                $candidatos = array_column(liderazgos_integrantes($idArea), 'id_practica');
                if (!in_array($idPractica, array_map('intval', $candidatos), true)) {
                    throw new DomainException('El candidato debe pertenecer actualmente al área y tener una práctica habilitada.');
                }
                if ($anterior !== null && (int)$anterior['id_practica'] === $idPractica) {
                    throw new DomainException('La práctica seleccionada ya tiene el liderazgo de esta área.');
                }
            }
            $actor = auth_usuario_actual_id();
            $ahora = date('Y-m-d H:i:s');
            if ($anterior !== null) {
                $s = $pdo->prepare('UPDATE liderazgos_area SET fecha_fin=?, finalizado_por=?, motivo_fin=? WHERE id_liderazgo=?');
                $s->execute([$ahora, $actor, $motivo, $anterior['id_liderazgo']]);
            }
            $idNuevo = null;
            if ($idPractica > 0) {
                $s = $pdo->prepare('INSERT INTO liderazgos_area
                    (id_area,id_practica,fecha_inicio,asignado_por,motivo_designacion) VALUES (?,?,?,?,?)');
                $s->execute([$idArea, $idPractica, $ahora, $actor, $motivo]);
                $idNuevo = (int)$pdo->lastInsertId();
            }
            $evento = $idPractica === 0 ? 'RETIRAR_LIDER_AREA' : ($anterior === null ? 'DESIGNAR_LIDER_AREA' : 'REEMPLAZAR_LIDER_AREA');
            if (!auditoria_registrar($evento, [
                'tabla_afectada'=>'liderazgos_area', 'id_registro_afectado'=>$idNuevo ?? $anterior['id_liderazgo'],
                'valores_anteriores'=>$anterior,
                'valores_nuevos'=>['id_liderazgo'=>$idNuevo,'id_area'=>$idArea,'id_practica'=>$idPractica ?: null,'motivo'=>$motivo],
            ])) {
                throw new RuntimeException('No fue posible registrar la auditoría del liderazgo.');
            }
            $pdo->commit();
            return ['ok'=>true];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            throw $e;
        }
    }
}
