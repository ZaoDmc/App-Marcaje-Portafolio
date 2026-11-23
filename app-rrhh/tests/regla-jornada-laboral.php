<?php

declare(strict_types=1);

// Test unitario para Tarea 1: regla de jornada laboral y bloqueo de días no laborables / sábados.
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/Services/ReglasAsistenciaService.php';
require_once __DIR__ . '/../src/Services/RecuperacionesService.php';

$checks = 0;
$failures = [];

function check_jornada(bool $condicion, string $mensaje): void
{
    global $checks, $failures;
    $checks++;
    if (!$condicion) {
        $failures[] = $mensaje;
        echo "❌ FALLO: $mensaje\n";
    } else {
        echo "✓ OK: $mensaje\n";
    }
}

echo "=== Comprobaciones de Tarea 1: Bloqueo de Jornada y Sábados ===\n";

// 1. Domingo: siempre bloqueado
$evalDomingo = asistencia_evaluar_dia_laboral(2, '2026-11-15'); // Domingo
check_jornada($evalDomingo['habilitado'] === false, 'Domingo debe estar estrictamente bloqueado');
check_jornada($evalDomingo['tipo_jornada'] === 'NO_LABORAL', 'Domingo tiene tipo NO_LABORAL');
check_jornada(str_contains((string) $evalDomingo['motivo_bloqueo'], 'domingo'), 'Domingo contiene mensaje explicativo');

// 2. Sábado sin recuperación: estrictamente bloqueado
$evalSabadoSin = asistencia_evaluar_dia_laboral(2, '2026-11-21'); // Sábado sin tramos
check_jornada($evalSabadoSin['habilitado'] === false, 'Sábado sin recuperación debe estar bloqueado');
check_jornada($evalSabadoSin['tipo_jornada'] === 'NO_LABORAL', 'Sábado sin recuperación tiene tipo NO_LABORAL');
check_jornada(str_contains((string) $evalSabadoSin['motivo_bloqueo'], 'sábado'), 'Sábado explica que requiere recuperación aprobada');

// 3. Sábado CON recuperación aprobada: habilitado y toma el horario de la recuperación
db()->beginTransaction();
try {
    // Insertamos permiso ficticio aprobado con tramo sabatino programado
    $stmtPerm = db()->prepare('INSERT INTO permisos_practicante (id_practica, fecha_ausencia, tipo_ausencia, motivo, estado, fecha_recuperacion, hora_inicio_recuperacion, hora_fin_recuperacion, horas_a_recuperar) VALUES (2, "2026-11-06", "COMPLETA", "Ausencia test", "APROBADO", "2026-11-28", "10:00:00", "18:00:00", 8.0)');
    $stmtPerm->execute();
    $idPermisoTest = (int) db()->lastInsertId();

    $stmtTramo = db()->prepare('INSERT INTO recuperaciones_permiso (id_permiso, fecha, tipo, hora_inicio, hora_fin, minutos_planificados, estado) VALUES (?, "2026-11-28", "SABADO", "10:00:00", "18:00:00", 480, "PROGRAMADA")');
    $stmtTramo->execute([$idPermisoTest]);

    $evalSabadoCon = asistencia_evaluar_dia_laboral(2, '2026-11-28');
    check_jornada($evalSabadoCon['habilitado'] === true, 'Sábado con recuperación aprobada debe estar habilitado');
    check_jornada($evalSabadoCon['tipo_jornada'] === 'RECUPERACION_SABADO', 'Tipo de jornada es RECUPERACION_SABADO');
    check_jornada($evalSabadoCon['horario']['entrada_esperada'] === '10:00:00', 'Entrada esperada del sábado tomada de la recuperación');
    check_jornada($evalSabadoCon['horario']['salida_esperada'] === '18:00:00', 'Salida esperada del sábado tomada de la recuperación');
} finally {
    db()->rollBack();
}

// 3b. Sábado SIN permiso pero CON excepción activa de ASISTENCIA_EXTRAORDINARIA: habilitado con horario de la excepción
db()->beginTransaction();
try {
    $idHorarioTest = (int) db()->query('SELECT id_horario FROM horarios_practicante WHERE id_practica = 2 AND activo = 1 LIMIT 1')->fetchColumn();
    check_jornada($idHorarioTest > 0, 'Práctica 2 cuenta con horario activo para probar excepción sabatina');
    if ($idHorarioTest > 0) {
        $stmtExc = db()->prepare('INSERT INTO excepciones_horario_practicante (id_horario, fecha, tipo_excepcion, entrada_esperada, salida_esperada, horas_planificadas, observacion, activo) VALUES (?, "2026-11-28", "ASISTENCIA_EXTRAORDINARIA", "10:00:00", "14:00:00", 4.00, "Prueba sabado excepcion", 1)');
        $stmtExc->execute([$idHorarioTest]);

        $evalSabadoExc = asistencia_evaluar_dia_laboral(2, '2026-11-28');
        check_jornada($evalSabadoExc['habilitado'] === true, 'Sábado con excepción ASISTENCIA_EXTRAORDINARIA debe estar habilitado');
        check_jornada($evalSabadoExc['tipo_jornada'] === 'EXCEPCION_SABADO', 'Tipo de jornada es EXCEPCION_SABADO');
        check_jornada(($evalSabadoExc['horario']['entrada_esperada'] ?? null) === '10:00:00', 'Entrada esperada del sábado tomada de la excepción');
        check_jornada(($evalSabadoExc['horario']['salida_esperada'] ?? null) === '14:00:00', 'Salida esperada del sábado tomada de la excepción');
    }
} finally {
    db()->rollBack();
}

// 4. Día de semana con horario pactado normal (Martes 2026-11-10 para Camila Soto, practica 2)
$evalMartesNormal = asistencia_evaluar_dia_laboral(2, '2026-11-10');
check_jornada($evalMartesNormal['habilitado'] === true, 'Martes con jornada regular debe estar habilitado');
check_jornada($evalMartesNormal['tipo_jornada'] === 'REGULAR', 'Tipo de jornada es REGULAR');
check_jornada(!empty($evalMartesNormal['horario']['entrada_esperada']), 'Horario cuenta con entrada esperada');

// 5. Día de semana donde el practicante NO tiene turno asignado (Martes 2026-11-10 para usuario sin martes, practica 13)
$evalSinMartes = asistencia_evaluar_dia_laboral(13, '2026-11-10');
check_jornada($evalSinMartes['habilitado'] === false, 'Día de semana sin turno programado debe estar bloqueado');
check_jornada($evalSinMartes['tipo_jornada'] === 'NO_LABORAL', 'Tipo de jornada es NO_LABORAL');
check_jornada(str_contains((string) $evalSinMartes['motivo_bloqueo'], 'no tienes jornada laboral'), 'Mensaje explica que no tiene jornada programada');

// 6. Día de semana en que ese mismo usuario Sí tiene turno asignado (Miércoles 2026-11-11 para practica 13)
$evalConMiercoles = asistencia_evaluar_dia_laboral(13, '2026-11-11');
check_jornada($evalConMiercoles['habilitado'] === true, 'Mismo usuario en día programado (miércoles) debe estar habilitado');
check_jornada($evalConMiercoles['tipo_jornada'] === 'REGULAR', 'Tipo de jornada en miércoles es REGULAR');

echo "\nTotal comprobaciones: $checks | Fallos: " . count($failures) . "\n";
exit($failures === [] ? 0 : 1);
