<?php
/**
 * ═══════════════════════════════════════════════════════════════════════
 * SCRIPT CRON - RECORDATORIOS AUTOMÁTICOS
 * ═══════════════════════════════════════════════════════════════════════
 * Este script debe ejecutarse diariamente para enviar recordatorios
 * automáticos de pago a usuarios cuyas suscripciones están por vencer.
 *
 * CONFIGURACIÓN DE CRON:
 * Ejecutar todos los días a las 9:00 AM:
 * 0 9 * * * /usr/bin/php /ruta/completa/a/cron_recordatorios.php
 *
 * O ejecutar cada hora:
 * 0 * * * * /usr/bin/php /ruta/completa/a/cron_recordatorios.php
 */

// Solo permitir ejecución desde línea de comandos o configurar una clave secreta
if (php_sapi_name() !== 'cli') {
    // Si se ejecuta desde web, verificar clave secreta
    $clave_secreta = 'tu_clave_secreta_aqui_cambiar'; // ⚠️ CAMBIAR ESTO

    if (!isset($_GET['key']) || $_GET['key'] !== $clave_secreta) {
        http_response_code(403);
        die('Acceso denegado');
    }
}

// Evitar timeout
set_time_limit(300); // 5 minutos máximo

// Log de inicio
echo "[" . date('Y-m-d H:i:s') . "] Iniciando proceso de recordatorios automáticos\n";

// Incluir archivos necesarios
require_once __DIR__ . '/config_email.php';
require_once __DIR__ . '/email_functions.php';

// ═══════════════════════════════════════════════════════════════════════
// CONECTAR A BASE DE DATOS
// ═══════════════════════════════════════════════════════════════════════

