# ⚡ TRIGGER AUTOMÁTICO PARA ADJUDICACIONES

## 🎯 ¿Qué hace esto?

Cuando tu script de importación agregue una línea a la tabla `lineas_adjudicadas`, **AUTOMÁTICAMENTE**:

✅ La licitación se marca como `estado = 'adjudicada'`
✅ Se pone la fecha de adjudicación
✅ Se actualiza la fecha de última modificación

**No tienes que hacer nada manualmente, todo es automático.**

---

## 📋 INSTALACIÓN (5 minutos)

### **Paso 1: Abrir phpMyAdmin**

1. Ve a tu panel de hosting (cPanel, Plesk, etc.)
2. Click en **phpMyAdmin**
3. Selecciona tu base de datos

### **Paso 2: Ejecutar el Script SQL**

1. Click en la pestaña **"SQL"** (arriba)
2. **Copia TODO el contenido** del archivo `crear_trigger_adjudicaciones.sql`
3. **Pégalo** en el cuadro de texto
4. Click en **"Continuar"** o **"Ejecutar"**

**Deberías ver:**
```
✓ Query OK, 0 rows affected
✓ Query OK, 0 rows affected
✓ Query OK, 0 rows affected
```

### **Paso 3: Verificar que Funcionó**

En phpMyAdmin, ejecuta este SQL:

```sql
SHOW TRIGGERS WHERE `Table` = 'lineas_adjudicadas';
```

**Deberías ver 3 triggers:**
- `actualizar_licitacion_al_adjudicar` (AFTER INSERT)
- `actualizar_licitacion_al_modificar` (AFTER UPDATE)
- `actualizar_licitacion_al_eliminar` (AFTER DELETE)

✅ Si los ves, **está instalado correctamente**.

---

## 🧪 PRUEBA RÁPIDA

Para verificar que funciona:

```sql
-- 1. Ver una licitación que tiene adjudicaciones
SELECT l.numero_sicop, l.estado, l.fecha_adjudicacion,
       (SELECT COUNT(*) FROM lineas_adjudicadas WHERE numero_sicop = l.numero_sicop) as lineas_adj
FROM licitaciones l
WHERE l.numero_sicop IN (SELECT DISTINCT numero_sicop FROM lineas_adjudicadas LIMIT 1);

-- 2. Debería mostrar estado='adjudicada' y tener líneas adjudicadas
```

---

## ✅ ¿Cómo saber si está funcionando?

**ANTES del trigger:**
- Importas datos a `lineas_adjudicadas`
- La tabla `licitaciones` sigue con `estado = 'abierta'` ❌
- Tienes que actualizar manualmente

**DESPUÉS del trigger:**
- Importas datos a `lineas_adjudicadas`
- La tabla `licitaciones` se actualiza AUTOMÁTICAMENTE a `estado = 'adjudicada'` ✅
- No haces nada más

---

## 🔄 ¿Qué hace cada trigger?

### **Trigger 1: Al INSERTAR adjudicación**
```
Script importa línea → lineas_adjudicadas
         ↓
Trigger detecta nuevo registro
         ↓
Busca licitación por numero_sicop
         ↓
Actualiza estado = 'adjudicada' ✅
```

### **Trigger 2: Al ACTUALIZAR adjudicación**
```
Modificas una línea existente
         ↓
Trigger actualiza fecha_ultima_actualizacion
```

### **Trigger 3: Al ELIMINAR adjudicación**
```
Eliminas una línea adjudicada
         ↓
Trigger cuenta cuántas quedan
         ↓
Si no quedan más, marca licitación como 'cerrada'
```

---

## 📊 ¿Afecta el rendimiento?

**NO.** Los triggers son extremadamente rápidos:

- ⚡ Se ejecutan en **microsegundos**
- 🔒 Se ejecutan en la misma transacción (si falla el INSERT, falla el trigger también)
- 📈 Si importas 1,000 registros, cada uno se procesa en < 0.001 segundos

---

## 🛠️ Actualizar Licitaciones Existentes

El script también incluye una actualización ÚNICA para marcar las licitaciones que **YA** tienen adjudicaciones:

