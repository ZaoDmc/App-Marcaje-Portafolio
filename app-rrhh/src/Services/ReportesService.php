<?php

declare(strict_types=1);

// Incluí REVISADA en los acumulados para conservar las horas y extras históricas aprobadas.

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/ReglasAsistenciaService.php';

if (!function_exists('reportes_gestion_fecha_valida')) {
    function reportes_gestion_fecha_valida(?string $fecha): bool
    {
        if ($fecha === null || $fecha === '') {
            return false;
        }

        $objetoFecha = DateTimeImmutable::createFromFormat('Y-m-d', $fecha);

        return $objetoFecha !== false && $objetoFecha->format('Y-m-d') === $fecha;
    }
}

if (!function_exists('reportes_gestion_formatear_fecha')) {
    function reportes_gestion_formatear_fecha(?string $fecha): string
    {
        if ($fecha === null || $fecha === '') {
            return 'No registrada';
        }

        $timestamp = strtotime($fecha);

        return $timestamp === false ? $fecha : date('d-m-Y', $timestamp);
    }
}

if (!function_exists('reportes_gestion_filtros')) {
    function reportes_gestion_filtros(): array
    {
        $desde = query_param('desde');
        $hasta = query_param('hasta');
        $estadoPractica = trim((string) query_param('estado_practica', ''));
        $busqueda = trim((string) query_param('q', ''));

        $desde = is_string($desde) && reportes_gestion_fecha_valida($desde) ? $desde : date('Y-m-d', strtotime('-30 days'));
        $hasta = is_string($hasta) && reportes_gestion_fecha_valida($hasta) ? $hasta : date('Y-m-d');

        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        return [
            'desde' => $desde,
            'hasta' => $hasta,
            'estado_practica' => $estadoPractica,
            'q' => $busqueda,
        ];
    }
}

if (!function_exists('reportes_gestion_estados_practica')) {
    function reportes_gestion_estados_practica(): array
    {
        $statement = db()->query(
            'SELECT nombre
             FROM estados_practica
             WHERE activo = 1
             ORDER BY nombre ASC'
        );

        return array_map(static fn (array $fila): string => (string) $fila['nombre'], $statement->fetchAll());
    }
}

if (!function_exists('reportes_gestion_nombre_practicante')) {
    function reportes_gestion_nombre_practicante(array $fila): string
    {
        $partes = [
            $fila['nombre_1'] ?? '',
            $fila['nombre_2'] ?? '',
            $fila['apellido_paterno'] ?? '',
            $fila['apellido_materno'] ?? '',
        ];

        return trim((string) preg_replace('/\s+/', ' ', implode(' ', array_filter($partes))));
    }
}

