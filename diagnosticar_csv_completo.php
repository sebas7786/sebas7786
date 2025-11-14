<?php
/**
 * ═══════════════════════════════════════════════════════════════════════
 * DIAGNÓSTICO COMPLETO - Ver inicio, fin y estadísticas
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

    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Diagnóstico Completo</title>";
    echo "<style>
    body { font-family: monospace; padding: 20px; background: #f5f5f5; font-size: 12px; }
    .info { background: #e3f2fd; padding: 15px; margin: 10px 0; border-radius: 4px; }
    .stats { background: #c8e6c9; padding: 15px; margin: 10px 0; border-radius: 4px; }
    .row { background: #fff; padding: 8px; margin: 3px 0; border-left: 3px solid #2196f3; font-size: 11px; }
    .header { background: #fff3cd; border-left-color: #ffc107; font-weight: bold; }
    .linea { background: #e8f5e9; border-left-color: #4caf50; }
    table { width: 100%; border-collapse: collapse; margin: 5px 0; font-size: 10px; }
    td { padding: 4px; border: 1px solid #ddd; word-break: break-all; }
    .num { background: #f0f0f0; font-weight: bold; width: 40px; }
    h2 { background: #1976d2; color: white; padding: 10px; margin: 20px 0 10px 0; }
    </style></head><body>";

    echo "<h1>🔍 Diagnóstico Completo CSV</h1>";

    echo "<div class='info'>";
    echo "<strong>Delimitador:</strong> " . ($delim == ';' ? 'Punto y coma (;)' : 'Coma (,)') . "<br>";
    echo "</div>";

    // LEER TODO EL ARCHIVO
    $handle = fopen($archivo, 'r');
    $todas_las_filas = [];

    while (($row = fgetcsv($handle, 0, $delim)) !== false) {
        $row = array_map(function($val) {
            return trim(str_replace("\xEF\xBB\xBF", '', $val));
        }, $row);
        $todas_las_filas[] = $row;
    }
    fclose($handle);

    $total_filas = count($todas_las_filas);

    // ESTADÍSTICAS
    $stats = [
        'total' => $total_filas,
        'header_cartel' => 0,
        'header_linea' => 0,
        'carteles' => 0,
        'lineas' => 0,
        'desconocidos' => 0
    ];

    foreach ($todas_las_filas as $row) {
        $num_cols = count($row);
        $primera_col = isset($row[0]) ? strtolower(trim($row[0])) : '';
        $segunda_col = isset($row[1]) ? strtolower(trim($row[1])) : '';

        if (strpos($primera_col, 'mero') !== false && strpos($primera_col, 'sicop') !== false) {
            if (strpos($segunda_col, 'dula') !== false || strpos($segunda_col, 'instituci') !== false) {
                $stats['header_cartel']++;
            } elseif (strpos($segunda_col, 'linea') !== false || strpos($segunda_col, 'línea') !== false) {
                $stats['header_linea']++;
            }
        } elseif ($num_cols >= 14 && $num_cols <= 16) {
            $stats['carteles']++;
        } elseif ($num_cols >= 6 && $num_cols <= 10) {
            $stats['lineas']++;
        } else {
            $stats['desconocidos']++;
        }
    }

    echo "<div class='stats'>";
    echo "<h3>📊 ESTADÍSTICAS DEL ARCHIVO</h3>";
    echo "<strong>Total de filas:</strong> {$stats['total']}<br>";
    echo "<strong>Headers de CARTEL:</strong> {$stats['header_cartel']}<br>";
    echo "<strong>Headers de LÍNEA:</strong> {$stats['header_linea']}<br>";
    echo "<strong>Carteles detectados:</strong> {$stats['carteles']}<br>";
    echo "<strong>Líneas detectadas:</strong> {$stats['lineas']}<br>";
    echo "<strong>Desconocidos:</strong> {$stats['desconocidos']}<br>";
    echo "</div>";

    // MOSTRAR PRIMERAS 15 FILAS
    echo "<h2>📋 PRIMERAS 15 FILAS</h2>";
    mostrarFilas(array_slice($todas_las_filas, 0, 15), 0);

    // MOSTRAR ÚLTIMAS 30 FILAS
    echo "<h2>📋 ÚLTIMAS 30 FILAS (Donde deberían estar las LÍNEAS)</h2>";
    $inicio_ultimas = max(0, $total_filas - 30);
    mostrarFilas(array_slice($todas_las_filas, $inicio_ultimas), $inicio_ultimas);

    // BUSCAR PRIMERA LÍNEA
    $primera_linea_idx = -1;
    foreach ($todas_las_filas as $idx => $row) {
        $num_cols = count($row);
        if ($num_cols >= 6 && $num_cols <= 10) {
            $primera_col = isset($row[0]) ? strtolower(trim($row[0])) : '';
            if (strpos($primera_col, 'mero') === false) {
                $primera_linea_idx = $idx;
                break;
            }
        }
    }

    if ($primera_linea_idx >= 0) {
        echo "<h2>📋 CONTEXTO DE LA PRIMERA LÍNEA (Fila $primera_linea_idx)</h2>";
        $inicio_contexto = max(0, $primera_linea_idx - 5);
        $fin_contexto = min($total_filas, $primera_linea_idx + 10);
        mostrarFilas(array_slice($todas_las_filas, $inicio_contexto, $fin_contexto - $inicio_contexto), $inicio_contexto);
    }

    echo "</body></html>";

} else {
    ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico Completo CSV</title>
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Diagnóstico Completo</h1>
            <p>Analizar TODO el archivo CSV</p>
        </div>

        <div class="content">
            <form method="POST" enctype="multipart/form-data">
                <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                    <h3>📁 Selecciona el archivo CSV</h3>
                    <label for="fileInput" class="file-label">Examinar Archivo</label>
                    <input type="file" name="archivo" id="fileInput" accept=".csv" required>
                </div>

                <button type="submit" class="btn-submit" id="submitBtn" disabled>
                    🔍 ANALIZAR COMPLETO
                </button>
            </form>

            <div style="background: #dbeafe; padding: 15px; border-radius: 8px; margin: 15px 0;">
                <strong>📋 Este diagnóstico mostrará:</strong>
                <ul style="margin: 10px 0 0 20px;">
                    <li>Total de filas del archivo</li>
                    <li>Cuántos carteles y líneas hay</li>
                    <li>Primeras 15 filas</li>
                    <li>Últimas 30 filas (donde están las líneas)</li>
                    <li>Contexto de la primera línea encontrada</li>
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

function mostrarFilas($filas, $offset) {
    foreach ($filas as $idx => $row) {
        $fila_num = $offset + $idx + 1;
        $num_cols = count($row);
        $primera_col = isset($row[0]) ? strtolower(trim($row[0])) : '';
        $segunda_col = isset($row[1]) ? strtolower(trim($row[1])) : '';

        // Detectar tipo
        $tipo = 'datos';
        $clase = 'row';
        $es_header = false;

        if (strpos($primera_col, 'mero') !== false && strpos($primera_col, 'sicop') !== false) {
            $es_header = true;

            if (strpos($segunda_col, 'dula') !== false || strpos($segunda_col, 'instituci') !== false) {
                $tipo = '🏢 HEADER CARTEL';
                $clase = 'header';
            } elseif (strpos($segunda_col, 'linea') !== false || strpos($segunda_col, 'línea') !== false) {
                $tipo = '📊 HEADER LÍNEA';
                $clase = 'header';
            } else {
                $tipo = '❓ HEADER DESCONOCIDO';
                $clase = 'header';
            }
        } elseif ($num_cols >= 14 && $num_cols <= 16) {
            $tipo = '🏢 CARTEL';
        } elseif ($num_cols >= 6 && $num_cols <= 10) {
            $tipo = '📊 LÍNEA';
            $clase = 'linea';
        }

        echo "<div class='$clase'>";
        echo "<strong>Fila $fila_num:</strong> [$tipo] - <strong>$num_cols columnas</strong><br>";

        if ($es_header || $tipo == '📊 LÍNEA') {
            echo "<strong>Primera col:</strong> " . htmlspecialchars($primera_col) . "<br>";
            echo "<strong>Segunda col:</strong> " . htmlspecialchars($segunda_col) . "<br>";
        }

        echo "<table><tr>";
        for ($i = 0; $i < $num_cols && $i < 12; $i++) {
            $valor = isset($row[$i]) ? htmlspecialchars($row[$i]) : '';
            if (strlen($valor) > 40) {
                $valor = substr($valor, 0, 40) . '...';
            }
            echo "<td class='num'>[$i]</td><td>$valor</td>";
            if (($i + 1) % 2 == 0) echo "</tr><tr>";
        }
        echo "</tr></table>";

        echo "</div>";
    }
}
?>
