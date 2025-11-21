-- =========================================
-- AGREGAR SOLO COLUMNAS QUE FALTAN
-- =========================================
-- Este script NO dará error si la columna ya existe

-- Columna: monto_pagar
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'usuarios'
     AND COLUMN_NAME = 'monto_pagar') = 0,
    'ALTER TABLE usuarios ADD COLUMN monto_pagar DECIMAL(10,2) DEFAULT 0 COMMENT "Monto mensual a pagar"',
    'SELECT "Columna monto_pagar ya existe" AS mensaje'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Columna: fecha_proximo_pago
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'usuarios'
     AND COLUMN_NAME = 'fecha_proximo_pago') = 0,
    'ALTER TABLE usuarios ADD COLUMN fecha_proximo_pago DATE NULL COMMENT "Fecha del próximo pago"',
    'SELECT "Columna fecha_proximo_pago ya existe" AS mensaje'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Columna: estado_pago (YA EXISTE en tu caso)
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'usuarios'
     AND COLUMN_NAME = 'estado_pago') = 0,
    'ALTER TABLE usuarios ADD COLUMN estado_pago TINYINT(1) DEFAULT 0 COMMENT "1=Al día, 0=Pendiente"',
    'SELECT "Columna estado_pago ya existe" AS mensaje'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Columna: activo
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'usuarios'
     AND COLUMN_NAME = 'activo') = 0,
    'ALTER TABLE usuarios ADD COLUMN activo TINYINT(1) DEFAULT 1 COMMENT "1=Activo, 0=Inactivo"',
    'SELECT "Columna activo ya existe" AS mensaje'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Columna: notas_pago
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'usuarios'
     AND COLUMN_NAME = 'notas_pago') = 0,
    'ALTER TABLE usuarios ADD COLUMN notas_pago TEXT NULL COMMENT "Notas sobre el pago"',
    'SELECT "Columna notas_pago ya existe" AS mensaje'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Columna: ultimo_pago
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'usuarios'
     AND COLUMN_NAME = 'ultimo_pago') = 0,
    'ALTER TABLE usuarios ADD COLUMN ultimo_pago DATE NULL COMMENT "Fecha del último pago recibido"',
    'SELECT "Columna ultimo_pago ya existe" AS mensaje'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Columna: monto_ultimo_pago
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'usuarios'
     AND COLUMN_NAME = 'monto_ultimo_pago') = 0,
    'ALTER TABLE usuarios ADD COLUMN monto_ultimo_pago DECIMAL(10,2) DEFAULT 0 COMMENT "Monto del último pago"',
    'SELECT "Columna monto_ultimo_pago ya existe" AS mensaje'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =========================================
-- CREAR TABLAS (si no existen)
-- =========================================

-- Tabla: historial_pagos
CREATE TABLE IF NOT EXISTS historial_pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    fecha_pago DATE NOT NULL,
    metodo_pago VARCHAR(50) NULL COMMENT 'transferencia, sinpe, efectivo, tarjeta, otro',
    referencia VARCHAR(100) NULL COMMENT 'Número de referencia o comprobante',
    estado VARCHAR(20) DEFAULT 'pagado' COMMENT 'pagado, pendiente, cancelado',
    notas TEXT NULL,
    creado_por INT NULL COMMENT 'ID del admin que registró el pago',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario_pago (usuario_id),
    INDEX idx_fecha_pago (fecha_pago),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Historial de pagos de usuarios';

-- Tabla: recordatorios_pago
CREATE TABLE IF NOT EXISTS recordatorios_pago (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    fecha_envio DATETIME NOT NULL,
    tipo VARCHAR(20) DEFAULT 'email' COMMENT 'email, sms, whatsapp',
    estado VARCHAR(20) DEFAULT 'enviado' COMMENT 'enviado, fallido',
    creado_por INT NULL COMMENT 'ID del admin que envió el recordatorio',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario_recordatorio (usuario_id),
    INDEX idx_fecha_envio (fecha_envio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Registro de recordatorios de pago enviados';

-- =========================================
-- ACTUALIZAR VALORES POR DEFECTO
-- =========================================
UPDATE usuarios
SET
    activo = COALESCE(activo, 1),
    estado_pago = COALESCE(estado_pago, 1),
    monto_pagar = COALESCE(monto_pagar, 0)
WHERE es_admin = 0;

-- =========================================
-- CREAR ÍNDICES
-- =========================================
CREATE INDEX IF NOT EXISTS idx_estado_pago ON usuarios(estado_pago);
CREATE INDEX IF NOT EXISTS idx_activo ON usuarios(activo);
CREATE INDEX IF NOT EXISTS idx_fecha_proximo_pago ON usuarios(fecha_proximo_pago);

-- =========================================
-- VERIFICACIÓN FINAL
-- =========================================
SELECT 'Script ejecutado correctamente. Verifica las columnas:' AS mensaje;
DESCRIBE usuarios;
