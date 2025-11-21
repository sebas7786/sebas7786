<?php
require_once 'config.php';

$aviso_id = $_GET['id'] ?? null;

if (!$aviso_id) {
    header('Location: admin_avisos.php');
    exit;
}

// Obtener datos del aviso
$sql = "SELECT * FROM avisos_accidentes WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $aviso_id]);
$aviso = $stmt->fetch();

if (!$aviso) {
    die('Aviso no encontrado.');
}

// Obtener seguimiento
$sql_seguimiento = "SELECT * FROM avisos_accidentes_seguimiento WHERE aviso_id = :aviso_id ORDER BY fecha_accion DESC";
$stmt_seguimiento = $pdo->prepare($sql_seguimiento);
$stmt_seguimiento->execute([':aviso_id' => $aviso_id]);
$seguimientos = $stmt_seguimiento->fetchAll();

// Decodificar JSON de partes del cuerpo
$partes_cuerpo = json_decode($aviso['partes_cuerpo_afectadas'], true) ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aviso #<?php echo str_pad($aviso['id'], 6, '0', STR_PAD_LEFT); ?> - Salud Ocupacional</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 28px;
        }

        .btn {
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            transition: transform 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .seccion-titulo {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 20px;
            font-weight: 600;
            border-left: 4px solid #667eea;
            padding-left: 15px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .info-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .info-item label {
            display: block;
            font-size: 13px;
            color: #666;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .info-item .value {
            font-size: 15px;
            color: #2c3e50;
        }

        .descripcion {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            line-height: 1.8;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
        }

        .badge-pendiente {
            background: #fff3cd;
            color: #856404;
        }

        .badge-revision {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-cerrado {
            background: #d4edda;
            color: #155724;
        }

        .partes-cuerpo {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .parte-tag {
            padding: 8px 15px;
            background: #e7f3ff;
            color: #0066cc;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
        }

        .evidencias {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .evidencia-item {
            text-align: center;
        }

        .evidencia-item img {
            max-width: 100%;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 10px;
        }

        .evidencia-item label {
            display: block;
            font-weight: 600;
            color: #666;
            margin-bottom: 10px;
        }

        .timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
        }

        .timeline-item {
            position: relative;
            padding: 15px;
            margin-bottom: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -24px;
            top: 20px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #667eea;
            border: 3px solid white;
        }

        .timeline-item .fecha {
            font-size: 12px;
            color: #999;
            margin-bottom: 5px;
        }

        .timeline-item .usuario {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .timeline-item .accion {
            color: #555;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
            font-style: italic;
        }

        @media print {
            .btn, .header {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>📋 Aviso de Accidente #<?php echo str_pad($aviso['id'], 6, '0', STR_PAD_LEFT); ?></h1>
                <p style="margin-top: 10px; opacity: 0.9;">
                    Registrado el <?php echo date('d/m/Y H:i', strtotime($aviso['fecha_registro'])); ?>
                </p>
            </div>
            <a href="admin_avisos.php" class="btn btn-secondary">← Volver</a>
        </div>

        <!-- Estado -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3>Estado del Aviso</h3>
                </div>
                <div>
                    <?php
                    $badge_class = 'badge-pendiente';
                    if ($aviso['estado'] === 'en_revision') $badge_class = 'badge-revision';
                    if ($aviso['estado'] === 'cerrado') $badge_class = 'badge-cerrado';
                    ?>
                    <span class="badge <?php echo $badge_class; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $aviso['estado'])); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- 1. Información Personal -->
        <div class="card">
            <h2 class="seccion-titulo">1. Información Personal</h2>
            <div class="info-grid">
                <div class="info-item">
                    <label>Nombre Completo</label>
                    <div class="value"><?php echo htmlspecialchars($aviso['nombre_completo']); ?></div>
                </div>
                <div class="info-item">
                    <label>Cédula</label>
                    <div class="value"><?php echo htmlspecialchars($aviso['cedula']); ?></div>
                </div>
                <div class="info-item">
                    <label>Puesto</label>
                    <div class="value"><?php echo htmlspecialchars($aviso['puesto']); ?></div>
                </div>
                <div class="info-item">
                    <label>Departamento</label>
                    <div class="value"><?php echo htmlspecialchars($aviso['departamento']); ?></div>
                </div>
                <div class="info-item">
                    <label>Supervisor Inmediato</label>
                    <div class="value"><?php echo htmlspecialchars($aviso['supervisor_inmediato']); ?></div>
                </div>
                <div class="info-item">
                    <label>Fecha del Incidente</label>
                    <div class="value"><?php echo date('d/m/Y', strtotime($aviso['fecha_incidente'])); ?></div>
                </div>
                <div class="info-item">
                    <label>Hora del Incidente</label>
                    <div class="value"><?php echo date('H:i', strtotime($aviso['hora_incidente'])); ?></div>
                </div>
            </div>
        </div>

        <!-- 2. Información del Lugar -->
        <div class="card">
            <h2 class="seccion-titulo">2. Información del Lugar</h2>
            <div class="info-item">
                <label>Lugar Exacto</label>
                <div class="value"><?php echo htmlspecialchars($aviso['lugar_exacto']); ?></div>
            </div>
        </div>

        <!-- 3. Actividad que Realizaba -->
        <div class="card">
            <h2 class="seccion-titulo">3. Actividad que Realizaba</h2>
            <div class="descripcion">
                <?php echo nl2br(htmlspecialchars($aviso['actividad_realizaba'])); ?>
            </div>
        </div>

        <!-- 4. Descripción del Accidente -->
        <div class="card">
            <h2 class="seccion-titulo">4. Descripción del Accidente</h2>
            <div class="descripcion">
                <?php echo nl2br(htmlspecialchars($aviso['descripcion_accidente'])); ?>
            </div>
        </div>

        <!-- 5. Partes del Cuerpo Afectadas -->
        <div class="card">
            <h2 class="seccion-titulo">5. Partes del Cuerpo Afectadas</h2>
            <div class="partes-cuerpo">
                <?php foreach ($partes_cuerpo as $parte): ?>
                    <span class="parte-tag"><?php echo htmlspecialchars($parte); ?></span>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($aviso['otra_parte_cuerpo'])): ?>
                <div class="info-item" style="margin-top: 15px;">
                    <label>Otra Parte Especificada</label>
                    <div class="value"><?php echo htmlspecialchars($aviso['otra_parte_cuerpo']); ?></div>
                </div>
            <?php endif; ?>
        </div>

        <!-- 6. Tipo de Lesión -->
        <div class="card">
            <h2 class="seccion-titulo">6. Tipo de Lesión</h2>
            <div class="info-item">
                <label>Tipo de Lesión</label>
                <div class="value"><?php echo htmlspecialchars($aviso['tipo_lesion']); ?></div>
            </div>
            <?php if (!empty($aviso['otra_lesion'])): ?>
                <div class="info-item" style="margin-top: 15px;">
                    <label>Otra Lesión Especificada</label>
                    <div class="value"><?php echo htmlspecialchars($aviso['otra_lesion']); ?></div>
                </div>
            <?php endif; ?>
        </div>

        <!-- 7. Atención Inmediata -->
        <div class="card">
            <h2 class="seccion-titulo">7. Atención Inmediata</h2>
            <div class="info-item">
                <label>¿Recibió Atención Inmediata?</label>
                <div class="value"><?php echo $aviso['recibio_atencion_inmediata'] === 'si' ? 'Sí' : 'No'; ?></div>
            </div>
            <?php if ($aviso['recibio_atencion_inmediata'] === 'si' && !empty($aviso['descripcion_atencion'])): ?>
                <div class="descripcion" style="margin-top: 15px;">
                    <label style="display: block; margin-bottom: 10px;">Descripción de la Atención</label>
                    <?php echo nl2br(htmlspecialchars($aviso['descripcion_atencion'])); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- 8. Testigos -->
        <div class="card">
            <h2 class="seccion-titulo">8. Testigos</h2>
            <div class="info-item">
                <label>¿Hubo Testigos?</label>
                <div class="value"><?php echo $aviso['hubo_testigos'] === 'si' ? 'Sí' : 'No'; ?></div>
            </div>
            <?php if ($aviso['hubo_testigos'] === 'si'): ?>
                <div class="info-grid" style="margin-top: 15px;">
                    <?php if (!empty($aviso['testigo_1'])): ?>
                        <div class="info-item">
                            <label>Testigo 1</label>
                            <div class="value"><?php echo htmlspecialchars($aviso['testigo_1']); ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($aviso['testigo_2'])): ?>
                        <div class="info-item">
                            <label>Testigo 2</label>
                            <div class="value"><?php echo htmlspecialchars($aviso['testigo_2']); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- 9. Declaración del Afectado -->
        <div class="card">
            <h2 class="seccion-titulo">9. Declaración del Afectado</h2>
            <div class="descripcion">
                <?php echo nl2br(htmlspecialchars($aviso['declaracion_afectado'])); ?>
            </div>
        </div>

        <!-- 10. Evidencias -->
        <div class="card">
            <h2 class="seccion-titulo">10. Evidencias Fotográficas</h2>
            <?php if (!empty($aviso['foto_lesion']) || !empty($aviso['foto_area'])): ?>
                <div class="evidencias">
                    <?php if (!empty($aviso['foto_lesion'])): ?>
                        <div class="evidencia-item">
                            <label>Foto de la Lesión</label>
                            <img src="uploads/lesiones/<?php echo htmlspecialchars($aviso['foto_lesion']); ?>" alt="Foto de la lesión">
                            <a href="uploads/lesiones/<?php echo htmlspecialchars($aviso['foto_lesion']); ?>" target="_blank" class="btn btn-secondary" style="margin-top: 10px; font-size: 13px; padding: 8px 15px;">Ver en tamaño completo</a>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($aviso['foto_area'])): ?>
                        <div class="evidencia-item">
                            <label>Foto del Área</label>
                            <img src="uploads/areas/<?php echo htmlspecialchars($aviso['foto_area']); ?>" alt="Foto del área">
                            <a href="uploads/areas/<?php echo htmlspecialchars($aviso['foto_area']); ?>" target="_blank" class="btn btn-secondary" style="margin-top: 10px; font-size: 13px; padding: 8px 15px;">Ver en tamaño completo</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="no-data">No se adjuntaron evidencias fotográficas</div>
            <?php endif; ?>
        </div>

        <!-- Seguimiento -->
        <div class="card">
            <h2 class="seccion-titulo">📝 Seguimiento del Aviso</h2>
            <?php if (!empty($seguimientos)): ?>
                <div class="timeline">
                    <?php foreach ($seguimientos as $seg): ?>
                        <div class="timeline-item">
                            <div class="fecha"><?php echo date('d/m/Y H:i', strtotime($seg['fecha_accion'])); ?></div>
                            <div class="usuario"><?php echo htmlspecialchars($seg['usuario']); ?></div>
                            <div class="accion">
                                <strong><?php echo htmlspecialchars($seg['accion']); ?></strong>
                                <?php if (!empty($seg['comentario'])): ?>
                                    <br><?php echo htmlspecialchars($seg['comentario']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-data">No hay seguimiento registrado</div>
            <?php endif; ?>
        </div>

        <!-- Botones de acción -->
        <div class="card">
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button onclick="window.print()" class="btn btn-secondary">🖨️ Imprimir</button>
                <a href="admin_avisos.php" class="btn btn-secondary">← Volver a la Lista</a>
            </div>
        </div>
    </div>
</body>
</html>
