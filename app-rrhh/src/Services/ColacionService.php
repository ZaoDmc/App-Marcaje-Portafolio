<?php
declare(strict_types=1);
require_once __DIR__.'/../bootstrap.php';
require_once __DIR__.'/AuthService.php';

// Normalicé los valores efectivos para detectar cambios de horario entre solicitud y revisión.
function colacion_firma(array $h): string
{
    return hash('sha256',json_encode([(int)$h['id_horario'],substr($h['entrada_esperada'],0,5),substr($h['salida_esperada'],0,5),
        substr($h['colacion_inicio']??'',0,5),substr($h['colacion_fin']??'',0,5),(float)$h['horas_planificadas']],JSON_THROW_ON_ERROR));
}
function colacion_fin(string $inicio): string { return $inicio==='13:00'?'14:00':'15:00'; }

// Apliqué una aprobación solo a su fecha y al mismo horario; una edición manual posterior prevalece.
function colacion_horario_aplicar(int $idPractica,string $fecha,?array $h): ?array
{
    if($h===null){return null;}
    $s=db()->prepare('SELECT * FROM solicitudes_colacion WHERE id_practica=? AND fecha=? AND estado="APROBADO"');
    $s->execute([$idPractica,$fecha]);$r=$s->fetch();
    if(!is_array($r) || !hash_equals($r['firma_horario'],colacion_firma($h))){return $h;}
    $h['colacion_inicio']=substr($r['inicio_solicitado'],0,5);
    $h['colacion_fin']=colacion_fin($h['colacion_inicio']);
    $h['id_cambio_colacion']=(int)$r['id_solicitud'];
    return $h;
}

// Verifiqué una hora de descanso real y ausencia de reservas incompatibles antes de aprobar.
function colacion_validar_horario(int $idPractica,string $fecha,string $inicio,?array $h): void
{
    if($h===null || !in_array($inicio,['13:00','14:00'],true)
        || !in_array(substr($h['colacion_inicio']??'',0,5),['13:00','14:00'],true)
        || substr($h['colacion_fin']??'',0,5)!==colacion_fin(substr($h['colacion_inicio']??'',0,5))
        || $inicio<substr($h['entrada_esperada'],0,5) || colacion_fin($inicio)>substr($h['salida_esperada'],0,5)) {
        throw new RuntimeException('Necesitas jornada asignada con una hora de colación y la nueva franja completa dentro del turno.');
    }
    if($inicio===substr($h['colacion_inicio'],0,5)){throw new RuntimeException('La franja elegida ya corresponde a tu colación.');}
    $s=db()->prepare('SELECT * FROM permisos_practicante WHERE id_practica=? AND fecha_ausencia=? AND estado IN ("PENDIENTE","APROBADO")');
    $s->execute([$idPractica,$fecha]);
    foreach($s->fetchAll() as $p){
        foreach([$inicio,substr($h['colacion_inicio'],0,5)] as $a){
            if($p['tipo_ausencia']!=='PARCIAL' || min(colacion_fin($a),substr($p['hora_fin_ausencia'],0,5))>max($a,substr($p['hora_inicio_ausencia'],0,5))){
                throw new RuntimeException('La colación anterior o nueva cruza una ausencia solicitada; resuelve el permiso antes de cambiarla.');
            }
        }
    }
    $s=db()->prepare('SELECT rp.* FROM recuperaciones_permiso rp JOIN permisos_practicante pp ON pp.id_permiso=rp.id_permiso WHERE pp.id_practica=? AND rp.fecha=? AND pp.estado IN ("PENDIENTE","APROBADO") AND rp.estado IN ("PROPUESTA","PROGRAMADA","VALIDADA")');
    $s->execute([$idPractica,$fecha]);
    foreach($s->fetchAll() as $p){
        foreach([$inicio,substr($h['colacion_inicio'],0,5)] as $a){
            if(min(colacion_fin($a),substr($p['hora_fin'],0,5))>max($a,substr($p['hora_inicio'],0,5))){throw new RuntimeException('Hay recuperación reservada en la colación anterior o nueva. Resuélvela antes.');}
        }
    }
    $s=db()->prepare('SELECT ct.* FROM compensaciones_tiempo ct JOIN tiempos_excepcionales te ON te.id_tiempo=ct.id_tiempo JOIN jornadas_asistencia j ON j.id_jornada=te.id_jornada WHERE j.id_practica=? AND ct.fecha=? AND ct.estado IN ("PENDIENTE","APROBADA","APLICADA")');
    $s->execute([$idPractica,$fecha]);
    foreach($s->fetchAll() as $c){
        if(min(colacion_fin($inicio),substr($c['hora_fin'],0,5))>max($inicio,substr($c['hora_inicio'],0,5))){throw new RuntimeException('La nueva colación cruza un canje de compensación. Resuélvelo antes de cambiarla.');}
    }
}

