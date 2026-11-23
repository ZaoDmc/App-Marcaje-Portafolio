<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| LogoutController — Controlador de cierre de sesión
|--------------------------------------------------------------------------
|
| Cierra tanto sesiones operativas con rol activo como sesiones temporales de
| selección de rol. La lógica de auditoría, actualización de sesiones_usuario
| y destrucción de la sesión PHP vive en auth_logout().
|
*/

require_once dirname(__DIR__, 2) . '/bootstrap.php';

auth_logout();
redirect_to('login.php?logout=1');
