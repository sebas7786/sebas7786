<?php
/**
 * ═══════════════════════════════════════════════════════════════════════
 * IMPORTADOR DE ACLARACIONES SICOP
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Importa el archivo "Aclaraciones.csv" del Observatorio SICOP
 *
 * Archivo CSV esperado: "Aclaraciones.csv"
 * Columnas del CSV:
 * 0 - Número Cartel (identificador SICOP)
 * 1 - No Cartel
 * 2 - Título
 * 3 - Fecha Solicitud
 * 4 - Número Aclaración
 * 5 - Solicitante
 * 6 - Cédula Empresa Proveedora
 * 7 - Estado Respuesta
 * 8 - Número Respuesta
 * 9 - Fecha Respuesta
 *
 * Uso desde el navegador:
 * http://tu-dominio.com/admin/importador_aclaraciones.php?archivo=Aclaraciones.csv
 */

// Configurar para mostrar errores durante importación
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Aumentar tiempo de ejecución para archivos grandes
set_time_limit(300); // 5 minutos

require_once '../config/db.php';

class ImportadorAclaraciones {
    private $conn;
    private $stats = [
        'insertadas' => 0,
        'actualizadas' => 0,
        'errores' => 0,
        'total_filas' => 0
    ];

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Procesa el archivo CSV de aclaraciones
     */
    public function importar($archivo_csv) {
        echo "═══════════════════════════════════════════════════════════════════════\n";
        echo "IMPORTADOR DE ACLARACIONES SICOP\n";
        echo "═══════════════════════════════════════════════════════════════════════\n\n";

        // Verificar que el archivo existe
        if (!file_exists($archivo_csv)) {
            die("❌ ERROR: El archivo '$archivo_csv' no existe.\n");
        }

        echo "📂 Archivo: " . basename($archivo_csv) . "\n";
        echo "📏 Tamaño: " . number_format(filesize($archivo_csv) / 1024, 2) . " KB\n\n";

        // Crear tabla si no existe
        $this->crearTabla();

        // Abrir archivo CSV
        $handle = fopen($archivo_csv, 'r');
        if (!$handle) {
            die("❌ ERROR: No se pudo abrir el archivo CSV.\n");
        }

        // Detectar encoding
        $primera_linea = fgets($handle);
        rewind($handle);

        $encoding = mb_detect_encoding($primera_linea, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        echo "🔤 Encoding detectado: " . ($encoding ?: 'UTF-8') . "\n\n";

        // Leer header
        $header = fgetcsv($handle, 0, ',');
        if (!$header) {
            die("❌ ERROR: No se pudo leer el encabezado del CSV.\n");
        }

        // Convertir encoding si es necesario
        if ($encoding && $encoding !== 'UTF-8') {
            $header = array_map(function($val) use ($encoding) {
                return mb_convert_encoding($val, 'UTF-8', $encoding);
            }, $header);
        }

        echo "📋 Columnas detectadas (" . count($header) . "):\n";
        foreach ($header as $i => $col) {
            echo "   [$i] " . trim($col) . "\n";
        }
        echo "\n";

        // Preparar statement de inserción
        $stmt = $this->prepararStatement();

        // Procesar filas
        echo "⏳ Procesando registros...\n\n";
        $fila_num = 1; // Ya leímos el header

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $fila_num++;
            $this->stats['total_filas']++;

            // Convertir encoding si es necesario
            if ($encoding && $encoding !== 'UTF-8') {
                $row = array_map(function($val) use ($encoding) {
                    return mb_convert_encoding($val, 'UTF-8', $encoding);
                }, $row);
            }

            // Procesar la fila
            $resultado = $this->procesarFila($row, $stmt, $fila_num);

            // Mostrar progreso cada 100 filas
            if ($this->stats['total_filas'] % 100 === 0) {
                echo "   Procesadas: " . $this->stats['total_filas'] . " | ";
                echo "Insertadas: " . $this->stats['insertadas'] . " | ";
                echo "Actualizadas: " . $this->stats['actualizadas'] . " | ";
                echo "Errores: " . $this->stats['errores'] . "\n";
            }
        }

        fclose($handle);

        // Mostrar resumen final
        $this->mostrarResumen();
    }

