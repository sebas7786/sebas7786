# 🏢 SISTEMA DE PROVEEDORES - LicitacionesYA

## 🎯 ¿Qué hace este sistema?

Sistema completo para gestionar proveedores que participan en licitaciones:

✅ **Registro de proveedores** con información completa
✅ **Importación masiva** desde CSV/Excel
✅ **Estadísticas automáticas** de adjudicaciones
✅ **Filtros avanzados** de búsqueda
✅ **Ranking de proveedores** por monto y cantidad
✅ **Sincronización automática** con adjudicaciones

---

## 📋 INSTALACIÓN (5 minutos)

### **Paso 1: Crear Tabla en Base de Datos**

1. Abre **phpMyAdmin**
2. Selecciona tu base de datos
3. Ve a la pestaña **"SQL"**
4. **Copia y pega** todo el contenido de `crear_tabla_proveedores.sql`
5. Click en **"Continuar"** o **"Ejecutar"**

**Resultado esperado:**
```
✓ Tabla 'proveedores' creada
✓ Trigger automático creado
✓ Vista de ranking creada
✓ X proveedores importados desde lineas_adjudicadas
```

### **Paso 2: Verificar Instalación**

En phpMyAdmin, ejecuta:

```sql
-- Ver estructura de la tabla
DESCRIBE proveedores;

-- Ver cuántos proveedores se importaron
SELECT COUNT(*) FROM proveedores;

-- Ver triggers
SHOW TRIGGERS WHERE `Table` = 'lineas_adjudicadas';
```

**Deberías ver:**
- Tabla `proveedores` con ~30 columnas
- Proveedores importados automáticamente desde adjudicaciones existentes
- Trigger `actualizar_estadisticas_proveedor`

### **Paso 3: Subir Archivos PHP**

Sube estos archivos a tu servidor:

```
/admin/proveedores.php          → Panel de administración
/admin/importar_proveedores.php → Importación masiva
```

**Verifica permisos:**
- Los archivos deben estar en la carpeta `admin/`
- Deben tener permisos de lectura (644)
- La carpeta debe ser accesible solo para administradores

---

## 🚀 USO DEL SISTEMA

### **1. Panel de Administración** (`admin/proveedores.php`)

**URL:** `https://tu-sitio.com/admin/proveedores.php`

**Funciones:**

#### **Estadísticas Generales**
- Total de proveedores
- Proveedores activos/inactivos/inhabilitados
- Total de adjudicaciones
- Monto total adjudicado

#### **Filtros de Búsqueda**
- 🔍 Por cédula o nombre
- Por tipo (Física, Jurídica, Extranjera, Consorcio)
- Por tamaño (Micro, Pequeña, Mediana, Grande)
- Por zona/provincia
- Por estado (Activo, Inactivo, Suspendido, Inhabilitado)

#### **Acciones**
- ➕ **Agregar proveedor** manualmente
- ✏️ **Editar** información del proveedor
- 🗑️ **Eliminar** (solo si no tiene adjudicaciones)
- 📥 **Importar** desde CSV/Excel
- 📤 **Exportar** listado completo

---

### **2. Importación Masiva** (`admin/importar_proveedores.php`)

**URL:** `https://tu-sitio.com/admin/importar_proveedores.php`

#### **Formato del CSV**

**Columnas OBLIGATORIAS:**
```
cedula_proveedor,nombre_proveedor
```

**Columnas OPCIONALES:**
```
tipo_proveedor,tamano_proveedor,fecha_constitucion,fecha_expiracion,
zona_geo_prov,direccion,telefono,email,sitio_web,actividad_economica,
codigo_actividad,estado_proveedor
```

#### **Ejemplo de CSV:**

```csv
cedula_proveedor,nombre_proveedor,tipo_proveedor,tamano_proveedor,zona_geo_prov,telefono,email
3-101-123456,Empresa Ejemplo S.A.,Jurídica,Mediana,San José,2222-3333,contacto@ejemplo.com
1-0234-5678,Juan Pérez Solano,Física,Micro,Heredia,8888-9999,juan@correo.com
3-102-654321,Constructora ABC S.A.,Jurídica,Grande,Cartago,2444-5555,info@abc.com
```

