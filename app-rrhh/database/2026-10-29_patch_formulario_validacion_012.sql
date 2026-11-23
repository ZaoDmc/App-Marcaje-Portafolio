CREATE TABLE IF NOT EXISTS formulario_validacion_configuracion (
    id_configuracion TINYINT UNSIGNED PRIMARY KEY,
    nombre_centro VARCHAR(150) NOT NULL DEFAULT '',
    rut_centro VARCHAR(20) NOT NULL DEFAULT '',
    direccion VARCHAR(250) NOT NULL DEFAULT '',
    comuna VARCHAR(150) NOT NULL DEFAULT '',
    giro_comercial VARCHAR(250) NOT NULL DEFAULT '',
    telefono VARCHAR(30) NOT NULL DEFAULT '',
    sitio_web VARCHAR(255) NOT NULL DEFAULT '',
    jefe_nombre VARCHAR(150) NOT NULL DEFAULT '',
    jefe_cargo VARCHAR(150) NOT NULL DEFAULT '',
    jefe_email VARCHAR(190) NOT NULL DEFAULT '',
    beneficios TEXT NOT NULL,
    actualizado_por INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_formulario_validacion_config_usuario
        FOREIGN KEY (actualizado_por) REFERENCES usuarios_sistema (id_usuario)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO formulario_validacion_configuracion (id_configuracion, beneficios)
VALUES (1, '');

CREATE TABLE IF NOT EXISTS formulario_validacion_plantillas_carrera (
    id_plantilla_carrera INT AUTO_INCREMENT PRIMARY KEY,
    id_carrera INT NOT NULL,
    unidad TEXT NOT NULL,
    funciones TEXT NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    actualizado_por INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_formulario_validacion_carrera (id_carrera),
    CONSTRAINT fk_formulario_validacion_carrera
        FOREIGN KEY (id_carrera) REFERENCES carreras (id_carrera)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_formulario_validacion_plantilla_usuario
        FOREIGN KEY (actualizado_por) REFERENCES usuarios_sistema (id_usuario)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS documentos_validacion_generados (
    id_documento_validacion INT AUTO_INCREMENT PRIMARY KEY,
    institucion_codigo VARCHAR(40) NOT NULL,
    version_plantilla VARCHAR(60) NOT NULL,
    id_carrera INT NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_relativa VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    tamano_bytes INT UNSIGNED NOT NULL,
    creado_por INT NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_documentos_validacion_carrera (id_carrera),
    KEY idx_documentos_validacion_actor_fecha (creado_por, created_at),
    KEY idx_documentos_validacion_activo_fecha (activo, created_at),
    CONSTRAINT fk_documentos_validacion_carrera
        FOREIGN KEY (id_carrera) REFERENCES carreras (id_carrera)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_documentos_validacion_usuario
        FOREIGN KEY (creado_por) REFERENCES usuarios_sistema (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
