<?php
declare(strict_types=1);
// Agregué escenarios aislados del cálculo real: los márgenes cambian la incidencia, nunca generan saldo ni extras.
if(PHP_SAPI!=='cli' || !isset($fixture,$checks)){exit(1);}
$fixture->exec('INSERT INTO practicas (id_practica,id_practicante,id_tipo_practica) VALUES (33,2,1)');
$fixture->exec('INSERT INTO horarios_practicante VALUES (33,33,"Horario de tolerancia","2000-01-01",NULL,1)');
for($dia=1;$dia<=7;$dia++){$fixture->exec("INSERT INTO detalle_horario_practicante VALUES (33,$dia,'10:00','17:00','13:00','14:00',6,1)");}
$fechaTolerancia=$ayer;
$fixture->prepare('INSERT INTO jornadas_asistencia (id_jornada,id_practica,fecha,estado_jornada,politica_recuperacion_v2) VALUES (8000,33,?,"VALIDA",1)')->execute([$fechaTolerancia]);
function tolerancia_test_marcas(string $fecha,string $entrada,?string $salida,?array $colacion=['13:00:00','14:00:00']): void
{
    db()->exec('DELETE FROM marcas_asistencia WHERE id_jornada=8000');
    $marcas=[1=>$entrada];if($colacion!==null){$marcas[2]=$colacion[0];$marcas[3]=$colacion[1];}if($salida!==null){$marcas[4]=$salida;}
    $s=db()->prepare('INSERT INTO marcas_asistencia (id_jornada,id_tipo_marca,fecha_hora) VALUES (8000,?,?)');
    foreach($marcas as $tipo=>$hora){$s->execute([$tipo,$fecha.' '.$hora]);}
}
$casos=[
    ['09:55:00','17:20:00','VALIDA',6.0,0,0,'Ejemplo del cliente con cinco minutos antes y veinte después'],
    ['09:55:00','17:50:00','OBSERVADA',6.0,0,0,'Ejemplo del cliente con cincuenta minutos después'],
    ['09:31:00','17:29:00','VALIDA',6.0,0,0,'Veintinueve antes y veintinueve después no se suman'],
    ['09:30:00','17:30:00','VALIDA',6.0,0,0,'Treinta minutos exactos en cada extremo siguen tolerados'],
    ['09:29:59','17:00:00','OBSERVADA',6.0,0,0,'Treinta minutos y un segundo antes requieren observación'],
    ['10:00:00','17:30:01','OBSERVADA',6.0,0,0,'Treinta minutos y un segundo después requieren observación'],
    ['10:30:00','17:00:00','VALIDA',5.5,0,0,'Atraso de treinta minutos cambia estado sin inventar trabajo'],
    ['10:30:01','17:00:00','OBSERVADA',5.5,1,0,'Atraso mayor de treinta conserva su incidencia'],
    ['10:00:00','16:30:00','VALIDA',5.5,0,0,'Salida anticipada de treinta minutos conserva horas efectivas'],
    ['10:00:00','16:29:59','OBSERVADA',5.5,0,1,'Salida anticipada mayor de treinta conserva su incidencia'],
    ['10:15:00','16:45:00','VALIDA',5.5,0,0,'Los márgenes de entrada y salida se evalúan independientemente'],
];
foreach($casos as [$entrada,$salida,$estado,$horas,$atraso,$anticipada,$nombre]){
    tolerancia_test_marcas($fechaTolerancia,$entrada,$salida);
    $r=asistencia_recalcular_jornada(8000);
    check($r['estado_jornada']===$estado,$nombre.': estado');
    check($r['horas_calculadas']===$horas,$nombre.': horas con tope y colación');
    check($r['flag_atraso']===$atraso && $r['flag_salida_anticipada']===$anticipada,$nombre.': flags');
    check($r['horas_extra_sugeridas']===0.0 && (int)$r['flag_horas_extra']===0,$nombre.': no genera extras');
}
tolerancia_test_marcas($fechaTolerancia,'09:31:00','17:29:00');
$r=asistencia_recalcular_jornada(8000);
check($r['observaciones']===[],'Cincuenta y ocho minutos repartidos no aparecen como incidencia ni saldo');
check((int)$fixture->query('SELECT COUNT(*) FROM tiempos_excepcionales WHERE id_jornada=8000')->fetchColumn()===0,'La tolerancia no crea solicitudes ni créditos excepcionales');
tolerancia_test_marcas($fechaTolerancia,'10:00:00','17:00:00',['13:00:00','13:30:00']);
check(asistencia_recalcular_jornada(8000)['horas_calculadas']===6.0,'Colación más corta no supera las seis horas netas pactadas');
tolerancia_test_marcas($fechaTolerancia,'10:00:00','17:00:00',null);
$r=asistencia_recalcular_jornada(8000);
check($r['estado_jornada']==='OBSERVADA' && $r['horas_calculadas']===6.0,'Sin colación conserva la observación y el tope pactado de la nueva regla');
tolerancia_test_marcas($fechaTolerancia,'10:00:00',null);
$r=asistencia_recalcular_jornada(8000);
check($r['estado_jornada']==='INCOMPLETA' && $r['horas_calculadas']===0.0,'La tolerancia no cierra una jornada sin salida');
// Recuperar un permiso sigue separado del tope ordinario y requiere validación previa.
tolerancia_test_marcas($fechaTolerancia,'10:00:00','17:00:00',null);
$firma=recuperaciones_firma_marcas(asistencia_marcas_vigentes_jornada(8000));
$fixture->prepare('INSERT INTO permisos_practicante (id_permiso,id_practica,estado,fecha_ausencia,horas_ausencia) VALUES (990,33,"APROBADO",?,1)')->execute([date('Y-m-d',strtotime($fechaTolerancia.' -2 days'))]);
$fixture->prepare('INSERT INTO recuperaciones_permiso (id_permiso,fecha,tipo,hora_inicio,hora_fin,minutos_planificados,minutos_validados,estado,firma_marcas) VALUES (990,?,"COLACION","13:00","14:00",60,60,"VALIDADA",?)')->execute([$fechaTolerancia,$firma]);
check(asistencia_recalcular_jornada(8000)['horas_calculadas']===7.0,'Recuperación validada de colación conserva su hora separada de seis horas ordinarias');
$fixture->exec('DELETE FROM recuperaciones_permiso WHERE id_permiso=990');
$fechaSabado=date('Y-m-d',strtotime('last saturday'));
$fixture->prepare('UPDATE jornadas_asistencia SET fecha=? WHERE id_jornada=8000')->execute([$fechaSabado]);
tolerancia_test_marcas($fechaSabado,'10:00:00','10:29:00',null);
$r=asistencia_recalcular_jornada(8000);
check($r['estado_jornada']==='OBSERVADA' && $r['horas_calculadas']===0.0,'El margen no evita la validación obligatoria de sábados');
