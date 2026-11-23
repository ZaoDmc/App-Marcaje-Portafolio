<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| FotoPerfilController — Visualización de foto de perfil (PRACTICANTE)
|--------------------------------------------------------------------------
|
| Valida acceso y entrega el archivo de imagen de perfil del practicante.
|
*/

require_once dirname(__DIR__, 2) . '/bootstrap.php';

auth_requiere_rol('PRACTICANTE');

$idPracticante = auth_practicante_actual_id();

if ($idPracticante === null) {
    http_response_code(404);
    exit;
}

try {
    $statement = db()->prepare(
        'SELECT ruta_relativa, mime_type
         FROM documentos_practicante
         WHERE id_practicante = :id_practicante
           AND tipo_documento = "FOTO_PERFIL"
           AND activo = 1
         ORDER BY created_at DESC, id_documento DESC
         LIMIT 1'
    );
    $statement->execute(['id_practicante' => $idPracticante]);
    $foto = $statement->fetch();
} catch (Throwable $exception) {
    error_log('Error al cargar foto de perfil: ' . $exception->getMessage());
    http_response_code(404);
    exit;
}

if (!is_array($foto)) {
    http_response_code(404);
    exit;
}

$uploadsRoot = realpath(APP_ROOT . DIRECTORY_SEPARATOR . 'uploads');
$rutaFisica = realpath(APP_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim((string) $foto['ruta_relativa'], '/')));

if ($uploadsRoot === false || $rutaFisica === false || !str_starts_with(strtolower($rutaFisica), strtolower($uploadsRoot . DIRECTORY_SEPARATOR)) || !is_file($rutaFisica)) {
    http_response_code(404);
    exit;
}

$mime = (string) ($foto['mime_type'] ?? 'image/jpeg');

if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
    http_response_code(404);
    exit;
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($rutaFisica));
header('Cache-Control: private, max-age=300');
readfile($rutaFisica);
exit;
