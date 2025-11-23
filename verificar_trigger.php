<?php
/**
 * ═══════════════════════════════════════════════════════════════════════
 * VERIFICADOR DE TRIGGER AUTOMÁTICO
 * ═══════════════════════════════════════════════════════════════════════
 * Este script verifica que el trigger de adjudicaciones esté funcionando
 *
 * INSTRUCCIONES:
 * 1. Sube este archivo a tu servidor
 * 2. Ábrelo en el navegador: https://licitacionesya.com/verificar_trigger.php
 * 3. Ve el resultado
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/db.php';

if (!isset($pdo) && isset($conn)) {
    $pdo = $conn;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Trigger de Adjudicaciones</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 30px;
            background: #f5f5f5;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2563eb;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 15px;
        }
        h2 {
            color: #374151;
            margin-top: 30px;
            border-left: 4px solid #2563eb;
            padding-left: 15px;
        }
        .ok {
            background: #d1fae5;
            border-left: 4px solid #10b981;
            color: #065f46;
            padding: 15px 20px;
            margin: 15px 0;
            border-radius: 6px;
        }
        .error {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            color: #991b1b;
            padding: 15px 20px;
            margin: 15px 0;
            border-radius: 6px;
        }
        .warning {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            color: #92400e;
            padding: 15px 20px;
            margin: 15px 0;
            border-radius: 6px;
        }
        .info {
            background: #dbeafe;
            border-left: 4px solid #2563eb;
            color: #1e40af;
            padding: 15px 20px;
            margin: 15px 0;
            border-radius: 6px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #e5e7eb;
        }
        th {
            background: #f3f4f6;
            font-weight: 600;
            color: #374151;
        }
        tr:hover {
            background: #f9fafb;
        }
        .code {
            background: #1e293b;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            font-family: monospace;
            margin: 15px 0;
        }
        .stat {
            display: inline-block;
            background: #2563eb;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            margin: 5px;
        }
        .btn {
            background: #2563eb;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #1d4ed8;
        }
        .btn-danger {
            background: #ef4444;
        }
        .btn-danger:hover {
            background: #dc2626;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>⚡ Verificador de Trigger de Adjudicaciones</h1>

        <?php
        // ═══════════════════════════════════════════════════════════════
        // 1. VERIFICAR SI EXISTEN LOS TRIGGERS
        // ═══════════════════════════════════════════════════════════════
        echo "<h2>1️⃣ Verificar si los Triggers Existen</h2>";

        try {
            $stmt = $pdo->query("SHOW TRIGGERS WHERE `Table` = 'lineas_adjudicadas'");
            $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($triggers) > 0) {
                echo "<div class='ok'>";
                echo "<strong>✅ Triggers encontrados: " . count($triggers) . "</strong>";
                echo "</div>";

                echo "<table>";
                echo "<tr><th>Trigger</th><th>Event</th><th>Timing</th><th>Statement</th></tr>";
                foreach ($triggers as $trigger) {
                    echo "<tr>";
                    echo "<td><strong>" . htmlspecialchars($trigger['Trigger']) . "</strong></td>";
                    echo "<td>" . htmlspecialchars($trigger['Event']) . "</td>";
                    echo "<td>" . htmlspecialchars($trigger['Timing']) . "</td>";
                    echo "<td><code>" . htmlspecialchars(substr($trigger['Statement'], 0, 100)) . "...</code></td>";
                    echo "</tr>";
                }
                echo "</table>";

                // Verificar triggers específicos
                $trigger_names = array_column($triggers, 'Trigger');
                $expected = [
                    'actualizar_licitacion_al_adjudicar',
                    'actualizar_licitacion_al_modificar',
                    'actualizar_licitacion_al_eliminar'
                ];

                $missing = array_diff($expected, $trigger_names);

                if (empty($missing)) {
                    echo "<div class='ok'>✅ Todos los triggers necesarios están creados</div>";
                } else {
                    echo "<div class='warning'>⚠️ Faltan triggers: " . implode(', ', $missing) . "</div>";
                }

            } else {
                echo "<div class='error'>";
                echo "<strong>❌ NO se encontraron triggers</strong><br>";
                echo "Debes ejecutar el script <code>crear_trigger_adjudicaciones.sql</code> en phpMyAdmin.";
                echo "</div>";
            }

        } catch (PDOException $e) {
            echo "<div class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }

        // ═══════════════════════════════════════════════════════════════
        // 2. VERIFICAR ESTADO DE LICITACIONES
        // ═══════════════════════════════════════════════════════════════
        echo "<h2>2️⃣ Estado Actual de Licitaciones</h2>";

        try {
            $stmt = $pdo->query("
                SELECT
                    estado,
                    COUNT(*) as cantidad,
                    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM licitaciones), 2) as porcentaje
                FROM licitaciones
                GROUP BY estado
                ORDER BY cantidad DESC
            ");
            $estados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo "<table>";
            echo "<tr><th>Estado</th><th>Cantidad</th><th>Porcentaje</th></tr>";

            $tiene_adjudicadas = false;
            foreach ($estados as $estado) {
                $estilo = '';
                if ($estado['estado'] === 'adjudicada') {
                    $estilo = 'background: #d1fae5;';
                    $tiene_adjudicadas = true;
                }

                echo "<tr style='$estilo'>";
                echo "<td><strong>" . htmlspecialchars($estado['estado']) . "</strong></td>";
                echo "<td>" . number_format($estado['cantidad']) . "</td>";
                echo "<td>" . $estado['porcentaje'] . "%</td>";
                echo "</tr>";
            }
            echo "</table>";

            if ($tiene_adjudicadas) {
                echo "<div class='ok'>✅ Hay licitaciones marcadas como adjudicadas</div>";
            } else {
                echo "<div class='error'>❌ NO hay licitaciones adjudicadas (ejecuta el UPDATE del Paso 6 del SQL)</div>";
            }

        } catch (PDOException $e) {
            echo "<div class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }

        // ═══════════════════════════════════════════════════════════════
        // 3. COMPARAR CON LINEAS_ADJUDICADAS
        // ═══════════════════════════════════════════════════════════════
        echo "<h2>3️⃣ Comparación con lineas_adjudicadas</h2>";

        try {
            // Contar líneas adjudicadas
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM lineas_adjudicadas");
            $total_lineas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Contar procedimientos únicos
            $stmt = $pdo->query("SELECT COUNT(DISTINCT numero_sicop) as total FROM lineas_adjudicadas");
            $procedimientos_unicos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Contar licitaciones adjudicadas
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM licitaciones WHERE estado = 'adjudicada'");
            $licitaciones_adjudicadas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            echo "<div class='info'>";
            echo "<strong>📊 Estadísticas:</strong><br>";
            echo "<span class='stat'>Líneas adjudicadas: " . number_format($total_lineas) . "</span>";
            echo "<span class='stat'>Procedimientos únicos: " . number_format($procedimientos_unicos) . "</span>";
            echo "<span class='stat'>Licitaciones adjudicadas: " . number_format($licitaciones_adjudicadas) . "</span>";
            echo "</div>";

            if ($licitaciones_adjudicadas === $procedimientos_unicos) {
                echo "<div class='ok'>";
                echo "✅ <strong>PERFECTO:</strong> El número de licitaciones adjudicadas coincide con los procedimientos únicos en lineas_adjudicadas.";
                echo "</div>";
            } else {
                echo "<div class='warning'>";
                echo "⚠️ <strong>DESINCRONIZADO:</strong> Hay $procedimientos_unicos procedimientos con adjudicaciones, pero solo $licitaciones_adjudicadas licitaciones marcadas como adjudicadas.<br>";
                echo "<strong>Solución:</strong> Ejecuta el UPDATE del Paso 6 en <code>crear_trigger_adjudicaciones.sql</code>";
                echo "</div>";
            }

        } catch (PDOException $e) {
            echo "<div class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }

        // ═══════════════════════════════════════════════════════════════
        // 4. VER LICITACIONES ADJUDICADAS RECIENTES
        // ═══════════════════════════════════════════════════════════════
        echo "<h2>4️⃣ Licitaciones Adjudicadas Recientes</h2>";

        try {
            $stmt = $pdo->query("
                SELECT
                    l.numero_sicop,
                    l.titulo,
                    l.estado,
                    l.fecha_adjudicacion,
                    (SELECT COUNT(*) FROM lineas_adjudicadas WHERE numero_sicop = l.numero_sicop) as lineas_adj
                FROM licitaciones l
                WHERE l.estado = 'adjudicada'
                ORDER BY l.fecha_adjudicacion DESC
                LIMIT 10
            ");
            $licitaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($licitaciones) > 0) {
                echo "<table>";
                echo "<tr><th>Número SICOP</th><th>Título</th><th>Fecha Adjud.</th><th>Líneas</th></tr>";

                foreach ($licitaciones as $lic) {
                    echo "<tr>";
                    echo "<td><code>" . htmlspecialchars($lic['numero_sicop']) . "</code></td>";
                    echo "<td>" . htmlspecialchars(substr($lic['titulo'], 0, 60)) . "...</td>";
                    echo "<td>" . htmlspecialchars($lic['fecha_adjudicacion'] ?? 'Sin fecha') . "</td>";
                    echo "<td><strong>" . $lic['lineas_adj'] . "</strong></td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<div class='warning'>⚠️ No hay licitaciones adjudicadas para mostrar</div>";
            }

        } catch (PDOException $e) {
            echo "<div class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }

        // ═══════════════════════════════════════════════════════════════
        // 5. PRUEBA EN VIVO (OPCIONAL)
        // ═══════════════════════════════════════════════════════════════
        echo "<h2>5️⃣ Probar Trigger (Opcional)</h2>";

        if (isset($_GET['test']) && $_GET['test'] === '1') {
            echo "<div class='info'>🧪 Ejecutando prueba del trigger...</div>";

            try {
                // Buscar una licitación que NO esté adjudicada
                $stmt = $pdo->query("
                    SELECT numero_sicop, titulo
                    FROM licitaciones
                    WHERE estado != 'adjudicada'
                    AND numero_sicop IS NOT NULL
                    AND numero_sicop != ''
                    LIMIT 1
                ");
                $lic_prueba = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($lic_prueba) {
                    $numero_sicop_prueba = $lic_prueba['numero_sicop'];

                    echo "<div class='code'>";
                    echo "Licitación de prueba: {$numero_sicop_prueba}<br>";
                    echo "Título: " . htmlspecialchars(substr($lic_prueba['titulo'], 0, 80)) . "...";
                    echo "</div>";

                    // Insertar una línea de prueba
                    $stmt = $pdo->prepare("
                        INSERT INTO lineas_adjudicadas (numero_sicop, descripcion, monto_adjudicado, fecha_adjudicacion)
                        VALUES (?, 'PRUEBA AUTOMÁTICA DE TRIGGER', 1.00, CURDATE())
                    ");
                    $stmt->execute([$numero_sicop_prueba]);

                    echo "<div class='ok'>✅ Línea de prueba insertada</div>";

                    // Verificar si el trigger actualizó la licitación
                    $stmt = $pdo->prepare("SELECT estado, fecha_adjudicacion FROM licitaciones WHERE numero_sicop = ?");
                    $stmt->execute([$numero_sicop_prueba]);
                    $lic_actualizada = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($lic_actualizada['estado'] === 'adjudicada') {
                        echo "<div class='ok'>";
                        echo "<strong>✅ ¡TRIGGER FUNCIONANDO!</strong><br>";
                        echo "El estado cambió automáticamente a 'adjudicada'<br>";
                        echo "Fecha de adjudicación: " . htmlspecialchars($lic_actualizada['fecha_adjudicacion']);
                        echo "</div>";
                    } else {
                        echo "<div class='error'>❌ El trigger NO funcionó. Estado actual: " . htmlspecialchars($lic_actualizada['estado']) . "</div>";
                    }

                    // Limpiar la prueba
                    $stmt = $pdo->prepare("DELETE FROM lineas_adjudicadas WHERE numero_sicop = ? AND descripcion = 'PRUEBA AUTOMÁTICA DE TRIGGER'");
                    $stmt->execute([$numero_sicop_prueba]);

                    // Restaurar el estado original
                    $stmt = $pdo->prepare("UPDATE licitaciones SET estado = 'abierta', fecha_adjudicacion = NULL WHERE numero_sicop = ?");
                    $stmt->execute([$numero_sicop_prueba]);

                    echo "<div class='info'>🧹 Prueba limpiada, licitación restaurada</div>";

                } else {
                    echo "<div class='warning'>No hay licitaciones disponibles para probar</div>";
                }

            } catch (PDOException $e) {
                echo "<div class='error'>Error en la prueba: " . htmlspecialchars($e->getMessage()) . "</div>";
            }

        } else {
            echo "<div class='info'>";
            echo "<p>Puedes hacer una prueba en vivo para verificar que el trigger funciona.</p>";
            echo "<p><strong>⚠️ Advertencia:</strong> Esto insertará y eliminará una línea de prueba temporalmente.</p>";
            echo "<a href='?test=1' class='btn'>🧪 Ejecutar Prueba</a>";
            echo "</div>";
        }

        // ═══════════════════════════════════════════════════════════════
        // 6. RESUMEN Y RECOMENDACIONES
        // ═══════════════════════════════════════════════════════════════
        echo "<h2>6️⃣ Resumen</h2>";

        $triggers_ok = count($triggers ?? []) >= 3;
        $lics_adj_ok = ($licitaciones_adjudicadas ?? 0) > 0;
        $sincronizado = ($licitaciones_adjudicadas ?? 0) === ($procedimientos_unicos ?? 0);

        if ($triggers_ok && $lics_adj_ok && $sincronizado) {
            echo "<div class='ok'>";
            echo "<h3>✅ TODO ESTÁ FUNCIONANDO CORRECTAMENTE</h3>";
            echo "<ul>";
            echo "<li>✅ Triggers instalados</li>";
            echo "<li>✅ Licitaciones adjudicadas sincronizadas</li>";
            echo "<li>✅ Dashboard funcionará correctamente</li>";
            echo "</ul>";
            echo "<p><strong>Resultado:</strong> Cuando importes nuevas adjudicaciones, las licitaciones se actualizarán automáticamente.</p>";
            echo "</div>";
        } else {
            echo "<div class='warning'>";
            echo "<h3>⚠️ SE REQUIERE ACCIÓN</h3>";
            echo "<ul>";
            if (!$triggers_ok) {
                echo "<li>❌ Falta instalar los triggers → Ejecuta <code>crear_trigger_adjudicaciones.sql</code></li>";
            }
            if (!$lics_adj_ok) {
                echo "<li>❌ No hay licitaciones adjudicadas → Ejecuta el UPDATE del Paso 6</li>";
            }
            if (!$sincronizado) {
                echo "<li>❌ Desincronizado → Ejecuta el UPDATE del Paso 6</li>";
            }
            echo "</ul>";
            echo "</div>";
        }

        echo "<div style='text-align: center; margin-top: 40px;'>";
        echo "<a href='dashboard.php' class='btn'>Ver Dashboard</a>";
        echo "<a href='?refresh=1' class='btn'>🔄 Actualizar</a>";
        echo "</div>";
        ?>
    </div>
</body>
</html>
