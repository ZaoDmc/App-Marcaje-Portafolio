<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/AuthService.php';

// Conservé UTC y el plazo de siete días para expedientes históricos de política 1.
function tiempo_ahora_utc(): string { return gmdate('Y-m-d H:i:s'); }
function tiempo_vencimiento(string $solicitudUtc): string
{
    return (new DateTimeImmutable($solicitudUtc, new DateTimeZone('UTC')))->modify('+7 days')->format('Y-m-d H:i:s');
}
function tiempo_fecha_utc(string $fechaHoraLocal): string
{
    return (new DateTimeImmutable($fechaHoraLocal, new DateTimeZone(APP_TIMEZONE)))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
}
// Calculé cinco jornadas posteriores a la aprobación con horario individual y sin feriados.
// Guardé el resultado al aprobar: una revisión posterior no renueva el crédito.
function tiempo_vencimiento_habil(int $idPractica, string $aprobacionUtc): string
{
    $dia=(new DateTimeImmutable($aprobacionUtc,new DateTimeZone('UTC')))->setTimezone(new DateTimeZone(APP_TIMEZONE))->setTime(0,0);
    $s=db()->prepare('SELECT fecha_termino_practica FROM practicas WHERE id_practica=?');
    $s->execute([$idPractica]);$limite=$s->fetchColumn();$contados=0;
    for($i=0;$i<366;$i++) {
        $dia=$dia->modify('+1 day');$fecha=$dia->format('Y-m-d');
        if($limite && $fecha>$limite) { break; }
        if(recuperaciones_es_feriado($fecha)) { continue; }
        $h=asistencia_horario_vigente_dia($idPractica,$fecha);
        if($h===null || (float)$h['horas_planificadas']<=0) { continue; }
        if(++$contados===5) { return tiempo_fecha_utc($fecha.' '.$h['salida_esperada']); }
    }
    throw new RuntimeException('No hay cinco jornadas futuras disponibles dentro de la práctica para habilitar el crédito.');
}
function tiempo_motivo_valido(string $motivo): bool { return trim($motivo) !== '' && mb_strlen(trim($motivo)) <= 500; }

if(!function_exists('tiempo_contar_pendientes')) {
    // Incluí solicitudes y canjes en el aviso lateral; excluí la práctica propia del revisor.
    function tiempo_contar_pendientes(): int
    {
        if(!auth_check() || !in_array(auth_rol_activo(),['ADMIN','SUPERVISOR'],true)){return 0;}
        $s=db()->prepare('SELECT COUNT(*) FROM (SELECT te.id_jornada FROM tiempos_excepcionales te WHERE te.estado IN ("PENDIENTE","REVISAR") UNION ALL SELECT te.id_jornada FROM compensaciones_tiempo ct JOIN tiempos_excepcionales te ON te.id_tiempo=ct.id_tiempo WHERE ct.estado IN ("PENDIENTE","REVISAR")) pendientes JOIN jornadas_asistencia j ON j.id_jornada=pendientes.id_jornada JOIN practicas p ON p.id_practica=j.id_practica WHERE p.activo=1 AND p.id_practicante<>?');
        $s->execute([auth_practicante_actual_id()??0]);return (int)$s->fetchColumn();
    }
}

if (!function_exists('tiempo_politica')) {
    // El corte se crea con la migración, nunca al consultar una pantalla.
    function tiempo_politica(): ?array
    {
        $fila = db()->query('SELECT * FROM politica_tiempo_excepcional WHERE id=1')->fetch();
        return is_array($fila) ? $fila : null;
    }
}
function tiempo_jornada_nueva(array $jornada): bool
{
    $politica = tiempo_politica();
    return $politica !== null && (int)$jornada['id_jornada'] > (int)$politica['ultima_jornada_historica'];
}
function tiempo_extra_historica(int $idJornada): ?float
{
    // Guardé el importe histórico separado para no perderlo al corregir marcas o aplicar la regla nueva.
    $stmt=db()->prepare('SELECT horas_aprobadas FROM extras_historicas WHERE id_jornada=?');
    $stmt->execute([$idJornada]); $valor=$stmt->fetchColumn();
    return $valor===false ? null : (float)$valor;
}
function tiempo_auditar(string $evento, string $tabla, int $id, ?array $antes, array $despues): void
{
    if (!auditoria_registrar($evento,['tabla_afectada'=>$tabla,'id_registro_afectado'=>$id,'valores_anteriores'=>$antes,'valores_nuevos'=>$despues])) {
        throw new RuntimeException('No se pudo guardar la auditoría de tiempo excepcional.');
    }
}
function tiempo_bloquear_practica(int $id): void
{
    // Serialicé solicitudes, revisiones y reservas de una práctica para impedir el doble gasto.
    $stmt=db()->prepare('SELECT id_practica FROM practicas WHERE id_practica=? FOR UPDATE'); $stmt->execute([$id]);
}
function tiempo_es_propietario(int $idPractica): bool
{
    $stmt=db()->prepare('SELECT id_practicante FROM practicas WHERE id_practica=? AND activo=1');$stmt->execute([$idPractica]);
    $propietario=$stmt->fetchColumn();
    return $propietario!==false && auth_practicante_actual_id()!==null && auth_rol_activo()==='PRACTICANTE' && (int)$propietario===(int)auth_practicante_actual_id();
}
function tiempo_fecha_reservada(int $idPractica,string $fecha): bool
{
    // No mezclé ausencias y canjes del mismo día, incluso cuando el canje nuevo es autónomo.
    $s=db()->prepare('SELECT COUNT(*) FROM compensaciones_tiempo ct JOIN tiempos_excepcionales te ON te.id_tiempo=ct.id_tiempo JOIN jornadas_asistencia j ON j.id_jornada=te.id_jornada WHERE j.id_practica=? AND ct.fecha=? AND ct.estado IN ("PENDIENTE","APROBADA","APLICADA")');
    $s->execute([$idPractica,$fecha]);return (int)$s->fetchColumn()>0;
}

