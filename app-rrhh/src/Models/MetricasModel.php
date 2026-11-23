<?php

declare(strict_types=1);

// Incluí REVISADA en los acumulados para conservar las horas y extras históricas aprobadas.

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../Services/AuthService.php';

if (!function_exists('dashboard_metricas_condicion_base')) {
    function dashboard_metricas_condicion_base(string $rol, array $filtros = []): array
    {
        $where = ['p.activo = 1', 'pr.activo = 1'];
        $params = [];
        $idPracticanteActor = auth_practicante_actual_id();

        if ($rol === 'SUPERVISOR' && $idPracticanteActor !== null) {
            $where[] = 'p.id_practicante <> :id_practicante_actor';
            $params['id_practicante_actor'] = $idPracticanteActor;
        }

        if ($rol === 'ADMIN') {
            dashboard_metricas_admin_aplicar_filtros_entidad($where, $params, $filtros);
        }

        return [
            'where' => $where,
            'params' => $params,
        ];
    }
}

if (!function_exists('dashboard_metricas_admin_aplicar_filtros_entidad')) {
    function dashboard_metricas_admin_aplicar_filtros_entidad(array &$where, array &$params, array $filtros): void
    {
        foreach ([
            'id_institucion' => 'p.id_institucion',
            'id_sede' => 'p.id_sede',
            'id_carrera' => 'p.id_carrera',
            'id_estado_practica' => 'pr.id_estado_practica',
        ] as $clave => $columna) {
            $valor = (int) ($filtros[$clave] ?? 0);

            if ($valor > 0) {
                $where[] = $columna . ' = :' . $clave;
                $params[$clave] = $valor;
            }
        }

        $idArea = (int) ($filtros['id_area'] ?? 0);

        if ($idArea > 0) {
            $where[] = 'EXISTS (
                SELECT 1
                FROM historial_areas_practicante hapf
                WHERE hapf.id_practica = pr.id_practica
                  AND hapf.id_area = :id_area
                  AND (hapf.fecha_fin IS NULL OR hapf.fecha_fin >= CURDATE())
            )';
            $params['id_area'] = $idArea;
        }

        $busqueda = trim((string) ($filtros['q'] ?? ''));

        // Búsqueda inteligente por nombre completo, apellidos, institución o carrera admitiendo espacios y múltiples términos
        if ($busqueda !== '') {
            $palabras = preg_split('/\s+/', $busqueda);
            $palabras = array_values(array_filter((array) $palabras, static fn (string $palabra): bool => trim($palabra) !== ''));
            $condicionesPalabras = [];
            $campoNombreCompleto = "CONCAT_WS(' ', p.nombre_1, p.nombre_2, p.apellido_paterno, p.apellido_materno)";

            foreach ($palabras as $indice => $palabra) {
                // Nombres de parámetros únicos por columna para compatibilidad estricta con PDO
                $pNom = "busq_dash_nom_{$indice}";
                $pEmail = "busq_dash_em_{$indice}";
                $pInstOtra = "busq_dash_io_{$indice}";
                $pSedeOtra = "busq_dash_so_{$indice}";
                $pInst = "busq_dash_inst_{$indice}";
                $pSede = "busq_dash_sede_{$indice}";
                $pCarr = "busq_dash_carr_{$indice}";
                $pArea = "busq_dash_area_{$indice}";
                $valorLike = '%' . $palabra . '%';

                $condicionesPalabras[] = "(
                    {$campoNombreCompleto} LIKE :{$pNom}
                    OR p.email LIKE :{$pEmail}
                    OR p.institucion_otra LIKE :{$pInstOtra}
                    OR p.sede_otra LIKE :{$pSedeOtra}
                    OR EXISTS (
                        SELECT 1
                        FROM instituciones iq
                        WHERE iq.id_institucion = p.id_institucion
                          AND iq.nombre LIKE :{$pInst}
                    )
                    OR EXISTS (
                        SELECT 1
                        FROM sedes sq
                        WHERE sq.id_sede = p.id_sede
                          AND sq.nombre_sede LIKE :{$pSede}
                    )
                    OR EXISTS (
                        SELECT 1
                        FROM carreras cq
                        WHERE cq.id_carrera = p.id_carrera
                          AND cq.nombre LIKE :{$pCarr}
                    )
                    OR EXISTS (
                        SELECT 1
                        FROM historial_areas_practicante hapq
                        INNER JOIN areas_practica apq ON apq.id_area = hapq.id_area
                        WHERE hapq.id_practica = pr.id_practica
                          AND (hapq.fecha_fin IS NULL OR hapq.fecha_fin >= CURDATE())
                          AND apq.nombre LIKE :{$pArea}
                    )
                )";
                $params[$pNom] = $valorLike;
                $params[$pEmail] = $valorLike;
                $params[$pInstOtra] = $valorLike;
                $params[$pSedeOtra] = $valorLike;
                $params[$pInst] = $valorLike;
                $params[$pSede] = $valorLike;
                $params[$pCarr] = $valorLike;
                $params[$pArea] = $valorLike;
            }

            if ($condicionesPalabras !== []) {
                $where[] = '(' . implode(' AND ', $condicionesPalabras) . ')';
            }
        }
    }
}

