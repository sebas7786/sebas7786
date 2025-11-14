# ⚡ Inicio Rápido - Importador v14.2

## 🎯 Solución en 3 Pasos

### **Paso 1: Sube el importador**
```
Archivo: importador_sicop_v14_2.php
Destino: /admin/importador_sicop_v14_2.php
```

### **Paso 2: Importa el CSV**
```
URL: http://tu-dominio.com/admin/importador_sicop_v14_2.php
Archivo: Detalle de Carteles.csv
```

### **Paso 3: Verifica**
```
Deberías ver en el log:
✅ Líneas detectadas: [>0]
✅ Partidas insertadas: [>0]
```

---

## ✅ Resultado Esperado

Antes de v14.2:
```
Licitaciones: 1,412
Partidas: 0  ❌
```

Después de v14.2:
```
Licitaciones: 1,412
Partidas: 8,065  ✅
```

---

## 📋 ¿Qué hace v14.2?

Importa de "Detalle de Carteles.csv":
- ✅ Información general de licitaciones
- ✅ Partidas con código, cantidad y precio
- ❌ Nombres de partidas (usar v13 después)

---

## 🔍 Verificar en la Web

```
https://licitacionesya.com/user/licitacion_detalle.php?id=1
```

Busca la sección: **"[ 7. Información de bien, servicio u obra ]"**

Deberías ver una tabla con:
- Partida
- Línea
- Código
- Cantidad
- Precio Unitario
- Moneda

---

## ⚠️ Si NO funciona

**Síntoma:** "0 Líneas detectadas"

**Diagnóstico:**
```
http://tu-dominio.com/diagnosticar_csv_completo.php
```
Sube el CSV y verifica que las últimas filas sean detectadas como LÍNEAS.

---

## 📚 Guías Detalladas

- **Guía completa:** `GUIA_IMPORTACION_v14_2.md`
- **Referencia rápida:** `README_IMPORTADORES.md`

---

## 🆘 Problema Común

**"Partidas sin nombre"**

✅ **Es normal**

Los nombres vienen de otro archivo. Para importar nombres:

1. Usa: `importador_sicop_v13.php`
2. Sube: `DetalleLineaCartel.csv`

Eso completará los nombres y descripciones.

---

¡Listo! Con estos 3 pasos ya deberías tener las partidas importadas correctamente.
