<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SeleccionarRolController — Controlador para selección de rol activo
|--------------------------------------------------------------------------
|
| Esta pantalla solo se usa después de un login multirrol. Mientras el usuario
| no elige contexto, la sesión PHP es temporal y no existe aún una fila
| operativa en sesiones_usuario.
|
*/

require_once dirname(__DIR__, 2) . '/bootstrap.php';

start_secure_session();

if (auth_check()) {
    redirect_to(auth_destino_por_rol((string) auth_rol_activo()));
}

$rolesDisponibles = $_SESSION['roles_disponibles'] ?? [];
$usuarioPendienteRol = $_SESSION['usuario_pendiente_rol'] ?? null;

if (!is_array($rolesDisponibles) || $rolesDisponibles === [] || $usuarioPendienteRol === null) {
    redirect_to('login.php');
}

$errors = [];
$csrfToken = $_SESSION['csrf_seleccionar_rol'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_seleccionar_rol'] = $csrfToken;

if (!function_exists('selector_rol_detalle')) {
    function selector_rol_detalle(string $rol): array
    {
        return match ($rol) {
            'ADMIN' => [
                'titulo' => 'Administrador',
                'descripcion' => 'Gestión global, usuarios, catálogos y auditoría.',
            ],
            'SUPERVISOR' => [
                'titulo' => 'Supervisor',
                'descripcion' => 'Seguimiento operativo de practicantes y asistencia.',
            ],
            'PRACTICANTE' => [
                'titulo' => 'Practicante',
                'descripcion' => 'Registro de asistencia, datos propios e historial.',
            ],
            default => [
                'titulo' => $rol,
                'descripcion' => 'Acceso según permisos asignados.',
            ],
        };
    }
}

if (is_post()) {
    $submittedToken = (string) input('csrf_token', '');
    $idRol = (string) input('id_rol', '');

    // El selector valida CSRF y que el rol venga desde roles_disponibles.
    if (!hash_equals((string) $_SESSION['csrf_seleccionar_rol'], $submittedToken)) {
        $errors[] = 'La solicitud no pudo validarse. Intenta nuevamente.';
    }

    if ($idRol === '' || !ctype_digit($idRol)) {
        $errors[] = 'Selecciona un rol válido.';
    }

    if ($errors === []) {
        try {
            $rolElegido = auth_rol_en_lista($rolesDisponibles, (int) $idRol);

            if ($rolElegido === null) {
                $errors[] = 'El rol seleccionado no está disponible para esta cuenta.';
            } elseif (auth_establecer_rol_activo((int) $idRol)) {
                unset($_SESSION['csrf_seleccionar_rol']);
                redirect_to(auth_destino_por_rol((string) $rolElegido['nombre']));
            } else {
                $errors[] = 'No fue posible activar el rol seleccionado.';
            }
        } catch (Throwable $exception) {
            error_log('Error al seleccionar rol activo: ' . $exception->getMessage());
            $errors[] = 'No fue posible activar el rol seleccionado en este momento.';
        }
    }

    // Se regenera el token si hubo error para evitar reenvíos accidentales.
    $csrfToken = bin2hex(random_bytes(32));
    $_SESSION['csrf_seleccionar_rol'] = $csrfToken;
}

require_once VIEWS_PATH . '/auth/seleccionar-rol.view.php';
