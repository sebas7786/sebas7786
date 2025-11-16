<?php
/**
 * DASHBOARD MEJORADO - LicitaHoy
 *
 * Mejoras:
 * - Cálculo correcto del estado (abierta/cerrada/adjudicada) basado en fechas
 * - Detección automática de adjudicaciones desde tabla lineas_adjudicadas
 * - Visualización prominente del presupuesto
 * - Estadísticas en tiempo real
 * - Mejor UX y diseño
 */

// Activar reporte de errores para diagnóstico
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario_id']) && !isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Obtener el ID del usuario
$usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : $_SESSION['user_id'];

// ═══════════════════════════════════════════════════════════════════════
// FUNCIONES MEJORADAS
// ═══════════════════════════════════════════════════════════════════════

// Calcular estado REAL de la licitación basado en fechas y adjudicaciones
function calcularEstadoReal($licitacion) {
    $fecha_actual = new DateTime();

    // 1. VERIFICAR SI ESTÁ ADJUDICADA (desde tabla lineas_adjudicadas)
    if (!empty($licitacion['tiene_adjudicaciones']) && $licitacion['tiene_adjudicaciones'] > 0) {
        return 'adjudicada';
    }

    // 2. Verificar campo fecha_adjudicacion o estado en BD
    if (!empty($licitacion['fecha_adjudicacion']) || strtolower($licitacion['estado'] ?? '') === 'adjudicada') {
        return 'adjudicada';
    }

    // 3. Obtener fecha de cierre
    $fecha_cierre = null;
    if (!empty($licitacion['fecha_cierre_recepcion'])) {
        $fecha_cierre = $licitacion['fecha_cierre_recepcion'];
    } elseif (!empty($licitacion['fecha_cierre_ofertas'])) {
        $fecha_cierre = $licitacion['fecha_cierre_ofertas'];
    } elseif (!empty($licitacion['fecha_cierre'])) {
        $fecha_cierre = $licitacion['fecha_cierre'];
    }

    // 4. Si no tiene fecha de cierre, usar estado de BD
    if (empty($fecha_cierre)) {
        return strtolower($licitacion['estado'] ?? 'abierta');
    }

    // 5. Comparar fecha de cierre con fecha actual
    try {
        $dt_cierre = new DateTime($fecha_cierre);

        if ($fecha_actual > $dt_cierre) {
            return 'cerrada';
        } else {
            return 'abierta';
        }
    } catch (Exception $e) {
        return strtolower($licitacion['estado'] ?? 'abierta');
    }
}

// Calcular días restantes
function diasRestantes($licitacion) {
    $fecha_cierre = null;

    if (!empty($licitacion['fecha_cierre_recepcion'])) {
        $fecha_cierre = $licitacion['fecha_cierre_recepcion'];
    } elseif (!empty($licitacion['fecha_cierre_ofertas'])) {
        $fecha_cierre = $licitacion['fecha_cierre_ofertas'];
    } elseif (!empty($licitacion['fecha_cierre'])) {
        $fecha_cierre = $licitacion['fecha_cierre'];
    }

    if (empty($fecha_cierre)) {
        return null;
    }

    try {
        $hoy = new DateTime();
        $cierre = new DateTime($fecha_cierre);
        $intervalo = $hoy->diff($cierre);

        if ($intervalo->invert) {
            return -1; // Ya cerró
        }

        return $intervalo->days;
    } catch (Exception $e) {
        error_log("Error al calcular días restantes: " . $e->getMessage());
        return null;
    }
}

// Formatear fecha
function formatearFecha($fecha) {
    if (empty($fecha)) return null;
    try {
        $dt = new DateTime($fecha);
        return $dt->format('d/m/Y');
    } catch (Exception $e) {
        return null;
    }
}

// Obtener fecha de cierre
function obtenerFechaCierre($licitacion) {
    if (!empty($licitacion['fecha_cierre_recepcion'])) {
        return formatearFecha($licitacion['fecha_cierre_recepcion']);
    } elseif (!empty($licitacion['fecha_cierre_ofertas'])) {
        return formatearFecha($licitacion['fecha_cierre_ofertas']);
    } elseif (!empty($licitacion['fecha_cierre'])) {
        return formatearFecha($licitacion['fecha_cierre']);
    }
    return null;
}

