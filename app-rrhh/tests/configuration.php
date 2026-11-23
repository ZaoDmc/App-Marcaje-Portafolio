<?php

declare(strict_types=1);

// Agregué una restricción CLI para que estas pruebas no puedan ejecutarse
// como una página web mientras el enrutador antiguo siga en transición.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// Agregué estas pruebas aisladas para proteger la carga de configuración sin
// conectar a MySQL, enviar correos ni escribir el .env real del proyecto.
require_once dirname(__DIR__) . '/config/environment.php';

$fixtureDirectories = [];
$checks = 0;

function config_assert(bool $condition, string $message): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $checks++;
    echo "OK: {$message}\n";
}

// Agregué archivos temporales con nombres propios de prueba; al terminar se
// eliminan únicamente esos archivos y directorios, sin recorrer uploads.
function config_fixture(?string $contents): string
{
    global $fixtureDirectories;
    $directory = sys_get_temp_dir() . '/rrhh-config-' . bin2hex(random_bytes(8));
    if (!mkdir($directory, 0700)) {
        throw new RuntimeException('No fue posible crear el entorno temporal de pruebas.');
    }
    $fixtureDirectories[] = $directory;
    if ($contents !== null && file_put_contents($directory . '/.env', $contents) === false) {
        throw new RuntimeException('No fue posible escribir el entorno temporal de pruebas.');
    }
    return $directory;
}

// Agregué un proceso PHP por escenario para probar constantes sin redefinirlas.
// Los valores del escenario tienen prioridad y no se imprimen credenciales.
function config_scenario(array $values, string $assertions): bool
{
    $root = dirname(__DIR__);
    // Agregué una copia temporal de la configuración con un autoloader puente
    // para probar constantes sin leer el .env real ni depender de su contenido.
    $backendFixture = config_fixture(null);
    mkdir($backendFixture . '/config', 0700);
    mkdir($backendFixture . '/vendor', 0700);
    foreach (['app.php', 'version.php', 'environment.php', 'database.php', 'mail.php'] as $configurationFile) {
        if (!copy($root . '/config/' . $configurationFile, $backendFixture . '/config/' . $configurationFile)) {
            throw new RuntimeException('No fue posible preparar la configuración temporal.');
        }
    }
    file_put_contents($backendFixture . '/vendor/autoload.php', '<?php require_once ' . var_export($root . '/vendor/autoload.php', true) . ';');
    $script = '<?php ';
    $script .= 'foreach (["APP_ENV", "APP_DEBUG", "APP_TIMEZONE", "APP_BASE_PATH", "APP_URL", "BASE_PATH", "BASE_URL", "MAIL_DEBUG", "DB_PASS"] as $key) {';
    $script .= 'putenv($key); unset($_ENV[$key], $_SERVER[$key]); }';
    $script .= 'foreach (' . var_export($values, true) . ' as $key => $value) { putenv($key . "=" . $value); $_ENV[$key] = $value; }';
    // Fijé cabeceras ficticias para que la detección automática no dependa
    // del servidor del desarrollador ni de variables heredadas del proceso.
    $script .= '$_SERVER["SCRIPT_NAME"] = "/" . ' . var_export(basename($backendFixture), true) . ' . "/login.php";';
    $script .= '$_SERVER["HTTP_HOST"] = "example.test"; $_SERVER["HTTPS"] = "on";';
    $script .= 'require ' . var_export($backendFixture . '/config/database.php', true) . ';';
    $script .= 'require ' . var_export($backendFixture . '/config/mail.php', true) . ';';
    $script .= 'exit((' . $assertions . ') ? 0 : 1);';

    $process = proc_open([PHP_BINARY], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) {
        throw new RuntimeException('No fue posible iniciar el proceso de configuración.');
    }
    fwrite($pipes[0], $script);
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);
    // Se oculta la salida del subproceso por si el entorno local emite datos
    // sensibles; el nombre del escenario identifica el fallo en la consola.
    return $exitCode === 0 && $output === '' && $errors === '';
}

