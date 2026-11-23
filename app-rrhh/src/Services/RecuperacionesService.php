<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/AuthService.php';

// Agregué lectura común del calendario; no se deducen feriados por país ni desde una API externa.
if (!function_exists('recuperaciones_es_feriado')) {
function recuperaciones_es_feriado(string $fecha): bool
{
    try {
        $stmt=db()->prepare('SELECT COUNT(*) FROM feriados WHERE fecha=? AND activo=1');
        $stmt->execute([$fecha]);
        return (int)$stmt->fetchColumn()>0;
    } catch (Throwable) {
        return false;
    }
}
}

// Fijé la política de tramos nuevos en servidor; el formulario no puede elegir la histórica.
if (!function_exists('recuperaciones_politica_solicitud')) {
function recuperaciones_politica_solicitud(): int { return 2; }
}

// Calculé el corte en la zona del portal, incluyendo cambios de hora de Chile.
function recuperaciones_corte_sabado(string $fecha): string
{
    return (new DateTimeImmutable($fecha,new DateTimeZone(APP_TIMEZONE)))->modify('-1 day')->setTime(12,0)->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
}
function recuperaciones_dentro_plazo(string $fecha, string $solicitudUtc): bool
{
    return (int)date('N',strtotime($fecha))===6 && $solicitudUtc<recuperaciones_corte_sabado($fecha);
}
const RECUPERACION_FUERA_PLAZO='Solicitud rechazada automáticamente: debes solicitar la recuperación antes del viernes a las 12:00. Puedes elegir el sábado de la semana siguiente.';

// Registré intentos fuera del corte sin crear permisos pendientes ni reservar minutos.
function recuperaciones_auditar_rechazo_plazo(int $idPractica): void
{
    if(!auditoria_registrar('RECHAZO_PLAZO_RECUPERACION',[
        'tabla_afectada'=>'practicas','id_registro_afectado'=>$idPractica,
        'valores_nuevos'=>['resultado'=>'RECHAZADO','motivo'=>RECUPERACION_FUERA_PLAZO,'fecha_utc'=>gmdate('Y-m-d H:i:s')],
    ])) { error_log('No se pudo auditar un rechazo de recuperación fuera de plazo.'); }
}

if (!function_exists('recuperaciones_calcular_plazo_aprobacion')) {
    /**
     * Calcula los plazos y márgenes hábiles de aprobación para una fecha de sábado de recuperación.
     * Ofrece una tolerancia de hasta 5 días hábiles posteriores al sábado (Opción A).
     *
     * @param string $fechaSabado Fecha del sábado de recuperación (YYYY-MM-DD).
     * @param string|null $fechaReferencia Fecha de comparación (por defecto fecha actual).
     * @return array
     */
    function recuperaciones_calcular_plazo_aprobacion(string $fechaSabado, ?string $fechaReferencia = null): array
    {
        $fechaRef = $fechaReferencia !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaReferencia)
            ? $fechaReferencia
            : date('Y-m-d');

        $diaBase = new DateTimeImmutable($fechaSabado);
        $contados = 0;
        $fechaLimite = $fechaSabado;
        $diasHabilesLista = [];

        // Buscamos 5 días hábiles siguientes al sábado (lunes a viernes)
        for ($i = 1; $i <= 30; $i++) {
            $sig = $diaBase->modify("+{$i} days");
            $numDia = (int) $sig->format('N');
            $fStr = $sig->format('Y-m-d');

            if ($numDia === 6 || $numDia === 7) {
                continue;
            }

            $contados++;
            $diasHabilesLista[] = $fStr;
            if ($contados === 5) {
                $fechaLimite = $fStr;
                break;
            }
        }

        $esPasado = $fechaRef > $fechaSabado;
        $esMismoDia = $fechaRef === $fechaSabado;
        $dentroMargen = $fechaRef <= $fechaLimite;

        // Días hábiles restantes para aprobar dentro del margen de 5 días
        $habilesRestantes = 0;
        foreach ($diasHabilesLista as $dh) {
            if ($dh >= $fechaRef) {
                $habilesRestantes++;
            }
        }

        // Determinar si el sábado es próximo (dentro de los próximos 6 días respecto a fechaRef)
        $diffDias = (int) (new DateTimeImmutable($fechaRef))->diff(new DateTimeImmutable($fechaSabado))->format('%r%a');
        $esProximo = !$esPasado && $diffDias >= 0 && $diffDias <= 6;

        return [
            'fecha_sabado'      => $fechaSabado,
            'fecha_limite'      => $fechaLimite,
            'dias_habiles'      => $diasHabilesLista,
            'es_pasado'         => $esPasado,
            'es_mismo_dia'      => $esMismoDia,
            'es_proximo'        => $esProximo,
            'diff_dias'         => $diffDias,
            'dentro_margen'     => $dentroMargen,
            'habiles_restantes' => $habilesRestantes,
        ];
    }
}

