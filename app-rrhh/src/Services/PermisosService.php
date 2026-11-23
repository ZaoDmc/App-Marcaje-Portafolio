<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/AuthService.php';
require_once __DIR__ . '/ReglasAsistenciaService.php';
require_once __DIR__ . '/ExcepcionesHorarioService.php';
// Agregué el plan de recuperación por tramos, separado del tiempo excepcional.
require_once __DIR__ . '/RecuperacionesService.php';
// Reutilizo horarios_practicante_dias() para mostrar el día de la semana en español en el listado.
require_once __DIR__ . '/HorariosPracticanteService.php';

/*
|--------------------------------------------------------------------------
| Gestión de Permisos y Recuperación de Horas
|--------------------------------------------------------------------------
|
| Permite solicitar ausencias pasadas, actuales o futuras dentro de la práctica
| con justificación breve (máximo 40 palabras) y propuesta de fecha/horario de
| recuperación (con soporte específico para días Sábado entre 10:00 y 20:00).
|
| Soporta permisos de JORNADA COMPLETA o PARCIAL (por horas), donde:
| - Si es parcial, al aprobar se crea CAMBIO_HORARIO en la fecha de ausencia
|   con el horario restante que debe cumplir el practicante.
| - Si es completa, al aprobar se crea AUSENCIA_JUSTIFICADA.
| - En ambos casos se crea ASISTENCIA_EXTRAORDINARIA para la fecha de recuperación.
|
*/

if (!function_exists('permisos_gestion_contar_palabras')) {
    /**
     * Cuenta palabras de forma precisa soportando caracteres en español y signos de puntuación.
     *
     * @param string $texto Texto a evaluar.
     * @return int Cantidad de palabras.
     */
    function permisos_gestion_contar_palabras(string $texto): int
    {
        $limpio = trim(preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $texto) ?? '');
        if ($limpio === '') {
            return 0;
        }

        $palabras = preg_split('/\s+/u', $limpio);

        return is_array($palabras) ? count($palabras) : 0;
    }
}

// Agregué validación estricta para no normalizar fechas inexistentes ni horas fuera de rango.
if (!function_exists('permisos_gestion_fecha_valida')) {
    function permisos_gestion_fecha_valida(string $fecha): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        return $parsed !== false && $parsed->format('Y-m-d') === $fecha;
    }
}
if (!function_exists('permisos_gestion_hora_valida')) {
    function permisos_gestion_hora_valida(string $hora): bool
    {
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $hora) === 1;
    }
}

// Calculé el solapamiento exacto con colación, incluso cuando solo una parte cae dentro del tramo.
if (!function_exists('permisos_gestion_minutos_colacion')) {
    function permisos_gestion_minutos_colacion(int $inicio, int $fin, ?string $colInicio, ?string $colFin): int
    {
        if ($colInicio === null || $colFin === null) { return 0; }
        $toMin = static fn(string $hora): int => (int) substr($hora, 0, 2) * 60 + (int) substr($hora, 3, 2);
        return max(0, min($fin, $toMin($colFin)) - max($inicio, $toMin($colInicio)));
    }
}

if (!function_exists('permisos_gestion_es_sabado')) {
    /**
     * Determina si una fecha corresponde a día Sábado (ISO-8601 = 6).
     *
     * @param string $fecha Fecha YYYY-MM-DD.
     * @return bool True si es sábado.
     */
    function permisos_gestion_es_sabado(string $fecha): bool
    {
        $timestamp = strtotime($fecha);
        if ($timestamp === false) {
            return false;
        }

        return (int) date('N', $timestamp) === 6;
    }
}

if (!function_exists('permisos_gestion_practica_activa')) {
    /**
     * Obtiene la práctica activa actual vinculada a un practicante.
     * Soporta los estados vigentes del sistema: EN_PROGRESO, PENDIENTE_INICIO, PAUSADA.
     *
     * @param int $idPracticante ID del practicante.
     * @return array|null Fila de práctica activa o null.
     */
    function permisos_gestion_practica_activa(int $idPracticante): ?array
    {
        $statement = db()->prepare(
            'SELECT pr.*, tp.nombre AS tipo_practica_nombre, ep.nombre AS estado_practica_nombre
             FROM practicas pr
             LEFT JOIN tipos_practica tp ON tp.id_tipo_practica = pr.id_tipo_practica
             INNER JOIN estados_practica ep ON ep.id_estado_practica = pr.id_estado_practica
             WHERE pr.id_practicante = :id_practicante
               AND pr.activo = 1
               AND ep.nombre IN ("EN_PROGRESO", "PENDIENTE_INICIO", "PAUSADA", "ACTIVA")
             ORDER BY pr.fecha_inicio_practica DESC, pr.id_practica DESC
             LIMIT 1'
        );
        $statement->execute(['id_practicante' => $idPracticante]);
        $fila = $statement->fetch();

        return is_array($fila) ? $fila : null;
    }
}

if (!function_exists('permisos_gestion_horario_base_vigente')) {
    /**
     * Obtiene el registro de horario pactado vigente para una práctica.
     *
     * @param int $idPractica ID de la práctica.
     * @param string|null $fecha Fecha de referencia (por defecto hoy).
     * @return array|null
     */
    function permisos_gestion_horario_base_vigente(int $idPractica, ?string $fecha = null): ?array
    {
        $fecha = $fecha ?? date('Y-m-d');
        $statement = db()->prepare(
            'SELECT hp.*
             FROM horarios_practicante hp
             WHERE hp.id_practica = :id_practica
               AND hp.activo = 1
               AND hp.fecha_inicio <= :fecha_ini
               AND (hp.fecha_fin IS NULL OR hp.fecha_fin >= :fecha_fin)
             ORDER BY hp.fecha_inicio DESC, hp.id_horario DESC
             LIMIT 1'
        );
        $statement->execute([
            'id_practica' => $idPractica,
            'fecha_ini'   => $fecha,
            'fecha_fin'   => $fecha,
        ]);
        $fila = $statement->fetch();

        return is_array($fila) ? $fila : null;
    }
}

if (!function_exists('permisos_gestion_horario_del_dia')) {
    /**
     * Obtiene el horario planificado de un día específico para una práctica.
     * Retorna null si ese día no hay jornada pactada.
     *
     * @param int $idPractica ID de la práctica.
     * @param string $fecha Fecha YYYY-MM-DD.
     * @return array{entrada: string, salida: string, colacion_inicio: string|null, colacion_fin: string|null, horas_planificadas: float}|null
     */
    function permisos_gestion_horario_del_dia(int $idPractica, string $fecha): ?array
    {
        $horario = asistencia_horario_vigente_dia($idPractica, $fecha);

        if ($horario === null || empty($horario['entrada_esperada']) || empty($horario['salida_esperada'])) {
            return null;
        }

        $colInicio = !empty($horario['colacion_inicio']) ? substr((string) $horario['colacion_inicio'], 0, 5) : null;
        $colFin    = !empty($horario['colacion_fin'])    ? substr((string) $horario['colacion_fin'],    0, 5) : null;

        $horasPlan = (float) ($horario['horas_planificadas'] ?? 0);
        if ($horasPlan <= 0) {
            $minEntrada = (int) strtotime($fecha . ' ' . $horario['entrada_esperada']);
            $minSalida  = (int) strtotime($fecha . ' ' . $horario['salida_esperada']);
            $minCol     = 0;
            if ($colInicio !== null && $colFin !== null) {
                $minCol = abs((int) strtotime($fecha . ' ' . $colFin) - (int) strtotime($fecha . ' ' . $colInicio));
            }
            $horasPlan = max(0.0, round(($minSalida - $minEntrada - $minCol) / 3600, 2));
        }

        return [
            'entrada'          => substr((string) $horario['entrada_esperada'], 0, 5),
            'salida'           => substr((string) $horario['salida_esperada'],  0, 5),
            'colacion_inicio'  => $colInicio,
            'colacion_fin'     => $colFin,
            'horas_planificadas' => $horasPlan,
        ];
    }
}

