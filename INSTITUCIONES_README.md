# 🏢 Sistema de Instituciones Proveedoras

Sistema de importación y gestión de instituciones proveedoras/compradoras del Observatorio SICOP.

## 📋 Descripción

Este sistema permite importar y gestionar información de instituciones que participan en procesos de licitación, facilitando:
- Enlazar cédula de institución con nombre completo
- Mostrar nombre de institución compradora en carteles
- Filtrar licitaciones por institución
- Análisis geográfico por provincia

## 🗄️ Estructura de Datos

### Tabla: `instituciones_proveedoras`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT | ID autoincremental (PK) |
| `cedula` | VARCHAR(20) | Cédula de la institución (UNIQUE) |
| `nombre_institucion` | VARCHAR(255) | Nombre completo |
| `direccion` | TEXT | Dirección física |
| `telefono` | VARCHAR(50) | Teléfono de contacto |
| `representante` | VARCHAR(255) | Nombre del representante |
| `codigo_postal` | VARCHAR(20) | Código postal |
| `provincia` | VARCHAR(100) | Provincia |
| `canton` | VARCHAR(100) | Cantón |
| `distrito` | VARCHAR(100) | Distrito |
| `fecha_importacion` | TIMESTAMP | Fecha de primera importación |
| `actualizado` | TIMESTAMP | Última actualización |

## 🚀 Instalación

### Paso 1: Crear la tabla

```bash
mysql -u usuario -p nombre_bd < sql/crear_tabla_instituciones_proveedoras.sql
```

O ejecuta directamente el SQL:
```sql
source /ruta/a/sql/crear_tabla_instituciones_proveedoras.sql
```

### Paso 2: Verificar la instalación

Accede a:
```
https://tu-dominio.com/admin/probar_instituciones.php
```

Deberías ver el dashboard con 0 instituciones.

## 📥 Importación de Datos

### Desde la interfaz web (Recomendado)

1. Accede a: `https://tu-dominio.com/admin/importador_instituciones.php`
2. Selecciona el archivo CSV descargado del Observatorio SICOP
3. Haz clic en "🚀 INICIAR IMPORTACIÓN"
4. Espera a que se complete el proceso
5. Verifica los resultados en el dashboard

### Formato del CSV

El archivo debe tener las siguientes columnas (en este orden):

```
Cédula, Nombre Institucion, Direccion, Telefono, Representante, Codigo Postal, Provincia, Canton, Distrito
```

**Ejemplo:**
```csv
Cédula;Nombre Institucion;Direccion;Telefono;Representante;Codigo Postal;Provincia;Canton;Distrito
3-101-654321;MINISTERIO DE EDUCACIÓN PÚBLICA;San José Centro;2222-1111;Juan Pérez;10101;San José;San José;Carmen
2-100-123456;CAJA COSTARRICENSE DE SEGURO SOCIAL;Avenida 14;2539-0000;María González;10103;San José;San José;Catedral
```

### Características del Importador

- ✅ **Auto-detección de encoding** (UTF-8, ISO-8859-1, Windows-1252)
- ✅ **Auto-detección de delimitador** (`;` o `,`)
- ✅ **Actualización automática** (ON DUPLICATE KEY UPDATE)
- ✅ **Log detallado** del proceso
- ✅ **Estadísticas en tiempo real**

## 🔗 Integración con Licitaciones

### Enlazar instituciones con licitaciones

Una vez importadas las instituciones, puedes enlazarlas con licitaciones usando:

```php
// En licitacion_detalle.php
$stmt = $conn->prepare("
    SELECT l.*,
           ip.nombre_institucion,
           ip.provincia,
           ip.telefono
    FROM licitaciones l
    LEFT JOIN instituciones_proveedoras ip ON l.cedula_institucion = ip.cedula
    WHERE l.id = :id
");
```

### Filtrar por institución

```php
// En dashboard o búsqueda
$stmt = $conn->prepare("
    SELECT l.*, ip.nombre_institucion
    FROM licitaciones l
    LEFT JOIN instituciones_proveedoras ip ON l.cedula_institucion = ip.cedula
    WHERE ip.cedula = :cedula_institucion
    ORDER BY l.fecha_apertura DESC
");
```

## 📊 Verificación y Estadísticas

### Ver estadísticas generales

Accede a: `https://tu-dominio.com/admin/probar_instituciones.php`

Verás:
- Total de instituciones importadas
- Instituciones con nombre completo
- Instituciones con dirección
- Instituciones con teléfono
- Distribución por provincia (Top 10)
- Últimas 20 instituciones importadas

### Consultas SQL útiles

```sql
-- Total de instituciones
SELECT COUNT(*) FROM instituciones_proveedoras;

-- Instituciones por provincia
SELECT provincia, COUNT(*) as total
FROM instituciones_proveedoras
WHERE provincia IS NOT NULL
GROUP BY provincia
ORDER BY total DESC;

-- Buscar institución por cédula
SELECT * FROM instituciones_proveedoras WHERE cedula = '3-101-654321';

-- Buscar institución por nombre
SELECT * FROM instituciones_proveedoras WHERE nombre_institucion LIKE '%EDUCACIÓN%';
```

## 🔧 Mantenimiento

### Actualizar instituciones existentes

El importador usa `ON DUPLICATE KEY UPDATE`, por lo que:
- Si la cédula ya existe → **ACTUALIZA** los datos
- Si la cédula no existe → **INSERTA** nueva institución

Puedes reimportar el CSV completo sin problemas de duplicados.

### Limpiar instituciones antiguas

```sql
-- Eliminar instituciones sin nombre
DELETE FROM instituciones_proveedoras WHERE nombre_institucion IS NULL;

-- Eliminar instituciones importadas antes de cierta fecha
DELETE FROM instituciones_proveedoras WHERE fecha_importacion < '2024-01-01';
```

## 📁 Archivos del Sistema

```
/sql/
  └── crear_tabla_instituciones_proveedoras.sql    # Script de creación de tabla

/admin/
  ├── importador_instituciones.php                 # Importador web con formulario
  └── probar_instituciones.php                     # Dashboard de verificación

/INSTITUCIONES_README.md                            # Esta documentación
```

## 🐛 Solución de Problemas

### Error: "Tabla no existe"
**Solución:** Ejecuta el script SQL de creación de tabla primero.

### Error: "HTTP ERROR 500"
**Solución:** Verifica que:
- `config/db.php` existe y tiene las credenciales correctas
- La carpeta `/admin/` tiene permisos correctos
- PHP tiene permisos para leer archivos temporales ($_FILES)

### Las instituciones no se importan
**Solución:** Verifica que:
- El CSV tiene el header correcto
- La columna de cédula no está vacía
- El archivo tiene encoding UTF-8 o ISO-8859-1

### Caracteres raros en nombres (Ã©, Ã±, etc.)
**Solución:** El importador auto-detecta el encoding. Si persiste:
- Abre el CSV en un editor de texto
- Guárdalo como UTF-8
- Vuelve a importar

## 📚 Próximos Pasos

1. ✅ Importar instituciones desde CSV del Observatorio SICOP
2. 🔲 Integrar con importador de licitaciones para auto-completar nombres
3. 🔲 Agregar filtro por institución en dashboard
4. 🔲 Crear reportes por institución compradora
5. 🔲 Agregar búsqueda avanzada de instituciones

## 📞 Soporte

Para más información sobre el formato de datos del Observatorio SICOP:
- https://observatoriosicop.ogp.pr.gov/

---

**Última actualización:** 2024-11-16
**Versión:** 1.0
