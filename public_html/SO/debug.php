<?php
/**
 * Archivo de Debug para identificar problemas
 */

// Activar todos los errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug del Módulo de Salud Ocupacional</h1>";
echo "<hr>";

// 1. Verificar información del servidor
echo "<h2>1. Información del Servidor</h2>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p><strong>Script Path:</strong> " . __DIR__ . "</p>";

// 2. Verificar sesión
echo "<hr><h2>2. Verificar Sesión</h2>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    echo "<p style='color: green;'>✓ Sesión iniciada correctamente</p>";
} else {
    echo "<p style='color: blue;'>ℹ Sesión ya estaba iniciada</p>";
}

// 3. Verificar conexión a base de datos
echo "<hr><h2>3. Verificar Conexión a Base de Datos</h2>";
$host = 'localhost';
$dbname = 'u656059172_SistemaRH';
$username = 'u656059172_SistemaRH';
$password = '~7JB>d0v';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>✓ Conexión a base de datos exitosa</p>";

    // Verificar si existen las tablas
    $stmt = $pdo->query("SHOW TABLES LIKE 'avisos_accidentes'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Tabla 'avisos_accidentes' existe</p>";
    } else {
        echo "<p style='color: red;'>✗ Tabla 'avisos_accidentes' NO existe</p>";
        echo "<p><strong>Solución:</strong> Ejecuta <a href='instalar.php'>instalar.php</a></p>";
    }
} catch(PDOException $e) {
    echo "<p style='color: red;'>✗ Error de conexión: " . $e->getMessage() . "</p>";
}

// 4. Verificar directorios
echo "<hr><h2>4. Verificar Directorios</h2>";
$dirs = [
    __DIR__ . '/uploads',
    __DIR__ . '/uploads/lesiones',
    __DIR__ . '/uploads/areas'
];

foreach ($dirs as $dir) {
    if (file_exists($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        echo "<p style='color: green;'>✓ {$dir} existe (Permisos: {$perms})</p>";
    } else {
        echo "<p style='color: red;'>✗ {$dir} NO existe</p>";
        if (mkdir($dir, 0755, true)) {
            echo "<p style='color: green;'>  ✓ Directorio creado</p>";
        } else {
            echo "<p style='color: red;'>  ✗ No se pudo crear el directorio</p>";
        }
    }
}

// 5. Verificar archivos PHP
echo "<hr><h2>5. Verificar Archivos PHP</h2>";
$files = [
    'config.php',
    'nuevo_aviso.php',
    'procesar_aviso.php',
    'admin_avisos.php',
    'ver_aviso.php',
    'index.php',
    'instalar.php'
];

foreach ($files as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        echo "<p style='color: green;'>✓ {$file} existe</p>";
    } else {
        echo "<p style='color: red;'>✗ {$file} NO existe</p>";
    }
}

// 6. Probar config.php
echo "<hr><h2>6. Probar Carga de config.php</h2>";
try {
    require_once __DIR__ . '/config.php';
    echo "<p style='color: green;'>✓ config.php cargado correctamente</p>";

    // Verificar que las constantes estén definidas
    $constants = ['SO_BASE_URL', 'SO_SITE_URL', 'SO_UPLOAD_PATH', 'SO_LESIONES_PATH', 'SO_AREAS_PATH'];
    foreach ($constants as $const) {
        if (defined($const)) {
            echo "<p style='color: green;'>✓ Constante {$const} = " . constant($const) . "</p>";
        } else {
            echo "<p style='color: red;'>✗ Constante {$const} NO definida</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error al cargar config.php: " . $e->getMessage() . "</p>";
}

// 7. Verificar extensiones PHP necesarias
echo "<hr><h2>7. Verificar Extensiones PHP</h2>";
$extensions = ['pdo', 'pdo_mysql', 'json', 'fileinfo', 'session'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p style='color: green;'>✓ Extensión {$ext} cargada</p>";
    } else {
        echo "<p style='color: red;'>✗ Extensión {$ext} NO cargada</p>";
    }
}

echo "<hr>";
echo "<h2>Diagnóstico Completado</h2>";
echo "<p>Si todo está en verde ✓, el sistema debería funcionar correctamente.</p>";
echo "<p>Si hay errores en rojo ✗, corrige los problemas indicados.</p>";
echo "<hr>";
echo "<p><a href='index.php'>Ir al Inicio</a> | <a href='nuevo_aviso.php'>Ir al Formulario</a></p>";
?>
