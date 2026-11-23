-- Agregué política por expediente: 1 conserva plazos y canjes históricos;
-- 2 usa cinco jornadas desde aprobación y canje autónomo. El código asigna 2 a nuevas solicitudes.
-- No renové ni convertí saldos existentes. Ejecutar con respaldo antes de publicar el código.
ALTER TABLE tiempos_excepcionales ADD COLUMN IF NOT EXISTS politica_canje TINYINT NOT NULL DEFAULT 1;
