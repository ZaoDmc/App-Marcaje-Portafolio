<?php

declare(strict_types=1);

// Pruebas unitarias para el modo solo lectura del practicante al completar el 100% de horas o egresar.
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/Services/ReglasAsistenciaService.php';

$checks = 0;
$failures = [];

function check_lectura(bool $condicion, string $mensaje): void
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

echo "=== Comprobaciones de Modo Solo Lectura (100% horas y egreso) ===\n";

$fila = db()->query(
    'SELECT pr.id_practica, pr.id_practicante, pr.horas_requeridas, pr.horas_previas
     FROM practicas pr
     INNER JOIN estados_practica ep ON ep.id_estado_practica = pr.id_estado_practica
     WHERE pr.activo = 1 AND ep.nombre IN ("PENDIENTE_INICIO", "EN_PROGRESO")
     LIMIT 1'
)->fetch(PDO::FETCH_ASSOC);

check_lectura(is_array($fila), 'Existe una práctica activa en la fixture local para probar');

if (is_array($fila)) {
    $idPractica = (int) $fila['id_practica'];
    $idPracticante = (int) $fila['id_practicante'];

    // 1. Práctica activa bajo el 100% -> modo normal (editable)
    db()->beginTransaction();
    try {
        db()->prepare('UPDATE practicas SET horas_requeridas = 500, horas_previas = 0, fecha_termino_practica = NULL WHERE id_practica = ?')
            ->execute([$idPractica]);

        $estadoNormal = asistencia_estado_solo_lectura_practicante($idPracticante, '2026-11-21');
        check_lectura($estadoNormal['solo_lectura'] === false, 'Práctica activa con menos del 100% no activa modo solo lectura');
        check_lectura($estadoNormal['meta_cumplida'] === false, 'meta_cumplida es false cuando faltan horas');
        check_lectura($estadoNormal['practica_egresada'] === false, 'practica_egresada es false en práctica vigente');

        // 2. Al completar el 100% de horas -> activa modo solo lectura y meta_cumplida
        db()->prepare('UPDATE practicas SET horas_requeridas = 100, horas_previas = 100 WHERE id_practica = ?')
            ->execute([$idPractica]);

        $estado100 = asistencia_estado_solo_lectura_practicante($idPracticante, '2026-11-21');
        check_lectura($estado100['solo_lectura'] === true, 'Al llegar al 100% se activa modo solo lectura');
        check_lectura($estado100['meta_cumplida'] === true, 'meta_cumplida es true al llegar al 100%');
        check_lectura($estado100['porcentaje_avance'] === 100.0, 'porcentaje_avance es 100.0');
        check_lectura(str_contains((string) $estado100['motivo'], '100%'), 'Motivo indica cumplimiento del 100%');

        // 3. Si el supervisor sube horas_requeridas por encima del acumulado -> se rehabilita automáticamente
        db()->prepare('UPDATE practicas SET horas_requeridas = 900 WHERE id_practica = ?')
            ->execute([$idPractica]);

        $estadoRehabilitado = asistencia_estado_solo_lectura_practicante($idPracticante, '2026-11-21');
        check_lectura($estadoRehabilitado['solo_lectura'] === false, 'Al subir horas_requeridas por sobre el acumulado se rehabilita automáticamente');

        // 4. Practicante egresado (activo = 0) -> activa modo solo lectura pero devuelve su última práctica para historial/confeti
        db()->prepare('UPDATE practicas SET activo = 0, horas_requeridas = 100, horas_previas = 100 WHERE id_practica = ?')
            ->execute([$idPractica]);

        $estadoEgresado = asistencia_estado_solo_lectura_practicante($idPracticante, '2026-11-21');
        check_lectura($estadoEgresado['solo_lectura'] === true, 'Practicante egresado (activo=0) queda en modo solo lectura');
        check_lectura($estadoEgresado['practica_egresada'] === true, 'practica_egresada es true cuando activo=0');
        check_lectura(is_array($estadoEgresado['practica']) && (int) $estadoEgresado['practica']['id_practica'] === $idPractica, 'Devuelve la última práctica para conservar resumen 100%, confeti e historial');
    } finally {
        db()->rollBack();
    }
}

echo "\nTotal comprobaciones: $checks | Fallos: " . count($failures) . "\n";
exit($failures === [] ? 0 : 1);
