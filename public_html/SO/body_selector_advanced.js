/**
 * Body Selector Advanced - Selector visual realista de partes del cuerpo
 * Vista frontal y posterior con anatomía detallada
 */

class BodySelectorAdvanced {
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
            <div class="body-selector-advanced-wrapper">
                <div class="body-views-container">
                    <!-- Vista Frontal -->
                    <div class="body-view">
                        <h3 class="view-title">Vista Frontal</h3>
                        <svg viewBox="0 0 300 700" class="body-svg-advanced">
                            <!-- Fondo del cuerpo -->
                            <defs>
                                <linearGradient id="skinGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%" style="stop-color:#ffd7ba;stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:#f4c2a0;stop-opacity:1" />
                                </linearGradient>

                                <filter id="shadow">
                                    <feDropShadow dx="0" dy="2" stdDeviation="3" flood-opacity="0.3"/>
                                </filter>
                            </defs>

                            <!-- CABEZA -->
                            <ellipse cx="150" cy="60" rx="45" ry="55" class="body-part-advanced" data-part="Cabeza" data-side="front" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>
                            <text x="150" y="65" class="body-label-advanced">Cabeza</text>

                            <!-- CARA -->
                            <ellipse cx="150" cy="50" rx="35" ry="40" class="body-part-advanced" data-part="Cara" data-side="front" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="1.5"/>
                            <text x="150" y="52" class="body-label-small">Cara</text>

                            <!-- CUELLO -->
                            <rect x="135" y="110" width="30" height="30" rx="5" class="body-part-advanced" data-part="Cuello" data-side="front" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>
                            <text x="150" y="128" class="body-label-small">Cuello</text>

                            <!-- PECHO / TÓRAX -->
                            <ellipse cx="150" cy="180" rx="55" ry="50" class="body-part-advanced" data-part="Pecho" data-side="front" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>
                            <text x="150" y="185" class="body-label-advanced">Pecho</text>

                            <!-- ABDOMEN -->
                            <ellipse cx="150" cy="250" rx="50" ry="45" class="body-part-advanced" data-part="Abdomen" data-side="front" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>
                            <text x="150" y="255" class="body-label-advanced">Abdomen</text>

                            <!-- HOMBRO IZQUIERDO -->
                            <circle cx="95" cy="155" r="25" class="body-part-advanced" data-part="Hombro Izquierdo" data-side="front" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>
                            <text x="95" y="160" class="body-label-small">Hombro</text>
                            <text x="95" y="172" class="body-label-tiny">Izq</text>

                            <!-- HOMBRO DERECHO -->
                            <circle cx="205" cy="155" r="25" class="body-part-advanced" data-part="Hombro Derecho" data-side="front" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>
                            <text x="205" y="160" class="body-label-small">Hombro</text>
                            <text x="205" y="172" class="body-label-tiny">Der</text>

                            <!-- BRAZO IZQUIERDO -->
                            <rect x="65" y="180" width="22" height="85" rx="11" class="body-part-advanced" data-part="Brazo Izquierdo" data-side="front" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>
                            <text x="76" y="225" class="body-label-small" transform="rotate(-5 76 225)">Brazo</text>

                            <!-- BRAZO DERECHO -->
                            <rect x="213" y="180" width="22" height="85" rx="11" class="body-part-advanced" data-part="Brazo Derecho" data-side="front" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>
                            <text x="224" y="225" class="body-label-small" transform="rotate(5 224 225)">Brazo</text>

                            <!-- CODO IZQUIERDO -->
                            <circle cx="76" cy="270" r="15" class="body-part-advanced" data-part="Codo Izquierdo" data-side="front" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>
                            <text x="76" y="274" class="body-label-tiny">Codo</text>

                            <!-- CODO DERECHO -->
                            <circle cx="224" cy="270" r="15" class="body-part-advanced" data-part="Codo Derecho" data-side="front" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>
                            <text x="224" y="274" class="body-label-tiny">Codo</text>

