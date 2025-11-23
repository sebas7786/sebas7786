-- ═══════════════════════════════════════════════════════════════════════
-- SCRIPT PARA MARCAR LICITACIONES ADJUDICADAS
-- ═══════════════════════════════════════════════════════════════════════
-- Este script actualiza la tabla 'licitaciones' para marcar como adjudicadas
-- todas las que tienen registros en la tabla 'lineas_adjudicadas'
--
-- EJECUTAR EN phpMyAdmin O LÍNEA DE COMANDOS
-- ═══════════════════════════════════════════════════════════════════════

-- Paso 1: Ver cuántas licitaciones serían actualizadas (PREVIEW)
SELECT
    COUNT(DISTINCT l.id) as total_a_actualizar,
    'licitaciones serán marcadas como adjudicadas' as mensaje
FROM licitaciones l
INNER JOIN lineas_adjudicadas la ON l.numero_sicop = la.numero_sicop
WHERE l.estado != 'adjudicada';

-- Paso 2: Ver algunos ejemplos de las que se actualizarán
SELECT
    l.id,
    l.numero_sicop,
    l.titulo,
    l.estado as estado_actual,
    COUNT(la.id) as lineas_adjudicadas
FROM licitaciones l
INNER JOIN lineas_adjudicadas la ON l.numero_sicop = la.numero_sicop
WHERE l.estado != 'adjudicada'
GROUP BY l.id, l.numero_sicop, l.titulo, l.estado
LIMIT 10;

-- ═══════════════════════════════════════════════════════════════════════
-- Paso 3: ACTUALIZAR (Descomenta las siguientes líneas para ejecutar)
-- ═══════════════════════════════════════════════════════════════════════

/*
-- OPCIÓN A: Solo cambiar el estado a 'adjudicada'
UPDATE licitaciones l
INNER JOIN lineas_adjudicadas la ON l.numero_sicop = la.numero_sicop
SET l.estado = 'adjudicada'
WHERE l.estado != 'adjudicada';

-- Verificar resultado
SELECT
    estado,
    COUNT(*) as cantidad
FROM licitaciones
GROUP BY estado;
*/

-- ═══════════════════════════════════════════════════════════════════════
-- OPCIÓN B: Cambiar estado Y agregar fecha de adjudicación
-- ═══════════════════════════════════════════════════════════════════════

/*
-- Actualizar con fecha de adjudicación (la más reciente de cada licitación)
UPDATE licitaciones l
INNER JOIN (
    SELECT
        numero_sicop,
        MAX(fecha_adjudicacion) as fecha_adj
    FROM lineas_adjudicadas
    WHERE fecha_adjudicacion IS NOT NULL
    GROUP BY numero_sicop
) la ON l.numero_sicop = la.numero_sicop
SET
    l.estado = 'adjudicada',
    l.fecha_adjudicacion = la.fecha_adj
WHERE l.estado != 'adjudicada';

-- Si lineas_adjudicadas no tiene fecha_adjudicacion, usa la fecha de creación:
UPDATE licitaciones l
INNER JOIN (
    SELECT
        numero_sicop,
        MAX(created_at) as fecha_adj
    FROM lineas_adjudicadas
    GROUP BY numero_sicop
) la ON l.numero_sicop = la.numero_sicop
SET
    l.estado = 'adjudicada',
    l.fecha_adjudicacion = DATE(la.fecha_adj)
WHERE l.estado != 'adjudicada';
*/

-- ═══════════════════════════════════════════════════════════════════════
-- Paso 4: Verificación final
-- ═══════════════════════════════════════════════════════════════════════

-- Ver distribución de estados
SELECT
    estado,
    COUNT(*) as cantidad,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM licitaciones), 2) as porcentaje
FROM licitaciones
GROUP BY estado
ORDER BY cantidad DESC;

-- Ver algunas licitaciones adjudicadas
SELECT
    id,
    numero_sicop,
    titulo,
    estado,
    fecha_adjudicacion,
    (SELECT COUNT(*) FROM lineas_adjudicadas WHERE numero_sicop = l.numero_sicop) as lineas_adj
FROM licitaciones l
WHERE estado = 'adjudicada'
LIMIT 10;

-- ═══════════════════════════════════════════════════════════════════════
-- CREAR TRIGGER AUTOMÁTICO (OPCIONAL)
-- Para que las licitaciones se marquen automáticamente al agregar adjudicaciones
-- ═══════════════════════════════════════════════════════════════════════

/*
DELIMITER $$

CREATE TRIGGER actualizar_estado_adjudicada
AFTER INSERT ON lineas_adjudicadas
FOR EACH ROW
BEGIN
    -- Actualizar el estado de la licitación a 'adjudicada'
    UPDATE licitaciones
    SET
        estado = 'adjudicada',
        fecha_adjudicacion = COALESCE(NEW.fecha_adjudicacion, CURDATE())
    WHERE numero_sicop = NEW.numero_sicop
    AND estado != 'adjudicada';
END$$

DELIMITER ;

-- Ver triggers creados
SHOW TRIGGERS LIKE 'lineas_adjudicadas';
*/

-- ═══════════════════════════════════════════════════════════════════════
-- NOTAS IMPORTANTES
-- ═══════════════════════════════════════════════════════════════════════
/*
1. Ejecuta primero los SELECT (Paso 1 y 2) para ver qué se va a actualizar
2. Luego descomenta el UPDATE que prefieras (OPCIÓN A o B)
3. Verifica con el Paso 4 que todo está correcto
4. El TRIGGER es opcional pero recomendado para mantener sincronizado

RECOMENDACIÓN:
- Usa OPCIÓN B si lineas_adjudicadas tiene fecha_adjudicacion
- Usa OPCIÓN A si no tiene fecha
- Crea el TRIGGER para automatizar futuras adjudicaciones
*/
