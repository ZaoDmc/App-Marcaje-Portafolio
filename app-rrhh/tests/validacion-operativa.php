<?php
declare(strict_types=1);
// Agregué revalidación histórica con evidencia real y conciliación del informe, únicamente en la base sintética.
if(PHP_SAPI!=='cli' || !isset($fixture,$checks)){exit(1);}
require_once dirname(__DIR__).'/src/Services/ReportesService.php';
$origen=date('Y-m-d',strtotime('-40 days'));
while((int)date('N',strtotime($origen))>5 || (int)$fixture->query("SELECT COUNT(*) FROM jornadas_asistencia WHERE id_practica=22 AND fecha='$origen'")->fetchColumn()>0){$origen=date('Y-m-d',strtotime($origen.' -1 day'));}
$destino=date('Y-m-d',strtotime($origen.' +1 day'));
while((int)date('N',strtotime($destino))>5){$destino=date('Y-m-d',strtotime($destino.' +1 day'));}
tiempo_test_jornada(7300,$origen,'09:00:00','19:00:00');
tiempo_test_jornada(7301,$destino,'10:00:00','18:00:00');
$solicitado=tiempo_fecha_utc($origen.' 19:05:00');$vence=tiempo_vencimiento($solicitado);
$firma=recuperaciones_firma_marcas(asistencia_marcas_vigentes_jornada(7300));
$fixture->prepare('INSERT INTO tiempos_excepcionales (id_jornada,hora_inicio,hora_fin,minutos_solicitados,minutos_aprobados,motivo_solicitud,estado,solicitado_utc,vence_utc,revisado_por,revisado_utc,firma_marcas) VALUES (7300,"18:00","19:00",60,60,"Reunión histórica","APROBADO",?,?,501,?,?)')->execute([$solicitado,$vence,$solicitado,$firma]);
$creditoHistorico=(int)$fixture->lastInsertId();
$autorizado=tiempo_fecha_utc($destino.' 08:00:00');
$fixture->prepare('INSERT INTO compensaciones_tiempo (id_tiempo,fecha,tipo,hora_inicio,hora_fin,minutos,minutos_aplicados,estado,motivo_solicitud,solicitado_utc,revisado_por,revisado_utc) VALUES (?, ?,"ENTRADA","09:00","10:00",60,60,"APLICADA","Canje autorizado",?,501,?)')->execute([$creditoHistorico,$destino,$solicitado,$autorizado]);
$canjeHistorico=(int)$fixture->lastInsertId();
$_SESSION=['id_usuario'=>501,'id_rol_activo'=>2,'rol_activo'=>'SUPERVISOR','id_practicante'=>1];
check(asistencia_recalcular_jornada(7301)['horas_calculadas']===8.0,'Canje autorizado dentro del plazo conserva su aplicación aunque el saldo haya vencido');
$fixture->prepare('UPDATE marcas_asistencia SET fecha_hora=? WHERE id_jornada=7300 AND id_tipo_marca=2')->execute([$origen.' 13:01:00']);
asistencia_recalcular_jornada(7300);
check(tiempo_credito($creditoHistorico)['estado']==='REVISAR','Corrección de fuente histórica retira su autorización vigente');
check((float)asistencia_jornada_por_id(7301)['horas_calculadas']===7.0,'Reapertura retira canje histórico del avance');
check(!tiempo_revisar_compensacion($canjeHistorico,true,'Revisar antes de fuente')['ok'],'No se revalida un canje con fuente pendiente');
failAudit('REVISAR_TIEMPO_EXCEPCIONAL');
check(!tiempo_revisar($creditoHistorico,true,'Revalidar evidencia')['ok'],'Revalidación también exige auditoría transaccional');
$fixture->exec('DROP TRIGGER fail_audit');
check(tiempo_revisar($creditoHistorico,true,'Verifiqué nuevamente la reunión autorizada')['ok'],'Supervisor puede revalidar evidencia aprobada que venció sin renovar saldo');
check(tiempo_credito($creditoHistorico)['vence_utc']===$vence && tiempo_saldo(tiempo_credito($creditoHistorico))===0,'Revalidación mantiene vencimiento y cero minutos canjeables');
check((float)asistencia_jornada_por_id(7301)['horas_calculadas']===7.0,'Revalidar la fuente no aprueba automáticamente el canje');
// Simulé otro revisor autorizado para verificar que la aprobación original no cambia de autor al revalidar.
$_SESSION['id_usuario']=502;
check(tiempo_revisar_compensacion($canjeHistorico,true,'Confirmo autorización previa y marcas del canje')['ok'],'Supervisor revalida expresamente el canje pasado autorizado antes de su uso');
$_SESSION['id_usuario']=501;
check((float)asistencia_jornada_por_id(7301)['horas_calculadas']===8.0,'Revalidación actualiza el avance del destino en la misma transacción');
$s=$fixture->query('SELECT * FROM compensaciones_tiempo WHERE id_compensacion='.$canjeHistorico)->fetch();
check($s['estado']==='APLICADA' && (int)$s['minutos_aplicados']===60 && $s['revisado_utc']===$autorizado && (int)$s['revisado_por']===501,'Se conserva fecha y autor de la autorización original sin duplicar el canje');
$desglose=reportes_gestion_tiempos(22,$destino,$destino);
check($desglose['compensados']===60 && $desglose['excepcionales']===0,'Informe usa fecha de canje y no fecha de origen para los minutos aplicados');
check(reportes_gestion_tiempos(22,$origen,$origen)['excepcionales']===60,'Informe desglosa aprobación excepcional por fecha del trabajo');
$fixture->prepare('UPDATE marcas_asistencia SET fecha_hora=? WHERE id_jornada=7300 AND id_tipo_marca=4')->execute([$origen.' 18:30:00']);
asistencia_recalcular_jornada(7300);
check(tiempo_revisar($creditoHistorico,true,'Solo media hora comprobada')['ok'],'Fuente corregida reduce minutos aprobados a lo realmente trabajado');
check(!tiempo_revisar_compensacion($canjeHistorico,true,'Intentar sesenta con treinta')['ok'],'No se revalida consumo mayor que el crédito corregido');
$fixture->prepare('UPDATE marcas_asistencia SET fecha_hora=? WHERE id_jornada=7301 AND id_tipo_marca=1')->execute([$destino.' 09:30:00']);
check(tiempo_revisar_compensacion($canjeHistorico,true,'Revalido solo media hora no trabajada')['ok'],'Consumo histórico parcial respeta el presupuesto corregido');
check(reportes_gestion_tiempos(22,$destino,$destino)['compensados']===30,'Informe refleja treinta minutos realmente aplicados tras correcciones');
check(tiempo_saldo(tiempo_credito($creditoHistorico))===0,'Corrección parcial no resucita saldo vencido');
// Distinguí una petición sin aprobación original de una revalidación: no se legalizan canjes retrospectivos.
$fixture->exec('UPDATE compensaciones_tiempo SET estado="REVISAR",revisado_por=NULL,revisado_utc=NULL WHERE id_compensacion='.$canjeHistorico);
check(!tiempo_revisar_compensacion($canjeHistorico,true,'Nunca fue autorizado')['ok'],'Canje pasado sin autorización previa no puede aprobarse');
$_SESSION=['id_usuario'=>502,'id_rol_activo'=>3,'rol_activo'=>'PRACTICANTE','id_practicante'=>2];
$denegado=false;try{reportes_gestion_tiempos(22,$origen,$destino);}catch(RuntimeException){$denegado=true;}
check($denegado,'Practicante no accede al informe reservado a supervisores');
check(tiempo_contar_pendientes()===0,'Practicante no recibe contador de expedientes ajenos');
$_SESSION=['id_usuario'=>501,'id_rol_activo'=>2,'rol_activo'=>'SUPERVISOR','id_practicante'=>1];
check(tiempo_contar_pendientes()>0,'Aviso de supervisor incluye canjes reabiertos');
$_SESSION['id_practicante']=2;
check(tiempo_contar_pendientes()===0,'Aviso excluye expedientes de la práctica propia');
