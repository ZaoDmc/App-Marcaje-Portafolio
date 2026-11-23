-- Agregué una rúbrica vigente para nuevas evaluaciones; las anteriores conservan su versión.
-- Requiere 009 y 010. Ejecutar sobre la base; no elimina notas ni modifica criterios anteriores.
CREATE TABLE IF NOT EXISTS configuracion_notas (
    id TINYINT PRIMARY KEY,
    id_rubrica INT NOT NULL,
    revision INT NOT NULL DEFAULT 1,
    actualizado_por INT NULL,
    motivo VARCHAR(500) NULL,
    actualizado_en DATETIME NULL,
    CONSTRAINT fk_config_notas_rubrica FOREIGN KEY (id_rubrica) REFERENCES rubricas_notas(id_rubrica) ON DELETE RESTRICT,
    CONSTRAINT fk_config_notas_actor FOREIGN KEY (actualizado_por) REFERENCES usuarios_sistema(id_usuario) ON DELETE RESTRICT,
    CONSTRAINT chk_config_notas_id CHECK (id=1),
    CONSTRAINT chk_config_notas_revision CHECK (revision>0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO configuracion_notas (id,id_rubrica)
SELECT 1,id_rubrica FROM rubricas_notas WHERE codigo='provisional-v1'
AND NOT EXISTS (SELECT 1 FROM configuracion_notas WHERE id=1);

-- Amplié el límite técnico a 50 filas; el servicio exige exactamente las posiciones de cada versión.
-- Sintaxis para MariaDB 10.4 local y 11.8 en Hostinger. Puede repetirse sin cambiar filas.
ALTER TABLE notas_semanales_detalle DROP CONSTRAINT IF EXISTS chk_nota_posicion;
ALTER TABLE notas_semanales_detalle ADD CONSTRAINT chk_nota_posicion CHECK (posicion BETWEEN 1 AND 50);
