<?php
declare(strict_types=1);
// Agregué un canje autorizado y vencido en la base E2E para revisar correcciones desde formularios reales.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$root=dirname(__DIR__);$archivo=realpath($argv[1]??'');$base=realpath($root.'/scratch/phase8');
if($archivo===false || $base===false || !str_starts_with($archivo,$base.DIRECTORY_SEPARATOR)){throw new RuntimeException('Fixture aislada requerida.');}
$f=json_decode(file_get_contents($archivo),true,512,JSON_THROW_ON_ERROR);
if(!preg_match('/^rrhh_e2e_[a-f0-9]{12}$/D',$f['database']) || !in_array($f['db_host'],['localhost','127.0.0.1'],true)){throw new RuntimeException('Base ficticia local requerida.');}
$pdo=new PDO("mysql:host={$f['db_host']};port={$f['db_port']};dbname={$f['database']};charset=utf8mb4",$f['db_user'],$f['db_pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$GLOBALS['validacionFixture']=$pdo;
function db(): PDO{return $GLOBALS['validacionFixture'];}
require $f['backend'].'/src/Services/AsistenciaService.php';
$dia=new DateTimeImmutable($f['today'].' -24 days');
do{
    $dia=$dia->modify('+1 day');$origen=$dia->format('Y-m-d');$fin=$dia->modify('+1 day');
    while((int)$fin->format('N')>5){$fin=$fin->modify('+1 day');}$destino=$fin->format('Y-m-d');
    $s=$pdo->prepare('SELECT COUNT(*) FROM jornadas_asistencia WHERE id_practica=1 AND fecha IN (?,?)');$s->execute([$origen,$destino]);
}while((int)$dia->format('N')>5 || (int)$s->fetchColumn()>0);
$ids=[];
foreach([[$origen,'09:00:00','19:00:00'],[$destino,'10:00:00','18:00:00']] as [$fecha,$entrada,$salida]){
    $pdo->prepare('INSERT INTO jornadas_asistencia (id_practica,fecha,estado_jornada,politica_recuperacion_v2) VALUES (1,?,"VALIDA",1)')->execute([$fecha]);$id=(int)$pdo->lastInsertId();$ids[]=$id;
    foreach(['ENTRADA'=>$entrada,'ENTRADA_COLACION'=>'13:00:00','SALIDA_COLACION'=>'14:00:00','SALIDA'=>$salida] as $codigo=>$hora){
        $pdo->prepare('INSERT INTO marcas_asistencia (id_jornada,id_tipo_marca,fecha_hora) SELECT ?,id_tipo_marca,? FROM tipos_marca WHERE codigo=?')->execute([$id,$fecha.' '.$hora,$codigo]);
    }
}
$actor=(int)$pdo->query('SELECT id_usuario FROM usuarios_sistema WHERE email="supervisor@example.test"')->fetchColumn();
$solicitado=tiempo_fecha_utc($origen.' 19:05:00');$vence=tiempo_vencimiento($solicitado);$autorizado=tiempo_fecha_utc($destino.' 08:00:00');
$firma=recuperaciones_firma_marcas(asistencia_marcas_vigentes_jornada($ids[0]));
$pdo->prepare('INSERT INTO tiempos_excepcionales (id_jornada,hora_inicio,hora_fin,minutos_solicitados,minutos_aprobados,motivo_solicitud,estado,solicitado_utc,vence_utc,revisado_por,revisado_utc,firma_marcas) VALUES (?,"18:00","19:00",60,60,"Reunión histórica ficticia","APROBADO",?,?,?,?,?)')->execute([$ids[0],$solicitado,$vence,$actor,$solicitado,$firma]);$credito=(int)$pdo->lastInsertId();
$pdo->prepare('INSERT INTO compensaciones_tiempo (id_tiempo,fecha,tipo,hora_inicio,hora_fin,minutos,minutos_aplicados,estado,motivo_solicitud,solicitado_utc,revisado_por,revisado_utc) VALUES (?, ?,"ENTRADA","09:00","10:00",60,60,"APLICADA","Canje histórico ficticio",?,?,?)')->execute([$credito,$destino,$solicitado,$actor,$autorizado]);$canje=(int)$pdo->lastInsertId();
asistencia_recalcular_jornada($ids[1]);
// Una corrección inocua del descanso invalida la firma y obliga a revisar ambos expedientes.
$pdo->prepare('UPDATE marcas_asistencia SET fecha_hora=? WHERE id_jornada=? AND id_tipo_marca=(SELECT id_tipo_marca FROM tipos_marca WHERE codigo="ENTRADA_COLACION")')->execute([$origen.' 13:01:00',$ids[0]]);
asistencia_recalcular_jornada($ids[0]);
echo json_encode(['id_tiempo'=>$credito,'id_compensacion'=>$canje,'id_destino'=>$ids[1],'origen'=>$origen,'destino'=>$destino,'vence_utc'=>$vence],JSON_THROW_ON_ERROR);
