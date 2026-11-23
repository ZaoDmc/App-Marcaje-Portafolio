<?php

declare(strict_types=1);


require_once dirname(__DIR__, 2) . '/bootstrap.php';
// Reemplacé el puente por su dependencia interna directa.
require_once SRC_PATH . '/Models/PracticanteModel.php';
$pageTitle = 'Crear practicante';

// Agregué preparación compartida antes de emitir HTML o cabeceras del layout.
$rolPermitido = 'ADMIN';
$titulo = 'Crear practicante';
$actionPath = 'admin/crear-practicante.php';
$dashboardPath = 'admin/nuevo-practicante.php';
require SRC_PATH . '/Controllers/Shared/CrearPracticanteController.php';

// Preparé los datos del layout antes de mostrar la vista.
require SRC_PATH . '/Controllers/Shared/LayoutContext.php';

require_once VIEWS_PATH . '/admin/crear-practicante.view.php';
