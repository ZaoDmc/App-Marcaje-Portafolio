<?php
declare(strict_types=1);
// Probé el corte semanal y la aprobación posterior con datos ficticios en memoria.
if(PHP_SAPI!=='cli' || !isset($fixture,$checks)){exit(1);}
$GLOBALS['politica_recuperacion_prueba']=2;
try {
    $corte=recuperaciones_corte_sabado('2026-11-07');
    check($corte===tiempo_fecha_utc('2026-11-06 12:00:00'),'Corte del sábado usa viernes 12:00 de Chile');
    check(recuperaciones_dentro_plazo('2026-11-07',tiempo_fecha_utc('2026-11-06 11:59:59')),'11:59:59 permite enviar');
    check(!recuperaciones_dentro_plazo('2026-11-07',$corte),'12:00 exactas rechaza automáticamente');
    check(!recuperaciones_dentro_plazo('2026-11-07',tiempo_fecha_utc('2026-11-06 15:00:00')),'Emergencia del viernes después de corte no habilita ese sábado');
    check(recuperaciones_dentro_plazo('2026-11-14',tiempo_fecha_utc('2026-11-06 15:00:00')),'Emergencia del viernes puede elegir sábado siguiente');
    $fixture->exec("INSERT INTO practicas (id_practica,id_practicante,id_tipo_practica) VALUES (66,2,1);
        INSERT INTO horarios_practicante VALUES (66,66,'Sábados ficticios','2026-01-01',NULL,1)");
    for($dia=1;$dia<=5;$dia++) {$fixture->exec("INSERT INTO detalle_horario_practicante VALUES (66,$dia,'10:00','17:00','13:00','14:00',6,1)");}
    $sabado=(new DateTimeImmutable('next saturday'))->format('Y-m-d');
    while(!recuperaciones_dentro_plazo($sabado,gmdate('Y-m-d H:i:s'))){$sabado=date('Y-m-d',strtotime($sabado.' +7 days'));}
    $ausencia=['fecha_ausencia'=>date('Y-m-d',strtotime($sabado.' -5 days')),'tipo_ausencia'=>'PARCIAL','hora_inicio_ausencia'=>'10:00','hora_fin_ausencia'=>'13:00'];
    $plan=[['fecha'=>$sabado,'tipo'=>'SABADO','hora_inicio'=>'10:00','hora_fin'=>'13:00']];
    check(recuperaciones_validar_plan(66,$plan,180,$ausencia)['errores']===[],'Nuevo plan acepta sábado posterior a ausencia y anterior a corte');
    $planLargo=[['fecha'=>$sabado,'tipo'=>'SABADO','hora_inicio'=>'10:00','hora_fin'=>'20:00']];
    check(recuperaciones_validar_plan(66,$planLargo,540,$ausencia)['errores']===[],'Sábado 10:00–20:00 descuenta 1 h de colación y suma 540 min (9 hrs)');
    $planOcho=[['fecha'=>$sabado,'tipo'=>'SABADO','hora_inicio'=>'10:00','hora_fin'=>'18:00']];
    check(recuperaciones_validar_plan(66,$planOcho,420,$ausencia)['errores']===[],'Sábado 10:00–18:00 descuenta 1 h de colación y suma 420 min (7 hrs)');
    $malo=$plan;$malo[0]['fecha']=date('Y-m-d',strtotime($sabado.' -1 day'));$malo[0]['tipo']='EXTENSION';$malo[0]['hora_inicio']='17:00';$malo[0]['hora_fin']='20:00';
    check(recuperaciones_validar_plan(66,$malo,180,$ausencia)['errores']!==[],'Rechaza extensión del viernes');
    $malo=$plan;$malo[0]['tipo']='COLACION';$malo[0]['politica_recuperacion']=1;
    check(recuperaciones_validar_plan(66,$malo,180,$ausencia)['errores']!==[],'Formulario no puede forzar política histórica para recuperar colación');
    check(recuperaciones_opciones_fecha(66,date('Y-m-d',strtotime($sabado.' -1 day')))===[],'API no ofrece recuperación entre semana');
    check(recuperaciones_opciones_fecha(66,$sabado)[0]['tipo']==='SABADO','Sábado sin jornada permite recuperar 10–20');
    $fixture->exec("INSERT INTO detalle_horario_practicante VALUES (66,6,'10:00','17:00','13:00','14:00',6,1)");
    $opcionesSab=recuperaciones_opciones_fecha(66,$sabado);
    check(count($opcionesSab)===1 && $opcionesSab[0]['inicio']==='17:00','Sábado con jornada solo ofrece tramo exterior, sin convertir almuerzo en recuperación');
    $fixture->exec('DELETE FROM detalle_horario_practicante WHERE id_horario=66 AND dia_semana=6');
    $heredado=[['fecha'=>date('Y-m-d',strtotime($sabado.' -1 day')),'tipo'=>'EXTENSION','hora_inicio'=>'17:00','hora_fin'=>'20:00','politica_recuperacion'=>1]];
    check(recuperaciones_validar_plan(66,$heredado,180,$ausencia,true)['errores']===[],'Revisión conserva un tramo histórico sin aplicar retroactivamente la nueva modalidad');
    $fixture->prepare('INSERT INTO feriados (fecha,nombre) VALUES (?,"Feriado ficticio")')->execute([$sabado]);
    check(recuperaciones_opciones_fecha(66,$sabado)[0]['tipo']==='FERIADO','Sábado feriado conserva requisito de autorización especial');
    $fixture->prepare('DELETE FROM feriados WHERE fecha=?')->execute([$sabado]);
    $_SESSION=['id_usuario'=>502,'id_rol_activo'=>3,'rol_activo'=>'PRACTICANTE','id_practicante'=>2];
    // La práctica más reciente se elige por ID en ausencia de diferencia de fecha inicial.
    $input=$ausencia+['motivo'=>'Permiso ficticio','recuperaciones'=>$plan];
    $r=permisos_gestion_solicitar(2,$input);
    check($r['ok'],'Solicitud propia guarda plan nuevo sabatino');
    $id=(int)($r['id_permiso']??0);$tramo=recuperaciones_plan($id)[0];
    check((int)$tramo['politica_recuperacion']===2 && $tramo['solicitado_utc']!==null,'Persistencia conserva política y fecha UTC original');
    $_SESSION=['id_usuario'=>501,'id_rol_activo'=>2,'rol_activo'=>'SUPERVISOR','id_practicante'=>1];
    check(permisos_gestion_aprobar($id,501,'Solicitud recibida a tiempo')['ok'],'Superior aprueba un plan sabatino válido');

    // Simulé solicitud recibida antes del corte, revisada varias semanas después de trabajar.
    $pasado=(new DateTimeImmutable('last saturday'))->modify('-14 days')->format('Y-m-d');
    $ausenciaPasada=date('Y-m-d',strtotime($pasado.' -5 days'));
    $fixture->prepare('INSERT INTO permisos_practicante (id_practica,fecha_ausencia,tipo_ausencia,hora_inicio_ausencia,hora_fin_ausencia,horas_ausencia,horas_trabajo_dia,nuevo_horario_inicio,nuevo_horario_fin,fecha_recuperacion,hora_inicio_recuperacion,hora_fin_recuperacion) VALUES (66,?,"PARCIAL","10:00","13:00",3,3,"13:00","17:00",?,"10:00","13:00")')->execute([$ausenciaPasada,$pasado]);
    $tardio=(int)$fixture->lastInsertId();
    $fixture->prepare('INSERT INTO recuperaciones_permiso (id_permiso,fecha,tipo,hora_inicio,hora_fin,minutos_planificados,politica_recuperacion,solicitado_utc) VALUES (?, ?,"SABADO","10:00","13:00",180,2,?)')->execute([$tardio,$pasado,date('Y-m-d H:i:s',strtotime(recuperaciones_corte_sabado($pasado).' -1 second'))]);
    $fixture->prepare('INSERT INTO jornadas_asistencia (id_jornada,id_practica,fecha,estado_jornada,politica_recuperacion_v2) VALUES (9600,66,?,"OBSERVADA",1)')->execute([$pasado]);
    $fixture->prepare('INSERT INTO marcas_asistencia (id_jornada,id_tipo_marca,fecha_hora) VALUES (9600,1,?),(9600,4,?)')->execute([$pasado.' 10:00:00',$pasado.' 13:00:00']);
    check(permisos_gestion_aprobar($tardio,501,'Validé sábado realizado solicitado antes del corte')['ok'],'Aprobación tardía usa fecha de solicitud, no fecha de revisión');
    check((int)recuperaciones_plan($tardio)[0]['minutos_validados']===180,'Aprobación tardía acredita marcas del sábado');

    $_SESSION=['id_usuario'=>502,'id_rol_activo'=>3,'rol_activo'=>'PRACTICANTE','id_practicante'=>2];
    $input['fecha_ausencia']=$ausenciaPasada;
    $input['recuperaciones'][0]['fecha']=$pasado;
    $antes=(int)$fixture->query('SELECT COUNT(*) FROM permisos_practicante')->fetchColumn();
    $fuera=permisos_gestion_solicitar(2,$input);
    check(!$fuera['ok'] && in_array(RECUPERACION_FUERA_PLAZO,$fuera['errores'],true),'Solicitud enviada después del corte recibe rechazo explícito');
    check((int)$fixture->query('SELECT COUNT(*) FROM permisos_practicante')->fetchColumn()===$antes,'Rechazo no crea permiso pendiente ni reserva');
    check((int)$fixture->query('SELECT COUNT(*) FROM auditoria_cambios WHERE evento="RECHAZO_PLAZO_RECUPERACION"')->fetchColumn()>0,'Rechazo fuera de plazo deja auditoría');
} finally {unset($GLOBALS['politica_recuperacion_prueba']);}
