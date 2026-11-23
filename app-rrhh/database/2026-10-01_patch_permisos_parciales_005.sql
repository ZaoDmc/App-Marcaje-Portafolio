-- ======================================================================
-- Patch: Soporte para Permisos por Horas (Parciales) y Horarios Ajustados
-- ======================================================================

ALTER TABLE `permisos_practicante`
    ADD COLUMN IF NOT EXISTS `tipo_ausencia` ENUM('COMPLETA', 'PARCIAL') NOT NULL DEFAULT 'COMPLETA' AFTER `fecha_ausencia`,
    ADD COLUMN IF NOT EXISTS `hora_inicio_ausencia` TIME NULL AFTER `tipo_ausencia`,
    ADD COLUMN IF NOT EXISTS `hora_fin_ausencia` TIME NULL AFTER `hora_inicio_ausencia`,
    ADD COLUMN IF NOT EXISTS `horas_ausencia` DECIMAL(4,2) NOT NULL DEFAULT 0.00 AFTER `hora_fin_ausencia`,
    ADD COLUMN IF NOT EXISTS `horas_trabajo_dia` DECIMAL(4,2) NOT NULL DEFAULT 0.00 AFTER `horas_ausencia`,
    ADD COLUMN IF NOT EXISTS `nuevo_horario_inicio` TIME NULL AFTER `horas_trabajo_dia`,
    ADD COLUMN IF NOT EXISTS `nuevo_horario_fin` TIME NULL AFTER `nuevo_horario_inicio`,
    ADD COLUMN IF NOT EXISTS `id_excepcion_ausencia` INT NULL AFTER `id_excepcion_creada`;
