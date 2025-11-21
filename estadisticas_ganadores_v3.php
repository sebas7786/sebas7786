<?php
/**
 * ═══════════════════════════════════════════════════════════════════════
 * INTELIGENCIA COMPETITIVA - Versión 3 con Auto-Detección
 * ═══════════════════════════════════════════════════════════════════════
 * Detecta automáticamente si es código UNSPSC o proveedor
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
$modo_vista = isset($_GET['modo']) ? $_GET['modo'] : 'auto'; // auto, codigo, proveedor

$datos_encontrados = false;
$adjudicaciones = [];
$stats = [];
$top_proveedores = [];
$top_instituciones = [];
$historial_precios = [];

// FUNCIÓN DE AUTO-DETECCIÓN
function detectarTipoBusqueda($texto) {
    $texto = trim($texto);

    // Si es solo números y tiene 8-16 dígitos → Código UNSPSC
    if (preg_match('/^[0-9]{8,16}$/', $texto)) {
        return 'codigo';
    }

    // Si tiene formato de cédula costarricense → Proveedor
    if (preg_match('/^[0-9]-[0-9]{3,4}-[0-9]{4,6}$/', $texto)) {
        return 'proveedor';
    }

    // Si es solo números pero corto (menos de 8 dígitos) → Proveedor
    if (preg_match('/^[0-9]{1,7}$/', $texto)) {
        return 'proveedor';
    }

    // Si tiene letras → Proveedor (nombre)
    if (preg_match('/[a-zA-Z]/', $texto)) {
        return 'proveedor';
    }

    // Por defecto: código
    return 'codigo';
}

if (!empty($codigo_buscar)) {
    try {
        // AUTO-DETECTAR si no se especificó modo
        if ($modo_vista === 'auto') {
            $modo_vista = detectarTipoBusqueda($codigo_buscar);

            if ($DEBUG_MODE) {
                $debug_info[] = "🤖 AUTO-DETECCIÓN: Entrada '" . $codigo_buscar . "' detectada como: " . strtoupper($modo_vista);
            }
        }

        if ($DEBUG_MODE) {
            $debug_info[] = "Buscando: " . $codigo_buscar;
            $debug_info[] = "Modo: " . $modo_vista;
        }

        // Construir consulta según el modo detectado
        if ($modo_vista === 'codigo') {
            $codigo_8 = substr($codigo_buscar, 0, 8);

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
                        la.codigo_producto = :codigo_completo OR
                        la.codigo_producto LIKE :codigo_like OR
                        SUBSTRING(la.codigo_producto, 1, 8) = :codigo_8
                    )";

            $params = [
                ':codigo_completo' => $codigo_buscar,
                ':codigo_like' => '%' . $codigo_buscar . '%',
                ':codigo_8' => $codigo_8
            ];

            if ($DEBUG_MODE) {
                $debug_info[] = "Código 8 dígitos: " . $codigo_8;
                $debug_info[] = "Buscando en: codigo_producto";
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
                        la.cedula_proveedor = :codigo OR
                        la.cedula_proveedor LIKE :codigo_like OR
                        p.nombre_proveedor LIKE :codigo_nombre
                    )";

            $params = [
                ':codigo' => $codigo_buscar,
                ':codigo_like' => '%' . $codigo_buscar . '%',
                ':codigo_nombre' => '%' . $codigo_buscar . '%'
            ];

            if ($DEBUG_MODE) {
                $debug_info[] = "Buscando en: cedula_proveedor y nombre_proveedor";
            }
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
            $debug_info[] = "SQL: " . preg_replace('/\s+/', ' ', $sql);
            $debug_info[] = "Parámetros: " . json_encode($params);
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $adjudicaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($DEBUG_MODE) {
            $debug_info[] = "✅ Resultados encontrados: " . count($adjudicaciones);

            // Si no hay resultados, probar búsquedas alternativas
            if (empty($adjudicaciones)) {
                $debug_info[] = "⚠️ NO se encontraron resultados. Probando búsquedas alternativas...";

                if ($modo_vista === 'codigo') {
                    // Probar búsqueda directa
                    $test_sql = "SELECT COUNT(*) as total,
                                        MIN(codigo_producto) as ejemplo_codigo
                                 FROM lineas_adjudicadas
                                 WHERE codigo_producto LIKE :codigo";
                    $test_stmt = $pdo->prepare($test_sql);
                    $test_stmt->execute([':codigo' => '%' . $codigo_buscar . '%']);
                    $test_result = $test_stmt->fetch(PDO::FETCH_ASSOC);

                    $debug_info[] = "📊 Búsqueda directa en lineas_adjudicadas.codigo_producto: " . $test_result['total'] . " registros";

                    if ($test_result['total'] > 0) {
                        $debug_info[] = "   Ejemplo de código encontrado: " . $test_result['ejemplo_codigo'];
                    } else {
                        // Buscar códigos similares
                        $codigo_8 = substr($codigo_buscar, 0, 8);
                        $test_sql2 = "SELECT DISTINCT codigo_producto, COUNT(*) as veces
                                     FROM lineas_adjudicadas
                                     WHERE SUBSTRING(codigo_producto, 1, 8) LIKE :codigo
                                     GROUP BY codigo_producto
                                     ORDER BY veces DESC
                                     LIMIT 5";
                        $test_stmt2 = $pdo->prepare($test_sql2);
                        $test_stmt2->execute([':codigo' => substr($codigo_8, 0, 6) . '%']);
                        $similares = $test_stmt2->fetchAll(PDO::FETCH_ASSOC);

                        if (!empty($similares)) {
                            $debug_info[] = "💡 Códigos similares encontrados:";
                            foreach ($similares as $sim) {
                                $debug_info[] = "   - " . $sim['codigo_producto'] . " (" . $sim['veces'] . " veces)";
                            }
                        } else {
                            $debug_info[] = "❌ No se encontraron códigos similares";

                            // Mostrar algunos códigos de ejemplo de la BD
                            $test_sql3 = "SELECT DISTINCT codigo_producto
                                         FROM lineas_adjudicadas
                                         WHERE codigo_producto IS NOT NULL
                                         LIMIT 5";
                            $test_stmt3 = $pdo->query($test_sql3);
                            $ejemplos = $test_stmt3->fetchAll(PDO::FETCH_ASSOC);

                            if (!empty($ejemplos)) {
                                $debug_info[] = "📝 Ejemplos de códigos válidos en la BD:";
                                foreach ($ejemplos as $ej) {
                                    $debug_info[] = "   - " . $ej['codigo_producto'];
                                }
                            }
                        }
                    }

                } else {
                    // Proveedor
                    $test_sql = "SELECT COUNT(*) as total FROM lineas_adjudicadas WHERE cedula_proveedor LIKE :codigo";
                    $test_stmt = $pdo->prepare($test_sql);
                    $test_stmt->execute([':codigo' => '%' . $codigo_buscar . '%']);
                    $test_result = $test_stmt->fetch(PDO::FETCH_ASSOC);

                    $debug_info[] = "📊 Búsqueda en cedula_proveedor: " . $test_result['total'] . " registros";

                    $test_sql2 = "SELECT COUNT(*) as total FROM proveedoras WHERE nombre_proveedor LIKE :nombre OR cedula LIKE :cedula";
                    $test_stmt2 = $pdo->prepare($test_sql2);
                    $test_stmt2->execute([':nombre' => '%' . $codigo_buscar . '%', ':cedula' => '%' . $codigo_buscar . '%']);
                    $test_result2 = $test_stmt2->fetch(PDO::FETCH_ASSOC);

                    $debug_info[] = "📊 Búsqueda en tabla proveedoras: " . $test_result2['total'] . " registros";

                    if ($test_result2['total'] > 0) {
                        $debug_info[] = "💡 El proveedor existe pero no tiene adjudicaciones registradas";
                    }
                }
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
        $debug_info[] = "❌ ERROR SQL: " . $e->getMessage();
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

        .badge-auto {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-left: 10px;
        }

        .debug-panel {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }

        .debug-panel h3 {
            color: #856404;
            margin-bottom: 10px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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

        .search-form {
            display: flex;
            gap: 15px;
            align-items: end;
        }

        .form-group {
            flex: 1;
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

        @media (max-width: 768px) {
            .search-form {
                flex-direction: column;
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
            <h1>
                <i class="fas fa-chart-line"></i> Inteligencia Competitiva v3
                <span class="badge-auto">🤖 AUTO-DETECCIÓN</span>
            </h1>
            <p>Detecta automáticamente si buscas código UNSPSC o proveedor</p>
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
            <form method="GET" class="search-form">
                <input type="hidden" name="modo" value="auto">

                <div class="form-group" style="flex: 2;">
                    <label>
                        <i class="fas fa-search"></i>
                        Buscar (Código UNSPSC o Proveedor)
                    </label>
                    <input type="text"
                           name="codigo"
                           placeholder="Ej: 76111501 o 3-101-123456 o Nombre de Empresa"
                           value="<?php echo htmlspecialchars($codigo_buscar); ?>"
                           required>
                    <small style="color: #6c757d;">
                        🤖 La búsqueda se ajusta automáticamente según lo que ingreses
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
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
            </form>
        </div>

        <?php if (!$datos_encontrados && !empty($codigo_buscar)): ?>
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <h3>No se encontraron resultados</h3>
                <p>No hay adjudicaciones registradas para "<?php echo htmlspecialchars($codigo_buscar); ?>"</p>
                <p style="margin-top: 15px; font-size: 0.95rem; color: #6c757d;">
                    <strong>Revisa el panel de debug arriba</strong> para más información sobre por qué no se encontraron resultados
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

            <!-- Tabla de Adjudicaciones -->
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
                            <th>Código</th>
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
                            <td><code style="font-size: 0.85rem;"><?php echo htmlspecialchars($adj['codigo_producto']); ?></code></td>
                            <td><?php echo htmlspecialchars(substr($adj['nombre_proveedor'] ?? $adj['cedula_proveedor'], 0, 30)); ?></td>
                            <td><?php echo htmlspecialchars(substr($adj['nombre_institucion'] ?? 'N/A', 0, 30)); ?></td>
                            <td><?php echo number_format($adj['cantidad_adjudicada'], 2); ?></td>
                            <td><strong><?php echo formatMonto($adj['precio_unitario_adjudicado']); ?></strong></td>
                            <td><strong style="color: var(--success);"><?php echo formatMonto($monto_linea); ?></strong></td>
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
                <p>Ingresa un código UNSPSC o nombre de proveedor</p>
                <p style="margin-top: 15px; font-size: 0.95rem; color: #6c757d;">
                    <strong>Ejemplos:</strong><br>
                    76111501 (código de 8 dígitos) - <em>detecta automáticamente como CÓDIGO</em><br>
                    7611150190033785 (código completo) - <em>detecta automáticamente como CÓDIGO</em><br>
                    3-101-123456 (cédula) - <em>detecta automáticamente como PROVEEDOR</em><br>
                    Nombre Empresa - <em>detecta automáticamente como PROVEEDOR</em>
                </p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
