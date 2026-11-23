<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../Models/AreaPracticanteModel.php';
require_once __DIR__ . '/ExcepcionesHorarioService.php';

/*
|--------------------------------------------------------------------------
| Horario pactado del practicante
|--------------------------------------------------------------------------
|
| Funciones reutilizables para normalizar, validar, renderizar y persistir el
| horario semanal pactado. El formulario permite dias con jornada y dias sin
| jornada, manteniendo la validacion centralizada en backend.
|
*/

if (!function_exists('horarios_practicante_dias')) {
    function horarios_practicante_dias(): array
    {
        return [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo',
        ];
    }
}

if (!function_exists('horarios_practicante_valores_iniciales')) {
    function horarios_practicante_valores_iniciales(): array
    {
        $valores = [];

        foreach (horarios_practicante_dias() as $dia => $nombre) {
            $valores[$dia] = [
                'activo' => '0',
                'entrada_esperada' => '',
                'salida_esperada' => '',
                'colacion_inicio' => '',
                'colacion_fin' => '',
            ];
        }

        return $valores;
    }
}

if (!function_exists('horarios_practicante_normalizar_hora')) {
    function horarios_practicante_normalizar_hora(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $hora = trim((string) $valor);

        if ($hora === '') {
            return null;
        }

        // Si viene como número decimal de Excel (entre 0 y 1, por ejemplo 0.416666...)
        if (is_numeric($hora) && (float) $hora >= 0 && (float) $hora < 1) {
            $totalMinutos = (int) round(((float) $hora) * 1440);
            $h = (int) floor($totalMinutos / 60) % 24;
            $m = $totalMinutos % 60;

            return sprintf('%02d:%02d', $h, $m);
        }

        // Tolera HH:MM, H:MM, HH:MM:SS y H:MM:SS (ej: '10:00:00' -> '10:00', '9:00' -> '09:00')
        if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $hora, $matches)) {
            $h = (int) $matches[1];
            $m = (int) $matches[2];

            if ($h >= 0 && $h <= 23 && $m >= 0 && $m <= 59) {
                return sprintf('%02d:%02d', $h, $m);
            }
        }

        return null;
    }
}

if (!function_exists('horarios_practicante_normalizar')) {
    function horarios_practicante_normalizar(array $data): array
    {
        $entrada = is_array($data['horario'] ?? null) ? $data['horario'] : [];
        $horario = horarios_practicante_valores_iniciales();

        foreach (horarios_practicante_dias() as $dia => $nombre) {
            $fila = is_array($entrada[$dia] ?? null) ? $entrada[$dia] : [];
            $horario[$dia] = [
                'activo' => isset($fila['activo']) ? '1' : '0',
                'entrada_esperada' => horarios_practicante_normalizar_hora($fila['entrada_esperada'] ?? null) ?? '',
                'salida_esperada' => horarios_practicante_normalizar_hora($fila['salida_esperada'] ?? null) ?? '',
                'colacion_inicio' => horarios_practicante_normalizar_hora($fila['colacion_inicio'] ?? null) ?? '',
                'colacion_fin' => horarios_practicante_normalizar_hora($fila['colacion_fin'] ?? null) ?? '',
            ];
        }

        return $horario;
    }
}