                            <!-- ANTEBRAZO IZQUIERDO -->
                            <rect x="65" y="285" width="22" height="75" rx="11" class="body-part-advanced" data-part="Antebrazo Izquierdo" data-side="front" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>

                            <!-- ANTEBRAZO DERECHO -->
                            <rect x="213" y="285" width="22" height="75" rx="11" class="body-part-advanced" data-part="Antebrazo Derecho" data-side="front" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>

                            <!-- MUÑECA IZQUIERDA -->
                            <circle cx="76" cy="365" r="10" class="body-part-advanced" data-part="Muñeca Izquierda" data-side="front" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="1.5"/>

                            <!-- MUÑECA DERECHA -->
                            <circle cx="224" cy="365" r="10" class="body-part-advanced" data-part="Muñeca Derecha" data-side="front" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="1.5"/>

                            <!-- MANO IZQUIERDA -->
                            <ellipse cx="76" cy="390" rx="16" ry="22" class="body-part-advanced" data-part="Mano Izquierda" data-side="front" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>
                            <text x="76" y="394" class="body-label-tiny">Mano</text>

                            <!-- MANO DERECHA -->
                            <ellipse cx="224" cy="390" rx="16" ry="22" class="body-part-advanced" data-part="Mano Derecha" data-side="front" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>
                            <text x="224" y="394" class="body-label-tiny">Mano</text>

                            <!-- DEDOS IZQUIERDA -->
                            <rect x="68" y="410" width="16" height="18" rx="3" class="body-part-advanced" data-part="Dedos Izquierda" data-side="front" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="1.5"/>

                            <!-- DEDOS DERECHA -->
                            <rect x="216" y="410" width="16" height="18" rx="3" class="body-part-advanced" data-part="Dedos Derecha" data-side="front" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="1.5"/>

                            <!-- CADERA / PELVIS -->
                            <ellipse cx="150" cy="310" rx="48" ry="30" class="body-part-advanced" data-part="Cadera" data-side="front" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>
                            <text x="150" y="315" class="body-label-advanced">Cadera</text>

                            <!-- INGLE -->
                            <ellipse cx="150" cy="340" rx="35" ry="15" class="body-part-advanced" data-part="Ingle" data-side="front" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="1.5"/>
                            <text x="150" y="344" class="body-label-tiny">Ingle</text>

                            <!-- MUSLO IZQUIERDO -->
                            <rect x="120" y="350" width="28" height="110" rx="14" class="body-part-advanced" data-part="Muslo Izquierdo" data-side="front" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>
                            <text x="134" y="410" class="body-label-small">Muslo</text>

                            <!-- MUSLO DERECHO -->
                            <rect x="152" y="350" width="28" height="110" rx="14" class="body-part-advanced" data-part="Muslo Derecho" data-side="front" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>
                            <text x="166" y="410" class="body-label-small">Muslo</text>

                            <!-- RODILLA IZQUIERDA -->
                            <circle cx="134" cy="465" r="18" class="body-part-advanced" data-part="Rodilla Izquierda" data-side="front" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>
                            <text x="134" y="469" class="body-label-tiny">Rodilla</text>

                            <!-- RODILLA DERECHA -->
                            <circle cx="166" cy="465" r="18" class="body-part-advanced" data-part="Rodilla Derecha" data-side="front" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>
                            <text x="166" y="469" class="body-label-tiny">Rodilla</text>

                            <!-- PANTORRILLA IZQUIERDA -->
                            <rect x="122" y="483" width="24" height="110" rx="12" class="body-part-advanced" data-part="Pantorrilla Izquierda" data-side="front" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>
                            <text x="134" y="540" class="body-label-small" transform="rotate(-2 134 540)">Pantorr.</text>

                            <!-- PANTORRILLA DERECHA -->
                            <rect x="154" y="483" width="24" height="110" rx="12" class="body-part-advanced" data-part="Pantorrilla Derecha" data-side="front" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>
                            <text x="166" y="540" class="body-label-small" transform="rotate(2 166 540)">Pantorr.</text>