if (!function_exists('dashboard_metricas_periodo')) {
    function dashboard_metricas_periodo(array $filtros = []): array
    {
        $desde = (string) ($filtros['fecha_desde'] ?? date('Y-m-d', strtotime('-30 days')));
        $hasta = (string) ($filtros['fecha_hasta'] ?? date('Y-m-d'));

        return [
            'desde' => $desde,
            'hasta' => $hasta,
        ];
    }
}

if (!function_exists('dashboard_metricas_periodo_anterior')) {
    function dashboard_metricas_periodo_anterior(array $filtros = []): array
    {
        $periodo = dashboard_metricas_periodo($filtros);
        $desde = DateTimeImmutable::createFromFormat('Y-m-d', $periodo['desde']) ?: new DateTimeImmutable('-30 days');
        $hasta = DateTimeImmutable::createFromFormat('Y-m-d', $periodo['hasta']) ?: new DateTimeImmutable();

        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        $dias = $desde->diff($hasta)->days + 1;
        $hastaAnterior = $desde->modify('-1 day');
        $desdeAnterior = $hastaAnterior->modify('-' . max(0, $dias - 1) . ' days');

        $filtrosAnteriores = $filtros;
        $filtrosAnteriores['fecha_desde'] = $desdeAnterior->format('Y-m-d');
        $filtrosAnteriores['fecha_hasta'] = $hastaAnterior->format('Y-m-d');

        return $filtrosAnteriores;
    }
}

if (!function_exists('dashboard_metricas_escalar')) {
    function dashboard_metricas_escalar(string $sql, array $params = []): mixed
    {
        $statement = db()->prepare($sql);
        $statement->execute($params);

        return $statement->fetchColumn();
    }
}

