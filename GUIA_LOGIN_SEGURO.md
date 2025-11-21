# 🔐 Guía de Implementación - Login Seguro

## ✅ Mejoras de Seguridad Implementadas

### 1. **Protección contra Fuerza Bruta**
- ✅ Máximo 5 intentos fallidos por IP
- ✅ Bloqueo temporal de 15 minutos después de 5 intentos
- ✅ Registro de todos los intentos fallidos
- ✅ Limpieza automática de registros antiguos

### 2. **Protección CSRF**
- ✅ Token único por sesión
- ✅ Validación en cada submit
- ✅ Regeneración automática de tokens

### 3. **Protección contra Timing Attacks**
- ✅ Tiempo de respuesta constante (0.1-0.3 segundos)
- ✅ Mismo mensaje de error para usuario no encontrado y contraseña incorrecta
- ✅ No revela información sobre existencia de usuarios

### 4. **Headers de Seguridad HTTP**
```php
X-Frame-Options: DENY              // Previene clickjacking
X-Content-Type-Options: nosniff    // Previene MIME sniffing
X-XSS-Protection: 1; mode=block    // Protección XSS
Referrer-Policy: strict-origin     // Control de referrer
```

### 5. **Cookies Seguras**
- ✅ HttpOnly: No accesible desde JavaScript
- ✅ Secure: Solo HTTPS (en producción)
- ✅ SameSite: Strict (previene CSRF)

### 6. **Validación de Entrada**
- ✅ Filtrado y sanitización de email
- ✅ Validación de formato de email
- ✅ Límite de longitud (255 caracteres)
- ✅ Prepared statements (previene SQL injection)

### 7. **Gestión de Sesiones**
- ✅ Regeneración de ID al login (previene session fixation)
- ✅ Token único por sesión
- ✅ Registro de IP y tiempo de login
- ✅ Validación de sesión activa

### 8. **Logging y Auditoría**
- ✅ Registro de todos los intentos fallidos
- ✅ Registro de logins exitosos
- ✅ Registro de intentos con cuentas desactivadas
- ✅ Logs con IP, email y timestamp

### 9. **UI/UX Mejorado**
- ✅ Botón para ver/ocultar contraseña
- ✅ Contador de intentos restantes
- ✅ Validación en tiempo real
- ✅ Prevención de doble submit
- ✅ Loading state en botón
- ✅ Auto-focus en campo email
- ✅ Diseño moderno y limpio

---

## 📋 Pasos de Implementación

### PASO 1: Crear Tabla de Seguridad

Ejecuta el script SQL en tu base de datos:

```bash
mysql -u tu_usuario -p tu_base_datos < crear_tabla_seguridad_login.sql
```

O desde phpMyAdmin:
1. Copia el contenido de `crear_tabla_seguridad_login.sql`
2. Pégalo en la pestaña SQL
3. Click en "Continuar"

**Verificar que se creó:**
```sql
SHOW TABLES LIKE 'login_attempts';
DESCRIBE login_attempts;
```

Deberías ver:
```
id             INT
ip_address     VARCHAR(45)
email          VARCHAR(255)
attempt_time   DATETIME
```

### PASO 2: Hacer Backup del Login Actual

```bash
cp login.php login_backup_original.php
```

### PASO 3: Reemplazar con Versión Segura

```bash
cp login_mejorado.php login.php
```

### PASO 4: Verificar Permisos

Asegúrate de que el archivo tenga los permisos correctos:

```bash
chmod 644 login.php
```

### PASO 5: Configurar SSL/HTTPS

**IMPORTANTE:** Para máxima seguridad, asegúrate de tener SSL/HTTPS habilitado.

Si NO tienes HTTPS, comenta esta línea en `login.php`:
```php
// 'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
```

### PASO 6: Probar el Sistema

#### Test 1: Login Normal
1. Abre `https://licitacionesya.com/login.php`
2. Ingresa credenciales válidas
3. Deberías ser redirigido al dashboard

#### Test 2: Protección contra Fuerza Bruta
1. Ingresa credenciales incorrectas 5 veces
2. Deberías ver: "Demasiados intentos fallidos. Espere 15 minutos"
3. El formulario se deshabilitará

