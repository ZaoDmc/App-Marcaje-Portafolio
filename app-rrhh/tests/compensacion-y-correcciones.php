<?php
declare(strict_types=1);
// Agregué regresiones aisladas; business.php proporciona únicamente la base SQLite ficticia.
if(PHP_SAPI!=='cli' || !isset($fixture,$checks)){exit(1);}
$fixture->exec("INSERT INTO practicas (id_practica,id_practicante) VALUES (44,2);
    INSERT INTO horarios_practicante VALUES (44,44,'Viernes ficticio','2000-01-01',NULL,1);
    INSERT INTO detalle_horario_practicante VALUES (44,5,'10:00:00','17:00:00','13:00:00','14:00:00',6,1)");
// Fijé las fechas para probar el encaje del horario sin depender de la hora de ejecución.
$viernes='2026-10-23';
$creditoViernes=['id_practica'=>44,'solicitado_utc'=>'2026-10-22 12:00:00','vence_utc'=>'2026-10-29 12:00:00'];
check(tiempo_validar_destino($creditoViernes,$viernes,'SALIDA','16:00','17:00',false)===60,'Viernes de 10 a 17 acepta salir de 16 a 17 con horas TIME completas');
check(tiempo_validar_destino($creditoViernes,$viernes,'ENTRADA','10:00','11:00',false)===60,'Entrada compensada comienza en el inicio del turno');
$rechazaCon=static function(string $tipo,string $inicio,string $fin,string $texto) use ($creditoViernes,$viernes): bool {
    try { tiempo_validar_destino($creditoViernes,$viernes,$tipo,$inicio,$fin,false); }
    catch(RuntimeException $e){return str_contains($e->getMessage(),$texto);}
    return false;
};
check($rechazaCon('SALIDA','16:00','16:30','terminar a las 17:00'),'Rechazo informa la salida vigente en vez del mensaje genérico');
check($rechazaCon('ENTRADA','11:00','12:00','comenzar a las 10:00'),'Rechazo informa la entrada vigente');
check($rechazaCon('SALIDA','13:00','17:00','colación vigente de 13:00 a 14:00'),'Colación mantiene su restricción con un mensaje específico');
$fixture->exec("INSERT INTO excepciones_horario_practicante (id_horario,fecha,tipo_excepcion,entrada_esperada,salida_esperada,colacion_inicio,colacion_fin,horas_planificadas) VALUES (44,'2026-10-23','CAMBIO_HORARIO','10:00:00','18:00:00','13:00:00','14:00:00',7)");
check($rechazaCon('SALIDA','16:00','17:00','terminar a las 18:00'),'Una excepción de viernes prevalece sobre el horario habitual y se explica');
check(tiempo_validar_destino($creditoViernes,$viernes,'SALIDA','17:00','18:00',false)===60,'El canje se ajusta a la salida excepcional efectiva');
try { tiempo_validar_destino($creditoViernes,'2026-10-24','SALIDA','16:00','17:00',false); check(false,'Día sin turno se rechaza'); }
catch(RuntimeException $e){check(str_contains($e->getMessage(),'No hay jornada laboral vigente'),'Día sin turno tiene un error específico');}

// Reproduje la corrección real del supervisor, no una aprobación excepcional directa por SQL.
$diaCorregido=date('Y-m-d',strtotime('-80 days'));
while((int)date('N',strtotime($diaCorregido))>5 || (int)$fixture->query("SELECT COUNT(*) FROM jornadas_asistencia WHERE id_practica=22 AND fecha='$diaCorregido'")->fetchColumn()>0){$diaCorregido=date('Y-m-d',strtotime($diaCorregido.' -1 day'));}
tiempo_test_jornada(9100,$diaCorregido,'09:00:00','18:00:00');
$fixture->exec("UPDATE marcas_asistencia SET anulada=1 WHERE id_jornada=9100 AND id_tipo_marca=4");
$_SESSION=['id_usuario'=>501,'id_rol_activo'=>2,'rol_activo'=>'SUPERVISOR','id_practicante'=>1];
check(asistencia_gestion_registrar_marca_correctiva(9100,'SALIDA',$diaCorregido.'T19:00','Salida corregida por reunión')['ok'],'Supervisor incorpora una salida correctiva ya ocurrida');
check(asistencia_gestion_marcar_revisada(9100,'Corrección de salida acreditada','CORREGIDA')['ok'],'Supervisor puede dejar la jornada corregida sin aprobar crédito');
$_SESSION=['id_usuario'=>502,'id_rol_activo'=>3,'rol_activo'=>'PRACTICANTE','id_practicante'=>2];
check(in_array(9100,array_map('intval',array_column(tiempo_jornadas_solicitables(22),'id_jornada')),true),'La jornada corregida aparece entre las solicitables del practicante');
$solicitudCorregida=tiempo_solicitar(9100,'18:00','19:00','Reunión excepcional con salida corregida');
check($solicitudCorregida['ok'],'Practicante solicita tiempo acreditado por la corrección del supervisor');
$idCorregido=(int)$fixture->query('SELECT MAX(id_tiempo) FROM tiempos_excepcionales')->fetchColumn();
$creditoCorregido=tiempo_credito($idCorregido);
check($creditoCorregido['estado']==='PENDIENTE' && tiempo_saldo($creditoCorregido)===0,'La corrección no salta la aprobación excepcional ni crea saldo automático');
$_SESSION=['id_usuario'=>501,'id_rol_activo'=>2,'rol_activo'=>'SUPERVISOR','id_practicante'=>1];
check(tiempo_revisar($idCorregido,true,'Jefe autorizó la reunión y se verificó la corrección')['ok'],'Supervisor aprueba por separado el tiempo de la jornada corregida');
check(tiempo_saldo(tiempo_credito($idCorregido))===60,'La aprobación separada habilita los sesenta minutos corregidos');
