-- Agregué solicitudes diarias independientes del horario semanal y de la recuperación de horas.
CREATE TABLE IF NOT EXISTS solicitudes_colacion (
 id_solicitud INT AUTO_INCREMENT PRIMARY KEY,
 id_practica INT NOT NULL,
 fecha DATE NOT NULL,
 inicio_anterior TIME NOT NULL,
 inicio_solicitado TIME NOT NULL,
 firma_horario CHAR(64) NOT NULL,
 motivo VARCHAR(500) NOT NULL,
 estado VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE',
 solicitado_por INT NOT NULL,
 solicitado_utc DATETIME NOT NULL,
 revisado_por INT NULL,
 revisado_utc DATETIME NULL,
 motivo_revision VARCHAR(500) NULL,
 KEY idx_colacion_dia (id_practica,fecha,estado),
 CONSTRAINT fk_colacion_practica FOREIGN KEY (id_practica) REFERENCES practicas(id_practica) ON DELETE RESTRICT,
 CONSTRAINT fk_colacion_solicitante FOREIGN KEY (solicitado_por) REFERENCES usuarios_sistema(id_usuario) ON DELETE RESTRICT,
 CONSTRAINT fk_colacion_revisor FOREIGN KEY (revisado_por) REFERENCES usuarios_sistema(id_usuario) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
