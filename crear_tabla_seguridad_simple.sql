-- =========================================
-- TABLA PARA PROTECCIÓN CONTRA FUERZA BRUTA
-- Versión simplificada SIN eventos (para hostings compartidos)
-- =========================================

-- Crear tabla para registrar intentos de login fallidos
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL COMMENT 'IP del cliente',
    email VARCHAR(255) NULL COMMENT 'Email usado en el intento',
    attempt_time DATETIME NOT NULL COMMENT 'Fecha y hora del intento',
    INDEX idx_ip_time (ip_address, attempt_time),
    INDEX idx_email (email),
    INDEX idx_attempt_time (attempt_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar columna ultimo_login a usuarios (si no existe)
ALTER TABLE usuarios
ADD COLUMN IF NOT EXISTS ultimo_login DATETIME NULL COMMENT 'Fecha del último login exitoso';

-- Crear índice en ultimo_login
CREATE INDEX IF NOT EXISTS idx_ultimo_login ON usuarios(ultimo_login);

-- Verificación
SELECT 'Tabla login_attempts creada correctamente' AS resultado;
SHOW TABLES LIKE 'login_attempts';
