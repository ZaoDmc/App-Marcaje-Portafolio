-- Conservé los tramos existentes como política 1, sin alterar fechas, estados ni créditos.
-- La aplicación asigna política 2 y fecha UTC a los tramos nuevos.
ALTER TABLE recuperaciones_permiso
 ADD COLUMN IF NOT EXISTS politica_recuperacion TINYINT NOT NULL DEFAULT 1,
 ADD COLUMN IF NOT EXISTS solicitado_utc DATETIME NULL;
