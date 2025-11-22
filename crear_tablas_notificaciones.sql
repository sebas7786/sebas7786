-- ═══════════════════════════════════════════════════════════════════════
-- TABLAS PARA SISTEMA DE NOTIFICACIONES
-- LicitacionesYA - Gestión de emails automáticos
-- ═══════════════════════════════════════════════════════════════════════

-- ═══════════════════════════════════════════════════════════════════════
-- TABLA: recordatorios_pago
-- Registra todos los recordatorios de pago enviados a usuarios
-- ═══════════════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS recordatorios_pago (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL COMMENT 'ID del usuario que recibió el recordatorio',
    tipo_recordatorio ENUM('pago_proximo', 'pago_vencido', 'cuenta_desactivada', 'cuenta_reactivada') NOT NULL COMMENT 'Tipo de recordatorio enviado',
    fecha_envio DATETIME NOT NULL COMMENT 'Fecha y hora del envío',
    dias_anticipacion INT DEFAULT NULL COMMENT 'Días de anticipación (positivo) o retraso (negativo)',
    email_destinatario VARCHAR(255) NULL COMMENT 'Email al que se envió',
    resultado ENUM('exitoso', 'fallido') DEFAULT 'exitoso' COMMENT 'Resultado del envío',
    error_mensaje TEXT NULL COMMENT 'Mensaje de error si falló',

    INDEX idx_usuario (usuario_id),
    INDEX idx_fecha (fecha_envio),
    INDEX idx_tipo (tipo_recordatorio),
    INDEX idx_resultado (resultado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Registro de recordatorios de pago enviados';

-- ═══════════════════════════════════════════════════════════════════════
-- TABLA: notificaciones_enviadas
-- Registra todas las notificaciones de licitaciones enviadas
-- ═══════════════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS notificaciones_enviadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL COMMENT 'ID del usuario que recibió la notificación',
    licitacion_id INT NOT NULL COMMENT 'ID de la licitación notificada',
    tipo_notificacion ENUM('nueva_licitacion', 'categoria', 'palabra_clave', 'bienvenida') NOT NULL,
    fecha_envio DATETIME NOT NULL COMMENT 'Fecha y hora del envío',
    email_destinatario VARCHAR(255) NULL,
    motivo_envio VARCHAR(255) NULL COMMENT 'Categoría o palabra clave que activó la notificación',
    resultado ENUM('exitoso', 'fallido') DEFAULT 'exitoso',
    error_mensaje TEXT NULL,

    INDEX idx_usuario (usuario_id),
    INDEX idx_licitacion (licitacion_id),
    INDEX idx_fecha (fecha_envio),
    INDEX idx_tipo (tipo_notificacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Registro de notificaciones de licitaciones enviadas';

-- ═══════════════════════════════════════════════════════════════════════
-- AGREGAR COLUMNAS A TABLA: usuarios
-- Campos para gestión de notificaciones y preferencias
-- ═══════════════════════════════════════════════════════════════════════

-- Verificar si la columna recibir_alertas_email existe, si no, agregarla
SET @column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'usuarios'
    AND COLUMN_NAME = 'recibir_alertas_email'
);

SET @sql = IF(@column_exists = 0,
    'ALTER TABLE usuarios ADD COLUMN recibir_alertas_email TINYINT(1) DEFAULT 1 COMMENT "Si desea recibir notificaciones por email"',
    'SELECT "La columna recibir_alertas_email ya existe" AS mensaje'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar columna palabras_clave
SET @column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'usuarios'
    AND COLUMN_NAME = 'palabras_clave'
);

SET @sql = IF(@column_exists = 0,
    'ALTER TABLE usuarios ADD COLUMN palabras_clave TEXT NULL COMMENT "Palabras clave separadas por comas para alertas"',
    'SELECT "La columna palabras_clave ya existe" AS mensaje'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar columna intereses
SET @column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'usuarios'
    AND COLUMN_NAME = 'intereses'
);

SET @sql = IF(@column_exists = 0,
    'ALTER TABLE usuarios ADD COLUMN intereses TEXT NULL COMMENT "Categorías de interés separadas por comas"',
    'SELECT "La columna intereses ya existe" AS mensaje'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar columna frecuencia_notificaciones
SET @column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'usuarios'
    AND COLUMN_NAME = 'frecuencia_notificaciones'
);

SET @sql = IF(@column_exists = 0,
    'ALTER TABLE usuarios ADD COLUMN frecuencia_notificaciones ENUM("inmediata", "diaria", "semanal") DEFAULT "inmediata" COMMENT "Frecuencia de notificaciones de licitaciones"',
    'SELECT "La columna frecuencia_notificaciones ya existe" AS mensaje'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar columna ultima_notificacion
SET @column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'usuarios'
    AND COLUMN_NAME = 'ultima_notificacion'
);

SET @sql = IF(@column_exists = 0,
    'ALTER TABLE usuarios ADD COLUMN ultima_notificacion DATETIME NULL COMMENT "Fecha de la última notificación enviada"',
    'SELECT "La columna ultima_notificacion ya existe" AS mensaje'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ═══════════════════════════════════════════════════════════════════════
-- TABLA: configuracion_notificaciones
-- Configuración global del sistema de notificaciones
-- ═══════════════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS configuracion_notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) NOT NULL UNIQUE COMMENT 'Clave de configuración',
    valor TEXT NULL COMMENT 'Valor de la configuración',
    descripcion TEXT NULL COMMENT 'Descripción de qué hace esta configuración',
    fecha_modificacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Configuración global del sistema de notificaciones';

-- Insertar configuraciones por defecto
INSERT IGNORE INTO configuracion_notificaciones (clave, valor, descripcion) VALUES
('notificaciones_activas', '1', 'Si el sistema de notificaciones está activo (1) o desactivado (0)'),
('dias_recordatorio_pago', '7,3,1', 'Días de anticipación para recordatorios de pago (separados por coma)'),
('dias_desactivacion_auto', '7', 'Días de pago vencido antes de desactivar automáticamente la cuenta'),
('max_emails_por_hora', '50', 'Máximo de emails que se pueden enviar por hora'),
('email_admin_notificaciones', 'admin@licitacionesya.com', 'Email del administrador para notificaciones del sistema'),
('enviar_resumen_diario', '1', 'Enviar resumen diario de actividad al admin (1 = sí, 0 = no)');

-- ═══════════════════════════════════════════════════════════════════════
-- TABLA: plantillas_email
-- Permite personalizar las plantillas de email desde la BD
-- ═══════════════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS plantillas_email (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE COMMENT 'Nombre identificador de la plantilla',
    asunto VARCHAR(255) NOT NULL COMMENT 'Asunto del email',
    contenido_html TEXT NOT NULL COMMENT 'Contenido HTML del email',
    variables_disponibles TEXT NULL COMMENT 'Variables que se pueden usar (JSON)',
    activa TINYINT(1) DEFAULT 1 COMMENT 'Si la plantilla está activa',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_nombre (nombre),
    INDEX idx_activa (activa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Plantillas de email personalizables';

-- ═══════════════════════════════════════════════════════════════════════
-- VISTA: estadisticas_notificaciones
-- Vista para obtener estadísticas rápidas de notificaciones
-- ═══════════════════════════════════════════════════════════════════════
CREATE OR REPLACE VIEW estadisticas_notificaciones AS
SELECT
    DATE(fecha_envio) as fecha,
    tipo_notificacion,
    COUNT(*) as total_enviados,
    SUM(CASE WHEN resultado = 'exitoso' THEN 1 ELSE 0 END) as exitosos,
    SUM(CASE WHEN resultado = 'fallido' THEN 1 ELSE 0 END) as fallidos,
    ROUND(SUM(CASE WHEN resultado = 'exitoso' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as tasa_exito
FROM notificaciones_enviadas
GROUP BY DATE(fecha_envio), tipo_notificacion
ORDER BY fecha DESC, tipo_notificacion;

-- ═══════════════════════════════════════════════════════════════════════
-- VISTA: recordatorios_pendientes
-- Vista para ver qué recordatorios están pendientes de enviar
-- ═══════════════════════════════════════════════════════════════════════
CREATE OR REPLACE VIEW recordatorios_pendientes AS
SELECT
    u.id as usuario_id,
    u.nombre,
    u.correo,
    u.monto_pagar,
    u.fecha_proximo_pago,
    DATEDIFF(u.fecha_proximo_pago, CURDATE()) as dias_restantes,
    CASE
        WHEN DATEDIFF(u.fecha_proximo_pago, CURDATE()) >= 7 THEN '7 días antes'
        WHEN DATEDIFF(u.fecha_proximo_pago, CURDATE()) >= 3 THEN '3 días antes'
        WHEN DATEDIFF(u.fecha_proximo_pago, CURDATE()) >= 1 THEN '1 día antes'
        WHEN DATEDIFF(u.fecha_proximo_pago, CURDATE()) = 0 THEN 'Hoy'
        ELSE 'Vencido'
    END as estado,
    (
        SELECT MAX(fecha_envio)
        FROM recordatorios_pago rp
        WHERE rp.usuario_id = u.id
        AND DATE(rp.fecha_envio) = CURDATE()
    ) as ultimo_envio_hoy
FROM usuarios u
WHERE u.es_admin = 0
AND u.activo = 1
AND u.fecha_proximo_pago IS NOT NULL
AND (
    DATEDIFF(u.fecha_proximo_pago, CURDATE()) IN (7, 3, 1, 0)
    OR u.fecha_proximo_pago < CURDATE()
)
ORDER BY dias_restantes ASC;

-- ═══════════════════════════════════════════════════════════════════════
-- PROCEDIMIENTO: limpiar_logs_antiguos
-- Limpia logs de notificaciones mayores a X días
-- ═══════════════════════════════════════════════════════════════════════
DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS limpiar_logs_antiguos(IN dias_antiguedad INT)
BEGIN
    DECLARE filas_eliminadas_recordatorios INT DEFAULT 0;
    DECLARE filas_eliminadas_notificaciones INT DEFAULT 0;

    -- Eliminar recordatorios antiguos
    DELETE FROM recordatorios_pago
    WHERE fecha_envio < DATE_SUB(NOW(), INTERVAL dias_antiguedad DAY);

    SET filas_eliminadas_recordatorios = ROW_COUNT();

    -- Eliminar notificaciones antiguas
    DELETE FROM notificaciones_enviadas
    WHERE fecha_envio < DATE_SUB(NOW(), INTERVAL dias_antiguedad DAY);

    SET filas_eliminadas_notificaciones = ROW_COUNT();

    -- Mostrar resultado
    SELECT
        filas_eliminadas_recordatorios AS recordatorios_eliminados,
        filas_eliminadas_notificaciones AS notificaciones_eliminadas,
        dias_antiguedad AS dias_antiguedad,
        NOW() AS fecha_limpieza;
END$$

DELIMITER ;

-- ═══════════════════════════════════════════════════════════════════════
-- DATOS DE EJEMPLO (Comentados - descomenta si quieres probar)
-- ═══════════════════════════════════════════════════════════════════════

/*
-- Actualizar algunos usuarios con intereses y palabras clave de prueba
UPDATE usuarios
SET
    recibir_alertas_email = 1,
    intereses = 'Tecnología de la Información y Comunicaciones,Construcción y Obras Públicas',
    palabras_clave = 'software,computadoras,redes,infraestructura',
    frecuencia_notificaciones = 'inmediata'
WHERE es_admin = 0
LIMIT 3;
*/

-- ═══════════════════════════════════════════════════════════════════════
-- VERIFICACIÓN
-- ═══════════════════════════════════════════════════════════════════════

-- Mostrar tablas creadas
SELECT 'Tablas creadas correctamente' AS mensaje;

SHOW TABLES LIKE '%recordatorios%';
SHOW TABLES LIKE '%notificaciones%';
SHOW TABLES LIKE '%plantillas_email%';
SHOW TABLES LIKE '%configuracion_notificaciones%';

-- Mostrar columnas agregadas a usuarios
SELECT 'Columnas agregadas a tabla usuarios:' AS mensaje;

SELECT COLUMN_NAME, COLUMN_TYPE, COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'usuarios'
AND COLUMN_NAME IN ('recibir_alertas_email', 'palabras_clave', 'intereses', 'frecuencia_notificaciones', 'ultima_notificacion');

-- Mostrar configuraciones
SELECT 'Configuraciones por defecto:' AS mensaje;
SELECT * FROM configuracion_notificaciones;
