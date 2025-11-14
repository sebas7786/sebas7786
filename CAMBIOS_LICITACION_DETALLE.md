# 🔧 Cambios en licitacion_detalle.php para v14.2

## 📋 Resumen

El archivo `licitacion_detalle_CORREGIDO.php` ha sido ajustado para mostrar correctamente las partidas importadas con **importador_sicop_v14_2.php**.

---

## ⚙️ Cambios Realizados

### 1. **Query de Partidas Simplificada**

#### ❌ Antes (con problemas):
```php
$query = "SELECT
            partida, linea,
            MAX(codigo) as codigo,
            MAX(codigo_identificacion) as codigo_identificacion,
            MAX(nombre) as nombre,
            MAX(descripcion) as descripcion,
            MAX(cantidad) as cantidad,
            MAX(unidad) as unidad,
            MAX(precio_unitario) as precio_unitario,
            MAX(monto_estimado) as monto_estimado,
            MAX(moneda) as moneda
          FROM partidas_licitacion
          WHERE licitacion_id = :licitacion_id
          GROUP BY partida, linea
          ORDER BY partida ASC, linea ASC";
```

**Problemas:**
- Usa `GROUP BY` innecesario (causa overhead)
- Usa `MAX()` en todos los campos (puede ocultar NULL)
- Busca campo `codigo` que no existe en v14.2

#### ✅ Ahora (optimizado):
```php
$query = "SELECT
            partida,
            linea,
            codigo_identificacion,
            cantidad,
            precio_unitario,
            monto_estimado,
            moneda,
            tipo_cambio_usd,
            nombre,
            descripcion,
            unidad
          FROM partidas_licitacion
          WHERE licitacion_id = :licitacion_id
          ORDER BY partida ASC, linea ASC";
```

**Ventajas:**
- Query más simple y rápida
- Lee directamente los campos correctos
- Compatible con v14.2

---

### 2. **Tabla HTML Actualizada**

#### Nueva columna: **Moneda**
Ahora muestra la moneda de cada partida (CRC o USD):

```html
<th>Moneda</th>
...
<td class="text-center"><?php echo htmlspecialchars($moneda); ?></td>
```

#### Manejo de múltiples monedas:
```php
$moneda = $partida['moneda'] ?? 'CRC';
$simbolo = ($moneda == 'USD') ? '$' : '₡';
```

---

### 3. **Indicadores de Campos Faltantes**

#### Alerta visual para campos sin importar:
```php
// Si el campo está vacío:
<span class="campo-faltante">Importar DetalleLineaCartel.csv</span>
```

Campos que pueden estar vacíos (si solo importaste con v14.2):
- **Nombre**: Muestra "Importar DetalleLineaCartel.csv"
- **Unidad**: Muestra "-"
- **Descripción**: Muestra "Importar DetalleLineaCartel.csv"

---

### 4. **Nota Informativa**

Si las partidas NO tienen nombres, muestra:

```
ℹ️ Información: Los nombres y descripciones de las partidas se importan desde
el archivo "DetalleLineaCartel.csv". Actualmente solo se muestran códigos,
cantidades y precios importados desde "Detalle de Carteles.csv".
```

Esto informa al usuario que debe importar el segundo archivo para completar los datos.

---

### 5. **Totales Solo en CRC**

El total solo suma partidas en CRC:

```php
if ($moneda == 'CRC') {
    $total_general += $monto_estimado;
}
```

**Razón:** No se pueden sumar directamente CRC y USD sin conversión.

---

## 📊 Campos que Muestra la Tabla

| Columna | Origen | Estado v14.2 |
|---------|--------|--------------|
| Partida | v14.2 | ✅ Completo |
| Línea | v14.2 | ✅ Completo |
| Código | v14.2 (`codigo_identificacion`) | ✅ Completo |
| Nombre | DetalleLineaCartel.csv | ⚠️ Vacío (importar v13) |
| Cantidad | v14.2 | ✅ Completo |
| Unidad | DetalleLineaCartel.csv | ⚠️ Vacío (importar v13) |
| Precio Unit. | v14.2 | ✅ Completo |
| Monto Estimado | v14.2 (calculado) | ✅ Completo |
| Moneda | v14.2 | ✅ Completo |

---

## 🎨 Estilos CSS Nuevos

### Clase para campos faltantes:
```css
.campo-faltante {
    color: #999;
    font-style: italic;
    font-size: 12px;
}
```

### Nota informativa:
```css
.info-note {
    background-color: #fff3cd;
    border-left: 4px solid #ffc107;
    padding: 12px 15px;
    margin-bottom: 15px;
    font-size: 13px;
    color: #856404;
}
```

---

## 🔍 Ejemplo de Visualización

### Con v14.2 solamente:

| Partida | Línea | Código | Nombre | Cantidad | Unidad | Precio Unit. | Monto Est. | Moneda |
|---------|-------|--------|--------|----------|--------|--------------|------------|--------|
| 1 | 1 | 7810182920959389 | *Importar DetalleLineaCartel.csv* | 1 | - | ₡500,000.00 | ₡500,000.00 | CRC |
| 1 | 2 | 4321179892345239 | *Importar DetalleLineaCartel.csv* | 2 | - | $1,200.00 | $2,400.00 | USD |

### Después de importar DetalleLineaCartel.csv con v13:

