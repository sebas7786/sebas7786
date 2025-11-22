<?php
/**
 * ═══════════════════════════════════════════════════════════════════════
 * PANEL ADMINISTRATIVO DE NOTIFICACIONES - LicitacionesYA
 * ═══════════════════════════════════════════════════════════════════════
 * Gestión completa del sistema de notificaciones por email
 */

// Configuración básica
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Incluir archivos necesarios
require_once __DIR__ . '/config_email.php';
require_once __DIR__ . '/email_functions.php';

// Simular conexión (en producción usa tu config/db.php real)
// require_once __DIR__ . '/config/db.php';
// require_once __DIR__ . '/includes/functions.php';
// require_admin();

// Variables de estado
$mensaje = '';
$tipo_mensaje = '';
$prueba_enviada = false;

// ═══════════════════════════════════════════════════════════════════════
// PROCESAR ACCIONES
// ═══════════════════════════════════════════════════════════════════════

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ═══════════════════════════════════════════════════════════════════
    // PROBAR CONFIGURACIÓN SMTP
    // ═══════════════════════════════════════════════════════════════════
    if (isset($_POST['probar_smtp'])) {
        $email_prueba = filter_var($_POST['email_prueba'], FILTER_VALIDATE_EMAIL);

        if (!$email_prueba) {
            $mensaje = "Email de prueba inválido";
            $tipo_mensaje = 'error';
        } else {
            $contenido = '
                <h2 style="color: #10b981;">✅ Prueba Exitosa</h2>
                <p>Si estás leyendo este correo, significa que la configuración SMTP está funcionando correctamente.</p>
                <p><strong>Hora de envío:</strong> ' . date('d/m/Y H:i:s') . '</p>
                <p><strong>Servidor:</strong> ' . SMTP_HOST . ':' . SMTP_PORT . '</p>
            ';

            $html = plantilla_email_base($contenido, 'Prueba de Configuración SMTP');

            if (enviar_email($email_prueba, 'Usuario de Prueba', 'Prueba de Configuración SMTP - LicitacionesYA', $html)) {
                $mensaje = "¡Email de prueba enviado exitosamente a $email_prueba! Revisa tu bandeja de entrada.";
                $tipo_mensaje = 'success';
                $prueba_enviada = true;
            } else {
                $mensaje = "Error al enviar email de prueba. Revisa la configuración SMTP y los logs.";
                $tipo_mensaje = 'error';
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // ENVIAR RECORDATORIOS MANUALES
    // ═══════════════════════════════════════════════════════════════════
    elseif (isset($_POST['enviar_recordatorios'])) {
        // En producción, usar la función con la conexión real
        // $enviados = procesar_recordatorios_pago_automaticos($conn);

        $mensaje = "Función de recordatorios preparada. Conecta con tu base de datos para activarla.";
        $tipo_mensaje = 'info';
    }

    // ═══════════════════════════════════════════════════════════════════
    // ENVIAR EMAIL DE BIENVENIDA DE PRUEBA
    // ═══════════════════════════════════════════════════════════════════
    elseif (isset($_POST['probar_bienvenida'])) {
        $email_prueba = filter_var($_POST['email_bienvenida'], FILTER_VALIDATE_EMAIL);

        if (!$email_prueba) {
            $mensaje = "Email de prueba inválido";
            $tipo_mensaje = 'error';
        } else {
            if (enviar_email_bienvenida($email_prueba, 'Usuario de Prueba')) {
                $mensaje = "Email de bienvenida enviado a $email_prueba";
                $tipo_mensaje = 'success';
            } else {
                $mensaje = "Error al enviar email de bienvenida";
                $tipo_mensaje = 'error';
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // PROBAR NOTIFICACIÓN DE LICITACIÓN
    // ═══════════════════════════════════════════════════════════════════
    elseif (isset($_POST['probar_licitacion'])) {
        $email_prueba = filter_var($_POST['email_licitacion'], FILTER_VALIDATE_EMAIL);

        if (!$email_prueba) {
            $mensaje = "Email de prueba inválido";
            $tipo_mensaje = 'error';
        } else {
            // Datos de prueba
            $licitacion_prueba = [
                'id' => 123,
                'titulo' => 'Adquisición de Equipos de Cómputo para Oficinas',
                'categoria' => 'Tecnología de la Información y Comunicaciones',
                'institucion' => 'Ministerio de Educación Pública',
                'fecha_cierre' => '15/12/2024',
                'hora_cierre' => '10:00'
            ];

            if (enviar_notificacion_nueva_licitacion($email_prueba, 'Usuario de Prueba', $licitacion_prueba)) {
                $mensaje = "Notificación de licitación enviada a $email_prueba";
                $tipo_mensaje = 'success';
            } else {
                $mensaje = "Error al enviar notificación de licitación";
                $tipo_mensaje = 'error';
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // PROBAR RECORDATORIO DE PAGO
    // ═══════════════════════════════════════════════════════════════════
    elseif (isset($_POST['probar_recordatorio'])) {
        $email_prueba = filter_var($_POST['email_recordatorio'], FILTER_VALIDATE_EMAIL);
        $dias = intval($_POST['dias_vencimiento']);

        if (!$email_prueba) {
            $mensaje = "Email de prueba inválido";
            $tipo_mensaje = 'error';
        } else {
            $fecha_venc = date('d/m/Y', strtotime("+$dias days"));

            if (enviar_recordatorio_pago($email_prueba, 'Usuario de Prueba', $dias, 25000, $fecha_venc)) {
                $mensaje = "Recordatorio de pago enviado a $email_prueba";
                $tipo_mensaje = 'success';
            } else {
                $mensaje = "Error al enviar recordatorio de pago";
                $tipo_mensaje = 'error';
            }
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════
// OBTENER ESTADÍSTICAS DE LOGS
// ═══════════════════════════════════════════════════════════════════════
$log_file = __DIR__ . '/logs/email_log.txt';
$ultimos_logs = [];
$estadisticas = [
    'total' => 0,
    'exitosos' => 0,
    'errores' => 0
];

if (file_exists($log_file)) {
    $logs = file($log_file);
    $logs_recientes = array_slice(array_reverse($logs), 0, 20);

    foreach ($logs as $linea) {
        $estadisticas['total']++;
        if (stripos($linea, '[success]') !== false) {
            $estadisticas['exitosos']++;
        } elseif (stripos($linea, '[error]') !== false) {
            $estadisticas['errores']++;
        }
    }

    $ultimos_logs = $logs_recientes;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Notificaciones - LicitacionesYA</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
            font-size: 16px;
        }

        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }

        .alert-success {
            background-color: #ecfdf5;
            border-color: #10b981;
            color: #065f46;
        }

        .alert-error {
            background-color: #fef2f2;
            border-color: #ef4444;
            color: #991b1b;
        }

        .alert-info {
            background-color: #eff6ff;
            border-color: #3b82f6;
            color: #1e40af;
        }

        .alert-warning {
            background-color: #fff7ed;
            border-color: #f59e0b;
            color: #92400e;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .card h2 {
            color: #1f2937;
            margin-bottom: 15px;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stat-box {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-box h3 {
            font-size: 36px;
            margin-bottom: 5px;
        }

        .stat-box p {
            opacity: 0.9;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 15px;
            background-color: #f9fafb;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #2563eb;
            background-color: white;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            display: inline-block;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(37, 99, 235, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(16, 185, 129, 0.3);
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }

        .config-info {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .config-info h4 {
            color: #495057;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .config-info table {
            width: 100%;
            font-size: 14px;
        }

        .config-info table td {
            padding: 5px 0;
        }

        .config-info table td:first-child {
            color: #6c757d;
            width: 40%;
        }

        .config-info table td:last-child {
            font-weight: 600;
            color: #212529;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-enabled {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-disabled {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .log-container {
            background-color: #1e293b;
            color: #e2e8f0;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            max-height: 400px;
            overflow-y: auto;
        }

        .log-line {
            padding: 4px 0;
            border-bottom: 1px solid #334155;
        }

        .log-success {
            color: #6ee7b7;
        }

        .log-error {
            color: #fca5a5;
        }

        .log-warning {
            color: #fcd34d;
        }

        .section-title {
            font-size: 24px;
            color: #1f2937;
            margin: 30px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 3px solid #2563eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📧 Panel de Notificaciones</h1>
            <p>Gestiona y prueba el sistema de notificaciones por correo electrónico</p>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <h2 class="section-title">📊 Estadísticas de Envíos</h2>
        <div class="grid">
            <div class="card">
                <div class="stat-box">
                    <h3><?php echo number_format($estadisticas['total']); ?></h3>
                    <p>Total de Envíos</p>
                </div>
            </div>
            <div class="card">
                <div class="stat-box" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <h3><?php echo number_format($estadisticas['exitosos']); ?></h3>
                    <p>Enviados Exitosamente</p>
                </div>
            </div>
            <div class="card">
                <div class="stat-box" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                    <h3><?php echo number_format($estadisticas['errores']); ?></h3>
                    <p>Errores de Envío</p>
                </div>
            </div>
        </div>

        <!-- Configuración Actual -->
        <h2 class="section-title">⚙️ Configuración Actual</h2>
        <div class="card">
            <h2>Configuración SMTP</h2>
            <div class="config-info">
                <table>
                    <tr>
                        <td>Estado SMTP:</td>
                        <td>
                            <span class="status-badge <?php echo SMTP_ENABLED ? 'status-enabled' : 'status-disabled'; ?>">
                                <?php echo SMTP_ENABLED ? 'ACTIVADO' : 'DESACTIVADO'; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td>Servidor:</td>
                        <td><?php echo htmlspecialchars(SMTP_HOST); ?></td>
                    </tr>
                    <tr>
                        <td>Puerto:</td>
                        <td><?php echo htmlspecialchars(SMTP_PORT); ?></td>
                    </tr>
                    <tr>
                        <td>Seguridad:</td>
                        <td><?php echo strtoupper(SMTP_SECURE); ?></td>
                    </tr>
                    <tr>
                        <td>Usuario:</td>
                        <td><?php echo htmlspecialchars(SMTP_USERNAME); ?></td>
                    </tr>
                    <tr>
                        <td>Remitente:</td>
                        <td><?php echo htmlspecialchars(EMAIL_FROM_NAME); ?> &lt;<?php echo htmlspecialchars(EMAIL_FROM); ?>&gt;</td>
                    </tr>
                </table>
            </div>

            <div class="alert alert-warning">
                <strong>⚠️ Importante:</strong> Si ves "tu-email@gmail.com", debes editar el archivo <code>config_email.php</code> con tus credenciales SMTP reales.
            </div>

            <form method="POST">
                <div class="form-group">
                    <label>Email de Prueba</label>
                    <input type="email" name="email_prueba" placeholder="ejemplo@correo.com" required>
                </div>
                <button type="submit" name="probar_smtp" class="btn btn-primary">
                    🧪 Probar Configuración SMTP
                </button>
            </form>
        </div>

        <!-- Probar Plantillas -->
        <h2 class="section-title">✉️ Probar Plantillas de Email</h2>
        <div class="grid">
            <!-- Email de Bienvenida -->
            <div class="card">
                <h2>👋 Bienvenida</h2>
                <p style="color: #6b7280; margin-bottom: 15px; font-size: 14px;">
                    Prueba el email que reciben los usuarios nuevos al registrarse.
                </p>
                <form method="POST">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email_bienvenida" placeholder="ejemplo@correo.com" required>
                    </div>
                    <button type="submit" name="probar_bienvenida" class="btn btn-success">
                        Enviar Prueba
                    </button>
                </form>
            </div>

            <!-- Notificación de Licitación -->
            <div class="card">
                <h2>📋 Nueva Licitación</h2>
                <p style="color: #6b7280; margin-bottom: 15px; font-size: 14px;">
                    Prueba la notificación de nuevas licitaciones.
                </p>
                <form method="POST">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email_licitacion" placeholder="ejemplo@correo.com" required>
                    </div>
                    <button type="submit" name="probar_licitacion" class="btn btn-success">
                        Enviar Prueba
                    </button>
                </form>
            </div>

            <!-- Recordatorio de Pago -->
            <div class="card">
                <h2>💰 Recordatorio de Pago</h2>
                <p style="color: #6b7280; margin-bottom: 15px; font-size: 14px;">
                    Prueba el recordatorio de pagos próximos a vencer.
                </p>
                <form method="POST">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email_recordatorio" placeholder="ejemplo@correo.com" required>
                    </div>
                    <div class="form-group">
                        <label>Días hasta vencimiento</label>
                        <select name="dias_vencimiento" required>
                            <option value="7">7 días</option>
                            <option value="3">3 días</option>
                            <option value="1">1 día</option>
                            <option value="-1">Vencido (1 día atrás)</option>
                        </select>
                    </div>
                    <button type="submit" name="probar_recordatorio" class="btn btn-warning">
                        Enviar Prueba
                    </button>
                </form>
            </div>
        </div>

        <!-- Logs Recientes -->
        <h2 class="section-title">📝 Logs Recientes</h2>
        <div class="card">
            <h2>Últimos 20 Eventos</h2>
            <?php if (!empty($ultimos_logs)): ?>
                <div class="log-container">
                    <?php foreach ($ultimos_logs as $log): ?>
                        <?php
                        $clase = 'log-line';
                        if (stripos($log, '[success]') !== false) {
                            $clase .= ' log-success';
                        } elseif (stripos($log, '[error]') !== false) {
                            $clase .= ' log-error';
                        } elseif (stripos($log, '[warning]') !== false) {
                            $clase .= ' log-warning';
                        }
                        ?>
                        <div class="<?php echo $clase; ?>">
                            <?php echo htmlspecialchars($log); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: #6b7280; text-align: center; padding: 40px;">
                    No hay logs disponibles. Los logs se generarán automáticamente al enviar emails.
                </p>
            <?php endif; ?>
        </div>

        <!-- Botón de regreso -->
        <div style="text-align: center; margin-top: 30px;">
            <a href="dashboard.php" class="btn btn-primary">← Volver al Dashboard</a>
        </div>
    </div>
</body>
</html>
