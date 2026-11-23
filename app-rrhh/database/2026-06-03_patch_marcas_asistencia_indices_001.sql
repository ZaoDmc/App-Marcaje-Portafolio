SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

SET @schema_name = DATABASE();

SET @drop_unique_marcas = (
    SELECT CASE
        WHEN NOT EXISTS (
            SELECT 1
            FROM information_schema.tables
            WHERE table_schema = @schema_name
              AND table_name = 'marcas_asistencia'
        ) THEN 'SELECT ''marcas_asistencia no existe; primero importa database/schema.sql'' AS info'
        WHEN EXISTS (
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = @schema_name
              AND table_name = 'marcas_asistencia'
              AND index_name = 'uq_marcas_jornada_tipo'
        ) THEN 'ALTER TABLE marcas_asistencia DROP INDEX uq_marcas_jornada_tipo'
        ELSE 'SELECT ''uq_marcas_jornada_tipo no existe; no se requiere eliminarlo'' AS info'
    END
);

PREPARE stmt FROM @drop_unique_marcas;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @add_index_marcas = (
    SELECT CASE
        WHEN NOT EXISTS (
            SELECT 1
            FROM information_schema.tables
            WHERE table_schema = @schema_name
              AND table_name = 'marcas_asistencia'
        ) THEN 'SELECT ''marcas_asistencia no existe; primero importa database/schema.sql'' AS info'
        WHEN NOT EXISTS (
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = @schema_name
              AND table_name = 'marcas_asistencia'
              AND index_name = 'idx_marcas_jornada_tipo_estado'
        ) THEN 'ALTER TABLE marcas_asistencia ADD KEY idx_marcas_jornada_tipo_estado (id_jornada, id_tipo_marca, activo, anulada)'
        ELSE 'SELECT ''idx_marcas_jornada_tipo_estado ya existe; no se requiere crearlo'' AS info'
    END
);

PREPARE stmt FROM @add_index_marcas;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