// Calculé minutos por intersección con marcas completas; no basta con declarar cuánto se trabajó.
function tiempo_minutos_trabajados(array $jornada, string $inicio, string $fin): int
{
    $marcas=asistencia_marcas_vigentes_jornada((int)$jornada['id_jornada']);
    if (!tiempo_marcas_completas($jornada,$marcas)) { return 0; }
    return recuperaciones_minutos_efectivos(['fecha'=>$jornada['fecha'],'hora_inicio'=>$inicio,'hora_fin'=>$fin,'minutos_planificados'=>1440],$marcas);
}
function tiempo_marcas_completas(array $jornada, array $marcas): bool
{
    // Validé evidencia independientemente del estado visual Revisada/Corregida/Observada.
    $previas=[];
    foreach($marcas as $m) {
        if(substr($m['fecha_hora'],0,10)!==$jornada['fecha'] || !asistencia_validar_secuencia($previas,$m['codigo'])['ok']) { return false; }
        $previas[]=$m;
    }
    return asistencia_marca_por_codigo($marcas,'ENTRADA')!==null && asistencia_marca_por_codigo($marcas,'SALIDA')!==null
        && asistencia_ultima_marca_vigente($marcas)==='SALIDA';
}
function tiempo_validar_origen(array $jornada, string $inicio, string $fin): int
{
    // Cargué validadores al ejecutar la acción, también desde Marcaje/Asistencia que no abren Permisos.
    require_once __DIR__.'/PermisosService.php';
    // Separé los rechazos para no presentar una salida futura como un error genérico de fecha.
    if(!tiempo_jornada_nueva($jornada)) { throw new RuntimeException('La jornada es anterior al corte de la política excepcional; selecciona una jornada nueva.'); }
    if($jornada['estado_jornada']==='ANULADA') { throw new RuntimeException('No puedes solicitar tiempo de una jornada anulada.'); }
    if(!permisos_gestion_hora_valida($inicio) || !permisos_gestion_hora_valida($fin) || $fin<=$inicio) {
        throw new RuntimeException('Indica inicio y fin válidos; el fin debe ser posterior al inicio.');
    }
    if($inicio<'10:00' || $fin>'20:00' || (int)date('N',strtotime($jornada['fecha']))===7) {
        throw new RuntimeException('El tramo excepcional debe quedar entre 10:00 y 20:00, de lunes a sábado.');
    }
    $marcasOrigen=asistencia_marcas_vigentes_jornada((int)$jornada['id_jornada']);
    $salidaOrigen=asistencia_marca_por_codigo($marcasOrigen,'SALIDA');
    if(strtotime($jornada['fecha'].' '.$fin)>time() || ($salidaOrigen!==null && strtotime($salidaOrigen['fecha_hora'])>time())) {
        throw new RuntimeException('La salida de la jornada o el fin solicitado está en el futuro. Solo puedes solicitar tiempo excepcional después de que haya ocurrido la salida y terminado el tramo, según la hora actual del servidor.');
    }
    $horario=asistencia_horario_vigente_dia((int)$jornada['id_practica'],$jornada['fecha']);
    $permiso=asistencia_permiso_del_dia((int)$jornada['id_practica'],$jornada['fecha']);
    if($permiso!==null && asistencia_permiso_cruza_marcas($permiso,asistencia_marcas_vigentes_jornada((int)$jornada['id_jornada']))) { throw new RuntimeException('Resuelve el cruce entre permiso y marcas antes de reconocer tiempo excepcional.'); }
    // Reservé colaciones y feriados para el flujo de recuperación ya autorizado; el excepcional amplía un turno real.
    if ($horario===null || (min($fin,substr($horario['salida_esperada'],0,5))>max($inicio,substr($horario['entrada_esperada'],0,5)))) {
        throw new RuntimeException('El tiempo excepcional debe quedar fuera de la jornada ordinaria de ese día.');
    }
    $stmt=db()->prepare('SELECT rp.* FROM recuperaciones_permiso rp JOIN permisos_practicante pp ON pp.id_permiso=rp.id_permiso WHERE pp.id_practica=? AND rp.fecha=? AND rp.estado IN ("PROPUESTA","PROGRAMADA","VALIDADA") AND pp.estado IN ("PENDIENTE","APROBADO")');
    $stmt->execute([(int)$jornada['id_practica'],$jornada['fecha']]);
    foreach($stmt->fetchAll() as $r) {
        if(min($fin,substr($r['hora_fin'],0,5))>max($inicio,substr($r['hora_inicio'],0,5))) { throw new RuntimeException('Ese tramo ya está reservado para recuperar un permiso.'); }
    }
    // También protegí las recuperaciones anteriores de un único día.
    $stmt=db()->prepare('SELECT * FROM permisos_practicante WHERE id_practica=? AND fecha_recuperacion=? AND estado IN ("PENDIENTE","APROBADO")');
    $stmt->execute([(int)$jornada['id_practica'],$jornada['fecha']]);
    foreach($stmt->fetchAll() as $p) {
        if(recuperaciones_plan((int)$p['id_permiso'])===[] && min($fin,substr((string)$p['hora_fin_recuperacion'],0,5))>max($inicio,substr((string)$p['hora_inicio_recuperacion'],0,5))) { throw new RuntimeException('Ese tramo pertenece a una recuperación histórica.'); }
    }
    $minutos=tiempo_minutos_trabajados($jornada,$inicio,$fin);
    if($minutos<=0) { throw new RuntimeException('No hay minutos completos trabajados y marcas coherentes dentro de ese tramo.'); }
    return $minutos;
}

