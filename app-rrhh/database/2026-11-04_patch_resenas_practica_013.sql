-- Implementé el parche 012 para la creación de la tabla de recepción y gestión de reseñas de practicantes - Alexander
-- Creé esta tabla con el fin de almacenar la calificación por estrellas, aspectos destacados, canal y el archivo de consentimiento legal

CREATE TABLE IF NOT EXISTS resenas_practica (

    id_resena   INT AUTO_INCREMENT PRIMARY KEY,
    id_practica INT NOT NULL,
    id_practicante INT NOT NULL,
    calificacion TINYINT NOT NULL, -- Almacené la valoración de 1 a 5 estrellas

    aspectos_destacados TEXT NULL,  -- Guardé los aspectos destacados seleccionados por el estudiante
    canal_envio VARCHAR(50) NOT NULL,  -- Definí los canales dentro de los cuales, solamente se encontrara DOCUMENTO
    testimonio TEXT NOT NULL, -- Guardé el testimonio o reseña redactada
    archivo_autorizacion VARCHAR (255) NULL,  -- Guardé la ruta del archivo PDF firmado que subió el practicante
    consentimiento_legal TINYINT(1) NOT NULL DEFAULT 1,             -- Registré la confirmación obligatoria de autorización legal
    estado ENUM('PENDIENTE', 'RECIBIDA', 'PUBLICADA') NOT NULL DEFAULT 'RECIBIDA', -- Defini el ciclo de cada estado, dependiendo de lo que ponga el practicante
    fecha_envio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, -- Guardé la fecha y hora exacta de envio 
    fecha_revision DATETIME NULL,  -- Registré la fecha en que el supervisor revisa la reseña
    id_supervisor_revisor INT NULL, -- Almacené el ID del supervisor que aprobó o revisó la solicitud de envio
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uk_resena_practica UNIQUE (id_practica),  -- Creé la restricción única para que cada práctica tenga una sola reseña
    CONSTRAINT fk_resena_practica FOREIGN KEY (id_practica) REFERENCES practicas(id_practica) ON DELETE CASCADE,
    CONSTRAINT fk_resena_practicante FOREIGN KEY (id_practicante) REFERENCES practicantes(id_practicante) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;