if (!function_exists('permisos_gestion_dias_disponibles_ausencia')) {
    /**
     * Obtiene los próximos días laborales desde hoy donde el practicante tiene turno pactado.
     *
     * @param int $idPractica ID de la práctica.
     * @param int $diasAdelante Cantidad de días hacia adelante a proyectar (por defecto 90).
     * @return array<int, array{fecha: string, fecha_formato: string, dia_nombre: string, horas: float, entrada: string, salida: string}>
     */
    function permisos_gestion_dias_disponibles_ausencia(int $idPractica, int $diasAdelante = 90): array
    {
        $diasSemana = [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo',
        ];

        $resultados = [];
        // Incluí hoy según la zona horaria de la aplicación, conservando la exclusión de días pasados.
        $hoy = new DateTimeImmutable('today');

        for ($i = 0; $i < $diasAdelante; $i++) {
            $fechaActual = $hoy->modify("+{$i} days");
            $fechaStr    = $fechaActual->format('Y-m-d');

            $horarioDia = permisos_gestion_horario_del_dia($idPractica, $fechaStr);
            if ($horarioDia !== null) {
                $diaN = (int) $fechaActual->format('N');
                $resultados[] = [
                    'fecha'         => $fechaStr,
                    'fecha_formato' => $fechaActual->format('d-m-Y'),
                    'dia_nombre'    => $diasSemana[$diaN] ?? 'Día',
                    'horas'         => $horarioDia['horas_planificadas'],
                    'entrada'       => $horarioDia['entrada'],
                    'salida'        => $horarioDia['salida'],
                    'colacion_inicio' => $horarioDia['colacion_inicio'],
                    'colacion_fin'    => $horarioDia['colacion_fin'],
                ];
            }
        }

        return $resultados;
    }
}

if (!function_exists('permisos_gestion_calcular_horario_restante')) {
    /**
     * Calcula el horario restante cuando el practicante faltará un tramo parcial.
     *
     * Ejemplo: Jornada 10:00–20:00, colación 13:00–14:00.
     * Si falta de 15:00 a 20:00, el horario restante es 10:00–15:00.
     * Las horas planificadas restantes se recalculan con la colación si aplica.
     *
     * @param string $jornEntrada Hora de entrada de la jornada (HH:MM).
     * @param string $jornSalida  Hora de salida de la jornada (HH:MM).
     * @param string|null $jornColacionInicio Colación inicio (HH:MM) o null.
     * @param string|null $jornColacionFin   Colación fin (HH:MM) o null.
     * @param string $ausenciaInicio Inicio de la franja ausente (HH:MM).
     * @param string $ausenciaFin   Fin de la franja ausente (HH:MM).
     * @return array{nueva_entrada: string, nueva_salida: string, nueva_colacion_inicio: string|null, nueva_colacion_fin: string|null, horas_restantes: float, horas_ausencia: float}|null
     *   Null si no es posible calcular el horario restante (tramo inválido).
     */
    function permisos_gestion_calcular_horario_restante(
        string $jornEntrada,
        string $jornSalida,
        ?string $jornColacionInicio,
        ?string $jornColacionFin,
        string $ausenciaInicio,
        string $ausenciaFin
    ): ?array {
        // Rechacé horas inexistentes antes de realizar cualquier aritmética del permiso parcial.
        foreach ([$jornEntrada,$jornSalida,$ausenciaInicio,$ausenciaFin] as $hora) {
            if (!permisos_gestion_hora_valida($hora)) { return null; }
        }
        if (($jornColacionInicio !== null && !permisos_gestion_hora_valida($jornColacionInicio))
            || ($jornColacionFin !== null && !permisos_gestion_hora_valida($jornColacionFin))) { return null; }
        // Convertir a minutos para aritmética
        $toMin = static function (string $h): int {
            [$hh, $mm] = explode(':', $h);
            return (int) $hh * 60 + (int) $mm;
        };
        $toHHMM = static function (int $min): string {
            return sprintf('%02d:%02d', intdiv($min, 60), $min % 60);
        };

        $jEntrada = $toMin($jornEntrada);
        $jSalida  = $toMin($jornSalida);
        $aInicio  = $toMin($ausenciaInicio);
        $aFin     = $toMin($ausenciaFin);

        // La franja de ausencia debe estar DENTRO de la jornada
        if ($aInicio < $jEntrada || $aFin > $jSalida || $aInicio >= $aFin) {
            return null;
        }

        // Conservé la ventana completa cuando la ausencia es intermedia, incluyendo el regreso.
        $nuevaEntrada = $jornEntrada;
        $nuevaSalida = $aFin === $jSalida ? $ausenciaInicio : $jornSalida;

        // Si la ausencia empieza al inicio de la jornada
        if ($aInicio === $jEntrada) {
            $nuevaEntrada = $ausenciaFin;
            $nuevaSalida  = $jornSalida;
        }

        $nEntrada = $toMin($nuevaEntrada);
        $nSalida  = $toMin($nuevaSalida);

        if ($nSalida <= $nEntrada) {
            return null;
        }

        // Ajustar colación: solo si cae completamente dentro del nuevo horario
        $nuevaColInicio = null;
        $nuevaColFin    = null;

        if ($jornColacionInicio !== null && $jornColacionFin !== null) {
            $cInicio = $toMin($jornColacionInicio);
            $cFin    = $toMin($jornColacionFin);

            // Conservé también la parte de colación que queda dentro del horario restante.
            if (min($cFin, $nSalida) > max($cInicio, $nEntrada)) {
                $nuevaColInicio = $toHHMM(max($cInicio, $nEntrada));
                $nuevaColFin    = $toHHMM(min($cFin, $nSalida));
            }
        }

        // Calcular horas restantes
        $minTrabajo  = $nSalida - $nEntrada;
        if ($nuevaColInicio !== null && $nuevaColFin !== null) {
            $minTrabajo -= ($toMin($nuevaColFin) - $toMin($nuevaColInicio));
        }
        // Resté la ausencia interior neta, descontando solo el descanso que realmente intersecta.
        if ($aInicio > $jEntrada && $aFin < $jSalida) {
            $minTrabajo -= $aFin - $aInicio - permisos_gestion_minutos_colacion($aInicio, $aFin, $jornColacionInicio, $jornColacionFin);
        }
        $horasRestantes = max(0.0, round($minTrabajo / 60, 2));

        // Calcular horas ausentes
        $minAusencia = $aFin - $aInicio;
        // Descontar colación si cae dentro de la franja ausente
        if ($jornColacionInicio !== null && $jornColacionFin !== null) {
            $cInicio = $toMin($jornColacionInicio);
            $cFin    = $toMin($jornColacionFin);
            $minAusencia -= permisos_gestion_minutos_colacion($aInicio, $aFin, $jornColacionInicio, $jornColacionFin);
        }
        $horasAusencia = max(0.0, round($minAusencia / 60, 2));

        return [
            'nueva_entrada'       => $toHHMM($nEntrada),
            'nueva_salida'        => $toHHMM($nSalida),
            'nueva_colacion_inicio' => $nuevaColInicio,
            'nueva_colacion_fin'    => $nuevaColFin,
            'horas_restantes'     => $horasRestantes,
            'horas_ausencia'      => $horasAusencia,
        ];
    }
}

