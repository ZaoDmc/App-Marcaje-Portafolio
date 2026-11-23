<?php

declare(strict_types=1);

// Trasladé la autorización, POST y preparación de datos fuera del servicio/modelo.
// Este controlador compartido se ejecuta antes del layout, con parámetros del controlador por rol.
auth_requiere_rol($rolPermitido);

$errores = [];
$mensajeExito = null;
$activationUrlManual = null;
$catalogos = null;
$values = practicante_registro_valores_iniciales();

try {
    $catalogos = practicante_registro_catalogos();
} catch (Throwable $exception) {
    error_log('Error al cargar catálogos de practicantes: ' . $exception->getMessage());
    $errores[] = 'No fue posible cargar los catálogos. Importa schema.sql, seed_regiones_comunas.sql y seed_catalogos.sql antes de usar este formulario.';
}

start_secure_session();

if (empty($_SESSION['csrf_crear_practicante'])) {
    $_SESSION['csrf_crear_practicante'] = bin2hex(random_bytes(32));
}

if (is_post() && $catalogos !== null) {
    $values = array_merge($values, $_POST);

    if (!hash_equals((string) $_SESSION['csrf_crear_practicante'], (string) input('csrf_token', ''))) {
        $errores[] = 'La sesión del formulario expiró. Intenta nuevamente.';
    } else {
        $normalizado = practicante_registro_normalizar($_POST, $catalogos);
        $errores = practicante_registro_validar($normalizado, $catalogos);

        if ($errores === []) {
            $resultado = practicante_registro_guardar($normalizado);

            if ((bool) ($resultado['ok'] ?? false)) {
                $mensajeExito = 'Practicante pre-registrado correctamente.';
                $activationUrlManual = $resultado['url_activacion'] ?? null;
                $values = practicante_registro_valores_iniciales();
                $_SESSION['csrf_crear_practicante'] = bin2hex(random_bytes(32));
            } else {
                $errores = $resultado['errores'] ?? ['No fue posible guardar el pre-registro.'];
            }
        }
    }
}

