<?php

declare(strict_types=1);

// Agregué utilidades de publicación exclusivas de CLI: no cargan .env ni el
// bootstrap, y nunca consultan datos o ejecutan operaciones del portal.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// Agregué recorrido ordenado que rechaza enlaces y archivos persistentes.
function release_files(string $directory): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->isLink()) {
            throw new RuntimeException('No se permiten enlaces en el paquete.');
        }
        if ($file->isFile()) {
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($directory) + 1));
            if (preg_match('~(?:^|/)(?:\.env(?:\..*)?|uploads(?:\.[^/]*)?)(?:/|$)~i', $relative)) {
                throw new RuntimeException('Se detectó un archivo persistente fuera del inventario permitido.');
            }
            $files[$relative] = $file->getPathname();
        }
    }
    ksort($files);
    return $files;
}

// Agregué copia con lista positiva; evita empaquetar accidentalmente el repo.
function release_copy(string $source, string $destination, callable $allowed): void
{
    if (is_link($source)) {
        throw new RuntimeException('La fuente no puede ser un enlace.');
    }
    foreach (release_files($source) as $relative => $file) {
        // Omití marcadores de carpetas de Git: no son código ejecutable.
        if (basename($relative) === '.gitkeep') {
            continue;
        }
        if (!$allowed($relative)) {
            throw new RuntimeException('Archivo no permitido en la fuente: ' . $relative);
        }
        $target = $destination . '/' . $relative;
        if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0755, true)) {
            throw new RuntimeException('No fue posible crear el destino.');
        }
        if (!copy($file, $target)) {
            throw new RuntimeException('No fue posible copiar un archivo.');
        }
    }
}

// Agregué formatos públicos explícitos y entradas PHP fijas; no admite otros
// scripts ejecutables bajo assets ni archivos de entorno/configuración.
function release_public_allowed(string $path): bool
{
    return in_array($path, ['index.php', 'backend.php', '.htaccess', 'assets/icons/LICENSE'], true)
        || (bool) preg_match('~^assets/(?!.*(?:^|/)\.)(?:[a-zA-Z0-9_./ -]+)\.(?:css|js|png|jpe?g|gif|svg|webp|ico|woff2?|ttf|eot|map)$~i', $path);
}

// Agregué manifiesto por archivo y ZIP sin carpeta envolvente, para extraer
// cada archivo directamente dentro de su destino ya existente en Hostinger.
function release_zip(string $directory, string $archive): array
{
    $zip = new ZipArchive();
    if ($zip->open($archive, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
        throw new RuntimeException('No fue posible crear el ZIP.');
    }
    $hashes = [];
    foreach (release_files($directory) as $relative => $file) {
        if (!$zip->addFile($file, $relative)) {
            throw new RuntimeException('No fue posible agregar un archivo al ZIP.');
        }
        $hashes[$relative] = hash_file('sha256', $file);
    }
    if (!$zip->close()) {
        throw new RuntimeException('No fue posible finalizar el ZIP.');
    }
    return ['sha256' => hash_file('sha256', $archive), 'files' => $hashes];
}

// Agregué validación independiente del contenido real: detecta cambios,
// faltantes, entradas duplicadas, recorridos y archivos ajenos al manifiesto.
function release_verify(string $directory): int
{
    $manifest = json_decode((string) file_get_contents($directory . '/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
    if (($manifest['schema'] ?? null) !== 1
        || array_keys($manifest['archives'] ?? []) !== ['backend-rrhh.zip', 'software-rrhh.zip']) {
        throw new RuntimeException('Manifiesto de publicación inválido.');
    }
    $count = 0;
    foreach ($manifest['archives'] as $name => $details) {
        $file = $directory . '/' . $name;
        if (!is_file($file) || !hash_equals($details['sha256'], hash_file('sha256', $file))) {
            throw new RuntimeException('Hash del ZIP inválido: ' . $name);
        }
        $zip = new ZipArchive();
        if ($zip->open($file, ZipArchive::RDONLY) !== true) {
            throw new RuntimeException('ZIP inválido.');
        }
        try {
            // Exigí las entradas mínimas para no aceptar un ZIP vacío aunque
            // su hash y manifiesto coincidan accidentalmente.
            $required = $name === 'software-rrhh.zip'
                ? ['index.php', 'backend.php', '.htaccess', 'assets/css/tailwind.css', 'assets/js/ui.js', 'assets/brand/logo.png', 'assets/icons/fonts/bootstrap-icons.woff2']
                : ['composer.json', 'composer.lock', '.htaccess', 'src/bootstrap.php', 'src/Http/Router.php', 'config/routes.php', 'vendor/autoload.php'];
            foreach ($required as $path) {
                if ($zip->locateName($path) === false) {
                    throw new RuntimeException('Falta un archivo requerido: ' . $path);
                }
            }
            $seen = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $path = $zip->getNameIndex($i);
                if (!is_string($path) || str_contains($path, '\\') || preg_match('~^/|(?:^|/)\.{1,2}(?:/|$)|^[a-z]:~i', $path)
                    || isset($seen[$path]) || !isset($details['files'][$path])) {
                    throw new RuntimeException('Entrada no permitida en ZIP.');
                }
                $allowed = $name === 'software-rrhh.zip' ? release_public_allowed($path)
                    : (in_array($path, ['composer.json', 'composer.lock', '.htaccess'], true)
                        || (bool) preg_match('~^(?:src|config)/.+\.php$~', $path)
                        || str_starts_with($path, 'vendor/')
                        || (str_starts_with($path, 'public/') && release_public_allowed(substr($path, 7))));
                if (!$allowed || preg_match('~(?:^|/)(?:\.env(?:\..*)?|uploads(?:\.[^/]*)?)(?:/|$)~i', $path)
                    || !hash_equals($details['files'][$path], hash('sha256', $zip->getFromIndex($i)))) {
                    throw new RuntimeException('Contenido no permitido o alterado: ' . $path);
                }
                $seen[$path] = true;
                $count++;
            }
            if (count($seen) !== count($details['files'])) {
                throw new RuntimeException('Faltan archivos declarados en el ZIP.');
            }
            if ($name === 'backend-rrhh.zip') {
                // Comprobé que vendor representa exactamente packages del lock,
                // sin PHPUnit ni otras dependencias de desarrollo.
                $lock = json_decode($zip->getFromName('composer.lock'), true, 512, JSON_THROW_ON_ERROR);
                $installed = json_decode($zip->getFromName('vendor/composer/installed.json'), true, 512, JSON_THROW_ON_ERROR);
                $expected = array_column($lock['packages'], 'version', 'name');
                $actual = array_column($installed['packages'], 'version', 'name');
                ksort($expected);
                ksort($actual);
                if ($expected !== $actual || ($installed['dev'] ?? true) !== false
                    || $zip->locateName('vendor/autoload.php') === false) {
                    throw new RuntimeException('Dependencias de producción incompatibles con el lock.');
                }
            }
        } finally {
            $zip->close();
        }
    }
    // Comprobé que ambas mitades pertenecen al mismo contenido público.
    foreach ($manifest['archives']['software-rrhh.zip']['files'] as $path => $hash) {
        if (($manifest['archives']['backend-rrhh.zip']['files']['public/' . $path] ?? null) !== $hash) {
            throw new RuntimeException('El frontend y backend no corresponden a la misma publicación.');
        }
    }
    return $count;
}
