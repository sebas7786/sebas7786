<?php
/**
 * ═══════════════════════════════════════════════════════════════════════
 * DIAGNÓSTICO DE ARCHIVO CSV - Ver estructura real
 * ═══════════════════════════════════════════════════════════════════════
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['archivo'])) {
    $archivo = $_FILES['archivo']['tmp_name'];

    // Detectar delimitador
    $handle = fopen($archivo, 'r');
    $primera_linea = fgets($handle);
    fclose($handle);

    $delim = substr_count($primera_linea, ';') > substr_count($primera_linea, ',') ? ';' : ',';

    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Diagnóstico CSV</title>";
    echo "<style>
    body { font-family: monospace; padding: 20px; background: #f5f5f5; }
    .info { background: #e3f2fd; padding: 15px; margin: 10px 0; border-radius: 4px; }
    .row { background: #fff; padding: 10px; margin: 5px 0; border-left: 3px solid #2196f3; }
    .header { background: #fff3cd; border-left-color: #ffc107; font-weight: bold; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    td { padding: 8px; border: 1px solid #ddd; }
    .num { background: #f0f0f0; font-weight: bold; width: 50px; }
    </style></head><body>";

    echo "<h1>🔍 Diagnóstico de Archivo CSV</h1>";

    echo "<div class='info'>";
    echo "<strong>Delimitador detectado:</strong> " . ($delim == ';' ? 'Punto y coma (;)' : 'Coma (,)') . "<br>";
    echo "<strong>Primera línea:</strong><br><pre>" . htmlspecialchars($primera_linea) . "</pre>";
    echo "</div>";

    // Leer primeras 50 filas
    $handle = fopen($archivo, 'r');
    $fila_num = 0;
    $max_filas = 50;

    echo "<h2>📋 Primeras $max_filas Filas del Archivo</h2>";

    while (($row = fgetcsv($handle, 0, $delim)) !== false && $fila_num < $max_filas) {
        $fila_num++;

        // Limpiar BOM
        $row = array_map(function($val) {
            return trim(str_replace("\xEF\xBB\xBF", '', $val));
        }, $row);

        $num_cols = count($row);
        $primera_col = isset($row[0]) ? strtolower(trim($row[0])) : '';
        $segunda_col = isset($row[1]) ? strtolower(trim($row[1])) : '';

        // Detectar tipo
        $tipo = 'datos';
        $es_header = false;

        if (strpos($primera_col, 'mero') !== false && strpos($primera_col, 'sicop') !== false) {
            $es_header = true;

            if (strpos($segunda_col, 'dula') !== false || strpos($segunda_col, 'instituci') !== false) {
                $tipo = '🏢 HEADER CARTEL';
            } elseif (strpos($segunda_col, 'linea') !== false || strpos($segunda_col, 'línea') !== false) {
                $tipo = '📊 HEADER LÍNEA';
            } else {
                $tipo = '❓ HEADER DESCONOCIDO';
            }
        } elseif ($num_cols >= 14 && $num_cols <= 16) {
            $tipo = '🏢 CARTEL';
        } elseif ($num_cols >= 7 && $num_cols <= 9) {
            $tipo = '📊 LÍNEA';
        }

        $clase = $es_header ? 'header' : 'row';

        echo "<div class='$clase'>";
        echo "<strong>Fila $fila_num:</strong> [$tipo] - <strong>$num_cols columnas</strong><br>";

        echo "<table><tr>";
        for ($i = 0; $i < $num_cols && $i < 15; $i++) {
            $valor = isset($row[$i]) ? htmlspecialchars($row[$i]) : '';
            if (strlen($valor) > 50) {
                $valor = substr($valor, 0, 50) . '...';
            }
            echo "<td class='num'>[$i]</td><td>$valor</td>";
            if (($i + 1) % 3 == 0) echo "</tr><tr>";
        }
        echo "</tr></table>";

        echo "</div>";
    }

    fclose($handle);

    echo "<div class='info'>";
    echo "<strong>Total de filas analizadas:</strong> $fila_num<br>";
    echo "<strong>Nota:</strong> Si no ves headers de LÍNEA (📊), el archivo no tiene esas filas o tienen formato diferente.";
    echo "</div>";

    echo "</body></html>";

} else {
    ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico CSV</title>
    <style>
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
            max-width: 600px;
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
        .header h1 { margin: 0; font-size: 32px; }
        .content { padding: 40px; }
        .upload-area {
            border: 3px dashed #667eea;
            border-radius: 12px;
            padding: 50px 30px;
            text-align: center;
            background: #f9fafb;
            cursor: pointer;
        }
        .upload-area:hover { background: #f3f4f6; }
        input[type="file"] { display: none; }
        .file-label {
            display: inline-block;
            padding: 12px 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }
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
        .btn-submit:disabled { opacity: 0.5; cursor: not-allowed; }
        .info {
            background: #dbeafe;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #3b82f6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Diagnóstico CSV</h1>
            <p>Analizar estructura del archivo</p>
        </div>

        <div class="content">
            <form method="POST" enctype="multipart/form-data">
                <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                    <h3>📁 Selecciona tu archivo CSV</h3>
                    <p style="color: #666; margin: 10px 0;">Detalle de Carteles</p>
                    <label for="fileInput" class="file-label">Examinar Archivo</label>
                    <input type="file" name="archivo" id="fileInput" accept=".csv" required>
                </div>

                <button type="submit" class="btn-submit" id="submitBtn" disabled>
                    🔍 ANALIZAR ARCHIVO
                </button>
            </form>

            <div class="info">
                <strong>📋 Este diagnóstico mostrará:</strong>
                <ul style="margin: 10px 0 0 20px;">
                    <li>Delimitador usado (coma o punto y coma)</li>
                    <li>Primeras 50 filas del archivo</li>
                    <li>Número de columnas por fila</li>
                    <li>Tipo detectado (Cartel o Línea)</li>
                    <li>Headers detectados</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('fileInput').addEventListener('change', function() {
            document.getElementById('submitBtn').disabled = this.files.length === 0;
        });
    </script>
</body>
</html>
    <?php
}
?>