if (!function_exists('dashboard_metricas_resumen')) {
    function dashboard_metricas_resumen(string $rol, array $filtros = []): array
    {
        $base = dashboard_metricas_condicion_base($rol, $filtros);
        $where = implode(' AND ', $base['where']);
        $params = $base['params'];
        $periodo = dashboard_metricas_periodo($filtros);

        $practicantesActivos = (int) dashboard_metricas_escalar(
            'SELECT COUNT(DISTINCT p.id_practicante)
             FROM practicantes p
             INNER JOIN practicas pr ON pr.id_practicante = p.id_practicante
             WHERE ' . $where,
            $params
        );

        $practicasActivas = (int) dashboard_metricas_escalar(
            'SELECT COUNT(*)
             FROM practicas pr
             INNER JOIN practicantes p ON p.id_practicante = pr.id_practicante
             WHERE ' . $where,
            $params
        );

        $paramsPeriodo = array_merge($params, [
            'desde' => $periodo['desde'],
            'hasta' => $periodo['hasta'],
        ]);

        $horasPeriodo = (float) dashboard_metricas_escalar(
            'SELECT COALESCE(SUM(CASE WHEN ja.estado_jornada IN ("VALIDA", "OBSERVADA", "CORREGIDA", "REVISADA") THEN (ja.horas_calculadas + COALESCE(ja.horas_extra_aprobadas, 0)) ELSE 0 END), 0)
             FROM jornadas_asistencia ja
             INNER JOIN practicas pr ON pr.id_practica = ja.id_practica
             INNER JOIN practicantes p ON p.id_practicante = pr.id_practicante
             WHERE ' . $where . '
               AND ja.fecha BETWEEN :desde AND :hasta',
            $paramsPeriodo
        );

        $pendientesRevision = (int) dashboard_metricas_escalar(
            'SELECT COUNT(*)
             FROM jornadas_asistencia ja
             INNER JOIN practicas pr ON pr.id_practica = ja.id_practica
             INNER JOIN practicantes p ON p.id_practicante = pr.id_practicante
             WHERE ' . $where . '
               AND ja.estado_jornada = "PENDIENTE_REVISION"
               AND ja.fecha BETWEEN :desde AND :hasta',
            $paramsPeriodo
        );

        $ausenciasEsperadas = (int) dashboard_metricas_escalar(
            'SELECT COUNT(*)
             FROM jornadas_asistencia ja
             INNER JOIN practicas pr ON pr.id_practica = ja.id_practica
             INNER JOIN practicantes p ON p.id_practicante = pr.id_practicante
             WHERE ' . $where . '
               AND ja.flag_ausencia_esperada = 1
               AND ja.fecha BETWEEN :desde AND :hasta',
            $paramsPeriodo
        );

        $horasRequeridas = (float) dashboard_metricas_escalar(
            'SELECT COALESCE(SUM(COALESCE(pr.horas_requeridas, 0)), 0)
             FROM practicas pr
             INNER JOIN practicantes p ON p.id_practicante = pr.id_practicante
             WHERE ' . $where,
            $params
        );

        $horasJornadas = (float) dashboard_metricas_escalar(
            'SELECT COALESCE(SUM(CASE WHEN ja.estado_jornada IN ("VALIDA", "OBSERVADA", "CORREGIDA", "REVISADA") THEN (ja.horas_calculadas + COALESCE(ja.horas_extra_aprobadas, 0)) ELSE 0 END), 0)
             FROM jornadas_asistencia ja
             INNER JOIN practicas pr ON pr.id_practica = ja.id_practica
             INNER JOIN practicantes p ON p.id_practicante = pr.id_practicante
             WHERE ' . $where,
            $params
        );

        $horasPreviasTotales = (float) dashboard_metricas_escalar(
            'SELECT COALESCE(SUM(COALESCE(pr.horas_previas, 0)), 0)
             FROM practicas pr
             INNER JOIN practicantes p ON p.id_practicante = pr.id_practicante
             WHERE ' . $where,
            $params
        );

        $horasTotales = $horasJornadas + $horasPreviasTotales;

        $avanceGlobal = $horasRequeridas > 0
            ? min(100.0, round(($horasTotales / $horasRequeridas) * 100, 1))
            : 0.0;

        return [
            'practicantes_activos' => $practicantesActivos,
            'practicas_activas' => $practicasActivas,
            'horas_30_dias' => round($horasPeriodo, 2),
            'horas_periodo' => round($horasPeriodo, 2),
            'pendientes_revision' => $pendientesRevision,
            'ausencias_esperadas' => $ausenciasEsperadas,
            'horas_totales' => round($horasTotales, 2),
            'horas_requeridas' => round($horasRequeridas, 2),
            'avance_global' => $avanceGlobal,
        ];
    }
}

if (!function_exists('dashboard_metricas_admin_extra')) {
    function dashboard_metricas_admin_extra(): array
    {
        $usuariosActivos = (int) dashboard_metricas_escalar(
            'SELECT COUNT(*)
             FROM usuarios_sistema
             WHERE activo = 1'
        );

        $eventos24Horas = (int) dashboard_metricas_escalar(
            'SELECT COUNT(*)
             FROM auditoria_cambios
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)'
        );

        return [
            'usuarios_activos' => $usuariosActivos,
            'eventos_24h' => $eventos24Horas,
        ];
    }
}

