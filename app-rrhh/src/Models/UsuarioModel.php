<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../Services/AuthService.php';
require_once __DIR__ . '/../Services/MailService.php';

if (!function_exists('usuarios_gestion_token')) {
    function usuarios_gestion_token(): string
    {
        start_secure_session();

        if (!isset($_SESSION['csrf_usuarios_gestion'])) {
            $_SESSION['csrf_usuarios_gestion'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['csrf_usuarios_gestion'];
    }
}

if (!function_exists('usuarios_gestion_email_valido')) {
    function usuarios_gestion_email_valido(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false && strlen($email) <= 150;
    }
}

if (!function_exists('usuarios_gestion_rol_id')) {
    function usuarios_gestion_rol_id(string $rol): ?int
    {
        $statement = db()->prepare(
            'SELECT id_rol
             FROM roles_sistema
             WHERE nombre = :nombre
               AND activo = 1
             LIMIT 1'
        );
        $statement->execute(['nombre' => $rol]);
        $idRol = $statement->fetchColumn();

        return $idRol !== false ? (int) $idRol : null;
    }
}

if (!function_exists('usuarios_gestion_listar')) {
    function usuarios_gestion_listar(?string $busqueda = null): array
    {
        $where = [];
        $params = [];

        $busqueda = trim((string) $busqueda);

        if ($busqueda !== '') {
            $where[] = '(
                u.email LIKE :busqueda_email
                OR u.estado_cuenta LIKE :busqueda_estado
                OR r.nombre LIKE :busqueda_rol
            )';
            $like = '%' . $busqueda . '%';
            $params['busqueda_email'] = $like;
            $params['busqueda_estado'] = $like;
            $params['busqueda_rol'] = $like;
        }

        $statement = db()->prepare(
            'SELECT
                u.id_usuario,
                u.id_practicante,
                u.email,
                u.estado_cuenta,
                u.debe_cambiar_password,
                u.email_verificado,
                u.activo,
                u.ultimo_login,
                u.created_at,
                GROUP_CONCAT(r.nombre ORDER BY r.nombre SEPARATOR ", ") AS roles
             FROM usuarios_sistema u
             LEFT JOIN usuarios_roles ur ON ur.id_usuario = u.id_usuario
             LEFT JOIN roles_sistema r ON r.id_rol = ur.id_rol AND ur.rol_activo = 1
             ' . ($where !== [] ? 'WHERE ' . implode(' AND ', $where) : '') . '
             GROUP BY
                u.id_usuario,
                u.id_practicante,
                u.email,
                u.estado_cuenta,
                u.debe_cambiar_password,
                u.email_verificado,
                u.activo,
                u.ultimo_login,
                u.created_at
             ORDER BY u.created_at DESC, u.id_usuario DESC
             LIMIT 100'
        );
        $statement->execute($params);

        return $statement->fetchAll();
    }
}

if (!function_exists('usuarios_gestion_usuario_por_email')) {
    function usuarios_gestion_usuario_por_email(string $email): ?array
    {
        $statement = db()->prepare(
            'SELECT *
             FROM usuarios_sistema
             WHERE email = :email
             LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $usuario = $statement->fetch();

        return is_array($usuario) ? $usuario : null;
    }
}

if (!function_exists('usuarios_gestion_usuario_por_id')) {
    function usuarios_gestion_usuario_por_id(int $idUsuario): ?array
    {
        $statement = db()->prepare(
            'SELECT *
             FROM usuarios_sistema
             WHERE id_usuario = :id_usuario
             LIMIT 1'
        );
        $statement->execute(['id_usuario' => $idUsuario]);
        $usuario = $statement->fetch();

        return is_array($usuario) ? $usuario : null;
    }
}

if (!function_exists('usuarios_gestion_usuario_tiene_rol')) {
    function usuarios_gestion_usuario_tiene_rol(int $idUsuario, string $rol): bool
    {
        foreach (auth_roles_usuario($idUsuario) as $rolUsuario) {
            if ((string) ($rolUsuario['nombre'] ?? '') === $rol) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('usuarios_gestion_quitar_supervisor_practicante')) {
    function usuarios_gestion_quitar_supervisor_practicante(int $idUsuario): array
    {
        if ($idUsuario <= 0) {
            return ['ok' => false, 'errores' => ['Usuario no valido.']];
        }

        $usuario = usuarios_gestion_usuario_por_id($idUsuario);

        if ($usuario === null) {
            return ['ok' => false, 'errores' => ['El usuario indicado no existe.']];
        }

        if ($usuario['id_practicante'] === null) {
            return ['ok' => false, 'errores' => ['Solo se puede quitar SUPERVISOR desde esta accion a usuarios vinculados a practicante.']];
        }

        if (!usuarios_gestion_usuario_tiene_rol($idUsuario, 'PRACTICANTE')) {
            return ['ok' => false, 'errores' => ['El usuario no tiene rol PRACTICANTE activo.']];
        }

        if (!usuarios_gestion_usuario_tiene_rol($idUsuario, 'SUPERVISOR')) {
            return [
                'ok' => true,
                'errores' => [],
                'mensaje' => 'El usuario ya no tiene rol SUPERVISOR activo.',
                'url_activacion' => null,
            ];
        }

        $rolSupervisorId = usuarios_gestion_rol_id('SUPERVISOR');

        if ($rolSupervisorId === null) {
            return ['ok' => false, 'errores' => ['No existe el rol SUPERVISOR activo.']];
        }

        try {
            $statement = db()->prepare(
                'UPDATE usuarios_roles
                 SET rol_activo = 0
                 WHERE id_usuario = :id_usuario
                   AND id_rol = :id_rol'
            );
            $statement->execute([
                'id_usuario' => $idUsuario,
                'id_rol' => $rolSupervisorId,
            ]);
        } catch (Throwable $exception) {
            error_log('Error al quitar rol supervisor: ' . $exception->getMessage());

            return ['ok' => false, 'errores' => ['No fue posible quitar el rol SUPERVISOR.']];
        }

        auditoria_registrar('ROL_REMOVIDO', [
            'tabla_afectada' => 'usuarios_roles',
            'id_registro_afectado' => $idUsuario,
            'valores_nuevos' => [
                'email' => (string) $usuario['email'],
                'rol' => 'SUPERVISOR',
                'id_practicante' => (int) $usuario['id_practicante'],
            ],
        ]);

        return [
            'ok' => true,
            'errores' => [],
            'mensaje' => 'Rol SUPERVISOR quitado. El usuario queda solo como PRACTICANTE.',
            'url_activacion' => null,
        ];
    }
}

if (!function_exists('usuarios_gestion_asignar_supervisor_existente')) {
    function usuarios_gestion_asignar_supervisor_existente(array $usuario, int $rolSupervisorId): array
    {
        $idUsuario = (int) ($usuario['id_usuario'] ?? 0);
        $email = (string) ($usuario['email'] ?? '');
        $estadoCuenta = (string) ($usuario['estado_cuenta'] ?? '');
        $activo = (int) ($usuario['activo'] ?? 0);

        if ($idUsuario <= 0 || $email === '') {
            return ['ok' => false, 'errores' => ['No fue posible identificar el usuario existente.']];
        }

        if ($activo !== 1 || !in_array($estadoCuenta, ['ACTIVO', 'PENDIENTE_ACTIVACION'], true)) {
            return ['ok' => false, 'errores' => ['La cuenta existe, pero no esta activa o pendiente de activacion.']];
        }

        if (!auth_validar_asignacion_rol(
            $usuario['id_practicante'] !== null ? (int) $usuario['id_practicante'] : null,
            'SUPERVISOR'
        )) {
            return ['ok' => false, 'errores' => ['No se puede asignar el rol SUPERVISOR a este usuario.']];
        }

        if (usuarios_gestion_usuario_tiene_rol($idUsuario, 'SUPERVISOR')) {
            return [
                'ok' => true,
                'errores' => [],
                'mensaje' => 'El usuario ya tiene rol SUPERVISOR activo.',
                'url_activacion' => null,
            ];
        }

        try {
            $statement = db()->prepare(
                'INSERT INTO usuarios_roles (id_usuario, id_rol, rol_activo)
                 VALUES (:id_usuario, :id_rol, 1)
                 ON DUPLICATE KEY UPDATE rol_activo = 1'
            );
            $statement->execute([
                'id_usuario' => $idUsuario,
                'id_rol' => $rolSupervisorId,
            ]);
        } catch (Throwable $exception) {
            error_log('Error al asignar rol supervisor: ' . $exception->getMessage());

            return ['ok' => false, 'errores' => ['No fue posible asignar el rol SUPERVISOR.']];
        }

        auditoria_registrar('ROL_ASIGNADO', [
            'tabla_afectada' => 'usuarios_roles',
            'id_registro_afectado' => $idUsuario,
            'valores_nuevos' => [
                'email' => $email,
                'rol' => 'SUPERVISOR',
                'usuario_existente' => true,
            ],
        ]);

        return [
            'ok' => true,
            'errores' => [],
            'mensaje' => 'Rol SUPERVISOR asignado al usuario existente.',
            'url_activacion' => null,
        ];
    }
}

if (!function_exists('usuarios_gestion_crear_supervisor')) {
    function usuarios_gestion_crear_supervisor(string $email): array
    {
        $email = strtolower(trim($email));

        if (!usuarios_gestion_email_valido($email)) {
            return ['ok' => false, 'errores' => ['Debe ingresar un correo valido.']];
        }

        $rolSupervisorId = usuarios_gestion_rol_id('SUPERVISOR');

        if ($rolSupervisorId === null) {
            return ['ok' => false, 'errores' => ['No existe el rol SUPERVISOR activo.']];
        }

        $usuarioExistente = usuarios_gestion_usuario_por_email($email);

        if ($usuarioExistente !== null) {
            return usuarios_gestion_asignar_supervisor_existente($usuarioExistente, $rolSupervisorId);
        }

        $pdo = db();
        $tokenPlano = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $tokenPlano);
        $passwordTemporal = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
        $fechaExpiracion = date('Y-m-d H:i:s', time() + (TOKEN_EXPIRATION_HOURS * 3600));
        $idUsuario = null;
        $idToken = null;

        try {
            $pdo->beginTransaction();

            $statement = $pdo->prepare(
                'INSERT INTO usuarios_sistema (
                    id_practicante,
                    email,
                    password_hash,
                    estado_cuenta,
                    debe_cambiar_password,
                    email_verificado,
                    activo
                 ) VALUES (
                    NULL,
                    :email,
                    :password_hash,
                    "PENDIENTE_ACTIVACION",
                    1,
                    0,
                    1
                 )'
            );
            $statement->execute([
                'email' => $email,
                'password_hash' => $passwordTemporal,
            ]);
            $idUsuario = (int) $pdo->lastInsertId();

            $statement = $pdo->prepare(
                'INSERT INTO usuarios_roles (id_usuario, id_rol, rol_activo)
                 VALUES (:id_usuario, :id_rol, 1)'
            );
            $statement->execute([
                'id_usuario' => $idUsuario,
                'id_rol' => $rolSupervisorId,
            ]);

            $statement = $pdo->prepare(
                'INSERT INTO tokens_usuario (
                    id_usuario,
                    tipo_token,
                    token_hash,
                    fecha_expiracion,
                    usado
                ) VALUES (
                    :id_usuario,
                    "ACTIVACION_CUENTA",
                    :token_hash,
                    :fecha_expiracion,
                    0
                )'
            );
            $statement->execute([
                'id_usuario' => $idUsuario,
                'token_hash' => $tokenHash,
                'fecha_expiracion' => $fechaExpiracion,
            ]);
            $idToken = (int) $pdo->lastInsertId();

            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Error al crear supervisor: ' . $exception->getMessage());

            return ['ok' => false, 'errores' => ['No fue posible crear el supervisor.']];
        }

        $urlActivacion = url('activar-cuenta.php?token=' . urlencode($tokenPlano));
        $envio = mailer_enviar_activacion($email, 'Supervisor', $urlActivacion);

        auditoria_registrar('CREACION_USUARIO', [
            'tabla_afectada' => 'usuarios_sistema',
            'id_registro_afectado' => $idUsuario,
            'valores_nuevos' => [
                'email' => $email,
                'estado_cuenta' => 'PENDIENTE_ACTIVACION',
                'rol' => 'SUPERVISOR',
            ],
        ]);

        auditoria_registrar('ROL_ASIGNADO', [
            'tabla_afectada' => 'usuarios_roles',
            'id_registro_afectado' => $idUsuario,
            'valores_nuevos' => [
                'email' => $email,
                'rol' => 'SUPERVISOR',
            ],
        ]);

        auditoria_registrar('TOKEN_GENERADO', [
            'tabla_afectada' => 'tokens_usuario',
            'id_registro_afectado' => $idToken,
            'valores_nuevos' => [
                'tipo_token' => 'ACTIVACION_CUENTA',
                'fecha_expiracion' => $fechaExpiracion,
            ],
        ]);

        return [
            'ok' => true,
            'errores' => [],
            'mensaje' => 'Supervisor externo creado correctamente.',
            'email_enviado' => (bool) ($envio['ok'] ?? false),
            'url_activacion' => (bool) ($envio['ok'] ?? false) ? null : $urlActivacion,
        ];
    }
}

if (!function_exists('usuarios_gestion_procesar_post')) {
    function usuarios_gestion_procesar_post(): array
    {
        if (!is_post()) {
            return ['ok' => null, 'errores' => [], 'mensaje' => null, 'url_activacion' => null];
        }

        if (!hash_equals(usuarios_gestion_token(), (string) input('csrf_token', ''))) {
            return ['ok' => false, 'errores' => ['La sesion del formulario expiro. Intenta nuevamente.'], 'mensaje' => null, 'url_activacion' => null];
        }

        $accion = (string) input('accion', '');

        if ($accion === 'crear_supervisor') {
            return usuarios_gestion_crear_supervisor((string) input('email', ''));
        }

        if ($accion === 'quitar_supervisor') {
            return usuarios_gestion_quitar_supervisor_practicante((int) input('id_usuario', 0));
        }

        return ['ok' => false, 'errores' => ['Accion no reconocida.'], 'mensaje' => null, 'url_activacion' => null];
    }
}
