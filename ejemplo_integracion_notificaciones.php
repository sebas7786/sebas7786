<?php
/**
 * ═══════════════════════════════════════════════════════════════════════
 * EJEMPLOS DE INTEGRACIÓN DEL SISTEMA DE NOTIFICACIONES
 * ═══════════════════════════════════════════════════════════════════════
 * Este archivo contiene ejemplos de cómo integrar las notificaciones
 * en tus archivos existentes de LicitacionesYA
 */

// ═══════════════════════════════════════════════════════════════════════
// EJEMPLO 1: INTEGRAR EN register.php
// ═══════════════════════════════════════════════════════════════════════
/*
<?php
// Inicio de register.php
require_once 'config/db.php';
require_once 'email_functions.php'; // ⬅️ AGREGAR ESTA LÍNEA

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $contrasena = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);

    // Insertar usuario
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, correo, contrasena) VALUES (?, ?, ?)");

    if ($stmt->execute([$nombre, $correo, $contrasena])) {
        $new_user_id = $conn->lastInsertId();

        // ⬇️ AGREGAR ESTA SECCIÓN ⬇️
        // Enviar email de bienvenida
        if (NOTIF_BIENVENIDA) { // Verifica si está activado en config_email.php
            enviar_email_bienvenida($correo, $nombre);
        }
        // ⬆️ FIN DE SECCIÓN ⬆️

        $_SESSION['success'] = "Registro exitoso. Revisa tu email.";
        header('Location: login.php');
        exit;
    }
}
?>
*/

// ═══════════════════════════════════════════════════════════════════════
// EJEMPLO 2: INTEGRAR EN users.php (Panel de Admin)
// ═══════════════════════════════════════════════════════════════════════
/*
<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../email_functions.php'; // ⬅️ AGREGAR ESTA LÍNEA

// Verificar admin
require_admin();

// ⬇️ AGREGAR ESTA SECCIÓN: DESACTIVAR USUARIO ⬇️
if (isset($_POST['desactivar_usuario'])) {
    $usuario_id = intval($_POST['usuario_id']);

    // Obtener datos del usuario antes de desactivar
    $stmt = $conn->prepare("SELECT nombre, correo FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Desactivar usuario
    $stmt = $conn->prepare("UPDATE usuarios SET activo = 0 WHERE id = ?");

    if ($stmt->execute([$usuario_id])) {
        // Enviar notificación de cuenta desactivada
        if (NOTIF_CUENTA_DESACTIVADA && $usuario) {
            enviar_notificacion_cuenta_desactivada(
                $usuario['correo'],
                $usuario['nombre'],
                'falta de pago'
            );
        }

        $_SESSION['success'] = "Usuario desactivado correctamente";
    }
}

// ⬇️ AGREGAR ESTA SECCIÓN: REACTIVAR USUARIO ⬇️
if (isset($_POST['reactivar_usuario'])) {
    $usuario_id = intval($_POST['usuario_id']);

    // Obtener datos del usuario
    $stmt = $conn->prepare("SELECT nombre, correo FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Reactivar usuario
    $stmt = $conn->prepare("UPDATE usuarios SET activo = 1 WHERE id = ?");

    if ($stmt->execute([$usuario_id])) {
        // Enviar notificación de cuenta reactivada
        if (NOTIF_CUENTA_REACTIVADA && $usuario) {
            enviar_notificacion_cuenta_reactivada(
                $usuario['correo'],
                $usuario['nombre']
            );
        }

        $_SESSION['success'] = "Usuario reactivado correctamente";
    }
}

// ⬇️ AGREGAR ESTA SECCIÓN: ENVIAR RECORDATORIO MANUAL ⬇️
if (isset($_POST['enviar_recordatorio_manual'])) {
    $usuario_id = intval($_POST['usuario_id']);

    // Obtener datos del usuario
    $stmt = $conn->prepare("
        SELECT nombre, correo, monto_pagar, fecha_proximo_pago,
               DATEDIFF(fecha_proximo_pago, CURDATE()) as dias_restantes
        FROM usuarios
        WHERE id = ?
    ");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && $usuario['fecha_proximo_pago']) {
        // Enviar recordatorio
        if (enviar_recordatorio_pago(
            $usuario['correo'],
            $usuario['nombre'],
            $usuario['dias_restantes'],
            $usuario['monto_pagar'],
            date('d/m/Y', strtotime($usuario['fecha_proximo_pago']))
        )) {
            // Registrar en base de datos
            $stmt_log = $conn->prepare("
                INSERT INTO recordatorios_pago
                (usuario_id, tipo_recordatorio, fecha_envio, dias_anticipacion, email_destinatario, resultado)
                VALUES (?, 'pago_proximo', NOW(), ?, ?, 'exitoso')
            ");
            $stmt_log->execute([
                $usuario_id,
                $usuario['dias_restantes'],
                $usuario['correo']
            ]);

            $_SESSION['success'] = "Recordatorio enviado a " . $usuario['correo'];
        } else {
            $_SESSION['error'] = "Error al enviar recordatorio";
        }
    }
}
?>

<!-- HTML: Botones para acciones -->
<table class="usuarios-table">
    <tr>
        <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
        <td><?php echo htmlspecialchars($usuario['correo']); ?></td>
        <td>
            <?php if ($usuario['activo']): ?>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                    <button type="submit" name="desactivar_usuario" class="btn-danger">
                        Desactivar
                    </button>
                </form>
            <?php else: ?>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                    <button type="submit" name="reactivar_usuario" class="btn-success">
                        Reactivar
                    </button>
                </form>
            <?php endif; ?>

            <form method="POST" style="display:inline;">
                <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                <button type="submit" name="enviar_recordatorio_manual" class="btn-warning">
                    📧 Recordatorio
                </button>
            </form>
        </td>
    </tr>
</table>
*/

