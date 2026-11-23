<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/ReglasAsistenciaService.php';
require_once __DIR__ . '/../Models/AreaJornadaModel.php';
require_once __DIR__ . '/AusenciasEsperadasService.php';

if (!function_exists('asistencia_gestion_fecha_valida')) {
    function asistencia_gestion_fecha_valida(?string $fecha): bool
    {
        if ($fecha === null || $fecha === '') {
            return false;
        }

        $objetoFecha = DateTimeImmutable::createFromFormat('Y-m-d', $fecha);

        return $objetoFecha !== false && $objetoFecha->format('Y-m-d') === $fecha;
    }
}

if (!function_exists('asistencia_gestion_fecha_hora_valida')) {
    function asistencia_gestion_fecha_hora_valida(?string $fechaHora): bool
    {
        if ($fechaHora === null || $fechaHora === '') {
            return false;
        }

        $normalizada = str_replace('T', ' ', $fechaHora);
        $objetoFecha = DateTimeImmutable::createFromFormat('Y-m-d H:i', $normalizada);

        return $objetoFecha !== false && $objetoFecha->format('Y-m-d H:i') === $normalizada;
    }
}

if (!function_exists('asistencia_gestion_fecha_hora_sql')) {
    function asistencia_gestion_fecha_hora_sql(string $fechaHora): string
    {
        return str_replace('T', ' ', $fechaHora) . ':00';
    }
}

if (!function_exists('asistencia_gestion_formatear_fecha')) {
    function asistencia_gestion_formatear_fecha(?string $fecha): string
    {
        if ($fecha === null || $fecha === '') {
            return 'No registrada';
        }

        $timestamp = strtotime($fecha);

        return $timestamp === false ? $fecha : date('d-m-Y', $timestamp);
    }
}

if (!function_exists('asistencia_gestion_formatear_fecha_hora')) {
    function asistencia_gestion_formatear_fecha_hora(?string $fechaHora): string
    {
        if ($fechaHora === null || $fechaHora === '') {
            return 'No registrada';
        }

        $timestamp = strtotime($fechaHora);

        return $timestamp === false ? $fechaHora : date('d-m-Y H:i', $timestamp);
    }
}

if (!function_exists('asistencia_gestion_formatear_hora')) {
    /**
     * Formatea una hora en formato HH:MM:SS o HH:MM a HH:MM para presentación limpia.
     *
     * @param string|null $hora Cadena de hora.
     * @return string Hora en formato HH:MM o '--:--' si es nula.
     */
    function asistencia_gestion_formatear_hora(?string $hora): string
    {
        if ($hora === null || trim($hora) === '') {
            return '--:--';
        }

        $partes = explode(':', trim($hora));
        if (count($partes) >= 2) {
            return $partes[0] . ':' . $partes[1];
        }

        return $hora;
    }
}

if (!function_exists('asistencia_gestion_estado_badge')) {
    function asistencia_gestion_estado_badge(?string $estado): string
    {
        return match ($estado) {
            'VALIDA' => 'success',
            'OBSERVADA' => 'warning',
            'INCOMPLETA' => 'secondary',
            'PENDIENTE_REVISION' => 'info',
            'ANULADA' => 'dark',
            // Agregué un tono de cierre para la revisión independiente.
            'REVISADA' => 'success',
            'CORREGIDA' => 'primary',
            default => 'secondary',
        };
    }
}

if (!function_exists('asistencia_gestion_nombre_practicante')) {
    function asistencia_gestion_nombre_practicante(array $jornada): string
    {
        $partes = [
            $jornada['nombre_1'] ?? '',
            $jornada['nombre_2'] ?? '',
            $jornada['apellido_paterno'] ?? '',
            $jornada['apellido_materno'] ?? '',
        ];

        return trim((string) preg_replace('/\s+/', ' ', implode(' ', array_filter($partes))));
    }
}

if (!function_exists('asistencia_gestion_filtros')) {
    function asistencia_gestion_filtros(): array
    {
        $desde = query_param('desde');
        $hasta = query_param('hasta');
        $estado = strtoupper(trim((string) query_param('estado', '')));
        $busqueda = trim((string) query_param('q', ''));
        $pagina = max(1, (int) query_param('pagina', 1));

        $desde = is_string($desde) && asistencia_gestion_fecha_valida($desde) ? $desde : date('Y-m-d', strtotime('-30 days'));
        $hasta = is_string($hasta) && asistencia_gestion_fecha_valida($hasta) ? $hasta : date('Y-m-d');

        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        if ($estado !== '' && !asistencia_estado_valido($estado)) {
            $estado = '';
        }

        return [
            'desde' => $desde,
            'hasta' => $hasta,
            'estado' => $estado,
            'q' => $busqueda,
            'pagina' => $pagina,
        ];
    }
}

if (!function_exists('asistencia_gestion_url_con_filtros')) {
    function asistencia_gestion_url_con_filtros(string $ruta, array $filtros, array $extra = []): string
    {
        $params = array_merge($filtros, $extra);
        $params = array_filter($params, static fn ($valor): bool => $valor !== null && $valor !== '');

        return url($ruta . ($params === [] ? '' : '?' . http_build_query($params)));
    }
}

