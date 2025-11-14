<?php
/**
 * PRUEBA DIRECTA DE PARTIDAS
 * Muestra las partidas de una licitación específica
 */

require_once __DIR__ . '/../config/db.php';

// ID de licitación a probar (usa el del diagnóstico)
$licitacion_id = isset($_GET['id']) ? (int)$_GET['id'] : 8078;

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
echo "<title>Prueba de Partidas</title>";
echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
.container { max-width: 1200px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
h1 { color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
h2 { color: #667eea; margin-top: 30px; }
table { width: 100%; border-collapse: collapse; margin: 20px 0; }
th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
th { background: #667eea; color: white; }
tr:nth-child(even) { background: #f9f9f9; }
.ok { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.info { background: #e7f3ff; padding: 15px; border-left: 4px solid #2196F3; margin: 20px 0; }
pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
.btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
.btn:hover { background: #5568d3; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>🧪 Prueba Directa de Partidas</h1>";

// Obtener información de la licitación
try {
    $stmt = $conn->prepare("SELECT * FROM licitaciones WHERE id = ?");
    $stmt->execute([$licitacion_id]);
    $licitacion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$licitacion) {
        echo "<div class='error'>❌ No se encontró la licitación con ID $licitacion_id</div>";

        // Mostrar licitaciones disponibles
        $stmt = $conn->query("SELECT l.id, l.numero_sicop, l.titulo, COUNT(p.id) as total_partidas
                              FROM licitaciones l
                              LEFT JOIN partidas_licitacion p ON l.id = p.licitacion_id
                              GROUP BY l.id
                              HAVING total_partidas > 0
                              ORDER BY total_partidas DESC
                              LIMIT 10");
        $lics = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<h2>Licitaciones disponibles con partidas:</h2>";
        echo "<table><tr><th>ID</th><th>SICOP</th><th>Título</th><th>Partidas</th><th>Acción</th></tr>";
        foreach ($lics as $lic) {
            echo "<tr>";
            echo "<td>{$lic['id']}</td>";
            echo "<td>{$lic['numero_sicop']}</td>";
            echo "<td>" . substr($lic['titulo'], 0, 60) . "...</td>";
            echo "<td class='ok'>{$lic['total_partidas']}</td>";
            echo "<td><a href='?id={$lic['id']}' class='btn'>Probar</a></td>";
            echo "</tr>";
        }
        echo "</table>";

        exit;
    }

    echo "<div class='info'>";
    echo "<strong>📋 Licitación:</strong> {$licitacion['titulo']}<br>";
    echo "<strong>🔢 ID:</strong> $licitacion_id<br>";
    echo "<strong>📝 SICOP:</strong> {$licitacion['numero_sicop']}";
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
    exit;
}

// Obtener partidas con la query EXACTA del archivo corregido
echo "<h2>1️⃣ Query Original (del archivo corregido)</h2>";

try {
    $query = "SELECT
                partida,
                linea,
                codigo_identificacion,
                cantidad,
                precio_unitario,
                monto_estimado,
                moneda,
                tipo_cambio_usd,
                nombre,
                descripcion,
                unidad
              FROM partidas_licitacion
              WHERE licitacion_id = :licitacion_id
              ORDER BY partida ASC, linea ASC";

    $stmt_partidas = $conn->prepare($query);
    $stmt_partidas->bindParam(':licitacion_id', $licitacion_id);
    $stmt_partidas->execute();
    $partidas = $stmt_partidas->fetchAll(PDO::FETCH_ASSOC);

    echo "<div class='ok'>✅ Query ejecutada correctamente</div>";
    echo "<div class='ok'>✅ Partidas encontradas: " . count($partidas) . "</div>";

    if (count($partidas) > 0) {
        echo "<h3>Partidas obtenidas:</h3>";
        echo "<table>";
        echo "<tr><th>Partida</th><th>Línea</th><th>Código</th><th>Nombre</th><th>Cantidad</th><th>Unidad</th><th>Precio Unit.</th><th>Monto Est.</th><th>Moneda</th></tr>";

        foreach ($partidas as $p) {
            $precio = floatval($p['precio_unitario'] ?? 0);
            $monto = floatval($p['monto_estimado'] ?? 0);
            $cantidad = floatval($p['cantidad'] ?? 0);

            echo "<tr>";
            echo "<td>{$p['partida']}</td>";
            echo "<td>{$p['linea']}</td>";
            echo "<td>" . ($p['codigo_identificacion'] ?: '<em>-</em>') . "</td>";
            echo "<td>" . ($p['nombre'] ?: '<em>Sin nombre</em>') . "</td>";
            echo "<td>" . ($cantidad > 0 ? number_format($cantidad, 2) : '-') . "</td>";
            echo "<td>" . ($p['unidad'] ?: '-') . "</td>";
            echo "<td>" . ($precio > 0 ? '₡' . number_format($precio, 2) : '-') . "</td>";
            echo "<td>" . ($monto > 0 ? '₡' . number_format($monto, 2) : '-') . "</td>";
            echo "<td>{$p['moneda']}</td>";
            echo "</tr>";
        }

        echo "</table>";

        // Mostrar datos crudos
        echo "<h3>Datos crudos de la primera partida:</h3>";
        echo "<pre>" . print_r($partidas[0], true) . "</pre>";

    } else {
        echo "<div class='error'>❌ La query NO retornó partidas</div>";
        echo "<div class='info'>Esta licitación no tiene partidas asociadas. Prueba con otra licitación usando los enlaces arriba.</div>";
    }

} catch (Exception $e) {
    echo "<div class='error'>❌ Error ejecutando query: " . $e->getMessage() . "</div>";
}

// Mostrar código PHP de ejemplo
echo "<h2>2️⃣ Código PHP para licitacion_detalle.php</h2>";
echo "<div class='info'>";
echo "<p>Este es el código que deberías tener en tu licitacion_detalle.php:</p>";
echo "<pre>";
echo htmlspecialchars('<?php
// En la sección de partidas (alrededor de la línea 990-1100)
foreach ($partidas as $partida) {
    $cantidad = floatval($partida[\'cantidad\'] ?? 0);
    $precio_unitario = floatval($partida[\'precio_unitario\'] ?? 0);
    $monto_estimado = floatval($partida[\'monto_estimado\'] ?? 0);
    $moneda = $partida[\'moneda\'] ?? \'CRC\';

    // Mostrar en la tabla...
    echo "<tr>";
    echo "<td>" . $partida[\'partida\'] . "</td>";
    echo "<td>" . $partida[\'linea\'] . "</td>";
    echo "<td>" . $partida[\'codigo_identificacion\'] . "</td>";
    // ... etc
}
?>');
echo "</pre>";
echo "</div>";

// Enlaces de navegación
echo "<h2>3️⃣ Navegación</h2>";
echo "<a href='../user/licitacion_detalle.php?id=$licitacion_id' class='btn' target='_blank'>🔗 Ver en licitacion_detalle.php</a>";
echo "<a href='diagnosticar_partidas_detallado.php' class='btn'>🔍 Diagnóstico Completo</a>";

// Licitaciones con partidas
$stmt = $conn->query("SELECT l.id, l.numero_sicop, l.titulo, COUNT(p.id) as total
                      FROM licitaciones l
                      INNER JOIN partidas_licitacion p ON l.id = p.licitacion_id
                      GROUP BY l.id
                      ORDER BY total DESC
                      LIMIT 5");
$lics = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Otras licitaciones con partidas:</h3>";
echo "<table><tr><th>ID</th><th>SICOP</th><th>Título</th><th>Partidas</th><th>Acción</th></tr>";
foreach ($lics as $lic) {
    echo "<tr>";
    echo "<td>{$lic['id']}</td>";
    echo "<td>{$lic['numero_sicop']}</td>";
    echo "<td>" . substr($lic['titulo'], 0, 50) . "...</td>";
    echo "<td class='ok'>{$lic['total']}</td>";
    echo "<td><a href='?id={$lic['id']}' class='btn'>Probar</a> <a href='../user/licitacion_detalle.php?id={$lic['id']}' class='btn' target='_blank'>Ver</a></td>";
    echo "</tr>";
}
echo "</table>";

echo "</div></body></html>";
?>
