<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| DashboardController — Panel principal del PRACTICANTE
|--------------------------------------------------------------------------
|
| Expone accesos a las secciones del practicante y restringe acceso solo
| a usuarios con rol activo PRACTICANTE.
|
*/

require_once dirname(__DIR__, 2) . '/bootstrap.php';

auth_requiere_rol('PRACTICANTE');

$pageTitle = 'Panel practicante';

// Preparé los datos del layout antes de mostrar la vista.
require SRC_PATH . '/Controllers/Shared/LayoutContext.php';

require_once VIEWS_PATH . '/practicante/dashboard.view.php';