// Agregué rangos diarios y conservé la política anterior solo para revisar tramos ya guardados.
function recuperaciones_opciones_fecha(int $idPractica, string $fecha, ?int $politica = null): array
{
    $politica??=recuperaciones_politica_solicitud();
    if (!permisos_gestion_fecha_valida($fecha)) { return []; }
    if ($politica===2 && (int)date('N',strtotime($fecha))!==6) { return []; }
    $base=permisos_gestion_horario_base_vigente($idPractica,$fecha);
    if ($base===null) { return []; }
    $feriado=recuperaciones_es_feriado($fecha);
    $stmt=db()->prepare('SELECT COUNT(*) FROM excepciones_horario_practicante WHERE id_horario=? AND fecha=? AND tipo_excepcion="FERIADO" AND activo=1');
    $stmt->execute([(int)$base['id_horario'],$fecha]);
    $feriado=$feriado || (int)$stmt->fetchColumn()>0;
    $dia=(int)date('N',strtotime($fecha));
    if ($dia===7) { return []; }
    // Conservé la autorización especial de feriados, restringida también a sábado en política nueva.
    if ($feriado) { return [['tipo'=>'FERIADO','inicio'=>'10:00','fin'=>'20:00','autorizacion_especial'=>true]]; }
    $turno=asistencia_horario_vigente_dia($idPractica,$fecha);
    if ($dia===6 && $turno===null) { return [['tipo'=>'SABADO','inicio'=>'10:00','fin'=>'20:00']]; }
    if ($turno===null) { return []; }
    $opciones=[];
    $entrada=substr($turno['entrada_esperada'],0,5);
    $salida=substr($turno['salida_esperada'],0,5);
    if ($entrada>'10:00') { $opciones[]=['tipo'=>$dia===6?'SABADO':'EXTENSION','inicio'=>'10:00','fin'=>min($entrada,'20:00')]; }
    if ($salida<'20:00') { $opciones[]=['tipo'=>$dia===6?'SABADO':'EXTENSION','inicio'=>max($salida,'10:00'),'fin'=>'20:00']; }
    if ($politica!==2 && !empty($turno['colacion_inicio']) && !empty($turno['colacion_fin'])) {
        $a=max('10:00',substr($turno['colacion_inicio'],0,5));
        $b=min('20:00',substr($turno['colacion_fin'],0,5));
        if ($b>$a) { $opciones[]=['tipo'=>'COLACION','inicio'=>$a,'fin'=>$b]; }
    }
    return $opciones;
}

// Desconté 1 hora de colación en tramos sabatinos/feriados de 6 horas o más,
// consistente con la regla de jornada laboral del sistema.
function recuperaciones_minutos_netos_tramo(string $tipo, string $inicio, string $fin): int
{
    $brutos=excepciones_horario_minutos($fin)-excepciones_horario_minutos($inicio);
    if ($brutos<=0) { return 0; }
    if (in_array($tipo,['SABADO','FERIADO'],true) && $brutos>=360) {
        return $brutos-60;
    }
    return $brutos;
}