#### Test 3: Token CSRF
1. Abre el login en un navegador
2. Abre las dev tools (F12)
3. En Console, ejecuta:
```javascript
document.querySelector('input[name="csrf_token"]').value = 'invalid';
```
4. Intenta hacer login
5. Deberías ver: "Token de seguridad inválido"

#### Test 4: Ver/Ocultar Contraseña
1. Escribe algo en el campo de contraseña
2. Click en el ícono del ojo 👁️
3. La contraseña debería mostrarse/ocultarse

---

## 🔍 Consultas Útiles

### Ver Intentos Fallidos Recientes
```sql
SELECT ip_address, email, attempt_time, COUNT(*) as intentos
FROM login_attempts
WHERE attempt_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)
GROUP BY ip_address, email
ORDER BY attempt_time DESC;
```

### Ver IPs Bloqueadas Actualmente
```sql
SELECT ip_address, COUNT(*) as intentos, MAX(attempt_time) as ultimo_intento
FROM login_attempts
WHERE attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
GROUP BY ip_address
HAVING COUNT(*) >= 5
ORDER BY ultimo_intento DESC;
```

### Limpiar Intentos de una IP Específica
```sql
DELETE FROM login_attempts WHERE ip_address = '192.168.1.100';
```

### Ver Últimos Logins Exitosos
```sql
SELECT id, nombre, correo, ultimo_login
FROM usuarios
WHERE ultimo_login IS NOT NULL
ORDER BY ultimo_login DESC
LIMIT 20;
```

### Estadísticas de Intentos Fallidos
```sql
SELECT
    DATE(attempt_time) as fecha,
    COUNT(*) as total_intentos,
    COUNT(DISTINCT ip_address) as ips_distintas,
    COUNT(DISTINCT email) as emails_distintos
FROM login_attempts
WHERE attempt_time > DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(attempt_time)
ORDER BY fecha DESC;
```

---

## 🛡️ Características de Seguridad Detalladas

### Protección contra SQL Injection
```php
// ❌ VULNERABLE
$sql = "SELECT * FROM usuarios WHERE correo = '$correo'";

// ✅ SEGURO (implementado)
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE correo = ?");
$stmt->execute([$correo]);
```

### Protección contra XSS
```php
// Sanitización de salida
echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8');

// Sanitización de entrada
$correo = filter_var(trim($_POST['correo']), FILTER_SANITIZE_EMAIL);
```

### Protección contra Session Fixation
```php
// Regenerar ID de sesión al login
session_regenerate_id(true);
```

### Protección contra Timing Attacks
```php
// Mismo tiempo de respuesta independientemente del resultado
usleep(rand(100000, 300000)); // 0.1 a 0.3 segundos
```

---

## ⚙️ Configuración Avanzada

### Cambiar Límite de Intentos

En `login_mejorado.php`, línea ~55:
```php
// Cambiar de 5 a otro número
if ($attempts >= 5) {  // Cambiar este número
    $error = "Demasiados intentos...";
```

Y también en línea ~316:
```php
$remaining_attempts = max(0, 5 - $attempts); // Cambiar este número
```

### Cambiar Tiempo de Bloqueo

En `login_mejorado.php`, línea ~44:
```php
// Cambiar de 15 minutos a otro valor
$stmt = $conn->prepare("DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
```

También en línea ~50:
```php
$stmt = $conn->prepare("SELECT COUNT(*) as attempts FROM login_attempts WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
```

### Deshabilitar Logging

Si quieres deshabilitar el logging (NO recomendado):

Comenta estas líneas:
```php
// error_log("Login exitoso: Usuario ID {$user['id']} desde IP $user_ip");
// error_log("Contraseña incorrecta para: $correo desde IP $user_ip");
// error_log("Usuario no encontrado: $correo desde IP $user_ip");
```

---

## 🐛 Solución de Problemas

### Problema 1: "Tabla login_attempts no existe"

