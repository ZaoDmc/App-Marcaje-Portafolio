-- Agregué un corte único en UTC: repetir el parche no desplaza la política ni convierte extras nuevas en históricas.
CREATE TABLE IF NOT EXISTS politica_tiempo_excepcional (
 id INT PRIMARY KEY,
 vigente_desde_utc DATETIME NOT NULL,
 ultima_jornada_historica INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO politica_tiempo_excepcional (id,vigente_desde_utc,ultima_jornada_historica)
 SELECT 1,UTC_TIMESTAMP(),COALESCE(MAX(id_jornada),0) FROM jornadas_asistencia;

-- Conservé los importes aprobados al activar la política; no se crea saldo canjeable con extras antiguas.
CREATE TABLE IF NOT EXISTS extras_historicas (
 id_jornada INT PRIMARY KEY,
 horas_aprobadas DECIMAL(6,2) NOT NULL,
 CONSTRAINT fk_extra_historica_jornada FOREIGN KEY (id_jornada) REFERENCES jornadas_asistencia(id_jornada) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO extras_historicas (id_jornada,horas_aprobadas)
 SELECT j.id_jornada,j.horas_extra_aprobadas FROM jornadas_asistencia j
 JOIN politica_tiempo_excepcional p ON p.id=1 AND j.id_jornada<=p.ultima_jornada_historica
 WHERE j.horas_extra_aprobadas>0;

-- Separé el crédito excepcional de horas_calculadas y de las recuperaciones de permisos.
CREATE TABLE IF NOT EXISTS tiempos_excepcionales (
 id_tiempo INT AUTO_INCREMENT PRIMARY KEY,
 id_jornada INT NOT NULL,
 hora_inicio TIME NOT NULL,
 hora_fin TIME NOT NULL,
 minutos_solicitados INT NOT NULL,
 minutos_aprobados INT NOT NULL DEFAULT 0,
 motivo_solicitud VARCHAR(500) NOT NULL,
 estado VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE',
 solicitado_utc DATETIME NOT NULL,
 vence_utc DATETIME NOT NULL,
 revisado_por INT NULL,
 motivo_revision VARCHAR(500) NULL,
 revisado_utc DATETIME NULL,
 firma_marcas CHAR(64) NULL,
 KEY idx_tiempo_jornada (id_jornada,estado),
 CONSTRAINT fk_tiempo_jornada FOREIGN KEY (id_jornada) REFERENCES jornadas_asistencia(id_jornada) ON DELETE RESTRICT,
 CONSTRAINT fk_tiempo_revisor FOREIGN KEY (revisado_por) REFERENCES usuarios_sistema(id_usuario) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregué reservas de compensación auditables; el vencimiento bloquea nuevos canjes sin borrar su historial.
CREATE TABLE IF NOT EXISTS compensaciones_tiempo (
 id_compensacion INT AUTO_INCREMENT PRIMARY KEY,
 id_tiempo INT NOT NULL,
 fecha DATE NOT NULL,
 tipo VARCHAR(10) NOT NULL,
 hora_inicio TIME NOT NULL,
 hora_fin TIME NOT NULL,
 minutos INT NOT NULL,
 minutos_aplicados INT NOT NULL DEFAULT 0,
 estado VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE',
 motivo_solicitud VARCHAR(500) NOT NULL,
 solicitado_utc DATETIME NOT NULL,
 revisado_por INT NULL,
 motivo_revision VARCHAR(500) NULL,
 revisado_utc DATETIME NULL,
 KEY idx_compensacion_fecha (fecha,estado),
 CONSTRAINT fk_compensacion_tiempo FOREIGN KEY (id_tiempo) REFERENCES tiempos_excepcionales(id_tiempo) ON DELETE RESTRICT,
 CONSTRAINT fk_compensacion_revisor FOREIGN KEY (revisado_por) REFERENCES usuarios_sistema(id_usuario) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
