<?php
/**
 * Instalador del Módulo de Salud Ocupacional
 * Este script crea las tablas necesarias en la base de datos
 */

// Configuración de base de datos
$host = 'localhost';
$dbname = 'u656059172_SistemaRH';
$username = 'u656059172_SistemaRH';
$password = '~7JB>d0v';

$mensajes = [];
$errores = [];

try {
    // Conectar a la base de datos
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $mensajes[] = "✓ Conexión a la base de datos exitosa";

    // Crear tabla avisos_accidentes
    $sql_avisos = "CREATE TABLE IF NOT EXISTS avisos_accidentes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre_completo VARCHAR(255) NOT NULL,
        cedula VARCHAR(50) NOT NULL,
        puesto VARCHAR(150) NOT NULL,
        departamento VARCHAR(150) NOT NULL,
        supervisor_inmediato VARCHAR(255) NOT NULL,
        fecha_incidente DATE NOT NULL,
        hora_incidente TIME NOT NULL,
        lugar_exacto TEXT NOT NULL,
        actividad_realizaba TEXT NOT NULL,
        descripcion_accidente TEXT NOT NULL,
        partes_cuerpo_afectadas JSON,
        otra_parte_cuerpo VARCHAR(255),
        tipo_lesion VARCHAR(100) NOT NULL,
        otra_lesion VARCHAR(255),
        recibio_atencion_inmediata ENUM('si', 'no') NOT NULL,
        descripcion_atencion TEXT,
        hubo_testigos ENUM('si', 'no') NOT NULL,
        testigo_1 VARCHAR(255),
        testigo_2 VARCHAR(255),
        declaracion_afectado TEXT NOT NULL,
        foto_lesion VARCHAR(255),
        foto_area VARCHAR(255),
        firma_digital VARCHAR(255),
        fecha_llenado DATETIME NOT NULL,
        estado ENUM('pendiente', 'en_revision', 'cerrado') DEFAULT 'pendiente',
        fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_cedula (cedula),
        INDEX idx_fecha_incidente (fecha_incidente),
        INDEX idx_departamento (departamento),
        INDEX idx_estado (estado),
        INDEX idx_fecha_registro (fecha_registro)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $pdo->exec($sql_avisos);
    $mensajes[] = "✓ Tabla 'avisos_accidentes' creada exitosamente";

    // Crear tabla avisos_accidentes_seguimiento
    $sql_seguimiento = "CREATE TABLE IF NOT EXISTS avisos_accidentes_seguimiento (
        id INT AUTO_INCREMENT PRIMARY KEY,
        aviso_id INT NOT NULL,
        usuario VARCHAR(150) NOT NULL,
        accion VARCHAR(100) NOT NULL,
        comentario TEXT,
        fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_aviso_id (aviso_id),
        INDEX idx_fecha_accion (fecha_accion)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $pdo->exec($sql_seguimiento);
    $mensajes[] = "✓ Tabla 'avisos_accidentes_seguimiento' creada exitosamente";

    // Crear directorios de uploads si no existen
    $upload_dirs = [
        __DIR__ . '/uploads',
        __DIR__ . '/uploads/lesiones',
        __DIR__ . '/uploads/areas'
    ];

    foreach ($upload_dirs as $dir) {
        if (!file_exists($dir)) {
            if (mkdir($dir, 0755, true)) {
                $mensajes[] = "✓ Directorio creado: " . basename($dir);
            } else {
                $errores[] = "✗ No se pudo crear el directorio: " . basename($dir);
            }
        } else {
            $mensajes[] = "✓ Directorio ya existe: " . basename($dir);
        }
    }

    $instalacion_exitosa = empty($errores);

} catch(PDOException $e) {
    $errores[] = "✗ Error de base de datos: " . $e->getMessage();
    $instalacion_exitosa = false;
} catch(Exception $e) {
    $errores[] = "✗ Error: " . $e->getMessage();
    $instalacion_exitosa = false;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación - Módulo de Salud Ocupacional</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            max-width: 700px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .content {
            padding: 40px;
        }

        .mensaje {
            padding: 12px 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .mensaje.exito {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .mensaje.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .resultado {
            margin-top: 30px;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .resultado.exito {
            background: #d4edda;
            color: #155724;
        }

        .resultado.error {
            background: #f8d7da;
            color: #721c24;
        }

        .resultado h2 {
            margin-bottom: 15px;
        }

        .btn {
            display: inline-block;
            padding: 15px 30px;
            margin-top: 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transition: transform 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚙️ Instalación del Módulo</h1>
            <p>Sistema de Salud Ocupacional</p>
        </div>

        <div class="content">
            <h3 style="margin-bottom: 20px;">Proceso de Instalación:</h3>

            <?php foreach ($mensajes as $mensaje): ?>
                <div class="mensaje exito"><?php echo $mensaje; ?></div>
            <?php endforeach; ?>

            <?php foreach ($errores as $error): ?>
                <div class="mensaje error"><?php echo $error; ?></div>
            <?php endforeach; ?>

            <?php if ($instalacion_exitosa): ?>
                <div class="resultado exito">
                    <h2>✅ Instalación Completada</h2>
                    <p>El módulo de Salud Ocupacional ha sido instalado correctamente.</p>
                    <p>Ya puede comenzar a usar el sistema.</p>
                    <a href="nuevo_aviso.php" class="btn">Crear Nuevo Aviso</a>
                    <a href="admin_avisos.php" class="btn" style="background: #6c757d;">Administrar Avisos</a>
                </div>
            <?php else: ?>
                <div class="resultado error">
                    <h2>❌ Error en la Instalación</h2>
                    <p>Ocurrieron errores durante la instalación.</p>
                    <p>Por favor, revise los mensajes anteriores y corrija los problemas.</p>
                    <a href="instalar.php" class="btn">Reintentar Instalación</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
