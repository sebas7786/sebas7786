<?php
/**
 * ═══════════════════════════════════════════════════════════════════════
 * SISTEMA DE IMPORTACIÓN SICOP v14.0 - DETALLE DE CARTELES
 * ═══════════════════════════════════════════════════════════════════════
 *
 * NUEVO: Importador para archivo "Detalle de Carteles" con filas mixtas
 * - Procesa información de carteles (licitaciones)
 * - Procesa líneas/partidas en el mismo archivo
 * - Detección automática del tipo de fila
 *
 * @version 14.0
 * @date 2025-11-14
 *
 */

ini_set('max_file_uploads', 20);
ini_set('max_execution_time', 7200);
ini_set('memory_limit', '4G');
ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '100M');
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('America/Costa_Rica');

require_once __DIR__ . '/../config/db.php';

class ImportadorSICOPv14 {
    private $conn;
    private $log = [];
    private $stats = [];
    private $errores_detallados = [];
    private $inicio_tiempo;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->inicio_tiempo = microtime(true);
        $this->verificarEstructura();
        $this->inicializarStats();
    }

    private function inicializarStats() {
        $this->stats = [
            'licitaciones' => ['insertadas' => 0, 'actualizadas' => 0, 'errores' => 0],
            'partidas' => ['insertadas' => 0, 'actualizadas' => 0, 'errores' => 0],
            'lineas_detectadas' => 0,
            'carteles_detectados' => 0,
        ];
    }

    private function verificarEstructura() {
        $this->addLog("🔍 Verificando estructura de base de datos...", 'info');

        // Verificar/agregar campo tipo_cambio_usd en partidas_licitacion
        try {
            $check = $this->conn->query("SHOW COLUMNS FROM partidas_licitacion LIKE 'tipo_cambio_usd'");
            if ($check->rowCount() == 0) {
                $sql = "ALTER TABLE partidas_licitacion ADD COLUMN tipo_cambio_usd DECIMAL(10,4) DEFAULT NULL COMMENT 'Tipo de cambio USD'";
                $this->conn->exec($sql);
                $this->addLog("  ✓ Campo tipo_cambio_usd agregado a partidas_licitacion", 'success');
            }
        } catch (Exception $e) {
            $this->addLog("  ✗ Error verificando campo tipo_cambio_usd: " . $e->getMessage(), 'error');
        }

        // Verificar que los campos nuevos existan en licitaciones
        $campos_licitaciones = [
            'cod_unidad_compra' => 'VARCHAR(50) DEFAULT NULL',
            'nombre_unidad_compra' => 'VARCHAR(255) DEFAULT NULL',
            'pago_adelantado_pymes' => "ENUM('S','N') DEFAULT NULL",
            'fecha_invitacion' => 'DATETIME DEFAULT NULL',
            'fecha_inicio_recepcion' => 'DATETIME DEFAULT NULL',
        ];

        foreach ($campos_licitaciones as $campo => $tipo) {
            try {
                $check = $this->conn->query("SHOW COLUMNS FROM licitaciones LIKE '$campo'");
                if ($check->rowCount() == 0) {
                    $sql = "ALTER TABLE licitaciones ADD COLUMN $campo $tipo";
                    $this->conn->exec($sql);
                    $this->addLog("  ✓ Campo $campo agregado a licitaciones", 'success');
                }
            } catch (Exception $e) {
                // Campo ya existe, silenciosamente continuar
            }
        }

        $this->addLog("✅ Estructura verificada correctamente", 'success');
    }

    private function detectarDelimitador($archivo) {
        if (!file_exists($archivo)) {
            throw new Exception("Archivo no encontrado: $archivo");
        }

        $handle = fopen($archivo, 'r');
        $linea = fgets($handle);
        fclose($handle);

        $delim = substr_count($linea, ';') > substr_count($linea, ',') ? ';' : ',';
        $this->addLog("  📝 Delimitador detectado: " . ($delim == ';' ? 'punto y coma (;)' : 'coma (,)'), 'info');

        return $delim;
    }

    private function leerCSV($archivo) {
        $delim = $this->detectarDelimitador($archivo);

        $handle = fopen($archivo, 'r');

        $data = [];
        $linea_num = 0;

        while (($row = fgetcsv($handle, 0, $delim)) !== false) {
            $linea_num++;

            // Limpiar BOM y espacios
            if ($linea_num == 1 || count($row) > 0) {
                $row = array_map(function($val) {
                    return trim(str_replace("\xEF\xBB\xBF", '', $val));
                }, $row);

                if (trim(implode('', $row)) != '') {
                    $data[] = $row;
                }
            }
        }

        fclose($handle);

        $this->addLog("  📊 Total de filas leídas: " . count($data), 'info');

        return $data;
    }

    private function detectarTipoFila($row) {
        // Si la fila tiene "Número sicop" en primera columna y "Cédula Institución" en segunda, es cartel
        // Si tiene "Número sicop" y "No Linea" es línea

        if (count($row) < 3) return 'desconocido';

        // Verificar si es header
        $primera_col = strtolower(trim($row[0]));
        if (strpos($primera_col, 'mero') !== false && strpos($primera_col, 'sicop') !== false) {
            $segunda_col = strtolower(trim($row[1]));

            if (strpos($segunda_col, 'dula') !== false || strpos($segunda_col, 'instituci') !== false) {
                return 'header_cartel';
            } elseif (strpos($segunda_col, 'linea') !== false) {
                return 'header_linea';
            }
        }

        // Si no es header, intentar detectar por contenido
        // Cartel: tiene 15 columnas aproximadamente
        // Línea: tiene 8 columnas aproximadamente

        if (count($row) >= 14 && count($row) <= 16) {
            return 'cartel';
        } elseif (count($row) >= 7 && count($row) <= 9) {
            return 'linea';
        }

        return 'desconocido';
    }

    private function convertirFecha($fecha) {
        if (!$fecha) return null;
        $fecha = trim($fecha);

        if (strpos($fecha, '/') !== false) {
            $partes = explode(' ', $fecha);
            $fecha_parte = $partes[0];
            list($d, $m, $y) = explode('/', $fecha_parte);
            return "$y-$m-$d";
        } elseif (strpos($fecha, '-') !== false) {
            return $fecha;
        }
        return null;
    }

    private function convertirFechaHora($fecha_hora) {
        if (!$fecha_hora) return null;
        $fecha_hora = trim($fecha_hora);

        if (strpos($fecha_hora, '/') !== false) {
            $partes = explode(' ', $fecha_hora);
            if (count($partes) >= 2) {
                list($d, $m, $y) = explode('/', $partes[0]);
                return "$y-$m-$d " . $partes[1];
            } else {
                list($d, $m, $y) = explode('/', $partes[0]);
                return "$y-$m-$d 00:00:00";
            }
        } elseif (strpos($fecha_hora, '-') !== false) {
            return $fecha_hora;
        }
        return null;
    }

    private function limpiarNumero($num) {
        if (!$num || trim($num) == '') return null;

        $num = trim($num);

        // Eliminar símbolos de moneda
        $num = preg_replace('/[₡$€£¥]/', '', $num);

        // Formato europeo: 1.234.567,89
        if (preg_match('/^\d{1,3}(\.\d{3})*,\d+$/', $num)) {
            $num = str_replace('.', '', $num);
            $num = str_replace(',', '.', $num);
            return (float)$num;
        }

        // Formato americano: 1,234,567.89
        if (preg_match('/^\d{1,3}(,\d{3})*\.\d+$/', $num)) {
            $num = str_replace(',', '', $num);
            return (float)$num;
        }

        // Limpiar y convertir
        $num = preg_replace('/[^\d.,]/', '', $num);
        $num = str_replace(',', '', $num);

        return (float)$num;
    }

    private function addLog($mensaje, $tipo = 'info') {
        $tiempo = date('H:i:s');
        $this->log[] = ['mensaje' => $mensaje, 'tipo' => $tipo, 'tiempo' => $tiempo];
    }

    public function getLog() {
        return $this->log;
    }

    public function getStats() {
        return $this->stats;
    }

    public function getErrores() {
        return $this->errores_detallados;
    }

    public function getTiempoEjecucion() {
        return number_format(microtime(true) - $this->inicio_tiempo, 2);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════════
     * IMPORTADOR PRINCIPAL: DETALLE DE CARTELES (FILAS MIXTAS)
     * ═══════════════════════════════════════════════════════════════════════
     */
    public function importarDetalleCartelesNuevo($archivo) {
        $this->addLog("", 'separator');
        $this->addLog("📦 IMPORTANDO: DETALLE DE CARTELES (NUEVO FORMATO)", 'title');
        $this->addLog("", 'separator');

        try {
            $data = $this->leerCSV($archivo);

            $headers_cartel = null;
            $headers_linea = null;
            $modo_actual = null;

            $this->conn->beginTransaction();

            $licitacion_actual = null;
            $procesadas = 0;

            foreach ($data as $index => $row) {
                $tipo = $this->detectarTipoFila($row);

                // Detectar headers
                if ($tipo == 'header_cartel') {
                    $headers_cartel = $row;
                    $modo_actual = 'cartel';
                    $this->addLog("  ✓ Headers de CARTEL detectados", 'info');
                    continue;
                } elseif ($tipo == 'header_linea') {
                    $headers_linea = $row;
                    $modo_actual = 'linea';
                    $this->addLog("  ✓ Headers de LÍNEA detectados", 'info');
                    continue;
                }

                // Procesar datos según tipo detectado
                if ($tipo == 'cartel' && $headers_cartel) {
                    $licitacion_actual = $this->procesarFilaCartel($row, $headers_cartel);
                    $procesadas++;

                    if ($procesadas % 50 == 0) {
                        $this->addLog("  ⏳ Procesados: $procesadas carteles", 'progress');
                    }

                } elseif ($tipo == 'linea' && $headers_linea && $licitacion_actual) {
                    $this->procesarFilaLinea($row, $headers_linea, $licitacion_actual);

                } elseif ($tipo == 'desconocido' && count($row) > 1) {
                    // Intentar detectar por modo actual
                    if ($modo_actual == 'cartel' && $headers_cartel) {
                        $licitacion_actual = $this->procesarFilaCartel($row, $headers_cartel);
                        $procesadas++;
                    } elseif ($modo_actual == 'linea' && $headers_linea && $licitacion_actual) {
                        $this->procesarFilaLinea($row, $headers_linea, $licitacion_actual);
                    }
                }
            }

            $this->conn->commit();

            $this->addLog("✅ DETALLE DE CARTELES completado", 'success');
            $this->addLog("  📊 Carteles: {$this->stats['carteles_detectados']} | Líneas: {$this->stats['lineas_detectadas']}", 'info');
            $this->addLog("  📊 Licitaciones insertadas: {$this->stats['licitaciones']['insertadas']} | Actualizadas: {$this->stats['licitaciones']['actualizadas']}", 'info');
            $this->addLog("  📊 Partidas insertadas: {$this->stats['partidas']['insertadas']} | Actualizadas: {$this->stats['partidas']['actualizadas']}", 'info');

        } catch (Exception $e) {
            $this->addLog("❌ Error crítico en DETALLE DE CARTELES: " . $e->getMessage(), 'error');
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
        }
    }

    private function procesarFilaCartel($row, $headers) {
        try {
            $this->stats['carteles_detectados']++;

            // Crear mapa de índices
            $idx = [];
            foreach ($headers as $i => $header) {
                $header_limpio = strtolower(trim($header));
                $idx[$header_limpio] = $i;
            }

            // Extraer datos
            $sicop = $this->val($row, $idx, 'número sicop');
            if (!$sicop) {
                $sicop = $this->val($row, $idx, 'numero sicop');
            }

            if (!$sicop) {
                $this->stats['licitaciones']['errores']++;
                return null;
            }

            $cedula_inst = $this->val($row, $idx, 'cédula institución');
            if (!$cedula_inst) $cedula_inst = $this->val($row, $idx, 'cedula institucion');

            $nro_proc = $this->val($row, $idx, 'número procedimiento');
            if (!$nro_proc) $nro_proc = $this->val($row, $idx, 'numero procedimiento');

            $tipo_proc = $this->val($row, $idx, 'tipo procedimiento');
            $modalidad = $this->val($row, $idx, 'modalidad procedimiento');

            $cod_excepcion = $this->val($row, $idx, 'excepción cd');
            if (!$cod_excepcion) $cod_excepcion = $this->val($row, $idx, 'excepcion cd');

            $desc_excepcion = $this->val($row, $idx, 'descripción');
            if (!$desc_excepcion) $desc_excepcion = $this->val($row, $idx, 'descripcion');

            $cod_unidad = $this->val($row, $idx, 'cod unidad compra');
            $nombre_unidad = $this->val($row, $idx, 'nombre unidad compra');
            $pago_pymes = $this->val($row, $idx, 'pago adelantado pymes');

            $fecha_pub = $this->convertirFechaHora($this->val($row, $idx, 'fecha publicación'));
            if (!$fecha_pub) $fecha_pub = $this->convertirFechaHora($this->val($row, $idx, 'fecha publicacion'));

            $fecha_inv = $this->convertirFechaHora($this->val($row, $idx, 'fecha invitación'));
            if (!$fecha_inv) $fecha_inv = $this->convertirFechaHora($this->val($row, $idx, 'fecha invitacion'));

            $fecha_inicio_rec = $this->convertirFechaHora($this->val($row, $idx, 'fecha inicio recepción'));
            if (!$fecha_inicio_rec) $fecha_inicio_rec = $this->convertirFechaHora($this->val($row, $idx, 'fecha inicio recepcion'));

            $fecha_cierre_rec = $this->convertirFechaHora($this->val($row, $idx, 'fecha cierre recepción'));
            if (!$fecha_cierre_rec) $fecha_cierre_rec = $this->convertirFechaHora($this->val($row, $idx, 'fecha cierre recepcion'));

            $fecha_apertura = $this->convertirFechaHora($this->val($row, $idx, 'fecha apertura'));

            // Verificar si existe
            $stmt = $this->conn->prepare("SELECT id FROM licitaciones WHERE numero_sicop = ?");
            $stmt->execute([$sicop]);
            $existe = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existe) {
                // Actualizar
                $sql = "UPDATE licitaciones SET
                    numero_procedimiento = COALESCE(?, numero_procedimiento),
                    cedula_institucion = COALESCE(?, cedula_institucion),
                    tipo_procedimiento = COALESCE(?, tipo_procedimiento),
                    modalidad = COALESCE(?, modalidad),
                    codigo_excepcion = COALESCE(?, codigo_excepcion),
                    descripcion_excepcion = COALESCE(?, descripcion_excepcion),
                    cod_unidad_compra = COALESCE(?, cod_unidad_compra),
                    nombre_unidad_compra = COALESCE(?, nombre_unidad_compra),
                    pago_adelantado_pymes = COALESCE(?, pago_adelantado_pymes),
                    fecha_publicacion = COALESCE(?, fecha_publicacion),
                    fecha_invitacion = COALESCE(?, fecha_invitacion),
                    fecha_inicio_recepcion = COALESCE(?, fecha_inicio_recepcion),
                    fecha_cierre_recepcion = COALESCE(?, fecha_cierre_recepcion),
                    fecha_apertura_ofertas = COALESCE(?, fecha_apertura_ofertas),
                    fecha_ultima_actualizacion = NOW()
                    WHERE id = ?";

                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    $nro_proc,
                    $cedula_inst,
                    $tipo_proc,
                    $modalidad,
                    $cod_excepcion,
                    $desc_excepcion,
                    $cod_unidad,
                    $nombre_unidad,
                    $pago_pymes,
                    $fecha_pub,
                    $fecha_inv,
                    $fecha_inicio_rec,
                    $fecha_cierre_rec,
                    $fecha_apertura,
                    $existe['id']
                ]);

                $this->stats['licitaciones']['actualizadas']++;
                return $existe['id'];

            } else {
                // Insertar nueva
                $titulo = $desc_excepcion ? substr($desc_excepcion, 0, 255) : 'Licitación ' . $sicop;

                $sql = "INSERT INTO licitaciones (
                    numero_sicop, numero_procedimiento, cedula_institucion,
                    tipo_procedimiento, modalidad, codigo_excepcion,
                    descripcion_excepcion, cod_unidad_compra, nombre_unidad_compra,
                    pago_adelantado_pymes, fecha_publicacion, fecha_invitacion,
                    fecha_inicio_recepcion, fecha_cierre_recepcion, fecha_apertura_ofertas,
                    titulo, descripcion, institucion, estado, categoria, clasificacion_objeto
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    $sicop,
                    $nro_proc ?: 'N/A',
                    $cedula_inst ?: 'N/A',
                    $tipo_proc ?: 'No especificado',
                    $modalidad,
                    $cod_excepcion,
                    $desc_excepcion,
                    $cod_unidad,
                    $nombre_unidad,
                    $pago_pymes,
                    $fecha_pub ?: date('Y-m-d'),
                    $fecha_inv,
                    $fecha_inicio_rec,
                    $fecha_cierre_rec,
                    $fecha_apertura,
                    $titulo,
                    $desc_excepcion,
                    'Por definir',
                    'abierta',
                    'General',
                    'BIENES/SERVICIOS'
                ]);

                $this->stats['licitaciones']['insertadas']++;
                return $this->conn->lastInsertId();
            }

        } catch (Exception $e) {
            $this->stats['licitaciones']['errores']++;
            $this->errores_detallados[] = "Cartel SICOP $sicop: " . $e->getMessage();
            return null;
        }
    }

    private function procesarFilaLinea($row, $headers, $licitacion_id) {
        try {
            $this->stats['lineas_detectadas']++;

            // Crear mapa de índices
            $idx = [];
            foreach ($headers as $i => $header) {
                $header_limpio = strtolower(trim($header));
                $idx[$header_limpio] = $i;
            }

            // Extraer datos
            $sicop = $this->val($row, $idx, 'número sicop');
            if (!$sicop) $sicop = $this->val($row, $idx, 'numero sicop');

            $linea = $this->val($row, $idx, 'no linea', 0);
            $partida = $this->val($row, $idx, 'número partida', 0);
            if (!$partida) $partida = $this->val($row, $idx, 'numero partida', 0);

            $cantidad = $this->limpiarNumero($this->val($row, $idx, 'cantidad solicitada'));
            $precio = $this->limpiarNumero($this->val($row, $idx, 'precio unitario estimado'));
            $moneda = $this->val($row, $idx, 'tipo moneda', 'CRC');
            $tipo_cambio = $this->limpiarNumero($this->val($row, $idx, 'tipo cambio usd'));
            $codigo = $this->val($row, $idx, 'código identificación');
            if (!$codigo) $codigo = $this->val($row, $idx, 'codigo identificacion');

            $monto_estimado = $cantidad && $precio ? $cantidad * $precio : null;

            // Insertar/actualizar partida
            $sql = "INSERT INTO partidas_licitacion (
                licitacion_id, partida, linea, codigo_identificacion,
                cantidad, precio_unitario, moneda, monto_estimado, tipo_cambio_usd
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                codigo_identificacion = VALUES(codigo_identificacion),
                cantidad = VALUES(cantidad),
                precio_unitario = VALUES(precio_unitario),
                moneda = VALUES(moneda),
                monto_estimado = VALUES(monto_estimado),
                tipo_cambio_usd = VALUES(tipo_cambio_usd)";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $licitacion_id,
                $partida,
                $linea,
                $codigo,
                $cantidad,
                $precio,
                $moneda,
                $monto_estimado,
                $tipo_cambio
            ]);

            $this->stats['partidas']['insertadas']++;

        } catch (Exception $e) {
            $this->stats['partidas']['errores']++;
            $this->errores_detallados[] = "Línea partida $partida línea $linea: " . $e->getMessage();
        }
    }

    private function val($row, $indices, $key, $default = null) {
        if (isset($indices[$key]) && isset($row[$indices[$key]])) {
            $v = trim($row[$indices[$key]]);
            return $v !== '' ? $v : $default;
        }
        return $default;
    }
}

