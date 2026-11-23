<?php

declare(strict_types=1);

require_once __DIR__ . '/NotasService.php';

// Centralicé el resumen oficial de consulta; no copia calificaciones al puntaje histórico de Acompañamiento.
function notas_resumen_supervision(array $idsPractica): array
{
    $actor = notas_actor();
    if (!in_array($actor['rol'], ['ADMIN', 'SUPERVISOR'], true)) {
        throw new DomainException('Sin autorización para consultar notas desde supervisión.');
    }
    $ids = [];
    foreach ($idsPractica as $id) {
        if (!is_int($id) || $id <= 0) {
            throw new DomainException('Práctica no disponible para supervisión.');
        }
        $ids[$id] = $id;
    }
    if ($ids === []) { return []; }
    // Revalidé existencia y conflicto de autosupervisión con la identidad actual de la base, en una consulta por lote.
    $marcas = implode(',', array_fill(0, count($ids), '?'));
    $alcance = db()->prepare('SELECT id_practica,id_practicante FROM practicas WHERE id_practica IN ('.$marcas.')');
    $alcance->execute(array_values($ids));
    $practicas = $alcance->fetchAll();
    if (count($practicas) !== count($ids)) { throw new DomainException('Práctica no disponible para supervisión.'); }
    foreach ($practicas as $practica) {
        if ($actor['rol'] === 'SUPERVISOR' && (int)$practica['id_practicante'] === (int)($actor['id_practicante'] ?? 0)) {
            throw new DomainException('Práctica no disponible para supervisión.');
        }
    }
    $disponible = notas_disponible();
    $resultado = array_fill_keys($ids, ['disponible' => $disponible, 'ultima' => null, 'cantidad' => 0]);
    if (!$disponible) { return $resultado; }
    // Elegí por viernes, no por fecha de carga: completar una semana atrasada no reemplaza la más reciente.
    // Solo seleccioné campos públicos; borradores, observaciones, motivos e historial interno no salen de esta consulta.
    $s = db()->prepare('SELECT n.id_practica,n.viernes,n.nota_final,n.revision,
        (SELECT COUNT(*) FROM notas_semanales c WHERE c.id_practica=n.id_practica AND c.estado="APROBADA") AS cantidad
        FROM notas_semanales n WHERE n.id_practica IN ('.$marcas.') AND n.estado="APROBADA"
        AND NOT EXISTS (SELECT 1 FROM notas_semanales posterior WHERE posterior.id_practica=n.id_practica
            AND posterior.estado="APROBADA" AND posterior.viernes>n.viernes)');
    $s->execute(array_values($ids));
    foreach ($s->fetchAll() as $fila) {
        $resultado[(int)$fila['id_practica']]['ultima'] = [
            'viernes' => $fila['viernes'], 'nota_final' => $fila['nota_final'], 'revision' => (int)$fila['revision'],
        ];
        $resultado[(int)$fila['id_practica']]['cantidad'] = (int)$fila['cantidad'];
    }
    return $resultado;
}
