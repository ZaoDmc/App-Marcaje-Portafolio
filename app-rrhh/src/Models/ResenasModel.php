<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';
// Implementé la función para asegurar que la tabla exista antes de operar, evitando errores en tiempo de ejecución - Alexander
if (!function_exists('resenas_asegurar_tabla')) {
    function resenas_asegurar_tabla(): void
    {
        $pdo = db();
        try {
            $existe = (bool) $pdo->query(
                "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'resenas_practica'"
            )->fetchColumn();
        } catch (Throwable) {
            $existe = false;
        }
        if ($existe) {
            return;
        }
        try {
            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS resenas_practica (
                    id_resena           INT AUTO_INCREMENT PRIMARY KEY,
                    id_practica         INT NOT NULL,
                    id_practicante      INT NOT NULL,
                    calificacion        TINYINT NOT NULL,
                    aspectos_destacados TEXT NULL,
                    canal_envio         VARCHAR(50) NOT NULL,
                    testimonio          TEXT NOT NULL,
                    archivo_autorizacion VARCHAR(255) NULL,
                    consentimiento_legal TINYINT(1) NOT NULL DEFAULT 1,
                    estado              ENUM('PENDIENTE', 'RECIBIDA', 'PUBLICADA') NOT NULL DEFAULT 'RECIBIDA',
                    fecha_envio         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    fecha_revision      DATETIME NULL,
                    id_supervisor_revisor INT NULL,
                    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    CONSTRAINT uk_resena_practica UNIQUE (id_practica),
                    CONSTRAINT fk_resena_practica FOREIGN KEY (id_practica) REFERENCES practicas(id_practica) ON DELETE CASCADE,
                    CONSTRAINT fk_resena_practicante FOREIGN KEY (id_practicante) REFERENCES practicantes(id_practicante) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
            );
        } catch (Throwable $e) {
            error_log('Error al asegurar tabla resenas_practica: ' . $e->getMessage());
        }
    }
}

// Creé la función para obtener la reseña enviada de una práctica específica - Alexander
if (!function_exists('resenas_obtener_por_practica')) {
    function resenas_obtener_por_practica(int $idPractica): ?array
    {
        resenas_asegurar_tabla();
        $stmt = db()->prepare('SELECT * FROM resenas_practica WHERE id_practica = :id_practica LIMIT 1');
        $stmt->execute(['id_practica' => $idPractica]);
        $resultado = $stmt->fetch();
        return is_array($resultado) ? $resultado : null;
    }
}

// Consulté una reseña por su ID único para uso de descargas del supervisor - Alexander
if (!function_exists('resenas_obtener_por_id')) {
    function resenas_obtener_por_id(int $idResena): ?array
    {
        resenas_asegurar_tabla();
        $stmt = db()->prepare('SELECT * FROM resenas_practica WHERE id_resena = :id_resena LIMIT 1');
        $stmt->execute(['id_resena' => $idResena]);
        $resultado = $stmt->fetch();
        return is_array($resultado) ? $resultado : null;
    }
}

// Desarrollé la función para guardar o actualizar una reseña y sincronizarla con la tabla acompañamiento - Alexander
if (!function_exists('resenas_guardar')) {
    function resenas_guardar(
        int $idPractica,
        int $idPracticante,
        int $calificacion,
        ?string $aspectos,
        string $canal,
        string $testimonio,
        ?string $archivoPdf,
        int $consentimiento = 1
    ): bool {
        resenas_asegurar_tabla();
        $pdo = db();
        try {
            $pdo->beginTransaction();
            // Inserté o actualicé la reseña del estudiante
            $stmt = $pdo->prepare(
                'INSERT INTO resenas_practica (
                    id_practica, id_practicante, calificacion, aspectos_destacados,
                    canal_envio, testimonio, archivo_autorizacion, consentimiento_legal, estado, fecha_envio
                ) VALUES (
                    :id_practica, :id_practicante, :calificacion, :aspectos,
                    :canal, :testimonio, :archivo, :consentimiento, "RECIBIDA", NOW()
                ) ON DUPLICATE KEY UPDATE
                    calificacion = VALUES(calificacion),
                    aspectos_destacados = VALUES(aspectos_destacados),
                    canal_envio = VALUES(canal_envio),
                    testimonio = VALUES(testimonio),
                    archivo_autorizacion = COALESCE(VALUES(archivo_autorizacion), archivo_autorizacion),
                    consentimiento_legal = VALUES(consentimiento_legal),
                    estado = "RECIBIDA",
                    updated_at = NOW()'
            );
            $stmt->execute([
                'id_practica' => $idPractica,
                'id_practicante' => $idPracticante,
                'calificacion' => $calificacion,
                'aspectos' => $aspectos,
                'canal' => $canal,
                'testimonio' => $testimonio,
                'archivo' => $archivoPdf,
                'consentimiento' => $consentimiento,
            ]);
            // Sincronicé la tabla acompañamiento marcando resena_recibida = 1
            $stmtAcom = $pdo->prepare(
                'INSERT INTO acompanamiento (id_practica, resena_recibida)
                 VALUES (:id_practica, 1)
                 ON DUPLICATE KEY UPDATE resena_recibida = 1, updated_at = NOW()'
            );
            $stmtAcom->execute(['id_practica' => $idPractica]);
            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Error al guardar reseña: ' . $e->getMessage());
            return false;
        }
    }
}

