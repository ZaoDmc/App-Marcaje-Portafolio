<?php

declare(strict_types=1);

// Trasladé la autorización, POST y preparación de datos fuera del servicio/modelo.
// Este controlador compartido se ejecuta antes del layout, con parámetros del controlador por rol.
auth_requiere_rol($rolPermitido);

start_secure_session();

if (empty($_SESSION['csrf_carga_masiva'])) {
    $_SESSION['csrf_carga_masiva'] = bin2hex(random_bytes(32));
}

$errores = [];
$resumen = null;
$catalogos = null;

try {
    $catalogos = practicante_registro_catalogos();
} catch (Throwable $exception) {
    error_log('Error al cargar catálogos para carga masiva: ' . $exception->getMessage());
    $errores[] = 'No fue posible cargar los catálogos. Importa schema.sql, seed_regiones_comunas.sql y seed_catalogos.sql antes de usar este formulario.';
}

if (is_post() && $catalogos !== null) {
    if (!hash_equals((string) $_SESSION['csrf_carga_masiva'], (string) input('csrf_token', ''))) {
        $errores[] = 'La sesión del formulario expiró. Intenta nuevamente.';
    } else {
        $archivoValidado = practicante_carga_masiva_validar_archivo($_FILES['archivo_csv'] ?? null);

        if (!($archivoValidado['ok'] ?? false)) {
            $errores = array_merge($errores, $archivoValidado['errores']);
        } else {
            try {
                $nombreSubido = (string) ($_FILES['archivo_csv']['name'] ?? '');
                $extSubida = strtolower((string) pathinfo($nombreSubido, PATHINFO_EXTENSION));

                if ($extSubida === 'xlsx') {
                    $datosArchivo = xlsx_leer_filas((string) $_FILES['archivo_csv']['tmp_name']);
                } else {
                    $datosArchivo = practicante_carga_masiva_leer_csv((string) $_FILES['archivo_csv']['tmp_name']);
                }

                $errores = array_merge($errores, practicante_carga_masiva_validar_encabezados($datosArchivo['headers']));

                if ($errores === [] && $datosArchivo['filas'] === []) {
                    $errores[] = 'El archivo no contiene filas de datos.';
                }

                if ($errores === [] && count($datosArchivo['filas']) > CARGA_MASIVA_MAX_FILAS) {
                    $errores[] = 'El archivo supera el máximo de ' . CARGA_MASIVA_MAX_FILAS . ' filas por carga. Divide el archivo en partes más pequeñas.';
                }

                if ($errores === []) {
                    set_time_limit(300);

                    $resumen = practicante_carga_masiva_procesar($datosArchivo['filas'], $catalogos);

                    auditoria_registrar('IMPORTAR_PRACTICANTES_CSV', [
                        'tabla_afectada' => 'practicantes',
                        'valores_nuevos' => [
                            'total_filas' => $resumen['total'],
                            'creados' => $resumen['creados'],
                            'fallidos' => $resumen['fallidos'],
                            'nombre_archivo' => $nombreSubido,
                        ],
                    ]);

                    $_SESSION['csrf_carga_masiva'] = bin2hex(random_bytes(32));
                }
            } catch (Throwable $exception) {
                error_log('Error al procesar carga masiva de practicantes: ' . $exception->getMessage());
                $errores[] = 'No fue posible procesar el archivo. Verifica el formato e inténtalo nuevamente.';
            }
        }
    }
}

