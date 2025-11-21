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
                            <!-- Definiciones de gradientes y efectos realistas -->
                            <defs>
                                <!-- Gradiente de piel realista -->
                                <linearGradient id="skinGradient" x1="30%" y1="0%" x2="70%" y2="100%">
                                    <stop offset="0%" style="stop-color:#f4d4ba;stop-opacity:1" />
                                    <stop offset="35%" style="stop-color:#e6c3a8;stop-opacity:1" />
                                    <stop offset="70%" style="stop-color:#d9b599;stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:#c9a589;stop-opacity:1" />
                                </linearGradient>

                                <!-- Gradiente para músculos -->
                                <radialGradient id="muscleGradient" cx="40%" cy="40%">
                                    <stop offset="0%" style="stop-color:#f0d0b8;stop-opacity:1" />
                                    <stop offset="50%" style="stop-color:#e4c1aa;stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:#d5a88e;stop-opacity:1" />
                                </radialGradient>

                                <!-- Gradiente para extremidades -->
                                <linearGradient id="limbGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" style="stop-color:#d9b599;stop-opacity:1" />
                                    <stop offset="50%" style="stop-color:#e6c3a8;stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:#d5a88e;stop-opacity:1" />
                                </linearGradient>

                                <!-- Gradiente para articulaciones -->
                                <radialGradient id="jointGradient" cx="50%" cy="50%">
                                    <stop offset="0%" style="stop-color:#e8c5ab;stop-opacity:1" />
                                    <stop offset="70%" style="stop-color:#d9b599;stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:#c9a589;stop-opacity:1" />
                                </radialGradient>

                                <!-- Sombra suave -->
                                <filter id="shadow">
                                    <feGaussianBlur in="SourceAlpha" stdDeviation="1.5"/>
                                    <feOffset dx="0.5" dy="1" result="offsetblur"/>
                                    <feComponentTransfer>
                                        <feFuncA type="linear" slope="0.2"/>
                                    </feComponentTransfer>
                                    <feMerge>
                                        <feMergeNode/>
                                        <feMergeNode in="SourceGraphic"/>
                                    </feMerge>
                                </filter>
                            </defs>

                            <!-- CABEZA (forma más realista con óvalo) -->
                            <ellipse cx="150" cy="55" rx="38" ry="48" class="body-part-advanced" data-part="Cabeza" data-side="front" fill="url(#skinGradient)" stroke="#c19a7a" stroke-width="1.5"/>
                            <text x="150" y="60" class="body-label-advanced">Cabeza</text>

                            <!-- CARA (zona facial) -->
                            <ellipse cx="150" cy="48" rx="30" ry="38" class="body-part-advanced" data-part="Cara" data-side="front" fill="url(#skinGradient)" stroke="#c19a7a" stroke-width="1.2"/>
                            <text x="150" y="50" class="body-label-small">Cara</text>

                            <!-- CUELLO (más cilíndrico y proporcional) -->
                            <path d="M 140 98 Q 138 110, 135 125 L 165 125 Q 162 110, 160 98 Z"
                                  class="body-part-advanced" data-part="Cuello" data-side="front"
                                  fill="url(#limbGradient)" stroke="#c19a7a" stroke-width="1.5"/>
                            <text x="150" y="115" class="body-label-small">Cuello</text>

                            <!-- PECHO / TÓRAX (forma anatómica con pectorales) -->
                            <path d="M 105 135 Q 90 150, 90 175 Q 90 195, 100 210 L 200 210 Q 210 195, 210 175 Q 210 150, 195 135 Q 180 125, 150 125 Q 120 125, 105 135 Z"
                                  class="body-part-advanced" data-part="Pecho" data-side="front"
                                  fill="url(#muscleGradient)" stroke="#c19a7a" stroke-width="1.8"/>
                            <text x="150" y="175" class="body-label-advanced">Pecho</text>

                            <!-- ABDOMEN (forma de core con cintura definida) -->
                            <path d="M 100 210 Q 95 225, 95 245 Q 95 265, 100 280 L 110 295 L 190 295 L 200 280 Q 205 265, 205 245 Q 205 225, 200 210 Z"
                                  class="body-part-advanced" data-part="Abdomen" data-side="front"
                                  fill="url(#muscleGradient)" stroke="#c19a7a" stroke-width="1.8"/>
                            <text x="150" y="250" class="body-label-advanced">Abdomen</text>

                            <!-- HOMBRO IZQUIERDO (forma de deltoides) -->
                            <ellipse cx="88" cy="145" rx="28" ry="24" class="body-part-advanced" data-part="Hombro Izquierdo" data-side="front" fill="url(#muscleGradient)" stroke="#c19a7a" stroke-width="1.6" transform="rotate(-15 88 145)"/>
                            <text x="88" y="150" class="body-label-small">Hombro</text>
                            <text x="88" y="162" class="body-label-tiny">Izq</text>

                            <!-- HOMBRO DERECHO (forma de deltoides) -->
                            <ellipse cx="212" cy="145" rx="28" ry="24" class="body-part-advanced" data-part="Hombro Derecho" data-side="front" fill="url(#muscleGradient)" stroke="#c19a7a" stroke-width="1.6" transform="rotate(15 212 145)"/>
                            <text x="212" y="150" class="body-label-small">Hombro</text>
                            <text x="212" y="162" class="body-label-tiny">Der</text>

                            <!-- BRAZO IZQUIERDO (bíceps con forma anatómica) -->
                            <path d="M 73 170 Q 65 175, 62 195 Q 60 220, 62 245 Q 64 258, 70 265 L 82 265 Q 88 258, 90 245 Q 92 220, 90 195 Q 87 175, 79 170 Z"
                                  class="body-part-advanced" data-part="Brazo Izquierdo" data-side="front"
                                  fill="url(#limbGradient)" stroke="#c19a7a" stroke-width="1.6"/>
                            <text x="76" y="220" class="body-label-small">Brazo</text>

                            <!-- BRAZO DERECHO (bíceps con forma anatómica) -->
                            <path d="M 221 170 Q 213 175, 210 195 Q 208 220, 210 245 Q 212 258, 218 265 L 230 265 Q 236 258, 238 245 Q 240 220, 238 195 Q 235 175, 227 170 Z"
                                  class="body-part-advanced" data-part="Brazo Derecho" data-side="front"
                                  fill="url(#limbGradient)" stroke="#c19a7a" stroke-width="1.6"/>
                            <text x="224" y="220" class="body-label-small">Brazo</text>

                            <!-- CODO IZQUIERDO (articulación redondeada) -->
                            <ellipse cx="76" cy="267" rx="14" ry="12" class="body-part-advanced" data-part="Codo Izquierdo" data-side="front" fill="url(#jointGradient)" stroke="#c19a7a" stroke-width="1.4"/>
                            <text x="76" y="271" class="body-label-tiny">Codo</text>

                            <!-- CODO DERECHO (articulación redondeada) -->
                            <ellipse cx="224" cy="267" rx="14" ry="12" class="body-part-advanced" data-part="Codo Derecho" data-side="front" fill="url(#jointGradient)" stroke="#c19a7a" stroke-width="1.4"/>
                            <text x="224" y="271" class="body-label-tiny">Codo</text>

                            <!-- ANTEBRAZO IZQUIERDO (adelgazándose hacia la muñeca) -->
                            <path d="M 70 280 Q 65 295, 63 320 Q 62 345, 65 357 L 77 357 Q 80 345, 82 320 Q 84 295, 82 280 Z"
                                  class="body-part-advanced" data-part="Antebrazo Izquierdo" data-side="front"
                                  fill="url(#limbGradient)" stroke="#c19a7a" stroke-width="1.5"/>

                            <!-- ANTEBRAZO DERECHO (adelgazándose hacia la muñeca) -->
                            <path d="M 218 280 Q 215 295, 218 320 Q 220 345, 223 357 L 235 357 Q 238 345, 237 320 Q 235 295, 230 280 Z"
                                  class="body-part-advanced" data-part="Antebrazo Derecho" data-side="front"
                                  fill="url(#limbGradient)" stroke="#c19a7a" stroke-width="1.5"/>

                            <!-- MUÑECA IZQUIERDA (articulación) -->
                            <ellipse cx="71" cy="360" rx="9" ry="8" class="body-part-advanced" data-part="Muñeca Izquierda" data-side="front" fill="url(#jointGradient)" stroke="#c19a7a" stroke-width="1.3"/>

                            <!-- MUÑECA DERECHA (articulación) -->
                            <ellipse cx="229" cy="360" rx="9" ry="8" class="body-part-advanced" data-part="Muñeca Derecha" data-side="front" fill="url(#jointGradient)" stroke="#c19a7a" stroke-width="1.3"/>

                            <!-- MANO IZQUIERDA (forma de palma) -->
                            <path d="M 65 368 Q 60 375, 60 388 Q 60 403, 65 415 L 77 415 Q 82 403, 82 388 Q 82 375, 77 368 Z"
                                  class="body-part-advanced" data-part="Mano Izquierda" data-side="front"
                                  fill="url(#limbGradient)" stroke="#c19a7a" stroke-width="1.4"/>
                            <text x="71" y="393" class="body-label-tiny">Mano</text>

                            <!-- MANO DERECHA (forma de palma) -->
                            <path d="M 223 368 Q 218 375, 218 388 Q 218 403, 223 415 L 235 415 Q 240 403, 240 388 Q 240 375, 235 368 Z"
                                  class="body-part-advanced" data-part="Mano Derecha" data-side="front"
                                  fill="url(#limbGradient)" stroke="#c19a7a" stroke-width="1.4"/>
                            <text x="229" y="393" class="body-label-tiny">Mano</text>

                            <!-- DEDOS IZQUIERDA (forma más realista) -->
                            <path d="M 65 415 Q 63 420, 63 427 Q 63 432, 66 435 L 76 435 Q 79 432, 79 427 Q 79 420, 77 415 Z"
                                  class="body-part-advanced" data-part="Dedos Izquierda" data-side="front"
                                  fill="url(#limbGradient)" stroke="#c19a7a" stroke-width="1.2"/>

                            <!-- DEDOS DERECHA (forma más realista) -->
                            <path d="M 223 415 Q 221 420, 221 427 Q 221 432, 224 435 L 234 435 Q 237 432, 237 427 Q 237 420, 235 415 Z"
                                  class="body-part-advanced" data-part="Dedos Derecha" data-side="front"
                                  fill="url(#limbGradient)" stroke="#c19a7a" stroke-width="1.2"/>

                            <!-- CADERA / PELVIS (forma anatómica de caderas) -->
                            <path d="M 110 295 Q 95 300, 95 315 Q 95 330, 108 345 L 192 345 Q 205 330, 205 315 Q 205 300, 190 295 Z"
                                  class="body-part-advanced" data-part="Cadera" data-side="front"
                                  fill="url(#muscleGradient)" stroke="#c19a7a" stroke-width="1.7"/>
                            <text x="150" y="320" class="body-label-advanced">Cadera</text>

                            <!-- INGLE (zona pélvica) -->
                            <ellipse cx="150" cy="348" rx="32" ry="12" class="body-part-advanced" data-part="Ingle" data-side="front" fill="url(#jointGradient)" stroke="#c19a7a" stroke-width="1.3"/>
                            <text x="150" y="351" class="body-label-tiny">Ingle</text>

                            <!-- MUSLO IZQUIERDO (cuádriceps con forma anatómica) -->
                            <path d="M 120 355 Q 112 370, 110 400 Q 108 430, 112 455 L 128 460 L 144 455 Q 148 430, 146 400 Q 144 370, 136 355 Z"
                                  class="body-part-advanced" data-part="Muslo Izquierdo" data-side="front"
                                  fill="url(#limbGradient)" stroke="#c19a7a" stroke-width="1.6"/>
                            <text x="128" y="410" class="body-label-small">Muslo</text>

                            <!-- MUSLO DERECHO (cuádriceps con forma anatómica) -->
                            <path d="M 156 355 Q 154 370, 154 400 Q 156 430, 160 455 L 172 460 L 188 455 Q 192 430, 190 400 Q 188 370, 180 355 Z"
                                  class="body-part-advanced" data-part="Muslo Derecho" data-side="front"
                                  fill="url(#limbGradient)" stroke="#c19a7a" stroke-width="1.6"/>
                            <text x="172" y="410" class="body-label-small">Muslo</text>

                            <!-- RODILLA IZQUIERDA (rótula) -->
                            <ellipse cx="128" cy="462" rx="16" ry="14" class="body-part-advanced" data-part="Rodilla Izquierda" data-side="front" fill="url(#jointGradient)" stroke="#c19a7a" stroke-width="1.5"/>
                            <text x="128" y="466" class="body-label-tiny">Rodilla</text>

                            <!-- RODILLA DERECHA (rótula) -->
                            <ellipse cx="172" cy="462" rx="16" ry="14" class="body-part-advanced" data-part="Rodilla Derecha" data-side="front" fill="url(#jointGradient)" stroke="#c19a7a" stroke-width="1.5"/>
                            <text x="172" y="466" class="body-label-tiny">Rodilla</text>

                            <!-- PANTORRILLA IZQUIERDA (gemelos con forma anatómica) -->
                            <path d="M 120 476 Q 114 490, 112 520 Q 110 550, 115 580 L 127 585 L 139 580 Q 144 550, 142 520 Q 140 490, 134 476 Z"
                                  class="body-part-advanced" data-part="Pantorrilla Izquierda" data-side="front"
                                  fill="url(#limbGradient)" stroke="#c19a7a" stroke-width="1.5"/>
                            <text x="127" y="535" class="body-label-small">Pantorr.</text>

                            <!-- PANTORRILLA DERECHA (gemelos con forma anatómica) -->
                            <path d="M 161 476 Q 158 490, 158 520 Q 160 550, 165 580 L 173 585 L 185 580 Q 190 550, 188 520 Q 186 490, 180 476 Z"
                                  class="body-part-advanced" data-part="Pantorrilla Derecha" data-side="front"
                                  fill="url(#limbGradient)" stroke="#c19a7a" stroke-width="1.5"/>
                            <text x="173" y="535" class="body-label-small">Pantorr.</text>

                            <!-- TOBILLO IZQUIERDO (articulación del tobillo) -->
                            <ellipse cx="127" cy="588" rx="11" ry="9" class="body-part-advanced" data-part="Tobillo Izquierdo" data-side="front" fill="url(#jointGradient)" stroke="#c19a7a" stroke-width="1.4"/>

                            <!-- TOBILLO DERECHO (articulación del tobillo) -->
                            <ellipse cx="173" cy="588" rx="11" ry="9" class="body-part-advanced" data-part="Tobillo Derecho" data-side="front" fill="url(#jointGradient)" stroke="#c19a7a" stroke-width="1.4"/>

                            <!-- PIE IZQUIERDO (forma anatómica del pie) -->
                            <path d="M 118 597 Q 110 607, 108 625 Q 108 643, 115 658 L 139 658 Q 146 643, 146 625 Q 144 607, 136 597 Z"
                                  class="body-part-advanced" data-part="Pie Izquierdo" data-side="front"
                                  fill="url(#limbGradient)" stroke="#c19a7a" stroke-width="1.4"/>
                            <text x="127" y="630" class="body-label-tiny">Pie</text>

                            <!-- PIE DERECHO (forma anatómica del pie) -->
                            <path d="M 164 597 Q 154 607, 154 625 Q 154 643, 161 658 L 185 658 Q 192 643, 192 625 Q 190 607, 182 597 Z"
                                  class="body-part-advanced" data-part="Pie Derecho" data-side="front"
                                  fill="url(#limbGradient)" stroke="#c19a7a" stroke-width="1.4"/>
                            <text x="173" y="630" class="body-label-tiny">Pie</text>

                            <!-- DEDOS PIE IZQUIERDO (dedos del pie) -->
                            <path d="M 115 658 Q 110 663, 110 670 L 139 670 Q 139 663, 136 658 Z"
                                  class="body-part-advanced" data-part="Dedos Pie Izquierdo" data-side="front"
                                  fill="url(#limbGradient)" stroke="#c19a7a" stroke-width="1.1"/>

                            <!-- DEDOS PIE DERECHO (dedos del pie) -->
                            <path d="M 161 658 Q 154 663, 154 670 L 185 670 Q 185 663, 182 658 Z"
                                  class="body-part-advanced" data-part="Dedos Pie Derecho" data-side="front"
                                  fill="url(#limbGradient)" stroke="#c19a7a" stroke-width="1.1"/>
                        </svg>
                    </div>

                    <!-- Vista Posterior -->
                    <div class="body-view">
                        <h3 class="view-title">Vista Posterior</h3>
                        <svg viewBox="0 0 300 700" class="body-svg-advanced">
                            <!-- CABEZA POSTERIOR / NUCA -->
                            <ellipse cx="150" cy="55" rx="38" ry="48" class="body-part-advanced" data-part="Cabeza Posterior" data-side="back" fill="url(#skinGradient)" stroke="#c19a7a" stroke-width="1.5"/>
                            <text x="150" y="60" class="body-label-advanced">Nuca</text>

                            <!-- CUELLO POSTERIOR -->
                            <path d="M 140 98 Q 138 110, 135 125 L 165 125 Q 162 110, 160 98 Z"
                                  class="body-part-advanced" data-part="Cuello Posterior" data-side="back"
                                  fill="url(#limbGradient)" stroke="#c19a7a" stroke-width="1.5"/>
                            <text x="150" y="115" class="body-label-small">Cuello</text>

                            <!-- HOMBRO POSTERIOR IZQUIERDO (trapecio) -->
                            <ellipse cx="88" cy="145" rx="28" ry="24" class="body-part-advanced" data-part="Hombro Posterior Izquierdo" data-side="back" fill="url(#muscleGradient)" stroke="#c19a7a" stroke-width="1.6" transform="rotate(-15 88 145)"/>
                            <text x="88" y="150" class="body-label-small">Hombro</text>

                            <!-- HOMBRO POSTERIOR DERECHO (trapecio) -->
                            <ellipse cx="212" cy="145" rx="28" ry="24" class="body-part-advanced" data-part="Hombro Posterior Derecho" data-side="back" fill="url(#muscleGradient)" stroke="#c19a7a" stroke-width="1.6" transform="rotate(15 212 145)"/>
                            <text x="212" y="150" class="body-label-small">Hombro</text>

                            <!-- ESPALDA ALTA (dorsales superiores) -->
                            <path d="M 105 130 Q 90 140, 90 165 Q 90 190, 100 205 L 200 205 Q 210 190, 210 165 Q 210 140, 195 130 Q 180 125, 150 125 Q 120 125, 105 130 Z"
                                  class="body-part-advanced" data-part="Espalda Alta" data-side="back"
                                  fill="url(#muscleGradient)" stroke="#c19a7a" stroke-width="1.7"/>
                            <text x="150" y="170" class="body-label-advanced">Espalda Alta</text>

                            <!-- ESPALDA MEDIA / DORSAL (dorsales medios) -->
                            <path d="M 100 205 Q 92 218, 92 240 Q 92 255, 100 268 L 200 268 Q 208 255, 208 240 Q 208 218, 200 205 Z"
                                  class="body-part-advanced" data-part="Espalda Media" data-side="back"
                                  fill="url(#muscleGradient)" stroke="#c19a7a" stroke-width="1.7"/>
                            <text x="150" y="240" class="body-label-advanced">Espalda Media</text>

                            <!-- LUMBAR / ESPALDA BAJA (zona lumbar) -->
                            <path d="M 100 268 Q 95 278, 95 292 Q 95 305, 105 315 L 195 315 Q 205 305, 205 292 Q 205 278, 200 268 Z"
                                  class="body-part-advanced" data-part="Lumbar" data-side="back"
                                  fill="url(#muscleGradient)" stroke="#c19a7a" stroke-width="1.7"/>
                            <text x="150" y="295" class="body-label-advanced">Lumbar</text>

                            <!-- BRAZO POSTERIOR IZQUIERDO (tríceps) -->
                            <path d="M 73 170 Q 65 175, 62 195 Q 60 220, 62 245 Q 64 258, 70 265 L 82 265 Q 88 258, 90 245 Q 92 220, 90 195 Q 87 175, 79 170 Z"
                                  class="body-part-advanced" data-part="Brazo Posterior Izquierdo" data-side="back"
                                  fill="url(#limbGradient)" stroke="#c19a7a" stroke-width="1.6"/>

                            <!-- BRAZO POSTERIOR DERECHO (tríceps) -->
                            <path d="M 221 170 Q 213 175, 210 195 Q 208 220, 210 245 Q 212 258, 218 265 L 230 265 Q 236 258, 238 245 Q 240 220, 238 195 Q 235 175, 227 170 Z"
                                  class="body-part-advanced" data-part="Brazo Posterior Derecho" data-side="back"
                                  fill="url(#limbGradient)" stroke="#c19a7a" stroke-width="1.6"/>

                            <!-- CODO POSTERIOR IZQUIERDO -->
                            <ellipse cx="76" cy="267" rx="14" ry="12" class="body-part-advanced" data-part="Codo Posterior Izquierdo" data-side="back" fill="url(#jointGradient)" stroke="#c19a7a" stroke-width="1.4"/>

                            <!-- CODO POSTERIOR DERECHO -->
                            <ellipse cx="224" cy="267" rx="14" ry="12" class="body-part-advanced" data-part="Codo Posterior Derecho" data-side="back" fill="url(#jointGradient)" stroke="#c19a7a" stroke-width="1.4"/>

                            <!-- ANTEBRAZO POSTERIOR IZQUIERDO -->
                            <path d="M 70 280 Q 65 295, 63 320 Q 62 345, 65 357 L 77 357 Q 80 345, 82 320 Q 84 295, 82 280 Z"
                                  class="body-part-advanced" data-part="Antebrazo Posterior Izquierdo" data-side="back"
                                  fill="url(#limbGradient)" stroke="#c19a7a" stroke-width="1.5"/>

                            <!-- ANTEBRAZO POSTERIOR DERECHO -->
                            <path d="M 218 280 Q 215 295, 218 320 Q 220 345, 223 357 L 235 357 Q 238 345, 237 320 Q 235 295, 230 280 Z"
                                  class="body-part-advanced" data-part="Antebrazo Posterior Derecho" data-side="back"
                                  fill="url(#limbGradient)" stroke="#c19a7a" stroke-width="1.5"/>

                            <!-- MANO POSTERIOR IZQUIERDA -->
                            <path d="M 65 360 Q 60 375, 60 395 Q 60 415, 65 430 L 77 430 Q 82 415, 82 395 Q 82 375, 77 360 Z"
                                  class="body-part-advanced" data-part="Mano Posterior Izquierda" data-side="back"
                                  fill="url(#limbGradient)" stroke="#c19a7a" stroke-width="1.4"/>

                            <!-- MANO POSTERIOR DERECHA -->
                            <path d="M 223 360 Q 218 375, 218 395 Q 218 415, 223 430 L 235 430 Q 240 415, 240 395 Q 240 375, 235 360 Z"
                                  class="body-part-advanced" data-part="Mano Posterior Derecha" data-side="back"
                                  fill="url(#limbGradient)" stroke="#c19a7a" stroke-width="1.4"/>

                            <!-- GLÚTEOS (forma anatómica) -->
                            <path d="M 108 315 Q 95 325, 95 345 Q 95 365, 108 378 L 192 378 Q 205 365, 205 345 Q 205 325, 192 315 Z"
                                  class="body-part-advanced" data-part="Glúteos" data-side="back"
                                  fill="url(#muscleGradient)" stroke="#c19a7a" stroke-width="1.7"/>
                            <text x="150" y="350" class="body-label-advanced">Glúteos</text>

                            <!-- MUSLO POSTERIOR IZQUIERDO (isquiotibiales) -->
                            <path d="M 120 380 Q 112 395, 110 420 Q 108 445, 112 468 L 128 473 L 144 468 Q 148 445, 146 420 Q 144 395, 136 380 Z"
                                  class="body-part-advanced" data-part="Muslo Posterior Izquierdo" data-side="back"
                                  fill="url(#limbGradient)" stroke="#c19a7a" stroke-width="1.6"/>

                            <!-- MUSLO POSTERIOR DERECHO (isquiotibiales) -->
                            <path d="M 156 380 Q 154 395, 154 420 Q 156 445, 160 468 L 172 473 L 188 468 Q 192 445, 190 420 Q 188 395, 180 380 Z"
                                  class="body-part-advanced" data-part="Muslo Posterior Derecho" data-side="back"
                                  fill="url(#limbGradient)" stroke="#c19a7a" stroke-width="1.6"/>

                            <!-- RODILLA POSTERIOR IZQUIERDA (corva) -->
                            <ellipse cx="128" cy="475" rx="16" ry="14" class="body-part-advanced" data-part="Rodilla Posterior Izquierda" data-side="back" fill="url(#jointGradient)" stroke="#c19a7a" stroke-width="1.5"/>

                            <!-- RODILLA POSTERIOR DERECHA (corva) -->
                            <ellipse cx="172" cy="475" rx="16" ry="14" class="body-part-advanced" data-part="Rodilla Posterior Derecha" data-side="back" fill="url(#jointGradient)" stroke="#c19a7a" stroke-width="1.5"/>

                            <!-- PANTORRILLA POSTERIOR IZQUIERDA (gemelos prominentes) -->
                            <path d="M 120 488 Q 110 500, 108 525 Q 106 545, 110 565 Q 114 580, 121 590 L 135 590 Q 142 580, 146 565 Q 150 545, 148 525 Q 146 500, 138 488 Z"
                                  class="body-part-advanced" data-part="Pantorrilla Posterior Izquierda" data-side="back"
                                  fill="url(#muscleGradient)" stroke="#c19a7a" stroke-width="1.6"/>
                            <text x="128" y="540" class="body-label-small">Gemelo</text>

                            <!-- PANTORRILLA POSTERIOR DERECHA (gemelos prominentes) -->
                            <path d="M 162 488 Q 154 500, 152 525 Q 150 545, 154 565 Q 158 580, 165 590 L 179 590 Q 186 580, 190 565 Q 194 545, 192 525 Q 190 500, 182 488 Z"
                                  class="body-part-advanced" data-part="Pantorrilla Posterior Derecha" data-side="back"
                                  fill="url(#muscleGradient)" stroke="#c19a7a" stroke-width="1.6"/>
                            <text x="172" y="540" class="body-label-small">Gemelo</text>

                            <!-- TALÓN IZQUIERDO (tendón de Aquiles) -->
                            <ellipse cx="128" cy="598" rx="13" ry="11" class="body-part-advanced" data-part="Talón Izquierdo" data-side="back" fill="url(#jointGradient)" stroke="#c19a7a" stroke-width="1.4"/>
                            <text x="128" y="602" class="body-label-tiny">Talón</text>

                            <!-- TALÓN DERECHO (tendón de Aquiles) -->
                            <ellipse cx="172" cy="598" rx="13" ry="11" class="body-part-advanced" data-part="Talón Derecho" data-side="back" fill="url(#jointGradient)" stroke="#c19a7a" stroke-width="1.4"/>
                            <text x="172" y="602" class="body-label-tiny">Talón</text>

                            <!-- PIE POSTERIOR IZQUIERDO -->
                            <path d="M 120 610 Q 112 622, 110 642 Q 110 658, 117 670 L 139 670 Q 146 658, 146 642 Q 144 622, 136 610 Z"
                                  class="body-part-advanced" data-part="Pie Posterior Izquierdo" data-side="back"
                                  fill="url(#limbGradient)" stroke="#c19a7a" stroke-width="1.4"/>

                            <!-- PIE POSTERIOR DERECHO -->
                            <path d="M 164 610 Q 154 622, 154 642 Q 154 658, 161 670 L 185 670 Q 192 658, 192 642 Q 190 622, 182 610 Z"
                                  class="body-part-advanced" data-part="Pie Posterior Derecho" data-side="back"
                                  fill="url(#limbGradient)" stroke="#c19a7a" stroke-width="1.4"/>
                        </svg>
                    </div>
                </div>

                <!-- Panel de selección -->
                <div class="selected-parts-panel">
                    <h3 class="panel-title">Partes Seleccionadas</h3>
                    <div id="selected-parts-display-advanced"></div>
                    <div class="panel-actions">
                        <button type="button" class="btn-clear-advanced" onclick="bodySelectorAdvanced.clearAll()">
                            Limpiar Selección
                        </button>
                        <div class="selection-count">
                            <span id="selection-count">0</span> parte(s)
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
                    background: linear-gradient(135deg, #fafafa 0%, #f5f5f5 100%);
                    border-radius: 12px;
                    padding: 30px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
                    border: 1px solid #ddd;
                }

                .body-views-container {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                    gap: 25px;
                    margin-bottom: 25px;
                }

                .body-view {
                    background: white;
                    border-radius: 10px;
                    padding: 20px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
                    border: 1px solid #ddd;
                    transition: all 0.3s ease;
                }

                .body-view:hover {
                    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
                    transform: translateY(-2px);
                }

                .view-title {
                    text-align: center;
                    color: #34495e;
                    font-size: 17px;
                    font-weight: 700;
                    margin-bottom: 15px;
                    padding-bottom: 10px;
                    border-bottom: 3px solid #3498db;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }

                .body-svg-advanced {
                    width: 100%;
                    height: auto;
                    max-height: 700px;
                }

                .body-part-advanced {
                    cursor: pointer;
                    transition: all 0.25s ease-in-out;
                    filter: brightness(1);
                }

                .body-part-advanced:hover {
                    fill: #ffeaa7 !important;
                    stroke: #2d3436 !important;
                    stroke-width: 2.2 !important;
                    opacity: 0.9;
                    filter: brightness(1.15) drop-shadow(0 0 4px rgba(255,234,167,0.6));
                }

                .body-part-advanced.selected {
                    fill: #ff7675 !important;
                    stroke: #d63031 !important;
                    stroke-width: 2.3 !important;
                    filter: brightness(1.1) drop-shadow(0 0 5px rgba(214,48,49,0.5));
                }

                .body-label-advanced {
                    fill: #2c3e50;
                    font-size: 11px;
                    font-weight: 700;
                    text-anchor: middle;
                    pointer-events: none;
                    user-select: none;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                }

                .body-label-small {
                    fill: #34495e;
                    font-size: 9px;
                    font-weight: 600;
                    text-anchor: middle;
                    pointer-events: none;
                    user-select: none;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                }

                .body-label-tiny {
                    fill: #34495e;
                    font-size: 7px;
                    font-weight: 600;
                    text-anchor: middle;
                    pointer-events: none;
                    user-select: none;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                }

                .selected-parts-panel {
                    background: white;
                    border-radius: 10px;
                    padding: 25px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
                    border: 1px solid #ddd;
                }

                .panel-title {
                    color: #2c3e50;
                    font-size: 18px;
                    font-weight: 700;
                    margin-bottom: 18px;
                    text-align: left;
                    border-bottom: 2px solid #3498db;
                    padding-bottom: 12px;
                }

                #selected-parts-display-advanced {
                    min-height: 100px;
                    max-height: 300px;
                    overflow-y: auto;
                    margin-bottom: 15px;
                    padding: 12px;
                    background: #fafafa;
                    border-radius: 4px;
                    border: 1px solid #e0e0e0;
                }

                #selected-parts-display-advanced::-webkit-scrollbar {
                    width: 8px;
                }

                #selected-parts-display-advanced::-webkit-scrollbar-track {
                    background: #f1f1f1;
                    border-radius: 10px;
                }

                #selected-parts-display-advanced::-webkit-scrollbar-thumb {
                    background: #999;
                    border-radius: 4px;
                }

                .selected-part-tag-advanced {
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
                    color: #1565c0;
                    padding: 8px 14px;
                    border-radius: 20px;
                    margin: 5px;
                    font-size: 12px;
                    font-weight: 600;
                    border: 1.5px solid #64b5f6;
                    transition: all 0.3s ease;
                    box-shadow: 0 2px 4px rgba(21,101,192,0.15);
                }

                .selected-part-tag-advanced:hover {
                    background: linear-gradient(135deg, #bbdefb 0%, #90caf9 100%);
                    border-color: #42a5f5;
                    transform: translateY(-1px);
                    box-shadow: 0 3px 6px rgba(21,101,192,0.25);
                }

                .selected-part-tag-advanced .side-badge {
                    background: #1565c0;
                    color: white;
                    padding: 3px 8px;
                    border-radius: 10px;
                    font-size: 9px;
                    font-weight: 700;
                    text-transform: uppercase;
                }

                .panel-actions {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    gap: 15px;
                    margin-top: 5px;
                }

                .btn-clear-advanced {
                    flex: 1;
                    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
                    color: white;
                    border: none;
                    padding: 12px 18px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-size: 14px;
                    font-weight: 600;
                    transition: all 0.3s ease;
                    box-shadow: 0 2px 6px rgba(231,76,60,0.3);
                }

                .btn-clear-advanced:hover {
                    background: linear-gradient(135deg, #c0392b 0%, #a93226 100%);
                    transform: translateY(-1px);
                    box-shadow: 0 4px 8px rgba(231,76,60,0.4);
                }

                .btn-clear-advanced:active {
                    transform: translateY(0);
                    box-shadow: 0 1px 3px rgba(231,76,60,0.3);
                }

                .selection-count {
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    color: #495057;
                    padding: 12px 18px;
                    border-radius: 8px;
                    font-weight: 600;
                    font-size: 13px;
                    text-align: center;
                    border: 1.5px solid #dee2e6;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                }

                .selection-count span {
                    font-size: 18px;
                    font-weight: 700;
                    color: #3498db;
                }

                .empty-selection {
                    text-align: center;
                    color: #95a5a6;
                    font-style: italic;
                    padding: 35px 20px;
                    font-size: 14px;
                    background: #f8f9fa;
                    border-radius: 8px;
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