// Agregué solicitud posterior a las marcas: la aprobación del supervisor documenta el permiso informal del jefe.
function tiempo_solicitar(int $idJornada, string $inicio, string $fin, string $motivo): array
{
    if(!auth_check() || !tiempo_motivo_valido($motivo)) { return ['ok'=>false,'errores'=>['Indica un motivo de hasta 500 caracteres.']]; }
    try {
        db()->beginTransaction();
        $jornada=asistencia_jornada_por_id($idJornada);
        if($jornada===null || !tiempo_es_propietario((int)$jornada['id_practica'])) { throw new RuntimeException('No puedes solicitar tiempo de esa jornada.'); }
        tiempo_bloquear_practica((int)$jornada['id_practica']);
        $minutos=tiempo_validar_origen($jornada,$inicio,$fin);
        $stmt=db()->prepare('SELECT * FROM tiempos_excepcionales WHERE id_jornada=?');$stmt->execute([$idJornada]);
        foreach($stmt->fetchAll() as $r) {
            // Un rechazo posterior al vencimiento tampoco permite solicitar otra vez el mismo trabajo y reiniciar siete días.
            if(($r['estado']!=='RECHAZADO' || $r['vence_utc']<=tiempo_ahora_utc()) && min($fin,substr($r['hora_fin'],0,5))>max($inicio,substr($r['hora_inicio'],0,5))) { throw new RuntimeException('Ese tramo ya tiene una solicitud; no se puede duplicar aunque haya vencido.'); }
        }
        $ahora=tiempo_ahora_utc(); $vence=tiempo_vencimiento($ahora);
        $stmt=db()->prepare('INSERT INTO tiempos_excepcionales (id_jornada,hora_inicio,hora_fin,minutos_solicitados,motivo_solicitud,solicitado_utc,vence_utc,politica_canje) VALUES (?,?,?,?,?,?,?,2)');
        $stmt->execute([$idJornada,$inicio,$fin,$minutos,trim($motivo),$ahora,$vence]);$id=(int)db()->lastInsertId();
        tiempo_auditar('SOLICITAR_TIEMPO_EXCEPCIONAL','tiempos_excepcionales',$id,null,['minutos'=>$minutos,'vence_utc'=>$vence,'motivo'=>$motivo]);
        db()->commit();return ['ok'=>true,'mensaje'=>'Tiempo solicitado. Los cinco días hábiles de canje comenzarán desde su aprobación.'];
    } catch(Throwable $e) { if(db()->inTransaction()){db()->rollBack();} return ['ok'=>false,'errores'=>[$e instanceof RuntimeException && !$e instanceof PDOException ? $e->getMessage() : 'No se pudo registrar el tiempo excepcional.']]; }
}
function tiempo_credito(int $id): ?array
{
    $stmt=db()->prepare('SELECT te.*,j.id_practica,j.fecha AS fecha_trabajo FROM tiempos_excepcionales te JOIN jornadas_asistencia j ON j.id_jornada=te.id_jornada WHERE te.id_tiempo=?');
    $stmt->execute([$id]);$r=$stmt->fetch();return is_array($r)?$r:null;
}
function tiempo_evidencia_valida(array $credito): bool
{
    if($credito['estado']!=='APROBADO') { return false; }
    $jornada=asistencia_jornada_por_id((int)$credito['id_jornada']);
    if($jornada===null || $jornada['estado_jornada']==='ANULADA') { return false; }
    $marcas=asistencia_marcas_vigentes_jornada((int)$credito['id_jornada']);
    if(!hash_equals((string)$credito['firma_marcas'],recuperaciones_firma_marcas($marcas))) { return false; }
    try { return tiempo_validar_origen($jornada,substr($credito['hora_inicio'],0,5),substr($credito['hora_fin'],0,5)) >= (int)$credito['minutos_aprobados']; }
    catch(RuntimeException $e) { return false; }
}
function tiempo_saldo(array $credito): int
{
    // El vencimiento se evalúa siempre al canjear, sin depender de cron ni borrar el registro.
    if($credito['vence_utc']<=tiempo_ahora_utc() || !tiempo_evidencia_valida($credito)) { return 0; }
    $stmt=db()->prepare('SELECT COALESCE(SUM(CASE WHEN estado="APLICADA" THEN minutos_aplicados ELSE minutos END),0) FROM compensaciones_tiempo WHERE id_tiempo=? AND estado IN ("PENDIENTE","APROBADA","APLICADA")');$stmt->execute([(int)$credito['id_tiempo']]);
    return max(0,(int)$credito['minutos_aprobados']-(int)$stmt->fetchColumn());
}

// Agregué revisión con motivo obligatorio y evidencia vigente, sin sumar extras al avance de la práctica.
function tiempo_revisar(int $id, bool $aprobar, string $motivo): array
{
    if(!auth_check() || !in_array(auth_rol_activo(),['ADMIN','SUPERVISOR'],true) || !tiempo_motivo_valido($motivo)) { return ['ok'=>false,'errores'=>['Requiere supervisor y motivo de hasta 500 caracteres.']]; }
    try {
        db()->beginTransaction();$c=tiempo_credito($id);
        if($c===null || !auth_puede_supervisar_practica((int)$c['id_practica'])) { throw new RuntimeException('No puedes revisar esa solicitud.'); }
        tiempo_bloquear_practica((int)$c['id_practica']);$c=tiempo_credito($id);
        // Permití revisar evidencia previamente aprobada aunque venciera; el saldo vencido sigue bloqueado.
        $revalidacion=$c['estado']==='REVISAR' && !empty($c['firma_marcas']) && !empty($c['revisado_por']);
        $nuevaPolitica=(int)($c['politica_canje']??1)===2;
        if(!in_array($c['estado'],['PENDIENTE','REVISAR'],true) || ($aprobar && !$nuevaPolitica && $c['vence_utc']<=tiempo_ahora_utc() && !$revalidacion)) { throw new RuntimeException('La solicitud ya fue revisada o venció.'); }
        $j=asistencia_jornada_por_id((int)$c['id_jornada']);
        $minutos=$aprobar?min((int)$c['minutos_solicitados'],tiempo_validar_origen($j,substr($c['hora_inicio'],0,5),substr($c['hora_fin'],0,5))):0;
        $firma=$aprobar?recuperaciones_firma_marcas(asistencia_marcas_vigentes_jornada((int)$c['id_jornada'])):null;
        $estado=$aprobar?'APROBADO':'RECHAZADO';
        // Fijé el plazo una sola vez al primer reconocimiento; corregir evidencia no lo renueva.
        $ahora=tiempo_ahora_utc();
        $vence=$aprobar && $nuevaPolitica && !$revalidacion?tiempo_vencimiento_habil((int)$c['id_practica'],$ahora):$c['vence_utc'];
        $stmt=db()->prepare('UPDATE tiempos_excepcionales SET estado=?,minutos_aprobados=?,revisado_por=?,motivo_revision=?,revisado_utc=?,firma_marcas=?,vence_utc=? WHERE id_tiempo=?');
        $stmt->execute([$estado,$minutos,auth_usuario_actual_id(),trim($motivo),$ahora,$firma,$vence,$id]);
        tiempo_auditar('REVISAR_TIEMPO_EXCEPCIONAL','tiempos_excepcionales',$id,$c,['estado'=>$estado,'minutos'=>$minutos,'motivo'=>$motivo,'vence_utc'=>$vence]);
        // Recalculé la fuente para que su observación refleje la aprobación o rechazo realmente registrado.
        require_once __DIR__.'/AsistenciaService.php';
        asistencia_recalcular_jornada((int)$c['id_jornada'],null,true);
        db()->commit();return ['ok'=>true,'mensaje'=>'Revisión registrada; el crédito excepcional no se suma como hora extra.'];
    } catch(Throwable $e) { if(db()->inTransaction()){db()->rollBack();}return ['ok'=>false,'errores'=>[$e instanceof RuntimeException && !$e instanceof PDOException?$e->getMessage():'No se pudo guardar la revisión.']]; }
}

