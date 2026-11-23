<?php
declare(strict_types=1);
// Probé la nueva política con personas y datos ficticios de business.php.
if(PHP_SAPI!=='cli' || !isset($fixture,$checks)){exit(1);}
$fixture->exec("INSERT INTO practicas (id_practica,id_practicante) VALUES (55,2);
    INSERT INTO horarios_practicante VALUES (55,55,'Turno autónomo','2026-01-01',NULL,1)");
for($dia=1;$dia<=5;$dia++) {
    $fixture->exec("INSERT INTO detalle_horario_practicante VALUES (55,$dia,'10:00','17:00','13:00','14:00',6,1)");
}
// La prueba fija del calendario no depende de la fecha del equipo.
$fixture->exec('DELETE FROM feriados');
check(tiempo_vencimiento_habil(55,'2026-11-06 15:00:00')===tiempo_fecha_utc('2026-11-13 17:00:00'),'Cinco jornadas posteriores al viernes terminan el viernes siguiente');
$fixture->exec("INSERT INTO feriados (fecha,nombre) VALUES ('2026-11-10','Feriado ficticio')");
check(tiempo_vencimiento_habil(55,'2026-11-06 15:00:00')===tiempo_fecha_utc('2026-11-16 17:00:00'),'Feriado y fin de semana no consumen días de canje');
$fixture->exec('DELETE FROM feriados');
$fixture->exec('UPDATE detalle_horario_practicante SET activo=0 WHERE id_horario=55 AND dia_semana IN (2,4)');
check(tiempo_vencimiento_habil(55,'2026-11-06 15:00:00')===tiempo_fecha_utc('2026-11-18 17:00:00'),'Cinco días hábiles siguen el horario individual lunes miércoles viernes');
$fixture->exec('UPDATE detalle_horario_practicante SET activo=1 WHERE id_horario=55');
$origen=(new DateTimeImmutable('last monday'))->modify('-14 days')->format('Y-m-d');
$fixture->prepare('INSERT INTO jornadas_asistencia (id_jornada,id_practica,fecha,estado_jornada,politica_recuperacion_v2) VALUES (9500,55,?,"OBSERVADA",1)')->execute([$origen]);
foreach([1=>'10:00:00',2=>'13:00:00',3=>'14:00:00',4=>'18:00:00'] as $tipo=>$hora) {
    $fixture->prepare('INSERT INTO marcas_asistencia (id_jornada,id_tipo_marca,fecha_hora) VALUES (9500,?,?)')->execute([$tipo,$origen.' '.$hora]);
}
$_SESSION=['id_usuario'=>502,'id_rol_activo'=>3,'rol_activo'=>'PRACTICANTE','id_practicante'=>2];
$r=tiempo_solicitar(9500,'17:00','18:00','Reunión excepcional ficticia');
check($r['ok'],'Nueva solicitud reconoce trabajo ya realizado');
$id=(int)$fixture->query('SELECT MAX(id_tiempo) FROM tiempos_excepcionales')->fetchColumn();
check((int)tiempo_credito($id)['politica_canje']===2,'Solicitudes nuevas guardan política independiente de los expedientes históricos');
$destino=(new DateTimeImmutable('tomorrow'))->format('Y-m-d');
while((int)date('N',strtotime($destino))>5){$destino=date('Y-m-d',strtotime($destino.' +1 day'));}
check(!tiempo_compensar($id,$destino,'SALIDA','16:00','17:00','Sin aprobar')['ok'],'Canje autónomo sigue exigiendo reconocimiento aprobado');
// Simulé espera prolongada del supervisor sin iniciar todavía el nuevo plazo.
$fixture->prepare('UPDATE tiempos_excepcionales SET vence_utc=? WHERE id_tiempo=?')->execute([gmdate('Y-m-d H:i:s',time()-86400),$id]);
$_SESSION=['id_usuario'=>501,'id_rol_activo'=>2,'rol_activo'=>'SUPERVISOR','id_practicante'=>1];
check(tiempo_revisar($id,true,'Jefe autorizó reunión, marcas revisadas')['ok'],'Aprobación tardía inicia plazo hábil de un crédito nuevo');
$credito=tiempo_credito($id);$vence=$credito['vence_utc'];
check($vence===tiempo_vencimiento_habil(55,$credito['revisado_utc']),'Vencimiento se fija desde la aprobación efectiva');
try {
    $fuera=(new DateTimeImmutable($vence,new DateTimeZone('UTC')))->setTimezone(new DateTimeZone(APP_TIMEZONE))->modify('+7 days')->format('Y-m-d');
    tiempo_validar_destino($credito,$fuera,'SALIDA','16:00','17:00');
    check(false,'Bloquea fecha posterior al quinto día');
} catch(RuntimeException $e) { check(true,'Bloquea fecha posterior al quinto día'); }
$_SESSION=['id_usuario'=>502,'id_rol_activo'=>3,'rol_activo'=>'PRACTICANTE','id_practicante'=>2];
failAudit('CANJEAR_COMPENSACION');
check(!tiempo_compensar($id,$destino,'SALIDA','16:00','17:00','Auditoría ficticia')['ok'],'Fallo de auditoría revierte el canje autónomo');
check(tiempo_saldo(tiempo_credito($id))===60,'Rollback conserva saldo íntegro');
$fixture->exec('DROP TRIGGER fail_audit');
$canjear=tiempo_compensar($id,$destino,'SALIDA','16:00','17:00','Salir antes por reunión');
check($canjear['ok'],'Practicante confirma canje sin una segunda aprobación: '.json_encode($canjear));
$canje=$fixture->query('SELECT * FROM compensaciones_tiempo WHERE id_tiempo='.$id)->fetch();
check($canje['estado']==='APROBADA' && $canje['revisado_por']===null,'Canje queda habilitado sin inventar revisión de un supervisor');
check(tiempo_saldo(tiempo_credito($id))===0,'Canje reserva exactamente el saldo gastado');
check(!tiempo_compensar($id,$destino,'SALIDA','16:00','17:00','Duplicado')['ok'],'No duplica el mismo saldo o la misma jornada');
$_SESSION=['id_usuario'=>501,'id_rol_activo'=>2,'rol_activo'=>'SUPERVISOR','id_practicante'=>1];
$fixture->prepare('UPDATE tiempos_excepcionales SET estado="REVISAR" WHERE id_tiempo=?')->execute([$id]);
check(tiempo_revisar($id,true,'Revalidación ficticia de evidencia')['ok'],'Evidencia puede revisarse conservando la aprobación original');
check(tiempo_credito($id)['vence_utc']===$vence,'Revalidar no renueva cinco jornadas de crédito');
$_SESSION=['id_usuario'=>502,'id_rol_activo'=>3,'rol_activo'=>'PRACTICANTE','id_practicante'=>2];
$fixture->prepare('UPDATE tiempos_excepcionales SET vence_utc=? WHERE id_tiempo=?')->execute([gmdate('Y-m-d H:i:s',time()-1),$id]);
check(tiempo_saldo(tiempo_credito($id))===0,'Saldo vencido no queda disponible aunque se conserve historial');
check(!tiempo_compensar($id,$destino,'ENTRADA','10:00','10:30','Vencido')['ok'],'No permite nuevos canjes con crédito vencido');
