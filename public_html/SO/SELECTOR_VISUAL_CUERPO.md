# 🎨 Selector Visual Interactivo de Partes del Cuerpo

## 📋 Descripción

El selector visual de partes del cuerpo es un componente interactivo que permite a los usuarios seleccionar visualmente las partes del cuerpo afectadas en un accidente laboral haciendo clic directamente en una figura humana.

## 🆕 Versión 2.0 (Advanced)

Esta versión mejorada incluye:
- **Dos vistas del cuerpo:** Frontal y posterior para una selección más precisa
- **Apariencia realista:** Degradado de piel natural con efectos de sombra
- **57 partes seleccionables:** Mayor detalle con específicación izquierda/derecha
- **Animaciones avanzadas:** Efectos de pulso en partes seleccionadas
- **Mapeo inteligente:** Sistema que convierte selecciones detalladas a categorías del formulario

---

## ✨ Características

### 🎯 **Interactividad Visual**
- Cuerpo humano completo dibujado en SVG
- Partes clicables con efecto hover
- Feedback visual inmediato al seleccionar
- Partes seleccionadas se marcan en **rojo**
- Partes al pasar el mouse se marcan en **amarillo**

### 📱 **Responsive**
- Se adapta a cualquier tamaño de pantalla
- Diseño optimizado para móviles y tablets
- SVG escalable sin pérdida de calidad

### 🎨 **Visual Atractivo**
- Diseño moderno y profesional
- Animaciones suaves
- Efectos de sombra (drop-shadow)
- Colores intuitivos

### ✅ **Funcionalidad Completa**
- Lista de partes seleccionadas en tiempo real
- Botón para limpiar toda la selección
- Compatible con el envío del formulario
- Validación integrada

---

## 🔧 Componentes del Sistema

### 1. **body_selector_advanced.js**
Archivo JavaScript principal que contiene:
- Clase `BodySelectorAdvanced`
- Renderizado de dos SVG del cuerpo humano (frontal y posterior)
- Vista realista con degradado de piel
- Más de 50 partes del cuerpo seleccionables
- Lógica de selección/deselección con animaciones
- Actualización de checkboxes ocultos
- Gestión del estado con mapeo inteligente

### 2. **Modificación en nuevo_aviso.php**
- Contenedor `<div id="body-selector-advanced"></div>`
- Checkboxes ocultos para envío del formulario
- Carga del script `body_selector_advanced.js`
- Instrucciones de uso

---

## 🎯 Cómo Funciona

### Paso 1: Usuario Hace Clic
```
Usuario → Clic en parte del cuerpo → Parte se marca en ROJO
```

### Paso 2: Sistema Actualiza
```
Clic → BodySelector actualiza Set → Actualiza Display → Actualiza Checkboxes
```

### Paso 3: Envío del Formulario
```
Checkboxes ocultos (sincronizados) → POST al servidor → Procesamiento normal
```

---

## 📐 Partes del Cuerpo Disponibles

### 🔴 Vista Frontal (26 partes)
1. ✅ **Cabeza** - Región craneal completa
2. ✅ **Cara** - Rostro
3. ✅ **Cuello** - Zona cervical frontal
4. ✅ **Pecho** - Torso anterior
5. ✅ **Abdomen** - Zona abdominal
6. ✅ **Hombro Izquierdo** - Articulación superior izquierda
7. ✅ **Hombro Derecho** - Articulación superior derecha
8. ✅ **Brazo Izquierdo** - Brazo superior izquierdo
9. ✅ **Brazo Derecho** - Brazo superior derecho
10. ✅ **Codo Izquierdo** - Articulación del codo izquierdo
11. ✅ **Codo Derecho** - Articulación del codo derecho
12. ✅ **Antebrazo Izquierdo** - Parte inferior del brazo izquierdo
13. ✅ **Antebrazo Derecho** - Parte inferior del brazo derecho
14. ✅ **Muñeca Izquierda** - Articulación de la muñeca izquierda
15. ✅ **Muñeca Derecha** - Articulación de la muñeca derecha
16. ✅ **Mano Izquierda** - Mano izquierda completa
17. ✅ **Mano Derecha** - Mano derecha completa
18. ✅ **Dedos Izquierdos** - Dedos de la mano izquierda
19. ✅ **Dedos Derechos** - Dedos de la mano derecha
20. ✅ **Cadera** - Región pélvica
21. ✅ **Ingle** - Zona inguinal
22. ✅ **Muslo Izquierdo** - Parte superior de la pierna izquierda
23. ✅ **Muslo Derecho** - Parte superior de la pierna derecha
24. ✅ **Rodilla Izquierda** - Articulación de la rodilla izquierda
25. ✅ **Rodilla Derecha** - Articulación de la rodilla derecha
26. ✅ **Pantorrilla Izquierda** - Parte inferior de la pierna izquierda
27. ✅ **Pantorrilla Derecha** - Parte inferior de la pierna derecha
28. ✅ **Tobillo Izquierdo** - Articulación del tobillo izquierdo
29. ✅ **Tobillo Derecho** - Articulación del tobillo derecho
30. ✅ **Pie Izquierdo** - Pie izquierdo completo
31. ✅ **Pie Derecho** - Pie derecho completo
32. ✅ **Dedos Pie Izquierdo** - Dedos del pie izquierdo
33. ✅ **Dedos Pie Derecho** - Dedos del pie derecho

