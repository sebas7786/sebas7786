<?php
// MOSTRAR TODOS LOS ERRORES PARA DIAGNÓSTICO
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!-- Diagnóstico iniciado -->\n";

// Test 1: Verificar archivos
echo "<!-- Test 1: Verificando archivos... -->\n";
if (!file_exists('config/db.php')) {
    die("ERROR: No se encuentra config/db.php");
}
if (!file_exists('includes/functions.php')) {
    die("ERROR: No se encuentra includes/functions.php");
}
echo "<!-- Archivos encontrados OK -->\n";

// Test 2: Incluir archivos
echo "<!-- Test 2: Incluyendo archivos... -->\n";
try {
    require_once 'config/db.php';
    echo "<!-- db.php incluido OK -->\n";
    require_once 'includes/functions.php';
    echo "<!-- functions.php incluido OK -->\n";
} catch (Exception $e) {
    die("ERROR incluyendo archivos: " . $e->getMessage());
}

// Test 3: Verificar conexión BD
echo "<!-- Test 3: Verificando conexión BD... -->\n";
if (!isset($conn)) {
    die("ERROR: Variable \$conn no está definida");
}
if (!($conn instanceof PDO)) {
    die("ERROR: \$conn no es una instancia de PDO");
}
echo "<!-- Conexión BD OK -->\n";

// Test 4: Verificar tabla login_attempts
echo "<!-- Test 4: Verificando tabla login_attempts... -->\n";
try {
    $stmt = $conn->query("SHOW TABLES LIKE 'login_attempts'");
    if ($stmt->rowCount() == 0) {
        echo "<!-- ADVERTENCIA: Tabla login_attempts NO existe -->\n";
        echo "<div style='background:yellow;padding:20px;margin:20px;'>";
        echo "<h2>⚠️ ADVERTENCIA</h2>";
        echo "<p>La tabla 'login_attempts' no existe en la base de datos.</p>";
        echo "<p>Ejecuta el archivo <strong>crear_tabla_seguridad_simple.sql</strong> en phpMyAdmin</p>";
        echo "</div>";
    } else {
        echo "<!-- Tabla login_attempts existe OK -->\n";
    }
} catch (PDOException $e) {
    echo "<!-- Error verificando tabla: " . $e->getMessage() . " -->\n";
}

// Test 5: Iniciar sesión
echo "<!-- Test 5: Iniciando sesión... -->\n";
if (session_status() == PHP_SESSION_NONE) {
    try {
        session_start();
        echo "<!-- Sesión iniciada OK -->\n";
    } catch (Exception $e) {
        die("ERROR iniciando sesión: " . $e->getMessage());
    }
}

// Generar token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

echo "<!-- Diagnóstico completado - Sistema funcionando -->\n";
echo "<div style='background:lightgreen;padding:20px;margin:20px;'>";
echo "<h2>✅ Sistema Básico Funcionando</h2>";
echo "<p>Conexión a BD: OK</p>";
echo "<p>Sesiones: OK</p>";
echo "<p>Ahora probando login completo...</p>";
echo "</div>";

// Función para verificar si usuario está logueado
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Redirigir si ya está logueado
if (is_logged_in()) {
    $redirect = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] ? 'admin/dashboard.php' : 'user/dashboard.php';
    header("Location: $redirect");
    exit;
}

