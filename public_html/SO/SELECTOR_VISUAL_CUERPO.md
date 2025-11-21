# 🎨 Selector Visual Interactivo de Partes del Cuerpo

## 📋 Descripción

El selector visual de partes del cuerpo es un componente interactivo que permite a los usuarios seleccionar visualmente las partes del cuerpo afectadas en un accidente laboral haciendo clic directamente en una figura humana.

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

### 1. **body_selector.js**
Archivo JavaScript principal que contiene:
- Clase `BodySelector`
- Renderizado del SVG del cuerpo humano
- Lógica de selección/deselección
- Actualización de checkboxes ocultos
- Gestión del estado

### 2. **Modificación en nuevo_aviso.php**
- Contenedor `<div id="body-selector"></div>`
- Checkboxes ocultos para envío del formulario
- Carga del script `body_selector.js`
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

El selector incluye **14 partes del cuerpo:**

1. ✅ **Cabeza** - Región craneal
2. ✅ **Cuello** - Zona cervical
3. ✅ **Espalda** - Torso posterior
4. ✅ **Hombro** - Ambos hombros (izquierdo y derecho)
5. ✅ **Brazo** - Ambos brazos completos
6. ✅ **Codo** - Ambos codos
7. ✅ **Mano** - Ambas manos
8. ✅ **Dedos** - Dedos de ambas manos
9. ✅ **Cadera** - Región pélvica
10. ✅ **Pierna** - Ambas piernas completas
11. ✅ **Rodilla** - Ambas rodillas
12. ✅ **Tobillo** - Ambos tobillos
13. ✅ **Pie** - Ambos pies
14. ✅ **Otra** - Opción personalizable

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
<div id="body-selector"></div>

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
let bodySelector;
document.addEventListener('DOMContentLoaded', function() {
    bodySelector = new BodySelector('body-selector');
});

// Obtener partes seleccionadas
const partesSeleccionadas = bodySelector.getSelectedParts();
console.log(partesSeleccionadas); // ["Cabeza", "Brazo", "Pierna"]

// Limpiar selección
bodySelector.clearAll();
```

---

## 🎛️ Métodos de la Clase BodySelector

### `constructor(containerId)`
Inicializa el selector en el contenedor especificado.

### `render()`
Renderiza el SVG del cuerpo humano y la interfaz.

### `togglePart(element, partName)`
Selecciona o deselecciona una parte del cuerpo.

### `updateDisplay()`
Actualiza la lista visual de partes seleccionadas.

### `updateHiddenCheckboxes()`
Sincroniza los checkboxes ocultos con las partes seleccionadas.

### `clearAll()`
Limpia toda la selección.

### `getSelectedParts()`
Retorna un array con las partes seleccionadas.

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

## 🚀 Mejoras Futuras

Posibles mejoras para versiones futuras:

- [ ] Vista frontal y posterior del cuerpo
- [ ] Intensidad de la lesión por parte
- [ ] Anotaciones personalizadas
- [ ] Exportar imagen con partes marcadas
- [ ] Historial de lesiones previas
- [ ] Integración con reportes médicos
- [ ] Múltiples niveles de lesión (leve, moderada, grave)
- [ ] Zoom en partes específicas

---

## 📝 Licencia

Este componente es parte del Sistema de Salud Ocupacional de EXPENIC RHS.

---

## 👤 Soporte

Para reportar problemas o sugerir mejoras, contacta al equipo de desarrollo.

---

**Versión:** 1.0
**Última actualización:** Noviembre 2024
**Autor:** Sistema de Salud Ocupacional
