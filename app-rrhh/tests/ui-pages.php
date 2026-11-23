<?php

declare(strict_types=1);

// Agregué fixtures para revisar las vistas existentes con Tailwind compilado.
// Se ejecutan controladores reales en GET, sustituyendo solo sus lecturas de BBDD.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root=dirname(__DIR__);
$pages=[
    'login'=>'Auth/Login','recuperar'=>'Auth/RecuperarPassword','restablecer'=>'Auth/RestablecerPassword',
    'activar'=>'Auth/ActivarCuenta','roles'=>'Auth/SeleccionarRol',
    'perfil'=>'Practicante/MisDatos','marcar'=>'Practicante/MarcarAsistencia',
    'historial'=>'Practicante/MiHistorial','permisos'=>'Practicante/Permisos',
    'panel'=>'Practicante/Dashboard','acom'=>'Supervisor/Acom','404'=>'error',
];
$name=$argv[1]??'';
if (!isset($pages[$name])) { exit(1); }
// Agregué registros y textos largos para probar desbordes que las listas vacías no detectaban.
$populated = ($argv[2] ?? '') === '--populated';
$sessionDir=$root.'/scratch/phase6/sessions';
if (!is_dir($sessionDir)) { mkdir($sessionDir,0700,true); }
ini_set('session.save_path',$sessionDir);
ini_set('error_log',$sessionDir.'/expected-errors.log');
session_id('ui-'.bin2hex(random_bytes(8)));session_start();
$_SESSION=['id_usuario'=>501,'id_rol_activo'=>3,'rol_activo'=>'PRACTICANTE','id_practicante'=>7,'email'=>'fixture@example.invalid'];
if ($populated) { $_SESSION['email']=str_repeat('cuenta',10).'@'.str_repeat('institucion',8).'.example.test'; }
if (str_starts_with($pages[$name],'Auth/')) { $_SESSION=[]; }
if ($name==='roles') {
    $_SESSION=['usuario_pendiente_rol'=>501,'roles_disponibles'=>[['id_rol'=>2,'nombre'=>'SUPERVISOR'],['id_rol'=>3,'nombre'=>'PRACTICANTE']]];
}
if ($name==='acom') { $_SESSION['rol_activo']='SUPERVISOR'; }
$_SERVER['SCRIPT_NAME']='/asistencia/index.php';$_SERVER['REQUEST_URI']='/asistencia/fixture';$_SERVER['REQUEST_METHOD']='GET';
$warnings=[];
set_error_handler(static function(int $severity,string $message) use (&$warnings): bool { $warnings[]=$message;return true; });
function db(): PDO { throw new RuntimeException('BBDD real prohibida en fixture visual'); }
function colacion_listar(?int $idPractica=null): array {return [];}
function uiPractice(): array {
    return ['id_practica'=>22,'id_practicante'=>7,'horas_requeridas'=>360,'horas_previas'=>5,
        'fecha_inicio_practica'=>'2026-01-01','fecha_termino_practica'=>null,'tipo_practica_nombre'=>'Profesional',
        'estado_practica_nombre'=>'EN_PROGRESO','nombre_1'=>$GLOBALS['populated'] ? str_repeat('Nombre',12) : 'Ficticio','apellido_paterno'=>'Dos'];
}
function uiCatalogues(): array { return array_fill_keys(['regiones','comunas','instituciones','sedes','carreras','tipos_practica','nacionalidades'],[]); }
function mis_datos_catalogos(): array { return uiCatalogues(); }
function mis_datos_obtener_practicante(int $id): ?array {
    return array_merge(practicante_registro_valores_iniciales(),uiPractice(),['id_practicante'=>7,'nombre_2'=>'',
        'apellido_materno'=>'','email'=>'fixture@example.invalid','estado_cuenta'=>'ACTIVA']);
}
function mis_datos_ficha_completa(array $data): bool { return false; }
function mis_datos_documentos_obtener(int $id): array {
    return ['foto'=>null,'documentos'=>$GLOBALS['populated'] ? [['id_documento'=>1,'nombre_original'=>str_repeat('Certificado',15).'.pdf','tamano_bytes'=>1024,'created_at'=>'2026-10-14 10:00:00']] : []];
}
function horarios_practicante_obtener_vigente(int $id): array { return horarios_practicante_valores_iniciales(); }
function marcar_asistencia_tipos_marca(): array {
    $result=[];
    foreach (['ENTRADA'=>'Entrada','ENTRADA_COLACION'=>'Inicio colación','SALIDA_COLACION'=>'Fin colación','SALIDA'=>'Salida'] as $code=>$label) { $result[$code]=['nombre'=>$label]; }
    return $result;
}
function marcar_asistencia_practica_activa(int $id): ?array { return uiPractice(); }
function marcar_asistencia_jornada_hoy(int $id,string $fecha): ?array { return null; }
// Agregué sustitución de la lectura nueva; las fixtures visuales nunca consultan MariaDB.
function asistencia_permiso_del_dia(int $id,string $fecha): ?array { return null; }
// Sustituí lecturas nuevas en fixtures visuales; no se conectan a datos habituales.
function recuperaciones_es_feriado(string $fecha): bool { return false; }
function recuperaciones_del_dia(int $id,string $fecha): array { return []; }
// Sustituí las lecturas de la fase 6 para conservar el aislamiento de las pruebas visuales.
function tiempo_politica(): ?array { return null; }
// La vista de supervisor usa el mismo aviso preparado por el controlador.
function tiempo_contar_pendientes(): int { return 2; }
function tiempo_listar(?int $idPractica=null): array {
    if(!$GLOBALS['populated']){return [];}
    return [['id_tiempo'=>71,'id_practica'=>22,'fecha_trabajo'=>'2026-10-14','hora_inicio'=>'18:00:00','hora_fin'=>'19:00:00',
        'estado'=>'APROBADO','vencido'=>false,'minutos_solicitados'=>60,'minutos_aprobados'=>60,'saldo'=>30,
        'vence_utc'=>gmdate('Y-m-d H:i:s',time()+7*86400),'motivo_solicitud'=>str_repeat('ReunionSinEspacios',15),'motivo_revision'=>'Autorizado por jefe',
        'compensaciones'=>[['id_compensacion'=>72,'fecha'=>'2026-10-16','tipo'=>'ENTRADA','hora_inicio'=>'09:00:00','hora_fin'=>'09:30:00',
            'estado'=>'APROBADA','minutos'=>30,'minutos_aplicados'=>0,'motivo_solicitud'=>'Ingreso posterior','motivo_revision'=>'Aprobado']]]];
}
function tiempo_jornadas_solicitables(int $idPractica): array { return [['id_jornada'=>31,'fecha'=>'2026-10-14']]; }
function asistencia_horario_vigente_dia(int $id,string $fecha): ?array {
    return ['entrada_esperada'=>'09:00','salida_esperada'=>'18:00','colacion_inicio'=>'13:00','colacion_fin'=>'14:00','horas_planificadas'=>8];
}
function marcar_asistencia_resumen_practica(int $id,int $horas,float $previas=0): array {
    return ['horas_realizadas'=>14,'horas_requeridas'=>360,'horas_restantes'=>346,'porcentaje_avance'=>3.9,'jornadas_total'=>1,'jornadas_sumables'=>1,'asistencia_mes'=>100];
}
// Estos datos son sintéticos; los controladores y las vistas siguen siendo los de la aplicación.
function uiDay(): array {
    return ['id_jornada'=>31,'fecha'=>'2026-10-13','estado_jornada'=>'PENDIENTE_REVISION',
        'hora_entrada'=>'2026-10-13 10:00:00','hora_salida'=>'2026-10-13 18:00:00',
        'horas_calculadas'=>7,'horas_extra_aprobadas'=>0.5,'observacion'=>str_repeat('ObservacionSinEspacios',10)];
}
function marcar_asistencia_historial_reciente(int $id): array { return $GLOBALS['populated'] ? [uiDay()] : []; }
function historial_practicas(int $id): array { return [uiPractice()]; }
function historial_jornadas(int $id,string $desde,string $hasta): array { return $GLOBALS['populated'] ? [uiDay()] : []; }
function historial_marcas_por_jornada(array $jornadas): array {
    return $GLOBALS['populated'] ? [31=>[['tipo_marca'=>'Entrada','fecha_hora'=>'2026-10-13 10:00:00'],['tipo_marca'=>'Salida','fecha_hora'=>'2026-10-13 18:00:00']]] : [];
}
function historial_resumen(int $id,int $horas,float $previas=0): array {
    return ['total_jornadas'=>0,'horas_realizadas'=>5,'horas_restantes'=>355,'porcentaje_avance'=>1.4,'jornadas_validas'=>0,'jornadas_observadas'=>0,'jornadas_incompletas'=>0];
}
function permisos_gestion_practica_activa(int $id): ?array { return uiPractice(); }
function permisos_gestion_listar_por_practica(int $id): array {
    if (!$GLOBALS['populated']) { return []; }
    $base=['id_permiso'=>1,'fecha_ausencia'=>'2026-10-13','fecha_recuperacion'=>'2026-10-17',
        'hora_inicio_recuperacion'=>'14:00:00','hora_fin_recuperacion'=>'19:00:00','horas_a_recuperar'=>5,
        'motivo'=>str_repeat('MotivoDePruebaSinEspacios',8),'created_at'=>'2026-10-12 10:00:00',
        'tipo_ausencia'=>'PARCIAL','hora_inicio_ausencia'=>'10:00:00','hora_fin_ausencia'=>'15:00:00',
        'nuevo_horario_inicio'=>'15:00:00','nuevo_horario_fin'=>'18:00:00','horas_trabajo_dia'=>3,
        'respuesta_supervisor'=>str_repeat('RespuestaDePruebaSinEspacios',7),'fecha_revision'=>'2026-10-12 11:00:00',
        // Incluí observaciones largas para comprobar que la nueva explicación no desborde.
        'requiere_revision'=>1,'observacion_solicitud'=>str_repeat('ObservacionDelPermiso',15)];
    return array_map(static fn(string $estado): array => array_merge($base,['estado'=>$estado]), ['PENDIENTE','APROBADO','RECHAZADO']);
}
function acom_gestion_listar_practicantes(?string $busqueda=null): array { return []; }
function permisos_gestion_contar_pendientes(): int { return 3; }

ob_start();
try {
    // El modelo aporta valores iniciales puros; sus consultas no se ejecutan.
    require_once $root.'/src/Models/PracticanteModel.php';
    if ($name==='404') { require VIEWS_PATH.'/errors/404.view.php'; }
    else { require $root.'/src/Controllers/'.$pages[$name].'Controller.php'; }
} catch (Throwable $exception) { $warnings[]='Excepción: '.$exception->getMessage(); }
$html=ob_get_clean();restore_error_handler();$_SESSION=[];session_destroy();
echo json_encode(['html'=>$html,'warnings'=>$warnings],JSON_THROW_ON_ERROR);
