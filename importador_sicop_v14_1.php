<?php
/**
 * ═══════════════════════════════════════════════════════════════════════
 * SISTEMA DE IMPORTACIÓN SICOP v14.1 - DETALLE DE CARTELES (CORREGIDO)
 * ═══════════════════════════════════════════════════════════════════════
 *
 * CORRECCIÓN: Ahora las líneas buscan la licitación por NUMERO_SICOP
 * No dependen de $licitacion_actual
 *
 * @version 14.1
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

class ImportadorSICOPv14_1 {
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

        // Verificar UNIQUE KEY en partidas
        try {
            $check = $this->conn->query("SHOW INDEXES FROM partidas_licitacion WHERE Key_name = 'unique_partida_linea'");
            if ($check->rowCount() == 0) {
                $this->addLog("  ⚠️ Creando UNIQUE KEY en partidas_licitacion...", 'warning');
                $this->conn->exec("ALTER TABLE partidas_licitacion ADD UNIQUE KEY unique_partida_linea (licitacion_id, partida, linea)");
                $this->addLog("  ✓ UNIQUE KEY creado", 'success');
            }
        } catch (Exception $e) {
            // Ya existe
        }

        // Verificar campo tipo_cambio_usd
        try {
            $check = $this->conn->query("SHOW COLUMNS FROM partidas_licitacion LIKE 'tipo_cambio_usd'");
            if ($check->rowCount() == 0) {
                $this->conn->exec("ALTER TABLE partidas_licitacion ADD COLUMN tipo_cambio_usd DECIMAL(10,4) DEFAULT NULL");
                $this->addLog("  ✓ Campo tipo_cambio_usd agregado", 'success');
            }
        } catch (Exception $e) {
            // Ya existe
        }

        $this->addLog("✅ Estructura verificada", 'success');
    }

    private function detectarDelimitador($archivo) {
        if (!file_exists($archivo)) {
            throw new Exception("Archivo no encontrado: $archivo");
        }

        $handle = fopen($archivo, 'r');
        $linea = fgets($handle);
        fclose($handle);

        $delim = substr_count($linea, ';') > substr_count($linea, ',') ? ';' : ',';
        $this->addLog("  📝 Delimitador: " . ($delim == ';' ? ';' : ','), 'info');

        return $delim;
    }

    private function leerCSV($archivo) {
        $delim = $this->detectarDelimitador($archivo);

        $handle = fopen($archivo, 'r');
        $data = [];
        $linea_num = 0;

        while (($row = fgetcsv($handle, 0, $delim)) !== false) {
            $linea_num++;
            $row = array_map(function($val) {
                return trim(str_replace("\xEF\xBB\xBF", '', $val));
            }, $row);

            if (trim(implode('', $row)) != '') {
                $data[] = $row;
            }
        }

        fclose($handle);

        $this->addLog("  📊 Filas leídas: " . count($data), 'info');

        return $data;
    }

    private function detectarTipoFila($row) {
        if (count($row) < 3) return 'desconocido';

        $primera_col = strtolower(trim($row[0]));
        if (strpos($primera_col, 'mero') !== false && strpos($primera_col, 'sicop') !== false) {
            $segunda_col = strtolower(trim($row[1]));

            if (strpos($segunda_col, 'dula') !== false || strpos($segunda_col, 'instituci') !== false) {
                return 'header_cartel';
            } elseif (strpos($segunda_col, 'linea') !== false) {
                return 'header_linea';
            }
        }

        // Por número de columnas
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
        }
        return $fecha_hora;
    }

    private function limpiarNumero($num) {
        if (!$num || trim($num) == '') return null;

        $num = trim($num);
        $num = preg_replace('/[₡$€£¥]/', '', $num);

        if (preg_match('/^\d{1,3}(\.\d{3})*,\d+$/', $num)) {
            $num = str_replace('.', '', $num);
            $num = str_replace(',', '.', $num);
            return (float)$num;
        }

        if (preg_match('/^\d{1,3}(,\d{3})*\.\d+$/', $num)) {
            $num = str_replace(',', '', $num);
            return (float)$num;
        }

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
     * IMPORTADOR PRINCIPAL: DETALLE DE CARTELES (CORREGIDO)
     * ═══════════════════════════════════════════════════════════════════════
     */
    public function importarDetalleCartelesNuevo($archivo) {
        $this->addLog("", 'separator');
        $this->addLog("📦 IMPORTANDO: DETALLE DE CARTELES v14.1", 'title');
        $this->addLog("", 'separator');

        try {
            $data = $this->leerCSV($archivo);

            $headers_cartel = null;
            $headers_linea = null;

            $this->conn->beginTransaction();

            $procesadas = 0;

            foreach ($data as $index => $row) {
                $tipo = $this->detectarTipoFila($row);

                // Detectar headers
                if ($tipo == 'header_cartel') {
                    $headers_cartel = $row;
                    $this->addLog("  ✓ Headers CARTEL detectados (columnas: " . count($row) . ")", 'info');
                    continue;
                } elseif ($tipo == 'header_linea') {
                    $headers_linea = $row;
                    $this->addLog("  ✓ Headers LÍNEA detectados (columnas: " . count($row) . ")", 'info');
                    continue;
                }

                // Procesar carteles
                if ($tipo == 'cartel' && $headers_cartel) {
                    $this->procesarFilaCartel($row, $headers_cartel);
                    $procesadas++;

                    if ($procesadas % 100 == 0) {
                        $this->addLog("  ⏳ Procesados: $procesadas carteles", 'progress');
                    }
                }

                // ⭐ CORRECCIÓN: Procesar líneas buscando por NUMERO_SICOP
                elseif ($tipo == 'linea' && $headers_linea) {
                    $this->procesarFilaLineaPorSICOP($row, $headers_linea);
                }
            }

            $this->conn->commit();

            $this->addLog("✅ IMPORTACIÓN COMPLETADA", 'success');
            $this->addLog("  📊 Carteles: {$this->stats['carteles_detectados']} | Líneas: {$this->stats['lineas_detectadas']}", 'info');
            $this->addLog("  📊 Licitaciones: I:{$this->stats['licitaciones']['insertadas']} A:{$this->stats['licitaciones']['actualizadas']}", 'info');
            $this->addLog("  📊 Partidas: I:{$this->stats['partidas']['insertadas']} A:{$this->stats['partidas']['actualizadas']}", 'info');

        } catch (Exception $e) {
            $this->addLog("❌ Error crítico: " . $e->getMessage(), 'error');
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
        }
    }

    private function procesarFilaCartel($row, $headers) {
        try {
            $this->stats['carteles_detectados']++;

            $idx = [];
            foreach ($headers as $i => $header) {
                $header_limpio = strtolower(trim($header));
                $idx[$header_limpio] = $i;
            }

            $sicop = $this->val($row, $idx, 'número sicop');
            if (!$sicop) $sicop = $this->val($row, $idx, 'numero sicop');

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
                    $nro_proc, $cedula_inst, $tipo_proc, $modalidad,
                    $cod_excepcion, $desc_excepcion, $cod_unidad, $nombre_unidad,
                    $pago_pymes, $fecha_pub, $fecha_inv, $fecha_inicio_rec,
                    $fecha_cierre_rec, $fecha_apertura, $existe['id']
                ]);

                $this->stats['licitaciones']['actualizadas']++;
                return $existe['id'];

            } else {
                // Insertar
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
                    $sicop, $nro_proc ?: 'N/A', $cedula_inst ?: 'N/A',
                    $tipo_proc ?: 'No especificado', $modalidad,
                    $cod_excepcion, $desc_excepcion, $cod_unidad, $nombre_unidad,
                    $pago_pymes, $fecha_pub ?: date('Y-m-d'), $fecha_inv,
                    $fecha_inicio_rec, $fecha_cierre_rec, $fecha_apertura,
                    $titulo, $desc_excepcion, 'Por definir', 'abierta',
                    'General', 'BIENES/SERVICIOS'
                ]);

                $this->stats['licitaciones']['insertadas']++;
                return $this->conn->lastInsertId();
            }

        } catch (Exception $e) {
            $this->stats['licitaciones']['errores']++;
            $this->errores_detallados[] = "Cartel SICOP " . ($sicop ?? 'desconocido') . ": " . $e->getMessage();
            return null;
        }
    }

    /**
     * ⭐ CORRECCIÓN: Buscar licitación por NUMERO_SICOP, no por ID
     */
    private function procesarFilaLineaPorSICOP($row, $headers) {
        try {
            $this->stats['lineas_detectadas']++;

            $idx = [];
            foreach ($headers as $i => $header) {
                $header_limpio = strtolower(trim($header));
                $idx[$header_limpio] = $i;
            }

            // Extraer NUMERO_SICOP de la línea
            $sicop = $this->val($row, $idx, 'número sicop');
            if (!$sicop) $sicop = $this->val($row, $idx, 'numero sicop');

            if (!$sicop) {
                $this->stats['partidas']['errores']++;
                $this->errores_detallados[] = "Línea sin NUMERO_SICOP";
                return;
            }

            // ⭐ BUSCAR la licitación por NUMERO_SICOP
            $stmt = $this->conn->prepare("SELECT id FROM licitaciones WHERE numero_sicop = ?");
            $stmt->execute([$sicop]);
            $licitacion = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$licitacion) {
                $this->stats['partidas']['errores']++;
                $this->errores_detallados[] = "Licitación no encontrada para SICOP: $sicop";
                return;
            }

            $licitacion_id = $licitacion['id'];

            $linea = $this->val($row, $idx, 'no linea', 0);
            if (!$linea) $linea = $this->val($row, $idx, 'no línea', 0);

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

            if ($stmt->rowCount() > 0) {
                $this->stats['partidas']['insertadas']++;
            } else {
                $this->stats['partidas']['actualizadas']++;
            }

        } catch (Exception $e) {
            $this->stats['partidas']['errores']++;
            $this->errores_detallados[] = "Línea SICOP " . ($sicop ?? 'desconocido') . " P:$partida L:$linea - " . $e->getMessage();
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

$imp = new ImportadorSICOPv14_1($conn);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['archivo'])) {
    header('Content-Type: text/html; charset=UTF-8');

    echo "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'><title>Importación SICOP v14.1</title>";
    echo "<style>
    body { font-family: -apple-system, system-ui; background: #f3f4f6; margin: 0; padding: 20px; }
    .container { max-width: 1200px; margin: 0 auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 40px; text-align: center; border-radius: 8px 8px 0 0; }
    .header h1 { margin: 0; font-size: 32px; }
    .content { padding: 30px; }
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
    .stat-card { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 20px; border-radius: 8px; color: #fff; }
    .stat-number { font-size: 36px; font-weight: bold; }
    .stat-label { font-size: 14px; opacity: 0.9; margin-top: 5px; }
    .log-container { background: #1e1e1e; border-radius: 8px; padding: 20px; max-height: 500px; overflow-y: auto; font-family: monospace; font-size: 13px; }
    .log-entry { padding: 6px 10px; margin: 3px 0; border-radius: 4px; }
    .log-title { background: #2563eb; color: #fff; font-weight: bold; }
    .log-success { background: #10b981; color: #fff; }
    .log-error { background: #ef4444; color: #fff; }
    .log-warning { background: #f59e0b; color: #fff; }
    .log-info { color: #60a5fa; }
    .log-progress { color: #8b5cf6; }
    .log-separator { height: 1px; background: #444; margin: 8px 0; }
    .btn { display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; text-decoration: none; border-radius: 6px; margin-top: 20px; }
    .errores { background: #fee; border-left: 3px solid #ef4444; padding: 15px; margin: 20px 0; border-radius: 4px; }
    .errores h3 { color: #dc2626; margin: 0 0 10px 0; }
    </style></head><body>";

    echo "<div class='container'>";
    echo "<div class='header'><h1>🚀 Importación SICOP v14.1</h1><p>Detalle de Carteles (Corregido)</p></div>";
    echo "<div class='content'>";

    // Procesar archivo
    $archivo = $_FILES['archivo'];
    if ($archivo['error'] == 0) {
        $imp->importarDetalleCartelesNuevo($archivo['tmp_name']);
    }

    // Estadísticas
    $stats = $imp->getStats();

    echo "<h2>📊 Resultados</h2>";
    echo "<div class='stats-grid'>";

    foreach ($stats as $key => $val) {
        if (is_array($val)) {
            $total = ($val['insertadas'] ?? 0) + ($val['actualizadas'] ?? 0);
            echo "<div class='stat-card'>";
            echo "<div class='stat-number'>$total</div>";
            echo "<div class='stat-label'>" . ucfirst($key) . "</div>";
            echo "<div style='font-size:11px; margin-top:8px;'>I:{$val['insertadas']} A:{$val['actualizadas']} E:{$val['errores']}</div>";
            echo "</div>";
        } else {
            echo "<div class='stat-card'>";
            echo "<div class='stat-number'>$val</div>";
            echo "<div class='stat-label'>" . ucfirst(str_replace('_', ' ', $key)) . "</div>";
            echo "</div>";
        }
    }

    echo "</div>";

    echo "<p><strong>⏱️ Tiempo:</strong> " . $imp->getTiempoEjecucion() . " segundos</p>";

    // Log
    echo "<h2>📋 Log de Importación</h2>";
    echo "<div class='log-container'>";

    foreach ($imp->getLog() as $entry) {
        if ($entry['tipo'] == 'separator') {
            echo "<div class='log-separator'></div>";
        } else {
            $clase = 'log-' . $entry['tipo'];
            echo "<div class='log-entry $clase'>[{$entry['tiempo']}] {$entry['mensaje']}</div>";
        }
    }

    echo "</div>";

    // Errores
    $errores = $imp->getErrores();
    if (!empty($errores)) {
        echo "<div class='errores'>";
        echo "<h3>⚠️ Errores Detallados (" . count($errores) . ")</h3>";
        echo "<ul style='margin: 0; padding-left: 20px;'>";
        foreach (array_slice($errores, 0, 30) as $error) {
            echo "<li style='padding: 4px 0;'>$error</li>";
        }
        if (count($errores) > 30) {
            echo "<li><em>... y " . (count($errores) - 30) . " errores más</em></li>";
        }
        echo "</ul></div>";
    }

    echo "<div style='text-align: center;'>";
    echo "<a href='importador_sicop_v14_1.php' class='btn'>🔄 Nueva Importación</a>";
    echo "</div>";

    echo "</div></div></body></html>";

} else {
    // Formulario
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Importador SICOP v14.1</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, system-ui;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            max-width: 700px;
            width: 100%;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 40px;
            text-align: center;
        }
        .header h1 { font-size: 36px; margin-bottom: 8px; }
        .header p { font-size: 16px; opacity: 0.95; }
        .content { padding: 40px; }
        .upload-area {
            border: 3px dashed #667eea;
            border-radius: 12px;
            padding: 50px 30px;
            text-align: center;
            background: #f9fafb;
            margin: 20px 0;
            cursor: pointer;
            transition: all 0.3s;
        }
        .upload-area:hover {
            border-color: #764ba2;
            background: #f3f4f6;
        }
        .upload-area h3 { font-size: 20px; color: #333; margin-bottom: 12px; }
        input[type="file"] { display: none; }
        .file-label {
            display: inline-block;
            padding: 12px 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.2s;
        }
        .file-label:hover { transform: translateY(-2px); }
        .btn-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn-submit:hover { transform: translateY(-2px); }
        .btn-submit:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
        .info-box {
            background: #dbeafe;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #3b82f6;
        }
        .info-box h4 { color: #1e40af; margin-bottom: 10px; }
        .info-box ul { padding-left: 20px; color: #1e3a8a; }
        .info-box li { padding: 4px 0; }
        .alert-warning {
            background: #fef3c7;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #f59e0b;
            margin: 15px 0;
        }
        .alert-warning strong { color: #92400e; display: block; margin-bottom: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 SICOP v14.1</h1>
            <p>Importador de Detalle de Carteles (Corregido)</p>
        </div>

        <div class="content">
            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                    <h3>📁 Selecciona el archivo CSV</h3>
                    <p style="color: #666; margin: 10px 0;">Detalle de Carteles</p>
                    <label for="fileInput" class="file-label">Examinar Archivo</label>
                    <input type="file" name="archivo" id="fileInput" accept=".csv" required>
                </div>

                <button type="submit" class="btn-submit" id="submitBtn" disabled>
                    🚀 INICIAR IMPORTACIÓN
                </button>
            </form>

            <div class="info-box">
                <h4>✅ Correcciones en v14.1</h4>
                <ul>
                    <li>Las líneas ahora buscan la licitación por NUMERO_SICOP</li>
                    <li>No depende de orden secuencial de carteles/líneas</li>
                    <li>Crea UNIQUE KEY automáticamente si no existe</li>
                    <li>Mejor manejo de errores y logs detallados</li>
                </ul>
            </div>

            <div class="alert-warning">
                <strong>⚠️ Importante:</strong>
                Este archivo solo importa datos básicos de las partidas (código, cantidad, precio).
                Los nombres/descripciones vienen del archivo <strong>DetalleLineaCartel.csv</strong>
                que debes importar con el importador v13.
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
            submitBtn.textContent = '⏳ Procesando... Espere por favor';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>
    <?php
}
?>
