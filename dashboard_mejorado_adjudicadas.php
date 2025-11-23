<?php
/**
 * DASHBOARD MEJORADO - LicitacionesYA
 * Versión con detección mejorada de licitaciones adjudicadas
 */

// Activar reporte de errores para diagnóstico
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Iniciar sesión
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
// MODO DEBUG (cambiar a false en producción)
// ═══════════════════════════════════════════════════════════════════════
$DEBUG_MODE = isset($_GET['debug']) && $_GET['debug'] === '1';

// ═══════════════════════════════════════════════════════════════════════
// FUNCIONES MEJORADAS
// ═══════════════════════════════════════════════════════════════════════

/**
 * Calcular estado REAL de la licitación con múltiples métodos de detección
 */
function calcularEstadoRealMejorado($licitacion, $pdo = null) {
    global $DEBUG_MODE;

    $fecha_actual = new DateTime();

    // MÉTODO 1: Verificar si tiene adjudicación en tabla lineas_adjudicadas
    if ($pdo && !empty($licitacion['numero_sicop'])) {
        try {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as tiene_adjudicacion
                FROM lineas_adjudicadas
                WHERE numero_sicop = ? OR numero_procedimiento = ?
                LIMIT 1
            ");
            $stmt->execute([$licitacion['numero_sicop'], $licitacion['numero_sicop']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && $result['tiene_adjudicacion'] > 0) {
                if ($DEBUG_MODE) {
                    error_log("Licitación {$licitacion['id']}: Adjudicada por tabla lineas_adjudicadas");
                }
                return 'adjudicada';
            }
        } catch (PDOException $e) {
            // Tabla no existe, continuar con otros métodos
        }
    }

    // MÉTODO 2: Verificar si tiene fecha_adjudicacion
    if (!empty($licitacion['fecha_adjudicacion'])) {
        if ($DEBUG_MODE) {
            error_log("Licitación {$licitacion['id']}: Adjudicada por fecha_adjudicacion");
        }
        return 'adjudicada';
    }

    // MÉTODO 3: Verificar columna adjudicada (booleano)
    if (isset($licitacion['adjudicada']) && $licitacion['adjudicada'] == 1) {
        if ($DEBUG_MODE) {
            error_log("Licitación {$licitacion['id']}: Adjudicada por columna adjudicada=1");
        }
        return 'adjudicada';
    }

    // MÉTODO 4: Verificar estado con variaciones
    $estado_lower = isset($licitacion['estado']) ? strtolower(trim($licitacion['estado'])) : '';

    $estados_adjudicados = [
        'adjudicada',
        'adjudicado',
        'adjudicación',
        'adjudicacion',
        'finalizada',
        'concluida'
    ];

    foreach ($estados_adjudicados as $estado_adj) {
        if ($estado_lower === $estado_adj || strpos($estado_lower, $estado_adj) !== false) {
            if ($DEBUG_MODE) {
                error_log("Licitación {$licitacion['id']}: Adjudicada por estado='{$estado_lower}'");
            }
            return 'adjudicada';
        }
    }

    // MÉTODO 5: Obtener fecha de cierre
    $fecha_cierre = null;
    if (!empty($licitacion['fecha_cierre_recepcion'])) {
        $fecha_cierre = $licitacion['fecha_cierre_recepcion'];
    } elseif (!empty($licitacion['fecha_cierre_ofertas'])) {
        $fecha_cierre = $licitacion['fecha_cierre_ofertas'];
    } elseif (!empty($licitacion['fecha_cierre'])) {
        $fecha_cierre = $licitacion['fecha_cierre'];
    }

    // Si no tiene fecha de cierre, usar estado de BD
    if (empty($fecha_cierre)) {
        $estados_abiertos = ['abierta', 'abierto', 'activa', 'activo', 'vigente'];
        $estados_cerrados = ['cerrada', 'cerrado', 'vencida', 'vencido'];

        if (in_array($estado_lower, $estados_abiertos)) {
            return 'abierta';
        } elseif (in_array($estado_lower, $estados_cerrados)) {
            return 'cerrada';
        }

        // Por defecto abierta si no sabemos
        return 'abierta';
    }

    // Comparar fecha de cierre con fecha actual
    try {
        $dt_cierre = new DateTime($fecha_cierre);

        if ($fecha_actual > $dt_cierre) {
            return 'cerrada';
        } else {
            return 'abierta';
        }
    } catch (Exception $e) {
        return $estado_lower ?: 'abierta';
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

// Formatear montos
function formatMontoMejorado($monto) {
    if (empty($monto) || !is_numeric($monto)) {
        return null;
    }

    $monto = floatval($monto);

    // Corregir valores multiplicados por 1,000,000
    if ($monto > 100000000000) {
        $monto = $monto / 1000000;
    }

    // Formato con millones
    if ($monto >= 1000000) {
        $millones = $monto / 1000000;
        return '₡' . number_format($millones, 1, '.', ',') . 'M';
    }

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

    if ($monto >= 100000000) return 'monto-alto';
    if ($monto >= 10000000) return 'monto-medio';
    return 'monto-bajo';
}

// ═══════════════════════════════════════════════════════════════════════
// CONEXIÓN A BD
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

            if (is_numeric($interes) && strlen($interes) >= 8) {
                $codigo_8 = substr($interes, 0, 8);
                $codigos_interes[] = $codigo_8;
            } else {
                $palabras_clave[] = $interes;
            }
        }
    }

    $codigos_interes = array_unique($codigos_interes);
    $palabras_clave = array_unique($palabras_clave);

    // CONSTRUIR CONSULTA SQL
    $params = [];

    $sql = "SELECT DISTINCT l.id, l.*, ic.nombre_institucion, ic.provincia as zona_geografica
            FROM licitaciones l
            LEFT JOIN instituciones_compradoras ic ON l.cedula_institucion = ic.cedula";

    // Si estamos en vista de intereses
    if ($vista === 'mis_intereses' && (!empty($codigos_interes) || !empty($palabras_clave))) {
        $sql .= " LEFT JOIN partidas_licitacion pl ON pl.licitacion_id = l.id";
    }

    $sql .= " WHERE 1=1";

    // FILTROS DE INTERESES
    if ($vista === 'mis_intereses' && (!empty($codigos_interes) || !empty($palabras_clave))) {
        $sql .= " AND (";
        $condiciones_interes = [];

        if (!empty($codigos_interes)) {
            $placeholders_codigos = implode(',', array_fill(0, count($codigos_interes), '?'));
            $condiciones_interes[] = "SUBSTRING(COALESCE(pl.codigo, pl.codigo_identificacion, ''), 1, 8) IN ($placeholders_codigos)";
            $params = array_merge($params, $codigos_interes);
        }

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

    // Ejecutar consulta SIN paginación
    $stmt_all = $pdo->prepare($sql);
    $stmt_all->execute($params);
    $todas_licitaciones = $stmt_all->fetchAll();

    // Calcular estadísticas REALES usando la función mejorada
    $total_abiertas = 0;
    $total_cerradas = 0;
    $total_adjudicadas = 0;

    foreach ($todas_licitaciones as $lic) {
        $estado_real = calcularEstadoRealMejorado($lic, $pdo);
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

    // DEBUG: Log de estadísticas
    if ($DEBUG_MODE) {
        error_log("=== ESTADÍSTICAS CALCULADAS ===");
        error_log("Abiertas: $total_abiertas");
        error_log("Cerradas: $total_cerradas");
        error_log("Adjudicadas: $total_adjudicadas");
        error_log("Filtro estado seleccionado: $filtro_estado");
    }

    // Filtrar por estado si es necesario
    if ($filtro_estado !== 'todas') {
        $todas_licitaciones = array_filter($todas_licitaciones, function($lic) use ($filtro_estado, $pdo, $DEBUG_MODE) {
            $estado_calculado = calcularEstadoRealMejorado($lic, $pdo);

            if ($DEBUG_MODE && $filtro_estado === 'adjudicada') {
                error_log("Licitación ID {$lic['id']}: Estado calculado = $estado_calculado, Filtro = $filtro_estado, Match = " . ($estado_calculado === $filtro_estado ? 'SÍ' : 'NO'));
            }

            return $estado_calculado === $filtro_estado;
        });

        if ($DEBUG_MODE) {
            error_log("Después de filtrar por '$filtro_estado': " . count($todas_licitaciones) . " licitaciones");
        }
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
    <title>Dashboard - LicitacionesYA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Copiar todos los estilos CSS del dashboard original aquí -->
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
        .stat-card.adjudicadas .stat-number { color: var(--text-light); }

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

        /* DEBUG MODE BANNER */
        .debug-banner {
            background: #7c3aed;
            color: white;
            padding: 12px 20px;
            text-align: center;
            font-weight: 600;
            margin-bottom: 20px;
            border-radius: 8px;
        }

        /* ... copiar el resto de CSS del dashboard original ... */

        /* (Por brevedad, incluye todos los estilos CSS del dashboard original aquí) */
    </style>
</head>
<body>
    <div class="container">
        <?php if ($DEBUG_MODE): ?>
        <div class="debug-banner">
            🐛 MODO DEBUG ACTIVADO - Estado adjudicadas: <?php echo $total_adjudicadas; ?> |
            <a href="?<?php echo http_build_query(array_merge($_GET, ['debug' => '0'])); ?>" style="color: white; text-decoration: underline;">Desactivar</a>
        </div>
        <?php endif; ?>

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

        <!-- EL RESTO DEL HTML ES IDÉNTICO AL DASHBOARD ORIGINAL -->
        <!-- Por brevedad, incluye todo el HTML del dashboard original aquí -->

        <p style="text-align: center; margin-top: 40px; color: #6c757d;">
            💡 <strong>Tip:</strong> Agrega <code>?debug=1</code> al final de la URL para ver logs de detección de adjudicadas
        </p>
    </div>
</body>
</html>