// ═══════════════════════════════════════════════════════════════════════
// EJEMPLO 3: INTEGRAR EN ai-chat.php (Ya lo tienes)
// ═══════════════════════════════════════════════════════════════════════
/*
// Tu código actual ya tiene esta integración, pero aquí está el ejemplo completo

require_once '../includes/email_functions.php';

// Después de insertar una licitación
if ($stmt->execute($params)) {
    $new_tender_id = $conn->lastInsertId();

    // ⬇️ ESTA PARTE YA LA TIENES ⬇️
    // Enviar notificaciones por categoría
    enviar_notificaciones_correo($new_tender_id, $categoria, $titulo, $conn);

    // Enviar notificaciones por palabras clave
    send_keyword_notifications($new_tender_id, $tender_text, $conn);
    // ⬆️ FIN DE PARTE QUE YA TIENES ⬆️

    $success_message = "Licitación agregada y notificaciones enviadas";
}
*/

// ═══════════════════════════════════════════════════════════════════════
// EJEMPLO 4: CONFIGURACIÓN DE INTERESES DEL USUARIO
// ═══════════════════════════════════════════════════════════════════════
/*
// Crear una página user/configuracion.php para que usuarios configuren sus preferencias

<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

// Verificar que esté logueado
require_login();

$user_id = $_SESSION['user_id'];

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recibir_alertas = isset($_POST['recibir_alertas']) ? 1 : 0;
    $frecuencia = $_POST['frecuencia'];
    $categorias = isset($_POST['categorias']) ? implode(',', $_POST['categorias']) : '';
    $palabras_clave = $_POST['palabras_clave'];

    $stmt = $conn->prepare("
        UPDATE usuarios SET
            recibir_alertas_email = ?,
            frecuencia_notificaciones = ?,
            intereses = ?,
            palabras_clave = ?
        WHERE id = ?
    ");

    if ($stmt->execute([$recibir_alertas, $frecuencia, $categorias, $palabras_clave, $user_id])) {
        $_SESSION['success'] = "Configuración actualizada";
    }
}

// Obtener configuración actual
$stmt = $conn->prepare("
    SELECT recibir_alertas_email, frecuencia_notificaciones, intereses, palabras_clave
    FROM usuarios WHERE id = ?
");
$stmt->execute([$user_id]);
$config = $stmt->fetch(PDO::FETCH_ASSOC);

$categorias_usuario = !empty($config['intereses']) ? explode(',', $config['intereses']) : [];
?>

<!-- HTML del formulario -->
<form method="POST">
    <h2>Configuración de Notificaciones</h2>

    <div class="form-group">
        <label>
            <input type="checkbox" name="recibir_alertas" value="1" <?php echo $config['recibir_alertas_email'] ? 'checked' : ''; ?>>
            Recibir notificaciones por email
        </label>
    </div>

    <div class="form-group">
        <label>Frecuencia de notificaciones</label>
        <select name="frecuencia">
            <option value="inmediata" <?php echo $config['frecuencia_notificaciones'] == 'inmediata' ? 'selected' : ''; ?>>
                Inmediata (al publicar licitación)
            </option>
            <option value="diaria" <?php echo $config['frecuencia_notificaciones'] == 'diaria' ? 'selected' : ''; ?>>
                Resumen diario
            </option>
            <option value="semanal" <?php echo $config['frecuencia_notificaciones'] == 'semanal' ? 'selected' : ''; ?>>
                Resumen semanal
            </option>
        </select>
    </div>

    <div class="form-group">
        <label>Categorías de Interés</label>
        <?php
        $categorias = [
            'Tecnología de la Información y Comunicaciones',
            'Construcción y Obras Públicas',
            'Educación y Capacitación',
            'Salud y Servicios Médicos',
            // ... todas las categorías
        ];

        foreach ($categorias as $cat): ?>
            <label>
                <input type="checkbox" name="categorias[]" value="<?php echo htmlspecialchars($cat); ?>"
                    <?php echo in_array($cat, $categorias_usuario) ? 'checked' : ''; ?>>
                <?php echo htmlspecialchars($cat); ?>
            </label><br>
        <?php endforeach; ?>
    </div>

    <div class="form-group">
        <label>Palabras Clave (separadas por comas)</label>
        <input type="text" name="palabras_clave"
               value="<?php echo htmlspecialchars($config['palabras_clave']); ?>"
               placeholder="software, redes, computadoras">
        <small>Recibirás alertas cuando una licitación contenga estas palabras</small>
    </div>

    <button type="submit" class="btn-primary">Guardar Configuración</button>
</form>
*/