if (!function_exists('reportes_gestion_practicas')) {
    function reportes_gestion_practicas(array $filtros, string $rolEsperado): array
    {
        $where = ['p.activo = 1', 'pr.activo = 1'];
        $params = [
            'desde_periodo' => $filtros['desde'],
            'hasta_periodo' => $filtros['hasta'],
        ];

        if (($filtros['estado_practica'] ?? '') !== '') {
            $where[] = 'ep.nombre = :estado_practica';
            $params['estado_practica'] = $filtros['estado_practica'];
        }

        // Búsqueda inteligente por nombre completo, apellidos, email o RUT admitiendo espacios y múltiples términos
        if (($filtros['q'] ?? '') !== '') {
            $busquedaPracticante = sql_construir_busqueda_practicante((string) $filtros['q'], 'p', 'busq_reportes');
            if ($busquedaPracticante['condicion'] !== '') {
                $where[] = $busquedaPracticante['condicion'];
                $params = array_merge($params, $busquedaPracticante['params']);
            }
        }

        $idPracticanteActor = auth_practicante_actual_id();

        if ($rolEsperado === 'SUPERVISOR' && $idPracticanteActor !== null) {
            $where[] = 'p.id_practicante <> :id_practicante_actor';
            $params['id_practicante_actor'] = $idPracticanteActor;
        }

        $statement = db()->prepare(
            'SELECT
                pr.id_practica,
                pr.id_practicante,
                pr.horas_requeridas,
                pr.fecha_inicio_practica,
                p.nombre_1,
                p.nombre_2,
                p.apellido_paterno,
                p.apellido_materno,
                p.email,
                p.rut_numero,
                p.rut_dv,
                tp.nombre AS tipo_practica_nombre,
                ep.nombre AS estado_practica_nombre,
                COALESCE(area.area_nombre, "Sin area") AS area_nombre,
                COALESCE(periodo.total_jornadas, 0) AS jornadas_periodo,
                COALESCE(periodo.horas_periodo, 0) AS horas_periodo,
                COALESCE(periodo.jornadas_validas, 0) AS jornadas_validas,
                COALESCE(periodo.jornadas_observadas, 0) AS jornadas_observadas,
                COALESCE(periodo.jornadas_incompletas, 0) AS jornadas_incompletas,
                COALESCE(periodo.jornadas_pendientes, 0) AS jornadas_pendientes,
                COALESCE(periodo.ausencias_esperadas, 0) AS ausencias_esperadas,
                COALESCE(periodo.horas_extra_pendientes, 0) AS horas_extra_pendientes,
                COALESCE(total.horas_totales, 0) + COALESCE(pr.horas_previas, 0) AS horas_totales
             FROM practicas pr
             INNER JOIN practicantes p ON p.id_practicante = pr.id_practicante
             LEFT JOIN tipos_practica tp ON tp.id_tipo_practica = pr.id_tipo_practica
             INNER JOIN estados_practica ep ON ep.id_estado_practica = pr.id_estado_practica
             LEFT JOIN (
                SELECT hap.id_practica, ap.nombre AS area_nombre
                FROM historial_areas_practicante hap
                INNER JOIN areas_practica ap ON ap.id_area = hap.id_area
                WHERE hap.fecha_fin IS NULL
             ) area ON area.id_practica = pr.id_practica
             LEFT JOIN (
                SELECT
                    id_practica,
                    COUNT(*) AS total_jornadas,
                    /*
                     * Horas del periodo: suma las horas base más las horas extra aprobadas
                     * para todas las jornadas en estado VALIDA, OBSERVADA, CORREGIDA o REVISADA.
                     */
                    SUM(CASE 
                        WHEN estado_jornada IN ("VALIDA", "OBSERVADA", "CORREGIDA", "REVISADA")
                        THEN (horas_calculadas + COALESCE(horas_extra_aprobadas, 0)) 
                        ELSE 0 
                    END) AS horas_periodo,
                    SUM(CASE WHEN estado_jornada = "VALIDA" THEN 1 ELSE 0 END) AS jornadas_validas,
                    SUM(CASE WHEN estado_jornada = "OBSERVADA" THEN 1 ELSE 0 END) AS jornadas_observadas,
                    SUM(CASE WHEN estado_jornada = "INCOMPLETA" THEN 1 ELSE 0 END) AS jornadas_incompletas,
                    SUM(CASE WHEN estado_jornada = "PENDIENTE_REVISION" THEN 1 ELSE 0 END) AS jornadas_pendientes,
                    SUM(CASE WHEN flag_ausencia_esperada = 1 THEN 1 ELSE 0 END) AS ausencias_esperadas,
                    SUM(CASE WHEN flag_horas_extra = 1 AND horas_extra_aprobadas IS NULL THEN 1 ELSE 0 END) AS horas_extra_pendientes
                FROM jornadas_asistencia
                WHERE fecha BETWEEN :desde_periodo AND :hasta_periodo
                GROUP BY id_practica
             ) periodo ON periodo.id_practica = pr.id_practica
             LEFT JOIN (
                SELECT
                    id_practica,
                    /*
                     * Horas totales acumuladas de la práctica: suma histórica de horas base
                     * más las horas extra validadas por el supervisor.
                     */
                    SUM(CASE 
                        WHEN estado_jornada IN ("VALIDA", "OBSERVADA", "CORREGIDA", "REVISADA")
                        THEN (horas_calculadas + COALESCE(horas_extra_aprobadas, 0)) 
                        ELSE 0 
                    END) AS horas_totales
                FROM jornadas_asistencia
                GROUP BY id_practica
             ) total ON total.id_practica = pr.id_practica
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY p.apellido_paterno ASC, p.nombre_1 ASC, pr.id_practica DESC
             LIMIT 200'
        );
        $statement->execute($params);

        return $statement->fetchAll();
    }
}