                            <!-- TOBILLO IZQUIERDO -->
                            <circle cx="134" cy="598" r="12" class="body-part-advanced" data-part="Tobillo Izquierdo" data-side="front" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>

                            <!-- TOBILLO DERECHO -->
                            <circle cx="166" cy="598" r="12" class="body-part-advanced" data-part="Tobillo Derecho" data-side="front" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>

                            <!-- PIE IZQUIERDO -->
                            <ellipse cx="134" cy="635" rx="20" ry="28" class="body-part-advanced" data-part="Pie Izquierdo" data-side="front" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>
                            <text x="134" y="638" class="body-label-tiny">Pie</text>

                            <!-- PIE DERECHO -->
                            <ellipse cx="166" cy="635" rx="20" ry="28" class="body-part-advanced" data-part="Pie Derecho" data-side="front" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>
                            <text x="166" y="638" class="body-label-tiny">Pie</text>

                            <!-- DEDOS PIE IZQUIERDO -->
                            <rect x="126" y="660" width="16" height="12" rx="2" class="body-part-advanced" data-part="Dedos Pie Izquierdo" data-side="front" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="1"/>

                            <!-- DEDOS PIE DERECHO -->
                            <rect x="158" y="660" width="16" height="12" rx="2" class="body-part-advanced" data-part="Dedos Pie Derecho" data-side="front" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="1"/>
                        </svg>
                    </div>

                    <!-- Vista Posterior -->
                    <div class="body-view">
                        <h3 class="view-title">Vista Posterior</h3>
                        <svg viewBox="0 0 300 700" class="body-svg-advanced">
                            <!-- CABEZA POSTERIOR -->
                            <ellipse cx="150" cy="60" rx="45" ry="55" class="body-part-advanced" data-part="Cabeza Posterior" data-side="back" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>
                            <text x="150" y="65" class="body-label-advanced">Nuca</text>

                            <!-- CUELLO POSTERIOR -->
                            <rect x="135" y="110" width="30" height="30" rx="5" class="body-part-advanced" data-part="Cuello Posterior" data-side="back" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>
                            <text x="150" y="128" class="body-label-small">Cuello</text>

                            <!-- HOMBRO POSTERIOR IZQUIERDO -->
                            <circle cx="95" cy="155" r="25" class="body-part-advanced" data-part="Hombro Posterior Izquierdo" data-side="back" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>
                            <text x="95" y="160" class="body-label-small">Hombro</text>

                            <!-- HOMBRO POSTERIOR DERECHO -->
                            <circle cx="205" cy="155" r="25" class="body-part-advanced" data-part="Hombro Posterior Derecho" data-side="back" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>
                            <text x="205" y="160" class="body-label-small">Hombro</text>

                            <!-- ESPALDA ALTA -->
                            <rect x="110" y="145" width="80" height="60" rx="10" class="body-part-advanced" data-part="Espalda Alta" data-side="back" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>
                            <text x="150" y="180" class="body-label-advanced">Espalda Alta</text>

                            <!-- ESPALDA MEDIA / DORSAL -->
                            <rect x="115" y="205" width="70" height="55" rx="10" class="body-part-advanced" data-part="Espalda Media" data-side="back" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>
                            <text x="150" y="238" class="body-label-advanced">Espalda Media</text>

                            <!-- LUMBAR / ESPALDA BAJA -->
                            <rect x="120" y="260" width="60" height="45" rx="8" class="body-part-advanced" data-part="Lumbar" data-side="back" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>
                            <text x="150" y="287" class="body-label-advanced">Lumbar</text>

                            <!-- BRAZO POSTERIOR IZQUIERDO -->
                            <rect x="65" y="180" width="22" height="85" rx="11" class="body-part-advanced" data-part="Brazo Posterior Izquierdo" data-side="back" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>

                            <!-- BRAZO POSTERIOR DERECHO -->
                            <rect x="213" y="180" width="22" height="85" rx="11" class="body-part-advanced" data-part="Brazo Posterior Derecho" data-side="back" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>

