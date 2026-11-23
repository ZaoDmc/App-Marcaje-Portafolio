<?php

declare(strict_types=1);

// Trasladé el manejador POST desde la capa de negocio; conserva CSRF y autorización.
if (!function_exists('catalogos_admin_procesar_post')) {
    function catalogos_admin_procesar_post(): array
    {
        if (!is_post()) {
            return ['ok' => null, 'errores' => [], 'mensaje' => null, 'catalogo' => null];
        }

        $clave = (string) input('catalogo', '');

        if (catalogos_admin_definicion($clave) === null) {
            return ['ok' => false, 'errores' => ['Catálogo no reconocido.'], 'mensaje' => null, 'catalogo' => $clave];
        }

        if (!hash_equals(catalogos_admin_token(), (string) input('csrf_token', ''))) {
            return ['ok' => false, 'errores' => ['La sesión del formulario expiró. Intenta nuevamente.'], 'mensaje' => null, 'catalogo' => $clave];
        }

        $accion = (string) input('accion', '');

        if ($accion === 'guardar') {
            $idTexto = (string) input('id', '');
            $id = ctype_digit($idTexto) && $idTexto !== '' ? (int) $idTexto : null;
            $resultado = catalogos_admin_guardar($clave, $id, (array) input('campos', []));

            return array_merge($resultado, ['catalogo' => $clave]);
        }

        if ($accion === 'cambiar_estado') {
            $id = (int) input('id', 0);
            $activo = (string) input('activo', '1') === '1';

            if ($id <= 0) {
                return ['ok' => false, 'errores' => ['Registro no válido.'], 'mensaje' => null, 'catalogo' => $clave];
            }

            $resultado = catalogos_admin_cambiar_estado($clave, $id, $activo);

            return array_merge($resultado, ['catalogo' => $clave]);
        }

        return ['ok' => false, 'errores' => ['Acción no reconocida.'], 'mensaje' => null, 'catalogo' => $clave];
    }
}


// Trasladé la autorización, POST y preparación de datos fuera del servicio/modelo.
// Este controlador compartido se ejecuta antes del layout, con parámetros del controlador por rol.
auth_requiere_rol('ADMIN');

$token = catalogos_admin_token();

$resultado = [];
try {
    $resultado = catalogos_admin_procesar_post();
} catch (Throwable $exception) {
    error_log('Error al procesar catálogo: ' . $exception->getMessage());
    $resultado = ['ok' => false, 'errores' => ['No fue posible procesar la solicitud.'], 'mensaje' => null, 'catalogo' => null];
}

$errores = $resultado['errores'] ?? [];
$mensajeExito = ($resultado['ok'] ?? null) === true ? (string) ($resultado['mensaje'] ?? 'Operación realizada correctamente.') : null;

$definiciones = catalogos_admin_definiciones();
$catalogoActivo = (string) ($resultado['catalogo'] ?? query_param('catalogo', 'instituciones'));

if (!isset($definiciones[$catalogoActivo])) {
    $catalogoActivo = 'instituciones';
}

$filasPorCatalogo = [];
foreach ($definiciones as $clave => $definicionCatalogo) {
    try {
        $filasPorCatalogo[$clave] = catalogos_admin_listar($clave);
    } catch (Throwable $exception) {
        error_log('Error al listar catálogo ' . $clave . ': ' . $exception->getMessage());
        $filasPorCatalogo[$clave] = [];
    }
}

$definicion = $definiciones[$catalogoActivo];

// Preparé las opciones relacionadas antes del layout; el formulario no consulta
// catálogos desde la vista y un fallo se informa sin interrumpir el HTML.
$opcionesPorCampo = [];
foreach ($definicion['campos'] as $campo => $metaCampo) {
    if (($metaCampo['tipo'] ?? 'texto') === 'catalogo') {
        try {
            $opcionesPorCampo[$campo] = catalogos_admin_opciones_fk($metaCampo['catalogo_fk']);
        } catch (Throwable $exception) {
            error_log('Error al cargar opciones de catálogo: ' . $exception->getMessage());
            $opcionesPorCampo[$campo] = [];
            $errores[] = 'No fue posible cargar las opciones del catálogo relacionado.';
        }
    }
}

$idEditar = null;
$guardadoFallido = ($resultado['ok'] ?? null) === false && (string) input('accion', '') === 'guardar' && $catalogoActivo === (string) input('catalogo', '');

if ($guardadoFallido) {
    $idTexto = (string) input('id', '');
    $idEditar = ctype_digit($idTexto) && $idTexto !== '' ? (int) $idTexto : null;
} elseif (($resultado['ok'] ?? null) === null) {
    $idTexto = (string) query_param('editar', '');
    $idEditar = ctype_digit($idTexto) && $idTexto !== '' ? (int) $idTexto : null;
}

$valoresFormulario = [];

if ($idEditar !== null) {
    foreach ($filasPorCatalogo[$catalogoActivo] as $fila) {
        if ((int) $fila[$definicion['pk']] === $idEditar) {
            $valoresFormulario = $fila;
            break;
        }
    }

    if ($valoresFormulario === []) {
        $idEditar = null;
    }
}

if ($guardadoFallido) {
    $valoresFormulario = array_merge($valoresFormulario, (array) input('campos', []));
}
