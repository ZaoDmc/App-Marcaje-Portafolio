<?php

declare(strict_types=1);

// Preparé exclusivamente usuarios y prácticas ficticias dentro de una fixture E2E ya creada.
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
$root=dirname(__DIR__); $file=realpath($argv[1]??''); $base=realpath($root.'/scratch/phase8');
if (!$file || !$base || !str_starts_with($file,$base.DIRECTORY_SEPARATOR)) { throw new RuntimeException('Fixture E2E aislada requerida.'); }
$f=json_decode(file_get_contents($file),true,512,JSON_THROW_ON_ERROR);
if (!preg_match('/^rrhh_e2e_[a-f0-9]{12}$/D',$f['database']) || !in_array($f['db_host'],['localhost','127.0.0.1'],true)) { throw new RuntimeException('Base ficticia local requerida.'); }
$pdo=new PDO("mysql:host={$f['db_host']};port={$f['db_port']};dbname={$f['database']};charset=utf8mb4",$f['db_user'],$f['db_pass'],
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
date_default_timezone_set('America/Santiago');
$admin=(int)$pdo->query('SELECT id_usuario FROM usuarios_sistema WHERE email="admin@example.test"')->fetchColumn();
$estado=(int)$pdo->query('SELECT id_estado_practica FROM estados_practica WHERE nombre="EN_PROGRESO"')->fetchColumn();
$pdo->exec("INSERT INTO areas_practica (nombre) VALUES ('Equipo Notas E2E')"); $area=(int)$pdo->lastInsertId();
$pdo->exec('UPDATE historial_areas_practicante SET fecha_fin=CURRENT_DATE WHERE id_practica=1 AND fecha_fin IS NULL');
$s=$pdo->prepare('INSERT INTO historial_areas_practicante (id_practica,id_area,fecha_inicio,asignado_por) VALUES (1,?,?,?)');
$s->execute([$area,date('Y-m-d',strtotime('-30 days')),$admin]);
$s=$pdo->prepare('INSERT INTO liderazgos_area (id_area,id_practica,fecha_inicio,asignado_por,motivo_designacion) VALUES (?,1,?,?,"Escenario ficticio")');
$s->execute([$area,date('Y-m-d H:i:s',strtotime('-30 days')),$admin]);
$practicas=[];
foreach (['Integrante','Ajeno','Reemplazo'] as $nombre) {
    $s=$pdo->prepare('INSERT INTO practicantes (nombre_1,apellido_paterno,email) VALUES (?,"Ficticio",?)'); $s->execute([$nombre,strtolower($nombre).'@example.test']);
    $persona=(int)$pdo->lastInsertId();
    $s=$pdo->prepare('INSERT INTO practicas (id_practicante,id_estado_practica,fecha_inicio_practica,fecha_termino_practica) VALUES (?,?,?,?)');
    $s->execute([$persona,$estado,date('Y-m-d',strtotime('-30 days')),date('Y-m-d',strtotime('+60 days'))]); $practicas[$nombre]=(int)$pdo->lastInsertId();
    if (($argv[2]??'')==='--publicacion') {
        // Creé cuentas ficticias para verificar la consulta privada y el reemplazo de líder en fase 4.
        $s=$pdo->prepare('INSERT INTO usuarios_sistema (id_practicante,email,password_hash,estado_cuenta,email_verificado) VALUES (?,?,?,"ACTIVO",1)');
        $s->execute([$persona,strtolower($nombre).'@example.test',password_hash($f['password'],PASSWORD_DEFAULT)]);
        $cuenta=(int)$pdo->lastInsertId();
        $pdo->exec('INSERT INTO usuarios_roles (id_usuario,id_rol) SELECT '.$cuenta.',id_rol FROM roles_sistema WHERE nombre="PRACTICANTE"');
    }
    if ($nombre!=='Ajeno') {
        $s=$pdo->prepare('INSERT INTO historial_areas_practicante (id_practica,id_area,fecha_inicio,asignado_por) VALUES (?,?,?,?)');
        $s->execute([$practicas[$nombre],$area,date('Y-m-d',strtotime('-30 days')),$admin]);
    }
}
$viernes=(new DateTimeImmutable('last friday'))->format('Y-m-d');
$f['notas']=['area'=>$area,'lider'=>1,'integrante'=>$practicas['Integrante'],'ajeno'=>$practicas['Ajeno'],'reemplazo'=>$practicas['Reemplazo'],
    'viernes'=>$viernes,'atrasado'=>(new DateTimeImmutable($viernes))->modify('-14 days')->format('Y-m-d')];
file_put_contents($file,json_encode($f,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
echo "Fixture de Notas preparada con datos ficticios.\n";