#### **Crear CSV desde Excel:**

1. Abre Excel
2. Crea una tabla con las columnas
3. Llena los datos
4. **Guardar como** → **CSV (delimitado por comas) (*.csv)**
5. Importa el archivo en `importar_proveedores.php`

#### **Comportamiento:**
- Si la **cédula YA existe**: se **actualiza** el registro
- Si la **cédula es nueva**: se **inserta** el registro
- **Errores:** se muestran al final (filas inválidas)

---

## 📊 CAMPOS DE LA TABLA

### **Identificación**
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `cedula_proveedor` | VARCHAR(50) | Cédula jurídica o física **(ÚNICO)** |
| `nombre_proveedor` | VARCHAR(255) | Nombre o razón social |

### **Clasificación**
| Campo | Tipo | Valores |
|-------|------|---------|
| `tipo_proveedor` | ENUM | Física, Jurídica, Extranjera, Consorcio, Otro |
| `tamano_proveedor` | ENUM | Micro, Pequeña, Mediana, Grande, No Especificado |

### **Fechas**
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `fecha_constitucion` | DATE | Fecha de constitución de la empresa |
| `fecha_expiracion` | DATE | Fecha de expiración de registro/licencia |
| `fecha_registro` | TIMESTAMP | Fecha de registro en el sistema (automático) |
| `fecha_actualizacion` | TIMESTAMP | Última actualización (automático) |

### **Ubicación**
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `zona_geo_prov` | VARCHAR(100) | Provincia o zona geográfica |
| `direccion` | TEXT | Dirección completa |

### **Contacto**
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `telefono` | VARCHAR(50) | Teléfono de contacto |
| `email` | VARCHAR(255) | Correo electrónico |
| `sitio_web` | VARCHAR(255) | Sitio web |

### **Información Adicional**
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `actividad_economica` | VARCHAR(255) | Descripción de actividad económica |
| `codigo_actividad` | VARCHAR(50) | Código CIIU o similar |

### **Estado**
| Campo | Tipo | Valores |
|-------|------|---------|
| `estado_proveedor` | ENUM | Activo, Inactivo, Suspendido, Inhabilitado |
| `motivo_inhabilitacion` | TEXT | Razón de inhabilitación (si aplica) |

### **Estadísticas (Automáticas)**
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `total_adjudicaciones` | INT | Total de licitaciones adjudicadas |
| `monto_total_adjudicado` | DECIMAL(20,2) | Monto total adjudicado histórico |
| `ultima_adjudicacion` | DATE | Fecha de última adjudicación |

### **Metadatos**
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `registrado_por` | INT | ID del usuario admin que registró |
| `notas` | TEXT | Notas internas sobre el proveedor |

---

## ⚡ TRIGGER AUTOMÁTICO

### **¿Qué hace?**

Cuando se **inserta una línea** en `lineas_adjudicadas`:

1. **Busca** el proveedor por `cedula_proveedor`
2. **Actualiza** sus estadísticas:
   - `total_adjudicaciones += 1`
   - `monto_total_adjudicado += monto_adjudicado`
   - `ultima_adjudicacion = fecha_adjudicacion`
3. Si el proveedor **NO existe**, lo **crea automáticamente**

### **Ejemplo:**

```sql
-- Insertas una adjudicación
INSERT INTO lineas_adjudicadas (numero_sicop, cedula_proveedor, nombre_proveedor, monto_adjudicado)
VALUES ('2024LA-000001-00001', '3-101-123456', 'Empresa ABC', 5000000.00);

-- El trigger automáticamente:
-- 1. Busca proveedor con cédula 3-101-123456
-- 2. Si existe: actualiza total_adjudicaciones + monto_total_adjudicado
-- 3. Si NO existe: lo crea con esos datos
```

