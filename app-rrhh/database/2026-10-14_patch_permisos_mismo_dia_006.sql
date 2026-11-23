-- Agregué observación de solicitudes sin cambiar los estados históricos del permiso.
ALTER TABLE permisos_practicante
    ADD COLUMN IF NOT EXISTS requiere_revision TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS observacion_solicitud TEXT NULL;

-- Agregué salida y regreso por permiso; la colación conserva sus marcas independientes.
INSERT INTO tipos_marca (codigo, nombre, orden, activo) VALUES
('SALIDA_PERMISO', 'Salida por permiso', 5, 1),
('REGRESO_PERMISO', 'Regreso de permiso', 6, 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);
