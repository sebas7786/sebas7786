<?php
/**
 * ═══════════════════════════════════════════════════════════════════════
 * IMPORTADOR DE LÍNEAS CONTRATADAS (ADJUDICADAS)
 * ═══════════════════════════════════════════════════════════════════════
 */

ini_set('max_execution_time', 600);
ini_set('memory_limit', '512M');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/db.php';

// Funciones helper
function obtenerValor($row, $indices, $columna, $default = null) {
    if (isset($indices[$columna]) && isset($row[$indices[$columna]])) {
        $val = trim($row[$indices[$columna]]);
        return $val !== '' ? $val : $default;
    }
    return $default;
}

function limpiarNumero($num) {
    if (!$num || trim($num) == '') return null;
    $num = trim($num);
    $num = preg_replace('/[^\d.,\-]/', '', $num);
    $num = str_replace(',', '.', $num);
    return is_numeric($num) ? (float)$num : null;
}

$stats = [
    'insertadas' => 0,
    'actualizadas' => 0,
    'errores' => 0,
    'total' => 0
];

$resultado = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['archivo'])) {
    $archivo = $_FILES['archivo'];

    if ($archivo['error'] == 0) {
        try {
            // Detectar delimitador
            $handle = fopen($archivo['tmp_name'], 'r');
            $primera = fgets($handle);
            $delim = substr_count($primera, ';') > substr_count($primera, ',') ? ';' : ',';
            fclose($handle);

            // Leer CSV
            $handle = fopen($archivo['tmp_name'], 'r');
            $header = fgetcsv($handle, 0, $delim);

            // Limpiar BOM
            $header = array_map(function($col) {
                return trim(str_replace("\xEF\xBB\xBF", '', $col));
            }, $header);

            // Índice de columnas
            $indices = [];
            foreach ($header as $idx => $col) {
                $indices[$col] = $idx;
            }

            // SQL INSERT
            $sql = "INSERT INTO lineas_contratadas (
                numero_sicop, numero_linea_contrato, numero_linea_cartel,
                numero_contrato, secuencia, cedula_proveedor, codigo_producto,
                cantidad_contratada, precio_unitario, tipo_moneda,
                descuento, iva, otros_impuestos, acarreos,
                tipo_cambio_crc, tipo_cambio_dolar, numero_acto,
                descripcion_producto, cantidad_aumentada, cantidad_disminuida,
                monto_aumentado, monto_disminuido
            ) VALUES (
                :numero_sicop, :numero_linea_contrato, :numero_linea_cartel,
                :numero_contrato, :secuencia, :cedula_proveedor, :codigo_producto,
                :cantidad_contratada, :precio_unitario, :tipo_moneda,
                :descuento, :iva, :otros_impuestos, :acarreos,
                :tipo_cambio_crc, :tipo_cambio_dolar, :numero_acto,
                :descripcion_producto, :cantidad_aumentada, :cantidad_disminuida,
                :monto_aumentado, :monto_disminuido
            ) ON DUPLICATE KEY UPDATE
                numero_linea_cartel = VALUES(numero_linea_cartel),
                numero_contrato = VALUES(numero_contrato),
                cedula_proveedor = VALUES(cedula_proveedor),
                codigo_producto = VALUES(codigo_producto),
                cantidad_contratada = VALUES(cantidad_contratada),
                precio_unitario = VALUES(precio_unitario),
                tipo_moneda = VALUES(tipo_moneda),
                descuento = VALUES(descuento),
                iva = VALUES(iva),
                otros_impuestos = VALUES(otros_impuestos),
                acarreos = VALUES(acarreos),
                tipo_cambio_crc = VALUES(tipo_cambio_crc),
                tipo_cambio_dolar = VALUES(tipo_cambio_dolar),
                numero_acto = VALUES(numero_acto),
                descripcion_producto = VALUES(descripcion_producto),
                cantidad_aumentada = VALUES(cantidad_aumentada),
                cantidad_disminuida = VALUES(cantidad_disminuida),
                monto_aumentado = VALUES(monto_aumentado),
                monto_disminuido = VALUES(monto_disminuido),
                actualizado = CURRENT_TIMESTAMP";

            $stmt = $conn->prepare($sql);

            // Procesar filas
            while (($row = fgetcsv($handle, 0, $delim)) !== false) {
                $stats['total']++;

                $numero_sicop = obtenerValor($row, $indices, 'NRO_SICOP');
                if (empty($numero_sicop)) {
                    $stats['errores']++;
                    continue;
                }

                try {
                    $ejecutado = $stmt->execute([
                        ':numero_sicop' => $numero_sicop,
                        ':numero_linea_contrato' => obtenerValor($row, $indices, 'NRO_LINEA_CONTRATO'),
                        ':numero_linea_cartel' => obtenerValor($row, $indices, 'NRO_LINEA_CARTEL'),
                        ':numero_contrato' => obtenerValor($row, $indices, 'NRO_CONTRATO'),
                        ':secuencia' => obtenerValor($row, $indices, 'SECUENCIA'),
                        ':cedula_proveedor' => obtenerValor($row, $indices, 'CEDULA_PROVEEDOR'),
                        ':codigo_producto' => obtenerValor($row, $indices, 'CODIGO_PRODUCTO'),
                        ':cantidad_contratada' => limpiarNumero(obtenerValor($row, $indices, 'CANTIDAD_CONTRATADA')),
                        ':precio_unitario' => limpiarNumero(obtenerValor($row, $indices, 'PRECIO_UNITARIO')),
                        ':tipo_moneda' => obtenerValor($row, $indices, 'TIPO_MONEDA', 'CRC'),
                        ':descuento' => limpiarNumero(obtenerValor($row, $indices, 'DESCUENTO')),
                        ':iva' => limpiarNumero(obtenerValor($row, $indices, 'IVA')),
                        ':otros_impuestos' => limpiarNumero(obtenerValor($row, $indices, 'OTROS_IMPUESTOS')),
                        ':acarreos' => limpiarNumero(obtenerValor($row, $indices, 'ACARREOS')),
                        ':tipo_cambio_crc' => limpiarNumero(obtenerValor($row, $indices, 'TIPO_CAMBIO_CRC')),
                        ':tipo_cambio_dolar' => limpiarNumero(obtenerValor($row, $indices, 'TIPO_CAMBIO_DOLAR')),
                        ':numero_acto' => obtenerValor($row, $indices, 'NRO_ACTO'),
                        ':descripcion_producto' => obtenerValor($row, $indices, 'DESC_PRODUCTO'),
                        ':cantidad_aumentada' => limpiarNumero(obtenerValor($row, $indices, 'cantidad_aumentada')),
                        ':cantidad_disminuida' => limpiarNumero(obtenerValor($row, $indices, 'cantidad_disminuida')),
                        ':monto_aumentado' => limpiarNumero(obtenerValor($row, $indices, 'monto_aumentado')),
                        ':monto_disminuido' => limpiarNumero(obtenerValor($row, $indices, 'monto_disminuido'))
                    ]);

                    if ($ejecutado) {
                        $stats['insertadas']++;
                    }

                } catch (PDOException $e) {
                    $stats['errores']++;
                }
            }

            fclose($handle);
            $resultado = 'success';

        } catch (Exception $e) {
            $resultado = 'error';
            $error_msg = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Líneas Contratadas</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            color: #fff;
            padding: 40px;
            text-align: center;
        }
        .header h1 { font-size: 32px; margin-bottom: 8px; }
        .subtitle { font-size: 14px; opacity: 0.95; }
        .content { padding: 40px; }

        .upload-area {
            border: 3px dashed #8b5cf6;
            border-radius: 12px;
            padding: 50px 30px;
            text-align: center;
            background: #f9fafb;
            margin: 20px 0;
            cursor: pointer;
            transition: all 0.3s;
        }
        .upload-area:hover { border-color: #6366f1; background: #f3f4f6; }
        .upload-area h3 { font-size: 20px; color: #333; margin-bottom: 12px; }

        input[type="file"] { display: none; }

        .file-label {
            display: inline-block;
            padding: 12px 32px;
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            color: #fff;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: opacity 0.3s;
        }
        .btn-submit:hover { opacity: 0.9; }
        .btn-submit:disabled { opacity: 0.5; cursor: not-allowed; }

        .result {
            background: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 25px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .result.error {
            background: #fee2e2;
            border-left-color: #ef4444;
        }
        .result h3 { margin-bottom: 15px; color: #059669; font-size: 20px; }
        .result.error h3 { color: #dc2626; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #8b5cf6;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 13px;
            color: #666;
        }

        .info-box {
            background: #e0e7ff;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #6366f1;
            margin: 20px 0;
            font-size: 14px;
            line-height: 1.6;
        }
        .info-box strong { color: #4338ca; }

        .btn-link {
            display: inline-block;
            padding: 12px 24px;
            background: #f3f4f6;
            color: #333;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px 5px 0 0;
            font-weight: 600;
            transition: background 0.3s;
        }
        .btn-link:hover { background: #e5e7eb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Líneas Contratadas</h1>
            <p class="subtitle">Importador de líneas adjudicadas SICOP</p>
        </div>

        <div class="content">
            <?php if ($resultado == 'success'): ?>
                <div class="result">
                    <h3>✅ Importación Completada</h3>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number"><?= number_format($stats['total']) ?></div>
                            <div class="stat-label">Procesadas</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?= number_format($stats['insertadas']) ?></div>
                            <div class="stat-label">Insertadas</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?= number_format($stats['errores']) ?></div>
                            <div class="stat-label">Errores</div>
                        </div>
                    </div>
                    <div style="margin-top: 25px;">
                        <a href="importar_lineas_contratadas.php" class="btn-link">🔄 Nueva Importación</a>
                        <a href="verificar_lineas_contratadas.php" class="btn-link">📊 Ver Estadísticas</a>
                    </div>
                </div>

            <?php elseif ($resultado == 'error'): ?>
                <div class="result error">
                    <h3>❌ Error en la Importación</h3>
                    <p><?= isset($error_msg) ? htmlspecialchars($error_msg) : 'Error desconocido' ?></p>
                    <div style="margin-top: 20px;">
                        <a href="importar_lineas_contratadas.php" class="btn-link">🔄 Intentar de Nuevo</a>
                    </div>
                </div>

            <?php else: ?>
                <div class="info-box">
                    <strong>📋 Campos del CSV LineasContratadas:</strong><br>
                    NRO_SICOP, NRO_LINEA_CONTRATO, NRO_LINEA_CARTEL, NRO_CONTRATO, SECUENCIA,
                    CEDULA_PROVEEDOR, CODIGO_PRODUCTO, CANTIDAD_CONTRATADA, PRECIO_UNITARIO,
                    TIPO_MONEDA, DESCUENTO, IVA, OTROS_IMPUESTOS, ACARREOS, TIPO_CAMBIO_CRC,
                    TIPO_CAMBIO_DOLAR, NRO_ACTO, DESC_PRODUCTO, cantidad_aumentada,
                    cantidad_disminuida, monto_aumentado, monto_disminuido
                </div>

                <form method="POST" enctype="multipart/form-data" id="uploadForm">
                    <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                        <h3>📁 Selecciona el archivo CSV</h3>
                        <p style="color: #666; margin: 10px 0;">LineasContratadas.csv</p>
                        <label for="fileInput" class="file-label">Examinar Archivo</label>
                        <input type="file" name="archivo" id="fileInput" accept=".csv" required>
                    </div>

                    <button type="submit" class="btn-submit" id="submitBtn" disabled>
                        🚀 INICIAR IMPORTACIÓN
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.getElementById('fileInput').addEventListener('change', function() {
            const btn = document.getElementById('submitBtn');
            if (this.files.length > 0) {
                btn.disabled = false;
                btn.textContent = '🚀 INICIAR IMPORTACIÓN';
            } else {
                btn.disabled = true;
            }
        });
    </script>
</body>
</html>