// Agregué bloqueo común para solicitudes, aprobación y marcajes de una misma práctica.
if (!function_exists('permisos_gestion_marcas_ausencia')) {
    function permisos_gestion_marcas_ausencia(int $idPractica, string $fecha): array
    {
        $lock = db()->prepare('SELECT id_practica FROM practicas WHERE id_practica = ? FOR UPDATE');
        $lock->execute([$idPractica]);
        $stmt = db()->prepare('SELECT id_jornada FROM jornadas_asistencia WHERE id_practica = ? AND fecha = ? AND estado_jornada <> "ANULADA" FOR UPDATE');
        $stmt->execute([$idPractica, $fecha]);
        $id = $stmt->fetchColumn();
        return $id !== false ? asistencia_marcas_vigentes_jornada((int) $id) : [];
    }
}

// Agregué descripción de ambos tramos para no presentar la ausencia intermedia como trabajo continuo.
if (!function_exists('permisos_gestion_texto_tramos')) {
    function permisos_gestion_texto_tramos(array $permiso): string
    {
        $inicio = substr((string) ($permiso['nuevo_horario_inicio'] ?? ''), 0, 5);
        $fin = substr((string) ($permiso['nuevo_horario_fin'] ?? ''), 0, 5);
        $a = substr((string) ($permiso['hora_inicio_ausencia'] ?? ''), 0, 5);
        $b = substr((string) ($permiso['hora_fin_ausencia'] ?? ''), 0, 5);
        // Delimité variables junto al guion tipográfico para evitar que PHP lo interprete como nombre.
        return $inicio < $a && $b < $fin ? "{$inicio}–{$a} y {$b}–{$fin}" : "{$inicio}–{$fin}";
    }
}

