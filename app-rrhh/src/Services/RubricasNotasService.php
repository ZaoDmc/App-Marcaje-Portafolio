<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

// Agregué detección de la configuración sin crear tablas al visitar una pantalla.
function notas_formulario_disponible(): bool
{
    return notas_disponible() && db()->query("SHOW TABLES LIKE 'configuracion_notas'")->fetchColumn() !== false;
}

// Validé criterios explícitos y un límite técnico para no guardar rúbricas vacías o desmesuradas.
function notas_validar_criterios(array $criterios): array
{
    if (!array_is_list($criterios) || count($criterios)<1 || count($criterios)>50) {
        throw new DomainException('La rúbrica debe contener entre 1 y 50 criterios.');
    }
    foreach ($criterios as &$criterio) {
        if (!is_string($criterio) || trim($criterio)==='' || mb_strlen($criterio)>500) {
            throw new DomainException('Escribe cada criterio, con un máximo de 500 caracteres.');
        }
        $criterio=trim($criterio);
    }
    unset($criterio);
    return $criterios;
}

// Recuperé la versión guardada en la nota; sus etiquetas no dependen de la rúbrica actualmente activa.
function notas_rubrica(int $idRubrica): array
{
    $s=db()->prepare('SELECT * FROM rubricas_notas WHERE id_rubrica=?'); $s->execute([$idRubrica]);
    $rubrica=$s->fetch();
    if (!$rubrica) { throw new DomainException('La versión de criterios no está disponible.'); }
    $rubrica['criterios']=notas_validar_criterios(json_decode($rubrica['criterios_json'],true,512,JSON_THROW_ON_ERROR));
    return $rubrica;
}

// Bloqueé la versión activa durante un guardado para detectar cambios de criterios entre pestañas.
function notas_rubrica_actual(): array
{
    if (!notas_formulario_disponible()) { throw new DomainException('Falta aplicar database/2026-10-19_patch_rubricas_configurables_011.sql.'); }
    $s=db()->query('SELECT * FROM configuracion_notas WHERE id=1'.(db()->inTransaction()?' FOR UPDATE':''));
    $config=$s->fetch();
    if (!$config) { throw new DomainException('No está configurada la rúbrica vigente.'); }
    return array_merge(notas_rubrica((int)$config['id_rubrica']),['revision_config'=>(int)$config['revision']]);
}

// ADMIN añade o quita filas creando otra versión; no reescribí versiones usadas por notas o borradores.
function notas_guardar_rubrica(array $criterios,string $descripcion,int $revisionEsperada,string $motivo): int
{
    $criterios=notas_validar_criterios($criterios); $descripcion=trim($descripcion); $motivo=trim($motivo);
    if ($descripcion==='' || mb_strlen($descripcion)>250 || $motivo==='' || mb_strlen($motivo)>500) {
        throw new DomainException('Indica un nombre de hasta 250 caracteres y un motivo de hasta 500.');
    }
    $pdo=db(); $pdo->beginTransaction();
    try {
        $actor=notas_actor();
        if ($actor['rol']!=='ADMIN') { throw new DomainException('Solo ADMIN puede cambiar los criterios de evaluación.'); }
        $anterior=notas_rubrica_actual();
        if ($anterior['revision_config']!==$revisionEsperada) { throw new DomainException('Los criterios cambiaron. Recarga antes de continuar.'); }
        $s=$pdo->prepare('INSERT INTO rubricas_notas (codigo,descripcion,criterios_json) VALUES (?,?,?)');
        $s->execute(['rubrica-'.bin2hex(random_bytes(12)),$descripcion,json_encode($criterios,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE)]);
        $id=(int)$pdo->lastInsertId();
        $s=$pdo->prepare('UPDATE configuracion_notas SET id_rubrica=?,revision=revision+1,actualizado_por=?,motivo=?,actualizado_en=? WHERE id=1');
        $s->execute([$id,$actor['id_usuario'],$motivo,date('Y-m-d H:i:s')]);
        if (!auditoria_registrar('CAMBIAR_RUBRICA_NOTAS',['tabla_afectada'=>'rubricas_notas','id_registro_afectado'=>$id,
            'valores_anteriores'=>['id_rubrica'=>$anterior['id_rubrica']],
            'valores_nuevos'=>['id_rubrica'=>$id,'criterios'=>$criterios,'motivo'=>$motivo]])) {
            throw new RuntimeException('No fue posible registrar la auditoría de criterios.');
        }
        $pdo->commit(); return $id;
    } catch (Throwable $e) { if ($pdo->inTransaction()) { $pdo->rollBack(); } throw $e; }
}