// Agregué validación de todos los tramos antes de reservarlos; los minutos se comparan sin redondear horas.
function recuperaciones_validar_plan(int $idPractica, array $input, int $minutosAusencia, array $ausencia, bool $revisando=false, bool $permitirMenos=false): array
{
    $errores=[]; $tramos=[]; $total=0;
    if ($input===[] || count($input)>60) { return ['errores'=>['Agrega entre uno y sesenta tramos de recuperación.'],'tramos'=>[]]; }
    $stmt=db()->prepare('SELECT fecha_inicio_practica,fecha_termino_practica FROM practicas WHERE id_practica=?');
    $stmt->execute([$idPractica]); $practica=$stmt->fetch();
    foreach ($input as $i=>$fila) {
        if (!is_array($fila)) { $errores[]='Tramo inválido.'; continue; }
        // Rechacé arrays/valores no textuales por campo antes de convertir fechas y horas.
        $camposValidos=true;
        foreach (['fecha','tipo','hora_inicio','hora_fin'] as $campo) {
            if (!isset($fila[$campo]) || !is_string($fila[$campo])) { $camposValidos=false; }
        }
        if (!$camposValidos) { $errores[]='Indica fecha, tipo e inicio/fin válidos en cada tramo.'; continue; }
        $fecha=(string)($fila['fecha']??''); $tipo=(string)($fila['tipo']??'');
        // Recorté segundos solo al revisar campos TIME ya persistidos, nunca entradas libres del formulario.
        $a=(string)($fila['hora_inicio']??''); $b=(string)($fila['hora_fin']??'');
        if ($revisando) { $a=substr($a,0,5); $b=substr($b,0,5); }
        // Solo una revisión de datos persistidos puede conservar política y fecha históricas.
        $politica=$revisando?(int)($fila['politica_recuperacion']??1):recuperaciones_politica_solicitud();
        $solicitadoUtc=$revisando?($fila['solicitado_utc']??null):gmdate('Y-m-d H:i:s');
        if (!permisos_gestion_fecha_valida($fecha) || !permisos_gestion_hora_valida($a) || !permisos_gestion_hora_valida($b)
            || $a<'10:00' || $b>'20:00' || $b<=$a || (int)date('N',strtotime($fecha))===7) {
            $errores[]='Cada tramo debe tener fecha válida, de lunes a sábado y entre 10:00 y 20:00.'; continue;
        }
        if (!$revisando && strtotime($fecha.' '.$a)<=time()) { $errores[]='Solicita la recuperación antes de comenzar ese tramo.'; }
        if ($politica===2) {
            if ((int)date('N',strtotime($fecha))!==6 || !in_array($tipo,['SABADO','FERIADO'],true)) {
                $errores[]='Las recuperaciones nuevas solo pueden realizarse los sábados entre 10:00 y 20:00.';
            } elseif (!is_string($solicitadoUtc) || !recuperaciones_dentro_plazo($fecha,$solicitadoUtc)) {
                $errores[]=RECUPERACION_FUERA_PLAZO;
            }
            if ($fecha<($ausencia['fecha_ausencia']??$fecha)) { $errores[]='El sábado de recuperación no puede ser anterior al día de ausencia.'; }
        }
        if ($practica===false || $fecha<$practica['fecha_inicio_practica'] || (!empty($practica['fecha_termino_practica']) && $fecha>$practica['fecha_termino_practica'])) {
            $errores[]='Los tramos deben estar dentro de la vigencia de la práctica.';
        }
        $elegible=false;
        foreach (recuperaciones_opciones_fecha($idPractica,$fecha,$politica) as $opcion) {
            if ($tipo===$opcion['tipo'] && $a>=$opcion['inicio'] && $b<=$opcion['fin']) { $elegible=true; }
        }
        if (!$elegible) { $errores[]=$politica===2?'El tramo no corresponde a un sábado disponible de 10:00 a 20:00; no puede ocupar jornada ordinaria. Un sábado feriado requiere autorización especial.':"El tramo {$fecha} {$a}–{$b} no corresponde a colación, extensión, sábado disponible o feriado de tu horario."; }
        foreach ($tramos as $previo) {
            if ($previo['fecha']===$fecha && min($b,$previo['hora_fin'])>max($a,$previo['hora_inicio'])) { $errores[]='Los tramos del plan no pueden superponerse.'; }
        }
        if (($ausencia['fecha_ausencia']??'')===$fecha) {
            $x=($ausencia['tipo_ausencia']??'COMPLETA')==='PARCIAL'?substr($ausencia['hora_inicio_ausencia'],0,5):($ausencia['entrada']??'00:00');
            $y=($ausencia['tipo_ausencia']??'COMPLETA')==='PARCIAL'?substr($ausencia['hora_fin_ausencia'],0,5):($ausencia['salida']??'23:59');
            if (min($b,$y)>max($a,$x)) { $errores[]='No puedes recuperar dentro del tramo que solicitaste faltar.'; }
        }
        // Impedí reutilizar minutos reservados por otro permiso pendiente o aprobado.
        // Protegí también intervalos solicitados como tiempo excepcional o como compensación.
        if(function_exists('tiempo_politica') && tiempo_politica()!==null) {
            $s=db()->prepare('SELECT te.hora_inicio,te.hora_fin FROM tiempos_excepcionales te JOIN jornadas_asistencia j ON j.id_jornada=te.id_jornada WHERE j.id_practica=? AND j.fecha=? AND te.estado<>"RECHAZADO"');
            $s->execute([$idPractica,$fecha]);
            foreach($s->fetchAll() as $r) { if(min($b,substr($r['hora_fin'],0,5))>max($a,substr($r['hora_inicio'],0,5))) { $errores[]='Ese intervalo ya está solicitado como tiempo excepcional.'; } }
            $s=db()->prepare('SELECT ct.hora_inicio,ct.hora_fin FROM compensaciones_tiempo ct JOIN tiempos_excepcionales te ON te.id_tiempo=ct.id_tiempo JOIN jornadas_asistencia j ON j.id_jornada=te.id_jornada WHERE j.id_practica=? AND ct.fecha=? AND ct.estado IN ("PENDIENTE","APROBADA","APLICADA")');
            $s->execute([$idPractica,$fecha]);
            foreach($s->fetchAll() as $r) { if(min($b,substr($r['hora_fin'],0,5))>max($a,substr($r['hora_inicio'],0,5))) { $errores[]='Ese intervalo ya está reservado como compensación.'; } }
        }
        $stmt=db()->prepare('SELECT rp.* FROM recuperaciones_permiso rp INNER JOIN permisos_practicante pp ON pp.id_permiso=rp.id_permiso WHERE pp.id_practica=? AND pp.estado IN ("PENDIENTE","APROBADO") AND rp.estado IN ("PROPUESTA","PROGRAMADA","VALIDADA") AND rp.fecha=? AND pp.id_permiso<>?');
        $stmt->execute([$idPractica,$fecha,(int)($ausencia['id_permiso']??0)]);
        foreach ($stmt->fetchAll() as $reservado) {
            if (min($b,substr($reservado['hora_fin'],0,5))>max($a,substr($reservado['hora_inicio'],0,5))) { $errores[]='Ese tiempo ya está reservado por otro permiso.'; }
        }
        // Protegí reservas históricas de un solo día y ausencias activas de otros permisos.
        $stmt=db()->prepare('SELECT pp.* FROM permisos_practicante pp WHERE pp.id_practica=? AND pp.estado IN ("PENDIENTE","APROBADO") AND pp.id_permiso<>? AND (pp.fecha_ausencia=? OR pp.fecha_recuperacion=?)');
        $stmt->execute([$idPractica,(int)($ausencia['id_permiso']??0),$fecha,$fecha]);
        foreach ($stmt->fetchAll() as $otro) {
            if ($otro['fecha_ausencia']===$fecha) {
                $x=($otro['tipo_ausencia']??'COMPLETA')==='PARCIAL'?substr($otro['hora_inicio_ausencia'],0,5):'00:00';
                $y=($otro['tipo_ausencia']??'COMPLETA')==='PARCIAL'?substr($otro['hora_fin_ausencia'],0,5):'23:59';
                if (min($b,$y)>max($a,$x)) { $errores[]='La recuperación cruza una ausencia pendiente o aprobada.'; }
            }
            if ($otro['fecha_recuperacion']===$fecha && recuperaciones_plan((int)$otro['id_permiso'])===[]
                && min($b,substr($otro['hora_fin_recuperacion'],0,5))>max($a,substr($otro['hora_inicio_recuperacion'],0,5))) {
                $errores[]='El tramo ya está reservado por un permiso anterior.';
            }
        }
        $minutos=$revisando && isset($fila['minutos_planificados'])
            ? (int)$fila['minutos_planificados']
            : recuperaciones_minutos_netos_tramo($tipo,$a,$b);
        $total+=$minutos;
        $tramos[]=['fecha'=>$fecha,'tipo'=>$tipo,'hora_inicio'=>$a,'hora_fin'=>$b,'minutos_planificados'=>$minutos,
            'politica_recuperacion'=>$politica,'solicitado_utc'=>$solicitadoUtc];
    }
    if ($total<=0 || $total>$minutosAusencia || (!$permitirMenos && $total!==$minutosAusencia)) {
        $errores[]=$permitirMenos?'El tramo adicional debe ser positivo y no superar el saldo libre.':'El total del plan debe coincidir exactamente con los minutos de ausencia laboral.';
    }
    return ['errores'=>array_values(array_unique($errores)),'tramos'=>$tramos];
}

