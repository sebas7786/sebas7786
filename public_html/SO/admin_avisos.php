<?php
require_once 'config.php';

// Parámetros de búsqueda y filtrado
$busqueda = $_GET['busqueda'] ?? '';
$estado_filtro = $_GET['estado'] ?? '';
$fecha_desde = $_GET['fecha_desde'] ?? '';
$fecha_hasta = $_GET['fecha_hasta'] ?? '';

// Construir query
$sql = "SELECT * FROM avisos_accidentes WHERE 1=1";
$params = [];

if (!empty($busqueda)) {
    $sql .= " AND (nombre_completo LIKE :busqueda OR cedula LIKE :busqueda OR departamento LIKE :busqueda)";
    $params[':busqueda'] = "%{$busqueda}%";
}

if (!empty($estado_filtro)) {
    $sql .= " AND estado = :estado";
    $params[':estado'] = $estado_filtro;
}

if (!empty($fecha_desde)) {
    $sql .= " AND fecha_incidente >= :fecha_desde";
    $params[':fecha_desde'] = $fecha_desde;
}

if (!empty($fecha_hasta)) {
    $sql .= " AND fecha_incidente <= :fecha_hasta";
    $params[':fecha_hasta'] = $fecha_hasta;
}

$sql .= " ORDER BY fecha_registro DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$avisos = $stmt->fetchAll();

// Estadísticas
$sql_stats = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
    SUM(CASE WHEN estado = 'en_revision' THEN 1 ELSE 0 END) as en_revision,
    SUM(CASE WHEN estado = 'cerrado' THEN 1 ELSE 0 END) as cerrados
FROM avisos_accidentes";
$stmt_stats = $pdo->query($sql_stats);
$stats = $stmt_stats->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de Avisos - Salud Ocupacional</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 28px;
        }

        .btn {
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            transition: transform 0.2s;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .stat-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }

        .stat-card .numero {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
        }

        .stat-card.pendientes .numero {
            color: #f39c12;
        }

        .stat-card.revision .numero {
            color: #3498db;
        }

        .stat-card.cerrados .numero {
            color: #27ae60;
        }

        .filtros {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .filtros h3 {
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .filtros-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 5px;
            font-size: 14px;
            color: #555;
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .tabla-container {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #dee2e6;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .badge-pendiente {
            background: #fff3cd;
            color: #856404;
        }

        .badge-revision {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-cerrado {
            background: #d4edda;
            color: #155724;
        }

        .acciones {
            display: flex;
            gap: 10px;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }

        .btn-ver {
            background: #3498db;
            color: white;
        }

        .btn-editar {
            background: #f39c12;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state svg {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
            }

            .stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Administración de Avisos de Accidentes</h1>
            <a href="nuevo_aviso.php" class="btn btn-primary">+ Nuevo Aviso</a>
        </div>

        <!-- Estadísticas -->
        <div class="stats">
            <div class="stat-card">
                <h3>Total de Avisos</h3>
                <div class="numero"><?php echo $stats['total']; ?></div>
            </div>
            <div class="stat-card pendientes">
                <h3>Pendientes</h3>
                <div class="numero"><?php echo $stats['pendientes']; ?></div>
            </div>
            <div class="stat-card revision">
                <h3>En Revisión</h3>
                <div class="numero"><?php echo $stats['en_revision']; ?></div>
            </div>
            <div class="stat-card cerrados">
                <h3>Cerrados</h3>
                <div class="numero"><?php echo $stats['cerrados']; ?></div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filtros">
            <h3>🔍 Filtros de Búsqueda</h3>
            <form method="GET" action="">
                <div class="filtros-grid">
                    <div class="form-group">
                        <label>Búsqueda</label>
                        <input type="text" name="busqueda" placeholder="Nombre, cédula, departamento..." value="<?php echo htmlspecialchars($busqueda); ?>">
                    </div>
                    <div class="form-group">
                        <label>Estado</label>
                        <select name="estado">
                            <option value="">Todos</option>
                            <option value="pendiente" <?php echo $estado_filtro === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="en_revision" <?php echo $estado_filtro === 'en_revision' ? 'selected' : ''; ?>>En Revisión</option>
                            <option value="cerrado" <?php echo $estado_filtro === 'cerrado' ? 'selected' : ''; ?>>Cerrado</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Fecha Desde</label>
                        <input type="date" name="fecha_desde" value="<?php echo htmlspecialchars($fecha_desde); ?>">
                    </div>
                    <div class="form-group">
                        <label>Fecha Hasta</label>
                        <input type="date" name="fecha_hasta" value="<?php echo htmlspecialchars($fecha_hasta); ?>">
                    </div>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <button type="submit" class="btn btn-primary">Buscar</button>
                    <a href="admin_avisos.php" class="btn" style="background: #6c757d; color: white;">Limpiar Filtros</a>
                </div>
            </form>
        </div>

        <!-- Tabla de avisos -->
        <div class="tabla-container">
            <?php if (empty($avisos)): ?>
                <div class="empty-state">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3>No se encontraron avisos</h3>
                    <p>No hay avisos que coincidan con los criterios de búsqueda.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha Incidente</th>
                            <th>Empleado</th>
                            <th>Cédula</th>
                            <th>Departamento</th>
                            <th>Tipo de Lesión</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($avisos as $aviso): ?>
                        <tr>
                            <td><strong>#<?php echo str_pad($aviso['id'], 6, '0', STR_PAD_LEFT); ?></strong></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($aviso['fecha_incidente'] . ' ' . $aviso['hora_incidente'])); ?></td>
                            <td><?php echo htmlspecialchars($aviso['nombre_completo']); ?></td>
                            <td><?php echo htmlspecialchars($aviso['cedula']); ?></td>
                            <td><?php echo htmlspecialchars($aviso['departamento']); ?></td>
                            <td><?php echo htmlspecialchars($aviso['tipo_lesion']); ?></td>
                            <td>
                                <?php
                                $badge_class = 'badge-pendiente';
                                if ($aviso['estado'] === 'en_revision') $badge_class = 'badge-revision';
                                if ($aviso['estado'] === 'cerrado') $badge_class = 'badge-cerrado';
                                ?>
                                <span class="badge <?php echo $badge_class; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $aviso['estado'])); ?>
                                </span>
                            </td>
                            <td>
                                <div class="acciones">
                                    <a href="ver_aviso.php?id=<?php echo $aviso['id']; ?>" class="btn btn-ver btn-sm">Ver</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
