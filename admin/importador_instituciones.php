<?php
/**
 * ============================================================================
 * IMPORTADOR DE INSTITUCIONES PROVEEDORAS
 * ============================================================================
 * Importa instituciones desde archivo CSV del Observatorio SICOP
 * Formato esperado: Cédula, Nombre Institucion, Direccion, Telefono,
 *                   Representante, Codigo Postal, Provincia, Canton, Distrito
 *
 * Uso: Subir archivo CSV mediante formulario web
 * ============================================================================
 */

require_once __DIR__ . '/../config/db.php';

class ImportadorInstituciones {
    private $conn;
    private $stats = [
        'total' => 0,
        'insertadas' => 0,
        'actualizadas' => 0,
        'errores' => 0
    ];
    private $log = [];

    public function __construct($conn) {
        $this->conn = $conn;
    }

    private function logMensaje($mensaje) {
        $this->log[] = $mensaje;
    }

    private function leerCSV($archivo) {
        $handle = fopen($archivo, 'r');
        if (!$handle) {
            throw new Exception("No se pudo abrir el archivo");
        }

        $primera = fgets($handle);
        rewind($handle);

        // Detectar delimitador
        $delim = substr_count($primera, ';') > substr_count($primera, ',') ? ';' : ',';

        // Detectar encoding
        $encoding = mb_detect_encoding($primera, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);

        $data = [];
        while (($row = fgetcsv($handle, 0, $delim)) !== false) {
            if ($encoding && $encoding !== 'UTF-8') {
                $row = array_map(function($val) use ($encoding) {
                    return mb_convert_encoding($val, 'UTF-8', $encoding);
                }, $row);
            }
            $data[] = $row;
        }

        fclose($handle);
        return $data;
    }

    private function limpiarTexto($texto) {
        if ($texto === null || trim($texto) === '') return null;
        return trim($texto);
    }

    public function importar($archivo_csv) {
        try {
            $this->logMensaje("🚀 Iniciando importación de instituciones...");

            $rows = $this->leerCSV($archivo_csv);
            $header = array_shift($rows); // Remover header

            $this->logMensaje("📄 Archivo cargado: " . count($rows) . " instituciones encontradas");

            // Preparar statement para INSERT/UPDATE
            $sql = "INSERT INTO instituciones_proveedoras
                    (cedula, nombre_institucion, direccion, telefono, representante,
                     codigo_postal, provincia, canton, distrito)
                    VALUES
                    (:cedula, :nombre, :direccion, :telefono, :representante,
                     :codigo_postal, :provincia, :canton, :distrito)
                    ON DUPLICATE KEY UPDATE
                    nombre_institucion = VALUES(nombre_institucion),
                    direccion = VALUES(direccion),
                    telefono = VALUES(telefono),
                    representante = VALUES(representante),
                    codigo_postal = VALUES(codigo_postal),
                    provincia = VALUES(provincia),
                    canton = VALUES(canton),
                    distrito = VALUES(distrito),
                    actualizado = CURRENT_TIMESTAMP";

            $stmt = $this->conn->prepare($sql);

            foreach ($rows as $i => $row) {
                $this->stats['total']++;

                // Validar que tenga al menos la cédula
                if (empty(trim($row[0] ?? ''))) {
                    $this->stats['errores']++;
                    continue;
                }

                // Mapear columnas del CSV
                $cedula = $this->limpiarTexto($row[0] ?? '');
                $nombre = $this->limpiarTexto($row[1] ?? '');
                $direccion = $this->limpiarTexto($row[2] ?? '');
                $telefono = $this->limpiarTexto($row[3] ?? '');
                $representante = $this->limpiarTexto($row[4] ?? '');
                $codigo_postal = $this->limpiarTexto($row[5] ?? '');
                $provincia = $this->limpiarTexto($row[6] ?? '');
                $canton = $this->limpiarTexto($row[7] ?? '');
                $distrito = $this->limpiarTexto($row[8] ?? '');

                try {
                    // Verificar si existe para contar insertadas vs actualizadas
                    $check = $this->conn->prepare("SELECT COUNT(*) FROM instituciones_proveedoras WHERE cedula = :cedula");
                    $check->bindParam(':cedula', $cedula);
                    $check->execute();
                    $existe = $check->fetchColumn() > 0;

                    // Ejecutar INSERT/UPDATE
                    $stmt->bindParam(':cedula', $cedula);
                    $stmt->bindParam(':nombre', $nombre);
                    $stmt->bindParam(':direccion', $direccion);
                    $stmt->bindParam(':telefono', $telefono);
                    $stmt->bindParam(':representante', $representante);
                    $stmt->bindParam(':codigo_postal', $codigo_postal);
                    $stmt->bindParam(':provincia', $provincia);
                    $stmt->bindParam(':canton', $canton);
                    $stmt->bindParam(':distrito', $distrito);
                    $stmt->execute();

                    if ($existe) {
                        $this->stats['actualizadas']++;
                    } else {
                        $this->stats['insertadas']++;
                    }

                } catch (PDOException $e) {
                    $this->stats['errores']++;
                    $this->logMensaje("⚠️ Error en fila " . ($i + 2) . ": " . $e->getMessage());
                }

                // Log de progreso cada 500 registros
                if (($i + 1) % 500 == 0) {
                    $this->logMensaje("⏳ Procesadas " . ($i + 1) . " instituciones...");
                }
            }

            $this->logMensaje("✅ Importación completada!");

        } catch (Exception $e) {
            $this->logMensaje("❌ Error fatal: " . $e->getMessage());
            $this->stats['errores']++;
        }
    }