try {
    // Compruebo que el parser conserve URLs, comentarios, espacios y valores
    // citados con símbolos que pueden formar parte de una contraseña.
    $parserFixture = config_fixture(<<<'ENV'
RRHH_TEST_URL=https://example.test/portal
RRHH_TEST_NAME="Portal de prácticas"
RRHH_TEST_SECRET='valor # con // y = símbolos'
RRHH_TEST_COMMENT=activo # comentario
RRHH_TEST_EMPTY=
RRHH_TEST_EXPANSION="${RRHH_TEST_URL}/assets"
ENV);
    app_load_env($parserFixture);
    config_assert(app_env('RRHH_TEST_URL') === 'https://example.test/portal', 'conserva https:// sin truncarlo');
    config_assert(app_env('RRHH_TEST_NAME') === 'Portal de prácticas', 'conserva espacios y UTF-8');
    config_assert(app_env('RRHH_TEST_SECRET') === 'valor # con // y = símbolos', 'conserva símbolos dentro de valores citados');
    config_assert(app_env('RRHH_TEST_COMMENT') === 'activo', 'interpreta comentarios de dotenv');
    config_assert(app_env('RRHH_TEST_EMPTY', 'fallback') === '', 'conserva valores vacíos explícitos');
    config_assert(app_env('RRHH_TEST_EXPANSION') === 'https://example.test/portal/assets', 'expande referencias entre variables');

    // Compruebo que no se sustituyan variables suministradas por el servidor
    // aunque el archivo tenga valores distintos, incluido un valor vacío.
    putenv('RRHH_TEST_PROCESS=servidor');
    putenv('RRHH_TEST_PROCESS_EMPTY=');
    $_ENV['RRHH_TEST_ENV_ONLY'] = 'entorno';
    $_SERVER['RRHH_TEST_SERVER_ONLY'] = 'servidor-web';
    $priorityFixture = config_fixture("RRHH_TEST_PROCESS=archivo\nRRHH_TEST_PROCESS_EMPTY=archivo\nRRHH_TEST_ENV_ONLY=archivo\nRRHH_TEST_SERVER_ONLY=archivo\n");
    app_load_env($priorityFixture);
    config_assert(app_env('RRHH_TEST_PROCESS') === 'servidor', 'respeta variables del proceso');
    config_assert(app_env('RRHH_TEST_PROCESS_EMPTY') === '', 'no sobrescribe un valor vacío del proceso');
    config_assert(app_env('RRHH_TEST_ENV_ONLY') === 'entorno', 'lee variables existentes en $_ENV');
    config_assert(app_env('RRHH_TEST_SERVER_ONLY') === 'servidor-web', 'lee variables existentes en $_SERVER');
    config_assert(app_env('RRHH_TEST_UNDEFINED', 'fallback') === 'fallback', 'aplica el valor por defecto solo si falta la variable');

    // Compruebo compatibilidad con el index.php productivo que ya carga Dotenv.
    $preloadedFixture = config_fixture("RRHH_TEST_PRELOADED=inicial\n");
    \Dotenv\Dotenv::createUnsafeImmutable($preloadedFixture)->safeLoad();
    file_put_contents($preloadedFixture . '/.env', "RRHH_TEST_PRELOADED=cambiado\n");
    app_load_env($preloadedFixture);
    config_assert(app_env('RRHH_TEST_PRELOADED') === 'inicial', 'respeta una carga previa de Dotenv');
    file_put_contents($preloadedFixture . '/.env', "línea inválida con secreto\n");
    app_load_env($preloadedFixture);
    config_assert(app_env('RRHH_TEST_PRELOADED') === 'inicial', 'no vuelve a parsear el mismo directorio');

    // Compruebo que un servidor configurado por variables no necesite archivo
    // y que un error de sintaxis no revele el contenido que lo provocó.
    app_load_env(config_fixture(null));
    config_assert(true, 'permite un entorno sin archivo .env');
    $sanitizedError = false;
    try {
        app_load_env(config_fixture("RRHH_TEST_BROKEN=secreto de prueba sin comillas\n"));
    } catch (RuntimeException $exception) {
        $sanitizedError = !str_contains($exception->getMessage(), 'secreto') && $exception->getPrevious() === null;
    }
    config_assert($sanitizedError, 'rechaza dotenv inválido sin revelar su contenido');

    // Compruebo producción, ruta vacía de subdominio, alias antiguos, desarrollo
    // en subcarpeta y parámetros de correo/base de datos sin abrir conexiones.
    config_assert(config_scenario([
        'APP_ENV' => 'production', 'APP_DEBUG' => 'true', 'MAIL_DEBUG' => '4',
        'APP_BASE_PATH' => '', 'BASE_PATH' => '',
        'APP_URL' => 'https://example.test', 'BASE_URL' => 'https://example.test',
        'DB_PASS' => '',
    ], 'APP_DEBUG === false && MAIL_DEBUG === 0 && BASE_PATH === "" && BASE_URL === "https://example.test" && ASSETS_URL === "https://example.test/assets" && DB_PASS === ""'), 'producción bloquea debug y acepta ruta y contraseña vacías');

    config_assert(config_scenario([
        'APP_ENV' => 'local', 'APP_DEBUG' => 'false', 'MAIL_DEBUG' => '2',
        'APP_BASE_PATH' => '/asistencia/', 'BASE_PATH' => '/asistencia/',
        'APP_URL' => 'http://localhost/asistencia/', 'BASE_URL' => 'http://localhost/asistencia/',
    ], 'APP_DEBUG === false && MAIL_DEBUG === 2 && BASE_PATH === "/asistencia" && BASE_URL === "http://localhost/asistencia"'), 'desarrollo respeta debug explícito y normaliza barras finales');

    config_assert(config_scenario([
        'APP_ENV' => 'local', 'APP_DEBUG' => 'true', 'MAIL_DEBUG' => '0',
        'BASE_PATH' => '/alias', 'APP_BASE_PATH' => '/otro',
        'BASE_URL' => 'https://example.test/alias', 'APP_URL' => 'https://example.test/otro',
    ], 'APP_DEBUG === true && BASE_PATH === "/alias" && BASE_URL === "https://example.test/alias"'), 'conserva prioridad de aliases BASE_PATH y BASE_URL');

    config_assert(config_scenario([
        'APP_ENV' => 'local', 'APP_BASE_PATH' => '', 'APP_URL' => 'https://example.test/',
    ], 'APP_DEBUG === true && BASE_PATH === "" && BASE_URL === "https://example.test"'), 'funciona con variables APP sin aliases antiguos');

    config_assert(config_scenario([
        'APP_ENV' => 'production',
    ], 'APP_DEBUG === false && BASE_PATH === "/" . basename(APP_ROOT) && BASE_URL === "https://example.test/" . basename(APP_ROOT)'), 'detecta URL y subcarpeta cuando no se configuraron explícitamente');

    // Comprobé que el entorno no pueda anunciar una versión distinta del código instalado.
    config_assert(config_scenario([
        'APP_ENV' => 'production', 'APP_VERSION' => '99.99.99',
    ], 'APP_VERSION === (require CONFIG_PATH . "/version.php") && preg_match("/^(0|[1-9][0-9]*)\\.(0|[1-9][0-9]*)\\.(0|[1-9][0-9]*)$/D", APP_VERSION) === 1'), 'la versión pertenece al código y no puede reemplazarse desde el entorno');

    echo "Configuración: {$checks} comprobaciones correctas.\n";
} finally {
    foreach ($fixtureDirectories as $directory) {
        if (is_file($directory . '/.env')) {
            unlink($directory . '/.env');
        }
        // Agregué limpieza explícita de las copias conocidas del escenario;
        // no se eliminan rutas recursivas ni archivos fuera del fixture creado.
        foreach (['config/app.php', 'config/version.php', 'config/environment.php', 'config/database.php', 'config/mail.php', 'vendor/autoload.php'] as $fixtureFile) {
            if (is_file($directory . '/' . $fixtureFile)) {
                unlink($directory . '/' . $fixtureFile);
            }
        }
        foreach (['config', 'vendor'] as $fixtureSubdirectory) {
            if (is_dir($directory . '/' . $fixtureSubdirectory)) {
                rmdir($directory . '/' . $fixtureSubdirectory);
            }
        }
        rmdir($directory);
    }
}
