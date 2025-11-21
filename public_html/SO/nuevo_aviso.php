<?php
require_once 'config.php';

// Obtener errores de la sesión si existen
$errores = $_SESSION['errores'] ?? [];
$form_data = $_SESSION['form_data'] ?? [];

// Limpiar errores de la sesión
unset($_SESSION['errores']);
unset($_SESSION['form_data']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aviso de Accidente Laboral - Salud Ocupacional</title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .form-content {
            padding: 40px;
        }

        .seccion {
            margin-bottom: 35px;
            border-left: 4px solid #667eea;
            padding-left: 20px;
        }

        .seccion-titulo {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #34495e;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group label.required::after {
            content: " *";
            color: #e74c3c;
        }

        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group input[type="time"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 12px;
            margin-top: 10px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
        }

        .checkbox-item input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-right: 8px;
            cursor: pointer;
        }

        .checkbox-item label {
            margin: 0;
            cursor: pointer;
            font-weight: normal;
        }

        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }

        .radio-item {
            display: flex;
            align-items: center;
        }

        .radio-item input[type="radio"] {
            width: 20px;
            height: 20px;
            margin-right: 8px;
            cursor: pointer;
        }

        .radio-item label {
            margin: 0;
            cursor: pointer;
            font-weight: normal;
        }

        .file-upload {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
        }

        .file-upload:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }

        .file-upload input[type="file"] {
            display: none;
        }

        .file-upload-label {
            cursor: pointer;
            color: #667eea;
            font-weight: 500;
        }

        .file-preview {
            margin-top: 10px;
            font-size: 13px;
            color: #666;
        }

        .firma-container {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            background: #f8f9fa;
        }

        .checkbox-confirmacion {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .checkbox-confirmacion input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-top: 3px;
            cursor: pointer;
        }

        .checkbox-confirmacion label {
            margin: 0;
            cursor: pointer;
            font-size: 14px;
            line-height: 1.5;
        }

        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: transform 0.2s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-submit:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .form-content {
                padding: 20px;
            }

            .checkbox-group {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Aviso de Accidente Laboral</h1>
            <p>Por favor complete el siguiente formulario con la mayor exactitud posible</p>
        </div>

        <div class="form-content">
            <?php if (!empty($errores)): ?>
                <div class="alert alert-error">
                    <strong>Se encontraron los siguientes errores:</strong>
                    <ul style="margin: 10px 0 0 20px;">
                        <?php foreach ($errores as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form id="formularioAviso" action="procesar_aviso.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <!-- Sección 1: Información Personal -->
                <div class="seccion">
                    <h2 class="seccion-titulo">1. Información Personal</h2>

                    <div class="form-group">
                        <label for="nombre_completo" class="required">Nombre Completo</label>
                        <input type="text" id="nombre_completo" name="nombre_completo" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="cedula" class="required">Cédula</label>
                            <input type="text" id="cedula" name="cedula" required>
                        </div>
                        <div class="form-group">
                            <label for="puesto" class="required">Puesto</label>
                            <input type="text" id="puesto" name="puesto" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="departamento" class="required">Departamento / Área</label>
                            <input type="text" id="departamento" name="departamento" required>
                        </div>
                        <div class="form-group">
                            <label for="supervisor_inmediato" class="required">Supervisor Inmediato</label>
                            <input type="text" id="supervisor_inmediato" name="supervisor_inmediato" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="fecha_incidente" class="required">Fecha del Incidente</label>
                            <input type="date" id="fecha_incidente" name="fecha_incidente" required>
                        </div>
                        <div class="form-group">
                            <label for="hora_incidente" class="required">Hora del Incidente</label>
                            <input type="time" id="hora_incidente" name="hora_incidente" required>
                        </div>
                    </div>
                </div>

                <!-- Sección 2: Información del Lugar -->
                <div class="seccion">
                    <h2 class="seccion-titulo">2. Información del Lugar</h2>

                    <div class="form-group">
                        <label for="lugar_exacto" class="required">Lugar exacto donde ocurrió</label>
                        <input type="text" id="lugar_exacto" name="lugar_exacto" placeholder="Ejemplo: Línea 3, área de empaque, pasillo, parqueo, etc." required>
                        <p class="help-text">Sea lo más específico posible</p>
                    </div>
                </div>

                <!-- Sección 3: Actividad que Realizaba -->
                <div class="seccion">
                    <h2 class="seccion-titulo">3. Actividad que Realizaba</h2>

                    <div class="form-group">
                        <label for="actividad_realizaba" class="required">¿Qué estaba haciendo en el momento del accidente?</label>
                        <textarea id="actividad_realizaba" name="actividad_realizaba" required></textarea>
                    </div>
                </div>

                <!-- Sección 4: Descripción del Accidente -->
                <div class="seccion">
                    <h2 class="seccion-titulo">4. Descripción del Accidente</h2>

                    <div class="form-group">
                        <label for="descripcion_accidente" class="required">Explique exactamente qué ocurrió</label>
                        <textarea id="descripcion_accidente" name="descripcion_accidente" rows="6" required></textarea>
                        <p class="help-text">Describa la secuencia de eventos de la manera más detallada posible</p>
                    </div>
                </div>

                <!-- Sección 5: Parte del Cuerpo Afectada -->
                <div class="seccion">
                    <h2 class="seccion-titulo">5. Parte del Cuerpo Afectada</h2>

                    <div class="form-group">
                        <label class="required">Haga clic en la(s) parte(s) del cuerpo afectada(s)</label>
                        <p class="help-text" style="margin-bottom: 15px;">Haga clic directamente en el cuerpo humano para seleccionar las partes afectadas. Las partes seleccionadas se marcarán en rojo.</p>

                        <!-- Selector Visual del Cuerpo -->
                        <div id="body-selector-advanced"></div>

                        <!-- Checkboxes ocultos para el envío del formulario -->
                        <div style="display: none;">
                            <input type="checkbox" id="parte_cabeza" name="partes_cuerpo[]" value="Cabeza">
                            <input type="checkbox" id="parte_cuello" name="partes_cuerpo[]" value="Cuello">
                            <input type="checkbox" id="parte_espalda" name="partes_cuerpo[]" value="Espalda">
                            <input type="checkbox" id="parte_hombro" name="partes_cuerpo[]" value="Hombro">
                            <input type="checkbox" id="parte_brazo" name="partes_cuerpo[]" value="Brazo">
                            <input type="checkbox" id="parte_codo" name="partes_cuerpo[]" value="Codo">
                            <input type="checkbox" id="parte_mano" name="partes_cuerpo[]" value="Mano">
                            <input type="checkbox" id="parte_dedos" name="partes_cuerpo[]" value="Dedos">
                            <input type="checkbox" id="parte_cadera" name="partes_cuerpo[]" value="Cadera">
                            <input type="checkbox" id="parte_pierna" name="partes_cuerpo[]" value="Pierna">
                            <input type="checkbox" id="parte_rodilla" name="partes_cuerpo[]" value="Rodilla">
                            <input type="checkbox" id="parte_tobillo" name="partes_cuerpo[]" value="Tobillo">
                            <input type="checkbox" id="parte_pie" name="partes_cuerpo[]" value="Pie">
                            <input type="checkbox" id="parte_otra" name="partes_cuerpo[]" value="Otra">
                        </div>
                    </div>

                    <div class="form-group" id="otra_parte_container" style="display: none;">
                        <label for="otra_parte_cuerpo">Especifique otra parte del cuerpo</label>
                        <input type="text" id="otra_parte_cuerpo" name="otra_parte_cuerpo">
                    </div>
                </div>

                <!-- Sección 6: Tipo de Lesión -->
                <div class="seccion">
                    <h2 class="seccion-titulo">6. Tipo de Lesión</h2>

                    <div class="form-group">
                        <label class="required">Tipo de lesión (según su percepción)</label>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" id="lesion_golpe" name="tipo_lesion" value="Golpe / Contusión" required>
                                <label for="lesion_golpe">Golpe / Contusión</label>
                            </div>
                        </div>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" id="lesion_torcedura" name="tipo_lesion" value="Torcedura / Esguince">
                                <label for="lesion_torcedura">Torcedura / Esguince</label>
                            </div>
                        </div>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" id="lesion_corte" name="tipo_lesion" value="Corte / Rasguño">
                                <label for="lesion_corte">Corte / Rasguño</label>
                            </div>
                        </div>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" id="lesion_caida" name="tipo_lesion" value="Caída">
                                <label for="lesion_caida">Caída</label>
                            </div>
                        </div>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" id="lesion_dolor" name="tipo_lesion" value="Dolor muscular">
                                <label for="lesion_dolor">Dolor muscular</label>
                            </div>
                        </div>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" id="lesion_moreton" name="tipo_lesion" value="Moretón">
                                <label for="lesion_moreton">Moretón</label>
                            </div>
                        </div>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" id="lesion_ninguna" name="tipo_lesion" value="Ninguna lesión visible">
                                <label for="lesion_ninguna">Ninguna lesión visible</label>
                            </div>
                        </div>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" id="lesion_otra" name="tipo_lesion" value="Otra">
                                <label for="lesion_otra">Otra</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" id="otra_lesion_container" style="display: none;">
                        <label for="otra_lesion">Especifique otro tipo de lesión</label>
                        <input type="text" id="otra_lesion" name="otra_lesion">
                    </div>
                </div>

                <!-- Sección 7: Atención Inmediata -->
                <div class="seccion">
                    <h2 class="seccion-titulo">7. Atención Inmediata</h2>

                    <div class="form-group">
                        <label class="required">¿Recibió atención inmediata en planta?</label>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" id="atencion_si" name="recibio_atencion" value="si" required>
                                <label for="atencion_si">Sí</label>
                            </div>
                            <div class="radio-item">
                                <input type="radio" id="atencion_no" name="recibio_atencion" value="no">
                                <label for="atencion_no">No</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" id="descripcion_atencion_container" style="display: none;">
                        <label for="descripcion_atencion">Describa la atención recibida</label>
                        <textarea id="descripcion_atencion" name="descripcion_atencion"></textarea>
                    </div>
                </div>

                <!-- Sección 8: Testigos -->
                <div class="seccion">
                    <h2 class="seccion-titulo">8. Testigos</h2>

                    <div class="form-group">
                        <label class="required">¿Hubo testigos?</label>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" id="testigos_si" name="hubo_testigos" value="si" required>
                                <label for="testigos_si">Sí</label>
                            </div>
                            <div class="radio-item">
                                <input type="radio" id="testigos_no" name="hubo_testigos" value="no">
                                <label for="testigos_no">No</label>
                            </div>
                        </div>
                    </div>

                    <div id="testigos_container" style="display: none;">
                        <div class="form-group">
                            <label for="testigo_1">Nombre del Testigo 1</label>
                            <input type="text" id="testigo_1" name="testigo_1">
                        </div>

                        <div class="form-group">
                            <label for="testigo_2">Nombre del Testigo 2 (opcional)</label>
                            <input type="text" id="testigo_2" name="testigo_2">
                        </div>
                    </div>
                </div>

                <!-- Sección 9: Declaración del Afectado -->
                <div class="seccion">
                    <h2 class="seccion-titulo">9. Declaración del Afectado</h2>

                    <div class="form-group">
                        <label for="declaracion_afectado" class="required">Explique con detalle lo sucedido desde su punto de vista</label>
                        <textarea id="declaracion_afectado" name="declaracion_afectado" rows="8" required></textarea>
                        <p class="help-text">Esta es su oportunidad para explicar todo lo que considere importante sobre el incidente</p>
                    </div>
                </div>

                <!-- Sección 10: Adjuntar Evidencias -->
                <div class="seccion">
                    <h2 class="seccion-titulo">10. Adjuntar Evidencias</h2>

                    <div class="form-group">
                        <label for="foto_lesion">Foto de la lesión (opcional)</label>
                        <div class="file-upload">
                            <input type="file" id="foto_lesion" name="foto_lesion" accept="image/*" onchange="mostrarPreview(this, 'preview_lesion')">
                            <label for="foto_lesion" class="file-upload-label">
                                📷 Haga clic aquí para seleccionar una foto de la lesión
                            </label>
                            <div id="preview_lesion" class="file-preview"></div>
                        </div>
                        <p class="help-text">Máximo 5MB - Formatos: JPG, PNG, GIF</p>
                    </div>

                    <div class="form-group">
                        <label for="foto_area">Foto del área donde ocurrió (opcional)</label>
                        <div class="file-upload">
                            <input type="file" id="foto_area" name="foto_area" accept="image/*" onchange="mostrarPreview(this, 'preview_area')">
                            <label for="foto_area" class="file-upload-label">
                                📷 Haga clic aquí para seleccionar una foto del área
                            </label>
                            <div id="preview_area" class="file-preview"></div>
                        </div>
                        <p class="help-text">Máximo 5MB - Formatos: JPG, PNG, GIF</p>
                    </div>
                </div>

                <!-- Sección 11: Confirmación del Afectado -->
                <div class="seccion">
                    <h2 class="seccion-titulo">11. Confirmación del Afectado</h2>

                    <div class="firma-container">
                        <div class="form-group">
                            <div class="checkbox-confirmacion">
                                <input type="checkbox" id="confirmo_veracidad" name="confirmo_veracidad" required>
                                <label for="confirmo_veracidad" class="required">
                                    Confirmo que la información proporcionada en este formulario es verdadera y precisa según mi conocimiento.
                                    Entiendo que esta información será utilizada para investigar el incidente y tomar medidas preventivas.
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Fecha de llenado</label>
                            <input type="text" value="<?php echo date('d/m/Y H:i'); ?>" readonly style="background: #f0f0f0;">
                        </div>
                    </div>
                </div>

                <!-- Botón de Envío -->
                <div style="margin-top: 30px;">
                    <button type="submit" class="btn-submit">Enviar Aviso de Accidente</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Mostrar/ocultar campo "otra parte del cuerpo"
        document.getElementById('parte_otra').addEventListener('change', function() {
            document.getElementById('otra_parte_container').style.display = this.checked ? 'block' : 'none';
        });

        // Mostrar/ocultar campo "otra lesión"
        document.getElementById('lesion_otra').addEventListener('change', function() {
            document.getElementById('otra_lesion_container').style.display = this.checked ? 'block' : 'none';
        });

        // Mostrar/ocultar descripción de atención
        document.querySelectorAll('input[name="recibio_atencion"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                document.getElementById('descripcion_atencion_container').style.display =
                    this.value === 'si' ? 'block' : 'none';
            });
        });

        // Mostrar/ocultar nombres de testigos
        document.querySelectorAll('input[name="hubo_testigos"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                document.getElementById('testigos_container').style.display =
                    this.value === 'si' ? 'block' : 'none';
            });
        });

        // Función para mostrar preview de imágenes
        function mostrarPreview(input, previewId) {
            const preview = document.getElementById(previewId);
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = '✓ Archivo seleccionado: ' + input.files[0].name;
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.innerHTML = '';
            }
        }

        // Validación antes de enviar
        document.getElementById('formularioAviso').addEventListener('submit', function(e) {
            const partesSeleccionadas = document.querySelectorAll('input[name="partes_cuerpo[]"]:checked');
            if (partesSeleccionadas.length === 0) {
                e.preventDefault();
                alert('Por favor seleccione al menos una parte del cuerpo afectada.');
                return false;
            }

            if (!document.getElementById('confirmo_veracidad').checked) {
                e.preventDefault();
                alert('Debe confirmar la veracidad de la información proporcionada.');
                return false;
            }

            return true;
        });

        // Establecer fecha máxima para el incidente (no puede ser futura)
        document.getElementById('fecha_incidente').max = new Date().toISOString().split('T')[0];
    </script>

    <!-- Body Selector Advanced Script -->
    <script src="body_selector_advanced.js"></script>
</body>
</html>
