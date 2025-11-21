-- =========================================
-- VERSIÓN MÁS SIMPLE: SIN FOREIGN KEYS
-- =========================================
-- Usa este si el otro script da error

-- Eliminar si existen
DROP TABLE IF EXISTS historial_pagos;
DROP TABLE IF EXISTS recordatorios_pago;

-- Crear historial_pagos (SIN foreign keys)
CREATE TABLE historial_pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    fecha_pago DATE NOT NULL,
    metodo_pago VARCHAR(50),
    referencia VARCHAR(100),
    estado VARCHAR(20) DEFAULT 'pagado',
    notas TEXT,
    creado_por INT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_usuario_pago (usuario_id),
    KEY idx_fecha_pago (fecha_pago)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Crear recordatorios_pago (SIN foreign keys)
CREATE TABLE recordatorios_pago (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    fecha_envio DATETIME NOT NULL,
    tipo VARCHAR(20) DEFAULT 'email',
    estado VARCHAR(20) DEFAULT 'enviado',
    creado_por INT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_usuario_recordatorio (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Verificar
SELECT 'Verificando historial_pagos...' AS paso;
SELECT COUNT(*) as total_columnas FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'historial_pagos' AND TABLE_SCHEMA = DATABASE();

SELECT 'Verificando recordatorios_pago...' AS paso;
SELECT COUNT(*) as total_columnas FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'recordatorios_pago' AND TABLE_SCHEMA = DATABASE();

SELECT 'Si ves 10 columnas en historial_pagos y 6 en recordatorios_pago, todo está bien!' AS resultado;
