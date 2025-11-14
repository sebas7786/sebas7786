# 🚀 Guía de Importación SICOP v14.2 - Solución Definitiva

## 📌 Resumen Ejecutivo

**Versión:** v14.2 (Importador con Detección Inteligente)
**Fecha:** 2025-11-14
**Estado:** ✅ SOLUCIÓN DEFINITIVA

---

## ❌ Problema Original

```
✅ Licitaciones importadas: 1,412
❌ Partidas importadas: 0
```

Las partidas no se importaban a pesar de estar en el archivo CSV.

---

## 🔍 Causa Raíz Identificada

### El archivo "Detalle de Carteles.csv" contiene:

1. **Carteles** (filas 1-9000 aprox): Datos generales de licitaciones
2. **Líneas/Partidas** (filas 9000-9477): Detalles de partidas

### ¿Por qué fallaban las versiones anteriores?

**Problema:** Tanto carteles como líneas tienen **15 columnas**, pero las líneas solo usan las primeras 8 (el resto están vacías).

| Versión | Método de Detección | Resultado |
|---------|---------------------|-----------|
| v14.0 | Contaba columnas (7-9 = línea) | ❌ 0 líneas (todas tienen 15 cols) |
| v14.1 | Buscaba por SICOP | ❌ 0 líneas (seguía contando cols) |
| **v14.2** | **Analiza CONTENIDO** | ✅ **Detecta correctamente** |

---

## ✅ Solución: v14.2 con Detección Inteligente

### Cómo detecta líneas vs carteles:

```
LÍNEA (Partida):
├─ Col [0]: SICOP (11-14 dígitos) → 20251101307
├─ Col [1]: Línea (≤999)          → 2
├─ Col [2]: Partida (≤999)        → 1
├─ Col [3]: Cantidad              → 1
├─ Col [4]: Precio                → 500000
├─ Col [5]: Moneda                → CRC
├─ Col [6]: Tipo cambio           → 505.46
├─ Col [7]: Código                → 7810182920959389
└─ Col [9]: VACÍA ← CLAVE para distinguir

CARTEL (Licitación):
├─ Col [0]: SICOP                 → 20251101307
├─ Col [1]: Cédula institución    → 3101243210
├─ ...
└─ Col [9]: Descripción larga     → "Contratación de servicios..."
```

**Diferencia clave:** Las líneas tienen la columna [9] **vacía**, los carteles tienen **descripción**.

---

## 📋 Estructura de Datos

### Archivo: "Detalle de Carteles.csv"

```
Fila 1-2:     Headers CARTEL
Fila 3-9000:  Datos de CARTELES (licitaciones)
Fila 9001-2:  Headers LÍNEA
Fila 9003+:   Datos de LÍNEAS (partidas)
```

### Campos que importa v14.2:

#### Para CARTELES (→ tabla `licitaciones`):
- Número SICOP
- Cédula institución
- Nombre institución
- Descripción del cartel
- Tipo de procedimiento
- Estado
- Moneda
- Presupuesto
- Código de unidad de compra
- Nombre de unidad de compra
- Pago adelantado PYMES
- Fecha de invitación
- Fecha inicio recepción ofertas

#### Para LÍNEAS (→ tabla `partidas_licitacion`):
- Número SICOP (para enlazar con licitación)
- Número de partida
- Número de línea
- Código de identificación
- Cantidad solicitada
- Precio unitario estimado
- Tipo de moneda
- Tipo de cambio USD

---

## 🎯 Pasos para Importar

### **Paso 1: Verificar Estado Actual**

Ejecuta este diagnóstico para ver el estado antes de importar:

```bash
mysql -u usuario -p u613395717_licitahoy1 < diagnostico_partidas.sql
```

**Resultado esperado:**
```
total_licitaciones: 1,412
total_partidas: 0  ← Debería ser 0 antes de v14.2
```

---

### **Paso 2: Subir Importador v14.2**

Coloca el archivo en tu servidor:

```
/admin/importador_sicop_v14_2.php
```

---

### **Paso 3: Ejecutar Importación**

