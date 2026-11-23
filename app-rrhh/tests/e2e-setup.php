<?php

declare(strict_types=1);

// Agregué un entorno real de pruebas con nombre único y datos ficticios.
// No carga .env: solo acepta credenciales E2E del proceso y conexiones locales.
require_once dirname(__DIR__) . '/scripts/release-lib.php';
$root = dirname(__DIR__);
date_default_timezone_set('America/Santiago');
$host = getenv('E2E_DB_HOST') ?: '127.0.0.1';
if (!in_array($host, ['127.0.0.1', 'localhost'], true)) {
    throw new RuntimeException('E2E solo admite MariaDB local.');
}
$user = getenv('E2E_DB_USER') ?: 'root';
$password = getenv('E2E_DB_PASS') ?: '';
$port = getenv('E2E_DB_PORT') ?: '3306';
$pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$id = bin2hex(random_bytes(6));
$database = 'rrhh_e2e_' . $id;
$pdo->exec("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `{$database}`");
// Probé la instalación nueva sin parches: toda estructura debe venir de schema.sql y los datos de sus seeds.
foreach (['schema.sql', 'seed_catalogos.sql'] as $file) {
    // Retiré el BOM de archivos SQL UTF-8 antes de enviarlos mediante PDO.
    $sql = preg_replace('/^\xEF\xBB\xBF/', '', file_get_contents($root . '/database/' . $file));
    $statement = $pdo->query($sql);
    while ($statement->nextRowset()) {}
    $statement->closeCursor();
}
// Comprobé antes de abrir pantallas que no falten tablas que antes se creaban con parches o al entrar a Acompañamiento.
foreach(['permisos_practicante','feriados','recuperaciones_permiso','politica_tiempo_excepcional','extras_historicas','tiempos_excepcionales','compensaciones_tiempo','acompanamiento'] as $tabla){
    if($pdo->query("SHOW TABLES LIKE '$tabla'")->fetchColumn()===false){throw new RuntimeException('Schema incompleto: '.$tabla);}
}
if((int)$pdo->query('SELECT ultima_jornada_historica FROM politica_tiempo_excepcional WHERE id=1')->fetchColumn()!==0){throw new RuntimeException('La instalación vacía debe iniciar con corte cero.');}
// Alineé las fechas del seed con PHP/Chile; schema.sql fija UTC en su sesión.
$pdo->exec("SET time_zone = '" . date('P') . "'");
$pdo->exec("INSERT INTO regiones (nombre_region) VALUES ('Región ficticia E2E'); INSERT INTO comunas (id_region,nombre_comuna) VALUES (1,'Comuna ficticia E2E')");
$pdo->exec("INSERT INTO practicantes (rut_numero,rut_dv,nombre_1,apellido_paterno,email,fecha_nacimiento,sexo,nacionalidad,direccion,numero_direccion,pais,id_region,id_comuna,id_institucion,id_sede,id_carrera,semestre) VALUES ('11111111','1','Prueba','Ficticia','practicante@example.test','2000-01-01','F','Chilena','Dirección ficticia','1','Chile',1,1,1,1,1,'4')");
$pdo->exec("INSERT INTO practicas (id_practicante,id_tipo_practica,id_estado_practica,horas_requeridas,fecha_inicio_practica,fecha_termino_practica) SELECT 1,1,id_estado_practica,360,DATE_SUB(CURDATE(),INTERVAL 30 DAY),DATE_ADD(CURDATE(),INTERVAL 60 DAY) FROM estados_practica WHERE nombre='EN_PROGRESO'");
$pdo->exec("INSERT INTO horarios_practicante (id_practica,nombre_horario,fecha_inicio) VALUES (1,'Horario ficticio E2E',DATE_SUB(CURDATE(),INTERVAL 30 DAY))");
// Incluí hoy si es fin de semana para probar marcaje cualquier día sin
// modificar el horario real del proyecto; queda otro día libre para recuperar.
foreach (array_unique([1,2,3,4,5,(int) date('N')]) as $day) {
    $pdo->exec("INSERT INTO detalle_horario_practicante (id_horario,dia_semana,entrada_esperada,salida_esperada,colacion_inicio,colacion_fin,horas_planificadas) VALUES (1,{$day},'09:00','18:00','13:00','14:00',8)");
}
$plain = 'E2e-' . bin2hex(random_bytes(12)) . '!';
$hash = password_hash($plain, PASSWORD_DEFAULT);
foreach (['admin' => 'ADMIN', 'supervisor' => 'SUPERVISOR', 'practicante' => 'PRACTICANTE'] as $account => $role) {
    $insert = $pdo->prepare('INSERT INTO usuarios_sistema (id_practicante,email,password_hash,estado_cuenta,email_verificado) VALUES (?, ?, ?, "ACTIVO", 1)');
    $insert->execute([$account === 'practicante' ? 1 : null, $account . '@example.test', $hash]);
    $uid = (int) $pdo->lastInsertId();
    $pdo->exec("INSERT INTO usuarios_roles (id_usuario,id_rol) SELECT {$uid},id_rol FROM roles_sistema WHERE nombre='{$role}'");
}
// Agregué una cuenta ficticia con dos roles para probar el selector real.
$insert = $pdo->prepare('INSERT INTO usuarios_sistema (email,password_hash,estado_cuenta,email_verificado) VALUES ("multi@example.test", ?, "ACTIVO", 1)');
$insert->execute([$hash]);
$multiId = (int) $pdo->lastInsertId();
$pdo->exec("INSERT INTO usuarios_roles (id_usuario,id_rol) SELECT {$multiId},id_rol FROM roles_sistema WHERE nombre IN ('ADMIN','SUPERVISOR')");
// Dejé una jornada laborable anterior sin colación para revisar esa regla.
$prior = new DateTimeImmutable('yesterday');
while ((int) $prior->format('N') > 5) {
    $prior = $prior->modify('-1 day');
}
$priorDate = $prior->format('Y-m-d');
$pdo->exec("INSERT INTO jornadas_asistencia (id_practica,fecha,hora_entrada,hora_salida,horas_calculadas,estado_jornada) VALUES (1,'{$priorDate}','{$priorDate} 09:00:00','{$priorDate} 18:00:00',9,'OBSERVADA')");
foreach (['ENTRADA' => '09:00:00', 'SALIDA' => '18:00:00'] as $code => $time) {
    $pdo->exec("INSERT INTO marcas_asistencia (id_jornada,id_tipo_marca,fecha_hora) SELECT 1,id_tipo_marca,'{$priorDate} {$time}' FROM tipos_marca WHERE codigo='{$code}'");
}
$directory = $root . '/scratch/phase8/' . $id;
// Activé el corte tras crear la jornada histórica ficticia; nunca en la base habitual.
// El schema nuevo ya activa una instalación vacía; ajusté solo la fixture para representar una migración con historial.
$pdo->exec('UPDATE politica_tiempo_excepcional SET ultima_jornada_historica=1 WHERE id=1');
// Actualicé la referencia al inventario de parches fechado; la fixture conserva su política histórica.
$sql=file_get_contents($root.'/database/2026-10-15_patch_tiempo_excepcional_008.sql');
$pdo->exec($sql);$pdo->exec($sql);
$backend = $directory . '/backend-rrhh';
$frontend = $directory . '/public_html/portal';
mkdir($backend, 0755, true);
mkdir($frontend, 0755, true);
foreach (['src', 'config'] as $folder) {
    release_copy($root . '/' . $folder, $backend . '/' . $folder, static fn(string $file): bool => str_ends_with($file, '.php'));
}
release_copy($root . '/public', $backend . '/public', 'release_public_allowed');
release_copy($root . '/public', $frontend, 'release_public_allowed');
// Uso vendor de producción ya generado, sin instalar o alterar dependencias.
$vendor = $argv[1] ?? '';
if (!is_file($vendor . '/autoload.php')) {
    throw new RuntimeException('Indicar el vendor de una publicación de fase 7.');
}
release_copy($vendor, $backend . '/vendor', static fn(string $file): bool => true);
$fixture = ['id' => $id, 'directory' => $directory, 'backend' => $backend, 'frontend' => $frontend,
    'database' => $database, 'db_host' => $host, 'db_port' => $port, 'db_user' => $user, 'db_pass' => $password,
    'password' => $plain, 'today' => date('Y-m-d'), 'yesterday' => $priorDate,
    'recovery_weekday' => (int) date('N') === 6 ? 0 : 6];
file_put_contents($directory . '/fixture.json', json_encode($fixture, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo $directory . '/fixture.json';