if (!function_exists('dashboard_metricas_admin_panel')) {
    function dashboard_metricas_admin_panel(array $filtros = []): array
    {
        return [
            'instituciones_vinculadas' => dashboard_metricas_admin_instituciones_vinculadas($filtros),
            'avance_promedio' => dashboard_metricas_admin_avance_promedio($filtros),
            'seguimiento_semanal' => dashboard_metricas_admin_seguimiento_semanal($filtros),
            'distribucion_estados' => dashboard_metricas_admin_distribucion_estados($filtros),
            'practicantes_por_area' => dashboard_metricas_admin_practicantes_por_area($filtros),
            'registros_recientes' => dashboard_metricas_admin_registros_recientes($filtros),
            'alertas' => dashboard_metricas_admin_alertas($filtros),
        ];
    }
}

if (!function_exists('dashboard_metricas_admin_instituciones_vinculadas')) {
    function dashboard_metricas_admin_instituciones_vinculadas(array $filtros = []): int
    {
        $base = dashboard_metricas_condicion_base('ADMIN', $filtros);
        $where = implode(' AND ', $base['where']);

        return (int) dashboard_metricas_escalar(
            'SELECT COUNT(DISTINCT p.id_institucion)
             FROM practicantes p
             INNER JOIN practicas pr ON pr.id_practicante = p.id_practicante
             WHERE ' . $where . '
               AND p.id_institucion IS NOT NULL',
            $base['params']
        );
    }
}

if (!function_exists('dashboard_metricas_admin_avance_promedio')) {
    function dashboard_metricas_admin_avance_promedio(array $filtros = []): float
    {
        $base = dashboard_metricas_condicion_base('ADMIN', $filtros);
        $where = implode(' AND ', $base['where']);

        $avance = (float) dashboard_metricas_escalar(
            'SELECT COALESCE(AVG(
                CASE
                    WHEN pr.horas_requeridas > 0
                    THEN LEAST(100, ((COALESCE(horas.total_horas, 0) + COALESCE(pr.horas_previas, 0)) / pr.horas_requeridas) * 100)
                    ELSE 0
                END
             ), 0)
             FROM practicas pr
             INNER JOIN practicantes p ON p.id_practicante = pr.id_practicante
             LEFT JOIN (
                SELECT
                    id_practica,
                    SUM(CASE WHEN estado_jornada IN ("VALIDA", "OBSERVADA", "CORREGIDA", "REVISADA") THEN (horas_calculadas + COALESCE(horas_extra_aprobadas, 0)) ELSE 0 END) AS total_horas
                FROM jornadas_asistencia
                GROUP BY id_practica
             ) horas ON horas.id_practica = pr.id_practica
             WHERE ' . $where,
            $base['params']
        );

        return round($avance, 1);
    }
}

if (!function_exists('dashboard_metricas_admin_seguimiento_semanal')) {
    function dashboard_metricas_admin_seguimiento_semanal(array $filtros = []): array
    {
        $dias = [];
        $nombresDias = [
            1 => 'Lun',
            2 => 'Mar',
            3 => 'Mie',
            4 => 'Jue',
            5 => 'Vie',
            6 => 'Sab',
            7 => 'Dom',
        ];
        $periodo = dashboard_metricas_periodo($filtros);
        $fechaInicio = DateTimeImmutable::createFromFormat('Y-m-d', $periodo['desde']) ?: new DateTimeImmutable('-30 days');
        $fechaFin = DateTimeImmutable::createFromFormat('Y-m-d', $periodo['hasta']) ?: new DateTimeImmutable();

        if ($fechaInicio > $fechaFin) {
            [$fechaInicio, $fechaFin] = [$fechaFin, $fechaInicio];
        }

        for ($fechaCursor = $fechaInicio; $fechaCursor <= $fechaFin; $fechaCursor = $fechaCursor->modify('+1 day')) {
            $fecha = $fechaCursor->format('Y-m-d');
            $dias[$fecha] = [
                'fecha' => $fecha,
                'dia' => $nombresDias[(int) $fechaCursor->format('N')] . ' ' . $fechaCursor->format('d-m'),
                'horas' => 0.0,
                'asistencias' => 0,
            ];
        }

        $base = dashboard_metricas_condicion_base('ADMIN', $filtros);
        $where = implode(' AND ', $base['where']);
        $params = array_merge($base['params'], [
            'desde' => $periodo['desde'],
            'hasta' => $periodo['hasta'],
        ]);

        $statement = db()->prepare(
            'SELECT
                ja.fecha,
                COALESCE(SUM(CASE WHEN ja.estado_jornada IN ("VALIDA", "OBSERVADA", "CORREGIDA", "REVISADA") THEN (ja.horas_calculadas + COALESCE(ja.horas_extra_aprobadas, 0)) ELSE 0 END), 0) AS horas,
                COUNT(*) AS asistencias
             FROM jornadas_asistencia ja
             INNER JOIN practicas pr ON pr.id_practica = ja.id_practica
             INNER JOIN practicantes p ON p.id_practicante = pr.id_practicante
             WHERE ' . $where . '
               AND ja.fecha BETWEEN :desde AND :hasta
             GROUP BY ja.fecha
             ORDER BY ja.fecha ASC'
        );
        $statement->execute($params);

        foreach ($statement->fetchAll() as $fila) {
            $fecha = (string) $fila['fecha'];

            if (!isset($dias[$fecha])) {
                continue;
            }

            $dias[$fecha]['horas'] = round((float) $fila['horas'], 2);
            $dias[$fecha]['asistencias'] = (int) $fila['asistencias'];
        }

        return array_values($dias);
    }
}

