<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/AuditoriaService.php';

/*
|--------------------------------------------------------------------------
| Autenticación y contexto activo
|--------------------------------------------------------------------------
|
| Este archivo concentra funciones reutilizables para login, sesión PHP,
| sesión operativa en base de datos, rol activo y validaciones de permisos.
| No contiene vistas ni controladores; esas piezas se implementarán después.
|
*/

if (!function_exists('auth_usuario_por_email')) {
    function auth_usuario_por_email(string $email): ?array
    {
        $statement = db()->prepare(
            'SELECT *
             FROM usuarios_sistema
             WHERE email = :email
             LIMIT 1'
        );
        $statement->execute(['email' => strtolower(trim($email))]);

        $usuario = $statement->fetch();

        return is_array($usuario) ? $usuario : null;
    }
}

if (!function_exists('auth_roles_usuario')) {
    function auth_roles_usuario(int $idUsuario): array
    {
        $statement = db()->prepare(
            'SELECT
                r.id_rol,
                r.nombre,
                r.descripcion
             FROM usuarios_roles ur
             INNER JOIN roles_sistema r ON r.id_rol = ur.id_rol
             WHERE ur.id_usuario = :id_usuario
               AND ur.rol_activo = 1
               AND r.activo = 1
             ORDER BY r.nombre ASC'
        );
        $statement->execute(['id_usuario' => $idUsuario]);

        return $statement->fetchAll();
    }
}

if (!function_exists('auth_rol_en_lista')) {
    function auth_rol_en_lista(array $roles, int $idRol): ?array
    {
        foreach ($roles as $rol) {
            if ((int) ($rol['id_rol'] ?? 0) === $idRol) {
                return $rol;
            }
        }

        return null;
    }
}

if (!function_exists('auth_usuario_bloqueado_temporalmente')) {
    function auth_usuario_bloqueado_temporalmente(array $usuario): bool
    {
        if (empty($usuario['bloqueado_hasta'])) {
            return false;
        }

        $tiempoBloqueo = strtotime((string) $usuario['bloqueado_hasta']);

        if ($tiempoBloqueo === false || $tiempoBloqueo <= time()) {
            return false;
        }

        return true;
    }
}

if (!function_exists('auth_usuario_habilitado')) {
    function auth_usuario_habilitado(array $usuario): bool
    {
        // El backend valida estados operativos porque estado_cuenta es VARCHAR documentado.
        return (int) ($usuario['activo'] ?? 0) === 1
            && (string) ($usuario['estado_cuenta'] ?? '') === 'ACTIVO'
            && (int) ($usuario['email_verificado'] ?? 0) === 1;
    }
}

if (!function_exists('auth_password_valida')) {
    function auth_password_valida(string $passwordPlano, array $usuario): bool
    {
        return password_verify($passwordPlano, (string) ($usuario['password_hash'] ?? ''));
    }
}

if (!function_exists('auth_incrementar_intento_fallido')) {
    function auth_incrementar_intento_fallido(int $idUsuario): void
    {
        $ahora = date('Y-m-d H:i:s');
        $bloqueoHasta = date('Y-m-d H:i:s', time() + (LOGIN_LOCK_MINUTES * 60));

        // Si el bloqueo anterior ya expiro o si pasaron mas de LOGIN_LOCK_MINUTES desde el ultimo intento,
        // el contador se reinicia a 1 para otorgar los intentos correspondientes.
        $statement = db()->prepare(
            'UPDATE usuarios_sistema
             SET intentos_fallidos = CASE
                    WHEN bloqueado_hasta IS NOT NULL AND bloqueado_hasta <= :ahora1 THEN 1
                    WHEN ultimo_intento_login IS NOT NULL AND ultimo_intento_login < DATE_SUB(:ahora2, INTERVAL :ventana MINUTE) THEN 1
                    ELSE intentos_fallidos + 1
                 END,
                 ultimo_intento_login = :ahora3,
                 bloqueado_hasta = CASE
                    WHEN (CASE
                            WHEN bloqueado_hasta IS NOT NULL AND bloqueado_hasta <= :ahora4 THEN 1
                            WHEN ultimo_intento_login IS NOT NULL AND ultimo_intento_login < DATE_SUB(:ahora5, INTERVAL :ventana2 MINUTE) THEN 1
                            ELSE intentos_fallidos + 1
                         END) >= :max_intentos
                    THEN :bloqueo_hasta
                    ELSE NULL
                 END
             WHERE id_usuario = :id_usuario'
        );

        $statement->execute([
            'ahora1' => $ahora,
            'ahora2' => $ahora,
            'ahora3' => $ahora,
            'ahora4' => $ahora,
            'ahora5' => $ahora,
            'ventana' => LOGIN_LOCK_MINUTES,
            'ventana2' => LOGIN_LOCK_MINUTES,
            'max_intentos' => MAX_LOGIN_ATTEMPTS,
            'bloqueo_hasta' => $bloqueoHasta,
            'id_usuario' => $idUsuario,
        ]);
    }
}

