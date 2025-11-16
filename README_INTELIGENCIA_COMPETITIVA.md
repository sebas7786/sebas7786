# 🔍 Sistema de Inteligencia Competitiva

Sistema completo para analizar el mercado de licitaciones, estudiar la competencia y detectar oportunidades.

## 📍 Ubicación

**URL:** `https://licitacionesya.com/user/estadisticas_ganadores.php`

## 🎯 Funcionalidades Principales

### 1️⃣ **Dos Modos de Búsqueda**

#### Modo 1: Por Código UNSPSC
- Ingresa un código de producto (ej: `2611170292229945`)
- El sistema busca por los **primeros 8 dígitos**
- Encuentra todas las adjudicaciones de ese producto

#### Modo 2: Por Proveedor
- Ingresa cédula jurídica (ej: `3-101-123456`)
- O nombre del proveedor
- Ve todas las adjudicaciones ganadas por ese proveedor

### 2️⃣ **Filtros Avanzados**
- **Fecha desde/hasta**: Analiza períodos específicos
- **Proveedor específico**: Filtra por competidor
- **Institución específica**: Filtra por comprador

---

## 📊 Estadísticas Calculadas

### Métricas Clave:
1. **Total de Adjudicaciones**: Cuántas veces se ha adjudicado
2. **Precio Promedio**: Precio promedio del mercado
3. **Precio Mínimo**: Precio más bajo adjudicado
4. **Precio Máximo**: Precio más alto adjudicado
5. **Monto Total**: Valor total del mercado
6. **Proveedores Únicos**: Cuántos competidores hay

### Análisis de Oportunidad:
- Calcula la **variación de precios** (máximo - mínimo)
- Identifica mercados con **alta competencia** (variación > 50%)
- Muestra el **precio mediano** para referencia

---

## 🏆 Top 10 Proveedores Ganadores

### Información mostrada:
- **Ranking con medallas** (🥇🥈🥉 para top 3)
- Nombre y cédula del proveedor
- Número de adjudicaciones ganadas
- Monto total adjudicado
- **% de participación en el mercado**

### ¿Para qué sirve?
- Identificar a los **competidores principales**
- Ver cuánto mercado controla cada uno
- Analizar estrategias de pricing
- Detectar **monopolios u oligopolios**

---

## 🏛️ Top 10 Instituciones Compradoras

### Información mostrada:
- Nombre y cédula de la institución
- Número de licitaciones realizadas
- Monto total gastado

### ¿Para qué sirve?
- Identificar **clientes potenciales**
- Ver qué instituciones compran más
- Priorizar esfuerzos comerciales
- Analizar frecuencia de compra

---

## 📈 Gráfico de Tendencia de Precios

### Características:
- **Línea temporal** de precios adjudicados
- Visualiza si los precios **suben o bajan**
- Detecta estacionalidad
- Identifica outliers (precios anormales)

### ¿Cómo usarlo?
- Ver si el mercado está **subiendo o bajando**
- Detectar **ciclos** de precios
- Encontrar el **mejor momento** para ofertar
- Validar tu estrategia de pricing

---

## 📋 Tabla de Adjudicaciones Históricas

### Columnas:
1. **Fecha**: Cuándo se adjudicó
2. **SICOP**: Número de licitación (clickeable)
3. **Licitación**: Título de la licitación
4. **Proveedor**: Quién ganó
5. **Institución**: Quién compró
6. **Cantidad**: Cuántas unidades
7. **Precio Unitario**: Precio por unidad
8. **Monto Total**: Precio × Cantidad

### Características:
- Muestra las **primeras 50 adjudicaciones**
- Ordenadas por **fecha más reciente**
- Link directo a cada licitación
- Exportable a CSV (todas las adjudicaciones)

---

## 💡 Casos de Uso Prácticos

### 1. **Preparar una Oferta**
```
1. Buscar código UNSPSC del producto que vas a ofertar
2. Ver el precio promedio y la variación
3. Analizar quién gana normalmente
4. Decidir tu precio estratégico (entre mínimo y promedio)
```

### 2. **Estudiar a un Competidor**
```
1. Cambiar a modo "Buscar por Proveedor"
2. Ingresar nombre o cédula del competidor
3. Ver qué productos oferta
4. Ver sus precios históricos
5. Identificar sus clientes principales
```

### 3. **Encontrar Nuevos Clientes**
```
1. Buscar código de tu producto estrella
2. Ver la tabla de "Top 10 Instituciones Compradoras"
3. Identificar instituciones que NO son tus clientes
4. Hacer prospección comercial con ellas
```

### 4. **Detectar Oportunidades de Mercado**
```
1. Buscar códigos relacionados a tu industria
2. Filtrar por fecha reciente (últimos 6 meses)
3. Ver si hay mercados con pocos proveedores
4. Evaluar entrar a esos nichos
```

### 5. **Validar tu Estrategia de Precios**
```
1. Buscar tu producto
2. Comparar tu precio con el precio promedio
3. Ver la tendencia (¿suben o bajan los precios?)
4. Ajustar tu estrategia
```

---

## 🎨 Diseño Visual

### Colores:
- **Gradiente violeta** para encabezados
- **Badges de colores** para estadísticas
- **Medallas** para top 3 proveedores
- **Gráfico interactivo** con Chart.js

