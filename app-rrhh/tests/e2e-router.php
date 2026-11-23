<?php

declare(strict_types=1);

// Agregué un router exclusivo del servidor CLI de pruebas. Usa el mismo
// front controller; las reglas reales Apache se verifican por separado.
if (PHP_SAPI !== 'cli-server' || !getenv('E2E_FRONTEND')) {
    http_response_code(404);
    exit;
}
$frontend = getenv('E2E_FRONTEND');
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (is_string($path) && str_starts_with($path, '/assets/') && !str_contains($path, '..')
    && preg_match('~\.(?:css|js|png|jpe?g|gif|svg|webp|ico|woff2?|ttf|eot)$~i', $path)
    && is_file($frontend . $path)) {
    return false;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
require $frontend . '/index.php';
