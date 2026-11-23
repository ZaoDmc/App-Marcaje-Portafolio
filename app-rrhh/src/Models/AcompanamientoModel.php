<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
// Agregué la regla compartida para no omitir extras aprobadas en el total.
require_once __DIR__ . '/../Services/ReglasAsistenciaService.php';

if (!function_exists('acom_gestion_token')) {
    function acom_gestion_token(): string
    {
        start_secure_session();

        if (!isset($_SESSION['csrf_acom_gestion'])) {
            $_SESSION['csrf_acom_gestion'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['csrf_acom_gestion'];
    }
}

if (!function_exists('acom_gestion_asegurar_tabla')) {
    function acom_gestion_asegurar_tabla(): void
    {
        $pdo = db();
        try {
            $existe = (bool) $pdo->query(
                "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'acompanamiento'"
            )->fetchColumn();
        } catch (Throwable) {
            $existe = false;
        }

        if ($existe) {
            return;
        }

        try {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS acompanamiento (
                    id_acompanamiento   INT          NOT NULL AUTO_INCREMENT,
                    id_practica         INT          NOT NULL,
                    evaluacion_enviada  TINYINT(1)   NOT NULL DEFAULT 0,
                    puntaje_evaluacion  DECIMAL(3,1) NULL    DEFAULT NULL,
                    resena_recibida     TINYINT(1)   NOT NULL DEFAULT 0,
                    fecha_generacion    DATETIME      NULL    DEFAULT NULL,
                    resena_publicada    TINYINT(1)    NOT NULL DEFAULT 0,
                    documento_entregado TINYINT(1)    NOT NULL DEFAULT 0,
                    capacitacion_realizada TINYINT(1) NOT NULL DEFAULT 0,
                    certificado_entregado  TINYINT(1) NOT NULL DEFAULT 0,
                    linkedin_operativo  TINYINT(1)   NOT NULL DEFAULT 0,
                    created_at          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id_acompanamiento),
                    UNIQUE KEY uk_acompanamiento_practica (id_practica)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
        } catch (Throwable $exception) {
            error_log('No se pudo crear la tabla acompanamiento: ' . $exception->getMessage());
        }
    }
}

if (!function_exists('acom_gestion_listar_practicantes')) {
    function acom_gestion_listar_practicantes(?string $busqueda = null): array
    {
        acom_gestion_asegurar_tabla();
        $busqueda = trim((string) $busqueda);
        $params = [];
        $whereBusqueda = '';

        // Búsqueda inteligente por nombre completo, apellidos, email o RUT admitiendo espacios y múltiples términos
        if ($busqueda !== '') {
            $busquedaPracticante = sql_construir_busqueda_practicante($busqueda, 'p', 'busq_acom');
            $whereBusqueda = $busquedaPracticante['where_sql'];
            $params = $busquedaPracticante['params'];
        }

        // Agregué el filtro visual de autosupervisión; guardar vuelve a validar
        // el objetivo, por lo que este filtro no es el único control de acceso.
        if (auth_rol_activo() === 'SUPERVISOR' && auth_practicante_actual_id() !== null) {
            $whereBusqueda .= ' AND p.id_practicante <> :id_practicante_actor';
            $params['id_practicante_actor'] = auth_practicante_actual_id();
        }

        $statement = db()->prepare(
            'SELECT
                p.id_practicante,
                p.rut_numero,
                p.rut_dv,
                p.nombre_1,
                p.nombre_2,
                p.apellido_paterno,
                p.apellido_materno,
                p.email,
                COALESCE(i.nombre, p.institucion_otra, "Sin institución") AS institucion,
                COALESCE(s.nombre_sede, p.sede_otra, "Sin sede") AS nombre_sede,
                COALESCE(c.nombre, "Sin carrera") AS carrera,
                pr.id_practica,
                pr.fecha_inicio_practica,
                pr.fecha_termino_practica,
                pr.horas_requeridas,
                COALESCE(tp.nombre, "Sin tipo") AS tipo_practica,
                (
                    SELECT ap.nombre
                    FROM historial_areas_practicante hap
                    INNER JOIN areas_practica ap ON ap.id_area = hap.id_area
                    WHERE hap.id_practica = pr.id_practica
                      AND hap.fecha_fin IS NULL
                    ORDER BY hap.fecha_inicio DESC, hap.id_historial_area DESC
                    LIMIT 1
                ) AS area_vigente,
                (
                    SELECT COALESCE(SUM(' . asistencia_sql_horas_reconocidas('ja') . '), 0)
                         + COALESCE(pr.horas_previas, 0)
                    FROM jornadas_asistencia ja
                    WHERE ja.id_practica = pr.id_practica
                ) AS horas_totales,
                ac.evaluacion_enviada,
                ac.puntaje_evaluacion,
                ac.resena_recibida,
                ac.fecha_generacion,
                ac.resena_publicada,
                ac.documento_entregado,
                ac.capacitacion_realizada,
                ac.certificado_entregado,
                ac.linkedin_operativo,
                hp.id_horario,
                hp.fecha_inicio AS horario_fecha_inicio,
                hp.fecha_fin AS horario_fecha_fin
             FROM practicantes p
             INNER JOIN practicas pr ON pr.id_practica = (
                 SELECT pr2.id_practica
                 FROM practicas pr2
                 WHERE pr2.id_practicante = p.id_practicante
                   AND pr2.activo = 1
                 ORDER BY pr2.fecha_inicio_practica DESC, pr2.id_practica DESC
                 LIMIT 1
             )
             LEFT JOIN instituciones i ON i.id_institucion = p.id_institucion
             LEFT JOIN sedes s ON s.id_sede = p.id_sede
             LEFT JOIN carreras c ON c.id_carrera = p.id_carrera
             LEFT JOIN tipos_practica tp ON tp.id_tipo_practica = pr.id_tipo_practica
             LEFT JOIN horarios_practicante hp ON hp.id_horario = (
                 SELECT hp2.id_horario
                 FROM horarios_practicante hp2
                 WHERE hp2.id_practica = pr.id_practica
                   AND hp2.activo = 1
                 ORDER BY hp2.fecha_inicio DESC, hp2.id_horario DESC
                 LIMIT 1
             )
             LEFT JOIN acompanamiento ac ON ac.id_practica = pr.id_practica
             WHERE p.activo = 1' . $whereBusqueda . '
             ORDER BY p.apellido_paterno ASC, p.nombre_1 ASC
             LIMIT 100'
        );
        $statement->execute($params);

        return $statement->fetchAll();
    }
}

if (!function_exists('acom_gestion_guardar')) {
    function acom_gestion_guardar(int $idPractica, array $datos): void
    {
        // Agregué autorización de la práctica recibida aunque se manipule el
        // formulario/AJAX. El usuario no puede guardar su propio seguimiento.
        if (!auth_puede_supervisar_practica($idPractica)) {
            throw new RuntimeException('No puedes modificar el acompañamiento de esta práctica.');
        }

        $columnas = [];

        // Conservé el puntaje anterior como histórico de solo lectura, incluso ante formularios antiguos o POST manipulados.
        if (array_key_exists('puntaje_evaluacion', $datos)) {
            throw new DomainException('El puntaje histórico es de solo lectura. Registra las evaluaciones semanales en Notas.');
        }

        foreach ($datos as $campo => $valor) {
            switch ($campo) {
                case 'evaluacion_enviada':
                case 'resena_recibida':
                case 'resena_publicada':
                case 'documento_entregado':
                case 'capacitacion_realizada':
                case 'certificado_entregado':
                case 'linkedin_operativo':
                    $columnas[$campo] = ($valor === '1' || $valor === 1 || $valor === true) ? 1 : 0;
                    break;
            }
        }

        if ($columnas === []) {
            return;
        }

        $nombresColumnas = array_keys($columnas);
        $asignaciones = [];
        $params = [':id_practica' => $idPractica];

        foreach ($columnas as $col => $val) {
            $asignaciones[] = $col . ' = :actualizar_' . $col;
            $params[':insertar_' . $col] = $val;
            $params[':actualizar_' . $col] = $val;
        }
        $asignaciones[] = 'updated_at = CURRENT_TIMESTAMP';

        $sql = 'INSERT INTO acompanamiento (id_practica, ' . implode(', ', $nombresColumnas) . ', fecha_generacion)
                VALUES (:id_practica, ' . implode(', ', array_map(fn ($col) => ':insertar_' . $col, $nombresColumnas)) . ', NOW())
                ON DUPLICATE KEY UPDATE ' . implode(', ', $asignaciones);

        try {
            // Agregué transacción y auditoría: un cambio sin traza no se guarda.
            db()->beginTransaction();
            // Agregué bloqueo de la práctica para serializar ediciones incluso
            // cuando aún no existe una fila de acompañamiento y auditar su valor previo real.
            $lock = db()->prepare('SELECT id_practica FROM practicas WHERE id_practica = :id_practica FOR UPDATE');
            $lock->execute(['id_practica' => $idPractica]);
            $before = db()->prepare('SELECT * FROM acompanamiento WHERE id_practica = :id_practica');
            $before->execute(['id_practica' => $idPractica]);
            $anteriores = $before->fetch();
            $stmt = db()->prepare($sql);
            $stmt->execute($params);
            $after = db()->prepare('SELECT * FROM acompanamiento WHERE id_practica = :id_practica');
            $after->execute(['id_practica' => $idPractica]);
            $nuevos = $after->fetch();
            if (!auditoria_registrar('EDITAR_ACOMPANAMIENTO', [
                'tabla_afectada' => 'acompanamiento',
                'id_registro_afectado' => $nuevos['id_acompanamiento'],
                'valores_anteriores' => is_array($anteriores) ? $anteriores : null,
                'valores_nuevos' => $nuevos,
            ])) {
                throw new RuntimeException('No se pudo auditar el acompañamiento.');
            }
            db()->commit();
        } catch (Throwable $exception) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            error_log('PDO ERROR al guardar acompañamiento: ' . $exception->getMessage());
            throw new RuntimeException('No fue posible guardar los cambios en la base de datos.');
        }
    }
}
