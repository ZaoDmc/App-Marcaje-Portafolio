<?php

declare(strict_types=1);

// Preparé autorización, CSRF y lecturas antes del layout; esta fase administra líderes, no calificaciones.
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once SERVICES_PATH . '/LiderazgoService.php';
auth_requiere_rol('ADMIN');

$pageTitle = 'Notas';
$errores = [];
$areas = [];
$integrantes = [];
$historialLideres = [];
$areaSeleccionada = null;
$migracionPendiente = false;
$motivo = '';
$candidatoSeleccionado = 0;
$_SESSION['csrf_notas_liderazgo'] ??= bin2hex(random_bytes(32));
$mensajeExito = $_SESSION['notas_liderazgo_exito'] ?? null;
unset($_SESSION['notas_liderazgo_exito']);

// Rechacé arrays, enteros negativos y valores ambiguos sin convertirlos en IDs válidos.
$idAreaTexto = is_post() ? input('id_area') : query_param('id_area');
$idArea = filter_var($idAreaTexto, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
if ($idAreaTexto !== null && $idArea === false) { $errores[] = 'Selecciona un área válida.'; }

try {
    $migracionPendiente = !liderazgos_disponible();
    if ($migracionPendiente) {
        // Actualicé la ruta al parche fechado y numerado del inventario database/README.md.
        $errores[] = 'Falta instalar la estructura de liderazgo. Aplica database/2026-10-19_patch_liderazgos_area_009.sql en la base del proyecto.';
    } else {
        if (is_post()) {
            $token = input('csrf_token');
            $accion = input('accion');
            $motivoEntrada = input('motivo');
            $motivo = is_string($motivoEntrada) ? $motivoEntrada : '';
            $esperado = filter_var(input('id_liderazgo_vigente'), FILTER_VALIDATE_INT, ['options'=>['min_range'=>0]]);
            $candidato = filter_var(input('id_practica'), FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
            $candidatoSeleccionado = $candidato === false ? 0 : $candidato;
            if (!is_string($token) || !hash_equals($_SESSION['csrf_notas_liderazgo'], $token)) {
                $errores[] = 'La sesión del formulario expiró. Recarga la página e intenta nuevamente.';
            }
            if (!in_array($accion, ['designar','retirar'], true) || $esperado === false || ($accion === 'designar' && $candidato === false)) {
                $errores[] = 'Selecciona una acción y un candidato válidos.';
            }
            if ($errores === []) {
                try {
                    liderazgos_guardar((int)$idArea, $accion === 'retirar' ? 0 : (int)$candidato, (int)$esperado, $motivo);
                    $_SESSION['csrf_notas_liderazgo'] = bin2hex(random_bytes(32));
                    $_SESSION['notas_liderazgo_exito'] = $accion === 'retirar'
                        ? 'Liderazgo retirado. El historial se conservó.' : 'Liderazgo actualizado correctamente.';
                    redirect_to('admin/notas.php?id_area=' . (int)$idArea);
                } catch (DomainException $exception) {
                    $errores[] = $exception->getMessage();
                }
            }
        }
        $areas = liderazgos_areas();
        // La primera área se muestra únicamente al entrar sin filtro; un ID inválido no cambia de destino.
        if ($idAreaTexto === null && $areas !== []) { $idArea = (int)$areas[0]['id_area']; }
        foreach ($areas as $area) {
            if ((int)$area['id_area'] === $idArea) { $areaSeleccionada = $area; break; }
        }
        if ($areaSeleccionada !== null) {
            $integrantes = liderazgos_integrantes((int)$idArea);
            $historialLideres = liderazgos_historial((int)$idArea);
        } elseif ($idArea !== false && $areas !== []) {
            $errores[] = 'No se encontró el área seleccionada.';
        }
    }
} catch (Throwable $exception) {
    error_log('Error en gestión de líderes: ' . $exception->getMessage());
    $errores[] = 'No fue posible procesar la gestión de líderes. Intenta nuevamente.';
}

require SRC_PATH . '/Controllers/Shared/LayoutContext.php';
require VIEWS_PATH . '/admin/notas.view.php';
