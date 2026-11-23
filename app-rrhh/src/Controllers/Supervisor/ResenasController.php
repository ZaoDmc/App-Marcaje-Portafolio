<?php

declare(strict_types= 1);

require_once dirname(__DIR__,2) . '/bootstrap.php';
require_once SERVICES_PATH .'/AuthService.php';
require_once MODELS_PATH .'/ResenasModel.php';


// Aseguré que solo supervisores y administradores puedan entrar a gestionar reseñas - Alexander
auth_requiere_rol(['ADMIN','SUPERVISOR']);
start_secure_session();

// Aqui se visualizara o descargara el documento firmado, subido por el estudiante - Alexander
$idDescarga = (int) query_param('descargar_pdf', 0);
if ($idDescarga > 0) {
    $resena = resenas_obtener_por_id($idDescarga);
    if ($resena && !empty ($resena['archivo_autorizacion'])) {
        $rutaFisica = APP_ROOT . '/' . ltrim((string) $resena['archivo_autorizacion'],'/');

    if(is_file($rutaFisica)) {
    $mime = mime_content_type($rutaFisica) ?: 'application/pdf';
    header('Content-Type: ' . $mime);
    header('Content-Disposition: inline; filename="' . basename($rutaFisica) . '"');
        header('Content-Length: ' . (string) filesize($rutaFisica));
    header('Cache-Control: private, no-transform, no-store, must-revalidate');
    readfile($rutaFisica);
    exit;
    }
}
http_response_code(404);
exit('Documento de autorización no encontrado.');
}


// Procesé el cambio de estado cuando el supervisor aprueba o publica una reseña anteriormente hecha - Alexander
if (is_post()) {
    $csrfToken = (string) ($_POST['csrf_token'] ??'');
    if (!csrf_validar($csrfToken)) {
        session_put('supervisor_resena_error' , 'Solicitud no valida o sesion expirada');
        redirect_to('supervisor/resenas.php');
    }

    $idResena = (int) ($_POST['id_resena'] ??0);
    $nuevoEstado = trim ((string) ($_POST['nuevo_estado'] ?? '')); 
 
    if ($idResena > 0 && in_array($nuevoEstado, ['PUBLICADA', 'RECIBIDA'], true)) {
        $idSupervisor = (int) (session_get('id_usuario') ?? 0);
        $actualizado = resenas_cambiar_estado($idResena, $nuevoEstado, $idSupervisor > 0 ? $idSupervisor : null);

        if ($actualizado) {
            session_put('supervisor_resena_exito', 'La reseña fue actualizada a estado "' . $nuevoEstado . '" exitosamente.');
            } else {
                session_put('supervisor_resena_error', 'No se pudo actualizar el estado de la reseña.');

            }
    }

    redirect_to('supervisor/resenas.php');
}

// Se construyen mensajes de alerta, en cuanto a la sesión - Alexander
$mensajeExito = session_get('supervisor_resena_exito');
$mensajeError = session_get('supervisor_resena_error');
session_forget('supervisor_resena_exito');
session_forget('supervisor_resena_error');

// Se consultan las metricas reales y la lista completa de practicantes desde el modelo nuestro - Alexander

$metricas = resenas_obtener_metricas();
$practicantes = resenas_listar_para_supervisor();

$pageTitle = 'Recepción y Gestión de Reseñas';

require SRC_PATH .'/Controllers/Shared/LayoutContext.php';
require_once VIEWS_PATH .'/supervisor/resenas.view.php';
