<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Application Bootstrap (Carga centralizada del sistema)
|--------------------------------------------------------------------------
|
| Define las constantes de directorios y carga los componentes base
| requeridos por toda la aplicación.
|
*/

if (!defined('SRC_PATH')) {
    define('SRC_PATH', __DIR__);
    define('SERVICES_PATH', SRC_PATH . '/Services');
    define('MODELS_PATH', SRC_PATH . '/Models');
    define('HELPERS_PATH', SRC_PATH . '/Helpers');
    define('VIEWS_PATH', SRC_PATH . '/Views');
}

// 1. Configuración del sistema
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';

// 2. Helpers y utilidades globales
require_once HELPERS_PATH . '/helpers.php';

// 3. Servicios base fundamentales
require_once SERVICES_PATH . '/AuditoriaService.php';
require_once SERVICES_PATH . '/AuthService.php';
