-- ============================================================================
-- Módulo de Acompañamiento - Estructura / Semilla
-- Carpeta : acon_db_semilla
-- Archivo : acompanamiento_creacion.sql
-- ----------------------------------------------------------------------------
-- Crea la tabla `acompanamiento`, aislada del núcleo de prácticas, para
-- registrar el seguimiento de evaluación, reseña y estado de LinkedIn de cada
-- práctica. Se enlaza a `practicas` por `id_practica` (una fila por práctica).
--
-- Criterios de tipos (acorde a la base de datos actual):
--   * Estados binarios (enviado / recibido / publicado / operativo)
--     -> TINYINT(1) NOT NULL DEFAULT 0  (0 = negativo, 1 = positivo),
--        igual que la columna `activo` usada en el resto del sistema.
--   * Puntaje numérico 0..7 (admite decimales, p.ej. 6.5)
--     -> DECIMAL(3,1) NULL.
--   * Fecha de generación -> DATETIME NULL.
--   * Auditoría mínima -> created_at / updated_at (mismo patrón que otras
--     tablas del sistema).
-- ============================================================================

CREATE TABLE IF NOT EXISTS acompanamiento (
    id_acompanamiento   INT          NOT NULL AUTO_INCREMENT,
    id_practica         INT          NOT NULL,

    -- (Estado de evaluación)  : 0 = no enviado | 1 = enviado
    evaluacion_enviada  TINYINT(1)   NOT NULL DEFAULT 0,

    -- (Puntaje de evaluación) : número decimal entre 0 y 7 (ej. 6.5)
    puntaje_evaluacion  DECIMAL(3,1) NULL    DEFAULT NULL,

    -- (Respuesta reseña)      : 0 = denegada  | 1 = recibida
    resena_recibida     TINYINT(1)   NOT NULL DEFAULT 0,

    -- (Fecha en que se generó el registro / acompañamiento)
    fecha_generacion    DATETIME      NULL    DEFAULT NULL,

    -- (Estado de reseña)      : 0 = no publicada | 1 = publicada
    resena_publicada    TINYINT(1)    NOT NULL DEFAULT 0,

    -- (Estado LinkedIn)       : 0 = no operativo | 1 = operativo
    linkedin_operativo  TINYINT(1)   NOT NULL DEFAULT 0,

    created_at          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id_acompanamiento),
    UNIQUE KEY uk_acompanamiento_practica (id_practica)
    -- id_practica referencia practicas.id_practica (una fila por práctica activa).
    -- Si se desea FK estricta, agregar:
    -- CONSTRAINT fk_acompanamiento_practica
    --     FOREIGN KEY (id_practica) REFERENCES practicas (id_practica) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Mapa de valores (documentación de la semilla de estados):
--   evaluacion_enviada : 0 = no enviado  | 1 = enviado
--   resena_recibida    : 0 = denegada    | 1 = recibida
--   resena_publicada   : 0 = no publicada| 1 = publicada
--   linkedin_operativo : 0 = no operativo| 1 = operativo
--   puntaje_evaluacion : decimal 0..7
--   fecha_generacion   : fecha/hora de creación del seguimiento
-- ----------------------------------------------------------------------------
