<?php

declare(strict_types=1);

// Consulté únicamente la fixture de Notas y permití un reemplazo ficticio para probar pérdida de permisos.
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
$root=dirname(__DIR__); $file=realpath($argv[1]??''); $base=realpath($root.'/scratch/phase8');
if (!$file || !$base || !str_starts_with($file,$base.DIRECTORY_SEPARATOR)) { throw new RuntimeException('Fixture aislada requerida.'); }
$f=json_decode(file_get_contents($file),true,512,JSON_THROW_ON_ERROR);
if (!preg_match('/^rrhh_e2e_[a-f0-9]{12}$/D',$f['database']) || !in_array($f['db_host'],['localhost','127.0.0.1'],true)) { throw new RuntimeException('Base ficticia requerida.'); }
$fixture=new PDO("mysql:host={$f['db_host']};port={$f['db_port']};dbname={$f['database']};charset=utf8mb4",$f['db_user'],$f['db_pass'],
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
function db(): PDO { return $GLOBALS['fixture']; }
if (($argv[2]??'')==='replace') {
    ini_set('session.save_path',$f['directory']); session_id('notas-state-'.bin2hex(random_bytes(5))); session_start();
    require_once $root.'/src/Services/LiderazgoService.php';
    $admin=(int)$fixture->query('SELECT id_usuario FROM usuarios_sistema WHERE email="admin@example.test"')->fetchColumn();
    $rol=(int)$fixture->query('SELECT id_rol FROM roles_sistema WHERE nombre="ADMIN"')->fetchColumn();
    $_SESSION=['id_usuario'=>$admin,'id_rol_activo'=>$rol,'rol_activo'=>'ADMIN'];
    $s=$fixture->prepare('SELECT id_liderazgo FROM liderazgos_area WHERE id_area=? AND fecha_fin IS NULL'); $s->execute([$f['notas']['area']]);
    liderazgos_guardar((int)$f['notas']['area'],(int)$f['notas']['reemplazo'],(int)$s->fetchColumn(),'Reemplazo ficticio de E2E');
    session_destroy();
} elseif (($argv[2]??'state')!=='state') { throw new RuntimeException('Operación ficticia no válida.'); }
echo json_encode(['notas'=>$fixture->query('SELECT id_nota,id_practica,viernes,id_rubrica,revision,estado,nota_final,aprobado_por,aprobado_en FROM notas_semanales ORDER BY id_nota')->fetchAll(),
    'config'=>$fixture->query('SELECT id_rubrica,revision FROM configuracion_notas WHERE id=1')->fetch(),
    'versiones'=>(int)$fixture->query('SELECT COUNT(*) FROM rubricas_notas')->fetchColumn(),
    'historial'=>(int)$fixture->query('SELECT COUNT(*) FROM notas_semanales_historial')->fetchColumn()],JSON_THROW_ON_ERROR);