if (!function_exists('permisos_gestion_solicitar')) {
    /**
     * Procesa la creación de una solicitud de permiso por parte del practicante.
     * Soporta permisos de jornada COMPLETA o PARCIAL (por horas dentro de la jornada).
     *
     * Para permisos parciales se valida que el tramo de ausencia esté dentro de la jornada.
     * Si el tramo cubre toda la jornada, automáticamente se convierte a tipo COMPLETA.
     *
     * @param int $idPracticante ID del practicante autenticado.
     * @param array $input Datos del formulario.
     * @return array{ok: bool, errores: array<string>, id_permiso?: int}
     */
    function permisos_gestion_solicitar(int $idPracticante, array $input): array
    {
        $errores = [];
        // Verifiqué identidad también en el servicio para no aceptar solicitudes de otra persona.
        if (!auth_check() || auth_rol_activo() !== 'PRACTICANTE' || auth_practicante_actual_id() !== $idPracticante) {
            return ['ok' => false, 'errores' => ['Solo puedes solicitar permisos para tu propia práctica.']];
        }

        // 1. Obtener práctica activa
        $practica = permisos_gestion_practica_activa($idPracticante);
        if ($practica === null) {
            return ['ok' => false, 'errores' => ['No tienes una práctica activa para solicitar permisos.']];
        }

        $idPractica = (int) $practica['id_practica'];

        // 2. Validar Fecha de Ausencia
        $fechaAusencia = trim((string) ($input['fecha_ausencia'] ?? ''));
        $horarioDia    = null;

        if (!permisos_gestion_fecha_valida($fechaAusencia)) {
            $errores[] = 'Ingresa una fecha de ausencia válida (YYYY-MM-DD).';
        } else {
            // Permití regularizar ausencias pasadas sin salir de la vigencia de la práctica.
            if ($fechaAusencia < $practica['fecha_inicio_practica']
                || (!empty($practica['fecha_termino_practica']) && $fechaAusencia > $practica['fecha_termino_practica'])) {
                $errores[] = 'La ausencia debe estar dentro de la vigencia de tu práctica.';
            } else {
                // Verificar que el practicante tenga turno planificado para ese día
                $horarioDia = permisos_gestion_horario_del_dia($idPractica, $fechaAusencia);
                if ($horarioDia === null) {
                    $errores[] = 'La fecha seleccionada no corresponde a un día con jornada laboral planificada en tu horario.';
                }
            }
        }

        // 3. Validar Motivo (máximo 40 palabras)
        $motivo = trim((string) ($input['motivo'] ?? ''));
        if ($motivo === '') {
            $errores[] = 'Debes explicar brevemente el motivo por el cual no asistirás.';
        } else {
            $cantidadPalabras = permisos_gestion_contar_palabras($motivo);
            if ($cantidadPalabras > 40) {
                $errores[] = "El motivo excede el límite máximo de 40 palabras (ingresaste {$cantidadPalabras} palabras).";
            }
        }

        // 4. Procesar tipo de ausencia y horas (COMPLETA o PARCIAL)
        $tipoAusencia       = 'COMPLETA';
        $horaInicioAusencia = null;
        $horaFinAusencia    = null;
        $horasAusencia      = 0.0;
        $horasTrabajodia    = 0.0;
        $nuevoHorarioInicio = null;
        $nuevoHorarioFin    = null;
        $horarioRestante    = null;

        if ($horarioDia !== null && $errores === []) {
            $tipoInput = trim((string) ($input['tipo_ausencia'] ?? 'COMPLETA'));
            $ausIni    = trim((string) ($input['hora_inicio_ausencia'] ?? ''));
            $ausFin    = trim((string) ($input['hora_fin_ausencia'] ?? ''));

            // Evité convertir un parcial inválido en una ausencia completa de forma silenciosa.
            if (!in_array($tipoInput, ['COMPLETA','PARCIAL'], true)) {
                $errores[] = 'Selecciona jornada completa o permiso parcial.';
            } elseif ($tipoInput === 'PARCIAL' && (!permisos_gestion_hora_valida($ausIni) || !permisos_gestion_hora_valida($ausFin))) {
                $errores[] = 'Indica horas válidas de inicio y fin de ausencia en formato HH:MM.';
            } elseif ($tipoInput === 'PARCIAL') {
                $jEntrada = $horarioDia['entrada'];
                $jSalida  = $horarioDia['salida'];

                // Si el tramo cubre toda la jornada, tratar como COMPLETA
                if ($ausIni === $jEntrada && $ausFin === $jSalida) {
                    $tipoAusencia    = 'COMPLETA';
                    $horasAusencia   = $horarioDia['horas_planificadas'];
                    $horasTrabajodia = 0.0;
                } else {
                    // Validar que el tramo esté dentro de la jornada
                    $jEntMin = (int) strtotime("1970-01-01 {$jEntrada}:00");
                    $jSalMin = (int) strtotime("1970-01-01 {$jSalida}:00");
                    $aIniMin = (int) strtotime("1970-01-01 {$ausIni}:00");
                    $aFinMin = (int) strtotime("1970-01-01 {$ausFin}:00");

                    if ($aIniMin < $jEntMin) {
                        $errores[] = "La hora de inicio de ausencia ({$ausIni}) no puede ser anterior a tu hora de entrada ({$jEntrada}).";
                    } elseif ($aFinMin > $jSalMin) {
                        $errores[] = "La hora de fin de ausencia ({$ausFin}) no puede ser posterior a tu hora de salida ({$jSalida}).";
                    } elseif ($aFinMin <= $aIniMin) {
                        $errores[] = 'La hora de fin de ausencia debe ser posterior a la de inicio.';
                    } else {
                        $horarioRestante = permisos_gestion_calcular_horario_restante(
                            $jEntrada,
                            $jSalida,
                            $horarioDia['colacion_inicio'],
                            $horarioDia['colacion_fin'],
                            $ausIni,
                            $ausFin
                        );

                        if ($horarioRestante === null) {
                            $errores[] = 'No fue posible calcular el horario restante. Verifica el tramo de ausencia.';
                        } else {
                            $tipoAusencia       = 'PARCIAL';
                            $horaInicioAusencia = $ausIni;
                            $horaFinAusencia    = $ausFin;
                            $horasAusencia      = $horarioRestante['horas_ausencia'];
                            $horasTrabajodia    = $horarioRestante['horas_restantes'];
                            $nuevoHorarioInicio = $horarioRestante['nueva_entrada'];
                            $nuevoHorarioFin    = $horarioRestante['nueva_salida'];
                            // Impedí solicitar recuperación de una franja que solo contiene descanso.
                            if ($horasAusencia <= 0) { $errores[] = 'La ausencia debe incluir tiempo laboral, no solamente colación.'; }
                        }
                    }
                }
            } else {
                // Jornada completa
                $tipoAusencia    = 'COMPLETA';
                $horasAusencia   = $horarioDia['horas_planificadas'];
                $horasTrabajodia = 0.0;
            }
        }

        // 5. Validar Fecha y Horario de Recuperación
        $fechaRecuperacion = trim((string) ($input['fecha_recuperacion'] ?? ''));
        $horaInicio        = trim((string) ($input['hora_inicio_recuperacion'] ?? ''));
        $horaFin           = trim((string) ($input['hora_fin_recuperacion'] ?? ''));

        // Admití formularios anteriores como un solo tramo, pero apliqué las reglas nuevas a ambos formatos.
        $planInput = $input['recuperaciones'] ?? null;
        if ($planInput === null) {
            $tipo = 'EXTENSION';
            if (permisos_gestion_fecha_valida($fechaRecuperacion)) {
                foreach (recuperaciones_opciones_fecha($idPractica,$fechaRecuperacion) as $opcion) {
                    if ($horaInicio >= $opcion['inicio'] && $horaFin <= $opcion['fin']) { $tipo=$opcion['tipo']; break; }
                }
            }
            $planInput=[['fecha'=>$fechaRecuperacion,'tipo'=>$tipo,'hora_inicio'=>$horaInicio,'hora_fin'=>$horaFin]];
        }
        $ausenciaPlan=['fecha_ausencia'=>$fechaAusencia,'tipo_ausencia'=>$tipoAusencia,'hora_inicio_ausencia'=>$horaInicioAusencia,
            'hora_fin_ausencia'=>$horaFinAusencia,'entrada'=>$horarioDia['entrada']??'00:00','salida'=>$horarioDia['salida']??'23:59'];
        $planValidado=recuperaciones_validar_plan($idPractica,is_array($planInput)?$planInput:[],(int)round($horasAusencia*60),$ausenciaPlan);
        $errores=array_merge($errores,$planValidado['errores']);
        $tramosRecuperacion=$planValidado['tramos'];
        if ($tramosRecuperacion!==[]) {
            $fechaRecuperacion=$tramosRecuperacion[0]['fecha'];
            $horaInicio=$tramosRecuperacion[0]['hora_inicio']; $horaFin=$tramosRecuperacion[0]['hora_fin'];
        }

        if (!permisos_gestion_fecha_valida($fechaRecuperacion)) {
            $errores[] = 'Ingresa una fecha de recuperación válida.';
        } elseif ($fechaRecuperacion < date('Y-m-d')) {
            $errores[] = 'La fecha de recuperación no puede ser una fecha pasada.';
        }

        // Validar formato de horas HH:MM
        if (!permisos_gestion_hora_valida($horaInicio) || !permisos_gestion_hora_valida($horaFin)) {
            $errores[] = 'Indica hora de inicio y fin de recuperación en formato HH:MM.';
        } else {
            $minInicio = (int) strtotime("1970-01-01 {$horaInicio}:00");
            $minFin    = (int) strtotime("1970-01-01 {$horaFin}:00");

            if ($minFin <= $minInicio) {
                $errores[] = 'La hora de término de recuperación debe ser posterior a la hora de inicio.';
            }

            // Regla específica para días SÁBADO: Permitir entre 10:00 y 20:00
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaRecuperacion) && permisos_gestion_es_sabado($fechaRecuperacion)) {
                $limiteMinimo = (int) strtotime('1970-01-01 10:00:00');
                $limiteMaximo = (int) strtotime('1970-01-01 20:00:00');

                if ($minInicio < $limiteMinimo || $minFin > $limiteMaximo) {
                    $errores[] = 'Para recuperaciones en día Sábado, el horario debe estar comprendido entre las 10:00 y las 20:00 hrs.';
                }
            }
        }

        // 6. Verificar si ya existe una solicitud pendiente o aprobada para esa misma fecha de ausencia
        if ($errores === []) {
            $stmtCheck = db()->prepare(
                'SELECT COUNT(*) FROM permisos_practicante
                 WHERE id_practica = :id_practica
                   AND fecha_ausencia = :fecha_ausencia
                   AND estado IN ("PENDIENTE", "APROBADO")'
            );
            $stmtCheck->execute([
                'id_practica'   => $idPractica,
                'fecha_ausencia' => $fechaAusencia,
            ]);
            if ((int) $stmtCheck->fetchColumn() > 0) {
                $errores[] = 'Ya tienes una solicitud de permiso pendiente o aprobada para esa fecha de ausencia.';
            }
        }

        if ($errores !== []) {
            // Rechacé automáticamente el envío tardío y audité el intento sin crear una reserva.
            if(in_array(RECUPERACION_FUERA_PLAZO,$errores,true)) { recuperaciones_auditar_rechazo_plazo($idPractica); }
            return ['ok' => false, 'errores' => $errores];
        }

        // Calcular total de horas de recuperación
        $horasRecuperar = round(array_sum(array_column($tramosRecuperacion,'minutos_planificados'))/60,2);

        try {
            // Guardé solicitud, observación y auditoría juntas; revisé duplicados bajo bloqueo.
            db()->beginTransaction();
            $marcasAusencia = permisos_gestion_marcas_ausencia($idPractica, $fechaAusencia);
            // Revalidé bajo bloqueo que la ausencia no duplique un canje ya reservado.
            if(tiempo_fecha_reservada($idPractica,$fechaAusencia)) { db()->rollBack();return ['ok'=>false,'errores'=>['Ya hay una compensación de tiempo excepcional ese día; resuélvela antes de solicitar el permiso.']]; }
            // Revalidé las reservas tras bloquear la práctica para evitar planes simultáneos superpuestos.
            $planBajoBloqueo=recuperaciones_validar_plan($idPractica,$planInput,(int)round($horasAusencia*60),$ausenciaPlan);
            if ($planBajoBloqueo['errores']!==[]) {
                db()->rollBack(); return ['ok'=>false,'errores'=>$planBajoBloqueo['errores']];
            }
            $stmtCheck->execute(['id_practica' => $idPractica, 'fecha_ausencia' => $fechaAusencia]);
            if ((int) $stmtCheck->fetchColumn() > 0) {
                db()->rollBack();
                return ['ok' => false, 'errores' => ['Ya existe una solicitud pendiente o aprobada para ese día.']];
            }
            $requiereRevision = asistencia_permiso_cruza_marcas([
                'fecha_ausencia' => $fechaAusencia, 'tipo_ausencia' => $tipoAusencia,
                'hora_inicio_ausencia' => $horaInicioAusencia, 'hora_fin_ausencia' => $horaFinAusencia,
            ], $marcasAusencia);
            $observacionSolicitud = $requiereRevision ? 'La ausencia cruza tiempo registrado como trabajado. El supervisor debe corregir las marcas o rechazar la solicitud antes de aprobar.' : null;
            $stmtInsert = db()->prepare(
                'INSERT INTO permisos_practicante (
                    id_practica,
                    fecha_ausencia,
                    tipo_ausencia,
                    hora_inicio_ausencia,
                    hora_fin_ausencia,
                    horas_ausencia,
                    horas_trabajo_dia,
                    nuevo_horario_inicio,
                    nuevo_horario_fin,
                    motivo,
                    fecha_recuperacion,
                    hora_inicio_recuperacion,
                    hora_fin_recuperacion,
                    horas_a_recuperar,
                    estado,
                    requiere_revision,
                    observacion_solicitud,
                    created_at
                ) VALUES (
                    :id_practica,
                    :fecha_ausencia,
                    :tipo_ausencia,
                    :hora_inicio_ausencia,
                    :hora_fin_ausencia,
                    :horas_ausencia,
                    :horas_trabajo_dia,
                    :nuevo_horario_inicio,
                    :nuevo_horario_fin,
                    :motivo,
                    :fecha_recuperacion,
                    :hora_inicio_recuperacion,
                    :hora_fin_recuperacion,
                    :horas_a_recuperar,
                    "PENDIENTE",
                    :requiere_revision,
                    :observacion_solicitud,
                    NOW()
                )'
            );
            $stmtInsert->execute([
                'id_practica'             => $idPractica,
                'fecha_ausencia'          => $fechaAusencia,
                'tipo_ausencia'           => $tipoAusencia,
                'hora_inicio_ausencia'    => $horaInicioAusencia ? $horaInicioAusencia . ':00' : null,
                'hora_fin_ausencia'       => $horaFinAusencia    ? $horaFinAusencia    . ':00' : null,
                'horas_ausencia'          => $horasAusencia,
                'horas_trabajo_dia'       => $horasTrabajodia,
                'nuevo_horario_inicio'    => $nuevoHorarioInicio ? $nuevoHorarioInicio . ':00' : null,
                'nuevo_horario_fin'       => $nuevoHorarioFin    ? $nuevoHorarioFin    . ':00' : null,
                'motivo'                  => $motivo,
                'fecha_recuperacion'      => $fechaRecuperacion,
                'hora_inicio_recuperacion' => $horaInicio . ':00',
                'hora_fin_recuperacion'   => $horaFin    . ':00',
                'horas_a_recuperar'       => $horasRecuperar,
                'requiere_revision' => $requiereRevision ? 1 : 0,
                'observacion_solicitud' => $observacionSolicitud,
            ]);

            $idPermiso = (int) db()->lastInsertId();
            recuperaciones_guardar_plan($idPermiso,$tramosRecuperacion);

            if (!auditoria_registrar('SOLICITUD_PERMISO', [
                'tabla_afectada' => 'permisos_practicante',
                // Corregí la clave para que auditoría conserve el permiso afectado.
                'id_registro_afectado' => $idPermiso,
                'valores_nuevos' => [
                    'id_practica'       => $idPractica,
                    'fecha_ausencia'    => $fechaAusencia,
                    'tipo_ausencia'     => $tipoAusencia,
                    'fecha_recuperacion' => $fechaRecuperacion,
                    'horas_a_recuperar' => $horasRecuperar,
                    'requiere_revision' => $requiereRevision,
                    'observacion_solicitud' => $observacionSolicitud,
                    'recuperaciones' => $tramosRecuperacion,
                ],
            ])) { throw new RuntimeException('No se pudo auditar la solicitud.'); }
            db()->commit();

            return ['ok' => true, 'errores' => [], 'id_permiso' => $idPermiso];
        } catch (Throwable $e) {
            if (db()->inTransaction()) { db()->rollBack(); }
            error_log('Error al registrar solicitud de permiso: ' . $e->getMessage());
            return ['ok' => false, 'errores' => ['Ocurrió un error al guardar la solicitud de permiso. Intenta nuevamente.']];
        }
    }
}