// Agregué persistencia del plan dentro de la transacción de la solicitud.
function recuperaciones_guardar_plan(int $idPermiso, array $tramos): void
{
    // Guardé UTC de envío, no la hora de aprobación: una revisión tardía conserva el corte original.
    $stmt=db()->prepare('INSERT INTO recuperaciones_permiso (id_permiso,fecha,tipo,hora_inicio,hora_fin,minutos_planificados,politica_recuperacion,solicitado_utc) VALUES (?,?,?,?,?,?,?,?)');
    foreach ($tramos as $tramo) { $stmt->execute([$idPermiso,$tramo['fecha'],$tramo['tipo'],$tramo['hora_inicio'],$tramo['hora_fin'],$tramo['minutos_planificados'],$tramo['politica_recuperacion']??recuperaciones_politica_solicitud(),$tramo['solicitado_utc']??gmdate('Y-m-d H:i:s')]); }
}

function recuperaciones_plan(int $idPermiso): array
{
    // Consulté el plan ordenado para mostrar todas las fechas y sus validaciones.
    $stmt=db()->prepare('SELECT * FROM recuperaciones_permiso WHERE id_permiso=? ORDER BY fecha,hora_inicio,id_recuperacion');
    $stmt->execute([$idPermiso]); return $stmt->fetchAll();
}

function recuperaciones_adjuntar_planes(array $permisos): array
{
    // Preparé datos en servicio; las vistas no hacen consultas a la base.
    foreach ($permisos as &$permiso) { $permiso['recuperaciones']=recuperaciones_plan((int)$permiso['id_permiso']); }
    unset($permiso); return $permisos;
}

// Agregué saldo libre para volver a solicitar lo no trabajado sin duplicar minutos ya reservados.
function recuperaciones_saldo_libre(array $permiso, array $plan): int
{
    $ocupados=0;
    foreach ($plan as $fila) {
        $ocupados+=in_array($fila['estado'],['PROPUESTA','PROGRAMADA'],true)?(int)$fila['minutos_planificados']:(int)$fila['minutos_validados'];
    }
    return max(0,(int)round((float)($permiso['horas_ausencia']??0)*60)-$ocupados);
}

