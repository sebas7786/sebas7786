<?php
/**
 * Configuración del Módulo de Salud Ocupacional
 * Sistema de Avisos de Accidentes Laborales
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    // Session lifetime
    ini_set('session.gc_maxlifetime', 5000);
    session_set_cookie_params(5000);
    session_start();
}

// Database connection
$host = 'localhost';
$dbname = 'u656059172_SistemaRH';
$username = 'u656059172_SistemaRH';
$password = '~7JB>d0v';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch(PDOException $e) {
    die("ERROR: No se pudo conectar a la base de datos. " . $e->getMessage());
}

// URLs y rutas base
define('SO_BASE_URL', 'https://expenicrhs.com/SO');
define('SO_SITE_URL', 'https://expenicrhs.com');

// Rutas de archivos
define('SO_UPLOAD_PATH', __DIR__ . '/uploads/');
define('SO_LESIONES_PATH', SO_UPLOAD_PATH . 'lesiones/');
define('SO_AREAS_PATH', SO_UPLOAD_PATH . 'areas/');

// Crear directorios si no existen
if (!file_exists(SO_LESIONES_PATH)) {
    @mkdir(SO_LESIONES_PATH, 0755, true);
}
if (!file_exists(SO_AREAS_PATH)) {
    @mkdir(SO_AREAS_PATH, 0755, true);
}

// Configuración de archivos
define('SO_MAX_FILE_SIZE', 5242880); // 5MB en bytes
define('SO_ALLOWED_FILE_TYPES', [
    'image/jpeg',
    'image/jpg',
    'image/png',
    'image/gif'
]);

// Set timezone
date_default_timezone_set('America/Costa_Rica');

// Error reporting (desactivado en producción)
error_reporting(0);
ini_set('display_errors', 0);

// CSRF Protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Tiempo de inactividad en segundos (83 minutos = 5000 segundos)
define('SO_SESSION_TIMEOUT', 5000);

// Configuración de roles y permisos
$GLOBALS['so_user_roles'] = [
    'admin' => ['view', 'add', 'edit', 'delete'],
    'so_manager' => ['view', 'add', 'edit'],
    'supervisor' => ['view', 'add'],
    'employee' => ['add'] // Los empleados solo pueden crear avisos
];

/**
 * Función para validar archivos de imagen
 */
function validarImagen($file) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['valido' => true, 'mensaje' => '', 'archivo' => null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['valido' => false, 'mensaje' => 'Error al subir el archivo.', 'archivo' => null];
    }

    if ($file['size'] > SO_MAX_FILE_SIZE) {
        return ['valido' => false, 'mensaje' => 'El archivo excede el tamaño máximo de 5MB.', 'archivo' => null];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, SO_ALLOWED_FILE_TYPES)) {
        return ['valido' => false, 'mensaje' => 'Tipo de archivo no permitido. Solo se permiten imágenes (JPG, PNG, GIF).', 'archivo' => null];
    }

    return ['valido' => true, 'mensaje' => '', 'archivo' => $file];
}

/**
 * Función para guardar imagen
 */
function guardarImagen($file, $tipo = 'lesion') {
    $validacion = validarImagen($file);
    if (!$validacion['valido']) {
        return ['exito' => false, 'mensaje' => $validacion['mensaje'], 'ruta' => null];
    }

    if ($validacion['archivo'] === null) {
        return ['exito' => true, 'mensaje' => '', 'ruta' => null];
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $nombreArchivo = uniqid('img_' . $tipo . '_') . '.' . $extension;

    $rutaDestino = ($tipo === 'lesion') ? SO_LESIONES_PATH : SO_AREAS_PATH;
    $rutaCompleta = $rutaDestino . $nombreArchivo;

    if (move_uploaded_file($file['tmp_name'], $rutaCompleta)) {
        return ['exito' => true, 'mensaje' => 'Imagen guardada correctamente.', 'ruta' => $nombreArchivo];
    } else {
        return ['exito' => false, 'mensaje' => 'Error al guardar la imagen.', 'ruta' => null];
    }
}

/**
 * Función para sanitizar entrada
 */
function limpiarEntrada($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Verificar token CSRF
 */
function verificarCSRF($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}
?>
