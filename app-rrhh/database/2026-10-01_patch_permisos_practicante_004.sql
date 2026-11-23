-- ======================================================================
-- Patch: Creación de tabla para Gestión de Permisos y Recuperación de Horas
-- ======================================================================

CREATE TABLE IF NOT EXISTS `permisos_practicante` (
    `id_permiso` INT AUTO_INCREMENT PRIMARY KEY,
    `id_practica` INT NOT NULL,
    `fecha_ausencia` DATE NOT NULL,
    `tipo_ausencia` ENUM('COMPLETA', 'PARCIAL') NOT NULL DEFAULT 'COMPLETA',
    `hora_inicio_ausencia` TIME NULL,
    `hora_fin_ausencia` TIME NULL,
    `horas_ausencia` DECIMAL(4,2) NOT NULL DEFAULT 0.00,
    `horas_trabajo_dia` DECIMAL(4,2) NOT NULL DEFAULT 0.00,
    `nuevo_horario_inicio` TIME NULL,
    `nuevo_horario_fin` TIME NULL,
    `motivo` VARCHAR(500) NOT NULL,
    `fecha_recuperacion` DATE NOT NULL,
    `hora_inicio_recuperacion` TIME NOT NULL,
    `hora_fin_recuperacion` TIME NOT NULL,
    `horas_a_recuperar` DECIMAL(4,2) NOT NULL DEFAULT 0.00,
    `estado` ENUM('PENDIENTE', 'APROBADO', 'RECHAZADO') NOT NULL DEFAULT 'PENDIENTE',
    `respuesta_supervisor` VARCHAR(500) NULL,
    `revisado_por` INT NULL,
    `fecha_revision` DATETIME NULL,
    `id_excepcion_creada` INT NULL,
    `id_excepcion_ausencia` INT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_permisos_practica` (`id_practica`),
    KEY `idx_permisos_estado` (`estado`),
    KEY `idx_permisos_fecha_ausencia` (`fecha_ausencia`),
    KEY `idx_permisos_fecha_recuperacion` (`fecha_recuperacion`),
    CONSTRAINT `fk_permisos_practica` FOREIGN KEY (`id_practica`) REFERENCES `practicas` (`id_practica`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_permisos_revisado_por` FOREIGN KEY (`revisado_por`) REFERENCES `usuarios_sistema` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