// Permití únicamente entrar después o salir antes en un día laboral individual, sin cruzar colación ni permisos.
function tiempo_validar_destino(array $credito, string $fecha, string $tipo, string $inicio, string $fin, bool $futuro=true): int
{
    require_once __DIR__.'/PermisosService.php';
    if(!permisos_gestion_fecha_valida($fecha) || !in_array($tipo,['ENTRADA','SALIDA'],true)
        || !permisos_gestion_hora_valida($inicio) || !permisos_gestion_hora_valida($fin) || $fin<=$inicio
        || ($futuro && strtotime($fecha.' '.$inicio)<=time()) || tiempo_fecha_utc($fecha.' '.$fin)>$credito['vence_utc']
        || tiempo_fecha_utc($fecha.' '.$inicio)<$credito['solicitado_utc']) { throw new RuntimeException('Elige un tramo futuro que termine dentro del vencimiento indicado para este crédito.'); }
    $stmt=db()->prepare('SELECT fecha_inicio_practica,fecha_termino_practica FROM practicas WHERE id_practica=? AND activo=1');$stmt->execute([(int)$credito['id_practica']]);$p=$stmt->fetch();
    if(!is_array($p) || $fecha<$p['fecha_inicio_practica'] || (!empty($p['fecha_termino_practica']) && $fecha>$p['fecha_termino_practica'])) { throw new RuntimeException('La compensación debe estar dentro de la práctica activa.'); }
    $h=asistencia_horario_vigente_dia((int)$credito['id_practica'],$fecha);
    // Evité aprobar un canje sobre una jornada preexistente que conserva el cálculo histórico al corte.
    $p=tiempo_politica();$s=db()->prepare('SELECT id_jornada FROM jornadas_asistencia WHERE id_practica=? AND fecha=?');$s->execute([(int)$credito['id_practica'],$fecha]);$destino=$s->fetchColumn();
    if($destino!==false && $p!==null && (int)$destino<=(int)$p['ultima_jornada_historica']) { throw new RuntimeException('Esa jornada pertenece al corte histórico; elige una jornada nueva para compensar.'); }
    // Separé los rechazos y mostré el turno efectivo, que puede cambiar por una excepción del día.
    // Conservé los límites del canje: el mensaje no sustituye ni relaja la autorización del servidor.
    if($h===null) { throw new RuntimeException('No hay jornada laboral vigente para la fecha de compensación; revisa el horario y los feriados con tu supervisor.'); }
    $entrada=substr($h['entrada_esperada'],0,5);
    $salida=substr($h['salida_esperada'],0,5);
    if(($tipo==='ENTRADA' ? $inicio!==$entrada : $fin!==$salida) || $inicio<$entrada || $fin>$salida) {
        $extremo=$tipo==='ENTRADA' ? 'Para entrar más tarde, el tramo debe comenzar a las '.$entrada : 'Para salir antes, el tramo debe terminar a las '.$salida;
        throw new RuntimeException('El horario vigente del '.$fecha.' es '.$entrada.'–'.$salida.'. '.$extremo.' y quedar dentro de esa jornada.');
    }
    if(!empty($h['colacion_inicio']) && !empty($h['colacion_fin']) && min($fin,substr($h['colacion_fin'],0,5))>max($inicio,substr($h['colacion_inicio'],0,5))) {
        throw new RuntimeException('El tramo de compensación no puede incluir la colación vigente de '.substr($h['colacion_inicio'],0,5).' a '.substr($h['colacion_fin'],0,5).'.');
    }
    $minutos=excepciones_horario_minutos($fin)-excepciones_horario_minutos($inicio);
    if($minutos >= (int)round((float)$h['horas_planificadas']*60)) { throw new RuntimeException('La compensación no puede reemplazar la jornada completa.'); }
    $stmt=db()->prepare('SELECT COUNT(*) FROM permisos_practicante WHERE id_practica=? AND fecha_ausencia=? AND estado IN ("PENDIENTE","APROBADO")');$stmt->execute([(int)$credito['id_practica'],$fecha]);
    if((int)$stmt->fetchColumn()>0) { throw new RuntimeException('Resuelve el permiso de ese día antes de usar una compensación.'); }
    return $minutos;
}
function tiempo_compensar(int $id, string $fecha, string $tipo, string $inicio, string $fin, string $motivo): array
{
    if(!auth_check() || !tiempo_motivo_valido($motivo)) { return ['ok'=>false,'errores'=>['Indica un motivo de hasta 500 caracteres.']]; }
    try {
        db()->beginTransaction();$c=tiempo_credito($id);
        if($c===null || !tiempo_es_propietario((int)$c['id_practica'])) { throw new RuntimeException('No puedes utilizar ese crédito.'); }
        tiempo_bloquear_practica((int)$c['id_practica']);$c=tiempo_credito($id);
        $minutos=tiempo_validar_destino($c,$fecha,$tipo,$inicio,$fin);
        if($minutos>tiempo_saldo($c)) { throw new RuntimeException('El saldo aprobado disponible es insuficiente o ha vencido.'); }
        // Una sola compensación activa por día evita reservas solapadas de entrada y salida o de distintos créditos.
        $stmt=db()->prepare('SELECT COUNT(*) FROM compensaciones_tiempo ct JOIN tiempos_excepcionales te ON te.id_tiempo=ct.id_tiempo JOIN jornadas_asistencia j ON j.id_jornada=te.id_jornada WHERE j.id_practica=? AND ct.fecha=? AND ct.estado IN ("PENDIENTE","APROBADA","APLICADA")');$stmt->execute([(int)$c['id_practica'],$fecha]);
        if((int)$stmt->fetchColumn()>0) { throw new RuntimeException('Ya existe una compensación pendiente o aprobada ese día.'); }
        // Habilité el canje autónomo solo para expedientes nuevos, con saldo bloqueado y auditoría.
        $autonomo=(int)($c['politica_canje']??1)===2;
        $estado=$autonomo?'APROBADA':'PENDIENTE';
        $stmt=db()->prepare('INSERT INTO compensaciones_tiempo (id_tiempo,fecha,tipo,hora_inicio,hora_fin,minutos,motivo_solicitud,solicitado_utc,estado,revisado_utc) VALUES (?,?,?,?,?,?,?,?,?,?)');
        $ahora=tiempo_ahora_utc();
        $stmt->execute([$id,$fecha,$tipo,$inicio,$fin,$minutos,trim($motivo),$ahora,$estado,$autonomo?$ahora:null]);$nuevo=(int)db()->lastInsertId();
        tiempo_auditar($autonomo?'CANJEAR_COMPENSACION':'SOLICITAR_COMPENSACION','compensaciones_tiempo',$nuevo,null,['minutos'=>$minutos,'fecha'=>$fecha,'motivo'=>$motivo,'estado'=>$estado]);
        if($autonomo) {
            // Actualicé también una jornada ya iniciada sin modificar sus marcas ni el horario base.
            $s=db()->prepare('SELECT id_jornada FROM jornadas_asistencia WHERE id_practica=? AND fecha=?');
            $s->execute([(int)$c['id_practica'],$fecha]);$destino=$s->fetchColumn();
            if($destino!==false) { asistencia_recalcular_jornada((int)$destino,null,true); }
        }
        db()->commit();return ['ok'=>true,'mensaje'=>$autonomo?'Canje confirmado. Usa el marcaje habitual en la entrada o salida elegida.':'Compensación histórica reservada; espera la aprobación antes de modificar tu entrada o salida.'];
    } catch(Throwable $e) { if(db()->inTransaction()){db()->rollBack();}return ['ok'=>false,'errores'=>[$e instanceof RuntimeException && !$e instanceof PDOException?$e->getMessage():'No se pudo solicitar la compensación.']]; }
}
function tiempo_revisar_compensacion(int $id, bool $aprobar, string $motivo): array
{
    if(!auth_check() || !in_array(auth_rol_activo(),['ADMIN','SUPERVISOR'],true) || !tiempo_motivo_valido($motivo)) { return ['ok'=>false,'errores'=>['Requiere supervisor y motivo de hasta 500 caracteres.']]; }
    try {
        db()->beginTransaction();$stmt=db()->prepare('SELECT * FROM compensaciones_tiempo WHERE id_compensacion=?');$stmt->execute([$id]);$r=$stmt->fetch();$c=is_array($r)?tiempo_credito((int)$r['id_tiempo']):null;
        if($c===null || !auth_puede_supervisar_practica((int)$c['id_practica'])) { throw new RuntimeException('No puedes revisar esa compensación.'); }
        tiempo_bloquear_practica((int)$c['id_practica']);$stmt->execute([$id]);$r=$stmt->fetch();$c=tiempo_credito((int)$r['id_tiempo']);
        if(!in_array($r['estado'],['PENDIENTE','REVISAR'],true)) { throw new RuntimeException('La compensación ya fue revisada.'); }
        if($aprobar) {
            // Volví a comprobar la exclusividad diaria: una solicitud nueva pudo ocupar el día durante la reapertura.
            $s=db()->prepare('SELECT COUNT(*) FROM compensaciones_tiempo ct JOIN tiempos_excepcionales te ON te.id_tiempo=ct.id_tiempo JOIN jornadas_asistencia j ON j.id_jornada=te.id_jornada WHERE j.id_practica=? AND ct.fecha=? AND ct.id_compensacion<>? AND ct.estado IN ("PENDIENTE","APROBADA","APLICADA")');$s->execute([(int)$c['id_practica'],$r['fecha'],$id]);
            if((int)$s->fetchColumn()>0) { throw new RuntimeException('Ya existe otra compensación activa ese día.'); }
            // Revalidé solo canjes pasados cuya autorización original precedía al tramo; nunca autorizo retrospectivamente uno pendiente.
            $historica=$r['estado']==='REVISAR' && (!empty($r['revisado_por']) || (int)($c['politica_canje']??1)===2) && !empty($r['revisado_utc'])
                && $r['solicitado_utc']<=$r['revisado_utc']
                && $r['revisado_utc']<tiempo_fecha_utc($r['fecha'].' '.$r['hora_inicio'])
                && strtotime($r['fecha'].' '.$r['hora_fin'])<=time();
            if(!tiempo_evidencia_valida($c) || (!$historica && $c['vence_utc']<=tiempo_ahora_utc())) { throw new RuntimeException('El crédito venció o sus marcas requieren revisión.'); }
            tiempo_validar_destino($c,$r['fecha'],$r['tipo'],substr($r['hora_inicio'],0,5),substr($r['hora_fin'],0,5),!$historica);
            $necesarios=(int)$r['minutos'];
            if($historica) {
                $s=db()->prepare('SELECT * FROM jornadas_asistencia WHERE id_practica=? AND fecha=?');$s->execute([(int)$c['id_practica'],$r['fecha']]);$destino=$s->fetch();
                $marcasDestino=is_array($destino)?asistencia_marcas_vigentes_jornada((int)$destino['id_jornada']):[];
                if(!is_array($destino) || $destino['estado_jornada']==='ANULADA' || !tiempo_marcas_completas($destino,$marcasDestino)
                    || max(array_column($marcasDestino,'fecha_hora'))>date('Y-m-d H:i:s')) { throw new RuntimeException('Completa y revisa las marcas del día compensado antes de revalidar.'); }
                $necesarios=max(0,$necesarios-tiempo_minutos_trabajados($destino,$r['hora_inicio'],$r['hora_fin']));
                // Desconté los demás canjes para impedir que dos revisiones consuman el mismo crédito reducido.
                $s=db()->prepare('SELECT COALESCE(SUM(CASE WHEN estado="APLICADA" THEN minutos_aplicados ELSE minutos END),0) FROM compensaciones_tiempo WHERE id_tiempo=? AND id_compensacion<>? AND estado IN ("PENDIENTE","APROBADA","APLICADA")');$s->execute([(int)$c['id_tiempo'],$id]);
                $disponible=max(0,(int)$c['minutos_aprobados']-(int)$s->fetchColumn());
            } else { $disponible=tiempo_saldo($c); }
            if($r['estado']==='REVISAR' && $necesarios>$disponible) { throw new RuntimeException('El saldo revisado no alcanza para volver a reservar esta compensación.'); }
        }
        $estado=$aprobar?'APROBADA':'RECHAZADA';$stmt=db()->prepare('UPDATE compensaciones_tiempo SET estado=?,revisado_por=?,motivo_revision=?,revisado_utc=? WHERE id_compensacion=?');
        // Conservé autor y fecha de la autorización original al revalidar; la nueva intervención identifica al actor en auditoría.
        $stmt->execute([$estado,!empty($historica)?$r['revisado_por']:auth_usuario_actual_id(),trim($motivo),!empty($historica)?$r['revisado_utc']:tiempo_ahora_utc(),$id]);
        tiempo_auditar('REVISAR_COMPENSACION','compensaciones_tiempo',$id,$r,['estado'=>$estado,'motivo'=>$motivo]);
        // Actualicé el avance en la misma transacción, también al rechazar una compensación reabierta.
        require_once __DIR__.'/AsistenciaService.php';
        $s=db()->prepare('SELECT id_jornada FROM jornadas_asistencia WHERE id_practica=? AND fecha=?');$s->execute([(int)$c['id_practica'],$r['fecha']]);$destino=$s->fetchColumn();
        if($destino!==false) { asistencia_recalcular_jornada((int)$destino,null,true); }
        db()->commit();return ['ok'=>true,'mensaje'=>'Compensación revisada. Solo se reconocerá el tiempo autorizado realmente no trabajado.'];
    } catch(Throwable $e) { if(db()->inTransaction()){db()->rollBack();}return ['ok'=>false,'errores'=>[$e instanceof RuntimeException && !$e instanceof PDOException?$e->getMessage():'No se pudo revisar la compensación.']]; }
}

