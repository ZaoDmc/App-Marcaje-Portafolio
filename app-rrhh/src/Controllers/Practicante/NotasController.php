<?php
declare(strict_types=1);
// Agregué entrada de líderes en contexto PRACTICANTE, sin conceder facultades de SUPERVISOR.
require_once dirname(__DIR__,2).'/bootstrap.php';
auth_requiere_rol('PRACTICANTE');
require SRC_PATH.'/Controllers/Shared/NotasEvaluacionesController.php';
