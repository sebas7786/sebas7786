-- =========================================
-- SCRIPT SQL: Sistema de Gestión de Pagos
-- =========================================

-- 1. Agregar columnas necesarias a la tabla usuarios
ALTER TABLE usuarios
ADD COLUMN IF NOT EXISTS monto_pagar DECIMAL(10,2) DEFAULT 0 COMMENT 'Monto mensual a pagar',
ADD COLUMN IF NOT EXISTS fecha_proximo_pago DATE NULL COMMENT 'Fecha del próximo pago',
ADD COLUMN IF NOT EXISTS notas_pago TEXT NULL COMMENT 'Notas sobre el pago del usuario',
ADD COLUMN IF NOT EXISTS ultimo_pago DATE NULL COMMENT 'Fecha del último pago recibido',
ADD COLUMN IF NOT EXISTS monto_ultimo_pago DECIMAL(10,2) DEFAULT 0 COMMENT 'Monto del último pago',
ADD COLUMN IF NOT EXISTS activo TINYINT(1) DEFAULT 1 COMMENT '1=Activo, 0=Inactivo';

-- 2. Modificar columna estado_pago si ya existe
-- Si la columna estado_pago no existe, crearla
ALTER TABLE usuarios
ADD COLUMN IF NOT EXISTS estado_pago TINYINT(1) DEFAULT 0 COMMENT '1=Al día, 0=Pendiente';

-- 3. Crear tabla para historial de pagos
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
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_usuario_pago (usuario_id),
    INDEX idx_fecha_pago (fecha_pago),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Historial de pagos de usuarios';

-- 4. Crear tabla para recordatorios enviados
CREATE TABLE IF NOT EXISTS recordatorios_pago (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    fecha_envio DATETIME NOT NULL,
    tipo VARCHAR(20) DEFAULT 'email' COMMENT 'email, sms, whatsapp',
    estado VARCHAR(20) DEFAULT 'enviado' COMMENT 'enviado, fallido',
    creado_por INT NULL COMMENT 'ID del admin que envió el recordatorio',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_usuario_recordatorio (usuario_id),
    INDEX idx_fecha_envio (fecha_envio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Registro de recordatorios de pago enviados';

-- 5. Actualizar usuarios existentes con valores por defecto
UPDATE usuarios
SET
    monto_pagar = 0,
    activo = 1,
    estado_pago = 1
WHERE es_admin = 0 AND monto_pagar IS NULL;

-- 6. Crear índices adicionales para mejorar rendimiento
CREATE INDEX IF NOT EXISTS idx_estado_pago ON usuarios(estado_pago);
CREATE INDEX IF NOT EXISTS idx_activo ON usuarios(activo);
CREATE INDEX IF NOT EXISTS idx_fecha_proximo_pago ON usuarios(fecha_proximo_pago);

-- 7. Vista para reportes de pagos
CREATE OR REPLACE VIEW vista_estado_pagos AS
SELECT
    u.id,
    u.nombre,
    u.email,
    u.empresa,
    u.activo,
    u.estado_pago,
    u.monto_pagar,
    u.fecha_proximo_pago,
    u.ultimo_pago,
    u.monto_ultimo_pago,
    DATEDIFF(u.fecha_proximo_pago, CURDATE()) as dias_hasta_pago,
    COUNT(hp.id) as total_pagos,
    COALESCE(SUM(CASE WHEN hp.estado = 'pagado' THEN hp.monto ELSE 0 END), 0) as total_pagado,
    MAX(hp.fecha_pago) as ultimo_pago_registrado,
    COUNT(rp.id) as recordatorios_enviados,
    MAX(rp.fecha_envio) as ultimo_recordatorio
FROM usuarios u
LEFT JOIN historial_pagos hp ON u.id = hp.usuario_id
LEFT JOIN recordatorios_pago rp ON u.id = rp.usuario_id
WHERE u.es_admin = 0
GROUP BY u.id;

-- =========================================
-- CONSULTAS ÚTILES
-- =========================================

-- Ver usuarios con pago vencido
-- SELECT * FROM vista_estado_pagos WHERE dias_hasta_pago < 0 AND estado_pago = 0 ORDER BY dias_hasta_pago;

-- Ver usuarios próximos a vencer (7 días)
-- SELECT * FROM vista_estado_pagos WHERE dias_hasta_pago BETWEEN 0 AND 7 AND estado_pago = 0;

-- Ver historial de pagos de un usuario
-- SELECT * FROM historial_pagos WHERE usuario_id = ? ORDER BY fecha_pago DESC;

-- Ingresos del mes actual
-- SELECT SUM(monto) as total FROM historial_pagos
-- WHERE MONTH(fecha_pago) = MONTH(CURDATE())
-- AND YEAR(fecha_pago) = YEAR(CURDATE())
-- AND estado = 'pagado';

-- Recordatorios enviados hoy
-- SELECT COUNT(*) as total FROM recordatorios_pago WHERE DATE(fecha_envio) = CURDATE();

-- =========================================
-- TRIGGER: Desactivar usuario si pago vencido más de 30 días
-- =========================================
DELIMITER $$

CREATE TRIGGER IF NOT EXISTS desactivar_usuario_pago_vencido
BEFORE UPDATE ON usuarios
FOR EACH ROW
BEGIN
    IF NEW.fecha_proximo_pago IS NOT NULL
       AND NEW.estado_pago = 0
       AND DATEDIFF(CURDATE(), NEW.fecha_proximo_pago) > 30
    THEN
        SET NEW.activo = 0;
    END IF;
END$$

DELIMITER ;

-- =========================================
-- DATOS DE EJEMPLO (OPCIONAL - Solo para testing)
-- =========================================

-- Insertar algunos pagos de ejemplo (comentado por defecto)
/*
INSERT INTO historial_pagos (usuario_id, monto, fecha_pago, metodo_pago, estado, notas)
VALUES
(1, 15000, '2024-01-15', 'transferencia', 'pagado', 'Pago mensual enero'),
(1, 15000, '2024-02-15', 'sinpe', 'pagado', 'Pago mensual febrero'),
(2, 20000, '2024-01-20', 'efectivo', 'pagado', 'Pago mensual enero');
*/

-- =========================================
-- FIN DEL SCRIPT
-- =========================================
