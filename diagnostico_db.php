<?php
/**
 * ═══════════════════════════════════════════════════════════════════════
 * SCRIPT DE DIAGNÓSTICO - Base de Datos
 * ═══════════════════════════════════════════════════════════════════════
 * Este script verifica la estructura de las tablas y datos existentes
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación
if (!isset($_SESSION['usuario_id']) && !isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

if (!isset($pdo) && isset($conn)) {
    $pdo = $conn;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Base de Datos</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        h1 {
            color: #4ec9b0;
            border-bottom: 3px solid #4ec9b0;
            padding-bottom: 10px;
        }
        h2 {
            color: #569cd6;
            margin-top: 30px;
            border-left: 5px solid #569cd6;
            padding-left: 15px;
        }
        .section {
            background: #252526;
            border: 1px solid #3e3e42;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: #1e1e1e;
        }
        th {
            background: #0e639c;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #3e3e42;
        }
        tr:hover {
            background: #2d2d30;
        }
        .success {
            color: #4ec9b0;
            font-weight: bold;
        }
        .warning {
            color: #ce9178;
            font-weight: bold;
        }
        .error {
            color: #f48771;
            font-weight: bold;
        }
        .info {
            color: #569cd6;
        }
        .code {
            background: #1e1e1e;
            border: 1px solid #3e3e42;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            margin: 10px 0;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: bold;
        }
        .badge-success {
            background: #4ec9b0;
            color: #000;
        }
        .badge-warning {
            background: #ce9178;
            color: #000;
        }
        .badge-error {
            background: #f48771;
            color: #000;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 DIAGNÓSTICO DE BASE DE DATOS - LicitaHoy</h1>
        <p style="color: #858585; margin-bottom: 30px;">
            Ejecutado el: <?php echo date('Y-m-d H:i:s'); ?>
        </p>

        <?php
        try {
            // 1. VERIFICAR TABLAS PRINCIPALES
            echo '<div class="section">';
            echo '<h2>📋 1. TABLAS PRINCIPALES</h2>';

            $tablas_requeridas = [
                'lineas_adjudicadas',
                'licitaciones',
                'proveedoras',
                'instituciones_compradoras',
                'partidas_licitacion'
            ];

            echo '<table>';
            echo '<thead><tr><th>Tabla</th><th>Existe</th><th>Registros</th><th>Estado</th></tr></thead>';
            echo '<tbody>';

            $tablas_info = [];

            foreach ($tablas_requeridas as $tabla) {
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM $tabla");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $count = $result['total'];
                    $existe = true;
                    $estado = $count > 0 ? 'success' : 'warning';
                    $estado_texto = $count > 0 ? '✓ Con datos' : '⚠ Vacía';

                    $tablas_info[$tabla] = ['existe' => true, 'count' => $count];
                } catch (PDOException $e) {
                    $count = 0;
                    $existe = false;
                    $estado = 'error';
                    $estado_texto = '✗ No existe';

                    $tablas_info[$tabla] = ['existe' => false, 'count' => 0];
                }

                $badge_class = $existe ? ($count > 0 ? 'badge-success' : 'badge-warning') : 'badge-error';

                echo '<tr>';
                echo '<td><strong>' . $tabla . '</strong></td>';
                echo '<td>' . ($existe ? '<span class="success">SÍ</span>' : '<span class="error">NO</span>') . '</td>';
                echo '<td>' . number_format($count) . '</td>';
                echo '<td><span class="badge ' . $badge_class . '">' . $estado_texto . '</span></td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
            echo '</div>';

            // 2. ESTRUCTURA DE LINEAS_ADJUDICADAS
            if ($tablas_info['lineas_adjudicadas']['existe']) {
                echo '<div class="section">';
                echo '<h2>📊 2. ESTRUCTURA: lineas_adjudicadas</h2>';

                $stmt = $pdo->query("DESCRIBE lineas_adjudicadas");
                $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo '<table>';
                echo '<thead><tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th></tr></thead>';
                echo '<tbody>';

                foreach ($columnas as $col) {
                    echo '<tr>';
                    echo '<td><strong>' . $col['Field'] . '</strong></td>';
                    echo '<td><code>' . $col['Type'] . '</code></td>';
                    echo '<td>' . $col['Null'] . '</td>';
                    echo '<td>' . ($col['Key'] ? '<span class="info">' . $col['Key'] . '</span>' : '-') . '</td>';
                    echo '</tr>';
                }

                echo '</tbody></table>';

                // Ejemplo de datos
                echo '<h3 style="color: #ce9178; margin-top: 20px;">🔍 Muestra de Datos (primeros 5 registros)</h3>';

                $stmt = $pdo->query("SELECT * FROM lineas_adjudicadas LIMIT 5");
                $muestra = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($muestra)) {
                    echo '<div class="code">';
                    echo '<pre>' . json_encode($muestra, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
                    echo '</div>';
                } else {
                    echo '<p class="warning">⚠ No hay datos en la tabla</p>';
                }

                echo '</div>';
            }

            // 3. ANÁLISIS DE CÓDIGOS PRODUCTOS
            if ($tablas_info['lineas_adjudicadas']['existe'] && $tablas_info['lineas_adjudicadas']['count'] > 0) {
                echo '<div class="section">';
                echo '<h2>🔢 3. ANÁLISIS DE CÓDIGOS DE PRODUCTO</h2>';

                // Verificar formatos de códigos
                $stmt = $pdo->query("
                    SELECT
                        COUNT(*) as total,
                        COUNT(DISTINCT codigo_producto) as codigos_unicos,
                        MIN(LENGTH(codigo_producto)) as longitud_min,
                        MAX(LENGTH(codigo_producto)) as longitud_max,
                        AVG(LENGTH(codigo_producto)) as longitud_prom
                    FROM lineas_adjudicadas
                    WHERE codigo_producto IS NOT NULL AND codigo_producto != ''
                ");

                $analisis = $stmt->fetch(PDO::FETCH_ASSOC);

                echo '<table>';
                echo '<thead><tr><th>Métrica</th><th>Valor</th></tr></thead>';
                echo '<tbody>';
                echo '<tr><td>Total de líneas con código</td><td><strong>' . number_format($analisis['total']) . '</strong></td></tr>';
                echo '<tr><td>Códigos únicos</td><td><strong>' . number_format($analisis['codigos_unicos']) . '</strong></td></tr>';
                echo '<tr><td>Longitud mínima</td><td>' . $analisis['longitud_min'] . ' caracteres</td></tr>';
                echo '<tr><td>Longitud máxima</td><td>' . $analisis['longitud_max'] . ' caracteres</td></tr>';
                echo '<tr><td>Longitud promedio</td><td>' . round($analisis['longitud_prom'], 1) . ' caracteres</td></tr>';
                echo '</tbody></table>';

                // Ejemplos de códigos
                echo '<h3 style="color: #ce9178; margin-top: 20px;">Ejemplos de códigos reales en la BD:</h3>';

                $stmt = $pdo->query("
                    SELECT DISTINCT codigo_producto, COUNT(*) as veces
                    FROM lineas_adjudicadas
                    WHERE codigo_producto IS NOT NULL AND codigo_producto != ''
                    GROUP BY codigo_producto
                    ORDER BY veces DESC
                    LIMIT 10
                ");

                $ejemplos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo '<table>';
                echo '<thead><tr><th>Código Producto</th><th>Longitud</th><th>Primeros 8 dígitos</th><th>Apariciones</th></tr></thead>';
                echo '<tbody>';

                foreach ($ejemplos as $ej) {
                    $codigo_8 = substr($ej['codigo_producto'], 0, 8);
                    echo '<tr>';
                    echo '<td><code>' . htmlspecialchars($ej['codigo_producto']) . '</code></td>';
                    echo '<td>' . strlen($ej['codigo_producto']) . '</td>';
                    echo '<td><strong>' . $codigo_8 . '</strong></td>';
                    echo '<td>' . number_format($ej['veces']) . '</td>';
                    echo '</tr>';
                }

                echo '</tbody></table>';

                echo '</div>';
            }

            // 4. PRUEBA DE BÚSQUEDA
            if ($tablas_info['lineas_adjudicadas']['existe'] && $tablas_info['lineas_adjudicadas']['count'] > 0) {
                echo '<div class="section">';
                echo '<h2>🔎 4. PRUEBA DE BÚSQUEDA</h2>';

                // Obtener un código de ejemplo
                $stmt = $pdo->query("
                    SELECT codigo_producto
                    FROM lineas_adjudicadas
                    WHERE codigo_producto IS NOT NULL AND codigo_producto != ''
                    LIMIT 1
                ");

                $ejemplo_codigo = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($ejemplo_codigo) {
                    $codigo_completo = $ejemplo_codigo['codigo_producto'];
                    $codigo_8 = substr($codigo_completo, 0, 8);

                    echo '<p>Probando búsqueda con código de ejemplo: <code>' . htmlspecialchars($codigo_completo) . '</code></p>';

                    // Prueba 1: Búsqueda exacta
                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM lineas_adjudicadas WHERE codigo_producto = :codigo");
                    $stmt->execute([':codigo' => $codigo_completo]);
                    $resultado1 = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Prueba 2: Búsqueda por 8 dígitos
                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM lineas_adjudicadas WHERE SUBSTRING(codigo_producto, 1, 8) = :codigo");
                    $stmt->execute([':codigo' => $codigo_8]);
                    $resultado2 = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Prueba 3: Búsqueda con LIKE
                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM lineas_adjudicadas WHERE codigo_producto LIKE :codigo");
                    $stmt->execute([':codigo' => $codigo_8 . '%']);
                    $resultado3 = $stmt->fetch(PDO::FETCH_ASSOC);

                    echo '<table>';
                    echo '<thead><tr><th>Tipo de Búsqueda</th><th>SQL</th><th>Resultados</th></tr></thead>';
                    echo '<tbody>';
                    echo '<tr>';
                    echo '<td>Búsqueda exacta</td>';
                    echo '<td><code>codigo_producto = "' . $codigo_completo . '"</code></td>';
                    echo '<td><strong class="success">' . $resultado1['total'] . '</strong></td>';
                    echo '</tr>';
                    echo '<tr>';
                    echo '<td>Primeros 8 dígitos (SUBSTRING)</td>';
                    echo '<td><code>SUBSTRING(codigo_producto, 1, 8) = "' . $codigo_8 . '"</code></td>';
                    echo '<td><strong class="success">' . $resultado2['total'] . '</strong></td>';
                    echo '</tr>';
                    echo '<tr>';
                    echo '<td>Búsqueda con LIKE</td>';
                    echo '<td><code>codigo_producto LIKE "' . $codigo_8 . '%"</code></td>';
                    echo '<td><strong class="success">' . $resultado3['total'] . '</strong></td>';
                    echo '</tr>';
                    echo '</tbody></table>';

                    echo '<div class="code" style="margin-top: 20px;">';
                    echo '<strong class="info">💡 URL de Prueba:</strong><br>';
                    echo '<a href="estadisticas_ganadores_v2.php?codigo=' . $codigo_8 . '&modo=codigo" style="color: #4ec9b0;">';
                    echo 'estadisticas_ganadores_v2.php?codigo=' . $codigo_8 . '&modo=codigo';
                    echo '</a>';
                    echo '</div>';
                }

                echo '</div>';
            }

            // 5. VERIFICAR JOINS
            if ($tablas_info['lineas_adjudicadas']['existe'] &&
                $tablas_info['licitaciones']['existe'] &&
                $tablas_info['lineas_adjudicadas']['count'] > 0) {

                echo '<div class="section">';
                echo '<h2>🔗 5. VERIFICACIÓN DE JOINS</h2>';

                // Verificar relación lineas_adjudicadas <-> licitaciones
                $stmt = $pdo->query("
                    SELECT
                        COUNT(DISTINCT la.numero_sicop) as sicops_en_adjudicadas,
                        COUNT(DISTINCT l.numero_sicop) as sicops_con_match
                    FROM lineas_adjudicadas la
                    LEFT JOIN licitaciones l ON la.numero_sicop = l.numero_sicop
                    LIMIT 1000
                ");

                $join_check = $stmt->fetch(PDO::FETCH_ASSOC);

                echo '<table>';
                echo '<thead><tr><th>Relación</th><th>Registros</th><th>Estado</th></tr></thead>';
                echo '<tbody>';
                echo '<tr>';
                echo '<td>SICOPs en lineas_adjudicadas</td>';
                echo '<td>' . number_format($join_check['sicops_en_adjudicadas']) . '</td>';
                echo '<td>-</td>';
                echo '</tr>';
                echo '<tr>';
                echo '<td>SICOPs con match en licitaciones</td>';
                echo '<td>' . number_format($join_check['sicops_con_match']) . '</td>';

                $porcentaje = $join_check['sicops_en_adjudicadas'] > 0 ?
                    round(($join_check['sicops_con_match'] / $join_check['sicops_en_adjudicadas']) * 100, 1) : 0;

                $badge = $porcentaje > 80 ? 'badge-success' : ($porcentaje > 50 ? 'badge-warning' : 'badge-error');

                echo '<td><span class="badge ' . $badge . '">' . $porcentaje . '% match</span></td>';
                echo '</tr>';
                echo '</tbody></table>';

                echo '</div>';
            }

            // 6. RECOMENDACIONES
            echo '<div class="section">';
            echo '<h2>💡 6. RECOMENDACIONES</h2>';

            echo '<ul style="line-height: 2;">';

            if (!$tablas_info['lineas_adjudicadas']['existe']) {
                echo '<li class="error">✗ La tabla lineas_adjudicadas NO EXISTE - Verifica la importación de datos</li>';
            } elseif ($tablas_info['lineas_adjudicadas']['count'] == 0) {
                echo '<li class="warning">⚠ La tabla lineas_adjudicadas está VACÍA - Importa los datos primero</li>';
            } else {
                echo '<li class="success">✓ Tabla lineas_adjudicadas funcionando correctamente</li>';
            }

            if ($tablas_info['licitaciones']['existe'] && $tablas_info['licitaciones']['count'] > 0) {
                echo '<li class="success">✓ Tabla licitaciones tiene datos</li>';
            } else {
                echo '<li class="warning">⚠ La tabla licitaciones no tiene datos - Los JOINs no funcionarán completamente</li>';
            }

            if ($tablas_info['proveedoras']['existe'] && $tablas_info['proveedoras']['count'] > 0) {
                echo '<li class="success">✓ Tabla proveedoras tiene datos</li>';
            } else {
                echo '<li class="warning">⚠ La tabla proveedoras no tiene datos - No se mostrarán nombres de proveedores</li>';
            }

            echo '<li class="info">📌 Usa <strong>estadisticas_ganadores_v2.php</strong> con modo DEBUG activado</li>';
            echo '<li class="info">📌 Busca por los primeros 8 dígitos del código UNSPSC para mejores resultados</li>';
            echo '<li class="info">📌 Verifica el panel de debug en la página para ver las consultas SQL ejecutadas</li>';

            echo '</ul>';

            echo '</div>';

        } catch (PDOException $e) {
            echo '<div class="section">';
            echo '<h2 class="error">❌ ERROR DE CONEXIÓN</h2>';
            echo '<p class="error">No se pudo conectar a la base de datos:</p>';
            echo '<div class="code">' . htmlspecialchars($e->getMessage()) . '</div>';
            echo '</div>';
        }
        ?>

        <div class="section" style="text-align: center; background: #0e639c; color: white;">
            <h3>✅ Diagnóstico Completo</h3>
            <p>Usa esta información para corregir el problema</p>
        </div>
    </div>
</body>
</html>
