<?php

declare(strict_types=1);


require_once dirname(__DIR__, 2) . '/bootstrap.php';
// Reemplacé el puente por su dependencia interna directa.
require_once SRC_PATH . '/Services/PermisosService.php';

/*
|--------------------------------------------------------------------------
| Permisos y Recuperación de Horas - SUPERVISOR
|--------------------------------------------------------------------------
|
| Permite a los supervisores revisar, aprobar o rechazar solicitudes de
| permisos enviadas por los practicantes, habilitando automáticamente los
| turnos de recuperación de horas correspondientes.
|
*/

auth_requiere_rol('SUPERVISOR');
$pageTitle = 'Gestión de Permisos';
// Agregué preparación compartida antes de emitir HTML o cabeceras del layout.
$rolEsperado = 'SUPERVISOR';
$rutaActual = 'supervisor/permisos.php';
require SRC_PATH . '/Controllers/Shared/PermisosController.php';

// Preparé los datos del layout antes de mostrar la vista.
require SRC_PATH . '/Controllers/Shared/LayoutContext.php';

require_once VIEWS_PATH . '/supervisor/permisos.view.php';
