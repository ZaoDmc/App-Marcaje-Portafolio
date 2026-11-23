<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
// Incorporé versiones configurables de criterios; las notas conservan su rúbrica de origen.
require_once __DIR__ . '/RubricasNotasService.php';

// Agregué una base de notas sin exponer rutas ni publicar evaluaciones en esta fase.
function notas_disponible(): bool
{
    foreach (['rubricas_notas','notas_semanales','notas_semanales_detalle','notas_semanales_historial'] as $tabla) {
        if (db()->query("SHOW TABLES LIKE '$tabla'")->fetchColumn() === false) { return false; }
    }
    return true;
}

// Separé el viernes histórico de la fecha real de carga, usando siempre Chile.
function notas_viernes(string $valor): string
{
    $zona = new DateTimeZone('America/Santiago');
    $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $valor, $zona);
    $hoy = new DateTimeImmutable('today', $zona);
    $viernesActual = $hoy->modify('+' . ((5 - (int)$hoy->format('N') + 7) % 7) . ' days');
    // El sábado/domingo siguen perteneciendo al viernes recién pasado, no al de la semana siguiente.
    if ((int)$hoy->format('N') > 5) { $viernesActual = $hoy->modify('last friday'); }
    if (!$fecha || $fecha->format('Y-m-d') !== $valor || $fecha->format('N') !== '5' || $fecha > $viernesActual) {
        throw new DomainException('Selecciona un viernes válido, actual o anterior.');
    }
    return $valor;
}