if (!function_exists('auth_resetear_intentos')) {
    function auth_resetear_intentos(int $idUsuario): void
    {
        $statement = db()->prepare(
            'UPDATE usuarios_sistema
             SET intentos_fallidos = 0,
                 ultimo_intento_login = NULL,
                 bloqueado_hasta = NULL,
                 ultimo_login = NOW()
             WHERE id_usuario = :id_usuario'
        );

        $statement->execute(['id_usuario' => $idUsuario]);
    }
}

if (!function_exists('auth_guardar_usuario_base_en_sesion')) {
    function auth_guardar_usuario_base_en_sesion(array $usuario, array $roles): void
    {
        start_secure_session();

        $_SESSION['id_usuario'] = (int) $usuario['id_usuario'];
        $_SESSION['id_practicante'] = $usuario['id_practicante'] !== null ? (int) $usuario['id_practicante'] : null;
        $_SESSION['email'] = (string) $usuario['email'];
        $_SESSION['roles_disponibles'] = $roles;
        $_SESSION['ultimo_acceso'] = time();
    }
}

if (!function_exists('auth_crear_sesion_operativa')) {
    function auth_crear_sesion_operativa(int $idUsuario, int $idRolActivo): int
    {
        start_secure_session();

        $statement = db()->prepare(
            'INSERT INTO sesiones_usuario (
                id_usuario,
                id_rol_activo,
                session_id_hash,
                ip,
                user_agent
             ) VALUES (
                :id_usuario,
                :id_rol_activo,
                :session_id_hash,
                :ip,
                :user_agent
             )'
        );

        $statement->execute([
            'id_usuario' => $idUsuario,
            'id_rol_activo' => $idRolActivo,
            'session_id_hash' => hash('sha256', session_id()),
            'ip' => auditoria_ip(),
            'user_agent' => auditoria_user_agent(),
        ]);

        $idSesion = (int) db()->lastInsertId();
        $_SESSION['id_sesion'] = $idSesion;

        return $idSesion;
    }
}

if (!function_exists('auth_establecer_rol_activo')) {
    function auth_establecer_rol_activo(int $idRol): bool
    {
        start_secure_session();

        $idUsuario = $_SESSION['id_usuario'] ?? $_SESSION['usuario_pendiente_rol'] ?? null;
        $roles = $_SESSION['roles_disponibles'] ?? [];

        if (!is_int($idUsuario) && !ctype_digit((string) $idUsuario)) {
            return false;
        }

        if (!is_array($roles)) {
            return false;
        }

        $rol = auth_rol_en_lista($roles, $idRol);

        if ($rol === null) {
            return false;
        }

        // La fila en sesiones_usuario se crea solo después de existir rol activo definido.
        if (empty($_SESSION['id_sesion'])) {
            auth_crear_sesion_operativa((int) $idUsuario, (int) $rol['id_rol']);
        }

        $_SESSION['id_usuario'] = (int) $idUsuario;
        $_SESSION['id_rol_activo'] = (int) $rol['id_rol'];
        $_SESSION['rol_activo'] = (string) $rol['nombre'];
        $_SESSION['ultimo_acceso'] = time();
        unset($_SESSION['usuario_pendiente_rol']);

        auditoria_registrar('SELECCION_ROL_CONTEXTO', [
            'tabla_afectada' => 'sesiones_usuario',
            'id_registro_afectado' => $_SESSION['id_sesion'] ?? null,
            'valores_nuevos' => [
                'id_usuario' => (int) $idUsuario,
                'id_rol_activo' => (int) $rol['id_rol'],
                'rol_activo' => (string) $rol['nombre'],
            ],
        ]);

        return true;
    }
}

