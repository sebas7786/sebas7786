-- ═══════════════════════════════════════════════════════════════════════
-- TABLA: lineas_adjudicadas
-- Almacena las líneas adjudicadas de licitaciones SICOP
-- Fuente: Archivo "LineasAdjudicadas" del Observatorio SICOP
-- ═══════════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `lineas_adjudicadas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,

    -- Identificadores
    `numero_sicop` VARCHAR(20) NOT NULL,
    `numero_oferta` VARCHAR(50) NULL,
    `numero_linea` INT NULL,
    `numero_acto` VARCHAR(50) NULL,

    -- Proveedor adjudicado
    `cedula_proveedor` VARCHAR(20) NULL,

    -- Producto/Servicio
    `codigo_producto` VARCHAR(100) NULL,

    -- Cantidades y montos adjudicados
    `cantidad_adjudicada` DECIMAL(15,4) NULL,
    `precio_unitario_adjudicado` DECIMAL(15,4) NULL,
    `tipo_moneda` VARCHAR(10) NULL DEFAULT 'CRC',

    -- Ajustes financieros
    `descuento` DECIMAL(15,4) NULL,
    `iva` DECIMAL(15,4) NULL,
    `otros_impuestos` DECIMAL(15,4) NULL,
    `acarreos` DECIMAL(15,4) NULL,

    -- Tipos de cambio
    `tipo_cambio_crc` DECIMAL(10,4) NULL,
    `tipo_cambio_dolar` DECIMAL(10,4) NULL,

    -- Metadata
    `fecha_importacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `actualizado` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Índices para búsquedas rápidas
    INDEX `idx_numero_sicop` (`numero_sicop`),
    INDEX `idx_cedula_proveedor` (`cedula_proveedor`),
    INDEX `idx_numero_oferta` (`numero_oferta`),
    INDEX `idx_numero_linea` (`numero_linea`),

    -- Clave única para evitar duplicados
    UNIQUE KEY `unique_linea_adjudicada` (`numero_sicop`, `numero_linea`, `numero_oferta`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════════════════════════════
-- INSTRUCCIONES DE USO
-- ═══════════════════════════════════════════════════════════════════════
-- 1. Ejecutar este script en phpMyAdmin o MySQL CLI
-- 2. Usar admin/importar_lineas_adjudicadas.php para cargar datos
-- 3. Usar admin/verificar_lineas_adjudicadas.php para ver estadísticas
-- ═══════════════════════════════════════════════════════════════════════
