<?php

declare(strict_types=1);

// Agregué un generador CLI aislado. Publica copias nuevas bajo scratch, nunca
// modifica vendor local, .env, uploads, servicios o archivos de Hostinger.
require_once __DIR__ . '/release-lib.php';

// Agregué ejecución por argumentos sin shell para Composer y datos Git.
function release_process(array $arguments, string $directory, bool $capture = false): string
{
    $pipes = [];
    $process = proc_open($arguments, $capture ? [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']] : [STDIN, STDOUT, STDERR], $pipes, $directory);
    if (!is_resource($process)) {
        throw new RuntimeException('No fue posible iniciar una herramienta de publicación.');
    }
    $result = '';
    if ($capture) {
        fclose($pipes[0]);
        $result = stream_get_contents($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
    }
    if (proc_close($process) !== 0) {
        throw new RuntimeException('Falló una herramienta; no se completa la publicación.');
    }
    return trim($result);
}

try {
    $root = dirname(__DIR__);
    $composer = isset($argv[1]) ? realpath($argv[1]) : false;
    if ($composer === false || !is_file($composer) || strtolower(pathinfo($composer, PATHINFO_EXTENSION)) !== 'phar') {
        throw new RuntimeException('Uso: php scripts/build-release.php ruta/a/composer.phar');
    }
    if (!class_exists(ZipArchive::class)) {
        throw new RuntimeException('Se necesita la extensión PHP zip.');
    }
    $revision = release_process(['git', 'rev-parse', 'HEAD'], $root, true);
    $dirty = release_process(['git', 'status', '--porcelain'], $root, true) !== '';
    $id = gmdate('Ymd-His') . '-' . substr($revision, 0, 8) . '-' . bin2hex(random_bytes(3));
    $base = $root . '/scratch/releases';
    if (!is_dir($base) && !mkdir($base, 0755, true)) {
        throw new RuntimeException('No fue posible crear scratch/releases.');
    }
    // Validé la ruta final antes de escribir: no acepta destinos arbitrarios
    // ni enlaces de scratch hacia otras carpetas del equipo.
    $resolvedBase = realpath($base);
    $expectedBase = $root . DIRECTORY_SEPARATOR . 'scratch' . DIRECTORY_SEPARATOR . 'releases';
    if ($resolvedBase === false || strcasecmp($resolvedBase, $expectedBase) !== 0 || is_link($base) || is_link($root . '/scratch')) {
        throw new RuntimeException('El destino debe permanecer dentro de scratch/releases.');
    }
    $output = $resolvedBase . '/' . $id;
    $backend = $output . '/staging/backend-rrhh';
    $frontend = $output . '/staging/public_html/software-rrhh';
    mkdir($backend, 0755, true);
    mkdir($frontend, 0755, true);
    foreach (['config', 'src'] as $directory) {
        release_copy($root . '/' . $directory, $backend . '/' . $directory, static fn(string $path): bool => str_ends_with($path, '.php') && !preg_match('~(?:^|/)\.~', $path));
    }
    release_copy($root . '/public', $backend . '/public', 'release_public_allowed');
    release_copy($root . '/public', $frontend, 'release_public_allowed');
    // Comprobé que todas las rutas tienen su controlador dentro del paquete.
    foreach (require $root . '/config/routes.php' as $route) {
        if (!is_file($backend . '/' . $route['controller'])) {
            throw new RuntimeException('Falta un controlador del catálogo en el paquete.');
        }
    }
    foreach (['composer.json', 'composer.lock'] as $file) {
        if (!copy($root . '/' . $file, $backend . '/' . $file)) {
            throw new RuntimeException('No fue posible copiar el manifiesto PHP.');
        }
    }
    // Agregué protección exclusiva del backend empaquetado. La raíz local
    // conserva su rewrite; el backend productivo debe quedar fuera de public_html.
    file_put_contents($backend . '/.htaccess', "# Agregué bloqueo HTTP del backend privado de producción.\nOptions -Indexes\nRequire all denied\n");
    release_process([PHP_BINARY, $composer, 'install', '--no-dev', '--prefer-dist', '--optimize-autoloader', '--no-interaction', '--no-scripts', '--no-plugins'], $backend);
    release_process([PHP_BINARY, $composer, 'check-platform-reqs', '--no-dev'], $backend);
    $manifest = [
        'schema' => 1, 'id' => $id, 'created_utc' => gmdate(DATE_ATOM),
        'source_commit' => $revision, 'working_tree_dirty' => $dirty, 'build_php' => PHP_VERSION,
        'preserve' => ['backend-rrhh/.env', 'backend-rrhh/uploads/', 'backend-rrhh/uploads.751/', 'public uploads existentes'],
        'archives' => [
            'backend-rrhh.zip' => release_zip($backend, $output . '/backend-rrhh.zip'),
            'software-rrhh.zip' => release_zip($frontend, $output . '/software-rrhh.zip'),
        ],
    ];
    file_put_contents($output . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
    $count = release_verify($output);
    echo "Publicación preparada: {$output}\n{$count} archivos verificados. No se ha subido ningún archivo.\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'Publicación interrumpida: ' . $error->getMessage() . "\n");
    exit(1);
}
