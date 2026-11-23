<?php

declare(strict_types=1);

// Incluí REVISADA en los acumulados para conservar las horas y extras históricas aprobadas.

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once SERVICES_PATH . '/ReglasAsistenciaService.php';
require_once SERVICES_PATH . '/AsistenciaService.php';
require_once SERVICES_PATH . '/MailService.php';

/*
|--------------------------------------------------------------------------
| Marcar asistencia - PRACTICANTE
|--------------------------------------------------------------------------
|
| Pantalla de registro diario para el practicante. La secuencia de marcas,
| creacion de jornada y recalculo de estado se delegan a las reglas de negocio
| centralizadas en Services/ReglasAsistenciaService.php (ruta actualizada en fase 4).
|
*/

auth_requiere_rol('PRACTICANTE');

if (!function_exists('marcar_asistencia_practicante_id')) {
    function marcar_asistencia_practicante_id(): ?int
    {
        $idPracticante = auth_practicante_actual_id();

        return $idPracticante !== null && $idPracticante > 0 ? $idPracticante : null;
    }
}

if (!function_exists('marcar_asistencia_practica_activa')) {
    function marcar_asistencia_practica_activa(int $idPracticante): ?array
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
               AND ep.nombre IN ("PENDIENTE_INICIO", "EN_PROGRESO")
             ORDER BY pr.fecha_inicio_practica DESC, pr.id_practica DESC
             LIMIT 1'
        );
        $statement->execute(['id_practicante' => $idPracticante]);

        $practica = $statement->fetch();

        return is_array($practica) ? $practica : null;
    }
}

if (!function_exists('marcar_asistencia_jornada_hoy')) {
    function marcar_asistencia_jornada_hoy(int $idPractica, string $fecha): ?array
    {
        $statement = db()->prepare(
            'SELECT *
             FROM jornadas_asistencia
             WHERE id_practica = :id_practica
               AND fecha = :fecha
             LIMIT 1'
        );
        $statement->execute([
            'id_practica' => $idPractica,
            'fecha' => $fecha,
        ]);

        $jornada = $statement->fetch();

        return is_array($jornada) ? $jornada : null;
    }
}

if (!function_exists('marcar_asistencia_tipos_marca')) {
    function marcar_asistencia_tipos_marca(): array
    {
        $statement = db()->query(
            'SELECT codigo, nombre, orden
             FROM tipos_marca
             WHERE activo = 1
             ORDER BY orden ASC'
        );

        $tipos = [];

        foreach ($statement->fetchAll() as $tipo) {
            $tipos[(string) $tipo['codigo']] = $tipo;
        }

        return $tipos;
    }
}

if (!function_exists('marcar_asistencia_nombre_marca')) {
    function marcar_asistencia_nombre_marca(array $tipos, string $codigo): string
    {
        return isset($tipos[$codigo]['nombre']) ? (string) $tipos[$codigo]['nombre'] : $codigo;
    }
}

if (!function_exists('marcar_asistencia_practica_en_fecha')) {
    function marcar_asistencia_practica_en_fecha(array $practica, string $fecha): bool
    {
        $inicio = (string) $practica['fecha_inicio_practica'];
        $termino = $practica['fecha_termino_practica'] ?? null;

        if ($inicio !== '' && $fecha < $inicio) {
            return false;
        }

        return $termino === null || $termino === '' || $fecha <= (string) $termino;
    }
}

if (!function_exists('marcar_asistencia_formatear_fecha_hora')) {
    function marcar_asistencia_formatear_fecha_hora(?string $fechaHora): string
    {
        if ($fechaHora === null || $fechaHora === '') {
            return 'No registrada';
        }

        $timestamp = strtotime($fechaHora);

        return $timestamp === false ? $fechaHora : date('d-m-Y H:i', $timestamp);
    }
}

if (!function_exists('marcar_asistencia_formatear_hora')) {
    function marcar_asistencia_formatear_hora(?string $hora): string
    {
        if ($hora === null || $hora === '') {
            return 'No definida';
        }

        $timestamp = strtotime($hora);

        return $timestamp === false ? $hora : date('H:i', $timestamp);
    }
}

