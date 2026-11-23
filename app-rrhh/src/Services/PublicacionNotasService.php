<?php

declare(strict_types=1);

require_once __DIR__.'/NotasFormularioService.php';

// Detecté borradores que el autor original ya no puede aprobar; no transfiero su responsabilidad al nuevo líder.
function notas_borrador_sin_responsable(array $nota): bool
{
    try { $destino=notas_destino((int)$nota['id_practica'],$nota['viernes']); }
    catch (DomainException) { return true; }
    if (!(int)$destino['activo'] || !(int)$destino['persona_activa'] ||
        !in_array($destino['estado_practica'],['PENDIENTE_INICIO','EN_PROGRESO'],true)) { return true; }
    $s=db()->prepare('SELECT u.id_usuario,u.id_practicante FROM usuarios_sistema u
        JOIN usuarios_roles ur ON ur.id_usuario=u.id_usuario AND ur.rol_activo=1
        JOIN roles_sistema r ON r.id_rol=ur.id_rol AND r.nombre="PRACTICANTE"
        WHERE u.id_usuario=? AND u.activo=1 AND u.estado_cuenta="ACTIVO"'.(db()->inTransaction()?' FOR UPDATE':''));
    $s->execute([$nota['id_evaluador']]); $responsable=$s->fetch();
    if (!$responsable) { return true; }
    $responsable['rol']='PRACTICANTE';
    try { notas_exigir_editor($responsable,$destino,$nota); return false; }
    catch (DomainException) { return true; }
}

// Preparé una publicación sin escribir: el envío definitivo revalida el mismo alcance bajo bloqueos.
function notas_preparar_publicacion(int $idPractica,string $viernes,array $valores,mixed $final,string $observacion,
    int $revisionEsperada,string $motivo,int $idRubrica,string $estadoEsperado): array
{
    notas_viernes($viernes);
    if (!in_array($estadoEsperado,['BORRADOR','APROBADA'],true) || $revisionEsperada<1) {
        throw new DomainException('Guarda primero el borrador que deseas publicar.');
    }
    $actor=notas_actor();
    $s=db()->prepare('SELECT * FROM notas_semanales WHERE id_practica=? AND viernes=?'.(db()->inTransaction()?' FOR UPDATE':''));
    $s->execute([$idPractica,$viernes]); $nota=$s->fetch();
    if (!$nota) { throw new DomainException('Guarda primero el borrador que deseas publicar.'); }
    $destino=notas_destino($idPractica,$viernes,$actor['rol']==='ADMIN'?(int)$nota['id_area']:null);
    notas_exigir_editor($actor,$destino,$nota);
    if ((int)$nota['revision']!==$revisionEsperada || $nota['estado']!==$estadoEsperado) {
        throw new DomainException('La evaluación cambió. Recarga antes de publicar.');
    }
    // ADMIN corrige cualquier publicada, pero la aprobación normal de integrantes corresponde a su líder.
    $resolucionExcepcional=false;
    if ($nota['estado']==='BORRADOR' && $actor['rol']==='ADMIN' && $nota['tipo_evaluado']!=='LIDER' && !$destino['es_lider']) {
        if (!notas_borrador_sin_responsable($nota)) {
            throw new DomainException('La aprobación de este integrante corresponde a su líder responsable.');
        }
        $resolucionExcepcional=true;
    }
    if ($actor['rol']!=='ADMIN' && (!(int)$destino['activo'] || !(int)$destino['persona_activa'] ||
        !in_array($destino['estado_practica'],['PENDIENTE_INICIO','EN_PROGRESO'],true))) {
        throw new DomainException('La práctica debe estar habilitada para publicar.');
    }
    if ((int)$nota['id_rubrica']!==$idRubrica) { throw new DomainException('La versión de criterios no corresponde a esta evaluación.'); }
    $rubrica=notas_rubrica($idRubrica);
    [$valores,$final,$observacion]=notas_validar_valores($valores,$final,$observacion,count($rubrica['criterios']));
    // Exigí completar cada criterio y la nota final según la regla confirmada para esta fase.
    if ($final===null || in_array(null,$valores,true)) { throw new DomainException('Completa todos los criterios y la nota final antes de publicar.'); }
    $motivo=trim($motivo);
    if (mb_strlen($motivo)>500 || (($nota['estado']==='APROBADA' || $resolucionExcepcional) && $motivo==='')) {
        throw new DomainException('Indica el motivo de la corrección, de hasta 500 caracteres.');
    }
    $s=db()->prepare('SELECT posicion,nota FROM notas_semanales_detalle WHERE id_nota=? ORDER BY posicion');
    $s->execute([$nota['id_nota']]); $anteriores=$s->fetchAll(PDO::FETCH_KEY_PAIR);
    // También exigí motivo si se cambian calificaciones u observaciones al publicar un borrador guardado.
    if ($motivo==='' && ($anteriores!=$valores || ($nota['nota_final']??null)!=$final || trim($nota['observacion']??'')!==$observacion)) {
        throw new DomainException('Indica un motivo para los cambios realizados antes de publicar.');
    }
    return ['nota'=>$nota,'actor'=>$actor,'rubrica'=>$rubrica,'valores'=>$valores,'final'=>$final,
        'observacion'=>$observacion,'motivo'=>$motivo,'anteriores'=>$anteriores,'resolucion_excepcional'=>$resolucionExcepcional];
}

// Publiqué estado, calificaciones, revisión e historial de manera atómica y sin ocultar la versión anterior durante una corrección.
function notas_publicar(int $idPractica,string $viernes,array $valores,mixed $final,string $observacion,
    int $revisionEsperada,string $motivo,int $idRubrica,string $estadoEsperado,bool $confirmado): array
{
    if (!$confirmado) { throw new DomainException('Confirma expresamente la publicación de la evaluación.'); }
    $pdo=db(); $pdo->beginTransaction();
    try {
        $actor=notas_actor();
        // Conservé el orden de bloqueo de prácticas utilizado por guardados, traslados y liderazgo.
        $s=$pdo->prepare('SELECT id_practica FROM practicas WHERE id_practica=? OR id_practicante=? ORDER BY id_practica FOR UPDATE');
        $s->execute([$idPractica,$actor['id_practicante']??0]); $s->fetchAll();
        $p=notas_preparar_publicacion($idPractica,$viernes,$valores,$final,$observacion,$revisionEsperada,$motivo,$idRubrica,$estadoEsperado);
        $anterior=$p['nota']; $id=(int)$anterior['id_nota']; $revision=$revisionEsperada+1; $ahora=date('Y-m-d H:i:s');
        $s=$pdo->prepare('UPDATE notas_semanales SET estado="APROBADA",nota_final=?,observacion=?,revision=?,actualizado_en=?,aprobado_por=?,aprobado_en=? WHERE id_nota=?');
        $s->execute([$p['final'],$p['observacion'],$revision,$ahora,$actor['id_usuario'],$ahora,$id]);
        $s=$pdo->prepare('DELETE FROM notas_semanales_detalle WHERE id_nota=?'); $s->execute([$id]);
        $s=$pdo->prepare('INSERT INTO notas_semanales_detalle (id_nota,posicion,nota) VALUES (?,?,?)');
        foreach ($p['valores'] as $posicion=>$valor) { $s->execute([$id,$posicion,$valor]); }
        $s=$pdo->prepare('SELECT * FROM notas_semanales WHERE id_nota=?'); $s->execute([$id]); $contenido=$s->fetch();
        $operacion=$anterior['estado']==='APROBADA'?'CORREGIR_NOTA_PUBLICADA':($p['resolucion_excepcional']?'RESOLVER_BORRADOR_NOTA':'PUBLICAR_NOTA_SEMANAL');
        $contenido['detalle']=$p['valores']; $contenido['criterios']=$p['rubrica']['criterios']; $contenido['operacion']=$operacion;
        $motivoRegistro=$p['motivo']?:'Aprobación y publicación de evaluación completa';
        $s=$pdo->prepare('INSERT INTO notas_semanales_historial (id_nota,revision,id_actor,motivo,contenido_json,registrado_en) VALUES (?,?,?,?,?,?)');
        $s->execute([$id,$revision,$actor['id_usuario'],$motivoRegistro,json_encode($contenido,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),$ahora]);
        if (!auditoria_registrar($operacion,['tabla_afectada'=>'notas_semanales','id_registro_afectado'=>$id,
            'valores_anteriores'=>array_merge($anterior,['detalle'=>$p['anteriores']]),
            'valores_nuevos'=>['revision'=>$revision,'nota_final'=>$p['final'],'motivo'=>$motivoRegistro]])) {
            throw new RuntimeException('No fue posible registrar la auditoría de publicación.');
        }
        $pdo->commit(); return ['id_nota'=>$id,'revision'=>$revision,'operacion'=>$operacion];
    } catch (Throwable $e) { if ($pdo->inTransaction()) { $pdo->rollBack(); } throw $e; }
}

// El historial interno solo lo consulta ADMIN o el evaluador competente, nunca el propio evaluado ordinario.
function notas_historial_revisiones(int $idNota): array
{
    $nota=notas_consultar($idNota); $actor=notas_actor();
    if ($actor['rol']!=='ADMIN') {
        notas_exigir_editor($actor,notas_destino((int)$nota['id_practica'],$nota['viernes']),$nota);
    }
    $s=db()->prepare('SELECT h.*,u.email AS actor_email FROM notas_semanales_historial h
        JOIN usuarios_sistema u ON u.id_usuario=h.id_actor WHERE h.id_nota=? ORDER BY h.revision DESC');
    $s->execute([$idNota]); $filas=$s->fetchAll();
    foreach ($filas as &$fila) { $fila['contenido']=json_decode($fila['contenido_json'],true,512,JSON_THROW_ON_ERROR); }
    unset($fila); return $filas;
}

// Listé exclusivamente versiones vigentes aprobadas de las prácticas pertenecientes a la cuenta autenticada.
function notas_propias_publicadas(): array
{
    $actor=notas_actor();
    if ($actor['rol']!=='PRACTICANTE' || empty($actor['id_practicante'])) { throw new DomainException('Solo puedes consultar las notas de tu propia práctica.'); }
    $s=db()->prepare('SELECT n.id_nota,n.id_practica,n.viernes,n.nota_final,n.aprobado_en,n.revision
        FROM notas_semanales n JOIN practicas pr ON pr.id_practica=n.id_practica
        WHERE pr.id_practicante=? AND n.estado="APROBADA" ORDER BY n.viernes DESC,n.id_practica DESC');
    $s->execute([$actor['id_practicante']]); return $s->fetchAll();
}

// Comprobé dueño y publicación juntos antes de devolver un detalle; un ID ajeno no revela ni el estado del registro.
function notas_consultar_propia(int $idNota): array
{
    $permitidas=array_map('intval',array_column(notas_propias_publicadas(),'id_nota'));
    if (!in_array($idNota,$permitidas,true)) { throw new DomainException('Evaluación no disponible para tu cuenta.'); }
    $nota=notas_consultar($idNota); $actor=notas_actor();
    if ((int)$nota['id_practicante']!==(int)$actor['id_practicante'] || $nota['estado']!=='APROBADA') {
        throw new DomainException('Evaluación no disponible para tu cuenta.');
    }
    return $nota;
}
