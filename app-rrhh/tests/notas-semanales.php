<?php

declare(strict_types=1);

// Probé migración y permisos en una base nueva de nombre aleatorio; nunca en asistencia o producción.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
date_default_timezone_set('America/Santiago');
$root = dirname(__DIR__);
$host = getenv('E2E_DB_HOST') ?: '127.0.0.1';
$port = getenv('E2E_DB_PORT') ?: '3306';
if (!in_array($host,['localhost','127.0.0.1'],true) || !ctype_digit($port)) { throw new RuntimeException('MariaDB local requerida.'); }
$options = [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false];
$server = new PDO("mysql:host=$host;port=$port;charset=utf8mb4",getenv('E2E_DB_USER') ?: 'root',getenv('E2E_DB_PASS') ?: '',$options);
$database = 'rrhh_e2e_'.bin2hex(random_bytes(6));
$server->exec("CREATE DATABASE `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$server->exec("USE `$database`");
$fixture = $server;
function db(): PDO { return $GLOBALS['fixture']; }
$sessionDir = $root.'/scratch/notas-sessions';
if (!is_dir($sessionDir)) { mkdir($sessionDir,0700,true); }
ini_set('session.save_path',$sessionDir); ini_set('error_log',$sessionDir.'/expected-errors.log');
session_id('notas-'.bin2hex(random_bytes(8))); session_start();
// Incorporé los proveedores de la pantalla para comprobar también el alcance de selectores e historial.
require_once $root.'/src/Services/PublicacionNotasService.php';
$checks=0;
function notaCheck(bool $ok,string $mensaje): void {
    if (!$ok) { throw new RuntimeException($mensaje); } $GLOBALS['checks']++;
}
function notaRechaza(callable $accion,string $texto): void {
    try { $accion(); } catch (DomainException $e) { notaCheck(str_contains($e->getMessage(),$texto),$e->getMessage()); return; }
    throw new RuntimeException('No se rechazó: '.$texto);
}
function notaSql(string $sql): void {
    // Los scripts DDL contienen varias sentencias; exec las envía sin prepararlas como una consulta única.
    db()->exec(preg_replace('/^\xEF\xBB\xBF/','',$sql));
}
function notaSesion(int $usuario,string $rol): void {
    $s=db()->prepare('SELECT id_rol FROM roles_sistema WHERE nombre=?'); $s->execute([$rol]);
    $_SESSION=['id_usuario'=>$usuario,'id_rol_activo'=>(int)$s->fetchColumn(),'rol_activo'=>$rol];
}
try {
    $patch=file_get_contents($root.'/database/2026-10-19_patch_notas_semanales_010.sql');
    $schema=file_get_contents($root.'/database/schema.sql');
    notaCheck(str_contains($schema,$patch),'Schema y parche deben contener exactamente la misma estructura.');
    // Instalé la base anterior, con datos históricos ficticios, y luego repetí la migración.
    // Retiré también 011 al reconstruir la base anterior a Notas; después pruebo ambas migraciones en orden.
    $patchRubrica=file_get_contents($root.'/database/2026-10-19_patch_rubricas_configurables_011.sql');
    notaSql(str_replace([$patch,$patchRubrica],'',$schema)); notaSql(file_get_contents($root.'/database/seed_catalogos.sql'));
    // Alineé los NOW de la fixture con la hora chilena que usa el servicio; el schema fija UTC.
    $fixture->exec("SET time_zone='".date('P')."'");
    notaCheck(!notas_disponible(),'No se crean tablas al consultar disponibilidad.');
    $fixture->exec("INSERT INTO areas_practica (nombre) VALUES ('Área ficticia A'),('Área ficticia B')");
    $areas=$fixture->query('SELECT id_area FROM areas_practica ORDER BY id_area DESC LIMIT 2')->fetchAll(PDO::FETCH_COLUMN);
    [$areaB,$areaA]=array_map('intval',$areas);
    $estado=(int)$fixture->query('SELECT id_estado_practica FROM estados_practica WHERE nombre="EN_PROGRESO"')->fetchColumn();
    $usuarios=[]; $practicas=[];
    foreach (range(1,6) as $i) {
        $s=$fixture->prepare('INSERT INTO practicantes (nombre_1,apellido_paterno,email) VALUES (?,"Ficticio",?)');
        $s->execute(['Persona '.$i,"notas$i@example.test"]); $persona=(int)$fixture->lastInsertId();
        $s=$fixture->prepare('INSERT INTO practicas (id_practicante,id_estado_practica,fecha_inicio_practica,fecha_termino_practica) VALUES (?,?,?,?)');
        $s->execute([$persona,$estado,date('Y-m-d',strtotime('-60 days')),date('Y-m-d',strtotime('+60 days'))]); $practicas[$i]=(int)$fixture->lastInsertId();
        $s=$fixture->prepare('INSERT INTO usuarios_sistema (id_practicante,email,password_hash,estado_cuenta) VALUES (?,?,"ficticio","ACTIVO")');
        $s->execute([$persona,"notas$i@example.test"]); $usuarios[$i]=(int)$fixture->lastInsertId();
        $fixture->exec('INSERT INTO usuarios_roles (id_usuario,id_rol) SELECT '.$usuarios[$i].',id_rol FROM roles_sistema WHERE nombre IN ("ADMIN","SUPERVISOR","PRACTICANTE")');
        $s=$fixture->prepare('INSERT INTO historial_areas_practicante (id_practica,id_area,fecha_inicio) VALUES (?,?,?)');
        $s->execute([$practicas[$i],$i===4?$areaB:$areaA,date('Y-m-d',strtotime('-60 days'))]);
    }
    $admin=$usuarios[6]; notaSesion($admin,'ADMIN');
    $s=$fixture->prepare('INSERT INTO liderazgos_area (id_area,id_practica,fecha_inicio,asignado_por,motivo_designacion) VALUES (?,?,?,?,"Prueba")');
    $s->execute([$areaA,$practicas[1],date('Y-m-d H:i:s',strtotime('-60 days')),$admin]); $lider=(int)$fixture->lastInsertId();
    $s=$fixture->prepare('INSERT INTO evaluaciones_practicante (id_practicante,id_practica,id_evaluador,nota_desempeno,fecha_evaluacion,estado_evaluacion) SELECT id_practicante,id_practica,?,6.2,?,"HISTORICA" FROM practicas WHERE id_practica=?');
    $s->execute([$admin,date('Y-m-d'),$practicas[2]]);
    $historia=$fixture->query('SELECT * FROM evaluaciones_practicante')->fetchAll();
    notaSql($patch); notaSql($patch); notaSql($patchRubrica); notaSql($patchRubrica);
    notaCheck(notas_disponible(),'Migración disponible.');
    notaCheck($historia===$fixture->query('SELECT * FROM evaluaciones_practicante')->fetchAll(),'Acompañamiento se conserva.');
    notaCheck((int)$fixture->query('SELECT COUNT(*) FROM rubricas_notas')->fetchColumn()===1,'Rúbrica idempotente.');
    $viernes=(new DateTimeImmutable('last friday'))->format('Y-m-d');
    $anterior=(new DateTimeImmutable($viernes))->modify('-7 days')->format('Y-m-d');
    $vacias=array_fill(1,10,null); $valores=array_fill(1,10,'6.5');
    foreach (['2026-02-30','2026-10-19','2999-01-01','2026-1-2','2026-10-16xxx'] as $fecha) {
        notaRechaza(fn()=>notas_viernes($fecha),'viernes');
    }
    notaCheck(notas_viernes($anterior)===$anterior,'Permite viernes atrasado.');
    $anoAnterior='2025-12-26';
    notaCheck(notas_viernes($anoAnterior)===$anoAnterior,'No confunde semanas al cambiar de año.');
    foreach (['0','7.5','6.2','1e0',[],true,'1,5'] as $valor) {
        $malas=$valores; $malas[1]=$valor;
        try { notas_validar_valores($malas,null,''); throw new RuntimeException('Escala inválida aceptada.'); }
        catch (DomainException) { notaCheck(true,'Escala rechazada.'); }
    }
    notaRechaza(fn()=>notas_validar_valores([],null,''),'evaluaciones');
    notaCheck(notas_validar_valores($vacias,'6.25','')[1]==='6.25','Nota final manual independiente.');
    foreach (range(2,14) as $medio) {
        notaCheck(notas_validar_valores(array_fill(1,10,(string)($medio/2)),null,'')[0][1]!==null,'Acepta toda la escala de medios puntos.');
    }
    notaSesion($usuarios[1],'SUPERVISOR');
    notaRechaza(fn()=>notas_guardar_borrador($practicas[2],$viernes,$valores,null,''),'autorización');
    notaSesion($usuarios[1],'PRACTICANTE');
    notaRechaza(fn()=>notas_guardar_borrador($practicas[1],$viernes,$valores,null,''),'propias');
    notaRechaza(fn()=>notas_guardar_borrador($practicas[4],$viernes,$valores,null,''),'responsable');
    $pendientes=notas_pendientes($practicas[2]);
    notaCheck(in_array($anterior,$pendientes,true),'Detecta viernes atrasado sin nota.');
    $primera=notas_guardar_borrador($practicas[2],$viernes,$valores,'6.25','Texto ficticio');
    notaCheck($primera['revision']===1,'Creación por líder.');
    notaCheck(count(notas_consultar($primera['id_nota'])['detalle'])===10,'Diez posiciones.');
    notaCheck(!in_array($viernes,notas_pendientes($practicas[2]),true),'Un borrador no se considera evaluación faltante.');
    notaRechaza(fn()=>notas_guardar_borrador($practicas[2],$viernes,$valores,null,''),'ya existe');
    notaRechaza(fn()=>notas_guardar_borrador($practicas[2],$viernes,$valores,null,'',1),'motivo');
    $segunda=notas_guardar_borrador($practicas[2],$viernes,$vacias,null,'Nuevo borrador',1,'Corrección de prueba');
    notaCheck($segunda['revision']===2,'Edición conserva revisión.');
    notaRechaza(fn()=>notas_guardar_borrador($practicas[2],$viernes,$valores,null,'',1,'Obsoleta'),'cambió');
    notaCheck((int)$fixture->query('SELECT COUNT(*) FROM notas_semanales_historial')->fetchColumn()===2,'Dos instantáneas conservadas.');
    notaCheck(notas_consultar($primera['id_nota'])['nota_final']===null,'Vacío no es cero ni promedio.');
    notaSesion($usuarios[2],'PRACTICANTE');
    notaRechaza(fn()=>notas_consultar($primera['id_nota']),'aún no');
    notaSesion($usuarios[4],'PRACTICANTE');
    notaRechaza(fn()=>notas_consultar($primera['id_nota']),'responsable');
    notaSesion($admin,'ADMIN');
    notaCheck(notas_consultar($primera['id_nota'])['revision']===2,'ADMIN consulta cualquier nota.');
    notas_guardar_borrador($practicas[2],$viernes,$valores,'6.50','Revisado por ADMIN',2,'Ajuste administrativo');
    notaCheck((int)$fixture->query('SELECT MAX(revision) FROM notas_semanales_historial')->fetchColumn()===3,'ADMIN edita nota ajena y conserva historial.');
    // ADMIN mantiene acceso de corrección aun cuando ya no exista pertenencia activa o la práctica esté cerrada.
    $fixture->exec('UPDATE historial_areas_practicante SET fecha_fin=CURRENT_DATE WHERE id_practica='.$practicas[2]);
    $fixture->exec('UPDATE practicas SET activo=0 WHERE id_practica='.$practicas[2]);
    notas_guardar_borrador($practicas[2],$viernes,$valores,'6.5','Corrección histórica',3,'Ajuste posterior a baja');
    notaCheck((int)notas_consultar($primera['id_nota'])['id_area']===$areaA,'La edición administrativa conserva el área original.');
    $fixture->exec('UPDATE historial_areas_practicante SET fecha_fin=NULL WHERE id_practica='.$practicas[2]);
    $fixture->exec('UPDATE practicas SET activo=1 WHERE id_practica='.$practicas[2]);
    notaRechaza(fn()=>notas_guardar_borrador($practicas[6],$viernes,$valores,null,''),'propias');
    notaRechaza(fn()=>notas_guardar_borrador($practicas[3],$viernes,$valores,null,''),'nuevas de líderes');
    $notaLider=notas_guardar_borrador($practicas[1],$viernes,$valores,'6.5','');
    notaCheck(notas_consultar($notaLider['id_nota'])['tipo_evaluado']==='LIDER','ADMIN registra líder.');
    notaSesion($usuarios[1],'PRACTICANTE');
    // Reemplacé al líder y verifiqué que el nuevo no herede borradores ni ediciones del anterior.
    $fixture->exec('UPDATE liderazgos_area SET fecha_fin=NOW() WHERE id_liderazgo='.$lider);
    $s=$fixture->prepare('INSERT INTO liderazgos_area (id_area,id_practica,fecha_inicio,asignado_por,motivo_designacion) VALUES (?,?,NOW(),?,"Reemplazo")');
    $s->execute([$areaA,$practicas[3],$admin]);
    notaRechaza(fn()=>notas_guardar_borrador($practicas[2],$viernes,$valores,null,'',3,'Intento anterior'),'responsable');
    notaSesion($usuarios[3],'PRACTICANTE');
    notaRechaza(fn()=>notas_consultar($primera['id_nota']),'responsable');
    notaRechaza(fn()=>notas_guardar_borrador($practicas[2],$viernes,$valores,null,'',3,'Intento nuevo'),'responsable');
    $atrasada=notas_guardar_borrador($practicas[2],$anterior,$valores,'6.5','Semana atrasada');
    notaCheck($atrasada['id_nota']!==$primera['id_nota'],'Líder nuevo completa viernes faltante sin sobrescribir.');
    $fixture->exec('UPDATE historial_areas_practicante SET id_area='.$areaB.' WHERE id_practica='.$practicas[2]);
    notaRechaza(fn()=>notas_guardar_borrador($practicas[2],$anterior,$valores,'6.5','',1,'Traslado'),'responsable');
    $fixture->exec('UPDATE historial_areas_practicante SET id_area='.$areaA.' WHERE id_practica='.$practicas[2]);
    notaRechaza(fn()=>notas_guardar_borrador($practicas[1],$viernes,$valores,null,''),'autorización');
    // Simulé publicación para comprobar solo la política de lectura; publicar aún no tiene ruta ni servicio.
    $fixture->exec('UPDATE notas_semanales SET estado="APROBADA",aprobado_por='.$usuarios[3].',aprobado_en=NOW() WHERE id_nota='.$atrasada['id_nota']);
    notaSesion($usuarios[2],'PRACTICANTE');
    notaCheck(notas_consultar($atrasada['id_nota'])['estado']==='APROBADA','El evaluado consulta únicamente aprobadas.');
    notaSesion($admin,'ADMIN');
    notaRechaza(fn()=>notas_guardar_borrador($practicas[2],$anterior,$valores,'7','',1,'Corrección'),'publicación');
    // Probé restricciones independientes del servicio, con SQL directo deliberadamente inválido.
    foreach ([
        'UPDATE notas_semanales_detalle SET nota=6.2 WHERE id_nota='.$atrasada['id_nota'],
        // 011 amplía la cantidad de criterios a 50; la nota sigue limitada por su versión en el servicio.
        'UPDATE notas_semanales_detalle SET posicion=51 WHERE id_nota='.$atrasada['id_nota'].' AND posicion=1',
        'UPDATE notas_semanales SET viernes="2026-10-19" WHERE id_nota='.$atrasada['id_nota'],
        'UPDATE notas_semanales SET estado="INVALIDO" WHERE id_nota='.$atrasada['id_nota'],
        'UPDATE notas_semanales SET nota_final=8 WHERE id_nota='.$atrasada['id_nota'],
        'UPDATE notas_semanales SET viernes="'.$viernes.'" WHERE id_nota='.$atrasada['id_nota'],
        'DELETE FROM practicas WHERE id_practica='.$practicas[2],
    ] as $sql) {
        try { $fixture->exec($sql); throw new RuntimeException('La base aceptó SQL inválido.'); }
        catch (PDOException) { notaCheck(true,'Restricción SQL.'); }
    }
    // Una auditoría fallida debe revertir nota, detalle y revisiones juntos.
    $fixture->exec("CREATE TRIGGER notas_sin_auditoria BEFORE INSERT ON auditoria_cambios FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Fallo simulado'");
    notaSesion($usuarios[3],'PRACTICANTE');
    $antes=(int)$fixture->query('SELECT COUNT(*) FROM notas_semanales')->fetchColumn();
    try { notas_guardar_borrador($practicas[5],$viernes,$valores,null,''); throw new LogicException('Fallo no revertido.'); }
    catch (RuntimeException $e) { notaCheck(str_contains($e->getMessage(),'auditoría'),'Error de auditoría esperado.'); }
    notaCheck((int)$fixture->query('SELECT COUNT(*) FROM notas_semanales')->fetchColumn()===$antes,'Rollback atómico.');
    $fixture->exec('DROP TRIGGER notas_sin_auditoria');
    // La revocación de cuenta o rol se comprueba aun si la sesión sigue abierta.
    $fixture->exec('UPDATE usuarios_sistema SET activo=0 WHERE id_usuario='.$usuarios[3]);
    notaRechaza(fn()=>notas_guardar_borrador($practicas[5],$viernes,$valores,null,''),'habilitados');
    $fixture->exec('UPDATE usuarios_sistema SET activo=1 WHERE id_usuario='.$usuarios[3]);
    $fixture->exec('UPDATE usuarios_roles SET rol_activo=0 WHERE id_usuario='.$usuarios[3]);
    notaRechaza(fn()=>notas_consultar($atrasada['id_nota']),'habilitados');
    $fixture->exec('UPDATE usuarios_roles SET rol_activo=1 WHERE id_usuario='.$usuarios[3]);
    // Mantuve un bloqueo mientras otra conexión intentaba crear exactamente el mismo viernes.
    $fixture->beginTransaction();
    $fixture->query('SELECT id_practica FROM practicas WHERE id_practica='.$practicas[5].' FOR UPDATE')->fetchAll();
    // La primera inserción queda sin confirmar mientras llega la segunda solicitud.
    $s=$fixture->prepare('INSERT INTO notas_semanales (id_practica,viernes,id_area,id_rubrica,id_evaluador,id_liderazgo,tipo_evaluado,creado_en,actualizado_en)
        SELECT ?,?,?,id_rubrica,?,(SELECT MAX(id_liderazgo) FROM liderazgos_area WHERE id_area=?),"INTEGRANTE",NOW(),NOW()
        FROM rubricas_notas WHERE codigo="provisional-v1"');
    $s->execute([$practicas[5],$viernes,$areaA,$usuarios[3],$areaA]);
    $pipes=[];
    $worker=proc_open([PHP_BINARY,$root.'/tests/notas-concurrencia-worker.php',$database,(string)$usuarios[3],(string)$practicas[5],$viernes],
        [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$root);
    if (!is_resource($worker)) { throw new RuntimeException('No se pudo iniciar el segundo proceso.'); }
    fclose($pipes[0]);
    try {
        $ready=$sessionDir.'/'.$database.'.ready'; $limite=microtime(true)+5;
        while (!is_file($ready) && microtime(true)<$limite) { usleep(20000); }
        notaCheck(is_file($ready),'El segundo proceso llegó a la operación.'); usleep(250000);
        notaCheck(proc_get_status($worker)['running'],'La segunda conexión esperó el bloqueo.');
        $fixture->commit();
    } finally { if ($fixture->inTransaction()) { $fixture->rollBack(); } }
    $salida=stream_get_contents($pipes[1]); $errores=stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]); $exit=proc_close($worker);
    notaCheck($exit===0 && $errores==='' && $salida==='RECHAZO_OBSOLETO','Rechazo tras esperar la primera transacción: '.$salida.$errores);
    notaCheck((int)$fixture->query('SELECT COUNT(*) FROM notas_semanales WHERE id_practica='.$practicas[5])->fetchColumn()===1,'Una sola semana vigente tras la carrera.');
    // Probé criterios configurables y conservación de versiones después de terminar la carrera concurrente.
    notaSesion($usuarios[3],'PRACTICANTE');
    notaCheck(notas_es_lider_actual(),'El nuevo líder obtiene acceso real al módulo.');
    $visibles=array_map('intval',array_column(notas_destinatarios(),'id_practica'));
    notaCheck(!in_array($practicas[4],$visibles,true) && !in_array($practicas[3],$visibles,true),'Selector excluye área ajena y autoevaluación.');
    notaRechaza(fn()=>notas_destinatario_visible($practicas[4]),'ámbito');
    $reservada=array_values(array_filter(notas_periodos($practicas[2]),static fn($n)=>$n['viernes']===$viernes))[0];
    notaCheck($reservada['id_nota']===null && !array_key_exists('nota_final',$reservada),'Otro responsable no ve el contenido del borrador anterior.');
    $configInicial=notas_rubrica_actual();
    notaRechaza(fn()=>notas_guardar_rubrica(['Criterio'],'Versión',1,'Intento'),'Solo ADMIN');
    foreach ([[],[''],[[]],array_fill(0,51,'Criterio'),[str_repeat('a',501)]] as $invalidos) {
        try { notas_validar_criterios($invalidos); throw new LogicException('Criterios inválidos aceptados.'); }
        catch (DomainException) { notaCheck(true,'Rechaza criterios inválidos.'); }
    }
    notaSesion($admin,'ADMIN');
    $doce=array_map(static fn($i)=>'Criterio escrito '.$i,range(1,12));
    $rubricaDoce=notas_guardar_rubrica($doce,'Rúbrica ampliada',$configInicial['revision_config'],'Se agregaron criterios');
    notaCheck(count(notas_rubrica_actual()['criterios'])===12,'ADMIN agrega más de diez criterios.');
    $nuevaLider=notas_guardar_borrador($practicas[3],$viernes,array_fill(1,12,'6.5'),'6.25','',0,'',$rubricaDoce);
    $notaNueva=notas_consultar($nuevaLider['id_nota']);
    notaCheck(count($notaNueva['detalle'])===12 && $notaNueva['rubrica']['criterios']===$doce,'Doce filas mantienen criterios y notas.');
    notaRechaza(fn()=>notas_guardar_rubrica(['Otro'],'Obsoleta',1,'Formulario antiguo'),'cambiaron');
    $rubricaDos=notas_guardar_rubrica(['Comunicación','Responsabilidad'],'Rúbrica compacta',2,'Se quitaron filas');
    notaCheck(count(notas_rubrica_actual()['criterios'])===2,'ADMIN quita filas creando otra versión.');
    $versionAnterior=notas_consultar($nuevaLider['id_nota']);
    notaCheck(count($versionAnterior['detalle'])===12 && $versionAnterior['rubrica']['criterios']===$doce,'Cambiar la rúbrica activa no altera borradores.');
    notas_guardar_borrador($practicas[3],$viernes,array_fill(1,12,'7'),'7','',1,'Edición con versión original',$rubricaDoce);
    notaSesion($usuarios[3],'PRACTICANTE');
    $otroViernes=(new DateTimeImmutable($viernes))->modify('-14 days')->format('Y-m-d');
    notaRechaza(fn()=>notas_guardar_borrador($practicas[2],$otroViernes,array_fill(1,12,'6.5'),'6.5','',0,'',$rubricaDoce),'criterios cambió');
    notaRechaza(fn()=>notas_guardar_borrador($practicas[2],$otroViernes,$valores,'6.5','',0,'',$rubricaDos),'evaluaciones');
    $compacta=notas_guardar_borrador($practicas[2],$otroViernes,[1=>'6.5',2=>'7'],'6.75','',0,'',$rubricaDos);
    notaCheck(count(notas_consultar($compacta['id_nota'])['detalle'])===2,'Nueva semana utiliza la rúbrica vigente de dos filas.');
    $snapshot=$fixture->query('SELECT contenido_json FROM notas_semanales_historial WHERE id_nota='.$compacta['id_nota'])->fetchColumn();
    notaCheck(json_decode($snapshot,true)['criterios']===['Comunicación','Responsabilidad'],'La revisión conserva los criterios escritos.');
    notaSesion($admin,'ADMIN');
    $original=notas_consultar($primera['id_nota']);
    notas_guardar_borrador($practicas[2],$viernes,$valores,'6.5','',(int)$original['revision'],'Mantener versión original',(int)$original['id_rubrica']);
    notaCheck(count(notas_consultar($primera['id_nota'])['detalle'])===10,'Borradores históricos conservan sus diez filas.');
    $fixture->exec("CREATE TRIGGER notas_sin_auditoria BEFORE INSERT ON auditoria_cambios FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Fallo simulado'");
    $antesRubricas=(int)$fixture->query('SELECT COUNT(*) FROM rubricas_notas')->fetchColumn();
    try { notas_guardar_rubrica(['Criterio nuevo'],'No debe persistir',3,'Fallo simulado'); throw new LogicException('Auditoría no revertida.'); }
    catch (RuntimeException $e) { notaCheck(str_contains($e->getMessage(),'auditoría'),'Falla esperada al auditar rúbrica.'); }
    notaCheck((int)$fixture->query('SELECT COUNT(*) FROM rubricas_notas')->fetchColumn()===$antesRubricas && (int)notas_rubrica_actual()['id_rubrica']===$rubricaDos,'Rollback conserva la rúbrica activa y versiones.');
    $fixture->exec('DROP TRIGGER notas_sin_auditoria');
    // Agregué casos reales de aprobación, consulta propia y correcciones antes de repetir las migraciones.
    require __DIR__.'/notas-publicacion-casos.php';
    // Añadí regresión de la fuente oficial y del bloqueo del puntaje antiguo sin tocar asistencia local.
    require __DIR__.'/notas-integracion-casos.php';
    // Comprobé el diagnóstico de despliegue tanto sano como ante publicaciones incompletas.
    require __DIR__.'/notas-preflight-casos.php';
    // Validé dos confirmaciones simultáneas sobre un mismo borrador, además de la creación concurrente ya cubierta.
    require __DIR__.'/notas-publicacion-concurrencia.php';
    // Repetí con datos poblados: no se pierden notas, versiones ni el puntaje antiguo.
    // Extendí la comprobación a liderazgos y puntajes históricos al repetir todas las migraciones de Notas.
    $patchLiderazgo=file_get_contents($root.'/database/2026-10-19_patch_liderazgos_area_009.sql');
    notaCheck(str_contains($schema,$patchLiderazgo),'Schema conserva también la estructura exacta de liderazgo 009.');
    $tablas=['notas_semanales','notas_semanales_detalle','notas_semanales_historial','rubricas_notas','evaluaciones_practicante','configuracion_notas','liderazgos_area','acompanamiento'];
    $huellas=[]; foreach ($tablas as $tabla) { $huellas[$tabla]=hash('sha256',json_encode($fixture->query('SELECT * FROM '.$tabla)->fetchAll())); }
    notaSql($patchLiderazgo); notaSql($patchLiderazgo); notaSql($patch); notaSql($patch); notaSql($patchRubrica); notaSql($patchRubrica);
    foreach ($tablas as $tabla) { notaCheck($huellas[$tabla]===hash('sha256',json_encode($fixture->query('SELECT * FROM '.$tabla)->fetchAll())),'Repetición conserva '.$tabla); }
    echo "Notas semanales MariaDB: $checks comprobaciones correctas.\n";
} finally {
    // Eliminé exclusivamente la base aleatoria creada por este proceso, conservando los entornos del usuario.
    if ($server->inTransaction()) { $server->rollBack(); }
    if (preg_match('/^rrhh_e2e_[a-f0-9]{12}$/D',$database)) { $server->exec("DROP DATABASE `$database`"); }
    session_destroy();
}