if (!function_exists('dashboard_metricas_admin_distribucion_estados')) {
    function dashboard_metricas_admin_distribucion_estados(array $filtros = []): array
    {
        $base = dashboard_metricas_condicion_base('ADMIN', $filtros);
        $where = implode(' AND ', $base['where']);
        $statement = db()->prepare(
            'SELECT
                ep.nombre AS estado,
                COUNT(*) AS total
             FROM practicas pr
             INNER JOIN practicantes p ON p.id_practicante = pr.id_practicante
             LEFT JOIN estados_practica ep ON ep.id_estado_practica = pr.id_estado_practica
             WHERE ' . $where . '
             GROUP BY ep.nombre
             ORDER BY total DESC, estado ASC'
        );
        $statement->execute($base['params']);

        return array_map(
            static fn (array $fila): array => [
                'estado' => (string) ($fila['estado'] ?? 'Sin estado'),
                'total' => (int) $fila['total'],
            ],
            $statement->fetchAll()
        );
    }
}

if (!function_exists('dashboard_metricas_admin_practicantes_por_area')) {
    function dashboard_metricas_admin_practicantes_por_area(array $filtros = []): array
    {
        $base = dashboard_metricas_condicion_base('ADMIN', $filtros);
        $where = implode(' AND ', $base['where']);
        $statement = db()->prepare(
            'SELECT
                COALESCE(ap.nombre, "Sin area") AS area,
                COUNT(DISTINCT pr.id_practica) AS total
             FROM practicas pr
             INNER JOIN practicantes p ON p.id_practicante = pr.id_practicante
             LEFT JOIN historial_areas_practicante hap ON hap.id_historial_area = (
                SELECT hap2.id_historial_area
                FROM historial_areas_practicante hap2
                WHERE hap2.id_practica = pr.id_practica
                  AND (hap2.fecha_fin IS NULL OR hap2.fecha_fin >= CURDATE())
                ORDER BY hap2.fecha_inicio DESC, hap2.id_historial_area DESC
                LIMIT 1
             )
             LEFT JOIN areas_practica ap ON ap.id_area = hap.id_area
             WHERE ' . $where . '
             GROUP BY area
             ORDER BY total DESC, area ASC
             LIMIT 6'
        );
        $statement->execute($base['params']);

        return array_map(
            static fn (array $fila): array => [
                'area' => (string) $fila['area'],
                'total' => (int) $fila['total'],
            ],
            $statement->fetchAll()
        );
    }
}

