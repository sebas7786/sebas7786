-- ═══════════════════════════════════════════════════════════════════════
-- CORRECCIÓN: Agregar UNIQUE KEY a partidas_licitacion
-- ═══════════════════════════════════════════════════════════════════════
-- Problema: El importador usa ON DUPLICATE KEY UPDATE pero no hay clave única
-- Solución: Agregar UNIQUE KEY para (licitacion_id, partida, linea)
-- ═══════════════════════════════════════════════════════════════════════

USE u613395717_licitahoy1;

-- ──────────────────────────────────────────────────────────────────────
-- 1. Verificar si existe la clave única
-- ──────────────────────────────────────────────────────────────────────
SHOW INDEXES FROM partidas_licitacion WHERE Key_name = 'unique_partida_linea';

-- ──────────────────────────────────────────────────────────────────────
-- 2. Eliminar duplicados ANTES de crear la clave única
-- ──────────────────────────────────────────────────────────────────────

-- Primero, ver si hay duplicados
SELECT
    licitacion_id,
    partida,
    linea,
    COUNT(*) as total
FROM partidas_licitacion
GROUP BY licitacion_id, partida, linea
HAVING COUNT(*) > 1;

-- Si hay duplicados, eliminarlos conservando el más reciente
DELETE p1 FROM partidas_licitacion p1
INNER JOIN partidas_licitacion p2
WHERE
    p1.licitacion_id = p2.licitacion_id AND
    p1.partida = p2.partida AND
    p1.linea = p2.linea AND
    p1.id < p2.id;

-- ──────────────────────────────────────────────────────────────────────
-- 3. Agregar la UNIQUE KEY
-- ──────────────────────────────────────────────────────────────────────

ALTER TABLE partidas_licitacion
ADD UNIQUE KEY unique_partida_linea (licitacion_id, partida, linea);

-- ──────────────────────────────────────────────────────────────────────
-- 4. Verificar que se creó correctamente
-- ──────────────────────────────────────────────────────────────────────
SHOW INDEXES FROM partidas_licitacion;

-- ──────────────────────────────────────────────────────────────────────
-- 5. Consulta de prueba: Ver partidas de una licitación
-- ──────────────────────────────────────────────────────────────────────

-- Reemplaza el 1 con el ID de una licitación real
SELECT
    p.id,
    p.licitacion_id,
    p.partida,
    p.linea,
    p.codigo_identificacion,
    p.nombre,
    p.descripcion,
    p.cantidad,
    p.unidad,
    p.precio_unitario,
    p.monto_estimado,
    p.moneda,
    p.tipo_cambio_usd,
    l.numero_sicop,
    l.titulo
FROM partidas_licitacion p
INNER JOIN licitaciones l ON p.licitacion_id = l.id
WHERE p.licitacion_id = 1
ORDER BY p.partida ASC, p.linea ASC
LIMIT 20;

-- ═══════════════════════════════════════════════════════════════════════
-- NOTAS:
-- ═══════════════════════════════════════════════════════════════════════
--
-- 1. La UNIQUE KEY evita duplicados de partida+línea por licitación
-- 2. Permite que el importador use ON DUPLICATE KEY UPDATE correctamente
-- 3. Mejora el rendimiento de las consultas
--
-- ═══════════════════════════════════════════════════════════════════════
