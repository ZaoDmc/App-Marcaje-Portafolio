<?php
declare(strict_types=1);
// Agregué pruebas integradas de los servicios reales con SQLite; se incluye al final de business.php.
if(PHP_SAPI!=='cli' || !isset($fixture,$checks)) { exit(1); }
$fixture->exec('DELETE FROM permisos_practicante; DELETE FROM recuperaciones_permiso; DELETE FROM feriados; DELETE FROM excepciones_horario_practicante;');
$corte=(int)$fixture->query('SELECT COALESCE(MAX(id_jornada),0) FROM jornadas_asistencia')->fetchColumn();
// Aislé las fechas del escenario nuevo; conservé la jornada que representa el corte histórico.
$fixture->prepare('DELETE FROM marcas_asistencia WHERE id_jornada<>?')->execute([$corte]);
$fixture->prepare('DELETE FROM jornadas_asistencia WHERE id_jornada<>?')->execute([$corte]);
$fixture->prepare('INSERT INTO politica_tiempo_excepcional VALUES (1,?,?)')->execute([tiempo_ahora_utc(),$corte]);
check(tiempo_vencimiento('2026-10-15 14:00:00')==='2026-10-22 14:00:00','Crédito vence exactamente siete días desde la solicitud');
check(tiempo_vencimiento('2026-09-01 14:00:00')==='2026-09-08 14:00:00','Cambio de hora no altera las 168 horas del plazo UTC');
check(!asistencia_gestion_aprobar_horas_extra(7000,'1','Prueba')['ok'],'POST antiguo no puede acumular nuevas extras');
$ayer=date('Y-m-d',strtotime('-1 day'));
while((int)date('N',strtotime($ayer))>5){$ayer=date('Y-m-d',strtotime($ayer.' -1 day'));}
$futuro=date('Y-m-d',strtotime('+1 day'));
while((int)date('N',strtotime($futuro))>5){$futuro=date('Y-m-d',strtotime($futuro.' +1 day'));}
function tiempo_test_jornada(int $id,string $fecha,string $entrada,string $salida): void
{
    db()->prepare('INSERT INTO jornadas_asistencia (id_jornada,id_practica,fecha,estado_jornada,politica_recuperacion_v2) VALUES (?,22,?,"VALIDA",1)')->execute([$id,$fecha]);
    foreach(['ENTRADA'=>$entrada,'ENTRADA_COLACION'=>'13:00:00','SALIDA_COLACION'=>'14:00:00','SALIDA'=>$salida] as $codigo=>$hora) {
        $tipo=['ENTRADA'=>1,'ENTRADA_COLACION'=>2,'SALIDA_COLACION'=>3,'SALIDA'=>4][$codigo];
        db()->prepare('INSERT INTO marcas_asistencia (id_jornada,id_tipo_marca,fecha_hora) VALUES (?,?,?)')->execute([$id,$tipo,$fecha.' '.$hora]);
    }
}
tiempo_test_jornada(7000,$ayer,'09:00:00','19:00:00');
// Reproduje la salida fuera del turno que antes se convertía incorrectamente en VALIDA.
$sinAutorizar=asistencia_recalcular_jornada(7000);
check($sinAutorizar['estado_jornada']==='OBSERVADA','Salida fuera del turno queda observada antes de solicitar o aprobar el tiempo');
check(str_contains(implode(' ',$sinAutorizar['observaciones']),'60 min'),'La incidencia identifica los minutos fuera de horario pendientes');
check($sinAutorizar['horas_extra_sugeridas']===0.0 && $sinAutorizar['horas_calculadas']===8.0,'La incidencia no reactiva extras ni aumenta la base ordinaria');
$_SESSION=['id_usuario'=>502,'id_rol_activo'=>3,'rol_activo'=>'PRACTICANTE','id_practicante'=>2];
check(!tiempo_solicitar(7000,'18:00','19:00','')['ok'],'Solicitud excepcional exige motivo');
check(!tiempo_solicitar(7000,'13:00','14:00','Colación')['ok'],'Colación ordinaria no crea crédito excepcional');
check(!tiempo_solicitar(7000,'19:00','20:00','No trabajado')['ok'],'Sin marcas efectivas no se reconoce tiempo');
$res=tiempo_solicitar(7000,'18:00','19:00','Reunión excepcional autorizada por jefe');
check($res['ok'],'Practicante solicita un tramo excepcional efectivamente trabajado: '.json_encode($res));
if(!$res['ok']) { throw new RuntimeException(implode(' ',$res['errores'])); }
$id=(int)$fixture->lastInsertId();$id=(int)$fixture->query('SELECT MAX(id_tiempo) FROM tiempos_excepcionales')->fetchColumn();
// Conservé este recorrido como regresión de expedientes históricos; el nuevo flujo tiene casos propios.
$fixture->prepare('UPDATE tiempos_excepcionales SET politica_canje=1 WHERE id_tiempo=?')->execute([$id]);
$credito=tiempo_credito($id);$vencimiento=$credito['vence_utc'];
check((int)$credito['minutos_solicitados']===60 && tiempo_saldo($credito)===0,'Solicitud pendiente no genera saldo utilizable');
check(!tiempo_solicitar(7000,'18:30','19:00','Duplicado')['ok'],'Solicitud solapada no duplica minutos');
check(!tiempo_revisar($id,true,'Me apruebo')['ok'],'Practicante no puede aprobar su solicitud');
$_SESSION=['id_usuario'=>501,'id_rol_activo'=>2,'rol_activo'=>'SUPERVISOR','id_practicante'=>1];
check(!tiempo_revisar($id,true,'')['ok'],'Supervisor debe documentar motivo de autorización del jefe');
failAudit('REVISAR_TIEMPO_EXCEPCIONAL');
check(!tiempo_revisar($id,true,'Jefe autorizó reunión')['ok'],'Fallo de auditoría impide aprobar el crédito');
check(tiempo_credito($id)['estado']==='PENDIENTE','Rollback conserva solicitud sin crédito utilizable');
$fixture->exec('DROP TRIGGER fail_audit');
$_SESSION['id_practicante']=2;
check(!tiempo_revisar($id,true,'Revisar propio')['ok'],'Supervisor con rol practicante no puede aprobarse a sí mismo');
$_SESSION['id_practicante']=1;
check(tiempo_revisar($id,true,'Jefe autorizó reunión de cierre')['ok'],'Supervisor aprueba únicamente tiempo comprobado');
$credito=tiempo_credito($id);
check((int)$credito['minutos_aprobados']===60 && tiempo_saldo($credito)===60,'Aprobación habilita sesenta minutos separados');
check($credito['vence_utc']===$vencimiento,'Aprobación no desplaza el vencimiento');
check(asistencia_jornada_por_id(7000)['estado_jornada']==='VALIDA','Aprobar el tiempo elimina su incidencia pendiente mediante recálculo de la fuente');
check(!tiempo_revisar($id,true,'Repetir')['ok'],'No se aprueba dos veces el mismo crédito');
$resumen=asistencia_recalcular_jornada(7000);
check($resumen['horas_calculadas']===8.0 && $resumen['horas_extra_sugeridas']===0.0,'Fuente reconoce base ordinaria y no suma el crédito excepcional al avance');
check(asistencia_jornada_por_id(7000)['horas_extra_aprobadas']===null,'Columna histórica no recibe nuevas extras');
$_SESSION=['id_usuario'=>502,'id_rol_activo'=>3,'rol_activo'=>'PRACTICANTE','id_practicante'=>1];
check(!tiempo_compensar($id,$futuro,'ENTRADA','09:00','09:30','Ajeno')['ok'],'No se canjea saldo de otro practicante');
$_SESSION['id_practicante']=2;
$fixture->prepare('INSERT INTO feriados (fecha,nombre) VALUES (?,"Feriado ficticio")')->execute([$futuro]);
check(!tiempo_compensar($id,$futuro,'ENTRADA','09:00','09:30','Feriado')['ok'],'Compensación no consume crédito en un feriado sin jornada laboral');
$fixture->exec('DELETE FROM feriados');
check(!tiempo_compensar($id,$futuro,'ENTRADA','09:00','11:00','Excede')['ok'],'Reserva no supera saldo disponible');
check(!tiempo_compensar($id,$futuro,'ENTRADA','10:00','10:30','Interior')['ok'],'Entrada posterior debe comenzar en el inicio real del turno');
check(tiempo_compensar($id,$futuro,'ENTRADA','09:00','09:30','Compensar reunión')['ok'],'Practicante reserva una parte del saldo');
$comp=(int)$fixture->query('SELECT MAX(id_compensacion) FROM compensaciones_tiempo')->fetchColumn();
check(tiempo_saldo(tiempo_credito($id))===30,'Reserva pendiente ya evita doble gasto');
check(!tiempo_compensar($id,$futuro,'SALIDA','17:30','18:00','Mismo día')['ok'],'Compensación del mismo día no se duplica entre créditos o tramos');
$_SESSION=['id_usuario'=>501,'id_rol_activo'=>2,'rol_activo'=>'SUPERVISOR','id_practicante'=>1];
check(tiempo_revisar_compensacion($comp,false,'Reprogramar reunión')['ok'],'Rechazo libera reserva con historial');
check(tiempo_saldo(tiempo_credito($id))===60,'Saldo se libera tras rechazo');
$_SESSION=['id_usuario'=>502,'id_rol_activo'=>3,'rol_activo'=>'PRACTICANTE','id_practicante'=>2];
check(tiempo_compensar($id,$futuro,'ENTRADA','09:00','10:00','Canjear reunión')['ok'],'Puede volver a solicitar el saldo liberado');
$comp=(int)$fixture->query('SELECT MAX(id_compensacion) FROM compensaciones_tiempo')->fetchColumn();
$_SESSION=['id_usuario'=>501,'id_rol_activo'=>2,'rol_activo'=>'SUPERVISOR','id_practicante'=>1];
check(tiempo_revisar_compensacion($comp,true,'Ingreso posterior autorizado')['ok'],'Supervisor aprueba reserva futura válida');
check(tiempo_saldo(tiempo_credito($id))===0,'Reserva aprobada consume el presupuesto disponible');
// Modelé un canje ya autorizado antes de comenzar hoy; no hay parámetro de reloj en la aplicación.
$hoy=date('Y-m-d');$solicitudPasada=tiempo_fecha_utc($ayer.' 19:05:00');
$fixture->prepare('UPDATE tiempos_excepcionales SET solicitado_utc=?,vence_utc=? WHERE id_tiempo=?')->execute([$solicitudPasada,tiempo_vencimiento($solicitudPasada),$id]);
$fixture->prepare('UPDATE compensaciones_tiempo SET fecha=?,revisado_utc=? WHERE id_compensacion=?')->execute([$hoy,tiempo_fecha_utc($hoy.' 08:00:00'),$comp]);
tiempo_test_jornada(7001,$hoy,'10:00:00','18:00:00');
$resumen=asistencia_recalcular_jornada(7001);
check($resumen['horas_calculadas']===8.0 && $resumen['flag_atraso']===0,'Entrada compensada conserva ocho horas y elimina solo el atraso autorizado');
$aplicada=$fixture->query('SELECT * FROM compensaciones_tiempo WHERE id_compensacion='.$comp)->fetch();
check($aplicada['estado']==='APLICADA' && (int)$aplicada['minutos_aplicados']===60,'Registro guarda minutos canjeados una sola vez');
check(asistencia_recalcular_jornada(7001)['horas_calculadas']===8.0,'Recálculo repetido no duplica crédito');
$fixture->prepare('UPDATE marcas_asistencia SET fecha_hora=? WHERE id_jornada=7001 AND id_tipo_marca=1')->execute([$hoy.' 09:30:00']);
check(asistencia_recalcular_jornada(7001)['horas_calculadas']===8.0,'Trabajo parcial del tramo reduce el crédito aplicado sin duplicar trabajo');
check((int)$fixture->query('SELECT minutos_aplicados FROM compensaciones_tiempo WHERE id_compensacion='.$comp)->fetchColumn()===30,'Solo media hora no trabajada consume el saldo');
check(tiempo_saldo(tiempo_credito($id))===30,'Minutos no usados vuelven al saldo mientras no venza');
$fixture->prepare('UPDATE tiempos_excepcionales SET vence_utc=? WHERE id_tiempo=?')->execute([gmdate('Y-m-d H:i:s',time()-1),$id]);
check(tiempo_saldo(tiempo_credito($id))===0,'Saldo remanente vencido no se puede canjear');
check((int)$fixture->query('SELECT COUNT(*) FROM tiempos_excepcionales')->fetchColumn()===1,'Vencimiento no elimina el historial');
$fixture->prepare('UPDATE tiempos_excepcionales SET vence_utc=? WHERE id_tiempo=?')->execute([tiempo_vencimiento($solicitudPasada),$id]);
$fixture->prepare('UPDATE marcas_asistencia SET fecha_hora=? WHERE id_jornada=7000 AND id_tipo_marca=4')->execute([$ayer.' 18:30:00']);
asistencia_recalcular_jornada(7000);
check(tiempo_credito($id)['estado']==='REVISAR' && tiempo_saldo(tiempo_credito($id))===0,'Cambio de fuente reabre crédito y bloquea canjes');
check((float)asistencia_jornada_por_id(7001)['horas_calculadas']===7.5,'Recálculo de fuente retira el crédito dependiente del día compensado');
check($fixture->query('SELECT estado FROM compensaciones_tiempo WHERE id_compensacion='.$comp)->fetchColumn()==='REVISAR','Historial conserva canje en revisión sin borrarlo');
check(lastAudit('REABRIR_TIEMPO_EXCEPCIONAL')!==[],'Reapertura de crédito queda auditada');
// Conservé importes previos al corte incluso al corregir marcas con la política activa.
$fixture->prepare('INSERT INTO extras_historicas VALUES (?,0.5)')->execute([$corte]);
$fixture->prepare('UPDATE jornadas_asistencia SET horas_extra_aprobadas=0.5 WHERE id_jornada=?')->execute([$corte]);
asistencia_recalcular_jornada($corte);
check((float)asistencia_jornada_por_id($corte)['horas_extra_aprobadas']===0.5,'Horas extra históricas aprobadas permanecen al recalcular');
// La política excepcional no debe abrir una vía de reconocimiento automático para sábados.
$sabadoNuevo=date('Y-m-d',strtotime('next saturday'));
tiempo_test_jornada(7002,$sabadoNuevo,'09:00:00','18:00:00');
check(asistencia_recalcular_jornada(7002)['horas_calculadas']===0.0,'Sábado nuevo no suma horas sin validación aunque tenga un horario asignado');
$firmaSabado=recuperaciones_firma_marcas(asistencia_marcas_vigentes_jornada(7002));
$fixture->prepare('INSERT INTO permisos_practicante (id_permiso,id_practica,estado,fecha_ausencia,horas_ausencia) VALUES (900,22,"APROBADO",?,1)')->execute([$ayer]);
$fixture->prepare('INSERT INTO recuperaciones_permiso (id_permiso,fecha,tipo,hora_inicio,hora_fin,minutos_planificados,minutos_validados,estado,firma_marcas) VALUES (900,?,"SABADO","14:00","15:00",60,60,"VALIDADA",?)')->execute([$sabadoNuevo,$firmaSabado]);
check(asistencia_recalcular_jornada(7002)['horas_calculadas']===1.0,'Sábado bajo política excepcional conserva únicamente la recuperación validada');
// Un rechazo no debe convertirse en un mecanismo para renovar un saldo ya vencido.
$fixture->prepare('UPDATE tiempos_excepcionales SET estado="RECHAZADO",vence_utc=? WHERE id_tiempo=?')->execute([gmdate('Y-m-d H:i:s',time()-1),$id]);
$_SESSION=['id_usuario'=>502,'id_rol_activo'=>3,'rol_activo'=>'PRACTICANTE','id_practicante'=>2];
check(!tiempo_solicitar(7000,'18:00','18:30','Solicitar de nuevo el vencido')['ok'],'Saldo vencido rechazado no renueva el plazo al volver a solicitar el mismo trabajo');
// Reproduje una corrección de salida futura: el error debe explicar la causa y no guardar ninguna solicitud.
$manana=date('Y-m-d',strtotime('+1 day'));
// Un viernes, mañana y el sábado del escenario anterior coinciden; uso otra fecha futura libre.
while((int)date('N',strtotime($manana))>5 || (int)$fixture->query("SELECT COUNT(*) FROM jornadas_asistencia WHERE id_practica=22 AND fecha='".$manana."'")->fetchColumn()>0){$manana=date('Y-m-d',strtotime($manana.' +1 day'));}
tiempo_test_jornada(7003,$manana,'09:00:00','19:30:00');
$antesSolicitudes=(int)$fixture->query('SELECT COUNT(*) FROM tiempos_excepcionales')->fetchColumn();
$futura=tiempo_solicitar(7003,'18:00','19:30','Reunión que todavía no terminó');
check(!$futura['ok'] && str_contains(implode(' ',$futura['errores']),'futuro'),'Solicitud con salida futura explica la hora futura y no un cruce con permisos normales');
check((int)$fixture->query('SELECT COUNT(*) FROM tiempos_excepcionales')->fetchColumn()===$antesSolicitudes,'Salida futura rechazada no crea crédito ni solicitud excepcional');
check(asistencia_recalcular_jornada(7003)['estado_jornada']==='OBSERVADA','Las marcas futuras no se presentan como jornada válida');
// Una aprobación parcial no debe cerrar el resto del excedente todavía sin autorizar.
$diaParcial=date('Y-m-d',strtotime($ayer.' -20 days'));
while((int)date('N',strtotime($diaParcial))>5 || (int)$fixture->query("SELECT COUNT(*) FROM jornadas_asistencia WHERE id_practica=22 AND fecha='".$diaParcial."'")->fetchColumn()>0){$diaParcial=date('Y-m-d',strtotime($diaParcial.' -1 day'));}
tiempo_test_jornada(7004,$diaParcial,'09:00:00','19:30:00');
check(tiempo_solicitar(7004,'18:00','18:30','Primera media hora autorizada')['ok'],'Puede solicitar solo una parte del tiempo adicional');
$idParcial=(int)$fixture->query('SELECT MAX(id_tiempo) FROM tiempos_excepcionales')->fetchColumn();
$_SESSION=['id_usuario'=>501,'id_rol_activo'=>2,'rol_activo'=>'SUPERVISOR','id_practicante'=>1];
check(tiempo_revisar($idParcial,true,'Autorizó solo la primera media hora')['ok'],'Supervisor aprueba un tramo parcial del excedente');
$parcial=asistencia_recalcular_jornada(7004);
check($parcial['estado_jornada']==='OBSERVADA' && str_contains(implode(' ',$parcial['observaciones']),'60 min'),'Tras aprobar treinta de noventa minutos, los sesenta restantes mantienen la observación');
// Agregué las reglas confirmadas de treinta minutos por extremo después de probar los créditos excepcionales.
require __DIR__.'/validacion-operativa.php';
require __DIR__.'/tolerancia-marcajes.php';
require __DIR__.'/compensacion-y-correcciones.php';
require __DIR__.'/canje-autonomo.php';
require __DIR__.'/permisos-bandeja.php';
require __DIR__.'/recuperaciones-sabados.php';
require __DIR__.'/cambio-colacion.php';
