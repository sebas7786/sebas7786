<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

// Check if user is admin
require_admin();

// ================================
// PROCESAR ACCIONES
// ================================
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Actualizar estado de pago
    if (isset($_POST['action']) && $_POST['action'] === 'update_payment') {
        $user_id = intval($_POST['user_id']);
        $monto_pagar = floatval($_POST['monto_pagar']);
        $fecha_proximo_pago = $_POST['fecha_proximo_pago'];
        $estado_pago = $_POST['estado_pago'];
        $notas_pago = $_POST['notas_pago'] ?? '';

        $stmt = $conn->prepare("UPDATE usuarios SET
                                monto_pagar = ?,
                                fecha_proximo_pago = ?,
                                estado_pago = ?,
                                notas_pago = ?
                                WHERE id = ?");

        if ($stmt->execute([$monto_pagar, $fecha_proximo_pago, $estado_pago, $notas_pago, $user_id])) {
            // Registrar en historial
            $stmt_hist = $conn->prepare("INSERT INTO historial_pagos (usuario_id, monto, fecha_pago, estado, notas, creado_por)
                                         VALUES (?, ?, NOW(), ?, ?, ?)");
            $stmt_hist->execute([$user_id, $monto_pagar, $estado_pago, $notas_pago, $_SESSION['usuario_id']]);

            $mensaje = "Estado de pago actualizado correctamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al actualizar el estado de pago";
            $tipo_mensaje = "error";
        }
    }

    // Registrar pago recibido
    if (isset($_POST['action']) && $_POST['action'] === 'registrar_pago') {
        $user_id = intval($_POST['user_id']);
        $monto_recibido = floatval($_POST['monto_recibido']);
        $fecha_pago = $_POST['fecha_pago'];
        $metodo_pago = $_POST['metodo_pago'];
        $referencia = $_POST['referencia'] ?? '';
        $notas = $_POST['notas_pago'] ?? '';

        // Registrar pago
        $stmt = $conn->prepare("INSERT INTO historial_pagos (usuario_id, monto, fecha_pago, metodo_pago, referencia, estado, notas, creado_por)
                               VALUES (?, ?, ?, ?, ?, 'pagado', ?, ?)");

        if ($stmt->execute([$user_id, $monto_recibido, $fecha_pago, $metodo_pago, $referencia, $notas, $_SESSION['usuario_id']])) {
            // Actualizar estado del usuario
            $stmt_update = $conn->prepare("UPDATE usuarios SET
                                          estado_pago = 1,
                                          ultimo_pago = ?,
                                          monto_ultimo_pago = ?
                                          WHERE id = ?");
            $stmt_update->execute([$fecha_pago, $monto_recibido, $user_id]);

            $mensaje = "Pago registrado exitosamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al registrar el pago";
            $tipo_mensaje = "error";
        }
    }

    // Activar/Desactivar usuario
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_user') {
        $user_id = intval($_POST['user_id']);
        $nuevo_estado = intval($_POST['nuevo_estado']);

        $stmt = $conn->prepare("UPDATE usuarios SET activo = ? WHERE id = ?");
        if ($stmt->execute([$nuevo_estado, $user_id])) {
            $accion = $nuevo_estado ? 'activado' : 'desactivado';
            $mensaje = "Usuario {$accion} correctamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al cambiar el estado del usuario";
            $tipo_mensaje = "error";
        }
    }

    // Enviar recordatorio de pago
    if (isset($_POST['action']) && $_POST['action'] === 'send_reminder') {
        $user_id = intval($_POST['user_id']);

        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user) {
            // Aquí implementarías el envío de email
            // mail($user['email'], "Recordatorio de Pago - LicitaHoy", $mensaje_email);

            // Registrar el recordatorio
            $stmt_rec = $conn->prepare("INSERT INTO recordatorios_pago (usuario_id, fecha_envio, creado_por)
                                       VALUES (?, NOW(), ?)");
            $stmt_rec->execute([$user_id, $_SESSION['usuario_id']]);

            $mensaje = "Recordatorio enviado a " . $user['email'];
            $tipo_mensaje = "success";
        }
    }
}

// ================================
// OBTENER USUARIOS CON INFO DE PAGO
// ================================
$filtro_estado = isset($_GET['estado_pago']) ? $_GET['estado_pago'] : '';
$filtro_activo = isset($_GET['activo']) ? $_GET['activo'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$where_conditions = ["u.es_admin = 0"];
$params = [];

if (!empty($filtro_estado)) {
    $where_conditions[] = "u.estado_pago = ?";
    $params[] = $filtro_estado;
}

if ($filtro_activo !== '') {
    $where_conditions[] = "u.activo = ?";
    $params[] = intval($filtro_activo);
}

if (!empty($search)) {
    $where_conditions[] = "(u.nombre LIKE ? OR u.email LIKE ? OR u.empresa LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = implode(" AND ", $where_conditions);

$sql = "SELECT u.*,
        (SELECT COUNT(*) FROM historial_pagos WHERE usuario_id = u.id) as total_pagos,
        (SELECT SUM(monto) FROM historial_pagos WHERE usuario_id = u.id AND estado = 'pagado') as total_pagado,
        DATEDIFF(u.fecha_proximo_pago, CURDATE()) as dias_hasta_pago
        FROM usuarios u
        WHERE {$where_clause}
        ORDER BY u.fecha_proximo_pago ASC, u.nombre ASC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll();

// Estadísticas
$stats = [
    'total_usuarios' => 0,
    'activos' => 0,
    'al_dia' => 0,
    'pendientes' => 0,
    'vencidos' => 0,
    'ingresos_mes' => 0
];

$stmt = $conn->query("SELECT COUNT(*) as count FROM usuarios WHERE es_admin = 0");
$stats['total_usuarios'] = $stmt->fetch()['count'];

$stmt = $conn->query("SELECT COUNT(*) as count FROM usuarios WHERE es_admin = 0 AND activo = 1");
$stats['activos'] = $stmt->fetch()['count'];

$stmt = $conn->query("SELECT COUNT(*) as count FROM usuarios WHERE es_admin = 0 AND estado_pago = 1");
$stats['al_dia'] = $stmt->fetch()['count'];

$stmt = $conn->query("SELECT COUNT(*) as count FROM usuarios WHERE es_admin = 0 AND estado_pago = 0 AND fecha_proximo_pago >= CURDATE()");
$stats['pendientes'] = $stmt->fetch()['count'];

$stmt = $conn->query("SELECT COUNT(*) as count FROM usuarios WHERE es_admin = 0 AND fecha_proximo_pago < CURDATE() AND estado_pago = 0");
$stats['vencidos'] = $stmt->fetch()['count'];

$stmt = $conn->query("SELECT COALESCE(SUM(monto), 0) as total FROM historial_pagos
                      WHERE MONTH(fecha_pago) = MONTH(CURDATE())
                      AND YEAR(fecha_pago) = YEAR(CURDATE())
                      AND estado = 'pagado'");
$stats['ingresos_mes'] = $stmt->fetch()['total'];

include '../includes/header.php';
?>

<link rel="stylesheet" href="/assets/css/admin-styles.css">
<style>
    /* Estilos para gestión de pagos */
    .user-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.4rem 0.85rem;
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: 500;
    }

    .user-status-badge.activo {
        background: #dcfce7;
        color: #166534;
    }

    .user-status-badge.inactivo {
        background: #fee2e2;
        color: #991b1b;
    }

    .payment-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.4rem 0.85rem;
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: 500;
    }

    .payment-status-badge.al-dia {
        background: #dcfce7;
        color: #166534;
    }

    .payment-status-badge.pendiente {
        background: #fef3c7;
        color: #92400e;
    }

    .payment-status-badge.vencido {
        background: #fee2e2;
        color: #991b1b;
    }

    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        overflow-y: auto;
    }

    .modal.show {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
    }

    .modal-content {
        background: white;
        border-radius: 12px;
        max-width: 600px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
    }

    .modal-header {
        padding: 1.5rem;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-header h3 {
        margin: 0;
        font-size: 1.25rem;
        color: #1e293b;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        color: #64748b;
        cursor: pointer;
        padding: 0;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        transition: all 0.2s;
    }

    .modal-close:hover {
        background: #f1f5f9;
        color: #1e293b;
    }

    .modal-body {
        padding: 1.5rem;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-group.full-width {
        grid-column: 1 / -1;
    }

    .form-label {
        display: block;
        font-weight: 500;
        color: #475569;
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
    }

    .form-input,
    .form-select,
    .form-textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 0.95rem;
        transition: all 0.2s;
    }

    .form-input:focus,
    .form-select:focus,
    .form-textarea:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .modal-footer {
        padding: 1.5rem;
        border-top: 1px solid #e2e8f0;
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
    }

    .btn-action {
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        border: none;
        text-decoration: none;
    }

    .btn-action.primary {
        background: #2563eb;
        color: white;
    }

    .btn-action.success {
        background: #10b981;
        color: white;
    }

    .btn-action.warning {
        background: #f59e0b;
        color: white;
    }

    .btn-action.danger {
        background: #ef4444;
        color: white;
    }

    .btn-action.secondary {
        background: #f1f5f9;
        color: #475569;
    }

    .btn-action:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .payment-history {
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid #e2e8f0;
    }

    .payment-history h4 {
        font-size: 1rem;
        margin-bottom: 1rem;
        color: #1e293b;
    }

    .payment-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem;
        background: #f8fafc;
        border-radius: 6px;
        margin-bottom: 0.5rem;
    }

    .payment-item-date {
        font-size: 0.875rem;
        color: #64748b;
    }

    .payment-item-amount {
        font-weight: 600;
        color: #10b981;
    }

    .alert {
        padding: 1rem 1.5rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .alert.success {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #86efac;
    }

    .alert.error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fca5a5;
    }

    .filters-container {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .filters-grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr auto;
        gap: 1rem;
        align-items: end;
    }

    .user-info-cell {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .user-name {
        font-weight: 600;
        color: #1e293b;
    }

    .user-email {
        font-size: 0.85rem;
        color: #64748b;
    }

    .user-empresa {
        font-size: 0.85rem;
        color: #475569;
    }

    .payment-info {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .payment-amount {
        font-weight: 600;
        color: #1e293b;
        font-size: 1.1rem;
    }

    .payment-date {
        font-size: 0.85rem;
        color: #64748b;
    }

    .days-warning {
        font-size: 0.85rem;
        font-weight: 500;
    }

    .days-warning.vencido {
        color: #ef4444;
    }

    .days-warning.proximo {
        color: #f59e0b;
    }

    .days-warning.ok {
        color: #10b981;
    }

    @media (max-width: 768px) {
        .filters-grid {
            grid-template-columns: 1fr;
        }

        .form-grid {
            grid-template-columns: 1fr;
        }

        .modal-content {
            margin: 1rem;
        }
    }
</style>

<div class="admin-container">
    <div class="dashboard-header">
        <h1>Gestión de Usuarios y Pagos</h1>
        <div class="dashboard-actions">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <a href="?action=add" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Nuevo Usuario
            </a>
        </div>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert <?php echo $tipo_mensaje; ?>">
            <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>

    <!-- Estadísticas -->
    <div class="stats-container">
        <div class="stat-card primary">
            <div class="stat-icon primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3>Total Usuarios</h3>
                <p class="stat-number"><?php echo $stats['total_usuarios']; ?></p>
                <div class="stat-progress">
                    <i class="fas fa-user-check"></i> <?php echo $stats['activos']; ?> activos
                </div>
            </div>
        </div>

        <div class="stat-card success">
            <div class="stat-icon success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <h3>Al Día</h3>
                <p class="stat-number"><?php echo $stats['al_dia']; ?></p>
                <div class="stat-progress">
                    <?php
                    $pct_aldia = $stats['total_usuarios'] > 0 ? round(($stats['al_dia'] / $stats['total_usuarios']) * 100) : 0;
                    echo "{$pct_aldia}% del total";
                    ?>
                </div>
            </div>
        </div>

        <div class="stat-card warning">
            <div class="stat-icon warning">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3>Pendientes</h3>
                <p class="stat-number"><?php echo $stats['pendientes']; ?></p>
                <div class="stat-progress">
                    <i class="fas fa-exclamation-triangle"></i> Por vencer
                </div>
            </div>
        </div>

        <div class="stat-card danger">
            <div class="stat-icon danger">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="stat-content">
                <h3>Vencidos</h3>
                <p class="stat-number"><?php echo $stats['vencidos']; ?></p>
                <div class="stat-progress">
                    <i class="fas fa-ban"></i> Requieren atención
                </div>
            </div>
        </div>

        <div class="stat-card info">
            <div class="stat-icon info">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-content">
                <h3>Ingresos Mes</h3>
                <p class="stat-number">₡<?php echo number_format($stats['ingresos_mes'], 0); ?></p>
                <div class="stat-progress">
                    <i class="fas fa-calendar-alt"></i> <?php echo date('F Y'); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filters-container">
        <form method="GET" action="">
            <div class="filters-grid">
                <div class="form-group">
                    <label class="form-label">Buscar</label>
                    <input type="text" name="search" class="form-input"
                           placeholder="Nombre, email o empresa..."
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Estado de Pago</label>
                    <select name="estado_pago" class="form-select">
                        <option value="">Todos</option>
                        <option value="1" <?php echo $filtro_estado === '1' ? 'selected' : ''; ?>>Al día</option>
                        <option value="0" <?php echo $filtro_estado === '0' ? 'selected' : ''; ?>>Pendiente</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Estado Usuario</label>
                    <select name="activo" class="form-select">
                        <option value="">Todos</option>
                        <option value="1" <?php echo $filtro_activo === '1' ? 'selected' : ''; ?>>Activos</option>
                        <option value="0" <?php echo $filtro_activo === '0' ? 'selected' : ''; ?>>Inactivos</option>
                    </select>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Tabla de Usuarios -->
    <div class="section-card">
        <div class="table-container">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Monto a Pagar</th>
                            <th>Próximo Pago</th>
                            <th>Estado Pago</th>
                            <th>Estado Usuario</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $user): ?>
                            <?php
                            $dias_hasta = $user['dias_hasta_pago'];
                            $estado_pago_class = '';
                            $estado_pago_text = '';

                            if ($user['estado_pago'] == 1) {
                                $estado_pago_class = 'al-dia';
                                $estado_pago_text = 'Al día';
                            } elseif ($dias_hasta < 0) {
                                $estado_pago_class = 'vencido';
                                $estado_pago_text = 'Vencido';
                            } else {
                                $estado_pago_class = 'pendiente';
                                $estado_pago_text = 'Pendiente';
                            }
                            ?>
                            <tr>
                                <td>
                                    <div class="user-info-cell">
                                        <span class="user-name"><?php echo htmlspecialchars($user['nombre']); ?></span>
                                        <span class="user-email"><?php echo htmlspecialchars($user['email']); ?></span>
                                        <?php if ($user['empresa']): ?>
                                            <span class="user-empresa">
                                                <i class="fas fa-building"></i> <?php echo htmlspecialchars($user['empresa']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="payment-info">
                                        <span class="payment-amount">
                                            ₡<?php echo number_format($user['monto_pagar'] ?? 0, 0); ?>
                                        </span>
                                        <?php if ($user['total_pagado'] > 0): ?>
                                            <span class="payment-date">
                                                Total pagado: ₡<?php echo number_format($user['total_pagado'], 0); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($user['fecha_proximo_pago']): ?>
                                        <div class="payment-info">
                                            <span class="payment-date">
                                                <?php echo date('d/m/Y', strtotime($user['fecha_proximo_pago'])); ?>
                                            </span>
                                            <?php if ($dias_hasta < 0): ?>
                                                <span class="days-warning vencido">
                                                    <i class="fas fa-exclamation-circle"></i>
                                                    Vencido hace <?php echo abs($dias_hasta); ?> días
                                                </span>
                                            <?php elseif ($dias_hasta <= 7): ?>
                                                <span class="days-warning proximo">
                                                    <i class="fas fa-clock"></i>
                                                    Vence en <?php echo $dias_hasta; ?> días
                                                </span>
                                            <?php else: ?>
                                                <span class="days-warning ok">
                                                    <i class="fas fa-check"></i>
                                                    Faltan <?php echo $dias_hasta; ?> días
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #94a3b8;">No definido</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="payment-status-badge <?php echo $estado_pago_class; ?>">
                                        <i class="fas fa-circle"></i>
                                        <?php echo $estado_pago_text; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="user-status-badge <?php echo $user['activo'] ? 'activo' : 'inactivo'; ?>">
                                        <i class="fas fa-<?php echo $user['activo'] ? 'check' : 'times'; ?>-circle"></i>
                                        <?php echo $user['activo'] ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="table-actions" style="gap: 0.5rem;">
                                        <button onclick="openPaymentModal(<?php echo htmlspecialchars(json_encode($user)); ?>)"
                                                class="btn-action primary" title="Gestionar Pago">
                                            <i class="fas fa-dollar-sign"></i>
                                        </button>

                                        <button onclick="openRegisterPaymentModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['nombre']); ?>')"
                                                class="btn-action success" title="Registrar Pago">
                                            <i class="fas fa-cash-register"></i>
                                        </button>

                                        <form method="POST" style="display: inline;" onsubmit="return confirm('¿Enviar recordatorio de pago?');">
                                            <input type="hidden" name="action" value="send_reminder">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn-action warning" title="Enviar Recordatorio">
                                                <i class="fas fa-bell"></i>
                                            </button>
                                        </form>

                                        <form method="POST" style="display: inline;"
                                              onsubmit="return confirm('¿<?php echo $user['activo'] ? 'Desactivar' : 'Activar'; ?> usuario?');">
                                            <input type="hidden" name="action" value="toggle_user">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="nuevo_estado" value="<?php echo $user['activo'] ? 0 : 1; ?>">
                                            <button type="submit" class="btn-action <?php echo $user['activo'] ? 'danger' : 'success'; ?>"
                                                    title="<?php echo $user['activo'] ? 'Desactivar' : 'Activar'; ?>">
                                                <i class="fas fa-power-off"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Gestionar Pago -->
<div id="paymentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Gestionar Estado de Pago</h3>
            <button class="modal-close" onclick="closeModal('paymentModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="update_payment">
            <input type="hidden" name="user_id" id="payment_user_id">

            <div class="modal-body">
                <div class="form-group full-width">
                    <label class="form-label">Usuario</label>
                    <input type="text" id="payment_user_name" class="form-input" disabled>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Monto a Pagar (₡)</label>
                        <input type="number" name="monto_pagar" id="payment_amount" class="form-input" required step="0.01">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Próximo Pago</label>
                        <input type="date" name="fecha_proximo_pago" id="payment_date" class="form-input" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Estado de Pago</label>
                    <select name="estado_pago" id="payment_status" class="form-select" required>
                        <option value="1">Al día (Pagado)</option>
                        <option value="0">Pendiente</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Notas</label>
                    <textarea name="notas_pago" id="payment_notes" class="form-textarea" rows="3"></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-action secondary" onclick="closeModal('paymentModal')">
                    Cancelar
                </button>
                <button type="submit" class="btn-action primary">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Registrar Pago Recibido -->
<div id="registerPaymentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Registrar Pago Recibido</h3>
            <button class="modal-close" onclick="closeModal('registerPaymentModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="registrar_pago">
            <input type="hidden" name="user_id" id="register_user_id">

            <div class="modal-body">
                <div class="form-group full-width">
                    <label class="form-label">Usuario</label>
                    <input type="text" id="register_user_name" class="form-input" disabled>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Monto Recibido (₡)</label>
                        <input type="number" name="monto_recibido" class="form-input" required step="0.01">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Fecha de Pago</label>
                        <input type="date" name="fecha_pago" class="form-input" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Método de Pago</label>
                        <select name="metodo_pago" class="form-select" required>
                            <option value="transferencia">Transferencia</option>
                            <option value="sinpe">SINPE Móvil</option>
                            <option value="efectivo">Efectivo</option>
                            <option value="tarjeta">Tarjeta</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Referencia/Comprobante</label>
                        <input type="text" name="referencia" class="form-input" placeholder="Número de referencia">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Notas</label>
                    <textarea name="notas_pago" class="form-textarea" rows="3"></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-action secondary" onclick="closeModal('registerPaymentModal')">
                    Cancelar
                </button>
                <button type="submit" class="btn-action success">
                    <i class="fas fa-check"></i> Registrar Pago
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openPaymentModal(user) {
    document.getElementById('payment_user_id').value = user.id;
    document.getElementById('payment_user_name').value = user.nombre;
    document.getElementById('payment_amount').value = user.monto_pagar || '';
    document.getElementById('payment_date').value = user.fecha_proximo_pago || '';
    document.getElementById('payment_status').value = user.estado_pago || '0';
    document.getElementById('payment_notes').value = user.notas_pago || '';

    document.getElementById('paymentModal').classList.add('show');
}

function openRegisterPaymentModal(userId, userName) {
    document.getElementById('register_user_id').value = userId;
    document.getElementById('register_user_name').value = userName;

    document.getElementById('registerPaymentModal').classList.add('show');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
}

// Cerrar modal al hacer clic fuera
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('show');
    }
}

// Cerrar modal con ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.classList.remove('show');
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>