// Agregué solicitud y autorización de tramos adicionales sobre un saldo incompleto, conservando el historial.
function recuperaciones_gestionar_adicional(int $idPermiso, array $fila, string $motivo, ?int $idRecuperacion = null, bool $autorizarFeriado = false): array
{
    $autorizar=$idRecuperacion!==null;
    if (!auth_check() || trim($motivo)==='' || mb_strlen($motivo)>500
        || ($autorizar ? !in_array(auth_rol_activo(),['ADMIN','SUPERVISOR'],true) : auth_rol_activo()!=='PRACTICANTE')) {
        return ['ok'=>false,'errores'=>['La acción requiere rol autorizado y motivo de hasta 500 caracteres.']];
    }
    try {
        db()->beginTransaction();
        $stmt=db()->prepare('SELECT pp.*,pr.id_practicante FROM permisos_practicante pp INNER JOIN practicas pr ON pr.id_practica=pp.id_practica WHERE pp.id_permiso=? FOR UPDATE');
        $stmt->execute([$idPermiso]);$permiso=$stmt->fetch();
        if (!is_array($permiso) || $permiso['estado']!=='APROBADO'
            || ($autorizar?!auth_puede_supervisar_practica((int)$permiso['id_practica']):(int)$permiso['id_practicante']!==auth_practicante_actual_id())) {
            throw new RuntimeException('No puedes modificar ese plan de recuperación.');
        }
        $lock=db()->prepare('SELECT id_practica FROM practicas WHERE id_practica=? FOR UPDATE');$lock->execute([(int)$permiso['id_practica']]);
        $stmt=db()->prepare('SELECT * FROM recuperaciones_permiso WHERE id_permiso=? FOR UPDATE');$stmt->execute([$idPermiso]);$plan=$stmt->fetchAll();
        if ($plan===[]) { throw new RuntimeException('Los permisos históricos conservan su flujo anterior.'); }
        $tramo=null;$otros=$plan;
        if ($autorizar) {
            foreach ($plan as $p) { if ((int)$p['id_recuperacion']===$idRecuperacion) { $tramo=$p; } }
            if ($tramo===null || $tramo['estado']!=='PROPUESTA') { throw new RuntimeException('El tramo no está pendiente de autorización.'); }
            $fila=$tramo;
            $otros=array_values(array_filter($plan,static fn(array $p):bool=>(int)$p['id_recuperacion']!==$idRecuperacion));
        }
        $libre=recuperaciones_saldo_libre($permiso,$otros);
        $validacion=recuperaciones_validar_plan((int)$permiso['id_practica'],[$fila],$libre,$permiso,$autorizar,true);
        if ($validacion['errores']!==[]) { throw new RuntimeException(implode(' ',$validacion['errores'])); }
        $nuevo=$validacion['tramos'][0];
        foreach ($otros as $p) {
            // Un tramo enviado a reprogramación conserva historial pero ya no reserva ese intervalo.
            if ($p['estado']==='REPROGRAMAR') { continue; }
            if ($p['fecha']===$nuevo['fecha'] && min(substr($p['hora_fin'],0,5),$nuevo['hora_fin'])>max(substr($p['hora_inicio'],0,5),$nuevo['hora_inicio'])) {
                throw new RuntimeException('Ese tramo ya figura en el plan del mismo permiso.');
            }
        }
        if ($autorizar) {
            if ($nuevo['tipo']==='FERIADO' && !$autorizarFeriado) { throw new RuntimeException('El feriado requiere autorización expresa.'); }
            $stmt=db()->prepare('UPDATE recuperaciones_permiso SET estado="PROGRAMADA",autorizado_feriado=? WHERE id_recuperacion=?');
            $stmt->execute([$autorizarFeriado?1:0,$idRecuperacion]);
        } else {
            recuperaciones_guardar_plan($idPermiso,[$nuevo]);$idRecuperacion=(int)db()->lastInsertId();
        }
        if (!auditoria_registrar($autorizar?'AUTORIZAR_RECUPERACION_ADICIONAL':'SOLICITAR_RECUPERACION_ADICIONAL',[
            'tabla_afectada'=>'recuperaciones_permiso','id_registro_afectado'=>$idRecuperacion,
            'valores_anteriores'=>$tramo,'valores_nuevos'=>['tramo'=>$nuevo,'motivo'=>trim($motivo),'autorizar_feriado'=>$autorizarFeriado],
        ])) { throw new RuntimeException('No se pudo auditar el tramo adicional.'); }
        db()->commit();return ['ok'=>true,'errores'=>[]];
    } catch(Throwable $e) {
        if(db()->inTransaction())db()->rollBack();
        // Después del rollback conservé la trazabilidad del intento adicional fuera de plazo.
        if(isset($permiso) && is_array($permiso) && str_contains($e->getMessage(),RECUPERACION_FUERA_PLAZO)) {
            recuperaciones_auditar_rechazo_plazo((int)$permiso['id_practica']);
        }
        error_log('Tramo adicional: '.$e->getMessage());
        return ['ok'=>false,'errores'=>[$e instanceof RuntimeException?$e->getMessage():'No se pudo guardar el tramo adicional.']];
    }
}

// Agregué lectura diaria de recuperaciones autorizadas, sin convertirlas en excepciones de turno completo.
if (!function_exists('recuperaciones_del_dia')) {
function recuperaciones_del_dia(int $idPractica, string $fecha): array
{
    $stmt=db()->prepare('SELECT rp.*,pp.horas_ausencia FROM recuperaciones_permiso rp INNER JOIN permisos_practicante pp ON pp.id_permiso=rp.id_permiso WHERE pp.id_practica=? AND pp.estado="APROBADO" AND rp.fecha=? AND rp.estado IN ("PROGRAMADA","VALIDADA") ORDER BY rp.hora_inicio,rp.id_recuperacion');
    $stmt->execute([$idPractica,$fecha]); return $stmt->fetchAll();
}
}

