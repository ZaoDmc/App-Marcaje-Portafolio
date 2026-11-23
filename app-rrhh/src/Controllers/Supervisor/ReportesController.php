<?php

declare(strict_types=1);


require_once dirname(__DIR__, 2) . '/bootstrap.php';
// Reemplacé el puente por su dependencia interna directa.
require_once SRC_PATH . '/Services/AuthService.php';
// Reemplacé el puente por su dependencia interna directa.
require_once SRC_PATH . '/Services/ReportesService.php';
$pageTitle = 'Reportes basicos';

// Agregué preparación compartida antes de emitir HTML o cabeceras del layout.
$rolEsperado = 'SUPERVISOR';
$titulo = 'Reportes basicos';
$rutaDashboard = 'supervisor/dashboard.php';
$rutaActual = 'supervisor/reportes.php';
require SRC_PATH . '/Controllers/Shared/ReportesController.php';

// Preparé los datos del layout antes de mostrar la vista.
require SRC_PATH . '/Controllers/Shared/LayoutContext.php';

require_once VIEWS_PATH . '/supervisor/reportes.view.php';
