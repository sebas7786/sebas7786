<?php
/**
 * Importación Masiva de Proveedores desde CSV/Excel
 * LicitacionesYA - Sistema de Gestión de Proveedores
 */

session_start();

// Verificar que es admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$mensaje = '';
$mensaje_tipo = '';
$resultados = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    $archivo = $_FILES['archivo'];

    // Validar archivo
    if ($archivo['error'] === UPLOAD_ERR_OK) {
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, ['csv', 'txt'])) {
            $mensaje = "❌ Error: Solo se permiten archivos CSV o TXT";
            $mensaje_tipo = 'error';
        } else {
            // Procesar archivo
            $handle = fopen($archivo['tmp_name'], 'r');

            if ($handle !== false) {
                $fila_num = 0;
                $importados = 0;
                $actualizados = 0;
                $errores = 0;
                $errores_detalle = [];

                // Leer encabezados
                $encabezados = fgetcsv($handle, 1000, ',');

                // Mapear nombres de columnas (flexible)
                $mapa_columnas = [
                    'cedula_proveedor' => ['cedula_proveedor', 'cedula', 'cédula', 'identificacion'],
                    'nombre_proveedor' => ['nombre_proveedor', 'nombre', 'razon_social', 'razón_social'],
                    'tipo_proveedor' => ['tipo_proveedor', 'tipo'],
                    'tamano_proveedor' => ['tamano_proveedor', 'tamaño_proveedor', 'tamano', 'tamaño', 'size'],
                    'fecha_constitucion' => ['fecha_constitucion', 'fecha_constitucion', 'fecha_fundacion'],
                    'fecha_expiracion' => ['fecha_expiracion', 'fecha_vencimiento'],
                    'zona_geo_prov' => ['zona_geo_prov', 'zona', 'provincia', 'region'],
                    'direccion' => ['direccion', 'dirección'],
                    'telefono' => ['telefono', 'teléfono', 'tel', 'phone'],
                    'email' => ['email', 'correo', 'mail'],
                    'sitio_web' => ['sitio_web', 'web', 'website', 'url'],
                    'actividad_economica' => ['actividad_economica', 'actividad', 'giro'],
                    'codigo_actividad' => ['codigo_actividad', 'código_actividad', 'ciiu'],
                    'estado_proveedor' => ['estado_proveedor', 'estado', 'status'],
                ];

                // Crear índice de columnas
                $indices = [];
                foreach ($encabezados as $idx => $nombre_col) {
                    $nombre_col = strtolower(trim($nombre_col));
                    foreach ($mapa_columnas as $campo => $variaciones) {
                        if (in_array($nombre_col, $variaciones)) {
                            $indices[$campo] = $idx;
                            break;
                        }
                    }
                }

                // Verificar que al menos tengamos cédula y nombre
                if (!isset($indices['cedula_proveedor']) || !isset($indices['nombre_proveedor'])) {
                    $mensaje = "❌ Error: El archivo debe tener al menos las columnas 'cedula_proveedor' y 'nombre_proveedor'";
                    $mensaje_tipo = 'error';
                    fclose($handle);
                } else {
                    // Procesar filas
                    while (($fila = fgetcsv($handle, 1000, ',')) !== false) {
                        $fila_num++;

                        // Saltar filas vacías
                        if (empty(array_filter($fila))) {
                            continue;
                        }

                        try {
                            // Extraer datos
                            $cedula = isset($indices['cedula_proveedor']) ? trim($fila[$indices['cedula_proveedor']]) : '';
                            $nombre = isset($indices['nombre_proveedor']) ? trim($fila[$indices['nombre_proveedor']]) : '';

                            // Validar campos obligatorios
                            if (empty($cedula) || empty($nombre)) {
                                $errores++;
                                $errores_detalle[] = "Fila $fila_num: Cédula o nombre vacío";
                                continue;
                            }

                            // Extraer campos opcionales
                            $tipo = isset($indices['tipo_proveedor']) ? trim($fila[$indices['tipo_proveedor']]) : 'Jurídica';
                            $tamano = isset($indices['tamano_proveedor']) ? trim($fila[$indices['tamano_proveedor']]) : 'No Especificado';
                            $fecha_constitucion = isset($indices['fecha_constitucion']) ? trim($fila[$indices['fecha_constitucion']]) : null;
                            $fecha_expiracion = isset($indices['fecha_expiracion']) ? trim($fila[$indices['fecha_expiracion']]) : null;
                            $zona = isset($indices['zona_geo_prov']) ? trim($fila[$indices['zona_geo_prov']]) : null;
                            $direccion = isset($indices['direccion']) ? trim($fila[$indices['direccion']]) : null;
                            $telefono = isset($indices['telefono']) ? trim($fila[$indices['telefono']]) : null;
                            $email = isset($indices['email']) ? trim($fila[$indices['email']]) : null;
                            $sitio_web = isset($indices['sitio_web']) ? trim($fila[$indices['sitio_web']]) : null;
                            $actividad = isset($indices['actividad_economica']) ? trim($fila[$indices['actividad_economica']]) : null;
                            $codigo_actividad = isset($indices['codigo_actividad']) ? trim($fila[$indices['codigo_actividad']]) : null;
                            $estado = isset($indices['estado_proveedor']) ? trim($fila[$indices['estado_proveedor']]) : 'Activo';

                            // Validar valores ENUM
                            $tipos_validos = ['Física', 'Jurídica', 'Extranjera', 'Consorcio', 'Otro'];
                            if (!in_array($tipo, $tipos_validos)) {
                                $tipo = 'Jurídica';
                            }

                            $tamanos_validos = ['Micro', 'Pequeña', 'Mediana', 'Grande', 'No Especificado'];
                            if (!in_array($tamano, $tamanos_validos)) {
                                $tamano = 'No Especificado';
                            }

                            $estados_validos = ['Activo', 'Inactivo', 'Suspendido', 'Inhabilitado'];
                            if (!in_array($estado, $estados_validos)) {
                                $estado = 'Activo';
                            }

                            // Validar fechas
                            if (!empty($fecha_constitucion) && !strtotime($fecha_constitucion)) {
                                $fecha_constitucion = null;
                            }
                            if (!empty($fecha_expiracion) && !strtotime($fecha_expiracion)) {
                                $fecha_expiracion = null;
                            }

                            // Verificar si ya existe
                            $stmt = $pdo->prepare("SELECT id FROM proveedores WHERE cedula_proveedor = ?");
                            $stmt->execute([$cedula]);
                            $existe = $stmt->fetch();

                            if ($existe) {
                                // Actualizar
                                $stmt = $pdo->prepare("
                                    UPDATE proveedores SET
                                        nombre_proveedor = ?,
                                        tipo_proveedor = ?,
                                        tamano_proveedor = ?,
                                        fecha_constitucion = ?,
                                        fecha_expiracion = ?,
                                        zona_geo_prov = ?,
                                        direccion = ?,
                                        telefono = ?,
                                        email = ?,
                                        sitio_web = ?,
                                        actividad_economica = ?,
                                        codigo_actividad = ?,
                                        estado_proveedor = ?
                                    WHERE cedula_proveedor = ?
                                ");

                                $stmt->execute([
                                    $nombre, $tipo, $tamano, $fecha_constitucion, $fecha_expiracion,
                                    $zona, $direccion, $telefono, $email, $sitio_web,
                                    $actividad, $codigo_actividad, $estado, $cedula
                                ]);

                                $actualizados++;
                            } else {
                                // Insertar
                                $stmt = $pdo->prepare("
                                    INSERT INTO proveedores (
                                        cedula_proveedor, nombre_proveedor, tipo_proveedor, tamano_proveedor,
                                        fecha_constitucion, fecha_expiracion, zona_geo_prov, direccion,
                                        telefono, email, sitio_web, actividad_economica, codigo_actividad,
                                        estado_proveedor, registrado_por
                                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                                ");

                                $stmt->execute([
                                    $cedula, $nombre, $tipo, $tamano, $fecha_constitucion, $fecha_expiracion,
                                    $zona, $direccion, $telefono, $email, $sitio_web,
                                    $actividad, $codigo_actividad, $estado, $_SESSION['user_id']
                                ]);

                                $importados++;
                            }
                        } catch (PDOException $e) {
                            $errores++;
                            $errores_detalle[] = "Fila $fila_num: " . $e->getMessage();
                        }
                    }

                    fclose($handle);

                    // Resultados
                    $resultados = [
                        'total' => $fila_num,
                        'importados' => $importados,
                        'actualizados' => $actualizados,
                        'errores' => $errores,
                        'errores_detalle' => $errores_detalle
                    ];

                    if ($errores === 0) {
                        $mensaje = "✅ Importación completada: $importados nuevos, $actualizados actualizados";
                        $mensaje_tipo = 'success';
                    } else {
                        $mensaje = "⚠️ Importación con errores: $importados nuevos, $actualizados actualizados, $errores errores";
                        $mensaje_tipo = 'warning';
                    }
                }
            } else {
                $mensaje = "❌ Error al abrir el archivo";
                $mensaje_tipo = 'error';
            }
        }
    } else {
        $mensaje = "❌ Error al subir el archivo";
        $mensaje_tipo = 'error';
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Proveedores - LicitacionesYA</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #f5f7fa;
            color: #2d3748;
            line-height: 1.6;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        header h1 {
            font-size: 2em;
            margin-bottom: 10px;
        }

        .card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .mensaje {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .mensaje-success {
            background: #c6f6d5;
            color: #22543d;
            border-left: 4px solid #48bb78;
        }

        .mensaje-error {
            background: #fed7d7;
            color: #742a2a;
            border-left: 4px solid #f56565;
        }

        .mensaje-warning {
            background: #feebc8;
            color: #7c2d12;
            border-left: 4px solid #ed8936;
        }

        .upload-zone {
            border: 3px dashed #cbd5e0;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            transition: all 0.3s;
        }

        .upload-zone:hover {
            border-color: #667eea;
            background: #f7fafc;
        }

        .upload-zone input[type="file"] {
            display: none;
        }

        .upload-label {
            cursor: pointer;
            color: #667eea;
            font-weight: 600;
            font-size: 1.1em;
        }

        .upload-icon {
            font-size: 4em;
            margin-bottom: 20px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #718096;
            color: white;
        }

        .btn-success {
            background: #48bb78;
            color: white;
            width: 100%;
            margin-top: 20px;
        }

        .btn-success:hover {
            background: #38a169;
        }

        .instructions {
            background: #edf2f7;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .instructions h3 {
            margin-bottom: 15px;
            color: #2d3748;
        }

        .instructions ul {
            margin-left: 20px;
        }

        .instructions li {
            margin-bottom: 8px;
        }

        .instructions code {
            background: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
            color: #e53e3e;
        }

        .ejemplo-tabla {
            overflow-x: auto;
            margin-top: 15px;
        }

        .ejemplo-tabla table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            font-size: 0.9em;
        }

        .ejemplo-tabla th,
        .ejemplo-tabla td {
            padding: 10px;
            text-align: left;
            border: 1px solid #e2e8f0;
        }

        .ejemplo-tabla th {
            background: #f7fafc;
            font-weight: 600;
        }

        .resultado-box {
            background: #f7fafc;
            border-left: 4px solid #667eea;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .resultado-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .resultado-stat {
            text-align: center;
            padding: 15px;
            background: white;
            border-radius: 8px;
        }

        .resultado-stat .number {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
        }

        .resultado-stat .label {
            color: #718096;
            font-size: 0.9em;
        }

        .errores-lista {
            max-height: 300px;
            overflow-y: auto;
            background: #fff5f5;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }

        .errores-lista ul {
            list-style: none;
        }

        .errores-lista li {
            padding: 5px 0;
            color: #742a2a;
            font-size: 0.9em;
        }

        .file-info {
            margin-top: 20px;
            padding: 15px;
            background: #f7fafc;
            border-radius: 8px;
            display: none;
        }

        .file-info.show {
            display: block;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            header {
                padding: 20px;
            }

            .card {
                padding: 20px;
            }

            .resultado-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>📥 Importar Proveedores</h1>
            <p>Importación masiva desde archivo CSV</p>
        </header>

        <?php if ($mensaje): ?>
            <div class="mensaje mensaje-<?php echo htmlspecialchars($mensaje_tipo); ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <?php if ($resultados): ?>
            <div class="resultado-box">
                <h3>📊 Resultados de Importación</h3>
                <div class="resultado-stats">
                    <div class="resultado-stat">
                        <div class="number"><?php echo $resultados['total']; ?></div>
                        <div class="label">Filas Procesadas</div>
                    </div>
                    <div class="resultado-stat">
                        <div class="number" style="color: #48bb78;"><?php echo $resultados['importados']; ?></div>
                        <div class="label">Nuevos</div>
                    </div>
                    <div class="resultado-stat">
                        <div class="number" style="color: #4299e1;"><?php echo $resultados['actualizados']; ?></div>
                        <div class="label">Actualizados</div>
                    </div>
                    <div class="resultado-stat">
                        <div class="number" style="color: #f56565;"><?php echo $resultados['errores']; ?></div>
                        <div class="label">Errores</div>
                    </div>
                </div>

                <?php if (!empty($resultados['errores_detalle'])): ?>
                    <h4 style="margin-top: 20px;">⚠️ Detalle de Errores:</h4>
                    <div class="errores-lista">
                        <ul>
                            <?php foreach (array_slice($resultados['errores_detalle'], 0, 50) as $error): ?>
                                <li>• <?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                            <?php if (count($resultados['errores_detalle']) > 50): ?>
                                <li style="margin-top: 10px; font-style: italic;">
                                    ... y <?php echo count($resultados['errores_detalle']) - 50; ?> errores más
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div style="margin-top: 20px; text-align: center;">
                    <a href="proveedores.php" class="btn btn-primary">Ver Proveedores</a>
                    <a href="importar_proveedores.php" class="btn btn-secondary">Nueva Importación</a>
                </div>
            </div>
        <?php else: ?>
            <!-- Instrucciones -->
            <div class="card">
                <div class="instructions">
                    <h3>📝 Instrucciones</h3>
                    <ul>
                        <li><strong>Formato:</strong> El archivo debe ser CSV (separado por comas)</li>
                        <li><strong>Primera fila:</strong> Debe contener los nombres de las columnas</li>
                        <li><strong>Columnas obligatorias:</strong>
                            <ul>
                                <li><code>cedula_proveedor</code> (o <code>cedula</code>)</li>
                                <li><code>nombre_proveedor</code> (o <code>nombre</code>)</li>
                            </ul>
                        </li>
                        <li><strong>Columnas opcionales:</strong> tipo_proveedor, tamano_proveedor, fecha_constitucion, fecha_expiracion, zona_geo_prov, direccion, telefono, email, sitio_web, actividad_economica, codigo_actividad, estado_proveedor</li>
                        <li><strong>Duplicados:</strong> Si la cédula ya existe, se actualiza el registro</li>
                        <li><strong>Codificación:</strong> UTF-8 (recomendado)</li>
                    </ul>
                </div>

                <div class="ejemplo-tabla">
                    <h4 style="margin-bottom: 10px;">Ejemplo de CSV:</h4>
                    <table>
                        <thead>
                            <tr>
                                <th>cedula_proveedor</th>
                                <th>nombre_proveedor</th>
                                <th>tipo_proveedor</th>
                                <th>tamano_proveedor</th>
                                <th>zona_geo_prov</th>
                                <th>telefono</th>
                                <th>email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>3-101-123456</td>
                                <td>Empresa Ejemplo S.A.</td>
                                <td>Jurídica</td>
                                <td>Mediana</td>
                                <td>San José</td>
                                <td>2222-3333</td>
                                <td>contacto@ejemplo.com</td>
                            </tr>
                            <tr>
                                <td>1-0234-5678</td>
                                <td>Juan Pérez Solano</td>
                                <td>Física</td>
                                <td>Micro</td>
                                <td>Heredia</td>
                                <td>8888-9999</td>
                                <td>juan@correo.com</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: 20px; padding: 15px; background: #fef5e7; border-radius: 8px;">
                    <strong>💡 Tip:</strong> Puedes crear el CSV desde Excel: "Guardar como" → "CSV (delimitado por comas)"
                </div>
            </div>

            <!-- Formulario de Importación -->
            <div class="card">
                <h3 style="margin-bottom: 20px;">Selecciona el Archivo CSV</h3>
                <form method="POST" enctype="multipart/form-data" id="form-importar">
                    <div class="upload-zone" id="upload-zone">
                        <div class="upload-icon">📄</div>
                        <label for="archivo" class="upload-label">
                            Haz click aquí o arrastra el archivo CSV
                        </label>
                        <input type="file" name="archivo" id="archivo" accept=".csv,.txt" required>
                        <p style="margin-top: 10px; color: #718096; font-size: 0.9em;">
                            Formatos aceptados: CSV, TXT
                        </p>
                    </div>

                    <div class="file-info" id="file-info">
                        <strong>Archivo seleccionado:</strong>
                        <p id="file-name"></p>
                        <p id="file-size" style="color: #718096; font-size: 0.9em;"></p>
                    </div>

                    <button type="submit" class="btn btn-success">
                        🚀 Iniciar Importación
                    </button>
                </form>
            </div>

            <div style="text-align: center; margin-top: 20px;">
                <a href="proveedores.php" class="btn btn-secondary">← Volver a Proveedores</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        const archivo = document.getElementById('archivo');
        const fileInfo = document.getElementById('file-info');
        const fileName = document.getElementById('file-name');
        const fileSize = document.getElementById('file-size');
        const uploadZone = document.getElementById('upload-zone');

        archivo.addEventListener('change', function(e) {
            if (this.files.length > 0) {
                const file = this.files[0];
                fileName.textContent = file.name;
                fileSize.textContent = `Tamaño: ${(file.size / 1024).toFixed(2)} KB`;
                fileInfo.classList.add('show');
                uploadZone.style.borderColor = '#48bb78';
                uploadZone.style.background = '#f0fff4';
            }
        });

        // Drag and drop
        uploadZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = '#667eea';
            this.style.background = '#ebf4ff';
        });

        uploadZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.borderColor = '#cbd5e0';
            this.style.background = 'transparent';
        });

        uploadZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = '#cbd5e0';
            this.style.background = 'transparent';

            if (e.dataTransfer.files.length > 0) {
                archivo.files = e.dataTransfer.files;
                archivo.dispatchEvent(new Event('change'));
            }
        });

        uploadZone.addEventListener('click', function() {
            archivo.click();
        });
    </script>
</body>
</html>
