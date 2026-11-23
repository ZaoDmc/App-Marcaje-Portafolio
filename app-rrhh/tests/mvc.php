<?php

declare(strict_types=1);

// Agregué comprobaciones de separación y renderizado en procesos aislados.
// Los proveedores de lectura se sustituyen por datos ficticios: no hay MySQL,
// correo, importaciones ni escrituras de negocio en estas pruebas.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root = dirname(__DIR__);
$modules = ['Asistencia'=>'asistencia','Permisos'=>'permisos','Reportes'=>'reportes',
    'CrearPracticante'=>'crear-practicante','PracticantesCargaMasiva'=>'practicantes-carga-masiva',
    'Practicantes'=>'practicantes','Catalogos'=>'catalogos','Notas'=>'notas'];

if (($argv[1] ?? '') === '--case') {
    [$role,$module,$method,$mode] = array_slice($argv,2);
    if (!isset($modules[$module]) || !in_array($role,['Admin','Supervisor'],true)) { exit(1); }
    $sessionDir = $root . '/scratch/mvc-sessions';
    if (!is_dir($sessionDir)) { mkdir($sessionDir,0700,true); }
    ini_set('session.save_path',$sessionDir);
    ini_set('error_log',$sessionDir . '/expected-errors.log');
    session_id('mvc-' . bin2hex(random_bytes(8)));
    session_start();
    $_SESSION = ['id_usuario'=>501,'id_rol_activo'=>2,'rol_activo'=>strtoupper($role),'email'=>'fixture@example.invalid'];
    if ($mode === 'anonymous') { $_SESSION = []; }
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['SCRIPT_NAME'] = '/asistencia/index.php';
    $_SERVER['REQUEST_URI'] = '/asistencia/' . strtolower($role) . '/' . $modules[$module] . '.php';
    $_GET = $module === 'Asistencia' ? ['id_jornada'=>'101'] : [];
    // Ejercité las categorías nuevas mediante el controlador de cada superior, incluido POST inválido.
    if($module==='Permisos' && in_array($mode,['tiempo','compensacion','colacion'],true)){$_GET['categoria']=$mode;}
    // Agregué selección real de ficha y registros sintéticos para probar la gestión de supervisores.
    if ($module === 'Practicantes' && $mode === 'management') { $_GET['id_practicante']='2'; }
    if ($module === 'Catalogos') { $_GET['catalogo'] = 'sedes'; }
    // Agregué detalles resueltos para verificar contadores e historial de ambos cierres.
    if ($module === 'Reportes' && in_array($mode, ['detail','reviewed','corrected'], true)) { $_GET['id_practica'] = '22'; }
    $_POST = $method === 'POST' ? ['csrf_token'=>'invalid','accion'=>'guardar'] : [];
    if($method==='POST' && $module==='Permisos' && $mode==='colacion'){$_POST=['csrf_token'=>'invalid','accion'=>'revisar_colacion','id_solicitud'=>'910','decision_colacion'=>'APROBAR','motivo_colacion'=>'Fixture'];}
    // En Notas envié IDs y acción válidos para comprobar que solo el CSRF inválido basta para impedir la escritura.
    if ($module === 'Notas' && $method === 'POST') {
        $_POST = ['csrf_token'=>'invalid','accion'=>'designar','id_area'=>'1','id_practica'=>'22','id_liderazgo_vigente'=>'9','motivo'=>'Prueba CSRF'];
    }
    if ($method === 'POST' && $module === 'Catalogos') { $_POST['catalogo'] = 'sedes'; }
    $lateReads = 0;
    $reads = 0;
    $writeCalls = 0;
    $warnings = [];
    set_error_handler(static function(int $severity,string $message) use (&$warnings): bool {
        $warnings[] = $message;
        return true;
    });
    function fixtureRead(): void {
        $GLOBALS['reads']++;
        if (ob_get_length() > 0) { $GLOBALS['lateReads']++; }
    }
    function db(): PDO { fixtureRead(); throw new RuntimeException('BBDD ficticia no disponible'); }
    // Sustituí lecturas de tiempo excepcional; MVC sigue sin conexión a bases reales.
    function tiempo_politica(): ?array { fixtureRead(); return null; }
    function tiempo_listar(?int $idPractica=null): array {
        fixtureRead();
        if(!in_array($GLOBALS['mode'],['tiempo','compensacion'],true)){return [];}
        return [['id_tiempo'=>900,'id_practica'=>22,'fecha_trabajo'=>'2026-11-05','nombre_1'=>'Persona ficticia','apellido_paterno'=>'Detalle <script>',
            'email'=>'fixture@example.test','hora_inicio'=>'17:00','hora_fin'=>'18:00','estado'=>'PENDIENTE','politica_canje'=>2,
            'minutos_solicitados'=>60,'minutos_aprobados'=>0,'saldo'=>0,'vencido'=>false,'vence_utc'=>'2026-11-13 20:00:00',
            'motivo_solicitud'=>'Reunión ficticia','revisado_por'=>null,
            'compensaciones'=>[['id_compensacion'=>901,'fecha'=>'2026-11-06','tipo'=>'SALIDA','hora_inicio'=>'16:00','hora_fin'=>'17:00',
                'estado'=>'APROBADA','minutos'=>60,'minutos_aplicados'=>0,'motivo_solicitud'=>'Canje ficticio']]]];
    }
    // Preparé también el aviso antes del HTML, sin base real en las pruebas MVC.
    function tiempo_contar_pendientes(): int { fixtureRead(); return 2; }
    function colacion_listar(?int $idPractica=null): array {
        fixtureRead();return $GLOBALS['mode']!=='colacion'?[]:[['id_solicitud'=>910,'id_practica'=>22,'fecha'=>'2026-11-06','nombre_1'=>'Persona ficticia','apellido_paterno'=>'Colación <script>','email'=>'fixture@example.test','inicio_anterior'=>'13:00','inicio_solicitado'=>'14:00','estado'=>'PENDIENTE','motivo'=>'Cambio ficticio <script>']];
    }
    final class MvcRedirect extends RuntimeException {}
    function redirect_to(string $path): never { throw new MvcRedirect($path); }
    function permisos_gestion_contar_pendientes(): int { fixtureRead(); return 3; }
    function permisos_gestion_listar_todos(array $filtros,string $rol,int $limite=20,int $pagina=1): array {
        fixtureRead();
        $permiso = ['id_permiso'=>31,'estado'=>'PENDIENTE','nombre_1'=>'Ficticio','apellido_paterno'=>'Dos',
            'practicante_email'=>'fixture@example.invalid','fecha_ausencia'=>'2026-11-02',
            'fecha_recuperacion'=>'2026-11-07','hora_inicio_recuperacion'=>'10:00','hora_fin_recuperacion'=>'13:00',
            'horas_a_recuperar'=>3,'motivo'=>'Consulta ficticia','created_at'=>'2026-10-13 12:00:00'];
        return ['items'=>[$permiso],'total'=>1,'paginas'=>1,'pendientes'=>1,'aprobados'=>0,'rechazados'=>0];
    }
    function practicante_registro_catalogos(): array {
        fixtureRead();
        if ($GLOBALS['mode'] === 'failure') { throw new RuntimeException('Fallo ficticio de lectura'); }
        return array_fill_keys(['regiones','comunas','instituciones','sedes','carreras','tipos_practica','nacionalidades'],[]);
    }
    function catalogos_admin_listar(string $clave): array { fixtureRead(); return []; }
    function catalogos_admin_opciones_fk(string $clave): array { fixtureRead(); return [1=>'Institución ficticia']; }
    function areas_practicante_catalogo(): array { fixtureRead(); return []; }
    function fixtureManagedPractice(): array {
        return array_merge(fixtureDay(), ['id_practicante'=>2,'id_practica'=>22,'id_horario'=>41,
            'nombre_1'=>str_repeat('Nombre',10),'email'=>str_repeat('correo',8).'@institucion.example.test',
            'tipo_practica'=>'Profesional','carrera'=>str_repeat('Carrera ',10),'area_vigente'=>'Desarrollo',
            'estado_cuenta'=>'ACTIVA','horas_requeridas'=>360,'fecha_inicio_practica'=>'2026-10-01']);
    }
    function horarios_practicante_listar_practicantes_gestion(string $busqueda): array { fixtureRead(); return $GLOBALS['mode']==='management' ? [fixtureManagedPractice()] : []; }
    function horarios_practicante_obtener_practicante_gestion(int $id): ?array { fixtureRead(); return fixtureManagedPractice(); }
    function horarios_practicante_obtener_vigente(int $id): array {
        fixtureRead(); $horario=horarios_practicante_valores_iniciales();
        // Cargué horas reales de ejemplo para revisar que el editor compacto conserve valores legibles.
        if ($GLOBALS['mode']==='management') foreach ([1,2,3,4,5] as $dia) {
            $horario[$dia]=['activo'=>'1','entrada_esperada'=>'10:00','salida_esperada'=>'18:00','colacion_inicio'=>'13:00','colacion_fin'=>'14:00'];
        }
        return $horario;
    }
    function horarios_practicante_resumen_vigente(int $id): ?array { fixtureRead(); return ['id_horario'=>41,'fecha_inicio'=>'2026-10-01']; }
    function areas_practicante_historial(int $id): array { fixtureRead(); return [['area_nombre'=>str_repeat('Area ',15),'fecha_inicio'=>'2026-10-01','fecha_fin'=>null,'asignado_por_email'=>'supervisor@example.test']]; }
    function excepciones_horario_listar(int $id): array { fixtureRead(); return [['id_excepcion'=>1,'activo'=>1,'fecha'=>'2026-10-17','tipo_excepcion'=>'ASISTENCIA_EXTRAORDINARIA','entrada_esperada'=>'10:00','salida_esperada'=>'20:00','observacion'=>str_repeat('ObservacionSinEspacios',10)]]; }
    function auth_es_conflicto_supervisor_practicante(int $id): bool { return false; }
    // Preparé líderes ficticios con texto adverso; las pruebas MVC no abren una conexión real.
    function liderazgos_disponible(): bool { fixtureRead(); return $GLOBALS['mode'] !== 'missing-migration'; }
    function liderazgos_areas(): array { fixtureRead(); return [['id_area'=>1,'nombre'=>'Área <script>','activo'=>1,'id_liderazgo'=>9,'id_practica'=>22,'nombre_1'=>'Ficticio','apellido_paterno'=>'Dos','integrantes'=>2]]; }
    function liderazgos_integrantes(int $id): array { fixtureRead(); return [['id_practica'=>22,'nombre_1'=>'Ficticio','apellido_paterno'=>'Dos','email'=>'fixture@example.invalid']]; }
    function liderazgos_historial(int $id): array { fixtureRead(); return [['id_practica'=>22,'nombre_1'=>'Ficticio','apellido_paterno'=>'Dos','fecha_inicio'=>'2026-10-01 10:00:00','fecha_fin'=>null,'asignado_email'=>'admin@example.invalid','finalizado_email'=>null,'motivo_designacion'=>'Motivo <script>','motivo_fin'=>null]]; }
    function liderazgos_guardar(...$args): array { $GLOBALS['writeCalls']++; throw new RuntimeException('Escritura inesperada'); }
    function reportes_gestion_estados_practica(): array { fixtureRead(); return []; }
    function reportes_gestion_practicas(array $filtros,string $rol): array { fixtureRead(); return []; }
    function reportes_gestion_practica_detalle(int $id): ?array {
        fixtureRead(); return array_merge(fixtureDay(),['carrera'=>'Ficticia','institucion'=>'Ficticia','nombre_sede'=>'Ficticia',
            'horas_requeridas'=>360,'horas_totales_acumuladas'=>14,'horas_previas'=>5]);
    }
    function reportes_gestion_jornadas_practicante(int $id,string $desde,string $hasta): array { fixtureRead(); return [fixtureDay()]; }
    // Agregué saldo ficticio para comprobar el informe sin lecturas después de emitir HTML.
    function reportes_gestion_tiempos(int $id,string $desde,string $hasta): array { fixtureRead(); return ['recuperados'=>60,'compensados'=>30,'excepcionales'=>90,'saldo'=>30,'pendientes'=>1]; }
    function asistencia_gestion_jornadas_conteo(array $filtros): int { fixtureRead(); return 1; }
    function fixtureDay(): array {
        return ['id_jornada'=>101,'id_practica'=>22,'id_practicante'=>2,'fecha'=>'2026-10-13',
            'estado_jornada'=>match ($GLOBALS['mode']) { 'reviewed'=>'REVISADA', 'corrected'=>'CORREGIDA', default=>'OBSERVADA' },'nombre_1'=>'Ficticio <script>','nombre_2'=>'',
            'apellido_paterno'=>'Dos','apellido_materno'=>'','email'=>'fixture@example.invalid',
            'tipo_practica_nombre'=>'Profesional','estado_practica_nombre'=>'EN_PROGRESO',
            'rut_numero'=>null,'rut_dv'=>null,'horas_calculadas'=>9,'horas_extra_aprobadas'=>null,
            'hora_entrada'=>'2026-10-13 09:00:00','hora_salida'=>'2026-10-13 18:00:00',
            'flag_atraso'=>0,'flag_salida_anticipada'=>0,'flag_horas_extra'=>0,'flag_asistencia_extraordinaria'=>0,
            'marcas_vigentes'=>2,'marcas_anuladas'=>0,'observacion'=>'Sin colación registrada.'];
    }
    function asistencia_gestion_jornadas(array $filtros,int $limite=10): array { fixtureRead(); return [fixtureDay()]; }
    function asistencia_gestion_jornada_detalle(int $id): ?array { fixtureRead(); return fixtureDay(); }
    function asistencia_gestion_marcas_jornada(int $id): array { fixtureRead(); return []; }
    function areas_jornada_bloques(int $id): array { fixtureRead(); return []; }
    function asistencia_horario_vigente_dia(int $id,string $fecha): ?array { fixtureRead(); return null; }
    function auth_puede_supervisar_practica(int $id,?int $actor=null): bool { return true; }
    // Estos reemplazos fallan si un POST con CSRF inválido alcanza una escritura.
    function practicante_registro_guardar(array $data): array { $GLOBALS['writeCalls']++; throw new RuntimeException('Escritura inesperada'); }
    function catalogos_admin_guardar(...$args): array { $GLOBALS['writeCalls']++; throw new RuntimeException('Escritura inesperada'); }
    ob_start();
    $redirect = null;
    try {
        if ($mode === 'components') {
            // Verifiqué los helpers de presentación trasladados desde MisDatos.
            require $root . '/src/bootstrap.php';
            require VIEWS_PATH . '/components/mis-datos-fields.php';
            mis_datos_render_select('campo', [['id'=>1,'label'=>'<script>']], 'id','label',['campo'=>'1'],'Seleccionar',true);
            mis_datos_label('campo','Nombre <script>',true);
        } else {
            require $root . '/src/Controllers/' . $role . '/' . $module . 'Controller.php';
        }
    } catch (MvcRedirect $exception) { $redirect = $exception->getMessage(); }
    catch (Throwable $exception) { $warnings[] = 'Excepción: ' . $exception->getMessage(); }
    $html = ob_get_clean();
    restore_error_handler();
    $_SESSION = []; session_destroy();
    echo json_encode(['html'=>$html,'warnings'=>$warnings,'lateReads'=>$lateReads,'reads'=>$reads,
        'writes'=>$writeCalls,'redirect'=>$redirect,'horasPeriodo'=>$horasPeriodoDet ?? null,
        'observacionesPendientes'=>$jornadasConObs ?? null],JSON_THROW_ON_ERROR);
    exit;
}

