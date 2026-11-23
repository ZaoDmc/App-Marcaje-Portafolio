<?php
declare(strict_types=1);
// Ensayé creación y repetición en una base local nueva que se retira al terminar.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$host=getenv('E2E_DB_HOST')?:'127.0.0.1';$port=getenv('E2E_DB_PORT')?:'3306';
if(!in_array($host,['127.0.0.1','localhost'],true)||!ctype_digit($port)){throw new RuntimeException('Solo MariaDB local.');}
$pdo=new PDO("mysql:host=$host;port=$port;charset=utf8mb4",getenv('E2E_DB_USER')?:'root',getenv('E2E_DB_PASS')?:'',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$base='rrhh_e2e_'.bin2hex(random_bytes(6));
try{
 $pdo->exec("CREATE DATABASE `$base` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");$pdo->exec("USE `$base`");
 $pdo->exec('CREATE TABLE practicas (id_practica INT PRIMARY KEY) ENGINE=InnoDB;CREATE TABLE usuarios_sistema (id_usuario INT PRIMARY KEY) ENGINE=InnoDB;INSERT INTO practicas VALUES (1);INSERT INTO usuarios_sistema VALUES (1)');
 $patch=file_get_contents(__DIR__.'/../database/2026-11-06_patch_cambio_colacion_016.sql');$schema=file_get_contents(__DIR__.'/../database/schema.sql');
 if(!str_contains(str_replace("\r\n","\n",$schema),trim(str_replace("\r\n","\n",$patch)))){throw new RuntimeException('Schema debe incluir el parche íntegro.');}
 $pdo->exec($patch);$pdo->exec("INSERT INTO solicitudes_colacion (id_practica,fecha,inicio_anterior,inicio_solicitado,firma_horario,motivo,solicitado_por,solicitado_utc) VALUES (1,'2026-11-06','13:00','14:00',REPEAT('a',64),'Ficticio',1,UTC_TIMESTAMP())");$pdo->exec($patch);
 if((int)$pdo->query('SELECT COUNT(*) FROM solicitudes_colacion')->fetchColumn()!==1){throw new RuntimeException('Repetición no conserva datos.');}
 try{$pdo->exec('DELETE FROM practicas WHERE id_practica=1');throw new RuntimeException('FK no protegió historial.');}catch(PDOException $e){if($e->getCode()!=='23000'){throw $e;}}
 echo "Colación: 3 comprobaciones de migración MariaDB correctas.\n";
}finally{$pdo->exec("DROP DATABASE IF EXISTS `$base`");}