// Usar tu archivo de configuración real
if (file_exists(__DIR__ . '/config/db.php')) {
    require_once __DIR__ . '/config/db.php';
} else {
    // Configuración de ejemplo si no existe config/db.php
    try {
        $conn = new PDO(
            "mysql:host=localhost;dbname=tu_base_datos;charset=utf8mb4",
            "tu_usuario",
            "tu_contraseña",
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    } catch (PDOException $e) {
        log_email("Error de conexión a BD: " . $e->getMessage(), 'error');
        die("Error de conexión a base de datos\n");
    }
}

// ═══════════════════════════════════════════════════════════════════════
// PROCESAR RECORDATORIOS DE PAGO
// ═══════════════════════════════════════════════════════════════════════

echo "[" . date('Y-m-d H:i:s') . "] Buscando usuarios con pagos próximos a vencer...\n";

try {
    // Buscar usuarios con pagos próximos a vencer (7, 3, 1 día antes)
    $stmt = $conn->prepare("
        SELECT
            id,
            nombre,
            correo,
            monto_pagar,
            fecha_proximo_pago,
            DATEDIFF(fecha_proximo_pago, CURDATE()) as dias_restantes
        FROM usuarios
        WHERE es_admin = 0
        AND fecha_proximo_pago IS NOT NULL
        AND fecha_proximo_pago >= CURDATE()
        AND DATEDIFF(fecha_proximo_pago, CURDATE()) IN (7, 3, 1)
        AND activo = 1
    ");

    $stmt->execute();
    $usuarios_recordatorio = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "[" . date('Y-m-d H:i:s') . "] Encontrados " . count($usuarios_recordatorio) . " usuarios para recordatorio\n";

    $enviados = 0;
    $errores = 0;

    foreach ($usuarios_recordatorio as $usuario) {
        // Verificar si ya se envió recordatorio hoy
        $stmt_check = $conn->prepare("
            SELECT id
            FROM recordatorios_pago
            WHERE usuario_id = ?
            AND DATE(fecha_envio) = CURDATE()
        ");
        $stmt_check->execute([$usuario['id']]);

        if ($stmt_check->rowCount() > 0) {
            echo "[" . date('Y-m-d H:i:s') . "] Ya se envió recordatorio hoy al usuario {$usuario['id']}\n";
            continue;
        }

        // Enviar recordatorio
        echo "[" . date('Y-m-d H:i:s') . "] Enviando recordatorio a {$usuario['correo']} ({$usuario['dias_restantes']} días)...\n";

        if (enviar_recordatorio_pago(
            $usuario['correo'],
            $usuario['nombre'],
            $usuario['dias_restantes'],
            $usuario['monto_pagar'],
            date('d/m/Y', strtotime($usuario['fecha_proximo_pago']))
        )) {
            // Registrar envío en la base de datos
            try {
                $stmt_log = $conn->prepare("
                    INSERT INTO recordatorios_pago
                    (usuario_id, tipo_recordatorio, fecha_envio, dias_anticipacion)
                    VALUES (?, 'pago_proximo', NOW(), ?)
                ");
                $stmt_log->execute([$usuario['id'], $usuario['dias_restantes']]);

                $enviados++;
                echo "[" . date('Y-m-d H:i:s') . "] ✓ Recordatorio enviado a {$usuario['correo']}\n";
            } catch (PDOException $e) {
                log_email("Error al registrar recordatorio: " . $e->getMessage(), 'error');
                echo "[" . date('Y-m-d H:i:s') . "] ⚠ Email enviado pero no se pudo registrar en BD\n";
            }
        } else {
            $errores++;
            echo "[" . date('Y-m-d H:i:s') . "] ✗ Error enviando a {$usuario['correo']}\n";
        }

        // Pausa breve para no saturar el servidor SMTP
        usleep(500000); // 0.5 segundos
    }

    echo "[" . date('Y-m-d H:i:s') . "] Recordatorios de pago: $enviados enviados, $errores errores\n";

} catch (PDOException $e) {
    log_email("Error en cron de recordatorios: " . $e->getMessage(), 'error');
    echo "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
}

// ═══════════════════════════════════════════════════════════════════════
// PROCESAR PAGOS VENCIDOS
// ═══════════════════════════════════════════════════════════════════════

echo "[" . date('Y-m-d H:i:s') . "] Buscando usuarios con pagos vencidos...\n";

try {
    // Buscar usuarios con pagos vencidos
    $stmt = $conn->prepare("
        SELECT
            id,
            nombre,
            correo,
            monto_pagar,
            fecha_proximo_pago,
            DATEDIFF(CURDATE(), fecha_proximo_pago) as dias_vencidos
        FROM usuarios
        WHERE es_admin = 0
        AND fecha_proximo_pago IS NOT NULL
        AND fecha_proximo_pago < CURDATE()
        AND activo = 1
        AND DATEDIFF(CURDATE(), fecha_proximo_pago) IN (1, 3, 7)
    ");

    $stmt->execute();
    $usuarios_vencidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "[" . date('Y-m-d H:i:s') . "] Encontrados " . count($usuarios_vencidos) . " usuarios con pagos vencidos\n";

    $enviados_vencidos = 0;

    foreach ($usuarios_vencidos as $usuario) {
        // Verificar si ya se envió notificación de vencido hoy
        $stmt_check = $conn->prepare("
            SELECT id
            FROM recordatorios_pago
            WHERE usuario_id = ?
            AND tipo_recordatorio = 'pago_vencido'
            AND DATE(fecha_envio) = CURDATE()
        ");
        $stmt_check->execute([$usuario['id']]);

        if ($stmt_check->rowCount() > 0) {
            continue;
        }

        // Enviar notificación de pago vencido
        echo "[" . date('Y-m-d H:i:s') . "] Enviando notificación de vencido a {$usuario['correo']}...\n";

        if (enviar_recordatorio_pago(
            $usuario['correo'],
            $usuario['nombre'],
            -$usuario['dias_vencidos'], // Negativo para indicar vencido
            $usuario['monto_pagar'],
            date('d/m/Y', strtotime($usuario['fecha_proximo_pago']))
        )) {
            try {
                $stmt_log = $conn->prepare("
                    INSERT INTO recordatorios_pago
                    (usuario_id, tipo_recordatorio, fecha_envio, dias_anticipacion)
                    VALUES (?, 'pago_vencido', NOW(), ?)
                ");
                $stmt_log->execute([$usuario['id'], -$usuario['dias_vencidos']]);

                $enviados_vencidos++;
                echo "[" . date('Y-m-d H:i:s') . "] ✓ Notificación de vencido enviada\n";
            } catch (PDOException $e) {
                log_email("Error al registrar notificación de vencido: " . $e->getMessage(), 'error');
            }
        }

        usleep(500000); // 0.5 segundos
    }

    echo "[" . date('Y-m-d H:i:s') . "] Notificaciones de vencidos: $enviados_vencidos enviadas\n";

} catch (PDOException $e) {
    log_email("Error procesando vencidos: " . $e->getMessage(), 'error');
    echo "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
}

// ═══════════════════════════════════════════════════════════════════════
// DESACTIVAR USUARIOS CON PAGO VENCIDO MÁS DE 7 DÍAS
// ═══════════════════════════════════════════════════════════════════════

echo "[" . date('Y-m-d H:i:s') . "] Buscando usuarios para desactivar (>7 días vencidos)...\n";

try {
    $stmt = $conn->prepare("
        SELECT id, nombre, correo
        FROM usuarios
        WHERE es_admin = 0
        AND activo = 1
        AND fecha_proximo_pago IS NOT NULL
        AND DATEDIFF(CURDATE(), fecha_proximo_pago) > 7
    ");

    $stmt->execute();
    $usuarios_desactivar = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "[" . date('Y-m-d H:i:s') . "] Encontrados " . count($usuarios_desactivar) . " usuarios para desactivar\n";

    foreach ($usuarios_desactivar as $usuario) {
        // Desactivar usuario
        $stmt_update = $conn->prepare("UPDATE usuarios SET activo = 0 WHERE id = ?");
        $stmt_update->execute([$usuario['id']]);

        // Enviar notificación de desactivación
        enviar_notificacion_cuenta_desactivada($usuario['correo'], $usuario['nombre'], 'falta de pago');

        echo "[" . date('Y-m-d H:i:s') . "] Usuario {$usuario['correo']} desactivado\n";

        usleep(500000);
    }

} catch (PDOException $e) {
    log_email("Error desactivando usuarios: " . $e->getMessage(), 'error');
    echo "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
}

// ═══════════════════════════════════════════════════════════════════════
// RESUMEN FINAL
// ═══════════════════════════════════════════════════════════════════════

echo "\n" . str_repeat("=", 70) . "\n";
echo "RESUMEN DE EJECUCIÓN\n";
echo str_repeat("=", 70) . "\n";
echo "Recordatorios enviados: $enviados\n";
echo "Notificaciones de vencidos: $enviados_vencidos\n";
echo "Usuarios desactivados: " . count($usuarios_desactivar) . "\n";
echo "Errores: $errores\n";
echo "Fecha y hora de finalización: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 70) . "\n";

log_email("Cron finalizado: $enviados recordatorios, $enviados_vencidos vencidos, " . count($usuarios_desactivar) . " desactivados", 'info');
