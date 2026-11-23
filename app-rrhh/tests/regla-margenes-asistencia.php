<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/Services/ReglasAsistenciaService.php';

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
$_SESSION['id_usuario'] = 1;
$_SESSION['rol_activo'] = 'ADMIN';

$fallos = 0;
$total = 0;

function check(bool $condicion, string $descripcion): void
{
    global $fallos, $total;
    $total++;
    if ($condicion) {
        echo "✓ OK: $descripcion\n";
    } else {
        $fallos++;
        echo "✗ FALLO: $descripcion\n";
    }
}

echo "=== Comprobaciones de Tarea 2: Margen de 15 min y Marcajes Automáticos ===\n";

// -------------------------------------------------------------
// 1. Constantes de configuración
// -------------------------------------------------------------
check(defined('ASISTENCIA_MARGEN_ANTICIPACION_MIN') && ASISTENCIA_MARGEN_ANTICIPACION_MIN === 15, 'Margen de anticipación configurado en 15 minutos');
check(defined('ASISTENCIA_MARGEN_AUTO_COLACION_MIN') && ASISTENCIA_MARGEN_AUTO_COLACION_MIN === 15, 'Margen de auto-retorno colación configurado en 15 minutos');
check(defined('ASISTENCIA_MARGEN_AUTO_SALIDA_MIN') && ASISTENCIA_MARGEN_AUTO_SALIDA_MIN === 30, 'Margen de auto-salida jornada configurado en 30 minutos');

// -------------------------------------------------------------
// 2. Ventana de Entrada (Turno 14:00 - 22:00)
// -------------------------------------------------------------
$horarioTarde = [
    'entrada_esperada' => '14:00:00',
    'salida_esperada' => '22:00:00',
    'colacion_inicio' => '17:00:00',
    'colacion_fin' => '18:00:00',
];
$fecha = '2026-11-11';

// A las 10:00 AM (4 horas antes) -> Bloqueado por anticipado
$vEntrada10 = asistencia_evaluar_ventana_marca('ENTRADA', $horarioTarde, $fecha, '10:00:00');
check(!$vEntrada10['habilitado'], 'Entrada a las 10:00 AM debe estar bloqueada para turno de 14:00');
check($vEntrada10['es_anticipado'], 'Debe marcarse como intento anticipado');
check($vEntrada10['hora_apertura'] === '13:45', 'Hora de apertura debe ser exactamente 13:45 (15 min antes)');

// A las 13:44 (16 min antes) -> Bloqueado
$vEntrada1344 = asistencia_evaluar_ventana_marca('ENTRADA', $horarioTarde, $fecha, '13:44:59');
check(!$vEntrada1344['habilitado'], 'Entrada a las 13:44:59 debe estar bloqueada');

// A las 13:45 (15 min antes) -> Habilitado
$vEntrada1345 = asistencia_evaluar_ventana_marca('ENTRADA', $horarioTarde, $fecha, '13:45:00');
check($vEntrada1345['habilitado'], 'Entrada a las 13:45:00 debe estar habilitada');
check(!$vEntrada1345['es_anticipado'], 'No debe marcarse como anticipado a las 13:45');

// A las 14:00 (Puntual) -> Habilitado
$vEntrada1400 = asistencia_evaluar_ventana_marca('ENTRADA', $horarioTarde, $fecha, '14:00:00');
check($vEntrada1400['habilitado'], 'Entrada puntual a las 14:00 debe estar habilitada');

// A las 14:25 (Con atraso) -> Habilitado (el sistema permite marcar entrada tarde con flag atraso)
$vEntrada1425 = asistencia_evaluar_ventana_marca('ENTRADA', $horarioTarde, $fecha, '14:25:00');
check($vEntrada1425['habilitado'], 'Entrada con atraso a las 14:25 debe estar habilitada');

// -------------------------------------------------------------
// 3. Ventana de Salida a Colación (Colación 17:00 - 18:00)
// -------------------------------------------------------------
// A las 16:30 (30 min antes) -> Bloqueado
$vCol1630 = asistencia_evaluar_ventana_marca('ENTRADA_COLACION', $horarioTarde, $fecha, '16:30:00');
check(!$vCol1630['habilitado'], 'Salida a colación a las 16:30 debe estar bloqueada');
check($vCol1630['hora_apertura'] === '16:45', 'Apertura de colación debe ser 16:45');

