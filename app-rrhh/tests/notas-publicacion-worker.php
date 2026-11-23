<?php

declare(strict_types=1);

// Ejecuté una publicación real desde otra conexión para comprobar la revisión esperada bajo concurrencia InnoDB.
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
[$script,$database,$actor,$idNota,$token] = $argv + array_fill(0,5,'');
if (!preg_match('/^rrhh_e2e_[a-f0-9]{12}$/D',$database) || !ctype_digit($actor) || !ctype_digit($idNota) || !preg_match('/^[a-f0-9]{8}$/D',$token)) {
    throw new RuntimeException('Solo se admiten identificadores ficticios.');
}
$host=getenv('E2E_DB_HOST') ?: '127.0.0.1'; $port=getenv('E2E_DB_PORT') ?: '3306';
if (!in_array($host,['localhost','127.0.0.1'],true) || !ctype_digit($port)) { throw new RuntimeException('MariaDB local requerida.'); }
$fixture=new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4",getenv('E2E_DB_USER') ?: 'root',getenv('E2E_DB_PASS') ?: '',
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
function db(): PDO { return $GLOBALS['fixture']; }
$directory=dirname(__DIR__).'/scratch/notas-sessions';
ini_set('session.save_path',$directory); session_id('publicacion-worker-'.$token); session_start();
require_once dirname(__DIR__).'/src/Services/PublicacionNotasService.php';
$rol=(int)$fixture->query('SELECT id_rol FROM roles_sistema WHERE nombre="ADMIN"')->fetchColumn();
$_SESSION=['id_usuario'=>(int)$actor,'id_rol_activo'=>$rol,'rol_activo'=>'ADMIN'];
$s=$fixture->prepare('SELECT * FROM notas_semanales WHERE id_nota=?'); $s->execute([(int)$idNota]); $nota=$s->fetch();
$s=$fixture->prepare('SELECT posicion,nota FROM notas_semanales_detalle WHERE id_nota=? ORDER BY posicion'); $s->execute([(int)$idNota]); $valores=$s->fetchAll(PDO::FETCH_KEY_PAIR);
$fixture->exec('SET innodb_lock_wait_timeout=10');
file_put_contents($directory.'/'.$database.'-'.$token.'.ready','1');
try {
    notas_publicar((int)$nota['id_practica'],$nota['viernes'],$valores,$nota['nota_final'],$nota['observacion']??'',
        (int)$nota['revision'],'',(int)$nota['id_rubrica'],'BORRADOR',true);
    echo 'PUBLICADA';
} catch (DomainException $e) {
    if (!str_contains($e->getMessage(),'cambió')) { throw $e; }
    echo 'OBSOLETA';
} finally { session_destroy(); }
