<?php
/**
 * Panel de Administración de Proveedores
 * LicitacionesYA - Sistema de Gestión de Proveedores
 */

session_start();

// Verificar que es admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

// Configuración de paginación
$items_por_pagina = 50;
$pagina_actual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina_actual - 1) * $items_por_pagina;

// Filtros
$filtro_busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$filtro_tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$filtro_tamano = isset($_GET['tamano']) ? $_GET['tamano'] : '';
$filtro_zona = isset($_GET['zona']) ? $_GET['zona'] : '';
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';

// Procesar acciones
$mensaje = '';
$mensaje_tipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'agregar':
                $cedula = trim($_POST['cedula_proveedor']);
                $nombre = trim($_POST['nombre_proveedor']);
                $tipo = $_POST['tipo_proveedor'];
                $tamano = $_POST['tamano_proveedor'];
                $fecha_constitucion = !empty($_POST['fecha_constitucion']) ? $_POST['fecha_constitucion'] : null;
                $fecha_expiracion = !empty($_POST['fecha_expiracion']) ? $_POST['fecha_expiracion'] : null;
                $zona = trim($_POST['zona_geo_prov']);
                $direccion = trim($_POST['direccion']);
                $telefono = trim($_POST['telefono']);
                $email = trim($_POST['email']);
                $sitio_web = trim($_POST['sitio_web']);
                $actividad = trim($_POST['actividad_economica']);
                $codigo_actividad = trim($_POST['codigo_actividad']);
                $estado = $_POST['estado_proveedor'];
                $notas = trim($_POST['notas']);

                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO proveedores (
                            cedula_proveedor, nombre_proveedor, tipo_proveedor, tamano_proveedor,
                            fecha_constitucion, fecha_expiracion, zona_geo_prov, direccion,
                            telefono, email, sitio_web, actividad_economica, codigo_actividad,
                            estado_proveedor, notas, registrado_por
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    $stmt->execute([
                        $cedula, $nombre, $tipo, $tamano, $fecha_constitucion, $fecha_expiracion,
                        $zona, $direccion, $telefono, $email, $sitio_web, $actividad,
                        $codigo_actividad, $estado, $notas, $_SESSION['user_id']
                    ]);

                    $mensaje = "✅ Proveedor agregado correctamente: $nombre";
                    $mensaje_tipo = 'success';
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $mensaje = "❌ Error: Ya existe un proveedor con la cédula $cedula";
                    } else {
                        $mensaje = "❌ Error al agregar proveedor: " . $e->getMessage();
                    }
                    $mensaje_tipo = 'error';
                }
                break;

            case 'editar':
                $id = intval($_POST['id']);
                $cedula = trim($_POST['cedula_proveedor']);
                $nombre = trim($_POST['nombre_proveedor']);
                $tipo = $_POST['tipo_proveedor'];
                $tamano = $_POST['tamano_proveedor'];
                $fecha_constitucion = !empty($_POST['fecha_constitucion']) ? $_POST['fecha_constitucion'] : null;
                $fecha_expiracion = !empty($_POST['fecha_expiracion']) ? $_POST['fecha_expiracion'] : null;
                $zona = trim($_POST['zona_geo_prov']);
                $direccion = trim($_POST['direccion']);
                $telefono = trim($_POST['telefono']);
                $email = trim($_POST['email']);
                $sitio_web = trim($_POST['sitio_web']);
                $actividad = trim($_POST['actividad_economica']);
                $codigo_actividad = trim($_POST['codigo_actividad']);
                $estado = $_POST['estado_proveedor'];
                $motivo_inhabilitacion = trim($_POST['motivo_inhabilitacion']);
                $notas = trim($_POST['notas']);

                try {
                    $stmt = $pdo->prepare("
                        UPDATE proveedores SET
                            cedula_proveedor = ?, nombre_proveedor = ?, tipo_proveedor = ?,
                            tamano_proveedor = ?, fecha_constitucion = ?, fecha_expiracion = ?,
                            zona_geo_prov = ?, direccion = ?, telefono = ?, email = ?,
                            sitio_web = ?, actividad_economica = ?, codigo_actividad = ?,
                            estado_proveedor = ?, motivo_inhabilitacion = ?, notas = ?
                        WHERE id = ?
                    ");

                    $stmt->execute([
                        $cedula, $nombre, $tipo, $tamano, $fecha_constitucion, $fecha_expiracion,
                        $zona, $direccion, $telefono, $email, $sitio_web, $actividad,
                        $codigo_actividad, $estado, $motivo_inhabilitacion, $notas, $id
                    ]);

                    $mensaje = "✅ Proveedor actualizado correctamente";
                    $mensaje_tipo = 'success';
                } catch (PDOException $e) {
                    $mensaje = "❌ Error al actualizar proveedor: " . $e->getMessage();
                    $mensaje_tipo = 'error';
                }
                break;

            case 'eliminar':
                $id = intval($_POST['id']);
                try {
                    // Verificar si tiene adjudicaciones
                    $stmt = $pdo->prepare("SELECT total_adjudicaciones FROM proveedores WHERE id = ?");
                    $stmt->execute([$id]);
                    $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($proveedor && $proveedor['total_adjudicaciones'] > 0) {
                        $mensaje = "⚠️ No se puede eliminar: El proveedor tiene {$proveedor['total_adjudicaciones']} adjudicaciones registradas. Puedes inactivarlo en su lugar.";
                        $mensaje_tipo = 'warning';
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM proveedores WHERE id = ?");
                        $stmt->execute([$id]);
                        $mensaje = "✅ Proveedor eliminado correctamente";
                        $mensaje_tipo = 'success';
                    }
                } catch (PDOException $e) {
                    $mensaje = "❌ Error al eliminar proveedor: " . $e->getMessage();
                    $mensaje_tipo = 'error';
                }
                break;
        }
    }
}