if (!function_exists('reportes_gestion_resumen')) {
    function reportes_gestion_resumen(array $filas): array
    {
        $practicas = count($filas);
        $horasRequeridas = 0.0;
        $horasTotales = 0.0;
        $horasPeriodo = 0.0;
        $jornadasPeriodo = 0;
        $pendientes = 0;
        $ausencias = 0;

        foreach ($filas as $fila) {
            $horasRequeridas += (float) ($fila['horas_requeridas'] ?? 0);
            $horasTotales += (float) ($fila['horas_totales'] ?? 0);
            $horasPeriodo += (float) ($fila['horas_periodo'] ?? 0);
            $jornadasPeriodo += (int) ($fila['jornadas_periodo'] ?? 0);
            $pendientes += (int) ($fila['jornadas_pendientes'] ?? 0);
            $ausencias += (int) ($fila['ausencias_esperadas'] ?? 0);
        }

        $avancePromedio = $horasRequeridas > 0 ? min(100.0, round(($horasTotales / $horasRequeridas) * 100, 1)) : 0.0;

        return [
            'practicas' => $practicas,
            'horas_requeridas' => round($horasRequeridas, 2),
            'horas_totales' => round($horasTotales, 2),
            'horas_periodo' => round($horasPeriodo, 2),
            'jornadas_periodo' => $jornadasPeriodo,
            'pendientes_revision' => $pendientes,
            'ausencias_esperadas' => $ausencias,
            'avance_promedio' => $avancePromedio,
        ];
    }
}

if (!function_exists('reportes_gestion_practica_detalle')) {
    /**
     * Obtiene la información completa de una práctica y practicante para el reporte detallado individual.
     *
     * @param int $idPractica ID de la práctica.
     * @return array|null Fila con los datos del practicante o null.
     */
    function reportes_gestion_practica_detalle(int $idPractica): ?array
    {
        $statement = db()->prepare(
            'SELECT
                pr.id_practica,
                pr.id_practicante,
                pr.horas_requeridas,
                pr.horas_previas,
                pr.fecha_inicio_practica,
                pr.fecha_termino_practica,
                ep.nombre AS estado_practica_nombre,
                tp.nombre AS tipo_practica_nombre,
                p.rut_numero,
                p.rut_dv,
                p.nombre_1,
                p.nombre_2,
                p.apellido_paterno,
                p.apellido_materno,
                p.email,
                p.telefono,
                p.fecha_nacimiento,
                COALESCE(c.nombre, "Sin carrera") AS carrera,
                COALESCE(i.nombre, p.institucion_otra, "Sin institución") AS institucion,
                COALESCE(s.nombre_sede, p.sede_otra, "Sin sede") AS nombre_sede,
                (
                    SELECT ap.nombre
                    FROM historial_areas_practicante hap
                    INNER JOIN areas_practica ap ON ap.id_area = hap.id_area
                    WHERE hap.id_practica = pr.id_practica
                      AND hap.fecha_fin IS NULL
                    ORDER BY hap.fecha_inicio DESC, hap.id_historial_area DESC
                    LIMIT 1
                ) AS area_nombre,
                (
                    SELECT SUM(CASE WHEN estado_jornada IN ("VALIDA", "OBSERVADA", "CORREGIDA", "REVISADA") THEN (horas_calculadas + COALESCE(horas_extra_aprobadas, 0)) ELSE 0 END)
                    FROM jornadas_asistencia
                    WHERE id_practica = pr.id_practica
                ) AS horas_totales_acumuladas
             FROM practicas pr
             INNER JOIN practicantes p ON p.id_practicante = pr.id_practicante
             INNER JOIN estados_practica ep ON ep.id_estado_practica = pr.id_estado_practica
             LEFT JOIN tipos_practica tp ON tp.id_tipo_practica = pr.id_tipo_practica
             LEFT JOIN carreras c ON c.id_carrera = p.id_carrera
             LEFT JOIN instituciones i ON i.id_institucion = p.id_institucion
             LEFT JOIN sedes s ON s.id_sede = p.id_sede
             WHERE pr.id_practica = :id_practica
               AND pr.activo = 1
             LIMIT 1'
        );
        $statement->execute(['id_practica' => $idPractica]);
        $fila = $statement->fetch();

        return is_array($fila) ? $fila : null;
    }
}

