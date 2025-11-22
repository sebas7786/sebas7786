<?php
/**
 * ═══════════════════════════════════════════════════════════════════════
 * FUNCIONES DE EMAIL - LicitacionesYA
 * ═══════════════════════════════════════════════════════════════════════
 * Sistema completo de notificaciones por correo electrónico
 */

// Incluir configuración de email
require_once __DIR__ . '/config_email.php';

/**
 * ═══════════════════════════════════════════════════════════════════════
 * FUNCIÓN PRINCIPAL DE ENVÍO DE EMAILS
 * ═══════════════════════════════════════════════════════════════════════
 */
function enviar_email($destinatario, $nombre_destinatario, $asunto, $cuerpo_html, $cuerpo_texto = '') {

    // Si SMTP no está habilitado, usar mail() de PHP
    if (!SMTP_ENABLED) {
        return enviar_email_simple($destinatario, $asunto, $cuerpo_html);
    }

    // Validar email
    if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
        log_email("Email inválido: $destinatario", 'error');
        return false;
    }

    // Preparar headers
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . EMAIL_FROM_NAME . " <" . EMAIL_FROM . ">\r\n";
    $headers .= "Reply-To: " . EMAIL_FROM . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    // Intentar enviar con reintentos
    $intentos = 0;
    $enviado = false;

    while ($intentos < EMAIL_MAX_RETRIES && !$enviado) {
        $intentos++;

        try {
            // Usar SMTP con fsockopen
            $enviado = enviar_email_smtp($destinatario, $asunto, $cuerpo_html, $headers);

            if ($enviado) {
                log_email("Email enviado exitosamente a $destinatario - Asunto: $asunto", 'success');
                return true;
            } else {
                if ($intentos < EMAIL_MAX_RETRIES) {
                    sleep(EMAIL_RETRY_DELAY);
                    log_email("Reintentando envío a $destinatario (intento $intentos)", 'warning');
                }
            }
        } catch (Exception $e) {
            log_email("Error enviando email: " . $e->getMessage(), 'error');
            if ($intentos < EMAIL_MAX_RETRIES) {
                sleep(EMAIL_RETRY_DELAY);
            }
        }
    }

    if (!$enviado) {
        log_email("Fallo definitivo al enviar email a $destinatario después de $intentos intentos", 'error');
    }

    return $enviado;
}

/**
 * Envío de email usando PHP mail() simple
 */
function enviar_email_simple($destinatario, $asunto, $cuerpo_html) {
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . EMAIL_FROM_NAME . " <" . EMAIL_FROM . ">\r\n";

    $resultado = @mail($destinatario, $asunto, $cuerpo_html, $headers);

    if ($resultado) {
        log_email("Email enviado (mail simple) a $destinatario", 'success');
    } else {
        log_email("Error enviando email (mail simple) a $destinatario", 'error');
    }

    return $resultado;
}

/**
 * Envío de email usando SMTP con fsockopen
 */