// ═══════════════════════════════════════════════════════════════════════
// EJEMPLO 5: ESTADÍSTICAS DE NOTIFICACIONES EN DASHBOARD ADMIN
// ═══════════════════════════════════════════════════════════════════════
/*
<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

require_admin();

// Obtener estadísticas
$stmt = $conn->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN resultado = 'exitoso' THEN 1 ELSE 0 END) as exitosos,
        SUM(CASE WHEN resultado = 'fallido' THEN 1 ELSE 0 END) as fallidos
    FROM notificaciones_enviadas
    WHERE DATE(fecha_envio) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener recordatorios pendientes
$stmt = $conn->query("SELECT COUNT(*) as total FROM recordatorios_pendientes");
$pendientes = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="dashboard-stats">
    <div class="stat-card">
        <h3>Notificaciones (30 días)</h3>
        <p class="stat-number"><?php echo number_format($stats['total']); ?></p>
        <p class="stat-detail">
            <?php echo $stats['exitosos']; ?> exitosos |
            <?php echo $stats['fallidos']; ?> fallidos
        </p>
    </div>

    <div class="stat-card">
        <h3>Recordatorios Pendientes</h3>
        <p class="stat-number"><?php echo $pendientes['total']; ?></p>
        <a href="notificaciones.php">Ver detalles</a>
    </div>
</div>

<!-- Botón para acceder al panel de notificaciones -->
<a href="../admin_notificaciones.php" class="btn-primary">
    📧 Panel de Notificaciones
</a>
*/

// ═══════════════════════════════════════════════════════════════════════
// EJEMPLO 6: ENVIAR NOTIFICACIÓN PERSONALIZADA
// ═══════════════════════════════════════════════════════════════════════
/*
// Ejemplo de cómo enviar una notificación personalizada desde cualquier parte

require_once 'email_functions.php';

// Notificación personalizada
$contenido = '
    <h2>Actualización Importante</h2>
    <p>Estimado usuario,</p>
    <p>Te informamos que hemos actualizado nuestro sistema...</p>
';

$html = plantilla_email_base($contenido, 'Actualización del Sistema');

enviar_email(
    'usuario@ejemplo.com',
    'Juan Pérez',
    'Actualización Importante - LicitacionesYA',
    $html
);
*/

// ═══════════════════════════════════════════════════════════════════════
// EJEMPLO 7: VERIFICAR SI SE PUEDE ENVIAR NOTIFICACIÓN
// ═══════════════════════════════════════════════════════════════════════
/*
// Verificar si un usuario quiere recibir notificaciones antes de enviar

function puede_recibir_notificaciones($usuario_id, $conn) {
    $stmt = $conn->prepare("
        SELECT recibir_alertas_email, activo, estado_pago
        FROM usuarios
        WHERE id = ?
    ");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    return $usuario &&
           $usuario['recibir_alertas_email'] == 1 &&
           $usuario['activo'] == 1 &&
           $usuario['estado_pago'] == 1;
}

// Uso:
if (puede_recibir_notificaciones($user_id, $conn)) {
    // Enviar notificación
}
*/

// ═══════════════════════════════════════════════════════════════════════
// EJEMPLO 8: REGISTRAR ENVÍO EN BASE DE DATOS
// ═══════════════════════════════════════════════════════════════════════
/*
// Después de enviar una notificación, registrarla en la base de datos

function registrar_notificacion($usuario_id, $licitacion_id, $tipo, $resultado, $conn) {
    try {
        // Obtener email del usuario
        $stmt = $conn->prepare("SELECT correo FROM usuarios WHERE id = ?");
        $stmt->execute([$usuario_id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $conn->prepare("
            INSERT INTO notificaciones_enviadas
            (usuario_id, licitacion_id, tipo_notificacion, fecha_envio, email_destinatario, resultado)
            VALUES (?, ?, ?, NOW(), ?, ?)
        ");

        $stmt->execute([
            $usuario_id,
            $licitacion_id,
            $tipo,
            $usuario['correo'],
            $resultado
        ]);

        // Actualizar última notificación del usuario
        $stmt = $conn->prepare("UPDATE usuarios SET ultima_notificacion = NOW() WHERE id = ?");
        $stmt->execute([$usuario_id]);

        return true;
    } catch (PDOException $e) {
        log_email("Error registrando notificación: " . $e->getMessage(), 'error');
        return false;
    }
}

// Uso:
if (enviar_notificacion_nueva_licitacion($email, $nombre, $licitacion)) {
    registrar_notificacion($user_id, $licitacion['id'], 'nueva_licitacion', 'exitoso', $conn);
} else {
    registrar_notificacion($user_id, $licitacion['id'], 'nueva_licitacion', 'fallido', $conn);
}
*/

