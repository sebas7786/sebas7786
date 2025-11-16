-- ═══════════════════════════════════════════════════════════════════════
-- TABLA: lineas_contratadas
-- Almacena las líneas adjudicadas de licitaciones
-- Fuente: Archivo "LineasContratadas" del Observatorio SICOP
-- ═══════════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `lineas_contratadas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,

    -- Identificadores
    `numero_sicop` VARCHAR(20) NOT NULL,
    `numero_linea_contrato` INT NULL,
    `numero_linea_cartel` INT NULL,
    `numero_contrato` VARCHAR(50) NULL,
    `secuencia` INT NULL,
    `numero_acto` VARCHAR(50) NULL,

    -- Proveedor adjudicado
    `cedula_proveedor` VARCHAR(20) NULL,

    -- Producto/Servicio
    `codigo_producto` VARCHAR(100) NULL,
    `descripcion_producto` TEXT NULL,

    -- Cantidades y montos
    `cantidad_contratada` DECIMAL(15,4) NULL,
    `precio_unitario` DECIMAL(15,4) NULL,
    `tipo_moneda` VARCHAR(10) NULL,

    -- Ajustes financieros
    `descuento` DECIMAL(15,4) NULL,
    `iva` DECIMAL(15,4) NULL,
    `otros_impuestos` DECIMAL(15,4) NULL,
    `acarreos` DECIMAL(15,4) NULL,

    -- Tipos de cambio
    `tipo_cambio_crc` DECIMAL(10,4) NULL,
    `tipo_cambio_dolar` DECIMAL(10,4) NULL,

    -- Modificaciones
    `cantidad_aumentada` DECIMAL(15,4) NULL,
    `cantidad_disminuida` DECIMAL(15,4) NULL,
    `monto_aumentado` DECIMAL(15,4) NULL,
    `monto_disminuido` DECIMAL(15,4) NULL,

    -- Metadata
    `fecha_importacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `actualizado` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Índices para búsquedas rápidas
    INDEX `idx_numero_sicop` (`numero_sicop`),
    INDEX `idx_cedula_proveedor` (`cedula_proveedor`),
    INDEX `idx_numero_contrato` (`numero_contrato`),
    INDEX `idx_numero_linea_cartel` (`numero_linea_cartel`),

    -- Clave única para evitar duplicados
    UNIQUE KEY `unique_linea_contratada` (`numero_sicop`, `numero_linea_contrato`, `secuencia`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════════════════════════════
-- INSTRUCCIONES DE USO
-- ═══════════════════════════════════════════════════════════════════════
-- 1. Ejecutar este script en phpMyAdmin o MySQL CLI
-- 2. Usar admin/importar_lineas_contratadas.php para cargar datos
-- 3. Usar admin/verificar_lineas_contratadas.php para ver estadísticas
-- ═══════════════════════════════════════════════════════════════════════