if (!function_exists('reportes_gestion_jornadas_practicante')) {
    /**
     * Obtiene el listado completo de jornadas de un practicante en un periodo determinado,
     * incluyendo el flujo de marcas, observaciones, banderas de atraso, ausencias y horas extra.
     *
     * @param int $idPractica ID de la práctica.
     * @param string $desde Fecha inicio YYYY-MM-DD.
     * @param string $hasta Fecha fin YYYY-MM-DD.
     * @return array Lista de jornadas con marcas e incidencias.
     */
    function reportes_gestion_jornadas_practicante(int $idPractica, string $desde, string $hasta): array
    {
        $statement = db()->prepare(
            'SELECT
                ja.*,
                (
                    SELECT GROUP_CONCAT(
                        CONCAT(tm.codigo, " (", DATE_FORMAT(ma.fecha_hora, "%H:%i"), ")")
                        ORDER BY ma.fecha_hora ASC
                        SEPARATOR " -> "
                    )
                    FROM marcas_asistencia ma
                    INNER JOIN tipos_marca tm ON tm.id_tipo_marca = ma.id_tipo_marca
                    WHERE ma.id_jornada = ja.id_jornada
                      AND ma.activo = 1
                      AND ma.anulada = 0
                ) AS flujo_marcas
             FROM jornadas_asistencia ja
             WHERE ja.id_practica = :id_practica
               AND ja.fecha BETWEEN :desde AND :hasta
             ORDER BY ja.fecha DESC, ja.id_jornada DESC'
        );
        $statement->execute([
            'id_practica' => $idPractica,
            'desde' => $desde,
            'hasta' => $hasta,
        ]);

        return $statement->fetchAll();
    }
}

// Trasladé reportes_gestion_render_page a la capa de presentación en fase 4.

if (!function_exists('reportes_gestion_tiempos')) {
    // Separé los libros de tiempo en el informe: son desglose y saldo, nunca sumandos adicionales del avance.
    function reportes_gestion_tiempos(int $idPractica, string $desde, string $hasta): array
    {
        if (!auth_check() || !in_array(auth_rol_activo(), ['ADMIN','SUPERVISOR'], true)
            || !auth_puede_supervisar_practica($idPractica)) {
            throw new RuntimeException('No puedes consultar los tiempos de esa práctica.');
        }
        $resultado=['recuperados'=>0,'compensados'=>0,'excepcionales'=>0,'saldo'=>0,'pendientes'=>0];
        $s=db()->prepare('SELECT COALESCE(SUM(r.minutos_validados),0) FROM recuperaciones_permiso r JOIN permisos_practicante p ON p.id_permiso=r.id_permiso WHERE p.id_practica=? AND p.estado="APROBADO" AND r.estado="VALIDADA" AND r.fecha BETWEEN ? AND ?');
        $s->execute([$idPractica,$desde,$hasta]);$resultado['recuperados']=(int)$s->fetchColumn();
        $s=db()->prepare('SELECT te.*,j.id_practica,j.fecha AS fecha_trabajo FROM tiempos_excepcionales te JOIN jornadas_asistencia j ON j.id_jornada=te.id_jornada WHERE j.id_practica=?');$s->execute([$idPractica]);
        foreach($s->fetchAll() as $credito) {
            $resultado['saldo']+=tiempo_saldo($credito);
            if(in_array($credito['estado'],['PENDIENTE','REVISAR'],true)) { $resultado['pendientes']++; }
            if($credito['fecha_trabajo']>=$desde && $credito['fecha_trabajo']<=$hasta && tiempo_evidencia_valida($credito)) { $resultado['excepcionales']+=(int)$credito['minutos_aprobados']; }
        }
        // Consulté compensaciones por su propia fecha, aunque el trabajo de origen fuera de otro periodo.
        $s=db()->prepare('SELECT ct.* FROM compensaciones_tiempo ct JOIN tiempos_excepcionales te ON te.id_tiempo=ct.id_tiempo JOIN jornadas_asistencia j ON j.id_jornada=te.id_jornada WHERE j.id_practica=?');$s->execute([$idPractica]);
        foreach($s->fetchAll() as $canje) {
            if(in_array($canje['estado'],['PENDIENTE','REVISAR'],true)) { $resultado['pendientes']++; }
            if($canje['estado']==='APLICADA' && $canje['fecha']>=$desde && $canje['fecha']<=$hasta) { $resultado['compensados']+=(int)$canje['minutos_aplicados']; }
        }
        return $resultado;
    }
}