function enviar_email_smtp($destinatario, $asunto, $cuerpo_html, $headers) {
    // Esta es una implementación básica con fsockopen
    // Para producción, se recomienda usar PHPMailer

    $smtp_host = SMTP_HOST;
    $smtp_port = SMTP_PORT;
    $smtp_user = SMTP_USERNAME;
    $smtp_pass = SMTP_PASSWORD;

    // Conectar al servidor SMTP
    $socket = @fsockopen($smtp_host, $smtp_port, $errno, $errstr, 10);

    if (!$socket) {
        log_email("No se pudo conectar a SMTP: $errstr ($errno)", 'error');
        return false;
    }

    // Leer respuesta inicial
    $response = fgets($socket, 515);
    if (EMAIL_DEBUG) {
        log_email("SMTP Response: $response", 'debug');
    }

    // Enviar EHLO
    fputs($socket, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
    $response = fgets($socket, 515);

    // STARTTLS si es necesario
    if (SMTP_SECURE === 'tls') {
        fputs($socket, "STARTTLS\r\n");
        $response = fgets($socket, 515);

        if (function_exists('stream_socket_enable_crypto')) {
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        }

        fputs($socket, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
        $response = fgets($socket, 515);
    }

    // Autenticación
    if (SMTP_AUTH) {
        fputs($socket, "AUTH LOGIN\r\n");
        $response = fgets($socket, 515);

        fputs($socket, base64_encode($smtp_user) . "\r\n");
        $response = fgets($socket, 515);

        fputs($socket, base64_encode($smtp_pass) . "\r\n");
        $response = fgets($socket, 515);

        if (strpos($response, '235') === false) {
            log_email("Error de autenticación SMTP", 'error');
            fclose($socket);
            return false;
        }
    }

    // Enviar email
    fputs($socket, "MAIL FROM: <" . EMAIL_FROM . ">\r\n");
    $response = fgets($socket, 515);

    fputs($socket, "RCPT TO: <$destinatario>\r\n");
    $response = fgets($socket, 515);

    fputs($socket, "DATA\r\n");
    $response = fgets($socket, 515);

    // Preparar mensaje
    $mensaje = "From: " . EMAIL_FROM_NAME . " <" . EMAIL_FROM . ">\r\n";
    $mensaje .= "To: $destinatario\r\n";
    $mensaje .= "Subject: $asunto\r\n";
    $mensaje .= $headers . "\r\n";
    $mensaje .= $cuerpo_html . "\r\n";
    $mensaje .= ".\r\n";

    fputs($socket, $mensaje);
    $response = fgets($socket, 515);

    // Cerrar conexión
    fputs($socket, "QUIT\r\n");
    fclose($socket);

    return true;
}

/**
 * ═══════════════════════════════════════════════════════════════════════
 * PLANTILLAS DE EMAILS
 * ═══════════════════════════════════════════════════════════════════════
 */

/**
 * Plantilla base HTML para todos los emails
 */
function plantilla_email_base($contenido, $titulo = 'LicitacionesYA') {
    return '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($titulo) . '</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f4f4f4; padding: 20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); padding: 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: 700;">
                                📋 LicitacionesYA
                            </h1>
                            <p style="color: #e0e7ff; margin: 10px 0 0 0; font-size: 14px;">
                                Tu plataforma de licitaciones en Costa Rica
                            </p>
                        </td>
                    </tr>

                    <!-- Contenido -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            ' . $contenido . '
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 20px 30px; text-align: center; border-top: 1px solid #e9ecef;">
                            <p style="color: #6c757d; font-size: 12px; margin: 0;">
                                LicitacionesYA - Costa Rica<br>
                                Este es un correo automático, por favor no responder.
                            </p>
                            <p style="color: #6c757d; font-size: 11px; margin: 10px 0 0 0;">
                                <a href="https://licitacionesya.com" style="color: #2563eb; text-decoration: none;">Visitar sitio web</a> |
                                <a href="https://licitacionesya.com/user/configuracion.php" style="color: #2563eb; text-decoration: none;">Configurar notificaciones</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
}

/**
 * ═══════════════════════════════════════════════════════════════════════
 * NOTIFICACIÓN: NUEVA LICITACIÓN
 * ═══════════════════════════════════════════════════════════════════════
 */
function enviar_notificacion_nueva_licitacion($usuario_email, $usuario_nombre, $licitacion) {
    $asunto = "Nueva Licitación: " . substr($licitacion['titulo'], 0, 50);

    $contenido = '
        <h2 style="color: #1d4ed8; margin-top: 0;">¡Nueva Licitación Disponible!</h2>
        <p style="color: #374151; font-size: 16px; line-height: 1.6;">
            Hola <strong>' . htmlspecialchars($usuario_nombre) . '</strong>,
        </p>
        <p style="color: #374151; font-size: 16px; line-height: 1.6;">
            Hemos encontrado una nueva licitación que coincide con tus intereses:
        </p>

        <div style="background-color: #eff6ff; border-left: 4px solid #2563eb; padding: 20px; margin: 20px 0; border-radius: 4px;">
            <h3 style="color: #1e40af; margin-top: 0; font-size: 18px;">
                ' . htmlspecialchars($licitacion['titulo']) . '
            </h3>
            <p style="color: #1e40af; margin: 10px 0;">
                <strong>Categoría:</strong> ' . htmlspecialchars($licitacion['categoria']) . '
            </p>
            <p style="color: #1e40af; margin: 10px 0;">
                <strong>Institución:</strong> ' . htmlspecialchars($licitacion['institucion']) . '
            </p>
            ' . (!empty($licitacion['fecha_cierre']) ? '
            <p style="color: #dc2626; margin: 10px 0; font-weight: 600;">
                ⏰ Cierre: ' . htmlspecialchars($licitacion['fecha_cierre']) . ' ' . htmlspecialchars($licitacion['hora_cierre']) . '
            </p>
            ' : '') . '
        </div>

        <p style="text-align: center; margin: 30px 0;">
            <a href="https://licitacionesya.com/user/licitacion.php?id=' . $licitacion['id'] . '"
               style="background-color: #2563eb; color: #ffffff; padding: 14px 30px; text-decoration: none; border-radius: 6px; display: inline-block; font-weight: 600;">
                Ver Licitación Completa
            </a>
        </p>

        <p style="color: #6b7280; font-size: 14px; line-height: 1.6;">
            No pierdas esta oportunidad de participar en esta licitación.
        </p>';

    $html = plantilla_email_base($contenido, 'Nueva Licitación - LicitacionesYA');

    return enviar_email($usuario_email, $usuario_nombre, $asunto, $html);
}

/**
 * ═══════════════════════════════════════════════════════════════════════
 * NOTIFICACIÓN: RECORDATORIO DE PAGO
 * ═══════════════════════════════════════════════════════════════════════
 */
function enviar_recordatorio_pago($usuario_email, $usuario_nombre, $dias_restantes, $monto, $fecha_vencimiento) {
    if ($dias_restantes > 0) {
        $asunto = "Recordatorio: Tu pago vence en $dias_restantes días";
        $urgencia_color = $dias_restantes <= 3 ? '#dc2626' : '#f59e0b';
        $urgencia_texto = $dias_restantes <= 3 ? '¡URGENTE!' : 'Recordatorio';
    } else {
        $asunto = "URGENTE: Tu pago ha vencido";
        $urgencia_color = '#dc2626';
        $urgencia_texto = '¡PAGO VENCIDO!';
    }

    $contenido = '
        <div style="background-color: ' . $urgencia_color . '; color: white; padding: 15px; text-align: center; border-radius: 6px; margin-bottom: 20px;">
            <strong style="font-size: 18px;">' . $urgencia_texto . '</strong>
        </div>

        <h2 style="color: #1d4ed8; margin-top: 0;">Recordatorio de Pago</h2>
        <p style="color: #374151; font-size: 16px; line-height: 1.6;">
            Hola <strong>' . htmlspecialchars($usuario_nombre) . '</strong>,
        </p>

        ' . ($dias_restantes > 0 ? '
        <p style="color: #374151; font-size: 16px; line-height: 1.6;">
            Tu suscripción a LicitacionesYA vence en <strong style="color: ' . $urgencia_color . ';">' . $dias_restantes . ' días</strong>.
        </p>
        ' : '
        <p style="color: #dc2626; font-size: 16px; line-height: 1.6; font-weight: 600;">
            Tu suscripción a LicitacionesYA ha vencido.
        </p>
        ') . '

        <div style="background-color: #f8f9fa; border: 2px solid #dee2e6; padding: 20px; margin: 20px 0; border-radius: 6px;">
            <table width="100%" cellpadding="5" cellspacing="0">
                <tr>
                    <td style="color: #6c757d; font-size: 14px;"><strong>Monto a pagar:</strong></td>
                    <td style="color: #1d4ed8; font-size: 18px; font-weight: 700; text-align: right;">₡' . number_format($monto, 2) . '</td>
                </tr>
                <tr>
                    <td style="color: #6c757d; font-size: 14px;"><strong>Fecha de vencimiento:</strong></td>
                    <td style="color: #374151; font-size: 16px; text-align: right;">' . htmlspecialchars($fecha_vencimiento) . '</td>
                </tr>
            </table>
        </div>

        <p style="color: #374151; font-size: 16px; line-height: 1.6;">
            Para mantener tu acceso a todas las licitaciones y no perder oportunidades de negocio,
            por favor realiza tu pago lo antes posible.
        </p>

        <p style="text-align: center; margin: 30px 0;">
            <a href="https://licitacionesya.com/user/pagos.php"
               style="background-color: #10b981; color: #ffffff; padding: 14px 30px; text-decoration: none; border-radius: 6px; display: inline-block; font-weight: 600;">
                Realizar Pago Ahora
            </a>
        </p>

        <p style="color: #6b7280; font-size: 14px; line-height: 1.6;">
            Si ya realizaste el pago, por favor ignora este mensaje. El sistema se actualizará en breve.
        </p>

        <p style="color: #6b7280; font-size: 14px; line-height: 1.6;">
            Para cualquier consulta, contáctanos respondiendo a este correo o llamando al soporte.
        </p>';

    $html = plantilla_email_base($contenido, 'Recordatorio de Pago - LicitacionesYA');

    return enviar_email($usuario_email, $usuario_nombre, $asunto, $html);
}

/**
 * ═══════════════════════════════════════════════════════════════════════
 * NOTIFICACIÓN: CUENTA DESACTIVADA
 * ═══════════════════════════════════════════════════════════════════════
 */
function enviar_notificacion_cuenta_desactivada($usuario_email, $usuario_nombre, $razon = 'falta de pago') {
    $asunto = "Tu cuenta ha sido desactivada";

    $contenido = '
        <div style="background-color: #dc2626; color: white; padding: 15px; text-align: center; border-radius: 6px; margin-bottom: 20px;">
            <strong style="font-size: 18px;">⚠️ CUENTA DESACTIVADA</strong>
        </div>

        <h2 style="color: #dc2626; margin-top: 0;">Cuenta Desactivada</h2>
        <p style="color: #374151; font-size: 16px; line-height: 1.6;">
            Hola <strong>' . htmlspecialchars($usuario_nombre) . '</strong>,
        </p>
        <p style="color: #374151; font-size: 16px; line-height: 1.6;">
            Lamentamos informarte que tu cuenta en LicitacionesYA ha sido desactivada por: <strong>' . htmlspecialchars($razon) . '</strong>.
        </p>

        <div style="background-color: #fef2f2; border-left: 4px solid #dc2626; padding: 20px; margin: 20px 0; border-radius: 4px;">
            <p style="color: #991b1b; margin: 0; font-size: 15px; line-height: 1.6;">
                <strong>¿Qué significa esto?</strong><br>
                • No podrás acceder a las licitaciones publicadas<br>
                • No recibirás notificaciones de nuevas oportunidades<br>
                • Tu perfil permanecerá inactivo hasta que regularices tu situación
            </p>
        </div>

        <p style="color: #374151; font-size: 16px; line-height: 1.6;">
            <strong>¿Cómo reactivar tu cuenta?</strong><br>
            Contáctanos para regularizar tu situación y recuperar el acceso a todas las funcionalidades.
        </p>

        <p style="text-align: center; margin: 30px 0;">
            <a href="https://licitacionesya.com/contacto.php"
               style="background-color: #2563eb; color: #ffffff; padding: 14px 30px; text-decoration: none; border-radius: 6px; display: inline-block; font-weight: 600;">
                Contactar Soporte
            </a>
        </p>';

    $html = plantilla_email_base($contenido, 'Cuenta Desactivada - LicitacionesYA');

    return enviar_email($usuario_email, $usuario_nombre, $asunto, $html);
}

/**
 * ═══════════════════════════════════════════════════════════════════════
 * NOTIFICACIÓN: CUENTA REACTIVADA
 * ═══════════════════════════════════════════════════════════════════════
 */
function enviar_notificacion_cuenta_reactivada($usuario_email, $usuario_nombre) {
    $asunto = "¡Tu cuenta ha sido reactivada!";

    $contenido = '
        <div style="background-color: #10b981; color: white; padding: 15px; text-align: center; border-radius: 6px; margin-bottom: 20px;">
            <strong style="font-size: 18px;">✅ CUENTA REACTIVADA</strong>
        </div>

        <h2 style="color: #10b981; margin-top: 0;">¡Bienvenido de Nuevo!</h2>
        <p style="color: #374151; font-size: 16px; line-height: 1.6;">
            Hola <strong>' . htmlspecialchars($usuario_nombre) . '</strong>,
        </p>
        <p style="color: #374151; font-size: 16px; line-height: 1.6;">
            ¡Excelentes noticias! Tu cuenta en LicitacionesYA ha sido reactivada exitosamente.
        </p>

        <div style="background-color: #ecfdf5; border-left: 4px solid #10b981; padding: 20px; margin: 20px 0; border-radius: 4px;">
            <p style="color: #065f46; margin: 0; font-size: 15px; line-height: 1.6;">
                <strong>Ya puedes:</strong><br>
                ✓ Acceder a todas las licitaciones publicadas<br>
                ✓ Recibir notificaciones de nuevas oportunidades<br>
                ✓ Consultar el histórico de adjudicaciones<br>
                ✓ Configurar tus alertas personalizadas
            </p>
        </div>

        <p style="text-align: center; margin: 30px 0;">
            <a href="https://licitacionesya.com/login.php"
               style="background-color: #2563eb; color: #ffffff; padding: 14px 30px; text-decoration: none; border-radius: 6px; display: inline-block; font-weight: 600;">
                Ingresar a Mi Cuenta
            </a>
        </p>

        <p style="color: #6b7280; font-size: 14px; line-height: 1.6;">
            Gracias por tu confianza en LicitacionesYA. Estamos aquí para ayudarte a encontrar las mejores oportunidades de negocio.
        </p>';

    $html = plantilla_email_base($contenido, 'Cuenta Reactivada - LicitacionesYA');

    return enviar_email($usuario_email, $usuario_nombre, $asunto, $html);
}

/**
 * ═══════════════════════════════════════════════════════════════════════
 * NOTIFICACIÓN: BIENVENIDA
 * ═══════════════════════════════════════════════════════════════════════
 */
function enviar_email_bienvenida($usuario_email, $usuario_nombre) {
    $asunto = "¡Bienvenido a LicitacionesYA!";

    $contenido = '
        <h2 style="color: #1d4ed8; margin-top: 0;">¡Bienvenido a LicitacionesYA!</h2>
        <p style="color: #374151; font-size: 16px; line-height: 1.6;">
            Hola <strong>' . htmlspecialchars($usuario_nombre) . '</strong>,
        </p>
        <p style="color: #374151; font-size: 16px; line-height: 1.6;">
            ¡Gracias por unirte a LicitacionesYA! Estamos emocionados de tenerte con nosotros.
        </p>

        <div style="background-color: #eff6ff; border-left: 4px solid #2563eb; padding: 20px; margin: 20px 0; border-radius: 4px;">
            <p style="color: #1e40af; margin: 0; font-size: 15px; line-height: 1.6;">
                <strong>¿Qué puedes hacer ahora?</strong><br>
                • Explorar licitaciones públicas de Costa Rica<br>
                • Configurar alertas personalizadas<br>
                • Ver estadísticas de adjudicaciones<br>
                • Analizar la competencia
            </p>
        </div>

        <p style="text-align: center; margin: 30px 0;">
            <a href="https://licitacionesya.com/login.php"
               style="background-color: #2563eb; color: #ffffff; padding: 14px 30px; text-decoration: none; border-radius: 6px; display: inline-block; font-weight: 600;">
                Comenzar Ahora
            </a>
        </p>

        <p style="color: #6b7280; font-size: 14px; line-height: 1.6;">
            Si tienes alguna pregunta, no dudes en contactarnos. Estamos aquí para ayudarte.
        </p>';

    $html = plantilla_email_base($contenido, 'Bienvenido - LicitacionesYA');

    return enviar_email($usuario_email, $usuario_nombre, $asunto, $html);
}

/**
 * ═══════════════════════════════════════════════════════════════════════
 * FUNCIÓN PARA ENVIAR NOTIFICACIONES POR CATEGORÍA
 * (Compatible con tu código existente en ai-chat.php)
 * ═══════════════════════════════════════════════════════════════════════
 */
function enviar_notificaciones_correo($licitacion_id, $categoria, $titulo, $conn) {
    try {
        // Obtener datos completos de la licitación
        $stmt = $conn->prepare("SELECT * FROM licitaciones WHERE id = ?");
        $stmt->execute([$licitacion_id]);
        $licitacion = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$licitacion) {
            log_email("Licitación $licitacion_id no encontrada", 'error');
            return false;
        }

        // Obtener usuarios con intereses coincidentes y que quieran recibir emails
        $stmt = $conn->prepare("
            SELECT DISTINCT u.id, u.nombre, u.correo, u.intereses
            FROM usuarios u
            WHERE u.es_admin = 0
            AND u.estado_pago = 1
            AND u.activo = 1
            AND (u.recibir_alertas_email = 1 OR u.recibir_alertas_email IS NULL)
        ");
        $stmt->execute();
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $enviados = 0;
        $errores = 0;

        foreach ($usuarios as $usuario) {
            // Verificar si el usuario tiene intereses coincidentes
            $intereses_usuario = !empty($usuario['intereses']) ? explode(',', $usuario['intereses']) : [];

            $coincide = false;
            foreach ($intereses_usuario as $interes) {
                $interes = trim($interes);
                if (strcasecmp($interes, $categoria) === 0 ||
                    stripos($categoria, $interes) !== false ||
                    stripos($interes, $categoria) !== false) {
                    $coincide = true;
                    break;
                }
            }

            // Si coincide o no tiene intereses configurados, enviar notificación
            if ($coincide || empty($intereses_usuario)) {
                if (enviar_notificacion_nueva_licitacion($usuario['correo'], $usuario['nombre'], $licitacion)) {
                    $enviados++;
                } else {
                    $errores++;
                }
            }
        }

        log_email("Notificaciones de licitación $licitacion_id: $enviados enviados, $errores errores", 'info');

        return $enviados > 0;

    } catch (Exception $e) {
        log_email("Error en enviar_notificaciones_correo: " . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * ═══════════════════════════════════════════════════════════════════════
 * FUNCIÓN PARA ENVIAR NOTIFICACIONES POR PALABRAS CLAVE
 * (Compatible con tu código existente en ai-chat.php)
 * ═══════════════════════════════════════════════════════════════════════
 */
function send_keyword_notifications($licitacion_id, $tender_text, $conn) {
    try {
        // Obtener usuarios con palabras clave configuradas
        $stmt = $conn->prepare("
            SELECT DISTINCT u.id, u.nombre, u.correo, u.palabras_clave
            FROM usuarios u
            WHERE u.es_admin = 0
            AND u.estado_pago = 1
            AND u.activo = 1
            AND u.palabras_clave IS NOT NULL
            AND u.palabras_clave != ''
            AND (u.recibir_alertas_email = 1 OR u.recibir_alertas_email IS NULL)
        ");
        $stmt->execute();
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Obtener datos de la licitación
        $stmt = $conn->prepare("SELECT * FROM licitaciones WHERE id = ?");
        $stmt->execute([$licitacion_id]);
        $licitacion = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$licitacion) {
            return false;
        }

        $enviados = 0;
        $texto_completo = strtolower($tender_text . ' ' . $licitacion['titulo'] . ' ' . $licitacion['descripcion']);

        foreach ($usuarios as $usuario) {
            $palabras_clave = explode(',', $usuario['palabras_clave']);
            $coincide = false;

            foreach ($palabras_clave as $palabra) {
                $palabra = trim(strtolower($palabra));
                if (!empty($palabra) && stripos($texto_completo, $palabra) !== false) {
                    $coincide = true;
                    break;
                }
            }

            if ($coincide) {
                if (enviar_notificacion_nueva_licitacion($usuario['correo'], $usuario['nombre'], $licitacion)) {
                    $enviados++;
                }
            }
        }

        log_email("Notificaciones por palabras clave para licitación $licitacion_id: $enviados enviados", 'info');

        return $enviados > 0;

    } catch (Exception $e) {
        log_email("Error en send_keyword_notifications: " . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * ═══════════════════════════════════════════════════════════════════════
 * FUNCIÓN PARA PROCESAR RECORDATORIOS DE PAGO AUTOMÁTICOS
 * ═══════════════════════════════════════════════════════════════════════
 */
function procesar_recordatorios_pago_automaticos($conn) {
    try {
        // Buscar usuarios cuyo pago vence en los próximos 7 días
        $stmt = $conn->prepare("
            SELECT id, nombre, correo, monto_pagar, fecha_proximo_pago,
                   DATEDIFF(fecha_proximo_pago, CURDATE()) as dias_restantes
            FROM usuarios
            WHERE es_admin = 0
            AND activo = 1
            AND fecha_proximo_pago IS NOT NULL
            AND fecha_proximo_pago >= CURDATE()
            AND DATEDIFF(fecha_proximo_pago, CURDATE()) <= 7
        ");
        $stmt->execute();
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $enviados = 0;

        foreach ($usuarios as $usuario) {
            // Verificar si ya se envió recordatorio hoy
            $stmt_check = $conn->prepare("
                SELECT id FROM recordatorios_pago
                WHERE usuario_id = ?
                AND DATE(fecha_envio) = CURDATE()
            ");
            $stmt_check->execute([$usuario['id']]);

            if ($stmt_check->rowCount() == 0) {
                // No se ha enviado hoy, enviar ahora
                if (enviar_recordatorio_pago(
                    $usuario['correo'],
                    $usuario['nombre'],
                    $usuario['dias_restantes'],
                    $usuario['monto_pagar'],
                    date('d/m/Y', strtotime($usuario['fecha_proximo_pago']))
                )) {
                    // Registrar envío
                    $stmt_log = $conn->prepare("
                        INSERT INTO recordatorios_pago (usuario_id, tipo_recordatorio, fecha_envio)
                        VALUES (?, 'pago_proximo', NOW())
                    ");
                    $stmt_log->execute([$usuario['id']]);

                    $enviados++;
                }
            }
        }

        log_email("Recordatorios automáticos de pago: $enviados enviados", 'info');

        return $enviados;

    } catch (Exception $e) {
        log_email("Error en procesar_recordatorios_pago_automaticos: " . $e->getMessage(), 'error');
        return 0;
    }
}
