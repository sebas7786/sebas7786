# ⚡ Instalación Rápida - Ver Partidas en licitacion_detalle.php

## 🎯 Objetivo
Mostrar las **8,060 partidas** importadas con v14.2 en la página de detalle de licitaciones.

---

## ✅ Estado Actual
- ✅ Partidas importadas: **8,060**
- ✅ Licitaciones: **1,412**
- ⚠️ Problema: La página web NO las muestra correctamente

---

## 🔧 Solución en 2 Pasos

### **Paso 1: Hacer backup del archivo actual**

En tu servidor, ejecuta:

```bash
cd /ruta/a/tu/proyecto/user
cp licitacion_detalle.php licitacion_detalle.php.BACKUP_$(date +%Y%m%d)
```

Esto creará un backup con la fecha, ejemplo: `licitacion_detalle.php.BACKUP_20251114`

---

### **Paso 2: Subir archivo corregido**

**Opción A - FTP/SFTP:**
1. Descarga `licitacion_detalle_CORREGIDO.php` del repositorio
2. Renómbralo a `licitacion_detalle.php`
3. Súbelo a `/user/` en tu servidor
4. Reemplaza el archivo actual

**Opción B - SSH:**
```bash
cd /ruta/a/tu/proyecto
# Descargar desde el repositorio
git pull origin claude/sicop-v13-import-system-01CZY5LU6NeCGi1Es4VZ8QPD

# Copiar archivo corregido
cp licitacion_detalle_CORREGIDO.php user/licitacion_detalle.php
```

---

## 🔍 Verificación

### 1. Accede a una licitación:
```
https://licitacionesya.com/user/licitacion_detalle.php?id=1
```

### 2. Desplázate a la sección:
```
[ 7. Información de bien, servicio u obra ]
```

### 3. Deberías ver:

**✅ ANTES (problema):**
```
[ 7. Información de bien, servicio u obra ]

Total de líneas: 0

(tabla vacía o no aparece)
```

**✅ AHORA (correcto):**
```
[ 7. Información de bien, servicio u obra ]

Total de líneas: 5

┌─────────┬───────┬──────────────────┬────────────────────────┬──────────┬────────┬──────────────┐
│ Partida │ Línea │ Código           │ Nombre                 │ Cantidad │ Precio │ Monto Est.   │
├─────────┼───────┼──────────────────┼────────────────────────┼──────────┼────────┼──────────────┤
│ 1       │ 1     │ 7810182920959389 │ (mensaje para importar)│ 1        │ ₡500K  │ ₡500,000.00  │
│ 1       │ 2     │ 4321179892345239 │ (mensaje para importar)│ 2        │ $1.2K  │ $2,400.00    │
└─────────┴───────┴──────────────────┴────────────────────────┴──────────┴────────┴──────────────┘

                                                      TOTAL ESTIMADO (CRC): ₡500,000.00
```

---

## 📋 Campos que Verás

| Campo | Estado | Notas |
|-------|--------|-------|
| **Partida** | ✅ Completo | Número de partida |
| **Línea** | ✅ Completo | Número de línea |
| **Código** | ✅ Completo | Código de identificación (puede ser muy largo) |
| **Nombre** | ⚠️ Vacío | Dice "Importar DetalleLineaCartel.csv" |
| **Cantidad** | ✅ Completo | Cantidad solicitada |
| **Unidad** | ⚠️ Vacío | Muestra "-" |
| **Precio Unit.** | ✅ Completo | Con símbolo ₡ o $ según moneda |
| **Monto Estimado** | ✅ Completo | Cantidad × Precio |
| **Moneda** | ✅ Completo | CRC o USD |

---

## ℹ️ Nota sobre Nombres Vacíos

Si la columna "Nombre" muestra:
```
Importar DetalleLineaCartel.csv
```

**Es normal**. Los nombres vienen de otro archivo CSV.

### Para completar los nombres:

1. Usa el importador v13: `importador_sicop_v13.php`
2. Sube el archivo: `DetalleLineaCartel.csv`
3. Las partidas se actualizarán con nombres y descripciones

---

## 🆘 Si No Funciona

### Problema 1: "Error de PHP"

**Causa:** Sintaxis incorrecta al copiar el archivo

**Solución:**
1. Restaura el backup:
   ```bash
   cp licitacion_detalle.php.BACKUP_20251114 licitacion_detalle.php
   ```
2. Descarga de nuevo `licitacion_detalle_CORREGIDO.php`
3. Verifica que el archivo está completo (no cortado)

---

### Problema 2: "Sigue sin mostrar partidas"

**Diagnóstico:**
```sql
SELECT COUNT(*) FROM partidas_licitacion WHERE licitacion_id = 1;
```

Si retorna **0**, esa licitación específica no tiene partidas.

**Solución:** Prueba con otra licitación o verifica que importaste correctamente con v14.2.

---

### Problema 3: "Muestra partidas pero sin datos"

**Causa:** La query sigue usando campos antiguos

**Solución:**
1. Abre `licitacion_detalle.php`
2. Busca la línea con `GROUP BY partida, linea`
3. Reemplaza toda la query con:
   ```php
   $query = "SELECT
               partida, linea, codigo_identificacion,
               cantidad, precio_unitario, monto_estimado,
               moneda, tipo_cambio_usd, nombre, descripcion, unidad
             FROM partidas_licitacion
             WHERE licitacion_id = :licitacion_id
             ORDER BY partida ASC, linea ASC";
   ```

---

## 📊 Comparación Visual

### ANTES (archivo original):
```php
// Query con problemas
MAX(codigo) as codigo,              // ❌ Campo 'codigo' no existe
MAX(codigo_identificacion) ...      // ⚠️ Usa MAX innecesariamente
GROUP BY partida, linea             // ⚠️ Overhead innecesario
```

### AHORA (archivo corregido):
```php
// Query optimizada
codigo_identificacion,              // ✅ Campo correcto
cantidad,                           // ✅ Directo
precio_unitario,                    // ✅ Directo
moneda,                             // ✅ Nuevo campo
ORDER BY partida ASC, linea ASC    // ✅ Sin GROUP BY
```

---

## ✅ Checklist Final

- [ ] Hice backup del archivo original
- [ ] Subí `licitacion_detalle_CORREGIDO.php` al servidor
- [ ] Renombré a `licitacion_detalle.php`
- [ ] Accedí a una licitación en el navegador
- [ ] Vi la sección "[ 7. Información de bien, servicio u obra ]"
- [ ] La tabla muestra partidas con:
  - [ ] Partida y Línea (números)
  - [ ] Código (largo)
  - [ ] Cantidad (número)
  - [ ] Precio unitario (con ₡ o $)
  - [ ] Monto estimado (calculado)
  - [ ] Moneda (CRC o USD)
- [ ] (Opcional) Importé `DetalleLineaCartel.csv` para ver nombres

---

## 🎉 ¡Listo!

Ahora tu sistema muestra correctamente las **8,060 partidas** importadas.

**Próximo paso opcional:**
Importar `DetalleLineaCartel.csv` con `importador_sicop_v13.php` para agregar nombres y descripciones a las partidas.

---

## 📞 Soporte

Si tienes problemas, comparte:
1. Captura de pantalla de `licitacion_detalle.php?id=1`
2. Resultado de: `SELECT COUNT(*) FROM partidas_licitacion`
3. Primeras 10 líneas del archivo PHP (para verificar sintaxis)