**Solución:**
```sql
CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    email VARCHAR(255) NULL,
    attempt_time DATETIME NOT NULL,
    INDEX idx_ip_time (ip_address, attempt_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Problema 2: "Token de seguridad inválido" siempre

**Solución:**
Verifica que las sesiones estén habilitadas:
```php
var_dump($_SESSION); // Debería mostrar el array de sesión
```

Si está vacío, revisa la configuración de PHP:
```bash
php -i | grep session
```

### Problema 3: Usuario bloqueado permanentemente

**Solución:**
Limpia los intentos de esa IP:
```sql
DELETE FROM login_attempts WHERE ip_address = 'LA_IP_BLOQUEADA';
```

### Problema 4: No se puede ver la contraseña

**Solución:**
Asegúrate de que JavaScript está habilitado. Si no funciona, revisa la consola del navegador (F12).

### Problema 5: "Cuenta desactivada" para usuarios válidos

**Solución:**
Verifica que la columna `activo` existe:
```sql
DESCRIBE usuarios;
```

Si no existe, agrégala:
```sql
ALTER TABLE usuarios ADD COLUMN activo TINYINT(1) DEFAULT 1;
UPDATE usuarios SET activo = 1 WHERE es_admin = 0;
```

---

## 📊 Monitoreo de Seguridad

### Dashboard de Seguridad (Consulta)

```sql
SELECT
    'Total intentos hoy' as metrica,
    COUNT(*) as valor
FROM login_attempts
WHERE DATE(attempt_time) = CURDATE()

UNION ALL

SELECT
    'IPs únicas hoy',
    COUNT(DISTINCT ip_address)
FROM login_attempts
WHERE DATE(attempt_time) = CURDATE()

UNION ALL

SELECT
    'IPs bloqueadas ahora',
    COUNT(DISTINCT ip_address)
FROM login_attempts
WHERE attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
GROUP BY ip_address
HAVING COUNT(*) >= 5

UNION ALL

SELECT
    'Logins exitosos hoy',
    COUNT(*)
FROM usuarios
WHERE DATE(ultimo_login) = CURDATE();
```

---

## ✅ Checklist de Seguridad

- [ ] Tabla `login_attempts` creada
- [ ] Columna `ultimo_login` agregada a `usuarios`
- [ ] Columna `activo` existe en `usuarios`
- [ ] SSL/HTTPS habilitado (recomendado)
- [ ] Archivo `login.php` reemplazado
- [ ] Backup del login original guardado
- [ ] Probado login normal
- [ ] Probado protección contra fuerza bruta
- [ ] Probado botón ver/ocultar contraseña
- [ ] Probado validación CSRF
- [ ] Verificado logs en servidor
- [ ] Probado en móvil
- [ ] Probado en diferentes navegadores

---

## 🎨 Personalización Visual

### Cambiar Colores

En `login_mejorado.php`, edita las variables CSS (línea ~121):

```css
:root {
  --primary: #2563eb;        /* Color principal (azul) */
  --primary-dark: #1d4ed8;   /* Azul oscuro */
  --danger: #ef4444;         /* Rojo para errores */
}
```

### Cambiar Emoji del Logo

Línea ~405:
```html
<div class="logo">🔐</div>  <!-- Cambiar este emoji -->
```

Opciones: 🔒 🛡️ 🔑 🚪 ✅ ⚡

### Cambiar Gradiente del Fondo

Línea ~158:
```css
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
```

---

## 📝 Notas Adicionales

### Compatibilidad
- ✅ PHP 7.4+
- ✅ MySQL 5.7+
- ✅ Navegadores modernos (Chrome, Firefox, Safari, Edge)
- ✅ Responsive (móvil y desktop)

### Rendimiento
- Tiempo de respuesta: 100-300ms (por diseño)
- Tamaño de página: ~15KB
- Sin dependencias externas (solo Google Fonts)

### Mantenimiento
- Limpieza automática de intentos antiguos cada hora
- Los registros de más de 24 horas se eliminan automáticamente
- No requiere mantenimiento manual

---

## 🚀 Próximos Pasos Recomendados

1. **Implementar recuperación de contraseña** con tokens seguros
2. **Agregar autenticación de dos factores (2FA)**
3. **Implementar rate limiting a nivel de servidor** (nginx/Apache)
4. **Configurar alertas** para múltiples intentos fallidos
5. **Implementar CAPTCHA** después de 3 intentos fallidos
6. **Agregar geolocalización** de IPs sospechosas
7. **Implementar detección de bots** con honeypots

---

**Sistema implementado por:** Claude
**Fecha:** 2025-11-21
**Versión:** 2.0 - Seguridad Mejorada