// A las 16:45 (15 min antes) -> Habilitado
$vCol1645 = asistencia_evaluar_ventana_marca('ENTRADA_COLACION', $horarioTarde, $fecha, '16:45:00');
check($vCol1645['habilitado'], 'Salida a colación a las 16:45 debe estar habilitada');

// -------------------------------------------------------------
// 4. Ventana de Regreso de Colación (Colación 17:00 - 18:00)
// -------------------------------------------------------------
// A las 17:30 (30 min antes de las 18:00) -> Bloqueado
$vRet1730 = asistencia_evaluar_ventana_marca('SALIDA_COLACION', $horarioTarde, $fecha, '17:30:00');
check(!$vRet1730['habilitado'], 'Regreso de colación a las 17:30 debe estar bloqueado');
check($vRet1730['hora_apertura'] === '17:45', 'Apertura de regreso de colación debe ser 17:45');

// A las 17:45 (15 min antes) -> Habilitado
$vRet1745 = asistencia_evaluar_ventana_marca('SALIDA_COLACION', $horarioTarde, $fecha, '17:45:00');
check($vRet1745['habilitado'], 'Regreso de colación a las 17:45 debe estar habilitada');

// -------------------------------------------------------------
// 5. Ventana de Salida de Jornada (Salida 22:00)
// -------------------------------------------------------------
// A las 21:00 (1 hora antes) -> Bloqueado
$vSalida2100 = asistencia_evaluar_ventana_marca('SALIDA', $horarioTarde, $fecha, '21:00:00');
check(!$vSalida2100['habilitado'], 'Salida de jornada a las 21:00 debe estar bloqueada');
check($vSalida2100['hora_apertura'] === '21:45', 'Apertura de salida debe ser 21:45');

// A las 21:45 (15 min antes) -> Habilitado
$vSalida2145 = asistencia_evaluar_ventana_marca('SALIDA', $horarioTarde, $fecha, '21:45:00');
check($vSalida2145['habilitado'], 'Salida de jornada a las 21:45 debe estar habilitada');

// -------------------------------------------------------------
// 6. Horario sin colación
// -------------------------------------------------------------
$horarioSinColacion = [
    'entrada_esperada' => '10:00:00',
    'salida_esperada' => '14:00:00',
    'colacion_inicio' => null,
    'colacion_fin' => null,
];
$vColNula = asistencia_evaluar_ventana_marca('ENTRADA_COLACION', $horarioSinColacion, $fecha, '12:00:00');
check(!$vColNula['habilitado'], 'Colación en horario sin colación debe estar deshabilitada');
check(!$vColNula['es_anticipado'], 'No debe considerarse anticipada sino no contemplada');