if (!function_exists('asistencia_gestion_jornadas_conteo')) {
    function asistencia_gestion_jornadas_conteo(array $filtros): int
    {
        $where = ['ja.fecha BETWEEN :desde AND :hasta'];
        $params = [
            'desde' => $filtros['desde'],
            'hasta' => $filtros['hasta'],
        ];

        if (($filtros['estado'] ?? '') !== '') {
            $where[] = 'ja.estado_jornada = :estado';
            $params['estado'] = $filtros['estado'];
        }

        // Búsqueda inteligente por nombre completo, apellidos, email o RUT admitiendo espacios y múltiples términos
        if (($filtros['q'] ?? '') !== '') {
            $busquedaPracticante = sql_construir_busqueda_practicante((string) $filtros['q'], 'p', 'busq_conteo');
            if ($busquedaPracticante['condicion'] !== '') {
                $where[] = $busquedaPracticante['condicion'];
                $params = array_merge($params, $busquedaPracticante['params']);
            }
        }

        $statement = db()->prepare(
            'SELECT COUNT(*) AS total
             FROM jornadas_asistencia ja
             INNER JOIN practicas pr ON pr.id_practica = ja.id_practica
             INNER JOIN practicantes p ON p.id_practicante = pr.id_practicante
             WHERE ' . implode(' AND ', $where)
        );
        $statement->execute($params);

        return (int) $statement->fetchColumn();
    }
}

if (!function_exists('asistencia_gestion_jornadas')) {
    function asistencia_gestion_jornadas(array $filtros, int $limite = 10): array
    {
        $pagina = max(1, (int) ($filtros['pagina'] ?? 1));
        $offset = ($pagina - 1) * $limite;

        $where = ['ja.fecha BETWEEN :desde AND :hasta'];
        $params = [
            'desde' => $filtros['desde'],
            'hasta' => $filtros['hasta'],
        ];

        if (($filtros['estado'] ?? '') !== '') {
            $where[] = 'ja.estado_jornada = :estado';
            $params['estado'] = $filtros['estado'];
        }

        // Búsqueda inteligente por nombre completo, apellidos, email o RUT admitiendo espacios y múltiples términos
        if (($filtros['q'] ?? '') !== '') {
            $busquedaPracticante = sql_construir_busqueda_practicante((string) $filtros['q'], 'p', 'busq_jornada');
            if ($busquedaPracticante['condicion'] !== '') {
                $where[] = $busquedaPracticante['condicion'];
                $params = array_merge($params, $busquedaPracticante['params']);
            }
        }

        $limiteInt = (int) $limite;
        $offsetInt = (int) $offset;

        $statement = db()->prepare(
            'SELECT
                ja.*,
                pr.id_practicante,
                pr.horas_requeridas,
                p.nombre_1,
                p.nombre_2,
                p.apellido_paterno,
                p.apellido_materno,
                p.email,
                p.rut_numero,
                p.rut_dv,
                tp.nombre AS tipo_practica_nombre,
                ep.nombre AS estado_practica_nombre,
                (
                    SELECT COUNT(*)
                    FROM marcas_asistencia ma
                    WHERE ma.id_jornada = ja.id_jornada
                      AND ma.activo = 1
                      AND ma.anulada = 0
                ) AS marcas_vigentes,
                (
                    SELECT COUNT(*)
                    FROM marcas_asistencia ma
                    WHERE ma.id_jornada = ja.id_jornada
                      AND ma.anulada = 1
                ) AS marcas_anuladas
             FROM jornadas_asistencia ja
             INNER JOIN practicas pr ON pr.id_practica = ja.id_practica
             INNER JOIN practicantes p ON p.id_practicante = pr.id_practicante
             LEFT JOIN tipos_practica tp ON tp.id_tipo_practica = pr.id_tipo_practica
             INNER JOIN estados_practica ep ON ep.id_estado_practica = pr.id_estado_practica
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY ja.fecha DESC, ja.id_jornada DESC
             LIMIT ' . $limiteInt . ' OFFSET ' . $offsetInt
        );
        $statement->execute($params);

        return $statement->fetchAll();
    }
}

if (!function_exists('asistencia_gestion_jornada_detalle')) {
    function asistencia_gestion_jornada_detalle(int $idJornada): ?array
    {
        $statement = db()->prepare(
            'SELECT
                ja.*,
                pr.id_practicante,
                pr.horas_requeridas,
                p.nombre_1,
                p.nombre_2,
                p.apellido_paterno,
                p.apellido_materno,
                p.email,
                p.rut_numero,
                p.rut_dv,
                tp.nombre AS tipo_practica_nombre,
                ep.nombre AS estado_practica_nombre
             FROM jornadas_asistencia ja
             INNER JOIN practicas pr ON pr.id_practica = ja.id_practica
             INNER JOIN practicantes p ON p.id_practicante = pr.id_practicante
             LEFT JOIN tipos_practica tp ON tp.id_tipo_practica = pr.id_tipo_practica
             INNER JOIN estados_practica ep ON ep.id_estado_practica = pr.id_estado_practica
             WHERE ja.id_jornada = :id_jornada
             LIMIT 1'
        );
        $statement->execute(['id_jornada' => $idJornada]);
        $jornada = $statement->fetch();

        return is_array($jornada) ? $jornada : null;
    }
}