if(!function_exists('tiempo_listar')) {
    // Preparé historiales en el servicio y filtré los registros propios antes de enviarlos a las vistas.
    function tiempo_listar(?int $idPractica=null): array
    {
        if(!auth_check() || ($idPractica!==null ? !tiempo_es_propietario($idPractica) : !in_array(auth_rol_activo(),['ADMIN','SUPERVISOR'],true))) { return []; }
        $sql='SELECT te.*,j.id_practica,j.fecha AS fecha_trabajo,p.nombre_1,p.apellido_paterno,p.email FROM tiempos_excepcionales te JOIN jornadas_asistencia j ON j.id_jornada=te.id_jornada JOIN practicas pr ON pr.id_practica=j.id_practica JOIN practicantes p ON p.id_practicante=pr.id_practicante';
        if($idPractica!==null) { $sql.=' WHERE j.id_practica=?'; }
        $sql.=' ORDER BY te.id_tiempo DESC';$stmt=db()->prepare($sql);$stmt->execute($idPractica===null?[]:[$idPractica]);$lista=[];
        foreach($stmt->fetchAll() as $c) {
            if($idPractica===null && !auth_puede_supervisar_practica((int)$c['id_practica'])) { continue; }
            $c['saldo']=tiempo_saldo($c);
            // Las solicitudes nuevas pendientes todavía no tienen plazo de uso iniciado.
            $c['vencido']=!((int)($c['politica_canje']??1)===2 && empty($c['revisado_por'])) && $c['vence_utc']<=tiempo_ahora_utc();
            $s=db()->prepare('SELECT * FROM compensaciones_tiempo WHERE id_tiempo=? ORDER BY fecha,id_compensacion');$s->execute([(int)$c['id_tiempo']]);$c['compensaciones']=$s->fetchAll();$lista[]=$c;
        }
        return $lista;
    }
}

