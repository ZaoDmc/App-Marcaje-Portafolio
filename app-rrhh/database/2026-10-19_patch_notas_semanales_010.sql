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
