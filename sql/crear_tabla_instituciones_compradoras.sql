-- ============================================================================
-- Tabla: instituciones_compradoras
-- Descripción: Almacena información de instituciones compradoras (las que licitan)
-- Fuente: Archivo CSV "Instituciones" del Observatorio SICOP
-- ============================================================================

CREATE TABLE IF NOT EXISTS `instituciones_compradoras` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `cedula` VARCHAR(20) NOT NULL UNIQUE,
    `nombre_institucion` VARCHAR(255) NULL,
    `direccion` TEXT NULL,
    `telefono` VARCHAR(50) NULL,
    `representante` VARCHAR(255) NULL,
    `codigo_postal` VARCHAR(20) NULL,
    `provincia` VARCHAR(100) NULL,
    `canton` VARCHAR(100) NULL,
    `distrito` VARCHAR(100) NULL,
    `fecha_importacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `actualizado` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_cedula` (`cedula`),
    INDEX `idx_nombre` (`nombre_institucion`),
    INDEX `idx_provincia` (`provincia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Notas de uso:
-- ============================================================================
-- 1. La cédula es UNIQUE para evitar duplicados
-- 2. Esta tabla se enlaza con licitaciones.cedula_institucion
-- 3. Permite filtrar por provincia para análisis geográfico
-- 4. El campo 'actualizado' se actualiza automáticamente en cada cambio
-- ============================================================================
