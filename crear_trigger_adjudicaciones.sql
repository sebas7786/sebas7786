-- ═══════════════════════════════════════════════════════════════════════
-- TRIGGER AUTOMÁTICO PARA ACTUALIZAR LICITACIONES ADJUDICADAS
-- ═══════════════════════════════════════════════════════════════════════
-- Este trigger se ejecuta automáticamente cuando:
-- 1. Se inserta una nueva línea en lineas_adjudicadas
-- 2. Se actualiza una línea existente
-- 3. Se elimina una línea (opcional)
--
-- EJECUTAR EN phpMyAdmin > SQL
-- ═══════════════════════════════════════════════════════════════════════

-- ═══════════════════════════════════════════════════════════════════════
-- PASO 1: Eliminar triggers existentes (si existen)
-- ═══════════════════════════════════════════════════════════════════════

DROP TRIGGER IF EXISTS actualizar_licitacion_al_adjudicar;
DROP TRIGGER IF EXISTS actualizar_licitacion_al_modificar;
DROP TRIGGER IF EXISTS actualizar_licitacion_al_eliminar;

-- ═══════════════════════════════════════════════════════════════════════
-- PASO 2: Crear trigger para INSERTAR nueva adjudicación
-- ═══════════════════════════════════════════════════════════════════════

DELIMITER $$

CREATE TRIGGER actualizar_licitacion_al_adjudicar
AFTER INSERT ON lineas_adjudicadas
FOR EACH ROW
BEGIN
    -- Actualizar la licitación correspondiente
    UPDATE licitaciones
    SET
        estado = 'adjudicada',
        fecha_adjudicacion = COALESCE(NEW.fecha_adjudicacion, CURDATE()),
        fecha_ultima_actualizacion = NOW()
    WHERE numero_sicop = NEW.numero_sicop
    AND estado != 'adjudicada';  -- Solo si no está ya marcada
END$$

DELIMITER ;

-- ═══════════════════════════════════════════════════════════════════════
-- PASO 3: Crear trigger para ACTUALIZAR adjudicación existente
-- ═══════════════════════════════════════════════════════════════════════

DELIMITER $$

CREATE TRIGGER actualizar_licitacion_al_modificar
AFTER UPDATE ON lineas_adjudicadas
FOR EACH ROW
BEGIN
    -- Actualizar la fecha de última actualización
    UPDATE licitaciones
    SET fecha_ultima_actualizacion = NOW()
    WHERE numero_sicop = NEW.numero_sicop;
END$$

DELIMITER ;

-- ═══════════════════════════════════════════════════════════════════════
-- PASO 4 (OPCIONAL): Trigger para ELIMINAR adjudicación
-- ═══════════════════════════════════════════════════════════════════════
-- Este trigger verifica si quedan adjudicaciones. Si no quedan, desmarca la licitación.

DELIMITER $$

CREATE TRIGGER actualizar_licitacion_al_eliminar
AFTER DELETE ON lineas_adjudicadas
FOR EACH ROW
BEGIN
    DECLARE lineas_restantes INT;

    -- Contar cuántas líneas adjudicadas quedan para esta licitación
    SELECT COUNT(*) INTO lineas_restantes
    FROM lineas_adjudicadas
    WHERE numero_sicop = OLD.numero_sicop;

    -- Si no quedan líneas adjudicadas, marcar como cerrada (no adjudicada)
    IF lineas_restantes = 0 THEN
        UPDATE licitaciones
        SET
            estado = 'cerrada',
            fecha_adjudicacion = NULL,
            fecha_ultima_actualizacion = NOW()
        WHERE numero_sicop = OLD.numero_sicop;
    END IF;
END$$

DELIMITER ;

-- ═══════════════════════════════════════════════════════════════════════
-- PASO 5: Verificar que los triggers se crearon correctamente
-- ═══════════════════════════════════════════════════════════════════════

SHOW TRIGGERS WHERE `Table` = 'lineas_adjudicadas';

-- Deberías ver 3 triggers:
-- 1. actualizar_licitacion_al_adjudicar (AFTER INSERT)
-- 2. actualizar_licitacion_al_modificar (AFTER UPDATE)
-- 3. actualizar_licitacion_al_eliminar (AFTER DELETE)