function colacion_solicitar(string $inicio,string $motivo): array
{
    if(!auth_check() || auth_rol_activo()!=='PRACTICANTE' || !auth_practicante_actual_id() || trim($motivo)==='' || mb_strlen($motivo)>500){return ['ok'=>false,'errores'=>['La solicitud requiere practicante y motivo de hasta 500 caracteres.']];}
    require_once __DIR__.'/PermisosService.php';
    try {
        db()->beginTransaction();
        $p=permisos_gestion_practica_activa((int)auth_practicante_actual_id());
        if($p===null){throw new RuntimeException('No tienes práctica activa.');}
        $id=(int)$p['id_practica'];$fecha=date('Y-m-d');
        tiempo_bloquear_practica($id);
        if($fecha<$p['fecha_inicio_practica'] || (!empty($p['fecha_termino_practica']) && $fecha>$p['fecha_termino_practica'])){throw new RuntimeException('La fecha actual está fuera de la práctica.');}
        $h=asistencia_horario_vigente_dia($id,$fecha);
        colacion_validar_horario($id,$fecha,$inicio,$h);
        // El bloqueo de práctica serializa solicitudes; un rechazo permite volver a solicitar conservando historial.
        $s=db()->prepare('SELECT COUNT(*) FROM solicitudes_colacion WHERE id_practica=? AND fecha=? AND estado IN ("PENDIENTE","APROBADO")');$s->execute([$id,$fecha]);
        if((int)$s->fetchColumn()>0){throw new RuntimeException('Ya existe una solicitud de colación para hoy. Consulta su estado.');}
        $s=db()->prepare('INSERT INTO solicitudes_colacion (id_practica,fecha,inicio_anterior,inicio_solicitado,firma_horario,motivo,solicitado_por,solicitado_utc) VALUES (?,?,?,?,?,?,?,?)');
        $s->execute([$id,$fecha,substr($h['colacion_inicio'],0,5),$inicio,colacion_firma($h),trim($motivo),auth_usuario_actual_id(),gmdate('Y-m-d H:i:s')]);
        $solicitud=(int)db()->lastInsertId();
        tiempo_auditar('SOLICITAR_CAMBIO_COLACION','solicitudes_colacion',$solicitud,null,['fecha'=>$fecha,'inicio'=>$inicio,'motivo'=>trim($motivo)]);
        db()->commit();return ['ok'=>true,'mensaje'=>'Cambio de colación solicitado para hoy. Requiere aprobación del supervisor.'];
    } catch(Throwable $e){if(db()->inTransaction()){db()->rollBack();}error_log('Cambio de colación: '.$e->getMessage());return ['ok'=>false,'errores'=>[$e instanceof RuntimeException && !$e instanceof PDOException?$e->getMessage():'No se pudo guardar la solicitud.']];}
}