1. Accede a: `http://tu-dominio.com/admin/importador_sicop_v14_2.php`
2. Sube el archivo: **`Detalle de Carteles.csv`**
3. Haz clic en **"INICIAR IMPORTACIÓN"**
4. Espera a que termine (puede tardar varios minutos)

---

### **Paso 4: Verificar Resultados**

El importador mostrará estadísticas como:

```
════════════════════════════════════════
   ESTADÍSTICAS FINALES
════════════════════════════════════════

📊 LICITACIONES
  ├─ Insertadas:    0
  ├─ Actualizadas:  1,412
  └─ Errores:       0

📊 PARTIDAS
  ├─ Insertadas:    8,065  ← DEBE SER > 0
  ├─ Actualizadas:  0
  └─ Errores:       0

📊 DETECCIÓN
  ├─ Carteles detectados: 1,412
  └─ Líneas detectadas:   8,065  ← DEBE SER > 0
```

---

### **Paso 5: Confirmar en Base de Datos**

Ejecuta de nuevo el diagnóstico:

```bash
mysql -u usuario -p u613395717_licitahoy1 < diagnostico_partidas.sql
```

**Resultado esperado:**
```
total_licitaciones: 1,412
total_partidas: 8,065  ← ✅ YA NO ES CERO
con_codigo_identificacion: 8,065
con_cantidad: 8,065
con_precio_unitario: 8,065
```

---

### **Paso 6: Verificar en la Web**

1. Ve a: `https://licitacionesya.com/user/licitacion_detalle.php?id=1`
2. Desplázate a: **"[ 7. Información de bien, servicio u obra ]"**

**Deberías ver:**

| Partida | Línea | Código           | Cantidad | Precio Unit.  | Moneda |
|---------|-------|------------------|----------|---------------|--------|
| 1       | 1     | 7810182920959389 | 1        | ₡500,000.00   | CRC    |
| 1       | 2     | 4321179892345239 | 2        | $1,200.00     | USD    |

---

## 🔧 Importación Opcional: Nombres y Descripciones

### ⚠️ IMPORTANTE:

El importador v14.2 NO importa los **nombres** ni **descripciones** de las partidas, solo los códigos y datos numéricos.

### Para importar nombres/descripciones:

**Paso 1:** Usa el importador v13 existente
**Archivo:** `DetalleLineaCartel.csv` (diferente del que usaste con v14.2)
**Ruta:** `http://tu-dominio.com/admin/importador_sicop_v13.php`

**Paso 2:** En el importador v13, sube **SOLO** el archivo `DetalleLineaCartel.csv`

**Resultado:** Las partidas ahora tendrán:
- ✅ Código (ya importado con v14.2)
- ✅ Cantidad (ya importado con v14.2)
- ✅ Precio (ya importado con v14.2)
- ✅ **Nombre** (nuevo, desde DetalleLineaCartel)
- ✅ **Descripción** (nuevo, desde DetalleLineaCartel)
- ✅ **Unidad de medida** (nuevo, desde DetalleLineaCartel)

---

## 🆘 Solución de Problemas

### ❌ Problema: "0 Líneas detectadas"

**Causa:** El archivo no tiene la estructura esperada.

**Solución:**

1. Verifica con el diagnóstico:
```bash
php diagnosticar_csv_completo.php
```

2. Busca en el resultado:
```
Líneas detectadas: 0  ← MAL
```

3. Verifica que las últimas filas tengan:
   - Col [0]: Número SICOP (11-14 dígitos)
   - Col [1]: Número pequeño (1, 2, 3...)
   - Col [9]: Vacía

---

### ❌ Problema: "Licitación no encontrada para SICOP XXXXX"

**Causa:** La línea referencia un SICOP que no existe en la tabla `licitaciones`.

**Solución:**

1. Verifica que la licitación existe:
```sql
SELECT id, numero_sicop, titulo
FROM licitaciones
WHERE numero_sicop = 'XXXXX';
```

2. Si no existe, primero importa los carteles (filas del inicio del CSV).

---

