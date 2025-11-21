<?php
/**
 * ═══════════════════════════════════════════════════════════════════════
 * INTELIGENCIA COMPETITIVA - Versión 2 con Debugging Mejorado
 * ═══════════════════════════════════════════════════════════════════════
 */

// Iniciar sesión
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación
if (!isset($_SESSION['usuario_id']) && !isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

if (!isset($pdo) && isset($conn)) {
    $pdo = $conn;
}

// MODO DEBUG - Cambiar a false en producción
$DEBUG_MODE = true;
$debug_info = [];

// Obtener parámetros de búsqueda
$codigo_buscar = isset($_GET['codigo']) ? trim($_GET['codigo']) : '';
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';
$proveedor_filtro = isset($_GET['proveedor']) ? $_GET['proveedor'] : '';
$institucion_filtro = isset($_GET['institucion']) ? $_GET['institucion'] : '';
$modo_vista = isset($_GET['modo']) ? $_GET['modo'] : 'codigo';

$datos_encontrados = false;
$adjudicaciones = [];
$stats = [];
$top_proveedores = [];
$top_instituciones = [];
$historial_precios = [];

if (!empty($codigo_buscar)) {
    try {
        // PASO 1: Probar búsqueda simple primero
        if ($DEBUG_MODE) {
            $debug_info[] = "Buscando: " . $codigo_buscar;
            $debug_info[] = "Modo: " . $modo_vista;
        }

        // Construir consulta MÁS SIMPLE y FLEXIBLE
        if ($modo_vista === 'codigo') {
            $codigo_8 = substr($codigo_buscar, 0, 8);

            // Intentar búsqueda en múltiples campos
            $sql = "SELECT
                        la.*,
                        l.titulo as titulo_licitacion,
                        l.fecha_publicacion,
                        l.presupuesto_estimado,
                        l.cedula_institucion,
                        p.nombre_proveedor,
                        ic.nombre_institucion,
                        ic.provincia
                    FROM lineas_adjudicadas la
                    LEFT JOIN licitaciones l ON la.numero_sicop = l.numero_sicop
                    LEFT JOIN proveedoras p ON la.cedula_proveedor = p.cedula
                    LEFT JOIN instituciones_compradoras ic ON l.cedula_institucion = ic.cedula
                    WHERE (
                        la.codigo_producto LIKE :codigo OR
                        SUBSTRING(la.codigo_producto, 1, 8) = :codigo_8
                    )";

            $params = [
                ':codigo' => '%' . $codigo_buscar . '%',
                ':codigo_8' => $codigo_8
            ];

            if ($DEBUG_MODE) {
                $debug_info[] = "Código 8 dígitos: " . $codigo_8;
            }

        } else {
            // Búsqueda por proveedor
            $sql = "SELECT
                        la.*,
                        l.titulo as titulo_licitacion,
                        l.fecha_publicacion,
                        l.presupuesto_estimado,
                        l.cedula_institucion,
                        p.nombre_proveedor,
                        ic.nombre_institucion,
                        ic.provincia
                    FROM lineas_adjudicadas la
                    LEFT JOIN licitaciones l ON la.numero_sicop = l.numero_sicop
                    LEFT JOIN proveedoras p ON la.cedula_proveedor = p.cedula
                    LEFT JOIN instituciones_compradoras ic ON l.cedula_institucion = ic.cedula
                    WHERE (
                        la.cedula_proveedor LIKE :codigo OR
                        p.nombre_proveedor LIKE :codigo_nombre
                    )";

            $params = [
                ':codigo' => '%' . $codigo_buscar . '%',
                ':codigo_nombre' => '%' . $codigo_buscar . '%'
            ];
        }

        // Filtros adicionales
        if (!empty($fecha_desde)) {
            $sql .= " AND l.fecha_publicacion >= :fecha_desde";
            $params[':fecha_desde'] = $fecha_desde;
        }

        if (!empty($fecha_hasta)) {
            $sql .= " AND l.fecha_publicacion <= :fecha_hasta";
            $params[':fecha_hasta'] = $fecha_hasta;
        }

        if (!empty($proveedor_filtro)) {
            $sql .= " AND la.cedula_proveedor = :proveedor";
            $params[':proveedor'] = $proveedor_filtro;
        }

        if (!empty($institucion_filtro)) {
            $sql .= " AND l.cedula_institucion = :institucion";
            $params[':institucion'] = $institucion_filtro;
        }

        $sql .= " ORDER BY l.fecha_publicacion DESC LIMIT 500";

        if ($DEBUG_MODE) {
            $debug_info[] = "SQL: " . $sql;
            $debug_info[] = "Parámetros: " . json_encode($params);
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $adjudicaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($DEBUG_MODE) {
            $debug_info[] = "Resultados encontrados: " . count($adjudicaciones);

            // Si no hay resultados, probar búsquedas alternativas
            if (empty($adjudicaciones) && $modo_vista === 'codigo') {
                // Probar si existe ALGÚN dato con ese código
                $test_sql = "SELECT COUNT(*) as total FROM lineas_adjudicadas WHERE codigo_producto LIKE :codigo";
                $test_stmt = $pdo->prepare($test_sql);
                $test_stmt->execute([':codigo' => '%' . $codigo_buscar . '%']);
                $test_result = $test_stmt->fetch(PDO::FETCH_ASSOC);
                $debug_info[] = "Búsqueda directa en lineas_adjudicadas: " . $test_result['total'] . " registros";

                // Probar búsqueda en partidas_licitacion
                $test_sql2 = "SELECT COUNT(*) as total FROM partidas_licitacion
                              WHERE codigo LIKE :codigo OR codigo_identificacion LIKE :codigo";
                $test_stmt2 = $pdo->prepare($test_sql2);
                $test_stmt2->execute([':codigo' => '%' . $codigo_buscar . '%']);
                $test_result2 = $test_stmt2->fetch(PDO::FETCH_ASSOC);
                $debug_info[] = "Búsqueda en partidas_licitacion: " . $test_result2['total'] . " registros";
            }
        }

        if (!empty($adjudicaciones)) {
            $datos_encontrados = true;

            // Calcular estadísticas
            $precios = [];
            $cantidades = [];
            $proveedores_count = [];
            $instituciones_count = [];
            $monto_total = 0;

            foreach ($adjudicaciones as $adj) {
                $precio = floatval($adj['precio_unitario_adjudicado'] ?? 0);
                $cantidad = floatval($adj['cantidad_adjudicada'] ?? 0);

                if ($precio > 0) {
                    $precios[] = $precio;

                    // Para historial de precios
                    $fecha = $adj['fecha_publicacion'] ?? '';
                    if ($fecha) {
                        $historial_precios[] = [
                            'fecha' => $fecha,
                            'precio' => $precio,
                            'sicop' => $adj['numero_sicop'],
                            'proveedor' => $adj['nombre_proveedor'] ?? $adj['cedula_proveedor']
                        ];
                    }
                }

                if ($cantidad > 0) {
                    $cantidades[] = $cantidad;
                }

                $monto_linea = $precio * $cantidad;
                $monto_total += $monto_linea;

                // Contar proveedores
                $cedula_prov = $adj['cedula_proveedor'];
                if (!isset($proveedores_count[$cedula_prov])) {
                    $proveedores_count[$cedula_prov] = [
                        'nombre' => $adj['nombre_proveedor'] ?? 'Sin nombre',
                        'cedula' => $cedula_prov,
                        'adjudicaciones' => 0,
                        'monto_total' => 0
                    ];
                }
                $proveedores_count[$cedula_prov]['adjudicaciones']++;
                $proveedores_count[$cedula_prov]['monto_total'] += $monto_linea;

                // Contar instituciones
                $cedula_inst = $adj['cedula_institucion'] ?? 'N/A';
                if (!isset($instituciones_count[$cedula_inst])) {
                    $instituciones_count[$cedula_inst] = [
                        'nombre' => $adj['nombre_institucion'] ?? 'Sin nombre',
                        'cedula' => $cedula_inst,
                        'licitaciones' => 0,
                        'monto_total' => 0
                    ];
                }
                $instituciones_count[$cedula_inst]['licitaciones']++;
                $instituciones_count[$cedula_inst]['monto_total'] += $monto_linea;
            }

            // Estadísticas generales
            $stats = [
                'total_adjudicaciones' => count($adjudicaciones),
                'precio_minimo' => !empty($precios) ? min($precios) : 0,
                'precio_maximo' => !empty($precios) ? max($precios) : 0,
                'precio_promedio' => !empty($precios) ? array_sum($precios) / count($precios) : 0,
                'precio_mediana' => !empty($precios) ? calcularMediana($precios) : 0,
                'cantidad_promedio' => !empty($cantidades) ? array_sum($cantidades) / count($cantidades) : 0,
                'monto_total' => $monto_total,
                'proveedores_unicos' => count($proveedores_count),
                'instituciones_unicas' => count($instituciones_count)
            ];

            // Top proveedores
            usort($proveedores_count, function($a, $b) {
                return $b['adjudicaciones'] - $a['adjudicaciones'];
            });
            $top_proveedores = array_slice($proveedores_count, 0, 10);

            // Top instituciones
            usort($instituciones_count, function($a, $b) {
                return $b['licitaciones'] - $a['licitaciones'];
            });
            $top_instituciones = array_slice($instituciones_count, 0, 10);

            // Ordenar historial de precios
            usort($historial_precios, function($a, $b) {
                return strcmp($a['fecha'], $b['fecha']);
            });
        }

    } catch (PDOException $e) {
        $debug_info[] = "ERROR SQL: " . $e->getMessage();
        error_log("Error en estadísticas_ganadores: " . $e->getMessage());
    }
}

function calcularMediana($arr) {
    sort($arr);
    $count = count($arr);
    $middle = floor($count / 2);

    if ($count % 2 == 0) {
        return ($arr[$middle - 1] + $arr[$middle]) / 2;
    } else {
        return $arr[$middle];
    }
}

function formatMonto($monto) {
    if ($monto >= 1000000) {
        return '₡' . number_format($monto / 1000000, 2) . 'M';
    }
    return '₡' . number_format($monto, 2);
}

include_once "../includes/header.php";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inteligencia Competitiva - LicitaHoy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #0056b3;
            --primary-light: #1e73d8;
            --secondary: #6c757d;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
            --purple: #8b5cf6;
            --orange: #f97316;
            --teal: #14b8a6;
            --text-dark: #1a1a1a;
            --text-light: #6c757d;
            --border: #dee2e6;
            --bg-gray: #f8f9fa;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
        }

        .page-header {
            background: white;
            border-radius: 16px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            text-align: center;
        }

        .page-header h1 {
            font-size: 2.5rem;
            color: var(--text-dark);
            margin-bottom: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-header p {
            color: var(--text-light);
            font-size: 1.1rem;
        }

        .debug-panel {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            font-family: monospace;
            font-size: 0.9rem;
        }

        .debug-panel h3 {
            color: #856404;
            margin-bottom: 10px;
        }

        .debug-panel ul {
            list-style: none;
            padding-left: 0;
        }

        .debug-panel li {
            padding: 5px;
            border-bottom: 1px solid #ffeeba;
        }

        .search-panel {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        }

        .search-header {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            border-bottom: 2px solid var(--border);
            padding-bottom: 15px;
        }

        .mode-tab {
            flex: 1;
            padding: 15px;
            background: var(--bg-gray);
            border: 2px solid transparent;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .mode-tab:hover {
            background: #e9ecef;
        }

        .mode-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
        }

        .search-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            transition: transform 0.3s;
            border-left: 5px solid;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card.purple { border-left-color: var(--purple); }
        .stat-card.blue { border-left-color: var(--primary); }
        .stat-card.green { border-left-color: var(--success); }
        .stat-card.orange { border-left-color: var(--orange); }
        .stat-card.teal { border-left-color: var(--teal); }

        .stat-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .stat-card.purple .stat-icon { color: var(--purple); }
        .stat-card.blue .stat-icon { color: var(--primary); }
        .stat-card.green .stat-icon { color: var(--success); }
        .stat-card.orange .stat-icon { color: var(--orange); }
        .stat-card.teal .stat-icon { color: var(--teal); }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--text-light);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .empty-state {
            background: white;
            border-radius: 16px;
            padding: 60px 30px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        }

        .empty-state i {
            font-size: 5rem;
            color: var(--border);
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: var(--text-dark);
            margin-bottom: 10px;
            font-size: 1.5rem;
        }

        .empty-state p {
            color: var(--text-light);
            font-size: 1.1rem;
        }

        .data-table {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid var(--border);
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        @media (max-width: 1200px) {
            .search-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 768px) {
            .search-grid {
                grid-template-columns: 1fr;
            }

            .page-header h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-chart-line"></i> Inteligencia Competitiva v2</h1>
            <p>Analiza precios históricos, competencia y tendencias del mercado</p>
        </div>

        <?php if ($DEBUG_MODE && !empty($debug_info)): ?>
        <div class="debug-panel">
            <h3><i class="fas fa-bug"></i> Información de Debug</h3>
            <ul>
                <?php foreach ($debug_info as $info): ?>
                    <li><?php echo htmlspecialchars($info); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="search-panel">
            <div class="search-header">
                <div class="mode-tab <?php echo $modo_vista === 'codigo' ? 'active' : ''; ?>"
                     onclick="document.getElementById('modo_codigo').click()">
                    <input type="radio" id="modo_codigo" name="modo_vista" value="codigo"
                           <?php echo $modo_vista === 'codigo' ? 'checked' : ''; ?> style="display:none;">
                    <i class="fas fa-barcode"></i> Buscar por Código UNSPSC
                </div>
                <div class="mode-tab <?php echo $modo_vista === 'proveedor' ? 'active' : ''; ?>"
                     onclick="document.getElementById('modo_proveedor').click()">
                    <input type="radio" id="modo_proveedor" name="modo_vista" value="proveedor"
                           <?php echo $modo_vista === 'proveedor' ? 'checked' : ''; ?> style="display:none;">
                    <i class="fas fa-building"></i> Buscar por Proveedor
                </div>
            </div>

            <form method="GET" id="searchForm">
                <input type="hidden" name="modo" id="modo_input" value="<?php echo $modo_vista; ?>">

                <div class="search-grid">
                    <div class="form-group">
                        <label>
                            <i class="fas fa-search"></i>
                            <?php echo $modo_vista === 'codigo' ? 'Código UNSPSC (completo o parcial)' : 'Cédula o Nombre del Proveedor'; ?>
                        </label>
                        <input type="text"
                               name="codigo"
                               placeholder="<?php echo $modo_vista === 'codigo' ? 'Ej: 43211500 u 4321150012345678' : 'Ej: 3-101-123456'; ?>"
                               value="<?php echo htmlspecialchars($codigo_buscar); ?>"
                               required>
                        <small style="color: #6c757d;">
                            <?php echo $modo_vista === 'codigo' ? 'Puedes buscar por 8 dígitos o código completo' : 'Nombre o cédula completa/parcial'; ?>
                        </small>
                    </div>

                    <div class="form-group">
                        <label><i class="far fa-calendar"></i> Desde</label>
                        <input type="date" name="fecha_desde" value="<?php echo $fecha_desde; ?>">
                    </div>

                    <div class="form-group">
                        <label><i class="far fa-calendar"></i> Hasta</label>
                        <input type="date" name="fecha_hasta" value="<?php echo $fecha_hasta; ?>">
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Analizar
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <?php if (!$datos_encontrados && !empty($codigo_buscar)): ?>
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <h3>No se encontraron resultados</h3>
                <p>No hay adjudicaciones registradas para "<?php echo htmlspecialchars($codigo_buscar); ?>"</p>
                <p style="margin-top: 15px; font-size: 0.95rem;">
                    <strong>Sugerencias:</strong><br>
                    - Verifica que el código sea correcto<br>
                    - Intenta con solo los primeros 8 dígitos del código UNSPSC<br>
                    - Prueba sin filtros de fecha<br>
                    - Revisa el panel de debug arriba para más información
                </p>
            </div>
        <?php elseif ($datos_encontrados): ?>

            <!-- Estadísticas Principales -->
            <div class="stats-grid">
                <div class="stat-card purple">
                    <div class="stat-icon"><i class="fas fa-trophy"></i></div>
                    <div class="stat-value"><?php echo number_format($stats['total_adjudicaciones']); ?></div>
                    <div class="stat-label">Adjudicaciones</div>
                </div>

                <div class="stat-card blue">
                    <div class="stat-icon"><i class="fas fa-tag"></i></div>
                    <div class="stat-value"><?php echo formatMonto($stats['precio_promedio']); ?></div>
                    <div class="stat-label">Precio Promedio</div>
                </div>

                <div class="stat-card green">
                    <div class="stat-icon"><i class="fas fa-arrow-down"></i></div>
                    <div class="stat-value"><?php echo formatMonto($stats['precio_minimo']); ?></div>
                    <div class="stat-label">Precio Mínimo</div>
                </div>

                <div class="stat-card orange">
                    <div class="stat-icon"><i class="fas fa-arrow-up"></i></div>
                    <div class="stat-value"><?php echo formatMonto($stats['precio_maximo']); ?></div>
                    <div class="stat-label">Precio Máximo</div>
                </div>

                <div class="stat-card teal">
                    <div class="stat-icon"><i class="fas fa-coins"></i></div>
                    <div class="stat-value"><?php echo formatMonto($stats['monto_total']); ?></div>
                    <div class="stat-label">Monto Total</div>
                </div>

                <div class="stat-card purple">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-value"><?php echo $stats['proveedores_unicos']; ?></div>
                    <div class="stat-label">Proveedores Únicos</div>
                </div>
            </div>

            <!-- Tabla de Adjudicaciones Detalladas -->
            <div class="data-table">
                <h3>
                    <i class="fas fa-list"></i>
                    Historial de Adjudicaciones (<?php echo count($adjudicaciones); ?> registros)
                </h3>
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>SICOP</th>
                            <th>Código Producto</th>
                            <th>Proveedor</th>
                            <th>Institución</th>
                            <th>Cantidad</th>
                            <th>Precio Unit.</th>
                            <th>Monto Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($adjudicaciones, 0, 100) as $adj):
                            $monto_linea = floatval($adj['precio_unitario_adjudicado']) * floatval($adj['cantidad_adjudicada']);
                        ?>
                        <tr>
                            <td><?php echo $adj['fecha_publicacion'] ? date('d/m/Y', strtotime($adj['fecha_publicacion'])) : 'N/A'; ?></td>
                            <td><strong><?php echo htmlspecialchars($adj['numero_sicop']); ?></strong></td>
                            <td><code><?php echo htmlspecialchars($adj['codigo_producto']); ?></code></td>
                            <td><?php echo htmlspecialchars(substr($adj['nombre_proveedor'] ?? $adj['cedula_proveedor'], 0, 30)); ?></td>
                            <td><?php echo htmlspecialchars(substr($adj['nombre_institucion'] ?? 'N/A', 0, 30)); ?></td>
                            <td><?php echo number_format($adj['cantidad_adjudicada'], 2); ?></td>
                            <td><strong><?php echo formatMonto($adj['precio_unitario_adjudicado']); ?></strong></td>
                            <td><strong><?php echo formatMonto($monto_linea); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (count($adjudicaciones) > 100): ?>
                <p style="text-align: center; margin-top: 20px; color: var(--text-light);">
                    Mostrando las primeras 100 adjudicaciones de <?php echo count($adjudicaciones); ?> totales.
                </p>
                <?php endif; ?>
            </div>

        <?php elseif (empty($codigo_buscar)): ?>
            <div class="empty-state">
                <i class="fas fa-search-dollar"></i>
                <h3>Comienza tu Análisis</h3>
                <p>Ingresa un código UNSPSC o nombre de proveedor para analizar el mercado</p>
                <p style="margin-top: 15px; font-size: 0.95rem; color: #6c757d;">
                    Ejemplos de búsqueda:<br>
                    <strong>Por código:</strong> 43211500 (8 dígitos) o 4321150012345678 (código completo)<br>
                    <strong>Por proveedor:</strong> 3-101-123456 o nombre de empresa
                </p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Cambiar modo de búsqueda
        document.querySelectorAll('input[name="modo_vista"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.getElementById('modo_input').value = this.value;
                // Limpiar el campo de búsqueda al cambiar de modo
                document.querySelector('input[name="codigo"]').value = '';
            });
        });
    </script>
</body>
</html>