if (!function_exists('auth_configurar_sesion_persistente')) {
    function auth_configurar_sesion_persistente(bool $mantenerSesion): void
    {
        start_secure_session();

        if (!$mantenerSesion) {
            unset($_SESSION['mantener_sesion'], $_SESSION['sesion_persistente_hasta']);

            return;
        }

        $expiraEn = time() + (REMEMBER_SESSION_DAYS * 24 * 60 * 60);
        $_SESSION['mantener_sesion'] = true;
        $_SESSION['sesion_persistente_hasta'] = $expiraEn;

        session_extend_cookie($expiraEn);
    }
}

if (!function_exists('auth_preparar_sesion_autenticada')) {
    function auth_preparar_sesion_autenticada(array $usuario, array $roles, bool $mantenerSesion = false): array
    {
        start_secure_session();
        session_regenerate_id(true);
        auth_guardar_usuario_base_en_sesion($usuario, $roles);
        auth_configurar_sesion_persistente($mantenerSesion);

        if (count($roles) === 1) {
            $rol = $roles[0];
            $_SESSION['id_rol_activo'] = (int) $rol['id_rol'];
            $_SESSION['rol_activo'] = (string) $rol['nombre'];
            auth_crear_sesion_operativa((int) $usuario['id_usuario'], (int) $rol['id_rol']);

            return [
                'requiere_seleccion_rol' => false,
                'redirect' => auth_destino_por_rol((string) $rol['nombre']),
            ];
        }

        // En multirrol se usa sesión PHP temporal y no se inserta sesiones_usuario aún.
        $_SESSION['usuario_pendiente_rol'] = (int) $usuario['id_usuario'];
        unset($_SESSION['id_rol_activo'], $_SESSION['rol_activo'], $_SESSION['id_sesion']);

        return [
            'requiere_seleccion_rol' => true,
            'redirect' => 'seleccionar-rol.php',
        ];
    }
}

if (!function_exists('auth_practicante_ficha_completa')) {
    function auth_practicante_ficha_completa(?int $idPracticante): bool
    {
        if ($idPracticante === null || $idPracticante <= 0) {
            return false;
        }

        $statement = db()->prepare(
            'SELECT
                p.rut_numero,
                p.rut_dv,
                p.nombre_1,
                p.apellido_paterno,
                p.email,
                p.fecha_nacimiento,
                p.sexo,
                p.nacionalidad,
                p.direccion,
                p.numero_direccion,
                p.pais,
                p.id_region,
                p.id_comuna,
                p.id_institucion,
                p.id_sede,
                p.institucion_otra,
                p.sede_otra,
                p.id_carrera,
                p.semestre,
                i.nombre AS institucion_nombre,
                pr.id_tipo_practica,
                pr.id_practica,
                pr.horas_requeridas,
                pr.fecha_inicio_practica
             FROM practicantes p
             LEFT JOIN instituciones i ON i.id_institucion = p.id_institucion
             LEFT JOIN practicas pr ON pr.id_practica = (
                SELECT pr2.id_practica
                FROM practicas pr2
                WHERE pr2.id_practicante = p.id_practicante
                  AND pr2.activo = 1
                ORDER BY pr2.fecha_inicio_practica DESC, pr2.id_practica DESC
                LIMIT 1
             )
             WHERE p.id_practicante = :id_practicante
               AND p.activo = 1
             LIMIT 1'
        );
        $statement->execute(['id_practicante' => $idPracticante]);

        $practicante = $statement->fetch();

        if (!is_array($practicante)) {
            return false;
        }

        $pais = trim((string) ($practicante['pais'] ?? ''));
        if ($pais === '' && (!empty($practicante['id_region']) || !empty($practicante['id_comuna']))) {
            $practicante['pais'] = 'Chile';
        }

        foreach ([
            'rut_numero',
            'rut_dv',
            'nombre_1',
            'apellido_paterno',
            'email',
            'fecha_nacimiento',
            'sexo',
            'nacionalidad',
            'direccion',
            'pais',
            'id_region',
            'id_comuna',
            'semestre',
            'id_tipo_practica',
            'horas_requeridas',
            'fecha_inicio_practica',
        ] as $campo) {
            if ($practicante[$campo] === null || (string) $practicante[$campo] === '') {
                return false;
            }
        }

        $tieneInstitucion = !empty($practicante['id_institucion']) || !empty($practicante['institucion_nombre']) || !empty($practicante['institucion_otra']);
        $tieneCarrera = !empty($practicante['id_carrera']);

        if (!$tieneInstitucion || !$tieneCarrera) {
            return false;
        }

        if (strcasecmp((string) ($practicante['institucion_nombre'] ?? ''), 'Otra') === 0) {
            if (empty($practicante['institucion_otra']) || empty($practicante['sede_otra'])) {
                return false;
            }
        } elseif (empty($practicante['id_sede']) && empty($practicante['sede_otra'])) {
            return false;
        }

        if (empty($practicante['id_practica'])) {
            return false;
        }

        return auth_practicante_tiene_horario((int) $practicante['id_practica']);
    }
}