// Formatear montos de forma inteligente
function formatMontoMejorado($monto) {
    if (empty($monto) || !is_numeric($monto)) {
        return null;
    }

    $monto = floatval($monto);

    // Corregir valores multiplicados por 1,000,000
    if ($monto > 100000000000) {
        $monto = $monto / 1000000;
    }

    // Formato con millones para montos grandes
    if ($monto >= 1000000) {
        $millones = $monto / 1000000;
        return '₡' . number_format($millones, 1, '.', ',') . 'M';
    }

    // Formato estándar para montos menores
    if ($monto >= 1000) {
        return '₡' . number_format($monto, 0, '.', ',');
    }

    return '₡' . number_format($monto, 2, '.', ',');
}

// Obtener clase CSS según monto
function getMontoClase($monto) {
    if (empty($monto)) return '';

    $monto = floatval($monto);
    if ($monto > 100000000000) {
        $monto = $monto / 1000000;
    }

    if ($monto >= 100000000) return 'monto-alto'; // ₡100M+
    if ($monto >= 10000000) return 'monto-medio'; // ₡10M+
    return 'monto-bajo'; // < ₡10M
}

// ═══════════════════════════════════════════════════════════════════════
// CONEXIÓN A BD Y LÓGICA PRINCIPAL
// ═══════════════════════════════════════════════════════════════════════

try {
    require_once __DIR__ . '/../config/db.php';

    if (!isset($pdo) && isset($conn)) {
        $pdo = $conn;
    }

} catch (Exception $e) {
    die("Error de conexión a la base de datos.");
}