// Preparé categorías paginadas a partir de expedientes ya autorizados por tiempo_listar.
// Esta transformación no concede acceso ni consulta desde las vistas.
function tiempo_bandeja(array $creditos, string $categoria, array $filtros, int $limite, int $pagina): array
{
    $filas=[];$conteos=['tiempo'=>0,'compensacion'=>0];
    foreach($creditos as $credito) {
        $conteos['tiempo']++;$conteos['compensacion']+=count($credito['compensaciones']);
        $registros=$categoria==='tiempo'?[$credito]:$credito['compensaciones'];
        foreach($registros as $registro) {
            $fecha=$categoria==='tiempo'?$credito['fecha_trabajo']:$registro['fecha'];
            $texto=mb_strtolower(($credito['nombre_1']??'').' '.($credito['apellido_paterno']??'').' '.($credito['email']??'').' #'.$credito['id_practica']);
            if(($filtros['desde']!=='' && $fecha<$filtros['desde']) || ($filtros['hasta']!=='' && $fecha>$filtros['hasta'])
                || ($filtros['q']!=='' && !str_contains($texto,mb_strtolower($filtros['q'])))) { continue; }
            $grupo=match($registro['estado']) {
                'PENDIENTE','REVISAR'=>'PENDIENTE',
                'APROBADO','APROBADA','APLICADA'=>'APROBADO',
                'RECHAZADO','RECHAZADA'=>'RECHAZADO',
                default=>$registro['estado'],
            };
            if($filtros['estado']!=='' && $filtros['estado']!==$grupo) { continue; }
            $filas[]=['credito'=>$credito,'registro'=>$registro,'fecha'=>$fecha,'grupo'=>$grupo];
        }
    }
    usort($filas,static fn(array $a,array $b):int=>[$b['fecha'],(int)($b['registro']['id_compensacion']??$b['registro']['id_tiempo'])]<=>[$a['fecha'],(int)($a['registro']['id_compensacion']??$a['registro']['id_tiempo'])]);
    $limite=in_array($limite,[5,10,25,50],true)?$limite:5;
    $total=count($filas);$paginas=max(1,(int)ceil($total/$limite));$pagina=max(1,min($pagina,$paginas));
    return ['items'=>array_slice($filas,($pagina-1)*$limite,$limite),'total'=>$total,'paginas'=>$paginas,'pagina'=>$pagina,'conteos'=>$conteos];
}

if(!function_exists('tiempo_jornadas_solicitables')) {
    // Ofrecí jornadas propias posteriores al corte sin cambiar registros históricos.
    function tiempo_jornadas_solicitables(int $idPractica): array
    {
        if(!tiempo_es_propietario($idPractica)) { return []; }
        $p=tiempo_politica();if($p===null){return [];}
        $s=db()->prepare('SELECT id_jornada,fecha FROM jornadas_asistencia WHERE id_practica=? AND id_jornada>? AND estado_jornada NOT IN ("ANULADA","INCOMPLETA") ORDER BY fecha DESC LIMIT 30');
        $s->execute([$idPractica,(int)$p['ultima_jornada_historica']]);return $s->fetchAll();
    }
}

