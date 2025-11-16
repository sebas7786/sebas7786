<?php
/**
 * ============================================================================
 * VERIFICADOR DE PROVEEDORAS
 * ============================================================================
 */

require_once __DIR__ . '/../config/db.php';

// Verificar tabla
$tabla_existe = false;
try {
    $check = $conn->query("SHOW TABLES LIKE 'proveedoras'");
    $tabla_existe = $check->rowCount() > 0;
} catch (PDOException $e) {
    $error = $e->getMessage();
}

$stats = ['total' => 0, 'con_nombre' => 0, 'con_tipo' => 0, 'con_tamano' => 0];
$recientes = [];
$por_tipo = [];
$por_tamano = [];
$por_provincia = [];

if ($tabla_existe) {
    try {
        // Stats
        $stmt = $conn->query("SELECT COUNT(*) as total FROM proveedoras");
        $stats['total'] = $stmt->fetch()['total'];

        $stmt = $conn->query("SELECT COUNT(*) as total FROM proveedoras WHERE nombre_proveedor IS NOT NULL AND nombre_proveedor != ''");
        $stats['con_nombre'] = $stmt->fetch()['total'];

        $stmt = $conn->query("SELECT COUNT(*) as total FROM proveedoras WHERE tipo_proveedor IS NOT NULL AND tipo_proveedor != ''");
        $stats['con_tipo'] = $stmt->fetch()['total'];

        $stmt = $conn->query("SELECT COUNT(*) as total FROM proveedoras WHERE tamano_proveedor IS NOT NULL AND tamano_proveedor != ''");
        $stats['con_tamano'] = $stmt->fetch()['total'];

        // Recientes
        $stmt = $conn->query("SELECT * FROM proveedoras ORDER BY fecha_importacion DESC LIMIT 20");
        $recientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Por tipo
        $stmt = $conn->query("
            SELECT tipo_proveedor, COUNT(*) as total
            FROM proveedoras
            WHERE tipo_proveedor IS NOT NULL AND tipo_proveedor != ''
            GROUP BY tipo_proveedor
            ORDER BY total DESC
        ");
        $por_tipo = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Por tamaño
        $stmt = $conn->query("
            SELECT tamano_proveedor, COUNT(*) as total
            FROM proveedoras
            WHERE tamano_proveedor IS NOT NULL AND tamano_proveedor != ''
            GROUP BY tamano_proveedor
            ORDER BY total DESC
        ");
        $por_tamano = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Por provincia
        $stmt = $conn->query("
            SELECT provincia, COUNT(*) as total
            FROM proveedoras
            WHERE provincia IS NOT NULL AND provincia != ''
            GROUP BY provincia
            ORDER BY total DESC
            LIMIT 10
        ");
        $por_provincia = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $error = $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Proveedoras</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .header h1 { font-size: 28px; }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid;
        }
        .stat-card.total { border-color: #f5576c; }
        .stat-card.nombre { border-color: #28a745; }
        .stat-card.tipo { border-color: #ffc107; }
        .stat-card.tamano { border-color: #17a2b8; }
        .stat-card .numero {
            font-size: 36px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
        }
        .stat-card .label {
            color: #6c757d;
            font-size: 14px;
            text-transform: uppercase;
        }
        .section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .section h2 {
            margin-bottom: 20px;
            color: #333;
            font-size: 20px;
            border-bottom: 2px solid #f5576c;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-size: 13px;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
        }
        table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
            font-size: 13px;
        }
        table tr:hover { background: #f8f9fa; }
        .error-box {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
        }
        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏭 Verificador de Proveedoras</h1>
            <p>Dashboard de proveedoras importadas</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-box">
                <strong>❌ Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!$tabla_existe): ?>
            <div class="error-box">
                <strong>⚠️ Atención:</strong> La tabla 'proveedoras' no existe.
                <br>Ejecuta: <code>sql/tabla_proveedoras.sql</code>
            </div>
        <?php else: ?>

            <!-- ESTADÍSTICAS -->
            <div class="stats-grid">
                <div class="stat-card total">
                    <div class="numero"><?php echo number_format($stats['total']); ?></div>
                    <div class="label">Total Proveedoras</div>
                </div>
                <div class="stat-card nombre">
                    <div class="numero"><?php echo number_format($stats['con_nombre']); ?></div>
                    <div class="label">Con Nombre</div>
                </div>
                <div class="stat-card tipo">
                    <div class="numero"><?php echo number_format($stats['con_tipo']); ?></div>
                    <div class="label">Con Tipo</div>
                </div>
                <div class="stat-card tamano">
                    <div class="numero"><?php echo number_format($stats['con_tamano']); ?></div>
                    <div class="label">Con Tamaño</div>
                </div>
            </div>

            <!-- POR TIPO Y TAMAÑO -->
            <div class="two-col">
                <?php if (!empty($por_tipo)): ?>
                <div class="section">
                    <h2>📊 Por Tipo de Proveedor</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th style="text-align: right;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($por_tipo as $t): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($t['tipo_proveedor']); ?></strong></td>
                                <td style="text-align: right;"><?php echo number_format($t['total']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <?php if (!empty($por_tamano)): ?>
                <div class="section">
                    <h2>📏 Por Tamaño</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Tamaño</th>
                                <th style="text-align: right;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($por_tamano as $t): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($t['tamano_proveedor']); ?></strong></td>
                                <td style="text-align: right;"><?php echo number_format($t['total']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- POR PROVINCIA -->
            <?php if (!empty($por_provincia)): ?>
            <div class="section">
                <h2>📍 Top 10 Provincias</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Provincia</th>
                            <th style="text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($por_provincia as $p): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($p['provincia']); ?></strong></td>
                            <td style="text-align: right;"><?php echo number_format($p['total']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- RECIENTES -->
            <?php if (!empty($recientes)): ?>
            <div class="section">
                <h2>🕒 Últimas 20 Proveedoras Importadas</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Cédula</th>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Tamaño</th>
                            <th>Provincia</th>
                            <th>Fecha Importación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recientes as $prov): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($prov['cedula']); ?></strong></td>
                            <td>
                                <?php
                                $nombre = $prov['nombre_proveedor'] ?? '-';
                                echo htmlspecialchars(mb_substr($nombre, 0, 40));
                                if (mb_strlen($nombre) > 40) echo '...';
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($prov['tipo_proveedor'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($prov['tamano_proveedor'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($prov['provincia'] ?? '-'); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($prov['fecha_importacion'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <div style="text-align: center;">
                <a href="importar_proveedoras.php" class="btn">📁 Ir al Importador</a>
            </div>

        <?php endif; ?>
    </div>
</body>
</html>
