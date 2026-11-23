<?php

declare(strict_types=1);

// Agregué validación de la retirada del legado y del endpoint trasladado.
// Los casos HTTP se simulan en procesos aislados con datos ficticios; nunca
// conectan a MySQL, envían correos ni leen documentos de practicantes.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root = dirname(__DIR__);
if (($argv[1] ?? '') === '--api-case') {
    $case = $argv[2] ?? '';
    $sessionDir = $root . '/scratch/phase5/sessions';
    if (!is_dir($sessionDir)) { mkdir($sessionDir,0700,true); }
    ini_set('session.save_path',$sessionDir);
    ini_set('error_log',$sessionDir . '/expected-errors.log');
    session_id('legacy-' . bin2hex(random_bytes(8)));
    session_start();
    $_SESSION = ['id_usuario'=>501,'id_rol_activo'=>3,'rol_activo'=>'PRACTICANTE','id_practicante'=>7];
    if ($case === 'no-user') { unset($_SESSION['id_practicante']); }
    if ($case === 'anonymous') { $_SESSION = []; }
    if ($case === 'wrong-role') { $_SESSION['rol_activo'] = 'SUPERVISOR'; }
    $_SERVER['SCRIPT_NAME'] = '/asistencia/public/index.php';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET['fecha'] = match ($case) {
        'malformed'=>'13-10-2026', 'array'=>['2030-01-01'], 'impossible'=>'2099-02-30',
        'null-byte'=>"2099-01-01\0", 'today'=>date('Y-m-d'),
        'past'=>date('Y-m-d',strtotime('-1 day')), default=>date('Y-m-d',strtotime('+7 days')),
    };
    $lookups = []; $warnings = [];
    set_error_handler(static function(int $severity,string $message) use (&$warnings): bool { $warnings[]=$message; return true; });
    // Sustituí únicamente las lecturas en el límite del controlador.
    function db(): PDO { throw new RuntimeException('Conexión real prohibida en prueba'); }
    function permisos_gestion_practica_activa(int $id): ?array {
        $GLOBALS['lookups'][] = ['practicante'=>$id];
        if ($GLOBALS['case'] === 'exception') { throw new RuntimeException('ficticio-confidencial'); }
        return $GLOBALS['case'] === 'no-practice' ? null : ['id_practica'=>22];
    }
    function permisos_gestion_horario_del_dia(int $id,string $fecha): ?array {
        $GLOBALS['lookups'][] = ['practica'=>$id,'fecha'=>$fecha];
        return $GLOBALS['case'] === 'no-shift' ? null : [
            'entrada'=>'09:00','salida'=>'18:00','colacion_inicio'=>'13:00',
            'colacion_fin'=>'14:00','horas_planificadas'=>8.0,
        ];
    }
    ob_start();
    // Capturé también las respuestas terminadas mediante exit, propio de una API.
    register_shutdown_function(static function() use (&$warnings,&$lookups): void {
        $body = ob_get_clean();
        $status = http_response_code() ?: 200;
        restore_error_handler();
        if (session_status() === PHP_SESSION_ACTIVE) { $_SESSION=[]; session_destroy(); }
        echo json_encode(['status'=>$status,'body'=>$body,'warnings'=>$warnings,'lookups'=>$lookups],JSON_THROW_ON_ERROR);
    });
    require $root . '/src/Controllers/Practicante/HorarioDiaController.php';
    exit;
}

