-- Agrega soporte de archivos opcionales para practicantes.
-- Compatible con bases existentes: no elimina datos ni modifica tablas previas.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS documentos_practicante (
    id_documento INT AUTO_INCREMENT PRIMARY KEY,
    id_practicante INT NOT NULL,
    id_practica INT NULL,
    tipo_documento VARCHAR(40) NOT NULL,
    nombre_original VARCHAR(255) NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_relativa VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    tamano_bytes INT NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_documentos_practicante (id_practicante),
    KEY idx_documentos_practica (id_practica),
    KEY idx_documentos_tipo_activo (tipo_documento, activo),
    KEY idx_documentos_activo (activo),
    CONSTRAINT fk_documentos_practicantes
        FOREIGN KEY (id_practicante) REFERENCES practicantes (id_practicante)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_documentos_practicas
        FOREIGN KEY (id_practica) REFERENCES practicas (id_practica)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
