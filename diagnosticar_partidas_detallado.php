<?php
/**
 * DIAGNÓSTICO DETALLADO DE PARTIDAS
 * Identifica por qué las partidas no se muestran en licitacion_detalle.php
 */

require_once __DIR__ . '/../config/db.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
echo "<title>Diagnóstico Partidas</title>";
echo "<style>
body { font-family: monospace; background: #1e1e1e; color: #fff; padding: 20px; }
.ok { color: #0f0; }
.error { color: #f00; }
.warning { color: #fa0; }
.info { color: #0af; }
pre { background: #2e2e2e; padding: 15px; border-radius: 5px; overflow-x: auto; }
table { border-collapse: collapse; width: 100%; margin: 20px 0; }
th, td { border: 1px solid #555; padding: 8px; text-align: left; }
th { background: #333; }
h2 { color: #0af; margin-top: 30px; }
</style></head><body>";

echo "<h1>🔍 DIAGNÓSTICO DETALLADO DE PARTIDAS</h1>";

// ==================================================================
// 1. VERIFICAR ESTRUCTURA DE LA TABLA
// ==================================================================
echo "<h2>1️⃣ ESTRUCTURA DE LA TABLA partidas_licitacion</h2>";

try {
    $stmt = $conn->query("SHOW COLUMNS FROM partidas_licitacion");
    $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Default</th></tr>";
    foreach ($columnas as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Verificar campos críticos
    $campos_necesarios = ['licitacion_id', 'partida', 'linea', 'codigo_identificacion', 'cantidad', 'precio_unitario', 'nombre', 'descripcion', 'unidad', 'moneda', 'monto_estimado'];
    $campos_existentes = array_column($columnas, 'Field');

    echo "<h3>Verificación de campos necesarios:</h3>";
    foreach ($campos_necesarios as $campo) {
        if (in_array($campo, $campos_existentes)) {
            echo "<div class='ok'>✅ $campo - EXISTE</div>";
        } else {
            echo "<div class='error'>❌ $campo - NO EXISTE</div>";
        }
    }

} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

// ==================================================================
// 2. CONTAR PARTIDAS TOTALES
// ==================================================================
echo "<h2>2️⃣ TOTAL DE PARTIDAS EN LA BASE DE DATOS</h2>";

try {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM partidas_licitacion");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['total'] > 0) {
        echo "<div class='ok'>✅ Total de partidas: {$result['total']}</div>";
    } else {
        echo "<div class='error'>❌ NO HAY PARTIDAS EN LA BASE DE DATOS</div>";
    }

} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

// ==================================================================
// 3. PARTIDAS POR LICITACIÓN
// ==================================================================
echo "<h2>3️⃣ PARTIDAS POR LICITACIÓN (Top 10)</h2>";

try {
    $sql = "SELECT
                l.id,
                l.numero_sicop,
                l.titulo,
                COUNT(p.id) as total_partidas,
                SUM(CASE WHEN p.codigo_identificacion IS NOT NULL THEN 1 ELSE 0 END) as con_codigo,
                SUM(CASE WHEN p.cantidad IS NOT NULL THEN 1 ELSE 0 END) as con_cantidad,
                SUM(CASE WHEN p.precio_unitario IS NOT NULL THEN 1 ELSE 0 END) as con_precio,
                SUM(CASE WHEN p.nombre IS NOT NULL AND p.nombre != '' THEN 1 ELSE 0 END) as con_nombre
            FROM licitaciones l
            LEFT JOIN partidas_licitacion p ON l.id = p.licitacion_id
            GROUP BY l.id
            HAVING total_partidas > 0
            ORDER BY total_partidas DESC
            LIMIT 10";

    $stmt = $conn->query($sql);
    $licitaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($licitaciones) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>SICOP</th><th>Título</th><th>Partidas</th><th>Con Código</th><th>Con Cantidad</th><th>Con Precio</th><th>Con Nombre</th></tr>";
        foreach ($licitaciones as $lic) {
            $titulo = strlen($lic['titulo']) > 50 ? substr($lic['titulo'], 0, 50) . '...' : $lic['titulo'];
            echo "<tr>";
            echo "<td><a href='#lic-{$lic['id']}' style='color:#0af;'>{$lic['id']}</a></td>";
            echo "<td>{$lic['numero_sicop']}</td>";
            echo "<td>{$titulo}</td>";
            echo "<td class='ok'>{$lic['total_partidas']}</td>";
            echo "<td>{$lic['con_codigo']}</td>";
            echo "<td>{$lic['con_cantidad']}</td>";
            echo "<td>{$lic['con_precio']}</td>";
            echo "<td>{$lic['con_nombre']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>❌ NO HAY LICITACIONES CON PARTIDAS</div>";
    }

} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

// ==================================================================
// 4. EJEMPLO DE PARTIDAS (Primera licitación con partidas)
// ==================================================================
echo "<h2>4️⃣ EJEMPLO DE PARTIDAS (Primera licitación)</h2>";

try {
    $sql = "SELECT l.id, l.numero_sicop, l.titulo
            FROM licitaciones l
            INNER JOIN partidas_licitacion p ON l.id = p.licitacion_id
            GROUP BY l.id
            LIMIT 1";

    $stmt = $conn->query($sql);
    $lic = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($lic) {
        echo "<div id='lic-{$lic['id']}'>";
        echo "<div class='info'>📋 Licitación ID: {$lic['id']}</div>";
        echo "<div class='info'>📋 SICOP: {$lic['numero_sicop']}</div>";
        echo "<div class='info'>📋 Título: {$lic['titulo']}</div>";

        // Obtener partidas de esta licitación
        $sql = "SELECT
                    partida,
                    linea,
                    codigo_identificacion,
                    nombre,
                    descripcion,
                    cantidad,
                    precio_unitario,
                    moneda,
                    monto_estimado,
                    unidad
                FROM partidas_licitacion
                WHERE licitacion_id = ?
                ORDER BY partida ASC, linea ASC
                LIMIT 10";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$lic['id']]);
        $partidas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<h3>Partidas encontradas: " . count($partidas) . "</h3>";

        if (count($partidas) > 0) {
            echo "<table>";
            echo "<tr><th>Partida</th><th>Línea</th><th>Código</th><th>Nombre</th><th>Cantidad</th><th>Unidad</th><th>Precio</th><th>Monto</th><th>Moneda</th></tr>";
            foreach ($partidas as $p) {
                echo "<tr>";
                echo "<td>{$p['partida']}</td>";
                echo "<td>{$p['linea']}</td>";
                echo "<td>" . ($p['codigo_identificacion'] ?: '<span class="error">NULL</span>') . "</td>";
                echo "<td>" . ($p['nombre'] ?: '<span class="warning">NULL</span>') . "</td>";
                echo "<td>" . ($p['cantidad'] ?: '<span class="error">NULL</span>') . "</td>";
                echo "<td>" . ($p['unidad'] ?: '<span class="warning">-</span>') . "</td>";
                echo "<td>" . ($p['precio_unitario'] ?: '<span class="error">NULL</span>') . "</td>";
                echo "<td>" . ($p['monto_estimado'] ?: '<span class="error">NULL</span>') . "</td>";
                echo "<td>" . ($p['moneda'] ?: 'CRC') . "</td>";
                echo "</tr>";
            }
            echo "</table>";

            // Mostrar query que usaría licitacion_detalle.php
            echo "<h3>Query que usa licitacion_detalle.php:</h3>";
            echo "<pre>SELECT
    partida, linea, codigo_identificacion,
    cantidad, precio_unitario, monto_estimado,
    moneda, tipo_cambio_usd, nombre, descripcion, unidad
FROM partidas_licitacion
WHERE licitacion_id = {$lic['id']}
ORDER BY partida ASC, linea ASC</pre>";

            echo "<div class='ok'>✅ Esta query debería funcionar en licitacion_detalle.php</div>";
            echo "<div class='info'>🔗 URL para probar: <a href='/user/licitacion_detalle.php?id={$lic['id']}' target='_blank' style='color:#0af;'>Ver Licitación {$lic['id']}</a></div>";

        } else {
            echo "<div class='error'>❌ NO SE ENCONTRARON PARTIDAS (esto es extraño)</div>";
        }

        echo "</div>";

    } else {
        echo "<div class='error'>❌ NO HAY LICITACIONES CON PARTIDAS</div>";
    }

} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

// ==================================================================
// 5. VERIFICAR QUERY DE licitacion_detalle.php
// ==================================================================
echo "<h2>5️⃣ SIMULAR QUERY DE licitacion_detalle.php</h2>";

try {
    // Probar con la primera licitación que tenga partidas
    $sql = "SELECT l.id FROM licitaciones l
            INNER JOIN partidas_licitacion p ON l.id = p.licitacion_id
            GROUP BY l.id
            LIMIT 1";

    $stmt = $conn->query($sql);
    $lic = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($lic) {
        $licitacion_id = $lic['id'];

        echo "<div class='info'>🧪 Probando con licitacion_id = $licitacion_id</div>";

        // Query EXACTA del archivo licitacion_detalle_CORREGIDO.php
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
            echo "<h3>Primeras 5 partidas:</h3>";
            echo "<pre>" . print_r(array_slice($partidas, 0, 5), true) . "</pre>";
        } else {
            echo "<div class='error'>❌ La query NO retornó partidas (hay un problema)</div>";
        }

    } else {
        echo "<div class='error'>❌ No hay licitaciones con partidas para probar</div>";
    }

} catch (Exception $e) {
    echo "<div class='error'>❌ Error ejecutando query: " . $e->getMessage() . "</div>";
}

// ==================================================================
// 6. VERIFICAR UNIQUE KEY
// ==================================================================
echo "<h2>6️⃣ VERIFICAR ÍNDICES Y CLAVES ÚNICAS</h2>";

try {
    $stmt = $conn->query("SHOW INDEXES FROM partidas_licitacion");
    $indices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table>";
    echo "<tr><th>Clave</th><th>Columna</th><th>Tipo</th><th>Único</th></tr>";
    foreach ($indices as $idx) {
        echo "<tr>";
        echo "<td>{$idx['Key_name']}</td>";
        echo "<td>{$idx['Column_name']}</td>";
        echo "<td>{$idx['Index_type']}</td>";
        echo "<td>" . ($idx['Non_unique'] == 0 ? 'SÍ' : 'NO') . "</td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

// ==================================================================
// 7. RESUMEN Y RECOMENDACIONES
// ==================================================================
echo "<h2>7️⃣ RESUMEN Y RECOMENDACIONES</h2>";

try {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM partidas_licitacion");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $conn->query("SELECT COUNT(DISTINCT licitacion_id) as total FROM partidas_licitacion");
    $licitaciones_con_partidas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $conn->query("SELECT COUNT(*) as total FROM licitaciones");
    $total_licitaciones = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    echo "<div class='info'>📊 Total de licitaciones: $total_licitaciones</div>";
    echo "<div class='info'>📊 Licitaciones con partidas: $licitaciones_con_partidas</div>";
    echo "<div class='info'>📊 Total de partidas: $total</div>";

    if ($total > 0 && $licitaciones_con_partidas > 0) {
        echo "<div class='ok'>✅ LAS PARTIDAS EXISTEN EN LA BASE DE DATOS</div>";
        echo "<div class='warning'>⚠️ Si no se ven en licitacion_detalle.php, el problema es en el archivo PHP o estás viendo una licitación sin partidas</div>";
        echo "<div class='info'>💡 Verifica que estés accediendo a licitacion_detalle.php?id=X donde X es una licitación que SÍ tenga partidas</div>";
        echo "<div class='info'>💡 Usa las URLs de prueba arriba para verificar</div>";
    } else {
        echo "<div class='error'>❌ NO HAY PARTIDAS - Necesitas importar con v14.2</div>";
    }

} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>
