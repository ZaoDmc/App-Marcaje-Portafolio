<?php

declare(strict_types=1);

// Incorporé una revisión CLI de solo lectura; nunca aplica parches ni imprime registros, entorno o credenciales.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (($argv[1] ?? '') !== '--check') { exit("Uso: php scripts/check-notas.php --check\n"); }
require_once __DIR__ . '/notas-preflight-lib.php';
try {
    require_once dirname(__DIR__) . '/src/bootstrap.php';
    $resultado = notas_preflight(db());
    foreach ($resultado['comprobaciones'] as $nombre=>$ok) { echo ($ok ? 'OK: ' : 'PENDIENTE: ').$nombre."\n"; }
    echo "Revisión de solo lectura. No certifica la rúbrica académica ni reemplaza las pruebas de permisos.\n";
    exit($resultado['ok'] ? 0 : 1);
} catch (Throwable) {
    // Omití mensajes SQL para no exponer configuración ni contenidos cuando el esquema sea incompatible.
    fwrite(STDERR,"No fue posible completar la revisión. Verifica la conexión y las migraciones 009–011. No se aplicaron cambios.\n");
    exit(1);
}