// Diseñé la consulta para listar a todos los practicantes activos y el estado de su reseña para el supervisor - Alexander
if (!function_exists('resenas_listar_para_supervisor')) {
    function resenas_listar_para_supervisor(): array
    {
        resenas_asegurar_tabla();
        $sql = 'SELECT 
                    p.id_practicante,
                    p.nombre_1,
                    p.apellido_paterno,
                    p.apellido_materno,
                    p.email,
                    COALESCE(c.nombre, "Sin carrera") AS carrera,
                    pr.id_practica,
                    rp.id_resena,
                    rp.calificacion,
                    rp.canal_envio,
                    rp.testimonio,
                    rp.archivo_autorizacion,
                    COALESCE(rp.estado, "PENDIENTE") AS estado_resena,
                    rp.fecha_envio
                FROM practicantes p
                INNER JOIN practicas pr ON pr.id_practica = (
                    SELECT pr2.id_practica
                    FROM practicas pr2
                    WHERE pr2.id_practicante = p.id_practicante
                      AND pr2.activo = 1
                    ORDER BY pr2.fecha_inicio_practica DESC, pr2.id_practica DESC
                    LIMIT 1
                )
                LEFT JOIN carreras c ON c.id_carrera = p.id_carrera
                LEFT JOIN resenas_practica rp ON rp.id_practica = pr.id_practica
                WHERE p.activo = 1
                ORDER BY rp.fecha_envio DESC, p.apellido_paterno ASC, p.nombre_1 ASC';
        $stmt = db()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }
}

// Calculé las métricas en tiempo real para las tarjetas del panel de supervisión - Alexander
if (!function_exists('resenas_obtener_metricas')) {
    function resenas_obtener_metricas(): array
    {
        resenas_asegurar_tabla();
        $lista = resenas_listar_para_supervisor();
        $pendientes = 0;
        $recibidas = 0;
        $publicadas = 0;
        foreach ($lista as $item) {
            $estado = strtoupper((string) ($item['estado_resena'] ?? 'PENDIENTE'));
            if ($estado === 'PUBLICADA') {
                $publicadas++;
            } elseif ($estado === 'RECIBIDA') {
                $recibidas++;
            } else {
                $pendientes++;
            }
        }
        return [
            'pendientes' => $pendientes,
            'recibidas' => $recibidas,
            'publicadas' => $publicadas,
        ];
    }
}

// Implementé la función para que el supervisor apruebe y publique la reseña - Alexander
if (!function_exists('resenas_cambiar_estado')) {
    function resenas_cambiar_estado(int $idResena, string $nuevoEstado, ?int $idSupervisor = null): bool
    {
        resenas_asegurar_tabla();
        $pdo = db();

        try {
            $pdo->beginTransaction();

            // Actualicé el estado de la reseña
            $stmt = $pdo->prepare(
                'UPDATE resenas_practica
         SET estado = :estado, fecha_revision = NOW(). id_supervisor_revisor = :id_sup updated_at = NOW()
         WHERE id_resena = :id_resena'
            );
            $stmt->execute([
                'estado' => $nuevoEstado,
                'id_sup' => $idSupervisor,
                'id_resena' => $idResena,
            ]);

            // Si se marcó como PUBLICADA, actualicé también acompanamiento
            if ($nuevoEstado === 'PUBLICADA') {
                $resena = resenas_obtener_por_id($idResena);
                if ($resena && !empty($resena['id_practica'])) {
                    $stmtAcom = $pdo->prepare(
                        'UPDATE acompanamiento 
                         SET resena_publicada = 1, updated_at = NOW() 
                         WHERE id_practica = :id_practica'
                    );
                    $stmtAcom->execute(['id_practica' => (int) $resena['id_practica']]);
                }
            }

            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Error al cambiar estado de reseña: ' . $e->getMessage());
            return false;
        }
    }
}