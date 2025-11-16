<?php
/**
 * ═══════════════════════════════════════════════════════════════════════
 * VERIFICADOR DE LINEAS CONTRATADAS
 * ═══════════════════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/../config/db.php';

// Obtener estadísticas
$total = $conn->query("SELECT COUNT(*) FROM lineas_contratadas")->fetchColumn();

$por_moneda = $conn->query("
    SELECT tipo_moneda, COUNT(*) as total
    FROM lineas_contratadas
    GROUP BY tipo_moneda
    ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);

$top_proveedores = $conn->query("
    SELECT
        lc.cedula_proveedor,
        p.nombre_proveedor,
        COUNT(*) as total_lineas,
        SUM(lc.cantidad_contratada * lc.precio_unitario) as monto_total
    FROM lineas_contratadas lc
    LEFT JOIN proveedoras p ON lc.cedula_proveedor = p.cedula
    GROUP BY lc.cedula_proveedor
    ORDER BY total_lineas DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

$por_licitacion = $conn->query("
    SELECT
        lc.numero_sicop,
        l.titulo,
        COUNT(*) as lineas_adjudicadas,
        SUM(lc.cantidad_contratada * lc.precio_unitario) as monto_total
    FROM lineas_contratadas lc
    LEFT JOIN licitaciones l ON lc.numero_sicop = l.numero_sicop
    GROUP BY lc.numero_sicop
    ORDER BY lineas_adjudicadas DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

$stats_generales = $conn->query("
    SELECT
        COUNT(DISTINCT numero_sicop) as licitaciones_con_adjudicaciones,
        COUNT(DISTINCT cedula_proveedor) as proveedores_adjudicados,
        SUM(cantidad_contratada) as cantidad_total,
        AVG(precio_unitario) as precio_promedio
    FROM lineas_contratadas
")->fetch(PDO::FETCH_ASSOC);

// Últimas 20 líneas importadas
$ultimas = $conn->query("
    SELECT
        lc.*,
        l.titulo as titulo_licitacion,
        p.nombre_proveedor
    FROM lineas_contratadas lc
    LEFT JOIN licitaciones l ON lc.numero_sicop = l.numero_sicop
    LEFT JOIN proveedoras p ON lc.cedula_proveedor = p.cedula
    ORDER BY lc.fecha_importacion DESC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificar Líneas Contratadas</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, system-ui;
            background: #f3f4f6;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .header {
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            color: #fff;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);
        }
        .header h1 { font-size: 32px; margin-bottom: 8px; }
        .header .subtitle { opacity: 0.95; font-size: 14px; }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            padding: 25px;
            border-radius: 10px;
            color: #fff;
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.2);
        }
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 13px;
            opacity: 0.95;
        }
        .section {
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .section h2 {
            font-size: 20px;
            margin-bottom: 20px;
            color: #333;
            border-bottom: 2px solid #8b5cf6;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #f9fafb;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e5e7eb;
            font-size: 13px;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
        }
        tr:hover { background: #f9fafb; }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-primary { background: #ddd6fe; color: #5b21b6; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 768px) {
            .grid-2 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Líneas Contratadas - Estadísticas</h1>
            <p class="subtitle">Visualización de adjudicaciones SICOP</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= number_format($total) ?></div>
                <div class="stat-label">Total Líneas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= number_format($stats_generales['licitaciones_con_adjudicaciones']) ?></div>
                <div class="stat-label">Licitaciones con Adjudicaciones</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= number_format($stats_generales['proveedores_adjudicados']) ?></div>
                <div class="stat-label">Proveedores Adjudicados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">₡<?= number_format($stats_generales['precio_promedio'], 2) ?></div>
                <div class="stat-label">Precio Unitario Promedio</div>
            </div>
        </div>

        <div class="grid-2">
            <div class="section">
                <h2>📊 Por Moneda</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Moneda</th>
                            <th>Total Líneas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($por_moneda as $row): ?>
                        <tr>
                            <td><span class="badge badge-primary"><?= htmlspecialchars($row['tipo_moneda'] ?: 'N/A') ?></span></td>
                            <td><?= number_format($row['total']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="section">
                <h2>🏆 Top 10 Proveedores</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Proveedor</th>
                            <th>Líneas</th>
                            <th>Monto Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_proveedores as $row): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($row['nombre_proveedor'] ?: $row['cedula_proveedor']) ?>
                                <br><small style="color: #999;"><?= htmlspecialchars($row['cedula_proveedor']) ?></small>
                            </td>
                            <td><?= number_format($row['total_lineas']) ?></td>
                            <td>₡<?= number_format($row['monto_total'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="section">
            <h2>📌 Top 10 Licitaciones con Más Adjudicaciones</h2>
            <table>
                <thead>
                    <tr>
                        <th>SICOP</th>
                        <th>Título</th>
                        <th>Líneas Adjudicadas</th>
                        <th>Monto Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($por_licitacion as $row): ?>
                    <tr>
                        <td><span class="badge badge-success"><?= htmlspecialchars($row['numero_sicop']) ?></span></td>
                        <td><?= htmlspecialchars(substr($row['titulo'] ?? 'Sin título', 0, 80)) ?></td>
                        <td><?= number_format($row['lineas_adjudicadas']) ?></td>
                        <td>₡<?= number_format($row['monto_total'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>🕒 Últimas 20 Líneas Importadas</h2>
            <table>
                <thead>
                    <tr>
                        <th>SICOP</th>
                        <th>Línea</th>
                        <th>Proveedor</th>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio Unit.</th>
                        <th>Moneda</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ultimas as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['numero_sicop']) ?></td>
                        <td><?= htmlspecialchars($row['numero_linea_cartel']) ?></td>
                        <td>
                            <?= htmlspecialchars($row['nombre_proveedor'] ?: $row['cedula_proveedor']) ?>
                        </td>
                        <td><?= htmlspecialchars(substr($row['descripcion_producto'] ?? 'N/A', 0, 40)) ?></td>
                        <td><?= number_format($row['cantidad_contratada'], 2) ?></td>
                        <td>₡<?= number_format($row['precio_unitario'], 2) ?></td>
                        <td><span class="badge badge-primary"><?= htmlspecialchars($row['tipo_moneda']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="importar_lineas_contratadas.php" class="btn">🔄 Nueva Importación</a>
        </div>
    </div>
</body>
</html>
