<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| LoginController — Controlador de inicio de sesión
|--------------------------------------------------------------------------
|
| Responsabilidades:
|   1. Verificar si el usuario ya tiene sesión activa (redirigir si es así).
|   2. Procesar el formulario POST con credenciales.
|   3. Gestionar el token CSRF.
|   4. Preparar variables de estado ($errors, $email, $csrfToken).
|   5. Cargar la vista correspondiente.
|
| Este controlador NO genera HTML. Solo trabaja con datos y lógica.
|
*/

require_once dirname(__DIR__, 2) . '/bootstrap.php';

start_secure_session();

// Si ya está autenticado, redirigir directamente a su panel
if (auth_check()) {
    $destino = auth_destino_por_rol((string) auth_rol_activo());
    if ($destino !== '' && $destino !== 'login.php' && $destino !== 'index.php') {
        redirect_to($destino);
    }
}

// Estado inicial de la vista
$errors        = [];
$email         = '';
$mantenerSesion = false;
$csrfToken     = $_SESSION['csrf_login'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_login'] = $csrfToken;

// Procesar formulario POST
if (is_post()) {
    $email          = trim((string) input('email', ''));
    $password       = (string) input('password', '');
    $mantenerSesion = input('mantener_sesion') === '1';
    $submittedToken = (string) input('csrf_token', '');

    // Validación de token CSRF
    if (!hash_equals((string) $_SESSION['csrf_login'], $submittedToken)) {
        $errors[] = 'La solicitud no pudo validarse. Intenta nuevamente.';
    }

    // Validación de campos
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Ingresa un correo válido.';
    }
    if ($password === '') {
        $errors[] = 'Ingresa tu contraseña.';
    }

    // Intentar autenticación solo si no hay errores de validación
    if ($errors === []) {
        try {
            $resultado = auth_intentar_login($email, $password, $mantenerSesion);

            if ($resultado['ok'] === true) {
                unset($_SESSION['csrf_login']);
                redirect_to((string) $resultado['redirect']);
            }

            $errors[] = (string) ($resultado['mensaje'] ?? 'No fue posible iniciar sesión.');
        } catch (Throwable $exception) {
            error_log('Error en login: ' . $exception->getMessage());
            $errors[] = 'No fue posible procesar el inicio de sesión en este momento.';
        }
    }

    // Regenerar token para evitar reenvíos accidentales tras un error
    $csrfToken = bin2hex(random_bytes(32));
    $_SESSION['csrf_login'] = $csrfToken;
}

// Cargar la vista — el Controller termina aquí, pasa las variables a la vista
require_once VIEWS_PATH . '/auth/login.view.php';