if (!function_exists('marcar_asistencia_formatear_fecha')) {
    function marcar_asistencia_formatear_fecha(?string $fecha): string
    {
        if ($fecha === null || $fecha === '') {
            return 'No registrada';
        }

        $timestamp = strtotime($fecha);

        return $timestamp === false ? $fecha : date('d-m-Y', $timestamp);
    }
}

if (!function_exists('marcar_asistencia_fecha_larga')) {
    function marcar_asistencia_fecha_larga(string $fecha): string
    {
        $timestamp = strtotime($fecha) ?: time();
        $dias = ['domingo', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'];
        $meses = [
            1 => 'enero',
            2 => 'febrero',
            3 => 'marzo',
            4 => 'abril',
            5 => 'mayo',
            6 => 'junio',
            7 => 'julio',
            8 => 'agosto',
            9 => 'septiembre',
            10 => 'octubre',
            11 => 'noviembre',
            12 => 'diciembre',
        ];

        return ucfirst($dias[(int) date('w', $timestamp)])
            . ', '
            . date('j', $timestamp)
            . ' de '
            . $meses[(int) date('n', $timestamp)]
            . ' de '
            . date('Y', $timestamp);
    }
}

if (!function_exists('marcar_asistencia_hora_corta_fecha_hora')) {
    function marcar_asistencia_hora_corta_fecha_hora(?string $fechaHora): string
    {
        if ($fechaHora === null || $fechaHora === '') {
            return '--:--';
        }

        $timestamp = strtotime($fechaHora);

        return $timestamp === false ? $fechaHora : date('H:i', $timestamp);
    }
}

if (!function_exists('marcar_asistencia_resumen_practica')) {
    function marcar_asistencia_resumen_practica(int $idPractica, int $horasRequeridas): array
    {
        $statement = db()->prepare(
            'SELECT
                /* Leí las horas previas y las jornadas en una misma consulta para evitar reutilizar un valor anterior al POST. */
                COALESCE(SUM(CASE 
                    WHEN ja.estado_jornada IN ("VALIDA", "OBSERVADA", "CORREGIDA", "REVISADA")
                    THEN (COALESCE(ja.horas_calculadas, 0) + COALESCE(ja.horas_extra_aprobadas, 0))
                    ELSE 0 
                END), 0) AS horas_realizadas,
                COUNT(ja.id_jornada) AS jornadas_total,
                COALESCE(SUM(CASE WHEN ja.estado_jornada IN ("VALIDA", "OBSERVADA", "CORREGIDA", "REVISADA") THEN 1 ELSE 0 END), 0) AS jornadas_sumables,
                COALESCE(SUM(CASE WHEN ja.fecha >= DATE_FORMAT(CURDATE(), "%Y-%m-01") THEN 1 ELSE 0 END), 0) AS jornadas_mes,
                COALESCE(SUM(CASE WHEN ja.fecha >= DATE_FORMAT(CURDATE(), "%Y-%m-01") AND ja.estado_jornada IN ("VALIDA", "OBSERVADA", "CORREGIDA", "REVISADA") THEN 1 ELSE 0 END), 0) AS jornadas_mes_sumables,
                COALESCE(p.horas_previas, 0) AS horas_previas
             FROM practicas p
             LEFT JOIN jornadas_asistencia ja ON ja.id_practica = p.id_practica
             WHERE p.id_practica = :id_practica
             GROUP BY p.horas_previas'
        );
        $statement->execute(['id_practica' => $idPractica]);
        $resumen = $statement->fetch() ?: [];
        $horasRealizadas = round(
            (float) ($resumen['horas_realizadas'] ?? 0) + (float) ($resumen['horas_previas'] ?? 0),
            2
        );
        $porcentaje = $horasRequeridas > 0 ? min(100.0, round(($horasRealizadas / $horasRequeridas) * 100, 1)) : 0.0;
        $jornadasMes = (int) ($resumen['jornadas_mes'] ?? 0);
        $jornadasMesSumables = (int) ($resumen['jornadas_mes_sumables'] ?? 0);

        return [
            'horas_realizadas' => $horasRealizadas,
            'horas_requeridas' => $horasRequeridas,
            'horas_restantes' => max(0.0, round($horasRequeridas - $horasRealizadas, 2)),
            'porcentaje_avance' => $porcentaje,
            'jornadas_total' => (int) ($resumen['jornadas_total'] ?? 0),
            'jornadas_sumables' => (int) ($resumen['jornadas_sumables'] ?? 0),
            'asistencia_mes' => $jornadasMes > 0 ? round(($jornadasMesSumables / $jornadasMes) * 100, 1) : 0.0,
        ];
    }
}

if (!function_exists('marcar_asistencia_historial_reciente')) {
    function marcar_asistencia_historial_reciente(int $idPractica): array
    {
        $statement = db()->prepare(
            'SELECT *
             FROM jornadas_asistencia
             WHERE id_practica = :id_practica
             ORDER BY fecha DESC, id_jornada DESC
             LIMIT 7'
        );
        $statement->execute(['id_practica' => $idPractica]);

        return $statement->fetchAll();
    }
}

if (!function_exists('marcar_asistencia_iniciales')) {
    function marcar_asistencia_iniciales(string $email): string
    {
        $nombre = trim(explode('@', $email)[0] ?? '');

        if ($nombre === '') {
            return 'PR';
        }

        $partes = preg_split('/[^a-z0-9]+/i', $nombre) ?: [];
        $iniciales = '';

        foreach ($partes as $parte) {
            if ($parte !== '') {
                $iniciales .= strtoupper(substr($parte, 0, 1));
            }
        }

        return substr($iniciales !== '' ? $iniciales : 'PR', 0, 2);
    }
}

if (!function_exists('marcar_asistencia_estado_visual')) {
    function marcar_asistencia_estado_visual(?string $estado, bool $permitidasDisponibles): array
    {
        if ($estado === null) {
            return [
                'label' => $permitidasDisponibles ? 'Pendiente de entrada' : 'Sin jornada',
                'class' => 'state-pending',
                'icon' => 'bi-hourglass-split',
            ];
        }

        return match ($estado) {
            'VALIDA', 'CORREGIDA', 'REVISADA' => ['label' => 'Completa', 'class' => 'state-ok', 'icon' => 'bi-check-circle-fill'],
            'OBSERVADA' => ['label' => 'Con observacion', 'class' => 'state-warning', 'icon' => 'bi-exclamation-triangle-fill'],
            'PENDIENTE_REVISION' => ['label' => 'Pendiente de revision', 'class' => 'state-info', 'icon' => 'bi-info-circle-fill'],
            'ANULADA' => ['label' => 'Anulada', 'class' => 'state-muted', 'icon' => 'bi-slash-circle'],
            default => ['label' => 'En curso', 'class' => 'state-pending', 'icon' => 'bi-hourglass-split'],
        };
    }
}

if (!function_exists('marcar_asistencia_marca_icono')) {
    function marcar_asistencia_marca_icono(string $codigo): string
    {
        return match ($codigo) {
            'ENTRADA' => 'bi-box-arrow-in-right',
            'ENTRADA_COLACION' => 'bi-cup-hot',
            'SALIDA_COLACION' => 'bi-cup-straw',
            'SALIDA' => 'bi-box-arrow-right',
            default => 'bi-clock',
        };
    }
}

if (!function_exists('marcar_asistencia_marca_accion')) {
    function marcar_asistencia_marca_accion(string $codigo): string
    {
        return match ($codigo) {
            'ENTRADA' => 'Registrar entrada',
            'ENTRADA_COLACION' => 'Registrar salida a colacion',
            'SALIDA_COLACION' => 'Registrar vuelta de colacion',
            'SALIDA' => 'Registrar fin de jornada',
            // Agregué acciones explícitas para salir y regresar de una ausencia parcial.
            'SALIDA_PERMISO' => 'Registrar salida por permiso',
            'REGRESO_PERMISO' => 'Registrar regreso de permiso',
            default => 'Registrar marca',
        };
    }
}

if (!function_exists('marcar_asistencia_dias_restantes')) {
    function marcar_asistencia_dias_restantes(?string $fechaTermino): ?int
    {
        if ($fechaTermino === null || $fechaTermino === '') {
            return null;
        }

        $termino = DateTimeImmutable::createFromFormat('Y-m-d', $fechaTermino);

        if ($termino === false) {
            return null;
        }

        $hoy = new DateTimeImmutable('today');

        return max(0, (int) $hoy->diff($termino)->format('%r%a'));
    }
}

if (!function_exists('marcar_asistencia_enviar_comprobante')) {
    /**
     * Envía el comprobante de una marca recién guardada. Nunca lanza: si el correo
     * falla, la marca ya está confirmada en base de datos y solo se deja registro en el log.
     */
    function marcar_asistencia_enviar_comprobante(int $idPracticante, int $idMarca, string $tipoMarcaTexto, ?string $observacion): void
    {
        try {
            // Leí la hora guardada en la marca para que el comprobante coincida exactamente con el registro.
            $statement = db()->prepare(
                'SELECT p.email, p.nombre_1, p.apellido_paterno, m.fecha_hora
                 FROM practicantes p
                 INNER JOIN marcas_asistencia m ON m.id_marca = :id_marca
                 WHERE p.id_practicante = :id_practicante
                 LIMIT 1'
            );
            $statement->execute(['id_marca' => $idMarca, 'id_practicante' => $idPracticante]);
            $datos = $statement->fetch();

            if (!is_array($datos) || !filter_var((string) $datos['email'], FILTER_VALIDATE_EMAIL)) {
                error_log('Comprobante de marca ' . $idMarca . ' no enviado: practicante sin email válido.');
                return;
            }

            $resultado = mailer_enviar_comprobante_marca(
                (string) $datos['email'],
                trim($datos['nombre_1'] . ' ' . $datos['apellido_paterno']),
                $tipoMarcaTexto,
                (string) $datos['fecha_hora'],
                $idMarca,
                [
                    'fecha_larga' => marcar_asistencia_fecha_larga((string) $datos['fecha_hora']),
                    'observacion' => $observacion,
                ]
            );

            if (!$resultado['ok']) {
                error_log('Comprobante de marca ' . $idMarca . ' no enviado: ' . $resultado['error']);
            }
        } catch (Throwable $exception) {
            error_log('Error al preparar comprobante de marca ' . $idMarca . ': ' . $exception->getMessage());
        }
    }
}

$errores = [];
$mensajeExito = null;
// Preparé confirmación del lado servidor para no registrar una marca al primer clic sin JavaScript.
$marcaPorConfirmar = null;
$observacionPorConfirmar = null;
$practica = null;
$jornada = null;
$marcas = [];
$tiposMarca = [];
$permitidas = [];
$horarioHoy = null;
$resumenPractica = null;
$historialReciente = [];
// Soporte de simulación de fecha y hora exclusivo para desarrollo local (APP_DEBUG).
$fechaHoy = (defined('APP_DEBUG') && APP_DEBUG && !empty($_GET['simular_fecha']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $_GET['simular_fecha']))
    ? (string) $_GET['simular_fecha']
    : date('Y-m-d');
$horaHoy = (defined('APP_DEBUG') && APP_DEBUG && !empty($_GET['simular_hora']) && preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', (string) $_GET['simular_hora']))
    ? (strlen((string) $_GET['simular_hora']) === 5 ? (string) $_GET['simular_hora'] . ':00' : (string) $_GET['simular_hora'])
    : date('H:i:s');
$idPracticante = marcar_asistencia_practicante_id();
$puedeMarcar = false;
$mensajeBloqueo = null;
$ventanasMarcas = [];
$avisoAnticipacion = null;
$estadoSoloLectura = ['solo_lectura' => false, 'meta_cumplida' => false, 'practica_egresada' => false, 'motivo' => null, 'practica' => null];

start_secure_session();

if (empty($_SESSION['csrf_marcar_asistencia'])) {
    $_SESSION['csrf_marcar_asistencia'] = bin2hex(random_bytes(32));
}

if ($idPracticante === null) {
    $errores[] = 'Tu cuenta no esta vinculada a un registro de practicante.';
} else {
    try {
        $tiposMarca = marcar_asistencia_tipos_marca();
        $estadoSoloLectura = asistencia_estado_solo_lectura_practicante($idPracticante, $fechaHoy);
        $practica = marcar_asistencia_practica_activa($idPracticante);

        // Si el practicante ya egresó, cargo su última práctica en consulta para conservar el resumen 100%, confeti e historial.
        if ($practica === null && is_array($estadoSoloLectura['practica'] ?? null)) {
            $practica = $estadoSoloLectura['practica'];
        }

        if ($practica === null) {
            $errores[] = 'No existe una practica activa disponible para marcar asistencia.';
        } elseif (!empty($estadoSoloLectura['solo_lectura'])) {
            $puedeMarcar = false;
            $mensajeBloqueo = (string) ($estadoSoloLectura['motivo'] ?? 'Tu perfil está en modo solo lectura.');
            $resumenPractica = marcar_asistencia_resumen_practica((int) $practica['id_practica'], (int) $practica['horas_requeridas']);
            $historialReciente = marcar_asistencia_historial_reciente((int) $practica['id_practica']);
            $horarioHoy = null;
            $jornada = marcar_asistencia_jornada_hoy((int) $practica['id_practica'], $fechaHoy);
            $marcas = $jornada !== null ? asistencia_marcas_vigentes_jornada((int) $jornada['id_jornada']) : [];
            $permitidas = [];
        } elseif (!marcar_asistencia_practica_en_fecha($practica, $fechaHoy)) {
            $errores[] = 'La fecha actual esta fuera del rango de tu practica.';
        } else {
            // Evalué si el día de hoy es laborable (regla de días no laborables y sábados con recuperación).
            $evaluacionDia = asistencia_evaluar_dia_laboral((int) $practica['id_practica'], $fechaHoy);
            $resumenPractica = marcar_asistencia_resumen_practica((int) $practica['id_practica'], (int) $practica['horas_requeridas']);
            $historialReciente = marcar_asistencia_historial_reciente((int) $practica['id_practica']);

            if (!$evaluacionDia['habilitado']) {
                $puedeMarcar = false;
                $mensajeBloqueo = (string) $evaluacionDia['motivo_bloqueo'];
                $horarioHoy = null;
                $jornada = null;
                $marcas = [];
                $permitidas = [];
            } else {
                $puedeMarcar = true;
                $horarioHoy = $evaluacionDia['horario'];
                $jornada = marcar_asistencia_jornada_hoy((int) $practica['id_practica'], $fechaHoy);

                // Procesar marcajes automáticos por inactividad (colación o fin de jornada)
                if ($jornada !== null) {
                    $autoResultado = asistencia_procesar_marcajes_automaticos_jornada(
                        (int) $jornada['id_jornada'],
                        $horarioHoy,
                        $fechaHoy,
                        $horaHoy
                    );
                    if ($autoResultado['procesado']) {
                        $jornada = marcar_asistencia_jornada_hoy((int) $practica['id_practica'], $fechaHoy);
                    }
                }

                $marcas = $jornada !== null ? asistencia_marcas_vigentes_jornada((int) $jornada['id_jornada']) : [];
                $permisoHoy = asistencia_permiso_del_dia((int) $practica['id_practica'], $fechaHoy);
                $permitidas = asistencia_marcas_disponibles($marcas, $permisoHoy);

                // Evaluar ventanas de tolerancia y anticipación por tipo de marca
                foreach (asistencia_tipos_marca() as $tipoCod) {
                    $ventanasMarcas[$tipoCod] = asistencia_evaluar_ventana_marca($tipoCod, $horarioHoy, $fechaHoy, $horaHoy);
                }
                foreach ($permitidas as $sigPermitida) {
                    if (isset($ventanasMarcas[$sigPermitida]) && !$ventanasMarcas[$sigPermitida]['habilitado'] && $ventanasMarcas[$sigPermitida]['es_anticipado']) {
                        $avisoAnticipacion = $ventanasMarcas[$sigPermitida]['motivo'];
                        break;
                    }
                }
            }
        }
    } catch (Throwable $exception) {
        error_log('Error al cargar asistencia del practicante: ' . $exception->getMessage());
        $errores[] = 'No fue posible cargar tu asistencia. Verifica que la base de datos este importada.';
    }
}

if (is_post() && $practica !== null) {
    // Revalidé modo solo lectura y día habilitado en servidor antes de procesar cualquier marca enviada.
    $estadoSoloLecturaPost = $idPracticante !== null
        ? asistencia_estado_solo_lectura_practicante($idPracticante, $fechaHoy)
        : $estadoSoloLectura;
    $evaluacionDia = asistencia_evaluar_dia_laboral((int) $practica['id_practica'], $fechaHoy);
    if (!empty($estadoSoloLecturaPost['solo_lectura'])) {
        $estadoSoloLectura = $estadoSoloLecturaPost;
        $puedeMarcar = false;
        $mensajeBloqueo = (string) ($estadoSoloLectura['motivo'] ?? 'Tu perfil está en modo solo lectura.');
        $errores[] = $mensajeBloqueo;
    } elseif (!$evaluacionDia['habilitado']) {
        $errores[] = (string) $evaluacionDia['motivo_bloqueo'];
    } elseif (!hash_equals((string) $_SESSION['csrf_marcar_asistencia'], (string) input('csrf_token', ''))) {
        $errores[] = 'La sesion del formulario expiro. Intenta nuevamente.';
    } else {
        $codigoMarca = strtoupper(trim((string) input('codigo_marca', '')));
        $observacion = trim((string) input('observacion', ''));
        $observacion = $observacion === '' ? null : substr($observacion, 0, 255);

        // Evaluar ventana horaria de anticipación para la marca solicitada
        $evaluacionVentanaPost = asistencia_evaluar_ventana_marca($codigoMarca, $horarioHoy, $fechaHoy, $horaHoy);

        if (!in_array($codigoMarca, asistencia_tipos_marca(), true)) {
            $errores[] = 'Selecciona un tipo de marca valido.';
        } elseif (!in_array($codigoMarca, $permitidas, true)) {
            // Volví a comprobar la secuencia vigente antes de confirmar o escribir.
            $errores[] = 'Esta marca no está habilitada en la secuencia actual. Actualiza la página.';
        } elseif (!$evaluacionVentanaPost['habilitado']) {
            $errores[] = $evaluacionVentanaPost['motivo'] ?? 'Esta marca no está habilitada para tu horario actual.';
        } elseif ((string) input('confirmar_marca', '') !== '1') {
            $marcaPorConfirmar = $codigoMarca;
            $observacionPorConfirmar = $observacion;
        } else {
            try {
                $jornada = asistencia_obtener_o_crear_jornada(
                    (int) $practica['id_practica'],
                    $fechaHoy,
                    auth_usuario_actual_id()
                );

                $timestampMarca = (defined('APP_DEBUG') && APP_DEBUG && !empty($_GET['simular_hora']))
                    ? "$fechaHoy $horaHoy"
                    : null;

                $resultado = asistencia_registrar_marca(
                    (int) $jornada['id_jornada'],
                    $codigoMarca,
                    $timestampMarca,
                    $observacion,
                    auth_usuario_actual_id(),
                    $horarioHoy
                );

                if ((bool) ($resultado['ok'] ?? false)) {
                    $mensajeExito = (string) $resultado['mensaje'];
                    $_SESSION['csrf_marcar_asistencia'] = bin2hex(random_bytes(32));
                    // Comprobante por correo después del commit: un fallo SMTP no revierte ni bloquea la marca.
                    marcar_asistencia_enviar_comprobante(
                        $idPracticante,
                        (int) ($resultado['id_marca'] ?? 0),
                        marcar_asistencia_nombre_marca($tiposMarca, $codigoMarca),
                        $observacion
                    );
                } else {
                    $errores[] = (string) ($resultado['mensaje'] ?? 'No fue posible registrar la marca.');
                }

                $jornada = marcar_asistencia_jornada_hoy((int) $practica['id_practica'], $fechaHoy);
                $marcas = $jornada !== null ? asistencia_marcas_vigentes_jornada((int) $jornada['id_jornada']) : [];
                $permisoHoy = asistencia_permiso_del_dia((int) $practica['id_practica'], $fechaHoy);
                $permitidas = asistencia_marcas_disponibles($marcas, $permisoHoy);

                // Recalcular ventanas horarias tras nuevo registro
                foreach (asistencia_tipos_marca() as $tipoCod) {
                    $ventanasMarcas[$tipoCod] = asistencia_evaluar_ventana_marca($tipoCod, $horarioHoy, $fechaHoy, $horaHoy);
                }
                $avisoAnticipacion = null;
                foreach ($permitidas as $sigPermitida) {
                    if (isset($ventanasMarcas[$sigPermitida]) && !$ventanasMarcas[$sigPermitida]['habilitado'] && $ventanasMarcas[$sigPermitida]['es_anticipado']) {
                        $avisoAnticipacion = $ventanasMarcas[$sigPermitida]['motivo'];
                        break;
                    }
                }

                // Recalculé desde la base tras guardar; el resumen vuelve a incluir horas previas desde su lectura autoritativa.
                $resumenPractica = marcar_asistencia_resumen_practica((int) $practica['id_practica'], (int) $practica['horas_requeridas']);
                $historialReciente = marcar_asistencia_historial_reciente((int) $practica['id_practica']);

                // Si esta marca completó el 100% de las horas, paso la pantalla a modo solo lectura conservando el confeti.
                if ($idPracticante !== null) {
                    $estadoSoloLectura = asistencia_estado_solo_lectura_practicante($idPracticante, $fechaHoy);
                    if (!empty($estadoSoloLectura['solo_lectura'])) {
                        $puedeMarcar = false;
                        $mensajeBloqueo = (string) ($estadoSoloLectura['motivo'] ?? 'Tu perfil está en modo solo lectura.');
                        $permitidas = [];
                    }
                }
            } catch (Throwable $exception) {
                error_log('Error al registrar asistencia del practicante: ' . $exception->getMessage());
                $errores[] = 'No fue posible registrar la marca. Intenta nuevamente.';
            }
        }
    }
}

$emailPracticante = (string) session_get('email', '');
$fechaLarga = marcar_asistencia_fecha_larga($fechaHoy);
if (!empty($estadoSoloLectura['solo_lectura'])) {
    $estadoVisual = [
        'label' => !empty($estadoSoloLectura['meta_cumplida']) ? 'Práctica completada' : 'Práctica finalizada',
        'class' => 'state-ok',
        'icon' => 'bi-patch-check-fill',
    ];
} else {
    $estadoVisual = marcar_asistencia_estado_visual($jornada['estado_jornada'] ?? null, $permitidas !== []);
}
$diasRestantes = $practica !== null ? marcar_asistencia_dias_restantes($practica['fecha_termino_practica'] ?? null) : null;
$horasRealizadas = $resumenPractica !== null ? (float) $resumenPractica['horas_realizadas'] : 0.0;
$horasRequeridas = $resumenPractica !== null ? (float) $resumenPractica['horas_requeridas'] : (float) ($practica['horas_requeridas'] ?? 0);
$porcentajeAvance = $resumenPractica !== null ? (float) $resumenPractica['porcentaje_avance'] : 0.0;
$avisosSistema = [];
$recuperacionesHoy = [];

// Expliqué los tramos autorizados antes del marcaje; marcar hechos no valida horas de recuperación.
if ($practica !== null) {
    // Reutilicé el plan diario para mostrar el ajuste temporal sin convertirlo en horas ordinarias.
    $recuperacionesHoy = recuperaciones_del_dia((int)$practica['id_practica'],$fechaHoy);
    foreach ($recuperacionesHoy as $tramoRecuperacion) {
        $avisosSistema[]=[
            'icon'=>'bi-info-circle-fill','class'=>'notice-info','title'=>'Recuperación autorizada: '.$tramoRecuperacion['tipo'],
            'text'=>substr($tramoRecuperacion['hora_inicio'],0,5).'–'.substr($tramoRecuperacion['hora_fin'],0,5)
                .($tramoRecuperacion['tipo']==='COLACION'?'. Si trabajas este tramo, no marques inicio/fin de colación durante él.':'. Registra entrada y salida.')
                .' El supervisor validará el tiempo efectivamente trabajado.',
        ];
    }
}

// Expliqué el permiso diario y su estado; mostrar la pausa no representa aprobación del supervisor.
if (!empty($permisoHoy)) {
    $avisosSistema[] = [
        'icon' => 'bi-info-circle-fill', 'class' => 'notice-info', 'title' => 'Permiso del día',
        'text' => 'Estado: ' . $permisoHoy['estado'] . '. '
            . (($permisoHoy['tipo_ausencia'] ?? '') === 'PARCIAL'
                ? 'Ausencia solicitada de ' . substr($permisoHoy['hora_inicio_ausencia'], 0, 5) . ' a ' . substr($permisoHoy['hora_fin_ausencia'], 0, 5) . '. Usa las marcas de permiso si sales y regresas; conserva la colación por separado.'
                : 'Ausencia de jornada completa.'),
    ];
}

if ($jornada !== null && !empty($jornada['observacion'])) {
    $avisosSistema[] = [
        'icon' => 'bi-info-circle-fill',
        'class' => 'notice-info',
        'title' => 'Observacion de jornada',
        'text' => (string) $jornada['observacion'],
    ];
}

if ($jornada !== null && (int) ($jornada['flag_atraso'] ?? 0) === 1) {
    $avisosSistema[] = [
        'icon' => 'bi-exclamation-triangle-fill',
        'class' => 'notice-warning',
        'title' => 'Validacion por horario',
        'text' => 'Tu entrada fue registrada con atraso respecto del horario pactado.',
    ];
}

if ($jornada !== null && (int) ($jornada['flag_salida_anticipada'] ?? 0) === 1) {
    $avisosSistema[] = [
        'icon' => 'bi-exclamation-triangle-fill',
        'class' => 'notice-warning',
        'title' => 'Salida anticipada',
        'text' => 'Tu salida quedo antes de la hora esperada y puede requerir revision.',
    ];
}

if ($jornada !== null && (int) ($jornada['flag_horas_extra'] ?? 0) === 1) {
    $avisosSistema[] = [
        'icon' => 'bi-clock-history',
        'class' => 'notice-info',
        'title' => 'Horas sobre lo planificado',
        'text' => 'Las horas sobre el horario pactado quedan sujetas a revision.',
    ];
}

if ($avisosSistema === []) {
    $avisosSistema[] = [
        'icon' => 'bi-shield-check',
        'class' => 'notice-ok',
        'title' => 'Registro seguro',
        'text' => $permitidas === [] && $jornada !== null ? 'La jornada no tiene marcas pendientes.' : 'Completa las marcas en orden para mantener tu registro al dia.',
    ];
}
$pageTitle = 'Marcar asistencia';
// Versioné la hoja visual por contenido para que cambios de tema no queden tapados por caché del navegador.
$estiloAsistencia = ASSETS_PATH . '/css/practicante-asistencia.css';
$versionEstiloAsistencia = is_file($estiloAsistencia) && is_readable($estiloAsistencia)
    ? hash_file('sha256', $estiloAsistencia)
    : false;
$extraStyles = [asset('css/practicante-asistencia.css') . ($versionEstiloAsistencia !== false ? '?v=' . substr($versionEstiloAsistencia, 0, 16) : '')];
// Preparé los datos del layout antes de mostrar la vista.
require SRC_PATH . '/Controllers/Shared/LayoutContext.php';

require_once VIEWS_PATH . '/practicante/marcar-asistencia.view.php';