if (!function_exists('horarios_practicante_tiene_dias')) {
    function horarios_practicante_tiene_dias(array $horario): bool
    {
        foreach ($horario as $fila) {
            if (($fila['activo'] ?? '0') === '1') {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('horarios_practicante_minutos')) {
    function horarios_practicante_minutos(string $hora): int
    {
        [$horas, $minutos] = array_map('intval', explode(':', $hora));

        return ($horas * 60) + $minutos;
    }
}

if (!function_exists('horarios_practicante_horas_planificadas')) {
    function horarios_practicante_horas_planificadas(array $fila): float
    {
        $entrada = horarios_practicante_minutos((string) $fila['entrada_esperada']);
        $salida = horarios_practicante_minutos((string) $fila['salida_esperada']);
        $minutos = $salida - $entrada;

        if (($fila['colacion_inicio'] ?? '') !== '' && ($fila['colacion_fin'] ?? '') !== '') {
            $minutos -= horarios_practicante_minutos((string) $fila['colacion_fin'])
                - horarios_practicante_minutos((string) $fila['colacion_inicio']);
        }

        return round(max(0, $minutos) / 60, 2);
    }
}

if (!function_exists('horarios_practicante_validar')) {
    function horarios_practicante_validar(array $horario, bool $requerido, ?string $fechaInicioPractica): array
    {
        $errores = [];
        $tieneDias = horarios_practicante_tiene_dias($horario);

        if ($requerido && !$tieneDias) {
            $errores[] = 'Debes registrar al menos un día con jornada pactada.';
        }

        if ($tieneDias && ($fechaInicioPractica === null || $fechaInicioPractica === '')) {
            $errores[] = 'Para registrar horario pactado, informa la fecha de inicio de práctica.';
        }

        foreach (horarios_practicante_dias() as $dia => $nombreDia) {
            $fila = $horario[$dia] ?? [];

            if (($fila['activo'] ?? '0') !== '1') {
                continue;
            }

            if (($fila['entrada_esperada'] ?? '') === '' || ($fila['salida_esperada'] ?? '') === '') {
                $errores[] = 'Completa entrada y salida para ' . $nombreDia . '.';
                continue;
            }

            $entrada = horarios_practicante_minutos((string) $fila['entrada_esperada']);
            $salida = horarios_practicante_minutos((string) $fila['salida_esperada']);

            if ($salida <= $entrada) {
                $errores[] = 'La salida debe ser posterior a la entrada en ' . $nombreDia . '.';
            }

            $colacionInicio = (string) ($fila['colacion_inicio'] ?? '');
            $colacionFin = (string) ($fila['colacion_fin'] ?? '');

            if (($colacionInicio === '' && $colacionFin !== '') || ($colacionInicio !== '' && $colacionFin === '')) {
                $errores[] = 'Completa inicio y fin de colación en ' . $nombreDia . ', o deja ambos vacíos.';
                continue;
            }

            if ($colacionInicio !== '' && $colacionFin !== '') {
                $inicio = horarios_practicante_minutos($colacionInicio);
                $fin = horarios_practicante_minutos($colacionFin);

                if ($fin <= $inicio || $inicio < $entrada || $fin > $salida) {
                    $errores[] = 'La colación debe estar dentro de la jornada en ' . $nombreDia . '.';
                }
            }

            if (horarios_practicante_horas_planificadas($fila) <= 0) {
                $errores[] = 'El horario de ' . $nombreDia . ' debe sumar horas planificadas mayores a cero.';
            }
        }

        return array_values(array_unique($errores));
    }
}

if (!function_exists('horarios_practicante_guardar')) {
    function horarios_practicante_guardar(int $idPractica, string $fechaInicioPractica, array $horario): ?int
    {
        if (!horarios_practicante_tiene_dias($horario)) {
            return null;
        }

        $pdo = db();

        // Se conserva historial: los horarios anteriores quedan inactivos y el nuevo queda vigente.
        $statement = $pdo->prepare(
            'UPDATE horarios_practicante
             SET activo = 0,
                 fecha_fin = COALESCE(
                    fecha_fin,
                    CASE
                        WHEN fecha_inicio < :fecha_inicio_fin_comparar THEN DATE_SUB(:fecha_inicio_fin_calcular, INTERVAL 1 DAY)
                        ELSE fecha_inicio
                    END
                 )
             WHERE id_practica = :id_practica
               AND activo = 1'
        );
        $statement->execute([
            'fecha_inicio_fin_comparar' => $fechaInicioPractica,
            'fecha_inicio_fin_calcular' => $fechaInicioPractica,
            'id_practica' => $idPractica,
        ]);

        $statement = $pdo->prepare(
            'INSERT INTO horarios_practicante (
                id_practica,
                nombre_horario,
                fecha_inicio,
                fecha_fin,
                activo
            ) VALUES (
                :id_practica,
                :nombre_horario,
                :fecha_inicio,
                NULL,
                1
            )'
        );
        $statement->execute([
            'id_practica' => $idPractica,
            'nombre_horario' => 'Horario pactado',
            'fecha_inicio' => $fechaInicioPractica,
        ]);
        $idHorario = (int) $pdo->lastInsertId();

        $statement = $pdo->prepare(
            'INSERT INTO detalle_horario_practicante (
                id_horario,
                dia_semana,
                entrada_esperada,
                salida_esperada,
                colacion_inicio,
                colacion_fin,
                horas_planificadas,
                activo
            ) VALUES (
                :id_horario,
                :dia_semana,
                :entrada_esperada,
                :salida_esperada,
                :colacion_inicio,
                :colacion_fin,
                :horas_planificadas,
                1
            )'
        );

        foreach (horarios_practicante_dias() as $dia => $nombreDia) {
            $fila = $horario[$dia] ?? [];

            if (($fila['activo'] ?? '0') !== '1') {
                continue;
            }

            $statement->execute([
                'id_horario' => $idHorario,
                'dia_semana' => $dia,
                'entrada_esperada' => $fila['entrada_esperada'],
                'salida_esperada' => $fila['salida_esperada'],
                'colacion_inicio' => ($fila['colacion_inicio'] ?? '') !== '' ? $fila['colacion_inicio'] : null,
                'colacion_fin' => ($fila['colacion_fin'] ?? '') !== '' ? $fila['colacion_fin'] : null,
                'horas_planificadas' => horarios_practicante_horas_planificadas($fila),
            ]);
        }

        return $idHorario;
    }
}

if (!function_exists('horarios_practicante_listar_practicantes_gestion')) {
    function horarios_practicante_listar_practicantes_gestion(?string $busqueda = null): array
    {
        $busqueda = trim((string) $busqueda);
        $params = [];
        $whereBusqueda = '';

        // Búsqueda inteligente por nombre completo, apellidos, email o RUT admitiendo espacios y múltiples términos
        if ($busqueda !== '') {
            $busquedaPracticante = sql_construir_busqueda_practicante($busqueda, 'p', 'busq_horarios');
            $whereBusqueda = $busquedaPracticante['where_sql'];
            $params = $busquedaPracticante['params'];
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
                i.nombre AS institucion,
                s.nombre_sede,
                c.nombre AS carrera,
                pr.id_practica,
                pr.fecha_inicio_practica,
                pr.horas_requeridas,
                tp.nombre AS tipo_practica,
                (
                    SELECT ap.nombre
                    FROM historial_areas_practicante hap
                    INNER JOIN areas_practica ap ON ap.id_area = hap.id_area
                    WHERE hap.id_practica = pr.id_practica
                      AND hap.fecha_fin IS NULL
                    ORDER BY hap.fecha_inicio DESC, hap.id_historial_area DESC
                    LIMIT 1
                ) AS area_vigente,
                hp.id_horario,
                hp.fecha_inicio AS horario_fecha_inicio,
                hp.fecha_fin AS horario_fecha_fin,
                u.estado_cuenta
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
             LEFT JOIN usuarios_sistema u ON u.id_practicante = p.id_practicante AND u.activo = 1
             WHERE p.activo = 1' . $whereBusqueda . '
             ORDER BY p.apellido_paterno ASC, p.nombre_1 ASC
             LIMIT 100'
        );
        $statement->execute($params);

        return $statement->fetchAll();
    }
}

if (!function_exists('horarios_practicante_obtener_practicante_gestion')) {
    /**
     * Obtiene la ficha completa de un practicante (datos personales, contacto, académicos y de práctica)
     * para visualizarla en el panel de detalle de gestión del supervisor o administrador.
     *
     * @param int $idPracticante ID del practicante.
     * @return array|null Fila con todos los datos o null si no se encuentra.
     */
    function horarios_practicante_obtener_practicante_gestion(int $idPracticante): ?array
    {
        // Consulta completa de practicante con datos personales, dirección, institución, carrera y práctica
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
                p.telefono,
                p.fecha_nacimiento,
                p.sexo,
                p.nacionalidad,
                p.direccion,
                p.numero_direccion,
                p.departamento,
                p.pais,
                p.semestre,
                p.institucion_otra,
                p.sede_otra,
                com.nombre_comuna AS comuna,
                reg.nombre_region AS region,
                COALESCE(i.nombre, p.institucion_otra, "Sin institución") AS institucion,
                COALESCE(s.nombre_sede, p.sede_otra, "Sin sede") AS nombre_sede,
                COALESCE(c.nombre, "Sin carrera") AS carrera,
                pr.id_practica,
                pr.fecha_inicio_practica,
                pr.fecha_termino_practica,
                pr.horas_requeridas,
                pr.horas_previas,
                tp.nombre AS tipo_practica,
                u.estado_cuenta,
                (
                    SELECT hap.id_area
                    FROM historial_areas_practicante hap
                    WHERE hap.id_practica = pr.id_practica
                      AND hap.fecha_fin IS NULL
                    ORDER BY hap.fecha_inicio DESC, hap.id_historial_area DESC
                    LIMIT 1
                ) AS id_area_vigente,
                (
                    SELECT ap.nombre
                    FROM historial_areas_practicante hap
                    INNER JOIN areas_practica ap ON ap.id_area = hap.id_area
                    WHERE hap.id_practica = pr.id_practica
                      AND hap.fecha_fin IS NULL
                    ORDER BY hap.fecha_inicio DESC, hap.id_historial_area DESC
                    LIMIT 1
                ) AS area_vigente
             FROM practicantes p
             INNER JOIN practicas pr ON pr.id_practica = (
                SELECT pr2.id_practica
                FROM practicas pr2
                WHERE pr2.id_practicante = p.id_practicante
                  AND pr2.activo = 1
                ORDER BY pr2.fecha_inicio_practica DESC, pr2.id_practica DESC
                LIMIT 1
             )
             LEFT JOIN comunas com ON com.id_comuna = p.id_comuna
             LEFT JOIN regiones reg ON reg.id_region = p.id_region
             LEFT JOIN instituciones i ON i.id_institucion = p.id_institucion
             LEFT JOIN sedes s ON s.id_sede = p.id_sede
             LEFT JOIN carreras c ON c.id_carrera = p.id_carrera
             LEFT JOIN tipos_practica tp ON tp.id_tipo_practica = pr.id_tipo_practica
             LEFT JOIN usuarios_sistema u ON u.id_practicante = p.id_practicante AND u.activo = 1
             WHERE p.id_practicante = :id_practicante
               AND p.activo = 1
             LIMIT 1'
        );
        $statement->execute(['id_practicante' => $idPracticante]);
        $practicante = $statement->fetch();

        return is_array($practicante) ? $practicante : null;
    }
}

