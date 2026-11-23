<?php

declare(strict_types=1);

require_once __DIR__.'/NotasService.php';
require_once __DIR__.'/LiderazgoService.php';

// Preparé el acceso del menú con liderazgo real, sin conceder un rol global nuevo.
function notas_es_lider_actual(): bool
{
    $actor=notas_actor();
    if ($actor['rol']!=='PRACTICANTE' || !liderazgos_disponible()) { return false; }
    $s=db()->prepare('SELECT l.id_liderazgo FROM liderazgos_area l
        JOIN practicas pr ON pr.id_practica=l.id_practica
        JOIN practicantes p ON p.id_practicante=pr.id_practicante
        JOIN estados_practica ep ON ep.id_estado_practica=pr.id_estado_practica
        JOIN areas_practica a ON a.id_area=l.id_area AND a.activo=1
        JOIN historial_areas_practicante h ON h.id_practica=pr.id_practica AND h.id_area=l.id_area
        WHERE pr.id_practicante=? AND pr.activo=1 AND p.activo=1
          AND ep.nombre IN ("PENDIENTE_INICIO","EN_PROGRESO")
          AND l.fecha_fin IS NULL AND l.fecha_inicio<=? AND h.fecha_fin IS NULL AND h.fecha_inicio<=?');
    $s->execute([$actor['id_practicante'],date('Y-m-d H:i:s'),date('Y-m-d')]);
    return $s->fetchColumn()!==false;
}

// ADMIN ve prácticas ajenas; el líder solo integrantes de las áreas que lidera actualmente.
function notas_destinatarios(): array
{
    $actor=notas_actor();
    if ($actor['rol']!=='ADMIN' && !notas_es_lider_actual()) { throw new DomainException('Solo un líder vigente puede evaluar a su equipo.'); }
    $sql='SELECT pr.id_practica,pr.activo,p.nombre_1,p.apellido_paterno,p.email,
        (SELECT GROUP_CONCAT(a.nombre SEPARATOR ", ") FROM historial_areas_practicante h
         JOIN areas_practica a ON a.id_area=h.id_area WHERE h.id_practica=pr.id_practica
           AND h.fecha_fin IS NULL AND h.fecha_inicio<=?) AS area_nombre,
        EXISTS(SELECT 1 FROM liderazgos_area l WHERE l.id_practica=pr.id_practica AND l.fecha_fin IS NULL AND l.fecha_inicio<=?) AS es_lider
        FROM practicas pr JOIN practicantes p ON p.id_practicante=pr.id_practicante
        WHERE pr.id_practicante<>?';
    $params=[date('Y-m-d'),date('Y-m-d H:i:s'),$actor['id_practicante'] ?? 0];
    if ($actor['rol']!=='ADMIN') {
        $sql.=' AND pr.activo=1 AND p.activo=1 AND EXISTS (
            SELECT 1 FROM historial_areas_practicante h JOIN liderazgos_area l ON l.id_area=h.id_area
            JOIN practicas responsable ON responsable.id_practica=l.id_practica
            JOIN practicantes persona ON persona.id_practicante=responsable.id_practicante
            JOIN estados_practica ep ON ep.id_estado_practica=responsable.id_estado_practica
            JOIN historial_areas_practicante hr ON hr.id_practica=responsable.id_practica AND hr.id_area=l.id_area
            JOIN areas_practica a ON a.id_area=l.id_area AND a.activo=1
            WHERE h.id_practica=pr.id_practica AND h.fecha_fin IS NULL AND h.fecha_inicio<=?
              AND l.fecha_fin IS NULL AND l.fecha_inicio<=? AND responsable.id_practicante=?
              AND responsable.activo=1 AND persona.activo=1 AND ep.nombre IN ("PENDIENTE_INICIO","EN_PROGRESO")
              AND hr.fecha_fin IS NULL AND hr.fecha_inicio<=?)';
        array_push($params,date('Y-m-d'),date('Y-m-d H:i:s'),$actor['id_practicante'],date('Y-m-d'));
    }
    $s=db()->prepare($sql.' ORDER BY p.apellido_paterno,p.nombre_1,pr.id_practica DESC'); $s->execute($params);
    return $s->fetchAll();
}

// Verifiqué el alcance también al consultar IDs directos, no solamente al construir el selector.
function notas_destinatario_visible(int $idPractica): array
{
    foreach (notas_destinatarios() as $practica) {
        if ((int)$practica['id_practica']===$idPractica) { return $practica; }
    }
    throw new DomainException('La práctica no pertenece a tu ámbito de evaluación.');
}

// Mostré periodos ocupados sin revelar calificaciones ni borradores de otros responsables.
function notas_periodos(int $idPractica): array
{
    notas_destinatario_visible($idPractica);
    $s=db()->prepare('SELECT id_nota,viernes FROM notas_semanales WHERE id_practica=? ORDER BY viernes DESC');
    $s->execute([$idPractica]); $periodos=[];
    foreach ($s->fetchAll() as $fila) {
        try { $nota=notas_consultar((int)$fila['id_nota']); }
        catch (DomainException) { $nota=['id_nota'=>null,'viernes'=>$fila['viernes'],'estado'=>'OTRO_RESPONSABLE']; }
        $periodos[]=$nota;
    }
    return $periodos;
}
