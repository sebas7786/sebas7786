<?php
/**
 * ============================================================================
 * IMPORTADOR DE PROVEEDORAS
 * ============================================================================
 * Empresas proveedoras que ofertan en licitaciones
 * CSV: Cédula Proveedor, Nombre Proveedor, Tipo Proveedor, Tamaño Proveedor,
 *      Codigo Postal, Provincia, Canton, Distrito
 * ============================================================================
 */

require_once __DIR__ . '/../config/db.php';

class ImportadorProveedoras {
    private $conn;
    private $stats = ['total' => 0, 'insertadas' => 0, 'actualizadas' => 0, 'errores' => 0];
    private $log = [];

    public function __construct($conn) {
        $this->conn = $conn;
    }

    private function log($msg) {
        $this->log[] = $msg;
    }

    private function limpiar($texto) {
        if ($texto === null || trim($texto) === '') return null;
        return trim($texto);
    }

    private function leerCSV($archivo) {
        $handle = fopen($archivo, 'r');
        if (!$handle) throw new Exception("No se pudo abrir el archivo");

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

    public function importar($archivo_csv) {
        try {
            $this->log("🚀 Iniciando importación de proveedoras...");

            $rows = $this->leerCSV($archivo_csv);
            $header = array_shift($rows); // Remover header

            $this->log("📄 Archivo cargado: " . count($rows) . " registros encontrados");

            // Preparar INSERT/UPDATE
            $sql = "INSERT INTO proveedoras
                    (cedula, nombre_proveedor, tipo_proveedor, tamano_proveedor,
                     codigo_postal, provincia, canton, distrito)
                    VALUES
                    (:cedula, :nombre, :tipo, :tamano,
                     :codigo_postal, :provincia, :canton, :distrito)
                    ON DUPLICATE KEY UPDATE
                    nombre_proveedor = VALUES(nombre_proveedor),
                    tipo_proveedor = VALUES(tipo_proveedor),
                    tamano_proveedor = VALUES(tamano_proveedor),
                    codigo_postal = VALUES(codigo_postal),
                    provincia = VALUES(provincia),
                    canton = VALUES(canton),
                    distrito = VALUES(distrito),
                    actualizado = CURRENT_TIMESTAMP";

            $stmt = $this->conn->prepare($sql);

            foreach ($rows as $i => $row) {
                $this->stats['total']++;

                // Validar cédula
                if (empty(trim($row[0] ?? ''))) {
                    $this->stats['errores']++;
                    continue;
                }

                // Mapear campos
                $cedula = $this->limpiar($row[0] ?? '');
                $nombre = $this->limpiar($row[1] ?? '');
                $tipo = $this->limpiar($row[2] ?? '');
                $tamano = $this->limpiar($row[3] ?? '');
                $codigo_postal = $this->limpiar($row[4] ?? '');
                $provincia = $this->limpiar($row[5] ?? '');
                $canton = $this->limpiar($row[6] ?? '');
                $distrito = $this->limpiar($row[7] ?? '');

                try {
                    // Verificar si existe
                    $check = $this->conn->prepare("SELECT COUNT(*) FROM proveedoras WHERE cedula = :cedula");
                    $check->bindParam(':cedula', $cedula);
                    $check->execute();
                    $existe = $check->fetchColumn() > 0;

                    // Ejecutar INSERT/UPDATE
                    $stmt->bindParam(':cedula', $cedula);
                    $stmt->bindParam(':nombre', $nombre);
                    $stmt->bindParam(':tipo', $tipo);
                    $stmt->bindParam(':tamano', $tamano);
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
                    $this->log("⚠️ Error en fila " . ($i + 2) . ": " . $e->getMessage());
                }

                // Log cada 500
                if (($i + 1) % 500 == 0) {
                    $this->log("⏳ Procesadas " . ($i + 1) . " proveedoras...");
                }
            }

            $this->log("✅ Importación completada!");

        } catch (Exception $e) {
            $this->log("❌ Error fatal: " . $e->getMessage());
            $this->stats['errores']++;
        }
    }

    public function getStats() { return $this->stats; }
    public function getLog() { return $this->log; }
}

// ============================================================================
// PROCESAMIENTO
// ============================================================================

$resultado = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    $imp = new ImportadorProveedoras($conn);
    $archivo = $_FILES['archivo'];

    if ($archivo['error'] == 0) {
        $imp->importar($archivo['tmp_name']);
        $resultado = [
            'stats' => $imp->getStats(),
            'log' => $imp->getLog()
        ];
    } else {
        $resultado = [
            'stats' => ['total' => 0, 'insertadas' => 0, 'actualizadas' => 0, 'errores' => 1],
            'log' => ['❌ Error al subir archivo']
        ];
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Proveedoras</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .header p { opacity: 0.9; font-size: 14px; }
        .content { padding: 30px; }
        .upload-form {
            background: #f8f9fa;
            border: 2px dashed #f5576c;
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
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }
        .upload-form button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(245, 87, 108, 0.4);
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
        }
        .log-container {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            max-height: 400px;
            overflow-y: auto;
        }
        .log-container h3 { margin-bottom: 15px; color: #495057; }
        .log-item {
            padding: 8px 12px;
            background: white;
            margin-bottom: 8px;
            border-radius: 4px;
            font-size: 13px;
            border-left: 3px solid #f5576c;
        }
        .info-box {
            background: #ffe7f0;
            border-left: 4px solid #f5576c;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .btn-volver {
            display: inline-block;
            padding: 12px 30px;
            background: #f5576c;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏭 Importador de Proveedoras</h1>
            <p>Empresas que ofertan en licitaciones</p>
        </div>

        <div class="content">
            <?php if (!$resultado): ?>
                <div class="info-box">
                    <strong>📋 Formato del CSV:</strong><br>
                    Cédula Proveedor | Nombre Proveedor | Tipo Proveedor | Tamaño Proveedor | Codigo Postal | Provincia | Canton | Distrito
                </div>

                <form method="POST" enctype="multipart/form-data" class="upload-form">
                    <h3>📁 Selecciona el archivo CSV</h3>
                    <input type="file" name="archivo" accept=".csv" required>
                    <br>
                    <button type="submit">🚀 INICIAR IMPORTACIÓN</button>
                </form>

            <?php else: ?>
                <div class="stats-grid">
                    <div class="stat-card total">
                        <div class="numero"><?php echo number_format($resultado['stats']['total']); ?></div>
                        <div class="label">Total</div>
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

                <div style="text-align: center;">
                    <a href="importar_proveedoras.php" class="btn-volver">← Importar otro archivo</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