**Resultado:** No tienes que actualizar manualmente, todo es automático. ⚡

---

## 📈 VISTA DE RANKING

La instalación crea automáticamente una **vista** llamada `ranking_proveedores`:

```sql
-- Ver top 10 proveedores por monto
SELECT * FROM ranking_proveedores LIMIT 10;

-- Ver ranking de un proveedor específico
SELECT * FROM ranking_proveedores WHERE cedula_proveedor = '3-101-123456';
```

**Campos de la vista:**
- `cedula_proveedor`
- `nombre_proveedor`
- `tipo_proveedor`
- `tamano_proveedor`
- `zona_geo_prov`
- `total_adjudicaciones`
- `monto_total_adjudicado`
- `ultima_adjudicacion`
- `ranking_por_monto` (posición por monto)
- `ranking_por_cantidad` (posición por cantidad)

---

## 🔍 CONSULTAS ÚTILES

### **Ver todos los proveedores activos**

```sql
SELECT cedula_proveedor, nombre_proveedor, total_adjudicaciones, monto_total_adjudicado
FROM proveedores
WHERE estado_proveedor = 'Activo'
ORDER BY monto_total_adjudicado DESC;
```

### **Top 10 por monto adjudicado**

```sql
SELECT
    cedula_proveedor,
    nombre_proveedor,
    total_adjudicaciones,
    monto_total_adjudicado,
    zona_geo_prov
FROM proveedores
ORDER BY monto_total_adjudicado DESC
LIMIT 10;
```

### **Proveedores por zona**

```sql
SELECT
    zona_geo_prov,
    COUNT(*) as cantidad_proveedores,
    SUM(total_adjudicaciones) as total_adj,
    SUM(monto_total_adjudicado) as total_monto
FROM proveedores
GROUP BY zona_geo_prov
ORDER BY total_monto DESC;
```

### **Proveedores sin adjudicaciones**

```sql
SELECT cedula_proveedor, nombre_proveedor, fecha_registro
FROM proveedores
WHERE total_adjudicaciones = 0
ORDER BY fecha_registro DESC;
```

### **Actualizar estadísticas manualmente (si es necesario)**

```sql
-- Recalcular estadísticas de un proveedor
UPDATE proveedores p
INNER JOIN (
    SELECT
        cedula_proveedor,
        COUNT(*) as total,
        SUM(monto_adjudicado) as total_monto,
        MAX(fecha_adjudicacion) as ultima_fecha
    FROM lineas_adjudicadas
    WHERE cedula_proveedor = '3-101-123456'
) la ON p.cedula_proveedor = la.cedula_proveedor
SET
    p.total_adjudicaciones = la.total,
    p.monto_total_adjudicado = la.total_monto,
    p.ultima_adjudicacion = la.ultima_fecha;
```

---

## 🎨 PERSONALIZACIÓN

### **Agregar un campo personalizado**

1. **Agregar columna en MySQL:**

```sql
ALTER TABLE proveedores
ADD COLUMN campo_nuevo VARCHAR(100) NULL
AFTER zona_geo_prov;
```

2. **Actualizar `proveedores.php`:**
   - Agregar campo al formulario (modal)
   - Agregar campo al INSERT/UPDATE
   - Agregar columna a la tabla (opcional)

3. **Actualizar `importar_proveedores.php`:**
   - Agregar campo al `$mapa_columnas`
   - Agregar campo al INSERT/UPDATE

---

## 🛡️ SEGURIDAD

### **Control de Acceso**

Ambos archivos verifican que el usuario sea **admin**:

```php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
```

### **Protección SQL Injection**

Todas las consultas usan **prepared statements**:

```php
$stmt = $pdo->prepare("SELECT * FROM proveedores WHERE cedula_proveedor = ?");
$stmt->execute([$cedula]);
```

### **Validación de Datos**

- **Cédula única:** No permite duplicados
- **Valores ENUM:** Valida tipos, tamaños, estados
- **Fechas:** Valida formato antes de insertar
- **Emails:** Validación de formato