// ═══════════════════════════════════════════════════════════════════════
// PROCESAMIENTO
// ═══════════════════════════════════════════════════════════════════════

$imp = new ImportadorSICOPv14($conn);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['archivo'])) {
    // Procesar importación
    echo "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'><title>Resultado Importación SICOP v14.0</title>";
    echo "<style>body { font-family: -apple-system, system-ui; background: #f3f4f6; color: #333; line-height: 1.6; }";
    echo ".container { max-width: 1200px; margin: 40px auto; background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.2); }";
    echo ".header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 60px 40px; text-align: center; }";
    echo ".header h1 { font-size: 48px; margin-bottom: 10px; }";
    echo ".content { padding: 40px; }";
    echo ".stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 30px 0; }";
    echo ".stat-card { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 25px; border-radius: 15px; color: #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }";
    echo ".stat-number { font-size: 48px; font-weight: bold; }";
    echo ".stat-label { font-size: 16px; opacity: 0.95; text-transform: uppercase; }";
    echo ".log-container { background: #1e1e1e; border-radius: 15px; padding: 20px; max-height: 600px; overflow-y: auto; font-family: monospace; }";
    echo ".log-entry { padding: 8px 12px; margin: 4px 0; border-radius: 6px; }";
    echo ".log-title { background: #2563eb; color: #fff; font-weight: bold; }";
    echo ".log-success { background: #10b981; color: #fff; }";
    echo ".log-error { background: #ef4444; color: #fff; }";
    echo ".log-info { color: #60a5fa; }";
    echo ".log-progress { color: #8b5cf6; }";
    echo ".btn { display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; text-decoration: none; border-radius: 50px; margin-top: 30px; }";
    echo "</style></head><body>";

    echo "<div class='container'>";
    echo "<div class='header'><h1>🚀 Importación SICOP v14.0</h1><p>Detalle de Carteles</p></div>";
    echo "<div class='content'>";

    // Procesar archivo
    $archivo = $_FILES['archivo'];
    if ($archivo['error'] == 0) {
        $imp->importarDetalleCartelesNuevo($archivo['tmp_name']);
    }

    // Mostrar estadísticas
    $stats = $imp->getStats();

    echo "<h2>📊 Resultados</h2>";
    echo "<div class='stats-grid'>";

    foreach ($stats as $key => $val) {
        if (is_array($val)) {
            $total = ($val['insertadas'] ?? 0) + ($val['actualizadas'] ?? 0);
            echo "<div class='stat-card'>";
            echo "<div class='stat-number'>$total</div>";
            echo "<div class='stat-label'>$key</div>";
            echo "</div>";
        } else {
            echo "<div class='stat-card'>";
            echo "<div class='stat-number'>$val</div>";
            echo "<div class='stat-label'>$key</div>";
            echo "</div>";
        }
    }

    echo "</div>";

    // Log
    echo "<h2>📋 Log de Importación</h2>";
    echo "<div class='log-container'>";

    foreach ($imp->getLog() as $entry) {
        $clase = 'log-' . $entry['tipo'];
        echo "<div class='log-entry $clase'>[{$entry['tiempo']}] {$entry['mensaje']}</div>";
    }

    echo "</div>";

    // Errores
    $errores = $imp->getErrores();
    if (!empty($errores)) {
        echo "<h3>⚠️ Errores (" . count($errores) . ")</h3>";
        echo "<ul>";
        foreach (array_slice($errores, 0, 20) as $error) {
            echo "<li>$error</li>";
        }
        echo "</ul>";
    }

    echo "<div style='text-align: center;'>";
    echo "<a href='importador_sicop_v14.php' class='btn'>🔄 Nueva Importación</a>";
    echo "</div>";

    echo "</div></div></body></html>";

} else {
    // Formulario de carga
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Importador SICOP v14.0 - Detalle de Carteles</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            width: 100%;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 50px 40px;
            text-align: center;
        }
        .header h1 { font-size: 48px; margin-bottom: 10px; }
        .content { padding: 50px 40px; }
        .upload-area {
            border: 3px dashed #667eea;
            border-radius: 20px;
            padding: 60px 40px;
            text-align: center;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            margin: 30px 0;
            cursor: pointer;
        }
        .upload-area:hover {
            border-color: #764ba2;
            transform: scale(1.02);
        }
        input[type="file"] { display: none; }
        .file-label {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border-radius: 50px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-submit {
            width: 100%;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border: none;
            border-radius: 50px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
        }
        .btn-submit:disabled { opacity: 0.5; cursor: not-allowed; }
        .info-box {
            background: #e0e7ff;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .info-box h3 { color: #4c1d95; margin-bottom: 10px; }
        .info-box ul { padding-left: 25px; color: #5b21b6; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 SICOP v14.0</h1>
            <p>Importador de Detalle de Carteles</p>
        </div>

        <div class="content">
            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                    <h3>📁 Selecciona el archivo CSV</h3>
                    <p>Archivo: Detalle de Carteles</p>
                    <label for="fileInput" class="file-label">Examinar Archivo</label>
                    <input type="file" name="archivo" id="fileInput" accept=".csv" required>
                </div>

                <button type="submit" class="btn-submit" id="submitBtn" disabled>
                    🚀 INICIAR IMPORTACIÓN
                </button>
            </form>

            <div class="info-box">
                <h3>📋 Estructura del Archivo</h3>
                <p><strong>El archivo contiene DOS tipos de filas:</strong></p>
                <ul>
                    <li><strong>Carteles:</strong> Información general de licitaciones (15 columnas)</li>
                    <li><strong>Líneas:</strong> Partidas/ítems de las licitaciones (8 columnas)</li>
                </ul>
                <p style="margin-top: 15px; color: #6b21a8;">
                    ✅ El sistema detecta automáticamente el tipo de cada fila<br>
                    ✅ Procesa carteles y líneas en una sola importación<br>
                    ✅ Actualiza licitaciones existentes
                </p>
            </div>
        </div>
    </div>

    <script>
        const fileInput = document.getElementById('fileInput');
        const submitBtn = document.getElementById('submitBtn');

        fileInput.addEventListener('change', function() {
            submitBtn.disabled = this.files.length === 0;
        });

        document.getElementById('uploadForm').addEventListener('submit', function() {
            submitBtn.textContent = '⏳ Procesando... Por favor espere';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>
    <?php
}
?>
