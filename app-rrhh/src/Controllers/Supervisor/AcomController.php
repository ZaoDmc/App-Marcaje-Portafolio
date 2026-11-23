<?php

declare(strict_types=1);


require_once dirname(__DIR__, 2) . '/bootstrap.php';
// Reemplacé el puente por su dependencia interna directa.
require_once SRC_PATH . '/Services/AuthService.php';
// Reemplacé el puente por su dependencia interna directa.
require_once SRC_PATH . '/Models/AcompanamientoModel.php';
// Incorporé el mismo resumen publicado que utiliza la ficha y el reporte, sin abrir permisos de edición.
require_once SRC_PATH . '/Services/ResumenNotasService.php';

auth_requiere_rol(['ADMIN', 'SUPERVISOR']);
// Evité almacenar en cachés compartidas el resumen académico autenticado.
header('Cache-Control: private, no-store');

$errores = [];
$busqueda = trim((string) query_param('q', ''));
$vistaActiva = (string) query_param('vista', 'acompanamiento');

$etiquetasVista = [
    'acompanamiento' => 'General',
    'legales' => 'Legales',
    'capacitaciones' => 'Capacitaciones',
    'monitoreo' => 'Monitoreo',
];
$etiquetaVistaActiva = $etiquetasVista[$vistaActiva] ?? 'Acompañamiento';

if (is_post()) {
    $esAjax = (strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest');

    // Respuesta JSON de error para peticiones AJAX fallidas en CSRF
    if (!hash_equals(acom_gestion_token(), (string) ($_POST['csrf_token'] ?? ''))) {
        if ($esAjax) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(419);
            echo json_encode(['ok' => false, 'error' => 'La sesión del formulario expiró. Intenta nuevamente.']);
            exit;
        }
        $errores[] = 'La sesión del formulario expiró. Intenta nuevamente.';
    } else {
        // Conservé la clave antigua de evaluación para rechazar explícitamente formularios previos desde el modelo.
        $campoFormulario = [
            'evaluacion' => 'puntaje_evaluacion',
            'evaluacion_enviada' => 'evaluacion_enviada',
            'linkedin_operativo' => 'linkedin_operativo',
            'resena_recibida' => 'resena_recibida',
            'resena_publicada' => 'resena_publicada',
            'documento_entregado' => 'documento_entregado',
            'capacitacion_realizada' => 'capacitacion_realizada',
'certificado_entregado' => 'certificado_entregado',
        ];

        // Recolectar ids de practica presentes en cualquier control enviado
        $idsPost = [];
        foreach (array_keys($campoFormulario) as $clave) {
            if (isset($_POST[$clave]) && is_array($_POST[$clave])) {
                foreach (array_keys($_POST[$clave]) as $id) {
                    $idsPost[(int) $id] = true;
                }
            }
        }

        $guardados = 0;
        $erroresAjax = [];

        foreach (array_keys($idsPost) as $idPractica) {
            if ($idPractica <= 0) {
                continue;
            }

            $datos = [];
            foreach ($campoFormulario as $clave => $campo) {
                if (!isset($_POST[$clave]) || !is_array($_POST[$clave])) {
                    continue;
                }

                $valor = $_POST[$clave][$idPractica] ?? null;

                if ($campo === 'puntaje_evaluacion') {
                    $datos[$campo] = ($valor === '' || $valor === null)
                        ? null
                        : max(0, min(7, (float) $valor));
                } else {
                    $datos[$campo] = ($valor === '1' || $valor === 1 || $valor === true) ? 1 : 0;
                }
            }

            if ($datos === []) {
                continue;
            }

            try {
                acom_gestion_guardar($idPractica, $datos);
                $guardados++;
            } catch (Throwable $exception) {
                error_log('Error al guardar acompañamiento: ' . $exception->getMessage());
                $erroresAjax[] = $exception instanceof DomainException
                    ? $exception->getMessage() : 'No fue posible guardar los cambios de un practicante.';
            }
        }

        if ($esAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                // Cambié el resultado para no informar éxito total cuando
                // alguna práctica fue rechazada o no pudo guardarse.
                'ok' => $guardados > 0 && $erroresAjax === [],
                'parcial' => $guardados > 0 && $erroresAjax !== [],
                'guardados' => $guardados,
                // Devolví también el mensaje que entiende el cliente AJAX antiguo cuando se rechaza un puntaje histórico.
                'error' => $erroresAjax === [] ? null : implode(' ', array_unique($erroresAjax)),
                'errores' => array_values(array_unique($erroresAjax)),
            ]);
            exit;
        }

        if ($guardados > 0 && $erroresAjax === []) {
            $mensajeExito = 'Cambios de acompañamiento guardados correctamente.';
        } else {
            foreach ($erroresAjax as $err) {
                $errores[] = $err;
            }
        }
    }
}

$token = acom_gestion_token();

try {
    $practicantes = acom_gestion_listar_practicantes($busqueda);
} catch (Throwable $exception) {
    error_log('Error al cargar practicantes en acompañamiento: ' . $exception->getMessage());
    $practicantes = [];
    $errores[] = 'No fue posible cargar la lista de practicantes.';
}

// Separé el fallo de notas del seguimiento operativo para no ocultar la lista ni presentar un cero ficticio.
$resumenNotas = [];
try {
    $resumenNotas = notas_resumen_supervision(array_map('intval', array_column($practicantes, 'id_practica')));
} catch (Throwable $exception) {
    error_log('No fue posible cargar el resumen de notas de acompañamiento: ' . $exception->getMessage());
}
$pageTitle = 'Acompañamiento';

// Preparé los datos del layout antes de mostrar la vista.
require SRC_PATH . '/Controllers/Shared/LayoutContext.php';

require_once VIEWS_PATH . '/supervisor/acom.view.php';