    public function getStats() {
        return $this->stats;
    }

    public function getLog() {
        return $this->log;
    }
}

// ============================================================================
// PROCESAMIENTO DEL FORMULARIO
// ============================================================================

$resultado = null;
$importador = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    $importador = new ImportadorInstituciones($conn);
    $archivo = $_FILES['archivo'];

    if ($archivo['error'] == 0) {
        $importador->importar($archivo['tmp_name']);
        $resultado = [
            'stats' => $importador->getStats(),
            'log' => $importador->getLog()
        ];
    } else {
        $resultado = [
            'stats' => ['total' => 0, 'insertadas' => 0, 'actualizadas' => 0, 'errores' => 1],
            'log' => ['❌ Error al subir archivo: ' . $archivo['error']]
        ];
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importador de Instituciones Proveedoras</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        .content {
            padding: 30px;
        }
        .upload-form {
            background: #f8f9fa;
            border: 2px dashed #667eea;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            margin-bottom: 30px;
        }
        .upload-form input[type="file"] {
            margin: 20px 0;
            padding: 10px;
            font-size: 14px;
        }
        .upload-form button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.2s;
        }
        .upload-form button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid;
        }
        .stat-card.total { border-color: #6c757d; }
        .stat-card.insertadas { border-color: #28a745; }
        .stat-card.actualizadas { border-color: #007bff; }
        .stat-card.errores { border-color: #dc3545; }
        .stat-card .numero {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .stat-card .label {
            color: #6c757d;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .log-container {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            max-height: 400px;
            overflow-y: auto;
        }
        .log-container h3 {
            margin-bottom: 15px;
            color: #495057;
        }
        .log-item {
            padding: 8px 12px;
            background: white;
            margin-bottom: 8px;
            border-radius: 4px;
            font-size: 13px;
            border-left: 3px solid #667eea;
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .info-box strong {
            color: #1976D2;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏢 Importador de Instituciones Proveedoras</h1>
            <p>Sistema de importación masiva desde Observatorio SICOP</p>
        </div>

        <div class="content">
            <?php if (!$resultado): ?>
                <!-- FORMULARIO DE CARGA -->
                <div class="info-box">
                    <strong>ℹ️ Formato del archivo CSV:</strong><br>
                    Cédula | Nombre Institucion | Direccion | Telefono | Representante | Codigo Postal | Provincia | Canton | Distrito
                </div>

                <form method="POST" enctype="multipart/form-data" class="upload-form">
                    <h3>📁 Selecciona el archivo CSV de instituciones</h3>
                    <input type="file" name="archivo" accept=".csv" required>
                    <br>
                    <button type="submit">🚀 INICIAR IMPORTACIÓN</button>
                </form>

            <?php else: ?>
                <!-- RESULTADOS -->
                <div class="stats-grid">
                    <div class="stat-card total">
                        <div class="numero"><?php echo number_format($resultado['stats']['total']); ?></div>
                        <div class="label">Total Procesadas</div>
                    </div>
                    <div class="stat-card insertadas">
                        <div class="numero"><?php echo number_format($resultado['stats']['insertadas']); ?></div>
                        <div class="label">Nuevas</div>
                    </div>
                    <div class="stat-card actualizadas">
                        <div class="numero"><?php echo number_format($resultado['stats']['actualizadas']); ?></div>
                        <div class="label">Actualizadas</div>
                    </div>
                    <div class="stat-card errores">
                        <div class="numero"><?php echo number_format($resultado['stats']['errores']); ?></div>
                        <div class="label">Errores</div>
                    </div>
                </div>

                <div class="log-container">
                    <h3>📋 Log de Importación</h3>
                    <?php foreach ($resultado['log'] as $item): ?>
                        <div class="log-item"><?php echo htmlspecialchars($item); ?></div>
                    <?php endforeach; ?>
                </div>

                <div style="text-align: center; margin-top: 20px;">
                    <a href="importador_instituciones.php" style="display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; font-weight: 600;">
                        ← Importar otro archivo
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