if (!function_exists('auth_practicante_tiene_horario')) {
    function auth_practicante_tiene_horario(int $idPractica): bool
    {
        $statement = db()->prepare(
            'SELECT COUNT(*)
             FROM horarios_practicante hp
             INNER JOIN detalle_horario_practicante dhp ON dhp.id_horario = hp.id_horario
             WHERE hp.id_practica = :id_practica
               AND hp.activo = 1
               AND dhp.activo = 1'
        );
        $statement->execute(['id_practica' => $idPractica]);

        return (int) $statement->fetchColumn() > 0;
    }
}

if (!function_exists('auth_destino_por_rol')) {
    function auth_destino_por_rol(string $rol): string
    {
        return auth_dashboard_por_rol($rol);
    }
}

if (!function_exists('auth_intentar_login')) {
    function auth_intentar_login(string $email, string $passwordPlano, bool $mantenerSesion = false): array
    {
        $usuario = auth_usuario_por_email($email);

        if ($usuario === null) {
            auditoria_registrar('LOGIN_FALLIDO', [
                'valores_nuevos' => ['email' => strtolower(trim($email)), 'motivo' => 'USUARIO_NO_EXISTE'],
            ]);

            return ['ok' => false, 'mensaje' => 'Credenciales inválidas.'];
        }

        if (auth_usuario_bloqueado_temporalmente($usuario)) {
            auditoria_registrar('USUARIO_BLOQUEADO', [
                'id_usuario_actor' => (int) $usuario['id_usuario'],
                'tabla_afectada' => 'usuarios_sistema',
                'id_registro_afectado' => (int) $usuario['id_usuario'],
            ]);

            return ['ok' => false, 'mensaje' => 'La cuenta se encuentra temporalmente bloqueada.'];
        }

        if (!auth_usuario_habilitado($usuario) || !auth_password_valida($passwordPlano, $usuario)) {
            auth_incrementar_intento_fallido((int) $usuario['id_usuario']);
            auditoria_registrar('LOGIN_FALLIDO', [
                'id_usuario_actor' => (int) $usuario['id_usuario'],
                'tabla_afectada' => 'usuarios_sistema',
                'id_registro_afectado' => (int) $usuario['id_usuario'],
            ]);

            return ['ok' => false, 'mensaje' => 'Credenciales inválidas.'];
        }

        $roles = auth_roles_usuario((int) $usuario['id_usuario']);

        if ($roles === []) {
            auditoria_registrar('LOGIN_FALLIDO', [
                'id_usuario_actor' => (int) $usuario['id_usuario'],
                'tabla_afectada' => 'usuarios_roles',
                'valores_nuevos' => ['motivo' => 'SIN_ROLES_ACTIVOS'],
            ]);

            return ['ok' => false, 'mensaje' => 'La cuenta no tiene roles activos asignados.'];
        }

        auth_resetear_intentos((int) $usuario['id_usuario']);
        $sesion = auth_preparar_sesion_autenticada($usuario, $roles, $mantenerSesion);

        auditoria_registrar('LOGIN_EXITOSO', [
            'id_usuario_actor' => (int) $usuario['id_usuario'],
            'tabla_afectada' => 'usuarios_sistema',
            'id_registro_afectado' => (int) $usuario['id_usuario'],
        ]);

        return [
            'ok' => true,
            'usuario' => $usuario,
            'roles' => $roles,
            'requiere_seleccion_rol' => $sesion['requiere_seleccion_rol'],
            'redirect' => $sesion['redirect'],
        ];
    }
}

