<?php

declare(strict_types=1);

// Agregué pruebas del artefacto real y casos adversos aislados bajo scratch.
// No usan .env, bases de datos, archivos de usuarios ni el servidor productivo.
require_once dirname(__DIR__) . '/scripts/release-lib.php';
$directory = $argv[1] ?? '';
$checks = 0;
function release_assert(bool $condition, string $message): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException('Falló publicación: ' . $message);
    }
    $checks++;
}
release_assert(release_verify($directory) > 0, 'ZIP reales íntegros');
$manifest = json_decode(file_get_contents($directory . '/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
$root = dirname(__DIR__);
foreach ($manifest['archives'] as $name => $archive) {
    foreach ($archive['files'] as $path => $hash) {
        release_assert(!preg_match('~(?:^|/)(?:\.env(?:\..*)?|uploads(?:\.[^/]*)?|node_modules|tests|scratch|\.git)(?:/|$)~i', $path), 'exclusión de datos y desarrollo: ' . $path);
        if (str_starts_with($path, 'src/') || str_starts_with($path, 'config/') || str_starts_with($path, 'public/')) {
            release_assert(is_file($root . '/' . $path) && hash_file('sha256', $root . '/' . $path) === $hash, 'backend corresponde al código actual: ' . $path);
        } elseif ($name === 'software-rrhh.zip') {
            release_assert(hash_file('sha256', $root . '/public/' . $path) === $hash, 'frontend corresponde a public: ' . $path);
        }
    }
}
// Verifiqué el retiro de copias antiguas mediante su inventario versionado.
// Actualicé la ruta del inventario al reorganizar docs; conservé sus comprobaciones.
$inventory = fopen($root . '/docs/historial/mvc-tailwind/FASE_7_ASSETS_RETIRADOS.csv', 'r');
fgetcsv($inventory, escape: '');
while (($row = fgetcsv($inventory, escape: '')) !== false) {
    [$old, $target, $oldHash, $targetHash] = $row;
    release_assert(!is_file($root . '/' . $old), 'copia antigua retirada: ' . $old);
    // El inventario documenta el traslado original; los assets activos pueden evolucionar después.
    // Exigí que el destino exista y que su versión actual esté íntegra en el paquete verificado.
    $publicPath = str_starts_with($target, 'public/') ? substr($target, 7) : '';
    release_assert(is_file($root . '/' . $target)
        && ($manifest['archives']['software-rrhh.zip']['files'][$publicPath] ?? null) === hash_file('sha256', $root . '/' . $target), 'destino actual incluido e íntegro: ' . $target);
}
fclose($inventory);

$fixtures = $root . '/scratch/phase7/negative-' . bin2hex(random_bytes(4));
// Extraje únicamente ZIP ya verificados en una estructura nueva equivalente
// a Hostinger; no copié .env ni documentos. Compruebo el descubrimiento real.
$layout = $fixtures . '/layout';
foreach (['backend-rrhh.zip' => '/backend-rrhh', 'software-rrhh.zip' => '/public_html/software-rrhh'] as $name => $target) {
    mkdir($layout . $target, 0755, true);
    $zip = new ZipArchive();
    $zip->open($directory . '/' . $name, ZipArchive::RDONLY);
    release_assert($zip->extractTo($layout . $target), 'extracción de ' . $name);
    $zip->close();
}
$previousBackend = getenv('BACKEND_PATH');
putenv('BACKEND_PATH');
$discovered = require $layout . '/public_html/software-rrhh/backend.php';
release_assert($discovered === realpath($layout . '/backend-rrhh'), 'entrada pública encuentra backend privado');
if ($previousBackend !== false) {
    putenv('BACKEND_PATH=' . $previousBackend);
}
// Agregué ataques con hashes recalculados para comprobar la política de
// contenidos, además de la detección simple de corrupción del ZIP.
foreach (['extra', 'missing', 'changed', 'corrupt', 'development'] as $case) {
    $fixture = $fixtures . '/' . $case;
    mkdir($fixture, 0755, true);
    $copy = $manifest;
    foreach (array_keys($manifest['archives']) as $name) {
        copy($directory . '/' . $name, $fixture . '/' . $name);
    }
    $name = $case === 'development' ? 'backend-rrhh.zip' : 'software-rrhh.zip';
    $zip = new ZipArchive();
    $zip->open($fixture . '/' . $name);
    if ($case === 'extra') {
        $zip->addFromString('.env', 'DATO_FICTICIO=prueba');
        $copy['archives'][$name]['files']['.env'] = hash('sha256', 'DATO_FICTICIO=prueba');
    } elseif ($case === 'missing') {
        $zip->deleteName('backend.php');
    } elseif ($case === 'changed' || $case === 'corrupt') {
        $zip->addFromString('assets/js/ui.js', 'contenido alterado');
    } else {
        $metadata = json_decode($zip->getFromName('vendor/composer/installed.json'), true, 512, JSON_THROW_ON_ERROR);
        $metadata['dev'] = true;
        $data = json_encode($metadata, JSON_THROW_ON_ERROR);
        $zip->addFromString('vendor/composer/installed.json', $data);
        $copy['archives'][$name]['files']['vendor/composer/installed.json'] = hash('sha256', $data);
    }
    $zip->close();
    if ($case !== 'corrupt') {
        $copy['archives'][$name]['sha256'] = hash_file('sha256', $fixture . '/' . $name);
    }
    file_put_contents($fixture . '/manifest.json', json_encode($copy, JSON_THROW_ON_ERROR));
    $rejected = false;
    try {
        release_verify($fixture);
    } catch (RuntimeException) {
        $rejected = true;
    }
    release_assert($rejected, 'rechaza ' . $case);
}
echo "Publicación: {$checks} comprobaciones correctas.\n";
echo "Estructura HTTP de prueba: {$layout}\n";
