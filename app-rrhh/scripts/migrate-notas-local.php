<?php

declare(strict_types=1);

// Agregué preparación local reproducible, con respaldo completo previo y sin destinos de producción.
if (PHP_SAPI!=='cli' || ($argv[1]??'')!=='--apply') { exit("Uso: php scripts/migrate-notas-local.php --apply\n"); }
require_once dirname(__DIR__).'/src/bootstrap.php';
if (APP_ENV==='production' || !in_array(DB_HOST,['localhost','127.0.0.1'],true) || !preg_match('/^[a-zA-Z0-9_]+$/D',DB_NAME)) {
    throw new RuntimeException('Esta herramienta solo admite la base local del entorno de desarrollo.');
}
$pdo=db();
if ($pdo->query("SHOW TABLES LIKE 'liderazgos_area'")->fetchColumn()===false) {
    throw new RuntimeException('Aplica y verifica primero la migración 009 de liderazgo.');
}
$dump=getenv('MYSQLDUMP_BINARY') ?: dirname(PHP_BINARY,2).'/mysql/bin/mysqldump.exe';
if (!is_file($dump)) { throw new RuntimeException('Define MYSQLDUMP_BINARY con la ruta de mariadb-dump/mysqldump local.'); }
$directory=APP_ROOT.'/scratch/notas-migration-'.date('Ymd-His').'-'.bin2hex(random_bytes(3));
if (!mkdir($directory,0700,true)) { throw new RuntimeException('No fue posible crear el directorio de respaldo.'); }
$backup=$directory.'/antes.sql';
// Pasé la contraseña por entorno al proceso hijo, sin incluirla en comandos, registros ni salida.
$environment=array_merge(getenv(),['MYSQL_PWD'=>DB_PASS]);
$pipes=[];
$process=proc_open([$dump,'--host='.DB_HOST,'--port='.DB_PORT,'--user='.DB_USER,'--single-transaction','--quick',
    '--routines','--events','--triggers','--hex-blob','--default-character-set=utf8mb4','--result-file='.$backup,DB_NAME],
    [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,APP_ROOT,$environment);
if (!is_resource($process)) { throw new RuntimeException('No se pudo iniciar el respaldo. No se ejecutaron migraciones.'); }
fclose($pipes[0]); stream_get_contents($pipes[1]); $error=stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
$exit=proc_close($process);
if ($exit!==0 || !is_file($backup) || filesize($backup)===0) {
    file_put_contents($directory.'/dump-error.log',$error);
    throw new RuntimeException('Falló el respaldo. No se ejecutaron migraciones; revisa el registro local de ese respaldo.');
}
file_put_contents($directory.'/antes.sha256',hash_file('sha256',$backup)."  antes.sql\n");
// Comparé las tablas anteriores sin imprimir sus registros ni copiar datos a documentación versionada.
$antes=[];
foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $tabla) {
    $fila=$pdo->query('CHECKSUM TABLE `'.str_replace('`','``',$tabla).'` EXTENDED')->fetch(PDO::FETCH_NUM);
    if ($fila[1]===null) { throw new RuntimeException('No fue posible comprobar una tabla previa. No se ejecutaron migraciones.'); }
    $antes[$tabla]=$fila[1];
}
foreach (['2026-10-19_patch_notas_semanales_010.sql','2026-10-19_patch_rubricas_configurables_011.sql'] as $parche) {
    $pdo->exec(file_get_contents(APP_ROOT.'/database/'.$parche));
}
foreach ($antes as $tabla=>$checksum) {
    $actual=$pdo->query('CHECKSUM TABLE `'.str_replace('`','``',$tabla).'` EXTENDED')->fetch(PDO::FETCH_NUM)[1];
    if ($checksum!==$actual) { throw new RuntimeException('Cambió el contenido de una tabla previa; conserva el respaldo y revisa antes de continuar.'); }
}
file_put_contents($directory.'/resultado.json',json_encode(['fecha'=>date(DATE_ATOM),'tablas_previas_conservadas'=>count($antes),
    'parches'=>['010','011'],'respaldo_sha256'=>hash_file('sha256',$backup)],JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
echo 'Notas local: migraciones 010/011 aplicadas; '.count($antes).' tablas previas conservadas. Respaldo: '.$directory."\n";
