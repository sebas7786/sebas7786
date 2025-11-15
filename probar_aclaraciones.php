<?php
/**
 * ═══════════════════════════════════════════════════════════════════════
 * SCRIPT DE PRUEBA: ACLARACIONES
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Verifica que las aclaraciones se hayan importado correctamente
 * y muestra estadísticas de la tabla
 */

require_once 'config/db.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba de Aclaraciones</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-label {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #333;
        }
        .stat-card.green .stat-value { color: #28a745; }
        .stat-card.blue .stat-value { color: #007bff; }
        .stat-card.orange .stat-value { color: #fd7e14; }
        .stat-card.red .stat-value { color: #dc3545; }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        th {
            background-color: #e6f0ff;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #b8daff;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #dee2e6;
        }
        tr:hover {
            background-color: #f8f9fa;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }
        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
        }
        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin: 30px 0 15px 0;
            color: #333;
        }
    </style>
</head>
<body>

<div class="header">
    <h1 style="margin: 0;">🔍 Verificación de Aclaraciones</h1>
    <p style="margin: 10px 0 0 0; opacity: 0.9;">Sistema de Importación SICOP</p>
</div>

<?php
// Verificar si existe la tabla
try {
    $check = $conn->query("SHOW TABLES LIKE 'aclaraciones_licitacion'");
    if ($check->rowCount() === 0) {
        echo '<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; border-left: 4px solid #dc3545;">';
        echo '<strong>❌ Error:</strong> La tabla "aclaraciones_licitacion" no existe. ';
        echo 'Ejecuta primero: <code>php importador_aclaraciones.php Aclaraciones.csv</code>';
        echo '</div>';
        exit;
    }
} catch (PDOException $e) {
    die("Error al verificar tabla: " . $e->getMessage());
}

// Estadísticas generales
try {
    $stmt = $conn->query("
        SELECT
            COUNT(*) as total_aclaraciones,
            COUNT(DISTINCT numero_cartel) as licitaciones_con_aclaraciones,
            COUNT(CASE WHEN fecha_respuesta IS NOT NULL THEN 1 END) as respondidas,
            COUNT(CASE WHEN fecha_respuesta IS NULL THEN 1 END) as pendientes,
            COUNT(DISTINCT solicitante) as total_solicitantes,
            COUNT(DISTINCT cedula_empresa_proveedora) as total_empresas
        FROM aclaraciones_licitacion
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($stats['total_aclaraciones'] == 0) {
        echo '<div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107;">';
        echo '<strong>⚠️ Advertencia:</strong> No hay aclaraciones importadas. ';
        echo 'Ejecuta: <code>php importador_aclaraciones.php Aclaraciones.csv</code>';
        echo '</div>';
        exit;
    }

    // Mostrar estadísticas
    ?>
    <div class="stats-grid">
        <div class="stat-card blue">
            <div class="stat-label">Total Aclaraciones</div>
            <div class="stat-value"><?php echo number_format($stats['total_aclaraciones']); ?></div>
        </div>
        <div class="stat-card green">
            <div class="stat-label">Respondidas</div>
            <div class="stat-value"><?php echo number_format($stats['respondidas']); ?></div>
        </div>
        <div class="stat-card orange">
            <div class="stat-label">Pendientes</div>
            <div class="stat-value"><?php echo number_format($stats['pendientes']); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Licitaciones</div>
            <div class="stat-value"><?php echo number_format($stats['licitaciones_con_aclaraciones']); ?></div>
        </div>
    </div>

    <div class="section-title">📋 Licitaciones con más aclaraciones</div>
    <?php
    // Top licitaciones con aclaraciones
    $stmt = $conn->query("
        SELECT
            numero_cartel,
            MAX(titulo) as titulo,
            COUNT(*) as total_aclaraciones,
            SUM(CASE WHEN fecha_respuesta IS NOT NULL THEN 1 ELSE 0 END) as respondidas,
            SUM(CASE WHEN fecha_respuesta IS NULL THEN 1 ELSE 0 END) as pendientes
        FROM aclaraciones_licitacion
        GROUP BY numero_cartel
        ORDER BY total_aclaraciones DESC
        LIMIT 10
    ");
    ?>
    <table>
        <thead>
            <tr>
                <th>Número SICOP</th>
                <th>Título</th>
                <th style="text-align: center;">Total</th>
                <th style="text-align: center;">Respondidas</th>
                <th style="text-align: center;">Pendientes</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($row['numero_cartel']); ?></strong></td>
                <td><?php echo htmlspecialchars(mb_substr($row['titulo'], 0, 80)) . (mb_strlen($row['titulo']) > 80 ? '...' : ''); ?></td>
                <td style="text-align: center;"><strong><?php echo $row['total_aclaraciones']; ?></strong></td>
                <td style="text-align: center; color: #28a745;"><?php echo $row['respondidas']; ?></td>
                <td style="text-align: center; color: #dc3545;"><?php echo $row['pendientes']; ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="section-title">📝 Aclaraciones recientes</div>
    <?php
    // Aclaraciones más recientes
    $stmt = $conn->query("
        SELECT *
        FROM aclaraciones_licitacion
        ORDER BY fecha_solicitud DESC
        LIMIT 20
    ");
    ?>
    <table>
        <thead>
            <tr>
                <th>N° Cartel</th>
                <th>Fecha Solicitud</th>
                <th>N° Aclaración</th>
                <th>Solicitante</th>
                <th>Estado</th>
                <th>Fecha Respuesta</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($row['numero_cartel']); ?></strong></td>
                <td>
                    <?php
                    if (!empty($row['fecha_solicitud'])) {
                        $dt = new DateTime($row['fecha_solicitud']);
                        echo $dt->format('d/m/Y H:i');
                    } else {
                        echo '-';
                    }
                    ?>
                </td>
                <td><?php echo htmlspecialchars($row['numero_aclaracion'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars(mb_substr($row['solicitante'] ?? '-', 0, 30)); ?></td>
                <td>
                    <?php
                    if (!empty($row['fecha_respuesta'])) {
                        echo '<span class="badge badge-success">✓ Respondida</span>';
                    } else {
                        echo '<span class="badge badge-warning">⏳ Pendiente</span>';
                    }
                    ?>
                </td>
                <td>
                    <?php
                    if (!empty($row['fecha_respuesta'])) {
                        $dt = new DateTime($row['fecha_respuesta']);
                        echo '<strong style="color: #28a745;">' . $dt->format('d/m/Y H:i') . '</strong>';
                    } else {
                        echo '<span style="color: #999;">-</span>';
                    }
                    ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="section-title">🏢 Top empresas solicitantes</div>
    <?php
    // Top empresas solicitantes
    $stmt = $conn->query("
        SELECT
            cedula_empresa_proveedora,
            solicitante,
            COUNT(*) as total_solicitudes,
            SUM(CASE WHEN fecha_respuesta IS NOT NULL THEN 1 ELSE 0 END) as respondidas
        FROM aclaraciones_licitacion
        WHERE cedula_empresa_proveedora IS NOT NULL
        GROUP BY cedula_empresa_proveedora, solicitante
        ORDER BY total_solicitudes DESC
        LIMIT 10
    ");
    ?>
    <table>
        <thead>
            <tr>
                <th>Cédula Empresa</th>
                <th>Solicitante</th>
                <th style="text-align: center;">Total Solicitudes</th>
                <th style="text-align: center;">Respondidas</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($row['cedula_empresa_proveedora']); ?></strong></td>
                <td><?php echo htmlspecialchars($row['solicitante'] ?? '-'); ?></td>
                <td style="text-align: center;"><strong><?php echo $row['total_solicitudes']; ?></strong></td>
                <td style="text-align: center; color: #28a745;"><?php echo $row['respondidas']; ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <?php
} catch (PDOException $e) {
    echo '<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-top: 20px;">';
    echo '<strong>❌ Error:</strong> ' . htmlspecialchars($e->getMessage());
    echo '</div>';
}
?>

<div style="background: white; padding: 15px; border-radius: 8px; margin-top: 30px; border-left: 4px solid #28a745;">
    <strong>✅ Sistema de aclaraciones funcionando correctamente</strong><br>
    <small style="color: #666;">
        Para ver aclaraciones en una licitación, visita:
        <code>licitacion_detalle.php?id=[ID]</code>
    </small>
</div>

</body>
</html>
