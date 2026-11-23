-- Agregué historial de líderes por área; no asigna líderes ni altera datos existentes.
-- Ejecutar sobre la base del proyecto, no sobre una tabla concreta. Puede repetirse.
CREATE TABLE IF NOT EXISTS liderazgos_area (
    id_liderazgo INT AUTO_INCREMENT PRIMARY KEY,
    id_area INT NOT NULL,
    id_practica INT NOT NULL,
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NULL,
    asignado_por INT NOT NULL,
    finalizado_por INT NULL,
    motivo_designacion VARCHAR(500) NOT NULL,
    motivo_fin VARCHAR(500) NULL,
    -- Impedí dos líderes vigentes por área incluso ante inserciones simultáneas.
    id_area_vigente INT GENERATED ALWAYS AS (CASE WHEN fecha_fin IS NULL THEN id_area ELSE NULL END) STORED,
    UNIQUE KEY uq_liderazgo_area_vigente (id_area_vigente),
    KEY idx_liderazgo_practica (id_practica, fecha_fin),
    KEY idx_liderazgo_historial (id_area, fecha_inicio),
    CONSTRAINT fk_liderazgo_area FOREIGN KEY (id_area) REFERENCES areas_practica(id_area) ON DELETE RESTRICT,
    CONSTRAINT fk_liderazgo_practica FOREIGN KEY (id_practica) REFERENCES practicas(id_practica) ON DELETE RESTRICT,
    CONSTRAINT fk_liderazgo_asignado FOREIGN KEY (asignado_por) REFERENCES usuarios_sistema(id_usuario) ON DELETE RESTRICT,
    CONSTRAINT fk_liderazgo_finalizado FOREIGN KEY (finalizado_por) REFERENCES usuarios_sistema(id_usuario) ON DELETE RESTRICT,
    CONSTRAINT chk_liderazgo_fechas CHECK (fecha_fin IS NULL OR fecha_fin >= fecha_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