if (!function_exists('horarios_practicante_resumen_vigente')) {
    function horarios_practicante_resumen_vigente(int $idPractica): ?array
    {
        $statement = db()->prepare(
            'SELECT id_horario, nombre_horario, fecha_inicio, fecha_fin, created_at
             FROM horarios_practicante
             WHERE id_practica = :id_practica
               AND activo = 1
             ORDER BY fecha_inicio DESC, id_horario DESC
             LIMIT 1'
        );
        $statement->execute(['id_practica' => $idPractica]);
        $horario = $statement->fetch();

        return is_array($horario) ? $horario : null;
    }
}

if (!function_exists('horarios_practicante_describir')) {
    function horarios_practicante_describir(array $horario): string
    {
        $partes = [];

        foreach (horarios_practicante_dias() as $dia => $nombreDia) {
            $fila = $horario[$dia] ?? [];

            if (($fila['activo'] ?? '0') !== '1') {
                continue;
            }

            $texto = $nombreDia . ' ' . ($fila['entrada_esperada'] ?? '') . '-' . ($fila['salida_esperada'] ?? '');

            if (($fila['colacion_inicio'] ?? '') !== '' && ($fila['colacion_fin'] ?? '') !== '') {
                $texto .= ' col. ' . $fila['colacion_inicio'] . '-' . $fila['colacion_fin'];
            }

            $partes[] = $texto;
        }

        return implode('; ', $partes);
    }
}