// Centralicé las acciones del formulario; cada servicio vuelve a validar rol, propiedad y estado.
function tiempo_procesar_accion(string $accion): array
{
    $motivo=(string)input('motivo_tiempo','');
    return match($accion) {
        'solicitar_tiempo'=>tiempo_solicitar((int)input('id_jornada',0),(string)input('hora_inicio_tiempo',''),(string)input('hora_fin_tiempo',''),$motivo),
        'revisar_tiempo'=>tiempo_revisar((int)input('id_tiempo',0),input('decision_tiempo')==='APROBAR',$motivo),
        'solicitar_compensacion'=>tiempo_compensar((int)input('id_tiempo',0),(string)input('fecha_compensacion',''),(string)input('tipo_compensacion',''),(string)input('hora_inicio_tiempo',''),(string)input('hora_fin_tiempo',''),$motivo),
        'revisar_compensacion'=>tiempo_revisar_compensacion((int)input('id_compensacion',0),input('decision_tiempo')==='APROBAR',$motivo),
        default=>['ok'=>false,'errores'=>['Acción de tiempo excepcional desconocida.']],
    };
}

// Agregué ajuste final del cálculo: no reconoce extras nuevas; el canje autorizado mantiene el avance ordinario.
function tiempo_ajustar_resumen(array $jornada, array $marcas, ?array $horario, array $resumen, bool $guardar=false): array
{
    if(!tiempo_jornada_nueva($jornada)) { return $resumen; }
    $resumen['flag_horas_extra']=0;$resumen['horas_extra_sugeridas']=0.0;
    $resumen['observaciones']=array_values(array_filter($resumen['observaciones'],static fn(string $o):bool=>!str_contains($o,'Horas fuera de horario')));
    // Conservé observación de colación ausente y recuperación pendiente, sin convertirlas en crédito excepcional.
    $base=0;$plan=recuperaciones_del_dia((int)$jornada['id_practica'],$jornada['fecha']);$recuperado=0;
    foreach($plan as $r) { if($r['estado']==='VALIDADA' && hash_equals((string)$r['firma_marcas'],recuperaciones_firma_marcas($marcas))) { $recuperado+=(int)$r['minutos_validados']; } }
    // Apliqué treinta minutos por extremo, sin sumar entrada anticipada y salida posterior ni acreditarlos.
    $pendientes=tiempo_segundos_fuera_por_extremo($jornada,$marcas,$horario,$plan);
    $segundosObservados=($pendientes['entrada']>1800?$pendientes['entrada']:0)+($pendientes['salida']>1800?$pendientes['salida']:0)+$pendientes['sin_turno'];
    if($segundosObservados>0) { $resumen['observaciones'][]='Tiempo fuera de horario pendiente de autorización excepcional: '.round($segundosObservados/60,2).' min. Revisar la jornada no aprueba ese tiempo.'; }
    if($horario!==null) {
        // Reemplacé los márgenes anteriores de atraso/salida anticipada por el margen individual de treinta minutos.
        $entradaEsperada=strtotime($jornada['fecha'].' '.$horario['entrada_esperada']);$salidaEsperada=strtotime($jornada['fecha'].' '.$horario['salida_esperada']);
        $resumen['flag_atraso']=!empty($resumen['hora_entrada']) && strtotime($resumen['hora_entrada'])-$entradaEsperada>1800?1:0;
        $resumen['flag_salida_anticipada']=!empty($resumen['hora_salida']) && $salidaEsperada-strtotime($resumen['hora_salida'])>1800?1:0;
        $resumen['observaciones']=array_values(array_filter($resumen['observaciones'],static fn(string $o):bool=>!in_array($o,['Atraso detectado.','Salida anticipada detectada.'],true)));
        if($resumen['flag_atraso']){$resumen['observaciones'][]='Atraso detectado.';}
        if($resumen['flag_salida_anticipada']){$resumen['observaciones'][]='Salida anticipada detectada.';}
    }
    foreach($marcas as $marca) {
        if(strtotime($marca['fecha_hora'])>time()) { $resumen['observaciones'][]='La jornada contiene marcas futuras; todavía no representan tiempo trabajado confirmado.';break; }
    }
    // Conservé la validación obligatoria de sábados de fase 5: solo una recuperación validada reconoce ese trabajo.
    if($horario!==null && (int)date('N',strtotime($jornada['fecha']))!==6) {
        $ini=strtotime($jornada['fecha'].' '.$horario['entrada_esperada']);$fin=strtotime($jornada['fecha'].' '.$horario['salida_esperada']);
        foreach(asistencia_tramos_trabajados($marcas) as [$a,$b]) {
            $x=max($a,$ini);$y=min($b,$fin);$seg=max(0,$y-$x);
            foreach($plan as $r) { $seg-=max(0,min($y,strtotime($r['fecha'].' '.$r['hora_fin']))-max($x,strtotime($r['fecha'].' '.$r['hora_inicio']))); }
            $base+=max(0,$seg);
        }
    }
    $credito=0;$stmt=db()->prepare('SELECT ct.* FROM compensaciones_tiempo ct JOIN tiempos_excepcionales te ON te.id_tiempo=ct.id_tiempo JOIN jornadas_asistencia j ON j.id_jornada=te.id_jornada WHERE j.id_practica=? AND ct.fecha=? AND ct.estado IN ("APROBADA","APLICADA","REVISAR")');
    $stmt->execute([(int)$jornada['id_practica'],$jornada['fecha']]);
    foreach($stmt->fetchAll() as $r) {
        $c=tiempo_credito((int)$r['id_tiempo']);$valida=$r['estado']!=='REVISAR' && tiempo_evidencia_valida($c);
        try { tiempo_validar_destino($c,$r['fecha'],$r['tipo'],substr($r['hora_inicio'],0,5),substr($r['hora_fin'],0,5),false); }
        catch(RuntimeException $e) { $valida=false; }
        $minutos=0;
        if($valida && tiempo_marcas_completas($jornada,$marcas)) {
            // Conté únicamente el tramo no trabajado; si se quedó trabajando no se duplica el crédito.
            $a=strtotime($r['fecha'].' '.$r['hora_inicio']);$b=strtotime($r['fecha'].' '.$r['hora_fin']);$trabajados=0;
            foreach(asistencia_tramos_trabajados($marcas) as [$x,$y]) { $trabajados+=max(0,min($b,$y)-max($a,$x)); }
            $minutos=max(0,(int)floor(($b-$a-$trabajados)/60));
            $credito+=$minutos;
            if($r['tipo']==='ENTRADA' && !empty($resumen['hora_entrada']) && strtotime($resumen['hora_entrada'])<=strtotime($r['fecha'].' '.$r['hora_fin'])) { $resumen['flag_atraso']=0;$resumen['observaciones']=array_values(array_filter($resumen['observaciones'],static fn(string $o):bool=>$o!=='Atraso detectado.')); }
            if($r['tipo']==='SALIDA' && !empty($resumen['hora_salida']) && strtotime($resumen['hora_salida'])>=strtotime($r['fecha'].' '.$r['hora_inicio'])) { $resumen['flag_salida_anticipada']=0;$resumen['observaciones']=array_values(array_filter($resumen['observaciones'],static fn(string $o):bool=>$o!=='Salida anticipada detectada.')); }
        }
        if($guardar) {
            $estado=$valida?($resumen['estado_jornada']==='INCOMPLETA'?'APROBADA':'APLICADA'):'REVISAR';
            if($estado!==$r['estado'] || $minutos!==(int)$r['minutos_aplicados']) {
                $s=db()->prepare('UPDATE compensaciones_tiempo SET estado=?,minutos_aplicados=? WHERE id_compensacion=?');$s->execute([$estado,$minutos,(int)$r['id_compensacion']]);
                tiempo_auditar('APLICAR_COMPENSACION','compensaciones_tiempo',(int)$r['id_compensacion'],$r,['estado'=>$estado,'minutos_aplicados'=>$minutos]);
            }
        }
        if(!$valida) { $resumen['observaciones'][]='Compensación pendiente de nueva revisión de horario o evidencia.'; }
    }
    // Limité trabajo ordinario y compensación al horario neto pactado; recuperación validada conserva su crédito separado.
    $ordinarias=$base+$credito*60;
    if($horario!==null && isset($horario['horas_planificadas'])){$ordinarias=min($ordinarias,max(0,(float)$horario['horas_planificadas'])*3600);}
    $resumen['horas_calculadas']=$resumen['estado_jornada']==='INCOMPLETA'?0.0:round($ordinarias/3600+$recuperado/60,2);
    if($resumen['estado_jornada']==='OBSERVADA' && $resumen['observaciones']===[]) { $resumen['estado_jornada']='VALIDA'; }
    if($resumen['estado_jornada']==='VALIDA' && $resumen['observaciones']!==[]) { $resumen['estado_jornada']='OBSERVADA'; }
    return $resumen;
}

