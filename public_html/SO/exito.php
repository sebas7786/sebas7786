<?php
require_once 'config.php';

$aviso_id = $_GET['id'] ?? null;
$mensaje = $_SESSION['mensaje_exito'] ?? 'Aviso registrado exitosamente';

// Limpiar el mensaje de la sesión
unset($_SESSION['mensaje_exito']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aviso Registrado - Salud Ocupacional</title>
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
            max-width: 600px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
            text-align: center;
        }

        .success-icon {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            padding: 40px;
            color: white;
        }

        .success-icon svg {
            width: 80px;
            height: 80px;
            animation: checkmark 0.5s ease-in-out;
        }

        @keyframes checkmark {
            0% {
                transform: scale(0);
            }
            50% {
                transform: scale(1.2);
            }
            100% {
                transform: scale(1);
            }
        }

        .content {
            padding: 40px;
        }

        h1 {
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .mensaje {
            font-size: 16px;
            color: #555;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .aviso-numero {
            background: #f8f9fa;
            border: 2px solid #667eea;
            border-radius: 10px;
            padding: 20px;
            margin: 25px 0;
        }

        .aviso-numero strong {
            color: #667eea;
            font-size: 24px;
        }

        .info-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            margin: 25px 0;
            text-align: left;
        }

        .info-box h3 {
            color: #856404;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .info-box ul {
            margin-left: 20px;
            color: #856404;
            font-size: 14px;
            line-height: 1.8;
        }

        .btn {
            display: inline-block;
            padding: 15px 30px;
            margin: 10px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .footer {
            background: #f8f9fa;
            padding: 20px;
            font-size: 13px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>

        <div class="content">
            <h1>✅ Aviso Registrado Exitosamente</h1>

            <p class="mensaje"><?php echo htmlspecialchars($mensaje); ?></p>

            <?php if ($aviso_id): ?>
            <div class="aviso-numero">
                <p>Número de Aviso:</p>
                <strong>#<?php echo str_pad($aviso_id, 6, '0', STR_PAD_LEFT); ?></strong>
            </div>
            <?php endif; ?>

            <div class="info-box">
                <h3>📋 Próximos Pasos:</h3>
                <ul>
                    <li>Su aviso ha sido registrado en el sistema de Salud Ocupacional</li>
                    <li>El departamento correspondiente revisará la información</li>
                    <li>Será contactado en caso de requerir información adicional</li>
                    <li>Puede guardar su número de aviso para futuras referencias</li>
                </ul>
            </div>

            <div style="margin-top: 30px;">
                <a href="nuevo_aviso.php" class="btn btn-secondary">Registrar Otro Aviso</a>
                <?php if ($aviso_id): ?>
                <a href="ver_aviso.php?id=<?php echo $aviso_id; ?>" class="btn btn-primary">Ver Mi Aviso</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="footer">
            <p>Sistema de Salud Ocupacional</p>
            <p>Fecha: <?php echo date('d/m/Y H:i'); ?></p>
        </div>
    </div>
</body>
</html>
