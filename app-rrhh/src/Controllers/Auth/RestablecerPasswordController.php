<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| RestablecerPasswordController — Controlador de restablecimiento
|--------------------------------------------------------------------------
|
| Valida tokens de recuperación y permite definir una nueva contraseña.
| El token plano solo viaja en el enlace, se compara contra token_hash y se
| invalida después de usarse correctamente.
|
*/

require_once dirname(__DIR__, 2) . '/bootstrap.php';

start_secure_session();

if (!function_exists('restablecer_password_hash_token')) {
    function restablecer_password_hash_token(string $token): string
    {
        // Debe coincidir con la estrategia usada al generar RECUPERACION_PASSWORD.
        return hash('sha256', $token);
    }
}

if (!function_exists('restablecer_password_token_valido')) {
    function restablecer_password_token_valido(string $token): ?array
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
                u.activo
             FROM tokens_usuario t
             INNER JOIN usuarios_sistema u ON u.id_usuario = t.id_usuario
             WHERE t.tipo_token = :tipo_token
               AND t.token_hash = :token_hash
               AND t.usado = 0
               AND t.fecha_expiracion >= NOW()
             LIMIT 1'
        );
        $statement->execute([
            'tipo_token' => 'RECUPERACION_PASSWORD',
            'token_hash' => restablecer_password_hash_token($token),
        ]);

        $registro = $statement->fetch();

        if (!is_array($registro)) {
            return null;
        }

        if ((int) $registro['activo'] !== 1 || !in_array((string) $registro['estado_cuenta'], ['ACTIVO', 'BLOQUEADO'], true)) {
            return null;
        }

        return $registro;
    }
}

if (!function_exists('restablecer_password_validar_password')) {
    function restablecer_password_validar_password(string $password, string $confirmacion): array
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

if (!function_exists('restablecer_password_aplicar')) {
    function restablecer_password_aplicar(array $tokenRegistro, string $password): bool
    {
        $idUsuario = (int) $tokenRegistro['id_usuario'];
        $idToken = (int) $tokenRegistro['id_token'];
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        try {
            db()->beginTransaction();

            $updateUsuario = db()->prepare(
                'UPDATE usuarios_sistema
                 SET password_hash = :password_hash,
                     debe_cambiar_password = 0,
                     intentos_fallidos = 0,
                     ultimo_intento_login = NULL,
                     bloqueado_hasta = NULL,
                     estado_cuenta = :nuevo_estado
                 WHERE id_usuario = :id_usuario
                   AND activo = 1'
            );
            $updateUsuario->execute([
                'password_hash' => $passwordHash,
                'nuevo_estado' => 'ACTIVO',
                'id_usuario' => $idUsuario,
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

            $invalidarOtros = db()->prepare(
                'UPDATE tokens_usuario
                 SET usado = 1,
                     fecha_uso = COALESCE(fecha_uso, NOW())
                 WHERE id_usuario = :id_usuario
                   AND tipo_token = :tipo_token
                   AND usado = 0'
            );
            $invalidarOtros->execute([
                'id_usuario' => $idUsuario,
                'tipo_token' => 'RECUPERACION_PASSWORD',
            ]);

            db()->commit();

            auditoria_registrar('PASSWORD_CAMBIADA', [
                'id_usuario_actor' => $idUsuario,
                'tabla_afectada' => 'usuarios_sistema',
                'id_registro_afectado' => $idUsuario,
                'valores_nuevos' => ['password_hash' => 'ACTUALIZADO'],
            ]);

            return true;
        } catch (Throwable $exception) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }

            error_log('Error al restablecer contraseña: ' . $exception->getMessage());

            return false;
        }
    }
}

$token = trim((string) query_param('token', ''));
$tokenRegistro = null;
$tokenError = null;
$formErrors = [];
$passwordChanged = false;
$csrfToken = $_SESSION['csrf_restablecer_password'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_restablecer_password'] = $csrfToken;

if ($token === '') {
    $tokenError = 'El enlace de restablecimiento no es válido o está incompleto.';
} else {
    try {
        $tokenRegistro = restablecer_password_token_valido($token);

        if ($tokenRegistro === null) {
            $tokenError = 'El enlace de restablecimiento no es válido, ya fue usado o expiró.';
        }
    } catch (Throwable $exception) {
        error_log('Error al validar token de recuperación: ' . $exception->getMessage());
        $tokenError = 'No fue posible validar el enlace de restablecimiento en este momento.';
    }
}

if (is_post() && $tokenError === null && $tokenRegistro !== null) {
    $submittedToken = (string) input('csrf_token', '');
    $password = (string) input('password', '');
    $passwordConfirmacion = (string) input('password_confirmacion', '');

    // CSRF protege el formulario; el token de URL protege el flujo de recuperación.
    if (!hash_equals((string) $_SESSION['csrf_restablecer_password'], $submittedToken)) {
        $formErrors[] = 'La solicitud no pudo validarse. Intenta nuevamente.';
    }

    $formErrors = array_merge($formErrors, restablecer_password_validar_password($password, $passwordConfirmacion));

    if ($formErrors === []) {
        if (restablecer_password_aplicar($tokenRegistro, $password)) {
            unset($_SESSION['csrf_restablecer_password']);
            $passwordChanged = true;
        } else {
            $formErrors[] = 'No fue posible restablecer la contraseña. Solicita un nuevo enlace.';
        }
    }

    $csrfToken = bin2hex(random_bytes(32));
    $_SESSION['csrf_restablecer_password'] = $csrfToken;
}

require_once VIEWS_PATH . '/auth/restablecer-password.view.php';