### Layout Responsivo:
- **Desktop**: Grid de 2 columnas
- **Tablet**: Grid adaptativo
- **Móvil**: 1 columna vertical

---

## 🔗 Integración con Otras Tablas

El sistema consulta:
1. ✅ `lineas_adjudicadas` - Datos de adjudicaciones
2. ✅ `licitaciones` - Info de licitaciones
3. ✅ `proveedoras` - Nombres de proveedores
4. ✅ `instituciones_compradoras` - Nombres de instituciones
5. ✅ `partidas_licitacion` - Códigos UNSPSC

---

## 📥 Exportación de Datos

### Botón "Exportar CSV":
- Descarga **TODAS** las adjudicaciones encontradas
- No solo las 50 mostradas en pantalla
- Formato CSV para análisis en Excel/Google Sheets
- Incluye todas las columnas

### ¿Cómo usar el CSV?
1. Abrir en Excel
2. Crear tablas dinámicas
3. Hacer análisis personalizados
4. Compartir con tu equipo

---

## 🚀 Ejemplos de Búsqueda

### Ejemplo 1: Analizar Laptops
```
Código: 43211507 (primeros 8 dígitos de laptops)
Resultado: Ver todos los proveedores que venden laptops al gobierno
```

### Ejemplo 2: Estudiar a un Competidor
```
Modo: Proveedor
Búsqueda: "3-101-123456" o "Nombre Empresa S.A."
Resultado: Ver todo lo que ha ganado ese proveedor
```

### Ejemplo 3: Tendencia de Precios en 2024
```
Código: 26111702 (equipos médicos)
Fecha desde: 2024-01-01
Fecha hasta: 2024-12-31
Resultado: Ver cómo han variado los precios este año
```

---

## 📊 Estadísticas Avanzadas

### Precio Mediano vs Promedio:
- **Mediana**: Valor del medio (más confiable con outliers)
- **Promedio**: Puede distorsionarse con valores extremos

### Variación de Precios:
```
Variación = ((Máximo - Mínimo) / Promedio) × 100

> 50% = Mercado muy competitivo
30-50% = Mercado normal
< 30% = Mercado estable (posible monopolio)
```

---

## 🎯 Estrategias Basadas en Datos

### Si la variación es ALTA (>50%):
- ✅ Hay oportunidad de ganar con precio bajo
- ✅ Mercado fragmentado, sin líder claro
- ⚠️ Mayor riesgo de guerra de precios

### Si la variación es BAJA (<30%):
- ⚠️ Precios estandarizados
- ⚠️ Posible cartel o monopolio
- ✅ Mayor certeza en cotizaciones

### Si un proveedor tiene >40% del mercado:
- 🏆 Es el líder del mercado
- 📚 Estudiar su estrategia
- 🎯 Puede ser difícil competir en precio
- 💡 Buscar diferenciación (calidad, servicio, garantías)

---

## 🔍 Tips de Búsqueda

### Para Códigos UNSPSC:
- Usa los **primeros 8 dígitos** para categorías amplias
- Usa **10+ dígitos** para productos específicos
- Si no encuentras nada, reduce dígitos

### Para Proveedores:
- Busca por **nombre exacto** si lo conoces
- O por **cédula jurídica** completa
- Funciona con búsqueda parcial

---

## 📈 Indicadores Clave de Rendimiento (KPIs)

### Para Proveedores:
1. **Tasa de éxito** = (Adjudicaciones / Total licitaciones participadas)
2. **Precio promedio** vs **precio de mercado**
3. **Crecimiento** de adjudicaciones año a año

### Para Instituciones:
1. **Frecuencia de compra** = Licitaciones por año
2. **Ticket promedio** = Monto promedio por licitación
3. **Estacionalidad** = Meses con más licitaciones

---

## 🛠️ Mantenimiento

### Actualización de Datos:
- Los datos se actualizan cuando importas `LineasAdjudicadas.csv`
- Usa `admin/importar_lineas_adjudicadas.php`

### Performance:
- Consulta limitada a **500 adjudicaciones** para velocidad
- Usa **índices** en `numero_sicop`, `codigo_producto`, `cedula_proveedor`

---

## 🆘 Solución de Problemas

### "No se encontraron resultados"
- ✅ Verifica que el código sea correcto
- ✅ Reduce dígitos del código UNSPSC
- ✅ Verifica que haya datos importados en `lineas_adjudicadas`

### Gráfico no aparece
- ✅ Verifica que haya al menos 2 adjudicaciones
- ✅ Verifica que tengan fechas válidas
- ✅ Revisa la consola del navegador (F12)

### Proveedores sin nombre
- ✅ Importa datos de proveedores en `proveedoras`
- ✅ Verifica que las cédulas coincidan

---

## 🔮 Próximas Mejoras Sugeridas

1. **Alertas por email** cuando se adjudica un producto de interés
2. **Comparación de 2 proveedores** lado a lado
3. **Predicción de precios** con machine learning
4. **Dashboard ejecutivo** con resumen general
5. **Análisis de temporalidad** (¿qué meses se licita más?)
6. **Mapa de calor** de instituciones por provincia

---

**Versión:** 1.0
**Fecha:** 2025-11-16
**Estado:** ✅ Listo para producción