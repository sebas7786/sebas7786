<?php
// Configuración de seguridad
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Headers de seguridad
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

// Verificar archivos requeridos
if (!file_exists('config/db.php') || !file_exists('includes/functions.php')) {
    die("Error: Archivos de configuración no encontrados.");
}

require_once 'config/db.php';
require_once 'includes/functions.php';

// Iniciar sesión de forma segura
if (session_status() == PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'cookie_samesite' => 'Strict'
    ]);
}

// Generar token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Redirigir si ya está logueado (usando función de functions.php)
if (is_logged_in()) {
    $redirect = is_admin() ? 'admin/dashboard.php' : 'user/dashboard.php';
    header("Location: $redirect");
    exit;
}

// ================================
// PROTECCIÓN CONTRA FUERZA BRUTA
// ================================
function check_login_attempts_local($ip) {
    global $conn;

    try {
        // Verificar si la tabla existe
        $stmt = $conn->query("SHOW TABLES LIKE 'login_attempts'");
        if ($stmt->rowCount() == 0) {
            return 0; // Tabla no existe, permitir login
        }

        // Limpiar intentos antiguos (más de 15 minutos)
        $stmt = $conn->prepare("DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
        $stmt->execute();

        // Contar intentos recientes
        $stmt = $conn->prepare("SELECT COUNT(*) as attempts FROM login_attempts WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
        $stmt->execute([$ip]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['attempts'];
    } catch (PDOException $e) {
        error_log("Error checking login attempts: " . $e->getMessage());
        return 0;
    }
}

function record_failed_attempt_local($ip, $email) {
    global $conn;

    try {
        $stmt = $conn->query("SHOW TABLES LIKE 'login_attempts'");
        if ($stmt->rowCount() == 0) {
            return; // Tabla no existe, skip
        }

        $stmt = $conn->prepare("INSERT INTO login_attempts (ip_address, email, attempt_time) VALUES (?, ?, NOW())");
        $stmt->execute([$ip, $email]);
    } catch (PDOException $e) {
        error_log("Error recording failed attempt: " . $e->getMessage());
    }
}

function clear_login_attempts_local($ip) {
    global $conn;

    try {
        $stmt = $conn->query("SHOW TABLES LIKE 'login_attempts'");
        if ($stmt->rowCount() == 0) {
            return;
        }

        $stmt = $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
        $stmt->execute([$ip]);
    } catch (PDOException $e) {
        error_log("Error clearing login attempts: " . $e->getMessage());
    }
}

// ================================
// PROCESAR LOGIN
// ================================
$error = '';
$user_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Token de seguridad inválido. Por favor, recargue la página.";
    } else {
        // Verificar intentos de login
        $attempts = check_login_attempts_local($user_ip);

        if ($attempts >= 5) {
            $error = "Demasiados intentos fallidos. Por favor, espere 15 minutos antes de volver a intentar.";
            error_log("Login bloqueado por fuerza bruta desde IP: $user_ip");
        } else {
            // Validar campos
            if (!isset($_POST['correo']) || !isset($_POST['contrasena'])) {
                $error = "Formulario incompleto.";
            } else {
                $correo = filter_var(trim($_POST['correo']), FILTER_SANITIZE_EMAIL);
                $contrasena = $_POST['contrasena'];

                // Validar formato de email
                if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                    $error = "Formato de correo electrónico inválido.";
                    record_failed_attempt_local($user_ip, $correo);
                } elseif (empty($correo) || empty($contrasena)) {
                    $error = "Por favor, complete todos los campos.";
                    record_failed_attempt_local($user_ip, $correo);
                } elseif (strlen($contrasena) > 255) {
                    $error = "Contraseña inválida.";
                    record_failed_attempt_local($user_ip, $correo);
                } else {
                    try {
                        // Preparar consulta con prepared statement
                        $stmt = $conn->prepare("SELECT id, nombre, correo, contrasena, es_admin, activo FROM usuarios WHERE correo = ? LIMIT 1");
                        $stmt->execute([$correo]);

                        if ($stmt->rowCount() == 1) {
                            $user = $stmt->fetch(PDO::FETCH_ASSOC);

                            // Verificar contraseña
                            $password_valid = password_verify($contrasena, $user['contrasena']);

                            // Timing attack protection
                            usleep(rand(100000, 300000));

                            if ($password_valid) {
                                // Verificar si la columna activo existe
                                if (!isset($user['activo'])) {
                                    $user['activo'] = 1;
                                }

                                if ($user['es_admin'] == 1 || $user['activo'] == 1) {
                                    // Login exitoso
                                    session_regenerate_id(true);

                                    // Establecer variables de sesión
                                    $_SESSION['user_id'] = $user['id'];
                                    $_SESSION['usuario_id'] = $user['id'];
                                    $_SESSION['user_name'] = htmlspecialchars($user['nombre'], ENT_QUOTES, 'UTF-8');
                                    $_SESSION['user_email'] = $user['correo'];
                                    $_SESSION['is_admin'] = (int)$user['es_admin'];
                                    $_SESSION['is_active'] = (int)$user['activo'];
                                    $_SESSION['login_time'] = time();
                                    $_SESSION['user_ip'] = $user_ip;

                                    // Limpiar intentos fallidos
                                    clear_login_attempts_local($user_ip);

                                    // Registrar login exitoso
                                    error_log("Login exitoso: Usuario ID {$user['id']} desde IP $user_ip");

                                    // Actualizar último login
                                    try {
                                        $stmt_update = $conn->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?");
                                        $stmt_update->execute([$user['id']]);
                                    } catch (PDOException $e) {
                                        // Continuar aunque falle
                                    }

                                    // Redirigir según tipo de usuario
                                    $redirect = $user['es_admin'] ? 'admin/dashboard.php' : 'user/dashboard.php';
                                    header("Location: $redirect");
                                    exit;
                                } else {
                                    $error = "Su cuenta está desactivada. Por favor, contacte al administrador.";
                                    record_failed_attempt_local($user_ip, $correo);
                                    error_log("Intento de login con cuenta desactivada: $correo desde IP $user_ip");
                                }
                            } else {
                                $error = "Credenciales incorrectas. Por favor, verifique su correo y contraseña.";
                                record_failed_attempt_local($user_ip, $correo);
                                error_log("Contraseña incorrecta para: $correo desde IP $user_ip");
                            }
                        } else {
                            usleep(rand(100000, 300000));
                            $error = "Credenciales incorrectas. Por favor, verifique su correo y contraseña.";
                            record_failed_attempt_local($user_ip, $correo);
                            error_log("Usuario no encontrado: $correo desde IP $user_ip");
                        }
                    } catch (PDOException $e) {
                        error_log("Error de base de datos en login: " . $e->getMessage());
                        $error = "Error al procesar la solicitud. Por favor, inténtelo de nuevo más tarde.";
                    }
                }
            }
        }
    }

    // Regenerar token CSRF
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Calcular intentos restantes
$attempts = check_login_attempts_local($user_ip);
$remaining_attempts = max(0, 5 - $attempts);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - LicitacionesYA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
          --primary: #2563eb;
          --primary-dark: #1d4ed8;
          --danger: #ef4444;
          --danger-light: #fef2f2;
          --gray-50: #f9fafb;
          --gray-100: #f3f4f6;
          --gray-200: #e5e7eb;
          --gray-400: #9ca3af;
          --gray-600: #4b5563;
          --gray-700: #374151;
          --gray-900: #111827;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
          font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
          background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          color: var(--gray-900);
          line-height: 1.6;
          display: flex;
          justify-content: center;
          align-items: center;
          min-height: 100vh;
          padding: 20px;
        }

        .login-container {
          background: white;
          border-radius: 20px;
          box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
          max-width: 440px;
          width: 100%;
          overflow: hidden;
        }

        .login-header {
          background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
          color: white;
          padding: 40px 40px 30px;
          text-align: center;
          position: relative;
          overflow: hidden;
        }

        .login-header::before {
          content: "";
          position: absolute;
          top: -50%;
          left: -50%;
          width: 200%;
          height: 200%;
          background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
          animation: pulse 15s ease-in-out infinite;
        }

        @keyframes pulse {
          0%, 100% { transform: scale(1); opacity: 0.5; }
          50% { transform: scale(1.1); opacity: 0.8; }
        }

        .logo-container {
          position: relative;
          z-index: 1;
          margin-bottom: 20px;
        }

        .logo {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          width: 70px;
          height: 70px;
          background: rgba(255, 255, 255, 0.2);
          border-radius: 18px;
          font-size: 32px;
          backdrop-filter: blur(10px);
          border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .login-header h1 {
          font-size: 28px;
          font-weight: 700;
          margin-bottom: 8px;
          position: relative;
          z-index: 1;
        }

        .login-header p {
          font-size: 15px;
          opacity: 0.95;
          position: relative;
          z-index: 1;
        }

        .login-body { padding: 40px; }

        .error-message {
          background-color: var(--danger-light);
          color: var(--danger);
          padding: 14px 16px;
          border-radius: 10px;
          margin-bottom: 24px;
          text-align: center;
          border-left: 4px solid var(--danger);
          font-size: 14px;
          font-weight: 500;
          display: flex;
          align-items: center;
          gap: 10px;
        }

        .error-message::before {
          content: "⚠️";
          font-size: 18px;
        }

        .warning-message {
          background-color: #fff7ed;
          color: #c2410c;
          padding: 12px 16px;
          border-radius: 10px;
          margin-bottom: 20px;
          text-align: center;
          font-size: 13px;
          font-weight: 500;
          border-left: 4px solid #fb923c;
        }

        .form-group { margin-bottom: 24px; }

        label {
          display: block;
          margin-bottom: 8px;
          font-weight: 600;
          color: var(--gray-700);
          font-size: 14px;
        }

        .input-wrapper { position: relative; }

        input[type="email"],
        input[type="password"],
        input[type="text"] {
          width: 100%;
          padding: 14px 16px;
          padding-right: 45px;
          border: 2px solid var(--gray-200);
          border-radius: 10px;
          font-size: 15px;
          transition: all 0.3s ease;
          background-color: var(--gray-50);
          color: var(--gray-900);
          font-family: 'Inter', sans-serif;
        }

        input:focus {
          border-color: var(--primary);
          box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
          outline: none;
          background-color: white;
        }

        .toggle-password {
          position: absolute;
          right: 14px;
          top: 50%;
          transform: translateY(-50%);
          background: none;
          border: none;
          cursor: pointer;
          color: var(--gray-400);
          font-size: 20px;
          padding: 5px;
          transition: color 0.2s;
        }

        .toggle-password:hover { color: var(--gray-600); }

        .form-options {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 28px;
          flex-wrap: wrap;
          gap: 12px;
        }

        .remember-me {
          display: flex;
          align-items: center;
          cursor: pointer;
        }

        .remember-me input {
          margin-right: 8px;
          width: 18px;
          height: 18px;
          accent-color: var(--primary);
          cursor: pointer;
        }

        .remember-me label {
          margin-bottom: 0;
          font-size: 14px;
          font-weight: 500;
          cursor: pointer;
        }

        .forgot-link {
          color: var(--primary);
          text-decoration: none;
          font-size: 14px;
          font-weight: 600;
          transition: all 0.2s;
        }

        .forgot-link:hover {
          color: var(--primary-dark);
          text-decoration: underline;
        }

        .login-button {
          width: 100%;
          padding: 16px;
          background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
          color: white;
          border: none;
          border-radius: 10px;
          font-size: 16px;
          font-weight: 700;
          cursor: pointer;
          transition: all 0.3s ease;
          box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .login-button:hover:not(:disabled) {
          transform: translateY(-2px);
          box-shadow: 0 6px 16px rgba(37, 99, 235, 0.4);
        }

        .login-button:active:not(:disabled) {
          transform: translateY(0);
        }

        .login-button:disabled {
          opacity: 0.6;
          cursor: not-allowed;
        }

        .login-footer {
          text-align: center;
          margin-top: 28px;
          padding-top: 24px;
          border-top: 2px solid var(--gray-100);
          color: var(--gray-600);
          font-size: 14px;
        }

        .login-footer a {
          color: var(--primary);
          text-decoration: none;
          font-weight: 600;
        }

        .login-footer a:hover {
          color: var(--primary-dark);
          text-decoration: underline;
        }

        .security-badge {
          margin-top: 24px;
          padding: 16px;
          background: var(--gray-50);
          border-radius: 10px;
          display: flex;
          align-items: center;
          gap: 12px;
          font-size: 13px;
          color: var(--gray-600);
        }

        .security-badge::before {
          content: "🔒";
          font-size: 24px;
        }

        @media (max-width: 480px) {
          .login-container { border-radius: 0; }
          .login-header { padding: 30px 30px 20px; }
          .login-body { padding: 30px 25px; }
          .form-options { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo-container">
                <div class="logo">🔐</div>
            </div>
            <h1>Bienvenido</h1>
            <p>Ingrese sus credenciales para continuar</p>
        </div>

        <div class="login-body">
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <?php if ($attempts > 0 && $attempts < 5): ?>
                <div class="warning-message">
                    ⚠️ <?php echo $remaining_attempts; ?> intento<?php echo $remaining_attempts != 1 ? 's' : ''; ?> restante<?php echo $remaining_attempts != 1 ? 's' : ''; ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

                <div class="form-group">
                    <label for="correo">Correo electrónico</label>
                    <input type="email"
                           id="correo"
                           name="correo"
                           placeholder="ejemplo@correo.com"
                           required
                           autocomplete="email"
                           maxlength="255"
                           <?php echo ($attempts >= 5) ? 'disabled' : ''; ?>>
                </div>

                <div class="form-group">
                    <label for="contrasena">Contraseña</label>
                    <div class="input-wrapper">
                        <input type="password"
                               id="contrasena"
                               name="contrasena"
                               placeholder="••••••••"
                               required
                               autocomplete="current-password"
                               maxlength="255"
                               <?php echo ($attempts >= 5) ? 'disabled' : ''; ?>>
                        <button type="button" class="toggle-password" id="togglePassword">
                            👁️
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <div class="remember-me">
                        <input type="checkbox" id="recordar" name="recordar">
                        <label for="recordar">Recordarme</label>
                    </div>
                    <a href="#" class="forgot-link">¿Olvidó su contraseña?</a>
                </div>

                <button type="submit" class="login-button" id="loginBtn" <?php echo ($attempts >= 5) ? 'disabled' : ''; ?>>
                    Iniciar Sesión
                </button>
            </form>

            <div class="login-footer">
                <p>¿No tiene una cuenta? <a href="/#contacto">Solicitar acceso</a></p>
            </div>

            <div class="security-badge">
                <strong>Conexión segura</strong> - Sus datos están protegidos
            </div>
        </div>
    </div>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('contrasena');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.textContent = type === 'password' ? '👁️' : '👁️‍🗨️';
        });

        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');

        loginForm.addEventListener('submit', function(e) {
            if (loginBtn.disabled) {
                e.preventDefault();
                return false;
            }
            loginBtn.disabled = true;
            setTimeout(() => loginBtn.disabled = false, 5000);
        });

        const emailInput = document.getElementById('correo');
        emailInput.addEventListener('blur', function() {
            const email = this.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email && !emailRegex.test(email)) {
                this.style.borderColor = 'var(--danger)';
            } else {
                this.style.borderColor = '';
            }
        });

        emailInput.addEventListener('input', function() {
            this.style.borderColor = '';
        });

        window.addEventListener('load', () => emailInput.focus());
    </script>
</body>
</html>
