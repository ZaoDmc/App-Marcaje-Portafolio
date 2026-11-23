<?php

declare(strict_types=1);

// Agregué descubrimiento relativo para copiar la misma entrada a Hostinger.
// No utiliza valores de la solicitud para localizar el código privado.
$configuredBackend = getenv('BACKEND_PATH');
$backendCandidates = $configuredBackend !== false && $configuredBackend !== ''
    ? [$configuredBackend]
    : [dirname(__DIR__), dirname(__DIR__, 2) . '/backend-rrhh'];
foreach ($backendCandidates as $backendCandidate) {
    $routerFile = $backendCandidate . '/src/Http/Router.php';
    if (is_file($routerFile)) {
        require_once $routerFile;
        return router_backend_path(__DIR__, $configuredBackend !== false ? $configuredBackend : null);
    }
}
return null;