                            <!-- CODO POSTERIOR IZQUIERDO -->
                            <circle cx="76" cy="270" r="15" class="body-part-advanced" data-part="Codo Posterior Izquierdo" data-side="back" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>

                            <!-- CODO POSTERIOR DERECHO -->
                            <circle cx="224" cy="270" r="15" class="body-part-advanced" data-part="Codo Posterior Derecho" data-side="back" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>

                            <!-- ANTEBRAZO POSTERIOR IZQUIERDO -->
                            <rect x="65" y="285" width="22" height="75" rx="11" class="body-part-advanced" data-part="Antebrazo Posterior Izquierdo" data-side="back" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>

                            <!-- ANTEBRAZO POSTERIOR DERECHO -->
                            <rect x="213" y="285" width="22" height="75" rx="11" class="body-part-advanced" data-part="Antebrazo Posterior Derecho" data-side="back" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>

                            <!-- MANO POSTERIOR IZQUIERDA -->
                            <ellipse cx="76" cy="385" rx="16" ry="28" class="body-part-advanced" data-part="Mano Posterior Izquierda" data-side="back" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>

                            <!-- MANO POSTERIOR DERECHA -->
                            <ellipse cx="224" cy="385" rx="16" ry="28" class="body-part-advanced" data-part="Mano Posterior Derecha" data-side="back" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>

                            <!-- GLÚTEOS -->
                            <ellipse cx="150" cy="315" rx="48" ry="35" class="body-part-advanced" data-part="Glúteos" data-side="back" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>
                            <text x="150" y="320" class="body-label-advanced">Glúteos</text>

                            <!-- MUSLO POSTERIOR IZQUIERDO -->
                            <rect x="120" y="350" width="28" height="110" rx="14" class="body-part-advanced" data-part="Muslo Posterior Izquierdo" data-side="back" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>

                            <!-- MUSLO POSTERIOR DERECHO -->
                            <rect x="152" y="350" width="28" height="110" rx="14" class="body-part-advanced" data-part="Muslo Posterior Derecho" data-side="back" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>

                            <!-- RODILLA POSTERIOR IZQUIERDA -->
                            <circle cx="134" cy="465" r="18" class="body-part-advanced" data-part="Rodilla Posterior Izquierda" data-side="back" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>

                            <!-- RODILLA POSTERIOR DERECHA -->
                            <circle cx="166" cy="465" r="18" class="body-part-advanced" data-part="Rodilla Posterior Derecha" data-side="back" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>

                            <!-- PANTORRILLA POSTERIOR IZQUIERDA -->
                            <rect x="122" y="483" width="24" height="110" rx="12" class="body-part-advanced" data-part="Pantorrilla Posterior Izquierda" data-side="back" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>
                            <text x="134" y="540" class="body-label-small">Gemelo</text>

                            <!-- PANTORRILLA POSTERIOR DERECHA -->
                            <rect x="154" y="483" width="24" height="110" rx="12" class="body-part-advanced" data-part="Pantorrilla Posterior Derecha" data-side="back" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>
                            <text x="166" y="540" class="body-label-small">Gemelo</text>

                            <!-- TALÓN IZQUIERDO -->
                            <circle cx="134" cy="610" r="14" class="body-part-advanced" data-part="Talón Izquierdo" data-side="back" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>
                            <text x="134" y="614" class="body-label-tiny">Talón</text>

                            <!-- TALÓN DERECHO -->
                            <circle cx="166" cy="610" r="14" class="body-part-advanced" data-part="Talón Derecho" data-side="back" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>
                            <text x="166" y="614" class="body-label-tiny">Talón</text>

                            <!-- PIE POSTERIOR IZQUIERDO -->
                            <ellipse cx="134" cy="645" rx="20" ry="28" class="body-part-advanced" data-part="Pie Posterior Izquierdo" data-side="back" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>

