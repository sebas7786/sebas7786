-- ═══════════════════════════════════════════════════════════════════════
-- SCRIPT DE MIGRACIÓN: Crear/Actualizar tabla lineas_adjudicadas
-- ═══════════════════════════════════════════════════════════════════════
-- Este script puede ejecutarse múltiples veces de forma segura
-- ═══════════════════════════════════════════════════════════════════════

-- 1. ELIMINAR tabla antigua si existe (CUIDADO: elimina datos)
-- Descomenta la siguiente línea si quieres empezar desde cero:
-- DROP TABLE IF EXISTS `lineas_adjudicadas`;

-- 2. CREAR tabla con estructura completa
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
    INDEX `idx_numero_linea` (`numero_linea`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. AGREGAR columnas faltantes SI YA EXISTE LA TABLA (seguro, no da error si ya existen)

-- Ajustes financieros
ALTER TABLE `lineas_adjudicadas`
ADD COLUMN IF NOT EXISTS `descuento` DECIMAL(15,4) NULL AFTER `tipo_moneda`;

ALTER TABLE `lineas_adjudicadas`
ADD COLUMN IF NOT EXISTS `iva` DECIMAL(15,4) NULL AFTER `descuento`;

ALTER TABLE `lineas_adjudicadas`
ADD COLUMN IF NOT EXISTS `otros_impuestos` DECIMAL(15,4) NULL AFTER `iva`;

ALTER TABLE `lineas_adjudicadas`
ADD COLUMN IF NOT EXISTS `acarreos` DECIMAL(15,4) NULL AFTER `otros_impuestos`;

-- Tipos de cambio
ALTER TABLE `lineas_adjudicadas`
ADD COLUMN IF NOT EXISTS `tipo_cambio_crc` DECIMAL(10,4) NULL AFTER `acarreos`;

ALTER TABLE `lineas_adjudicadas`
ADD COLUMN IF NOT EXISTS `tipo_cambio_dolar` DECIMAL(10,4) NULL AFTER `tipo_cambio_crc`;

-- 4. AGREGAR índice único SI NO EXISTE
-- Primero intentamos eliminarlo por si existe
ALTER TABLE `lineas_adjudicadas`
DROP INDEX IF EXISTS `unique_linea_adjudicada`;

-- Luego lo creamos
ALTER TABLE `lineas_adjudicadas`
ADD UNIQUE KEY `unique_linea_adjudicada` (`numero_sicop`, `numero_linea`, `numero_oferta`);

-- ═══════════════════════════════════════════════════════════════════════
-- VERIFICACIÓN: Ejecuta este query para confirmar que todo está bien
-- ═══════════════════════════════════════════════════════════════════════

SELECT
    'TABLA CREADA CORRECTAMENTE' as status,
    COUNT(*) as total_columnas
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'lineas_adjudicadas';

-- Debería mostrar aproximadamente 18 columnas