if (!function_exists('horarios_practicante_baja_logica_practicante')) {
    function horarios_practicante_baja_logica_practicante(int $idPracticante): bool
    {
        $pdo = db();

        $statement = $pdo->prepare(
            'SELECT
                p.id_practicante,
                p.nombre_1,
                p.apellido_paterno,
                p.email,
                p.activo,
                u.id_usuario,
                u.estado_cuenta,
                u.activo AS usuario_activo
             FROM practicantes p
             LEFT JOIN usuarios_sistema u ON u.id_practicante = p.id_practicante
             WHERE p.id_practicante = :id_practicante
             LIMIT 1'
        );
        $statement->execute(['id_practicante' => $idPracticante]);
        $actual = $statement->fetch();

        if (!is_array($actual) || (int) $actual['activo'] !== 1) {
            return false;
        }

        try {
            $pdo->beginTransaction();

            // Bloqueé las prácticas antes de la baja para impedir designaciones concurrentes o líderes huérfanos.
            $liderPracticas = $pdo->prepare('SELECT id_practica FROM practicas WHERE id_practicante=? ORDER BY id_practica FOR UPDATE');
            $liderPracticas->execute([$idPracticante]);
            foreach ($liderPracticas->fetchAll() as $liderPractica) {
                liderazgos_exigir_practica_sin_lider((int)$liderPractica['id_practica']);
            }

            $statement = $pdo->prepare(
                'UPDATE practicantes
                 SET activo = 0
                 WHERE id_practicante = :id_practicante
                   AND activo = 1'
            );
            $statement->execute(['id_practicante' => $idPracticante]);

            $statement = $pdo->prepare(
                'UPDATE practicas
                 SET activo = 0
                 WHERE id_practicante = :id_practicante
                   AND activo = 1'
            );
            $statement->execute(['id_practicante' => $idPracticante]);

            $statement = $pdo->prepare(
                'UPDATE usuarios_sistema
                 SET activo = 0,
                     estado_cuenta = :estado_cuenta
                 WHERE id_practicante = :id_practicante
                   AND activo = 1'
            );
            $statement->execute([
                'estado_cuenta' => 'INACTIVO',
                'id_practicante' => $idPracticante,
            ]);

            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }

        auditoria_registrar('EDITAR_PRACTICANTE', [
            'tabla_afectada' => 'practicantes',
            'id_registro_afectado' => $idPracticante,
            'valores_anteriores' => [
                'id_practicante' => (int) $actual['id_practicante'],
                'email' => (string) $actual['email'],
                'activo' => (int) $actual['activo'],
                'id_usuario' => $actual['id_usuario'] !== null ? (int) $actual['id_usuario'] : null,
                'usuario_activo' => $actual['usuario_activo'] !== null ? (int) $actual['usuario_activo'] : null,
                'estado_cuenta' => $actual['estado_cuenta'],
            ],
            'valores_nuevos' => [
                'id_practicante' => $idPracticante,
                'activo' => 0,
                'practicas_activas' => 0,
                'usuario_activo' => 0,
                'estado_cuenta' => 'INACTIVO',
            ],
        ]);

        return true;
    }
}