                            <!-- PIE POSTERIOR DERECHO -->
                            <ellipse cx="166" cy="645" rx="20" ry="28" class="body-part-advanced" data-part="Pie Posterior Derecho" data-side="back" fill="url(#skinGradient)" stroke="#d4a574" stroke-width="2"/>
                        </svg>
                    </div>
                </div>

                <!-- Panel de selección -->
                <div class="selected-parts-panel">
                    <h3 class="panel-title">🎯 Partes Seleccionadas</h3>
                    <div id="selected-parts-display-advanced"></div>
                    <div class="panel-actions">
                        <button type="button" class="btn-clear-advanced" onclick="bodySelectorAdvanced.clearAll()">
                            🗑️ Limpiar Todo
                        </button>
                        <div class="selection-count">
                            <span id="selection-count">0</span> parte(s) seleccionada(s)
                        </div>
                    </div>
                </div>
            </div>
        `;

        this.addStyles();
    }

    addStyles() {
        if (!document.getElementById('body-selector-advanced-styles')) {
            const style = document.createElement('style');
            style.id = 'body-selector-advanced-styles';
            style.textContent = `
                .body-selector-advanced-wrapper {
                    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
                    border-radius: 20px;
                    padding: 30px;
                    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
                }

                .body-views-container {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                    gap: 30px;
                    margin-bottom: 30px;
                }

                .body-view {
                    background: white;
                    border-radius: 15px;
                    padding: 20px;
                    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
                    transition: transform 0.3s ease;
                }