// Construir query con filtros
$where_conditions = [];
$params = [];

if (!empty($filtro_busqueda)) {
    $where_conditions[] = "(cedula_proveedor LIKE ? OR nombre_proveedor LIKE ?)";
    $search_param = "%{$filtro_busqueda}%";
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($filtro_tipo)) {
    $where_conditions[] = "tipo_proveedor = ?";
    $params[] = $filtro_tipo;
}

if (!empty($filtro_tamano)) {
    $where_conditions[] = "tamano_proveedor = ?";
    $params[] = $filtro_tamano;
}

if (!empty($filtro_zona)) {
    $where_conditions[] = "zona_geo_prov LIKE ?";
    $params[] = "%{$filtro_zona}%";
}

if (!empty($filtro_estado)) {
    $where_conditions[] = "estado_proveedor = ?";
    $params[] = $filtro_estado;
}

$where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Contar total
$count_query = "SELECT COUNT(*) FROM proveedores $where_sql";
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_proveedores = $stmt->fetchColumn();
$total_paginas = ceil($total_proveedores / $items_por_pagina);

// Obtener proveedores
$query = "
    SELECT *
    FROM proveedores
    $where_sql
    ORDER BY fecha_registro DESC
    LIMIT $items_por_pagina OFFSET $offset
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas generales
$stats_query = "
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN estado_proveedor = 'Activo' THEN 1 ELSE 0 END) as activos,
        SUM(CASE WHEN estado_proveedor = 'Inactivo' THEN 1 ELSE 0 END) as inactivos,
        SUM(CASE WHEN estado_proveedor = 'Inhabilitado' THEN 1 ELSE 0 END) as inhabilitados,
        SUM(total_adjudicaciones) as total_adj,
        SUM(monto_total_adjudicado) as total_monto
    FROM proveedores
";
$stats = $pdo->query($stats_query)->fetch(PDO::FETCH_ASSOC);

