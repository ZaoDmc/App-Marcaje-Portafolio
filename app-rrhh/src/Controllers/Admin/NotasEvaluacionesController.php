<?php
declare(strict_types=1);
// Agregué entrada ADMIN para evaluaciones; la autorización de cada práctica permanece en el servicio.
require_once dirname(__DIR__,2).'/bootstrap.php';
auth_requiere_rol('ADMIN');
require SRC_PATH.'/Controllers/Shared/NotasEvaluacionesController.php';
