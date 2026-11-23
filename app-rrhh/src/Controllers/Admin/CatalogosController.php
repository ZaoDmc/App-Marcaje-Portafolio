<?php

declare(strict_types=1);


require_once dirname(__DIR__, 2) . '/bootstrap.php';
// Reemplacé el puente por su dependencia interna directa.
require_once SRC_PATH . '/Models/CatalogoModel.php';

auth_requiere_rol('ADMIN');

$pageTitle = 'Catálogos';
// Agregué preparación compartida antes de emitir HTML o cabeceras del layout.
require SRC_PATH . '/Controllers/Shared/CatalogosController.php';

// Preparé los datos del layout antes de mostrar la vista.
require SRC_PATH . '/Controllers/Shared/LayoutContext.php';

require_once VIEWS_PATH . '/admin/catalogos.view.php';