// -------------------------------------------------------------
// 7. Prueba en Base de Datos: Marcajes Automáticos
// -------------------------------------------------------------
try {
    // Buscar una práctica activa de pruebas
    $stmt = db()->query('SELECT id_practica FROM practicas WHERE activo = 1 LIMIT 1');
    $idPracticaTest = (int) $stmt->fetchColumn();

    if ($idPracticaTest > 0) {
        $fechaTest = '2026-11-04'; // Miércoles pasado de prueba para no disparar regla de marcas futuras
        // Limpiar jornada de prueba previa si existiera
        $stmtJornada = db()->prepare('SELECT id_jornada FROM jornadas_asistencia WHERE id_practica = ? AND fecha = ?');
        $stmtJornada->execute([$idPracticaTest, $fechaTest]);
        $idJornadaExistente = $stmtJornada->fetchColumn();
        if ($idJornadaExistente !== false) {
            db()->prepare('DELETE FROM marcas_asistencia WHERE id_jornada = ?')->execute([(int) $idJornadaExistente]);
            db()->prepare('DELETE FROM jornadas_asistencia WHERE id_jornada = ?')->execute([(int) $idJornadaExistente]);
        }

        // Crear jornada de prueba
        $jornadaTest = asistencia_obtener_o_crear_jornada($idPracticaTest, $fechaTest);
        $idJornadaTest = (int) $jornadaTest['id_jornada'];

        $horarioTest = [
            'id_horario' => 1,
            'entrada_esperada' => '09:00:00',
            'salida_esperada' => '18:00:00',
            'colacion_inicio' => '13:00:00',
            'colacion_fin' => '14:00:00',
            'horas_planificadas' => 8.0,
        ];

        // 7.1 Registrar Entrada
        asistencia_registrar_marca($idJornadaTest, 'ENTRADA', "$fechaTest 09:00:00", 'Entrada test', null, $horarioTest);
        // Registrar Salida a Colación a las 13:00
        asistencia_registrar_marca($idJornadaTest, 'ENTRADA_COLACION', "$fechaTest 13:00:00", 'Colación test', null, $horarioTest);

        // A las 14:10 (10 min tras fin colación, límite es +15m) -> NO debe auto-cerrar colación aún
        $auto1410 = asistencia_procesar_marcajes_automaticos_jornada($idJornadaTest, $horarioTest, $fechaTest, '14:10:00');
        check(!$auto1410['procesado'], 'A las 14:10 (10 min tras colación_fin) no debe auto-cerrar colación todavía');

        // A las 14:15:01 (15 min y 1 seg tras fin colación) -> DEBE auto-cerrar colación
        $auto1415 = asistencia_procesar_marcajes_automaticos_jornada($idJornadaTest, $horarioTest, $fechaTest, '14:15:01');
        check($auto1415['procesado'], 'A las 14:15 debe procesar auto-retorno de colación');
        check($auto1415['auto_colacion'], 'La bandera auto_colacion debe ser true');

        $marcasActualizadas = asistencia_marcas_vigentes_jornada($idJornadaTest);
        $marcaAutoCol = asistencia_marca_por_codigo($marcasActualizadas, 'SALIDA_COLACION');
        check($marcaAutoCol !== null, 'Marca SALIDA_COLACION debe existir en la base de datos');
        check(str_contains((string) ($marcaAutoCol['observacion'] ?? ''), 'automático'), 'Observación debe registrar que fue automático');
        check($marcaAutoCol['creada_por'] === null, 'creada_por debe ser NULL en marca de sistema');

        // 7.2 Probar Auto-cierre de Jornada (Salida esperada: 18:00, límite +30m = 18:30)
        // A las 18:25 -> NO debe cerrar aún
        $auto1825 = asistencia_procesar_marcajes_automaticos_jornada($idJornadaTest, $horarioTest, $fechaTest, '18:25:00');
        check(!$auto1825['procesado'], 'A las 18:25 (25 min tras salida) no debe auto-cerrar salida');

        // A las 18:30:01 -> DEBE cerrar automáticamente
        $auto1830 = asistencia_procesar_marcajes_automaticos_jornada($idJornadaTest, $horarioTest, $fechaTest, '18:30:01');
        check($auto1830['procesado'], 'A las 18:30:01 debe procesar auto-cierre de salida');
        check($auto1830['auto_salida'], 'La bandera auto_salida debe ser true');

        $marcasFinales = asistencia_marcas_vigentes_jornada($idJornadaTest);
        $marcaAutoSalida = asistencia_marca_por_codigo($marcasFinales, 'SALIDA');
        check($marcaAutoSalida !== null, 'Marca SALIDA debe existir en la base de datos');
        check(str_contains((string) ($marcaAutoSalida['observacion'] ?? ''), 'automático'), 'Observación de salida debe registrar cierre automático');

        // Verificar que la jornada recalculó su estado y horas
        $jornadaFinal = asistencia_jornada_por_id($idJornadaTest);
        check(in_array($jornadaFinal['estado_jornada'] ?? '', ['VALIDA', 'REVISADA', 'CORREGIDA'], true), 'Jornada debe quedar en estado válido o revisada tras auto-cierre');
        check((float) ($jornadaFinal['horas_calculadas'] ?? 0) > 0, 'Jornada debe acumular horas trabajadas correctamente');

        // Limpiar jornada de prueba
        db()->prepare('DELETE FROM marcas_asistencia WHERE id_jornada = ?')->execute([$idJornadaTest]);
        db()->prepare('DELETE FROM jornadas_asistencia WHERE id_jornada = ?')->execute([$idJornadaTest]);
    }
} catch (Throwable $e) {
    echo "Error en pruebas con BD: " . $e->getMessage() . "\n";
    $fallos++;
}

echo "\nTotal comprobaciones: $total | Fallos: $fallos\n";
exit($fallos === 0 ? 0 : 1);
