<?php
declare(strict_types=1);
// Agregué una jornada ficticia terminada, posterior al corte por ID, sin modificar el reloj del servidor.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$raiz=realpath(dirname(__DIR__).'/scratch/phase8');$archivo=realpath($argv[1]??'');
if($raiz===false || $archivo===false || !str_starts_with($archivo,$raiz.DIRECTORY_SEPARATOR)){throw new RuntimeException('Fixture fuera de scratch E2E.');}
$f=json_decode(file_get_contents($archivo),true,512,JSON_THROW_ON_ERROR);
if(!preg_match('/^rrhh_e2e_[a-f0-9]{12}$/',$f['database']) || !in_array($f['db_host'],['localhost','127.0.0.1'],true)){throw new RuntimeException('Base ficticia local requerida.');}
$pdo=new PDO("mysql:host={$f['db_host']};port={$f['db_port']};dbname={$f['database']};charset=utf8mb4",$f['db_user'],$f['db_pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
date_default_timezone_set('America/Santiago');
$dia=(new DateTimeImmutable($f['yesterday']))->modify('-10 days');
do{$dia=$dia->modify('-1 day');}while((int)$dia->format('N')>5 || $pdo->query("SELECT COUNT(*) FROM jornadas_asistencia WHERE id_practica=1 AND fecha='".$dia->format('Y-m-d')."'")->fetchColumn()>0);
$fecha=$dia->format('Y-m-d');
$pdo->prepare('INSERT INTO jornadas_asistencia (id_practica,fecha,hora_entrada,hora_salida,horas_calculadas,estado_jornada,politica_recuperacion_v2) VALUES (1,?,?,?,8,"VALIDA",1)')->execute([$fecha,$fecha.' 09:00:00',$fecha.' 19:00:00']);
$id=(int)$pdo->lastInsertId();
foreach(['ENTRADA'=>'09:00:00','ENTRADA_COLACION'=>'13:00:00','SALIDA_COLACION'=>'14:00:00','SALIDA'=>'19:00:00'] as $codigo=>$hora){
    $pdo->prepare('INSERT INTO marcas_asistencia (id_jornada,id_tipo_marca,fecha_hora) SELECT ?,id_tipo_marca,? FROM tipos_marca WHERE codigo=?')->execute([$id,$fecha.' '.$hora,$codigo]);
}
$futura=new DateTimeImmutable($f['today']);
// Elegí un día libre de permisos de otros recorridos para no mezclar escenarios de recuperación y compensación.
do{$futura=$futura->modify('+1 day');$ocupada=$pdo->prepare('SELECT COUNT(*) FROM permisos_practicante WHERE id_practica=1 AND fecha_ausencia=? AND estado IN ("PENDIENTE","APROBADO")');$ocupada->execute([$futura->format('Y-m-d')]);}
while((int)$futura->format('N')>5 || (int)$ocupada->fetchColumn()>0);
if($futura>new DateTimeImmutable($f['today'].' +6 days')){throw new RuntimeException('No hay día ficticio libre en la semana de compensación.');}
// Reproduje también marcas futuras como las ingresadas manualmente al probar en localhost.
$fechaFutura=(new DateTimeImmutable($f['today']))->modify('+10 days');
while((int)$fechaFutura->format('N')>5){$fechaFutura=$fechaFutura->modify('+1 day');}
$diaFuturo=$fechaFutura->format('Y-m-d');
$pdo->prepare('INSERT INTO jornadas_asistencia (id_practica,fecha,estado_jornada,politica_recuperacion_v2) VALUES (1,?,"VALIDA",1)')->execute([$diaFuturo]);$idFuturo=(int)$pdo->lastInsertId();
foreach(['ENTRADA'=>'09:00:00','ENTRADA_COLACION'=>'13:00:00','SALIDA_COLACION'=>'14:00:00','SALIDA'=>'19:30:00'] as $codigo=>$hora){
    $pdo->prepare('INSERT INTO marcas_asistencia (id_jornada,id_tipo_marca,fecha_hora) SELECT ?,id_tipo_marca,? FROM tipos_marca WHERE codigo=?')->execute([$idFuturo,$diaFuturo.' '.$hora,$codigo]);
}
$GLOBALS['tiempoFixture']=$pdo;
function db(): PDO { return $GLOBALS['tiempoFixture']; }
require $f['backend'].'/src/Services/AsistenciaService.php';
// Ejercité el mismo cálculo usado por las correcciones de supervisor, no un estado escrito para hacer pasar el caso.
asistencia_recalcular_jornada($id);asistencia_recalcular_jornada($idFuturo);
// Reproduje los ejemplos del cliente en días separados con horario 10:00–17:00 y colación de una hora.
$tolerancias=[];$diaTolerancia=new DateTimeImmutable($f['yesterday']);
$idHorario=(int)$pdo->query('SELECT id_horario FROM horarios_practicante WHERE id_practica=1 AND activo=1 ORDER BY fecha_inicio DESC,id_horario DESC LIMIT 1')->fetchColumn();
foreach([['09:55:00','17:20:00','VALIDA'],['09:55:00','17:50:00','OBSERVADA'],['09:31:00','17:29:00','VALIDA']] as [$entrada,$salida,$estado]){
    do{
        $diaTolerancia=$diaTolerancia->modify('-1 day');$dt=$diaTolerancia->format('Y-m-d');
        $ocupado=$pdo->prepare('SELECT (SELECT COUNT(*) FROM jornadas_asistencia WHERE id_practica=1 AND fecha=?) + (SELECT COUNT(*) FROM permisos_practicante WHERE id_practica=1 AND fecha_ausencia=? AND estado IN ("PENDIENTE","APROBADO"))');$ocupado->execute([$dt,$dt]);
    }while((int)$diaTolerancia->format('N')>5 || (int)$ocupado->fetchColumn()>0);
    $pdo->prepare('INSERT INTO excepciones_horario_practicante (id_horario,fecha,tipo_excepcion,entrada_esperada,salida_esperada,colacion_inicio,colacion_fin,horas_planificadas,observacion) VALUES (?, ?,"CAMBIO_HORARIO","10:00","17:00","13:00","14:00",6,"Prueba ficticia de márgenes")')->execute([$idHorario,$dt]);
    $pdo->prepare('INSERT INTO jornadas_asistencia (id_practica,fecha,estado_jornada,politica_recuperacion_v2) VALUES (1,?,"INCOMPLETA",1)')->execute([$dt]);$jt=(int)$pdo->lastInsertId();
    foreach(['ENTRADA'=>$entrada,'ENTRADA_COLACION'=>'13:00:00','SALIDA_COLACION'=>'14:00:00','SALIDA'=>$salida] as $codigo=>$hora){
        $pdo->prepare('INSERT INTO marcas_asistencia (id_jornada,id_tipo_marca,fecha_hora) SELECT ?,id_tipo_marca,? FROM tipos_marca WHERE codigo=?')->execute([$jt,$dt.' '.$hora,$codigo]);
    }
    asistencia_recalcular_jornada($jt);$tolerancias[]=['id_jornada'=>$jt,'estado'=>$estado];
}
echo json_encode(['id_jornada'=>$id,'fecha'=>$fecha,'id_jornada_futura'=>$idFuturo,'fecha_compensacion'=>$futura->format('Y-m-d'),'tolerancias'=>$tolerancias],JSON_THROW_ON_ERROR);
