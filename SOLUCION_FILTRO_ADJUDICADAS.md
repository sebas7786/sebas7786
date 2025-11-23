# 🎯 SOLUCIÓN: Filtro de Licitaciones Adjudicadas

## 📋 DIAGNÓSTICO

Según el análisis de tu base de datos:

✅ **Tienes:**
- Tabla `licitaciones` con 21,863 registros
- Tabla `lineas_adjudicadas` con 11,336 líneas de **1,710 procedimientos únicos**
- Columna `estado` tipo ENUM con valores: 'abierta', 'cerrada', 'adjudicada'
- Columna `fecha_adjudicacion` (existe pero está vacía)

❌ **Problema:**
- **TODAS** las licitaciones tienen `estado = 'abierta'`
- La columna `fecha_adjudicacion` está **VACÍA**
- Las adjudicaciones están en la tabla `lineas_adjudicadas`, pero **NO** están marcadas en la tabla `licitaciones`

**Resultado:** El filtro de adjudicadas no encuentra nada porque busca en `estado`, que siempre es 'abierta'.

---

## 🛠️ SOLUCIONES

Tienes **2 opciones** (elige la que prefieras):

### **OPCIÓN 1: Actualizar la Base de Datos** ✅ RECOMENDADO

**Ventajas:**
- ✅ Solución permanente
- ✅ Más rápida (no hace JOIN cada vez)
- ✅ Más fácil de mantener
- ✅ El dashboard funciona sin cambios

**Desventajas:**
- ⚠️ Requiere ejecutar SQL una vez
- ⚠️ Necesitas acceso a phpMyAdmin

---

### **OPCIÓN 2: Modificar el Dashboard**

**Ventajas:**
- ✅ No modifica la base de datos
- ✅ Cambios solo en PHP

**Desventajas:**
- ⚠️ Más lento (hace consulta extra por cada licitación)
- ⚠️ Tienes que modificar código
- ⚠️ Si agregas más adjudicaciones, se detectan automáticamente

---

## 📝 OPCIÓN 1: Actualizar Base de Datos (Recomendado)

### **Paso 1: Abrir phpMyAdmin**

1. Ve a tu panel de hosting (cPanel, Plesk, etc.)
2. Abre **phpMyAdmin**
3. Selecciona tu base de datos

### **Paso 2: Ejecutar SQL de Preview**

Antes de hacer cambios, ve cuántas licitaciones se actualizarán:

```sql
SELECT
    COUNT(DISTINCT l.id) as total_a_actualizar,
    'licitaciones serán marcadas como adjudicadas' as mensaje
FROM licitaciones l
INNER JOIN lineas_adjudicadas la ON l.numero_sicop = la.numero_sicop
WHERE l.estado != 'adjudicada';
```

**Resultado esperado:** Algo como "1710 licitaciones serán marcadas como adjudicadas"

### **Paso 3: Ver Ejemplos**

Ver algunas de las que se van a actualizar:

```sql
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
```

### **Paso 4: Actualizar (ELIGE UNA)**

#### **OPCIÓN A: Solo cambiar estado**

```sql
UPDATE licitaciones l
INNER JOIN lineas_adjudicadas la ON l.numero_sicop = la.numero_sicop
SET l.estado = 'adjudicada'
WHERE l.estado != 'adjudicada';
```

#### **OPCIÓN B: Cambiar estado + agregar fecha** (Mejor)

```sql
UPDATE licitaciones l
INNER JOIN (
    SELECT
        numero_sicop,
        MIN(created_at) as primera_adjudicacion
    FROM lineas_adjudicadas
    GROUP BY numero_sicop
) la ON l.numero_sicop = la.numero_sicop
SET
    l.estado = 'adjudicada',
    l.fecha_adjudicacion = DATE(la.primera_adjudicacion)
WHERE l.estado != 'adjudicada';
```

**Nota:** Si `lineas_adjudicadas` no tiene columna `created_at`, usa solo la OPCIÓN A.

### **Paso 5: Verificar**

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
- `abierta`: ~20,000
- `adjudicada`: ~1,710
- `cerrada`: (las que tengan)

### **Paso 6: Probar Dashboard**

Ve a tu dashboard y selecciona el filtro **"Adjudicadas"**.

**Ahora deberías ver las 1,710 licitaciones adjudicadas.** ✅

### **Paso 7 (OPCIONAL): Crear Trigger Automático**

