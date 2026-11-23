<?php

declare(strict_types=1);

// Agregué este cargador común para que las entradas web, CLI y configuración
// utilicen phpdotenv sin mantener un segundo parser de archivos .env.
if (!function_exists('app_load_env')) {
    function app_load_env(?string $directory = null): void
    {
        static $loadedDirectories = [];

        $directory ??= dirname(__DIR__);
        $resolvedDirectory = realpath($directory);
        if ($resolvedDirectory === false || !is_dir($resolvedDirectory)) {
            throw new RuntimeException('No existe el directorio de configuración del entorno.');
        }

        // Agregué esta comprobación para cargar cada entorno una sola vez por
        // petición, incluso si varios componentes solicitan la configuración.
        if (isset($loadedDirectories[$resolvedDirectory])) {
            return;
        }

        $autoloadPath = dirname(__DIR__) . '/vendor/autoload.php';
        if (!is_file($autoloadPath)) {
            throw new RuntimeException('Faltan dependencias del proyecto. Ejecuta composer install en el backend.');
        }
        require_once $autoloadPath;

        if (!class_exists(\Dotenv\Dotenv::class)) {
            throw new RuntimeException('Falta phpdotenv. Instala las dependencias bloqueadas del proyecto.');
        }

        try {
            // Conservé getenv() para los componentes existentes. El repositorio
            // inmutable respeta variables del servidor y cargas previas del
            // index.php productivo, sin sobrescribirlas con el archivo .env.
            \Dotenv\Dotenv::createUnsafeImmutable($resolvedDirectory)->safeLoad();
        } catch (\Dotenv\Exception\InvalidFileException | \Dotenv\Exception\InvalidEncodingException $exception) {
            // Agregué un error sin el contenido original: el parser puede incluir
            // valores sensibles en sus mensajes al encontrar una línea inválida.
            throw new RuntimeException('El archivo .env tiene una sintaxis o codificación inválida.');
        }

        $loadedDirectories[$resolvedDirectory] = true;
    }
}

// Agregué este acceso común para respetar valores del proceso, $_ENV o $_SERVER,
// incluidos valores vacíos válidos como DB_PASS y la ruta base del subdominio.
if (!function_exists('app_env')) {
    function app_env(string $name, ?string $default = null): ?string
    {
        $processValue = getenv($name);
        if ($processValue !== false) {
            return $processValue;
        }

        foreach ([$_ENV[$name] ?? null, $_SERVER[$name] ?? null] as $value) {
            if (is_string($value)) {
                return $value;
            }
        }

        return $default;
    }
}
