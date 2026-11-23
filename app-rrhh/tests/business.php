<?php

declare(strict_types=1);

// Agregué pruebas de negocio con datos sintéticos en memoria. Nunca conectan
// a MySQL ni alteran usuarios reales; el adaptador NO simula concurrencia InnoDB.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
    fwrite(STDERR, "Estas pruebas requieren pdo_sqlite en la CLI.\n");
    exit(1);
}

final class BusinessTestPDO extends PDO
{
    public function __construct()
    {
        parent::__construct('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $this->sqliteCreateFunction('NOW', static fn(): string => '2026-10-13 12:00:00');
    }

    // Adapté exclusivamente el dialecto de las consultas ejecutadas por las
    // pruebas. FOR UPDATE se elimina: los bloqueos reales requieren MySQL.
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        $query = str_replace('FOR UPDATE', '', $query);
        if (str_contains($query, 'ON DUPLICATE KEY UPDATE')) {
            $conflict = str_contains($query, 'INSERT INTO acompanamiento')
                ? 'id_practica' : (str_contains($query, 'INSERT INTO jornadas_asistencia')
                    ? 'id_practica, fecha' : 'id_horario, fecha, tipo_excepcion');
            $query = str_replace('ON DUPLICATE KEY UPDATE', 'ON CONFLICT (' . $conflict . ') DO UPDATE SET', $query);
            $query = preg_replace('/VALUES\((\w+)\)/', 'excluded.$1', $query);
            $query = str_replace('LAST_INSERT_ID(id_jornada)', 'id_jornada', $query);
        }
        return parent::prepare($query, $options);
    }
}

// Agregué sustitución del proveedor antes del bootstrap, que permite db()
// predefinida. La creación automática de tablas se omite solo en este proceso.
$fixture = new BusinessTestPDO();
function db(): PDO { return $GLOBALS['fixture']; }
// Conservé regresiones de planes históricos; los escenarios nuevos activan explícitamente política 2.
function recuperaciones_politica_solicitud(): int { return $GLOBALS['politica_recuperacion_prueba']??1; }
function acom_gestion_asegurar_tabla(): void {}
// Usé temporales del proyecto para no depender de los permisos de XAMPP/tmp.
$sessionDir = __DIR__ . '/../scratch/business-sessions';
if (!is_dir($sessionDir)) { mkdir($sessionDir, 0700, true); }
ini_set('session.save_path', $sessionDir);
ini_set('error_log', $sessionDir . '/expected-errors.log');
session_id('business-' . bin2hex(random_bytes(8)));
session_start();
$_SESSION = ['id_usuario' => 501, 'id_rol_activo' => 2, 'rol_activo' => 'SUPERVISOR', 'id_practicante' => 1];
require_once __DIR__ . '/../src/Services/AsistenciaService.php';
require_once __DIR__ . '/../src/Services/PermisosService.php';
require_once __DIR__ . '/../src/Models/AcompanamientoModel.php';

