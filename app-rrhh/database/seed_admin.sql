-- Seed inicial del usuario administrador.
-- No guardar contrasena en texto plano.
-- Antes de importar este archivo, reemplazar REEMPLAZAR_POR_HASH_BCRYPT
-- por un hash generado localmente con password_hash().

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

SET @admin_email = _utf8mb4'admin@localhost.test' COLLATE utf8mb4_unicode_ci;
SET @admin_password_hash = _utf8mb4'$2y$10$X.AMmOmdBKWgmpqJ2/q8XunD1f3eEJh0lww2B9y.0.LHWKvxX12Ny' COLLATE utf8mb4_unicode_ci;

INSERT INTO usuarios_sistema (
    id_practicante,
    email,
    password_hash,
    estado_cuenta,
    debe_cambiar_password,
    email_verificado,
    intentos_fallidos,
    activo
) VALUES (
    NULL,
    @admin_email,
    @admin_password_hash,
    'ACTIVO',
    1,
    1,
    0,
    1
)
ON DUPLICATE KEY UPDATE
    id_usuario = LAST_INSERT_ID(id_usuario),
    estado_cuenta = VALUES(estado_cuenta),
    debe_cambiar_password = VALUES(debe_cambiar_password),
    email_verificado = VALUES(email_verificado),
    activo = VALUES(activo),
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO usuarios_roles (id_usuario, id_rol, rol_activo)
SELECT u.id_usuario, r.id_rol, 1
FROM usuarios_sistema u
INNER JOIN roles_sistema r ON r.nombre = _utf8mb4'ADMIN' COLLATE utf8mb4_unicode_ci
WHERE u.email = @admin_email
ON DUPLICATE KEY UPDATE
    rol_activo = VALUES(rol_activo),
    updated_at = CURRENT_TIMESTAMP;