if (!function_exists('asistencia_gestion_marcas_jornada')) {
    function asistencia_gestion_marcas_jornada(int $idJornada): array
    {
        $statement = db()->prepare(
            'SELECT
                ma.*,
                tm.codigo,
                tm.nombre AS tipo_marca,
                tm.orden,
                uc.email AS creada_por_email,
                ua.email AS anulada_por_email
             FROM marcas_asistencia ma
             INNER JOIN tipos_marca tm ON tm.id_tipo_marca = ma.id_tipo_marca
             LEFT JOIN usuarios_sistema uc ON uc.id_usuario = ma.creada_por
             LEFT JOIN usuarios_sistema ua ON ua.id_usuario = ma.anulada_por
             WHERE ma.id_jornada = :id_jornada
             /* Conservé la secuencia real de permiso y colación en marcas del mismo segundo. */
             ORDER BY ma.fecha_hora ASC, ma.id_marca ASC'
        );
        $statement->execute(['id_jornada' => $idJornada]);

        return $statement->fetchAll();
    }
}

//Se modificó el return asistencia_tipos_marca() debido a que ocasionaba un bug.

if (!function_exists('asistencia_gestion_tipos_marca')) {
    function asistencia_gestion_tipos_marca(): array
    {
        return [
            ['codigo' => 'ENTRADA',         'nombre' => 'Entrada'],
            ['codigo' => 'ENTRADA_COLACION', 'nombre' => 'Entrada colación'],
            ['codigo' => 'SALIDA_COLACION',  'nombre' => 'Salida colación'],
            ['codigo' => 'SALIDA',           'nombre' => 'Salida'],
        ];
    }
}

if (!function_exists('asistencia_gestion_puede_operar_jornada')) {
    function asistencia_gestion_puede_operar_jornada(array $jornada): bool
    {
        // Reforcé la comprobación para exigir rol operativo y práctica válida,
        // además de impedir que un supervisor opere su propia práctica.
        return auth_puede_supervisar_practica((int) ($jornada['id_practica'] ?? 0));
    }
}

// Agregué bloqueo y auditoría atómica también a los cambios de estado normales,
// para que no compitan con una revisión ni reactiven jornadas anuladas.
if (!function_exists('asistencia_gestion_cambiar_estado')) {
    function asistencia_gestion_cambiar_estado(int $idJornada, string $estado, string $motivo): array
    {
        $estado = strtoupper(trim($estado));
        $motivo = trim($motivo);
        // Agregué dos cierres independientes que validan marcas sin aprobar extras.
        if (in_array($estado, ['CORREGIDA', 'REVISADA'], true)) {
            return asistencia_gestion_marcar_revisada($idJornada, $motivo, $estado);
        }
        if (!in_array($estado, ['OBSERVADA', 'PENDIENTE_REVISION'], true)
            || $motivo === '' || mb_strlen($motivo) > 255) {
            return ['ok' => false, 'mensaje' => 'Indica un estado permitido y un motivo de hasta 255 caracteres.'];
        }
        if (!auth_check() || !in_array(auth_rol_activo(), ['ADMIN', 'SUPERVISOR'], true)) {
            return ['ok' => false, 'mensaje' => 'No tienes permisos para cambiar el estado.'];
        }
        $pdo = db();
        try {
            $pdo->beginTransaction();
            $lock = $pdo->prepare('SELECT * FROM jornadas_asistencia WHERE id_jornada = :id_jornada FOR UPDATE');
            $lock->execute(['id_jornada' => $idJornada]);
            $jornada = $lock->fetch();
            if (!is_array($jornada) || $jornada['estado_jornada'] === 'ANULADA'
                || !asistencia_gestion_puede_operar_jornada($jornada)) {
                $pdo->rollBack();
                return ['ok' => false, 'mensaje' => 'No puedes cambiar el estado de esta jornada.'];
            }
            $statement = $pdo->prepare('UPDATE jornadas_asistencia SET estado_jornada = :estado, observacion = :observacion WHERE id_jornada = :id_jornada');
            $statement->execute(['estado' => $estado, 'observacion' => $motivo, 'id_jornada' => $idJornada]);
            if (!auditoria_registrar('CORREGIR_ASISTENCIA', [
                'tabla_afectada' => 'jornadas_asistencia', 'id_registro_afectado' => $idJornada,
                'valores_anteriores' => $jornada, 'valores_nuevos' => asistencia_jornada_por_id($idJornada),
            ])) {
                throw new RuntimeException('No se pudo auditar el cambio de estado.');
            }
            $pdo->commit();
            return ['ok' => true, 'mensaje' => 'Estado de jornada actualizado.'];
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Error al cambiar estado de jornada: ' . $exception->getMessage());
            return ['ok' => false, 'mensaje' => 'No fue posible guardar el estado y su auditoría.'];
        }
    }
}
// Agregué validación de las marcas vigentes antes de reconocer una revisión.
// Conserva las secuencias permitidas y rechaza duplicados, fechas incorrectas
// o una salida anterior/igual a la entrada, sin inventar una colación.
if (!function_exists('asistencia_gestion_validar_revision')) {
    function asistencia_gestion_validar_revision(array $jornada, array $marcas): array
    {
        if (!in_array($jornada['estado_jornada'] ?? '', ['OBSERVADA', 'PENDIENTE_REVISION'], true)) {
            return ['ok' => false, 'mensaje' => 'Solo se revisan jornadas observadas o pendientes de revisión.'];
        }
        $vigentes = array_values(array_filter($marcas, static fn(array $marca): bool => (int) ($marca['activo'] ?? 1) === 1 && (int) ($marca['anulada'] ?? 0) === 0));
        usort($vigentes, static fn(array $a, array $b): int => strcmp((string) ($a['fecha_hora'] ?? ''), (string) ($b['fecha_hora'] ?? '')));
        $revisadas = [];
        foreach ($vigentes as $marca) {
            $fechaHora = (string) ($marca['fecha_hora'] ?? '');
            $fecha = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $fechaHora);
            if ($fecha === false || $fecha->format('Y-m-d H:i:s') !== $fechaHora
                || substr($fechaHora, 0, 10) !== (string) ($jornada['fecha'] ?? '')
                || !asistencia_validar_secuencia($revisadas, (string) ($marca['codigo'] ?? ''))['ok']) {
                return ['ok' => false, 'mensaje' => 'Las marcas requieren corrección antes de revisar la jornada.'];
            }
            $revisadas[] = $marca;
        }
        $entrada = asistencia_marca_por_codigo($revisadas, 'ENTRADA');
        $salida = asistencia_marca_por_codigo($revisadas, 'SALIDA');
        if ($entrada === null || $salida === null || $salida['fecha_hora'] <= $entrada['fecha_hora']) {
            return ['ok' => false, 'mensaje' => 'Se requieren entrada y salida válidas para revisar la jornada.'];
        }
        return ['ok' => true, 'mensaje' => 'Marcas válidas para revisión.'];
    }
}

