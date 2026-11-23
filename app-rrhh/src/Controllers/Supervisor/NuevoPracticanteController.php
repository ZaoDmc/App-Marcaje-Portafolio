<?php

declare(strict_types=1);


require_once dirname(__DIR__, 2) . '/bootstrap.php';
// Reemplacé el puente por su dependencia interna directa.
require_once SRC_PATH . '/Services/AuthService.php';

auth_requiere_rol('SUPERVISOR');

$pageTitle = 'Nuevo practicante';

// Preparé los datos del layout antes de mostrar la vista.
require SRC_PATH . '/Controllers/Shared/LayoutContext.php';

require_once VIEWS_PATH . '/supervisor/nuevo-practicante.view.php';