if (!function_exists('permisos_gestion_listar_por_practica')) {
    /**
     * Lista todas las solicitudes de permiso de una práctica específica.
     *
     * @param int $idPractica ID de la práctica.
     * @return array Lista de permisos.
     */
    function permisos_gestion_listar_por_practica(int $idPractica): array
    {
        $statement = db()->prepare(
            'SELECT pp.*, u.email AS supervisor_email
             FROM permisos_practicante pp
             LEFT JOIN usuarios_sistema u ON u.id_usuario = pp.revisado_por
             WHERE pp.id_practica = :id_practica
             ORDER BY pp.created_at DESC, pp.id_permiso DESC'
        );
        $statement->execute(['id_practica' => $idPractica]);

        return recuperaciones_adjuntar_planes($statement->fetchAll());
    }
}

if (!function_exists('permisos_gestion_listar_todos')) {
    /**
     * Lista solicitudes de permisos para el panel de Supervisor o Admin con filtros, buscador y paginación.
     *
     * @param array $filtros Filtros aplicados.
     * @param string $rolEsperado Rol del usuario autenticado.
     * @param int $limite Cantidad por página.
     * @param int $pagina Número de página.
     * @return array{items: array, total: int, paginas: int, pendientes: int, aprobados: int, rechazados: int}
     */
    function permisos_gestion_listar_todos(array $filtros, string $rolEsperado, int $limite = 5, int $pagina = 1): array
    {
        $where  = ['pr.activo = 1', 'p.activo = 1'];
        $params = [];

        // Filtro por estado
        $estado = trim((string) ($filtros['estado'] ?? ''));
        if (in_array($estado, ['PENDIENTE', 'APROBADO', 'RECHAZADO'], true)) {
            // Incluí planes aprobados con saldo adicional pendiente de autorización en el filtro de revisión.
            if ($estado==='PENDIENTE') {
                $where[]='(pp.estado="PENDIENTE" OR (pp.estado="APROBADO" AND EXISTS (SELECT 1 FROM recuperaciones_permiso rp WHERE rp.id_permiso=pp.id_permiso AND rp.estado="PROPUESTA")))';
            } else { $where[]='pp.estado = :estado'; $params['estado']=$estado; }
        }

        // Filtro por rango de fechas de ausencia
        if (!empty($filtros['desde'])) {
            $where[]          = 'pp.fecha_ausencia >= :desde';
            $params['desde']  = $filtros['desde'];
        }
        if (!empty($filtros['hasta'])) {
            $where[]          = 'pp.fecha_ausencia <= :hasta';
            $params['hasta']  = $filtros['hasta'];
        }

        // Búsqueda inteligente por practicante
        if (!empty($filtros['q'])) {
            $busq = sql_construir_busqueda_practicante((string) $filtros['q'], 'p', 'busq_perm');
            if ($busq['condicion'] !== '') {
                $where[]  = $busq['condicion'];
                $params   = array_merge($params, $busq['params']);
            }
        }

        $idPracticanteActor = auth_practicante_actual_id();
        if ($rolEsperado === 'SUPERVISOR' && $idPracticanteActor !== null) {
            $where[]                      = 'p.id_practicante <> :id_practicante_actor';
            $params['id_practicante_actor'] = $idPracticanteActor;
        }

        $whereSql = implode(' AND ', $where);

        // Conteo total para paginación
        $stmtTotal = db()->prepare(
            "SELECT COUNT(*)
             FROM permisos_practicante pp
             INNER JOIN practicas pr ON pr.id_practica = pp.id_practica
             INNER JOIN practicantes p ON p.id_practicante = pr.id_practicante
             WHERE {$whereSql}"
        );
        $stmtTotal->execute($params);
        $totalItems = (int) $stmtTotal->fetchColumn();

        $offset = max(0, ($pagina - 1) * $limite);

        // Consulta de elementos
        $stmtItems = db()->prepare(
            "SELECT
                pp.*,
                p.nombre_1,
                p.nombre_2,
                p.apellido_paterno,
                p.apellido_materno,
                p.email AS practicante_email,
                p.rut_numero,
                p.rut_dv,
                tp.nombre AS tipo_practica_nombre,
                u.email AS revisor_email
             FROM permisos_practicante pp
             INNER JOIN practicas pr ON pr.id_practica = pp.id_practica
             INNER JOIN practicantes p ON p.id_practicante = pr.id_practicante
             LEFT JOIN tipos_practica tp ON tp.id_tipo_practica = pr.id_tipo_practica
             LEFT JOIN usuarios_sistema u ON u.id_usuario = pp.revisado_por
             WHERE {$whereSql}
             ORDER BY
                CASE pp.estado WHEN 'PENDIENTE' THEN 1 WHEN 'APROBADO' THEN 2 ELSE 3 END ASC,
                pp.fecha_ausencia ASC,
                pp.id_permiso DESC
             LIMIT {$limite} OFFSET {$offset}"
        );
        $stmtItems->execute($params);
        $items = $stmtItems->fetchAll();

        // Conteo de métricas rápidas (globales o según filtros de rol)
        $stmtMetricas = db()->query(
            'SELECT
                /* Conté permisos que necesitan revisar la ausencia o autorizar tramos adicionales. */
                SUM(CASE WHEN estado = "PENDIENTE" OR (estado="APROBADO" AND EXISTS (SELECT 1 FROM recuperaciones_permiso rp WHERE rp.id_permiso=pp.id_permiso AND rp.estado="PROPUESTA")) THEN 1 ELSE 0 END) AS pendientes,
                SUM(CASE WHEN estado = "APROBADO" THEN 1 ELSE 0 END) AS aprobados,
                SUM(CASE WHEN estado = "RECHAZADO" THEN 1 ELSE 0 END) AS rechazados
             FROM permisos_practicante pp'
        );
        $metricas = $stmtMetricas->fetch() ?: [];

        return [
            'items'     => recuperaciones_adjuntar_planes($items),
            'total'     => $totalItems,
            'paginas'   => (int) ceil($totalItems / $limite),
            'pendientes' => (int) ($metricas['pendientes'] ?? 0),
            'aprobados'  => (int) ($metricas['aprobados']  ?? 0),
            'rechazados' => (int) ($metricas['rechazados'] ?? 0),
        ];
    }
}

