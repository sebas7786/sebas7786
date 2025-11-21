<?php
/**
 * Página de Inicio del Módulo de Salud Ocupacional
 */

// Configuración de base de datos
$host = 'localhost';
$dbname = 'u656059172_SistemaRH';
$username = 'u656059172_SistemaRH';
$password = '~7JB>d0v';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Verificar si las tablas existen
    $stmt = $pdo->query("SHOW TABLES LIKE 'avisos_accidentes'");
    $tabla_existe = $stmt->rowCount() > 0;

    if (!$tabla_existe) {
        // Redirigir al instalador
        header('Location: instalar.php');
        exit;
    }

} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salud Ocupacional - Sistema de Avisos</title>
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
            max-width: 900px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 50px 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 36px;
            margin-bottom: 15px;
        }

        .header p {
            font-size: 16px;
            opacity: 0.9;
        }

        .content {
            padding: 50px 40px;
        }

        .menu-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .menu-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            transition: all 0.3s;
            border: 2px solid transparent;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            border-color: #667eea;
        }

        .menu-card .icono {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .menu-card h3 {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .menu-card p {
            font-size: 14px;
            color: #666;
            line-height: 1.6;
        }

        .info-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .info-box h3 {
            color: #856404;
            margin-bottom: 10px;
        }

        .info-box p {
            color: #856404;
            font-size: 14px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏥 Salud Ocupacional</h1>
            <p>Sistema de Gestión de Avisos de Accidentes Laborales</p>
        </div>

        <div class="content">
            <div class="info-box">
                <h3>📢 Importante</h3>
                <p>
                    Este sistema permite registrar y gestionar avisos de accidentes laborales de manera rápida y eficiente.
                    Si ha sufrido un accidente laboral, por favor reporte el incidente lo antes posible.
                </p>
            </div>

            <h2 style="text-align: center; color: #2c3e50; margin-bottom: 30px;">¿Qué desea hacer?</h2>

            <div class="menu-cards">
                <a href="nuevo_aviso.php" class="menu-card">
                    <div class="icono">📝</div>
                    <h3>Reportar Accidente</h3>
                    <p>Complete el formulario para reportar un nuevo accidente laboral</p>
                </a>

                <a href="admin_avisos.php" class="menu-card">
                    <div class="icono">📊</div>
                    <h3>Ver Avisos</h3>
                    <p>Consulte y administre los avisos de accidentes registrados</p>
                </a>
            </div>

            <div style="margin-top: 40px; text-align: center; color: #666; font-size: 13px;">
                <p>Sistema de Salud Ocupacional v1.0</p>
                <p style="margin-top: 5px;">Diseñado para la seguridad y bienestar de nuestros colaboradores</p>
            </div>
        </div>
    </div>
</body>
</html>
