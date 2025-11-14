<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

// Check if user is logged in
require_login();

// Get licitacion ID from URL
$licitacion_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($licitacion_id <= 0) {
    header('Location: dashboard.php');
    exit;
}

// Get licitacion details
try {
    $stmt = $conn->prepare("
        SELECT l.*,
               (SELECT COUNT(*) FROM alertas WHERE id_usuario = :user_id AND id_licitacion = l.id AND favorito = 1) as es_favorito,
               ir.nombre_institucion,
               ir.zona_geografica
        FROM licitaciones l
        LEFT JOIN instituciones_registradas ir ON l.cedula_institucion = ir.cedula
        WHERE l.id = :id
    ");
    $stmt->bindParam(':id', $licitacion_id);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();

    if ($stmt->rowCount() == 0) {
        header('Location: dashboard.php');
        exit;
    }

    $licitacion = $stmt->fetch();

    // ═══════════════════════════════════════════════════════════════════════
    // ⭐ CORREGIDO: Query actualizada para v14.2
    // ═══════════════════════════════════════════════════════════════════════
    $partidas = [];
    $tabla_partidas_existe = false;

    try {
        $check_table = $conn->query("SHOW TABLES LIKE 'partidas_licitacion'");
        if ($check_table->rowCount() > 0) {
            $tabla_partidas_existe = true;

            // ⭐ NUEVA QUERY: Sin GROUP BY, con los campos que realmente importa v14.2
            $query = "SELECT
                        partida,
                        linea,
                        codigo_identificacion,
                        cantidad,
                        precio_unitario,
                        monto_estimado,
                        moneda,
                        tipo_cambio_usd,
                        nombre,
                        descripcion,
                        unidad
                      FROM partidas_licitacion
                      WHERE licitacion_id = :licitacion_id
                      ORDER BY partida ASC, linea ASC";
            $stmt_partidas = $conn->prepare($query);
            $stmt_partidas->bindParam(':licitacion_id', $licitacion_id);
            $stmt_partidas->execute();
            $partidas = $stmt_partidas->fetchAll(PDO::FETCH_ASSOC);
        }

    } catch (PDOException $e) {
        error_log("Error al consultar partidas: " . $e->getMessage());
    }

    // Obtener fechas por etapas
    $fechas_etapas = [];
    try {
        $check_table = $conn->query("SHOW TABLES LIKE 'fechas_por_etapas'");
        if ($check_table->rowCount() > 0 && !empty($licitacion['numero_sicop'])) {
            $query = "SELECT * FROM fechas_por_etapas WHERE nro_sicop = :numero_sicop ORDER BY partida ASC, linea ASC LIMIT 1";
            $stmt_fechas = $conn->prepare($query);
            $stmt_fechas->bindParam(':numero_sicop', $licitacion['numero_sicop']);
            $stmt_fechas->execute();
            $fechas_etapas = $stmt_fechas->fetch(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log("Error al consultar fechas_por_etapas: " . $e->getMessage());
    }

    // Obtener sistema de evaluación
    $sistema_evaluacion = [];
    try {
        $check_table = $conn->query("SHOW TABLES LIKE 'sistema_evaluacion_ofertas'");
        if ($check_table->rowCount() > 0 && !empty($licitacion['numero_sicop'])) {
            $query = "SELECT * FROM sistema_evaluacion_ofertas WHERE nro_sicop = :numero_sicop ORDER BY eval_item_seqno ASC";
            $stmt_evaluacion = $conn->prepare($query);
            $stmt_evaluacion->bindParam(':numero_sicop', $licitacion['numero_sicop']);
            $stmt_evaluacion->execute();
            $sistema_evaluacion = $stmt_evaluacion->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log("Error al consultar sistema_evaluacion_ofertas: " . $e->getMessage());
    }

    // Obtener adjudicaciones - CORREGIDO: usar columnas correctas de la BD
    $adjudicaciones = [];
    try {
        $check_table = $conn->query("SHOW TABLES LIKE 'lineas_adjudicadas'");
        if ($check_table->rowCount() > 0 && !empty($licitacion['numero_sicop'])) {
            // ⭐ USAR: numero_sicop, numero_linea, numero_oferta, numero_acto, monto_adjudicado
            $query = "SELECT * FROM lineas_adjudicadas WHERE numero_sicop = :numero_sicop ORDER BY numero_linea ASC";
            $stmt_adj = $conn->prepare($query);
            $stmt_adj->bindParam(':numero_sicop', $licitacion['numero_sicop']);
            $stmt_adj->execute();
            $adjudicaciones = $stmt_adj->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log("Error al consultar adjudicaciones: " . $e->getMessage());
    }

    // Obtener ofertas - CORREGIDO: usar tabla correcta
    $ofertas = [];
    try {
        $check_table = $conn->query("SHOW TABLES LIKE 'lineas_ofertadas'");
        if ($check_table->rowCount() > 0 && !empty($licitacion['numero_sicop'])) {
            $query = "SELECT * FROM lineas_ofertadas WHERE nro_sicop = :numero_sicop ORDER BY nro_oferta ASC, nro_linea ASC";
            $stmt_ofertas = $conn->prepare($query);
            $stmt_ofertas->bindParam(':numero_sicop', $licitacion['numero_sicop']);
            $stmt_ofertas->execute();
            $ofertas = $stmt_ofertas->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log("Error al consultar ofertas: " . $e->getMessage());
    }

    // Obtener recepciones - CORREGIDO: usar tabla correcta
    $recepciones = [];
    try {
        $check_table = $conn->query("SHOW TABLES LIKE 'recepciones'");
        if ($check_table->rowCount() > 0 && !empty($licitacion['numero_sicop'])) {
            $query = "SELECT * FROM recepciones WHERE nro_sicop = :numero_sicop ORDER BY fecha_solicitud_recepcion DESC";
            $stmt_rec = $conn->prepare($query);
            $stmt_rec->bindParam(':numero_sicop', $licitacion['numero_sicop']);
            $stmt_rec->execute();
            $recepciones = $stmt_rec->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log("Error al consultar recepciones: " . $e->getMessage());
    }

    // Obtener recursos de objeción - CORREGIDO: usar tabla correcta
    $recursos = [];
    try {
        $check_table = $conn->query("SHOW TABLES LIKE 'recursos_objecion'");
        if ($check_table->rowCount() > 0 && !empty($licitacion['numero_sicop'])) {
            $query = "SELECT * FROM recursos_objecion WHERE nro_sicop = :numero_sicop ORDER BY fecha_recurso DESC";
            $stmt_recursos = $conn->prepare($query);
            $stmt_recursos->bindParam(':numero_sicop', $licitacion['numero_sicop']);
            $stmt_recursos->execute();
            $recursos = $stmt_recursos->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log("Error al consultar recursos: " . $e->getMessage());
    }

} catch (PDOException $e) {
    die("Error al obtener detalles de la licitación: " . $e->getMessage());
}

// Handle favorite toggle
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['toggle_favorite'])) {
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT id, favorito FROM alertas WHERE id_usuario = :user_id AND id_licitacion = :licitacion_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':licitacion_id', $licitacion_id);
    $stmt->execute();
    $alert = $stmt->fetch();

    if ($alert) {
        $new_favorite = $alert['favorito'] ? 0 : 1;
        $stmt = $conn->prepare("UPDATE alertas SET favorito = :favorito WHERE id = :id");
        $stmt->bindParam(':favorito', $new_favorite);
        $stmt->bindParam(':id', $alert['id']);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO alertas (id_usuario, id_licitacion, favorito) VALUES (:user_id, :licitacion_id, 1)");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':licitacion_id', $licitacion_id);
        $stmt->execute();
    }

    header('Location: licitacion_detalle.php?id=' . $licitacion_id);
    exit;
}

// Determine status
$estado = "abierta";
$estado_texto = "En recepción de ofertas";
$fecha_actual = new DateTime();

$fecha_cierre_str = null;
if (!empty($fechas_etapas['fecha_apertura'])) {
    $fecha_cierre_str = $fechas_etapas['fecha_apertura'];
} elseif (!empty($licitacion['fecha_cierre_ofertas'])) {
    $fecha_cierre_str = $licitacion['fecha_cierre_ofertas'];
} elseif (!empty($licitacion['fecha_cierre'])) {
    $fecha_cierre_str = $licitacion['fecha_cierre'];
}

if ($fecha_cierre_str) {
    try {
        $fecha_cierre = new DateTime($fecha_cierre_str);

        if ($fecha_actual > $fecha_cierre) {
            $estado = "cerrada";
            $estado_texto = "Cerrada";
        } else {
            $diff = $fecha_actual->diff($fecha_cierre);
            $dias_restantes = $diff->days;

            if ($dias_restantes <= 3) {
                $estado = "proximo";
                $estado_texto = "Próxima a cerrar";
            }
        }
    } catch (Exception $e) {
        error_log("Error al procesar fecha de cierre: " . $e->getMessage());
    }
}

if (!empty($fechas_etapas['adjudicacion_firme']) || !empty($adjudicaciones)) {
    $estado = "adjudicada";
    $estado_texto = "Adjudicada";
}

function formatearFechaHora($fecha) {
    if (empty($fecha)) return '-';
    try {
        $dt = new DateTime($fecha);
        return $dt->format('d/m/Y H:i');
    } catch (Exception $e) {
        return '-';
    }
}

function formatearFecha($fecha) {
    if (empty($fecha)) return '-';
    try {
        $dt = new DateTime($fecha);
        return $dt->format('d/m/Y');
    } catch (Exception $e) {
        return '-';
    }
}

include '../includes/header.php';
?>

<style>
:root {
    --primary-blue: #0056b3;
    --light-blue: #e6f0ff;
    --border-color: #dee2e6;
    --text-color: #333;
    --bg-light: #f8f9fa;
    --success-green: #28a745;
    --warning-orange: #fd7e14;
    --danger-red: #dc3545;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f5f5f5;
    color: var(--text-color);
}

.container-licitacion {
    max-width: 1400px;
    margin: 20px auto;
    padding: 0 15px;
}

.breadcrumb-sicop {
    background-color: white;
    padding: 12px 20px;
    margin-bottom: 20px;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
}

.breadcrumb-sicop a {
    color: var(--primary-blue);
    text-decoration: none;
}

.breadcrumb-sicop a:hover {
    text-decoration: underline;
}

.breadcrumb-sicop .separator {
    color: #666;
}

.licitacion-header-box {
    background-color: white;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border-left: 4px solid var(--primary-blue);
}

.licitacion-header-box h1 {
    font-size: 20px;
    font-weight: 600;
    color: var(--text-color);
    margin: 0 0 15px 0;
    line-height: 1.4;
}

.licitacion-actions-bar {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 15px;
}

.btn-sicop {
    padding: 8px 16px;
    border: 1px solid var(--border-color);
    background-color: white;
    color: var(--text-color);
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-sicop:hover {
    background-color: var(--bg-light);
}

.btn-sicop.btn-primary {
    background-color: var(--primary-blue);
    color: white;
    border-color: var(--primary-blue);
}

.btn-sicop.btn-primary:hover {
    background-color: #004494;
}

.btn-favorite {
    color: #ffc107;
}

.btn-favorite.active {
    background-color: #fff3cd;
}

.sicop-section {
    background-color: white;
    margin-bottom: 20px;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    overflow: hidden;
}

.sicop-section-header {
    background-color: var(--light-blue);
    padding: 12px 20px;
    font-weight: 600;
    font-size: 15px;
    color: var(--text-color);
    border-bottom: 2px solid #b8daff;
}

.sicop-section-body {
    padding: 20px;
}

.info-table {
    width: 100%;
    border-collapse: collapse;
}

.info-table tr {
    border-bottom: 1px solid var(--border-color);
}

.info-table tr:last-child {
    border-bottom: none;
}

.info-table td {
    padding: 12px 15px;
    font-size: 14px;
    vertical-align: top;
}

.info-table td:first-child {
    font-weight: 600;
    color: #555;
    width: 35%;
    background-color: #fafafa;
}

.info-table td:last-child {
    color: var(--text-color);
}

.partidas-table-sicop {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    margin-top: 15px;
}

.partidas-table-sicop thead {
    background-color: var(--light-blue);
}

.partidas-table-sicop th {
    padding: 10px 8px;
    text-align: left;
    font-weight: 600;
    color: var(--text-color);
    border: 1px solid #b8daff;
    font-size: 13px;
}

.partidas-table-sicop td {
    padding: 10px 8px;
    border: 1px solid var(--border-color);
    vertical-align: top;
}

.partidas-table-sicop tbody tr:nth-child(even) {
    background-color: #fafafa;
}

.partidas-table-sicop tbody tr:hover {
    background-color: #f0f8ff;
}

.estado-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 600;
}

.estado-badge.abierta {
    background-color: #d4edda;
    color: #155724;
}

.estado-badge.cerrada {
    background-color: #f8d7da;
    color: #721c24;
}

.estado-badge.proximo {
    background-color: #fff3cd;
    color: #856404;
}

.estado-badge.adjudicada {
    background-color: #d1ecf1;
    color: #0c5460;
}

.ofertas-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    margin-top: 10px;
}

.ofertas-table thead {
    background-color: var(--light-blue);
}

.ofertas-table th {
    padding: 8px;
    text-align: left;
    font-weight: 600;
    border: 1px solid #b8daff;
    font-size: 12px;
}

.ofertas-table td {
    padding: 8px;
    border: 1px solid var(--border-color);
}

.ofertas-table tbody tr:nth-child(even) {
    background-color: #fafafa;
}

.text-right {
    text-align: right;
}

.text-center {
    text-align: center;
}

/* ⭐ NUEVO: Alerta para campos faltantes */
.campo-faltante {
    color: #999;
    font-style: italic;
    font-size: 12px;
}

.info-note {
    background-color: #fff3cd;
    border-left: 4px solid #ffc107;
    padding: 12px 15px;
    margin-bottom: 15px;
    font-size: 13px;
    color: #856404;
}

@media (max-width: 768px) {
    .info-table td:first-child {
        width: 40%;
    }

    .partidas-table-sicop {
        font-size: 12px;
    }

    .partidas-table-sicop th,
    .partidas-table-sicop td {
        padding: 8px 6px;
    }
}

@media print {
    .licitacion-actions-bar,
    .breadcrumb-sicop,
    .btn-sicop,
    .info-note {
        display: none !important;
    }

    .sicop-section {
        box-shadow: none;
        border: 1px solid #ddd;
        page-break-inside: avoid;
    }
}
</style>

<div class="container-licitacion">
    <div class="breadcrumb-sicop">
        <a href="dashboard.php"><i class="fas fa-home"></i> Inicio</a>
        <span class="separator">›</span>
        <span>Detalle de licitación</span>
    </div>

    <div class="licitacion-header-box">
        <h1><?php echo htmlspecialchars($licitacion['titulo']); ?></h1>

        <div class="licitacion-actions-bar">
            <form method="POST" style="display: inline-block;">
                <input type="hidden" name="toggle_favorite" value="1">
                <button type="submit" class="btn-sicop btn-favorite <?php echo $licitacion['es_favorito'] ? 'active' : ''; ?>">
                    <i class="fas fa-star"></i>
                    <?php echo $licitacion['es_favorito'] ? 'En favoritos' : 'Añadir a favoritos'; ?>
                </button>
            </form>

            <?php if (!empty($licitacion['enlace_documento'])): ?>
            <a href="<?php echo htmlspecialchars($licitacion['enlace_documento']); ?>" target="_blank" class="btn-sicop btn-primary">
                <i class="fas fa-file-pdf"></i> Ver documento
            </a>
            <?php endif; ?>

            <button onclick="window.print()" class="btn-sicop">
                <i class="fas fa-print"></i> Imprimir
            </button>

            <a href="dashboard.php" class="btn-sicop">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <!-- Sección 1: Información General -->
    <div class="sicop-section">
        <div class="sicop-section-header">
            [ 1. Información general ]
        </div>
        <div class="sicop-section-body">
            <table class="info-table">
                <tr>
                    <td>Estado del concurso</td>
                    <td>
                        <span class="estado-badge <?php echo $estado; ?>">
                            <?php echo $estado_texto; ?>
                        </span>
                    </td>
                </tr>

                <?php if (!empty($licitacion['numero_sicop'])): ?>
                <tr>
                    <td>Número de SICOP</td>
                    <td><strong><?php echo htmlspecialchars($licitacion['numero_sicop']); ?></strong></td>
                </tr>
                <?php endif; ?>

                <?php if (!empty($licitacion['numero_procedimiento'])): ?>
                <tr>
                    <td>Número de procedimiento</td>
                    <td><strong><?php echo htmlspecialchars($licitacion['numero_procedimiento']); ?></strong></td>
                </tr>
                <?php endif; ?>

                <?php if (!empty($licitacion['nombre_institucion'])): ?>
                <tr>
                    <td>Nombre de la institución</td>
                    <td><?php echo htmlspecialchars($licitacion['nombre_institucion']); ?></td>
                </tr>
                <?php elseif (!empty($licitacion['institucion'])): ?>
                <tr>
                    <td>Nombre de la institución</td>
                    <td><?php echo htmlspecialchars($licitacion['institucion']); ?></td>
                </tr>
                <?php endif; ?>

                <?php if (!empty($licitacion['cedula_institucion'])): ?>
                <tr>
                    <td>Cédula de la institución</td>
                    <td><?php echo htmlspecialchars($licitacion['cedula_institucion']); ?></td>
                </tr>
                <?php endif; ?>

                <?php if (!empty($licitacion['zona_geografica'])): ?>
                <tr>
                    <td>Ubicación</td>
                    <td><?php echo htmlspecialchars($licitacion['zona_geografica']); ?></td>
                </tr>
                <?php endif; ?>

                <tr>
                    <td>Descripción del procedimiento</td>
                    <td><?php echo nl2br(htmlspecialchars($licitacion['descripcion'])); ?></td>
                </tr>

                <?php if (!empty($licitacion['clasificacion_objeto'])): ?>
                <tr>
                    <td>Clasificación del objeto</td>
                    <td><?php echo htmlspecialchars($licitacion['clasificacion_objeto']); ?></td>
                </tr>
                <?php endif; ?>

                <?php if (!empty($licitacion['tipo_procedimiento'])): ?>
                <tr>
                    <td>Tipo de procedimiento</td>
                    <td><?php echo htmlspecialchars($licitacion['tipo_procedimiento']); ?></td>
                </tr>
                <?php endif; ?>

                <?php if (!empty($licitacion['modalidad'])): ?>
                <tr>
                    <td>Tipo de modalidad</td>
                    <td><?php echo htmlspecialchars($licitacion['modalidad']); ?></td>
                </tr>
                <?php endif; ?>

                <?php if (!empty($licitacion['codigo_excepcion'])): ?>
                <tr>
                    <td>Código de excepción</td>
                    <td><?php echo htmlspecialchars($licitacion['codigo_excepcion']); ?></td>
                </tr>
                <?php endif; ?>

                <?php if (!empty($licitacion['descripcion_excepcion'])): ?>
                <tr>
                    <td>Descripción de excepción</td>
                    <td><?php echo htmlspecialchars($licitacion['descripcion_excepcion']); ?></td>
                </tr>
                <?php endif; ?>

                <?php if (!empty($licitacion['presupuesto']) || !empty($licitacion['presupuesto_estimado'])): ?>
                <tr>
                    <td>Presupuesto total estimado</td>
                    <td>
                        <strong>
                        <?php
                        $presupuesto = !empty($licitacion['presupuesto_estimado']) ? $licitacion['presupuesto_estimado'] : $licitacion['presupuesto'];

                        if (is_numeric($presupuesto)) {
                            $presupuesto = floatval($presupuesto);

                            if ($presupuesto > 100000000000) {
                                $presupuesto = $presupuesto / 1000000;
                            }

                            echo '₡' . number_format($presupuesto, 2, '.', ',');
                        } else {
                            echo htmlspecialchars($presupuesto);
                        }

                        if (!empty($licitacion['moneda'])) {
                            echo ' [' . htmlspecialchars($licitacion['moneda']) . ']';
                        }
                        ?>
                        </strong>
                    </td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- Sección 2: Fechas Importantes -->
    <div class="sicop-section">
        <div class="sicop-section-header">
            [ 2. Fechas Importantes del Proceso ]
        </div>
        <div class="sicop-section-body">
            <table class="info-table">
                <tr>
                    <td>Inicio de recepción de ofertas</td>
                    <td>
                        <?php
                        if (!empty($fechas_etapas['fecha_publicacion'])) {
                            echo '<strong>' . formatearFechaHora($fechas_etapas['fecha_publicacion']) . '</strong>';
                        } elseif (!empty($licitacion['fecha_publicacion'])) {
                            $dt = formatearFecha($licitacion['fecha_publicacion']);
                            if (!empty($licitacion['hora_publicacion'])) {
                                $dt .= ' ' . $licitacion['hora_publicacion'];
                            }
                            echo '<strong>' . $dt . '</strong>';
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                </tr>

                <tr>
                    <td>Cierre de recepción de ofertas</td>
                    <td>
                        <?php
                        if (!empty($licitacion['fecha_cierre_recepcion'])) {
                            echo '<strong style="color: #dc3545;">' . formatearFechaHora($licitacion['fecha_cierre_recepcion']) . '</strong>';
                        } elseif (!empty($fechas_etapas['fecha_apertura'])) {
                            echo '<strong style="color: #dc3545;">' . formatearFechaHora($fechas_etapas['fecha_apertura']) . '</strong>';
                        } elseif (!empty($licitacion['fecha_cierre_ofertas'])) {
                            $dt = formatearFecha($licitacion['fecha_cierre_ofertas']);
                            if (!empty($licitacion['hora_cierre_ofertas'])) {
                                $dt .= ' ' . $licitacion['hora_cierre_ofertas'];
                            }
                            echo '<strong style="color: #dc3545;">' . $dt . '</strong>';
                        } elseif (!empty($licitacion['fecha_cierre'])) {
                            $dt = formatearFecha($licitacion['fecha_cierre']);
                            if (!empty($licitacion['hora_cierre'])) {
                                $dt .= ' ' . $licitacion['hora_cierre'];
                            }
                            echo '<strong style="color: #dc3545;">' . $dt . '</strong>';
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                </tr>

                <tr>
                    <td>Fecha/hora de apertura de ofertas</td>
                    <td>
                        <?php
                        if (!empty($fechas_etapas['fecha_apertura'])) {
                            echo '<strong style="color: #0056b3;">' . formatearFechaHora($fechas_etapas['fecha_apertura']) . '</strong>';
                        } elseif (!empty($licitacion['fecha_apertura_ofertas'])) {
                            echo '<strong style="color: #0056b3;">' . formatearFechaHora($licitacion['fecha_apertura_ofertas']) . '</strong>';
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                </tr>

                <tr>
                    <td>Fecha/hora límite de recepción de objeciones</td>
                    <td>
                        <?php
                        if (!empty($licitacion['fecha_limite_objeciones'])) {
                            $dt = formatearFecha($licitacion['fecha_limite_objeciones']);
                            if (!empty($licitacion['hora_limite_objeciones'])) {
                                $dt .= ' ' . $licitacion['hora_limite_objeciones'];
                            }
                            echo '<strong style="color: #fd7e14;">' . $dt . '</strong>';
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Sección: Adjudicaciones -->
    <?php if (!empty($adjudicaciones)): ?>
    <div class="sicop-section">
        <div class="sicop-section-header">
            [ 3. Líneas Adjudicadas ]
        </div>
        <div class="sicop-section-body">
            <p style="margin-bottom: 15px;"><strong>Total de líneas adjudicadas:</strong> <?php echo count($adjudicaciones); ?></p>
            <table class="ofertas-table">
                <thead>
                    <tr>
                        <th>Línea</th>
                        <th>Código Producto</th>
                        <th>N° Oferta</th>
                        <th>Cédula Proveedor</th>
                        <th>Cantidad</th>
                        <th class="text-right">Precio Unit.</th>
                        <th class="text-right">Monto Adjudicado</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total_adjudicado = 0;
                    foreach ($adjudicaciones as $adj):
                        $total_adjudicado += floatval($adj['monto_adjudicado'] ?? 0);
                    ?>
                    <tr>
                        <td class="text-center"><?php echo htmlspecialchars($adj['numero_linea'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($adj['codigo_producto'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($adj['numero_oferta'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($adj['cedula_proveedor'] ?? '-'); ?></td>
                        <td class="text-right"><?php echo number_format($adj['cantidad_adjudicada'] ?? 0, 2); ?></td>
                        <td class="text-right">₡<?php echo number_format($adj['precio_unitario_adjudicado'] ?? 0, 2); ?></td>
                        <td class="text-right"><strong>₡<?php echo number_format($adj['monto_adjudicado'] ?? 0, 2); ?></strong></td>
                        <td><span class="estado-badge abierta"><?php echo htmlspecialchars($adj['estado'] ?? '-'); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="background-color: #e6f0ff; font-weight: bold;">
                        <td colspan="6" class="text-right">TOTAL ADJUDICADO:</td>
                        <td class="text-right">₡<?php echo number_format($total_adjudicado, 2); ?></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Sección: Ofertas -->
    <?php if (!empty($ofertas)): ?>
    <div class="sicop-section">
        <div class="sicop-section-header">
            [ 4. Ofertas Presentadas ]
        </div>
        <div class="sicop-section-body">
            <p style="margin-bottom: 15px;"><strong>Total de ofertas:</strong> <?php echo count($ofertas); ?></p>
            <table class="ofertas-table">
                <thead>
                    <tr>
                        <th>N° Oferta</th>
                        <th>Línea</th>
                        <th>Código Producto</th>
                        <th>Cantidad</th>
                        <th class="text-right">Precio Unit.</th>
                        <th class="text-right">Monto Línea</th>
                        <th>Moneda</th>
                        <th class="text-center">Descuento %</th>
                        <th class="text-center">IVA %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ofertas as $oferta): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($oferta['nro_oferta'] ?? '-'); ?></td>
                        <td class="text-center"><?php echo htmlspecialchars($oferta['nro_linea'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($oferta['codigo_producto'] ?? '-'); ?></td>
                        <td class="text-right"><?php echo number_format($oferta['cantidad_ofertada'] ?? 0, 2); ?></td>
                        <td class="text-right">₡<?php echo number_format($oferta['precio_unitario_ofertado'] ?? 0, 2); ?></td>
                        <td class="text-right"><strong>₡<?php echo number_format($oferta['monto_linea_ofertado'] ?? 0, 2); ?></strong></td>
                        <td class="text-center"><?php echo htmlspecialchars($oferta['tipo_moneda'] ?? 'CRC'); ?></td>
                        <td class="text-center"><?php echo number_format($oferta['descuento'] ?? 0, 2); ?>%</td>
                        <td class="text-center"><?php echo number_format($oferta['iva'] ?? 0, 2); ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Sección: Recepciones -->
    <?php if (!empty($recepciones)): ?>
    <div class="sicop-section">
        <div class="sicop-section-header">
            [ 5. Recepciones ]
        </div>
        <div class="sicop-section-body">
            <p style="margin-bottom: 15px;"><strong>Total de recepciones:</strong> <?php echo count($recepciones); ?></p>
            <table class="ofertas-table">
                <thead>
                    <tr>
                        <th>N° Contrato</th>
                        <th>Fecha Recepción</th>
                        <th>Proveedor</th>
                        <th>Tipo</th>
                        <th class="text-right">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total_recepcion = 0;
                    foreach ($recepciones as $rec):
                        $total_recepcion += floatval($rec['monto_recepcion'] ?? 0);
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($rec['nro_contrato'] ?? '-'); ?></td>
                        <td><?php echo formatearFecha($rec['fecha_solicitud_recepcion'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($rec['cedula_proveedor'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($rec['tipo_recepcion'] ?? '-'); ?></td>
                        <td class="text-right"><strong>₡<?php echo number_format($rec['monto_recepcion'] ?? 0, 2); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="background-color: #e6f0ff; font-weight: bold;">
                        <td colspan="4" class="text-right">TOTAL RECIBIDO:</td>
                        <td class="text-right">₡<?php echo number_format($total_recepcion, 2); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Sección: Recursos -->
    <?php if (!empty($recursos)): ?>
    <div class="sicop-section">
        <div class="sicop-section-header">
            [ 6. Recursos de Objeción ]
        </div>
        <div class="sicop-section-body">
            <p style="margin-bottom: 15px;"><strong>Total de recursos:</strong> <?php echo count($recursos); ?></p>
            <table class="ofertas-table">
                <thead>
                    <tr>
                        <th>N° Recurso</th>
                        <th>Proveedor</th>
                        <th>Tipo</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recursos as $rec): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($rec['nro_recurso'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($rec['cedula_proveedor'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($rec['tipo_recurso'] ?? '-'); ?></td>
                        <td><?php echo formatearFecha($rec['fecha_recurso'] ?? ''); ?></td>
                        <td><span class="estado-badge abierta"><?php echo htmlspecialchars($rec['estado_recurso'] ?? '-'); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- ⭐ SECCIÓN 7: PARTIDAS - CORREGIDA PARA v14.2 -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <?php if ($tabla_partidas_existe && !empty($partidas)): ?>
    <div class="sicop-section">
        <div class="sicop-section-header">
            [ 7. Información de bien, servicio u obra ]
        </div>
        <div class="sicop-section-body">
            <p style="margin-bottom: 15px;"><strong>Total de líneas:</strong> <?php echo count($partidas); ?></p>

            <?php
            // Verificar si hay partidas con nombres
            $tiene_nombres = false;
            foreach ($partidas as $p) {
                if (!empty($p['nombre']) || !empty($p['descripcion'])) {
                    $tiene_nombres = true;
                    break;
                }
            }

            // Mostrar nota si faltan nombres
            if (!$tiene_nombres):
            ?>
            <div class="info-note">
                <strong>ℹ️ Información:</strong> Los nombres y descripciones de las partidas se importan desde el archivo <strong>"DetalleLineaCartel.csv"</strong>.
                Actualmente solo se muestran códigos, cantidades y precios importados desde "Detalle de Carteles.csv".
            </div>
            <?php endif; ?>

            <table class="partidas-table-sicop">
                <thead>
                    <tr>
                        <th>Partida</th>
                        <th>Línea</th>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th class="text-right">Cantidad</th>
                        <th>Unidad</th>
                        <th class="text-right">Precio Unit.</th>
                        <th class="text-right">Monto Estimado</th>
                        <th>Moneda</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total_general = 0;
                    foreach ($partidas as $partida):
                        $cantidad = floatval($partida['cantidad'] ?? 0);
                        $precio_unitario = floatval($partida['precio_unitario'] ?? 0);
                        $monto_estimado = floatval($partida['monto_estimado'] ?? 0);
                        $moneda = $partida['moneda'] ?? 'CRC';

                        // Acumular total
                        if ($moneda == 'CRC') {
                            $total_general += $monto_estimado;
                        }
                    ?>
                    <tr>
                        <td class="text-center"><?php echo htmlspecialchars($partida['partida'] ?? '-'); ?></td>
                        <td class="text-center"><?php echo htmlspecialchars($partida['linea'] ?? '-'); ?></td>
                        <td>
                            <?php
                            $codigo = $partida['codigo_identificacion'] ?? '';
                            if (!empty($codigo)) {
                                echo htmlspecialchars($codigo);
                            } else {
                                echo '<span class="campo-faltante">Sin código</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            $nombre = $partida['nombre'] ?? $partida['descripcion'] ?? '';
                            if (!empty($nombre)) {
                                echo htmlspecialchars($nombre);
                            } else {
                                echo '<span class="campo-faltante">Importar DetalleLineaCartel.csv</span>';
                            }
                            ?>
                        </td>
                        <td class="text-right">
                            <?php
                            if ($cantidad > 0) {
                                echo ($cantidad == floor($cantidad)) ? number_format($cantidad, 0) : number_format($cantidad, 2);
                            } else {
                                echo '<span class="campo-faltante">-</span>';
                            }
                            ?>
                        </td>
                        <td class="text-center">
                            <?php
                            $unidad = $partida['unidad'] ?? '';
                            if (!empty($unidad)) {
                                echo htmlspecialchars($unidad);
                            } else {
                                echo '<span class="campo-faltante">-</span>';
                            }
                            ?>
                        </td>
                        <td class="text-right">
                            <?php
                            if ($precio_unitario > 0) {
                                $simbolo = ($moneda == 'USD') ? '$' : '₡';
                                echo $simbolo . number_format($precio_unitario, 2, '.', ',');
                            } else {
                                echo '<span class="campo-faltante">-</span>';
                            }
                            ?>
                        </td>
                        <td class="text-right">
                            <?php
                            if ($monto_estimado > 0) {
                                $simbolo = ($moneda == 'USD') ? '$' : '₡';
                                echo '<strong>' . $simbolo . number_format($monto_estimado, 2, '.', ',') . '</strong>';
                            } else {
                                echo '<span class="campo-faltante">-</span>';
                            }
                            ?>
                        </td>
                        <td class="text-center"><?php echo htmlspecialchars($moneda); ?></td>
                    </tr>
                    <?php endforeach; ?>

                    <?php if ($total_general > 0): ?>
                    <tr style="background-color: #e6f0ff; font-weight: bold;">
                        <td colspan="7" class="text-right">TOTAL ESTIMADO (CRC):</td>
                        <td class="text-right">₡<?php echo number_format($total_general, 2, '.', ','); ?></td>
                        <td></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php elseif ($tabla_partidas_existe && empty($partidas)): ?>
    <div class="sicop-section">
        <div class="sicop-section-header">
            [ 7. Información de bien, servicio u obra ]
        </div>
        <div class="sicop-section-body">
            <div class="info-note">
                <strong>ℹ️ Sin partidas:</strong> Esta licitación no tiene partidas asociadas en la base de datos.
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php include '../includes/footer.php'; ?>
