<?php

declare(strict_types=1);


require_once dirname(__DIR__, 2) . '/bootstrap.php';
// Reemplacé el puente por su dependencia interna directa.
require_once SRC_PATH . '/Models/UsuarioModel.php';

auth_requiere_rol('ADMIN');

$token = usuarios_gestion_token();
$busqueda = trim((string) query_param('q', ''));
$resultado = usuarios_gestion_procesar_post();
$errores = $resultado['errores'] ?? [];
$mensajeExito = ($resultado['ok'] ?? null) === true ? (string) ($resultado['mensaje'] ?? 'Accion realizada correctamente.') : null;
$urlActivacion = is_string($resultado['url_activacion'] ?? null) ? $resultado['url_activacion'] : null;

try {
    $usuarios = usuarios_gestion_listar($busqueda);
} catch (Throwable $exception) {
    error_log('Error al listar usuarios: ' . $exception->getMessage());
    $usuarios = [];
    $errores[] = 'No fue posible cargar los usuarios.';
}

if (!function_exists('usuarios_admin_fila_tiene_rol')) {
    function usuarios_admin_fila_tiene_rol(array $usuario, string $rol): bool
    {
        $roles = array_map('trim', explode(',', (string) ($usuario['roles'] ?? '')));

        return in_array($rol, $roles, true);
    }
}
$pageTitle = 'Gestion de usuarios';
// Preparé los datos del layout antes de mostrar la vista.
require SRC_PATH . '/Controllers/Shared/LayoutContext.php';

require_once VIEWS_PATH . '/admin/usuarios.view.php';
