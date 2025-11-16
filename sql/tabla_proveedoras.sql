-- ============================================================================
-- TABLA: proveedoras
-- ============================================================================
-- Descripción: Empresas proveedoras que ofertan en licitaciones
-- Fuente: Archivo CSV "Proveedores" del Observatorio SICOP
-- ============================================================================

CREATE TABLE IF NOT EXISTS `proveedoras` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `cedula` VARCHAR(20) NOT NULL UNIQUE,
    `nombre_proveedor` VARCHAR(255) NULL,
    `tipo_proveedor` VARCHAR(100) NULL,
    `tamano_proveedor` VARCHAR(50) NULL,
    `codigo_postal` VARCHAR(20) NULL,
    `provincia` VARCHAR(100) NULL,
    `canton` VARCHAR(100) NULL,
    `distrito` VARCHAR(100) NULL,
    `fecha_importacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `actualizado` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_cedula` (`cedula`),
    INDEX `idx_nombre` (`nombre_proveedor`),
    INDEX `idx_provincia` (`provincia`),
    INDEX `idx_tipo` (`tipo_proveedor`),
    INDEX `idx_tamano` (`tamano_proveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Instrucciones:
-- ============================================================================
-- mysql -u usuario -p nombre_bd < sql/tabla_proveedoras.sql
-- ============================================================================
