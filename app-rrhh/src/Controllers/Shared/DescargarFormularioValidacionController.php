<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once MODELS_PATH . '/FormularioValidacionModel.php';

auth_requiere_rol(['ADMIN', 'SUPERVISOR']);

$idDocumento = query_param('id');
if (!is_string($idDocumento) || !ctype_digit($idDocumento) || (int) $idDocumento < 1) {
    http_response_code(404);
    exit;
}

try {
    $documento = formulario_validacion_documento_por_id((int) $idDocumento);
} catch (Throwable $exception) {
    error_log('No fue posible consultar un documento de validación.');
    http_response_code(404);
    exit;
}

if ($documento === null) {
    http_response_code(404);
    exit;
}

$uploadsRoot = realpath(APP_ROOT . DIRECTORY_SEPARATOR . 'uploads');
$rutaFisica = realpath(
    APP_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim((string) $documento['ruta_relativa'], '/'))
);
if ($uploadsRoot === false || $rutaFisica === false
    || !str_starts_with(strtolower($rutaFisica), strtolower($uploadsRoot . DIRECTORY_SEPARATOR))
    || !str_starts_with(
        strtolower($rutaFisica),
        strtolower($uploadsRoot . DIRECTORY_SEPARATOR . 'formularios-validacion' . DIRECTORY_SEPARATOR)
    )
    || !is_file($rutaFisica)) {
    http_response_code(404);
    exit;
}

$nombreArchivo = basename((string) $documento['nombre_archivo']);
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Length: ' . (string) filesize($rutaFisica));
header('Content-Disposition: attachment; filename="' . addcslashes($nombreArchivo, '"\\') . '"; filename*=UTF-8\'\'' . rawurlencode($nombreArchivo));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($rutaFisica);
exit;
