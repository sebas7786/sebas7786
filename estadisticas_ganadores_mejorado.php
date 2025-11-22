<?php
/**
 * ═══════════════════════════════════════════════════════════════════════
 * INTELIGENCIA COMPETITIVA - Versión Mejorada
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

// Normalizar conexión
if (!isset($pdo) && isset($conn)) {
    $pdo = $conn;
}

// MODO DEBUG
$DEBUG_MODE = true;
$debug_info = [];

// Parámetros de búsqueda
$codigo_buscar = isset($_GET['codigo']) ? trim($_GET['codigo']) : '';
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';

$datos_encontrados = false;
$adjudicaciones = [];
$stats = [];

// FUNCIÓN DE AUTO-DETECCIÓN
function detectarTipoBusqueda($texto) {
    $texto = trim($texto);

    // Si es solo números y tiene 8-16 dígitos → Código UNSPSC
    if (preg_match('/^[0-9]{8,16}$/', $texto)) {
        return 'codigo';
    }

    // Si tiene formato de cédula → Proveedor
    if (preg_match('/^[0-9]-[0-9]{3,4}-[0-9]{4,6}$/', $texto)) {
        return 'proveedor';
    }

    // Si es solo números pero corto → Proveedor
    if (preg_match('/^[0-9]{1,7}$/', $texto)) {
        return 'proveedor';
    }

    // Si tiene letras → Proveedor
    if (preg_match('/[a-zA-Z]/', $texto)) {
        return 'proveedor';
    }

    return 'codigo';
}

if (!empty($codigo_buscar)) {
    try {
        $debug_info[] = "🔍 Búsqueda iniciada para: " . $codigo_buscar;

        // Verificar conexión
        if (!isset($pdo)) {
            $debug_info[] = "❌ ERROR: No hay conexión a la base de datos";
            throw new Exception("No hay conexión a la base de datos");
        }

        $debug_info[] = "✅ Conexión a BD establecida";

        // AUTO-DETECTAR tipo
        $modo_vista = detectarTipoBusqueda($codigo_buscar);
        $debug_info[] = "🤖 Tipo detectado: " . strtoupper($modo_vista);

        // PASO 1: Verificar tablas existentes
        $debug_info[] = "📊 Verificando tablas...";

        try {
            $check_tables = $pdo->query("SHOW TABLES");
            $tables = $check_tables->fetchAll(PDO::FETCH_COLUMN);
            $debug_info[] = "✅ Tablas encontradas: " . count($tables);

            $tabla_adjudicadas = in_array('lineas_adjudicadas', $tables);
            $tabla_licitaciones = in_array('licitaciones', $tables);
            $tabla_proveedoras = in_array('proveedoras', $tables);
            $tabla_instituciones = in_array('instituciones_compradoras', $tables);

            $debug_info[] = "   - lineas_adjudicadas: " . ($tabla_adjudicadas ? "✅" : "❌");
            $debug_info[] = "   - licitaciones: " . ($tabla_licitaciones ? "✅" : "❌");
            $debug_info[] = "   - proveedoras: " . ($tabla_proveedoras ? "✅" : "❌");
            $debug_info[] = "   - instituciones_compradoras: " . ($tabla_instituciones ? "✅" : "❌");

        } catch (Exception $e) {
            $debug_info[] = "⚠️ No se pudieron verificar las tablas: " . $e->getMessage();
        }

        // PASO 2: Consulta SIMPLE sin JOINs complicados
        if ($modo_vista === 'codigo') {
            $codigo_8 = substr($codigo_buscar, 0, 8);

            $debug_info[] = "🔎 Buscando código: " . $codigo_buscar;
            $debug_info[] = "   - Código 8 dígitos: " . $codigo_8;

            // Consulta MUY SIMPLE - Solo tabla principal
            $sql = "SELECT * FROM lineas_adjudicadas
                    WHERE codigo_producto LIKE :codigo
                    OR codigo_producto LIKE :codigo_parcial
                    OR SUBSTRING(codigo_producto, 1, 8) = :codigo_8
                    ORDER BY id DESC
                    LIMIT 500";

            $params = [
                ':codigo' => '%' . $codigo_buscar . '%',
                ':codigo_parcial' => $codigo_8 . '%',
                ':codigo_8' => $codigo_8
            ];

        } else {
            // Búsqueda por proveedor
            $debug_info[] = "🔎 Buscando proveedor: " . $codigo_buscar;

            $sql = "SELECT * FROM lineas_adjudicadas
                    WHERE cedula_proveedor LIKE :codigo
                    ORDER BY id DESC
                    LIMIT 500";

            $params = [':codigo' => '%' . $codigo_buscar . '%'];
        }

        // Filtros de fecha
        if (!empty($fecha_desde)) {
            $sql = str_replace("ORDER BY", "AND fecha_adjudicacion >= :fecha_desde ORDER BY", $sql);
            $params[':fecha_desde'] = $fecha_desde;
        }

        if (!empty($fecha_hasta)) {
            $sql = str_replace("ORDER BY", "AND fecha_adjudicacion <= :fecha_hasta ORDER BY", $sql);
            $params[':fecha_hasta'] = $fecha_hasta;
        }

        $debug_info[] = "📝 SQL: " . preg_replace('/\s+/', ' ', $sql);
        $debug_info[] = "📝 Parámetros: " . json_encode($params);

        // EJECUTAR CONSULTA
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $adjudicaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $debug_info[] = "✅ Resultados: " . count($adjudicaciones) . " adjudicaciones";

        // Si NO hay resultados, hacer diagnóstico
        if (empty($adjudicaciones)) {
            $debug_info[] = "⚠️ NO se encontraron resultados. Ejecutando diagnóstico...";

            // Contar total de registros en la tabla
            $count_stmt = $pdo->query("SELECT COUNT(*) as total FROM lineas_adjudicadas");
            $count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
            $debug_info[] = "📊 Total registros en lineas_adjudicadas: " . $count_result['total'];

            if ($count_result['total'] == 0) {
                $debug_info[] = "❌ La tabla lineas_adjudicadas está VACÍA";
            } else {
                // Mostrar ejemplos de códigos/proveedores en la BD
                if ($modo_vista === 'codigo') {
                    $ejemplos_stmt = $pdo->query("SELECT DISTINCT codigo_producto
                                                   FROM lineas_adjudicadas
                                                   WHERE codigo_producto IS NOT NULL
                                                   LIMIT 10");
                    $ejemplos = $ejemplos_stmt->fetchAll(PDO::FETCH_COLUMN);

                    if (!empty($ejemplos)) {
                        $debug_info[] = "💡 Ejemplos de códigos en la BD:";
                        foreach ($ejemplos as $ej) {
                            $debug_info[] = "   - " . $ej;
                        }
                    }

                    // Buscar códigos similares
                    $similar_stmt = $pdo->prepare("SELECT DISTINCT codigo_producto
                                                    FROM lineas_adjudicadas
                                                    WHERE codigo_producto LIKE :codigo
                                                    LIMIT 5");
                    $similar_stmt->execute([':codigo' => substr($codigo_buscar, 0, 4) . '%']);
                    $similares = $similar_stmt->fetchAll(PDO::FETCH_COLUMN);

                    if (!empty($similares)) {
                        $debug_info[] = "💡 Códigos similares encontrados:";
                        foreach ($similares as $sim) {
                            $debug_info[] = "   - " . $sim;
                        }
                    }

                } else {
                    $ejemplos_stmt = $pdo->query("SELECT DISTINCT cedula_proveedor
                                                   FROM lineas_adjudicadas
                                                   WHERE cedula_proveedor IS NOT NULL
                                                   LIMIT 10");
                    $ejemplos = $ejemplos_stmt->fetchAll(PDO::FETCH_COLUMN);

                    if (!empty($ejemplos)) {
                        $debug_info[] = "💡 Ejemplos de cédulas de proveedores:";
                        foreach ($ejemplos as $ej) {
                            $debug_info[] = "   - " . $ej;
                        }
                    }
                }
            }
        } else {
            $datos_encontrados = true;

            // CALCULAR ESTADÍSTICAS
            $debug_info[] = "📊 Calculando estadísticas...";

            $precios = [];
            $cantidades = [];
            $monto_total = 0;
            $proveedores_unicos = [];

            foreach ($adjudicaciones as $adj) {
                $precio = floatval($adj['precio_unitario_adjudicado'] ?? 0);
                $cantidad = floatval($adj['cantidad_adjudicada'] ?? 0);

                if ($precio > 0) {
                    $precios[] = $precio;
                }

                if ($cantidad > 0) {
                    $cantidades[] = $cantidad;
                }

                $monto_total += ($precio * $cantidad);

                $cedula = $adj['cedula_proveedor'] ?? 'N/A';
                if (!in_array($cedula, $proveedores_unicos)) {
                    $proveedores_unicos[] = $cedula;
                }
            }

            $stats = [
                'total_adjudicaciones' => count($adjudicaciones),
                'precio_minimo' => !empty($precios) ? min($precios) : 0,
                'precio_maximo' => !empty($precios) ? max($precios) : 0,
                'precio_promedio' => !empty($precios) ? array_sum($precios) / count($precios) : 0,
                'cantidad_promedio' => !empty($cantidades) ? array_sum($cantidades) / count($cantidades) : 0,
                'monto_total' => $monto_total,
                'proveedores_unicos' => count($proveedores_unicos)
            ];

            $debug_info[] = "✅ Estadísticas calculadas correctamente";
        }

    } catch (PDOException $e) {
        $debug_info[] = "❌ ERROR SQL: " . $e->getMessage();
        $debug_info[] = "   Código error: " . $e->getCode();
        error_log("Error en estadísticas_ganadores: " . $e->getMessage());
    } catch (Exception $e) {
        $debug_info[] = "❌ ERROR GENERAL: " . $e->getMessage();
        error_log("Error general en estadísticas_ganadores: " . $e->getMessage());
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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #0056b3;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --purple: #8b5cf6;
            --orange: #f97316;
            --teal: #14b8a6;
            --text-dark: #1a1a1a;
            --text-light: #6c757d;
            --border: #dee2e6;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
        }

        .page-header {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }

        .page-header h1 {
            font-size: 2rem;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .page-header p {
            color: var(--text-light);
            font-size: 1rem;
        }

        .badge-auto {
            display: inline-block;
            background: var(--purple);
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 8px;
        }

        .debug-panel {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            max-height: 500px;
            overflow-y: auto;
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
            padding: 4px;
            border-bottom: 1px solid #ffeeba;
            word-wrap: break-word;
        }

        .search-panel {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .search-form {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 6px;
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 0.95rem;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: #004494;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid;
        }

        .stat-card.purple { border-left-color: var(--purple); }
        .stat-card.blue { border-left-color: var(--primary); }
        .stat-card.green { border-left-color: var(--success); }
        .stat-card.orange { border-left-color: var(--orange); }
        .stat-card.teal { border-left-color: var(--teal); }

        .stat-icon {
            font-size: 1.5rem;
            margin-bottom: 8px;
        }

        .stat-card.purple .stat-icon { color: var(--purple); }
        .stat-card.blue .stat-icon { color: var(--primary); }
        .stat-card.green .stat-icon { color: var(--success); }
        .stat-card.orange .stat-icon { color: var(--orange); }
        .stat-card.teal .stat-icon { color: var(--teal); }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 4px;
        }

        .stat-label {
            color: var(--text-light);
            font-size: 0.85rem;
            font-weight: 500;
        }

        .empty-state {
            background: white;
            border-radius: 12px;
            padding: 60px 30px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--border);
            margin-bottom: 15px;
        }

        .empty-state h3 {
            color: var(--text-dark);
            margin-bottom: 8px;
            font-size: 1.3rem;
        }

        .empty-state p {
            color: var(--text-light);
            font-size: 1rem;
        }

        .data-table {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: var(--primary);
            color: white;
        }

        th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid var(--border);
            font-size: 0.9rem;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        @media (max-width: 768px) {
            .search-form {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1>
                <i class="fas fa-chart-line"></i> Inteligencia Competitiva
                <span class="badge-auto">🤖 AUTO</span>
            </h1>
            <p>Detecta automáticamente código UNSPSC o proveedor</p>
        </div>

        <?php if ($DEBUG_MODE && !empty($debug_info)): ?>
        <div class="debug-panel">
            <h3><i class="fas fa-bug"></i> Debug (<?php echo count($debug_info); ?> mensajes)</h3>
            <ul>
                <?php foreach ($debug_info as $info): ?>
                    <li><?php echo htmlspecialchars($info); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="search-panel">
            <form method="GET" class="search-form">
                <div class="form-group">
                    <label>
                        <i class="fas fa-search"></i>
                        Buscar (Código UNSPSC o Proveedor)
                    </label>
                    <input type="text"
                           name="codigo"
                           placeholder="Ej: 76111501 o 3-101-123456"
                           value="<?php echo htmlspecialchars($codigo_buscar); ?>"
                           required>
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
                <p>No hay adjudicaciones para "<?php echo htmlspecialchars($codigo_buscar); ?>"</p>
                <p style="margin-top: 15px; font-size: 0.9rem; color: #6c757d;">
                    <strong>Revisa el panel de debug arriba</strong> para más detalles
                </p>
            </div>

        <?php elseif ($datos_encontrados): ?>
            <!-- Estadísticas -->
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
                    <div class="stat-label">Proveedores</div>
                </div>
            </div>

            <!-- Tabla -->
            <div class="data-table">
                <h3>
                    <i class="fas fa-list"></i>
                    Adjudicaciones (<?php echo count($adjudicaciones); ?> registros)
                </h3>
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>SICOP</th>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th>Proveedor</th>
                            <th>Cantidad</th>
                            <th>Precio Unit.</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($adjudicaciones, 0, 100) as $adj):
                            $monto = floatval($adj['precio_unitario_adjudicado'] ?? 0) * floatval($adj['cantidad_adjudicada'] ?? 0);
                        ?>
                        <tr>
                            <td><?php echo $adj['fecha_adjudicacion'] ?? 'N/A'; ?></td>
                            <td><strong><?php echo htmlspecialchars($adj['numero_sicop'] ?? 'N/A'); ?></strong></td>
                            <td><code><?php echo htmlspecialchars($adj['codigo_producto'] ?? 'N/A'); ?></code></td>
                            <td><?php echo htmlspecialchars(substr($adj['descripcion'] ?? 'N/A', 0, 40)); ?></td>
                            <td><?php echo htmlspecialchars($adj['cedula_proveedor'] ?? 'N/A'); ?></td>
                            <td><?php echo number_format($adj['cantidad_adjudicada'] ?? 0, 2); ?></td>
                            <td><strong><?php echo formatMonto($adj['precio_unitario_adjudicado'] ?? 0); ?></strong></td>
                            <td><strong style="color: var(--success);"><?php echo formatMonto($monto); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (count($adjudicaciones) > 100): ?>
                <p style="text-align: center; margin-top: 15px; color: var(--text-light);">
                    Mostrando 100 de <?php echo count($adjudicaciones); ?> resultados
                </p>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-search-dollar"></i>
                <h3>Comienza tu Análisis</h3>
                <p>Ingresa un código UNSPSC o cédula de proveedor</p>
                <p style="margin-top: 15px; font-size: 0.9rem; color: #6c757d;">
                    <strong>Ejemplos:</strong><br>
                    76111501 - Código UNSPSC<br>
                    3-101-123456 - Cédula de proveedor
                </p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
