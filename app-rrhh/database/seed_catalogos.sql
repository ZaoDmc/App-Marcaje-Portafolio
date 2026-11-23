-- Seeds iniciales de catÃ¡logos del Portal de Asistencia y Control de Practicantes.
-- Este archivo no elimina datos; inserta o actualiza catÃ¡logos base por claves Ãºnicas.

SET NAMES utf8mb4;

UPDATE carreras SET nombre = 'Ingeniería en Informática' WHERE nombre = 'IngenierÃ­a en InformÃ¡tica';
UPDATE carreras SET nombre = 'Técnico en Informática' WHERE nombre = 'TÃ©cnico en InformÃ¡tica';
UPDATE carreras SET nombre = 'Técnico en Administración' WHERE nombre = 'TÃ©cnico en AdministraciÃ³n';
UPDATE carreras SET nombre = 'Técnico en Logística' WHERE nombre = 'TÃ©cnico en LogÃ­stica';
UPDATE carreras SET nombre = 'Diseño Gráfico' WHERE nombre = 'DiseÃ±o GrÃ¡fico';

UPDATE areas_practica SET nombre = 'Creación de Productos' WHERE nombre = 'CreaciÃ³n de Productos';
UPDATE areas_practica SET nombre = 'Automatización IA' WHERE nombre = 'AutomatizaciÃ³n IA';
UPDATE areas_practica SET nombre = 'Diseño Gráfico' WHERE nombre = 'DiseÃ±o GrÃ¡fico';
UPDATE areas_practica SET nombre = 'Administración' WHERE nombre = 'AdministraciÃ³n';
UPDATE areas_practica SET nombre = 'Logística' WHERE nombre = 'LogÃ­stica';

-- Las regiones y comunas se cargan desde seed_regiones_comunas.sql para mantener
-- el catalogo territorial completo y reutilizable en todos los formularios.
INSERT INTO instituciones (nombre, activo) VALUES
('Duoc UC', 1),
('INACAP', 1),
('ENAC', 1),
('Otra', 1)
ON DUPLICATE KEY UPDATE activo = VALUES(activo);

-- Sedes normalizadas Duoc UC.
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'Campus Virtual', 'VIRTUAL', 1 FROM instituciones WHERE nombre = 'Duoc UC'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'Sede Alameda', 'ALAMEDA', 1 FROM instituciones WHERE nombre = 'Duoc UC'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'Sede Padre Alonso de Ovalle', 'PAO', 1 FROM instituciones WHERE nombre = 'Duoc UC'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'Sede Antonio Varas', 'AVARAS', 1 FROM instituciones WHERE nombre = 'Duoc UC'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'Sede Maipú', 'MAIPU', 1 FROM instituciones WHERE nombre = 'Duoc UC'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'Sede Melipilla', 'MELIPILLA', 1 FROM instituciones WHERE nombre = 'Duoc UC'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'Sede Plaza Norte', 'PLAZA_NORTE', 1 FROM instituciones WHERE nombre = 'Duoc UC'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'Sede Plaza Oeste', 'PLAZA_OESTE', 1 FROM instituciones WHERE nombre = 'Duoc UC'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'Sede Plaza Vespucio', 'PLAZA_VESPUCIO', 1 FROM instituciones WHERE nombre = 'Duoc UC'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'Sede Puente Alto', 'PUENTE_ALTO', 1 FROM instituciones WHERE nombre = 'Duoc UC'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'Sede San Bernardo', 'SAN_BERNARDO', 1 FROM instituciones WHERE nombre = 'Duoc UC'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'Sede San Carlos de Apoquindo', 'SAN_CARLOS_APOQUINDO', 1 FROM instituciones WHERE nombre = 'Duoc UC'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'Sede San Joaquín', 'SAN_JOAQUIN', 1 FROM instituciones WHERE nombre = 'Duoc UC'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'Sede Valparaíso', 'VALPARAISO', 1 FROM instituciones WHERE nombre = 'Duoc UC'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'Sede Viña del Mar', 'VINA_DEL_MAR', 1 FROM instituciones WHERE nombre = 'Duoc UC'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'Campus Arauco', 'ARAUCO', 1 FROM instituciones WHERE nombre = 'Duoc UC'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'Campus Nacimiento', 'NACIMIENTO', 1 FROM instituciones WHERE nombre = 'Duoc UC'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'Sede San Andrés de Concepción', 'SAN_ANDRES_CONCEPCION', 1 FROM instituciones WHERE nombre = 'Duoc UC'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'Campus Villarrica', 'VILLARRICA', 1 FROM instituciones WHERE nombre = 'Duoc UC'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'Sede Puerto Montt', 'PUERTO_MONTT', 1 FROM instituciones WHERE nombre = 'Duoc UC'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'Duoc UC - No especificada', 'NO_ESPECIFICADA', 1 FROM instituciones WHERE nombre = 'Duoc UC'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);

