<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once SERVICES_PATH . '/AuthService.php';
require_once MODELS_PATH . '/ResenasModel.php';

// Aseguré que solamente usuarios con el rol de practicante puedan acceder a este módulo - Alexander
auth_requiere_rol('PRACTICANTE');
start_secure_session();

$idPracticante = auth_practicante_actual_id();

// Consulté la práctica activa del estudiante logueado - Alexander
$stmt = db()->prepare(
    'SELECT id_practica FROM practicas 
     WHERE id_practicante = :id_practicante AND activo = 1 
     ORDER BY fecha_inicio_practica DESC, id_practica DESC LIMIT 1'
);
$stmt->execute(['id_practicante' => $idPracticante]);
$practica = $stmt->fetch();
$idPractica = $practica ? (int) $practica['id_practica'] : 0;

// Descarga segura de la plantilla oficial de autorización en PDF - Alexander

if (query_param('descargar') === 'plantilla') {
    $archivoPdf = APP_ROOT . '/resources/docs/autorizacion_resena_practica.pdf';
    if (is_file($archivoPdf)) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="Autorizacion_Resena_Estudiantes.pdf"');
        header('Content-Length: ' . (string) filesize($archivoPdf));
        header('Cache-Control: private, no-transform, no-store, must-revalidate');
        readfile($archivoPdf);
        exit;
    }
    http_response_code(404);
    exit('No se encontró la plantilla en el servidor.');
}


// Descarga o visualización segura del documento firmado ya subido por el propio estudiante - Alexander
if (query_param('descargar') === 'mi_archivo') {
    $resena = resenas_obtener_por_practica($idPractica);
    if ($resena && !empty($resena['archivo_autorizacion'])) {
        $rutaFisica = APP_ROOT . '/' . ltrim((string) $resena['archivo_autorizacion'], '/');
        if (is_file($rutaFisica)) {
            $mime = mime_content_type($rutaFisica) ?: 'application/pdf';
            header('Content-Type: ' . $mime);
            header('Content-Disposition: inline; filename="' . basename($rutaFisica) . '"');
            header('Content-Length: ' . (string) filesize($rutaFisica));
            readfile($rutaFisica);
            exit;
        }
    }
    http_response_code(404);
    exit('Documento no disponible.');
}

// Recuperé los mensajes temporales y limpié la sesión - Alexander
$mensajeExito = session_get('resena_exito');
$mensajeError = session_get('resena_error');
session_forget('resena_exito');
session_forget('resena_error');

// Procesé el envío del formulario cuando el estudiante pulsa "Enviar Reseña" - Alexander
if (is_post()) {
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');
    if (!csrf_validar($csrfToken)) {
        session_put('resena_error', 'La sesión expiró o la solicitud no es válida. Por favor, intenta de nuevo.');
        redirect_to('practicante/resenas.php');
    }

    if ($idPractica <= 0) {
        session_put('resena_error', 'No se encontró una práctica activa vinculada a tu cuenta.');
        redirect_to('practicante/resenas.php');
    }

    $calificacion = max(1, min(5, (int) ($_POST['calificacion'] ?? 5)));
    $aspectos = trim((string) ($_POST['aspectos_destacados'] ?? ''));
    $canal = trim((string) ($_POST['canal_envio'] ?? 'DOCUMENTO'));
    $testimonio = trim((string) ($_POST['testimonio'] ?? ''));
    $consentimiento = isset($_POST['consentimiento_legal']) ? 1 : 0;

    if ($testimonio === '') {
        session_put('resena_error', 'Por favor ingresa tu testimonio o reseña de la práctica.');
        redirect_to('practicante/resenas.php');
    }

    // Gestioné la subida segura del archivo de autorización firmado - Alexander
    $archivoRelativo = null;
    $resenaPrevia = resenas_obtener_por_practica($idPractica);
    if ($resenaPrevia && !empty($resenaPrevia['archivo_autorizacion'])) {
        $archivoRelativo = (string) $resenaPrevia['archivo_autorizacion'];
    }

    if (isset($_FILES['documento_firmado']) && $_FILES['documento_firmado']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['documento_firmado'];
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        $permitidos = ['pdf', 'png', 'jpg', 'jpeg'];

        if (!in_array($ext, $permitidos, true)) {
            session_put('resena_error', 'El formato del archivo no es permitido. Debe ser PDF o imagen.');
            redirect_to('practicante/resenas.php');
        }

        if ($file['size'] > 8 * 1024 * 1024) {
            session_put('resena_error', 'El archivo no puede superar los 8MB.');
            redirect_to('practicante/resenas.php');
        }

        $directorioDestino = APP_ROOT . '/uploads/resenas';
        if (!is_dir($directorioDestino)) {
            mkdir($directorioDestino, 0755, true);
        }

        $nombreLimpio = 'autorizacion_' . $idPracticante . '_' . $idPractica . '_' . time() . '.' . $ext;
        $rutaCompleta = $directorioDestino . '/' . $nombreLimpio;

        if (move_uploaded_file((string) $file['tmp_name'], $rutaCompleta)) {
            $archivoRelativo = 'uploads/resenas/' . $nombreLimpio;
        } else {
            session_put('resena_error', 'No se pudo guardar el archivo en el servidor. Intenta de nuevo.');
            redirect_to('practicante/resenas.php');
        }
    }

    // Guardé la información en la base de datos llamando a nuestro modelo - Alexander
    $guardado = resenas_guardar(
        $idPractica,
        $idPracticante,
        $calificacion,
        $aspectos,
        $canal,
        $testimonio,
        $archivoRelativo,
        $consentimiento
    );

    if ($guardado) {
        session_put('resena_exito', '¡Excelente! Tu reseña y autorización han sido recibidas exitosamente por Recursos Humanos.');
    } else {
        session_put('resena_error', 'Ocurrió un error al guardar tu reseña en la base de datos.');
    }

    redirect_to('practicante/resenas.php');
}

// Consulté si el estudiante ya tiene una reseña guardada para pre-llenar o mostrar estado - Alexander
$resenaActual = $idPractica > 0 ? resenas_obtener_por_practica($idPractica) : null;

$pageTitle = 'Recepción de Reseñas';

require SRC_PATH . '/Controllers/Shared/LayoutContext.php';
require_once VIEWS_PATH . '/practicante/resenas.view.php';
