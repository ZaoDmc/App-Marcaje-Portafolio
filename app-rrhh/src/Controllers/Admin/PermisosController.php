<?php

declare(strict_types=1);


require_once dirname(__DIR__, 2) . '/bootstrap.php';
// Reemplacé el puente por su dependencia interna directa.
require_once SRC_PATH . '/Services/PermisosService.php';

/*
|--------------------------------------------------------------------------
| Permisos y Recuperación de Horas - ADMINISTRADOR
|--------------------------------------------------------------------------
|
| Permite a los administradores revisar, aprobar o rechazar solicitudes de
| permisos de practicantes de forma global.
|
*/

auth_requiere_rol('ADMIN');
$pageTitle = 'Gestión de Permisos';
// Agregué preparación compartida antes de emitir HTML o cabeceras del layout.
$rolEsperado = 'ADMIN';
$rutaActual = 'admin/permisos.php';
require SRC_PATH . '/Controllers/Shared/PermisosController.php';

// Preparé los datos del layout antes de mostrar la vista.
require SRC_PATH . '/Controllers/Shared/LayoutContext.php';

require_once VIEWS_PATH . '/admin/permisos.view.php';
