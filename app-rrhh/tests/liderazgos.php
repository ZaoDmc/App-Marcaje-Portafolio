<?php

declare(strict_types=1);

// Agregué regresiones con SQLite en memoria; no certifican bloqueos InnoDB ni modifican usuarios locales.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
final class LiderazgoTestPDO extends PDO
{
    public function __construct() {
        parent::__construct('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    }
    public function prepare(string $query, array $options=[]): PDOStatement|false {
        $query = str_replace('FOR UPDATE', '', $query);
        $query = preg_replace('/DATE_SUB\((:\w+), INTERVAL 1 DAY\)/', 'date($1, "-1 day")', $query);
        return parent::prepare($query, $options);
    }
}
$fixture = new LiderazgoTestPDO();
function db(): PDO { return $GLOBALS['fixture']; }
$sessionDir = dirname(__DIR__).'/scratch/liderazgos-sessions';
if (!is_dir($sessionDir)) { mkdir($sessionDir,0700,true); }
ini_set('session.save_path',$sessionDir);
ini_set('error_log',$sessionDir.'/expected-errors.log');
session_id('liderazgo-'.bin2hex(random_bytes(8))); session_start();
$_SESSION=['id_usuario'=>501,'id_rol_activo'=>1,'rol_activo'=>'ADMIN'];
require_once dirname(__DIR__).'/src/Services/HorariosPracticanteService.php';
require_once dirname(__DIR__).'/src/Models/CatalogoModel.php';
$fixture->exec(<<<'SQL'
CREATE TABLE areas_practica (id_area INTEGER PRIMARY KEY,nombre TEXT,descripcion TEXT,activo INTEGER DEFAULT 1);
INSERT INTO areas_practica VALUES (1,'Desarrollo',NULL,1),(2,'Diseño',NULL,1),(3,'Inactiva',NULL,0);
CREATE TABLE estados_practica (id_estado_practica INTEGER PRIMARY KEY,nombre TEXT);
INSERT INTO estados_practica VALUES (1,'EN_PROGRESO'),(2,'PENDIENTE_INICIO'),(3,'COMPLETADA');
CREATE TABLE practicantes (id_practicante INTEGER PRIMARY KEY,nombre_1 TEXT,apellido_paterno TEXT,email TEXT,activo INTEGER DEFAULT 1);
INSERT INTO practicantes VALUES (2,'Uno','Ficticio','uno@example.test',1),(3,'Dos','Ficticio','dos@example.test',1),(4,'Tres','Ficticio','tres@example.test',1),(5,'Cuatro','Ficticio','cuatro@example.test',1);
-- Amplié la fixture con los campos de identidad que utiliza el buscador de candidatos.
ALTER TABLE practicantes ADD COLUMN nombre_2 TEXT;
ALTER TABLE practicantes ADD COLUMN apellido_materno TEXT;
ALTER TABLE practicantes ADD COLUMN rut_numero TEXT;
ALTER TABLE practicantes ADD COLUMN rut_dv TEXT;
CREATE TABLE practicas (id_practica INTEGER PRIMARY KEY,id_practicante INTEGER,id_estado_practica INTEGER,activo INTEGER DEFAULT 1);
INSERT INTO practicas VALUES (22,2,1,1),(23,3,2,1),(24,4,1,1),(25,5,3,1);
CREATE TABLE usuarios_sistema (id_usuario INTEGER PRIMARY KEY,id_practicante INTEGER,email TEXT,estado_cuenta TEXT,activo INTEGER DEFAULT 1);
INSERT INTO usuarios_sistema VALUES (501,NULL,'admin@example.test','ACTIVO',1),(502,2,'uno@example.test','ACTIVO',1);
CREATE TABLE historial_areas_practicante (id_historial_area INTEGER PRIMARY KEY,id_practica INTEGER,id_area INTEGER,fecha_inicio TEXT,fecha_fin TEXT,observacion TEXT,asignado_por INTEGER);
INSERT INTO historial_areas_practicante VALUES (1,22,1,'2026-01-01',NULL,NULL,501),(2,23,1,'2026-01-01',NULL,NULL,501),(3,24,2,'2026-01-01',NULL,NULL,501),(4,25,1,'2026-01-01',NULL,NULL,501);
CREATE TABLE liderazgos_area (id_liderazgo INTEGER PRIMARY KEY,id_area INTEGER,id_practica INTEGER,fecha_inicio TEXT,fecha_fin TEXT,asignado_por INTEGER,finalizado_por INTEGER,motivo_designacion TEXT,motivo_fin TEXT,
 id_area_vigente INTEGER GENERATED ALWAYS AS (CASE WHEN fecha_fin IS NULL THEN id_area ELSE NULL END) STORED UNIQUE);
CREATE TABLE auditoria_cambios (id_auditoria INTEGER PRIMARY KEY,id_usuario_actor INTEGER,id_rol_contexto INTEGER,id_sesion INTEGER,evento TEXT,tabla_afectada TEXT,id_registro_afectado INTEGER,valores_anteriores TEXT,valores_nuevos TEXT,ip TEXT,user_agent TEXT);
SQL);
$checks=0;
function liderCheck(bool $ok,string $mensaje): void {
    $GLOBALS['checks']++;
    if (!$ok) { throw new RuntimeException($mensaje); }
}
function liderRechaza(callable $accion,string $texto): void {
    try { $accion(); } catch (DomainException $e) { liderCheck(str_contains($e->getMessage(),$texto),'Rechazo inesperado: '.$e->getMessage()); return; }
    throw new RuntimeException('Se permitió una operación inválida: '.$texto);
}
try {
    liderCheck(liderazgos_disponible(),'Migración disponible');
    liderCheck(count(liderazgos_areas())===3,'Áreas activas e inactivas');
    liderCheck(count(liderazgos_integrantes(1))===2,'Candidaturas excluyen práctica completada');
    foreach (['SUPERVISOR','PRACTICANTE'] as $rol) {
        $_SESSION['rol_activo']=$rol;
        liderRechaza(fn()=>liderazgos_guardar(1,22,0,'Prueba'),'Solo ADMIN');
        liderRechaza(fn()=>liderazgos_historial(1),'Solo ADMIN');
    }
    $_SESSION['rol_activo']='ADMIN';
    liderRechaza(fn()=>liderazgos_guardar(1,22,0,''),'motivo');
    liderRechaza(fn()=>liderazgos_guardar(1,22,0,str_repeat('á',501)),'motivo');
    liderRechaza(fn()=>liderazgos_guardar(999,22,0,'Prueba'),'área activa');
    liderRechaza(fn()=>liderazgos_guardar(3,22,0,'Prueba'),'área activa');
    liderRechaza(fn()=>liderazgos_guardar(1,24,0,'Prueba'),'pertenecer');
    liderRechaza(fn()=>liderazgos_guardar(1,25,0,'Prueba'),'pertenecer');
    $fixture->exec("UPDATE historial_areas_practicante SET fecha_inicio='2999-01-01' WHERE id_practica=23");
    liderRechaza(fn()=>liderazgos_guardar(1,23,0,'Prueba'),'pertenecer');
    $fixture->exec("UPDATE historial_areas_practicante SET fecha_inicio='2026-01-01' WHERE id_practica=23");
    liderCheck(liderazgos_guardar(1,22,0,'Designación inicial')['ok'],'Designación');
    $primero=(int)$fixture->query('SELECT id_liderazgo FROM liderazgos_area WHERE fecha_fin IS NULL')->fetchColumn();
    liderRechaza(fn()=>liderazgos_guardar(1,23,0,'Formulario viejo'),'cambió');
    liderRechaza(fn()=>liderazgos_guardar(1,22,$primero,'Repetido'),'ya tiene');
    $cambio=areas_practicante_cambiar(22,2,date('Y-m-d'),'Traslado');
    liderCheck(!$cambio['ok'] && str_contains(implode(' ',$cambio['errores']),'Retira primero'),'Traslado exige retiro explícito');
    liderRechaza(fn()=>horarios_practicante_baja_logica_practicante(2),'Retira primero');
    liderCheck((int)$fixture->query('SELECT activo FROM practicantes WHERE id_practicante=2')->fetchColumn()===1,'Baja bloqueada conserva datos');
    liderCheck(!catalogos_admin_cambiar_estado('areas',1,false)['ok'],'Desactivar área exige retirar líder');
    liderCheck(liderazgos_guardar(1,23,$primero,'Reemplazo de líder')['ok'],'Reemplazo');
    $historial=liderazgos_historial(1); $segundo=(int)$historial[0]['id_liderazgo'];
    liderCheck(count($historial)===2 && $historial[1]['fecha_fin']!==null && (int)$historial[1]['finalizado_por']===501,'Historial conserva cierre y actor');
    liderRechaza(fn()=>liderazgos_guardar(1,0,$primero,'Retiro obsoleto'),'cambió');
    // Comprobé el índice generado incluso saltando el servicio de aplicación.
    $duplicado=false;
    try { $fixture->exec("INSERT INTO liderazgos_area (id_area,id_practica,fecha_inicio,asignado_por,motivo_designacion) VALUES (1,22,CURRENT_TIMESTAMP,501,'Duplicado')"); }
    catch (PDOException) { $duplicado=true; }
    liderCheck($duplicado,'La base rechaza dos líderes vigentes');
    // Un fallo de auditoría debe revertir también el reemplazo y su cierre anterior.
    $fixture->exec("CREATE TRIGGER sin_auditoria BEFORE INSERT ON auditoria_cambios BEGIN SELECT RAISE(FAIL,'Fallo simulado'); END");
    $rollback=false;
    try { liderazgos_guardar(1,22,$segundo,'Debe revertirse'); } catch (RuntimeException) { $rollback=true; }
    liderCheck($rollback && count(liderazgos_historial(1))===2 && (int)liderazgos_historial(1)[0]['id_practica']===23,'Auditoría fallida revierte reemplazo');
    $fixture->exec('DROP TRIGGER sin_auditoria');
    liderCheck(liderazgos_guardar(1,0,$segundo,'Fin de designación')['ok'],'Retiro');
    liderCheck((int)$fixture->query('SELECT COUNT(*) FROM liderazgos_area WHERE fecha_fin IS NULL')->fetchColumn()===0,'Área sin líder');
    liderCheck(areas_practicante_cambiar(22,2,date('Y-m-d'),'Traslado autorizado')['ok'],'Traslado permitido tras retirar liderazgo');
    liderCheck(catalogos_admin_cambiar_estado('areas',1,false)['ok'],'Área se puede desactivar tras retiro');
    liderCheck(horarios_practicante_baja_logica_practicante(2),'Baja permitida sin liderazgo');
    liderCheck(count(liderazgos_historial(1))===2,'Baja conserva historial');
    liderCheck((int)$fixture->query("SELECT COUNT(*) FROM auditoria_cambios WHERE evento IN ('DESIGNAR_LIDER_AREA','REEMPLAZAR_LIDER_AREA','RETIRAR_LIDER_AREA')")->fetchColumn()===3,'Tres eventos de liderazgo auditados');
    echo "Liderazgos: $checks comprobaciones correctas.\n";
} catch (Throwable $e) {
    fwrite(STDERR,'FALLO Liderazgos: '.$e->getMessage()."\n"); exit(1);
}