if (!function_exists('auth_check')) {
    function auth_check(): bool
    {
        start_secure_session();

        return isset($_SESSION['id_usuario'], $_SESSION['id_rol_activo'], $_SESSION['rol_activo']);
    }
}

if (!function_exists('auth_usuario_actual_id')) {
    function auth_usuario_actual_id(): ?int
    {
        start_secure_session();

        return isset($_SESSION['id_usuario']) ? (int) $_SESSION['id_usuario'] : null;
    }
}

if (!function_exists('auth_practicante_actual_id')) {
    function auth_practicante_actual_id(): ?int
    {
        start_secure_session();

        return isset($_SESSION['id_practicante']) && $_SESSION['id_practicante'] !== null
            ? (int) $_SESSION['id_practicante']
            : null;
    }
}

if (!function_exists('auth_rol_activo')) {
    function auth_rol_activo(): ?string
    {
        start_secure_session();

        return isset($_SESSION['rol_activo']) ? (string) $_SESSION['rol_activo'] : null;
    }
}

if (!function_exists('auth_id_rol_activo')) {
    function auth_id_rol_activo(): ?int
    {
        start_secure_session();

        return isset($_SESSION['id_rol_activo']) ? (int) $_SESSION['id_rol_activo'] : null;
    }
}

if (!function_exists('auth_tiene_rol')) {
    function auth_tiene_rol(string $rolEsperado): bool
    {
        return auth_rol_activo() === $rolEsperado;
    }
}

if (!function_exists('auth_requiere_login')) {
    function auth_requiere_login(): void
    {
        auth_verificar_timeout();

        if (!auth_check()) {
            redirect_to('login.php');
        }
    }
}

if (!function_exists('auth_requiere_rol')) {
    function auth_requiere_rol(array|string $rolesPermitidos): void
    {
        auth_requiere_login();

        $roles = is_array($rolesPermitidos) ? $rolesPermitidos : [$rolesPermitidos];

        if (!in_array(auth_rol_activo(), $roles, true)) {
            http_response_code(403);
            exit('Acceso denegado.');
        }
    }
}

if (!function_exists('auth_verificar_timeout')) {
    function auth_verificar_timeout(): void
    {
        start_secure_session();

        $ultimoAcceso = isset($_SESSION['ultimo_acceso']) ? (int) $_SESSION['ultimo_acceso'] : null;

        if ($ultimoAcceso === null) {
            return;
        }

        if (!empty($_SESSION['mantener_sesion'])) {
            $persistenteHasta = isset($_SESSION['sesion_persistente_hasta'])
                ? (int) $_SESSION['sesion_persistente_hasta']
                : 0;

            if ($persistenteHasta > time()) {
                $_SESSION['ultimo_acceso'] = time();
                auth_actualizar_ultimo_acceso_bbdd();

                return;
            }

            auth_logout();
            redirect_to('login.php?timeout=1');
        }

        if ((time() - $ultimoAcceso) > (SESSION_TIMEOUT_MINUTES * 60)) {
            auth_logout();
            redirect_to('login.php?timeout=1');
        }

        $_SESSION['ultimo_acceso'] = time();
        auth_actualizar_ultimo_acceso_bbdd();
    }
}

