<?php
/**
 * DIAGNÓSTICO Y CORRECCIÓN DE PARTIDAS HUÉRFANAS
 * Identifica partidas que no están enlazadas correctamente
 */

require_once __DIR__ . '/../config/db.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
echo "<title>Corrección de Partidas</title>";
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
.btn { display: inline-block; padding: 10px 20px; background: #0af; color: #000; text-decoration: none; border-radius: 5px; margin: 10px 5px; font-weight: bold; }
.btn:hover { background: #0cf; }
.btn-danger { background: #f00; color: #fff; }
</style></head><body>";

echo "<h1>🔧 DIAGNÓSTICO Y CORRECCIÓN DE PARTIDAS</h1>";

// ==================================================================
// 1. VERIFICAR LICITACIONES SIN PARTIDAS
// ==================================================================
echo "<h2>1️⃣ LICITACIONES SIN PARTIDAS</h2>";

try {
    $sql = "SELECT l.id, l.numero_sicop, l.titulo
            FROM licitaciones l
            LEFT JOIN partidas_licitacion p ON l.id = p.licitacion_id
            WHERE p.id IS NULL
            ORDER BY l.id DESC
            LIMIT 20";

    $stmt = $conn->query($sql);
    $sin_partidas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<div class='warning'>⚠️ Licitaciones sin partidas: " . count($sin_partidas) . "</div>";

    if (count($sin_partidas) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>SICOP</th><th>Título</th><th>Acción</th></tr>";
        foreach (array_slice($sin_partidas, 0, 10) as $lic) {
            $titulo = strlen($lic['titulo']) > 60 ? substr($lic['titulo'], 0, 60) . '...' : $lic['titulo'];
            echo "<tr>";
            echo "<td>{$lic['id']}</td>";
            echo "<td>{$lic['numero_sicop']}</td>";
            echo "<td>$titulo</td>";
            echo "<td><a href='#buscar-{$lic['numero_sicop']}' style='color:#0af;'>Buscar partidas</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    }

} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

// ==================================================================
// 2. BUSCAR PARTIDAS POR SICOP (no por licitacion_id)
// ==================================================================
echo "<h2>2️⃣ VERIFICAR PARTIDAS EN TABLA DETALLE_LINEA_CARTEL</h2>";

try {
    // Obtener una licitación sin partidas
    $sql = "SELECT l.id, l.numero_sicop, l.titulo
            FROM licitaciones l
            LEFT JOIN partidas_licitacion p ON l.id = p.licitacion_id
            WHERE p.id IS NULL
            LIMIT 1";

    $stmt = $conn->query($sql);
    $lic = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($lic) {
        echo "<div id='buscar-{$lic['numero_sicop']}'>";
        echo "<div class='info'>🔍 Probando con: {$lic['numero_sicop']} - {$lic['titulo']}</div>";

        // Verificar si existen tablas temporales o de respaldo
        $tablas_posibles = [
            'detalle_linea_cartel',
            'lineas_cartel',
            'partidas_temp',
            'partidas_backup'
        ];

        foreach ($tablas_posibles as $tabla) {
            try {
                $check = $conn->query("SHOW TABLES LIKE '$tabla'");
                if ($check->rowCount() > 0) {
                    echo "<div class='ok'>✅ Tabla encontrada: $tabla</div>";

                    // Buscar partidas en esta tabla
                    $stmt = $conn->prepare("SELECT * FROM $tabla WHERE nro_sicop = ? OR numero_sicop = ? LIMIT 5");
                    $stmt->execute([$lic['numero_sicop'], $lic['numero_sicop']]);
                    $partidas = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (count($partidas) > 0) {
                        echo "<div class='ok'>✅ Encontradas " . count($partidas) . " partidas en $tabla</div>";
                        echo "<pre>" . print_r($partidas[0], true) . "</pre>";
                    }
                }
            } catch (Exception $e) {
                // Tabla no existe, continuar
            }
        }
        echo "</div>";
    }

} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

// ==================================================================
// 3. VERIFICAR RELACIÓN licitacion_id
// ==================================================================
echo "<h2>3️⃣ VERIFICAR INTEGRIDAD DE licitacion_id</h2>";

try {
    // Partidas con licitacion_id que NO existe en licitaciones
    $sql = "SELECT COUNT(*) as total
            FROM partidas_licitacion p
            LEFT JOIN licitaciones l ON p.licitacion_id = l.id
            WHERE l.id IS NULL";

    $stmt = $conn->query($sql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['total'] > 0) {
        echo "<div class='error'>❌ Partidas huérfanas (licitacion_id no existe): {$result['total']}</div>";

        // Mostrar ejemplos
        $sql = "SELECT p.*, p.licitacion_id as lic_id_invalido
                FROM partidas_licitacion p
                LEFT JOIN licitaciones l ON p.licitacion_id = l.id
                WHERE l.id IS NULL
                LIMIT 10";

        $stmt = $conn->query($sql);
        $huerfanas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<table>";
        echo "<tr><th>Partida ID</th><th>licitacion_id (inválido)</th><th>Partida</th><th>Línea</th><th>Código</th></tr>";
        foreach ($huerfanas as $p) {
            echo "<tr>";
            echo "<td>{$p['id']}</td>";
            echo "<td class='error'>{$p['lic_id_invalido']}</td>";
            echo "<td>{$p['partida']}</td>";
            echo "<td>{$p['linea']}</td>";
            echo "<td>{$p['codigo_identificacion']}</td>";
            echo "</tr>";
        }
        echo "</table>";

    } else {
        echo "<div class='ok'>✅ Todas las partidas tienen licitacion_id válido</div>";
    }

} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

// ==================================================================
// 4. ANÁLISIS DETALLADO: ¿POR QUÉ FALTAN PARTIDAS?
// ==================================================================
echo "<h2>4️⃣ ANÁLISIS: ¿Por qué 72 licitaciones no tienen partidas?</h2>";

try {
    $sql = "SELECT COUNT(*) as total FROM licitaciones";
    $stmt = $conn->query($sql);
    $total_lic = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $sql = "SELECT COUNT(DISTINCT licitacion_id) as total FROM partidas_licitacion";
    $stmt = $conn->query($sql);
    $lic_con_partidas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $sin_partidas = $total_lic - $lic_con_partidas;

    echo "<div class='info'>📊 Total licitaciones: $total_lic</div>";
    echo "<div class='info'>📊 Licitaciones con partidas: $lic_con_partidas</div>";
    echo "<div class='warning'>⚠️ Licitaciones SIN partidas: $sin_partidas</div>";

    // Obtener las licitaciones sin partidas
    $sql = "SELECT l.id, l.numero_sicop, l.titulo, l.fecha_publicacion
            FROM licitaciones l
            LEFT JOIN partidas_licitacion p ON l.id = p.licitacion_id
            WHERE p.id IS NULL
            ORDER BY l.fecha_publicacion DESC
            LIMIT 10";

    $stmt = $conn->query($sql);
    $sin_partidas_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>Primeras 10 licitaciones sin partidas:</h3>";
    echo "<table>";
    echo "<tr><th>ID</th><th>SICOP</th><th>Título</th><th>Fecha Pub.</th></tr>";
    foreach ($sin_partidas_list as $lic) {
        echo "<tr>";
        echo "<td>{$lic['id']}</td>";
        echo "<td>{$lic['numero_sicop']}</td>";
        echo "<td>" . substr($lic['titulo'], 0, 50) . "...</td>";
        echo "<td>{$lic['fecha_publicacion']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<div class='warning'>";
    echo "<p><strong>Posibles causas:</strong></p>";
    echo "<ul>";
    echo "<li>Las partidas se importaron con un importador diferente (v13 vs v14.2)</li>";
    echo "<li>El archivo CSV de partidas no contenía esas licitaciones</li>";
    echo "<li>Las licitaciones se agregaron DESPUÉS de importar partidas</li>";
    echo "<li>El NUMERO_SICOP en partidas no coincide con el de licitaciones</li>";
    echo "</ul>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

// ==================================================================
// 5. VERIFICAR IMPORTADOR USADO
// ==================================================================
echo "<h2>5️⃣ VERIFICAR CAMPOS IMPORTADOS</h2>";

try {
    // Ver qué campos tienen datos
    $sql = "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN codigo_identificacion IS NOT NULL THEN 1 ELSE 0 END) as con_codigo_identificacion,
                SUM(CASE WHEN codigo IS NOT NULL THEN 1 ELSE 0 END) as con_codigo,
                SUM(CASE WHEN precio_unitario IS NOT NULL AND precio_unitario != '' THEN 1 ELSE 0 END) as con_precio_varchar,
                SUM(CASE WHEN precio_unitario_numerico IS NOT NULL THEN 1 ELSE 0 END) as con_precio_numerico,
                SUM(CASE WHEN tipo_cambio_usd IS NOT NULL THEN 1 ELSE 0 END) as con_tipo_cambio
            FROM partidas_licitacion";

    $stmt = $conn->query($sql);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "<table>";
    echo "<tr><th>Campo</th><th>Partidas con datos</th><th>Porcentaje</th></tr>";

    foreach ($stats as $campo => $valor) {
        if ($campo == 'total') continue;
        $pct = ($valor / $stats['total']) * 100;
        $clase = $pct > 90 ? 'ok' : ($pct > 50 ? 'warning' : 'error');
        echo "<tr>";
        echo "<td>$campo</td>";
        echo "<td class='$clase'>$valor / {$stats['total']}</td>";
        echo "<td class='$clase'>" . number_format($pct, 1) . "%</td>";
        echo "</tr>";
    }
    echo "</table>";

    if ($stats['con_tipo_cambio'] > 0) {
        echo "<div class='ok'>✅ Partidas importadas con importador v14.2 (tiene tipo_cambio_usd)</div>";
    }
    if ($stats['con_precio_varchar'] > 0 && $stats['con_precio_numerico'] == 0) {
        echo "<div class='warning'>⚠️ Partidas importadas con importador v13 (precio como VARCHAR)</div>";
    }

} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

// ==================================================================
// 6. BOTÓN DE CORRECCIÓN
// ==================================================================
echo "<h2>6️⃣ SOLUCIÓN</h2>";

echo "<div class='info'>";
echo "<p><strong>Si las licitaciones SIN partidas deberían tenerlas:</strong></p>";
echo "<ol>";
echo "<li>Importa de nuevo el archivo CSV con <strong>todas las licitaciones</strong></li>";
echo "<li>Usa el importador v14.2 para asegurar compatibilidad</li>";
echo "<li>Verifica que el CSV tenga TODAS las partidas (no solo algunas)</li>";
echo "</ol>";
echo "</div>";

echo "<div style='margin: 30px 0;'>";
echo "<a href='../admin/importador_sicop_v14_2.php' class='btn'>📤 Importar con v14.2</a>";
echo "<a href='probar_partidas.php' class='btn'>🧪 Probar Partidas</a>";
echo "<a href='diagnosticar_partidas_detallado.php' class='btn'>🔍 Diagnóstico Completo</a>";
echo "</div>";

echo "</body></html>";
?>
