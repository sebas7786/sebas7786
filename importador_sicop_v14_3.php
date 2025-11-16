<?php
/**
 * ═══════════════════════════════════════════════════════════════════════
 * IMPORTADOR SICOP v14.3 - DETECCIÓN INTELIGENTE POR CONTENIDO
 * ═══════════════════════════════════════════════════════════════════════
 *
 * CORRECCIÓN v14.3:
 * - Detecta líneas por CONTENIDO, no por número de columnas
 * - Las líneas tienen 15 columnas pero solo usan las primeras 8
 * - Diferencia carteles de líneas por el contenido de las celdas
 *
 * @version 14.2
 * @date 2025-11-14
 */

ini_set('max_file_uploads', 20);
ini_set('max_execution_time', 7200);
ini_set('memory_limit', '4G');
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('America/Costa_Rica');

require_once __DIR__ . '/../config/db.php';

class ImportadorSICOPv14_3 {
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
            'detalle_carteles' => ['procesadas' => 0, 'actualizadas' => 0, 'errores' => 0],
            'lineas_detectadas' => 0,
            'carteles_detectados' => 0,
        ];
    }

    private function verificarEstructura() {
        $this->addLog("🔍 Verificando estructura...", 'info');

        try {
            $check = $this->conn->query("SHOW INDEXES FROM partidas_licitacion WHERE Key_name = 'unique_partida_linea'");
            if ($check->rowCount() == 0) {
                $this->conn->exec("ALTER TABLE partidas_licitacion ADD UNIQUE KEY unique_partida_linea (licitacion_id, partida, linea)");
                $this->addLog("  ✓ UNIQUE KEY creado", 'success');
            }
        } catch (Exception $e) {
            // Ya existe
        }

        try {
            $check = $this->conn->query("SHOW COLUMNS FROM partidas_licitacion LIKE 'tipo_cambio_usd'");
            if ($check->rowCount() == 0) {
                $this->conn->exec("ALTER TABLE partidas_licitacion ADD COLUMN tipo_cambio_usd DECIMAL(10,4) DEFAULT NULL");
            }
        } catch (Exception $e) {
            // Ya existe
        }

        $this->addLog("✅ Estructura OK", 'success');
    }

    private function leerCSV($archivo) {
        $handle = fopen($archivo, 'r');
        $primera = fgets($handle);
        $delim = substr_count($primera, ';') > substr_count($primera, ',') ? ';' : ',';
        fclose($handle);

        $this->addLog("  📝 Delimitador: " . ($delim == ';' ? ';' : ','), 'info');

        $handle = fopen($archivo, 'r');
        $data = [];

        while (($row = fgetcsv($handle, 0, $delim)) !== false) {
            $row = array_map(function($val) {
                return trim(str_replace("\xEF\xBB\xBF", '', $val));
            }, $row);

            if (trim(implode('', $row)) != '') {
                $data[] = $row;
            }
        }

        fclose($handle);
        $this->addLog("  📊 Filas: " . count($data), 'info');

        return $data;
    }

    /**
     * ⭐ DETECCIÓN INTELIGENTE POR CONTENIDO
     */
    private function detectarTipoFila($row) {
        if (count($row) < 3) return 'desconocido';

        $col0 = strtolower(trim($row[0] ?? ''));
        $col1 = strtolower(trim($row[1] ?? ''));

        // DETECTAR HEADERS
        if (strpos($col0, 'mero') !== false && strpos($col0, 'sicop') !== false) {
            if (strpos($col1, 'dula') !== false || strpos($col1, 'instituci') !== false) {
                return 'header_cartel';
            } elseif (strpos($col1, 'linea') !== false || strpos($col1, 'línea') !== false) {
                return 'header_linea';
            }
        }

        // DETECTAR LÍNEAS vs CARTELES por CONTENIDO
        $col0_val = trim($row[0] ?? '');
        $col1_val = trim($row[1] ?? '');
        $col2_val = trim($row[2] ?? '');
        $col5_val = trim($row[5] ?? '');
        $col9_val = trim($row[9] ?? '');

        // LÍNEA: Columna 0 es SICOP, col 1 y 2 son números pequeños, col 9 vacía
        if (
            preg_match('/^\d{11,14}$/', $col0_val) && // SICOP de 11-14 dígitos
            is_numeric($col1_val) && intval($col1_val) <= 999 && // Línea ≤ 999
            is_numeric($col2_val) && intval($col2_val) <= 999 && // Partida ≤ 999
            empty($col9_val) // Col 9 vacía (en carteles tiene descripción)
        ) {
            return 'linea';
        }

        // CARTEL: Columna 9 tiene texto (descripción)
        if (
            preg_match('/^\d{11,14}$/', $col0_val) && // SICOP
            !empty($col9_val) && // Col 9 con texto
            strlen($col9_val) > 5 // Descripción larga
        ) {
            return 'cartel';
        }

        return 'desconocido';
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

        // Manejar notación científica (7,81018E+15)
        if (stripos($num, 'E+') !== false || stripos($num, 'E-') !== false) {
            $num = str_replace(',', '.', $num);
            return sprintf('%.0f', (float)$num);
        }

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
        $this->log[] = ['mensaje' => $mensaje, 'tipo' => $tipo, 'tiempo' => date('H:i:s')];
    }

    public function getLog() { return $this->log; }
    public function getStats() { return $this->stats; }
    public function getErrores() { return $this->errores_detallados; }
    public function getTiempoEjecucion() { return number_format(microtime(true) - $this->inicio_tiempo, 2); }

    /**
     * ═══════════════════════════════════════════════════════════════════════
     * IMPORTADOR PRINCIPAL
     * ═══════════════════════════════════════════════════════════════════════
     */
    public function importarDetalleCartelesNuevo($archivo) {
        $this->addLog("", 'separator');
        $this->addLog("📦 IMPORTANDO: DETALLE DE CARTELES v14.3", 'title');
        $this->addLog("", 'separator');

        try {
            $data = $this->leerCSV($archivo);

            $headers_cartel = null;
            $headers_linea = null;

            $this->conn->beginTransaction();

            foreach ($data as $index => $row) {
                $tipo = $this->detectarTipoFila($row);

                // Headers
                if ($tipo == 'header_cartel') {
                    $headers_cartel = $row;
                    $this->addLog("  ✓ HEADER CARTEL (cols: " . count($row) . ")", 'info');
                    continue;
                } elseif ($tipo == 'header_linea') {
                    $headers_linea = $row;
                    $this->addLog("  ✓ HEADER LÍNEA (cols: " . count($row) . ")", 'info');
                    continue;
                }

                // Procesar
                if ($tipo == 'cartel' && $headers_cartel) {
                    $this->procesarFilaCartel($row, $headers_cartel);

                    if ($this->stats['carteles_detectados'] % 500 == 0) {
                        $this->addLog("  ⏳ Carteles: {$this->stats['carteles_detectados']}", 'progress');
                    }
                } elseif ($tipo == 'linea' && $headers_linea) {
                    $this->procesarFilaLineaPorSICOP($row, $headers_linea);

                    if ($this->stats['lineas_detectadas'] % 500 == 0) {
                        $this->addLog("  ⏳ Líneas: {$this->stats['lineas_detectadas']}", 'progress');
                    }
                }
            }

            $this->conn->commit();

            $this->addLog("✅ IMPORTACIÓN COMPLETADA", 'success');
            $this->addLog("  📊 Carteles: {$this->stats['carteles_detectados']} | Líneas: {$this->stats['lineas_detectadas']}", 'info');
            $this->addLog("  📊 Licitaciones: I:{$this->stats['licitaciones']['insertadas']} A:{$this->stats['licitaciones']['actualizadas']}", 'info');
            $this->addLog("  📊 Partidas: I:{$this->stats['partidas']['insertadas']} A:{$this->stats['partidas']['actualizadas']}", 'info');

        } catch (Exception $e) {
            $this->addLog("❌ Error: " . $e->getMessage(), 'error');
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
                $idx[strtolower(trim($header))] = $i;
            }

            $sicop = $this->val($row, $idx, 'número sicop') ?: $this->val($row, $idx, 'numero sicop');
            if (!$sicop) {
                $this->stats['licitaciones']['errores']++;
                return null;
            }

            $cedula_inst = $this->val($row, $idx, 'cédula institución') ?: $this->val($row, $idx, 'cedula institucion');
            $nro_proc = $this->val($row, $idx, 'número procedimiento') ?: $this->val($row, $idx, 'numero procedimiento');
            $tipo_proc = $this->val($row, $idx, 'tipo procedimiento');
            $modalidad = $this->val($row, $idx, 'modalidad procedimiento');
            $cod_excepcion = $this->val($row, $idx, 'excepción cd') ?: $this->val($row, $idx, 'excepcion cd');
            $desc_excepcion = $this->val($row, $idx, 'descripción') ?: $this->val($row, $idx, 'descripcion');
            $cod_unidad = $this->val($row, $idx, 'cod unidad compra');
            $nombre_unidad = $this->val($row, $idx, 'nombre unidad compra');
            $pago_pymes = $this->val($row, $idx, 'pago adelantado pymes');

            $fecha_pub = $this->convertirFechaHora($this->val($row, $idx, 'fecha publicación') ?: $this->val($row, $idx, 'fecha publicacion'));
            $fecha_inv = $this->convertirFechaHora($this->val($row, $idx, 'fecha invitación') ?: $this->val($row, $idx, 'fecha invitacion'));
            $fecha_inicio_rec = $this->convertirFechaHora($this->val($row, $idx, 'fecha inicio recepción') ?: $this->val($row, $idx, 'fecha inicio recepcion'));
            $fecha_cierre_rec = $this->convertirFechaHora($this->val($row, $idx, 'fecha cierre recepción') ?: $this->val($row, $idx, 'fecha cierre recepcion'));
            $fecha_apertura = $this->convertirFechaHora($this->val($row, $idx, 'fecha apertura'));

            $stmt = $this->conn->prepare("SELECT id FROM licitaciones WHERE numero_sicop = ?");
            $stmt->execute([$sicop]);
            $existe = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existe) {
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
            $this->errores_detallados[] = "Cartel " . ($sicop ?? '?') . ": " . $e->getMessage();
            return null;
        }
    }

    /**
     * ⭐ PROCESAR LÍNEA POR SICOP
     */
    private function procesarFilaLineaPorSICOP($row, $headers) {
        try {
            $this->stats['lineas_detectadas']++;

            $idx = [];
            foreach ($headers as $i => $header) {
                $idx[strtolower(trim($header))] = $i;
            }

            // SICOP en columna 0
            $sicop = trim($row[0] ?? '');
            if (!$sicop) {
                $this->stats['partidas']['errores']++;
                return;
            }

            $stmt = $this->conn->prepare("SELECT id FROM licitaciones WHERE numero_sicop = ?");
            $stmt->execute([$sicop]);
            $licitacion = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$licitacion) {
                $this->stats['partidas']['errores']++;
                $this->errores_detallados[] = "Licitación no encontrada: $sicop";
                return;
            }

            $licitacion_id = $licitacion['id'];

            // Columnas por ÍNDICE (no por nombre)
            $linea = intval(trim($row[1] ?? 0));
            $partida = intval(trim($row[2] ?? 0));
            $cantidad = $this->limpiarNumero($row[3] ?? null);
            $precio = $this->limpiarNumero($row[4] ?? null);
            $moneda = trim($row[5] ?? 'CRC');
            $tipo_cambio = $this->limpiarNumero($row[6] ?? null);
            $codigo = trim($row[7] ?? '');

            // Convertir notación científica en código
            if (stripos($codigo, 'E+') !== false || stripos($codigo, 'E-') !== false) {
                $codigo = sprintf('%.0f', floatval(str_replace(',', '.', $codigo)));
            }

            $monto_estimado = $cantidad && $precio ? $cantidad * $precio : null;

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
                $licitacion_id, $partida, $linea,
                $codigo, $cantidad, $precio,
                $moneda, $monto_estimado, $tipo_cambio
            ]);

            if ($stmt->rowCount() > 0) {
                $this->stats['partidas']['insertadas']++;
            }

        } catch (Exception $e) {
            $this->stats['partidas']['errores']++;
            $this->errores_detallados[] = "Línea SICOP " . ($sicop ?? '?') . " P:$partida L:$linea - " . $e->getMessage();
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

    /**
     * ═══════════════════════════════════════════════════════════════════════
     * IMPORTAR DETALLECARTELES (INFORMACIÓN ADICIONAL)
     * ═══════════════════════════════════════════════════════════════════════
     */
    public function importarDetalleCartelesAdicional($archivo) {
        $this->addLog("", 'separator');
        $this->addLog("📋 IMPORTANDO: DETALLECARTELES (INFO ADICIONAL)", 'title');
        $this->addLog("", 'separator');

        try {
            $data = $this->leerCSV($archivo);
            $header = array_shift($data);

            $this->addLog("📊 Total de registros: " . count($data), 'info');

            $indices = [];
            foreach ($header as $idx => $col) {
                $indices[trim($col)] = $idx;
            }

            $sql = "UPDATE licitaciones SET
                    tipo_procedimiento = COALESCE(:tipo_procedimiento, tipo_procedimiento),
                    modalidad = COALESCE(:modalidad, modalidad),
                    fecha_apertura_ofertas = COALESCE(:fecha_apertura, fecha_apertura_ofertas),
                    clasificacion_objeto = COALESCE(:clas_obj, clasificacion_objeto),
                    codigo_excepcion = COALESCE(:cod_excepcion, codigo_excepcion),
                    descripcion_excepcion = COALESCE(:des_excepcion, descripcion_excepcion),
                    presupuesto_estimado = COALESCE(:monto_est, presupuesto_estimado),
                    fecha_modificacion = COALESCE(:fecha_mod, fecha_modificacion)
                    WHERE numero_sicop = :numero_sicop";

            $stmt = $this->conn->prepare($sql);

            foreach ($data as $i => $row) {
                $this->stats['detalle_carteles']['procesadas']++;

                try {
                    $numero_sicop = $this->val($row, $indices, 'NRO_SICOP');

                    if (empty($numero_sicop)) {
                        $this->stats['detalle_carteles']['errores']++;
                        continue;
                    }

                    $fecha_apertura = null;
                    $fechah_apertura_raw = $this->val($row, $indices, 'FECHAH_APERTURA');
                    if (!empty($fechah_apertura_raw)) {
                        $fecha_apertura = $this->convertirFechaHora($fechah_apertura_raw);
                    }

                    $fecha_mod = null;
                    $fecha_mod_raw = $this->val($row, $indices, 'FECHA_MOD');
                    if (!empty($fecha_mod_raw)) {
                        $fecha_mod = $this->convertirFechaHora($fecha_mod_raw);
                    }

                    $monto_est = $this->limpiarNumero($this->val($row, $indices, 'MONTO_EST'));

                    $stmt->bindParam(':numero_sicop', $numero_sicop);
                    $stmt->bindParam(':tipo_procedimiento', $this->val($row, $indices, 'TIPO_PROCEDIMIENTO'));
                    $stmt->bindParam(':modalidad', $this->val($row, $indices, 'MODALIDAD_PROCEDIMIENTO'));
                    $stmt->bindParam(':fecha_apertura', $fecha_apertura);
                    $stmt->bindParam(':clas_obj', $this->val($row, $indices, 'CLAS_OBJ'));
                    $stmt->bindParam(':cod_excepcion', $this->val($row, $indices, 'COD_EXCEPCION'));
                    $stmt->bindParam(':des_excepcion', $this->val($row, $indices, 'DES_EXCEPCION'));
                    $stmt->bindParam(':monto_est', $monto_est);
                    $stmt->bindParam(':fecha_mod', $fecha_mod);

                    $stmt->execute();

                    if ($stmt->rowCount() > 0) {
                        $this->stats['detalle_carteles']['actualizadas']++;
                    }

                    if (($i + 1) % 100 == 0) {
                        $this->addLog("  ⏳ Procesadas " . ($i + 1) . " registros...", 'info');
                    }

                } catch (PDOException $e) {
                    $this->stats['detalle_carteles']['errores']++;
                    $this->addLog("  ❌ Error en SICOP {$numero_sicop}: " . $e->getMessage(), 'error');
                }
            }

            $this->addLog("✅ DetalleCarteles procesado", 'success');
            $this->addLog("  📊 Procesadas: " . $this->stats['detalle_carteles']['procesadas'], 'success');
            $this->addLog("  ✓ Actualizadas: " . $this->stats['detalle_carteles']['actualizadas'], 'success');

        } catch (Exception $e) {
            $this->addLog("❌ Error: " . $e->getMessage(), 'error');
            $this->stats['detalle_carteles']['errores']++;
        }
    }

// ═══════════════════════════════════════════════════════════════════════
// INTERFAZ
// ═══════════════════════════════════════════════════════════════════════

$imp = new ImportadorSICOPv14_3($conn);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['archivo'])) {
    header('Content-Type: text/html; charset=UTF-8');

    echo "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'><title>Importación SICOP v14.3</title>";
    echo "<style>
    body { font-family: -apple-system, system-ui; background: #f3f4f6; margin: 0; padding: 20px; }
    .container { max-width: 1200px; margin: 0 auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; padding: 40px; text-align: center; border-radius: 8px 8px 0 0; }
    .header h1 { margin: 0; font-size: 32px; }
    .header .version { opacity: 0.9; font-size: 14px; margin-top: 8px; }
    .content { padding: 30px; }
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin: 20px 0; }
    .stat-card { background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 20px; border-radius: 8px; color: #fff; }
    .stat-number { font-size: 36px; font-weight: bold; }
    .stat-label { font-size: 13px; opacity: 0.95; margin-top: 5px; }
    .stat-detail { font-size: 11px; margin-top: 8px; opacity: 0.9; }
    .log-container { background: #1e1e1e; border-radius: 8px; padding: 20px; max-height: 500px; overflow-y: auto; font-family: monospace; font-size: 13px; }
    .log-entry { padding: 6px 10px; margin: 3px 0; border-radius: 4px; }
    .log-title { background: #10b981; color: #fff; font-weight: bold; }
    .log-success { background: #10b981; color: #fff; }
    .log-error { background: #ef4444; color: #fff; }
    .log-warning { background: #f59e0b; color: #fff; }
    .log-info { color: #60a5fa; }
    .log-progress { color: #10b981; }
    .log-separator { height: 1px; background: #444; margin: 8px 0; }
    .btn { display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; text-decoration: none; border-radius: 6px; margin-top: 20px; }
    .errores { background: #fee; border-left: 3px solid #ef4444; padding: 15px; margin: 20px 0; border-radius: 4px; max-height: 300px; overflow-y: auto; }
    .errores h3 { color: #dc2626; margin: 0 0 10px 0; }
    .highlight { background: #d1fae5; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #10b981; }
    </style></head><body>";

    echo "<div class='container'>";
    echo "<div class='header'><h1>🚀 Importación SICOP v14.3</h1><p class='version'>Con soporte para DetalleCarteles</p></div>";
    echo "<div class='content'>";

    $archivo = $_FILES['archivo'];
    if ($archivo['error'] == 0) {
        $imp->importarDetalleCartelesNuevo($archivo['tmp_name']);
    }

    // Procesar archivo adicional (DetalleCarteles) si existe
    if (isset($_FILES['archivo_detalle']) && $_FILES['archivo_detalle']['error'] == 0) {
        $imp->importarDetalleCartelesAdicional($_FILES['archivo_detalle']['tmp_name']);
    }

    $stats = $imp->getStats();

    if ($stats['partidas']['insertadas'] > 0) {
        echo "<div class='highlight'>";
        echo "<h3 style='color: #059669; margin: 0 0 10px 0;'>✅ ¡ÉXITO! Partidas insertadas correctamente</h3>";
        echo "<p style='margin: 0;'>El sistema ahora SÍ detecta y procesa las líneas del archivo.</p>";
        echo "</div>";
    }

    echo "<h2>📊 Resultados</h2>";
    echo "<div class='stats-grid'>";

    foreach ($stats as $key => $val) {
        if (is_array($val)) {
            $total = ($val['insertadas'] ?? 0) + ($val['actualizadas'] ?? 0) + ($val['procesadas'] ?? 0);
            echo "<div class='stat-card'>";
            echo "<div class='stat-number'>$total</div>";
            echo "<div class='stat-label'>" . ucfirst(str_replace('_', ' ', $key)) . "</div>";

            // Mostrar detalle según campos disponibles
            if (isset($val['procesadas'])) {
                echo "<div class='stat-detail'>P:{$val['procesadas']} A:{$val['actualizadas']} E:{$val['errores']}</div>";
            } else {
                echo "<div class='stat-detail'>I:{$val['insertadas']} A:{$val['actualizadas']} E:{$val['errores']}</div>";
            }
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

    echo "<h2>📋 Log</h2>";
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

    $errores = $imp->getErrores();
    if (!empty($errores)) {
        echo "<div class='errores'>";
        echo "<h3>⚠️ Errores (" . count($errores) . ")</h3>";
        echo "<ul style='margin: 0; padding-left: 20px;'>";
        foreach (array_slice($errores, 0, 30) as $error) {
            echo "<li style='padding: 4px 0; font-size: 12px;'>$error</li>";
        }
        if (count($errores) > 30) {
            echo "<li><em>... y " . (count($errores) - 30) . " más</em></li>";
        }
        echo "</ul></div>";
    }

    echo "<div style='text-align: center;'>";
    echo "<a href='importador_sicop_v14_2.php' class='btn'>🔄 Nueva Importación</a>";
    echo "</div>";

    echo "</div></div></body></html>";

} else {
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Importador SICOP v14.3</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, system-ui;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #fff;
            padding: 40px;
            text-align: center;
        }
        .header h1 { font-size: 36px; margin-bottom: 8px; }
        .header .version { font-size: 14px; opacity: 0.95; }
        .content { padding: 40px; }
        .upload-area {
            border: 3px dashed #10b981;
            border-radius: 12px;
            padding: 50px 30px;
            text-align: center;
            background: #f9fafb;
            margin: 20px 0;
            cursor: pointer;
            transition: all 0.3s;
        }
        .upload-area:hover { border-color: #059669; background: #f3f4f6; }
        .upload-area h3 { font-size: 20px; color: #333; margin-bottom: 12px; }
        input[type="file"] { display: none; }
        .file-label {
            display: inline-block;
            padding: 12px 32px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #fff;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }
        .btn-submit:disabled { opacity: 0.5; cursor: not-allowed; }
        .highlight {
            background: #d1fae5;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #10b981;
        }
        .highlight h4 { color: #059669; margin-bottom: 10px; }
        .highlight ul { padding-left: 20px; color: #065f46; }
        .highlight li { padding: 4px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 SICOP v14.3</h1>
            <p class="version">Con soporte para DetalleCarteles</p>
        </div>

        <div class="content">
            <form method="POST" enctype="multipart/form-data">
                <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                    <h3>📁 Archivo 1: Detalle de Carteles (requerido)</h3>
                    <p style="color: #666; margin: 10px 0;">Archivo principal con licitaciones y partidas</p>
                    <label for="fileInput" class="file-label">Examinar Archivo</label>
                    <input type="file" name="archivo" id="fileInput" accept=".csv" required>
                </div>

                <div class="upload-area" onclick="document.getElementById('fileInputDetalle').click()" style="border-style: dashed;">
                    <h3>📋 Archivo 2: DetalleCarteles (opcional)</h3>
                    <p style="color: #666; margin: 10px 0;">Información adicional de licitaciones</p>
                    <label for="fileInputDetalle" class="file-label">Examinar Archivo (Opcional)</label>
                    <input type="file" name="archivo_detalle" id="fileInputDetalle" accept=".csv">
                </div>

                <button type="submit" class="btn-submit" id="submitBtn" disabled>
                    🚀 INICIAR IMPORTACIÓN
                </button>
            </form>

            <div class="highlight">
                <h4>✅ Novedades en v14.3</h4>
                <ul>
                    <li><strong>Soporte para 2 archivos:</strong> Importa "Detalle de Carteles" + "DetalleCarteles"</li>
                    <li><strong>Información adicional:</strong> Tipo de procedimiento, modalidad, excepciones, etc.</li>
                    <li><strong>Actualización inteligente:</strong> Solo actualiza campos vacíos (COALESCE)</li>
                    <li><strong>Detección automática:</strong> Identifica líneas por contenido</li>
                    <li><strong>Notación científica:</strong> Maneja códigos como 7,81E+15</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('fileInput').addEventListener('change', function() {
            document.getElementById('submitBtn').disabled = this.files.length === 0;
        });
    </script>
</body>
</html>
    <?php
}
?>
