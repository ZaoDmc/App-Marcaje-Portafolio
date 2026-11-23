<?php

declare(strict_types=1);


require_once dirname(__DIR__, 2) . '/bootstrap.php';
// Reemplacé el puente por su dependencia interna directa.
require_once SRC_PATH . '/Services/AuthService.php';
// Reemplacé el puente por su dependencia interna directa.
require_once SRC_PATH . '/Services/AsistenciaService.php';
$pageTitle = 'Revision de asistencia';

// Agregué preparación compartida antes de emitir HTML o cabeceras del layout.
$rolEsperado = 'SUPERVISOR';
$titulo = 'Revision de asistencia';
$rutaDashboard = 'supervisor/dashboard.php';
$rutaActual = 'supervisor/asistencia.php';
require SRC_PATH . '/Controllers/Shared/AsistenciaController.php';

// Preparé los datos del layout antes de mostrar la vista.
require SRC_PATH . '/Controllers/Shared/LayoutContext.php';

require_once VIEWS_PATH . '/supervisor/asistencia.view.php';