### 🔵 Vista Posterior (24 partes)
1. ✅ **Nuca** - Parte posterior de la cabeza
2. ✅ **Cuello Posterior** - Zona cervical posterior
3. ✅ **Hombros Posteriores** - Ambos hombros (vista posterior)
4. ✅ **Espalda Alta** - Parte superior de la espalda
5. ✅ **Espalda Media** - Parte media de la espalda
6. ✅ **Lumbar** - Zona lumbar (espalda baja)
7. ✅ **Glúteos** - Región glútea
8. ✅ **Brazo Posterior Izquierdo** - Parte posterior del brazo izquierdo
9. ✅ **Brazo Posterior Derecho** - Parte posterior del brazo derecho
10. ✅ **Codo Posterior Izquierdo** - Parte posterior del codo izquierdo
11. ✅ **Codo Posterior Derecho** - Parte posterior del codo derecho
12. ✅ **Antebrazo Posterior Izquierdo** - Parte posterior del antebrazo izquierdo
13. ✅ **Antebrazo Posterior Derecho** - Parte posterior del antebrazo derecho
14. ✅ **Mano Posterior Izquierda** - Vista posterior de la mano izquierda
15. ✅ **Mano Posterior Derecha** - Vista posterior de la mano derecha
16. ✅ **Muslo Posterior Izquierdo** - Parte posterior del muslo izquierdo
17. ✅ **Muslo Posterior Derecho** - Parte posterior del muslo derecho
18. ✅ **Rodilla Posterior Izquierda** - Parte posterior de la rodilla izquierda
19. ✅ **Rodilla Posterior Derecha** - Parte posterior de la rodilla derecha
20. ✅ **Gemelo Izquierdo** - Pantorrilla izquierda (vista posterior)
21. ✅ **Gemelo Derecho** - Pantorrilla derecha (vista posterior)
22. ✅ **Talón Izquierdo** - Parte posterior del pie izquierdo
23. ✅ **Talón Derecho** - Parte posterior del pie derecho
24. ✅ **Otra** - Opción personalizable

**Total: 57 partes del cuerpo seleccionables**

---

## 🎨 Código de Colores

| Estado | Color | Descripción |
|--------|-------|-------------|
| **Normal** | Gris (`#e0e0e0`) | Parte no seleccionada |
| **Hover** | Amarillo (`#ffd54f`) | Parte bajo el cursor |
| **Seleccionado** | Rojo (`#ff5252`) | Parte afectada/seleccionada |

---

## 💻 Uso en el Formulario

### HTML
```html
<!-- Contenedor donde se renderiza el selector -->
<div id="body-selector-advanced"></div>

<!-- Checkboxes ocultos (sincronizados automáticamente) -->
<div style="display: none;">
    <input type="checkbox" name="partes_cuerpo[]" value="Cabeza">
    <input type="checkbox" name="partes_cuerpo[]" value="Cuello">
    <!-- ... más partes ... -->
</div>
```

### JavaScript
```javascript
// Inicialización automática al cargar la página
let bodySelectorAdvanced;
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('body-selector-advanced')) {
        bodySelectorAdvanced = new BodySelectorAdvanced('body-selector-advanced');
    }
});

// Obtener partes seleccionadas
const partesSeleccionadas = bodySelectorAdvanced.getSelectedParts();
console.log(partesSeleccionadas); // ["Cabeza", "Brazo Izquierdo", "Rodilla Derecha"]

// Limpiar selección
bodySelectorAdvanced.clearAll();
```

---

## 🎛️ Métodos de la Clase BodySelectorAdvanced

### `constructor(containerId)`
Inicializa el selector avanzado en el contenedor especificado.

### `render()`
Renderiza dos SVG del cuerpo humano (frontal y posterior) con apariencia realista y la interfaz completa.

### `togglePart(element, partName, side)`
Selecciona o deselecciona una parte específica del cuerpo. El parámetro `side` indica si es la vista frontal o posterior.

### `updateDisplay()`
Actualiza la lista visual de partes seleccionadas con contador y etiquetas coloridas.

### `updateHiddenCheckboxes()`
Sincroniza los checkboxes ocultos con las partes seleccionadas usando un sistema de mapeo inteligente que convierte las 57 partes detalladas a las 14 categorías principales del formulario.