$checks = 0;
function mvcCheck(bool $ok,string $message): void {
    if (!$ok) { throw new RuntimeException($message); }
    $GLOBALS['checks']++;
}
function mvcCase(string $role,string $module,string $method,string $mode='normal'): array {
    $pipes = [];
    $process = proc_open([PHP_BINARY,__FILE__,'--case',$role,$module,$method,$mode],
        [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__));
    if (!is_resource($process)) { throw new RuntimeException('No se pudo iniciar PHP de pruebas'); }
    fclose($pipes[0]); $output=stream_get_contents($pipes[1]); fclose($pipes[1]);
    $errors=stream_get_contents($pipes[2]); fclose($pipes[2]); $status=proc_close($process);
    mvcCheck($status===0 && $errors==='',"Proceso $role/$module/$method");
    return json_decode($output,true,512,JSON_THROW_ON_ERROR);
}
try {
    // Verifiqué que los proveedores de negocio ya no contienen HTML intercalado.
    foreach (glob($root . '/src/{Services,Models}/*.php',GLOB_BRACE) as $path) {
        $html = array_filter(token_get_all(file_get_contents($path)),static fn($t): bool => is_array($t) && $t[0]===T_INLINE_HTML && trim($t[1])!=='');
        mvcCheck($html===[],'HTML en capa de negocio: ' . basename($path));
    }
    foreach (['Admin','Supervisor'] as $role) {
        foreach(['GET','POST'] as $metodo){
            $colacion=mvcCase($role,'Permisos',$metodo,'colacion');
            mvcCheck($colacion['warnings']===[] && $colacion['lateReads']===0,'Colación prepara datos antes de la vista');
            mvcCheck(str_contains($colacion['html'],'data-ui-open="colacion-910"') && str_contains($colacion['html'],'Colación &lt;script&gt;'),'Colación abre detalle y escapa textos para ambos superiores');
        }
        foreach ($modules as $module=>$view) {
            if ($role==='Supervisor' && in_array($module,['Catalogos','Notas'],true)) { continue; }
            foreach (['GET','POST'] as $method) {
                $result=mvcCase($role,$module,$method);
                mvcCheck($result['warnings']===[],"Avisos en $role/$module/$method: " . implode('; ',$result['warnings']));
                mvcCheck($result['lateReads']===0,'Lectura después de HTML: ' . $role . '/' . $module);
                mvcCheck($result['writes']===0,'CSRF inválido alcanzó escritura');
                mvcCheck(substr_count($result['html'],'<!doctype html>')===1 && str_contains($result['html'],'</html>'),'Layout completo único: ' . $module);
                if ($module==='Asistencia') {
                    // Comprobé ambos cierres independientes y el escape de datos del practicante.
                    mvcCheck(str_contains($result['html'],'value="REVISADA"') && str_contains($result['html'],'Corregido / Cerrar corrección') && str_contains($result['html'],'Ficticio &lt;script&gt;'),'Detalle y escape de asistencia');
                }
                if ($module==='CrearPracticante') { mvcCheck(str_contains($result['html'],'horario[1][entrada_esperada]'),'Componente de horario en alta'); }
                if ($module==='Catalogos') { mvcCheck(str_contains($result['html'],'Institución ficticia'),'Opciones relacionadas preparadas'); }
                if ($module==='Permisos') { mvcCheck(str_contains($result['html'],'modalAprobar_31') && str_contains($result['html'],'modalRechazar_31'),'Modales de permiso pendiente conservados'); }
                if ($module==='Notas' && $method==='GET') {
                    mvcCheck(str_contains($result['html'],'Área &lt;script&gt;') && str_contains($result['html'],'Motivo &lt;script&gt;'),'Notas escapa nombres y motivos');
                    mvcCheck(str_contains($result['html'],'id_liderazgo_vigente') && str_contains($result['html'],'Reemplazar líder'),'Notas conserva control de concurrencia y formulario');
                }
            }
            $anonymous=mvcCase($role,$module,'GET','anonymous');
            mvcCheck($anonymous['redirect']==='login.php' && $anonymous['html']==='','Autorización antes del HTML: ' . $role . '/' . $module);
        }
    }
    $failed=mvcCase('Admin','CrearPracticante','GET','failure');
    // Verifiqué que falte la migración sin mostrar un formulario operativo ni ejecutar escrituras.
    $missing=mvcCase('Admin','Notas','GET','missing-migration');
    mvcCheck($missing['warnings']===[] && str_contains($missing['html'],'2026-10-19_patch_liderazgos_area_009.sql') && !str_contains($missing['html'],'id_liderazgo_vigente'), 'Notas informa migración pendiente y bloquea gestión');
    mvcCheck($failed['warnings']===[] && str_contains($failed['html'],'No fue posible cargar los catálogos'),'Error de lectura se presenta sin fallar el layout');
    $components=mvcCase('Admin','CrearPracticante','GET','components');
    mvcCheck($components['warnings']===[] && str_contains($components['html'],'selected') && str_contains($components['html'],'required'),'Campos trasladados conservan selección y obligatoriedad');
    mvcCheck(str_contains($components['html'],'&lt;script&gt;') && !str_contains($components['html'],'<script>'),'Campos trasladados escapan texto');
    foreach (['Admin','Supervisor'] as $role) {
        foreach(['tiempo','compensacion'] as $categoria) {
            foreach(['GET','POST'] as $metodo) {
                $bandeja=mvcCase($role,'Permisos',$metodo,$categoria);
                mvcCheck($bandeja['warnings']===[] && $bandeja['lateReads']===0,'Categoría sin lecturas en vista ni avisos: '.$role.'/'.$categoria);
                mvcCheck(str_contains($bandeja['html'],'Categorías de permisos') && str_contains($bandeja['html'],'data-ui-open="permiso-'),'Categoría conserva navegación y detalle emergente');
                mvcCheck(str_contains($bandeja['html'],'</html>'),'Retornar de la vista compartida conserva cierre del layout');
            }
        }
        $detail=mvcCase($role,'Reportes','GET','detail');
        mvcCheck($detail['warnings']===[] && $detail['lateReads']===0,'Detalle de reporte sin avisos ni lecturas tardías');
        mvcCheck((float)$detail['horasPeriodo']===9.0 && str_contains($detail['html'],'Historial de jornadas e incidencias'),'Detalle conserva acumulados e historial');
        // Comprobé desglose sin duplicar los acumulados existentes del reporte.
        mvcCheck(str_contains($detail['html'],'Recuperación y compensación') && str_contains($detail['html'],'No vuelvas a sumarlas.'),'Reporte distingue los libros de tiempo y explica su inclusión en el total');
        foreach (['reviewed','corrected'] as $closedMode) {
            $closed=mvcCase($role,'Reportes','GET',$closedMode);
            mvcCheck($closed['warnings']===[] && $closed['lateReads']===0,'Reporte resuelto sin errores ni lecturas tardías');
            mvcCheck($closed['observacionesPendientes']===0,'Texto histórico no incrementa pendientes de revisión');
            mvcCheck((float)$closed['horasPeriodo']===9.0 && str_contains($closed['html'],'Sin colación registrada.'),'Cierre conserva horas e historial');
        }
    }
    echo "MVC: $checks comprobaciones correctas.\n";
} catch (Throwable $exception) {
    fwrite(STDERR,'FALLO MVC: ' . $exception->getMessage() . "\n"); exit(1);
}