-- ═══════════════════════════════════════════════════════════════════════
-- PASO 6: Actualizar las licitaciones existentes (UNA SOLA VEZ)
-- ═══════════════════════════════════════════════════════════════════════
-- Esto marca todas las licitaciones que YA tienen adjudicaciones

UPDATE licitaciones l
INNER JOIN (
    SELECT DISTINCT numero_sicop
    FROM lineas_adjudicadas
) la ON l.numero_sicop = la.numero_sicop
SET
    l.estado = 'adjudicada',
    l.fecha_adjudicacion = CURDATE(),
    l.fecha_ultima_actualizacion = NOW()
WHERE l.estado != 'adjudicada';

-- Ver cuántas se actualizaron
SELECT ROW_COUNT() AS 'Licitaciones marcadas como adjudicadas';

-- ═══════════════════════════════════════════════════════════════════════
-- PASO 7: Verificar el resultado
-- ═══════════════════════════════════════════════════════════════════════

SELECT
    estado,
    COUNT(*) as cantidad,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM licitaciones), 2) as porcentaje
FROM licitaciones
GROUP BY estado
ORDER BY cantidad DESC;

-- ═══════════════════════════════════════════════════════════════════════
-- PRUEBA DEL TRIGGER
-- ═══════════════════════════════════════════════════════════════════════

-- Para probar que funciona, puedes hacer una prueba:

/*
-- 1. Ver el estado actual de una licitación
SELECT id, numero_sicop, estado, fecha_adjudicacion
FROM licitaciones
WHERE numero_sicop = 'TU_NUMERO_SICOP_AQUI'  -- Cambiar por un número real
LIMIT 1;

-- 2. Insertar una línea adjudicada de prueba
INSERT INTO lineas_adjudicadas (numero_sicop, descripcion, monto_adjudicado)
VALUES ('TU_NUMERO_SICOP_AQUI', 'Prueba de trigger', 1000.00);

-- 3. Verificar que el estado cambió automáticamente
SELECT id, numero_sicop, estado, fecha_adjudicacion
FROM licitaciones
WHERE numero_sicop = 'TU_NUMERO_SICOP_AQUI';

-- 4. Eliminar la prueba (opcional)
DELETE FROM lineas_adjudicadas
WHERE numero_sicop = 'TU_NUMERO_SICOP_AQUI'
AND descripcion = 'Prueba de trigger';
*/

-- ═══════════════════════════════════════════════════════════════════════
-- NOTAS IMPORTANTES
-- ═══════════════════════════════════════════════════════════════════════

/*
✅ QUÉ HACE ESTE TRIGGER:

1. CUANDO SE INSERTA una nueva línea en lineas_adjudicadas:
   - Busca la licitación con ese numero_sicop
   - La marca como 'adjudicada'
   - Le pone la fecha de adjudicación

2. CUANDO SE ACTUALIZA una línea existente:
   - Actualiza la fecha de última modificación

3. CUANDO SE ELIMINA una línea:
   - Verifica si quedan más líneas adjudicadas
   - Si no quedan, desmarca la licitación (la pone como 'cerrada')

⚠️ IMPORTANTE:
- Los triggers se ejecutan AUTOMÁTICAMENTE, no necesitas hacer nada
- Funcionan desde el momento que los crees
- Si importas datos masivos, se ejecutarán para cada inserción
- No afectan el rendimiento significativamente

🔧 MANTENIMIENTO:
- Para ver los triggers: SHOW TRIGGERS;
- Para eliminar un trigger: DROP TRIGGER nombre_trigger;
- Para modificar un trigger: Elimínalo y créalo de nuevo

📊 RENDIMIENTO:
- Los triggers son rápidos (microsegundos)
- Se ejecutan en la misma transacción que el INSERT
- Si falla el trigger, falla también el INSERT (integridad de datos)
*/

-- ═══════════════════════════════════════════════════════════════════════
-- FIN DEL SCRIPT
-- ═══════════════════════════════════════════════════════════════════════

SELECT
    '✅ Triggers creados correctamente' AS resultado,
    'Ahora las licitaciones se actualizarán automáticamente' AS mensaje;
