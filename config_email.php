<?php
/**
 * ═══════════════════════════════════════════════════════════════════════
 * CONFIGURACIÓN DE EMAIL - LicitacionesYA
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Este archivo contiene la configuración SMTP para envío de emails
 * Soporta Gmail, Outlook, SMTP personalizado y otros proveedores
 */

// ═══════════════════════════════════════════════════════════════════════
// CONFIGURACIÓN SMTP
// ═══════════════════════════════════════════════════════════════════════

define('SMTP_ENABLED', true); // true = usar SMTP, false = usar mail() de PHP

// Datos del servidor SMTP
define('SMTP_HOST', 'smtp.gmail.com'); // Gmail, o tu servidor SMTP
define('SMTP_PORT', 587); // 587 para TLS, 465 para SSL
define('SMTP_SECURE', 'tls'); // 'tls' o 'ssl'
define('SMTP_AUTH', true); // Requiere autenticación

// Credenciales de email
define('SMTP_USERNAME', 'tu-email@gmail.com'); // ⚠️ CAMBIAR
define('SMTP_PASSWORD', 'tu-contraseña-app'); // ⚠️ CAMBIAR (usa contraseña de aplicación para Gmail)

// Remitente
define('EMAIL_FROM', 'noreply@licitacionesya.com'); // Email del remitente
define('EMAIL_FROM_NAME', 'LicitacionesYA'); // Nombre del remitente

// Configuración de reintentos
define('EMAIL_MAX_RETRIES', 3); // Número de reintentos si falla
define('EMAIL_RETRY_DELAY', 2); // Segundos entre reintentos

// Debug (solo en desarrollo)
define('EMAIL_DEBUG', false); // true = mostrar debug, false = silencioso

// ═══════════════════════════════════════════════════════════════════════
// PLANTILLAS PRECONFIGURADAS POR PROVEEDOR
// ═══════════════════════════════════════════════════════════════════════

// Para usar Gmail: Activar "Acceso de apps menos seguras" o generar "Contraseña de aplicación"
// https://myaccount.google.com/apppasswords

$EMAIL_PROVIDERS = [
    'gmail' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'secure' => 'tls',
    ],
    'outlook' => [
        'host' => 'smtp-mail.outlook.com',
        'port' => 587,
        'secure' => 'tls',
    ],
    'yahoo' => [
        'host' => 'smtp.mail.yahoo.com',
        'port' => 465,
        'secure' => 'ssl',
    ],
    'custom' => [
        'host' => 'mail.tudominio.com',
        'port' => 587,
        'secure' => 'tls',
    ]
];

// ═══════════════════════════════════════════════════════════════════════
// TIPOS DE NOTIFICACIONES ACTIVADAS
// ═══════════════════════════════════════════════════════════════════════

define('NOTIF_PAGO_PROXIMO', true); // Aviso de pago próximo a vencer
define('NOTIF_PAGO_VENCIDO', true); // Aviso de pago vencido
define('NOTIF_CUENTA_DESACTIVADA', true); // Aviso de cuenta desactivada
define('NOTIF_CUENTA_REACTIVADA', true); // Aviso de cuenta reactivada
define('NOTIF_BIENVENIDA', true); // Email de bienvenida
define('NOTIF_RECUPERACION', true); // Recuperación de contraseña

// Días de anticipación para recordatorios
define('DIAS_AVISO_PAGO', 7); // Avisar 7 días antes del vencimiento

// ═══════════════════════════════════════════════════════════════════════
// FUNCIÓN AUXILIAR PARA LOGS
// ═══════════════════════════════════════════════════════════════════════

function log_email($mensaje, $tipo = 'info') {
    $log_file = __DIR__ . '/logs/email_log.txt';
    $dir = dirname($log_file);

    if (!file_exists($dir)) {
        @mkdir($dir, 0755, true);
    }

    $fecha = date('Y-m-d H:i:s');
    $linea = "[{$fecha}] [{$tipo}] {$mensaje}\n";

    @file_put_contents($log_file, $linea, FILE_APPEND);
}

// ═══════════════════════════════════════════════════════════════════════
// INSTRUCCIONES DE CONFIGURACIÓN
// ═══════════════════════════════════════════════════════════════════════
/*

📧 CÓMO CONFIGURAR GMAIL:

1. Ve a tu cuenta de Google: https://myaccount.google.com
2. Seguridad > Verificación en 2 pasos (actívala si no la tienes)
3. Contraseñas de aplicaciones: https://myaccount.google.com/apppasswords
4. Genera una nueva contraseña de aplicación
5. Copia esa contraseña (sin espacios) y úsala en SMTP_PASSWORD
6. Usa tu email completo en SMTP_USERNAME

Ejemplo:
define('SMTP_USERNAME', 'tuempresa@gmail.com');
define('SMTP_PASSWORD', 'abcd efgh ijkl mnop'); // La que generaste

📧 CÓMO CONFIGURAR HOSTING COMPARTIDO:

Si tu hosting tiene email incluido (ej: contacto@licitacionesya.com):

1. Ve al panel de control de tu hosting (cPanel, Plesk, etc)
2. Busca la sección de "Email" o "Cuentas de correo"
3. Encuentra los datos SMTP (generalmente en "Configurar cliente de correo")
4. Usa esos datos:

define('SMTP_HOST', 'mail.licitacionesya.com'); // Tu servidor
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'noreply@licitacionesya.com');
define('SMTP_PASSWORD', 'tu-contraseña-cpanel');

📧 PROBAR LA CONFIGURACIÓN:

Después de configurar, ve a:
/admin/notificaciones.php

Ahí podrás:
- Probar el envío de emails
- Ver logs de errores
- Enviar emails de prueba

*/
