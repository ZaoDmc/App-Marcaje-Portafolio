<?php
declare(strict_types=1);
// Ensayé actualización repetible y creación limpia en una base aleatoria local, sin cargar .env.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$host=getenv('E2E_DB_HOST')?:'127.0.0.1';$port=getenv('E2E_DB_PORT')?:'3306';
if(!in_array($host,['127.0.0.1','localhost'],true)||!ctype_digit($port)){throw new RuntimeException('Solo MariaDB local.');}
$pdo=new PDO("mysql:host=$host;port=$port;charset=utf8mb4",getenv('E2E_DB_USER')?:'root',getenv('E2E_DB_PASS')?:'',[
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$base='rrhh_e2e_'.bin2hex(random_bytes(6));$checks=0;
$check=static function(bool $ok,string $msg) use (&$checks):void {if(!$ok){throw new RuntimeException($msg);} $checks++;};
try {
    $pdo->exec("CREATE DATABASE `$base` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$base`");
    $pdo->exec('CREATE TABLE jornadas_asistencia (id_jornada INT PRIMARY KEY) ENGINE=InnoDB;
        CREATE TABLE usuarios_sistema (id_usuario INT PRIMARY KEY) ENGINE=InnoDB;
        INSERT INTO jornadas_asistencia VALUES (1)');
    $schema=file_get_contents(__DIR__.'/../database/schema.sql');
    preg_match('/CREATE TABLE IF NOT EXISTS tiempos_excepcionales \([\s\S]*?ENGINE=InnoDB[^;]*;/',$schema,$match);
    $tabla=$match[0];
    $anterior=preg_replace('/^.*politica_canje TINYINT.*\R/m','',$tabla);
    $pdo->exec($anterior);
    $pdo->exec("INSERT INTO tiempos_excepcionales (id_jornada,hora_inicio,hora_fin,minutos_solicitados,motivo_solicitud,solicitado_utc,vence_utc) VALUES (1,'17:00','18:00',60,'Ficticio','2026-10-01 12:00:00','2026-10-08 12:00:00')");
    $parche=file_get_contents(__DIR__.'/../database/2026-11-06_patch_canje_autonomo_014.sql');
    $pdo->exec($parche);$pdo->exec($parche);
    $fila=$pdo->query('SELECT * FROM tiempos_excepcionales')->fetch();
    $check((int)$fila['politica_canje']===1,'Expediente existente debe conservar política histórica.');
    $check($fila['vence_utc']==='2026-10-08 12:00:00','Migración no debe renovar plazos existentes.');
    $actualizada=$pdo->query("SHOW COLUMNS FROM tiempos_excepcionales LIKE 'politica_canje'")->fetch();
    $pdo->exec('DROP TABLE tiempos_excepcionales');$pdo->exec($tabla);
    $nueva=$pdo->query("SHOW COLUMNS FROM tiempos_excepcionales LIKE 'politica_canje'")->fetch();
    $check($actualizada===$nueva,'Schema nuevo y actualización deben coincidir.');
    echo "Canje: $checks comprobaciones de migración MariaDB correctas.\n";
} finally {
    // El nombre se genera aquí y nunca procede de una entrada ni de una base de aplicación.
    $pdo->exec("DROP DATABASE IF EXISTS `$base`");
}