// Agregué revisión atómica con bloqueo de jornada, validación de propiedad y
// auditoría obligatoria. Conserva las marcas y las extras aprobadas existentes;
// no escribe una aprobación implícita de horas extra.
if (!function_exists('asistencia_gestion_marcar_revisada')) {
    function asistencia_gestion_marcar_revisada(int $idJornada, string $motivo, string $estado = 'CORREGIDA'): array
    {
        // Conservé el destino de llamadas existentes y limité los nuevos cierres permitidos.
        if (!in_array($estado, ['CORREGIDA', 'REVISADA'], true)) {
            return ['ok' => false, 'mensaje' => 'El estado de cierre no está permitido.'];
        }
        $motivo = trim($motivo);
        if ($motivo === '' || mb_strlen($motivo) > 255) {
            return ['ok' => false, 'mensaje' => 'Indica un motivo de revisión de hasta 255 caracteres.'];
        }
        if (!auth_check() || !in_array(auth_rol_activo(), ['ADMIN', 'SUPERVISOR'], true)) {
            return ['ok' => false, 'mensaje' => 'No tienes permisos para revisar jornadas.'];
        }
        $pdo = db();
        try {
            $pdo->beginTransaction();
            $lock = $pdo->prepare('SELECT * FROM jornadas_asistencia WHERE id_jornada = :id_jornada FOR UPDATE');
            $lock->execute(['id_jornada' => $idJornada]);
            $jornada = $lock->fetch();
            if (!is_array($jornada) || !asistencia_gestion_puede_operar_jornada($jornada)) {
                $pdo->rollBack();
                return ['ok' => false, 'mensaje' => 'No puedes revisar la jornada seleccionada.'];
            }
            $marcas = asistencia_marcas_vigentes_jornada($idJornada);
            $validacion = asistencia_gestion_validar_revision($jornada, $marcas);
            if (!$validacion['ok']) {
                $pdo->rollBack();
                return $validacion;
            }
            $horario = asistencia_horario_vigente_dia((int) $jornada['id_practica'], (string) $jornada['fecha']);
            $resumen = asistencia_calcular_resumen_jornada($marcas, $horario);
            // Incluí únicamente recuperación validada y separé los minutos reservados de extras.
            $resumen = recuperaciones_ajustar_resumen($jornada, $marcas, $horario, $resumen);
            // Incluí canjes autorizados al cerrar una jornada, sin aprobar tiempo excepcional por esta acción.
            $resumen = tiempo_ajustar_resumen($jornada, $marcas, $horario, $resumen);
            // Agregué una comprobación de coherencia para no duplicar tiempo si
            // una aprobación anterior incluía la colación que ahora es base.
            if (tiempo_extra_historica($idJornada)===null && (float) ($jornada['horas_extra_aprobadas'] ?? 0) > (float) $resumen['horas_extra_sugeridas'] + 0.001) {
                $pdo->rollBack();
                return ['ok' => false, 'mensaje' => 'Revisa primero las horas extra aprobadas: exceden las detectadas con las marcas actuales.'];
            }
            // Conservé el motivo anterior y registré el tipo de cierre en el historial.
            $observacion = trim((string) ($jornada['observacion'] ?? '') . ' ' . ($estado === 'REVISADA' ? 'Revisada' : 'Corregida') . ': ' . $motivo);
            $statement = $pdo->prepare(
                'UPDATE jornadas_asistencia SET estado_jornada = :estado,
                 hora_entrada = :hora_entrada, hora_salida = :hora_salida,
                 horas_calculadas = :horas, flag_atraso = :atraso,
                 flag_salida_anticipada = :anticipada, flag_horas_extra = :extra,
                 flag_asistencia_extraordinaria = :extraordinaria, observacion = :observacion
                 WHERE id_jornada = :id_jornada'
            );
            $statement->execute([
                'estado' => $estado,
                'hora_entrada' => $resumen['hora_entrada'], 'hora_salida' => $resumen['hora_salida'],
                'horas' => $resumen['horas_calculadas'], 'atraso' => $resumen['flag_atraso'],
                'anticipada' => $resumen['flag_salida_anticipada'], 'extra' => $resumen['flag_horas_extra'],
                'extraordinaria' => $resumen['flag_asistencia_extraordinaria'],
                'observacion' => $observacion, 'id_jornada' => $idJornada,
            ]);
            if (!auditoria_registrar('REVISAR_JORNADA', [
                'tabla_afectada' => 'jornadas_asistencia', 'id_registro_afectado' => $idJornada,
                'valores_anteriores' => $jornada,
                'valores_nuevos' => asistencia_jornada_por_id($idJornada),
            ])) {
                throw new RuntimeException('No se pudo registrar la auditoría de revisión.');
            }
            $pdo->commit();
            return ['ok' => true, 'mensaje' => 'Jornada marcada como ' . ($estado === 'REVISADA' ? 'revisada' : 'corregida') . ', sin aprobar horas extra.'];
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Error al revisar jornada: ' . $exception->getMessage());
            return ['ok' => false, 'mensaje' => 'No fue posible guardar la revisión y su auditoría.'];
        }
    }
}