if (!function_exists('dashboard_metricas_admin_registros_recientes')) {
    function dashboard_metricas_admin_registros_recientes(array $filtros = []): array
    {
        $base = dashboard_metricas_condicion_base('ADMIN', $filtros);
        $where = implode(' AND ', $base['where']);
        $periodo = dashboard_metricas_periodo($filtros);
        $params = array_merge($base['params'], [
            'desde' => $periodo['desde'],
            'hasta' => $periodo['hasta'],
        ]);
        $statement = db()->prepare(
            'SELECT
                ja.id_jornada,
                ja.fecha,
                ja.estado_jornada,
                ja.horas_calculadas,
                CONCAT_WS(" ", p.nombre_1, p.apellido_paterno) AS practicante,
                COALESCE(i.nombre, p.institucion_otra, "Sin institucion") AS institucion,
                COALESCE(ap.nombre, "Sin area") AS area
             FROM jornadas_asistencia ja
             INNER JOIN practicas pr ON pr.id_practica = ja.id_practica
             INNER JOIN practicantes p ON p.id_practicante = pr.id_practicante
             LEFT JOIN instituciones i ON i.id_institucion = p.id_institucion
             LEFT JOIN historial_areas_practicante hap ON hap.id_historial_area = (
                SELECT hap2.id_historial_area
                FROM historial_areas_practicante hap2
                WHERE hap2.id_practica = pr.id_practica
                  AND hap2.fecha_inicio <= ja.fecha
                  AND (hap2.fecha_fin IS NULL OR hap2.fecha_fin >= ja.fecha)
                ORDER BY hap2.fecha_inicio DESC, hap2.id_historial_area DESC
                LIMIT 1
             )
             LEFT JOIN areas_practica ap ON ap.id_area = hap.id_area
             WHERE ' . $where . '
               AND ja.fecha BETWEEN :desde AND :hasta
             ORDER BY ja.fecha DESC, ja.id_jornada DESC
             LIMIT 6'
        );
        $statement->execute($params);

        return array_map(
            static fn (array $fila): array => [
                'id_jornada' => (int) $fila['id_jornada'],
                'fecha' => (string) $fila['fecha'],
                'estado' => (string) $fila['estado_jornada'],
                'horas' => round((float) $fila['horas_calculadas'], 2),
                'practicante' => (string) $fila['practicante'],
                'institucion' => (string) $fila['institucion'],
                'area' => (string) $fila['area'],
            ],
            $statement->fetchAll()
        );
    }
}