// Agregué el esquema mínimo que necesitan los servicios reales de esta fase.
$fixture->exec(<<<'SQL'
CREATE TABLE practicas (id_practica INTEGER PRIMARY KEY, id_practicante INTEGER);
INSERT INTO practicas VALUES (11,1),(22,2);
CREATE TABLE jornadas_asistencia (
 id_jornada INTEGER PRIMARY KEY, id_practica INTEGER, fecha TEXT,
 estado_jornada TEXT, hora_entrada TEXT, hora_salida TEXT,
 horas_calculadas REAL DEFAULT 0, horas_extra_aprobadas REAL, aprobada_por INTEGER,
 flag_atraso INTEGER DEFAULT 0, flag_salida_anticipada INTEGER DEFAULT 0,
 flag_horas_extra INTEGER DEFAULT 0, flag_asistencia_extraordinaria INTEGER DEFAULT 0,
 observacion TEXT, creada_por INTEGER, politica_recuperacion_v2 INTEGER DEFAULT 0, UNIQUE(id_practica,fecha));
CREATE TABLE tipos_marca (id_tipo_marca INTEGER PRIMARY KEY, codigo TEXT, orden INTEGER, activo INTEGER DEFAULT 1);
-- Agregué marcas independientes para interrupciones en la fixture aislada.
INSERT INTO tipos_marca VALUES (1,'ENTRADA',1,1),(2,'ENTRADA_COLACION',2,1),(3,'SALIDA_COLACION',3,1),(4,'SALIDA',4,1),(5,'SALIDA_PERMISO',5,1),(6,'REGRESO_PERMISO',6,1);
CREATE TABLE marcas_asistencia (
 id_marca INTEGER PRIMARY KEY, id_jornada INTEGER, id_tipo_marca INTEGER,
 fecha_hora TEXT, observacion TEXT, creada_por INTEGER, activo INTEGER DEFAULT 1,
 anulada INTEGER DEFAULT 0, motivo_anulacion TEXT, anulada_por INTEGER, fecha_anulacion TEXT);
CREATE TABLE horarios_practicante (id_horario INTEGER PRIMARY KEY, id_practica INTEGER,
 nombre_horario TEXT, fecha_inicio TEXT, fecha_fin TEXT, activo INTEGER DEFAULT 1);
INSERT INTO horarios_practicante VALUES (1,11,'Ficticio','2000-01-01',NULL,1),(2,22,'Ficticio','2000-01-01',NULL,1);
CREATE TABLE detalle_horario_practicante (id_horario INTEGER, dia_semana INTEGER,
 entrada_esperada TEXT, salida_esperada TEXT, colacion_inicio TEXT, colacion_fin TEXT,
 horas_planificadas REAL, activo INTEGER DEFAULT 1);
CREATE TABLE excepciones_horario_practicante (
 id_excepcion INTEGER PRIMARY KEY, id_horario INTEGER, fecha TEXT, tipo_excepcion TEXT,
 entrada_esperada TEXT, salida_esperada TEXT, colacion_inicio TEXT, colacion_fin TEXT,
 horas_planificadas REAL, observacion TEXT, activo INTEGER DEFAULT 1,
 UNIQUE(id_horario,fecha,tipo_excepcion));
CREATE TABLE auditoria_cambios (
 id_auditoria INTEGER PRIMARY KEY, id_usuario_actor INTEGER, id_rol_contexto INTEGER,
 id_sesion INTEGER, evento TEXT, tabla_afectada TEXT, id_registro_afectado INTEGER,
 valores_anteriores TEXT, valores_nuevos TEXT, ip TEXT, user_agent TEXT,
 created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE permisos_practicante (
 id_permiso INTEGER PRIMARY KEY, id_practica INTEGER, estado TEXT DEFAULT 'PENDIENTE',
 fecha_ausencia TEXT DEFAULT '2026-11-02', fecha_recuperacion TEXT DEFAULT '2026-11-07',
 hora_inicio_recuperacion TEXT DEFAULT '10:00', hora_fin_recuperacion TEXT DEFAULT '18:00',
 tipo_ausencia TEXT DEFAULT 'COMPLETA', respuesta_supervisor TEXT, revisado_por INTEGER,
 fecha_revision TEXT, id_excepcion_creada INTEGER, id_excepcion_ausencia INTEGER,
 requiere_revision INTEGER DEFAULT 0, observacion_solicitud TEXT,
 hora_inicio_ausencia TEXT, hora_fin_ausencia TEXT, horas_ausencia REAL,
 horas_trabajo_dia REAL, nuevo_horario_inicio TEXT, nuevo_horario_fin TEXT,
 motivo TEXT, horas_a_recuperar REAL, created_at TEXT);
INSERT INTO permisos_practicante (id_permiso,id_practica) VALUES (1,11),(2,22),(3,22),(4,22),(5,22);
-- Agregué tablas aisladas de calendario y tramos para los servicios reales de recuperación.
CREATE TABLE feriados (id_feriado INTEGER PRIMARY KEY,fecha TEXT UNIQUE,nombre TEXT,activo INTEGER DEFAULT 1);
CREATE TABLE recuperaciones_permiso (
 politica_recuperacion INTEGER DEFAULT 1,solicitado_utc TEXT,
 id_recuperacion INTEGER PRIMARY KEY,id_permiso INTEGER,fecha TEXT,tipo TEXT,hora_inicio TEXT,hora_fin TEXT,
 minutos_planificados INTEGER,minutos_validados INTEGER DEFAULT 0,estado TEXT DEFAULT 'PROPUESTA',
 autorizado_feriado INTEGER DEFAULT 0,validado_por INTEGER,motivo_validacion TEXT,firma_marcas TEXT,
 created_at TEXT DEFAULT CURRENT_TIMESTAMP,fecha_validacion TEXT);
CREATE TABLE acompanamiento (
 id_acompanamiento INTEGER PRIMARY KEY, id_practica INTEGER UNIQUE,
 evaluacion_enviada INTEGER DEFAULT 0, puntaje_evaluacion REAL, resena_recibida INTEGER DEFAULT 0,
 fecha_generacion TEXT, resena_publicada INTEGER DEFAULT 0, documento_entregado INTEGER DEFAULT 0,
 capacitacion_realizada INTEGER DEFAULT 0, certificado_entregado INTEGER DEFAULT 0,
 linkedin_operativo INTEGER DEFAULT 0, updated_at TEXT);
ALTER TABLE tipos_marca ADD COLUMN nombre TEXT;
CREATE TABLE usuarios_sistema (id_usuario INTEGER PRIMARY KEY, email TEXT);
CREATE TABLE solicitudes_colacion (id_solicitud INTEGER PRIMARY KEY,id_practica INTEGER,fecha TEXT,inicio_anterior TEXT,inicio_solicitado TEXT,firma_horario TEXT,motivo TEXT,estado TEXT DEFAULT 'PENDIENTE',solicitado_por INTEGER,solicitado_utc TEXT,revisado_por INTEGER,revisado_utc TEXT,motivo_revision TEXT);
ALTER TABLE practicas ADD COLUMN horas_previas REAL DEFAULT 5;
ALTER TABLE practicas ADD COLUMN horas_requeridas REAL DEFAULT 360;
ALTER TABLE practicas ADD COLUMN fecha_inicio_practica TEXT DEFAULT '2026-01-01';
ALTER TABLE practicas ADD COLUMN fecha_termino_practica TEXT;
ALTER TABLE practicas ADD COLUMN activo INTEGER DEFAULT 1;
ALTER TABLE practicas ADD COLUMN id_tipo_practica INTEGER;
CREATE TABLE practicantes (id_practicante INTEGER PRIMARY KEY, rut_numero INTEGER, rut_dv TEXT,
 nombre_1 TEXT, nombre_2 TEXT, apellido_paterno TEXT, apellido_materno TEXT, email TEXT,
 institucion_otra TEXT, sede_otra TEXT, id_institucion INTEGER, id_sede INTEGER, id_carrera INTEGER, activo INTEGER DEFAULT 1);
INSERT INTO practicantes (id_practicante,nombre_1,apellido_paterno) VALUES (1,'Ficticio','Uno'),(2,'Ficticio','Dos');
CREATE TABLE instituciones (id_institucion INTEGER, nombre TEXT);
CREATE TABLE sedes (id_sede INTEGER, nombre_sede TEXT);
CREATE TABLE carreras (id_carrera INTEGER, nombre TEXT);
CREATE TABLE tipos_practica (id_tipo_practica INTEGER, nombre TEXT);
-- Agregué catálogos y vínculo activos para probar solicitudes con el servicio real.
INSERT INTO tipos_practica VALUES (1,'PROFESIONAL');
CREATE TABLE estados_practica (id_estado_practica INTEGER, nombre TEXT);
INSERT INTO estados_practica VALUES (1,'EN_PROGRESO');
ALTER TABLE practicas ADD COLUMN id_estado_practica INTEGER DEFAULT 1;
UPDATE practicas SET id_tipo_practica=1;
CREATE TABLE areas_practica (id_area INTEGER, nombre TEXT);
-- Agregué tablas de la fase 6 sin activar el corte durante las regresiones históricas anteriores.
CREATE TABLE politica_tiempo_excepcional (id INTEGER PRIMARY KEY,vigente_desde_utc TEXT,ultima_jornada_historica INTEGER);
CREATE TABLE extras_historicas (id_jornada INTEGER PRIMARY KEY,horas_aprobadas REAL);
CREATE TABLE tiempos_excepcionales (id_tiempo INTEGER PRIMARY KEY,id_jornada INTEGER,hora_inicio TEXT,hora_fin TEXT,politica_canje INTEGER DEFAULT 1,
 minutos_solicitados INTEGER,minutos_aprobados INTEGER DEFAULT 0,motivo_solicitud TEXT,estado TEXT DEFAULT 'PENDIENTE',
 solicitado_utc TEXT,vence_utc TEXT,revisado_por INTEGER,motivo_revision TEXT,revisado_utc TEXT,firma_marcas TEXT);
CREATE TABLE compensaciones_tiempo (id_compensacion INTEGER PRIMARY KEY,id_tiempo INTEGER,fecha TEXT,tipo TEXT,
 hora_inicio TEXT,hora_fin TEXT,minutos INTEGER,minutos_aplicados INTEGER DEFAULT 0,estado TEXT DEFAULT 'PENDIENTE',
 motivo_solicitud TEXT,solicitado_utc TEXT,revisado_por INTEGER,motivo_revision TEXT,revisado_utc TEXT);
CREATE TABLE historial_areas_practicante (id_practica INTEGER, id_area INTEGER, fecha_fin TEXT, fecha_inicio TEXT, id_historial_area INTEGER);
SQL);
for ($dia = 1; $dia <= 7; $dia++) {
    $fixture->exec("INSERT INTO detalle_horario_practicante VALUES (1,$dia,'09:00','18:00','13:00','14:00',8,1),(2,$dia,'09:00','18:00','13:00','14:00',8,1)");
}

$checks = 0;
$failures = [];
function check(bool $ok, string $name): void
{
    $GLOBALS['checks']++;
    if (!$ok) { $GLOBALS['failures'][] = $name; }
}
function marks(string $salida = '18:00:00', bool $colacion = false): array
{
    $result = [['codigo' => 'ENTRADA', 'fecha_hora' => '2026-10-13 09:00:00']];
    if ($colacion) {
        $result[] = ['codigo' => 'ENTRADA_COLACION', 'fecha_hora' => '2026-10-13 13:00:00'];
        $result[] = ['codigo' => 'SALIDA_COLACION', 'fecha_hora' => '2026-10-13 14:00:00'];
    }
    $result[] = ['codigo' => 'SALIDA', 'fecha_hora' => '2026-10-13 ' . $salida];
    return $result;
}
function seedDay(int $id, int $practica = 22, string $estado = 'OBSERVADA', string $salida = '18:00:00'): void
{
    // Cada escenario reemplaza su jornada, manteniendo aislados los datos ficticios.
    db()->exec('DELETE FROM marcas_asistencia');
    db()->exec('DELETE FROM jornadas_asistencia');
    $stmt = db()->prepare('INSERT INTO jornadas_asistencia (id_jornada,id_practica,fecha,estado_jornada,horas_calculadas,observacion) VALUES (?,?,?, ?,8,?)');
    $stmt->execute([$id,$practica,'2026-10-13',$estado,'Observación anterior.']);
    foreach (marks($salida) as $mark) {
        $stmt = db()->prepare('INSERT INTO marcas_asistencia (id_jornada,id_tipo_marca,fecha_hora) VALUES (?,?,?)');
        $stmt->execute([$id,$mark['codigo'] === 'ENTRADA' ? 1 : 4,$mark['fecha_hora']]);
    }
}
function lastAudit(string $event): array
{
    $stmt = db()->prepare('SELECT * FROM auditoria_cambios WHERE evento=? ORDER BY id_auditoria DESC LIMIT 1');
    $stmt->execute([$event]);
    return $stmt->fetch() ?: [];
}
function failAudit(string $event): void
{
    // Agregué fallos deliberados de almacenamiento para comprobar rollback real.
    db()->exec("CREATE TRIGGER fail_audit BEFORE INSERT ON auditoria_cambios WHEN NEW.evento='$event' BEGIN SELECT RAISE(FAIL,'Fallo ficticio'); END");
}

try {
    // Comprobé fechas civiles reales y horas exactas, sin normalización silenciosa del servidor.
    foreach (['2028-02-29'=>true,'2027-02-29'=>false,'2026-02-30'=>false,'2026-10-14'=>true,'14-10-2026'=>false] as $date=>$valid) {
        check(permisos_gestion_fecha_valida($date)===$valid,'Fecha de permiso: '.$date);
    }
    foreach (['00:00'=>true,'23:59'=>true,'24:00'=>false,'12:60'=>false,'9:00'=>false,'09:00:00'=>false] as $time=>$valid) {
        check(permisos_gestion_hora_valida($time)===$valid,'Hora de permiso: '.$time);
    }
    // Comprobé conservación de horas cuando el permiso intersecta parcialmente el descanso.
    $parcial=permisos_gestion_calcular_horario_restante('10:00','18:00','13:00','14:00','13:30','18:00');
    check($parcial['horas_ausencia']===4.0 && $parcial['horas_restantes']===3.0,'Salida anticipada descuenta solo media colación de cada tramo');
    check($parcial['nueva_colacion_inicio']==='13:00' && $parcial['nueva_colacion_fin']==='13:30','Colación restante acotada');
    $parcial=permisos_gestion_calcular_horario_restante('10:00','18:00','13:00','14:00','10:00','13:30');
    check($parcial['horas_ausencia']===3.0 && $parcial['horas_restantes']===4.0,'Entrada tardía conserva el total pactado');
    check($parcial['nueva_colacion_inicio']==='13:30' && $parcial['nueva_colacion_fin']==='14:00','Colación posterior se conserva');
    check(permisos_gestion_calcular_horario_restante('10:00','18:00',null,null,'25:00','18:00')===null,'Parcial rechaza hora fuera de rango');
    $horario = ['entrada_esperada'=>'09:00','salida_esperada'=>'18:00','horas_planificadas'=>8];
    $resumen = asistencia_calcular_resumen_jornada(marks(), $horario);
    check($resumen['estado_jornada'] === 'OBSERVADA', 'Sin colación queda observada');
    check($resumen['horas_calculadas'] === 9.0, 'Sin colación reconoce las nueve horas dentro del turno');
    check($resumen['horas_extra_sugeridas'] === 0.0, 'Colación no se convierte en extra');
    $resumen = asistencia_calcular_resumen_jornada(marks('18:00:00', true), $horario);
    check($resumen['horas_calculadas'] === 8.0 && $resumen['estado_jornada'] === 'VALIDA', 'Colación registrada descuenta su duración');
    $resumen = asistencia_calcular_resumen_jornada(marks('19:00:00'), $horario);
    check($resumen['horas_calculadas'] === 9.0 && $resumen['horas_extra_sugeridas'] === 1.0, 'Exceso fuera del turno separado de la base');
    check(asistencia_calcular_resumen_jornada(marks('18:15:00'), $horario)['flag_horas_extra'] === 0, 'Tolerancia de quince minutos conservada');
    check(asistencia_calcular_resumen_jornada(marks('18:16:00'), $horario)['flag_horas_extra'] === 1, 'Exceso sobre tolerancia detectado');
    check(asistencia_calcular_resumen_jornada([marks()[0]], $horario)['horas_calculadas'] === 0.0, 'Jornada incompleta no reconoce horas');
    foreach (asistencia_estados_jornada() as $estado) {
        $row = ['estado_jornada'=>$estado,'horas_calculadas'=>9,'horas_extra_aprobadas'=>1];
        // Comprobé el mismo reconocimiento de base y extras históricas al cerrar una revisión.
        $expected = in_array($estado,['VALIDA','OBSERVADA','CORREGIDA','REVISADA'],true) ? 10.0 : 0.0;
        check(asistencia_horas_reconocidas($row) === $expected, 'Total PHP en estado ' . $estado);
        $stmt = $fixture->prepare('SELECT ' . asistencia_sql_horas_reconocidas('j') . ' FROM (SELECT ? AS estado_jornada, 9 AS horas_calculadas, 1 AS horas_extra_aprobadas) j');
        $stmt->execute([$estado]);
        check((float)$stmt->fetchColumn() === $expected, 'Total SQL en estado ' . $estado);
    }
    try { asistencia_sql_horas_reconocidas('j;DROP'); check(false,'Rechaza alias SQL inválido'); }
    catch (InvalidArgumentException) { check(true,'Rechaza alias SQL inválido'); }

    $jornada = ['estado_jornada'=>'OBSERVADA','fecha'=>'2026-10-13'];
    check(asistencia_gestion_validar_revision($jornada,array_reverse(marks()))['ok'], 'Revisión ordena marcas válidas');
    check(!asistencia_gestion_validar_revision($jornada,[marks()[0]])['ok'], 'Revisión exige salida');
    check(!asistencia_gestion_validar_revision($jornada,array_merge(marks(),[marks()[0]]))['ok'], 'Revisión rechaza duplicados');
    $mal = marks(); $mal[1]['fecha_hora'] = '2026-10-14 18:00:00';
    check(!asistencia_gestion_validar_revision($jornada,$mal)['ok'], 'Revisión rechaza otra fecha');
    $mal[1]['fecha_hora'] = '2026-10-13 29:00:00';
    check(!asistencia_gestion_validar_revision($jornada,$mal)['ok'], 'Revisión rechaza hora inválida');
    check(!asistencia_gestion_validar_revision(array_replace($jornada,['estado_jornada'=>'ANULADA']),marks())['ok'], 'Revisión no reactiva anulada');
    $anulada = marks()[0]; $anulada['anulada'] = 1;
    check(asistencia_gestion_validar_revision($jornada,array_merge(marks(),[$anulada]))['ok'], 'Ignora marcas anuladas');

    check(!auth_puede_supervisar_practica(11), 'Bloquea práctica propia');
    check(auth_puede_supervisar_practica(22), 'Permite otra práctica');
    check(!auth_puede_supervisar_practica(999), 'Bloquea práctica inexistente');
    check(!auth_puede_supervisar_practica(22,999), 'Bloquea actor falsificado');
    check(!permisos_gestion_aprobar(1,501)['ok'], 'No aprueba permiso propio');
    check(!permisos_gestion_rechazar(1,501,'Motivo')['ok'], 'No rechaza permiso propio');
    check($fixture->query('SELECT COUNT(*) FROM excepciones_horario_practicante')->fetchColumn() === 0, 'Denegación no crea excepciones');
    check(!permisos_gestion_aprobar(2,999)['ok'], 'Permiso rechaza revisor falsificado');
    $_SESSION['rol_activo'] = 'PRACTICANTE';
    check(!permisos_gestion_rechazar(2,501,'Motivo')['ok'], 'Practicante no resuelve permiso');
    $_SESSION['rol_activo'] = 'SUPERVISOR';
    check(permisos_gestion_rechazar(2,501,'No corresponde')['ok'], 'Rechaza permiso ajeno');
    $audit = lastAudit('RECHAZAR_PERMISO');
    check((int)$audit['id_registro_afectado'] === 2 && (int)$audit['id_usuario_actor'] === 501, 'Rechazo audita registro y actor');
    check(json_decode($audit['valores_anteriores'],true)['estado'] === 'PENDIENTE', 'Rechazo audita estado anterior');
    check(!permisos_gestion_rechazar(2,501,'Otra vez')['ok'], 'Permiso resuelto no se resuelve de nuevo');
    check(permisos_gestion_aprobar(3,501,'Autorizado')['ok'], 'Aprueba permiso y recuperación ajenos');
    check((int)$fixture->query('SELECT COUNT(*) FROM excepciones_horario_practicante')->fetchColumn() === 2, 'Aprobación crea ambas excepciones');
    check((int)lastAudit('APROBAR_PERMISO')['id_registro_afectado'] === 3, 'Aprobación audita ID correcto');
    $fixture->exec('DELETE FROM excepciones_horario_practicante');
    $fixture->exec("CREATE TRIGGER fail_recovery BEFORE INSERT ON excepciones_horario_practicante WHEN NEW.tipo_excepcion='ASISTENCIA_EXTRAORDINARIA' BEGIN SELECT RAISE(FAIL,'Fallo ficticio'); END");
    check(!permisos_gestion_aprobar(4,501)['ok'], 'Fallo de recuperación rechaza aprobación');
    check((int)$fixture->query('SELECT COUNT(*) FROM excepciones_horario_practicante')->fetchColumn() === 0, 'Fallo revierte también excepción de ausencia');
    check($fixture->query('SELECT estado FROM permisos_practicante WHERE id_permiso=4')->fetchColumn() === 'PENDIENTE', 'Fallo conserva permiso pendiente');
    $fixture->exec('DROP TRIGGER fail_recovery');
    failAudit('APROBAR_PERMISO');
    check(!permisos_gestion_aprobar(5,501)['ok'], 'Fallo de auditoría rechaza aprobación');
    check((int)$fixture->query('SELECT COUNT(*) FROM excepciones_horario_practicante')->fetchColumn() === 0, 'Fallo de auditoría revierte excepciones');
    $fixture->exec('DROP TRIGGER fail_audit');

    seedDay(101,11);
    check(!asistencia_gestion_marcar_revisada(101,'Verificada')['ok'], 'No revisa jornada propia');
    check(!asistencia_gestion_aprobar_horas_extra(101,'1','Motivo')['ok'], 'No aprueba extras propias');
    seedDay(102);
    check(asistencia_gestion_cambiar_estado(102,'CORREGIDA','Verificada con el practicante')['ok'], 'Acción CORREGIDA revisa jornada');
    $row = asistencia_jornada_por_id(102);
    check($row['estado_jornada'] === 'CORREGIDA' && (float)$row['horas_calculadas'] === 9.0, 'Revisión guarda estado y base actual');
    check($row['horas_extra_aprobadas'] === null && $row['aprobada_por'] === null, 'Revisión no aprueba extras');
    check(str_contains($row['observacion'],'Observación anterior.') && str_contains($row['observacion'],'Verificada'), 'Revisión conserva observación y motivo');
    check(count(asistencia_marcas_vigentes_jornada(102)) === 2, 'Revisión conserva marcas');
    $audit = lastAudit('REVISAR_JORNADA');
    check((int)$audit['id_usuario_actor'] === 501 && (int)$audit['id_registro_afectado'] === 102 && $audit['created_at'] !== null, 'Revisión audita actor, ID y fecha');
    check(json_decode($audit['valores_anteriores'],true)['estado_jornada'] === 'OBSERVADA' && json_decode($audit['valores_nuevos'],true)['estado_jornada'] === 'CORREGIDA', 'Revisión audita antes y después');
    check(!asistencia_gestion_marcar_revisada(102,'Otra vez')['ok'], 'Revisión repetida se rechaza');
    check(!asistencia_gestion_marcar_revisada(102,'')['ok'], 'Revisión exige motivo');
    // Agregué el cierre REVISADA, la conservación de marcas/extras y el historial de autorización.
    seedDay(113,22,'OBSERVADA','19:00:00');
    $fixture->exec('UPDATE jornadas_asistencia SET horas_extra_aprobadas=0.5, aprobada_por=501');
    $antes = asistencia_marcas_vigentes_jornada(113);
    check(asistencia_gestion_cambiar_estado(113,'REVISADA','Reunión verificada')['ok'], 'Cierre REVISADA permitido');
    $row = asistencia_jornada_por_id(113);
    check($row['estado_jornada'] === 'REVISADA' && (float)$row['horas_calculadas'] === 9.0, 'Revisada reconoce base sin colación inventada');
    check($row['horas_extra_aprobadas'] === 0.5 && (int)$row['aprobada_por'] === 501, 'Revisada conserva aprobación histórica');
    check(asistencia_horas_reconocidas($row) === 9.5, 'Revisada conserva acumulado de base y extras aprobadas');
    check(asistencia_marcas_vigentes_jornada(113) === $antes, 'Revisada no modifica marcas');
    check(!asistencia_jornada_observacion_pendiente($row) && asistencia_jornada_revision_resuelta($row), 'Historial no deja revisada en observación pendiente');
    check(!asistencia_jornada_observacion_pendiente(['estado_jornada'=>'CORREGIDA','observacion'=>'Motivo histórico']), 'Corregida tampoco reabre observación');
    check(asistencia_jornada_observacion_pendiente(['estado_jornada'=>'OBSERVADA','observacion'=>'']), 'Estado observado queda pendiente incluso sin texto');
    check(asistencia_jornada_observacion_pendiente(['estado_jornada'=>'PENDIENTE_REVISION']), 'Pendiente sin texto exige revisión');
    $audit = lastAudit('REVISAR_JORNADA');
    check(json_decode($audit['valores_nuevos'],true)['estado_jornada'] === 'REVISADA' && (int)$audit['id_usuario_actor'] === 501 && $audit['created_at'] !== null, 'Revisada audita estado, responsable y fecha');
    check(!asistencia_gestion_cambiar_estado(113,'REVISADA','Repetida')['ok'], 'Cierre repetido rechazado');
    check(!asistencia_gestion_marcar_revisada(113,'Motivo','VALIDA')['ok'], 'No permite cierre arbitrario');
    seedDay(114,11);
    check(!asistencia_gestion_cambiar_estado(114,'REVISADA','Propia')['ok'], 'No revisa práctica propia con nuevo estado');
    seedDay(115);
    check(!asistencia_gestion_cambiar_estado(115,'REVISADA','')['ok'], 'Revisada exige motivo');
    failAudit('REVISAR_JORNADA');
    check(!asistencia_gestion_cambiar_estado(115,'REVISADA','Fallo ficticio')['ok'], 'Revisada exige auditoría atómica');
    check(asistencia_jornada_por_id(115)['estado_jornada'] === 'OBSERVADA', 'Fallo revierte el cierre REVISADA');
    $fixture->exec('DROP TRIGGER fail_audit');
    seedDay(116,22,'INCOMPLETA');
    check(!asistencia_gestion_cambiar_estado(116,'REVISADA','Sin salida')['ok'], 'No cierra incompleta cambiando estado');
    seedDay(103,22,'PENDIENTE_REVISION','19:00:00');
    check(asistencia_gestion_marcar_revisada(103,'Revisada')['ok'], 'Revisión acepta pendiente');
    $row = asistencia_jornada_por_id(103);
    $vista = asistencia_gestion_calcular_horas_vista($row);
    check($vista['es_revisada'] && !$vista['es_validada'] && $vista['horas_extra_sugeridas'] === 1.0 && $vista['horas_validadas'] === 9.0, 'Revisión mantiene extras por confirmar separadas');
    check(!asistencia_gestion_aprobar_horas_extra(103,'2','Exceso')['ok'], 'No aprueba más del exceso real');
    check(asistencia_gestion_aprobar_horas_extra(103,'0,5','Aprobación parcial')['ok'], 'Aprueba parte del exceso real');
    check(asistencia_horas_reconocidas(asistencia_jornada_por_id(103)) === 9.5, 'Total reconoce solo extra aprobada');
    failAudit('APROBAR_HORAS_EXTRA');
    check(!asistencia_gestion_aprobar_horas_extra(103,'1','Actualizar')['ok'], 'Aprobación de extras falla sin auditoría');
    check((float)asistencia_jornada_por_id(103)['horas_extra_aprobadas'] === 0.5, 'Fallo de aprobación conserva extra anterior');
    $fixture->exec('DROP TRIGGER fail_audit');
    seedDay(104);
    $fixture->exec('UPDATE jornadas_asistencia SET horas_extra_aprobadas=1');
    check(!asistencia_gestion_marcar_revisada(104,'Revisada')['ok'], 'No duplica antigua colación aprobada como extra');
    check(asistencia_gestion_aprobar_horas_extra(104,'0','Ajuste de aprobación anterior')['ok'], 'Permite retirar extra anterior incompatible');
    check(asistencia_horas_reconocidas(asistencia_jornada_por_id(104)) === 9.0, 'Retirada recalcula base sin duplicar tiempo');
    seedDay(105); failAudit('REVISAR_JORNADA');
    check(!asistencia_gestion_marcar_revisada(105,'Revisada')['ok'], 'Fallo de auditoría rechaza revisión');
    $row = asistencia_jornada_por_id(105);
    check($row['estado_jornada'] === 'OBSERVADA' && (float)$row['horas_calculadas'] === 8.0, 'Fallo de revisión revierte estado y horas');
    $fixture->exec('DROP TRIGGER fail_audit');
    seedDay(106,22,'ANULADA');
    check(!asistencia_gestion_cambiar_estado(106,'OBSERVADA','Reabrir')['ok'], 'Cambio de estado no reactiva anulada');
    check(!asistencia_gestion_registrar_marca_correctiva(106,'ENTRADA','2026-10-13T08:00','Corrección')['ok'], 'No inserta corrección en anulada');

    try { acom_gestion_guardar(11,['evaluacion_enviada'=>1]); check(false,'No guarda acompañamiento propio'); }
    catch (RuntimeException) { check(true,'No guarda acompañamiento propio'); }
    // Actualicé la expectativa: el seguimiento continúa editable, pero el puntaje anterior queda histórico.
    try { acom_gestion_guardar(22,['puntaje_evaluacion'=>6]); check(false,'Puntaje histórico inmutable'); }
    catch (DomainException) { check(true,'Puntaje histórico inmutable'); }
    acom_gestion_guardar(22,['evaluacion_enviada'=>1]);
    check((int)$fixture->query('SELECT evaluacion_enviada FROM acompanamiento')->fetchColumn() === 1, 'Guarda acompañamiento ajeno');
    check((int)lastAudit('EDITAR_ACOMPANAMIENTO')['id_usuario_actor'] === 501, 'Acompañamiento audita actor');
    failAudit('EDITAR_ACOMPANAMIENTO');
    try { acom_gestion_guardar(22,['evaluacion_enviada'=>0]); check(false,'Fallo de auditoría rechaza acompañamiento'); }
    catch (RuntimeException) { check(true,'Fallo de auditoría rechaza acompañamiento'); }
    check((int)$fixture->query('SELECT evaluacion_enviada FROM acompanamiento')->fetchColumn() === 1, 'Fallo conserva acompañamiento previo');
    $fixture->exec('DROP TRIGGER fail_audit');
    seedDay(112,22,'CORREGIDA');
    $fixture->exec('UPDATE jornadas_asistencia SET horas_calculadas=9,horas_extra_aprobadas=1');
    $fixture->exec("INSERT INTO jornadas_asistencia (id_practica,fecha,estado_jornada,horas_calculadas,horas_extra_aprobadas) VALUES (22,'2026-10-12','PENDIENTE_REVISION',8,2)");
    $listado = acom_gestion_listar_practicantes('');
    check(count($listado) === 1 && (int)$listado[0]['id_practicante'] === 2, 'Listado de acompañamiento oculta práctica propia');
    check((float)$listado[0]['horas_totales'] === 15.0, 'Acompañamiento suma previas y extras aprobadas; excluye pendientes');

    // Comprobé correcciones/anulaciones con aprobación previa y auditoría fallida.
    seedDay(107,22,'CORREGIDA','19:00:00');
    $fixture->exec('UPDATE jornadas_asistencia SET horas_extra_aprobadas=1, aprobada_por=501');
    check(asistencia_gestion_registrar_marca_correctiva(107,'ENTRADA_COLACION','2026-10-13T13:00','Corrección')['ok'], 'Corrección manual permitida');
    check(asistencia_jornada_por_id(107)['horas_extra_aprobadas'] === null, 'Corrección invalida extras anteriores');
    seedDay(108);
    failAudit('CORREGIR_ASISTENCIA');
    check(!asistencia_gestion_registrar_marca_correctiva(108,'ENTRADA_COLACION','2026-10-13T13:00','Corrección')['ok'], 'Corrección falla sin auditoría');
    check(count(asistencia_marcas_vigentes_jornada(108)) === 2, 'Fallo de auditoría revierte marca correctiva');
    $fixture->exec('DROP TRIGGER fail_audit');
    seedDay(109,11);
    $marcaId = (int)asistencia_marcas_vigentes_jornada(109)[0]['id_marca'];
    check(!asistencia_anular_marca($marcaId,'Motivo',501)['ok'], 'No anula marca propia');
    seedDay(110,22,'CORREGIDA','19:00:00');
    $fixture->exec('UPDATE jornadas_asistencia SET horas_extra_aprobadas=1, aprobada_por=501');
    $marcaId = (int)asistencia_marcas_vigentes_jornada(110)[1]['id_marca'];
    failAudit('ANULAR_MARCA_ASISTENCIA');
    check(!asistencia_anular_marca($marcaId,'Motivo',501)['ok'], 'Anulación de marca falla sin auditoría');
    check(count(asistencia_marcas_vigentes_jornada(110)) === 2 && (float)asistencia_jornada_por_id(110)['horas_extra_aprobadas'] === 1.0, 'Fallo restaura marca y aprobación');
    $fixture->exec('DROP TRIGGER fail_audit');
    check(asistencia_anular_marca($marcaId,'Salida errónea',501)['ok'], 'Anula marca ajena');
    check(asistencia_jornada_por_id(110)['horas_extra_aprobadas'] === null && asistencia_jornada_por_id(110)['estado_jornada'] === 'INCOMPLETA', 'Anular salida invalida aprobación y horas');
    check(!asistencia_gestion_anular_jornada(110,'Motivo')['ok'], 'Supervisor no anula jornada completa');
    $_SESSION['rol_activo'] = 'ADMIN'; $_SESSION['id_practicante'] = null;
    seedDay(111);
    failAudit('ANULAR_ASISTENCIA');
    check(!asistencia_gestion_anular_jornada(111,'Motivo')['ok'], 'Anulación completa falla sin auditoría');
    check(asistencia_jornada_por_id(111)['estado_jornada'] === 'OBSERVADA' && count(asistencia_marcas_vigentes_jornada(111)) === 2, 'Fallo restaura jornada y sus marcas');
    $fixture->exec('DROP TRIGGER fail_audit');
    check(asistencia_gestion_anular_jornada(111,'Motivo')['ok'], 'Administrador anula jornada');
    check(asistencia_horas_reconocidas(asistencia_jornada_por_id(111)) === 0.0 && count(asistencia_marcas_vigentes_jornada(111)) === 0, 'Anulación completa deja cero reconocido');
    $_SESSION['rol_activo'] = 'SUPERVISOR'; $_SESSION['id_practicante'] = 1;

    $fixture->exec('DELETE FROM marcas_asistencia; DELETE FROM jornadas_asistencia');
    $day = asistencia_obtener_o_crear_jornada(22,'2026-10-13',501);
    check(asistencia_obtener_o_crear_jornada(22,'2026-10-13',501)['id_jornada'] === $day['id_jornada'], 'Creación repetida reutiliza jornada');
    check(asistencia_registrar_marca((int)$day['id_jornada'],'ENTRADA','2026-10-13 09:00:00',null,501,$horario)['ok'], 'Primera entrada se guarda');
    check(!asistencia_registrar_marca((int)$day['id_jornada'],'ENTRADA','2026-10-13 09:00:01',null,501,$horario)['ok'], 'Entrada repetida se rechaza');
    check(count(asistencia_marcas_vigentes_jornada((int)$day['id_jornada'])) === 1, 'Duplicado no altera marcas');
    $_SESSION['rol_activo'] = 'ADMIN'; $_SESSION['id_practicante'] = null;
    check(auth_puede_supervisar_practica(11), 'Administrador sin vínculo puede supervisar');

    // Comprobé interrupciones intermedias, colación independiente y cruces de marcas abiertas.
    $restante = permisos_gestion_calcular_horario_restante('09:00','18:00','13:00','14:00','11:00','12:00');
    check($restante['nueva_salida']==='18:00' && $restante['horas_restantes']===7.0 && $restante['horas_ausencia']===1.0,'Interrupción conserva el tramo posterior');
    check(permisos_gestion_texto_tramos(['nuevo_horario_inicio'=>'09:00','nuevo_horario_fin'=>'18:00','hora_inicio_ausencia'=>'11:00','hora_fin_ausencia'=>'12:00'])==='09:00–11:00 y 12:00–18:00','Descripción incluye dos tramos completos');
    check(permisos_gestion_texto_tramos(['nuevo_horario_inicio'=>'09:00','nuevo_horario_fin'=>'16:00','hora_inicio_ausencia'=>'16:00','hora_fin_ausencia'=>'18:00'])==='09:00–16:00','Salida anticipada presenta solo su tramo restante');
    $cruceLunch = permisos_gestion_calcular_horario_restante('09:00','18:00','13:00','14:00','12:30','14:30');
    check($cruceLunch['horas_restantes']===7.0 && $cruceLunch['horas_ausencia']===1.0,'Interrupción sobre colación no duplica descuento');
    $interrumpidas = marks('18:00:00', true);
    $interrumpidas[] = ['codigo'=>'SALIDA_PERMISO','fecha_hora'=>'2026-10-13 11:00:00'];
    $interrumpidas[] = ['codigo'=>'REGRESO_PERMISO','fecha_hora'=>'2026-10-13 12:00:00'];
    $resumen = asistencia_calcular_resumen_jornada($interrumpidas,$horario);
    check($resumen['horas_calculadas']===7.0 && $resumen['horas_extra_sugeridas']===0.0,'Dos tramos y colación suman siete horas');
    check(asistencia_calcular_resumen_jornada(array_filter($interrumpidas,static fn($m)=>$m['codigo']!=='REGRESO_PERMISO'),$horario)['estado_jornada']==='INCOMPLETA','No se inventa regreso de permiso');
    $permisoPrueba=['fecha_ausencia'=>'2026-10-13','tipo_ausencia'=>'PARCIAL','hora_inicio_ausencia'=>'11:00','hora_fin_ausencia'=>'12:00'];
    check(!asistencia_permiso_cruza_marcas($permisoPrueba,$interrumpidas),'Pausa exacta no cruza tiempo trabajado');
    check(asistencia_permiso_cruza_marcas($permisoPrueba,marks()),'Jornada continua cruza permiso intermedio');
    check(asistencia_permiso_cruza_marcas($permisoPrueba,[marks()[0]],'2026-10-13 11:30:00'),'Entrada abierta requiere observación');
    check(!asistencia_permiso_cruza_marcas($permisoPrueba,[marks()[0]],'2026-10-13 10:30:00'),'Trabajo anterior no cruza ausencia futura');
    // Probé truncamiento al minuto en permisos parciales para evitar falsos cruces por segundos ocultos.
    $permisoSalida=['fecha_ausencia'=>'2026-10-13','tipo_ausencia'=>'PARCIAL','hora_inicio_ausencia'=>'19:00','hora_fin_ausencia'=>'20:00'];
    check(!asistencia_permiso_cruza_marcas($permisoSalida,[
        ['codigo'=>'ENTRADA','fecha_hora'=>'2026-10-13 14:59:22'],
        ['codigo'=>'SALIDA','fecha_hora'=>'2026-10-13 19:00:45'],
    ]),'Segundos en el minuto exacto de inicio de permiso parcial no generan cruce');
    check(asistencia_permiso_cruza_marcas($permisoSalida,[
        ['codigo'=>'ENTRADA','fecha_hora'=>'2026-10-13 14:59:22'],
        ['codigo'=>'SALIDA','fecha_hora'=>'2026-10-13 19:01:05'],
    ]),'Salida un minuto dentro del permiso parcial sí genera cruce');
    check(!asistencia_permiso_cruza_marcas($permisoPrueba,[
        ['codigo'=>'ENTRADA','fecha_hora'=>'2026-10-13 09:00:12'],
        ['codigo'=>'SALIDA_PERMISO','fecha_hora'=>'2026-10-13 11:00:38'],
        ['codigo'=>'REGRESO_PERMISO','fecha_hora'=>'2026-10-13 12:00:45'],
        ['codigo'=>'SALIDA','fecha_hora'=>'2026-10-13 18:00:10'],
    ]),'Segundos en bordes de pausa parcial no generan cruce');
    check(asistencia_permiso_cruza_marcas($permisoPrueba,[
        ['codigo'=>'ENTRADA','fecha_hora'=>'2026-10-13 09:00:12'],
        ['codigo'=>'SALIDA_PERMISO','fecha_hora'=>'2026-10-13 11:00:38'],
        ['codigo'=>'REGRESO_PERMISO','fecha_hora'=>'2026-10-13 11:59:45'],
        ['codigo'=>'SALIDA','fecha_hora'=>'2026-10-13 18:00:10'],
    ]),'Regreso un minuto antes del término del permiso parcial sí genera cruce');
    check(!in_array('SALIDA_PERMISO',asistencia_marcas_disponibles([marks()[0]],null),true),'No hay pausa por permiso sin solicitud');
    check(!in_array('SALIDA_PERMISO',asistencia_marcas_disponibles([marks()[0]],['tipo_ausencia'=>'PARCIAL','hora_inicio_ausencia'=>'16:00','hora_fin_ausencia'=>'18:00','nuevo_horario_inicio'=>'09:00','nuevo_horario_fin'=>'16:00']),true),'Salida anticipada usa salida final y no una pausa con regreso');

    // Probé solicitud para hoy y aprobación bloqueada hasta corregir el cruce, sin usar datos reales.
    $fixture->exec('DELETE FROM permisos_practicante; DELETE FROM marcas_asistencia; DELETE FROM jornadas_asistencia; DELETE FROM excepciones_horario_practicante');
    $hoy=date('Y-m-d');
    $fixture->prepare('INSERT INTO jornadas_asistencia (id_jornada,id_practica,fecha,estado_jornada) VALUES (200,22,?,"OBSERVADA")')->execute([$hoy]);
    $fixture->prepare('INSERT INTO marcas_asistencia (id_jornada,id_tipo_marca,fecha_hora) VALUES (200,1,?),(200,4,?)')->execute([$hoy.' 09:00:00',$hoy.' 18:00:00']);
    $_SESSION['rol_activo']='PRACTICANTE'; $_SESSION['id_practicante']=2;
    $input=['fecha_ausencia'=>$hoy,'tipo_ausencia'=>'PARCIAL','hora_inicio_ausencia'=>'11:00','hora_fin_ausencia'=>'12:00','motivo'=>'Trámite ficticio','fecha_recuperacion'=>date('Y-m-d',strtotime('+7 days')),'hora_inicio_recuperacion'=>'18:00','hora_fin_recuperacion'=>'19:00'];
    check(!permisos_gestion_solicitar(1,$input)['ok'],'Servicio rechaza solicitud de otro practicante');
    $solicitud=permisos_gestion_solicitar(2,$input);
    check($solicitud['ok'],'Permiso intermedio de hoy puede solicitarse');
    $id=(int)($solicitud['id_permiso']??0);
    $guardado=$fixture->query('SELECT * FROM permisos_practicante WHERE id_permiso='.$id)->fetch();
    check((int)$guardado['requiere_revision']===1 && $guardado['observacion_solicitud']!==null,'Cruce queda registrado en observación');
    check(!permisos_gestion_solicitar(2,$input)['ok'],'Segunda solicitud del mismo día se rechaza');
    $_SESSION['rol_activo']='SUPERVISOR'; $_SESSION['id_practicante']=1;
    check(!permisos_gestion_aprobar($id,501,'Revisado')['ok'],'Supervisor no puede aprobar cruce activo');
    check((int)$fixture->query('SELECT COUNT(*) FROM excepciones_horario_practicante')->fetchColumn()===0,'Aprobación bloqueada no crea excepciones');
    $fixture->prepare('INSERT INTO marcas_asistencia (id_jornada,id_tipo_marca,fecha_hora) VALUES (200,5,?),(200,6,?)')->execute([$hoy.' 11:00:00',$hoy.' 12:00:00']);
    check(!permisos_gestion_aprobar($id,501,'')['ok'],'Observación resuelta exige comentario');
    check(permisos_gestion_aprobar($id,501,'Corregí salida y regreso con respaldo')['ok'],'Se aprueba tras resolver cruce');
    check((int)$fixture->query('SELECT requiere_revision FROM permisos_practicante WHERE id_permiso='.$id)->fetchColumn()===0,'Aprobación cierra indicador de observación');
    check((float)asistencia_jornada_por_id(200)['horas_calculadas']===8.0,'Recálculo resta permiso sin descontar colación ficticia');
    $_SESSION['rol_activo']='PRACTICANTE'; $_SESSION['id_practicante']=2;
    // El caso prueba una interrupción ordinaria, no la política especial de recuperación sabatina.
    $input['fecha_ausencia']=date('Y-m-d',strtotime('+1 day'));
    while((int)date('N',strtotime($input['fecha_ausencia']))>5){$input['fecha_ausencia']=date('Y-m-d',strtotime($input['fecha_ausencia'].' +1 day'));}
    $input['fecha_recuperacion']=date('Y-m-d',strtotime($input['fecha_ausencia'].' +7 days'));
    failAudit('SOLICITUD_PERMISO');
    check(!permisos_gestion_solicitar(2,$input)['ok'],'Solicitud falla si no puede auditarse');
    check((int)$fixture->query('SELECT COUNT(*) FROM permisos_practicante')->fetchColumn()===1,'Fallo de auditoría no deja solicitud parcial');
    $fixture->exec('DROP TRIGGER fail_audit');
    // Actualicé el escenario a la regla vigente: una pausa requiere permiso aprobado y ventana horaria activa.
    $solicitud=permisos_gestion_solicitar(2,$input);
    check($solicitud['ok'],'Parcial futuro se guarda sin observación');
    $fecha=$input['fecha_ausencia'];
    $jornada=asistencia_obtener_o_crear_jornada(22,$fecha,501);
    check(asistencia_registrar_marca((int)$jornada['id_jornada'],'ENTRADA',$fecha.' 09:00:00',null,501,$horario)['ok'],'Entrada anterior al permiso se guarda');
    check(!asistencia_registrar_marca((int)$jornada['id_jornada'],'SALIDA_PERMISO',$fecha.' 11:00:00',null,501,$horario)['ok'],'Pausa pendiente no habilita marcaje');
    $_SESSION['rol_activo']='SUPERVISOR'; $_SESSION['id_practicante']=1;
    check(permisos_gestion_aprobar((int)$solicitud['id_permiso'],501,'Pausa respaldada')['ok'],'Supervisor aprueba ausencia futura sin cruce');
    $permisoAprobado=asistencia_permiso_del_dia(22,$fecha);
    $entradaVigente=asistencia_marcas_vigentes_jornada((int)$jornada['id_jornada']);
    check(!in_array('SALIDA_PERMISO',asistencia_marcas_disponibles($entradaVigente,$permisoAprobado,$fecha.' 10:59:00'),true),'Pausa aprobada no se adelanta');
    check(in_array('SALIDA_PERMISO',asistencia_marcas_disponibles($entradaVigente,$permisoAprobado,$fecha.' 11:00:00'),true),'Pausa aprobada se habilita dentro de su ventana');
    check(!in_array('SALIDA_PERMISO',asistencia_marcas_disponibles($entradaVigente,$permisoAprobado,$fecha.' 12:00:00'),true),'Pausa aprobada no se inicia después de la ventana');
    check(!asistencia_registrar_marca((int)$jornada['id_jornada'],'SALIDA_PERMISO',$fecha.' 11:00:00',null,501,$horario)['ok'],'Fecha enviada no permite saltar ventana real del servidor');
    // Sembré únicamente las marcas de la pausa futura autorizada; el reloj real no se cambia para simular ese día.
    $fixture->prepare('INSERT INTO marcas_asistencia (id_jornada,id_tipo_marca,fecha_hora) VALUES (?,5,?),(?,6,?)')->execute([$jornada['id_jornada'],$fecha.' 11:00:00',$jornada['id_jornada'],$fecha.' 12:00:00']);
    foreach (['ENTRADA_COLACION'=>'13:00:00','SALIDA_COLACION'=>'14:00:00','SALIDA'=>'18:00:00'] as $codigo=>$hora) {
        check(asistencia_registrar_marca((int)$jornada['id_jornada'],$codigo,$fecha.' '.$hora,null,501,$horario)['ok'],'Marca de interrupción: '.$codigo);
    }
    $antes=asistencia_marcas_vigentes_jornada((int)$jornada['id_jornada']);
    $cerrada=asistencia_jornada_por_id((int)$jornada['id_jornada']);
    check($cerrada['estado_jornada']==='VALIDA' && (float)$cerrada['horas_calculadas']===7.0,'Permiso aprobado reconoce ambos tramos sin pausa');
    asistencia_recalcular_jornada((int)$jornada['id_jornada'],$horario);
    check(asistencia_marcas_vigentes_jornada((int)$jornada['id_jornada'])===$antes,'Recalcular permiso aprobado no modifica marcas');
    // Comprobé que la escritura desempata marcas del mismo segundo sin usar el ordinal fijo del catálogo.
    $fixture->prepare('UPDATE marcas_asistencia SET fecha_hora = ? WHERE id_jornada = ?')->execute([$fecha.' 09:00:00',(int)$jornada['id_jornada']]);
    $simultaneas=asistencia_marcas_vigentes_jornada((int)$jornada['id_jornada']);
    check(asistencia_ultima_marca_vigente($simultaneas)==='SALIDA','Última marca del mismo segundo respeta orden de escritura');
    check(asistencia_permiso_cruza_marcas($input,$simultaneas)===false,'Desempate conserva secuencia de seis marcas');

    // Probé recuperación por minutos, calendario común y autorización especial, sin datos reales.
    require_once __DIR__.'/../src/Models/CatalogoModel.php';
    require_once __DIR__.'/../src/Services/AusenciasEsperadasService.php';
    check(!ausencias_esperadas_generar('2026-10-01','2026-10-02')['ok'],'Supervisor no genera ausencias desde el servicio');
    $def=catalogos_admin_definicion('feriados');
    check($def!==null && catalogos_admin_validar($def,['nombre'=>'Feriado ficticio','fecha'=>'2026-02-30'])!==[],'Calendario rechaza fecha inexistente');
    $fechaPlan=date('Y-m-d',strtotime('+21 days'));
    while ((int)date('N',strtotime($fechaPlan))>5) { $fechaPlan=date('Y-m-d',strtotime($fechaPlan.' +1 day')); }
    $aus=['id_permiso'=>9999,'fecha_ausencia'=>date('Y-m-d',strtotime('+20 days')),'tipo_ausencia'=>'PARCIAL','hora_inicio_ausencia'=>'11:00','hora_fin_ausencia'=>'13:00'];
    $plan=[['fecha'=>$fechaPlan,'tipo'=>'COLACION','hora_inicio'=>'13:00','hora_fin'=>'14:00'],['fecha'=>$fechaPlan,'tipo'=>'EXTENSION','hora_inicio'=>'18:00','hora_fin'=>'19:00']];
    check(recuperaciones_validar_plan(22,$plan,120,$aus)['errores']===[],'Colación y extensión suman la ausencia exacta');
    check(recuperaciones_validar_plan(22,$plan,121,$aus)['errores']!==[],'No se acepta déficit de un minuto en el plan');
    $malo=$plan;$malo[1]['hora_inicio']='17:00';
    check(recuperaciones_validar_plan(22,$malo,180,$aus)['errores']!==[],'Extensión no invade jornada ordinaria');
    $malo=$plan;$malo[1]=$malo[0];
    check(recuperaciones_validar_plan(22,$malo,120,$aus)['errores']!==[],'Dos tramos no reutilizan la misma colación');
    $malo=$plan;$malo[0]['hora_inicio']='13:00invalido';
    check(recuperaciones_validar_plan(22,$malo,120,$aus)['errores']!==[],'Entrada libre no trunca horas inválidas');
    $malo=[['fecha'=>$fechaPlan,'tipo'=>'EXTENSION','hora_inicio'=>'19:00','hora_fin'=>'21:00']];
    check(recuperaciones_validar_plan(22,$malo,120,$aus)['errores']!==[],'Recuperación no supera las 20:00');
    $fixture->prepare('INSERT INTO feriados (fecha,nombre) VALUES (?,?)')->execute([$fechaPlan,'Feriado ficticio']);
    check(asistencia_horario_vigente_dia(22,$fechaPlan)===null,'Feriado común excluye jornada ordinaria');
    check(recuperaciones_opciones_fecha(22,$fechaPlan)[0]['tipo']==='FERIADO','Feriado ofrece solo recuperación especial');
    $fixture->prepare('INSERT INTO permisos_practicante (id_permiso,id_practica,horas_ausencia,fecha_ausencia,tipo_ausencia,hora_inicio_ausencia,hora_fin_ausencia,nuevo_horario_inicio,nuevo_horario_fin,horas_trabajo_dia) VALUES (601,22,2,?,"PARCIAL","11:00","13:00","09:00","18:00",6)')->execute([$aus['fecha_ausencia']]);
    recuperaciones_guardar_plan(601,[['fecha'=>$fechaPlan,'tipo'=>'FERIADO','hora_inicio'=>'14:00','hora_fin'=>'16:00','minutos_planificados'=>120]]);
    check(!permisos_gestion_aprobar(601,501,'Autorización ficticia')['ok'],'Feriado no se autoriza solo con comentario');
    check(!permisos_gestion_aprobar(601,501,'',true)['ok'],'Feriado requiere motivo además de consentimiento');
    check(permisos_gestion_aprobar(601,501,'Autorización expresa de recuperación',true)['ok'],'Supervisor autoriza expresamente el feriado');
    check(recuperaciones_plan(601)[0]['estado']==='PROGRAMADA' && (int)recuperaciones_plan(601)[0]['minutos_validados']===0,'Autorización no reconoce automáticamente horas');

    // Registré jornada sintética pasada para validar colación y extensión realmente trabajadas.
    seedDay(310,22,'OBSERVADA','19:00:00');
    $fixture->exec('INSERT INTO permisos_practicante (id_permiso,id_practica,estado,horas_ausencia,fecha_ausencia,tipo_ausencia,hora_inicio_ausencia,hora_fin_ausencia) VALUES (602,22,"APROBADO",2,"2026-10-12","PARCIAL","11:00","13:00")');
    recuperaciones_guardar_plan(602,[['fecha'=>'2026-10-13','tipo'=>'COLACION','hora_inicio'=>'13:00','hora_fin'=>'14:00','minutos_planificados'=>60],['fecha'=>'2026-10-13','tipo'=>'EXTENSION','hora_inicio'=>'18:00','hora_fin'=>'19:00','minutos_planificados'=>60]]);
    $fixture->exec('UPDATE recuperaciones_permiso SET estado="PROGRAMADA" WHERE id_permiso=602');
    asistencia_recalcular_jornada(310);
    $antes=asistencia_jornada_por_id(310);
    check((float)$antes['horas_calculadas']===8.0 && (int)$antes['flag_horas_extra']===0,'Recuperación pendiente no cuenta como base ni extra');
    $tramos=recuperaciones_plan(602);
    check(!recuperaciones_validar((int)$tramos[0]['id_recuperacion'],'')['ok'],'Validar minutos requiere motivo');
    $_SESSION['rol_activo']='PRACTICANTE';$_SESSION['id_practicante']=2;
    check(!recuperaciones_validar((int)$tramos[0]['id_recuperacion'],'Autovalidación')['ok'],'Practicante no valida sus horas');
    $_SESSION['rol_activo']='SUPERVISOR';$_SESSION['id_practicante']=1;
    failAudit('VALIDAR_RECUPERACION');
    check(!recuperaciones_validar((int)$tramos[0]['id_recuperacion'],'Motivo ficticio')['ok'],'Fallo de auditoría revierte validación');
    check((int)recuperaciones_plan(602)[0]['minutos_validados']===0 && (float)asistencia_jornada_por_id(310)['horas_calculadas']===8.0,'Rollback conserva saldo y acumulado anterior');
    $fixture->exec('DROP TRIGGER fail_audit');
    check(recuperaciones_validar((int)$tramos[0]['id_recuperacion'],'Trabajó colación solicitada')['ok'],'Supervisor valida colación con entrada/salida');
    check((float)asistencia_jornada_por_id(310)['horas_calculadas']===9.0,'Solo colación validada agrega una hora');
    check(!recuperaciones_validar((int)$tramos[0]['id_recuperacion'],'Segundo intento')['ok'],'No se duplica validación');
    check(recuperaciones_validar((int)$tramos[1]['id_recuperacion'],'Trabajó extensión acordada')['ok'],'Supervisor valida segundo tramo');
    check((float)asistencia_jornada_por_id(310)['horas_calculadas']===10.0 && asistencia_jornada_por_id(310)['estado_jornada']==='VALIDA','Trabajo ordinario y recuperación validada suman sin duplicarse');
    $fixture->exec('UPDATE marcas_asistencia SET fecha_hora="2026-10-13 18:30:00" WHERE id_jornada=310 AND id_tipo_marca=4');
    asistencia_recalcular_jornada(310);
    check(array_sum(array_column(recuperaciones_plan(602),'minutos_validados'))===0,'Corrección posterior reabre validaciones anteriores');
    check(recuperaciones_validar((int)$tramos[1]['id_recuperacion'],'Nueva salida respaldada')['ok'],'Se revalida con marcas corregidas');
    check((int)recuperaciones_plan(602)[1]['minutos_validados']===30,'Extensión solo valida treinta minutos realmente trabajados');
    check((float)asistencia_jornada_por_id(310)['horas_calculadas']===8.5,'Crédito parcial no inventa el resto de la recuperación');
    check(recuperaciones_saldo_libre($fixture->query('SELECT * FROM permisos_practicante WHERE id_permiso=602')->fetch(),recuperaciones_plan(602))===30,'Saldo no trabajado queda libre para volver a solicitar');
    $extraFecha=date('Y-m-d',strtotime($fechaPlan.' +1 day'));
    while((int)date('N',strtotime($extraFecha))>5){$extraFecha=date('Y-m-d',strtotime($extraFecha.' +1 day'));}
    $extra=['fecha'=>$extraFecha,'tipo'=>'COLACION','hora_inicio'=>'13:00','hora_fin'=>'13:30'];
    $_SESSION['rol_activo']='PRACTICANTE';$_SESSION['id_practicante']=1;
    check(!recuperaciones_gestionar_adicional(602,$extra,'Saldo')['ok'],'Otro practicante no modifica plan ajeno');
    $_SESSION['id_practicante']=2;
    $exceso=$extra;$exceso['hora_fin']='14:00';
    check(!recuperaciones_gestionar_adicional(602,$exceso,'Saldo')['ok'],'Tramo adicional no supera saldo libre');
    check(recuperaciones_gestionar_adicional(602,$extra,'Recuperaré los treinta minutos pendientes')['ok'],'Practicante solicita saldo no trabajado');
    $ultimo=recuperaciones_plan(602)[2];
    check($ultimo['estado']==='PROPUESTA','Saldo adicional exige nueva autorización');
    check(!recuperaciones_gestionar_adicional(602,[],'Autorizar',(int)$ultimo['id_recuperacion'])['ok'],'Practicante no autoriza su tramo adicional');
    $_SESSION['rol_activo']='SUPERVISOR';$_SESSION['id_practicante']=1;
    check(recuperaciones_gestionar_adicional(602,[],'Se autoriza el saldo',(int)$ultimo['id_recuperacion'])['ok'],'Supervisor autoriza saldo adicional');
    check((int)recuperaciones_plan(602)[2]['minutos_validados']===0,'Autorización adicional tampoco reconoce horas futuras');
    $fixture->exec('INSERT INTO permisos_practicante (id_permiso,id_practica,estado,horas_ausencia,fecha_ausencia) VALUES (603,22,"APROBADO",0.5,"2026-10-09")');
    recuperaciones_guardar_plan(603,[['fecha'=>'2026-10-12','tipo'=>'COLACION','hora_inicio'=>'13:00','hora_fin'=>'13:30','minutos_planificados'=>30]]);
    $fixture->exec('UPDATE recuperaciones_permiso SET estado="PROGRAMADA" WHERE id_permiso=603');
    check(recuperaciones_validar((int)recuperaciones_plan(603)[0]['id_recuperacion'],'No existe trabajo registrado en ese tramo')['ok'],'Supervisor puede cerrar tramo sin trabajo con cero minutos');
    check(recuperaciones_saldo_libre($fixture->query('SELECT * FROM permisos_practicante WHERE id_permiso=603')->fetch(),recuperaciones_plan(603))===30,'Cierre en cero conserva íntegro saldo recuperable');
    // Cambiar calendario antes de reconocer un tramo no permite acreditarlo bajo condiciones antiguas.
    $fixture->exec('INSERT INTO feriados (fecha,nombre) VALUES ("2026-10-13","Cambio de calendario ficticio")');
    check(recuperaciones_validar((int)$tramos[0]['id_recuperacion'],'Se revisa el calendario cambiado')['ok'],'Cambio posterior de calendario envía el tramo a reprogramación');
    $reprogramado=recuperaciones_plan(602)[0];
    check($reprogramado['estado']==='REPROGRAMAR' && (int)$reprogramado['minutos_validados']===0,'Tramo que dejó de ser elegible no genera crédito');
    check(recuperaciones_saldo_libre($fixture->query('SELECT * FROM permisos_practicante WHERE id_permiso=602')->fetch(),recuperaciones_plan(602))===60,'Reprogramación libera saldo y conserva historial');
    require_once __DIR__.'/../src/Models/PracticanteModel.php';
    $normalizado=practicante_registro_normalizar(['id_region'=>'1','id_comuna'=>'1'],array_fill_keys(['instituciones','nacionalidades'],[]));
    check($normalizado['id_region']===1 && $normalizado['id_comuna']===1 && $normalizado['pais']==='Chile','Normalización conserva ubicación y país derivado');
    // Una corrección en la ausencia también debe retirar crédito previamente validado en otro día.
    $fixture->exec('INSERT INTO jornadas_asistencia (id_jornada,id_practica,fecha,estado_jornada) VALUES (900,22,"2026-10-12","OBSERVADA")');
    $fixture->exec('INSERT INTO marcas_asistencia (id_jornada,id_tipo_marca,fecha_hora) VALUES (900,1,"2026-10-12 09:00:00"),(900,4,"2026-10-12 18:00:00")');
    asistencia_recalcular_jornada(900);
    check((int)recuperaciones_plan(602)[1]['minutos_validados']===0,'Cruce en origen retira recuperación reconocida en otro día');
    check((float)asistencia_jornada_por_id(310)['horas_calculadas']===0.0,'Informe del día dependiente ya no conserva crédito reabierto');
    $rechazo=recuperaciones_validar((int)$tramos[1]['id_recuperacion'],'Intento con ausencia cruzada');
    check(!$rechazo['ok'] && str_contains(implode(' ',$rechazo['errores']),'ausencia de origen cruza'),'No puede volver a validar hasta resolver ausencia trabajada');

    // Aseguré que los sábados nuevos necesiten aprobación y los históricos conserven su política.
    $sabado=['id_practica'=>22,'fecha'=>'2026-10-10','politica_recuperacion_v2'=>1];
    $marcasSab=marks('14:00:00');foreach($marcasSab as &$m){$m['fecha_hora']=str_replace('2026-10-13','2026-10-10',$m['fecha_hora']);}unset($m);
    $resumenSab=recuperaciones_ajustar_resumen($sabado,$marcasSab,null,asistencia_calcular_resumen_jornada($marcasSab,null));
    check($resumenSab['horas_calculadas']===0.0 && $resumenSab['horas_extra_sugeridas']===5.0,'Sábado nuevo sin aprobación no suma');
    $sabado['politica_recuperacion_v2']=0;
    check(recuperaciones_ajustar_resumen($sabado,$marcasSab,null,asistencia_calcular_resumen_jornada($marcasSab,null))['horas_calculadas']===5.0,'Sábado histórico conserva reconocimiento anterior');
    // Agregué regresiones aisladas de regularización y recuperación antes de la ausencia.
    require __DIR__.'/permisos-anticipados.php';
    // Ejecuté la política nueva después de las regresiones anteriores, con su propio corte y datos sintéticos.
    require __DIR__.'/tiempo-excepcional.php';
} catch (Throwable $exception) {
    $failures[] = 'Excepción inesperada: ' . $exception->getMessage();
} finally {
    $_SESSION = [];
    session_destroy();
}
foreach ($failures as $failure) { fwrite(STDERR, "FALLO: $failure\n"); }
echo "$checks comprobaciones de negocio; " . count($failures) . " fallos.\n";
exit($failures === [] ? 0 : 1);