if (!function_exists('asistencia_gestion_horas_extra_sugeridas')) {
    /**
     * Calcula las horas extra sugeridas pendientes de confirmación para una jornada.
     * Evalúa las marcas de asistencia y el horario asignado al practicante aplicando
     * el margen de tolerancia de 0.25 hrs (15 minutos).
     * 
     * @param array $jornada Fila de la jornada de asistencia.
     * @return float Horas extra sugeridas (excedente sobre el margen de 0.25 hrs).
     */
    function asistencia_gestion_horas_extra_sugeridas(array $jornada): float
    {
        // Desde el corte se desactiva la acumulación antigua para todas las jornadas pendientes.
        if(tiempo_politica()!==null) { return 0.0; }
        // Regla de horas extra: si no tiene activada la bandera flag_horas_extra, no hay horas extra pendientes
        if ((int) ($jornada['flag_horas_extra'] ?? 0) !== 1) {
            return 0.0;
        }

        $idJornada = (int) ($jornada['id_jornada'] ?? 0);

        if ($idJornada > 0) {
            // Obtenemos las marcas vigentes y el horario pactado para el día
            $marcas = asistencia_marcas_vigentes_jornada($idJornada);
            $horario = asistencia_horario_vigente_dia((int) $jornada['id_practica'], (string) $jornada['fecha']);
            $resumen = asistencia_calcular_resumen_jornada($marcas, $horario);
            // Incluí únicamente recuperación validada y separé los minutos reservados de extras.
            $resumen = recuperaciones_ajustar_resumen($jornada, $marcas, $horario, $resumen);

            // Regla de horas_extra_sugeridas bajo condición mayor a 0,25 hrs:
            // Solo retornamos horas extra sugeridas si superan el umbral de 15 minutos (0.25 hrs)
            // El sábado nuevo exige revisión incluso de fracciones menores a quince minutos.
            if (isset($resumen['horas_extra_sugeridas']) && (float) $resumen['horas_extra_sugeridas'] > 0) {
                return (float) $resumen['horas_extra_sugeridas'];
            }
        }

        // Eliminé la sugerencia inventada: sin marcas que permitan calcular
        // un exceso real no se propone tiempo adicional para aprobar.
        return 0.0;
    }
}
// Unifiqué el total visual con las reglas comunes y distinguí la revisión de
// jornada de una aprobación de extras, sin reconocer horas de estados pendientes.
if (!function_exists('asistencia_gestion_calcular_horas_vista')) {
    function asistencia_gestion_calcular_horas_vista(array $jornada): array
    {
        $tieneHorasExtra = (int) ($jornada['flag_horas_extra'] ?? 0) === 1;
        // Tras el corte solo mostré como extras los importes históricos ya aprobados.
        if(tiempo_politica()!==null) { $tieneHorasExtra=(float)($jornada['horas_extra_aprobadas']??0)>0; }
        $horasExtraAprobadas = isset($jornada['horas_extra_aprobadas']) ? (float) $jornada['horas_extra_aprobadas'] : null;
        return [
            'tiene_horas_extra' => $tieneHorasExtra,
            'es_validada' => $horasExtraAprobadas !== null && asistencia_estado_suma_horas((string) ($jornada['estado_jornada'] ?? '')),
            'es_revisada' => asistencia_jornada_revision_resuelta($jornada),
            'horas_base' => (float) ($jornada['horas_calculadas'] ?? 0),
            'horas_extra_aprobadas' => $horasExtraAprobadas,
            'horas_extra_sugeridas' => $tieneHorasExtra ? asistencia_gestion_horas_extra_sugeridas($jornada) : 0.0,
            'horas_validadas' => asistencia_horas_reconocidas($jornada),
        ];
    }
}

