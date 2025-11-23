<?php
/**
 * DIAGNÓSTICO DE DASHBOARD
 * Este archivo ayuda a identificar por qué no se muestran licitaciones adjudicadas
 */

// Activar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar sesión
if (!isset($_SESSION['usuario_id']) && !isset($_SESSION['user_id'])) {
    die("Debes iniciar sesión");
}

require_once __DIR__ . '/../config/db.php';

if (!isset($pdo) && isset($conn)) {
    $pdo = $conn;
}

echo "<h1>🔍 Diagnóstico del Sistema de Licitaciones</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    h1 { color: #333; }
    h2 { color: #2563eb; margin-top: 30px; border-bottom: 2px solid #2563eb; padding-bottom: 10px; }
    .ok { color: #10b981; font-weight: bold; }
    .error { color: #ef4444; font-weight: bold; }
    .warning { color: #f59e0b; font-weight: bold; }
    table { border-collapse: collapse; width: 100%; margin: 15px 0; background: white; }
    th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
    th { background: #2563eb; color: white; }
    tr:nth-child(even) { background: #f8f9fa; }
    .code { background: #1e293b; color: #e2e8f0; padding: 15px; border-radius: 8px; overflow-x: auto; margin: 10px 0; }
    .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
</style>";

// ═══════════════════════════════════════════════════════════════════════
// 1. VERIFICAR ESTRUCTURA DE TABLA LICITACIONES
// ═══════════════════════════════════════════════════════════════════════
echo "<div class='section'>";
echo "<h2>1. Estructura de la tabla 'licitaciones'</h2>";

try {
    $stmt = $pdo->query("DESCRIBE licitaciones");
    $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<p class='ok'>✓ Tabla 'licitaciones' encontrada con " . count($columnas) . " columnas</p>";

    echo "<table>";
    echo "<tr><th>Columna</th><th>Tipo</th><th>Null</th><th>Default</th></tr>";

    $columnas_importantes = ['estado', 'fecha_adjudicacion', 'adjudicada', 'fecha_cierre', 'fecha_cierre_ofertas', 'fecha_cierre_recepcion'];

    foreach ($columnas as $col) {
        $importante = in_array($col['Field'], $columnas_importantes);
        $estilo = $importante ? "background: #fef3c7;" : "";

        echo "<tr style='$estilo'>";
        echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Verificar columnas clave
    $columnas_nombres = array_column($columnas, 'Field');

    echo "<h3>Verificación de columnas clave:</h3>";
    echo "<ul>";

    if (in_array('estado', $columnas_nombres)) {
        echo "<li class='ok'>✓ Columna 'estado' existe</li>";
    } else {
        echo "<li class='error'>✗ Columna 'estado' NO existe</li>";
    }

    if (in_array('fecha_adjudicacion', $columnas_nombres)) {
        echo "<li class='ok'>✓ Columna 'fecha_adjudicacion' existe</li>";
    } else {
        echo "<li class='warning'>⚠ Columna 'fecha_adjudicacion' NO existe (se puede agregar)</li>";
    }

    if (in_array('adjudicada', $columnas_nombres)) {
        echo "<li class='ok'>✓ Columna 'adjudicada' existe</li>";
    } else {
        echo "<li class='warning'>⚠ Columna 'adjudicada' NO existe (se puede agregar)</li>";
    }

    echo "</ul>";

} catch (PDOException $e) {
    echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// ═══════════════════════════════════════════════════════════════════════
// 2. VERIFICAR VALORES DE LA COLUMNA 'estado'
// ═══════════════════════════════════════════════════════════════════════
echo "<div class='section'>";
echo "<h2>2. Valores en la columna 'estado'</h2>";

try {
    $stmt = $pdo->query("
        SELECT estado, COUNT(*) as cantidad
        FROM licitaciones
        WHERE estado IS NOT NULL AND estado != ''
        GROUP BY estado
        ORDER BY cantidad DESC
    ");
    $estados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($estados) > 0) {
        echo "<p class='ok'>✓ Encontrados " . count($estados) . " estados diferentes</p>";

        echo "<table>";
        echo "<tr><th>Estado</th><th>Cantidad</th></tr>";

        foreach ($estados as $estado) {
            $es_adjudicada = (stripos($estado['estado'], 'adjudica') !== false);
            $estilo = $es_adjudicada ? "background: #d1fae5; font-weight: bold;" : "";

            echo "<tr style='$estilo'>";
            echo "<td>" . htmlspecialchars($estado['estado']) . "</td>";
            echo "<td>" . number_format($estado['cantidad']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";

        // Buscar variaciones de "adjudicada"
        $variaciones = [];
        foreach ($estados as $estado) {
            if (stripos($estado['estado'], 'adjudica') !== false) {
                $variaciones[] = $estado['estado'];
            }
        }

        if (count($variaciones) > 0) {
            echo "<p class='ok'>✓ Encontradas licitaciones adjudicadas con estos valores:</p>";
            echo "<ul>";
            foreach ($variaciones as $var) {
                echo "<li><strong>" . htmlspecialchars($var) . "</strong></li>";
            }
            echo "</ul>";
        } else {
            echo "<p class='error'>✗ NO se encontraron licitaciones con estado que contenga 'adjudica'</p>";
        }

    } else {
        echo "<p class='warning'>⚠ La columna 'estado' está vacía en todas las licitaciones</p>";
    }

} catch (PDOException $e) {
    echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// ═══════════════════════════════════════════════════════════════════════
// 3. VERIFICAR LICITACIONES CON fecha_adjudicacion
// ═══════════════════════════════════════════════════════════════════════
echo "<div class='section'>";
echo "<h2>3. Licitaciones con fecha de adjudicación</h2>";

try {
    // Verificar si la columna existe
    $stmt = $pdo->query("SHOW COLUMNS FROM licitaciones LIKE 'fecha_adjudicacion'");

    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("
            SELECT COUNT(*) as total
            FROM licitaciones
            WHERE fecha_adjudicacion IS NOT NULL
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['total'] > 0) {
            echo "<p class='ok'>✓ Hay {$result['total']} licitaciones con fecha_adjudicacion</p>";

            // Mostrar algunas
            $stmt = $pdo->query("
                SELECT id, numero_procedimiento, titulo, fecha_adjudicacion, estado
                FROM licitaciones
                WHERE fecha_adjudicacion IS NOT NULL
                ORDER BY fecha_adjudicacion DESC
                LIMIT 5
            ");
            $lics = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo "<table>";
            echo "<tr><th>ID</th><th>Número</th><th>Título</th><th>Fecha Adjud.</th><th>Estado</th></tr>";
            foreach ($lics as $lic) {
                echo "<tr>";
                echo "<td>" . $lic['id'] . "</td>";
                echo "<td>" . htmlspecialchars($lic['numero_procedimiento'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars(substr($lic['titulo'], 0, 50)) . "...</td>";
                echo "<td>" . htmlspecialchars($lic['fecha_adjudicacion']) . "</td>";
                echo "<td>" . htmlspecialchars($lic['estado'] ?? 'NULL') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='warning'>⚠ La columna 'fecha_adjudicacion' existe pero está vacía</p>";
        }
    } else {
        echo "<p class='warning'>⚠ La columna 'fecha_adjudicacion' NO existe</p>";
    }

} catch (PDOException $e) {
    echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// ═══════════════════════════════════════════════════════════════════════
// 4. PRUEBA DE CONSULTA CON FILTRO DE ADJUDICADAS
// ═══════════════════════════════════════════════════════════════════════
echo "<div class='section'>";
echo "<h2>4. Prueba de consulta de licitaciones adjudicadas</h2>";

try {
    // Intentar diferentes variaciones
    $variaciones = [
        "estado = 'adjudicada'" => "WHERE estado = 'adjudicada'",
        "estado LIKE '%adjudica%'" => "WHERE estado LIKE '%adjudica%'",
        "LOWER(estado) = 'adjudicada'" => "WHERE LOWER(estado) = 'adjudicada'",
        "fecha_adjudicacion IS NOT NULL" => "WHERE fecha_adjudicacion IS NOT NULL"
    ];

    foreach ($variaciones as $nombre => $condicion) {
        try {
            $sql = "SELECT COUNT(*) as total FROM licitaciones $condicion";
            $stmt = $pdo->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['total'] > 0) {
                echo "<p class='ok'>✓ <strong>$nombre</strong>: {$result['total']} resultados</p>";
            } else {
                echo "<p class='warning'>⚠ <strong>$nombre</strong>: 0 resultados</p>";
            }
        } catch (PDOException $e) {
            echo "<p class='error'>✗ <strong>$nombre</strong>: Error - " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }

} catch (Exception $e) {
    echo "<p class='error'>✗ Error general: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// ═══════════════════════════════════════════════════════════════════════
// 5. TABLA lineas_adjudicadas
// ═══════════════════════════════════════════════════════════════════════
echo "<div class='section'>";
echo "<h2>5. Verificar tabla 'lineas_adjudicadas'</h2>";

try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'lineas_adjudicadas'");

    if ($stmt->rowCount() > 0) {
        echo "<p class='ok'>✓ Tabla 'lineas_adjudicadas' existe</p>";

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM lineas_adjudicadas");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        echo "<p>Total de líneas adjudicadas: <strong>" . number_format($result['total']) . "</strong></p>";

        // Verificar si tiene numero_sicop o numero_procedimiento
        $stmt = $pdo->query("DESCRIBE lineas_adjudicadas");
        $columnas_adj = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo "<p>Columnas importantes:</p><ul>";
        foreach (['numero_sicop', 'numero_procedimiento', 'cedula_proveedor', 'nombre_proveedor'] as $col) {
            if (in_array($col, $columnas_adj)) {
                echo "<li class='ok'>✓ $col</li>";
            } else {
                echo "<li class='warning'>⚠ $col (no existe)</li>";
            }
        }
        echo "</ul>";

        // Obtener números SICOP únicos
        $posibles_cols = ['numero_sicop', 'numero_procedimiento'];
        $col_encontrada = null;

        foreach ($posibles_cols as $col) {
            if (in_array($col, $columnas_adj)) {
                $col_encontrada = $col;
                break;
            }
        }

        if ($col_encontrada) {
            $stmt = $pdo->query("
                SELECT COUNT(DISTINCT $col_encontrada) as total_sicop
                FROM lineas_adjudicadas
                WHERE $col_encontrada IS NOT NULL
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            echo "<p class='ok'>✓ Hay {$result['total_sicop']} procedimientos únicos adjudicados</p>";
        }

    } else {
        echo "<p class='error'>✗ Tabla 'lineas_adjudicadas' NO existe</p>";
        echo "<p>Esta tabla contiene las adjudicaciones. Si no existe, las licitaciones adjudicadas deben estar marcadas en la tabla 'licitaciones'.</p>";
    }

} catch (PDOException $e) {
    echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// ═══════════════════════════════════════════════════════════════════════
// 6. RECOMENDACIONES
// ═══════════════════════════════════════════════════════════════════════
echo "<div class='section'>";
echo "<h2>6. 🔧 Recomendaciones</h2>";

echo "<div class='code'>";
echo "<h3>Basándome en el diagnóstico anterior, aquí están las soluciones:</h3>";

// Determinar cuál es el problema y dar solución
$stmt = $pdo->query("
    SELECT COUNT(*) as total
    FROM licitaciones
    WHERE estado LIKE '%adjudica%'
");
$adj_estado = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SHOW COLUMNS FROM licitaciones LIKE 'fecha_adjudicacion'");
$tiene_fecha_adj = ($stmt->rowCount() > 0);

$stmt = $pdo->query("SHOW TABLES LIKE 'lineas_adjudicadas'");
$tiene_tabla_adj = ($stmt->rowCount() > 0);

echo "<ol>";

if ($adj_estado > 0) {
    echo "<li class='ok'><strong>SOLUCIÓN 1: Usar la columna 'estado'</strong><br>";
    echo "Tienes $adj_estado licitaciones con estado que contiene 'adjudica'.<br>";
    echo "Modifica la función calcularEstadoReal() para detectar esto.</li>";
} elseif ($tiene_tabla_adj) {
    echo "<li class='ok'><strong>SOLUCIÓN 2: Usar tabla 'lineas_adjudicadas'</strong><br>";
    echo "Tienes una tabla separada de adjudicaciones.<br>";
    echo "Debes hacer un JOIN con esta tabla para detectar licitaciones adjudicadas.</li>";
} elseif ($tiene_fecha_adj) {
    echo "<li class='ok'><strong>SOLUCIÓN 3: Usar columna 'fecha_adjudicacion'</strong><br>";
    echo "Tienes la columna fecha_adjudicacion.<br>";
    echo "La función ya la usa, solo necesitas datos en esa columna.</li>";
} else {
    echo "<li class='warning'><strong>SOLUCIÓN 4: Agregar columna o datos</strong><br>";
    echo "No se detectaron licitaciones adjudicadas.<br>";
    echo "Opciones:<br>";
    echo "- Agregar columna 'fecha_adjudicacion' o 'adjudicada'<br>";
    echo "- Actualizar la columna 'estado' con valor 'adjudicada'<br>";
    echo "- Importar datos de adjudicaciones</li>";
}

echo "</ol>";
echo "</div>";

echo "</div>";

echo "<div style='margin-top: 40px; padding: 20px; background: #eff6ff; border-left: 4px solid #2563eb; border-radius: 8px;'>";
echo "<h3>📊 Próximo Paso</h3>";
echo "<p>Según los resultados de este diagnóstico, te crearé una versión corregida del dashboard.</p>";
echo "<p><strong>Copia toda la información de esta página y envíamela para crear la solución.</strong></p>";
echo "</div>";
?>