try {
    // OBTENER INFORMACIÓN DEL USUARIO
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        session_destroy();
        header('Location: ../login.php');
        exit;
    }

    // PROCESAR FILTROS
    $filtro_categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';
    $filtro_institucion = isset($_GET['institucion']) ? $_GET['institucion'] : '';
    $filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : 'abierta';
    $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
    $orden = isset($_GET['orden']) ? $_GET['orden'] : 'relevancia';
    $vista = isset($_GET['vista']) ? $_GET['vista'] : 'mis_intereses';

    // Paginación
    $pagina_actual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
    $resultados_por_pagina = 12;
    $offset = ($pagina_actual - 1) * $resultados_por_pagina;

    // OBTENER INTERESES DEL USUARIO
    $codigos_interes = [];
    $palabras_clave = [];

    if (!empty($usuario['intereses'])) {
        $intereses_raw = explode(',', $usuario['intereses']);
        foreach ($intereses_raw as $interes) {
            $interes = trim($interes);
            if (empty($interes)) continue;

            // Si es numérico y tiene al menos 8 dígitos, es un código
            if (is_numeric($interes) && strlen($interes) >= 8) {
                $codigo_8 = substr($interes, 0, 8);
                $codigos_interes[] = $codigo_8;
            } else {
                // Es una palabra clave
                $palabras_clave[] = $interes;
            }
        }
    }

    // Remover duplicados
    $codigos_interes = array_unique($codigos_interes);
    $palabras_clave = array_unique($palabras_clave);

    // CONSTRUIR CONSULTA SQL CON DETECCIÓN DE ADJUDICACIONES
    $params = [];

    $sql = "SELECT DISTINCT l.id, l.*,
            ic.nombre_institucion,
            ic.provincia as zona_geografica,
            COUNT(DISTINCT la.id) as tiene_adjudicaciones
            FROM licitaciones l
            LEFT JOIN instituciones_compradoras ic ON l.cedula_institucion = ic.cedula
            LEFT JOIN lineas_adjudicadas la ON la.numero_sicop = l.numero_sicop";

    // Si estamos en vista de intereses Y hay intereses configurados
    if ($vista === 'mis_intereses' && (!empty($codigos_interes) || !empty($palabras_clave))) {
        $sql .= " LEFT JOIN partidas_licitacion pl ON pl.licitacion_id = l.id";
    }

    $sql .= " WHERE 1=1";

    // FILTROS DE INTERESES - Solo si estamos en vista "mis_intereses"
    if ($vista === 'mis_intereses' && (!empty($codigos_interes) || !empty($palabras_clave))) {
        $sql .= " AND (";
        $condiciones_interes = [];

        // Filtro por códigos (primeros 8 dígitos)
        if (!empty($codigos_interes)) {
            $placeholders_codigos = implode(',', array_fill(0, count($codigos_interes), '?'));
            $condiciones_interes[] = "SUBSTRING(COALESCE(pl.codigo, pl.codigo_identificacion, ''), 1, 8) IN ($placeholders_codigos)";
            $params = array_merge($params, $codigos_interes);
        }

        // Filtro por palabras clave
        if (!empty($palabras_clave)) {
            foreach ($palabras_clave as $palabra) {
                $condiciones_interes[] = "(l.titulo LIKE ? OR l.descripcion LIKE ? OR pl.nombre LIKE ? OR pl.descripcion LIKE ?)";
                $palabra_param = '%' . $palabra . '%';
                $params[] = $palabra_param;
                $params[] = $palabra_param;
                $params[] = $palabra_param;
                $params[] = $palabra_param;
            }
        }

        $sql .= implode(' OR ', $condiciones_interes);
        $sql .= ")";
    }

    // Filtros adicionales
    if ($filtro_estado !== 'todas') {
        // NO filtrar por estado en BD, lo calcularemos después
    }

    if (!empty($filtro_categoria)) {
        $sql .= " AND l.categoria = ?";
        $params[] = $filtro_categoria;
    }

    if (!empty($filtro_institucion)) {
        $sql .= " AND l.cedula_institucion = ?";
        $params[] = $filtro_institucion;
    }

    if (!empty($busqueda)) {
        $sql .= " AND (l.numero_procedimiento LIKE ? OR l.numero_sicop LIKE ? OR l.titulo LIKE ? OR l.descripcion LIKE ?)";
        $busqueda_param = '%' . $busqueda . '%';
        $params[] = $busqueda_param;
        $params[] = $busqueda_param;
        $params[] = $busqueda_param;
        $params[] = $busqueda_param;
    }

    // GROUP BY para el COUNT de adjudicaciones
    $sql .= " GROUP BY l.id";

    // Ordenamiento
    switch ($orden) {
        case 'fecha_desc':
        case 'relevancia':
        default:
            $sql .= " ORDER BY l.fecha_publicacion DESC";
            break;
        case 'fecha_asc':
            $sql .= " ORDER BY l.fecha_publicacion ASC";
            break;
        case 'cierre_asc':
            $sql .= " ORDER BY COALESCE(l.fecha_cierre_recepcion, l.fecha_cierre_ofertas, l.fecha_cierre) ASC";
            break;
        case 'presupuesto_desc':
            $sql .= " ORDER BY l.presupuesto_estimado DESC";
            break;
    }

    // Ejecutar consulta SIN paginación para calcular estadísticas
    $stmt_all = $pdo->prepare($sql);
    $stmt_all->execute($params);
    $todas_licitaciones = $stmt_all->fetchAll();

    // Calcular estadísticas REALES
    $total_abiertas = 0;
    $total_cerradas = 0;
    $total_adjudicadas = 0;

    foreach ($todas_licitaciones as $lic) {
        $estado_real = calcularEstadoReal($lic);
        switch ($estado_real) {
            case 'abierta':
                $total_abiertas++;
                break;
            case 'cerrada':
                $total_cerradas++;
                break;
            case 'adjudicada':
                $total_adjudicadas++;
                break;
        }
    }

    // Filtrar por estado si es necesario
    if ($filtro_estado !== 'todas') {
        $todas_licitaciones = array_filter($todas_licitaciones, function($lic) use ($filtro_estado) {
            return calcularEstadoReal($lic) === $filtro_estado;
        });
    }

    $total_resultados = count($todas_licitaciones);
    $total_paginas = $total_resultados > 0 ? ceil($total_resultados / $resultados_por_pagina) : 0;

    // Aplicar paginación
    $licitaciones_mostrar = array_slice($todas_licitaciones, $offset, $resultados_por_pagina);

    // OBTENER CATEGORÍAS E INSTITUCIONES
    $stmt = $pdo->query("
        SELECT DISTINCT categoria
        FROM licitaciones
        WHERE categoria IS NOT NULL AND categoria != ''
        ORDER BY categoria
    ");
    $categorias_disponibles = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $pdo->query("
        SELECT DISTINCT ic.cedula, ic.nombre_institucion
        FROM instituciones_compradoras ic
        INNER JOIN licitaciones l ON l.cedula_institucion = ic.cedula
        ORDER BY ic.nombre_institucion
        LIMIT 200
    ");
    $instituciones_disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error en dashboard: " . $e->getMessage());
    $error_message = "Ha ocurrido un error al cargar el dashboard.";
    $licitaciones_mostrar = [];
    $total_resultados = 0;
    $total_paginas = 0;
}

include_once "../includes/header.php";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - LicitaHoy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #0056b3;
            --primary-light: #1e73d8;
            --text-dark: #1a1a1a;
            --text-light: #6c757d;
            --border: #dee2e6;
            --bg-gray: #f8f9fa;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --monto-alto: #7c3aed;
            --monto-medio: #2563eb;
            --monto-bajo: #10b981;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f0f2f5;
            color: var(--text-dark);
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .page-header h1 {
            font-size: 1.8rem;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .page-header p {
            color: var(--text-light);
            font-size: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }

        .stat-number {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .stat-card.abiertas .stat-number { color: var(--success); }
        .stat-card.cerradas .stat-number { color: var(--danger); }
        .stat-card.adjudicadas .stat-number { color: #8b5cf6; }

        .alert-info {
            background: #d1ecf1;
            border-left: 4px solid #0c5460;
            color: #0c5460;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-info i {
            font-size: 1.5rem;
        }

        .alert-warning {
            background: #fff3cd;
            border-left: 4px solid #856404;
            color: #856404;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-warning i {
            font-size: 1.5rem;
        }

        .view-tabs {
            display: flex;
            gap: 12px;
            margin-bottom: 25px;
        }

        .view-tab {
            flex: 1;
            background: white;
            padding: 16px 24px;
            border-radius: 12px;
            text-align: center;
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 500;
            transition: all 0.2s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 2px solid transparent;
        }

        .view-tab:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
            color: var(--primary);
        }

        .view-tab.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .view-tab i {
            margin-right: 8px;
        }

        .filters-panel {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .filters-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border);
        }

        .filters-header h3 {
            color: var(--text-dark);
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 18px;
        }

        .filter-group label {
            display: block;
            font-weight: 500;
            color: var(--text-dark);
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.95rem;
            transition: border-color 0.2s;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: var(--primary);
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-light);
        }

        .btn-outline {
            background: white;
            color: var(--text-dark);
            border: 1px solid var(--border);
        }

        .btn-outline:hover {
            background: var(--bg-gray);
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .licitacion-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
            border: 1px solid var(--border);
        }

        .licitacion-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }

        .card-header {
            padding: 20px;
            border-bottom: 1px solid var(--border);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-badge.abierta {
            background: #d1f4e0;
            color: #047857;
        }

        .status-badge.cerrada {
            background: #fee2e2;
            color: #b91c1c;
        }

        .status-badge.adjudicada {
            background: #f5f3ff;
            color: #7c3aed;
            border: 2px solid #8b5cf6;
        }

        .card-title {
            font-size: 1.05rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 50px;
        }

        .card-subtitle {
            color: var(--text-light);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .card-body {
            padding: 20px;
            flex-grow: 1;
        }

        .info-row {
            display: flex;
            margin-bottom: 14px;
            font-size: 0.9rem;
            align-items: flex-start;
        }

        .info-row i {
            color: var(--primary);
            width: 18px;
            margin-right: 10px;
            margin-top: 3px;
            flex-shrink: 0;
        }

        .info-content {
            flex: 1;
            min-width: 0;
        }

        .info-label {
            font-size: 0.75rem;
            color: var(--text-light);
            margin-bottom: 3px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-weight: 500;
            color: var(--text-dark);
            word-wrap: break-word;
        }

        /* Estilos mejorados para presupuesto */
        .presupuesto-destacado {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .presupuesto-destacado .info-label {
            color: rgba(255,255,255,0.9);
            font-weight: 600;
            margin-bottom: 8px;
        }

        .presupuesto-destacado .info-value {
            color: white !important;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .presupuesto-destacado i {
            color: white !important;
            font-size: 1.2rem;
        }

        .monto-alto .presupuesto-destacado {
            background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%);
        }

        .monto-medio .presupuesto-destacado {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        }

        .monto-bajo .presupuesto-destacado {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .card-footer {
            padding: 18px 20px;
            background: var(--bg-gray);
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .footer-info {
            flex: 1;
            min-width: 0;
        }

        .countdown {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 4px;
        }

        .countdown.urgent { color: var(--danger); }
        .countdown.warning { color: var(--warning); }
        .countdown.normal { color: var(--success); }
        .countdown.secondary { color: var(--text-light); }

        .fecha-cierre {
            font-size: 0.8rem;
            color: var(--text-light);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .empty-state {
            background: white;
            border-radius: 12px;
            padding: 60px 30px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--border);
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: var(--text-dark);
            margin-bottom: 10px;
        }

        .empty-state p {
            color: var(--text-light);
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin: 30px 0;
        }

        .pagination a,
        .pagination span {
            min-width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border: 1px solid var(--border);
            border-radius: 8px;
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 500;
            transition: all 0.2s;
            padding: 0 12px;
        }

        .pagination a:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .pagination .active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        @media (max-width: 768px) {
            .cards-grid {
                grid-template-columns: 1fr;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .view-tabs {
                flex-direction: column;
            }

            .card-footer {
                flex-direction: column;
                align-items: stretch;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1>Bienvenido, <?php echo htmlspecialchars($usuario['nombre'] ?? 'Usuario'); ?></h1>
            <p>
                <?php if ($vista === 'mis_intereses'): ?>
                    Licitaciones personalizadas según tus intereses
                <?php else: ?>
                    Explora todas las licitaciones disponibles
                <?php endif; ?>
            </p>
        </div>

        <?php if ($vista === 'mis_intereses' && (empty($codigos_interes) && empty($palabras_clave))): ?>
        <div class="alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <strong>No has configurado tus intereses</strong><br>
                <a href="profile.php" style="color: inherit; text-decoration: underline;">Haz clic aquí para agregar códigos UNSPSC o palabras clave</a> y recibir recomendaciones personalizadas.
            </div>
        </div>
        <?php endif; ?>

        <?php if ($vista === 'mis_intereses' && (!empty($codigos_interes) || !empty($palabras_clave))): ?>
        <div class="alert-info">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>Mostrando licitaciones según tus intereses:</strong><br>
                <?php if (!empty($codigos_interes)): ?>
                    <span>Códigos UNSPSC: <?php echo implode(', ', $codigos_interes); ?></span><br>
                <?php endif; ?>
                <?php if (!empty($palabras_clave)): ?>
                    <span>Palabras clave: <?php echo implode(', ', $palabras_clave); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card abiertas">
                <div class="stat-number"><?php echo $total_abiertas; ?></div>
                <div class="stat-label">Abiertas</div>
            </div>

            <div class="stat-card cerradas">
                <div class="stat-number"><?php echo $total_cerradas; ?></div>
                <div class="stat-label">Cerradas</div>
            </div>

            <div class="stat-card adjudicadas">
                <div class="stat-number"><?php echo $total_adjudicadas; ?></div>
                <div class="stat-label">Adjudicadas</div>
            </div>

            <div class="stat-card">
                <div class="stat-number"><?php echo $total_resultados; ?></div>
                <div class="stat-label">Resultados</div>
            </div>
        </div>

        <div class="view-tabs">
            <a href="?vista=mis_intereses" class="view-tab <?php echo $vista === 'mis_intereses' ? 'active' : ''; ?>">
                <i class="fas fa-star"></i>
                Recomendadas para ti
            </a>
            <a href="?vista=todas" class="view-tab <?php echo $vista === 'todas' ? 'active' : ''; ?>">
                <i class="fas fa-list"></i>
                Todas las Licitaciones
            </a>
        </div>

        <div class="filters-panel">
            <div class="filters-header">
                <h3>
                    <i class="fas fa-filter"></i>
                    Filtros
                </h3>
                <a href="?vista=<?php echo $vista; ?>" class="btn btn-outline btn-sm">
                    <i class="fas fa-times"></i>
                    Limpiar
                </a>
            </div>

            <form method="GET">
                <input type="hidden" name="vista" value="<?php echo $vista; ?>">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label>Buscar</label>
                        <input type="text" name="busqueda" placeholder="Número o título..." value="<?php echo htmlspecialchars($busqueda); ?>">
                    </div>

                    <div class="filter-group">
                        <label>Estado</label>
                        <select name="estado">
                            <option value="todas" <?php echo $filtro_estado === 'todas' ? 'selected' : ''; ?>>Todas</option>
                            <option value="abierta" <?php echo $filtro_estado === 'abierta' ? 'selected' : ''; ?>>Abiertas</option>
                            <option value="cerrada" <?php echo $filtro_estado === 'cerrada' ? 'selected' : ''; ?>>Cerradas</option>
                            <option value="adjudicada" <?php echo $filtro_estado === 'adjudicada' ? 'selected' : ''; ?>>Adjudicadas</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Categoría</label>
                        <select name="categoria">
                            <option value="">Todas</option>
                            <?php foreach ($categorias_disponibles as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $filtro_categoria === $cat ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Institución</label>
                        <select name="institucion">
                            <option value="">Todas</option>
                            <?php foreach ($instituciones_disponibles as $inst): ?>
                                <option value="<?php echo htmlspecialchars($inst['cedula']); ?>" <?php echo $filtro_institucion === $inst['cedula'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(substr($inst['nombre_institucion'], 0, 40)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Ordenar</label>
                        <select name="orden">
                            <option value="fecha_desc" <?php echo $orden === 'fecha_desc' ? 'selected' : ''; ?>>Más recientes</option>
                            <option value="cierre_asc" <?php echo $orden === 'cierre_asc' ? 'selected' : ''; ?>>Por cerrar</option>
                            <option value="presupuesto_desc" <?php echo $orden === 'presupuesto_desc' ? 'selected' : ''; ?>>Mayor presupuesto</option>
                        </select>
                    </div>

                    <div class="filter-group" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-search"></i>
                            Buscar
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <?php if (empty($licitaciones_mostrar)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>No se encontraron licitaciones</h3>
                <p>
                    <?php if ($vista === 'mis_intereses'): ?>
                        No hay licitaciones que coincidan con tus intereses.<br>
                        <a href="profile.php">Ajusta tus intereses</a> o <a href="?vista=todas">explora todas las licitaciones</a>.
                    <?php else: ?>
                        Intenta ajustar los filtros de búsqueda.
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <div class="cards-grid">
                <?php foreach ($licitaciones_mostrar as $lic):
                    $estado_real = calcularEstadoReal($lic);
                    $dias = diasRestantes($lic);
                    $fecha_cierre_formatted = obtenerFechaCierre($lic);
                    $clase_countdown = 'normal';
                    $icono_countdown = 'calendar';
                    $texto_countdown = "Sin fecha";

                    if ($dias !== null) {
                        if ($dias < 0) {
                            $texto_countdown = "Cerrada";
                            $clase_countdown = "secondary";
                            $icono_countdown = 'times-circle';
                        } elseif ($dias == 0) {
                            $texto_countdown = "¡Cierra hoy!";
                            $clase_countdown = "urgent";
                            $icono_countdown = 'exclamation-circle';
                        } elseif ($dias <= 3) {
                            $texto_countdown = "$dias día" . ($dias != 1 ? "s" : "") . " restantes";
                            $clase_countdown = "urgent";
                            $icono_countdown = 'exclamation-circle';
                        } elseif ($dias <= 7) {
                            $texto_countdown = "$dias días restantes";
                            $clase_countdown = "warning";
                            $icono_countdown = 'clock';
                        } else {
                            $texto_countdown = "$dias días restantes";
                            $icono_countdown = 'clock';
                        }
                    }

                    $presupuesto_formatted = formatMontoMejorado($lic['presupuesto_estimado']);
                    $clase_monto = getMontoClase($lic['presupuesto_estimado']);
                ?>
                    <div class="licitacion-card <?php echo $clase_monto; ?>">
                        <div class="card-header">
                            <span class="status-badge <?php echo $estado_real; ?>">
                                <?php
                                $iconos = [
                                    'abierta' => 'circle-check',
                                    'cerrada' => 'circle-xmark',
                                    'adjudicada' => 'trophy'
                                ];
                                echo '<i class="fas fa-' . ($iconos[$estado_real] ?? 'circle') . '"></i> ';
                                echo ucfirst($estado_real);
                                ?>
                            </span>
                            <div class="card-title"><?php echo htmlspecialchars($lic['titulo'] ?? 'Sin título'); ?></div>
                            <div class="card-subtitle">
                                <i class="fas fa-hashtag"></i>
                                <?php echo htmlspecialchars($lic['numero_procedimiento'] ?? $lic['numero_sicop'] ?? 'N/A'); ?>
                            </div>
                        </div>

                        <div class="card-body">
                            <?php if ($presupuesto_formatted): ?>
                            <div class="presupuesto-destacado info-row">
                                <i class="fas fa-coins"></i>
                                <div class="info-content">
                                    <div class="info-label">Presupuesto Estimado</div>
                                    <div class="info-value"><?php echo $presupuesto_formatted; ?></div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="info-row">
                                <i class="fas fa-building"></i>
                                <div class="info-content">
                                    <div class="info-label">Institución</div>
                                    <div class="info-value"><?php echo htmlspecialchars(substr($lic['nombre_institucion'] ?? $lic['institucion'] ?? 'No disponible', 0, 45)); ?></div>
                                </div>
                            </div>

                            <?php if (!empty($lic['zona_geografica'])): ?>
                            <div class="info-row">
                                <i class="fas fa-map-marker-alt"></i>
                                <div class="info-content">
                                    <div class="info-label">Ubicación</div>
                                    <div class="info-value"><?php echo htmlspecialchars($lic['zona_geografica']); ?></div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($lic['categoria'])): ?>
                            <div class="info-row">
                                <i class="fas fa-tag"></i>
                                <div class="info-content">
                                    <div class="info-label">Categoría</div>
                                    <div class="info-value"><?php echo htmlspecialchars($lic['categoria']); ?></div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="card-footer">
                            <div class="footer-info">
                                <div class="countdown <?php echo $clase_countdown; ?>">
                                    <i class="fas fa-<?php echo $icono_countdown; ?>"></i>
                                    <?php echo $texto_countdown; ?>
                                </div>
                                <?php if ($fecha_cierre_formatted): ?>
                                <div class="fecha-cierre">
                                    <i class="far fa-calendar"></i>
                                    Cierre: <?php echo $fecha_cierre_formatted; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <a href="licitacion_detalle.php?id=<?php echo $lic['id']; ?>" class="btn btn-primary">
                                Ver detalles
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($total_paginas > 1): ?>
            <div class="pagination">
                <?php if ($pagina_actual > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina_actual - 1])); ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>

                <?php for ($i = max(1, $pagina_actual - 2); $i <= min($total_paginas, $pagina_actual + 2); $i++): ?>
                    <?php if ($i == $pagina_actual): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($pagina_actual < $total_paginas): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina_actual + 1])); ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
