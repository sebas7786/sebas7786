# 🔧 Solución para Estadísticas Ganadores

## Problema Identificado

El código de `estadisticas_ganadores.php` no está mostrando resultados. Esto puede deberse a varios factores:

1. **Consultas SQL demasiado complejas** con múltiples JOINs que fallan
2. **Nombres de tablas o columnas incorrectos**
3. **Búsqueda demasiado estricta** que no encuentra coincidencias
4. **Datos no existen en las tablas**

---

## ✅ Solución Implementada

He creado **3 archivos** para resolver el problema:

### 1. `estadisticas_ganadores_v2.php`
**Versión mejorada con debugging**

**Mejoras principales:**
- ✅ **Modo DEBUG activado** - Muestra toda la información de las consultas
- ✅ **Consultas SQL más simples** - Menos JOINs complejos
- ✅ **Búsqueda más flexible** - Acepta códigos parciales o completos
- ✅ **Mejor manejo de errores** - Te dice exactamente qué está pasando
- ✅ **Búsquedas alternativas** - Si no encuentra con una consulta, prueba otras

**Características:**
```php
// MODO DEBUG - Cambiar a false en producción
$DEBUG_MODE = true;
```

Cuando DEBUG está activado, verás:
- La consulta SQL exacta que se ejecuta
- Los parámetros que se están usando
- Cuántos resultados se encontraron
- Búsquedas alternativas si no hay resultados

### 2. `diagnostico_db.php`
**Script para verificar la base de datos**

Este script te muestra:
- ✅ Qué tablas existen en tu base de datos
- ✅ Cuántos registros tiene cada tabla
- ✅ La estructura exacta de `lineas_adjudicadas`
- ✅ Ejemplos de códigos reales en tu BD
- ✅ Pruebas de búsqueda con diferentes métodos
- ✅ Verificación de relaciones (JOINs)
- ✅ Recomendaciones específicas para tu caso

### 3. `SOLUCION_ESTADISTICAS.md`
Este archivo con instrucciones completas

---

## 📋 Pasos para Solucionar

### Paso 1: Subir los Archivos
```bash
# Sube estos archivos a tu servidor en la carpeta /user/
estadisticas_ganadores_v2.php
diagnostico_db.php
```

### Paso 2: Ejecutar el Diagnóstico
```
https://licitacionesya.com/user/diagnostico_db.php
```

Esto te mostrará:
1. Si las tablas existen
2. Cuántos datos tienes
3. Ejemplos de códigos reales
4. Qué búsquedas funcionan

### Paso 3: Probar la Nueva Versión
```
https://licitacionesya.com/user/estadisticas_ganadores_v2.php
```

**Con DEBUG activado**, verás un panel amarillo en la parte superior que te dice:
- Qué está buscando
- Qué consulta SQL ejecuta
- Cuántos resultados encontró
- Si no encuentra nada, te dice por qué

### Paso 4: Usar Código de Ejemplo del Diagnóstico

El script `diagnostico_db.php` te dará una URL de prueba con un código real de tu base de datos, algo como:

```
estadisticas_ganadores_v2.php?codigo=43211500&modo=codigo
```

---

## 🔍 Diferencias Clave entre Versión Original y V2

### **Versión Original (problemática):**
```php
$sql = "SELECT ... FROM lineas_adjudicadas la
        LEFT JOIN licitaciones l ON la.numero_sicop = l.numero_sicop
        LEFT JOIN proveedoras p ON la.cedula_proveedor = p.cedula
        LEFT JOIN instituciones_compradoras ic ON l.cedula_institucion = ic.cedula
        LEFT JOIN partidas_licitacion pl ON (
            pl.licitacion_id = l.id AND
            pl.linea = la.numero_linea
        )
        WHERE (
            SUBSTRING(la.codigo_producto, 1, 8) = :codigo OR
            SUBSTRING(pl.codigo, 1, 8) = :codigo OR
            SUBSTRING(pl.codigo_identificacion, 1, 8) = :codigo
        )";
```
**Problemas:**
- Demasiados JOINs (5 tablas)
- Si una tabla no tiene datos, falla todo
- No muestra información de debug
- Búsqueda muy estricta

### **Versión 2 (mejorada):**
```php
$sql = "SELECT ... FROM lineas_adjudicadas la
        LEFT JOIN licitaciones l ON la.numero_sicop = l.numero_sicop
        LEFT JOIN proveedoras p ON la.cedula_proveedor = p.cedula
        LEFT JOIN instituciones_compradoras ic ON l.cedula_institucion = ic.cedula
        WHERE (
            la.codigo_producto LIKE :codigo OR
            SUBSTRING(la.codigo_producto, 1, 8) = :codigo_8
        )";
```
**Mejoras:**
- Solo 3 JOINs necesarios
- Búsqueda más flexible (LIKE + SUBSTRING)
- Modo DEBUG con información detallada
- Si no encuentra, prueba búsquedas alternativas

---

## 🎯 Cómo Usar la Nueva Versión

