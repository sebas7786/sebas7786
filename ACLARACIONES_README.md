# Sistema de Aclaraciones SICOP

Sistema completo para importar y visualizar solicitudes de aclaraciones de licitaciones del Observatorio SICOP de Costa Rica.

## 📋 Descripción

El sistema de aclaraciones permite:
- ✅ Importar aclaraciones desde CSV del Observatorio SICOP
- ✅ Ver aclaraciones en el detalle de cada licitación
- ✅ Distinguir entre aclaraciones respondidas y pendientes
- ✅ Estadísticas de aclaraciones por licitación y empresa

## 📂 Archivos del Sistema

### 1. Base de Datos
**Archivo:** `sql/crear_tabla_aclaraciones.sql`

Crea la tabla `aclaraciones_licitacion` con los siguientes campos:
- `numero_cartel` - Número SICOP del cartel
- `no_cartel` - Número de cartel descriptivo
- `titulo` - Título de la licitación
- `fecha_solicitud` - Fecha en que se hizo la solicitud
- `numero_aclaracion` - Número identificador de la aclaración
- `solicitante` - Nombre del solicitante
- `cedula_empresa_proveedora` - Cédula de la empresa
- `estado_respuesta` - Estado de la respuesta
- `numero_respuesta` - Número de la respuesta
- `fecha_respuesta` - Fecha en que se respondió

### 2. Importador
**Archivo:** `importador_aclaraciones.php`

Script para importar el CSV de aclaraciones desde el Observatorio SICOP.

### 3. Visualización
**Archivo:** `licitacion_detalle_CORREGIDO.php`

Página de detalle de licitación actualizada con sección de aclaraciones.

### 4. Pruebas
**Archivo:** `probar_aclaraciones.php`

Script de verificación que muestra estadísticas y ejemplos de aclaraciones importadas.

## 🚀 Instalación

### Paso 1: Crear la Tabla

Ejecuta el script SQL para crear la tabla:

```bash
mysql -u usuario -p nombre_bd < sql/crear_tabla_aclaraciones.sql
```

O desde MySQL:

```sql
SOURCE sql/crear_tabla_aclaraciones.sql;
```

### Paso 2: Descargar CSV del Observatorio

