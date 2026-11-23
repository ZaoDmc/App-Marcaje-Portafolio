<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| RecuperarPasswordController — Controlador de solicitud de recuperación
|--------------------------------------------------------------------------
|
| Genera un token temporal de recuperación cuando el correo corresponde a una
| cuenta válida. La respuesta pública siempre es genérica para no revelar si un
| email existe o no dentro del sistema.
|
*/

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once SERVICES_PATH . '/MailService.php';

start_secure_session();

if (!function_exists('recuperar_password_respuesta_generica')) {
    function recuperar_password_respuesta_generica(): string
    {
        return 'Si el correo existe, se enviaron instrucciones para restablecer la contraseña.';
    }
}

if (!function_exists('recuperar_password_generar_token')) {
    function recuperar_password_generar_token(): string
    {
        // El token plano solo se entrega al enlace de correo; en BBDD se guarda su hash.
        return bin2hex(random_bytes(32));
    }
}

if (!function_exists('recuperar_password_hash_token')) {
    function recuperar_password_hash_token(string $token): string
    {
        return hash('sha256', $token);
    }
}

if (!function_exists('recuperar_password_usuario_por_email')) {
    function recuperar_password_usuario_por_email(string $email): ?array
    {
        $statement = db()->prepare(
            'SELECT id_usuario, email, estado_cuenta, activo, email_verificado
             FROM usuarios_sistema
             WHERE email = :email
             LIMIT 1'
        );
        $statement->execute(['email' => strtolower(trim($email))]);

        $usuario = $statement->fetch();

        if (!is_array($usuario)) {
            return null;
        }

        if ((int) $usuario['activo'] !== 1 || !in_array((string) $usuario['estado_cuenta'], ['ACTIVO', 'BLOQUEADO'], true)) {
            return null;
        }

        return $usuario;
    }
}

if (!function_exists('recuperar_password_invalidar_tokens_previos')) {
    function recuperar_password_invalidar_tokens_previos(int $idUsuario): void
    {
        $statement = db()->prepare(
            'UPDATE tokens_usuario
             SET usado = 1,
                 fecha_uso = COALESCE(fecha_uso, NOW())
             WHERE id_usuario = :id_usuario
               AND tipo_token = :tipo_token
               AND usado = 0'
        );
        $statement->execute([
            'id_usuario' => $idUsuario,
            'tipo_token' => 'RECUPERACION_PASSWORD',
        ]);
    }
}

if (!function_exists('recuperar_password_crear_token')) {
    function recuperar_password_crear_token(array $usuario): ?string
    {
        $tokenPlano = recuperar_password_generar_token();

        try {
            db()->beginTransaction();

            recuperar_password_invalidar_tokens_previos((int) $usuario['id_usuario']);

            $statement = db()->prepare(
                'INSERT INTO tokens_usuario (
                    id_usuario,
                    tipo_token,
                    token_hash,
                    fecha_expiracion
                 ) VALUES (
                    :id_usuario,
                    :tipo_token,
                    :token_hash,
                    DATE_ADD(NOW(), INTERVAL :horas_expiracion HOUR)
                 )'
            );
            $statement->execute([
                'id_usuario' => (int) $usuario['id_usuario'],
                'tipo_token' => 'RECUPERACION_PASSWORD',
                'token_hash' => recuperar_password_hash_token($tokenPlano),
                'horas_expiracion' => TOKEN_EXPIRATION_HOURS,
            ]);

            $idToken = (int) db()->lastInsertId();
            db()->commit();

            auditoria_registrar('TOKEN_GENERADO', [
                'id_usuario_actor' => (int) $usuario['id_usuario'],
                'tabla_afectada' => 'tokens_usuario',
                'id_registro_afectado' => $idToken,
                'valores_nuevos' => [
                    'tipo_token' => 'RECUPERACION_PASSWORD',
                    'fecha_expiracion_horas' => TOKEN_EXPIRATION_HOURS,
                ],
            ]);

            return $tokenPlano;
        } catch (Throwable $exception) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }

            error_log('Error al crear token de recuperación: ' . $exception->getMessage());

            return null;
        }
    }
}

if (!function_exists('recuperar_password_procesar')) {
    function recuperar_password_procesar(string $email): void
    {
        try {
            $usuario = recuperar_password_usuario_por_email($email);

            auditoria_registrar('RECUPERACION_SOLICITADA', [
                'tabla_afectada' => 'usuarios_sistema',
                'id_registro_afectado' => is_array($usuario) ? (int) $usuario['id_usuario'] : null,
                'valores_nuevos' => ['email_solicitado' => strtolower(trim($email))],
            ]);

            if ($usuario === null) {
                return;
            }

            $tokenPlano = recuperar_password_crear_token($usuario);

            if ($tokenPlano === null) {
                return;
            }

            $urlRecuperacion = url('restablecer-password.php?token=' . rawurlencode($tokenPlano));
            $resultadoCorreo = mailer_enviar_recuperacion_password(
                (string) $usuario['email'],
                (string) $usuario['email'],
                $urlRecuperacion
            );

            if ($resultadoCorreo['ok'] !== true) {
                // No se muestra al usuario para mantener respuesta genérica y no exponer tokens.
                error_log('No fue posible enviar correo de recuperación para usuario ID ' . (int) $usuario['id_usuario']);
            }
        } catch (Throwable $exception) {
            // La respuesta pública sigue siendo genérica por seguridad.
            error_log('Error en solicitud de recuperación: ' . $exception->getMessage());
        }
    }
}

$email = '';
$errors = [];
$successMessage = null;
$csrfToken = $_SESSION['csrf_recuperar_password'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_recuperar_password'] = $csrfToken;

if (is_post()) {
    $email = trim((string) input('email', ''));
    $submittedToken = (string) input('csrf_token', '');

    if (!hash_equals((string) $_SESSION['csrf_recuperar_password'], $submittedToken)) {
        $errors[] = 'La solicitud no pudo validarse. Intenta nuevamente.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Ingresa un correo válido.';
    }

    if ($errors === []) {
        recuperar_password_procesar($email);
        $successMessage = recuperar_password_respuesta_generica();
        $email = '';
    }

    $csrfToken = bin2hex(random_bytes(32));
    $_SESSION['csrf_recuperar_password'] = $csrfToken;
}

require_once VIEWS_PATH . '/auth/recuperar-password.view.php';
