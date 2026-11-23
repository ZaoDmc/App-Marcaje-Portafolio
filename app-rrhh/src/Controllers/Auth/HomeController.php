<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';

// Trasladé el acceso inicial de index.php para conservar el destino por sesión
// y rol sin que el enrutador vuelva a incluirse recursivamente.
start_secure_session();
if (auth_check()) {
    $rol = auth_rol_activo();
    if ($rol !== null) {
        $destino = auth_destino_por_rol($rol);
        if ($destino !== '' && $destino !== 'login.php' && $destino !== 'index.php') {
            redirect_to($destino);
        }
    }
}
redirect_to('login.php');