---

## ⚠️ SOLUCIÓN DE PROBLEMAS

### **No aparecen los proveedores**

1. Verifica que la tabla se creó:
   ```sql
   SHOW TABLES LIKE 'proveedores';
   ```

2. Verifica que hay datos:
   ```sql
   SELECT COUNT(*) FROM proveedores;
   ```

3. Si dice 0, ejecuta la importación automática:
   ```sql
   -- Ver línea 154-175 de crear_tabla_proveedores.sql
   INSERT INTO proveedores (cedula_proveedor, nombre_proveedor, ...)
   SELECT ... FROM lineas_adjudicadas ...
   ```

### **Error al importar CSV**

- **Encoding:** Asegúrate que el CSV esté en UTF-8
- **Separador:** Debe ser **coma** (,)
- **Primera fila:** Debe tener nombres de columnas
- **Columnas obligatorias:** cedula_proveedor, nombre_proveedor

### **El trigger no funciona**

1. Verifica que existe:
   ```sql
   SHOW TRIGGERS WHERE `Table` = 'lineas_adjudicadas';
   ```

2. Si no existe, ejecuta de nuevo las líneas 115-147 de `crear_tabla_proveedores.sql`

### **Estadísticas incorrectas**

Recalcular manualmente:

```sql
UPDATE proveedores p
INNER JOIN (
    SELECT
        cedula_proveedor,
        COUNT(*) as total,
        SUM(COALESCE(monto_adjudicado, 0)) as total_monto,
        MAX(fecha_adjudicacion) as ultima_fecha
    FROM lineas_adjudicadas
    GROUP BY cedula_proveedor
) la ON p.cedula_proveedor = la.cedula_proveedor
SET
    p.total_adjudicaciones = la.total,
    p.monto_total_adjudicado = la.total_monto,
    p.ultima_adjudicacion = la.ultima_fecha;
```

---

## 📞 SOPORTE

### **Archivos del Sistema**

1. `crear_tabla_proveedores.sql` - Script de instalación de base de datos
2. `admin/proveedores.php` - Panel de administración
3. `admin/importar_proveedores.php` - Importación masiva
4. `GUIA_SISTEMA_PROVEEDORES.md` - Esta guía

### **Logs de Importación**

Si hay errores en la importación, se muestran en pantalla con:
- Número de fila con error
- Descripción del error
- Máximo 50 errores (si hay más, se dice cuántos)

---

## ✅ CHECKLIST DE INSTALACIÓN

- [ ] Ejecutar `crear_tabla_proveedores.sql` en phpMyAdmin
- [ ] Verificar que la tabla se creó: `DESCRIBE proveedores;`
- [ ] Verificar que hay proveedores: `SELECT COUNT(*) FROM proveedores;`
- [ ] Verificar trigger: `SHOW TRIGGERS WHERE 'Table' = 'lineas_adjudicadas';`
- [ ] Subir `admin/proveedores.php` al servidor
- [ ] Subir `admin/importar_proveedores.php` al servidor
- [ ] Probar acceso: `https://tu-sitio.com/admin/proveedores.php`
- [ ] Probar importación con CSV de prueba
- [ ] Verificar que las estadísticas se actualizan automáticamente

---

## 🚀 PRÓXIMOS PASOS

Una vez instalado el sistema:

1. **Importa proveedores** desde CSV (si tienes un listado)
2. **Verifica** que las adjudicaciones existentes se vincularon
3. **Prueba** agregar un proveedor manualmente
4. **Verifica** que el trigger funciona (insertar línea adjudicada y ver si actualiza)
5. **Usa** los filtros para buscar proveedores
6. **Exporta** el listado completo (función a implementar)

---

¡Listo! Ahora tienes un **sistema completo de gestión de proveedores** con actualización automática de estadísticas. 🎉

**Todo funciona automáticamente, no necesitas hacer mantenimiento manual.** ⚡
