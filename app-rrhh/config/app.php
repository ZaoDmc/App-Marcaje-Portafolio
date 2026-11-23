<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Configuración general de la aplicación
|--------------------------------------------------------------------------
|
| Este archivo centraliza valores comunes del proyecto sin depender de rutas
| absolutas del equipo local. Las rutas de servidor se calculan desde la
| ubicación real del proyecto y las URLs se calculan desde la petición actual.
|
*/

define('APP_ROOT', dirname(__DIR__));
define('CONFIG_PATH', APP_ROOT . DIRECTORY_SEPARATOR . 'config');
// Retiré INCLUDES_PATH al eliminar los puentes; los módulos usan SRC_PATH.
// Corregí el directorio de recursos para reflejar la fuente pública existente.
define('ASSETS_PATH', APP_ROOT . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets');


// Reemplacé el parser manual por el cargador central para preservar URLs,
// comillas y variables del servidor con la misma semántica que producción.
require_once __DIR__ . '/environment.php';
app_load_env();

define('APP_NAME', 'Portal de Asistencia y Control de Practicantes');
// Agregué la versión de esta entrega desde su única fuente en el repositorio.
define('APP_VERSION', require __DIR__ . '/version.php');
define('APP_ENV', app_env('APP_ENV') ?: 'local');
// Agregué soporte explícito para APP_DEBUG; producción siempre lo desactiva
// para que una configuración accidental no habilite detalles sensibles.
define('APP_DEBUG', APP_ENV !== 'production' && filter_var(
    app_env('APP_DEBUG', APP_ENV === 'local' ? 'true' : 'false'),
    FILTER_VALIDATE_BOOLEAN
));
define('APP_TIMEZONE', app_env('APP_TIMEZONE') ?: 'America/Santiago');
define('SESSION_NAME', 'ASISTENCIA_PORTAL_SESION');
define('SESSION_TIMEOUT_MINUTES', 30);
define('REMEMBER_SESSION_DAYS', 30);
define('TOKEN_EXPIRATION_HOURS', 24);
define('MAX_LOGIN_ATTEMPTS', 3);
define('LOGIN_LOCK_MINUTES', 15);

date_default_timezone_set(APP_TIMEZONE);

if (!function_exists('app_detect_base_path')) {
    function app_detect_base_path(): string
    {
        // Permite fijar la ruta base desde el entorno cuando el hosting lo requiera.
        // Cambié la lectura para aceptar una ruta vacía explícita en subdominios
        // y conservar BASE_PATH como alias compatible de APP_BASE_PATH.
        $configuredBasePath = app_env('BASE_PATH') ?? app_env('APP_BASE_PATH');

        if ($configuredBasePath !== null) {
            return rtrim('/' . trim($configuredBasePath, '/'), '/');
        }

        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $projectFolder = basename(APP_ROOT);

        if ($scriptName === '' || $projectFolder === '') {
            return '';
        }

        $segments = array_values(array_filter(explode('/', str_replace('\\', '/', $scriptName))));
        $projectIndex = array_search($projectFolder, $segments, true);

        if ($projectIndex === false) {
            return '';
        }

        return '/' . implode('/', array_slice($segments, 0, $projectIndex + 1));
    }
}

if (!function_exists('app_detect_url')) {
    function app_detect_url(): string
    {
        // Permite fijar la URL pública sin depender de cabeceras del servidor.
        // Cambié la lectura al acceso común; una URL vacía mantiene la detección
        // automática y BASE_URL conserva prioridad sobre APP_URL.
        $configuredUrl = app_env('BASE_URL') ?: app_env('APP_URL');

        if ($configuredUrl !== null && $configuredUrl !== '') {
            return rtrim($configuredUrl, '/');
        }

        $isHttps = (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? null) === '443')
        );

        $scheme = $isHttps ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $scheme . '://' . $host . BASE_PATH;
    }
}

define('BASE_PATH', app_detect_base_path());
define('BASE_URL', app_detect_url());
define('ASSETS_URL', BASE_URL . '/assets');

// Aliases de compatibilidad interna para código existente o integraciones futuras.
define('APP_BASE_PATH', BASE_PATH);
define('APP_URL', BASE_URL);