// Obtener zonas para filtro
$zonas = $pdo->query("
    SELECT DISTINCT zona_geo_prov
    FROM proveedores
    WHERE zona_geo_prov IS NOT NULL AND zona_geo_prov != ''
    ORDER BY zona_geo_prov
")->fetchAll(PDO::FETCH_COLUMN);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de Proveedores - LicitacionesYA</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #f5f7fa;
            color: #2d3748;
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        header h1 {
            font-size: 2em;
            margin-bottom: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card .number {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-card .label {
            color: #718096;
            font-size: 0.9em;
        }

        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #48bb78;
            color: white;
        }

        .btn-success:hover {
            background: #38a169;
        }

        .btn-danger {
            background: #f56565;
            color: white;
        }

        .btn-danger:hover {
            background: #e53e3e;
        }

        .btn-secondary {
            background: #718096;
            color: white;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }

        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 5px;
            font-weight: 600;
            font-size: 0.9em;
            color: #4a5568;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f7fafc;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #4a5568;
            border-bottom: 2px solid #e2e8f0;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        tbody tr:hover {
            background: #f7fafc;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .badge-success {
            background: #c6f6d5;
            color: #22543d;
        }

        .badge-warning {
            background: #feebc8;
            color: #7c2d12;
        }

        .badge-danger {
            background: #fed7d7;
            color: #742a2a;
        }

        .badge-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }

        .mensaje {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .mensaje-success {
            background: #c6f6d5;
            color: #22543d;
            border-left: 4px solid #48bb78;
        }

        .mensaje-error {
            background: #fed7d7;
            color: #742a2a;
            border-left: 4px solid #f56565;
        }

        .mensaje-warning {
            background: #feebc8;
            color: #7c2d12;
            border-left: 4px solid #ed8936;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            padding: 20px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border-radius: 6px;
            text-decoration: none;
            color: #4a5568;
        }

        .pagination a:hover {
            background: #e2e8f0;
        }

        .pagination .active {
            background: #667eea;
            color: white;
            font-weight: bold;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            overflow-y: auto;
        }

        .modal-content {
            background: white;
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }

        .modal-header h2 {
            color: #2d3748;
        }

        .close-modal {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #a0aec0;
            line-height: 1;
        }

        .close-modal:hover {
            color: #2d3748;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .form-grid .full-width {
            grid-column: 1 / -1;
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: #a0aec0;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .filters-grid,
            .form-grid {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🏢 Administración de Proveedores</h1>
            <p>Gestión completa de proveedores del sistema</p>
        </header>

        <?php if ($mensaje): ?>
            <div class="mensaje mensaje-<?php echo htmlspecialchars($mensaje_tipo); ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="number"><?php echo number_format($stats['total']); ?></div>
                <div class="label">Total Proveedores</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo number_format($stats['activos']); ?></div>
                <div class="label">Activos</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo number_format($stats['inactivos']); ?></div>
                <div class="label">Inactivos</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo number_format($stats['inhabilitados']); ?></div>
                <div class="label">Inhabilitados</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo number_format($stats['total_adj']); ?></div>
                <div class="label">Total Adjudicaciones</div>
            </div>
            <div class="stat-card">
                <div class="number">₡<?php echo number_format($stats['total_monto'], 0); ?></div>
                <div class="label">Monto Total Adjudicado</div>
            </div>
        </div>

        <!-- Acciones -->
        <div class="actions-bar">
            <div>
                <button class="btn btn-primary" onclick="openModal('agregar')">
                    ➕ Agregar Proveedor
                </button>
                <a href="importar_proveedores.php" class="btn btn-success">
                    📥 Importar CSV/Excel
                </a>
                <a href="exportar_proveedores.php" class="btn btn-secondary">
                    📤 Exportar
                </a>
            </div>
            <div>
                <a href="dashboard.php" class="btn btn-secondary">
                    ← Volver al Dashboard
                </a>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters-section">
            <form method="GET" action="">
                <div class="filters-grid">
                    <div class="form-group">
                        <label>🔍 Buscar (Cédula o Nombre)</label>
                        <input type="text" name="busqueda" value="<?php echo htmlspecialchars($filtro_busqueda); ?>" placeholder="Ej: 3-101-123456">
                    </div>
                    <div class="form-group">
                        <label>Tipo de Proveedor</label>
                        <select name="tipo">
                            <option value="">Todos</option>
                            <option value="Física" <?php echo $filtro_tipo === 'Física' ? 'selected' : ''; ?>>Física</option>
                            <option value="Jurídica" <?php echo $filtro_tipo === 'Jurídica' ? 'selected' : ''; ?>>Jurídica</option>
                            <option value="Extranjera" <?php echo $filtro_tipo === 'Extranjera' ? 'selected' : ''; ?>>Extranjera</option>
                            <option value="Consorcio" <?php echo $filtro_tipo === 'Consorcio' ? 'selected' : ''; ?>>Consorcio</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tamaño</label>
                        <select name="tamano">
                            <option value="">Todos</option>
                            <option value="Micro" <?php echo $filtro_tamano === 'Micro' ? 'selected' : ''; ?>>Micro</option>
                            <option value="Pequeña" <?php echo $filtro_tamano === 'Pequeña' ? 'selected' : ''; ?>>Pequeña</option>
                            <option value="Mediana" <?php echo $filtro_tamano === 'Mediana' ? 'selected' : ''; ?>>Mediana</option>
                            <option value="Grande" <?php echo $filtro_tamano === 'Grande' ? 'selected' : ''; ?>>Grande</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Zona/Provincia</label>
                        <select name="zona">
                            <option value="">Todas</option>
                            <?php foreach ($zonas as $zona): ?>
                                <option value="<?php echo htmlspecialchars($zona); ?>" <?php echo $filtro_zona === $zona ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($zona); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Estado</label>
                        <select name="estado">
                            <option value="">Todos</option>
                            <option value="Activo" <?php echo $filtro_estado === 'Activo' ? 'selected' : ''; ?>>Activo</option>
                            <option value="Inactivo" <?php echo $filtro_estado === 'Inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                            <option value="Suspendido" <?php echo $filtro_estado === 'Suspendido' ? 'selected' : ''; ?>>Suspendido</option>
                            <option value="Inhabilitado" <?php echo $filtro_estado === 'Inhabilitado' ? 'selected' : ''; ?>>Inhabilitado</option>
                        </select>
                    </div>
                </div>
                <div style="margin-top: 15px;">
                    <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
                    <a href="proveedores.php" class="btn btn-secondary">Limpiar</a>
                </div>
            </form>
        </div>

        <!-- Tabla de Proveedores -->
        <div class="table-container">
            <?php if (count($proveedores) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Cédula</th>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Tamaño</th>
                            <th>Zona</th>
                            <th>Estado</th>
                            <th>Adjudicaciones</th>
                            <th>Monto Total</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($proveedores as $proveedor): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($proveedor['cedula_proveedor']); ?></td>
                                <td><strong><?php echo htmlspecialchars($proveedor['nombre_proveedor']); ?></strong></td>
                                <td><?php echo htmlspecialchars($proveedor['tipo_proveedor']); ?></td>
                                <td><?php echo htmlspecialchars($proveedor['tamano_proveedor']); ?></td>
                                <td><?php echo htmlspecialchars($proveedor['zona_geo_prov'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php
                                    $badge_class = '';
                                    switch ($proveedor['estado_proveedor']) {
                                        case 'Activo': $badge_class = 'badge-success'; break;
                                        case 'Inactivo': $badge_class = 'badge-secondary'; break;
                                        case 'Suspendido': $badge_class = 'badge-warning'; break;
                                        case 'Inhabilitado': $badge_class = 'badge-danger'; break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo htmlspecialchars($proveedor['estado_proveedor']); ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($proveedor['total_adjudicaciones']); ?></td>
                                <td>₡<?php echo number_format($proveedor['monto_total_adjudicado'], 2); ?></td>
                                <td>
                                    <button class="btn btn-primary btn-small" onclick='editarProveedor(<?php echo json_encode($proveedor, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                        ✏️ Editar
                                    </button>
                                    <button class="btn btn-danger btn-small" onclick="confirmarEliminar(<?php echo $proveedor['id']; ?>, '<?php echo htmlspecialchars($proveedor['nombre_proveedor'], ENT_QUOTES); ?>')">
                                        🗑️
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Paginación -->
                <?php if ($total_paginas > 1): ?>
                    <div class="pagination">
                        <?php if ($pagina_actual > 1): ?>
                            <a href="?pagina=<?php echo $pagina_actual - 1; ?>&busqueda=<?php echo urlencode($filtro_busqueda); ?>&tipo=<?php echo urlencode($filtro_tipo); ?>&tamano=<?php echo urlencode($filtro_tamano); ?>&zona=<?php echo urlencode($filtro_zona); ?>&estado=<?php echo urlencode($filtro_estado); ?>">
                                ← Anterior
                            </a>
                        <?php endif; ?>

                        <?php
                        $rango = 2;
                        $inicio = max(1, $pagina_actual - $rango);
                        $fin = min($total_paginas, $pagina_actual + $rango);

                        for ($i = $inicio; $i <= $fin; $i++):
                        ?>
                            <a href="?pagina=<?php echo $i; ?>&busqueda=<?php echo urlencode($filtro_busqueda); ?>&tipo=<?php echo urlencode($filtro_tipo); ?>&tamano=<?php echo urlencode($filtro_tamano); ?>&zona=<?php echo urlencode($filtro_zona); ?>&estado=<?php echo urlencode($filtro_estado); ?>"
                               class="<?php echo $i === $pagina_actual ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($pagina_actual < $total_paginas): ?>
                            <a href="?pagina=<?php echo $pagina_actual + 1; ?>&busqueda=<?php echo urlencode($filtro_busqueda); ?>&tipo=<?php echo urlencode($filtro_tipo); ?>&tamano=<?php echo urlencode($filtro_tamano); ?>&zona=<?php echo urlencode($filtro_zona); ?>&estado=<?php echo urlencode($filtro_estado); ?>">
                                Siguiente →
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-results">
                    <h3>😕 No se encontraron proveedores</h3>
                    <p>Intenta ajustar los filtros o agrega un nuevo proveedor</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Agregar/Editar -->
    <div id="modal-proveedor" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-titulo">Agregar Proveedor</h2>
                <span class="close-modal" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" id="form-proveedor">
                <input type="hidden" name="accion" id="form-accion" value="agregar">
                <input type="hidden" name="id" id="form-id">

                <div class="form-grid">
                    <div class="form-group">
                        <label>Cédula <span style="color: red;">*</span></label>
                        <input type="text" name="cedula_proveedor" id="form-cedula" required placeholder="Ej: 3-101-123456">
                    </div>
                    <div class="form-group">
                        <label>Nombre/Razón Social <span style="color: red;">*</span></label>
                        <input type="text" name="nombre_proveedor" id="form-nombre" required placeholder="Nombre completo">
                    </div>
                    <div class="form-group">
                        <label>Tipo de Proveedor</label>
                        <select name="tipo_proveedor" id="form-tipo">
                            <option value="Jurídica">Jurídica</option>
                            <option value="Física">Física</option>
                            <option value="Extranjera">Extranjera</option>
                            <option value="Consorcio">Consorcio</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tamaño</label>
                        <select name="tamano_proveedor" id="form-tamano">
                            <option value="No Especificado">No Especificado</option>
                            <option value="Micro">Micro</option>
                            <option value="Pequeña">Pequeña</option>
                            <option value="Mediana">Mediana</option>
                            <option value="Grande">Grande</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Fecha de Constitución</label>
                        <input type="date" name="fecha_constitucion" id="form-fecha-constitucion">
                    </div>
                    <div class="form-group">
                        <label>Fecha de Expiración</label>
                        <input type="date" name="fecha_expiracion" id="form-fecha-expiracion">
                    </div>
                    <div class="form-group">
                        <label>Provincia/Zona</label>
                        <input type="text" name="zona_geo_prov" id="form-zona" placeholder="Ej: San José">
                    </div>
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="text" name="telefono" id="form-telefono" placeholder="Ej: 2222-3333">
                    </div>
                    <div class="form-group full-width">
                        <label>Dirección</label>
                        <input type="text" name="direccion" id="form-direccion" placeholder="Dirección completa">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" id="form-email" placeholder="correo@ejemplo.com">
                    </div>
                    <div class="form-group">
                        <label>Sitio Web</label>
                        <input type="url" name="sitio_web" id="form-sitio" placeholder="https://ejemplo.com">
                    </div>
                    <div class="form-group">
                        <label>Actividad Económica</label>
                        <input type="text" name="actividad_economica" id="form-actividad" placeholder="Ej: Construcción">
                    </div>
                    <div class="form-group">
                        <label>Código CIIU</label>
                        <input type="text" name="codigo_actividad" id="form-codigo" placeholder="Ej: 4120">
                    </div>
                    <div class="form-group">
                        <label>Estado</label>
                        <select name="estado_proveedor" id="form-estado">
                            <option value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                            <option value="Suspendido">Suspendido</option>
                            <option value="Inhabilitado">Inhabilitado</option>
                        </select>
                    </div>
                    <div class="form-group" id="motivo-container" style="display: none;">
                        <label>Motivo de Inhabilitación</label>
                        <textarea name="motivo_inhabilitacion" id="form-motivo"></textarea>
                    </div>
                    <div class="form-group full-width">
                        <label>Notas Internas</label>
                        <textarea name="notas" id="form-notas"></textarea>
                    </div>
                </div>

                <div style="margin-top: 20px; text-align: right;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(modo) {
            const modal = document.getElementById('modal-proveedor');
            const form = document.getElementById('form-proveedor');
            const titulo = document.getElementById('modal-titulo');

            form.reset();
            document.getElementById('form-accion').value = modo;

            if (modo === 'agregar') {
                titulo.textContent = 'Agregar Proveedor';
                document.getElementById('form-id').value = '';
            }

            modal.style.display = 'block';
        }

        function closeModal() {
            document.getElementById('modal-proveedor').style.display = 'none';
        }

        function editarProveedor(proveedor) {
            openModal('editar');
            document.getElementById('modal-titulo').textContent = 'Editar Proveedor';
            document.getElementById('form-accion').value = 'editar';
            document.getElementById('form-id').value = proveedor.id;
            document.getElementById('form-cedula').value = proveedor.cedula_proveedor;
            document.getElementById('form-nombre').value = proveedor.nombre_proveedor;
            document.getElementById('form-tipo').value = proveedor.tipo_proveedor;
            document.getElementById('form-tamano').value = proveedor.tamano_proveedor;
            document.getElementById('form-fecha-constitucion').value = proveedor.fecha_constitucion || '';
            document.getElementById('form-fecha-expiracion').value = proveedor.fecha_expiracion || '';
            document.getElementById('form-zona').value = proveedor.zona_geo_prov || '';
            document.getElementById('form-direccion').value = proveedor.direccion || '';
            document.getElementById('form-telefono').value = proveedor.telefono || '';
            document.getElementById('form-email').value = proveedor.email || '';
            document.getElementById('form-sitio').value = proveedor.sitio_web || '';
            document.getElementById('form-actividad').value = proveedor.actividad_economica || '';
            document.getElementById('form-codigo').value = proveedor.codigo_actividad || '';
            document.getElementById('form-estado').value = proveedor.estado_proveedor;
            document.getElementById('form-motivo').value = proveedor.motivo_inhabilitacion || '';
            document.getElementById('form-notas').value = proveedor.notas || '';

            // Mostrar campo de motivo si está inhabilitado
            if (proveedor.estado_proveedor === 'Inhabilitado') {
                document.getElementById('motivo-container').style.display = 'block';
            }
        }

        function confirmarEliminar(id, nombre) {
            if (confirm(`¿Estás seguro de eliminar al proveedor "${nombre}"?\n\nEsta acción no se puede deshacer.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Mostrar/ocultar campo de motivo según estado
        document.getElementById('form-estado').addEventListener('change', function() {
            const motivoContainer = document.getElementById('motivo-container');
            if (this.value === 'Inhabilitado') {
                motivoContainer.style.display = 'block';
            } else {
                motivoContainer.style.display = 'none';
                document.getElementById('form-motivo').value = '';
            }
        });

        // Cerrar modal al hacer click fuera
        window.onclick = function(event) {
            const modal = document.getElementById('modal-proveedor');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