// Reforcé la aprobación para calcular el tiempo real bajo bloqueo, autorizar
// la práctica y auditar en la misma transacción. No puede aprobar una pausa
// inexistente como extra ni exceder el tiempo detectado fuera del turno.
if (!function_exists('asistencia_gestion_aprobar_horas_extra')) {
    function asistencia_gestion_aprobar_horas_extra(int $idJornada, string $horasAprobadas, string $motivo): array
    {
        // Cerré también el POST antiguo: ocultar el botón no basta para evitar nuevas extras.
        if(tiempo_politica()!==null) { return ['ok'=>false,'mensaje'=>'La aprobación antigua está cerrada. Usa Tiempo excepcional en Permisos; las extras históricas se conservan.']; }
        $motivo = trim($motivo);
        $normalizadas = str_replace(',', '.', trim($horasAprobadas));
        if ($motivo === '' || mb_strlen($motivo) > 255 || !is_numeric($normalizadas)) {
            return ['ok' => false, 'mensaje' => 'Indica un motivo y horas aprobadas válidas.'];
        }
        $horas = round((float) $normalizadas, 2);
        if (!is_finite($horas) || $horas < 0 || $horas > 12) {
            return ['ok' => false, 'mensaje' => 'Las horas extra aprobadas deben estar entre 0 y 12.'];
        }
        if (!auth_check() || !in_array(auth_rol_activo(), ['ADMIN', 'SUPERVISOR'], true)) {
            return ['ok' => false, 'mensaje' => 'No tienes permisos para aprobar horas extra.'];
        }
        $pdo = db();
        try {
            $pdo->beginTransaction();
            $lock = $pdo->prepare('SELECT * FROM jornadas_asistencia WHERE id_jornada = :id_jornada FOR UPDATE');
            $lock->execute(['id_jornada' => $idJornada]);
            $jornada = $lock->fetch();
            if (!is_array($jornada) || !asistencia_gestion_puede_operar_jornada($jornada)
                || in_array($jornada['estado_jornada'], ['ANULADA', 'INCOMPLETA'], true)) {
                $pdo->rollBack();
                return ['ok' => false, 'mensaje' => 'No puedes aprobar horas extra en esta jornada.'];
            }
            $marcas = asistencia_marcas_vigentes_jornada($idJornada);
            // Reutilicé la validación de marcas al actualizar una aprobación existente.
            $validacion = asistencia_gestion_validar_revision(array_replace($jornada, ['estado_jornada' => 'OBSERVADA']), $marcas);
            if (!$validacion['ok']) { $pdo->rollBack(); return $validacion; }
            $horario = asistencia_horario_vigente_dia((int) $jornada['id_practica'], (string) $jornada['fecha']);
            $resumen = asistencia_calcular_resumen_jornada($marcas, $horario);
            // Incluí únicamente recuperación validada y separé los minutos reservados de extras.
            $resumen = recuperaciones_ajustar_resumen($jornada, $marcas, $horario, $resumen);
            $disponibles = (float) $resumen['horas_extra_sugeridas'];
            // Permití retirar una aprobación anterior incompatible con el recálculo.
            if (($disponibles <= 0 && $jornada['horas_extra_aprobadas'] === null)
                || $horas > $disponibles + 0.001) {
                $pdo->rollBack();
                return ['ok' => false, 'mensaje' => 'Las horas solicitadas superan las extras detectadas. Revisa la jornada o ajusta la aprobación.'];
            }
            $statement = $pdo->prepare(
                'UPDATE jornadas_asistencia SET horas_calculadas = :base,
                 horas_extra_aprobadas = :extra, aprobada_por = :actor,
                 estado_jornada = "CORREGIDA", flag_horas_extra = :flag_extra,
                 observacion = :observacion WHERE id_jornada = :id_jornada'
            );
            $statement->execute([
                'base' => $resumen['horas_calculadas'], 'extra' => $horas,
                'actor' => auth_usuario_actual_id(), 'flag_extra' => $resumen['flag_horas_extra'],
                'observacion' => trim((string) ($jornada['observacion'] ?? '') . ' Horas extra aprobadas: ' . number_format($horas, 2, '.', '') . ' hrs. ' . $motivo),
                'id_jornada' => $idJornada,
            ]);
            if (!auditoria_registrar('APROBAR_HORAS_EXTRA', [
                'tabla_afectada' => 'jornadas_asistencia', 'id_registro_afectado' => $idJornada,
                'valores_anteriores' => $jornada, 'valores_nuevos' => asistencia_jornada_por_id($idJornada),
            ])) { throw new RuntimeException('No se pudo auditar la aprobación de horas extra.'); }
            $pdo->commit();
            return ['ok' => true, 'mensaje' => 'Horas extra validadas y aprobadas correctamente.'];
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            error_log('Error al aprobar horas extra: ' . $exception->getMessage());
            return ['ok' => false, 'mensaje' => 'No fue posible guardar las horas extra y su auditoría.'];
        }
    }
}

