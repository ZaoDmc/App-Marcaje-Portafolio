<?php

declare(strict_types=1);

// Agregué verificación CLI de ZIP/manifiesto sin extraer ni ejecutar el código.
require_once __DIR__ . '/release-lib.php';
try {
    if (!isset($argv[1]) || !is_dir($argv[1])) {
        throw new RuntimeException('Uso: php scripts/verify-release.php carpeta/de/publicacion');
    }
    echo 'Publicación íntegra: ' . release_verify($argv[1]) . " archivos comprobados.\n";
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