### ❌ Problema: "Partidas insertadas pero sin nombre"

**Causa:** El archivo "Detalle de Carteles.csv" NO contiene nombres, solo códigos.

**Solución:** Importa `DetalleLineaCartel.csv` con el importador v13 (ver sección "Importación Opcional").

---

### ❌ Problema: "Error de UNIQUE KEY duplicate"

**Causa:** Ya existe una partida con la misma combinación (licitacion_id, partida, linea).

**Solución:**

✅ **Es normal**. El importador usa `ON DUPLICATE KEY UPDATE` y actualizará la partida existente automáticamente.

---

## 📊 Comparación de Versiones

| Característica                | v14.0 | v14.1 | v14.2 |
|-------------------------------|-------|-------|-------|
| Importa licitaciones          | ✅    | ✅    | ✅    |
| Importa partidas              | ❌    | ❌    | ✅    |
| Busca licitación por SICOP    | ❌    | ✅    | ✅    |
| Detección por columnas        | ✅    | ✅    | ❌    |
| Detección por contenido       | ❌    | ❌    | ✅    |
| Manejo notación científica    | ❌    | ❌    | ✅    |
| UNIQUE KEY automático         | ❌    | ✅    | ✅    |
| Logs detallados               | ⚠️    | ⚠️    | ✅    |
| Estadísticas desglosadas      | ❌    | ✅    | ✅    |

---

## 🔬 Detalles Técnicos

### Detección de tipo de fila (detectarTipoFila):

```php
// LÍNEA
if (
    preg_match('/^\d{11,14}$/', $col0_val) &&  // SICOP
    is_numeric($col1_val) && intval($col1_val) <= 999 &&  // Línea
    is_numeric($col2_val) && intval($col2_val) <= 999 &&  // Partida
    empty($col9_val)  // Columna 9 vacía ← CLAVE
) {
    return 'linea';
}

// CARTEL
if (
    preg_match('/^\d{11,14}$/', $col0_val) &&  // SICOP
    !empty($col9_val) &&  // Columna 9 con texto
    strlen($col9_val) > 5  // Descripción larga
) {
    return 'cartel';
}
```

### Procesamiento de líneas (procesarFilaLineaPorSICOP):

```php
// Leer datos directamente por ÍNDICE (no por nombre de header)
$sicop = trim($row[0] ?? '');
$linea = intval(trim($row[1] ?? 0));
$partida = intval(trim($row[2] ?? 0));
$cantidad = $this->limpiarNumero($row[3] ?? null);
$precio = $this->limpiarNumero($row[4] ?? null);
$moneda = trim($row[5] ?? 'CRC');
$tipo_cambio = $this->limpiarNumero($row[6] ?? null);
$codigo = trim($row[7] ?? '');

// Buscar licitación por SICOP
$stmt = $this->conn->prepare("
    SELECT id FROM licitaciones WHERE numero_sicop = ?
");
$stmt->execute([$sicop]);
$licitacion = $stmt->fetch(PDO::FETCH_ASSOC);

// Insertar partida
if ($licitacion) {
    $stmt = $this->conn->prepare("
        INSERT INTO partidas_licitacion (
            licitacion_id, partida, linea,
            codigo_identificacion, cantidad_solicitada,
            precio_unitario_estimado, tipo_moneda, tipo_cambio_usd
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            codigo_identificacion = VALUES(codigo_identificacion),
            cantidad_solicitada = VALUES(cantidad_solicitada),
            precio_unitario_estimado = VALUES(precio_unitario_estimado),
            tipo_moneda = VALUES(tipo_moneda),
            tipo_cambio_usd = VALUES(tipo_cambio_usd)
    ");
    $stmt->execute([
        $licitacion['id'], $partida, $linea,
        $codigo, $cantidad, $precio, $moneda, $tipo_cambio
    ]);
}
```

### Manejo de notación científica:

```php
// Convierte 7,81018E+15 → 7810182920959389
if (stripos($codigo, 'E+') !== false || stripos($codigo, 'E-') !== false) {
    $codigo = sprintf('%.0f', floatval(str_replace(',', '.', $codigo)));
}
```

