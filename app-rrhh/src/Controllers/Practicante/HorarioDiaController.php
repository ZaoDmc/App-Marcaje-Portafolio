<?php

declare(strict_types=1);

// Trasladé el endpoint al controlador MVC. El catálogo conserva la URL
// /practicante/api/horario-dia.php y limita su acceso a PRACTICANTE y GET/HEAD.
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once SERVICES_PATH . '/PermisosService.php';

auth_requiere_rol('PRACTICANTE');
header('Content-Type: application/json; charset=utf-8');

$idPracticante = auth_practicante_actual_id();
if ($idPracticante === null) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Acceso no autorizado.']);
    exit;
}

// Reforcé la entrada para rechazar arrays y fechas inexistentes sin avisos PHP;
// conservé los códigos y mensajes de validación del contrato anterior.
$fechaInput = query_param('fecha', '');
$fecha = is_string($fechaInput) ? trim($fechaInput, " \t\r\n") : '';
$fechaObjeto = preg_match('/^\d{4}-\d{2}-\d{2}$/D', $fecha)
    ? DateTimeImmutable::createFromFormat('!Y-m-d', $fecha) : false;
if ($fechaObjeto === false || $fechaObjeto->format('Y-m-d') !== $fecha) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Fecha inválida.']);
    exit;
}

try {
    $practica = permisos_gestion_practica_activa($idPracticante);
    if ($practica === null) {
        echo json_encode(['ok' => false, 'error' => 'Sin práctica activa.']);
        exit;
    }
    // Permití consultar ausencias anteriores únicamente dentro de la práctica propia vigente.
    if ($fecha < $practica['fecha_inicio_practica']
        || (!empty($practica['fecha_termino_practica']) && $fecha > $practica['fecha_termino_practica'])) {
        echo json_encode(['ok' => false, 'error' => 'La fecha está fuera de la vigencia de tu práctica.']);
        exit;
    }
    // Añadí consulta de opciones sin alterar el contrato del horario usado para solicitar ausencia.
    if (query_param('recuperacion','')==='1') {
        echo json_encode(['ok'=>true,'opciones'=>recuperaciones_opciones_fecha((int)$practica['id_practica'],$fecha)]);
        exit;
    }
    $horario = permisos_gestion_horario_del_dia((int) $practica['id_practica'], $fecha);
    if ($horario === null) {
        echo json_encode(['ok' => false, 'error' => 'No tienes jornada planificada para ese día.']);
        exit;
    }
    // Conservé los nombres y tipos de campos consumidos por el formulario.
    echo json_encode([
        'ok' => true,
        'entrada' => $horario['entrada'],
        'salida' => $horario['salida'],
        'colacion_inicio' => $horario['colacion_inicio'],
        'colacion_fin' => $horario['colacion_fin'],
        'horas_planificadas' => $horario['horas_planificadas'],
    ]);
} catch (Throwable $exception) {
    error_log('Error en consulta de horario diario: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error interno.']);
}