// ═══════════════════════════════════════════════════════════════════════
// EJEMPLO 9: RESUMEN DIARIO DE LICITACIONES
// ═══════════════════════════════════════════════════════════════════════
/*
// Script para enviar resumen diario de licitaciones (ejecutar con cron)

require_once 'config/db.php';
require_once 'email_functions.php';

// Obtener licitaciones del día
$stmt = $conn->query("
    SELECT * FROM licitaciones
    WHERE DATE(fecha_creacion) = CURDATE()
    ORDER BY fecha_creacion DESC
");
$licitaciones_hoy = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($licitaciones_hoy) > 0) {
    // Obtener usuarios con frecuencia 'diaria'
    $stmt = $conn->query("
        SELECT id, nombre, correo, intereses
        FROM usuarios
        WHERE es_admin = 0
        AND activo = 1
        AND estado_pago = 1
        AND recibir_alertas_email = 1
        AND frecuencia_notificaciones = 'diaria'
    ");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($usuarios as $usuario) {
        $intereses = !empty($usuario['intereses']) ? explode(',', $usuario['intereses']) : [];

        // Filtrar licitaciones que coincidan con intereses
        $licitaciones_usuario = [];
        foreach ($licitaciones_hoy as $lic) {
            if (empty($intereses) || in_array($lic['categoria'], $intereses)) {
                $licitaciones_usuario[] = $lic;
            }
        }

        if (count($licitaciones_usuario) > 0) {
            // Crear email de resumen
            $contenido = '<h2>Resumen Diario de Licitaciones</h2>';
            $contenido .= '<p>Hola <strong>' . htmlspecialchars($usuario['nombre']) . '</strong>,</p>';
            $contenido .= '<p>Estas son las ' . count($licitaciones_usuario) . ' licitaciones publicadas hoy que coinciden con tus intereses:</p>';

            foreach ($licitaciones_usuario as $lic) {
                $contenido .= '
                <div style="border-left: 4px solid #2563eb; padding: 15px; margin: 15px 0; background: #eff6ff;">
                    <h3 style="margin: 0 0 10px 0; color: #1e40af;">' . htmlspecialchars($lic['titulo']) . '</h3>
                    <p style="margin: 5px 0;"><strong>Categoría:</strong> ' . htmlspecialchars($lic['categoria']) . '</p>
                    <p style="margin: 5px 0;"><strong>Institución:</strong> ' . htmlspecialchars($lic['institucion']) . '</p>
                    <a href="https://licitacionesya.com/user/licitacion.php?id=' . $lic['id'] . '">Ver detalles</a>
                </div>';
            }

            $html = plantilla_email_base($contenido, 'Resumen Diario');
            enviar_email($usuario['correo'], $usuario['nombre'], 'Resumen Diario de Licitaciones', $html);
        }
    }
}
*/

// ═══════════════════════════════════════════════════════════════════════
// EJEMPLO 10: PRUEBA RÁPIDA
// ═══════════════════════════════════════════════════════════════════════
/*
// Archivo de prueba simple para verificar que todo funciona

<?php
require_once 'config_email.php';
require_once 'email_functions.php';

echo "=== PRUEBA DEL SISTEMA DE NOTIFICACIONES ===\n\n";

// 1. Probar conexión básica
echo "1. Configuración SMTP:\n";
echo "   Host: " . SMTP_HOST . "\n";
echo "   Puerto: " . SMTP_PORT . "\n";
echo "   Usuario: " . SMTP_USERNAME . "\n\n";

// 2. Probar envío
echo "2. Enviando email de prueba...\n";
$email_prueba = "tu-email@ejemplo.com"; // CAMBIAR

if (enviar_email_bienvenida($email_prueba, "Usuario de Prueba")) {
    echo "   ✓ Email enviado correctamente a $email_prueba\n";
} else {
    echo "   ✗ Error al enviar email\n";
}

echo "\n3. Revisa tu bandeja de entrada (y spam)\n";
echo "\nPrueba completada.\n";
?>
*/

echo "Este es un archivo de ejemplos. Lee los comentarios para ver cómo integrar el sistema de notificaciones.";
