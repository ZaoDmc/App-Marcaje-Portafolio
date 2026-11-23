<?php

declare(strict_types=1);


require_once dirname(__DIR__, 2) . '/bootstrap.php';
// Reemplacé el puente por su dependencia interna directa.
require_once SRC_PATH . '/Services/CargaMasivaService.php';

practicante_carga_masiva_servir_plantilla_si_corresponde('SUPERVISOR');

$pageTitle = 'Carga masiva de practicantes';

// Agregué preparación compartida antes de emitir HTML o cabeceras del layout.
$rolPermitido = 'SUPERVISOR';
$actionPath = 'supervisor/practicantes-carga-masiva.php';
$dashboardPath = 'supervisor/nuevo-practicante.php';
require SRC_PATH . '/Controllers/Shared/CargaMasivaController.php';

// Preparé los datos del layout antes de mostrar la vista.
require SRC_PATH . '/Controllers/Shared/LayoutContext.php';

require_once VIEWS_PATH . '/supervisor/practicantes-carga-masiva.view.php';
