<?php

declare(strict_types=1);

// Trasladé la consulta del badge de permisos desde el sidebar: se prepara
// antes del HTML para que el layout solo presente datos de la sesión y del controlador.
require_once SERVICES_PATH . '/PermisosService.php';
// Preparé las acciones de evaluación por liderazgo vigente; Notas y la consulta propia son visibles para todos los practicantes.
$mostrarNotasLider=false;
// Evalué el modo solo lectura del practicante antes de pintar el layout para mostrar el aviso global.
$modoSoloLecturaPracticante = ['solo_lectura' => false, 'meta_cumplida' => false, 'practica_egresada' => false, 'motivo' => null];
if (auth_rol_activo()==='PRACTICANTE') {
    require_once SERVICES_PATH.'/NotasFormularioService.php';
    require_once SERVICES_PATH.'/ReglasAsistenciaService.php';
    try { $mostrarNotasLider=notas_es_lider_actual(); }
    catch (Throwable $e) { error_log('No se pudo preparar el acceso a Notas: '.$e->getMessage()); }
    try {
        $idPractLayout = function_exists('auth_practicante_actual_id') ? auth_practicante_actual_id() : null;
        if ($idPractLayout !== null && function_exists('asistencia_estado_solo_lectura_practicante')) {
            $modoSoloLecturaPracticante = asistencia_estado_solo_lectura_practicante($idPractLayout);
        }
    } catch (Throwable $e) {
        error_log('No se pudo evaluar el modo solo lectura del practicante: ' . $e->getMessage());
    }
}
$permisosPendientes = in_array(auth_rol_activo(), ['ADMIN', 'SUPERVISOR'], true)
    ? permisos_gestion_contar_pendientes() : 0;
// Agregué los expedientes excepcionales al aviso existente sin alterar el contador de permisos normales.
if(in_array(auth_rol_activo(),['ADMIN','SUPERVISOR'],true)) {
    $permisosPendientes += tiempo_contar_pendientes();
    // Incluí colaciones pendientes en el aviso del menú, conservando el alcance autorizado.
    $permisosPendientes += count(array_filter(colacion_listar(),static fn(array $r):bool=>$r['estado']==='PENDIENTE'));
}
