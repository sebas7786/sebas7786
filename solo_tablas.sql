-- =========================================
-- OPCIÓN SIMPLE: Solo crear las TABLAS
-- =========================================
-- Si ya tienes las columnas en 'usuarios', ejecuta solo esto:

-- Tabla: historial_pagos
CREATE TABLE IF NOT EXISTS historial_pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    fecha_pago DATE NOT NULL,
    metodo_pago VARCHAR(50) NULL,
    referencia VARCHAR(100) NULL,
    estado VARCHAR(20) DEFAULT 'pagado',
    notas TEXT NULL,
    creado_por INT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla: recordatorios_pago
CREATE TABLE IF NOT EXISTS recordatorios_pago (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    fecha_envio DATETIME NOT NULL,
    tipo VARCHAR(20) DEFAULT 'email',
    estado VARCHAR(20) DEFAULT 'enviado',
    creado_por INT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Actualizar valores por defecto
UPDATE usuarios SET activo = 1 WHERE activo IS NULL AND es_admin = 0;
UPDATE usuarios SET estado_pago = 1 WHERE estado_pago IS NULL AND es_admin = 0;
UPDATE usuarios SET monto_pagar = 0 WHERE monto_pagar IS NULL AND es_admin = 0;

SELECT 'Tablas creadas correctamente' AS mensaje;