// Agregué cálculo de recuperación separado de base ordinaria y extras: solo lo validado suma al acumulado.
function recuperaciones_ajustar_resumen(array $jornada, array $marcas, ?array $horario, array $resumen, bool $invalidar = false): array
{
    $plan=recuperaciones_del_dia((int)$jornada['id_practica'],$jornada['fecha']);
    // Los feriados tampoco generan jornada ordinaria en la política nueva.
    $sabado=!empty($jornada['politica_recuperacion_v2']) && ((int)date('N',strtotime($jornada['fecha']))===6 || recuperaciones_es_feriado($jornada['fecha']));
    if ($plan===[] && !$sabado) { return $resumen; }
    $firma=recuperaciones_firma_marcas($marcas); $credito=0; $reservados=0; $hayPendientes=false;
    foreach ($plan as $tramo) {
        // Una corrección de marcas reabre la validación; no conserva créditos sobre evidencia anterior.
        if ($tramo['estado']==='VALIDADA' && !hash_equals((string)$tramo['firma_marcas'],$firma)) {
            if ($invalidar) {
            $stmt=db()->prepare('UPDATE recuperaciones_permiso SET estado="PROGRAMADA",minutos_validados=0,firma_marcas=NULL WHERE id_recuperacion=?');
            $stmt->execute([(int)$tramo['id_recuperacion']]);
            if (!auditoria_registrar('REABRIR_RECUPERACION',['tabla_afectada'=>'recuperaciones_permiso','id_registro_afectado'=>(int)$tramo['id_recuperacion'],'valores_anteriores'=>$tramo,'valores_nuevos'=>['estado'=>'PROGRAMADA','minutos_validados'=>0]])) {
                throw new RuntimeException('No se pudo auditar la reapertura de recuperación.');
            }
            }
            $tramo['minutos_validados']=0;
            $tramo['estado']='PROGRAMADA';
        }
        $hayPendientes=$hayPendientes || $tramo['estado']==='PROGRAMADA';
        $credito+=(int)$tramo['minutos_validados'];
        $reservados+=recuperaciones_minutos_efectivos($tramo,$marcas)*60;
    }
    if ($resumen['estado_jornada']==='INCOMPLETA') { return $resumen; }
    $base=0; $total=0;
    $entrada=isset($horario['entrada_esperada'])?strtotime($jornada['fecha'].' '.$horario['entrada_esperada']):null;
    $salida=isset($horario['salida_esperada'])?strtotime($jornada['fecha'].' '.$horario['salida_esperada']):null;
    foreach (asistencia_tramos_trabajados($marcas) as [$a,$b]) {
        $total+=$b-$a;
        if (!$sabado && $entrada!==null && $salida!==null) {
            $x=max($a,$entrada); $y=min($b,$salida); $seg=max(0,$y-$x);
            foreach ($plan as $tramo) {
                $ini=strtotime($tramo['fecha'].' '.$tramo['hora_inicio']); $fin=strtotime($tramo['fecha'].' '.$tramo['hora_fin']);
                $seg-=max(0,min($y,$fin)-max($x,$ini));
            }
            $base+=max(0,$seg);
        }
    }
    $extra=round(max(0,$total-$base-$reservados)/3600,2);
    $resumen['horas_calculadas']=round($base/3600+$credito/60,2);
    $resumen['horas_extra_sugeridas']=($sabado || $extra>0.25)?$extra:0.0;
    $resumen['flag_horas_extra']=$resumen['horas_extra_sugeridas']>0?1:0;
    // Evité exigir marcas de descanso cuando el descanso entero estaba autorizado como trabajado.
    $colInicio=$horario['colacion_inicio']??null; $colFin=$horario['colacion_fin']??null;
    foreach ($plan as $tramo) {
        if ($tramo['tipo']==='COLACION' && $colInicio!==null && $colFin!==null
            && substr($tramo['hora_inicio'],0,5)<=substr($colInicio,0,5) && substr($tramo['hora_fin'],0,5)>=substr($colFin,0,5)) {
            $resumen['observaciones']=array_values(array_filter($resumen['observaciones'],static fn(string $o): bool=>!str_contains($o,'sin colación registrada')));
        }
    }
    // Quité la observación de extras cuando ese tiempo pertenece al plan de recuperación autorizado.
    if (!$resumen['flag_horas_extra']) {
        $resumen['observaciones']=array_values(array_filter($resumen['observaciones'],static fn(string $o): bool=>!str_contains($o,'Horas fuera de horario')));
    }
    if (($sabado && $extra>0) || $hayPendientes) {
        $resumen['observaciones'][]='El tiempo de recuperación o de sábado requiere validación del supervisor.';
    }
    if ($resumen['estado_jornada']==='OBSERVADA' && $resumen['observaciones']===[]) { $resumen['estado_jornada']='VALIDA'; }
    if ($resumen['observaciones']!==[] && $resumen['estado_jornada']==='VALIDA') { $resumen['estado_jornada']='OBSERVADA'; }
    return $resumen;
}

// Agregué firma de marcas para que una corrección posterior invalide la validación anterior.
function recuperaciones_firma_marcas(array $marcas): string
{
    return hash('sha256',json_encode(array_map(static fn(array $m): array=>[(int)($m['id_marca']??0),$m['codigo'],$m['fecha_hora']],$marcas),JSON_THROW_ON_ERROR));
}