if (!function_exists('reportes_actualizar_horas_practica')) {
    /**
     * Implementé la actualización de horas requeridas y acumuladas de una práctica,
     * validando que no superen el límite de 1000 horas y registrando la auditoría correspondiente.
     *
     * @param int $idPractica ID de la práctica.
     * @param int $horasRequeridas Horas requeridas (1 a 1000).
     * @param float $horasAcumuladasDeseadas Horas acumuladas históricas totales (0 a 1000).
     * @return array{ok: bool, error?: string}
     */
    function reportes_actualizar_horas_practica(int $idPractica, int $horasRequeridas, float $horasAcumuladasDeseadas): array
    {
        if (!auth_check() || !in_array(auth_rol_activo(), ['ADMIN', 'SUPERVISOR'], true)
            || !auth_puede_supervisar_practica($idPractica)) {
            return ['ok' => false, 'error' => 'No tienes permisos para modificar las horas de esta práctica.'];
        }

        if ($horasRequeridas < 1 || $horasRequeridas > 1000) {
            return ['ok' => false, 'error' => 'Las horas requeridas deben estar entre 1 y 1000 horas.'];
        }

        if ($horasAcumuladasDeseadas < 0 || $horasAcumuladasDeseadas > 1000) {
            return ['ok' => false, 'error' => 'Las horas acumuladas históricas deben estar entre 0 y 1000 horas.'];
        }

        $pdo = db();

        // Consulté las horas calculadas por marcas reales de asistencia para no duplicar cómputos
        $stmtMarcas = $pdo->prepare(
            'SELECT COALESCE(SUM(CASE WHEN estado_jornada IN ("VALIDA", "OBSERVADA", "CORREGIDA", "REVISADA") THEN (horas_calculadas + COALESCE(horas_extra_aprobadas, 0)) ELSE 0 END), 0)
             FROM jornadas_asistencia
             WHERE id_practica = :id_practica'
        );
        $stmtMarcas->execute(['id_practica' => $idPractica]);
        $horasMarcas = (float) $stmtMarcas->fetchColumn();

        // Ajusté las horas previas para que la suma total coincida con el objetivo solicitado
        $horasPrevias = max(0.0, round($horasAcumuladasDeseadas - $horasMarcas, 2));

        // Rescaté los valores actuales antes del cambio para conservarlos en auditoría
        $stmtActual = $pdo->prepare('SELECT horas_requeridas, horas_previas FROM practicas WHERE id_practica = :id_practica');
        $stmtActual->execute(['id_practica' => $idPractica]);
        $practicaActual = $stmtActual->fetch(PDO::FETCH_ASSOC) ?: [];

        $stmtUpd = $pdo->prepare(
            'UPDATE practicas
             SET horas_requeridas = :horas_req,
                 horas_previas = :horas_prev
             WHERE id_practica = :id_practica
               AND activo = 1'
        );
        $stmtUpd->execute([
            'horas_req' => $horasRequeridas,
            'horas_prev' => $horasPrevias,
            'id_practica' => $idPractica,
        ]);

        auditoria_registrar('MODIFICAR_HORAS_PRACTICA', [
            'tabla_afectada' => 'practicas',
            'id_registro_afectado' => $idPractica,
            'valores_anteriores' => [
                'horas_requeridas' => (int) ($practicaActual['horas_requeridas'] ?? 0),
                'horas_previas' => (float) ($practicaActual['horas_previas'] ?? 0.0),
            ],
            'valores_nuevos' => [
                'horas_requeridas' => $horasRequeridas,
                'horas_previas' => $horasPrevias,
                'horas_acumuladas_totales' => $horasAcumuladasDeseadas,
            ],
        ]);

        return ['ok' => true];
    }
}