    /**
     * Crea la tabla si no existe
     */
    private function crearTabla() {
        $sql = "CREATE TABLE IF NOT EXISTS `aclaraciones_licitacion` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `numero_cartel` VARCHAR(20) NOT NULL COMMENT 'Número SICOP del cartel',
            `no_cartel` VARCHAR(100) NULL COMMENT 'Número de cartel descriptivo',
            `titulo` TEXT NULL COMMENT 'Título de la licitación',
            `fecha_solicitud` DATETIME NULL COMMENT 'Fecha en que se hizo la solicitud de aclaración',
            `numero_aclaracion` VARCHAR(50) NULL COMMENT 'Número identificador de la aclaración',
            `solicitante` VARCHAR(255) NULL COMMENT 'Nombre o identificación del solicitante',
            `cedula_empresa_proveedora` VARCHAR(20) NULL COMMENT 'Cédula de la empresa que solicita',
            `estado_respuesta` VARCHAR(50) NULL COMMENT 'Estado de la respuesta (Respondida, Pendiente, etc)',
            `numero_respuesta` VARCHAR(50) NULL COMMENT 'Número de la respuesta',
            `fecha_respuesta` DATETIME NULL COMMENT 'Fecha en que se respondió la aclaración',
            `fecha_importacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha en que se importó este registro',
            `actualizado` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_numero_cartel` (`numero_cartel`),
            INDEX `idx_cedula_empresa` (`cedula_empresa_proveedora`),
            INDEX `idx_estado_respuesta` (`estado_respuesta`),
            INDEX `idx_fecha_solicitud` (`fecha_solicitud`),
            INDEX `idx_numero_aclaracion` (`numero_aclaracion`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='Solicitudes de aclaraciones y respuestas de licitaciones SICOP'";

        try {
            $this->conn->exec($sql);
            echo "✅ Tabla 'aclaraciones_licitacion' verificada/creada\n\n";
        } catch (PDOException $e) {
            die("❌ ERROR al crear tabla: " . $e->getMessage() . "\n");
        }
    }

    /**
     * Prepara el statement SQL para inserción
     */
    private function prepararStatement() {
        $sql = "INSERT INTO aclaraciones_licitacion (
                    numero_cartel,
                    no_cartel,
                    titulo,
                    fecha_solicitud,
                    numero_aclaracion,
                    solicitante,
                    cedula_empresa_proveedora,
                    estado_respuesta,
                    numero_respuesta,
                    fecha_respuesta
                ) VALUES (
                    :numero_cartel,
                    :no_cartel,
                    :titulo,
                    :fecha_solicitud,
                    :numero_aclaracion,
                    :solicitante,
                    :cedula_empresa_proveedora,
                    :estado_respuesta,
                    :numero_respuesta,
                    :fecha_respuesta
                )
                ON DUPLICATE KEY UPDATE
                    titulo = VALUES(titulo),
                    fecha_solicitud = VALUES(fecha_solicitud),
                    estado_respuesta = VALUES(estado_respuesta),
                    numero_respuesta = VALUES(numero_respuesta),
                    fecha_respuesta = VALUES(fecha_respuesta)";

        return $this->conn->prepare($sql);
    }

