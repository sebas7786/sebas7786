-- =========================================
-- TABLA PARA PROTECCIÓN CONTRA FUERZA BRUTA
-- =========================================

-- Crear tabla para registrar intentos de login fallidos
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL COMMENT 'IP del cliente (IPv4 o IPv6)',
    email VARCHAR(255) NULL COMMENT 'Email usado en el intento',
    attempt_time DATETIME NOT NULL COMMENT 'Fecha y hora del intento',
    INDEX idx_ip_time (ip_address, attempt_time),
    INDEX idx_email (email),
    INDEX idx_attempt_time (attempt_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Registro de intentos de login fallidos para protección contra fuerza bruta';

-- Agregar columna ultimo_login a usuarios (si no existe)
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'usuarios'
     AND COLUMN_NAME = 'ultimo_login') = 0,
    'ALTER TABLE usuarios ADD COLUMN ultimo_login DATETIME NULL COMMENT "Fecha del último login exitoso"',
    'SELECT "Columna ultimo_login ya existe" AS mensaje'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Crear índice en ultimo_login
CREATE INDEX IF NOT EXISTS idx_ultimo_login ON usuarios(ultimo_login);

-- =========================================
-- EVENTO PARA LIMPIAR INTENTOS ANTIGUOS
-- =========================================
-- Esto limpia automáticamente intentos de más de 24 horas cada hora

SET GLOBAL event_scheduler = ON;

DROP EVENT IF EXISTS limpiar_intentos_login;

CREATE EVENT IF NOT EXISTS limpiar_intentos_login
ON SCHEDULE EVERY 1 HOUR
DO
DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 24 HOUR);

-- =========================================
-- VERIFICACIÓN
-- =========================================
SELECT 'Tabla login_attempts creada correctamente' AS resultado;
DESCRIBE login_attempts;