if (!function_exists('dashboard_metricas_admin_alertas')) {
    function dashboard_metricas_admin_alertas(array $filtros = []): array
    {
        $base = dashboard_metricas_condicion_base('ADMIN', $filtros);
        $where = implode(' AND ', $base['where']);
        $periodo = dashboard_metricas_periodo($filtros);
        $paramsPeriodo = array_merge($base['params'], [
            'desde' => $periodo['desde'],
            'hasta' => $periodo['hasta'],
        ]);

        $fichasIncompletas = (int) dashboard_metricas_escalar(
            'SELECT COUNT(DISTINCT p.id_practicante)
             FROM practicantes p
             INNER JOIN practicas pr ON pr.id_practicante = p.id_practicante
             LEFT JOIN instituciones i ON i.id_institucion = p.id_institucion
             WHERE ' . $where . '
               AND (
                    p.rut_numero IS NULL
                    OR p.rut_dv IS NULL
                    OR p.fecha_nacimiento IS NULL
                    OR p.sexo IS NULL
                    OR p.nacionalidad IS NULL
                    OR p.direccion IS NULL
                    OR p.numero_direccion IS NULL
                    OR p.pais IS NULL
                    OR p.id_region IS NULL
                    OR p.id_comuna IS NULL
                    OR p.id_institucion IS NULL
                    OR p.id_carrera IS NULL
                    OR p.semestre IS NULL
                    OR pr.id_tipo_practica IS NULL
                    OR pr.horas_requeridas IS NULL
                    OR pr.fecha_inicio_practica IS NULL
                    OR (i.nombre = "Otra" AND (p.institucion_otra IS NULL OR p.sede_otra IS NULL))
               )',
            $base['params']
        );

        $pendientesRevision = (int) dashboard_metricas_escalar(
            'SELECT COUNT(*)
             FROM jornadas_asistencia ja
             INNER JOIN practicas pr ON pr.id_practica = ja.id_practica
             INNER JOIN practicantes p ON p.id_practicante = pr.id_practicante
             WHERE ' . $where . '
               AND ja.estado_jornada = "PENDIENTE_REVISION"
               AND ja.fecha BETWEEN :desde AND :hasta',
            $paramsPeriodo
        );

        $ausenciasSinResolver = (int) dashboard_metricas_escalar(
            'SELECT COUNT(*)
             FROM jornadas_asistencia ja
             INNER JOIN practicas pr ON pr.id_practica = ja.id_practica
             INNER JOIN practicantes p ON p.id_practicante = pr.id_practicante
             WHERE ' . $where . '
               AND ja.flag_ausencia_esperada = 1
               AND ja.estado_jornada IN ("INCOMPLETA", "PENDIENTE_REVISION")
               AND ja.fecha BETWEEN :desde AND :hasta',
            $paramsPeriodo
        );

        $horasExtraPendientes = (int) dashboard_metricas_escalar(
            'SELECT COUNT(*)
             FROM jornadas_asistencia ja
             INNER JOIN practicas pr ON pr.id_practica = ja.id_practica
             INNER JOIN practicantes p ON p.id_practicante = pr.id_practicante
             WHERE ' . $where . '
               AND ja.flag_horas_extra = 1
               AND ja.horas_extra_aprobadas IS NULL
               AND ja.fecha BETWEEN :desde AND :hasta',
            $paramsPeriodo
        );

        return [
            [
                'titulo' => 'Registros incompletos',
                'detalle' => $fichasIncompletas . ' practicantes con ficha pendiente',
                'total' => $fichasIncompletas,
                'nivel' => $fichasIncompletas > 0 ? 'warning' : 'ok',
                'url' => 'admin/practicantes.php',
            ],
            [
                'titulo' => 'Jornadas en revision',
                'detalle' => $pendientesRevision . ' jornadas requieren validacion',
                'total' => $pendientesRevision,
                'nivel' => $pendientesRevision > 0 ? 'attention' : 'ok',
                'url' => 'admin/asistencia.php',
            ],
            [
                'titulo' => 'Ausencias esperadas',
                'detalle' => $ausenciasSinResolver . ' ausencias pendientes de resolver',
                'total' => $ausenciasSinResolver,
                'nivel' => $ausenciasSinResolver > 0 ? 'warning' : 'ok',
                'url' => 'admin/asistencia.php',
            ],
            [
                'titulo' => 'Horas extra pendientes',
                'detalle' => $horasExtraPendientes . ' jornadas esperan aprobacion',
                'total' => $horasExtraPendientes,
                'nivel' => $horasExtraPendientes > 0 ? 'attention' : 'ok',
                'url' => 'admin/asistencia.php',
            ],
        ];
    }
}

if (!function_exists('dashboard_metricas_admin_catalogos')) {
    function dashboard_metricas_admin_catalogos(): array
    {
        $catalogos = [
            'instituciones' => [],
            'sedes' => [],
            'carreras' => [],
            'areas' => [],
            'estados' => [],
        ];

        $catalogos['instituciones'] = db()->query(
            'SELECT id_institucion AS id, nombre
             FROM instituciones
             WHERE activo = 1
             ORDER BY nombre ASC'
        )->fetchAll();

        $catalogos['sedes'] = db()->query(
            'SELECT id_sede AS id, id_institucion, nombre_sede AS nombre
             FROM sedes
             WHERE activo = 1
             ORDER BY nombre_sede ASC'
        )->fetchAll();

        $catalogos['carreras'] = db()->query(
            'SELECT id_carrera AS id, nombre
             FROM carreras
             WHERE activo = 1
             ORDER BY nombre ASC'
        )->fetchAll();

        $catalogos['areas'] = db()->query(
            'SELECT id_area AS id, nombre
             FROM areas_practica
             WHERE activo = 1
             ORDER BY nombre ASC'
        )->fetchAll();

        $catalogos['estados'] = db()->query(
            'SELECT id_estado_practica AS id, nombre
             FROM estados_practica
             WHERE activo = 1
             ORDER BY nombre ASC'
        )->fetchAll();

        return $catalogos;
    }
}
