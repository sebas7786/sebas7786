# 📋 Sistema de Líneas Contratadas (Adjudicadas)

Sistema para importar y visualizar las líneas adjudicadas de licitaciones SICOP.

## 🎯 Propósito

Permite registrar qué líneas de cada licitación fueron adjudicadas, a qué proveedor, por cuánto dinero, cantidades, impuestos, descuentos, etc.

## 📦 Archivos Creados

```
sql/tabla_lineas_contratadas.sql           - Estructura de base de datos
admin/importar_lineas_contratadas.php      - Importador web
admin/verificar_lineas_contratadas.php     - Dashboard de estadísticas
```

## 🚀 Instalación

### Paso 1: Crear la tabla en la base de datos

Ejecuta el script SQL:

```bash
mysql -u usuario -p nombre_bd < sql/tabla_lineas_contratadas.sql
```

O desde phpMyAdmin:
1. Abre phpMyAdmin
2. Selecciona tu base de datos
3. Ve a SQL
4. Pega el contenido de `sql/tabla_lineas_contratadas.sql`
5. Ejecuta

### Paso 2: Importar datos

1. Accede a: `admin/importar_lineas_contratadas.php`
2. Sube el archivo CSV **LineasContratadas.csv** del Observatorio SICOP
3. Haz clic en "INICIAR IMPORTACIÓN"
4. Espera a que termine

### Paso 3: Ver estadísticas

Accede a: `admin/verificar_lineas_contratadas.php`

## 📋 Formato del CSV

El archivo **LineasContratadas.csv** debe tener estos campos:

```
NRO_SICOP, NRO_LINEA_CONTRATO, NRO_LINEA_CARTEL, NRO_CONTRATO,
SECUENCIA, CEDULA_PROVEEDOR, CODIGO_PRODUCTO, CANTIDAD_CONTRATADA,
PRECIO_UNITARIO, TIPO_MONEDA, DESCUENTO, IVA, OTROS_IMPUESTOS,
ACARREOS, TIPO_CAMBIO_CRC, TIPO_CAMBIO_DOLAR, NRO_ACTO,
DESC_PRODUCTO, cantidad_aumentada, cantidad_disminuida,
monto_aumentado, monto_disminuido
```

## 🗄️ Estructura de la Tabla

### Campos Principales:

| Campo | Tipo | Descripción |
|---|---|---|
| `numero_sicop` | VARCHAR(20) | Número de licitación SICOP |
| `numero_linea_contrato` | INT | Número de línea del contrato |
| `numero_linea_cartel` | INT | Número de línea del cartel original |
| `cedula_proveedor` | VARCHAR(20) | Cédula del proveedor adjudicado |
| `cantidad_contratada` | DECIMAL(15,4) | Cantidad adjudicada |
| `precio_unitario` | DECIMAL(15,4) | Precio por unidad |
| `tipo_moneda` | VARCHAR(10) | CRC, USD, EUR, etc. |

### Campos Financieros:

- `descuento` - Descuento aplicado
- `iva` - Impuesto IVA
- `otros_impuestos` - Otros impuestos
- `acarreos` - Costos de acarreo
- `tipo_cambio_crc` - Tipo de cambio CRC
- `tipo_cambio_dolar` - Tipo de cambio USD

### Modificaciones:

- `cantidad_aumentada` - Cantidad añadida por adendas
- `cantidad_disminuida` - Cantidad reducida
- `monto_aumentado` - Monto añadido
- `monto_disminuido` - Monto reducido

## 📊 Estadísticas Disponibles

El verificador muestra:

- ✅ Total de líneas contratadas
- ✅ Licitaciones con adjudicaciones
- ✅ Proveedores adjudicados
- ✅ Precio unitario promedio
- ✅ Distribución por moneda
- ✅ Top 10 proveedores por líneas adjudicadas
- ✅ Top 10 licitaciones con más adjudicaciones
- ✅ Últimas 20 líneas importadas

## 🔗 Relaciones con Otras Tablas

```sql
lineas_contratadas.numero_sicop → licitaciones.numero_sicop
lineas_contratadas.cedula_proveedor → proveedoras.cedula
```

## ⚙️ Características del Importador

- ✅ **Auto-detección de delimitador** (coma o punto y coma)
- ✅ **ON DUPLICATE KEY UPDATE** - No crea duplicados
- ✅ **Limpieza automática de números** - Maneja formatos diversos
- ✅ **Soporte para valores NULL** - Campos opcionales
- ✅ **Estadísticas en tiempo real** - Insertadas/Actualizadas/Errores
- ✅ **Interfaz visual moderna** - Fácil de usar

## 🎨 Diseño Visual

- **Color principal:** Violeta (#8b5cf6 → #6366f1)
- **Interfaz:** Moderna, responsiva
- **Tablas:** Ordenadas y con hover effects
- **Badges:** Para monedas y números SICOP

## 📝 Ejemplo de Uso

### Importar LineasContratadas:

```
1. Descargar LineasContratadas.csv del Observatorio SICOP
2. Ir a admin/importar_lineas_contratadas.php
3. Seleccionar archivo
4. Clic en "INICIAR IMPORTACIÓN"
5. Ver resultados
```

### Ver estadísticas:

```
1. Ir a admin/verificar_lineas_contratadas.php
2. Ver dashboard completo
3. Analizar top proveedores
4. Revisar últimas importaciones
```

## 🔍 Consultas SQL Útiles

### Líneas adjudicadas por proveedor:

```sql
SELECT
    p.nombre_proveedor,
    COUNT(*) as lineas_adjudicadas,
    SUM(lc.cantidad_contratada * lc.precio_unitario) as monto_total
FROM lineas_contratadas lc
LEFT JOIN proveedoras p ON lc.cedula_proveedor = p.cedula
GROUP BY lc.cedula_proveedor
ORDER BY lineas_adjudicadas DESC;
```

### Licitaciones con adjudicaciones:

```sql
SELECT
    l.numero_sicop,
    l.titulo,
    COUNT(lc.id) as lineas_adjudicadas
FROM licitaciones l
LEFT JOIN lineas_contratadas lc ON l.numero_sicop = lc.numero_sicop
WHERE lc.id IS NOT NULL
GROUP BY l.numero_sicop
ORDER BY lineas_adjudicadas DESC;
```

## ✅ Próximos Pasos

Una vez importadas las líneas contratadas, puedes:

1. **Integrar en licitacion_detalle.php** - Mostrar líneas adjudicadas de cada licitación
2. **Crear filtros** - Filtrar por proveedor adjudicado
3. **Análisis de mercado** - Ver qué proveedores ganan más licitaciones
4. **Reportes** - Generar reportes de adjudicaciones por período

## 📞 Soporte

Para dudas o problemas:
- Revisa que la tabla esté creada correctamente
- Verifica que el CSV tenga el formato correcto
- Chequea los logs de errores en el importador

---

**Versión:** 1.0
**Fecha:** 2025-11-16
**Estado:** Listo para producción 🚀