| Partida | Línea | Código | Nombre | Cantidad | Unidad | Precio Unit. | Monto Est. | Moneda |
|---------|-------|--------|--------|----------|--------|--------------|------------|--------|
| 1 | 1 | 7810182920959389 | **Servicio de consultoría** | 1 | **Servicio** | ₡500,000.00 | ₡500,000.00 | CRC |
| 1 | 2 | 4321179892345239 | **Equipo de cómputo** | 2 | **Unidad** | $1,200.00 | $2,400.00 | USD |

---

## 📥 Instalación

### Opción 1: Reemplazar archivo actual
```bash
# Hacer backup del archivo actual
mv user/licitacion_detalle.php user/licitacion_detalle.php.BACKUP

# Copiar archivo corregido
cp licitacion_detalle_CORREGIDO.php user/licitacion_detalle.php
```

### Opción 2: Aplicar cambios manualmente

Si prefieres mantener personalizaciones, aplica solo estas secciones:

**1. Query de partidas (línea ~36-50):**
```php
$query = "SELECT
            partida, linea, codigo_identificacion,
            cantidad, precio_unitario, monto_estimado,
            moneda, tipo_cambio_usd, nombre, descripcion, unidad
          FROM partidas_licitacion
          WHERE licitacion_id = :licitacion_id
          ORDER BY partida ASC, linea ASC";
```

**2. Tabla HTML (sección 7):**
- Agregar columna "Moneda"
- Usar `codigo_identificacion` en lugar de `codigo`
- Agregar estilos para `.campo-faltante` y `.info-note`

---

## ✅ Verificación

### 1. Accede a una licitación:
```
https://licitacionesya.com/user/licitacion_detalle.php?id=1
```

### 2. Busca la sección:
```
[ 7. Información de bien, servicio u obra ]
```

### 3. Deberías ver:
- ✅ Total de líneas: 8,060+ (o el número que tenga esa licitación)
- ✅ Tabla con partidas mostrando:
  - Partida (número)
  - Línea (número)
  - Código (largo)
  - Nombre (o mensaje para importar)
  - Cantidad (número)
  - Precio unitario (con símbolo ₡ o $)
  - Monto estimado (calculado)
  - Moneda (CRC o USD)

### 4. Si ves nombres vacíos:
Es normal. Importa `DetalleLineaCartel.csv` con `importador_sicop_v13.php` para completar.

---

## 🆘 Solución de Problemas

### "No aparecen partidas"

**Diagnóstico:**
```sql
SELECT COUNT(*) FROM partidas_licitacion WHERE licitacion_id = 1;
```

Si retorna 0, la licitación no tiene partidas. Verifica:
1. Que importaste con v14.2
2. Que el NUMERO_SICOP existe en la tabla `licitaciones`

### "Aparecen pero sin datos"

Verifica que la query use los campos correctos:
```php
// ✅ Correcto
codigo_identificacion

// ❌ Incorrecto
codigo
```

### "Error en la query"

Ejecuta manualmente:
```sql
SELECT
    partida, linea, codigo_identificacion,
    cantidad, precio_unitario, monto_estimado,
    moneda, tipo_cambio_usd, nombre, descripcion, unidad
FROM partidas_licitacion
WHERE licitacion_id = 1
ORDER BY partida ASC, linea ASC;
```

Si falla, revisa que las columnas existan:
```sql
SHOW COLUMNS FROM partidas_licitacion;
```

---

## 📝 Notas Técnicas

### Diferencias con el archivo original:

1. **Eliminado `GROUP BY`**: No es necesario si no hay duplicados
2. **Eliminado `MAX()`**: Lee valores directos
3. **Agregada columna Moneda**: Muestra CRC o USD
4. **Manejo de NULL**: Muestra mensajes informativos en lugar de "-"
5. **Nota informativa**: Guía al usuario para completar datos

### Compatibilidad:

- ✅ Compatible con v14.2
- ✅ Compatible con v13 (si importas DetalleLineaCartel.csv después)
- ✅ Compatible con datos parciales
- ✅ Muestra información útil cuando faltan datos

---

## 🎉 Resultado Final

Con este archivo corregido verás:

```
═══════════════════════════════════════════════════════════════
[ 7. Información de bien, servicio u obra ]
═══════════════════════════════════════════════════════════════

Total de líneas: 5

┌─────────┬───────┬──────────────────┬──────────┬──────────┬────────┬──────────────┬──────────────┬────────┐
│ Partida │ Línea │ Código           │ Nombre   │ Cantidad │ Unidad │ Precio Unit. │ Monto Est.   │ Moneda │
├─────────┼───────┼──────────────────┼──────────┼──────────┼────────┼──────────────┼──────────────┼────────┤
│ 1       │ 1     │ 7810182920959389 │ ...      │ 1        │ ...    │ ₡500,000.00  │ ₡500,000.00  │ CRC    │
│ 1       │ 2     │ 4321179892345239 │ ...      │ 2        │ -      │ $1,200.00    │ $2,400.00    │ USD    │
│ 2       │ 1     │ 9876543210123456 │ ...      │ 10       │ ...    │ ₡25,000.00   │ ₡250,000.00  │ CRC    │
└─────────┴───────┴──────────────────┴──────────┴──────────┴────────┴──────────────┴──────────────┴────────┘

                                                    TOTAL ESTIMADO (CRC): ₡750,000.00
```

---

¡Listo! Tu sistema ahora muestra correctamente las partidas importadas con v14.2.