// Separé segundos antes/después del turno para no acumular los márgenes; los segundos conservan el límite exacto.
function tiempo_segundos_fuera_por_extremo(array $jornada,array $marcas,?array $horario,array $plan): array
{
    $cubiertos=[];
    $entradaEsperada=$horario!==null?strtotime($jornada['fecha'].' '.$horario['entrada_esperada']):null;
    $salidaEsperada=$horario!==null?strtotime($jornada['fecha'].' '.$horario['salida_esperada']):null;
    if($horario!==null) { $cubiertos[]=[$entradaEsperada,$salidaEsperada]; }
    foreach($plan as $tramo) { $cubiertos[]=[strtotime($tramo['fecha'].' '.$tramo['hora_inicio']),strtotime($tramo['fecha'].' '.$tramo['hora_fin'])]; }
    $s=db()->prepare('SELECT id_tiempo,hora_inicio,hora_fin FROM tiempos_excepcionales WHERE id_jornada=? AND estado="APROBADO"');$s->execute([(int)$jornada['id_jornada']]);
    foreach($s->fetchAll() as $tramo) {
        if(tiempo_evidencia_valida(tiempo_credito((int)$tramo['id_tiempo']))) { $cubiertos[]=[strtotime($jornada['fecha'].' '.$tramo['hora_inicio']),strtotime($jornada['fecha'].' '.$tramo['hora_fin'])]; }
    }
    $segundos=['entrada'=>0,'salida'=>0,'sin_turno'=>0];
    foreach(asistencia_tramos_trabajados($marcas) as $trabajo) {
        $restantes=[$trabajo];
        foreach($cubiertos as [$a,$b]) {
            $nuevos=[];
            foreach($restantes as [$x,$y]) {
                if($b<=$x || $a>=$y) { $nuevos[]=[$x,$y];continue; }
                if($x<$a) { $nuevos[]=[$x,$a]; }if($b<$y) { $nuevos[]=[$b,$y]; }
            }
            $restantes=$nuevos;
        }
        foreach($restantes as [$x,$y]) {
            if($entradaEsperada===null || $salidaEsperada===null){$segundos['sin_turno']+=$y-$x;}
            elseif($y<=$entradaEsperada){$segundos['entrada']+=$y-$x;}
            elseif($x>=$salidaEsperada){$segundos['salida']+=$y-$x;}
        }
    }
    return $segundos;
}

// Reabrí el crédito y retiré compensaciones dependientes si se alteran las marcas que sustentaban la aprobación.
function tiempo_reabrir_por_marcas(array $jornada): array
{
    $stmt=db()->prepare('SELECT * FROM tiempos_excepcionales WHERE id_jornada=? AND estado="APROBADO"');$stmt->execute([(int)$jornada['id_jornada']]);$dependientes=[];
    foreach($stmt->fetchAll() as $r) {
        if(tiempo_evidencia_valida(tiempo_credito((int)$r['id_tiempo']))) { continue; }
        $s=db()->prepare('UPDATE tiempos_excepcionales SET estado="REVISAR",minutos_aprobados=0 WHERE id_tiempo=?');$s->execute([(int)$r['id_tiempo']]);
        tiempo_auditar('REABRIR_TIEMPO_EXCEPCIONAL','tiempos_excepcionales',(int)$r['id_tiempo'],$r,['estado'=>'REVISAR']);
        $s=db()->prepare('SELECT j.id_jornada FROM compensaciones_tiempo ct JOIN jornadas_asistencia j ON j.fecha=ct.fecha WHERE ct.id_tiempo=? AND j.id_practica=? AND ct.estado IN ("APROBADA","APLICADA")');$s->execute([(int)$r['id_tiempo'],(int)$jornada['id_practica']]);$dependientes=array_merge($dependientes,$s->fetchAll(PDO::FETCH_COLUMN));
        $s=db()->prepare('UPDATE compensaciones_tiempo SET estado="REVISAR",minutos_aplicados=0 WHERE id_tiempo=? AND estado IN ("PENDIENTE","APROBADA","APLICADA")');$s->execute([(int)$r['id_tiempo']]);
    }
    return array_unique(array_map('intval',$dependientes));
}
