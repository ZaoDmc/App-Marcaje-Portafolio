<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| DescargarDocumentoController — Descarga de documentos (PRACTICANTE)
|--------------------------------------------------------------------------
|
| Valida acceso y entrega el archivo binario solicitado por el practicante.
|
*/

require_once dirname(__DIR__, 2) . '/bootstrap.php';

auth_requiere_rol('PRACTICANTE');

$idPracticante = auth_practicante_actual_id();
$idDocumento = query_param('id');

if ($idPracticante === null || !ctype_digit((string) $idDocumento)) {
    http_response_code(404);
    exit;
}

try {
    $statement = db()->prepare(
        'SELECT nombre_original, ruta_relativa, mime_type, tamano_bytes
         FROM documentos_practicante
         WHERE id_documento = :id_documento
           AND id_practicante = :id_practicante
           AND tipo_documento = "DOCUMENTO_PRACTICA"
           AND activo = 1
         LIMIT 1'
    );
    $statement->execute([
        'id_documento' => (int) $idDocumento,
        'id_practicante' => $idPracticante,
    ]);
    $documento = $statement->fetch();
} catch (Throwable $exception) {
    error_log('Error al descargar documento de practicante: ' . $exception->getMessage());
    http_response_code(404);
    exit;
}

if (!is_array($documento)) {
    http_response_code(404);
    exit;
}

$uploadsRoot = realpath(APP_ROOT . DIRECTORY_SEPARATOR . 'uploads');
$rutaFisica = realpath(APP_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim((string) $documento['ruta_relativa'], '/')));

if ($uploadsRoot === false || $rutaFisica === false || !str_starts_with(strtolower($rutaFisica), strtolower($uploadsRoot . DIRECTORY_SEPARATOR)) || !is_file($rutaFisica)) {
    http_response_code(404);
    exit;
}

$nombreOriginal = basename((string) $documento['nombre_original']);
$mime = (string) ($documento['mime_type'] ?? 'application/octet-stream');

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($rutaFisica));
header('Content-Disposition: attachment; filename="' . addcslashes($nombreOriginal, '"\\') . '"; filename*=UTF-8\'\'' . rawurlencode($nombreOriginal));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($rutaFisica);
exit;
