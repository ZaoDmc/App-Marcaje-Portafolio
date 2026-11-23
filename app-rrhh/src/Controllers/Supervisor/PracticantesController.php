<?php

declare(strict_types=1);


require_once dirname(__DIR__, 2) . '/bootstrap.php';
// Reemplacé el puente por su dependencia interna directa.
require_once SRC_PATH . '/Services/AuthService.php';
// Reemplacé el puente por su dependencia interna directa.
require_once SRC_PATH . '/Services/HorariosPracticanteService.php';
$pageTitle = 'Practicantes y horarios';

// Agregué preparación compartida antes de emitir HTML o cabeceras del layout.
$rolPermitido = 'SUPERVISOR';
$titulo = 'Practicantes y horarios';
$dashboardPath = 'supervisor/dashboard.php';
$actionPath = 'supervisor/practicantes.php';
require SRC_PATH . '/Controllers/Shared/PracticantesController.php';

// Preparé los datos del layout antes de mostrar la vista.
require SRC_PATH . '/Controllers/Shared/LayoutContext.php';

require_once VIEWS_PATH . '/supervisor/practicantes.view.php';
