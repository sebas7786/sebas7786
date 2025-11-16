-- ============================================================================
-- Script para renombrar tabla de instituciones_proveedoras a instituciones_compradoras
-- ============================================================================
-- Ejecuta este script SOLO si ya creaste la tabla con el nombre antiguo
-- "instituciones_proveedoras" y quieres cambiarla a "instituciones_compradoras"
-- ============================================================================

-- Verificar si existe la tabla antigua
SELECT COUNT(*) INTO @tabla_antigua_existe
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'instituciones_proveedoras';

-- Verificar si existe la tabla nueva
SELECT COUNT(*) INTO @tabla_nueva_existe
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'instituciones_compradoras';

-- Si existe la antigua y NO existe la nueva, renombrar
SET @sql = IF(
    @tabla_antigua_existe = 1 AND @tabla_nueva_existe = 0,
    'RENAME TABLE instituciones_proveedoras TO instituciones_compradoras',
    'SELECT "No es necesario renombrar: la tabla antigua no existe o la nueva ya existe" AS mensaje'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar resultado
SELECT
    CASE
        WHEN EXISTS (SELECT 1 FROM information_schema.TABLES
                     WHERE TABLE_SCHEMA = DATABASE()
                       AND TABLE_NAME = 'instituciones_compradoras')
        THEN '✅ Tabla instituciones_compradoras existe correctamente'
        ELSE '❌ La tabla instituciones_compradoras NO existe'
    END AS resultado;

-- ============================================================================
-- Instrucciones de uso:
-- ============================================================================
-- 1. Si YA creaste la tabla con el nombre "instituciones_proveedoras":
--    mysql -u usuario -p nombre_bd < sql/renombrar_instituciones_proveedoras_a_compradoras.sql
--
-- 2. Si AÚN NO has creado la tabla:
--    mysql -u usuario -p nombre_bd < sql/crear_tabla_instituciones_compradoras.sql
--    (No necesitas este script de renombrado)
-- ============================================================================