// Reabrí créditos si una corrección posterior demuestra trabajo dentro de la ausencia que los originó.
function recuperaciones_reabrir_por_ausencia(array $jornada, array $marcas): array
{
    $stmt=db()->prepare('SELECT * FROM permisos_practicante WHERE id_practica=? AND fecha_ausencia=? AND estado="APROBADO"');
    $stmt->execute([(int)$jornada['id_practica'],$jornada['fecha']]);$afectadas=[];
    foreach($stmt->fetchAll()as$permiso){
        if(!asistencia_permiso_cruza_marcas($permiso,$marcas))continue;
        $rec=db()->prepare('SELECT * FROM recuperaciones_permiso WHERE id_permiso=? AND estado="VALIDADA" AND minutos_validados>0 FOR UPDATE');
        $rec->execute([(int)$permiso['id_permiso']]);
        foreach($rec->fetchAll()as$fila){
            $upd=db()->prepare('UPDATE recuperaciones_permiso SET estado="PROGRAMADA",minutos_validados=0,firma_marcas=NULL WHERE id_recuperacion=?');
            $upd->execute([(int)$fila['id_recuperacion']]);
            if(!auditoria_registrar('REABRIR_RECUPERACION',['tabla_afectada'=>'recuperaciones_permiso','id_registro_afectado'=>(int)$fila['id_recuperacion'],'valores_anteriores'=>$fila,'valores_nuevos'=>['estado'=>'PROGRAMADA','minutos_validados'=>0,'causa'=>'Cruce en ausencia de origen','id_jornada_origen'=>(int)$jornada['id_jornada']]])){
                throw new RuntimeException('No se pudo auditar el cruce en la ausencia.');
            }
            $buscar=db()->prepare('SELECT id_jornada FROM jornadas_asistencia WHERE id_practica=? AND fecha=?');
            $buscar->execute([(int)$jornada['id_practica'],$fila['fecha']]);$id=$buscar->fetchColumn();
            if($id!==false&&(int)$id!==(int)$jornada['id_jornada'])$afectadas[]=(int)$id;
        }
    }
    return array_values(array_unique($afectadas));
}

// Agregué minutos efectivamente trabajados en un tramo; pausas y tiempos fuera del tramo no se recuperan.
function recuperaciones_minutos_efectivos(array $tramo, array $marcas): int
{
    if (asistencia_marca_por_codigo($marcas,'ENTRADA')===null || asistencia_marca_por_codigo($marcas,'SALIDA')===null) { return 0; }
    $a=strtotime($tramo['fecha'].' '.$tramo['hora_inicio']); $b=strtotime($tramo['fecha'].' '.$tramo['hora_fin']); $segundos=0;
    foreach (asistencia_tramos_trabajados($marcas) as [$x,$y]) { $segundos+=max(0,min($b,$y)-max($a,$x)); }
    return min((int)$tramo['minutos_planificados'],(int)floor($segundos/60));
}

