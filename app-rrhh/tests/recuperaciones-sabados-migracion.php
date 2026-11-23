<?php
declare(strict_types=1);
// Verifiqué solo una base aleatoria local: no cargo el .env ni uso la base habitual.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$host=getenv('E2E_DB_HOST')?:'127.0.0.1';$port=getenv('E2E_DB_PORT')?:'3306';
if(!in_array($host,['127.0.0.1','localhost'],true)||!ctype_digit($port)){throw new RuntimeException('Solo MariaDB local.');}
$pdo=new PDO("mysql:host=$host;port=$port;charset=utf8mb4",getenv('E2E_DB_USER')?:'root',getenv('E2E_DB_PASS')?:'',[
 PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$base='rrhh_e2e_'.bin2hex(random_bytes(6));$checks=0;
$check=static function(bool $ok,string $msg) use (&$checks):void {if(!$ok){throw new RuntimeException($msg);} $checks++;};
try {
 $pdo->exec("CREATE DATABASE `$base` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");$pdo->exec("USE `$base`");
 $pdo->exec('CREATE TABLE permisos_practicante (id_permiso INT PRIMARY KEY) ENGINE=InnoDB;
 CREATE TABLE usuarios_sistema (id_usuario INT PRIMARY KEY) ENGINE=InnoDB; INSERT INTO permisos_practicante VALUES (1)');
 preg_match('/CREATE TABLE IF NOT EXISTS recuperaciones_permiso \([\s\S]*?ENGINE=InnoDB[^;]*;/',file_get_contents(__DIR__.'/../database/schema.sql'),$m);
 $tabla=$m[0];$anterior=preg_replace('/^.*(?:politica_recuperacion TINYINT|solicitado_utc DATETIME).*\R/m','',$tabla);$pdo->exec($anterior);
 $pdo->exec("INSERT INTO recuperaciones_permiso (id_permiso,fecha,tipo,hora_inicio,hora_fin,minutos_planificados,minutos_validados,estado) VALUES (1,'2026-10-13','COLACION','13:00','14:00',60,60,'VALIDADA')");
 $patch=file_get_contents(__DIR__.'/../database/2026-11-06_patch_recuperaciones_sabados_015.sql');$pdo->exec($patch);$pdo->exec($patch);
 $r=$pdo->query('SELECT * FROM recuperaciones_permiso')->fetch();
 $check((int)$r['politica_recuperacion']===1 && $r['solicitado_utc']===null,'Planes anteriores conservan política sin inventar fecha UTC');
 $check($r['estado']==='VALIDADA' && (int)$r['minutos_validados']===60 && $r['tipo']==='COLACION','Migración conserva crédito y modalidad anterior');
 $columnas=[];foreach(['politica_recuperacion','solicitado_utc'] as $campo){$columnas[$campo]=$pdo->query("SHOW COLUMNS FROM recuperaciones_permiso LIKE '$campo'")->fetch();}
 $pdo->exec('DROP TABLE recuperaciones_permiso');$pdo->exec($tabla);
 foreach($columnas as $campo=>$columna){$check($columna===$pdo->query("SHOW COLUMNS FROM recuperaciones_permiso LIKE '$campo'")->fetch(),'Schema y parche coinciden en '.$campo);}
 echo "Recuperaciones: $checks comprobaciones MariaDB correctas.\n";
} finally {$pdo->exec("DROP DATABASE IF EXISTS `$base`");}
