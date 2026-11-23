-- Agrega el catalogo de nacionalidades para que ADMIN pueda administrarlo
-- desde admin/catalogos.php y para reducir variantes de tipeo (Chilena vs
-- chilena vs CHI) en el pre-registro y la carga masiva de practicantes.
-- Es seguro volver a ejecutar este archivo: no elimina datos.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS nacionalidades (
    id_nacionalidad INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_nacionalidades_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO nacionalidades (nombre, activo) VALUES
('Chilena', 1),
('Argentina', 1),
('Boliviana', 1),
('Brasileña', 1),
('Colombiana', 1),
('Cubana', 1),
('Ecuatoriana', 1),
('Española', 1),
('Estadounidense', 1),
('Haitiana', 1),
('Mexicana', 1),
('Paraguaya', 1),
('Peruana', 1),
('Uruguaya', 1),
('Venezolana', 1),
('Otra', 1)
ON DUPLICATE KEY UPDATE activo = VALUES(activo);