if (!function_exists('permisos_gestion_contar_pendientes')) {
    /**
     * Retorna el número de solicitudes de permiso pendientes para mostrar en el badge del sidebar.
     *
     * @return int Cantidad de permisos pendientes.
     */
    function permisos_gestion_contar_pendientes(): int
    {
        try {
            // Mantuve visible el saldo adicional pendiente en el contador operativo.
            $statement = db()->query('SELECT COUNT(*) FROM permisos_practicante pp WHERE estado="PENDIENTE" OR (estado="APROBADO" AND EXISTS (SELECT 1 FROM recuperaciones_permiso rp WHERE rp.id_permiso=pp.id_permiso AND rp.estado="PROPUESTA"))');
            return (int) $statement->fetchColumn();
        } catch (Throwable) {
            return 0;
        }
    }
}

if (!function_exists('permisos_gestion_aprobar')) {
    /**
     * Aprueba un permiso solicitado por el practicante:
     *
     * 1. Actualiza el estado a APROBADO con la respuesta del supervisor.
     * 2a. Si es COMPLETA: crea excepción AUSENCIA_JUSTIFICADA en la fecha de ausencia.
     * 2b. Si es PARCIAL: crea excepción CAMBIO_HORARIO en la fecha de ausencia con el
     *     horario restante (nuevo_horario_inicio a nuevo_horario_fin), acotando las
     *     horas planificadas a las horas_trabajo_dia calculadas al solicitar.
     * 3. Crea excepción ASISTENCIA_EXTRAORDINARIA para el día de recuperación.
     * 4. Registra en auditoría.
     *
     * @param int $idPermiso ID del permiso.
     * @param int $idUsuarioRevisor ID del usuario que aprueba.
     * @param string $comentario Mensaje u observación de aprobación.
     * @return array{ok: bool, errores: array<string>}
     */
    function permisos_gestion_aprobar(int $idPermiso, int $idUsuarioRevisor, string $comentario = '', bool $autorizarFeriados = false): array
    {
        $comentario = trim($comentario);

        // Agregué comprobación del actor real para impedir roles no operativos
        // o un ID de revisor distinto del usuario autenticado.
        if (!auth_check() || !in_array(auth_rol_activo(), ['ADMIN', 'SUPERVISOR'], true)
            || $idUsuarioRevisor !== auth_usuario_actual_id()) {
            return ['ok' => false, 'errores' => ['No tienes permisos para aprobar esta solicitud.']];
        }

        try {
            db()->beginTransaction();

            $stmtPermiso = db()->prepare(
                'SELECT * FROM permisos_practicante WHERE id_permiso = :id_permiso FOR UPDATE'
            );
            $stmtPermiso->execute(['id_permiso' => $idPermiso]);
            $permiso = $stmtPermiso->fetch();

            if (!is_array($permiso)) {
                db()->rollBack();
                return ['ok' => false, 'errores' => ['El permiso seleccionado no existe.']];
            }

            // Agregué validación del objetivo recibido, antes de crear
            // excepciones: ocultar permisos propios en el listado no basta.
            if (!auth_puede_supervisar_practica((int) $permiso['id_practica'], $idUsuarioRevisor)) {
                db()->rollBack();
                return ['ok' => false, 'errores' => ['No puedes aprobar permisos de esta práctica.']];
            }

            if ($permiso['estado'] !== 'PENDIENTE') {
                db()->rollBack();
                return ['ok' => false, 'errores' => ['Este permiso ya fue revisado anteriormente.']];
            }

            $idPractica        = (int) $permiso['id_practica'];
            $fechaAusencia     = (string) $permiso['fecha_ausencia'];
            $fechaRecuperacion = (string) $permiso['fecha_recuperacion'];
            $horaInicio        = substr((string) $permiso['hora_inicio_recuperacion'], 0, 5);
            $horaFin           = substr((string) $permiso['hora_fin_recuperacion'], 0, 5);
            $tipoAusencia      = (string) ($permiso['tipo_ausencia'] ?? 'COMPLETA');

            // Recalculé el cruce con marcas vigentes; una observación no puede aprobarse por omisión.
            $marcasAusencia = permisos_gestion_marcas_ausencia($idPractica, $fechaAusencia);
            // Una compensación creada después de solicitar el permiso también impide aprobar ambos.
            if(tiempo_fecha_reservada($idPractica,$fechaAusencia)) { db()->rollBack();return ['ok'=>false,'errores'=>['Resuelve la compensación de ese día antes de aprobar el permiso.']]; }
            // Revalidé el plan completo con el calendario vigente; feriados requieren autorización y motivo.
            $planRecuperacion=recuperaciones_plan($idPermiso);
            if ($planRecuperacion!==[]) {
                // Verifiqué también la ausencia: un feriado o cambio de jornada no debe crear deuda inexistente.
                $turnoActual=permisos_gestion_horario_del_dia($idPractica,$fechaAusencia);
                if ($turnoActual===null || ($tipoAusencia==='COMPLETA' && abs((float)$turnoActual['horas_planificadas']-(float)$permiso['horas_ausencia'])>0.001)) {
                    db()->rollBack();return ['ok'=>false,'errores'=>['El horario o calendario de la ausencia cambió. Revisa la solicitud antes de aprobar su recuperación.']];
                }
                $validacionPlan=recuperaciones_validar_plan($idPractica,$planRecuperacion,(int)round((float)$permiso['horas_ausencia']*60),$permiso,true);
                if ($validacionPlan['errores']!==[]) { db()->rollBack(); return ['ok'=>false,'errores'=>$validacionPlan['errores']]; }
                if (in_array('FERIADO',array_column($planRecuperacion,'tipo'),true) && (!$autorizarFeriados || $comentario==='')) {
                    db()->rollBack(); return ['ok'=>false,'errores'=>['La recuperación en feriados exige autorización expresa y motivo del supervisor.']];
                }
            }
            if (asistencia_permiso_cruza_marcas($permiso, $marcasAusencia)) {
                db()->rollBack();
                return ['ok' => false, 'errores' => ['La ausencia cruza tiempo trabajado. Corrige las marcas de asistencia o rechaza la solicitud antes de aprobar.']];
            }
            if ((int) ($permiso['requiere_revision'] ?? 0) === 1 && $comentario === '') {
                db()->rollBack();
                return ['ok' => false, 'errores' => ['Registra cómo resolviste la observación antes de aprobar.']];
            }

            // Obtener el horario base vigente para la práctica (para fecha de recuperación)
            $horarioBase = permisos_gestion_horario_base_vigente($idPractica, $fechaRecuperacion);
            if ($horarioBase === null) {
                $horarioBase = permisos_gestion_horario_base_vigente($idPractica);
            }

            $idExcepcionCreada   = null;
            $idExcepcionAusencia = null;

            // ---- 2a / 2b: Crear excepción en fecha de ausencia ----
            // Necesitamos el id_horario vigente para la fecha de ausencia
            $horarioBaseAusencia = permisos_gestion_horario_base_vigente($idPractica, $fechaAusencia);
            if ($horarioBaseAusencia === null) {
                $horarioBaseAusencia = permisos_gestion_horario_base_vigente($idPractica);
            }

            // Agregué este control para no aprobar una recuperación sin los
            // horarios necesarios para crear ambas excepciones.
            if ($horarioBase === null || $horarioBaseAusencia === null) {
                db()->rollBack();
                return ['ok' => false, 'errores' => ['No hay horario vigente para completar la aprobación.']];
            }

            if ($horarioBaseAusencia !== null) {
                $idHorarioAusencia = (int) $horarioBaseAusencia['id_horario'];
                $obsAusencia       = 'Permiso autorizado: ' . ($comentario !== '' ? $comentario : 'ausencia justificada.');

                if ($tipoAusencia === 'PARCIAL'
                    && !empty($permiso['nuevo_horario_inicio'])
                    && !empty($permiso['nuevo_horario_fin'])
                ) {
                    // Permiso PARCIAL: CAMBIO_HORARIO con horario acotado
                    $nuevoInicio   = substr((string) $permiso['nuevo_horario_inicio'], 0, 5);
                    $nuevoFin      = substr((string) $permiso['nuevo_horario_fin'],    0, 5);
                    // Recalculé la colación restante: esos campos no se almacenan en permisos_practicante.
                    $turnoAusencia = permisos_gestion_horario_del_dia($idPractica, $fechaAusencia);
                    $restante = $turnoAusencia !== null ? permisos_gestion_calcular_horario_restante(
                        $turnoAusencia['entrada'], $turnoAusencia['salida'],
                        $turnoAusencia['colacion_inicio'], $turnoAusencia['colacion_fin'],
                        substr((string) $permiso['hora_inicio_ausencia'], 0, 5),
                        substr((string) $permiso['hora_fin_ausencia'], 0, 5)
                    ) : null;
                    if ($restante === null || $restante['nueva_entrada'] !== $nuevoInicio || $restante['nueva_salida'] !== $nuevoFin
                        || ($planRecuperacion!==[] && (abs($restante['horas_ausencia']-(float)$permiso['horas_ausencia'])>0.001 || abs($restante['horas_restantes']-(float)$permiso['horas_trabajo_dia'])>0.001))) {
                        db()->rollBack();
                        return ['ok' => false, 'errores' => ['El horario cambió desde la solicitud. Revisa el permiso parcial y su tramo de ausencia antes de aprobar.']];
                    }
                    $nuevaColIni = $restante['nueva_colacion_inicio'];
                    $nuevaColFin = $restante['nueva_colacion_fin'];

                    $resultExcAus = excepciones_horario_guardar(
                        $idHorarioAusencia,
                        $fechaAusencia,
                        'CAMBIO_HORARIO',
                        $nuevoInicio,
                        $nuevoFin,
                        $nuevaColIni,
                        $nuevaColFin,
                        'Permiso parcial aprobado: trabaja de ' . $nuevoInicio . ' a ' . $nuevoFin . '. Ausente de ' . substr((string) $permiso['hora_inicio_ausencia'], 0, 5) . ' a ' . substr((string) $permiso['hora_fin_ausencia'], 0, 5) . '.'
                    );

                    if (empty($resultExcAus['ok']) || empty($resultExcAus['id_excepcion'])) {
                        throw new RuntimeException('No se pudo crear la excepción de ausencia parcial.');
                    }
                    // Guardé horas netas de ambos tramos, no la duración continua de la ventana exterior.
                    $stmtHoras = db()->prepare('UPDATE excepciones_horario_practicante SET horas_planificadas = ? WHERE id_excepcion = ?');
                    $stmtHoras->execute([$restante['horas_restantes'], (int) $resultExcAus['id_excepcion']]);
                    if (!empty($resultExcAus['ok']) && !empty($resultExcAus['id_excepcion'])) {
                        $idExcepcionAusencia = (int) $resultExcAus['id_excepcion'];
                    }
                } else {
                    // Permiso COMPLETO: AUSENCIA_JUSTIFICADA
                    $resultExcAus = excepciones_horario_guardar(
                        $idHorarioAusencia,
                        $fechaAusencia,
                        'AUSENCIA_JUSTIFICADA',
                        null,
                        null,
                        null,
                        null,
                        $obsAusencia
                    );

                    if (empty($resultExcAus['ok']) || empty($resultExcAus['id_excepcion'])) {
                        throw new RuntimeException('No se pudo crear la excepción de ausencia.');
                    }
                    if (!empty($resultExcAus['ok']) && !empty($resultExcAus['id_excepcion'])) {
                        $idExcepcionAusencia = (int) $resultExcAus['id_excepcion'];
                    }
                }
            }

            // ---- 3: Crear excepción de ASISTENCIA_EXTRAORDINARIA para recuperación ----
            if ($horarioBase !== null && $planRecuperacion === []) {
                $idHorario    = (int) $horarioBase['id_horario'];
                $obsExcepcion = 'Recuperación de permiso del ' . date('d-m-Y', strtotime($fechaAusencia));
                if ($comentario !== '') {
                    $obsExcepcion .= ' - ' . $comentario;
                }

                $resultadoExcepcion = excepciones_horario_guardar(
                    $idHorario,
                    $fechaRecuperacion,
                    'ASISTENCIA_EXTRAORDINARIA',
                    $horaInicio,
                    $horaFin,
                    null,
                    null,
                    $obsExcepcion
                );

                // Agregué rollback ante una excepción fallida para no dejar
                // permisos aprobados con una recuperación incompleta.
                if (empty($resultadoExcepcion['ok']) || empty($resultadoExcepcion['id_excepcion'])) {
                    throw new RuntimeException('No se pudo crear la excepción de recuperación.');
                }
                if (!empty($resultadoExcepcion['ok']) && !empty($resultadoExcepcion['id_excepcion'])) {
                    $idExcepcionCreada = (int) $resultadoExcepcion['id_excepcion'];
                }
            }

            // ---- 4: Actualizar permiso a APROBADO ----
            $stmtUpdate = db()->prepare(
                'UPDATE permisos_practicante
                 SET estado = "APROBADO",
                     requiere_revision = 0,
                     respuesta_supervisor = :respuesta,
                     revisado_por = :revisado_por,
                     fecha_revision = NOW(),
                     id_excepcion_creada = :id_excepcion,
                     id_excepcion_ausencia = :id_excepcion_ausencia
                 WHERE id_permiso = :id_permiso'
            );
            $stmtUpdate->execute([
                'respuesta'            => $comentario !== '' ? $comentario : 'Permiso aprobado y turno de recuperación habilitado.',
                'revisado_por'         => $idUsuarioRevisor,
                'id_excepcion'         => $idExcepcionCreada,
                'id_excepcion_ausencia' => $idExcepcionAusencia,
                'id_permiso'           => $idPermiso,
            ]);
            // Autorización del plan no valida horas: cada tramo requiere comprobar las marcas al terminar.
            $stmtTramos=db()->prepare('UPDATE recuperaciones_permiso SET estado="PROGRAMADA",autorizado_feriado=? WHERE id_permiso=? AND estado="PROPUESTA"');
            $stmtTramos->execute([$autorizarFeriados?1:0,$idPermiso]);

            // Actualicé la jornada existente con el horario autorizado, conservando marcas e historial.
            $stmtJornada = db()->prepare('SELECT id_jornada FROM jornadas_asistencia WHERE id_practica = ? AND fecha = ? AND estado_jornada <> "ANULADA"');
            $stmtJornada->execute([$idPractica, $fechaAusencia]);
            $idJornada = $stmtJornada->fetchColumn();
            if ($idJornada !== false && $tipoAusencia === 'PARCIAL') {
                asistencia_recalcular_jornada((int) $idJornada, null, true);
            }

            // Reconocí al aprobar los tramos ya terminados con marcas completas, sin plazo
            // máximo de revisión. Una aprobación tardía no inventa evidencia que falte.
            foreach ($planRecuperacion as $tramo) {
                if (strtotime($tramo['fecha'].' '.$tramo['hora_fin']) > time()) { continue; }
                $buscar = db()->prepare('SELECT * FROM jornadas_asistencia WHERE id_practica=? AND fecha=? FOR UPDATE');
                $buscar->execute([$idPractica, $tramo['fecha']]);
                $jornadaRec = $buscar->fetch();
                if (!is_array($jornadaRec) || $jornadaRec['estado_jornada']==='ANULADA') { continue; }
                $marcasRec = asistencia_marcas_vigentes_jornada((int)$jornadaRec['id_jornada'], true);
                $revisionRec = asistencia_gestion_validar_revision(array_replace($jornadaRec, ['estado_jornada'=>'OBSERVADA']), $marcasRec);
                if (!$revisionRec['ok']) { continue; }
                $validada = recuperaciones_validar((int)$tramo['id_recuperacion'], mb_substr(
                    'Validación al aprobar permiso: '.($comentario!==''?$comentario:'marcas completas revisadas por el supervisor.'), 0, 500));
                if (!$validada['ok']) { throw new RuntimeException(implode(' ', $validada['errores'])); }
            }

            // Moví la auditoría dentro de la transacción: sin traza válida
            // se revierten tanto la aprobación como sus excepciones.
            if (!auditoria_registrar('APROBAR_PERMISO', [
                'tabla_afectada' => 'permisos_practicante',
                'id_registro_afectado' => $idPermiso,
                'valores_anteriores' => $permiso,
                'valores_nuevos' => [
                    'estado'               => 'APROBADO',
                    'tipo_ausencia'        => $tipoAusencia,
                    'id_excepcion_creada'  => $idExcepcionCreada,
                    'id_excepcion_ausencia' => $idExcepcionAusencia,
                    'revisado_por'         => $idUsuarioRevisor,
                    'recuperaciones' => $planRecuperacion,
                    'autorizar_feriados' => $autorizarFeriados,
                ],
            ])) {
                throw new RuntimeException('No se pudo auditar la aprobación del permiso.');
            }
            db()->commit();

            return ['ok' => true, 'errores' => []];
        } catch (Throwable $e) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            error_log('Error al aprobar permiso: ' . $e->getMessage());
            return ['ok' => false, 'errores' => ['Error al aprobar el permiso. Verifique los registros.']];
        }
    }
}

