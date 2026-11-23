<?php

declare(strict_types=1);

// Agregué un segundo proceso local para comprobar que dos conexiones no creen la misma semana.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
[$script,$database,$actor,$practica,$viernes] = $argv + array_fill(0,5,'');
if (!preg_match('/^rrhh_e2e_[a-f0-9]{12}$/D',$database) || !ctype_digit($actor) || !ctype_digit($practica)) {
    throw new RuntimeException('Solo se admiten identificadores ficticios E2E.');
}
$host=getenv('E2E_DB_HOST') ?: '127.0.0.1'; $port=getenv('E2E_DB_PORT') ?: '3306';
if (!in_array($host,['localhost','127.0.0.1'],true) || !ctype_digit($port)) { throw new RuntimeException('Conexión local requerida.'); }
$fixture=new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4",getenv('E2E_DB_USER') ?: 'root',getenv('E2E_DB_PASS') ?: '',
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
function db(): PDO { return $GLOBALS['fixture']; }
$directory=dirname(__DIR__).'/scratch/notas-sessions';
ini_set('session.save_path',$directory); session_id('notas-worker-'.bin2hex(random_bytes(6))); session_start();
require_once dirname(__DIR__).'/src/Services/NotasService.php';
$rol=(int)$fixture->query('SELECT id_rol FROM roles_sistema WHERE nombre="PRACTICANTE"')->fetchColumn();
$_SESSION=['id_usuario'=>(int)$actor,'id_rol_activo'=>$rol,'rol_activo'=>'PRACTICANTE'];
$fixture->exec('SET innodb_lock_wait_timeout=10');
file_put_contents($directory.'/'.$database.'.ready','1');
try {
    notas_guardar_borrador((int)$practica,$viernes,array_fill(1,10,'6.5'),'6.5','Carga concurrente');
    exit(1);
} catch (DomainException $e) {
    if (!str_contains($e->getMessage(),'ya existe')) { throw $e; }
    echo 'RECHAZO_OBSOLETO';
} finally { session_destroy(); }