if (!function_exists('horarios_practicante_obtener_vigente')) {
    function horarios_practicante_obtener_vigente(int $idPractica): array
    {
        $statement = db()->prepare(
            'SELECT id_horario
             FROM horarios_practicante
             WHERE id_practica = :id_practica
               AND activo = 1
             ORDER BY fecha_inicio DESC, id_horario DESC
             LIMIT 1'
        );
        $statement->execute(['id_practica' => $idPractica]);
        $idHorario = $statement->fetchColumn();

        if ($idHorario === false) {
            return horarios_practicante_valores_iniciales();
        }

        $statement = db()->prepare(
            'SELECT dia_semana, entrada_esperada, salida_esperada, colacion_inicio, colacion_fin
             FROM detalle_horario_practicante
             WHERE id_horario = :id_horario
               AND activo = 1
             ORDER BY dia_semana ASC'
        );
        $statement->execute(['id_horario' => (int) $idHorario]);

        $horario = horarios_practicante_valores_iniciales();

        foreach ($statement->fetchAll() as $fila) {
            $dia = (int) $fila['dia_semana'];
            $horario[$dia] = [
                'activo' => '1',
                'entrada_esperada' => substr((string) $fila['entrada_esperada'], 0, 5),
                'salida_esperada' => substr((string) $fila['salida_esperada'], 0, 5),
                'colacion_inicio' => $fila['colacion_inicio'] !== null ? substr((string) $fila['colacion_inicio'], 0, 5) : '',
                'colacion_fin' => $fila['colacion_fin'] !== null ? substr((string) $fila['colacion_fin'], 0, 5) : '',
            ];
        }

        return $horario;
    }
}

// Trasladé horarios_practicante_render_form a la capa de presentación en fase 4.


// Trasladé horarios_practicante_render_gestion_page a la capa de presentación en fase 4.
