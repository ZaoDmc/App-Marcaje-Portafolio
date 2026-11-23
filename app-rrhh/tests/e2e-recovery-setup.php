<?php
declare(strict_types=1);

// Agregué un escenario terminado para probar validación real sin manipular el reloj de la aplicación.
// Solo modifica una base E2E previamente creada; no acepta nombres de bases ni SQL desde el navegador.
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
$raiz=realpath(dirname(__DIR__).'/scratch/phase8');$archivo=realpath($argv[1]??'');
if ($raiz===false || $archivo===false || !str_starts_with($archivo,$raiz.DIRECTORY_SEPARATOR)) { throw new RuntimeException('Fixture fuera de scratch E2E.'); }
$f=json_decode(file_get_contents($archivo),true,512,JSON_THROW_ON_ERROR);
if (!preg_match('/^rrhh_e2e_[a-f0-9]{12}$/',$f['database']) || !in_array($f['db_host'],['localhost','127.0.0.1'],true)) { throw new RuntimeException('Base ficticia local requerida.'); }
$GLOBALS['recoveryFixture']=new PDO("mysql:host={$f['db_host']};port={$f['db_port']};dbname={$f['database']};charset=utf8mb4",$f['db_user'],$f['db_pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
function db(): PDO { return $GLOBALS['recoveryFixture']; }
ini_set('session.save_path',$f['directory']);session_id('recovery-'.bin2hex(random_bytes(6)));session_start();
require $f['backend'].'/src/Services/AsistenciaService.php';
require $f['backend'].'/src/Services/PermisosService.php';
$usuario=db()->query('SELECT id_usuario FROM usuarios_sistema WHERE email="supervisor@example.test"')->fetchColumn();
$rol=db()->query('SELECT id_rol FROM roles_sistema WHERE nombre="SUPERVISOR"')->fetchColumn();
$_SESSION=['id_usuario'=>(int)$usuario,'id_rol_activo'=>(int)$rol,'rol_activo'=>'SUPERVISOR','id_practicante'=>null];
$ausencia=new DateTimeImmutable($f['yesterday']);do{$ausencia=$ausencia->modify('-1 day');}while((int)$ausencia->format('N')>5);
db()->beginTransaction();
// La solicitud y autorización se modelan anteriores al trabajo; los formularios de solicitud rechazan pasado.
$stmt=db()->prepare('INSERT INTO permisos_practicante (id_practica,fecha_ausencia,tipo_ausencia,hora_inicio_ausencia,hora_fin_ausencia,horas_ausencia,horas_trabajo_dia,nuevo_horario_inicio,nuevo_horario_fin,motivo,fecha_recuperacion,hora_inicio_recuperacion,hora_fin_recuperacion,horas_a_recuperar,estado,created_at) VALUES (1,?,"PARCIAL","11:00","13:00",2,6,"09:00","18:00","Fixture de colación trabajada y extensión no cumplida",?,"13:00","14:00",2,"APROBADO",?)');
$stmt->execute([$ausencia->format('Y-m-d'),$f['yesterday'],$ausencia->format('Y-m-d').' 08:00:00']);
$idPermiso=(int)db()->lastInsertId();
recuperaciones_guardar_plan($idPermiso,[['fecha'=>$f['yesterday'],'tipo'=>'COLACION','hora_inicio'=>'13:00','hora_fin'=>'14:00','minutos_planificados'=>60],['fecha'=>$f['yesterday'],'tipo'=>'EXTENSION','hora_inicio'=>'18:00','hora_fin'=>'19:00','minutos_planificados'=>60]]);
$stmt=db()->prepare('UPDATE recuperaciones_permiso SET estado="PROGRAMADA",created_at=? WHERE id_permiso=?');
$stmt->execute([$ausencia->format('Y-m-d').' 08:00:00',$idPermiso]);
asistencia_recalcular_jornada(1,null,true);
db()->commit();
echo json_encode(['id_permiso'=>$idPermiso,'id_recuperacion'=>(int)recuperaciones_plan($idPermiso)[0]['id_recuperacion'],'id_no_trabajado'=>(int)recuperaciones_plan($idPermiso)[1]['id_recuperacion']],JSON_THROW_ON_ERROR);
$_SESSION=[];session_destroy();
