# 🔍 Problema: Partidas Sin Nombre/Descripción

## 📋 Diagnóstico del Problema

Las líneas/partidas **NO se están mostrando con nombre** porque el archivo **"Detalle de Carteles"** que contiene las líneas solo tiene estos campos:

### Campos que SÍ vienen en "Detalle de Carteles" (Líneas):
- ✅ Número SICOP
- ✅ No Linea
- ✅ Número partida
- ✅ Cantidad Solicitada
- ✅ Precio Unitario Estimado
- ✅ Tipo Moneda
- ✅ Tipo Cambio USD
- ✅ Código Identificación

### Campos que NO vienen (y se necesitan):
- ❌ Nombre/Descripción de la línea
- ❌ Unidad de medida

---

## 🎯 ¿De dónde vienen esos datos?

Los **nombres/descripciones** de las líneas vienen del archivo:
**`DetalleLineaCartel.csv`**

Este archivo tiene:
- NRO_SICOP
- NUMERO_LINEA
- NUMERO_PARTIDA
- **DESC_LINEA** ← Este es el nombre que falta
- CANTIDAD_SOLICITADA
- PRECIO_UNITARIO_ESTIMADO
- TIPO_MONEDA
- CODIGO_IDENTIFICACION
- MONTO_RESERVADO

---

## ✅ Soluciones

### **Opción 1: Usar el Importador v13 para DetalleLineaCartel.csv** (Más Rápido)

1. Ve a `importador_sicop_v13.php`
2. Sube **SOLO** el archivo `DetalleLineaCartel.csv`
3. Este archivo llenará los campos `nombre` y `descripcion` de las partidas
4. Luego usa el importador v14 para `Detalle de Carteles.csv`

**Resultado:** Las partidas tendrán nombre y descripción

---

### **Opción 2: Importador Combinado** (Requiere cambios)

Puedo crear un **importador v14.1** que procese:
1. Primero el archivo "DetalleLineaCartel.csv" (nombres)
2. Luego el archivo "Detalle de Carteles.csv" (carteles y líneas básicas)
3. Combine ambos datos automáticamente

---

### **Opción 3: Actualizar el Query de Visualización** (Temporal)

Modificar `licitacion_detalle.php` para que muestre lo que sí hay:
- Código de identificación
- Cantidad
- Precio
- Monto

Aunque no tenga nombre/descripción.

---

## 🚀 Recomendación

### **Te recomiendo Opción 1** porque:
1. ✅ Es más rápido
2. ✅ Usa código ya probado (importador v13)
3. ✅ No requiere cambios
4. ✅ Te da control de qué archivos importar

### **Orden de importación correcto:**

```
1º → DetalleLineaCartel.csv (importador v13)
     ↓ Esto llena: nombre, descripción, cantidad, precio

2º → Detalle de Carteles.csv (importador v14)
     ↓ Esto llena: info general de licitaciones
                   y actualiza datos de líneas si hace falta
```

---

## 📊 Para Verificar los Datos

**Paso 1:** Ejecuta el script de diagnóstico
```bash
mysql -u usuario -p base_datos < diagnostico_partidas.sql
```

**Paso 2:** Revisa los resultados
- Si `con_nombre = 0` → Necesitas importar DetalleLineaCartel.csv
- Si `con_codigo_identificacion > 0` → Las líneas básicas sí se importaron

**Paso 3:** Ejecuta el fix de clave única
```bash
mysql -u usuario -p base_datos < fix_partidas_unique_key.sql
```

---

## 🔧 Scripts SQL Creados

He creado estos scripts SQL para ayudarte:

### 1. `fix_partidas_unique_key.sql`
- Elimina duplicados
- Agrega clave única a la tabla
- Mejora el rendimiento

### 2. `diagnostico_partidas.sql`
- Muestra qué datos existen
- Identifica qué campos están vacíos
- Detecta duplicados

---

## ❓ ¿Qué Opción Prefieres?

Dime cuál opción quieres y te ayudo:

**A)** Usar importador v13 para DetalleLineaCartel.csv (ya existe)

**B)** Crear importador v14.1 combinado (requiere desarrollo)

**C)** Actualizar solo la visualización (temporal, sin nombres)

---

## 📝 Ejemplo de Cómo Quedará

### Con la Opción A (Recomendada):

| Partida | Línea | Código | Nombre | Cantidad | Precio Unit. |
|---------|-------|--------|--------|----------|--------------|
| 1 | 1 | 8111220292095938 | RENOVACION DE CERTIFICADO DE FIRMA DIGITAL | 1 | ₡25,000.00 |
| 1 | 2 | 4321179892345239 | LECTOR TARJETAS INTELIGENTES DIGITAL... | 1 | ₡25,000.00 |

### Con la Opción C (Sin nombre):

| Partida | Línea | Código | Cantidad | Precio Unit. |
|---------|-------|--------|----------|--------------|
| 1 | 1 | 8111220292095938 | 1 | ₡25,000.00 |
| 1 | 2 | 4321179892345239 | 1 | ₡25,000.00 |

---

**¿Qué opción prefieres?** 🚀