if (!function_exists('asistencia_gestion_resolver_ausencia_esperada')) {
    function asistencia_gestion_resolver_ausencia_esperada(int $idJornada, string $resultado, string $motivo): array
    {
        $resultado = strtoupper(trim($resultado));
        $motivo = trim($motivo);
        $opciones = [
            'JUSTIFICADA' => [
                'estado_jornada' => 'OBSERVADA',
                'texto' => 'Ausencia esperada justificada',
            ],
            'NO_JUSTIFICADA' => [
                'estado_jornada' => 'INCOMPLETA',
                'texto' => 'Ausencia esperada no justificada',
            ],
            'PENDIENTE_REVISION' => [
                'estado_jornada' => 'PENDIENTE_REVISION',
                'texto' => 'Ausencia esperada pendiente de revision',
            ],
        ];

        if (!isset($opciones[$resultado])) {
            return ['ok' => false, 'mensaje' => 'Resultado de ausencia no permitido.'];
        }

        if ($motivo === '') {
            return ['ok' => false, 'mensaje' => 'Debe indicar un motivo para resolver la ausencia.'];
        }

        $jornada = asistencia_jornada_por_id($idJornada);

        if ($jornada === null) {
            return ['ok' => false, 'mensaje' => 'Jornada no encontrada.'];
        }

        if ((int) ($jornada['flag_ausencia_esperada'] ?? 0) !== 1) {
            return ['ok' => false, 'mensaje' => 'La jornada no corresponde a una ausencia esperada.'];
        }

        if ((string) $jornada['estado_jornada'] === 'ANULADA') {
            return ['ok' => false, 'mensaje' => 'No se puede resolver una jornada anulada.'];
        }

        $observaciones = [];

        if (!empty($jornada['observacion'])) {
            $observaciones[] = trim((string) $jornada['observacion']);
        }

        $observaciones[] = $opciones[$resultado]['texto'] . ': ' . $motivo;
        $observacion = substr(implode(' ', $observaciones), 0, 2000);

        $statement = db()->prepare(
            'UPDATE jornadas_asistencia
             SET estado_jornada = :estado_jornada,
                 horas_calculadas = 0.00,
                 observacion = :observacion
             WHERE id_jornada = :id_jornada'
        );
        $statement->execute([
            'estado_jornada' => $opciones[$resultado]['estado_jornada'],
            'observacion' => $observacion,
            'id_jornada' => $idJornada,
        ]);

        auditoria_registrar('CORREGIR_ASISTENCIA', [
            'tabla_afectada' => 'jornadas_asistencia',
            'id_registro_afectado' => $idJornada,
            'valores_anteriores' => $jornada,
            'valores_nuevos' => [
                'accion' => 'RESOLVER_AUSENCIA_ESPERADA',
                'resultado' => $resultado,
                'estado_jornada' => $opciones[$resultado]['estado_jornada'],
                'observacion' => $motivo,
            ],
        ]);

        return ['ok' => true, 'mensaje' => 'Ausencia esperada actualizada.'];
    }
}

if (!function_exists('asistencia_gestion_registrar_marca_correctiva')) {
    function asistencia_gestion_registrar_marca_correctiva(int $idJornada, string $codigoMarca, string $fechaHora, ?string $observacion): array
    {
        $codigoMarca = strtoupper(trim($codigoMarca));
        $fechaHora = trim($fechaHora);
        $observacion = $observacion !== null ? trim($observacion) : null;
        $observacion = $observacion === '' ? null : substr((string) $observacion, 0, 255);

        if (!asistencia_gestion_fecha_hora_valida($fechaHora)) {
            return ['ok' => false, 'mensaje' => 'Debe indicar fecha y hora valida para la marca.'];
        }

        $jornada = asistencia_jornada_por_id($idJornada);

        if ($jornada === null) {
            return ['ok' => false, 'mensaje' => 'Jornada no encontrada.'];
        }

        $fechaHoraSql = asistencia_gestion_fecha_hora_sql($fechaHora);

        if (substr($fechaHoraSql, 0, 10) !== (string) $jornada['fecha']) {
            return ['ok' => false, 'mensaje' => 'La marca correctiva debe pertenecer a la fecha de la jornada.'];
        }

        // Validar que el código sea un tipo de marca reconocido (sin validar secuencia,
        // ya que el admin tiene autoridad para insertar cualquier marca como corrección).
        $tipoMarca = asistencia_tipo_marca_por_codigo($codigoMarca);

        if ($tipoMarca === null) {
            return ['ok' => false, 'mensaje' => 'Tipo de marca no reconocido.'];
        }

        $horario = asistencia_horario_vigente_dia((int) $jornada['id_practica'], (string) $jornada['fecha']);

        try {
            db()->beginTransaction();

            // Agregué el mismo bloqueo padre que usa el marcaje y la revisión,
            // conservando la facultad de corrección manual sin competir con ella.
            $lock = db()->prepare('SELECT * FROM jornadas_asistencia WHERE id_jornada = :id_jornada FOR UPDATE');
            $lock->execute(['id_jornada' => $idJornada]);
            $jornadaActual = $lock->fetch();
            if (!is_array($jornadaActual) || $jornadaActual['estado_jornada'] === 'ANULADA'
                || !asistencia_gestion_puede_operar_jornada($jornadaActual)) {
                db()->rollBack();
                return ['ok' => false, 'mensaje' => 'No puedes corregir esta jornada.'];
            }

            $statement = db()->prepare(
                'INSERT INTO marcas_asistencia (
                    id_jornada,
                    id_tipo_marca,
                    fecha_hora,
                    observacion,
                    creada_por
                 ) VALUES (
                    :id_jornada,
                    :id_tipo_marca,
                    :fecha_hora,
                    :observacion,
                    :creada_por
                 )'
            );
            $statement->execute([
                'id_jornada'    => $idJornada,
                'id_tipo_marca' => (int) $tipoMarca['id_tipo_marca'],
                'fecha_hora'    => $fechaHoraSql,
                'observacion'   => $observacion,
                'creada_por'    => auth_usuario_actual_id(),
            ]);

            $idMarca = (int) db()->lastInsertId();
            asistencia_recalcular_jornada($idJornada, $horario);
            // Agregué auditoría obligatoria antes del commit de la corrección.
            if (!auditoria_registrar('CORREGIR_ASISTENCIA', [
                'tabla_afectada' => 'marcas_asistencia',
                'id_registro_afectado' => $idMarca,
                'valores_anteriores' => ['jornada' => $jornadaActual],
                'valores_nuevos' => [
                    'id_jornada' => $idJornada, 'codigo_marca' => $codigoMarca,
                    'fecha_hora' => $fechaHoraSql, 'observacion' => $observacion,
                    'jornada' => asistencia_jornada_por_id($idJornada),
                ],
            ])) {
                throw new RuntimeException('No se pudo auditar la marca correctiva.');
            }
            db()->commit();
        } catch (Throwable $exception) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            error_log('Error al registrar marca correctiva: ' . $exception->getMessage());
            return ['ok' => false, 'mensaje' => 'No fue posible registrar la marca.'];
        }

        return ['ok' => true, 'mensaje' => 'Marca correctiva registrada.'];
    }
}


