<?php

declare(strict_types=1);

// Reemplacé la carga de archivos desde la URL por la misma entrada en local y
// Hostinger. backend.php localiza el backend antes de poder leer su .env.
$backendPath = require __DIR__ . '/backend.php';
if ($backendPath === null) {
    http_response_code(500);
    exit('No fue posible localizar el backend o sus dependencias.');
}

try {
    require_once $backendPath . '/src/bootstrap.php';
} catch (Throwable $exception) {
    error_log('Error al iniciar el portal: ' . $exception->getMessage());
    http_response_code(500);
    exit('No fue posible iniciar la aplicación. Revisa la configuración del backend.');
}

$routes = require $backendPath . '/config/routes.php';
$routePath = router_request_path($_SERVER['REQUEST_URI'] ?? '/', BASE_PATH);
$route = router_match($routes, $routePath);
if ($route === null) {
    http_response_code(404);
    require VIEWS_PATH . '/errors/404.view.php';
    exit;
}

// Agregué método y autorización antes de renderizar o enviar archivos. Las
// reescrituras internas conservan query y POST; no se redirige la solicitud.
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if (!in_array($method, $route['methods'], true)) {
    header('Allow: ' . implode(', ', $route['methods']));
    http_response_code(405);
    exit('Método no permitido.');
}
if ($route['roles'] !== []) {
    auth_requiere_rol($route['roles']);
}

// El destino proviene exclusivamente del catálogo privado, nunca de la URL.
$controller = $backendPath . '/' . $route['controller'];
if (!is_file($controller)) {
    error_log('Falta un controlador del catálogo de rutas.');
    http_response_code(500);
    exit('La ruta solicitada no está disponible.');
}

// Agregué la ruta lógica para el menú y conservé el directorio de trabajo
// que utilizaba la entrada productiva anterior.
define('APP_ROUTE_PATH', $routePath);
chdir($backendPath);
require $controller;