                .body-view:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
                }

                .view-title {
                    text-align: center;
                    color: #2c3e50;
                    font-size: 18px;
                    font-weight: 700;
                    margin-bottom: 15px;
                    padding-bottom: 10px;
                    border-bottom: 3px solid #667eea;
                }

                .body-svg-advanced {
                    width: 100%;
                    height: auto;
                    max-height: 700px;
                }

                .body-part-advanced {
                    cursor: pointer;
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
                }

                .body-part-advanced:hover {
                    fill: #ffe0b2 !important;
                    stroke: #ff9800 !important;
                    stroke-width: 3 !important;
                    filter: drop-shadow(0 4px 12px rgba(255, 152, 0, 0.5));
                    transform: scale(1.05);
                }

                .body-part-advanced.selected {
                    fill: #ffcdd2 !important;
                    stroke: #e53935 !important;
                    stroke-width: 3 !important;
                    filter: drop-shadow(0 4px 15px rgba(229, 57, 53, 0.6));
                    animation: pulse 2s infinite;
                }

                @keyframes pulse {
                    0%, 100% {
                        filter: drop-shadow(0 4px 15px rgba(229, 57, 53, 0.6));
                    }
                    50% {
                        filter: drop-shadow(0 4px 20px rgba(229, 57, 53, 0.9));
                    }
                }

                .body-label-advanced {
                    fill: #2c3e50;
                    font-size: 11px;
                    font-weight: 600;
                    text-anchor: middle;
                    pointer-events: none;
                    user-select: none;
                }

                .body-label-small {
                    fill: #34495e;
                    font-size: 9px;
                    font-weight: 600;
                    text-anchor: middle;
                    pointer-events: none;
                    user-select: none;
                }

                .body-label-tiny {
                    fill: #34495e;
                    font-size: 7px;
                    font-weight: 500;
                    text-anchor: middle;
                    pointer-events: none;
                    user-select: none;
                }

                .selected-parts-panel {
                    background: white;
                    border-radius: 15px;
                    padding: 25px;
                    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
                }

                .panel-title {
                    color: #2c3e50;
                    font-size: 20px;
                    font-weight: 700;
                    margin-bottom: 20px;
                    text-align: center;
                }

                #selected-parts-display-advanced {
                    min-height: 120px;
                    max-height: 300px;
                    overflow-y: auto;
                    margin-bottom: 20px;
                    padding: 15px;
                    background: #f8f9fa;
                    border-radius: 10px;
                    border: 2px dashed #dee2e6;
                }

                #selected-parts-display-advanced::-webkit-scrollbar {
                    width: 8px;
                }

                #selected-parts-display-advanced::-webkit-scrollbar-track {
                    background: #f1f1f1;
                    border-radius: 10px;
                }

                #selected-parts-display-advanced::-webkit-scrollbar-thumb {
                    background: #667eea;
                    border-radius: 10px;
                }

                .selected-part-tag-advanced {
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 10px 15px;
                    border-radius: 25px;
                    margin: 5px;
                    font-size: 13px;
                    font-weight: 600;
                    box-shadow: 0 3px 10px rgba(102, 126, 234, 0.3);
                    transition: all 0.3s ease;
                }

                .selected-part-tag-advanced:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
                }

                .selected-part-tag-advanced .side-badge {
                    background: rgba(255,255,255,0.3);
                    padding: 2px 8px;
                    border-radius: 12px;
                    font-size: 10px;
                }

                .panel-actions {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    gap: 15px;
                }

                .btn-clear-advanced {
                    flex: 1;
                    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
                    color: white;
                    border: none;
                    padding: 12px 20px;
                    border-radius: 10px;
                    cursor: pointer;
                    font-size: 14px;
                    font-weight: 700;
                    transition: all 0.3s ease;
                    box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
                }

                .btn-clear-advanced:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
                }

                .btn-clear-advanced:active {
                    transform: translateY(0);
                }

                .selection-count {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 12px 20px;
                    border-radius: 10px;
                    font-weight: 700;
                    font-size: 14px;
                    text-align: center;
                    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
                }

                .selection-count span {
                    font-size: 20px;
                    font-weight: 900;
                }

                .empty-selection {
                    text-align: center;
                    color: #999;
                    font-style: italic;
                    padding: 40px 20px;
                }

                .empty-selection::before {
                    content: "👆";
                    display: block;
                    font-size: 48px;
                    margin-bottom: 15px;
                }

                @media (max-width: 768px) {
                    .body-views-container {
                        grid-template-columns: 1fr;
                    }

                    .panel-actions {
                        flex-direction: column;
                    }

                    .btn-clear-advanced {
                        width: 100%;
                    }
                }
            `;
            document.head.appendChild(style);
        }
    }

    attachEventListeners() {
        const bodyParts = this.container.querySelectorAll('.body-part-advanced');
        bodyParts.forEach(part => {
            part.addEventListener('click', (e) => {
                const partName = e.target.getAttribute('data-part');
                const side = e.target.getAttribute('data-side');
                this.togglePart(e.target, partName, side);
            });
        });
    }

    togglePart(element, partName, side) {
        const fullName = `${partName}`;

        if (this.selectedParts.has(fullName)) {
            this.selectedParts.delete(fullName);
            element.classList.remove('selected');
        } else {
            this.selectedParts.add(fullName);
            element.classList.add('selected');
        }

        this.updateDisplay();
        this.updateHiddenCheckboxes();
    }

    updateDisplay() {
        const display = document.getElementById('selected-parts-display-advanced');
        const countSpan = document.getElementById('selection-count');

        countSpan.textContent = this.selectedParts.size;

        if (this.selectedParts.size === 0) {
            display.innerHTML = '<div class="empty-selection">Haga clic en las partes del cuerpo para seleccionar</div>';
        } else {
            const tags = Array.from(this.selectedParts)
                .map(part => {
                    const side = part.includes('Posterior') || part.includes('Glúteos') || part.includes('Lumbar') || part.includes('Nuca') || part.includes('Gemelo') || part.includes('Talón') ? 'Posterior' : 'Frontal';
                    return `<span class="selected-part-tag-advanced">
                        ${part}
                        <span class="side-badge">${side}</span>
                    </span>`;
                })
                .join('');
            display.innerHTML = tags;
        }
    }

    updateHiddenCheckboxes() {
        // Mapeo simplificado para los checkboxes
        const mapping = {
            'Cabeza': 'Cabeza',
            'Cara': 'Cara',
            'Cabeza Posterior': 'Cabeza',
            'Nuca': 'Cuello',
            'Cuello': 'Cuello',
            'Cuello Posterior': 'Cuello',
            'Pecho': 'Pecho',
            'Abdomen': 'Abdomen',
            'Espalda Alta': 'Espalda',
            'Espalda Media': 'Espalda',
            'Lumbar': 'Espalda',
            'Hombro Izquierdo': 'Hombro',
            'Hombro Derecho': 'Hombro',
            'Hombro Posterior Izquierdo': 'Hombro',
            'Hombro Posterior Derecho': 'Hombro',
            'Brazo Izquierdo': 'Brazo',
            'Brazo Derecho': 'Brazo',
            'Brazo Posterior Izquierdo': 'Brazo',
            'Brazo Posterior Derecho': 'Brazo',
            'Antebrazo Izquierdo': 'Brazo',
            'Antebrazo Derecho': 'Brazo',
            'Antebrazo Posterior Izquierdo': 'Brazo',
            'Antebrazo Posterior Derecho': 'Brazo',
            'Codo Izquierdo': 'Codo',
            'Codo Derecho': 'Codo',
            'Codo Posterior Izquierdo': 'Codo',
            'Codo Posterior Derecho': 'Codo',
            'Muñeca Izquierda': 'Mano',
            'Muñeca Derecha': 'Mano',
            'Mano Izquierda': 'Mano',
            'Mano Derecha': 'Mano',
            'Mano Posterior Izquierda': 'Mano',
            'Mano Posterior Derecha': 'Mano',
            'Dedos Izquierda': 'Dedos',
            'Dedos Derecha': 'Dedos',
            'Cadera': 'Cadera',
            'Ingle': 'Cadera',
            'Glúteos': 'Cadera',
            'Muslo Izquierdo': 'Pierna',
            'Muslo Derecho': 'Pierna',
            'Muslo Posterior Izquierdo': 'Pierna',
            'Muslo Posterior Derecho': 'Pierna',
            'Rodilla Izquierda': 'Rodilla',
            'Rodilla Derecha': 'Rodilla',
            'Rodilla Posterior Izquierda': 'Rodilla',
            'Rodilla Posterior Derecha': 'Rodilla',
            'Pantorrilla Izquierda': 'Pierna',
            'Pantorrilla Derecha': 'Pierna',
            'Pantorrilla Posterior Izquierda': 'Pierna',
            'Pantorrilla Posterior Derecha': 'Pierna',
            'Tobillo Izquierdo': 'Tobillo',
            'Tobillo Derecho': 'Tobillo',
            'Talón Izquierdo': 'Pie',
            'Talón Derecho': 'Pie',
            'Pie Izquierdo': 'Pie',
            'Pie Derecho': 'Pie',
            'Pie Posterior Izquierdo': 'Pie',
            'Pie Posterior Derecho': 'Pie',
            'Dedos Pie Izquierdo': 'Pie',
            'Dedos Pie Derecho': 'Pie'
        };

        // Limpiar todos los checkboxes
        const checkboxes = document.querySelectorAll('input[name="partes_cuerpo[]"]');
        checkboxes.forEach(cb => cb.checked = false);

        // Marcar los checkboxes correspondientes
        this.selectedParts.forEach(part => {
            const mappedValue = mapping[part];
            if (mappedValue) {
                const checkbox = document.querySelector(`input[name="partes_cuerpo[]"][value="${mappedValue}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                }
            }
        });
    }

    clearAll() {
        this.selectedParts.clear();
        const bodyParts = this.container.querySelectorAll('.body-part-advanced');
        bodyParts.forEach(part => part.classList.remove('selected'));
        this.updateDisplay();
        this.updateHiddenCheckboxes();
    }

    getSelectedParts() {
        return Array.from(this.selectedParts);
    }
}

// Inicializar cuando el DOM esté listo
let bodySelectorAdvanced;
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('body-selector-advanced')) {
        bodySelectorAdvanced = new BodySelectorAdvanced('body-selector-advanced');
    }
});