if (!function_exists('asistencia_gestion_anular_jornada')) {
    function asistencia_gestion_anular_jornada(int $idJornada, string $motivo): array
    {
        $motivo = trim($motivo);

        // Agregué la restricción ADMIN en el servicio, además del formulario.
        if (!auth_check() || auth_rol_activo() !== 'ADMIN') {
            return ['ok' => false, 'mensaje' => 'Solo ADMIN puede anular una jornada completa.'];
        }

        if ($motivo === '') {
            return ['ok' => false, 'mensaje' => 'Debe indicar un motivo de anulacion.'];
        }

        $jornada = asistencia_jornada_por_id($idJornada);

        if ($jornada === null) {
            return ['ok' => false, 'mensaje' => 'Jornada no encontrada.'];
        }

        if ((string) $jornada['estado_jornada'] === 'ANULADA') {
            return ['ok' => false, 'mensaje' => 'La jornada ya se encuentra anulada.'];
        }

        $actor = auth_usuario_actual_id();

        try {
            db()->beginTransaction();

            // Agregué bloqueo compartido con marcajes/revisiones y relectura
            // del estado para evitar anular mientras otra operación lo modifica.
            $lock = db()->prepare('SELECT * FROM jornadas_asistencia WHERE id_jornada = :id_jornada FOR UPDATE');
            $lock->execute(['id_jornada' => $idJornada]);
            $jornada = $lock->fetch();
            if (!is_array($jornada) || $jornada['estado_jornada'] === 'ANULADA') {
                db()->rollBack();
                return ['ok' => false, 'mensaje' => 'La jornada no existe o ya fue anulada.'];
            }
            $marcas = asistencia_gestion_marcas_jornada($idJornada);

            $updateMarcas = db()->prepare(
                'UPDATE marcas_asistencia
                 SET activo = 0,
                     anulada = 1,
                     motivo_anulacion = :motivo_anulacion,
                     anulada_por = :anulada_por,
                     fecha_anulacion = NOW()
                 WHERE id_jornada = :id_jornada
                   AND activo = 1
                   AND anulada = 0'
            );
            $updateMarcas->execute([
                'motivo_anulacion' => $motivo,
                'anulada_por' => $actor,
                'id_jornada' => $idJornada,
            ]);

            $updateJornada = db()->prepare(
                'UPDATE jornadas_asistencia
                 SET estado_jornada = "ANULADA",
                     hora_entrada = NULL,
                     hora_salida = NULL,
                     horas_calculadas = 0.00,
                     observacion = :observacion,
                     flag_atraso = 0,
                     flag_salida_anticipada = 0,
                     flag_asistencia_extraordinaria = 0,
                     flag_horas_extra = 0
                 WHERE id_jornada = :id_jornada'
            );
            $updateJornada->execute([
                'observacion' => $motivo,
                'id_jornada' => $idJornada,
            ]);
            // Una anulación de la fuente retira también sus canjes dependientes dentro de la misma transacción.
            foreach(tiempo_reabrir_por_marcas(asistencia_jornada_por_id($idJornada)) as $idDependiente) {
                if($idDependiente!==$idJornada){asistencia_recalcular_jornada($idDependiente,null,true);}
            }

            // Moví la auditoría dentro de la transacción de anulación completa.
            if (!auditoria_registrar('ANULAR_ASISTENCIA', [
                'tabla_afectada' => 'jornadas_asistencia',
                'id_registro_afectado' => $idJornada,
                'valores_anteriores' => [
                    'jornada' => $jornada,
                    'marcas' => $marcas,
                ],
                'valores_nuevos' => [
                    'estado_jornada' => 'ANULADA',
                    'motivo' => $motivo,
                    'anulada_por' => $actor,
                ],
            ])) {
                throw new RuntimeException('No se pudo auditar la anulación de jornada.');
            }
            db()->commit();

            return ['ok' => true, 'mensaje' => 'Jornada anulada correctamente.'];
        } catch (Throwable $exception) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }

            error_log('Error al anular jornada de asistencia: ' . $exception->getMessage());

            return ['ok' => false, 'mensaje' => 'No fue posible anular la jornada.'];
        }
    }
}

if (!function_exists('asistencia_gestion_token')) {
    function asistencia_gestion_token(): string
    {
        start_secure_session();

        if (!isset($_SESSION['csrf_asistencia_gestion'])) {
            $_SESSION['csrf_asistencia_gestion'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['csrf_asistencia_gestion'];
    }
}

// Trasladé el procesamiento HTTP de asistencia_gestion_procesar_post al controlador compartido.
// Trasladé asistencia_gestion_render_page a la capa de presentación en fase 4.
