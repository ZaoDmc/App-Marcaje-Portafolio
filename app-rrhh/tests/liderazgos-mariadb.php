<?php
declare(strict_types=1);

// Agregué idempotencia y concurrencia real únicamente sobre una base E2E local, nunca asistencia.
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
$root=dirname(__DIR__); $base=realpath($root.'/scratch/phase8'); $file=realpath($argv[1]??'');
if ($base===false || $file===false || !str_starts_with($file,$base.DIRECTORY_SEPARATOR)) { throw new RuntimeException('Fixture fuera de scratch E2E.'); }
$f=json_decode(file_get_contents($file),true,512,JSON_THROW_ON_ERROR);
if (!preg_match('/^rrhh_e2e_[a-f0-9]{12}$/',$f['database']) || !in_array($f['db_host'],['localhost','127.0.0.1'],true)) { throw new RuntimeException('Base ficticia local requerida.'); }
$fixture=new PDO("mysql:host={$f['db_host']};port={$f['db_port']};dbname={$f['database']};charset=utf8mb4",$f['db_user'],$f['db_pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
function db(): PDO { return $GLOBALS['fixture']; }
ini_set('session.save_path',$f['directory']);session_id('lider-maria-'.bin2hex(random_bytes(8)));session_start();
require_once $root.'/src/Services/LiderazgoService.php';
$actor=(int)$fixture->query('SELECT id_usuario FROM usuarios_sistema WHERE email="admin@example.test"')->fetchColumn();
$rol=(int)$fixture->query('SELECT id_rol FROM roles_sistema WHERE nombre="ADMIN"')->fetchColumn();
$_SESSION=['id_usuario'=>$actor,'id_rol_activo'=>$rol,'rol_activo'=>'ADMIN'];
if (($argv[2]??'')==='--worker') {
    // El segundo proceso avisa antes de intentar el bloqueo que mantiene la primera conexión.
    $fixture->exec('SET innodb_lock_wait_timeout=10');
    file_put_contents($f['directory'].'/lider-worker-ready','1');
    try { liderazgos_guardar((int)$argv[3],(int)$argv[4],0,'Solicitud concurrente'); exit(1); }
    catch (DomainException $e) { if (!str_contains($e->getMessage(),'cambió')) { throw $e; } echo 'RECHAZO_OBSOLETO'; exit; }
}
$checks=0;
function mariaLiderCheck(bool $ok,string $message): void {
    if (!$ok) { throw new RuntimeException($message); } $GLOBALS['checks']++;
}
// Repetí el parche sobre la instalación poblada y comparé sus conteos y definición generada.
$counts=[];
foreach ($fixture->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
    $counts[$table]=(int)$fixture->query('SELECT COUNT(*) FROM `'.str_replace('`','``',$table).'`')->fetchColumn();
}
$ddl=$fixture->query('SHOW CREATE TABLE liderazgos_area')->fetch(PDO::FETCH_NUM)[1];
// Actualicé el nombre del parche 009; continúo comprobando su repetición sin alterar datos.
$migration=file_get_contents($root.'/database/2026-10-19_patch_liderazgos_area_009.sql');$fixture->exec($migration);$fixture->exec($migration);
foreach ($counts as $table=>$count) { mariaLiderCheck((int)$fixture->query('SELECT COUNT(*) FROM `'.str_replace('`','``',$table).'`')->fetchColumn()===$count,'La migración alteró datos.'); }
mariaLiderCheck($ddl===$fixture->query('SHOW CREATE TABLE liderazgos_area')->fetch(PDO::FETCH_NUM)[1],'La definición cambió al repetir el parche.');

// Preparé integrantes ficticios para los formularios y otro equipo para competir por un mismo liderazgo.
$s=$fixture->prepare('INSERT INTO areas_practica (nombre) VALUES (?)');
$s->execute(['Notas E2E '.$f['id']]);$area=(int)$fixture->lastInsertId();
$s->execute(['Concurrencia E2E '.$f['id']]);$areaConcurrente=(int)$fixture->lastInsertId();
$estado=(int)$fixture->query('SELECT id_estado_practica FROM estados_practica WHERE nombre="EN_PROGRESO"')->fetchColumn();
$practicas=[1];
foreach (['Segundo','Tercero','Cuarto'] as $nombre) {
    $s=$fixture->prepare('INSERT INTO practicantes (nombre_1,apellido_paterno,email) VALUES (?,"Ficticio",?)');$s->execute([$nombre,strtolower($nombre).'@example.test']);$persona=(int)$fixture->lastInsertId();
    $s=$fixture->prepare('INSERT INTO practicas (id_practicante,id_estado_practica,horas_requeridas) VALUES (?,?,360)');$s->execute([$persona,$estado]);$practicas[]=(int)$fixture->lastInsertId();
}
// La fixture completa puede traer pertenencias de casos anteriores; cerré solo las de la práctica ficticia 1.
$fixture->exec('UPDATE historial_areas_practicante SET fecha_fin=CURRENT_DATE WHERE id_practica=1 AND fecha_fin IS NULL');
foreach ($practicas as $index=>$practica) {
    $s=$fixture->prepare('INSERT INTO historial_areas_practicante (id_practica,id_area,fecha_inicio,asignado_por) VALUES (?,?,?,?)');$s->execute([$practica,$index<2?$area:$areaConcurrente,date('Y-m-d'),$actor]);
}
$fixture->beginTransaction();
$s=$fixture->prepare('SELECT id_area FROM areas_practica WHERE id_area=? FOR UPDATE');$s->execute([$areaConcurrente]);
$pipes=[];
$worker=proc_open([PHP_BINARY,__FILE__,$file,'--worker',(string)$areaConcurrente,(string)$practicas[3]],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$root);
if (!is_resource($worker)) { throw new RuntimeException('No se pudo crear el proceso concurrente.'); }
fclose($pipes[0]);
try {
    $deadline=microtime(true)+5;
    while (!is_file($f['directory'].'/lider-worker-ready') && microtime(true)<$deadline) { usleep(20000); }
    mariaLiderCheck(is_file($f['directory'].'/lider-worker-ready'),'El segundo proceso no llegó.');usleep(250000);
    mariaLiderCheck(proc_get_status($worker)['running'],'El segundo proceso no esperó el bloqueo.');
    $s=$fixture->prepare('INSERT INTO liderazgos_area (id_area,id_practica,fecha_inicio,asignado_por,motivo_designacion) VALUES (?,?,?,?,?)');$s->execute([$areaConcurrente,$practicas[2],date('Y-m-d H:i:s'),$actor,'Primera transacción de prueba']);
    $fixture->commit();
} finally { if ($fixture->inTransaction()) { $fixture->rollBack(); } }
$output=stream_get_contents($pipes[1]);$errors=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($worker);
mariaLiderCheck($exit===0 && $errors==='' && $output==='RECHAZO_OBSOLETO','El formulario concurrente no se rechazó correctamente.');
$s=$fixture->prepare('SELECT COUNT(*) FROM liderazgos_area WHERE id_area=? AND fecha_fin IS NULL');$s->execute([$areaConcurrente]);mariaLiderCheck((int)$s->fetchColumn()===1,'Hay líderes vigentes duplicados.');
$duplicate=false;
try {
    $s=$fixture->prepare('INSERT INTO liderazgos_area (id_area,id_practica,fecha_inicio,asignado_por,motivo_designacion) VALUES (?,?,?,?,?)');$s->execute([$areaConcurrente,$practicas[3],date('Y-m-d H:i:s'),$actor,'Duplicado directo']);
} catch (PDOException $e) { $duplicate=$e->getCode()==='23000'; }
mariaLiderCheck($duplicate,'El índice generado no rechazó el duplicado.');
$f['liderazgo']=['area'=>$area,'otra_area'=>$areaConcurrente,'primera'=>$practicas[0],'segunda'=>$practicas[1],'ajena'=>$practicas[2]];
file_put_contents($file,json_encode($f,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
echo "Liderazgos MariaDB: $checks comprobaciones correctas.\n";
