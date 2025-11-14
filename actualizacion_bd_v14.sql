-- ═══════════════════════════════════════════════════════════════════════
-- ACTUALIZACIÓN BASE DE DATOS PARA IMPORTADOR SICOP v14.0
-- ═══════════════════════════════════════════════════════════════════════
-- Fecha: 2025-11-14
-- Propósito: Agregar campos para importación de Detalle de Carteles
-- ═══════════════════════════════════════════════════════════════════════

USE u613395717_licitahoy1;

-- ──────────────────────────────────────────────────────────────────────
-- 1. VERIFICAR Y AGREGAR CAMPOS A TABLA licitaciones
-- ──────────────────────────────────────────────────────────────────────

-- Código de Unidad de Compra
-- (Ya existe según el dump, pero incluimos por seguridad)
ALTER TABLE licitaciones
ADD COLUMN IF NOT EXISTS cod_unidad_compra VARCHAR(50) DEFAULT NULL
COMMENT 'Código de la unidad de compra';

-- Nombre de Unidad de Compra
ALTER TABLE licitaciones
ADD COLUMN IF NOT EXISTS nombre_unidad_compra VARCHAR(255) DEFAULT NULL
COMMENT 'Nombre completo de la unidad de compra';

-- Pago Adelantado a PYMEs
ALTER TABLE licitaciones
ADD COLUMN IF NOT EXISTS pago_adelantado_pymes ENUM('S','N') DEFAULT NULL
COMMENT 'Permite pago adelantado a pequeñas y medianas empresas';

-- Fecha de Invitación
ALTER TABLE licitaciones
ADD COLUMN IF NOT EXISTS fecha_invitacion DATETIME DEFAULT NULL
COMMENT 'Fecha en que se envió la invitación a participar';

-- Fecha Inicio de Recepción
ALTER TABLE licitaciones
ADD COLUMN IF NOT EXISTS fecha_inicio_recepcion DATETIME DEFAULT NULL
COMMENT 'Fecha y hora de inicio de recepción de ofertas';

-- Nota: fecha_cierre_recepcion ya existe en la tabla


-- ──────────────────────────────────────────────────────────────────────
-- 2. VERIFICAR Y AGREGAR CAMPOS A TABLA partidas_licitacion
-- ──────────────────────────────────────────────────────────────────────

-- Tipo de Cambio USD
ALTER TABLE partidas_licitacion
ADD COLUMN IF NOT EXISTS tipo_cambio_usd DECIMAL(10,4) DEFAULT NULL
COMMENT 'Tipo de cambio a dólares estadounidenses';


-- ──────────────────────────────────────────────────────────────────────
-- 3. CREAR ÍNDICES PARA OPTIMIZAR BÚSQUEDAS (OPCIONAL)
-- ──────────────────────────────────────────────────────────────────────

-- Índice para búsquedas por código de unidad de compra
CREATE INDEX IF NOT EXISTS idx_licitaciones_cod_unidad
ON licitaciones(cod_unidad_compra);

-- Índice para búsquedas por nombre de unidad de compra
CREATE INDEX IF NOT EXISTS idx_licitaciones_nombre_unidad
ON licitaciones(nombre_unidad_compra(100));

-- Índice para filtrar por pago adelantado PYMEs
CREATE INDEX IF NOT EXISTS idx_licitaciones_pago_pymes
ON licitaciones(pago_adelantado_pymes);

-- Índice para ordenar por fecha de invitación
CREATE INDEX IF NOT EXISTS idx_licitaciones_fecha_invitacion
ON licitaciones(fecha_invitacion);

-- Índice para ordenar por fecha inicio recepción
CREATE INDEX IF NOT EXISTS idx_licitaciones_fecha_inicio_recepcion
ON licitaciones(fecha_inicio_recepcion);


-- ──────────────────────────────────────────────────────────────────────
-- 4. VERIFICAR CAMPOS EXISTENTES
-- ──────────────────────────────────────────────────────────────────────

-- Mostrar estructura actualizada de licitaciones
SHOW COLUMNS FROM licitaciones LIKE '%unidad%';
SHOW COLUMNS FROM licitaciones LIKE '%pago%';
SHOW COLUMNS FROM licitaciones LIKE '%invitacion%';
SHOW COLUMNS FROM licitaciones LIKE '%recepcion%';

-- Mostrar estructura actualizada de partidas
SHOW COLUMNS FROM partidas_licitacion LIKE '%cambio%';


-- ──────────────────────────────────────────────────────────────────────
-- 5. CONSULTAS DE VERIFICACIÓN
-- ──────────────────────────────────────────────────────────────────────

-- Contar licitaciones con datos nuevos
SELECT
    COUNT(*) as total_licitaciones,
    COUNT(cod_unidad_compra) as con_cod_unidad,
    COUNT(nombre_unidad_compra) as con_nombre_unidad,
    COUNT(pago_adelantado_pymes) as con_pago_pymes,
    COUNT(fecha_invitacion) as con_fecha_invitacion,
    COUNT(fecha_inicio_recepcion) as con_fecha_inicio_recepcion
FROM licitaciones;

-- Contar partidas con tipo de cambio
SELECT
    COUNT(*) as total_partidas,
    COUNT(tipo_cambio_usd) as con_tipo_cambio_usd
FROM partidas_licitacion;


-- ══════════════════════════════════════════════════════════════════════
-- NOTAS IMPORTANTES
-- ══════════════════════════════════════════════════════════════════════
--
-- 1. Estos cambios son COMPATIBLES con los datos existentes
-- 2. Los campos nuevos aceptan NULL, no afecta registros actuales
-- 3. El importador verifica automáticamente la estructura
-- 4. Si un campo ya existe, el ALTER TABLE lo ignora
-- 5. Los índices mejoran el rendimiento de búsquedas
--
-- ══════════════════════════════════════════════════════════════════════

-- Fin del script de actualización
