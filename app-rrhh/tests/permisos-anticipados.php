<?php
declare(strict_types=1);

// Ejecuté este escenario desde business.php con SQLite en memoria y personas ficticias.
if (!isset($fixture) || !($fixture instanceof BusinessTestPDO)) { exit; }
$sesionAnterior = $_SESSION;
try {
    $fixture->exec("INSERT INTO practicantes (id_practicante,nombre_1) VALUES (3,'Anticipada');
        INSERT INTO practicas (id_practica,id_practicante,id_tipo_practica) VALUES (33,3,1);
        INSERT INTO horarios_practicante VALUES (3,33,'Turno de prueba','2026-01-01',NULL,1)");
    for ($dia=1; $dia<=5; $dia++) {
        $fixture->exec("INSERT INTO detalle_horario_practicante VALUES (3,$dia,'10:00','17:00','13:00','14:00',6,1)");
    }
    $viernes=(new DateTimeImmutable('next friday'))->format('Y-m-d');
    $lunes=(new DateTimeImmutable($viernes))->modify('+3 days')->format('Y-m-d');
    $_SESSION['rol_activo']='PRACTICANTE'; $_SESSION['id_practicante']=3;
    $solicitar=[
        'fecha_ausencia'=>$lunes,'tipo_ausencia'=>'PARCIAL','hora_inicio_ausencia'=>'10:00',
        'hora_fin_ausencia'=>'13:00','motivo'=>'Ausencia ficticia con recuperación anterior',
        'recuperaciones'=>[['fecha'=>$viernes,'tipo'=>'EXTENSION','hora_inicio'=>'17:00','hora_fin'=>'20:00']],
    ];
    $r=permisos_gestion_solicitar(3,$solicitar);
    check($r['ok'],'Permite recuperar viernes 17–20 para faltar lunes 10–13');
    $idPermiso=(int)($r['id_permiso']??0);
    check(recuperaciones_del_dia(33,$viernes)===[],'Solicitud pendiente no habilita recuperación diaria');
    check(!permisos_gestion_aprobar($idPermiso,501,'Autoaprobación')['ok'],'Practicante no aprueba su ajuste temporal');
    $_SESSION['rol_activo']='SUPERVISOR'; $_SESSION['id_practicante']=1;
    check(permisos_gestion_aprobar($idPermiso,501,'Autorizo ausencia y recuperación anticipada')['ok'],'Supervisor aprueba plan anticipado');
    check(count(recuperaciones_del_dia(33,$viernes))===1,'Recuperación autorizada aparece el viernes seleccionado');
    $turno=asistencia_horario_vigente_dia(33,$lunes);
    check(substr($turno['entrada_esperada'],0,5)==='13:00','Permiso parcial modifica únicamente la entrada de su fecha');
    $martes=(new DateTimeImmutable($lunes))->modify('+1 day')->format('Y-m-d');
    check(substr(asistencia_horario_vigente_dia(33,$martes)['entrada_esperada'],0,5)==='10:00','Al día siguiente vuelve al horario base sin tarea de restauración');
    check(recuperaciones_del_dia(33,$martes)===[],'La recuperación no se propaga a otro día');
    check((int)$fixture->query('SELECT COUNT(*) FROM detalle_horario_practicante WHERE id_horario=3 AND entrada_esperada="10:00" AND salida_esperada="17:00"')->fetchColumn()===5,'La aprobación conserva intacto el horario semanal');
    $tramo=recuperaciones_plan($idPermiso)[0];
    check(!recuperaciones_validar((int)$tramo['id_recuperacion'],'Aún no trabajado')['ok'],'No valida minutos futuros por aprobar un plan');

    // Simulé un plan solicitado anteriormente, con trabajo ya terminado y ausencia aún futura.
    $pasado=(new DateTimeImmutable('last friday'))->format('Y-m-d');
    $fixture->prepare('UPDATE recuperaciones_permiso SET fecha=? WHERE id_permiso=?')->execute([$pasado,$idPermiso]);
    $fixture->prepare('INSERT INTO jornadas_asistencia (id_jornada,id_practica,fecha,estado_jornada,politica_recuperacion_v2) VALUES (933,33,?,"OBSERVADA",1)')->execute([$pasado]);
    foreach ([1=>'10:00:00',2=>'13:00:00',3=>'14:00:00',4=>'20:00:00'] as $tipo=>$hora) {
        $fixture->prepare('INSERT INTO marcas_asistencia (id_jornada,id_tipo_marca,fecha_hora) VALUES (933,?,?)')->execute([$tipo,$pasado.' '.$hora]);
    }
    check(recuperaciones_validar((int)$tramo['id_recuperacion'],'Tres horas verificadas antes de la ausencia')['ok'],'Valida recuperación terminada aunque aún no ocurra la ausencia');
    check((int)recuperaciones_plan($idPermiso)[0]['minutos_validados']===180,'Reconoce exactamente tres horas comprobadas');
    check((float)asistencia_jornada_por_id(933)['horas_calculadas']===9.0,'Seis horas ordinarias más tres recuperadas sin duplicación');
    check(!recuperaciones_validar((int)$tramo['id_recuperacion'],'Duplicado')['ok'],'Impide duplicar validación anticipada');
    $fixture->prepare('INSERT INTO jornadas_asistencia (id_jornada,id_practica,fecha,estado_jornada) VALUES (934,33,?,"OBSERVADA")')->execute([$lunes]);
    foreach ([1=>'10:00:00',4=>'17:00:00'] as $tipo=>$hora) {
        $fixture->prepare('INSERT INTO marcas_asistencia (id_jornada,id_tipo_marca,fecha_hora) VALUES (934,?,?)')->execute([$tipo,$lunes.' '.$hora]);
    }
    asistencia_recalcular_jornada(934);
    check((int)recuperaciones_plan($idPermiso)[0]['minutos_validados']===0,'Trabajar durante la ausencia reabre el crédito anticipado');

    $_SESSION['rol_activo']='PRACTICANTE'; $_SESSION['id_practicante']=3;
    $solicitar['fecha_ausencia']=(new DateTimeImmutable('last monday'))->format('Y-m-d');
    $solicitar['recuperaciones'][0]['fecha']=(new DateTimeImmutable($viernes))->modify('+7 days')->format('Y-m-d');
    $r=permisos_gestion_solicitar(3,$solicitar);
    check($r['ok'],'Permite solicitar ausencia parcial después de la jornada');
    $_SESSION['rol_activo']='ADMIN'; $_SESSION['id_practicante']=null;
    check(permisos_gestion_aprobar((int)($r['id_permiso']??0),501,'Regularización verificada')['ok'],'ADMIN aprueba regularización pasada con los mismos controles');
    $_SESSION['rol_activo']='PRACTICANTE'; $_SESSION['id_practicante']=3;
    $solicitar['fecha_ausencia']=(new DateTimeImmutable('last tuesday'))->format('Y-m-d');
    $solicitar['tipo_ausencia']='COMPLETA';
    $solicitar['recuperaciones']=[
        ['fecha'=>(new DateTimeImmutable($viernes))->modify('+28 days')->format('Y-m-d'),'tipo'=>'EXTENSION','hora_inicio'=>'17:00','hora_fin'=>'20:00'],
        ['fecha'=>(new DateTimeImmutable($viernes))->modify('+35 days')->format('Y-m-d'),'tipo'=>'EXTENSION','hora_inicio'=>'17:00','hora_fin'=>'20:00'],
    ];
    $completa=permisos_gestion_solicitar(3,$solicitar);
    check($completa['ok'],'Permite jornada completa pasada con recuperación distribuida: '.implode(' ', $completa['errores']??[]));
    $solicitar['fecha_ausencia']='2025-12-31';
    check(!permisos_gestion_solicitar(3,$solicitar)['ok'],'No regulariza ausencias anteriores al inicio de práctica');
    $solicitar['fecha_ausencia']=$martes;
    $solicitar['recuperaciones'][0]['fecha']=$pasado;
    check(!permisos_gestion_solicitar(3,$solicitar)['ok'],'No solicita retroactivamente un tramo de recuperación ya trabajado');
    // Probé además una regularización pasada que contradice marcas existentes.
    $miercoles=(new DateTimeImmutable('last wednesday'))->format('Y-m-d');
    $fixture->prepare('INSERT INTO jornadas_asistencia (id_jornada,id_practica,fecha,estado_jornada) VALUES (935,33,?,"OBSERVADA")')->execute([$miercoles]);
    foreach ([1=>'10:00:00',4=>'17:00:00'] as $tipo=>$hora) {
        $fixture->prepare('INSERT INTO marcas_asistencia (id_jornada,id_tipo_marca,fecha_hora) VALUES (935,?,?)')->execute([$tipo,$miercoles.' '.$hora]);
    }
    $solicitar['fecha_ausencia']=$miercoles;
    $solicitar['tipo_ausencia']='PARCIAL';
    $solicitar['recuperaciones']=[['fecha'=>(new DateTimeImmutable($viernes))->modify('+42 days')->format('Y-m-d'),'tipo'=>'EXTENSION','hora_inicio'=>'17:00','hora_fin'=>'20:00']];
    $cruce=permisos_gestion_solicitar(3,$solicitar);
    check($cruce['ok'],'Ausencia pasada cruzada se admite para revisión');
    $idCruce=(int)($cruce['id_permiso']??0);
    check((int)$fixture->query('SELECT requiere_revision FROM permisos_practicante WHERE id_permiso='.$idCruce)->fetchColumn()===1,'Cruce pasado queda expresamente observado');
    $_SESSION['rol_activo']='SUPERVISOR'; $_SESSION['id_practicante']=1;
    check(!permisos_gestion_aprobar($idCruce,501,'Intento sin corregir')['ok'],'No aprueba regularización pasada que sigue cruzada con trabajo');
    // Representé una solicitud pendiente de varias semanas, con recuperación ya realizada.
    $ausenciaTardia=(new DateTimeImmutable('last monday'))->modify('-14 days')->format('Y-m-d');
    $recuperacionTardia=(new DateTimeImmutable($ausenciaTardia))->modify('+1 day')->format('Y-m-d');
    $fixture->prepare('INSERT INTO permisos_practicante (id_practica,fecha_ausencia,tipo_ausencia,hora_inicio_ausencia,hora_fin_ausencia,horas_ausencia,horas_trabajo_dia,nuevo_horario_inicio,nuevo_horario_fin,fecha_recuperacion,hora_inicio_recuperacion,hora_fin_recuperacion,created_at) VALUES (33,?,"PARCIAL","10:00","13:00",3,3,"13:00","17:00",?,"17:00","20:00",?)')->execute([$ausenciaTardia,$recuperacionTardia,$ausenciaTardia.' 08:00:00']);
    $idTardio=(int)$fixture->lastInsertId();
    recuperaciones_guardar_plan($idTardio,[['fecha'=>$recuperacionTardia,'tipo'=>'EXTENSION','hora_inicio'=>'17:00','hora_fin'=>'20:00','minutos_planificados'=>180]]);
    $fixture->prepare('INSERT INTO jornadas_asistencia (id_jornada,id_practica,fecha,estado_jornada,politica_recuperacion_v2) VALUES (936,33,?,"OBSERVADA",1)')->execute([$recuperacionTardia]);
    foreach ([1=>'10:00:00',2=>'13:00:00',3=>'14:00:00',4=>'20:00:00'] as $tipo=>$hora) {
        $fixture->prepare('INSERT INTO marcas_asistencia (id_jornada,id_tipo_marca,fecha_hora) VALUES (936,?,?)')->execute([$tipo,$recuperacionTardia.' '.$hora]);
    }
    failAudit('APROBAR_PERMISO');
    check(!permisos_gestion_aprobar($idTardio,501,'Revisión tardía ficticia')['ok'],'Fallo de auditoría revierte aprobación tardía y validación conjunta');
    check(recuperaciones_plan($idTardio)[0]['estado']==='PROPUESTA','Rollback tardío no deja crédito reconocido parcialmente');
    $fixture->exec('DROP TRIGGER fail_audit');
    check(permisos_gestion_aprobar($idTardio,501,'Revisé las marcas después de varias semanas')['ok'],'Aprueba permiso realizado hace más de cinco días');
    check((int)recuperaciones_plan($idTardio)[0]['minutos_validados']===180,'Aprobación tardía reconoce automáticamente recuperación comprobada');
    check((float)asistencia_jornada_por_id(936)['horas_calculadas']===9.0,'Recálculo tardío suma seis horas base y tres recuperadas');
    check(asistencia_jornada_por_id(936)['estado_jornada']==='VALIDA','Retira observación resuelta de recuperación sin ocultar incidencias ajenas');
} finally {
    // Retiré solo las filas de esta práctica ficticia; los servicios controlan sus transacciones.
    $fixture->exec('DELETE FROM marcas_asistencia WHERE id_jornada IN (933,934,935,936);
        DELETE FROM jornadas_asistencia WHERE id_practica=33;
        DELETE FROM recuperaciones_permiso WHERE id_permiso IN (SELECT id_permiso FROM permisos_practicante WHERE id_practica=33);
        DELETE FROM permisos_practicante WHERE id_practica=33;
        DELETE FROM excepciones_horario_practicante WHERE id_horario=3;
        DELETE FROM detalle_horario_practicante WHERE id_horario=3;
        DELETE FROM horarios_practicante WHERE id_horario=3;
        DELETE FROM practicas WHERE id_practica=33;
        DELETE FROM practicantes WHERE id_practicante=3');
    $_SESSION=$sesionAnterior;
}