1. Accede al [Observatorio SICOP](https://observatoriosicop.hacienda.go.cr/)
2. Ve a la sección de **Datos Abiertos**
3. Descarga el archivo **"Aclaraciones.csv"**
4. Guárdalo en la raíz del proyecto

### Paso 3: Importar Datos

#### Desde Línea de Comandos (CLI):
```bash
php importador_aclaraciones.php Aclaraciones.csv
```

#### Desde Navegador:
```
http://tu-dominio.com/importador_aclaraciones.php?archivo=Aclaraciones.csv
```

### Paso 4: Verificar Importación

Accede al script de prueba:
```
http://tu-dominio.com/probar_aclaraciones.php
```

Deberías ver:
- Total de aclaraciones importadas
- Licitaciones con aclaraciones
- Aclaraciones respondidas vs pendientes
- Top licitaciones con más aclaraciones

## 📊 Ejemplo de Salida del Importador

```
═══════════════════════════════════════════════════════════════════════
IMPORTADOR DE ACLARACIONES SICOP
═══════════════════════════════════════════════════════════════════════

📂 Archivo: Aclaraciones.csv
📏 Tamaño: 1,234.56 KB
🔤 Encoding detectado: UTF-8

📋 Columnas detectadas (10):
   [0] Número Cartel
   [1] No Cartel
   [2] Título
   [3] Fecha Solicitud
   [4] Número Aclaración
   [5] Solicitante
   [6] Cédula Empresa Proveedora
   [7] Estado Respuesta
   [8] Número Respuesta
   [9] Fecha Respuesta

⏳ Procesando registros...

   Procesadas: 100 | Insertadas: 98 | Actualizadas: 2 | Errores: 0
   Procesadas: 200 | Insertadas: 195 | Actualizadas: 5 | Errores: 0
   ...

═══════════════════════════════════════════════════════════════════════
RESUMEN DE IMPORTACIÓN
═══════════════════════════════════════════════════════════════════════

📊 Total de filas procesadas: 523
✅ Aclaraciones insertadas:   520
🔄 Aclaraciones actualizadas: 3
❌ Errores:                   0

📈 ESTADÍSTICAS DE LA BASE DE DATOS:
   Total aclaraciones:            523
   Licitaciones con aclaraciones: 287
   Respondidas:                   498
   Pendientes:                    25

✅ Importación completada!
```

## 🖥️ Visualización en licitacion_detalle.php

Cuando accedes al detalle de una licitación que tiene aclaraciones, verás:

```
┌─────────────────────────────────────────────────────────────┐
│ [ 7. Aclaraciones ]                                         │
├─────────────────────────────────────────────────────────────┤
│ Total de aclaraciones: 5                                    │
│ ✓ Respondidas: 4    ⏳ Pendientes: 1                        │
│                                                             │
│ ┌──────────────────────────────────────────────────────┐   │
│ │ Fecha Sol. │ N° Aclaración │ Solicitante │ Estado   │   │
│ ├──────────────────────────────────────────────────────┤   │
│ │ 15/11/2025 │ ACL-001       │ Empresa XYZ │ ✓ Resp.  │   │
│ │ 14/11/2025 │ ACL-002       │ Proveedor A │ ⏳ Pend.  │   │
│ └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

### Características de Visualización:

1. **Contador Total**: Muestra el número total de aclaraciones
2. **Estadísticas**: Respondidas vs Pendientes con colores distintivos
3. **Tabla Detallada** con:
   - Fecha de solicitud
   - Número de aclaración
   - Solicitante
   - Cédula de empresa
   - Estado (con badges verde/amarillo)
   - Número de respuesta
   - Fecha de respuesta (en verde si existe)

4. **Estados Visuales**:
   - ✓ **Verde** = Respondida
   - ⏳ **Amarillo** = Pendiente

## 🔄 Actualización de Datos

Para actualizar las aclaraciones con nuevos datos:

1. Descarga el CSV actualizado del Observatorio SICOP
2. Ejecuta nuevamente el importador:
   ```bash
   php importador_aclaraciones.php Aclaraciones.csv
   ```

El importador usa **`ON DUPLICATE KEY UPDATE`**, por lo que:
- ✅ Nuevas aclaraciones se **insertan**
- 🔄 Aclaraciones existentes se **actualizan** (si cambió el estado)

## 📝 Estructura del CSV

El CSV de Aclaraciones debe tener las siguientes columnas:

| Índice | Columna                      | Descripción                          |
|--------|------------------------------|--------------------------------------|
| 0      | Número Cartel                | Identificador SICOP (11-14 dígitos)  |
| 1      | No Cartel                    | Número descriptivo del cartel        |
| 2      | Título                       | Título de la licitación              |
| 3      | Fecha Solicitud              | Fecha/hora de la solicitud           |
| 4      | Número Aclaración            | ID único de la aclaración            |
| 5      | Solicitante                  | Nombre del solicitante               |
| 6      | Cédula Empresa Proveedora    | Cédula de la empresa                 |
| 7      | Estado Respuesta             | Estado (Respondida, Pendiente, etc)  |
| 8      | Número Respuesta             | ID de la respuesta                   |
| 9      | Fecha Respuesta              | Fecha/hora de la respuesta           |

## 🔍 Queries Útiles

### Ver todas las aclaraciones de una licitación específica:
```sql
SELECT *
FROM aclaraciones_licitacion
WHERE numero_cartel = '20251100270'
ORDER BY fecha_solicitud DESC;
```

### Licitaciones con más aclaraciones:
```sql
SELECT
    numero_cartel,
    COUNT(*) as total_aclaraciones,
    SUM(CASE WHEN fecha_respuesta IS NOT NULL THEN 1 ELSE 0 END) as respondidas
FROM aclaraciones_licitacion
GROUP BY numero_cartel
ORDER BY total_aclaraciones DESC
LIMIT 10;
```

### Aclaraciones pendientes de respuesta:
```sql
SELECT *
FROM aclaraciones_licitacion
WHERE fecha_respuesta IS NULL
ORDER BY fecha_solicitud DESC;
```

### Empresas más activas en solicitudes:
```sql
SELECT
    cedula_empresa_proveedora,
    solicitante,
    COUNT(*) as total_solicitudes
FROM aclaraciones_licitacion
WHERE cedula_empresa_proveedora IS NOT NULL
GROUP BY cedula_empresa_proveedora, solicitante
ORDER BY total_solicitudes DESC
LIMIT 10;
```

## 🐛 Troubleshooting

### Error: "La tabla aclaraciones_licitacion no existe"
**Solución:** Ejecuta el script SQL de creación de tabla:
```bash
mysql -u usuario -p nombre_bd < sql/crear_tabla_aclaraciones.sql
```

### Error: "El archivo Aclaraciones.csv no existe"
**Solución:** Verifica que el archivo CSV esté en la ubicación correcta:
```bash
ls -lh Aclaraciones.csv
```

### No se muestran aclaraciones en licitacion_detalle.php
**Solución:**
1. Verifica que la tabla tenga datos: `SELECT COUNT(*) FROM aclaraciones_licitacion`
2. Verifica que el `numero_cartel` coincida con el `numero_sicop` de la licitación
3. Revisa los logs de error de PHP

### Encoding incorrecto (caracteres raros)
**Solución:** El importador detecta automáticamente UTF-8, ISO-8859-1 y Windows-1252. Si aún hay problemas, convierte manualmente el CSV:
```bash
iconv -f ISO-8859-1 -t UTF-8 Aclaraciones.csv > Aclaraciones_UTF8.csv
```

## 📌 Notas Importantes

1. **Duplicados**: El sistema usa `numero_cartel` + `numero_aclaracion` como clave única
2. **Fechas**: Soporta múltiples formatos (d/m/Y, Y-m-d, con o sin hora)
3. **Performance**: Tabla indexada por `numero_cartel` para búsquedas rápidas
4. **Encoding**: Detecta y convierte automáticamente UTF-8, ISO-8859-1, Windows-1252

## 📧 Soporte

Para reportar problemas o sugerencias relacionadas con el sistema de aclaraciones, contacta al administrador del sistema.

## ✅ Checklist de Implementación

- [x] Crear tabla `aclaraciones_licitacion`
- [x] Crear importador `importador_aclaraciones.php`
- [x] Actualizar `licitacion_detalle_CORREGIDO.php`
- [x] Crear script de prueba `probar_aclaraciones.php`
- [x] Documentar el sistema
- [ ] Importar primer CSV de aclaraciones
- [ ] Verificar visualización en licitaciones
- [ ] Configurar actualización automática (cron)

---

**Última actualización:** 15 de noviembre de 2025
**Versión:** 1.0