if (!function_exists('auth_actualizar_ultimo_acceso_bbdd')) {
    function auth_actualizar_ultimo_acceso_bbdd(): void
    {
        start_secure_session();

        if (empty($_SESSION['id_sesion'])) {
            return;
        }

        $statement = db()->prepare(
            'UPDATE sesiones_usuario
             SET fecha_ultimo_acceso = NOW()
             WHERE id_sesion = :id_sesion
               AND activa = 1'
        );
        $statement->execute(['id_sesion' => (int) $_SESSION['id_sesion']]);
    }
}

if (!function_exists('auth_logout')) {
    function auth_logout(): void
    {
        start_secure_session();

        $idSesion = $_SESSION['id_sesion'] ?? null;

        auditoria_registrar('LOGOUT', [
            'tabla_afectada' => 'sesiones_usuario',
            'id_registro_afectado' => $idSesion,
        ]);

        if ($idSesion !== null) {
            try {
                $statement = db()->prepare(
                    'UPDATE sesiones_usuario
                     SET fecha_cierre = NOW(),
                         cerrada_por_logout = 1,
                         activa = 0
                     WHERE id_sesion = :id_sesion'
                );
                $statement->execute(['id_sesion' => (int) $idSesion]);
            } catch (Throwable $exception) {
                // El cierre de sesión PHP debe completarse aunque falle la traza en BBDD.
                error_log('Error al cerrar sesion operativa: ' . $exception->getMessage());
            }
        }

        $_SESSION = [];

        session_clear_cookie();

        session_destroy();
    }
}

if (!function_exists('auth_dashboard_por_rol')) {
    function auth_dashboard_por_rol(string $rol): string
    {
        return match ($rol) {
            'ADMIN' => 'admin/dashboard.php',
            'SUPERVISOR' => 'supervisor/dashboard.php',
            'PRACTICANTE' => 'practicante/marcar-asistencia.php',
            default => 'login.php',
        };
    }
}

if (!function_exists('auth_puede_asignar_rol_admin')) {
    function auth_puede_asignar_rol_admin(?int $idPracticante): bool
    {
        // Regla oficial: cualquier usuario vinculado a practicante no puede recibir rol ADMIN.
        return $idPracticante === null;
    }
}

if (!function_exists('auth_validar_asignacion_rol')) {
    function auth_validar_asignacion_rol(?int $idPracticante, string $rol): bool
    {
        if ($rol !== 'ADMIN') {
            return true;
        }

        if (auth_puede_asignar_rol_admin($idPracticante)) {
            return true;
        }

        auditoria_registrar('INTENTO_ASIGNACION_ADMIN_RECHAZADO', [
            'tabla_afectada' => 'usuarios_roles',
            'valores_nuevos' => [
                'id_practicante' => $idPracticante,
                'rol_rechazado' => 'ADMIN',
            ],
        ]);

        return false;
    }
}

if (!function_exists('auth_es_conflicto_supervisor_practicante')) {
    function auth_es_conflicto_supervisor_practicante(?int $idPracticanteObjetivo): bool
    {
        // Un usuario PRACTICANTE + SUPERVISOR puede existir, pero no puede autosupervisarse.
        return auth_rol_activo() === 'SUPERVISOR'
            && $idPracticanteObjetivo !== null
            && auth_practicante_actual_id() === $idPracticanteObjetivo;
    }
}

// Agregué esta comprobación de backend para autorizar cada práctica recibida,
// aunque su ID se envíe manualmente y no aparezca en el listado de la pantalla.
if (!function_exists('auth_puede_supervisar_practica')) {
    function auth_puede_supervisar_practica(int $idPractica, ?int $idUsuarioActor = null): bool
    {
        if (!auth_check() || !in_array(auth_rol_activo(), ['ADMIN', 'SUPERVISOR'], true)
            || $idPractica <= 0
            || ($idUsuarioActor !== null && $idUsuarioActor !== auth_usuario_actual_id())) {
            return false;
        }
        $statement = db()->prepare('SELECT id_practicante FROM practicas WHERE id_practica = :id_practica');
        $statement->execute(['id_practica' => $idPractica]);
        $idPracticante = $statement->fetchColumn();
        return $idPracticante !== false && !auth_es_conflicto_supervisor_practicante((int) $idPracticante);
    }
}