function colacion_revisar(int $id,bool $aprobar,string $motivo): array
{
    if(!auth_check() || !in_array(auth_rol_activo(),['ADMIN','SUPERVISOR'],true) || trim($motivo)==='' || mb_strlen($motivo)>500){return ['ok'=>false,'errores'=>['Requiere supervisor autorizado y motivo de hasta 500 caracteres.']];}
    require_once __DIR__.'/PermisosService.php';
    try {
        db()->beginTransaction();
        $s=db()->prepare('SELECT * FROM solicitudes_colacion WHERE id_solicitud=?');$s->execute([$id]);$r=$s->fetch();
        if(!is_array($r) || !auth_puede_supervisar_practica((int)$r['id_practica'])){throw new RuntimeException('No puedes revisar esa solicitud.');}
        tiempo_bloquear_practica((int)$r['id_practica']);
        $s=db()->prepare('SELECT * FROM solicitudes_colacion WHERE id_solicitud=? FOR UPDATE');$s->execute([$id]);$r=$s->fetch();
        if($r['estado']!=='PENDIENTE'){throw new RuntimeException('La solicitud ya fue revisada.');}
        if($aprobar){
            $h=asistencia_horario_vigente_dia((int)$r['id_practica'],$r['fecha']);
            colacion_validar_horario((int)$r['id_practica'],$r['fecha'],substr($r['inicio_solicitado'],0,5),$h);
            if(!hash_equals($r['firma_horario'],colacion_firma($h))){throw new RuntimeException('El horario cambió desde la solicitud. Revisa la configuración antes de resolverla.');}
        }
        $estado=$aprobar?'APROBADO':'RECHAZADO';
        $s=db()->prepare('UPDATE solicitudes_colacion SET estado=?,revisado_por=?,revisado_utc=?,motivo_revision=? WHERE id_solicitud=?');
        $s->execute([$estado,auth_usuario_actual_id(),gmdate('Y-m-d H:i:s'),trim($motivo),$id]);
        tiempo_auditar('REVISAR_CAMBIO_COLACION','solicitudes_colacion',$id,$r,['estado'=>$estado,'motivo'=>trim($motivo)]);
        // Recalculé después de aprobar, sin editar marcas ni limpiar incidencias no resueltas.
        if($aprobar){
            require_once __DIR__.'/AsistenciaService.php';
            $s=db()->prepare('SELECT id_jornada FROM jornadas_asistencia WHERE id_practica=? AND fecha=? AND estado_jornada<>"ANULADA"');$s->execute([(int)$r['id_practica'],$r['fecha']]);$j=$s->fetchColumn();
            if($j!==false){asistencia_recalcular_jornada((int)$j,null,true);}
        }
        db()->commit();return ['ok'=>true,'mensaje'=>'Solicitud de colación revisada. El ajuste afecta solo a la fecha solicitada.'];
    } catch(Throwable $e){if(db()->inTransaction()){db()->rollBack();}error_log('Revisión de colación: '.$e->getMessage());return ['ok'=>false,'errores'=>[$e instanceof RuntimeException && !$e instanceof PDOException?$e->getMessage():'No se pudo revisar la solicitud.']];}
}

if(!function_exists('colacion_listar')) {
function colacion_listar(?int $idPractica=null): array
{
    if(!auth_check() || ($idPractica===null?!in_array(auth_rol_activo(),['ADMIN','SUPERVISOR'],true):!tiempo_es_propietario($idPractica))){return [];}
    $sql='SELECT c.*,p.nombre_1,p.apellido_paterno,p.email FROM solicitudes_colacion c JOIN practicas pr ON pr.id_practica=c.id_practica JOIN practicantes p ON p.id_practicante=pr.id_practicante';
    if($idPractica!==null){$sql.=' WHERE c.id_practica=?';}$sql.=' ORDER BY c.id_solicitud DESC';
    $s=db()->prepare($sql);$s->execute($idPractica===null?[]:[$idPractica]);
    $lista=array_values(array_filter($s->fetchAll(),static fn(array $r):bool=>$idPractica!==null || auth_puede_supervisar_practica((int)$r['id_practica'])));
    foreach($lista as &$r){
        if($r['estado']==='APROBADO'){
            $h=asistencia_horario_vigente_dia((int)$r['id_practica'],$r['fecha']);
            $r['aplica_actualmente']=(int)($h['id_cambio_colacion']??0)===(int)$r['id_solicitud'];
        }
    }unset($r);
    return $lista;
}
}