// Agregué validación manual por supervisor, con motivo, control de propiedad y bloqueo del saldo.
function recuperaciones_validar(int $idRecuperacion, string $motivo): array
{
    // Cargué validación de jornadas al ejecutar esta acción; la pantalla de permisos no la importaba.
    require_once __DIR__.'/AsistenciaService.php';
    if (!auth_check() || !in_array(auth_rol_activo(),['ADMIN','SUPERVISOR'],true) || trim($motivo)==='' || mb_strlen($motivo)>500) {
        return ['ok'=>false,'errores'=>['La validación requiere supervisor autorizado y motivo de hasta 500 caracteres.']];
    }
    // Integré la validación en la transacción de aprobación tardía para evitar créditos parciales.
    $transaccionPropia = !db()->inTransaction();
    try {
        if ($transaccionPropia) { db()->beginTransaction(); }
        $stmt=db()->prepare('SELECT pp.* FROM permisos_practicante pp INNER JOIN recuperaciones_permiso rp ON rp.id_permiso=pp.id_permiso WHERE rp.id_recuperacion=? FOR UPDATE');
        $stmt->execute([$idRecuperacion]); $permiso=$stmt->fetch();
        if (!is_array($permiso) || $permiso['estado']!=='APROBADO' || !auth_puede_supervisar_practica((int)$permiso['id_practica'])) { throw new RuntimeException('No puedes validar esa recuperación.'); }
        $lock=db()->prepare('SELECT id_practica FROM practicas WHERE id_practica=? FOR UPDATE'); $lock->execute([(int)$permiso['id_practica']]);
        $stmt=db()->prepare('SELECT * FROM recuperaciones_permiso WHERE id_permiso=? FOR UPDATE'); $stmt->execute([(int)$permiso['id_permiso']]); $plan=$stmt->fetchAll();
        $tramo=null; $usados=0;
        foreach ($plan as $fila) { if ((int)$fila['id_recuperacion']===$idRecuperacion) { $tramo=$fila; } else { $usados+=(int)$fila['minutos_validados']; } }
        if ($tramo===null || $tramo['estado']!=='PROGRAMADA' || strtotime($tramo['fecha'].' '.$tramo['hora_fin'])>time()) { throw new RuntimeException('El tramo debe estar autorizado, terminado y pendiente de validación.'); }
        $feriadoNoAutorizado=(recuperaciones_es_feriado($tramo['fecha']) || $tramo['tipo']==='FERIADO') && !(int)$tramo['autorizado_feriado'];
        $stmt=db()->prepare('SELECT * FROM jornadas_asistencia WHERE id_practica=? AND fecha=? FOR UPDATE'); $stmt->execute([(int)$permiso['id_practica'],$tramo['fecha']]); $jornada=$stmt->fetch();
        if (is_array($jornada) && $jornada['estado_jornada']==='ANULADA') { throw new RuntimeException('La jornada está anulada; resuelve su estado antes de validar.'); }
        $marcas=is_array($jornada)?asistencia_marcas_vigentes_jornada((int)$jornada['id_jornada'],true):[];
        // Cerrar con cero minutos libera saldo, pero una secuencia incompleta necesita corrección previa.
        if ($marcas!==[]) {
            $revision=asistencia_gestion_validar_revision(array_merge($jornada,['estado_jornada'=>'OBSERVADA']),$marcas);
            if (!$revision['ok']) { throw new RuntimeException($revision['mensaje']); }
        }
        $minutos=min(recuperaciones_minutos_efectivos($tramo,$marcas),max(0,(int)round((float)$permiso['horas_ausencia']*60)-$usados));
        // Permití validar trabajo terminado antes de la ausencia; conservé el bloqueo por cruce
        // y la reapertura existente si posteriormente se trabaja durante la ausencia autorizada.
        if($minutos>0){
            $fuente=db()->prepare('SELECT id_jornada FROM jornadas_asistencia WHERE id_practica=? AND fecha=? AND estado_jornada<>"ANULADA" FOR UPDATE');
            $fuente->execute([(int)$permiso['id_practica'],$permiso['fecha_ausencia']]);$idFuente=$fuente->fetchColumn();
            $marcasFuente=$idFuente!==false?asistencia_marcas_vigentes_jornada((int)$idFuente,true):[];
            if(asistencia_permiso_cruza_marcas($permiso,$marcasFuente))throw new RuntimeException('La ausencia de origen cruza tiempo trabajado. Resuelve ese cruce antes de validar recuperación.');
        }
        // Revalidé condiciones al reconocer horas; un cambio de horario/calendario libera saldo para reprogramar.
        $planActual=recuperaciones_validar_plan((int)$permiso['id_practica'],[$tramo],(int)$tramo['minutos_planificados'],$permiso,true);
        if ($feriadoNoAutorizado) { $planActual['errores'][]='El feriado requiere autorización expresa.'; }
        if ($minutos>0 && $planActual['errores']!==[]) {
            $nota='Requiere reprogramación: '.implode(' ',$planActual['errores']).' '.$motivo;
            $stmt=db()->prepare('UPDATE recuperaciones_permiso SET estado="REPROGRAMAR",minutos_validados=0,validado_por=?,motivo_validacion=?,fecha_validacion=NOW() WHERE id_recuperacion=?');
            $stmt->execute([auth_usuario_actual_id(),mb_substr($nota,0,500),$idRecuperacion]);
            if(is_array($jornada))asistencia_recalcular_jornada((int)$jornada['id_jornada'],null,true);
            if(!auditoria_registrar('REPROGRAMAR_RECUPERACION',['tabla_afectada'=>'recuperaciones_permiso','id_registro_afectado'=>$idRecuperacion,'valores_anteriores'=>$tramo,'valores_nuevos'=>['estado'=>'REPROGRAMAR','minutos_validados'=>0,'motivo'=>$nota]])){
                throw new RuntimeException('No se pudo auditar la reprogramación.');
            }
            if ($transaccionPropia) { db()->commit(); }
            return ['ok'=>true,'errores'=>[],'mensaje'=>'El horario o calendario cambió: no se reconocieron minutos. El tramo requiere reprogramación; el practicante puede solicitar su saldo en otra fecha.'];
        }
        $stmt=db()->prepare('UPDATE recuperaciones_permiso SET minutos_validados=?,estado="VALIDADA",validado_por=?,motivo_validacion=?,firma_marcas=?,fecha_validacion=NOW() WHERE id_recuperacion=?');
        $stmt->execute([$minutos,auth_usuario_actual_id(),trim($motivo),recuperaciones_firma_marcas($marcas),$idRecuperacion]);
        if (is_array($jornada)) { asistencia_recalcular_jornada((int)$jornada['id_jornada'],null,true); }
        if (!auditoria_registrar('VALIDAR_RECUPERACION',['tabla_afectada'=>'recuperaciones_permiso','id_registro_afectado'=>$idRecuperacion,'valores_anteriores'=>$tramo,'valores_nuevos'=>['minutos_validados'=>$minutos,'motivo'=>trim($motivo)]])) { throw new RuntimeException('No se pudo auditar la validación.'); }
        if ($transaccionPropia) { db()->commit(); }
        return ['ok'=>true,'errores'=>[]];
    } catch (Throwable $e) {
        if ($transaccionPropia && db()->inTransaction()) { db()->rollBack(); }
        error_log('Validación de recuperación: '.$e->getMessage());
        return ['ok'=>false,'errores'=>[$e instanceof RuntimeException?$e->getMessage():'No se pudo validar la recuperación.']];
    }
}
