<?php

declare(strict_types=1);

// Integré estos casos en la base aleatoria de tests/notas-semanales.php, nunca en asistencia.
if (PHP_SAPI!=='cli' || !isset($fixture,$compacta,$practicas,$usuarios)) { throw new RuntimeException('Ejecutar mediante tests/notas-semanales.php.'); }
$fechaPublicacion=$otroViernes; $notasCompletas=[1=>'6.5',2=>'7'];
notaSesion($usuarios[2],'PRACTICANTE');
notaRechaza(fn()=>notas_consultar_propia($compacta['id_nota']),'no disponible');
notaSesion($usuarios[3],'PRACTICANTE');
$antesPreview=(int)$fixture->query('SELECT COUNT(*) FROM notas_semanales_historial')->fetchColumn();
notaRechaza(fn()=>notas_publicar($practicas[2],$fechaPublicacion,$notasCompletas,'6.75','',1,'',$rubricaDos,'BORRADOR',false),'Confirma');
notaRechaza(fn()=>notas_preparar_publicacion($practicas[2],$fechaPublicacion,[1=>null,2=>'7'],'6.75','',1,'',$rubricaDos,'BORRADOR'),'Completa todos');
notaRechaza(fn()=>notas_preparar_publicacion($practicas[2],$fechaPublicacion,$notasCompletas,null,'',1,'',$rubricaDos,'BORRADOR'),'Completa todos');
notaRechaza(fn()=>notas_preparar_publicacion($practicas[2],$fechaPublicacion,$notasCompletas,'6.75','',1,'',999,'BORRADOR'),'criterios');
$pre=notas_preparar_publicacion($practicas[2],$fechaPublicacion,$notasCompletas,'6.75','',1,'',$rubricaDos,'BORRADOR');
notaCheck($pre['nota']['estado']==='BORRADOR' && (int)$fixture->query('SELECT COUNT(*) FROM notas_semanales_historial')->fetchColumn()===$antesPreview,'Revisar no publica ni crea revisiones.');
$publicada=notas_publicar($practicas[2],$fechaPublicacion,$notasCompletas,'6.75','',1,'',$rubricaDos,'BORRADOR',true);
notaCheck($publicada['revision']===2 && $publicada['operacion']==='PUBLICAR_NOTA_SEMANAL','Líder publica a su integrante sin paso ADMIN.');
notaRechaza(fn()=>notas_publicar($practicas[2],$fechaPublicacion,$notasCompletas,'6.75','',1,'',$rubricaDos,'BORRADOR',true),'cambió');
notaSesion($usuarios[2],'PRACTICANTE');
$propias=notas_propias_publicadas();
notaCheck(in_array($compacta['id_nota'],array_map('intval',array_column($propias,'id_nota')),true),'La nota aparece en el historial propio al aprobarse.');
notaCheck(notas_consultar_propia($compacta['id_nota'])['nota_final']==='6.75','Detalle propio contiene la versión aprobada.');
notaRechaza(fn()=>notas_historial_revisiones($compacta['id_nota']),'propias');
notaRechaza(fn()=>notas_consultar_propia($nuevaLider['id_nota']),'no disponible');
notaSesion($usuarios[3],'PRACTICANTE');
notaRechaza(fn()=>notas_publicar($practicas[3],$viernes,array_fill(1,12,'7'),'7','',2,'',$rubricaDoce,'BORRADOR',true),'propias');
notaRechaza(fn()=>notas_publicar($practicas[2],$fechaPublicacion,[1=>'7',2=>'7'],'7','',2,'',$rubricaDos,'APROBADA',true),'motivo');
$corregida=notas_publicar($practicas[2],$fechaPublicacion,[1=>'7',2=>'7'],'7','Observación actual',2,'Corrección del responsable',$rubricaDos,'APROBADA',true);
notaCheck($corregida['revision']===3 && $corregida['operacion']==='CORREGIR_NOTA_PUBLICADA','El responsable vigente corrige con motivo.');
$historial=notas_historial_revisiones($compacta['id_nota']);
notaCheck(count($historial)===3 && $historial[0]['contenido']['nota_final']==='7.00' && $historial[1]['contenido']['nota_final']==='6.75','El historial conserva antes y después de una corrección.');
notaSesion($usuarios[2],'PRACTICANTE');
notaCheck(notas_consultar_propia($compacta['id_nota'])['nota_final']==='7.00','La consulta propia muestra la corrección confirmada.');
notaSesion($usuarios[4],'PRACTICANTE');
notaRechaza(fn()=>notas_consultar_propia($compacta['id_nota']),'no disponible');
notaSesion($usuarios[1],'PRACTICANTE');
notaRechaza(fn()=>notas_publicar($practicas[2],$fechaPublicacion,$notasCompletas,'6.75','',3,'Antiguo líder',$rubricaDos,'APROBADA',true),'responsable');
notaSesion($usuarios[3],'SUPERVISOR');
notaRechaza(fn()=>notas_publicar($practicas[2],$fechaPublicacion,$notasCompletas,'6.75','',3,'Rol indebido',$rubricaDos,'APROBADA',true),'autorización');
notaSesion($admin,'ADMIN');
// ADMIN conserva la corrección global después de traslados o bajas, sin reemplazar autor ni rúbrica originales.
$fixture->exec('UPDATE historial_areas_practicante SET fecha_fin=CURRENT_DATE WHERE id_practica='.$practicas[2]);
$fixture->exec('UPDATE practicas SET activo=0 WHERE id_practica='.$practicas[2]);
notas_publicar($practicas[2],$fechaPublicacion,$notasCompletas,'6.75','',3,'Corrección posterior a baja',$rubricaDos,'APROBADA',true);
$administrativa=notas_consultar($compacta['id_nota']);
notaCheck($administrativa['estado']==='APROBADA' && (int)$administrativa['id_evaluador']===$usuarios[3] && (int)$administrativa['aprobado_por']===$admin,'ADMIN corrige sin borrar el autor y publica con su identidad.');
$fixture->exec('UPDATE historial_areas_practicante SET fecha_fin=NULL WHERE id_practica='.$practicas[2]);
$fixture->exec('UPDATE practicas SET activo=1 WHERE id_practica='.$practicas[2]);
$aprobacionLider=notas_publicar($practicas[3],$viernes,array_fill(1,12,'7'),'7','',2,'',$rubricaDoce,'BORRADOR',true);
notaCheck($aprobacionLider['operacion']==='PUBLICAR_NOTA_SEMANAL','ADMIN publica las notas de líderes.');
notaSesion($usuarios[3],'PRACTICANTE');
notaCheck(notas_consultar_propia($nuevaLider['id_nota'])['estado']==='APROBADA','El líder consulta su nota después de aprobación ADMIN.');
notaRechaza(fn()=>notas_publicar($practicas[3],$viernes,array_fill(1,12,'7'),'7','',3,'Auto corrección',$rubricaDoce,'APROBADA',true),'propias');
// Un borrador normal de integrante no obtiene aprobación ADMIN como paso alternativo automático.
$fechaNueva=(new DateTimeImmutable($viernes))->modify('-21 days')->format('Y-m-d');
$pendiente=notas_guardar_borrador($practicas[5],$fechaNueva,$notasCompletas,'6.75','',0,'',$rubricaDos);
notaSesion($admin,'ADMIN');
notaRechaza(fn()=>notas_publicar($practicas[5],$fechaNueva,$notasCompletas,'6.75','',1,'Intento ADMIN',$rubricaDos,'BORRADOR',true),'corresponde a su líder');
// El borrador del líder anterior sí puede resolverse excepcionalmente por ADMIN, con motivo y sin reasignación.
$huerfana=notas_consultar($primera['id_nota']);
notaRechaza(fn()=>notas_publicar($practicas[2],$viernes,$valores,'6.5','',(int)$huerfana['revision'],'',(int)$huerfana['id_rubrica'],'BORRADOR',true),'motivo');
$resuelta=notas_publicar($practicas[2],$viernes,$valores,'6.5','',(int)$huerfana['revision'],'Resolución por reemplazo de líder',(int)$huerfana['id_rubrica'],'BORRADOR',true);
notaCheck($resuelta['operacion']==='RESOLVER_BORRADOR_NOTA' && (int)notas_consultar($resuelta['id_nota'])['id_evaluador']===$usuarios[1],'ADMIN resuelve sin borrar el autor anterior.');
// Simulé fallo de auditoría para probar que publicar no deje cambios parciales visibles.
$sinPublicar=notas_consultar($notaLider['id_nota']); $conteoAntes=(int)$fixture->query('SELECT COUNT(*) FROM notas_semanales_historial')->fetchColumn();
$fixture->exec("CREATE TRIGGER notas_sin_auditoria BEFORE INSERT ON auditoria_cambios FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Fallo simulado'");
try {
    notas_publicar($practicas[1],$viernes,array_fill(1,10,'6.5'),'6.5','',(int)$sinPublicar['revision'],'',(int)$sinPublicar['id_rubrica'],'BORRADOR',true);
    throw new LogicException('Se publicó sin auditoría.');
} catch (RuntimeException $e) { notaCheck(str_contains($e->getMessage(),'auditoría'),'Fallo de auditoría esperado.'); }
notaCheck(notas_consultar($notaLider['id_nota'])['estado']==='BORRADOR' && (int)$fixture->query('SELECT COUNT(*) FROM notas_semanales_historial')->fetchColumn()===$conteoAntes,'Rollback conserva borrador, detalle e historial.');
$fixture->exec('DROP TRIGGER notas_sin_auditoria');