$error = '';
$user_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// Función simple para verificar intentos (sin la tabla)
function check_login_attempts($ip) {
    global $conn;
    try {
        $stmt = $conn->query("SHOW TABLES LIKE 'login_attempts'");
        if ($stmt->rowCount() == 0) {
            return 0; // Tabla no existe, permitir login
        }

        $stmt = $conn->prepare("SELECT COUNT(*) as attempts FROM login_attempts
                               WHERE ip_address = ?
                               AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
        $stmt->execute([$ip]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['attempts'];
    } catch (PDOException $e) {
        error_log("Error checking attempts: " . $e->getMessage());
        return 0;
    }
}

function record_failed_attempt($ip, $email) {
    global $conn;
    try {
        $stmt = $conn->query("SHOW TABLES LIKE 'login_attempts'");
        if ($stmt->rowCount() == 0) {
            return; // Tabla no existe, skip
        }

        $stmt = $conn->prepare("INSERT INTO login_attempts (ip_address, email, attempt_time) VALUES (?, ?, NOW())");
        $stmt->execute([$ip, $email]);
    } catch (PDOException $e) {
        error_log("Error recording attempt: " . $e->getMessage());
    }
}

// Procesar login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Token de seguridad inválido.";
    } else {
        $attempts = check_login_attempts($user_ip);

        if ($attempts >= 5) {
            $error = "Demasiados intentos fallidos. Espere 15 minutos.";
        } else {
            if (!isset($_POST['correo']) || !isset($_POST['contrasena'])) {
                $error = "Formulario incompleto.";
            } else {
                $correo = filter_var(trim($_POST['correo']), FILTER_SANITIZE_EMAIL);
                $contrasena = $_POST['contrasena'];

                if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                    $error = "Formato de correo inválido.";
                    record_failed_attempt($user_ip, $correo);
                } elseif (empty($correo) || empty($contrasena)) {
                    $error = "Complete todos los campos.";
                    record_failed_attempt($user_ip, $correo);
                } else {
                    try {
                        $stmt = $conn->prepare("SELECT id, nombre, correo, contrasena, es_admin, activo FROM usuarios WHERE correo = ? LIMIT 1");
                        $stmt->execute([$correo]);

                        if ($stmt->rowCount() == 1) {
                            $user = $stmt->fetch(PDO::FETCH_ASSOC);

                            if (password_verify($contrasena, $user['contrasena'])) {
                                if (!isset($user['activo'])) {
                                    $user['activo'] = 1;
                                }

                                if ($user['es_admin'] == 1 || $user['activo'] == 1) {
                                    session_regenerate_id(true);

                                    $_SESSION['user_id'] = $user['id'];
                                    $_SESSION['usuario_id'] = $user['id'];
                                    $_SESSION['user_name'] = htmlspecialchars($user['nombre'], ENT_QUOTES, 'UTF-8');
                                    $_SESSION['user_email'] = $user['correo'];
                                    $_SESSION['is_admin'] = (int)$user['es_admin'];
                                    $_SESSION['is_active'] = (int)$user['activo'];
                                    $_SESSION['login_time'] = time();

                                    $redirect = $user['es_admin'] ? 'admin/dashboard.php' : 'user/dashboard.php';
                                    header("Location: $redirect");
                                    exit;
                                } else {
                                    $error = "Su cuenta está desactivada. Contacte al administrador.";
                                    record_failed_attempt($user_ip, $correo);
                                }
                            } else {
                                $error = "Credenciales incorrectas.";
                                record_failed_attempt($user_ip, $correo);
                            }
                        } else {
                            $error = "Credenciales incorrectas.";
                            record_failed_attempt($user_ip, $correo);
                        }
                    } catch (PDOException $e) {
                        $error = "Error de base de datos: " . $e->getMessage();
                    }
                }
            }
        }
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$attempts = check_login_attempts($user_ip);
$remaining_attempts = max(0, 5 - $attempts);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Diagnóstico</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 440px;
            width: 100%;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .logo {
            font-size: 60px;
            margin-bottom: 20px;
        }
        .body {
            padding: 40px;
        }
        .error {
            background: #fef2f2;
            color: #ef4444;
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #ef4444;
        }
        .warning {
            background: #fff7ed;
            color: #c2410c;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #fb923c;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }
        .input-wrapper {
            position: relative;
        }
        input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 15px;
            background: #f9fafb;
        }
        input:focus {
            border-color: #2563eb;
            outline: none;
            background: white;
        }
        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 20px;
        }
        button[type="submit"] {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 20px;
        }
        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(37,99,235,0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🔐</div>
            <h1>Diagnóstico de Login</h1>
            <p>Versión de prueba</p>
        </div>
        <div class="body">
            <?php if (!empty($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($attempts > 0 && $attempts < 5): ?>
                <div class="warning">
                    ⚠️ <?php echo $remaining_attempts; ?> intento(s) restante(s)
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <div class="form-group">
                    <label>Correo electrónico</label>
                    <input type="email" name="correo" placeholder="ejemplo@correo.com" required>
                </div>

                <div class="form-group">
                    <label>Contraseña</label>
                    <div class="input-wrapper">
                        <input type="password" id="pass" name="contrasena" placeholder="••••••••" required>
                        <button type="button" class="toggle-password" onclick="togglePassword()">👁️</button>
                    </div>
                </div>

                <button type="submit">Iniciar Sesión</button>
            </form>
        </div>
    </div>

    <script>
        function togglePassword() {
            const pass = document.getElementById('pass');
            pass.type = pass.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>
</html>