if (!function_exists('permisos_gestion_rechazar')) {
    /**
     * Rechaza una solicitud de permiso registrando obligatoriamente el motivo.
     *
     * @param int $idPermiso ID del permiso.
     * @param int $idUsuarioRevisor ID del usuario que rechaza.
     * @param string $motivoRechazo Explicación de la denegación.
     * @return array{ok: bool, errores: array<string>}
     */
    function permisos_gestion_rechazar(int $idPermiso, int $idUsuarioRevisor, string $motivoRechazo): array
    {
        $motivoRechazo = trim($motivoRechazo);
        if ($motivoRechazo === '') {
            return ['ok' => false, 'errores' => ['Debes indicar el motivo del rechazo para informar al practicante.']];
        }

        // Agregué la misma comprobación de actor en rechazo que en aprobación.
        if (!auth_check() || !in_array(auth_rol_activo(), ['ADMIN', 'SUPERVISOR'], true)
            || $idUsuarioRevisor !== auth_usuario_actual_id()) {
            return ['ok' => false, 'errores' => ['No tienes permisos para rechazar esta solicitud.']];
        }

        try {
            // Agregué bloqueo y autorización del permiso antes de modificarlo,
            // evitando autosupervisión y dos resoluciones simultáneas.
            db()->beginTransaction();
            $select = db()->prepare('SELECT * FROM permisos_practicante WHERE id_permiso = :id_permiso FOR UPDATE');
            $select->execute(['id_permiso' => $idPermiso]);
            $permiso = $select->fetch();
            if (!is_array($permiso) || $permiso['estado'] !== 'PENDIENTE'
                || !auth_puede_supervisar_practica((int) $permiso['id_practica'], $idUsuarioRevisor)) {
                db()->rollBack();
                return ['ok' => false, 'errores' => ['No puedes rechazar este permiso o ya fue resuelto.']];
            }
            $stmtUpdate = db()->prepare(
                'UPDATE permisos_practicante
                 SET estado = "RECHAZADO",
                     respuesta_supervisor = :respuesta,
                     revisado_por = :revisado_por,
                     fecha_revision = NOW()
                 WHERE id_permiso = :id_permiso
                   AND estado = "PENDIENTE"'
            );
            $stmtUpdate->execute([
                'respuesta'    => $motivoRechazo,
                'revisado_por' => $idUsuarioRevisor,
                'id_permiso'   => $idPermiso,
            ]);

            if ($stmtUpdate->rowCount() === 0) {
                db()->rollBack();
                return ['ok' => false, 'errores' => ['El permiso no existe o ya fue resuelto previamente.']];
            }

            // Agregué trazabilidad obligatoria dentro de la misma transacción.
            if (!auditoria_registrar('RECHAZAR_PERMISO', [
                'tabla_afectada' => 'permisos_practicante',
                'id_registro_afectado' => $idPermiso,
                'valores_anteriores' => $permiso,
                'valores_nuevos' => [
                    'estado'          => 'RECHAZADO',
                    'motivo_rechazo'  => $motivoRechazo,
                    'revisado_por'    => $idUsuarioRevisor,
                ],
            ])) {
                throw new RuntimeException('No se pudo auditar el rechazo del permiso.');
            }
            db()->commit();

            return ['ok' => true, 'errores' => []];
        } catch (Throwable $e) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            error_log('Error al rechazar permiso: ' . $e->getMessage());
            return ['ok' => false, 'errores' => ['Ocurrió un error al registrar el rechazo del permiso.']];
        }
    }
}

// Trasladé permisos_gestion_render_supervisor a la capa de presentación en fase 4.
