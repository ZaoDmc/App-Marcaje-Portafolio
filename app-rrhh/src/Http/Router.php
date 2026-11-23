<?php

declare(strict_types=1);

// Agregué esta función para resolver el backend antes de leer su .env. Usa
// rutas relativas en local/Hostinger o BACKEND_PATH proporcionado por el proceso.
function router_backend_path(string $publicDirectory, ?string $configuredPath = null): ?string
{
    $candidates = $configuredPath !== null && $configuredPath !== ''
        ? [$configuredPath]
        : [dirname($publicDirectory), dirname($publicDirectory, 2) . '/backend-rrhh'];
    foreach ($candidates as $candidate) {
        $resolved = realpath($candidate);
        if ($resolved !== false && is_file($resolved . '/src/bootstrap.php')
            && is_file($resolved . '/config/routes.php') && is_file($resolved . '/vendor/autoload.php')) {
            return $resolved;
        }
    }
    return null;
}

// Agregué normalización por segmentos: rechaza recorridos de directorios,
// barras inversas, controles y doble codificación. Nunca construye un archivo
// desde la URL: el resultado solo se consulta en el catálogo explícito.
function router_request_path(string $requestUri, string $basePath = ''): ?string
{
    if (!str_starts_with($requestUri, '/') || str_starts_with($requestUri, '//')
        || preg_match('/[\x00-\x1F\x7F]/', $requestUri)) {
        return null;
    }
    $parsed = parse_url($requestUri, PHP_URL_PATH);
    if (!is_string($parsed)) {
        return null;
    }
    $path = rawurldecode($parsed);
    if (str_contains($path, '\\') || str_contains($path, '%') || str_contains($path, '//')
        || preg_match('~[\x00-\x1F\x7F]|(?:^|/)\.{1,2}(?:/|$)~', $path)) {
        return null;
    }
    $basePath = rtrim($basePath, '/');
    if ($basePath !== '') {
        if ($path === $basePath) {
            $path = '/';
        } elseif (str_starts_with($path, $basePath . '/')) {
            $path = substr($path, strlen($basePath));
        } else {
            return null;
        }
    }
    // Conservé el acceso local anterior bajo /public sin cambiar las URLs
    // canónicas que generan los formularios y enlaces de la aplicación.
    if ($path === '/public' || $path === '/public/') {
        $path = '/';
    } elseif (str_starts_with($path, '/public/')) {
        $path = substr($path, strlen('/public'));
    }
    return rtrim($path, '/') ?: '/';
}

// Agregué coincidencia exacta: que un archivo exista no lo convierte en ruta.
function router_match(array $routes, ?string $path): ?array
{
    return $path === null ? null : ($routes[$path] ?? null);
}