Para que las futuras adjudicaciones se marquen automáticamente:

```sql
DELIMITER $$

CREATE TRIGGER actualizar_estado_adjudicada
AFTER INSERT ON lineas_adjudicadas
FOR EACH ROW
BEGIN
    UPDATE licitaciones
    SET
        estado = 'adjudicada',
        fecha_adjudicacion = COALESCE(NEW.fecha_adjudicacion, CURDATE())
    WHERE numero_sicop = NEW.numero_sicop
    AND estado != 'adjudicada';
END$$

DELIMITER ;
```

---

## 🔧 OPCIÓN 2: Modificar Dashboard

Si prefieres **NO** tocar la base de datos:

### **Paso 1: Modificar función calcularEstadoReal**

Abre `dashboard.php` y busca (línea ~40-80):

```php
function calcularEstadoReal($licitacion) {
    $fecha_actual = new DateTime();
```

**Reemplázala con:**

```php
function calcularEstadoReal($licitacion) {
    global $pdo; // ⬅️ AGREGAR ESTA LÍNEA

    $fecha_actual = new DateTime();

    // NUEVO: Verificar tabla lineas_adjudicadas
    if (isset($pdo) && !empty($licitacion['numero_sicop'])) {
        try {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as tiene_adj
                FROM lineas_adjudicadas
                WHERE numero_sicop = ?
                LIMIT 1
            ");
            $stmt->execute([$licitacion['numero_sicop']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && $result['tiene_adj'] > 0) {
                return 'adjudicada';
            }
        } catch (PDOException $e) {
            // Continuar con otros métodos
        }
    }

    // ... RESTO DEL CÓDIGO ORIGINAL (mantener todo igual) ...
```

### **Paso 2: Hacer $pdo global**

Busca (línea ~310):

```php
try {
    require_once __DIR__ . '/../config/db.php';

    if (!isset($pdo) && isset($conn)) {
        $pdo = $conn;
    }
```

**Y agrega DESPUÉS:**

```php
    // Hacer $pdo accesible globalmente
    global $pdo;
```

### **Paso 3: Probar**

Guarda y recarga tu dashboard. El filtro de adjudicadas debería funcionar.

**Desventaja:** Hace 1 consulta SQL extra por cada licitación mostrada (más lento).

---

## ✅ VERIFICACIÓN FINAL

Después de aplicar cualquiera de las 2 opciones:

1. Ve a: `https://licitacionesya.com/user/dashboard.php`
2. Click en el filtro **"Estado" → "Adjudicadas"**
3. **Deberías ver ~1,710 licitaciones**

Si aparecen 0 resultados, revisa:
- ¿Ejecutaste el SQL correctamente?
- ¿Guardaste los cambios en dashboard.php?
- ¿Agregaste `global $pdo;`?

---

## 🎓 ¿Cuál opción elegir?

| Criterio | Opción 1 (SQL) | Opción 2 (PHP) |
|----------|----------------|----------------|
| **Velocidad** | ⚡ Muy rápido | 🐌 Lento |
| **Complejidad** | ✅ Fácil (1 SQL) | ⚠️ Moderado (editar PHP) |
| **Permanente** | ✅ Sí | ⚠️ Temporal |
| **Mantenimiento** | ✅ Bajo | ⚠️ Alto |
| **Recomendado** | ✅ **SÍ** | ❌ Solo si no puedes ejecutar SQL |

**Mi recomendación:** Usa **OPCIÓN 1** (actualizar la BD con SQL).

---

## 📞 Ayuda Adicional

Si tienes problemas:

1. **Ejecuta el diagnóstico:** `dashboard_diagnostico.php`
2. **Revisa el archivo:** `actualizar_licitaciones_adjudicadas.sql` (tiene comentarios detallados)
3. **Mira el ejemplo:** `fix_dashboard_adjudicadas.php` (código con comentarios)

---

## 🚀 Archivos Creados

- `dashboard_diagnostico.php` - Herramienta de diagnóstico
- `actualizar_licitaciones_adjudicadas.sql` - Script SQL para OPCIÓN 1
- `fix_dashboard_adjudicadas.php` - Código PHP para OPCIÓN 2
- `SOLUCION_FILTRO_ADJUDICADAS.md` - Esta guía

---

¡Listo! Con cualquiera de las 2 opciones, tu filtro de adjudicadas funcionará perfectamente. 🎉
