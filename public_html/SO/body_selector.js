/**
 * Body Selector - Selector visual de partes del cuerpo
 * Para formulario de avisos de accidentes
 */

class BodySelector {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.selectedParts = new Set();
        this.init();
    }

    init() {
        this.render();
        this.attachEventListeners();
    }

    render() {
        this.container.innerHTML = `
            <div class="body-selector-wrapper">
                <div class="body-selector-container">
                    <svg viewBox="0 0 400 800" class="body-svg">
                        <!-- Cabeza -->
                        <ellipse cx="200" cy="80" rx="50" ry="60" class="body-part" data-part="Cabeza" />
                        <text x="200" y="85" class="body-label">Cabeza</text>

                        <!-- Cuello -->
                        <rect x="185" y="135" width="30" height="35" class="body-part" data-part="Cuello" />
                        <text x="200" y="157" class="body-label">Cuello</text>

                        <!-- Hombro Izquierdo -->
                        <ellipse cx="150" cy="185" rx="35" ry="25" class="body-part" data-part="Hombro" />
                        <text x="150" y="190" class="body-label">Hombro</text>

                        <!-- Hombro Derecho -->
                        <ellipse cx="250" cy="185" rx="35" ry="25" class="body-part" data-part="Hombro" />
                        <text x="250" y="190" class="body-label">Hombro</text>

                        <!-- Espalda/Torso -->
                        <rect x="165" y="170" width="70" height="100" rx="10" class="body-part" data-part="Espalda" />
                        <text x="200" y="225" class="body-label">Espalda</text>

                        <!-- Brazo Izquierdo -->
                        <rect x="115" y="210" width="25" height="90" rx="12" class="body-part" data-part="Brazo" />
                        <text x="127" y="260" class="body-label">Brazo</text>

                        <!-- Brazo Derecho -->
                        <rect x="260" y="210" width="25" height="90" rx="12" class="body-part" data-part="Brazo" />
                        <text x="272" y="260" class="body-label">Brazo</text>

                        <!-- Codo Izquierdo -->
                        <circle cx="127" cy="305" r="15" class="body-part" data-part="Codo" />
                        <text x="127" y="310" class="body-label" style="font-size: 10px;">Codo</text>

                        <!-- Codo Derecho -->
                        <circle cx="272" cy="305" r="15" class="body-part" data-part="Codo" />
                        <text x="272" y="310" class="body-label" style="font-size: 10px;">Codo</text>

                        <!-- Antebrazo Izquierdo -->
                        <rect x="115" y="320" width="25" height="70" rx="12" class="body-part" data-part="Brazo" />

                        <!-- Antebrazo Derecho -->
                        <rect x="260" y="320" width="25" height="70" rx="12" class="body-part" data-part="Brazo" />

                        <!-- Mano Izquierda -->
                        <ellipse cx="127" cy="405" rx="18" ry="25" class="body-part" data-part="Mano" />
                        <text x="127" y="410" class="body-label" style="font-size: 10px;">Mano</text>

                        <!-- Mano Derecha -->
                        <ellipse cx="272" cy="405" rx="18" ry="25" class="body-part" data-part="Mano" />
                        <text x="272" y="410" class="body-label" style="font-size: 10px;">Mano</text>

                        <!-- Dedos Izquierda -->
                        <rect x="118" y="425" width="18" height="20" rx="3" class="body-part" data-part="Dedos" />
                        <text x="127" y="438" class="body-label" style="font-size: 9px;">Dedos</text>

                        <!-- Dedos Derecha -->
                        <rect x="263" y="425" width="18" height="20" rx="3" class="body-part" data-part="Dedos" />
                        <text x="272" y="438" class="body-label" style="font-size: 9px;">Dedos</text>

                        <!-- Cadera -->
                        <ellipse cx="200" cy="290" rx="45" ry="30" class="body-part" data-part="Cadera" />
                        <text x="200" y="295" class="body-label">Cadera</text>

                        <!-- Pierna Izquierda -->
                        <rect x="165" y="315" width="30" height="140" rx="15" class="body-part" data-part="Pierna" />
                        <text x="180" y="390" class="body-label">Pierna</text>

                        <!-- Pierna Derecha -->
                        <rect x="205" y="315" width="30" height="140" rx="15" class="body-part" data-part="Pierna" />
                        <text x="220" y="390" class="body-label">Pierna</text>

                        <!-- Rodilla Izquierda -->
                        <circle cx="180" cy="460" r="18" class="body-part" data-part="Rodilla" />
                        <text x="180" y="465" class="body-label" style="font-size: 10px;">Rodilla</text>

                        <!-- Rodilla Derecha -->
                        <circle cx="220" cy="460" r="18" class="body-part" data-part="Rodilla" />
                        <text x="220" y="465" class="body-label" style="font-size: 10px;">Rodilla</text>

                        <!-- Pantorrilla Izquierda -->
                        <rect x="165" y="478" width="30" height="120" rx="15" class="body-part" data-part="Pierna" />

                        <!-- Pantorrilla Derecha -->
                        <rect x="205" y="478" width="30" height="120" rx="15" class="body-part" data-part="Pierna" />

                        <!-- Tobillo Izquierdo -->
                        <circle cx="180" cy="605" r="12" class="body-part" data-part="Tobillo" />
                        <text x="180" y="609" class="body-label" style="font-size: 9px;">Tobillo</text>

                        <!-- Tobillo Derecho -->
                        <circle cx="220" cy="605" r="12" class="body-part" data-part="Tobillo" />
                        <text x="220" y="609" class="body-label" style="font-size: 9px;">Tobillo</text>

                        <!-- Pie Izquierdo -->
                        <ellipse cx="180" cy="635" rx="20" ry="30" class="body-part" data-part="Pie" />
                        <text x="180" y="640" class="body-label" style="font-size: 10px;">Pie</text>

                        <!-- Pie Derecho -->
                        <ellipse cx="220" cy="635" rx="20" ry="30" class="body-part" data-part="Pie" />
                        <text x="220" y="640" class="body-label" style="font-size: 10px;">Pie</text>
                    </svg>
                </div>

                <div class="selected-parts-list">
                    <h4>Partes Seleccionadas:</h4>
                    <div id="selected-parts-display"></div>
                    <button type="button" class="btn-clear-selection" onclick="bodySelector.clearAll()">
                        Limpiar Selección
                    </button>
                </div>
            </div>
        `;

        // Agregar estilos
        this.addStyles();
    }

    addStyles() {
        if (!document.getElementById('body-selector-styles')) {
            const style = document.createElement('style');
            style.id = 'body-selector-styles';
            style.textContent = `
                .body-selector-wrapper {
                    display: flex;
                    gap: 30px;
                    align-items: flex-start;
                    flex-wrap: wrap;
                    margin: 20px 0;
                }

                .body-selector-container {
                    flex: 1;
                    min-width: 300px;
                    max-width: 400px;
                    background: #f8f9fa;
                    border-radius: 15px;
                    padding: 20px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }

                .body-svg {
                    width: 100%;
                    height: auto;
                    max-height: 800px;
                }

                .body-part {
                    fill: #e0e0e0;
                    stroke: #333;
                    stroke-width: 2;
                    cursor: pointer;
                    transition: all 0.3s ease;
                }

                .body-part:hover {
                    fill: #ffd54f;
                    stroke: #f57c00;
                    stroke-width: 3;
                    filter: drop-shadow(0 0 8px rgba(255, 152, 0, 0.6));
                }

                .body-part.selected {
                    fill: #ff5252;
                    stroke: #c62828;
                    stroke-width: 3;
                    filter: drop-shadow(0 0 10px rgba(255, 82, 82, 0.8));
                }

                .body-label {
                    fill: #333;
                    font-size: 12px;
                    font-weight: bold;
                    text-anchor: middle;
                    pointer-events: none;
                    user-select: none;
                }

                .selected-parts-list {
                    flex: 1;
                    min-width: 250px;
                    background: white;
                    border: 2px solid #667eea;
                    border-radius: 10px;
                    padding: 20px;
                }

                .selected-parts-list h4 {
                    color: #2c3e50;
                    margin-bottom: 15px;
                    font-size: 16px;
                }

                #selected-parts-display {
                    min-height: 100px;
                    margin-bottom: 15px;
                }

                .selected-part-tag {
                    display: inline-block;
                    background: #667eea;
                    color: white;
                    padding: 8px 15px;
                    border-radius: 20px;
                    margin: 5px;
                    font-size: 13px;
                    font-weight: 500;
                }

                .btn-clear-selection {
                    background: #e74c3c;
                    color: white;
                    border: none;
                    padding: 10px 20px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-size: 14px;
                    font-weight: 600;
                    width: 100%;
                    transition: background 0.3s;
                }

                .btn-clear-selection:hover {
                    background: #c0392b;
                }

                @media (max-width: 768px) {
                    .body-selector-wrapper {
                        flex-direction: column;
                    }

                    .body-selector-container,
                    .selected-parts-list {
                        max-width: 100%;
                    }
                }
            `;
            document.head.appendChild(style);
        }
    }

    attachEventListeners() {
        const bodyParts = this.container.querySelectorAll('.body-part');
        bodyParts.forEach(part => {
            part.addEventListener('click', (e) => {
                const partName = e.target.getAttribute('data-part');
                this.togglePart(e.target, partName);
            });
        });
    }

    togglePart(element, partName) {
        if (this.selectedParts.has(partName)) {
            this.selectedParts.delete(partName);
            element.classList.remove('selected');
        } else {
            this.selectedParts.add(partName);
            element.classList.add('selected');
        }
        this.updateDisplay();
        this.updateHiddenCheckboxes();
    }

    updateDisplay() {
        const display = document.getElementById('selected-parts-display');
        if (this.selectedParts.size === 0) {
            display.innerHTML = '<p style="color: #999; font-style: italic;">Ninguna parte seleccionada</p>';
        } else {
            const tags = Array.from(this.selectedParts)
                .map(part => `<span class="selected-part-tag">${part}</span>`)
                .join('');
            display.innerHTML = tags;
        }
    }

    updateHiddenCheckboxes() {
        // Actualizar los checkboxes ocultos para el envío del formulario
        const checkboxes = document.querySelectorAll('input[name="partes_cuerpo[]"]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.selectedParts.has(checkbox.value);
        });
    }

    clearAll() {
        this.selectedParts.clear();
        const bodyParts = this.container.querySelectorAll('.body-part');
        bodyParts.forEach(part => part.classList.remove('selected'));
        this.updateDisplay();
        this.updateHiddenCheckboxes();
    }

    getSelectedParts() {
        return Array.from(this.selectedParts);
    }
}

// Inicializar cuando el DOM esté listo
let bodySelector;
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('body-selector')) {
        bodySelector = new BodySelector('body-selector');
    }
});
