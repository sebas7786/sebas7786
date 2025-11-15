<?php
/**
 * ═══════════════════════════════════════════════════════════════════════
 * IMPORTADOR DE ACLARACIONES SICOP
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Importa el archivo "Aclaraciones.csv" del Observatorio SICOP
 * Con interfaz web para subir archivos
 *
 * @version 1.0
 * @date 2025-11-15
 */

ini_set('max_execution_time', 300);
ini_set('memory_limit', '512M');
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('America/Costa_Rica');

require_once __DIR__ . '/../config/db.php';

class ImportadorAclaraciones {
    private $conn;
    private $log = [];
    private $stats = [];
    private $errores_detallados = [];
    private $inicio_tiempo;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->inicio_tiempo = microtime(true);
        $this->inicializarStats();
    }

    private function inicializarStats() {
        $this->stats = [
            'aclaraciones' => ['insertadas' => 0, 'actualizadas' => 0, 'errores' => 0],
            'licitaciones_con_aclaraciones' => 0,
            'respondidas' => 0,
            'pendientes' => 0,
        ];
    }

    private function addLog($mensaje, $tipo = 'info') {
        $this->log[] = [
            'mensaje' => $mensaje,
            'tipo' => $tipo,
            'tiempo' => date('H:i:s')
        ];
    }

    public function importar($archivo_path) {
        $this->addLog("═══════════════════════════════════════════════════════════════════════", 'separator');
        $this->addLog("🚀 INICIANDO IMPORTACIÓN DE ACLARACIONES", 'info');
        $this->addLog("═══════════════════════════════════════════════════════════════════════", 'separator');

        // Verificar archivo
        if (!file_exists($archivo_path)) {
            $this->addLog("❌ ERROR: Archivo no encontrado", 'error');
            return false;
        }

        $size = filesize($archivo_path) / 1024;
        $this->addLog("📂 Archivo: " . basename($archivo_path), 'info');
        $this->addLog("📏 Tamaño: " . number_format($size, 2) . " KB", 'info');

        // Crear/verificar tabla
        $this->crearTabla();

        // Leer CSV
        $data = $this->leerCSV($archivo_path);
        $this->addLog("📊 Total de filas: " . count($data), 'info');

        // Procesar datos
        $this->procesarDatos($data);

        // Calcular estadísticas finales
        $this->calcularEstadisticas();

        $this->addLog("═══════════════════════════════════════════════════════════════════════", 'separator');
        $this->addLog("✅ IMPORTACIÓN COMPLETADA", 'success');
        $this->addLog("═══════════════════════════════════════════════════════════════════════", 'separator');

        return true;
    }

    private function crearTabla() {
        $this->addLog("🔍 Verificando tabla aclaraciones_licitacion...", 'info');

        $sql = "CREATE TABLE IF NOT EXISTS `aclaraciones_licitacion` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `numero_cartel` VARCHAR(20) NOT NULL COMMENT 'Número SICOP del cartel',
            `no_cartel` VARCHAR(100) NULL COMMENT 'Número de cartel descriptivo',
            `titulo` TEXT NULL COMMENT 'Título de la licitación',
            `fecha_solicitud` DATETIME NULL COMMENT 'Fecha en que se hizo la solicitud de aclaración',
            `numero_aclaracion` VARCHAR(50) NULL COMMENT 'Número identificador de la aclaración',
            `solicitante` VARCHAR(255) NULL COMMENT 'Nombre o identificación del solicitante',
            `cedula_empresa_proveedora` VARCHAR(20) NULL COMMENT 'Cédula de la empresa que solicita',
            `estado_respuesta` VARCHAR(50) NULL COMMENT 'Estado de la respuesta (Respondida, Pendiente, etc)',
            `numero_respuesta` VARCHAR(50) NULL COMMENT 'Número de la respuesta',
            `fecha_respuesta` DATETIME NULL COMMENT 'Fecha en que se respondió la aclaración',
            `fecha_importacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha en que se importó este registro',
            `actualizado` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_numero_cartel` (`numero_cartel`),
            INDEX `idx_cedula_empresa` (`cedula_empresa_proveedora`),
            INDEX `idx_estado_respuesta` (`estado_respuesta`),
            INDEX `idx_fecha_solicitud` (`fecha_solicitud`),
            INDEX `idx_numero_aclaracion` (`numero_aclaracion`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='Solicitudes de aclaraciones y respuestas de licitaciones SICOP'";

        try {
            $this->conn->exec($sql);
            $this->addLog("✅ Tabla verificada/creada correctamente", 'success');
        } catch (PDOException $e) {
            $this->addLog("❌ ERROR al crear tabla: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    private function leerCSV($archivo) {
        $this->addLog("📖 Leyendo archivo CSV...", 'info');

        $handle = fopen($archivo, 'r');
        if (!$handle) {
            $this->addLog("❌ ERROR: No se pudo abrir el archivo", 'error');
            return [];
        }

        // Detectar delimitador
        $primera = fgets($handle);
        rewind($handle);
        $delim = substr_count($primera, ';') > substr_count($primera, ',') ? ';' : ',';
        $this->addLog("📝 Delimitador detectado: " . ($delim == ';' ? 'punto y coma (;)' : 'coma (,)'), 'info');

        // Detectar encoding
        $encoding = mb_detect_encoding($primera, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        $this->addLog("🔤 Encoding detectado: " . ($encoding ?: 'UTF-8'), 'info');

        $data = [];
        $fila_num = 0;

        while (($row = fgetcsv($handle, 0, $delim)) !== false) {
            $fila_num++;

            // Convertir encoding si es necesario
            if ($encoding && $encoding !== 'UTF-8') {
                $row = array_map(function($val) use ($encoding) {
                    return mb_convert_encoding($val, 'UTF-8', $encoding);
                }, $row);
            }

            // Limpiar BOM y espacios
            $row = array_map(function($val) {
                return trim(str_replace("\xEF\xBB\xBF", '', $val));
            }, $row);

            // Saltar líneas vacías
            if (trim(implode('', $row)) != '') {
                $data[] = $row;
            }
        }

        fclose($handle);
        $this->addLog("✅ Archivo leído: " . count($data) . " filas", 'success');

        return $data;
    }

    private function procesarDatos($data) {
        $this->addLog("⏳ Procesando aclaraciones...", 'info');

        if (empty($data)) {
            $this->addLog("⚠️ No hay datos para procesar", 'warning');
            return;
        }

        // Primera fila es el header
        $header = array_shift($data);
        $this->addLog("📋 Columnas encontradas: " . count($header), 'info');

        // Preparar statement
        $stmt = $this->prepararStatement();

        // Procesar cada fila
        $procesadas = 0;
        foreach ($data as $fila_num => $row) {
            $procesadas++;

            if ($procesadas % 100 === 0) {
                $this->addLog("   Procesadas: $procesadas / " . count($data), 'info');
            }

            $this->procesarFila($row, $stmt, $fila_num + 2); // +2 porque empezamos en fila 1 y saltamos header
        }

        $this->addLog("✅ Procesamiento completado: $procesadas filas", 'success');
    }

    private function prepararStatement() {
        $sql = "INSERT INTO aclaraciones_licitacion (
                    numero_cartel,
                    no_cartel,
                    titulo,
                    fecha_solicitud,
                    numero_aclaracion,
                    solicitante,
                    cedula_empresa_proveedora,
                    estado_respuesta,
                    numero_respuesta,
                    fecha_respuesta
                ) VALUES (
                    :numero_cartel,
                    :no_cartel,
                    :titulo,
                    :fecha_solicitud,
                    :numero_aclaracion,
                    :solicitante,
                    :cedula_empresa_proveedora,
                    :estado_respuesta,
                    :numero_respuesta,
                    :fecha_respuesta
                )
                ON DUPLICATE KEY UPDATE
                    titulo = VALUES(titulo),
                    fecha_solicitud = VALUES(fecha_solicitud),
                    estado_respuesta = VALUES(estado_respuesta),
                    numero_respuesta = VALUES(numero_respuesta),
                    fecha_respuesta = VALUES(fecha_respuesta)";

        return $this->conn->prepare($sql);
    }

    private function procesarFila($row, $stmt, $fila_num) {
        try {
            // Extraer datos (basado en las columnas del CSV)
            $numero_cartel = $this->limpiar($row[0] ?? '');
            $no_cartel = $this->limpiar($row[1] ?? '');
            $titulo = $this->limpiar($row[2] ?? '');
            $fecha_solicitud = $this->parsearFecha($row[3] ?? '');
            $numero_aclaracion = $this->limpiar($row[4] ?? '');
            $solicitante = $this->limpiar($row[5] ?? '');
            $cedula_empresa = $this->limpiar($row[6] ?? '');
            $estado_respuesta = $this->limpiar($row[7] ?? '');
            $numero_respuesta = $this->limpiar($row[8] ?? '');
            $fecha_respuesta = $this->parsearFecha($row[9] ?? '');

            // Validar campos requeridos
            if (empty($numero_cartel) || empty($numero_aclaracion)) {
                $this->stats['aclaraciones']['errores']++;
                $this->errores_detallados[] = "Fila $fila_num: Faltan datos requeridos (número_cartel o número_aclaración)";
                return false;
            }

            // Bind parameters
            $stmt->bindParam(':numero_cartel', $numero_cartel);
            $stmt->bindParam(':no_cartel', $no_cartel);
            $stmt->bindParam(':titulo', $titulo);
            $stmt->bindParam(':fecha_solicitud', $fecha_solicitud);
            $stmt->bindParam(':numero_aclaracion', $numero_aclaracion);
            $stmt->bindParam(':solicitante', $solicitante);
            $stmt->bindParam(':cedula_empresa_proveedora', $cedula_empresa);
            $stmt->bindParam(':estado_respuesta', $estado_respuesta);
            $stmt->bindParam(':numero_respuesta', $numero_respuesta);
            $stmt->bindParam(':fecha_respuesta', $fecha_respuesta);

            // Ejecutar
            $resultado = $stmt->execute();

            if ($resultado) {
                $rowCount = $stmt->rowCount();
                if ($rowCount === 1) {
                    $this->stats['aclaraciones']['insertadas']++;
                } elseif ($rowCount === 2) {
                    $this->stats['aclaraciones']['actualizadas']++;
                }
                return true;
            } else {
                $this->stats['aclaraciones']['errores']++;
                return false;
            }

        } catch (PDOException $e) {
            $this->stats['aclaraciones']['errores']++;
            $this->errores_detallados[] = "Fila $fila_num: " . $e->getMessage();
            return false;
        }
    }

    private function limpiar($valor) {
        $valor = trim($valor);
        return empty($valor) ? null : $valor;
    }

    private function parsearFecha($fecha_str) {
        $fecha_str = trim($fecha_str);
        if (empty($fecha_str)) {
            return null;
        }

        // Formatos comunes de fecha
        $formatos = [
            'd/m/Y H:i:s',
            'd/m/Y H:i',
            'd/m/Y',
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d',
            'd-m-Y H:i:s',
            'd-m-Y H:i',
            'd-m-Y'
        ];

        foreach ($formatos as $formato) {
            $dt = DateTime::createFromFormat($formato, $fecha_str);
            if ($dt !== false) {
                return $dt->format('Y-m-d H:i:s');
            }
        }

        return null;
    }

    private function calcularEstadisticas() {
        $this->addLog("📊 Calculando estadísticas finales...", 'info');

        try {
            $stmt = $this->conn->query("
                SELECT
                    COUNT(DISTINCT numero_cartel) as licitaciones,
                    COUNT(CASE WHEN fecha_respuesta IS NOT NULL THEN 1 END) as respondidas,
                    COUNT(CASE WHEN fecha_respuesta IS NULL THEN 1 END) as pendientes
                FROM aclaraciones_licitacion
            ");
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            $this->stats['licitaciones_con_aclaraciones'] = $stats['licitaciones'];
            $this->stats['respondidas'] = $stats['respondidas'];
            $this->stats['pendientes'] = $stats['pendientes'];

            $this->addLog("✅ Estadísticas calculadas", 'success');
        } catch (PDOException $e) {
            $this->addLog("⚠️ No se pudieron calcular estadísticas: " . $e->getMessage(), 'warning');
        }
    }

    public function getStats() {
        return $this->stats;
    }

    public function getLog() {
        return $this->log;
    }

    public function getErrores() {
        return $this->errores_detallados;
    }

    public function getTiempoEjecucion() {
        return round(microtime(true) - $this->inicio_tiempo, 2);
    }
}

// ═══════════════════════════════════════════════════════════════════════
// PROCESAMIENTO
// ═══════════════════════════════════════════════════════════════════════

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    $imp = new ImportadorAclaraciones($conn);
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Importador de Aclaraciones - Resultados</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, system-ui;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 40px;
            text-align: center;
        }
        .header h1 { font-size: 36px; margin-bottom: 8px; }
        .header .version { font-size: 14px; opacity: 0.95; }
        .content { padding: 40px; }
        h2 { color: #333; margin: 30px 0 15px 0; font-size: 24px; }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .stat-card {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            padding: 25px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-number {
            font-size: 42px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 8px;
        }
        .stat-label {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
        }
        .stat-detail {
            font-size: 12px;
            color: #999;
            margin-top: 8px;
        }
        .log-container {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            max-height: 400px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
        .log-entry {
            padding: 4px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .log-entry:last-child { border-bottom: none; }
        .log-info { color: #333; }
        .log-success { color: #059669; font-weight: 600; }
        .log-error { color: #dc2626; font-weight: 600; }
        .log-warning { color: #f59e0b; font-weight: 600; }
        .log-separator {
            border-top: 2px solid #e5e7eb;
            margin: 8px 0;
        }
        .highlight {
            background: #ddd6fe;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #667eea;
        }
        .highlight h3 { color: #5b21b6; margin-bottom: 10px; }
        .errores {
            background: #fee2e2;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #dc2626;
        }
        .errores h3 { color: #dc2626; margin-bottom: 10px; }
        .btn {
            display: inline-block;
            padding: 12px 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin-top: 20px;
        }
        .btn:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <?php
    echo "<div class='container'>";
    echo "<div class='header'><h1>🔍 Importación de Aclaraciones</h1><p class='version'>Sistema de Importación SICOP</p></div>";
    echo "<div class='content'>";

    $archivo = $_FILES['archivo'];
    if ($archivo['error'] == 0) {
        $imp->importar($archivo['tmp_name']);
    }

    $stats = $imp->getStats();

    if ($stats['aclaraciones']['insertadas'] > 0 || $stats['aclaraciones']['actualizadas'] > 0) {
        echo "<div class='highlight'>";
        echo "<h3 style='color: #5b21b6; margin: 0 0 10px 0;'>✅ ¡Importación exitosa!</h3>";
        echo "<p style='margin: 0;'>Las aclaraciones han sido importadas correctamente.</p>";
        echo "</div>";
    }

    echo "<h2>📊 Resultados</h2>";
    echo "<div class='stats-grid'>";

    // Aclaraciones
    $total_acl = ($stats['aclaraciones']['insertadas'] ?? 0) + ($stats['aclaraciones']['actualizadas'] ?? 0);
    echo "<div class='stat-card'>";
    echo "<div class='stat-number'>$total_acl</div>";
    echo "<div class='stat-label'>Aclaraciones</div>";
    echo "<div class='stat-detail'>I:{$stats['aclaraciones']['insertadas']} A:{$stats['aclaraciones']['actualizadas']} E:{$stats['aclaraciones']['errores']}</div>";
    echo "</div>";

    // Licitaciones
    echo "<div class='stat-card'>";
    echo "<div class='stat-number'>{$stats['licitaciones_con_aclaraciones']}</div>";
    echo "<div class='stat-label'>Licitaciones</div>";
    echo "<div class='stat-detail'>Con aclaraciones</div>";
    echo "</div>";

    // Respondidas
    echo "<div class='stat-card'>";
    echo "<div class='stat-number'>{$stats['respondidas']}</div>";
    echo "<div class='stat-label'>Respondidas</div>";
    echo "<div class='stat-detail'>Con fecha de respuesta</div>";
    echo "</div>";

    // Pendientes
    echo "<div class='stat-card'>";
    echo "<div class='stat-number'>{$stats['pendientes']}</div>";
    echo "<div class='stat-label'>Pendientes</div>";
    echo "<div class='stat-detail'>Sin respuesta</div>";
    echo "</div>";

    echo "</div>";

    echo "<p><strong>⏱️ Tiempo:</strong> " . $imp->getTiempoEjecucion() . " segundos</p>";

    echo "<h2>📋 Log de Proceso</h2>";
    echo "<div class='log-container'>";

    foreach ($imp->getLog() as $entry) {
        if ($entry['tipo'] == 'separator') {
            echo "<div class='log-separator'></div>";
        } else {
            $clase = 'log-' . $entry['tipo'];
            echo "<div class='log-entry $clase'>[{$entry['tiempo']}] {$entry['mensaje']}</div>";
        }
    }

    echo "</div>";

    $errores = $imp->getErrores();
    if (!empty($errores)) {
        echo "<div class='errores'>";
        echo "<h3>⚠️ Errores (" . count($errores) . ")</h3>";
        echo "<ul style='margin: 0; padding-left: 20px;'>";
        foreach (array_slice($errores, 0, 20) as $error) {
            echo "<li style='padding: 4px 0; font-size: 12px;'>$error</li>";
        }
        if (count($errores) > 20) {
            echo "<li><em>... y " . (count($errores) - 20) . " más</em></li>";
        }
        echo "</ul></div>";
    }

    echo "<div style='text-align: center;'>";
    echo "<a href='importador_aclaraciones.php' class='btn'>🔄 Nueva Importación</a>";
    echo "<a href='probar_aclaraciones.php' class='btn'>📊 Ver Estadísticas</a>";
    echo "</div>";

    echo "</div></div></body></html>";
    ?>
    <?php
} else {
    // Mostrar formulario
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Importador de Aclaraciones SICOP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, system-ui;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            max-width: 700px;
            width: 100%;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 40px;
            text-align: center;
        }
        .header h1 { font-size: 36px; margin-bottom: 8px; }
        .header .version { font-size: 14px; opacity: 0.95; }
        .content { padding: 40px; }
        .upload-area {
            border: 3px dashed #667eea;
            border-radius: 12px;
            padding: 50px 30px;
            text-align: center;
            background: #f9fafb;
            margin: 20px 0;
            cursor: pointer;
            transition: all 0.3s;
        }
        .upload-area:hover { border-color: #5b21b6; background: #f3f4f6; }
        .upload-area h3 { font-size: 20px; color: #333; margin-bottom: 12px; }
        .upload-area p { color: #666; margin: 10px 0; }
        input[type="file"] { display: none; }
        .file-label {
            display: inline-block;
            padding: 12px 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 10px;
        }
        .file-label:hover { opacity: 0.9; }
        .btn-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
        }
        .btn-submit:hover { opacity: 0.9; }
        .btn-submit:disabled { opacity: 0.5; cursor: not-allowed; }
        .highlight {
            background: #ddd6fe;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #667eea;
        }
        .highlight h4 { color: #5b21b6; margin-bottom: 10px; font-size: 16px; }
        .highlight ul { padding-left: 20px; color: #5b21b6; }
        .highlight li { padding: 4px 0; font-size: 14px; }
        .file-name {
            margin-top: 15px;
            padding: 10px;
            background: #f0fdf4;
            border-radius: 6px;
            color: #166534;
            font-size: 14px;
            display: none;
        }
        .file-name.show { display: block; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Aclaraciones SICOP</h1>
            <p class="version">Sistema de Importación</p>
        </div>

        <div class="content">
            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                    <h3>📁 Selecciona el archivo CSV</h3>
                    <p>Aclaraciones.csv del Observatorio SICOP</p>
                    <label for="fileInput" class="file-label">Examinar Archivo</label>
                    <input type="file" name="archivo" id="fileInput" accept=".csv" required>
                    <div class="file-name" id="fileName"></div>
                </div>

                <button type="submit" class="btn-submit" id="submitBtn" disabled>
                    🚀 INICIAR IMPORTACIÓN
                </button>
            </form>

            <div class="highlight">
                <h4>📋 Información</h4>
                <ul>
                    <li><strong>Archivo esperado:</strong> Aclaraciones.csv</li>
                    <li><strong>Fuente:</strong> <a href="https://observatoriosicop.hacienda.go.cr/" target="_blank" style="color: #5b21b6;">Observatorio SICOP</a></li>
                    <li><strong>Formato:</strong> CSV con 10 columnas</li>
                    <li><strong>Función:</strong> Importa solicitudes y respuestas de aclaraciones</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        const fileInput = document.getElementById('fileInput');
        const submitBtn = document.getElementById('submitBtn');
        const fileName = document.getElementById('fileName');

        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                submitBtn.disabled = false;
                fileName.textContent = '✓ Archivo seleccionado: ' + this.files[0].name;
                fileName.classList.add('show');
            } else {
                submitBtn.disabled = true;
                fileName.classList.remove('show');
            }
        });
    </script>
</body>
</html>
    <?php
}
?>
