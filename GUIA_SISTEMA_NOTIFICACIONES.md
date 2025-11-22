# 📧 GUÍA COMPLETA DEL SISTEMA DE NOTIFICACIONES

## LicitacionesYA - Sistema de Notificaciones por Email

---

## 📋 ÍNDICE

1. [Introducción](#introducción)
2. [Archivos del Sistema](#archivos-del-sistema)
3. [Configuración Inicial](#configuración-inicial)
4. [Configuración de Gmail](#configuración-de-gmail)
5. [Configuración de Hosting](#configuración-de-hosting)
6. [Pruebas del Sistema](#pruebas-del-sistema)
7. [Automatización con Cron](#automatización-con-cron)
8. [Tipos de Notificaciones](#tipos-de-notificaciones)
9. [Solución de Problemas](#solución-de-problemas)
10. [Base de Datos](#base-de-datos)

---

## 🎯 INTRODUCCIÓN

El sistema de notificaciones de LicitacionesYA permite enviar emails automáticos a usuarios cuando:

✅ **Se publica una nueva licitación** que coincide con sus intereses
✅ **Su pago está próximo a vencer** (7, 3, 1 día antes)
✅ **Su pago ha vencido** (1, 3, 7 días después)
✅ **Su cuenta fue desactivada** por falta de pago
✅ **Su cuenta fue reactivada**
✅ **Se registra en el sistema** (email de bienvenida)

---

## 📁 ARCHIVOS DEL SISTEMA

```
licitacionesya/
├── config_email.php              # Configuración SMTP y credenciales
├── email_functions.php           # Funciones de envío y plantillas
├── admin_notificaciones.php      # Panel de administración y pruebas
├── cron_recordatorios.php        # Script automático para cron
├── logs/
│   └── email_log.txt            # Log automático de envíos
└── GUIA_SISTEMA_NOTIFICACIONES.md  # Esta guía
```

---

## ⚙️ CONFIGURACIÓN INICIAL

### **PASO 1: Configurar Credenciales SMTP**

Edita el archivo `config_email.php` y busca estas líneas:

```php
// Credenciales de email
define('SMTP_USERNAME', 'tu-email@gmail.com'); // ⚠️ CAMBIAR
define('SMTP_PASSWORD', 'tu-contraseña-app'); // ⚠️ CAMBIAR (usa contraseña de aplicación para Gmail)

// Remitente
define('EMAIL_FROM', 'noreply@licitacionesya.com'); // Email del remitente
define('EMAIL_FROM_NAME', 'LicitacionesYA'); // Nombre del remitente
```

**Cámbialo por:**

```php
define('SMTP_USERNAME', 'contacto@licitacionesya.com');
define('SMTP_PASSWORD', 'tu_contraseña_real');
define('EMAIL_FROM', 'noreply@licitacionesya.com');
define('EMAIL_FROM_NAME', 'LicitacionesYA');
```

---

## 📧 CONFIGURACIÓN DE GMAIL

Si quieres usar Gmail para enviar los correos:

### **Opción A: Contraseña de Aplicación (Recomendado)**

1. Ve a tu cuenta de Google: https://myaccount.google.com
2. Click en **"Seguridad"**
3. Activa **"Verificación en 2 pasos"** si no la tienes
4. Busca **"Contraseñas de aplicaciones"**: https://myaccount.google.com/apppasswords
5. Genera una nueva contraseña de aplicación:
   - Aplicación: Correo
   - Dispositivo: Otro (personalizado) → "LicitacionesYA"
6. Copia la contraseña generada (16 caracteres)

### **Configuración en `config_email.php`:**

```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'tuempresa@gmail.com');
define('SMTP_PASSWORD', 'abcd efgh ijkl mnop'); // La contraseña de aplicación
```

### **Opción B: Activar Acceso de Apps Menos Seguras (No Recomendado)**

1. Ve a: https://myaccount.google.com/lesssecureapps
2. Activa **"Permitir aplicaciones menos seguras"**

**⚠️ Advertencia:** Google puede bloquear esto en cualquier momento. Usa contraseñas de aplicación en su lugar.

---

## 🌐 CONFIGURACIÓN DE HOSTING COMPARTIDO

Si tu hosting tiene email incluido (ej: `contacto@licitacionesya.com`):

1. Ingresa a tu **cPanel** o panel de control
2. Busca la sección de **"Email"** o **"Cuentas de correo"**
3. Encuentra los datos SMTP:
   - Generalmente en **"Configurar cliente de correo"** o **"Configuración manual"**

4. Anota estos datos:
   - **Servidor SMTP:** `mail.licitacionesya.com` (o similar)
   - **Puerto:** `587` (TLS) o `465` (SSL)
   - **Usuario:** `noreply@licitacionesya.com`
   - **Contraseña:** La que configuraste en cPanel

5. Edita `config_email.php`:

```php
define('SMTP_HOST', 'mail.licitacionesya.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'noreply@licitacionesya.com');
define('SMTP_PASSWORD', 'tu_contraseña_cpanel');
```

---

## 🧪 PRUEBAS DEL SISTEMA

### **PASO 1: Acceder al Panel de Notificaciones**

Abre en tu navegador:
```
https://licitacionesya.com/admin_notificaciones.php
```

### **PASO 2: Probar Configuración SMTP**

1. En la sección **"Configuración Actual"**
2. Ingresa tu email en **"Email de Prueba"**
3. Click en **"🧪 Probar Configuración SMTP"**
4. Verifica que llegue el email

✅ **Si llegó:** Configuración correcta
❌ **Si no llegó:** Revisa los logs y configuración SMTP

### **PASO 3: Probar Plantillas de Email**

Prueba cada tipo de email:

- **👋 Bienvenida** - Email para nuevos usuarios
- **📋 Nueva Licitación** - Notificación de oportunidades
- **💰 Recordatorio de Pago** - Recordatorios automáticos

Ingresa tu email y envía pruebas para ver cómo se ven.

### **PASO 4: Revisar Logs**

En el panel verás:
- **Total de Envíos**
- **Enviados Exitosamente**
- **Errores de Envío**
- **Logs Recientes** (últimos 20 eventos)

Los logs completos están en: `/logs/email_log.txt`

---

## ⏰ AUTOMATIZACIÓN CON CRON

Para que los recordatorios de pago se envíen automáticamente, debes configurar un **cron job**.

### **PASO 1: Editar el Script**

Abre `cron_recordatorios.php` y cambia esta línea:

```php
$clave_secreta = 'tu_clave_secreta_aqui_cambiar'; // ⚠️ CAMBIAR ESTO
```

Por algo seguro:
```php
$clave_secreta = 'Mi_Clave_Super_Segura_2024_XYZ';
```

### **PASO 2: Configurar Cron en cPanel**

1. Ingresa a tu **cPanel**
2. Busca **"Cron Jobs"** o **"Tareas Cron"**
3. Agrega una nueva tarea:

**Ejecutar diariamente a las 9:00 AM:**
```
0 9 * * * /usr/bin/php /home/tuusuario/public_html/cron_recordatorios.php
```

**Ejecutar cada hora:**
```
0 * * * * /usr/bin/php /home/tuusuario/public_html/cron_recordatorios.php
```

### **PASO 3: Alternativa - Ejecutar por URL (menos seguro)**

Si tu hosting no permite cron jobs, puedes ejecutarlo por URL:

```
https://licitacionesya.com/cron_recordatorios.php?key=Mi_Clave_Super_Segura_2024_XYZ
```

Usa un servicio como:
- **EasyCron** (https://www.easycron.com/)
- **cron-job.org** (https://cron-job.org/)
- **SetCronJob** (https://www.setcronjob.com/)

Configura que visiten esa URL cada día.

---

## 📬 TIPOS DE NOTIFICACIONES

### **1️⃣ Nueva Licitación**

**Se envía cuando:**
- Se agrega una nueva licitación desde `admin/ai-chat.php`
- La categoría coincide con los intereses del usuario
- El texto contiene palabras clave del usuario

**Función:**
```php
enviar_notificacion_nueva_licitacion($email, $nombre, $licitacion);
```

**Ejemplo de uso en tu código:**
```php
// Ya lo tienes en ai-chat.php:
enviar_notificaciones_correo($new_tender_id, $categoria, $titulo, $conn);
send_keyword_notifications($new_tender_id, $tender_text, $conn);
```

---

### **2️⃣ Recordatorio de Pago**

**Se envía cuando:**
- El pago del usuario vence en 7, 3 o 1 día
- El pago ya venció (1, 3, 7 días atrás)

**Función:**
```php
enviar_recordatorio_pago($email, $nombre, $dias_restantes, $monto, $fecha_vencimiento);
```

**Ejemplo de uso:**
```php
// Recordatorio 3 días antes
enviar_recordatorio_pago(
    'usuario@ejemplo.com',
    'Juan Pérez',
    3,                    // Días restantes
    25000,                // Monto a pagar
    '25/12/2024'          // Fecha de vencimiento
);

// Pago vencido hace 2 días
enviar_recordatorio_pago(
    'usuario@ejemplo.com',
    'Juan Pérez',
    -2,                   // Negativo = ya venció
    25000,
    '20/11/2024'
);
```

---

### **3️⃣ Cuenta Desactivada**

**Se envía cuando:**
- Un administrador desactiva la cuenta de un usuario
- El cron desactiva automáticamente por pago vencido >7 días

**Función:**
```php
enviar_notificacion_cuenta_desactivada($email, $nombre, $razon);
```

**Ejemplo de uso:**
```php
// En users.php al desactivar usuario
if ($conn->query("UPDATE usuarios SET activo = 0 WHERE id = $user_id")) {
    enviar_notificacion_cuenta_desactivada(
        $user_email,
        $user_nombre,
        'falta de pago'
    );
}
```

---

### **4️⃣ Cuenta Reactivada**

**Se envía cuando:**
- Un administrador reactiva la cuenta de un usuario

**Función:**
```php
enviar_notificacion_cuenta_reactivada($email, $nombre);
```

**Ejemplo de uso:**
```php
// En users.php al reactivar usuario
if ($conn->query("UPDATE usuarios SET activo = 1 WHERE id = $user_id")) {
    enviar_notificacion_cuenta_reactivada($user_email, $user_nombre);
}
```

---

### **5️⃣ Bienvenida**

**Se envía cuando:**
- Un nuevo usuario se registra en el sistema

**Función:**
```php
enviar_email_bienvenida($email, $nombre);
```

**Ejemplo de uso:**
```php
// En register.php después de crear usuario
if ($stmt->execute()) {
    $new_user_id = $conn->lastInsertId();

    // Enviar email de bienvenida
    enviar_email_bienvenida($correo, $nombre);

    header('Location: login.php?registered=1');
}
```

---

## 🔧 SOLUCIÓN DE PROBLEMAS

### ❌ **Los emails no llegan**

**Posibles causas:**

1. **Credenciales incorrectas**
   - Verifica `SMTP_USERNAME` y `SMTP_PASSWORD`
   - Si usas Gmail, asegúrate de usar contraseña de aplicación

2. **Puerto bloqueado**
   - Algunos hostings bloquean el puerto 587
   - Prueba cambiar a puerto `465` con `SMTP_SECURE = 'ssl'`

3. **Servidor SMTP incorrecto**
   - Verifica que `SMTP_HOST` esté correcto
   - Para Gmail: `smtp.gmail.com`
   - Para hosting: `mail.tudominio.com`

4. **Firewall o bloqueo**
   - Algunos hostings bloquean conexiones SMTP salientes
   - Contacta a tu proveedor de hosting

**Solución:**
```php
// Prueba con configuración simple primero
define('SMTP_ENABLED', false); // Usar mail() de PHP
```

---

### ❌ **Los emails llegan a SPAM**

**Soluciones:**

1. **Configura SPF en tu dominio**
   ```
   v=spf1 include:_spf.google.com ~all
   ```

2. **Configura DKIM**
   - En cPanel → Email → Autenticación de Email → DKIM

3. **Usa un email del mismo dominio**
   ```php
   define('EMAIL_FROM', 'noreply@licitacionesya.com'); // ✅
   // NO uses: 'noreply@gmail.com' si envías desde licitacionesya.com
   ```

---

### ❌ **Error: "Connection refused"**

**Causa:** Puerto bloqueado o servidor SMTP incorrecto

**Solución:**
1. Verifica que el puerto esté correcto (587 o 465)
2. Verifica que el hosting permita conexiones SMTP
3. Prueba con `telnet smtp.gmail.com 587` desde tu servidor

---

### ❌ **Error: "Authentication failed"**

**Causa:** Usuario o contraseña incorrectos

**Solución:**
1. Verifica `SMTP_USERNAME` (debe ser el email completo)
2. Si usas Gmail, usa contraseña de aplicación, NO tu contraseña normal
3. Verifica que no haya espacios en la contraseña

---

### ❌ **Cron no se ejecuta**

**Solución:**

1. **Verifica la ruta del PHP:**
   ```bash
   which php
   # Resultado: /usr/bin/php o /usr/local/bin/php
   ```

2. **Verifica los permisos:**
   ```bash
   chmod +x cron_recordatorios.php
   ```

3. **Prueba manualmente:**
   ```bash
   php /ruta/completa/cron_recordatorios.php
   ```

4. **Revisa los logs de cron:**
   ```bash
   tail -f /var/log/cron
   ```

---

## 💾 BASE DE DATOS

### **Tablas Necesarias**

Si aún no las tienes, crea estas tablas:

```sql
-- Tabla para registrar recordatorios enviados
CREATE TABLE IF NOT EXISTS recordatorios_pago (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo_recordatorio ENUM('pago_proximo', 'pago_vencido', 'cuenta_desactivada') NOT NULL,
    fecha_envio DATETIME NOT NULL,
    dias_anticipacion INT DEFAULT NULL,
    INDEX idx_usuario (usuario_id),
    INDEX idx_fecha (fecha_envio),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar columnas a usuarios si no existen
ALTER TABLE usuarios
ADD COLUMN IF NOT EXISTS recibir_alertas_email TINYINT(1) DEFAULT 1 COMMENT 'Si desea recibir notificaciones por email',
ADD COLUMN IF NOT EXISTS palabras_clave TEXT NULL COMMENT 'Palabras clave separadas por comas para alertas',
ADD COLUMN IF NOT EXISTS intereses TEXT NULL COMMENT 'Categorías de interés separadas por comas';
```

---

## 📊 INTEGRACIÓN CON TU CÓDIGO EXISTENTE

### **En `ai-chat.php` (Ya lo tienes):**

```php
// Después de insertar una licitación
require_once '../includes/email_functions.php';

// Enviar notificaciones por categoría
enviar_notificaciones_correo($new_tender_id, $categoria, $titulo, $conn);

// Enviar notificaciones por palabras clave
send_keyword_notifications($new_tender_id, $tender_text, $conn);
```

### **En `users.php` (Para agregar):**

```php
require_once '../email_functions.php';

// Al desactivar usuario
if ($_POST['action'] == 'desactivar') {
    $stmt = $conn->prepare("UPDATE usuarios SET activo = 0 WHERE id = ?");
    $stmt->execute([$user_id]);

    // Obtener datos del usuario
    $stmt = $conn->prepare("SELECT nombre, correo FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    // Enviar notificación
    enviar_notificacion_cuenta_desactivada($user['correo'], $user['nombre'], 'falta de pago');
}

// Al reactivar usuario
if ($_POST['action'] == 'reactivar') {
    $stmt = $conn->prepare("UPDATE usuarios SET activo = 1 WHERE id = ?");
    $stmt->execute([$user_id]);

    $stmt = $conn->prepare("SELECT nombre, correo FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    enviar_notificacion_cuenta_reactivada($user['correo'], $user['nombre']);
}
```

### **En `register.php` (Para agregar):**

```php
require_once 'email_functions.php';

// Después de crear el usuario
if ($stmt->execute()) {
    $user_id = $conn->lastInsertId();

    // Enviar email de bienvenida
    enviar_email_bienvenida($correo, $nombre);

    $_SESSION['success'] = "Registro exitoso. Revisa tu email.";
    header('Location: login.php');
}
```

---

## 🎓 RESUMEN DE CONFIGURACIÓN

### ✅ **Lista de Verificación**

- [ ] Editar `config_email.php` con credenciales SMTP
- [ ] Probar envío desde `admin_notificaciones.php`
- [ ] Verificar que los emails lleguen (revisar spam también)
- [ ] Crear tablas de base de datos necesarias
- [ ] Configurar cron job para `cron_recordatorios.php`
- [ ] Integrar notificaciones en `register.php`
- [ ] Integrar notificaciones en `users.php`
- [ ] Probar cada tipo de notificación

---

## 📞 SOPORTE

Si tienes problemas:

1. **Revisa los logs:** `/logs/email_log.txt`
2. **Activa el modo debug:** En `config_email.php` → `define('EMAIL_DEBUG', true);`
3. **Prueba desde el panel:** `admin_notificaciones.php`

---

## 🚀 CARACTERÍSTICAS AVANZADAS

### **Personalización de Plantillas**

Edita las funciones en `email_functions.php`:
- `enviar_notificacion_nueva_licitacion()` - Modifica el diseño del email de licitaciones
- `enviar_recordatorio_pago()` - Personaliza recordatorios
- `plantilla_email_base()` - Cambia el diseño general

### **Agregar Nuevos Tipos de Notificaciones**

1. Crea una nueva función en `email_functions.php`
2. Usa `plantilla_email_base()` para el diseño
3. Llama la función desde donde la necesites

---

## ✨ ¡LISTO!

Tu sistema de notificaciones está configurado. Ahora tus usuarios recibirán:

📬 Alertas de nuevas licitaciones
💰 Recordatorios de pago automáticos
👋 Emails de bienvenida
📊 Notificaciones de activación/desactivación

¡Disfruta de tu sistema automatizado de notificaciones!
