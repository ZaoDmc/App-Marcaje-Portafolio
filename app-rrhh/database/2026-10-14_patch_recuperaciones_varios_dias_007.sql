-- Agregué un calendario común administrado desde Catálogos, sin cargar feriados supuestos.
CREATE TABLE IF NOT EXISTS feriados (
 id_feriado INT AUTO_INCREMENT PRIMARY KEY,
 fecha DATE NOT NULL UNIQUE,
 nombre VARCHAR(150) NOT NULL,
 activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregué tramos independientes; las recuperaciones antiguas conservan su representación anterior.
CREATE TABLE IF NOT EXISTS recuperaciones_permiso (
 id_recuperacion INT AUTO_INCREMENT PRIMARY KEY,
 id_permiso INT NOT NULL,
 fecha DATE NOT NULL,
 tipo VARCHAR(20) NOT NULL,
 hora_inicio TIME NOT NULL,
 hora_fin TIME NOT NULL,
 minutos_planificados INT NOT NULL,
 minutos_validados INT NOT NULL DEFAULT 0,
 estado VARCHAR(20) NOT NULL DEFAULT 'PROPUESTA',
 autorizado_feriado TINYINT(1) NOT NULL DEFAULT 0,
 validado_por INT NULL,
 motivo_validacion VARCHAR(500) NULL,
 firma_marcas CHAR(64) NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 fecha_validacion DATETIME NULL,
 KEY idx_recuperacion_permiso (id_permiso),
 KEY idx_recuperacion_fecha (fecha,estado),
 CONSTRAINT fk_recuperacion_permiso FOREIGN KEY (id_permiso) REFERENCES permisos_practicante(id_permiso) ON DELETE RESTRICT,
 CONSTRAINT fk_recuperacion_revisor FOREIGN KEY (validado_por) REFERENCES usuarios_sistema(id_usuario) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Marqué la política solo en jornadas nuevas para conservar los sábados históricos.
ALTER TABLE jornadas_asistencia ADD COLUMN IF NOT EXISTS politica_recuperacion_v2 TINYINT(1) NOT NULL DEFAULT 0;