-- Sedes normalizadas INACAP.
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'INACAP Arica', 'ARICA', 1 FROM instituciones WHERE nombre = 'INACAP'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'INACAP Iquique', 'IQUIQUE', 1 FROM instituciones WHERE nombre = 'INACAP'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'INACAP Calama', 'CALAMA', 1 FROM instituciones WHERE nombre = 'INACAP'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'INACAP Antofagasta', 'ANTOFAGASTA', 1 FROM instituciones WHERE nombre = 'INACAP'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'INACAP Copiapó', 'COPIAPO', 1 FROM instituciones WHERE nombre = 'INACAP'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'INACAP La Serena', 'LA_SERENA', 1 FROM instituciones WHERE nombre = 'INACAP'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'INACAP Valparaíso', 'VALPARAISO', 1 FROM instituciones WHERE nombre = 'INACAP'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'INACAP Apoquindo', 'APOQUINDO', 1 FROM instituciones WHERE nombre = 'INACAP'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'INACAP Maipú', 'MAIPU', 1 FROM instituciones WHERE nombre = 'INACAP'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'INACAP Renca', 'RENCA', 1 FROM instituciones WHERE nombre = 'INACAP'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'INACAP Ñuñoa', 'NUNOA', 1 FROM instituciones WHERE nombre = 'INACAP'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'INACAP Santiago Centro', 'SANTIAGO_CENTRO', 1 FROM instituciones WHERE nombre = 'INACAP'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'INACAP Santiago Sur', 'SANTIAGO_SUR', 1 FROM instituciones WHERE nombre = 'INACAP'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'INACAP La Granja', 'LA_GRANJA', 1 FROM instituciones WHERE nombre = 'INACAP'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'INACAP Puente Alto', 'PUENTE_ALTO', 1 FROM instituciones WHERE nombre = 'INACAP'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'INACAP Sede Central', 'SEDE_CENTRAL', 1 FROM instituciones WHERE nombre = 'INACAP'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'INACAP Rancagua', 'RANCAGUA', 1 FROM instituciones WHERE nombre = 'INACAP'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'INACAP Curicó', 'CURICO', 1 FROM instituciones WHERE nombre = 'INACAP'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'INACAP Talca', 'TALCA', 1 FROM instituciones WHERE nombre = 'INACAP'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'INACAP Chillán', 'CHILLAN', 1 FROM instituciones WHERE nombre = 'INACAP'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'INACAP Concepción-Talcahuano', 'CONCEPCION_TALCAHUANO', 1 FROM instituciones WHERE nombre = 'INACAP'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'INACAP San Pedro de la Paz', 'SAN_PEDRO_DE_LA_PAZ', 1 FROM instituciones WHERE nombre = 'INACAP'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'INACAP Los Ángeles', 'LOS_ANGELES', 1 FROM instituciones WHERE nombre = 'INACAP'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'INACAP Temuco', 'TEMUCO', 1 FROM instituciones WHERE nombre = 'INACAP'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'INACAP Valdivia', 'VALDIVIA', 1 FROM instituciones WHERE nombre = 'INACAP'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'INACAP Osorno', 'OSORNO', 1 FROM instituciones WHERE nombre = 'INACAP'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'INACAP Puerto Montt', 'PUERTO_MONTT', 1 FROM instituciones WHERE nombre = 'INACAP'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'INACAP Coyhaique', 'COYHAIQUE', 1 FROM instituciones WHERE nombre = 'INACAP'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'INACAP Punta Arenas', 'PUNTA_ARENAS', 1 FROM instituciones WHERE nombre = 'INACAP'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'INACAP Online', 'ONLINE', 1 FROM instituciones WHERE nombre = 'INACAP'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'INACAP - No especificada', 'NO_ESPECIFICADA', 1 FROM instituciones WHERE nombre = 'INACAP'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);

