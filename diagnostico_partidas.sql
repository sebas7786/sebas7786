-- ═══════════════════════════════════════════════════════════════════════
-- DIAGNÓSTICO DE PARTIDAS - Ver qué datos existen
-- ═══════════════════════════════════════════════════════════════════════

USE u613395717_licitahoy1;

-- ──────────────────────────────────────────────────────────────────────
-- 1. Contar total de licitaciones y partidas
-- ──────────────────────────────────────────────────────────────────────
SELECT
    (SELECT COUNT(*) FROM licitaciones) as total_licitaciones,
    (SELECT COUNT(*) FROM partidas_licitacion) as total_partidas,
    (SELECT COUNT(DISTINCT licitacion_id) FROM partidas_licitacion) as licitaciones_con_partidas;

-- ──────────────────────────────────────────────────────────────────────
-- 2. Ver licitaciones que TIENEN partidas
-- ──────────────────────────────────────────────────────────────────────
SELECT
    l.id,
    l.numero_sicop,
    l.titulo,
    COUNT(p.id) as total_partidas,
    MIN(p.partida) as primera_partida,
    MAX(p.partida) as ultima_partida,
    MIN(p.linea) as primera_linea,
    MAX(p.linea) as ultima_linea
FROM licitaciones l
INNER JOIN partidas_licitacion p ON l.id = p.licitacion_id
GROUP BY l.id
ORDER BY l.id DESC
LIMIT 10;

-- ──────────────────────────────────────────────────────────────────────
-- 3. Ver ejemplo de partidas (primeras 20)
-- ──────────────────────────────────────────────────────────────────────
SELECT
    p.id,
    p.licitacion_id,
    l.numero_sicop,
    p.partida,
    p.linea,
    p.codigo,
    p.codigo_identificacion,
    p.nombre,
    p.descripcion,
    p.cantidad,
    p.unidad,
    p.precio_unitario,
    p.monto_estimado,
    p.moneda,
    p.tipo_cambio_usd
FROM partidas_licitacion p
INNER JOIN licitaciones l ON p.licitacion_id = l.id
ORDER BY p.id DESC
LIMIT 20;

-- ──────────────────────────────────────────────────────────────────────
-- 4. Verificar qué campos tienen datos
-- ──────────────────────────────────────────────────────────────────────
SELECT
    COUNT(*) as total_registros,
    COUNT(codigo) as con_codigo,
    COUNT(codigo_identificacion) as con_codigo_identificacion,
    COUNT(nombre) as con_nombre,
    COUNT(descripcion) as con_descripcion,
    COUNT(cantidad) as con_cantidad,
    COUNT(unidad) as con_unidad,
    COUNT(precio_unitario) as con_precio_unitario,
    COUNT(monto_estimado) as con_monto_estimado,
    COUNT(moneda) as con_moneda,
    COUNT(tipo_cambio_usd) as con_tipo_cambio
FROM partidas_licitacion;

-- ──────────────────────────────────────────────────────────────────────
-- 5. Ver si hay duplicados
-- ──────────────────────────────────────────────────────────────────────
SELECT
    licitacion_id,
    partida,
    linea,
    COUNT(*) as total_duplicados
FROM partidas_licitacion
GROUP BY licitacion_id, partida, linea
HAVING COUNT(*) > 1
LIMIT 10;

-- ──────────────────────────────────────────────────────────────────────
-- 6. Ver estructura actual de la tabla
-- ──────────────────────────────────────────────────────────────────────
DESCRIBE partidas_licitacion;

-- ──────────────────────────────────────────────────────────────────────
-- 7. Ver índices existentes
-- ──────────────────────────────────────────────────────────────────────
SHOW INDEXES FROM partidas_licitacion;

-- ═══════════════════════════════════════════════════════════════════════
-- INTERPRETACIÓN DE RESULTADOS:
-- ═══════════════════════════════════════════════════════════════════════
--
-- Si total_partidas = 0:
--   → El importador NO está insertando datos
--   → Verificar que el archivo CSV tenga las líneas correctas
--
-- Si con_nombre = 0 y con_descripcion = 0:
--   → Las líneas del CSV no tienen nombre/descripción
--   → Es normal, esos datos vienen en otro archivo (DetalleLineaCartel.csv)
--
-- Si hay duplicados (total_duplicados > 1):
--   → Ejecutar fix_partidas_unique_key.sql para eliminarlos
--
-- ═══════════════════════════════════════════════════════════════════════