### `clearAll()`
Limpia toda la selección en ambas vistas (frontal y posterior).

### `getSelectedParts()`
Retorna un array con las partes seleccionadas detalladas (ej: ["Cabeza", "Brazo Izquierdo", "Rodilla Derecha"]).

---

## 🎯 Ventajas del Selector Visual

### ✅ **Para el Usuario**
- Más intuitivo que checkboxes
- Visual y fácil de entender
- Reduce errores de selección
- Más rápido de usar

### ✅ **Para el Sistema**
- Compatible con el backend existente
- No requiere cambios en la base de datos
- Funciona con JavaScript desactivado (fallback a checkboxes)
- Validación integrada

### ✅ **Para Móviles**
- Touch-friendly
- Responsive
- Optimizado para pantallas táctiles

---

## 📱 Responsive Design

El selector se adapta a diferentes tamaños de pantalla:

### Desktop (> 768px)
```
┌─────────────────────────────┬──────────────────┐
│                             │                  │
│    Figura del Cuerpo        │  Partes          │
│    (SVG Interactivo)        │  Seleccionadas   │
│                             │                  │
└─────────────────────────────┴──────────────────┘
```

### Mobile (< 768px)
```
┌──────────────────────────┐
│  Figura del Cuerpo       │
│  (SVG Interactivo)       │
└──────────────────────────┘
┌──────────────────────────┐
│  Partes Seleccionadas    │
└──────────────────────────┘
```

---

## 🔍 Validación

El selector integra validación automática:

```javascript
// Al enviar el formulario
if (partesSeleccionadas.length === 0) {
    alert('Por favor seleccione al menos una parte del cuerpo afectada.');
    return false;
}
```

---

## 🎨 Personalización

### Cambiar Colores
Edita el archivo `body_selector.js` en la sección `addStyles()`:

```javascript
.body-part {
    fill: #e0e0e0;        // Color normal
    stroke: #333;         // Borde
}

.body-part:hover {
    fill: #ffd54f;        // Color hover
}

.body-part.selected {
    fill: #ff5252;        // Color seleccionado
}
```

### Agregar Más Partes
1. Agrega el elemento SVG en el método `render()`
2. Agrega el checkbox correspondiente en el HTML
3. El sistema lo detectará automáticamente

---

## 🐛 Solución de Problemas

### El selector no aparece
**Causa:** El script no se cargó correctamente
**Solución:** Verifica que `body_selector.js` esté en la misma carpeta que `nuevo_aviso.php`

### Las partes no se marcan
**Causa:** Conflicto con otros scripts
**Solución:** Verifica la consola del navegador (F12) para ver errores

### No se envían las partes seleccionadas
**Causa:** Checkboxes ocultos no se sincronizan
**Solución:** Verifica que los valores de `data-part` coincidan con los valores de los checkboxes

---

## 📊 Compatibilidad

| Navegador | Versión Mínima | Estado |
|-----------|----------------|--------|
| Chrome | 60+ | ✅ Completo |
| Firefox | 55+ | ✅ Completo |
| Safari | 11+ | ✅ Completo |
| Edge | 79+ | ✅ Completo |
| IE | 11 | ⚠️ Fallback a checkboxes |

---

## 🚀 Características Implementadas y Mejoras Futuras

### ✅ Implementado (Versión Advanced)
- [x] Vista frontal y posterior del cuerpo
- [x] Apariencia realista con degradado de piel
- [x] 57 partes del cuerpo seleccionables
- [x] Animaciones suaves y efectos visuales
- [x] Contador de partes seleccionadas
- [x] Sistema de mapeo inteligente para sincronización con formulario
- [x] Diseño responsive para móviles y tablets
- [x] Feedback visual con hover y selección

### 🔮 Posibles Mejoras Futuras
- [ ] Intensidad de la lesión por parte (leve, moderada, grave)
- [ ] Anotaciones personalizadas en cada parte
- [ ] Exportar imagen PNG/PDF con partes marcadas
- [ ] Historial de lesiones previas del empleado
- [ ] Integración con reportes médicos
- [ ] Zoom en partes específicas para mayor detalle
- [ ] Vista 3D rotativa del cuerpo humano
- [ ] Indicadores de tipo de lesión (corte, golpe, quemadura, etc.)

---

## 📝 Licencia

Este componente es parte del Sistema de Salud Ocupacional de EXPENIC RHS.

---

## 👤 Soporte

Para reportar problemas o sugerir mejoras, contacta al equipo de desarrollo.

---

**Versión:** 2.0 (Advanced)
**Última actualización:** Noviembre 2024
**Autor:** Sistema de Salud Ocupacional
**Archivo:** body_selector_advanced.js
