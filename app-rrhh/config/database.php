<?php

declare(strict_types=1);

require_once __DIR__ . '/app.php';

/*
|--------------------------------------------------------------------------
| Configuración de base de datos
|--------------------------------------------------------------------------
|
| Este archivo centraliza la conexión PDO del sistema. La conexión se crea
| bajo demanda para evitar fallos al incluir configuración antes de importar
| el schema inicial en XAMPP o en Hostinger.
|
*/

// Cambié estas lecturas al acceso común para respetar la configuración del
// servidor y conservar contraseñas vacías válidas en el entorno local.
define('DB_HOST', app_env('DB_HOST', 'localhost'));
define('DB_PORT', app_env('DB_PORT', '3306'));
define('DB_NAME', app_env('DB_NAME', 'asistencia'));
define('DB_USER', app_env('DB_USER', 'root'));
define('DB_PASS', app_env('DB_PASS', ''));
define('DB_CHARSET', app_env('DB_CHARSET', 'utf8mb4'));

if (!function_exists('database_dsn')) {
    function database_dsn(): string
    {
        // El DSN se arma desde constantes para mantener portabilidad entre XAMPP y hosting.
        return sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );
    }
}

if (!function_exists('database_options')) {
    function database_options(): array
    {
        // Opciones obligatorias del proyecto para errores, lectura asociativa y prepares reales.
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
    }
}

if (!function_exists('db')) {
    function db(): PDO
    {
        static $connection = null;

        if ($connection instanceof PDO) {
            return $connection;
        }

        try {
            $connection = new PDO(database_dsn(), DB_USER, DB_PASS, database_options());
            return $connection;
        } catch (PDOException $exception) {
            if (APP_DEBUG) {
                throw $exception;
            }

            throw new RuntimeException('No fue posible conectar con la base de datos.');
        }
    }
}