### Búsqueda por Código UNSPSC:

**Opción 1: Solo 8 dígitos (RECOMENDADO)**
```
https://licitacionesya.com/user/estadisticas_ganadores_v2.php?codigo=43211500&modo=codigo
```

**Opción 2: Código completo**
```
https://licitacionesya.com/user/estadisticas_ganadores_v2.php?codigo=4321150012345678&modo=codigo
```

**Opción 3: Código parcial**
```
https://licitacionesya.com/user/estadisticas_ganadores_v2.php?codigo=4321&modo=codigo
```

### Búsqueda por Proveedor:

**Por cédula:**
```
https://licitacionesya.com/user/estadisticas_ganadores_v2.php?codigo=3-101-123456&modo=proveedor
```

**Por nombre:**
```
https://licitacionesya.com/user/estadisticas_ganadores_v2.php?codigo=ACME&modo=proveedor
```

---

## 🐛 Interpretando el Panel de Debug

Cuando ejecutes la v2, verás algo como:

```
📋 Información de Debug
- Buscando: 43211500
- Modo: codigo
- Código 8 dígitos: 43211500
- SQL: SELECT ... FROM lineas_adjudicadas la ...
- Parámetros: {"codigo":"%43211500%","codigo_8":"43211500"}
- Resultados encontrados: 45
```

### Si dice "Resultados encontrados: 0"

Verás búsquedas alternativas:
```
- Búsqueda directa en lineas_adjudicadas: 0 registros
- Búsqueda en partidas_licitacion: 15 registros
```

Esto te dice:
- ✅ El código NO existe en `lineas_adjudicadas`
- ✅ Pero SÍ existe en `partidas_licitacion`
- 💡 Significa: Hay licitaciones con ese producto, pero todavía no se han adjudicado

---

## 🚀 Desactivar Debug en Producción

Una vez que todo funcione, edita `estadisticas_ganadores_v2.php`:

```php
// Cambiar de:
$DEBUG_MODE = true;

// A:
$DEBUG_MODE = false;
```

Esto ocultará el panel amarillo de debug a los usuarios.

---

## 📊 Posibles Causas si NO Encuentra Resultados

### 1. **La tabla está vacía**
**Solución:** Importar datos de adjudicaciones

### 2. **El código no existe en la BD**
**Solución:** Verificar con `diagnostico_db.php` qué códigos reales existen

### 3. **El formato del código es diferente**
**Ejemplo:**
- Buscas: `43211500`
- En BD está: `43-21-15-00`
- **Solución:** Busca sin guiones: `43211500`

### 4. **Los datos están en otra tabla**
**Solución:** El diagnóstico te dirá si el código existe en `partidas_licitacion` pero no en `lineas_adjudicadas`

### 5. **Problema de encoding**
**Ejemplo:** Espacios o caracteres especiales
**Solución:** La v2 usa `trim()` para limpiar espacios

---

## 💡 Consejos de Uso

1. **Siempre ejecuta el diagnóstico primero**
   - Te da códigos reales para probar
   - Te dice qué tablas tienen datos

2. **Usa el modo DEBUG**
   - Te muestra exactamente qué está buscando
   - Te ayuda a entender por qué no encuentra resultados

3. **Prueba con códigos de 8 dígitos**
   - Es el estándar UNSPSC
   - Encuentra más resultados

4. **Verifica los logs del servidor**
   - Si hay errores SQL, estarán en el log de PHP
   - `error_log("Error en estadísticas_ganadores: " . $e->getMessage());`

---

## 📞 Siguiente Paso si Sigue Sin Funcionar

Si después de ejecutar el diagnóstico y usar la v2 **todavía no funciona**, necesito que me proporciones:

1. **La salida completa del diagnóstico**
   - Copia todo lo que muestra `diagnostico_db.php`

2. **El panel de debug de la v2**
   - Copia el contenido del panel amarillo

3. **Un código de ejemplo que deberías encontrar**
   - Un código que SABES que existe en la base de datos

Con esa información podré hacer una versión v3 específica para tu estructura de base de datos.

---

## ✅ Checklist de Solución

- [ ] Subir `estadisticas_ganadores_v2.php`
- [ ] Subir `diagnostico_db.php`
- [ ] Ejecutar diagnóstico y verificar que las tablas existen
- [ ] Copiar un código de ejemplo del diagnóstico
- [ ] Probar la v2 con ese código
- [ ] Verificar el panel de debug
- [ ] Si funciona, desactivar DEBUG_MODE
- [ ] Reemplazar el archivo original con la v2

---

## 🎉 Resultado Esperado

Después de seguir estos pasos, deberías ver:
- ✅ Estadísticas de adjudicaciones
- ✅ Gráficos de precios
- ✅ Top 10 de proveedores
- ✅ Top 10 de instituciones
- ✅ Historial completo de adjudicaciones

---

**¿Necesitas más ayuda?**
Ejecuta el diagnóstico y envíame los resultados.