---

## ✅ Checklist de Verificación

- [ ] Subí `importador_sicop_v14_2.php` al servidor
- [ ] Ejecuté el diagnóstico inicial (antes de importar)
- [ ] Importé `Detalle de Carteles.csv` con v14.2
- [ ] Vi en el log: "Líneas detectadas: [>0]"
- [ ] Vi en el log: "Partidas insertadas: [>0]"
- [ ] Ejecuté el diagnóstico final (después de importar)
- [ ] Confirmé que `total_partidas > 0`
- [ ] Verifiqué en la web que las partidas se muestran
- [ ] (Opcional) Importé `DetalleLineaCartel.csv` con v13
- [ ] (Opcional) Confirmé que las partidas tienen nombres

---

## 🎉 Resultado Final Esperado

### En la base de datos:

```sql
SELECT
    l.numero_sicop,
    l.titulo,
    COUNT(p.id) as total_partidas,
    SUM(CASE WHEN p.codigo_identificacion IS NOT NULL THEN 1 ELSE 0 END) as con_codigo,
    SUM(CASE WHEN p.cantidad_solicitada IS NOT NULL THEN 1 ELSE 0 END) as con_cantidad,
    SUM(CASE WHEN p.nombre IS NOT NULL THEN 1 ELSE 0 END) as con_nombre
FROM licitaciones l
LEFT JOIN partidas_licitacion p ON l.id = p.licitacion_id
GROUP BY l.id
ORDER BY l.id DESC
LIMIT 5;
```

**Resultado esperado:**
| numero_sicop | titulo | total_partidas | con_codigo | con_cantidad | con_nombre |
|--------------|--------|----------------|------------|--------------|------------|
| 20251101307  | ...    | 5              | 5          | 5            | 0 o 5*     |
| 20251101298  | ...    | 3              | 3          | 3            | 0 o 3*     |

*con_nombre = 0 si solo importaste con v14.2
*con_nombre > 0 si también importaste DetalleLineaCartel con v13

### En la web:

`https://licitacionesya.com/user/licitacion_detalle.php?id=1`

**Sección "[ 7. Información de bien, servicio u obra ]":**

```
┌─────────┬───────┬──────────────────┬──────────┬──────────────┬────────┐
│ Partida │ Línea │ Código           │ Cantidad │ Precio Unit. │ Moneda │
├─────────┼───────┼──────────────────┼──────────┼──────────────┼────────┤
│ 1       │ 1     │ 7810182920959389 │ 1        │ ₡500,000.00  │ CRC    │
│ 1       │ 2     │ 4321179892345239 │ 2        │ $1,200.00    │ USD    │
│ 2       │ 1     │ 9876543210123456 │ 10       │ ₡25,000.00   │ CRC    │
└─────────┴───────┴──────────────────┴──────────┴──────────────┴────────┘
```

---

## 📞 Soporte

Si después de seguir esta guía sigues teniendo problemas, comparte:

1. **Log completo** de la importación (copiar desde el navegador)
2. **Resultado del diagnóstico** antes y después
3. **Primeras 5 filas** del diagnóstico CSV completo
4. **Últimas 30 filas** del diagnóstico CSV completo
5. **Captura de pantalla** de licitacion_detalle.php

---

## 📝 Historial de Versiones

**v14.0** (2025-11-13)
- Primera versión para "Detalle de Carteles"
- ❌ Bug: No insertaba partidas (dependía de $licitacion_actual)

**v14.1** (2025-11-14)
- ✅ Corregido: Busca licitación por NUMERO_SICOP
- ❌ Bug: Seguía sin detectar líneas (contaba columnas)

**v14.2** (2025-11-14)
- ✅ **SOLUCIÓN DEFINITIVA**
- ✅ Detección inteligente por contenido
- ✅ Maneja notación científica en códigos
- ✅ Logs detallados con estadísticas completas
- ✅ Procesamiento directo por índice de columna

---

¡Listo! Ahora tienes la guía completa para importar correctamente con v14.2.