// Consulté la cuenta y el rol vigente en la base; no confié en IDs de practicante enviados por formularios.
function notas_actor(): array
{
    if (!auth_check()) { throw new DomainException('Debes iniciar sesión.'); }
    // Usé lecturas actuales al guardar, evitando permisos obsoletos por el snapshot de InnoDB.
    $bloqueo = db()->inTransaction() ? ' FOR UPDATE' : '';
    $s = db()->prepare('SELECT u.id_usuario,u.id_practicante,r.nombre AS rol FROM usuarios_sistema u
        JOIN usuarios_roles ur ON ur.id_usuario=u.id_usuario AND ur.rol_activo=1
        JOIN roles_sistema r ON r.id_rol=ur.id_rol
        WHERE u.id_usuario=? AND u.activo=1 AND u.estado_cuenta="ACTIVO" AND r.id_rol=? AND r.nombre=?'.$bloqueo);
    $s->execute([auth_usuario_actual_id(), auth_id_rol_activo(), auth_rol_activo()]);
    $actor = $s->fetch();
    if (!$actor) { throw new DomainException('La cuenta o el rol activo no están habilitados.'); }
    return $actor;
}

// Revalidé pertenencia actual y liderazgo del evaluado, incluyendo el viernes consultado.
function notas_destino(int $idPractica, string $viernes, ?int $areaHistorica = null): array
{
    // Las lecturas de autorización dentro de la transacción ven y bloquean el estado vigente.
    $bloqueo = db()->inTransaction() ? ' FOR UPDATE' : '';
    $s = db()->prepare('SELECT pr.*,p.activo AS persona_activa,ep.nombre AS estado_practica
        FROM practicas pr JOIN practicantes p ON p.id_practicante=pr.id_practicante
        JOIN estados_practica ep ON ep.id_estado_practica=pr.id_estado_practica WHERE pr.id_practica=?'.$bloqueo);
    $s->execute([$idPractica]); $practica = $s->fetch();
    if (!$practica) { throw new DomainException('La práctica no existe.'); }
    // ADMIN conserva el área guardada al corregir una nota existente, aun tras una baja o traslado.
    if ($areaHistorica !== null) { $practica['id_area'] = $areaHistorica; }
    else {
        $s = db()->prepare('SELECT h.id_area FROM historial_areas_practicante h JOIN areas_practica a ON a.id_area=h.id_area
            WHERE h.id_practica=? AND h.fecha_fin IS NULL AND h.fecha_inicio<=? AND a.activo=1'.$bloqueo);
        $s->execute([$idPractica, date('Y-m-d')]); $areas = $s->fetchAll(PDO::FETCH_COLUMN);
        if (count($areas) !== 1) { throw new DomainException('La práctica debe tener una única área vigente y activa.'); }
        $practica['id_area'] = (int)$areas[0];
    }
    $s = db()->prepare('SELECT id_liderazgo FROM liderazgos_area WHERE id_practica=?
        AND ((fecha_fin IS NULL AND fecha_inicio<=?) OR (fecha_inicio<=? AND (fecha_fin IS NULL OR fecha_fin>=?)))'.$bloqueo);
    $s->execute([$idPractica, date('Y-m-d H:i:s'), $viernes.' 23:59:59', $viernes.' 00:00:00']);
    $practica['es_lider'] = $s->fetchColumn() !== false;
    return $practica;
}

// Centralicé permisos: ADMIN puede corregir cualquier nota ajena; un líder nuevo no hereda ediciones.
function notas_exigir_editor(array $actor, array $destino, ?array $nota = null): ?int
{
    if ((int)($actor['id_practicante'] ?? 0) === (int)$destino['id_practicante']) {
        throw new DomainException('No puedes evaluar ni editar tus propias notas.');
    }
    if ($actor['rol'] === 'ADMIN') {
        if ($nota === null && !$destino['es_lider']) {
            throw new DomainException('ADMIN registra evaluaciones nuevas de líderes; puede corregir notas existentes de cualquier practicante.');
        }
        return null;
    }
    if ($actor['rol'] !== 'PRACTICANTE' || $destino['es_lider'] || ($nota !== null && $nota['tipo_evaluado'] === 'LIDER')) {
        throw new DomainException('No tienes autorización para evaluar esta práctica.');
    }
    $bloqueo = db()->inTransaction() ? ' FOR UPDATE' : '';
    $s = db()->prepare('SELECT l.id_liderazgo FROM liderazgos_area l
        JOIN practicas pr ON pr.id_practica=l.id_practica
        JOIN practicantes p ON p.id_practicante=pr.id_practicante
        JOIN historial_areas_practicante h ON h.id_practica=pr.id_practica AND h.id_area=l.id_area
        JOIN estados_practica ep ON ep.id_estado_practica=pr.id_estado_practica
        WHERE l.id_area=? AND l.fecha_fin IS NULL AND l.fecha_inicio<=? AND pr.id_practicante=?
          AND pr.activo=1 AND p.activo=1 AND ep.nombre IN ("PENDIENTE_INICIO","EN_PROGRESO")
          AND h.fecha_fin IS NULL AND h.fecha_inicio<=?'.$bloqueo);
    $s->execute([$destino['id_area'], date('Y-m-d H:i:s'), $actor['id_practicante'], date('Y-m-d')]);
    $liderazgo = $s->fetchColumn();
    if ($liderazgo === false || ($nota !== null && (
        (int)$nota['id_area'] !== $destino['id_area'] ||
        (int)$nota['id_evaluador'] !== (int)$actor['id_usuario'] ||
        (int)$nota['id_liderazgo'] !== (int)$liderazgo
    ))) { throw new DomainException('Solo el líder responsable puede editar su evaluación; ADMIN resuelve las anteriores.'); }
    return (int)$liderazgo;
}

// Validé las posiciones de la versión elegida, sin redondear notas ni calcular promedios.
function notas_validar_valores(array $notas, mixed $final, string $observacion, int $filas=10): array
{
    if ($filas<1 || $filas>50 || array_keys($notas) !== range(1,$filas) || mb_strlen($observacion) > 5000) {
        throw new DomainException('Envía todas las evaluaciones de la rúbrica y una observación de hasta 5000 caracteres.');
    }
    $validar = static function (mixed $valor, bool $medio): ?string {
        if ($valor === null || $valor === '') { return null; }
        if ((!is_string($valor) && !is_int($valor) && !is_float($valor)) ||
            !preg_match('/^[1-7](?:\.[0-9]{1,2})?$/D', (string)$valor)) {
            throw new DomainException('Las notas deben estar entre 1 y 7.');
        }
        $numero = (float)$valor;
        if ($numero > 7 || ($medio && floor($numero * 2) !== $numero * 2)) {
            throw new DomainException('Las evaluaciones utilizan incrementos de 0,5 entre 1 y 7.');
        }
        return number_format($numero, 2, '.', '');
    };
    return [array_map(static fn($n) => $validar($n, true), $notas), $validar($final, false), trim($observacion)];
}

// Guardé borrador, detalle, versión y auditoría en una transacción con control de formulario obsoleto.
function notas_guardar_borrador(int $idPractica, string $viernes, array $notas, mixed $final,
    string $observacion, int $revisionEsperada = 0, string $motivo = '', ?int $idRubricaEsperada=null): array
{
    $viernes = notas_viernes($viernes);
    $motivo = trim($motivo);
    if ($revisionEsperada < 0 || mb_strlen($motivo) > 500 || ($revisionEsperada > 0 && $motivo === '')) {
        throw new DomainException('Indica el motivo de la edición, de hasta 500 caracteres.');
    }
    if (!notas_disponible()) { throw new DomainException('Falta aplicar database/2026-10-19_patch_notas_semanales_010.sql.'); }
    $pdo = db(); $pdo->beginTransaction();
    try {
        $actor = notas_actor();
        // Bloqueé las prácticas en orden antes del área, compatible con traslados y designaciones.
        $s = $pdo->prepare('SELECT id_practica FROM practicas WHERE id_practica=? OR id_practicante=? ORDER BY id_practica FOR UPDATE');
        $s->execute([$idPractica, $actor['id_practicante'] ?? 0]); $s->fetchAll();
        $s = $pdo->prepare('SELECT * FROM notas_semanales WHERE id_practica=? AND viernes=? FOR UPDATE');
        $s->execute([$idPractica,$viernes]); $anterior = $s->fetch() ?: null;
        $areaHistorica = $actor['rol'] === 'ADMIN' && $anterior !== null ? (int)$anterior['id_area'] : null;
        $destino = notas_destino($idPractica,$viernes,$areaHistorica);
        $s = $pdo->prepare('SELECT id_area FROM areas_practica WHERE id_area=? FOR UPDATE');
        $s->execute([$destino['id_area']]); $s->fetchAll();
        // Releí tras adquirir el área por si una designación terminó mientras esperábamos.
        $destino = notas_destino($idPractica,$viernes,$areaHistorica);
        $liderazgo = notas_exigir_editor($actor,$destino,$anterior);
        // La corrección administrativa de una nota existente sigue disponible tras cerrar la práctica.
        if ($areaHistorica === null && (!(int)$destino['activo'] || !(int)$destino['persona_activa'] ||
            !in_array($destino['estado_practica'], ['PENDIENTE_INICIO','EN_PROGRESO'], true) ||
            (!empty($destino['fecha_inicio_practica']) && $viernes < $destino['fecha_inicio_practica']) ||
            (!empty($destino['fecha_termino_practica']) && $viernes > $destino['fecha_termino_practica']))) {
            throw new DomainException('El viernes debe corresponder a una práctica habilitada y a sus fechas.');
        }
        if ((int)($anterior['revision'] ?? 0) !== $revisionEsperada) {
            throw new DomainException('La evaluación ya existe o cambió. Recarga antes de continuar.');
        }
        // La aprobación y corrección de publicadas se habilitarán expresamente en la fase 4.
        if ($anterior !== null && $anterior['estado'] !== 'BORRADOR') {
            throw new DomainException('Esta evaluación está aprobada; su corrección corresponde al flujo de publicación.');
        }
        // Una nota existente mantiene sus criterios; una nueva exige la versión vigente vista en el formulario.
        $rubrica=$anterior!==null ? notas_rubrica((int)$anterior['id_rubrica']) : notas_rubrica_actual();
        if ($idRubricaEsperada!==null && $idRubricaEsperada!==(int)$rubrica['id_rubrica']) {
            throw new DomainException('La versión de criterios cambió. Recarga antes de guardar; tus valores no se enviaron a otra rúbrica.');
        }
        [$notas,$final,$observacion]=notas_validar_valores($notas,$final,$observacion,count($rubrica['criterios']));
        $ahora = date('Y-m-d H:i:s'); $revision = $revisionEsperada + 1;
        if ($anterior === null) {
            $s = $pdo->prepare('INSERT INTO notas_semanales
                (id_practica,viernes,id_area,id_rubrica,id_evaluador,id_liderazgo,tipo_evaluado,nota_final,observacion,creado_en,actualizado_en)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)');
            $s->execute([$idPractica,$viernes,$destino['id_area'],$rubrica['id_rubrica'],$actor['id_usuario'],$liderazgo,
                $destino['es_lider'] ? 'LIDER' : 'INTEGRANTE',$final,$observacion,$ahora,$ahora]);
            $idNota = (int)$pdo->lastInsertId();
        } else {
            $idNota = (int)$anterior['id_nota'];
            $s = $pdo->prepare('UPDATE notas_semanales SET nota_final=?,observacion=?,revision=?,actualizado_en=? WHERE id_nota=?');
            $s->execute([$final,$observacion,$revision,$ahora,$idNota]);
            $s = $pdo->prepare('DELETE FROM notas_semanales_detalle WHERE id_nota=?'); $s->execute([$idNota]);
        }
        $s = $pdo->prepare('INSERT INTO notas_semanales_detalle (id_nota,posicion,nota) VALUES (?,?,?)');
        foreach ($notas as $posicion => $nota) { $s->execute([$idNota,$posicion,$nota]); }
        $s = $pdo->prepare('SELECT * FROM notas_semanales WHERE id_nota=?'); $s->execute([$idNota]);
        $contenido = $s->fetch(); $contenido['detalle'] = $notas;
        // Incluí etiquetas en la instantánea para interpretar las revisiones incluso al cambiar la rúbrica activa.
        $contenido['criterios']=$rubrica['criterios'];
        $s = $pdo->prepare('INSERT INTO notas_semanales_historial (id_nota,revision,id_actor,motivo,contenido_json,registrado_en) VALUES (?,?,?,?,?,?)');
        $s->execute([$idNota,$revision,$actor['id_usuario'],$motivo ?: 'Creación de borrador',json_encode($contenido,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),$ahora]);
        if (!auditoria_registrar('GUARDAR_NOTA_SEMANAL', ['tabla_afectada'=>'notas_semanales',
            'id_registro_afectado'=>$idNota,'valores_anteriores'=>$anterior,
            'valores_nuevos'=>['revision'=>$revision,'viernes'=>$viernes,'motivo'=>$motivo]])) {
            throw new RuntimeException('No fue posible registrar la auditoría de la evaluación.');
        }
        $pdo->commit(); return ['id_nota'=>$idNota,'revision'=>$revision];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $e;
    }
}

// Protegí también la lectura directa: el evaluado solo consulta aprobadas y ADMIN tiene acceso global.
function notas_consultar(int $idNota): array
{
    $actor = notas_actor();
    // Mantuve cabecera y detalle coherentes mientras otra solicitud publica una corrección.
    $pdo=db(); $transaccionPropia=!$pdo->inTransaction();
    if ($transaccionPropia) {
        $pdo->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $pdo->beginTransaction();
    }
    try {
        $s = db()->prepare('SELECT n.*,pr.id_practicante FROM notas_semanales n JOIN practicas pr ON pr.id_practica=n.id_practica WHERE n.id_nota=?');
        $s->execute([$idNota]); $nota = $s->fetch();
        if (!$nota) { throw new DomainException('Evaluación no disponible.'); }
        if ($actor['rol'] !== 'ADMIN') {
            if ((int)($actor['id_practicante'] ?? 0) === (int)$nota['id_practicante']) {
                if ($nota['estado'] !== 'APROBADA') { throw new DomainException('La evaluación aún no está aprobada.'); }
            } else {
                notas_exigir_editor($actor,notas_destino((int)$nota['id_practica'],$nota['viernes']),$nota);
            }
        }
        $s = db()->prepare('SELECT posicion,nota FROM notas_semanales_detalle WHERE id_nota=? ORDER BY posicion');
        $s->execute([$idNota]); $nota['detalle'] = $s->fetchAll(PDO::FETCH_KEY_PAIR);
        $nota['rubrica']=notas_rubrica((int)$nota['id_rubrica']);
        if ($transaccionPropia) { $pdo->commit(); }
        return $nota;
    } catch (Throwable $e) { if ($transaccionPropia && $pdo->inTransaction()) { $pdo->rollBack(); } throw $e; }
}

// Preparé el apartado de atrasadas: devuelvo viernes sin evaluación, nunca notas cero ni borradores ajenos.
function notas_pendientes(int $idPractica): array
{
    $actor = notas_actor();
    $zona = new DateTimeZone('America/Santiago');
    $hoy = new DateTimeImmutable('today',$zona);
    $ultimo = $hoy->modify((int)$hoy->format('N') > 5 ? 'last friday' : 'friday this week');
    $destino = notas_destino($idPractica,$ultimo->format('Y-m-d'));
    notas_exigir_editor($actor,$destino);
    if (!(int)$destino['activo'] || !(int)$destino['persona_activa'] ||
        !in_array($destino['estado_practica'],['PENDIENTE_INICIO','EN_PROGRESO'],true)) { return []; }
    if (empty($destino['fecha_inicio_practica'])) {
        throw new DomainException('Registra el inicio de práctica para identificar los viernes pendientes.');
    }
    $inicio = new DateTimeImmutable($destino['fecha_inicio_practica'],$zona);
    $primero = $inicio->modify('+' . ((5 - (int)$inicio->format('N') + 7) % 7) . ' days');
    $s = db()->prepare('SELECT viernes FROM notas_semanales WHERE id_practica=?');
    $s->execute([$idPractica]); $existentes = array_flip($s->fetchAll(PDO::FETCH_COLUMN));
    $pendientes = [];
    for ($fecha=$primero; $fecha<=$ultimo; $fecha=$fecha->modify('+7 days')) {
        $viernes = $fecha->format('Y-m-d');
        if (!empty($destino['fecha_termino_practica']) && $viernes>$destino['fecha_termino_practica']) { break; }
        if (isset($existentes[$viernes])) { continue; }
        // Revalidé el tipo de evaluado de cada viernes para no asignar al líder notas de otro líder histórico.
        try { notas_exigir_editor($actor,notas_destino($idPractica,$viernes)); }
        catch (DomainException) { continue; }
        $pendientes[] = $viernes;
    }
    return array_reverse($pendientes);
}
