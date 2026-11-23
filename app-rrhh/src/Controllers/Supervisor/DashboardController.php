<?php

declare(strict_types=1);


require_once dirname(__DIR__, 2) . '/bootstrap.php';
// Reemplacé el puente por su dependencia interna directa.
require_once SRC_PATH . '/Services/AuthService.php';
// Reemplacé el puente por su dependencia interna directa.
require_once SRC_PATH . '/Models/MetricasModel.php';

/*
|--------------------------------------------------------------------------
| Dashboard SUPERVISOR
|--------------------------------------------------------------------------
|
| Panel base para supervisores. Permite entrar a las funciones operativas
| autorizadas sin exponer acciones reservadas al rol ADMIN.
|
*/

auth_requiere_rol('SUPERVISOR');

$dashboardErrores = [];
$metricas = [
    'practicantes_activos' => 0,
    'practicas_activas' => 0,
    'horas_30_dias' => 0,
    'pendientes_revision' => 0,
    'ausencias_esperadas' => 0,
    'avance_global' => 0,
];

try {
    $metricas = dashboard_metricas_resumen('SUPERVISOR');
} catch (Throwable $exception) {
    error_log('Error al cargar metricas del dashboard SUPERVISOR: ' . $exception->getMessage());
    $dashboardErrores[] = 'No fue posible cargar las metricas del dashboard.';
}
$pageTitle = 'Panel supervisor';
// Preparé los datos del layout antes de mostrar la vista.
require SRC_PATH . '/Controllers/Shared/LayoutContext.php';

require_once VIEWS_PATH . '/supervisor/dashboard.view.php';
