-- Agregué el esquema completo para instalaciones nuevas; las bases existentes se actualizan con parches, no reimportando este archivo.
-- Compatible con MySQL/MariaDB, XAMPP, Hostinger, phpMyAdmin y PDO.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS regiones (
    id_region INT AUTO_INCREMENT PRIMARY KEY,
    nombre_region VARCHAR(120) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_regiones_nombre_region (nombre_region),
    KEY idx_regiones_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comunas (
    id_comuna INT AUTO_INCREMENT PRIMARY KEY,
    id_region INT NOT NULL,
    nombre_comuna VARCHAR(120) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_comunas_region_nombre (id_region, nombre_comuna),
    KEY idx_comunas_region (id_region),
    KEY idx_comunas_activo (activo),
    CONSTRAINT fk_comunas_regiones
        FOREIGN KEY (id_region) REFERENCES regiones (id_region)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS instituciones (
    id_institucion INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_instituciones_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sedes (
    id_sede INT AUTO_INCREMENT PRIMARY KEY,
    id_institucion INT NOT NULL,
    nombre_sede VARCHAR(150) NOT NULL,
    codigo_sede VARCHAR(80) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_sedes_institucion_codigo (id_institucion, codigo_sede),
    KEY idx_sedes_institucion (id_institucion),
    KEY idx_sedes_activo (activo),
    CONSTRAINT fk_sedes_instituciones
        FOREIGN KEY (id_institucion) REFERENCES instituciones (id_institucion)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS carreras (
    id_carrera INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_carreras_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS nacionalidades (
    id_nacionalidad INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_nacionalidades_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tipos_practica (
    id_tipo_practica INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    horas_base INT NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tipos_practica_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS estados_practica (
    id_estado_practica INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_estados_practica_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS areas_practica (
    id_area INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_areas_practica_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tipos_marca (
    id_tipo_marca INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(30) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    orden TINYINT NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tipos_marca_codigo (codigo),
    UNIQUE KEY uq_tipos_marca_orden (orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS roles_sistema (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    descripcion TEXT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_roles_sistema_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS practicantes (
    id_practicante INT AUTO_INCREMENT PRIMARY KEY,
    rut_numero VARCHAR(20) NULL,
    rut_dv VARCHAR(2) NULL,
    nombre_1 VARCHAR(100) NOT NULL,
    nombre_2 VARCHAR(100) NULL,
    apellido_paterno VARCHAR(100) NOT NULL,
    apellido_materno VARCHAR(100) NULL,
    email VARCHAR(150) NOT NULL,
    telefono VARCHAR(20) NULL,
    fecha_nacimiento DATE NULL,
    sexo CHAR(1) NULL,
    nacionalidad VARCHAR(50) NULL,
    direccion VARCHAR(200) NULL,
    numero_direccion VARCHAR(20) NULL,
    departamento VARCHAR(50) NULL,
    id_comuna INT NULL,
    pais VARCHAR(100) NULL,
    id_region INT NULL,
    id_institucion INT NULL,
    id_sede INT NULL,
    institucion_otra VARCHAR(150) NULL,
    sede_otra VARCHAR(150) NULL,
    id_carrera INT NULL,
    semestre VARCHAR(20) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_practicantes_rut (rut_numero, rut_dv),
    UNIQUE KEY uq_practicantes_email (email),
    KEY idx_practicantes_region (id_region),
    KEY idx_practicantes_comuna (id_comuna),
    KEY idx_practicantes_institucion (id_institucion),
    KEY idx_practicantes_sede (id_sede),
    KEY idx_practicantes_carrera (id_carrera),
    KEY idx_practicantes_nombre (apellido_paterno, nombre_1),
    KEY idx_practicantes_activo (activo),
    CONSTRAINT fk_practicantes_regiones
        FOREIGN KEY (id_region) REFERENCES regiones (id_region)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_practicantes_comunas
        FOREIGN KEY (id_comuna) REFERENCES comunas (id_comuna)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_practicantes_instituciones
        FOREIGN KEY (id_institucion) REFERENCES instituciones (id_institucion)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_practicantes_sedes
        FOREIGN KEY (id_sede) REFERENCES sedes (id_sede)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_practicantes_carreras
        FOREIGN KEY (id_carrera) REFERENCES carreras (id_carrera)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS practicas (
    id_practica INT AUTO_INCREMENT PRIMARY KEY,
    id_practicante INT NOT NULL,
    id_tipo_practica INT NULL,
    id_estado_practica INT NOT NULL,
    horas_requeridas INT NULL,
    horas_previas DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    fecha_inicio_practica DATE NULL,
    fecha_termino_practica DATE NULL,
    motivo_interrupcion TEXT NULL,
    observacion_estado TEXT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_practicas_practicante (id_practicante),
    KEY idx_practicas_tipo (id_tipo_practica),
    KEY idx_practicas_estado (id_estado_practica),
    KEY idx_practicas_inicio (fecha_inicio_practica),
    KEY idx_practicas_activo (activo),
    CONSTRAINT fk_practicas_practicantes
        FOREIGN KEY (id_practicante) REFERENCES practicantes (id_practicante)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_practicas_tipos
        FOREIGN KEY (id_tipo_practica) REFERENCES tipos_practica (id_tipo_practica)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_practicas_estados
        FOREIGN KEY (id_estado_practica) REFERENCES estados_practica (id_estado_practica)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS horarios_practicante (
    id_horario INT AUTO_INCREMENT PRIMARY KEY,
    id_practica INT NOT NULL,
    nombre_horario VARCHAR(100) NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_horarios_practica (id_practica),
    KEY idx_horarios_inicio (fecha_inicio),
    KEY idx_horarios_fin (fecha_fin),
    KEY idx_horarios_activo (activo),
    CONSTRAINT fk_horarios_practicas
        FOREIGN KEY (id_practica) REFERENCES practicas (id_practica)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS detalle_horario_practicante (
    id_detalle_horario INT AUTO_INCREMENT PRIMARY KEY,
    id_horario INT NOT NULL,
    dia_semana TINYINT NOT NULL,
    entrada_esperada TIME NOT NULL,
    salida_esperada TIME NOT NULL,
    colacion_inicio TIME NULL,
    colacion_fin TIME NULL,
    horas_planificadas DECIMAL(5,2) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_detalle_horario_dia (id_horario, dia_semana),
    KEY idx_detalle_horario (id_horario),
    KEY idx_detalle_dia (dia_semana),
    CONSTRAINT fk_detalle_horarios
        FOREIGN KEY (id_horario) REFERENCES horarios_practicante (id_horario)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS excepciones_horario_practicante (
    id_excepcion INT AUTO_INCREMENT PRIMARY KEY,
    id_horario INT NOT NULL,
    fecha DATE NOT NULL,
    tipo_excepcion VARCHAR(50) NOT NULL,
    entrada_esperada TIME NULL,
    salida_esperada TIME NULL,
    colacion_inicio TIME NULL,
    colacion_fin TIME NULL,
    horas_planificadas DECIMAL(5,2) NULL,
    observacion TEXT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_excepciones_horario_fecha_tipo (id_horario, fecha, tipo_excepcion),
    KEY idx_excepciones_horario (id_horario),
    KEY idx_excepciones_fecha (fecha),
    CONSTRAINT fk_excepciones_horarios
        FOREIGN KEY (id_horario) REFERENCES horarios_practicante (id_horario)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS usuarios_sistema (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    id_practicante INT NULL,
    email VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    estado_cuenta VARCHAR(30) NOT NULL,
    debe_cambiar_password TINYINT(1) NOT NULL DEFAULT 0,
    email_verificado TINYINT(1) NOT NULL DEFAULT 0,
    intentos_fallidos INT NOT NULL DEFAULT 0,
    ultimo_intento_login DATETIME NULL,
    bloqueado_hasta DATETIME NULL,
    ultimo_login DATETIME NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_usuarios_email (email),
    UNIQUE KEY uq_usuarios_practicante (id_practicante),
    KEY idx_usuarios_estado (estado_cuenta),
    KEY idx_usuarios_activo (activo),
    KEY idx_usuarios_bloqueado_hasta (bloqueado_hasta),
    CONSTRAINT fk_usuarios_practicantes
        FOREIGN KEY (id_practicante) REFERENCES practicantes (id_practicante)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS usuarios_roles (
    id_usuario_rol INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_rol INT NOT NULL,
    rol_activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_usuarios_roles_usuario_rol (id_usuario, id_rol),
    KEY idx_usuarios_roles_usuario (id_usuario),
    KEY idx_usuarios_roles_rol (id_rol),
    CONSTRAINT fk_usuarios_roles_usuarios
        FOREIGN KEY (id_usuario) REFERENCES usuarios_sistema (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_usuarios_roles_roles
        FOREIGN KEY (id_rol) REFERENCES roles_sistema (id_rol)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sesiones_usuario (
    id_sesion INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_rol_activo INT NOT NULL,
    session_id_hash VARCHAR(255) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) NULL,
    fecha_inicio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_ultimo_acceso DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_cierre DATETIME NULL,
    cerrada_por_logout TINYINT(1) NOT NULL DEFAULT 0,
    activa TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_sesiones_usuario (id_usuario),
    KEY idx_sesiones_rol_activo (id_rol_activo),
    KEY idx_sesiones_activa (activa),
    KEY idx_sesiones_inicio (fecha_inicio),
    KEY idx_sesiones_cierre (fecha_cierre),
    KEY idx_sesiones_hash (session_id_hash),
    CONSTRAINT fk_sesiones_usuarios
        FOREIGN KEY (id_usuario) REFERENCES usuarios_sistema (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_sesiones_roles
        FOREIGN KEY (id_rol_activo) REFERENCES roles_sistema (id_rol)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tokens_usuario (
    id_token INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    tipo_token VARCHAR(50) NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    fecha_expiracion DATETIME NOT NULL,
    usado TINYINT(1) NOT NULL DEFAULT 0,
    fecha_uso DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_tokens_usuario (id_usuario),
    KEY idx_tokens_tipo (tipo_token),
    KEY idx_tokens_expiracion (fecha_expiracion),
    KEY idx_tokens_usado (usado),
    KEY idx_tokens_hash (token_hash),
    CONSTRAINT fk_tokens_usuarios
        FOREIGN KEY (id_usuario) REFERENCES usuarios_sistema (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS jornadas_asistencia (
    id_jornada INT AUTO_INCREMENT PRIMARY KEY,
    id_practica INT NOT NULL,
    fecha DATE NOT NULL,
    estado_jornada VARCHAR(30) NOT NULL,
    hora_entrada DATETIME NULL,
    hora_salida DATETIME NULL,
    horas_calculadas DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    observacion TEXT NULL,
    creada_por INT NULL,
    flag_atraso TINYINT(1) NOT NULL DEFAULT 0,
    flag_salida_anticipada TINYINT(1) NOT NULL DEFAULT 0,
    flag_ausencia_esperada TINYINT(1) NOT NULL DEFAULT 0,
    flag_asistencia_extraordinaria TINYINT(1) NOT NULL DEFAULT 0,
    flag_horas_extra TINYINT(1) NOT NULL DEFAULT 0,
    -- Agregué la política de recuperación; el servicio activa la regla al crear nuevas jornadas.
    politica_recuperacion_v2 TINYINT(1) NOT NULL DEFAULT 0,
    horas_extra_aprobadas DECIMAL(5,2) NULL,
    aprobada_por INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_jornadas_practica_fecha (id_practica, fecha),
    KEY idx_jornadas_practica (id_practica),
    KEY idx_jornadas_fecha (fecha),
    KEY idx_jornadas_estado (estado_jornada),
    KEY idx_jornadas_creada_por (creada_por),
    KEY idx_jornadas_aprobada_por (aprobada_por),
    CONSTRAINT fk_jornadas_practicas
        FOREIGN KEY (id_practica) REFERENCES practicas (id_practica)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_jornadas_creada_por
        FOREIGN KEY (creada_por) REFERENCES usuarios_sistema (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_jornadas_aprobada_por
        FOREIGN KEY (aprobada_por) REFERENCES usuarios_sistema (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS marcas_asistencia (
    id_marca INT AUTO_INCREMENT PRIMARY KEY,
    id_jornada INT NOT NULL,
    id_tipo_marca INT NOT NULL,
    fecha_hora DATETIME NOT NULL,
    observacion VARCHAR(255) NULL,
    creada_por INT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    anulada TINYINT(1) NOT NULL DEFAULT 0,
    motivo_anulacion TEXT NULL,
    anulada_por INT NULL,
    fecha_anulacion DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_marcas_jornada_tipo_estado (id_jornada, id_tipo_marca, activo, anulada),
    KEY idx_marcas_jornada (id_jornada),
    KEY idx_marcas_tipo (id_tipo_marca),
    KEY idx_marcas_fecha_hora (fecha_hora),
    KEY idx_marcas_creada_por (creada_por),
    KEY idx_marcas_activo (activo),
    KEY idx_marcas_anulada (anulada),
    KEY idx_marcas_anulada_por (anulada_por),
    KEY idx_marcas_fecha_anulacion (fecha_anulacion),
    CONSTRAINT fk_marcas_jornadas
        FOREIGN KEY (id_jornada) REFERENCES jornadas_asistencia (id_jornada)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_marcas_tipos
        FOREIGN KEY (id_tipo_marca) REFERENCES tipos_marca (id_tipo_marca)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_marcas_creada_por
        FOREIGN KEY (creada_por) REFERENCES usuarios_sistema (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_marcas_anulada_por
        FOREIGN KEY (anulada_por) REFERENCES usuarios_sistema (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS historial_areas_practicante (
    id_historial_area INT AUTO_INCREMENT PRIMARY KEY,
    id_practica INT NOT NULL,
    id_area INT NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NULL,
    observacion TEXT NULL,
    asignado_por INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_historial_area_practica (id_practica),
    KEY idx_historial_area_area (id_area),
    KEY idx_historial_area_inicio (fecha_inicio),
    KEY idx_historial_area_fin (fecha_fin),
    KEY idx_historial_area_asignado_por (asignado_por),
    CONSTRAINT fk_historial_areas_practicas
        FOREIGN KEY (id_practica) REFERENCES practicas (id_practica)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_historial_areas_areas
        FOREIGN KEY (id_area) REFERENCES areas_practica (id_area)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_historial_areas_usuarios
        FOREIGN KEY (asignado_por) REFERENCES usuarios_sistema (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS bloques_area_jornada (
    id_bloque_area INT AUTO_INCREMENT PRIMARY KEY,
    id_jornada INT NOT NULL,
    id_area INT NOT NULL,
    hora_inicio DATETIME NOT NULL,
    hora_fin DATETIME NULL,
    horas_asignadas DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    asignado_por INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_bloques_jornada (id_jornada),
    KEY idx_bloques_area (id_area),
    KEY idx_bloques_asignado_por (asignado_por),
    KEY idx_bloques_hora_inicio (hora_inicio),
    KEY idx_bloques_hora_fin (hora_fin),
    CONSTRAINT fk_bloques_jornadas
        FOREIGN KEY (id_jornada) REFERENCES jornadas_asistencia (id_jornada)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_bloques_areas
        FOREIGN KEY (id_area) REFERENCES areas_practica (id_area)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_bloques_usuarios
        FOREIGN KEY (asignado_por) REFERENCES usuarios_sistema (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS evaluaciones_practicante (
    id_evaluacion INT AUTO_INCREMENT PRIMARY KEY,
    id_practicante INT NOT NULL,
    id_practica INT NOT NULL,
    id_evaluador INT NOT NULL,
    nota_desempeno DECIMAL(4,2) NULL,
    potencial VARCHAR(50) NULL,
    nivel_desarrollo_habilidades VARCHAR(50) NULL,
    observacion TEXT NULL,
    fecha_evaluacion DATE NOT NULL,
    estado_evaluacion VARCHAR(30) NOT NULL,
    aprobado_por INT NULL,
    fecha_aprobacion DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_evaluaciones_practicante (id_practicante),
    KEY idx_evaluaciones_practica (id_practica),
    KEY idx_evaluaciones_evaluador (id_evaluador),
    KEY idx_evaluaciones_aprobado_por (aprobado_por),
    KEY idx_evaluaciones_fecha (fecha_evaluacion),
    KEY idx_evaluaciones_estado (estado_evaluacion),
    CONSTRAINT fk_evaluaciones_practicantes
        FOREIGN KEY (id_practicante) REFERENCES practicantes (id_practicante)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_evaluaciones_practicas
        FOREIGN KEY (id_practica) REFERENCES practicas (id_practica)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_evaluaciones_evaluador
        FOREIGN KEY (id_evaluador) REFERENCES usuarios_sistema (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_evaluaciones_aprobador
        FOREIGN KEY (aprobado_por) REFERENCES usuarios_sistema (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auditoria_cambios (
    id_auditoria INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario_actor INT NULL,
    id_rol_contexto INT NULL,
    id_sesion INT NULL,
    evento VARCHAR(100) NOT NULL,
    tabla_afectada VARCHAR(100) NULL,
    id_registro_afectado INT NULL,
    valores_anteriores TEXT NULL,
    valores_nuevos TEXT NULL,
    ip VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_auditoria_usuario_actor (id_usuario_actor),
    KEY idx_auditoria_rol_contexto (id_rol_contexto),
    KEY idx_auditoria_sesion (id_sesion),
    KEY idx_auditoria_evento (evento),
    KEY idx_auditoria_tabla (tabla_afectada),
    KEY idx_auditoria_registro (id_registro_afectado),
    KEY idx_auditoria_created_at (created_at),
    CONSTRAINT fk_auditoria_usuarios
        FOREIGN KEY (id_usuario_actor) REFERENCES usuarios_sistema (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_auditoria_roles
        FOREIGN KEY (id_rol_contexto) REFERENCES roles_sistema (id_rol)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_auditoria_sesiones
        FOREIGN KEY (id_sesion) REFERENCES sesiones_usuario (id_sesion)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- Agregué permisos, recuperaciones y calendario que antes requerían parches separados.
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
    -- Agregué observación para permisos del mismo día e interrupciones intermedias.
    `requiere_revision` TINYINT(1) NOT NULL DEFAULT 0,
    `observacion_solicitud` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_permisos_practica` (`id_practica`),
    KEY `idx_permisos_estado` (`estado`),
    KEY `idx_permisos_fecha_ausencia` (`fecha_ausencia`),
    KEY `idx_permisos_fecha_recuperacion` (`fecha_recuperacion`),
    CONSTRAINT `fk_permisos_practica` FOREIGN KEY (`id_practica`) REFERENCES `practicas` (`id_practica`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_permisos_revisado_por` FOREIGN KEY (`revisado_por`) REFERENCES `usuarios_sistema` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregué un calendario común administrado desde Catálogos, sin cargar feriados supuestos.
CREATE TABLE IF NOT EXISTS feriados (
 id_feriado INT AUTO_INCREMENT PRIMARY KEY,
 fecha DATE NOT NULL UNIQUE,
 nombre VARCHAR(150) NOT NULL,
 activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregué tramos independientes; las recuperaciones antiguas conservan su representación anterior.
CREATE TABLE IF NOT EXISTS recuperaciones_permiso (
 -- Agregué política por tramo y evidencia UTC del envío para el corte del viernes.
 politica_recuperacion TINYINT NOT NULL DEFAULT 1,
 solicitado_utc DATETIME NULL,
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


-- Agregué crédito y compensación con corte inicial propio de esta base.
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
 -- Conservé política histórica por defecto; la aplicación asigna 2 a solicitudes nuevas.
 politica_canje TINYINT NOT NULL DEFAULT 1,
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

-- Agregué Acompañamiento para no depender de su creación al abrir la pantalla.
CREATE TABLE IF NOT EXISTS acompanamiento (
    id_acompanamiento   INT          NOT NULL AUTO_INCREMENT,
    id_practica         INT          NOT NULL,
    evaluacion_enviada  TINYINT(1)   NOT NULL DEFAULT 0,
    puntaje_evaluacion  DECIMAL(3,1) NULL    DEFAULT NULL,
    resena_recibida     TINYINT(1)   NOT NULL DEFAULT 0,
    fecha_generacion    DATETIME      NULL    DEFAULT NULL,
    resena_publicada    TINYINT(1)    NOT NULL DEFAULT 0,
    documento_entregado TINYINT(1)    NOT NULL DEFAULT 0,
    capacitacion_realizada TINYINT(1) NOT NULL DEFAULT 0,
    certificado_entregado  TINYINT(1) NOT NULL DEFAULT 0,
    linkedin_operativo  TINYINT(1)   NOT NULL DEFAULT 0,
    created_at          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_acompanamiento),
    UNIQUE KEY uk_acompanamiento_practica (id_practica)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregué los tipos de interrupción requeridos por permisos; los otros catálogos siguen en sus seeds.
INSERT INTO tipos_marca (codigo, nombre, orden, activo) VALUES
('SALIDA_PERMISO', 'Salida por permiso', 5, 1),
('REGRESO_PERMISO', 'Regreso de permiso', 6, 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

-- Agregué evaluaciones identificadas por viernes; no convierto puntajes de Acompañamiento.
-- Requiere esquema base y parche 009. No modifica filas existentes y puede repetirse.
CREATE TABLE IF NOT EXISTS rubricas_notas (
    id_rubrica INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(60) NOT NULL UNIQUE,
    descripcion VARCHAR(250) NOT NULL,
    criterios_json TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conservé una versión provisional, sin atribuirle ponderaciones ni criterios definitivos.
INSERT INTO rubricas_notas (codigo,descripcion,criterios_json)
SELECT 'provisional-v1','Diez evaluaciones provisionales; nota final manual, sin ponderación.',
'["Evaluación 1","Evaluación 2","Evaluación 3","Evaluación 4","Evaluación 5","Evaluación 6","Evaluación 7","Evaluación 8","Evaluación 9","Evaluación 10"]'
WHERE NOT EXISTS (SELECT 1 FROM rubricas_notas WHERE codigo='provisional-v1');

CREATE TABLE IF NOT EXISTS notas_semanales (
    id_nota INT AUTO_INCREMENT PRIMARY KEY,
    id_practica INT NOT NULL,
    viernes DATE NOT NULL,
    id_area INT NOT NULL,
    id_rubrica INT NOT NULL,
    id_evaluador INT NOT NULL,
    id_liderazgo INT NULL,
    tipo_evaluado VARCHAR(12) NOT NULL,
    estado VARCHAR(12) NOT NULL DEFAULT 'BORRADOR',
    nota_final DECIMAL(4,2) NULL,
    observacion TEXT NULL,
    revision INT NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL,
    actualizado_en DATETIME NOT NULL,
    aprobado_por INT NULL,
    aprobado_en DATETIME NULL,
    UNIQUE KEY uq_nota_practica_viernes (id_practica,viernes),
    KEY idx_nota_area_viernes (id_area,viernes),
    CONSTRAINT fk_nota_practica FOREIGN KEY (id_practica) REFERENCES practicas(id_practica) ON DELETE RESTRICT,
    CONSTRAINT fk_nota_area FOREIGN KEY (id_area) REFERENCES areas_practica(id_area) ON DELETE RESTRICT,
    CONSTRAINT fk_nota_rubrica FOREIGN KEY (id_rubrica) REFERENCES rubricas_notas(id_rubrica) ON DELETE RESTRICT,
    CONSTRAINT fk_nota_evaluador FOREIGN KEY (id_evaluador) REFERENCES usuarios_sistema(id_usuario) ON DELETE RESTRICT,
    CONSTRAINT fk_nota_liderazgo FOREIGN KEY (id_liderazgo) REFERENCES liderazgos_area(id_liderazgo) ON DELETE RESTRICT,
    CONSTRAINT fk_nota_aprobador FOREIGN KEY (aprobado_por) REFERENCES usuarios_sistema(id_usuario) ON DELETE RESTRICT,
    CONSTRAINT chk_nota_viernes CHECK (DAYOFWEEK(viernes)=6),
    CONSTRAINT chk_nota_tipo CHECK (tipo_evaluado IN ('LIDER','INTEGRANTE')),
    CONSTRAINT chk_nota_estado CHECK (estado IN ('BORRADOR','APROBADA')),
    CONSTRAINT chk_nota_final CHECK (nota_final IS NULL OR nota_final BETWEEN 1 AND 7),
    CONSTRAINT chk_nota_revision CHECK (revision>0),
    CONSTRAINT chk_nota_aprobacion CHECK (
        (estado='BORRADOR' AND aprobado_por IS NULL AND aprobado_en IS NULL) OR
        (estado='APROBADA' AND aprobado_por IS NOT NULL AND aprobado_en IS NOT NULL AND nota_final IS NOT NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cada posición conserva vacío o una nota de la escala 1–7 con paso 0,5.
CREATE TABLE IF NOT EXISTS notas_semanales_detalle (
    id_nota INT NOT NULL,
    posicion TINYINT NOT NULL,
    nota DECIMAL(3,1) NULL,
    PRIMARY KEY (id_nota,posicion),
    CONSTRAINT fk_nota_detalle FOREIGN KEY (id_nota) REFERENCES notas_semanales(id_nota) ON DELETE RESTRICT,
    CONSTRAINT chk_nota_posicion CHECK (posicion BETWEEN 1 AND 10),
    CONSTRAINT chk_nota_escala CHECK (nota IS NULL OR (nota BETWEEN 1 AND 7 AND MOD(nota*10,5)=0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Guardé una instantánea por revisión; editar no elimina las versiones anteriores.
CREATE TABLE IF NOT EXISTS notas_semanales_historial (
    id_historial INT AUTO_INCREMENT PRIMARY KEY,
    id_nota INT NOT NULL,
    revision INT NOT NULL,
    id_actor INT NOT NULL,
    motivo VARCHAR(500) NOT NULL,
    contenido_json MEDIUMTEXT NOT NULL,
    registrado_en DATETIME NOT NULL,
    UNIQUE KEY uq_nota_historial_revision (id_nota,revision),
    CONSTRAINT fk_nota_historial FOREIGN KEY (id_nota) REFERENCES notas_semanales(id_nota) ON DELETE RESTRICT,
    CONSTRAINT fk_nota_historial_actor FOREIGN KEY (id_actor) REFERENCES usuarios_sistema(id_usuario) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregué una rúbrica vigente para nuevas evaluaciones; las anteriores conservan su versión.
-- Requiere 009 y 010. Ejecutar sobre la base; no elimina notas ni modifica criterios anteriores.
CREATE TABLE IF NOT EXISTS configuracion_notas (
    id TINYINT PRIMARY KEY,
    id_rubrica INT NOT NULL,
    revision INT NOT NULL DEFAULT 1,
    actualizado_por INT NULL,
    motivo VARCHAR(500) NULL,
    actualizado_en DATETIME NULL,
    CONSTRAINT fk_config_notas_rubrica FOREIGN KEY (id_rubrica) REFERENCES rubricas_notas(id_rubrica) ON DELETE RESTRICT,
    CONSTRAINT fk_config_notas_actor FOREIGN KEY (actualizado_por) REFERENCES usuarios_sistema(id_usuario) ON DELETE RESTRICT,
    CONSTRAINT chk_config_notas_id CHECK (id=1),
    CONSTRAINT chk_config_notas_revision CHECK (revision>0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO configuracion_notas (id,id_rubrica)
SELECT 1,id_rubrica FROM rubricas_notas WHERE codigo='provisional-v1'
AND NOT EXISTS (SELECT 1 FROM configuracion_notas WHERE id=1);

-- Amplié el límite técnico a 50 filas; el servicio exige exactamente las posiciones de cada versión.
-- Sintaxis para MariaDB 10.4 local y 11.8 en Hostinger. Puede repetirse sin cambiar filas.
ALTER TABLE notas_semanales_detalle DROP CONSTRAINT IF EXISTS chk_nota_posicion;
ALTER TABLE notas_semanales_detalle ADD CONSTRAINT chk_nota_posicion CHECK (posicion BETWEEN 1 AND 50);

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

-- Parche 013: Tabla de recepción y gestión de reseñas de practicantes
CREATE TABLE IF NOT EXISTS resenas_practica (
    id_resena   INT AUTO_INCREMENT PRIMARY KEY,
    id_practica INT NOT NULL,
    id_practicante INT NOT NULL,
    calificacion TINYINT NOT NULL,
    aspectos_destacados TEXT NULL,
    canal_envio VARCHAR(50) NOT NULL,
    testimonio TEXT NOT NULL,
    archivo_autorizacion VARCHAR(255) NULL,
    consentimiento_legal TINYINT(1) NOT NULL DEFAULT 1,
    estado ENUM('PENDIENTE', 'RECIBIDA', 'PUBLICADA') NOT NULL DEFAULT 'RECIBIDA',
    fecha_envio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_revision DATETIME NULL,
    id_supervisor_revisor INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uk_resena_practica UNIQUE (id_practica),
    CONSTRAINT fk_resena_practica FOREIGN KEY (id_practica) REFERENCES practicas(id_practica) ON DELETE CASCADE,
    CONSTRAINT fk_resena_practicante FOREIGN KEY (id_practicante) REFERENCES practicantes(id_practicante) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