    /**
     * Procesa una fila del CSV
     */
    private function procesarFila($row, $stmt, $fila_num) {
        try {
            // Extraer y limpiar datos
            $numero_cartel = $this->limpiar($row[0] ?? '');
            $no_cartel = $this->limpiar($row[1] ?? '');
            $titulo = $this->limpiar($row[2] ?? '');
            $fecha_solicitud = $this->parsearFecha($row[3] ?? '');
            $numero_aclaracion = $this->limpiar($row[4] ?? '');
            $solicitante = $this->limpiar($row[5] ?? '');
            $cedula_empresa = $this->limpiar($row[6] ?? '');
            $estado_respuesta = $this->limpiar($row[7] ?? '');
            $numero_respuesta = $this->limpiar($row[8] ?? '');
            $fecha_respuesta = $this->parsearFecha($row[9] ?? '');

            // Validar que tenga al menos numero_cartel y numero_aclaracion
            if (empty($numero_cartel) || empty($numero_aclaracion)) {
                $this->stats['errores']++;
                return false;
            }

            // Bind parameters
            $stmt->bindParam(':numero_cartel', $numero_cartel);
            $stmt->bindParam(':no_cartel', $no_cartel);
            $stmt->bindParam(':titulo', $titulo);
            $stmt->bindParam(':fecha_solicitud', $fecha_solicitud);
            $stmt->bindParam(':numero_aclaracion', $numero_aclaracion);
            $stmt->bindParam(':solicitante', $solicitante);
            $stmt->bindParam(':cedula_empresa_proveedora', $cedula_empresa);
            $stmt->bindParam(':estado_respuesta', $estado_respuesta);
            $stmt->bindParam(':numero_respuesta', $numero_respuesta);
            $stmt->bindParam(':fecha_respuesta', $fecha_respuesta);

            // Ejecutar
            $resultado = $stmt->execute();

            if ($resultado) {
                if ($stmt->rowCount() > 0) {
                    // Si rowCount es 1 = INSERT, si es 2 = UPDATE
                    if ($stmt->rowCount() === 1) {
                        $this->stats['insertadas']++;
                    } else {
                        $this->stats['actualizadas']++;
                    }
                }
                return true;
            } else {
                $this->stats['errores']++;
                return false;
            }

        } catch (PDOException $e) {
            $this->stats['errores']++;
            echo "❌ Error en fila $fila_num: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * Limpia y normaliza un valor de texto
     */
    private function limpiar($valor) {
        $valor = trim($valor);
        return empty($valor) ? null : $valor;
    }

    /**
     * Parsea una fecha del CSV al formato MySQL
     */
    private function parsearFecha($fecha_str) {
        $fecha_str = trim($fecha_str);
        if (empty($fecha_str)) {
            return null;
        }

        // Intentar varios formatos comunes de fecha
        $formatos = [
            'd/m/Y H:i:s',  // 15/11/2025 14:30:00
            'd/m/Y H:i',    // 15/11/2025 14:30
            'd/m/Y',        // 15/11/2025
            'Y-m-d H:i:s',  // 2025-11-15 14:30:00
            'Y-m-d H:i',    // 2025-11-15 14:30
            'Y-m-d',        // 2025-11-15
            'd-m-Y H:i:s',  // 15-11-2025 14:30:00
            'd-m-Y H:i',    // 15-11-2025 14:30
            'd-m-Y'         // 15-11-2025
        ];

        foreach ($formatos as $formato) {
            $dt = DateTime::createFromFormat($formato, $fecha_str);
            if ($dt !== false) {
                return $dt->format('Y-m-d H:i:s');
            }
        }

        // Si no se pudo parsear, devolver null
        return null;
    }

    /**
     * Muestra resumen de la importación
     */
    private function mostrarResumen() {
        echo "\n";
        echo "═══════════════════════════════════════════════════════════════════════\n";
        echo "RESUMEN DE IMPORTACIÓN\n";
        echo "═══════════════════════════════════════════════════════════════════════\n\n";

        echo "📊 Total de filas procesadas: " . $this->stats['total_filas'] . "\n";
        echo "✅ Aclaraciones insertadas:   " . $this->stats['insertadas'] . "\n";
        echo "🔄 Aclaraciones actualizadas: " . $this->stats['actualizadas'] . "\n";
        echo "❌ Errores:                   " . $this->stats['errores'] . "\n\n";

        // Estadísticas de la tabla
        try {
            $stmt = $this->conn->query("
                SELECT
                    COUNT(*) as total,
                    COUNT(DISTINCT numero_cartel) as licitaciones_con_aclaraciones,
                    COUNT(CASE WHEN estado_respuesta = 'Respondida' THEN 1 END) as respondidas,
                    COUNT(CASE WHEN estado_respuesta != 'Respondida' OR estado_respuesta IS NULL THEN 1 END) as pendientes
                FROM aclaraciones_licitacion
            ");
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            echo "📈 ESTADÍSTICAS DE LA BASE DE DATOS:\n";
            echo "   Total aclaraciones:            " . number_format($stats['total']) . "\n";
            echo "   Licitaciones con aclaraciones: " . number_format($stats['licitaciones_con_aclaraciones']) . "\n";
            echo "   Respondidas:                   " . number_format($stats['respondidas']) . "\n";
            echo "   Pendientes:                    " . number_format($stats['pendientes']) . "\n\n";

        } catch (PDOException $e) {
            echo "⚠️  No se pudieron obtener estadísticas: " . $e->getMessage() . "\n\n";
        }

        echo "✅ Importación completada!\n";
        echo "═══════════════════════════════════════════════════════════════════════\n";
    }

    /**
     * Obtiene las estadísticas de importación
     */
    public function getStats() {
        return $this->stats;
    }
}

// ═══════════════════════════════════════════════════════════════════════
// EJECUCIÓN
// ═══════════════════════════════════════════════════════════════════════

// Determinar si se ejecuta desde CLI o navegador
$es_cli = (php_sapi_name() === 'cli');

if (!$es_cli) {
    header('Content-Type: text/plain; charset=utf-8');
}

// Obtener nombre del archivo
$archivo_csv = null;

if ($es_cli) {
    // Desde línea de comandos
    if ($argc < 2) {
        echo "Uso: php importador_aclaraciones.php <archivo.csv>\n";
        echo "Ejemplo: php importador_aclaraciones.php Aclaraciones.csv\n";
        exit(1);
    }
    $archivo_csv = $argv[1];
} else {
    // Desde navegador
    if (isset($_GET['archivo'])) {
        $archivo_csv = $_GET['archivo'];
        // Buscar archivo en diferentes ubicaciones
        if (!file_exists($archivo_csv)) {
            $archivo_csv = '../' . $_GET['archivo'];
        }
        if (!file_exists($archivo_csv)) {
            $archivo_csv = dirname(__DIR__) . '/' . $_GET['archivo'];
        }
    } else {
        echo "╔════════════════════════════════════════════════════════════════════╗\n";
        echo "║         IMPORTADOR DE ACLARACIONES SICOP                          ║\n";
        echo "╚════════════════════════════════════════════════════════════════════╝\n\n";
        echo "Uso: importador_aclaraciones.php?archivo=Aclaraciones.csv\n\n";
        echo "Pasos:\n";
        echo "1. Descarga el archivo 'Aclaraciones.csv' del Observatorio SICOP\n";
        echo "2. Súbelo al servidor (a la carpeta raíz o /admin/)\n";
        echo "3. Accede a esta URL con el parámetro 'archivo'\n\n";
        echo "Ejemplo:\n";
        echo "https://licitacionesya.com/admin/importador_aclaraciones.php?archivo=Aclaraciones.csv\n\n";
        exit(1);
    }
}

// Ejecutar importación
try {
    $importador = new ImportadorAclaraciones($conn);
    $importador->importar($archivo_csv);
} catch (Exception $e) {
    echo "\n❌ ERROR FATAL: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