if (!function_exists('reportes_obtener_ultima_modificacion_horas')) {
    /**
     * Obtiene el último registro de auditoría de modificación de horas para una práctica,
     * identificando al responsable (usuario, email y rol) y el momento exacto en que se realizó.
     *
     * @param int $idPractica ID de la práctica.
     * @return array{id_auditoria: int, fecha_hora: string, responsable: string, rol: string, valores_anteriores?: ?array, valores_nuevos?: ?array}|null
     */
    function reportes_obtener_ultima_modificacion_horas(int $idPractica): ?array
    {
        try {
            $pdo = db();
            $stmt = $pdo->prepare(
                'SELECT
                    ac.id_auditoria,
                    ac.id_usuario_actor,
                    ac.id_rol_contexto,
                    ac.valores_anteriores,
                    ac.valores_nuevos,
                    ac.created_at,
                    us.email AS actor_email,
                    rs.nombre AS rol_nombre,
                    p.nombre_1,
                    p.apellido_paterno
                 FROM auditoria_cambios ac
                 LEFT JOIN usuarios_sistema us ON us.id_usuario = ac.id_usuario_actor
                 LEFT JOIN roles_sistema rs ON rs.id_rol = ac.id_rol_contexto
                 LEFT JOIN practicantes p ON (p.id_practicante = us.id_practicante OR (p.email = us.email AND p.email <> ""))
                 WHERE ac.evento = "MODIFICAR_HORAS_PRACTICA"
                   AND ac.id_registro_afectado = :id_practica
                 ORDER BY ac.created_at DESC, ac.id_auditoria DESC
                 LIMIT 1'
            );
            $stmt->execute(['id_practica' => $idPractica]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$fila) {
                return null;
            }

            $nombreCompletoActor = trim(($fila['nombre_1'] ?? '') . ' ' . ($fila['apellido_paterno'] ?? ''));
            $emailActor = (string) ($fila['actor_email'] ?? '');
            $responsable = $nombreCompletoActor !== '' ? $nombreCompletoActor : ($emailActor !== '' ? $emailActor : 'Usuario #' . $fila['id_usuario_actor']);
            $rol = !empty($fila['rol_nombre']) ? (string) $fila['rol_nombre'] : 'SISTEMA';

            $timestamp = strtotime((string) $fila['created_at']);
            $fechaHora = $timestamp !== false ? date('d-m-Y H:i', $timestamp) : (string) $fila['created_at'];

            return [
                'id_auditoria' => (int) $fila['id_auditoria'],
                'fecha_hora' => $fechaHora,
                'nombre' => $nombreCompletoActor !== '' ? $nombreCompletoActor : null,
                'email' => $emailActor,
                'responsable' => $responsable,
                'rol' => $rol,
                'valores_anteriores' => !empty($fila['valores_anteriores']) ? json_decode((string) $fila['valores_anteriores'], true) : null,
                'valores_nuevos' => !empty($fila['valores_nuevos']) ? json_decode((string) $fila['valores_nuevos'], true) : null,
            ];
        } catch (Throwable) {
            return null;
        }
    }
}