$checks = 0;
function legacyCheck(bool $ok,string $name): void {
    if (!$ok) { throw new RuntimeException($name); }
    $GLOBALS['checks']++;
}
function legacyApiCase(string $name): array {
    $pipes=[];
    $process=proc_open([PHP_BINARY,__FILE__,'--api-case',$name],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__));
    if (!is_resource($process)) { throw new RuntimeException('No se pudo iniciar la prueba API'); }
    fclose($pipes[0]);$output=stream_get_contents($pipes[1]);fclose($pipes[1]);
    $errors=stream_get_contents($pipes[2]);fclose($pipes[2]);$status=proc_close($process);
    legacyCheck($status===0 && $errors==='','Proceso API: '.$name);
    return json_decode($output,true,512,JSON_THROW_ON_ERROR);
}
try {
    // Actualicé la ruta del inventario al reorganizar docs; conservé sus comprobaciones.
    $inventory=fopen($root.'/docs/historial/mvc-tailwind/FASE_5_ARCHIVOS_RETIRADOS.csv','r');
    fgetcsv($inventory,0,',','"','');
    while (($row=fgetcsv($inventory,0,',','"',''))!==false) {
        legacyCheck(!is_file($root.'/'.$row[0]),'Puente todavía presente: '.$row[0]);
        if ($row[0]!=='diagnostico_correo.php') { legacyCheck(is_file($root.'/'.$row[1]),'Destino retirado ausente: '.$row[1]); }
    }
    fclose($inventory);
    legacyCheck(glob($root.'/*.php')===[],'Quedan scripts PHP en raíz');
    $routes=require $root.'/config/routes.php';
    foreach ($routes as $route) {
        legacyCheck(str_starts_with($route['controller'],'src/Controllers/'),'Ruta dependiente del legado');
    }
    legacyCheck($routes['/practicante/api/horario-dia.php']['methods']===['GET','HEAD'],'Métodos del endpoint alterados');
    // Busqué dependencias residuales en código ejecutable, sin incluir históricos,
    // vendor, scratch o pruebas que mencionan deliberadamente archivos retirados.
    foreach (['src','config','public'] as $dir) {
        $iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$dir,FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->getExtension()!=='php') { continue; }
            $source=file_get_contents($file->getPathname());
            legacyCheck(!preg_match('~(?:require|include)(?:_once)?[^;\n]*[\x27\x22][^\x27\x22]*includes/~',$source),'Dependencia de includes: '.$file->getFilename());
        }
    }
    foreach (['no-user'=>403,'malformed'=>400,'array'=>400,'impossible'=>400,'null-byte'=>400,
        'today'=>200,'past'=>200,'no-practice'=>200,'no-shift'=>200,'exception'=>500,'normal'=>200] as $name=>$status) {
        $result=legacyApiCase($name);
        legacyCheck($result['warnings']===[],'Avisos PHP en API: '.$name);
        legacyCheck($result['status']===$status,'Estado API: '.$name);
        $body=json_decode($result['body'],true,512,JSON_THROW_ON_ERROR);
        // Incluí hoy en los casos autorizados y conservé el rechazo de fechas pasadas.
        legacyCheck($body['ok']===in_array($name,['normal','today'],true),'Contrato ok API: '.$name);
        if (in_array($name,['normal','today'],true)) {
            legacyCheck(array_keys($body)===['ok','entrada','salida','colacion_inicio','colacion_fin','horas_planificadas'],'Campos JSON cambiados');
            legacyCheck($body['entrada']==='09:00' && (float)$body['horas_planificadas']===8.0,'Horario JSON cambiado');
            legacyCheck($result['lookups'][0]===['practicante'=>7] && $result['lookups'][1]['practica']===22,'Consulta de la práctica del usuario autenticado');
        } elseif (in_array($name,['no-user','malformed','array','impossible','null-byte','past'],true)) {
            legacyCheck($result['lookups']===[],'Entrada inválida alcanzó consulta de negocio');
        }
        legacyCheck(!str_contains($result['body'],'ficticio-confidencial'),'Se expuso un error interno');
    }
    foreach (['anonymous'=>302,'wrong-role'=>403] as $name=>$status) {
        $result=legacyApiCase($name);
        legacyCheck($result['status']===$status && $result['lookups']===[] && $result['warnings']===[],'Autorización API: '.$name);
    }
    echo "Retiro de legado/API: $checks comprobaciones correctas.\n";
} catch (Throwable $exception) {
    fwrite(STDERR,'FALLO: '.$exception->getMessage()."\n");exit(1);
}
