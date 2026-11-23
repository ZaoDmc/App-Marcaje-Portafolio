<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| ActivarCuentaController — Controlador de activación inicial de cuenta
|--------------------------------------------------------------------------
|
| Permite validar un token de activación y definir la contraseña inicial del
| usuario. El token plano llega desde el enlace, se compara contra su hash y
| nunca se guarda ni se registra en auditoría.
|
*/

require_once dirname(__DIR__, 2) . '/bootstrap.php';

start_secure_session();

if (!function_exists('activar_cuenta_hash_token')) {
    function activar_cuenta_hash_token(string $token): string
    {
        // El hash debe coincidir con la estrategia usada al generar tokens.
        return hash('sha256', $token);
    }
}

if (!function_exists('activar_cuenta_token_valido')) {
    function activar_cuenta_token_valido(string $token): ?array
    {
        if ($token === '') {
            return null;
        }

        $statement = db()->prepare(
            'SELECT
                t.id_token,
                t.id_usuario,
                t.fecha_expiracion,
                u.email,
                u.estado_cuenta,
                u.activo,
                u.email_verificado
             FROM tokens_usuario t
             INNER JOIN usuarios_sistema u ON u.id_usuario = t.id_usuario
             WHERE t.tipo_token = :tipo_token
               AND t.token_hash = :token_hash
               AND t.usado = 0
               AND t.fecha_expiracion >= NOW()
             LIMIT 1'
        );
        $statement->execute([
            'tipo_token' => 'ACTIVACION_CUENTA',
            'token_hash' => activar_cuenta_hash_token($token),
        ]);

        $registro = $statement->fetch();

        if (!is_array($registro)) {
            return null;
        }

        if ((int) $registro['activo'] !== 1 || (string) $registro['estado_cuenta'] !== 'PENDIENTE_ACTIVACION') {
            return null;
        }

        return $registro;
    }
}

if (!function_exists('activar_cuenta_validar_password')) {
    function activar_cuenta_validar_password(string $password, string $confirmacion): array
    {
        $errores = [];

        if (strlen($password) < 8) {
            $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
        }

        if ($password !== $confirmacion) {
            $errores[] = 'La confirmación de contraseña no coincide.';
        }

        return $errores;
    }
}

if (!function_exists('activar_cuenta_aplicar')) {
    function activar_cuenta_aplicar(array $tokenRegistro, string $password): bool
    {
        $idUsuario = (int) $tokenRegistro['id_usuario'];
        $idToken = (int) $tokenRegistro['id_token'];
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        try {
            db()->beginTransaction();

            $updateUsuario = db()->prepare(
                'UPDATE usuarios_sistema
                 SET password_hash = :password_hash,
                     estado_cuenta = :estado_cuenta,
                     debe_cambiar_password = 0,
                     email_verificado = 1,
                     intentos_fallidos = 0,
                     ultimo_intento_login = NULL,
                     bloqueado_hasta = NULL
                 WHERE id_usuario = :id_usuario
                   AND estado_cuenta = :estado_actual'
            );
            $updateUsuario->execute([
                'password_hash' => $passwordHash,
                'estado_cuenta' => 'ACTIVO',
                'id_usuario' => $idUsuario,
                'estado_actual' => 'PENDIENTE_ACTIVACION',
            ]);

            if ($updateUsuario->rowCount() !== 1) {
                db()->rollBack();
                return false;
            }

            $updateToken = db()->prepare(
                'UPDATE tokens_usuario
                 SET usado = 1,
                     fecha_uso = NOW()
                 WHERE id_token = :id_token
                   AND usado = 0'
            );
            $updateToken->execute(['id_token' => $idToken]);

            if ($updateToken->rowCount() !== 1) {
                db()->rollBack();
                return false;
            }

            db()->commit();

            auditoria_registrar('PASSWORD_CAMBIADA', [
                'id_usuario_actor' => $idUsuario,
                'tabla_afectada' => 'usuarios_sistema',
                'id_registro_afectado' => $idUsuario,
                'valores_nuevos' => ['password_hash' => 'ACTUALIZADO'],
            ]);

            auditoria_registrar('CUENTA_ACTIVADA', [
                'id_usuario_actor' => $idUsuario,
                'tabla_afectada' => 'usuarios_sistema',
                'id_registro_afectado' => $idUsuario,
                'valores_anteriores' => ['estado_cuenta' => 'PENDIENTE_ACTIVACION'],
                'valores_nuevos' => ['estado_cuenta' => 'ACTIVO', 'email_verificado' => 1],
            ]);

            return true;
        } catch (Throwable $exception) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }

            error_log('Error al activar cuenta: ' . $exception->getMessage());

            return false;
        }
    }
}

$token = trim((string) query_param('token', ''));
$tokenRegistro = null;
$tokenError = null;
$formErrors = [];
$activated = false;
$csrfToken = $_SESSION['csrf_activar_cuenta'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_activar_cuenta'] = $csrfToken;

if ($token === '') {
    $tokenError = 'El enlace de activación no es válido o está incompleto.';
} else {
    try {
        $tokenRegistro = activar_cuenta_token_valido($token);

        if ($tokenRegistro === null) {
            $tokenError = 'El enlace de activación no es válido, ya fue usado o expiró.';
        }
    } catch (Throwable $exception) {
        error_log('Error al validar token de activación: ' . $exception->getMessage());
        $tokenError = 'No fue posible validar el enlace de activación en este momento.';
    }
}

if (is_post() && $tokenError === null && $tokenRegistro !== null) {
    $submittedToken = (string) input('csrf_token', '');
    $password = (string) input('password', '');
    $passwordConfirmacion = (string) input('password_confirmacion', '');

    // La validación CSRF protege el formulario; el token de activación protege el flujo.
    if (!hash_equals((string) $_SESSION['csrf_activar_cuenta'], $submittedToken)) {
        $formErrors[] = 'La solicitud no pudo validarse. Intenta nuevamente.';
    }

    $formErrors = array_merge($formErrors, activar_cuenta_validar_password($password, $passwordConfirmacion));

    if ($formErrors === []) {
        if (activar_cuenta_aplicar($tokenRegistro, $password)) {
            unset($_SESSION['csrf_activar_cuenta']);
            $activated = true;
        } else {
            $formErrors[] = 'No fue posible activar la cuenta. Solicita un nuevo enlace de activación.';
        }
    }

    $csrfToken = bin2hex(random_bytes(32));
    $_SESSION['csrf_activar_cuenta'] = $csrfToken;
}

require_once VIEWS_PATH . '/auth/activar-cuenta.view.php';