```sql
-- Esto se ejecuta automáticamente cuando instalas el trigger
UPDATE licitaciones l
INNER JOIN (SELECT DISTINCT numero_sicop FROM lineas_adjudicadas) la
ON l.numero_sicop = la.numero_sicop
SET l.estado = 'adjudicada'
WHERE l.estado != 'adjudicada';
```

**Resultado esperado:**
- ~1,710 licitaciones marcadas como adjudicadas
- Dashboard muestra correctamente las adjudicaciones

---

## 🔍 Verificar Estado Actual

Después de instalar, verifica con:

```sql
SELECT
    estado,
    COUNT(*) as cantidad,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM licitaciones), 2) as porcentaje
FROM licitaciones
GROUP BY estado
ORDER BY cantidad DESC;
```

**Deberías ver:**
```
estado      | cantidad | porcentaje
------------|----------|------------
abierta     | ~20,000  | ~92%
adjudicada  | ~1,710   | ~8%
cerrada     | ...      | ...
```

---

## ⚠️ Solución de Problemas

### **El trigger no se creó**

**Error:** "Syntax error near 'DELIMITER'"

**Solución:** phpMyAdmin no entiende `DELIMITER`. Usa este método alternativo:

1. Abre el archivo SQL en un editor de texto
2. Elimina todas las líneas que digan `DELIMITER $$` y `DELIMITER ;`
3. Cambia `END$$` por `END;` en cada trigger
4. Ejecuta de nuevo

O ejecuta cada trigger por separado (uno a la vez).

### **No aparecen las adjudicadas en el dashboard**

1. Verifica que el trigger se creó: `SHOW TRIGGERS;`
2. Verifica que las licitaciones se actualizaron:
   ```sql
   SELECT COUNT(*) FROM licitaciones WHERE estado = 'adjudicada';
   ```
3. Si dice 0, ejecuta manualmente el UPDATE del Paso 6

### **Error de permisos**

**Error:** "Access denied for user... TRIGGER"

**Solución:** Tu usuario de MySQL necesita permiso `TRIGGER`. Contacta a tu hosting o ejecuta:

```sql
GRANT TRIGGER ON tu_base_datos.* TO 'tu_usuario'@'localhost';
FLUSH PRIVILEGES;
```

---

## 🎓 Comandos Útiles

```sql
-- Ver todos los triggers
SHOW TRIGGERS;

-- Ver triggers de una tabla específica
SHOW TRIGGERS WHERE `Table` = 'lineas_adjudicadas';

-- Eliminar un trigger
DROP TRIGGER IF EXISTS actualizar_licitacion_al_adjudicar;

-- Verificar cuántas licitaciones están adjudicadas
SELECT COUNT(*) FROM licitaciones WHERE estado = 'adjudicada';

-- Ver licitaciones adjudicadas recientemente
SELECT numero_sicop, titulo, fecha_adjudicacion
FROM licitaciones
WHERE estado = 'adjudicada'
ORDER BY fecha_adjudicacion DESC
LIMIT 10;
```

---

## ✅ Checklist de Instalación

- [ ] Abrir phpMyAdmin
- [ ] Ejecutar `crear_trigger_adjudicaciones.sql`
- [ ] Verificar con `SHOW TRIGGERS;`
- [ ] Verificar que hay licitaciones adjudicadas (SELECT COUNT...)
- [ ] Probar el dashboard (filtro de adjudicadas)
- [ ] Importar nuevos datos y verificar que se actualizan automáticamente

---

## 🚀 Resultado Final

**ANTES:**
```
Importas adjudicaciones
    ↓
Dashboard muestra 0 adjudicadas ❌
    ↓
Tienes que actualizar manualmente
```

**DESPUÉS:**
```
Importas adjudicaciones
    ↓
Trigger actualiza automáticamente ⚡
    ↓
Dashboard muestra adjudicadas correctamente ✅
```

---

## 📞 ¿Necesitas Ayuda?

Si tienes problemas:

1. Ejecuta `SHOW TRIGGERS;` y envíame el resultado
2. Ejecuta `SELECT COUNT(*) FROM licitaciones WHERE estado = 'adjudicada';`
3. Dime qué error ves (si hay alguno)

---

**¡Listo! Con esto, todo se actualiza automáticamente. No tendrás que hacer nada manualmente nunca más.** 🎉
