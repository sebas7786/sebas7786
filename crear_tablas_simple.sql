-- =========================================
-- SCRIPT SIMPLE: Crear tablas de pago
-- =========================================

-- 1. Verificar que estás en la base de datos correcta
SELECT DATABASE() as base_datos_actual;

-- 2. Eliminar tabla si existe (para empezar limpio)
DROP TABLE IF EXISTS historial_pagos;
DROP TABLE IF EXISTS recordatorios_pago;

-- 3. Crear tabla historial_pagos (VERSIÓN SIMPLE)
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
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Agregar índices después
ALTER TABLE historial_pagos ADD INDEX idx_usuario_pago (usuario_id);
ALTER TABLE historial_pagos ADD INDEX idx_fecha_pago (fecha_pago);

-- 5. Agregar foreign key después (si da error, continúa sin él)
ALTER TABLE historial_pagos
ADD CONSTRAINT fk_historial_usuario
FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE;

-- 6. Crear tabla recordatorios_pago (VERSIÓN SIMPLE)
CREATE TABLE recordatorios_pago (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    fecha_envio DATETIME NOT NULL,
    tipo VARCHAR(20) DEFAULT 'email',
    estado VARCHAR(20) DEFAULT 'enviado',
    creado_por INT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 7. Agregar índices
ALTER TABLE recordatorios_pago ADD INDEX idx_usuario_recordatorio (usuario_id);

-- 8. Agregar foreign key (si da error, continúa sin él)
ALTER TABLE recordatorios_pago
ADD CONSTRAINT fk_recordatorio_usuario
FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE;

-- 9. Verificar que se crearon
SHOW TABLES LIKE '%historial%';
SHOW TABLES LIKE '%recordatorio%';

-- 10. Ver estructura
DESCRIBE historial_pagos;
DESCRIBE recordatorios_pago;

SELECT 'Tablas creadas exitosamente' AS resultado;
