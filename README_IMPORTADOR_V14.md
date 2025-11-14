# 🚀 Importador SICOP v14.0 - Detalle de Carteles

## 📋 ¿Qué hace este importador?

Este sistema procesa el archivo **"Detalle de Carteles"** que contiene **DOS tipos de información mezclada**:

1. **Información de Carteles** (Licitaciones generales)
2. **Líneas/Partidas** de cada cartel

## 🎯 Estructura del Archivo CSV

### Tipo 1: Filas de Carteles (15 columnas)
```
Número sicop, Cédula Institución, Fecha Publicación, Número Procedimiento,
Cod Unidad Compra, Nombre Unidad Compra, Tipo Procedimiento,
Modalidad Procedimiento, Excepción CD, Descripción, Pago Adelantado Pymes,
Fecha Invitación, Fecha Inicio Recepción, Fecha Cierre Recepción, Fecha Apertura
```

### Tipo 2: Filas de Líneas (8 columnas)
```
Número sicop, No Linea, Número partida, Cantidad Solicitada,
Precio Unitario Estimado, Tipo Moneda, Tipo Cambio USD, Código Identificación
```

## 🔧 Campos Nuevos Agregados a la Base de Datos

### Tabla `licitaciones`
- `cod_unidad_compra` (VARCHAR 50) - Código de la unidad de compra
- `nombre_unidad_compra` (VARCHAR 255) - Nombre de la unidad de compra
- `pago_adelantado_pymes` (ENUM 'S','N') - Si permite pago adelantado a PYMEs
- `fecha_invitacion` (DATETIME) - Fecha de invitación
- `fecha_inicio_recepcion` (DATETIME) - Fecha de inicio de recepción de ofertas

### Tabla `partidas_licitacion`
- `tipo_cambio_usd` (DECIMAL 10,4) - Tipo de cambio a dólares

## 📂 Instalación y Uso

### 1. Ubicación del Archivo
Coloca el archivo `importador_sicop_v14.php` en:
```
/tu-proyecto/admin/importador_sicop_v14.php
```

### 2. Requisitos
- PHP 7.2 o superior
- Base de datos MySQL/MariaDB
- Archivo de configuración: `/config/db.php`

### 3. Acceso
```
http://tu-dominio.com/admin/importador_sicop_v14.php
```

## 🚀 Cómo Usar

### Paso 1: Preparar el Archivo CSV
- Descarga el archivo "Detalle de Carteles" del sistema SICOP
- No lo modifiques, déjalo tal cual viene

### Paso 2: Subir el Archivo
1. Accede al importador
2. Haz clic en "Examinar Archivo"
3. Selecciona el archivo CSV
4. Haz clic en "INICIAR IMPORTACIÓN"

### Paso 3: Esperar Resultados
- El sistema detectará automáticamente los tipos de fila
- Mostrará un log en tiempo real
- Al finalizar, verás las estadísticas completas

## 📊 ¿Qué Hace el Importador?

### Detección Automática
- ✅ Detecta automáticamente si una fila es de tipo "Cartel" o "Línea"
- ✅ Lee los headers dinámicamente
- ✅ Maneja caracteres especiales y tildes

### Procesamiento de Carteles
- ✅ Crea nuevas licitaciones si no existen
- ✅ Actualiza licitaciones existentes
- ✅ Preserva datos que ya existen (no los sobrescribe con NULL)

### Procesamiento de Líneas
- ✅ Asocia líneas con la licitación correspondiente
- ✅ Calcula montos estimados automáticamente
- ✅ Maneja múltiples monedas y tipos de cambio

## 🔍 Ejemplo de Procesamiento

### Entrada (CSV):
```csv
Número sicop,Cédula Institución,Fecha Publicación,...
2024LA-000001-01,3101234567,15/01/2024,...

Número sicop,No Linea,Número partida,...
2024LA-000001-01,1,1,...
2024LA-000001-01,2,1,...
```

### Resultado:
- **1 licitación** creada/actualizada en tabla `licitaciones`
- **2 partidas** insertadas en tabla `partidas_licitacion`

## 📈 Estadísticas Mostradas

El sistema muestra:
- **Carteles detectados**: Total de filas de tipo cartel
- **Líneas detectadas**: Total de filas de tipo línea
- **Licitaciones insertadas**: Nuevas licitaciones creadas
- **Licitaciones actualizadas**: Licitaciones existentes actualizadas
- **Partidas insertadas**: Nuevas partidas creadas
- **Errores**: Cantidad de errores encontrados

## ⚠️ Manejo de Errores

### Errores Comunes
1. **"Archivo no encontrado"**
   - Verifica que subiste el archivo correctamente

2. **"No se pudieron leer los headers"**
   - El archivo debe estar en formato CSV
   - Verifica el delimitador (coma o punto y coma)

3. **"SICOP no encontrado"**
   - La primera columna debe tener el número SICOP

### Registro de Errores
- Todos los errores se muestran al final del proceso
- Se guardan en `$errores_detallados`
- Incluyen el número SICOP afectado

## 🔐 Seguridad

- ✅ Usa transacciones de base de datos
- ✅ Valida datos antes de insertar
- ✅ Previene duplicados con `ON DUPLICATE KEY UPDATE`
- ✅ Limpia caracteres especiales

## 🎨 Características Adicionales

### Conversión de Fechas
- Convierte formato DD/MM/YYYY a YYYY-MM-DD
- Maneja fechas con hora
- Acepta formatos variados

### Conversión de Números
- Limpia símbolos de moneda
- Maneja formato europeo (1.234,56)
- Maneja formato americano (1,234.56)
- Convierte a decimal correctamente

### Log Detallado
- Muestra progreso en tiempo real
- Marca cada 50 carteles procesados
- Colores según tipo de mensaje:
  - 🟢 Verde: Éxito
  - 🔵 Azul: Información
  - 🟡 Amarillo: Advertencia
  - 🔴 Rojo: Error

## 🆘 Soporte

Si encuentras problemas:
1. Revisa el log de errores
2. Verifica que el archivo CSV esté completo
3. Comprueba que la base de datos tenga los campos necesarios
4. Contacta al administrador del sistema

## 📝 Notas Importantes

- ⚠️ El proceso puede tardar varios minutos para archivos grandes
- ⚠️ No cierres el navegador mientras procesa
- ⚠️ Haz respaldo de la base de datos antes de importar
- ⚠️ Verifica los resultados después de la importación

## 🔄 Versiones

- **v14.0** (2025-11-14): Versión inicial con detección automática de filas mixtas