-- Sedes normalizadas ENAC.
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'ENAC 1 - Edificio Padre Baldo Santi', 'ENAC_1', 1 FROM instituciones WHERE nombre = 'ENAC'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'ENAC 2 - Edificio Elisa Sanfuentes', 'ENAC_2', 1 FROM instituciones WHERE nombre = 'ENAC'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'ENAC 4 - Edificio Mercedes Ezquerra', 'ENAC_4', 1 FROM instituciones WHERE nombre = 'ENAC'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'ENAC - No especificada', 'NO_ESPECIFICADA', 1 FROM instituciones WHERE nombre = 'ENAC'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);

-- Fallback de instituciÃ³n Otra. No debe usarse para guardar texto libre de usuarios.
INSERT INTO sedes (id_institucion, nombre_sede, codigo_sede, activo)
SELECT id_institucion, 'Otra - No especificada', 'NO_ESPECIFICADA', 1 FROM instituciones WHERE nombre = 'Otra'
ON DUPLICATE KEY UPDATE nombre_sede = VALUES(nombre_sede), activo = VALUES(activo);

INSERT INTO carreras (nombre, activo) VALUES
('Analista Programador', 1),
('Ingeniería en Informática', 1),
('Técnico en Informática', 1),
('Técnico en Administración', 1),
('Técnico en Logística', 1),
('Comercio Exterior', 1),
('Marketing', 1),
('Recursos Humanos', 1),
('Contabilidad', 1),
('Diseño Gráfico', 1),
('Otra', 1)
ON DUPLICATE KEY UPDATE activo = VALUES(activo);

INSERT INTO tipos_practica (nombre, horas_base, activo) VALUES
('Laboral', 180, 1),
('Profesional', 360, 1),
('Profesional extendida', 540, 1),
('Otra', 0, 1)
ON DUPLICATE KEY UPDATE horas_base = VALUES(horas_base), activo = VALUES(activo);

INSERT INTO nacionalidades (nombre, activo) VALUES
('Chilena', 1),
('Argentina', 1),
('Boliviana', 1),
('Brasileña', 1),
('Colombiana', 1),
('Cubana', 1),
('Ecuatoriana', 1),
('Española', 1),
('Estadounidense', 1),
('Haitiana', 1),
('Mexicana', 1),
('Paraguaya', 1),
('Peruana', 1),
('Uruguaya', 1),
('Venezolana', 1),
('Otra', 1)
ON DUPLICATE KEY UPDATE activo = VALUES(activo);

INSERT INTO estados_practica (nombre, activo) VALUES
('PENDIENTE_INICIO', 1),
('EN_PROGRESO', 1),
('COMPLETADA', 1),
('PAUSADA', 1),
('INTERRUMPIDA', 1),
('ANULADA', 1)
ON DUPLICATE KEY UPDATE activo = VALUES(activo);

INSERT INTO areas_practica (nombre, descripcion, activo) VALUES
('Creación de Productos', NULL, 1),
('Automatización IA', NULL, 1),
('Marketing', NULL, 1),
('Diseño Gráfico', NULL, 1),
('Administración', NULL, 1),
('Logística', NULL, 1),
('Desarrollo Web', NULL, 1),
('Otra', NULL, 1)
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion), activo = VALUES(activo);

INSERT INTO tipos_marca (codigo, nombre, orden, activo) VALUES
('ENTRADA', 'Entrada', 1, 1),
('ENTRADA_COLACION', 'Entrada a colación', 2, 1),
('SALIDA_COLACION', 'Salida de colación', 3, 1),
('SALIDA', 'Salida', 4, 1),
-- Agregué los tipos de permiso también al catálogo para instalaciones nuevas completas.
('SALIDA_PERMISO', 'Salida por permiso', 5, 1),
('REGRESO_PERMISO', 'Regreso de permiso', 6, 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), orden = VALUES(orden), activo = VALUES(activo);

INSERT INTO roles_sistema (nombre, descripcion, activo) VALUES
('ADMIN', 'Administrador con acceso completo al sistema.', 1),
('SUPERVISOR', 'Supervisor con permisos operativos limitados.', 1),
('PRACTICANTE', 'Practicante con acceso individual a asistencia y datos propios.', 1)
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion), activo = VALUES(activo);

