# 🏆 Sistema de Líneas Adjudicadas

Sistema para importar y visualizar las líneas adjudicadas de licitaciones SICOP.

## 🎯 Propósito

Permite registrar qué líneas fueron adjudicadas, a qué proveedor, por cuánto dinero, cantidades adjudicadas, etc.

## 📦 Archivos Creados

```
sql/tabla_lineas_adjudicadas.sql           - Estructura de base de datos
admin/importar_lineas_adjudicadas.php      - Importador web
admin/verificar_lineas_adjudicadas.php     - Dashboard de estadísticas
```

## 🚀 Instalación

### Paso 1: Crear la tabla en la base de datos

```sql
-- Ejecuta este comando o usa phpMyAdmin
mysql -u usuario -p nombre_bd < sql/tabla_lineas_adjudicadas.sql
```

### Paso 2: Importar datos

1. Descarga **LineasAdjudicadas.csv** del Observatorio SICOP
2. Accede a: `admin/importar_lineas_adjudicadas.php`
3. Sube el archivo CSV
4. Haz clic en "🚀 INICIAR IMPORTACIÓN"

### Paso 3: Ver estadísticas

Accede a: `admin/verificar_lineas_adjudicadas.php`

## 📋 Formato del CSV

El archivo **LineasAdjudicadas.csv** debe tener estos campos:

```
NRO_SICOP
NRO_OFERTA
CODIGO_PRODUCTO
NRO_LINEA
NRO_ACTO
CEDULA_PROVEEDOR
CANTIDAD_ADJUDICADA
PRECIO_UNITARIO_ADJUDICADO
TIPO_MONEDA
DESCUENTO
IVA
OTROS_IMPUESTOS
ACARREOS
TIPO_CAMBIO_CRC
TIPO_CAMBIO_DOLAR
```

**Total: 15 campos**

## 🗄️ Estructura de la Tabla

### Campos Principales:

| Campo | Tipo | Descripción |
|---|---|---|
| `numero_sicop` | VARCHAR(20) | Número de licitación SICOP |
| `numero_oferta` | VARCHAR(50) | Número de oferta |
| `numero_linea` | INT | Número de línea |
| `numero_acto` | VARCHAR(50) | Número del acto administrativo |
| `cedula_proveedor` | VARCHAR(20) | Cédula del proveedor adjudicado |
| `codigo_producto` | VARCHAR(100) | Código del producto |

### Cantidades y Montos:

| Campo | Tipo | Descripción |
|---|---|---|
| `cantidad_adjudicada` | DECIMAL(15,4) | Cantidad adjudicada |
| `precio_unitario_adjudicado` | DECIMAL(15,4) | Precio por unidad |
| `tipo_moneda` | VARCHAR(10) | CRC, USD, EUR, etc. |

### Ajustes Financieros:

- `descuento` - Descuento aplicado
- `iva` - Impuesto IVA
- `otros_impuestos` - Otros impuestos
- `acarreos` - Costos de acarreo

### Tipos de Cambio:

- `tipo_cambio_crc` - Tipo de cambio CRC
- `tipo_cambio_dolar` - Tipo de cambio USD

## 📊 Estadísticas Disponibles

El verificador muestra:

- ✅ Total de líneas adjudicadas
- ✅ Licitaciones con adjudicaciones
- ✅ Proveedores adjudicados
- ✅ Precio unitario promedio
- ✅ Distribución por moneda
- ✅ Top 10 proveedores por líneas adjudicadas
- ✅ Top 10 licitaciones con más adjudicaciones
- ✅ Últimas 20 líneas importadas

## 🔗 Relaciones con Otras Tablas

```sql
lineas_adjudicadas.numero_sicop → licitaciones.numero_sicop
lineas_adjudicadas.cedula_proveedor → proveedoras.cedula
```

## ⚙️ Características del Importador

- ✅ **Auto-detección de delimitador** (coma o punto y coma)
- ✅ **ON DUPLICATE KEY UPDATE** - No crea duplicados
- ✅ **Limpieza automática de números** - Maneja formatos diversos
- ✅ **Soporte para valores NULL** - Campos opcionales
- ✅ **Estadísticas en tiempo real** - Insertadas/Errores
- ✅ **Interfaz visual moderna** - Color violeta

## 🎨 Diseño Visual

- **Color principal:** Violeta (#8b5cf6 → #6366f1)
- **Título:** 🏆 Líneas Adjudicadas
- **Interfaz:** Moderna, responsiva, fácil de usar

## 📝 Ejemplo de Uso

### Importar LineasAdjudicadas:

1. Descargar **LineasAdjudicadas.csv** del Observatorio SICOP
2. Ir a `admin/importar_lineas_adjudicadas.php`
3. Seleccionar archivo
4. Clic en "🚀 INICIAR IMPORTACIÓN"
5. Ver resultados (Procesadas/Insertadas/Errores)

### Ver estadísticas:

1. Ir a `admin/verificar_lineas_adjudicadas.php`
2. Ver dashboard completo
3. Analizar top proveedores
4. Revisar últimas importaciones

## 🔍 Consultas SQL Útiles

### Líneas adjudicadas por proveedor:

```sql
SELECT
    p.nombre_proveedor,
    COUNT(*) as lineas_adjudicadas,
    SUM(la.cantidad_adjudicada * la.precio_unitario_adjudicado) as monto_total
FROM lineas_adjudicadas la
LEFT JOIN proveedoras p ON la.cedula_proveedor = p.cedula
GROUP BY la.cedula_proveedor
ORDER BY lineas_adjudicadas DESC;
```

### Licitaciones con adjudicaciones:

```sql
SELECT
    l.numero_sicop,
    l.titulo,
    COUNT(la.id) as lineas_adjudicadas,
    SUM(la.cantidad_adjudicada * la.precio_unitario_adjudicado) as monto_total
FROM licitaciones l
LEFT JOIN lineas_adjudicadas la ON l.numero_sicop = la.numero_sicop
WHERE la.id IS NOT NULL
GROUP BY l.numero_sicop
ORDER BY lineas_adjudicadas DESC;
```

### Ver adjudicaciones por oferta:

```sql
SELECT
    numero_oferta,
    COUNT(*) as total_lineas,
    SUM(cantidad_adjudicada * precio_unitario_adjudicado) as monto_total
FROM lineas_adjudicadas
GROUP BY numero_oferta
ORDER BY total_lineas DESC;
```

## ✅ Próximos Pasos

Una vez importadas las líneas adjudicadas, puedes:

1. **Integrar en licitacion_detalle.php** - Mostrar líneas adjudicadas de cada licitación
2. **Crear filtros** - Filtrar por proveedor adjudicado
3. **Análisis de mercado** - Ver qué proveedores ganan más licitaciones
4. **Reportes** - Generar reportes de adjudicaciones por período

## 🆚 Diferencia con Otros Sistemas

| Sistema | Propósito |
|---------|-----------|
| **Líneas Adjudicadas** | Qué líneas ganó cada proveedor ✅ |
| Partidas Licitación | Todas las líneas del cartel original |
| Proveedoras | Datos de proveedores registrados |
| Instituciones Compradoras | Datos de instituciones que licitan |

---

**Versión:** 1.0
**Fecha:** 2025-11-16
**Archivo CSV:** LineasAdjudicadas.csv
**Estado:** ✅ Listo para producción
