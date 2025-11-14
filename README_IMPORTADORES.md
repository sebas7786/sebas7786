# 📚 Guía Rápida de Importadores SICOP

## 🎯 ¿Qué importador debo usar?

### Para archivo: **"Detalle de Carteles.csv"**
**Importador:** `importador_sicop_v14_2.php` ✅ **USAR ESTE**

**Importa:**
- Licitaciones (carteles)
- Partidas (líneas con código, cantidad, precio)

**NO importa:**
- Nombres de partidas
- Descripciones de partidas
- Unidades de medida

---

### Para archivo: **"DetalleLineaCartel.csv"**
**Importador:** `importador_sicop_v13.php`

**Importa/Actualiza:**
- Nombres de partidas
- Descripciones de partidas
- Unidades de medida

**Requiere:** Que las partidas ya existan (importadas con v14.2)

---

### Para otros 9 archivos SICOP
**Importador:** `importador_sicop_v13.php`

**Archivos soportados:**
1. InstitucionesRegistradas.csv
2. FechaPorEtapas.csv
3. SistemaEvaluacionOfertas.csv
4. Sistemas.csv
5. DetalleCarteles.csv (usa v14.2 en su lugar)
6. DetalleLineaCartel.csv
7. LineasAdjudicadas.csv
8. LineasOfertadas.csv
9. Recepciones.csv
10. RecursosObjecion.csv
11. AdjudicacionFirme.csv

---

## 📋 Orden Recomendado de Importación

### Opción 1: Importación Completa (Recomendado)

```
1. importador_sicop_v14_2.php
   ↓ Archivo: "Detalle de Carteles.csv"
   ↓ Resultado: Licitaciones + Partidas con códigos/precios

2. importador_sicop_v13.php
   ↓ Archivo: "DetalleLineaCartel.csv"
   ↓ Resultado: Partidas ahora tienen nombres/descripciones

3. importador_sicop_v13.php
   ↓ Archivo: "FechaPorEtapas.csv"
   ↓ Resultado: Fechas detalladas por etapa

4. importador_sicop_v13.php
   ↓ Archivo: "LineasAdjudicadas.csv"
   ↓ Resultado: Información de adjudicaciones

... (otros archivos según necesidad)
```

### Opción 2: Solo Partidas Básicas

```
1. importador_sicop_v14_2.php
   ↓ Archivo: "Detalle de Carteles.csv"
   ✅ Listo - Licitaciones con partidas básicas
```

---

## 🔍 Herramientas de Diagnóstico

### Antes de importar:

**1. Verificar estructura del CSV:**
```
http://tu-dominio.com/diagnosticar_csv_completo.php
```
- Sube el archivo CSV
- Verás: Total filas, carteles detectados, líneas detectadas
- Muestra: Primeras 15 filas, últimas 30 filas, contexto de primera línea

**2. Verificar estado de la base de datos:**
```bash
mysql -u usuario -p u613395717_licitahoy1 < diagnostico_partidas.sql
```
- Muestra: Total licitaciones, total partidas, estadísticas

---

### Después de importar:

**1. Ejecutar diagnóstico de nuevo:**
```bash
mysql -u usuario -p u613395717_licitahoy1 < diagnostico_partidas.sql
```
- Compara: Total partidas antes vs después
- Verifica: Campos completos (código, cantidad, precio, nombre)

**2. Verificar en la web:**
```
https://licitacionesya.com/user/licitacion_detalle.php?id=1
```
- Desplázate a: "[ 7. Información de bien, servicio u obra ]"
- Deberías ver: Tabla con partidas completas

---

## 📊 Archivos Disponibles

### Importadores:
- ✅ `importador_sicop_v14_2.php` - **USAR PARA "Detalle de Carteles"**
- ⚠️ `importador_sicop_v14_1.php` - Obsoleto (bug: no detecta líneas)
- ⚠️ `importador_sicop_v14.php` - Obsoleto (bug: no inserta partidas)
- ✅ `importador_sicop_v13.php` - Usar para otros archivos

### Herramientas:
- ✅ `diagnosticar_csv_completo.php` - Analiza estructura completa del CSV
- ✅ `diagnosticar_csv.php` - Muestra primeras 50 filas
- ✅ `diagnostico_partidas.sql` - Verifica estado de BD

### Scripts SQL:
- ✅ `actualizacion_bd_v14.sql` - Agrega campos nuevos a BD
- ✅ `fix_partidas_unique_key.sql` - Crea UNIQUE KEY (automático en v14.2)
- ✅ `crear_usuario_admin.sql` - Crea usuario admin
- ✅ `generar_hash_password.php` - Genera hash bcrypt

### Documentación:
- 📖 `GUIA_IMPORTACION_v14_2.md` - **GUÍA COMPLETA Y DETALLADA**
- 📖 `INSTRUCCIONES_IMPORTACION_COMPLETA.md` - Guía para v14.1 (obsoleta)
- 📖 `README_IMPORTADORES.md` - Este archivo (referencia rápida)

---

## ⚠️ Errores Comunes

### "0 Partidas insertadas"
**Causa:** Usaste v14.0 o v14.1 en lugar de v14.2
**Solución:** Usa `importador_sicop_v14_2.php`

### "Partidas sin nombre"
**Causa:** Solo importaste con v14.2
**Solución:** Importa `DetalleLineaCartel.csv` con v13

### "UNIQUE KEY duplicate"
**Causa:** Importaste el mismo archivo dos veces
**Solución:** Es normal, el importador actualiza automáticamente

### "Licitación no encontrada para SICOP"
**Causa:** Las líneas referencian un SICOP que no existe
**Solución:** Verifica que importaste primero los carteles

---

## 📞 ¿Necesitas ayuda?

**Lee primero:**
📖 `GUIA_IMPORTACION_v14_2.md` - Guía detallada con solución de problemas

**Si sigues con problemas, comparte:**
1. Log completo del importador
2. Resultado del diagnóstico (antes y después)
3. Primeras y últimas filas del CSV (usa diagnosticar_csv_completo.php)

---

## ✅ Checklist Rápido

### Primera vez:
- [ ] Ejecutar `diagnostico_partidas.sql` (ver estado inicial)
- [ ] Importar con `importador_sicop_v14_2.php` → "Detalle de Carteles.csv"
- [ ] Verificar log: "Líneas detectadas > 0"
- [ ] Ejecutar `diagnostico_partidas.sql` (confirmar partidas > 0)
- [ ] (Opcional) Importar con `importador_sicop_v13.php` → "DetalleLineaCartel.csv"
- [ ] Verificar en web: licitacion_detalle.php

### Actualizaciones:
- [ ] Importar con `importador_sicop_v14_2.php` (actualiza automáticamente)
- [ ] (Opcional) Importar con `importador_sicop_v13.php` para otros archivos

---

¡Listo! Consulta `GUIA_IMPORTACION_v14_2.md` para instrucciones detalladas.
