<?php

declare(strict_types=1);

// Agregué lectura de resultados limitada a una fixture creada en scratch y
// a su base rrhh_e2e_*. Nunca acepta nombres de tablas/SQL del navegador.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$root = realpath(dirname(__DIR__) . '/scratch/phase8');
$file = realpath($argv[1] ?? '');
if ($root === false || $file === false || !str_starts_with($file, $root . DIRECTORY_SEPARATOR)) {
    throw new RuntimeException('Fixture fuera del entorno E2E.');
}
$f = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
if (!preg_match('/^rrhh_e2e_[a-f0-9]{12}$/', $f['database']) || !in_array($f['db_host'], ['127.0.0.1','localhost'], true)) {
    throw new RuntimeException('Base E2E inválida.');
}
$pdo = new PDO("mysql:host={$f['db_host']};port={$f['db_port']};dbname={$f['database']};charset=utf8mb4", $f['db_user'], $f['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
// Agregué lectura del calendario y ledger de recuperación en la base ficticia.
$tables = ['roles_sistema', 'carreras', 'feriados', 'recuperaciones_permiso', 'acompanamiento', 'practicantes', 'horarios_practicante', 'detalle_horario_practicante', 'permisos_practicante', 'jornadas_asistencia', 'marcas_asistencia', 'auditoria_cambios', 'excepciones_horario_practicante', 'documentos_practicante', 'tokens_usuario', 'sesiones_usuario'];
$state = [];
// Incorporé saldos y reservas nuevos para verificar las escrituras de los formularios reales.
$tables=array_merge($tables,['tiempos_excepcionales','compensaciones_tiempo','politica_tiempo_excepcional','extras_historicas']);
// Incluí el historial organizacional nuevo en la lectura limitada a la fixture E2E.
$tables=array_merge($tables,['liderazgos_area']);
foreach ($tables as $table) {
    // Acompañamiento crea su tabla al abrir el módulo; su ausencia inicial
    // no debe impedir inspeccionar las otras operaciones independientes.
    if ($table === 'acompanamiento' && $pdo->query("SHOW TABLES LIKE 'acompanamiento'")->fetchColumn() === false) {
        $state[$table] = [];
        continue;
    }
    $state[$table] = $pdo->query('SELECT * FROM ' . $table)->fetchAll(PDO::FETCH_ASSOC);
}
echo json_encode($state, JSON_THROW_ON_ERROR);
