<?php
declare(strict_types=1);
// Ejecuté servicios reales con SQLite ficticio, sin MySQL, correo ni datos personales.
if(PHP_SAPI!=='cli' || !isset($fixture,$checks)){exit(1);}
$fixture->exec("INSERT INTO practicas (id_practica,id_practicante,id_tipo_practica) VALUES (77,2,1);
 INSERT INTO horarios_practicante VALUES (77,77,'Colación ficticia','2026-01-01',NULL,1)");
for($dia=1;$dia<=7;$dia++){$fixture->exec("INSERT INTO detalle_horario_practicante VALUES (77,$dia,'10:00','18:00','13:00','14:00',7,1)");}
$_SESSION=['id_usuario'=>502,'id_rol_activo'=>3,'rol_activo'=>'PRACTICANTE','id_practicante'=>2];
check(!colacion_solicitar('12:00','Inválida')['ok'],'No solicita una franja fuera de 13–14 o 14–15');
check(!colacion_solicitar('13:00','Misma franja')['ok'],'No solicita la misma colación vigente');
check(!colacion_solicitar('14:00','')['ok'],'Cambio de colación requiere motivo');
failAudit('SOLICITAR_CAMBIO_COLACION');
check(!colacion_solicitar('14:00','Prueba ficticia')['ok'],'Fallo de auditoría revierte solicitud');
check((int)$fixture->query('SELECT COUNT(*) FROM solicitudes_colacion')->fetchColumn()===0,'No queda solicitud parcial');
$fixture->exec('DROP TRIGGER fail_audit');
check(colacion_solicitar('14:00','Cambio ficticio <script>')['ok'],'Practicante solicita para hoy sin enviar fecha o práctica');
$r=$fixture->query('SELECT * FROM solicitudes_colacion')->fetch();$id=(int)$r['id_solicitud'];
check($r['fecha']===date('Y-m-d') && (int)$r['id_practica']===77,'Servidor fija fecha actual y práctica propia');
check(!colacion_solicitar('14:00','Duplicado')['ok'],'Una sola solicitud por día evita cambios enfrentados');
check(substr(asistencia_horario_vigente_dia(77,date('Y-m-d'))['colacion_inicio'],0,5)==='13:00','Pendiente no modifica colación efectiva');
check(!colacion_revisar($id,true,'Autoaprobación')['ok'],'Practicante no aprueba su solicitud');
check(colacion_listar(22)===[],'Practicante no consulta solicitudes de otra práctica');
$_SESSION=['id_usuario'=>501,'id_rol_activo'=>2,'rol_activo'=>'SUPERVISOR','id_practicante'=>2];
check(!colacion_revisar($id,true,'Propia')['ok'],'Supervisor no aprueba su propia colación');
$_SESSION['id_practicante']=1;
check(!colacion_revisar($id,true,'')['ok'],'Revisión requiere motivo');
// Simulé una solicitud enviada durante un día anterior y revisada después de terminarlo.
$pasado=(new DateTimeImmutable('last monday'))->modify('-14 days')->format('Y-m-d');
$fixture->prepare('UPDATE solicitudes_colacion SET fecha=? WHERE id_solicitud=?')->execute([$pasado,$id]);
$fixture->prepare('INSERT INTO jornadas_asistencia (id_jornada,id_practica,fecha,estado_jornada,politica_recuperacion_v2) VALUES (9700,77,?,"OBSERVADA",1)')->execute([$pasado]);
foreach([1=>'10:00:00',2=>'14:00:00',3=>'15:00:00',4=>'18:00:00'] as $tipo=>$hora){$fixture->prepare('INSERT INTO marcas_asistencia (id_jornada,id_tipo_marca,fecha_hora) VALUES (9700,?,?)')->execute([$tipo,$pasado.' '.$hora]);}
$marcas=asistencia_marcas_vigentes_jornada(9700);
failAudit('REVISAR_CAMBIO_COLACION');
check(!colacion_revisar($id,true,'Ensayo de rollback')['ok'],'Auditoría fallida revierte aprobación tardía');
check($fixture->query('SELECT estado FROM solicitudes_colacion WHERE id_solicitud='.$id)->fetchColumn()==='PENDIENTE','Rollback conserva solicitud pendiente');
$fixture->exec('DROP TRIGGER fail_audit');
check(colacion_revisar($id,true,'Colación 14–15 acreditada')['ok'],'Supervisor aprueba tras finalizar la jornada');
check(substr(asistencia_horario_vigente_dia(77,$pasado)['colacion_inicio'],0,5)==='14:00','Aprobación aplica franja solo en fecha elegida');
check(substr(asistencia_horario_vigente_dia(77,date('Y-m-d',strtotime($pasado.' +1 day')))['colacion_inicio'],0,5)==='13:00','El día siguiente conserva horario base');
check(asistencia_marcas_vigentes_jornada(9700)===$marcas,'Aprobación no modifica marcas reales');
check((float)asistencia_jornada_por_id(9700)['horas_calculadas']===7.0,'Recálculo no convierte colación en trabajo extra');
check((int)$fixture->query('SELECT COUNT(*) FROM detalle_horario_practicante WHERE id_horario=77 AND colacion_inicio="13:00"')->fetchColumn()===7,'Horario semanal no se sobrescribe');
check(!colacion_revisar($id,true,'Duplicado')['ok'],'No revisa dos veces la misma solicitud');
// La modificación manual posterior prevalece; no aplica una autorización sobre otro turno.
$fixture->exec('UPDATE detalle_horario_practicante SET salida_esperada="17:00",horas_planificadas=6 WHERE id_horario=77');
check(substr(asistencia_horario_vigente_dia(77,$pasado)['colacion_inicio'],0,5)==='13:00','Una modificación posterior del turno invalida la superposición anterior');
$_SESSION=['id_usuario'=>502,'id_rol_activo'=>3,'rol_activo'=>'PRACTICANTE','id_practicante'=>2];
check(colacion_solicitar('14:00','Nueva solicitud ficticia')['ok'],'Puede solicitar para hoy conservando registro aprobado de otro día');
$nuevo=(int)$fixture->query('SELECT MAX(id_solicitud) FROM solicitudes_colacion')->fetchColumn();
$_SESSION=['id_usuario'=>501,'id_rol_activo'=>2,'rol_activo'=>'ADMIN','id_practicante'=>null];
check(colacion_revisar($nuevo,false,'No corresponde esta vez')['ok'],'ADMIN rechaza con motivo');
$_SESSION=['id_usuario'=>502,'id_rol_activo'=>3,'rol_activo'=>'PRACTICANTE','id_practicante'=>2];
check(colacion_solicitar('14:00','Solicitud posterior al rechazo')['ok'],'Puede volver a solicitar tras rechazo sin sobrescribir historial');
check((int)$fixture->query('SELECT COUNT(*) FROM solicitudes_colacion WHERE estado="RECHAZADO"')->fetchColumn()===1,'Se conserva el rechazo original');
// Aprobar una colación no suple la falta de marcas de pausa.
$ultimo=(int)$fixture->query('SELECT MAX(id_solicitud) FROM solicitudes_colacion')->fetchColumn();
$otroPasado=date('Y-m-d',strtotime($pasado.' +1 day'));
$fixture->prepare('UPDATE solicitudes_colacion SET fecha=? WHERE id_solicitud=?')->execute([$otroPasado,$ultimo]);
$fixture->prepare('INSERT INTO jornadas_asistencia (id_jornada,id_practica,fecha,estado_jornada,politica_recuperacion_v2) VALUES (9701,77,?,"OBSERVADA",1)')->execute([$otroPasado]);
$fixture->prepare('INSERT INTO marcas_asistencia (id_jornada,id_tipo_marca,fecha_hora) VALUES (9701,1,?),(9701,4,?)')->execute([$otroPasado.' 10:00:00',$otroPasado.' 17:00:00']);
$_SESSION=['id_usuario'=>501,'id_rol_activo'=>2,'rol_activo'=>'ADMIN','id_practicante'=>null];
check(colacion_revisar($ultimo,true,'Cambio autorizado con marcas de pausa pendientes')['ok'],'ADMIN puede aprobar ajuste tardío');
check(asistencia_jornada_por_id(9701)['estado_jornada']==='OBSERVADA','Aprobación conserva observación por colación no marcada');
$hInversa=asistencia_horario_vigente_dia(77,date('Y-m-d',strtotime('+7 days')));$hInversa['colacion_inicio']='14:00';$hInversa['colacion_fin']='15:00';
colacion_validar_horario(77,date('Y-m-d',strtotime('+7 days')),'13:00',$hInversa);
check(colacion_fin('13:00')==='14:00','Admite también mover descanso 14–15 a 13–14');
$_SESSION=['id_usuario'=>502,'id_rol_activo'=>3,'rol_activo'=>'PRACTICANTE','id_practicante'=>2];
check(colacion_solicitar('14:00','Solicitud para probar cambio de turno')['ok'],'Puede solicitar hoy tras cierre de otra fecha');
$cambioTurno=(int)$fixture->query('SELECT MAX(id_solicitud) FROM solicitudes_colacion')->fetchColumn();
$fixture->exec('UPDATE detalle_horario_practicante SET entrada_esperada="11:00",horas_planificadas=5 WHERE id_horario=77');
$_SESSION=['id_usuario'=>501,'id_rol_activo'=>2,'rol_activo'=>'SUPERVISOR','id_practicante'=>1];
check(!colacion_revisar($cambioTurno,true,'Intento con turno modificado')['ok'],'Aprobación detecta horario modificado desde solicitud');
$fechaCruce=date('Y-m-d',strtotime('+7 days'));
$fixture->prepare('INSERT INTO permisos_practicante (id_practica,fecha_ausencia,tipo_ausencia,hora_inicio_ausencia,hora_fin_ausencia) VALUES (77,?,"PARCIAL","12:00","14:00")')->execute([$fechaCruce]);
try {colacion_validar_horario(77,$fechaCruce,'14:00',asistencia_horario_vigente_dia(77,$fechaCruce));check(false,'No altera deuda de ausencia al mover la pausa');}
catch(RuntimeException $e){check(str_contains($e->getMessage(),'ausencia solicitada'),'No altera deuda de ausencia al mover la pausa');}
