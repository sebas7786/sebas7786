<?php
/**
 * ═══════════════════════════════════════════════════════════════════════
 * FIX PARA DASHBOARD - LICITACIONES ADJUDICADAS
 * ═══════════════════════════════════════════════════════════════════════
 *
 * INSTRUCCIONES:
 * 1. Abre tu archivo dashboard.php
 * 2. Busca la función "calcularEstadoReal"
 * 3. Reemplázala con la versión de abajo
 * 4. Busca donde haces la consulta SQL principal
 * 5. Reemplaza con la consulta mejorada de abajo
 */

// ═══════════════════════════════════════════════════════════════════════
// PASO 1: REEMPLAZAR LA FUNCIÓN calcularEstadoReal
// ═══════════════════════════════════════════════════════════════════════
?>
// Busca esta función en tu dashboard.php (alrededor de la línea 40-80):
// function calcularEstadoReal($licitacion) {
//
// Y REEMPLÁZALA con esta versión:

function calcularEstadoReal($licitacion) {
    global $pdo;

    $fecha_actual = new DateTime();

    // MÉTODO 1: Verificar si tiene registros en lineas_adjudicadas
    if (isset($pdo) && !empty($licitacion['numero_sicop'])) {
        try {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as tiene_adjudicacion
                FROM lineas_adjudicadas
                WHERE numero_sicop = ?
                LIMIT 1
            ");
            $stmt->execute([$licitacion['numero_sicop']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && $result['tiene_adjudicacion'] > 0) {
                return 'adjudicada';
            }
        } catch (PDOException $e) {
            // Si hay error (tabla no existe, etc.), continuar con otros métodos
        }
    }

    // MÉTODO 2: Verificar fecha_adjudicacion
    if (!empty($licitacion['fecha_adjudicacion'])) {
        return 'adjudicada';
    }

    // MÉTODO 3: Verificar estado en BD
    $estado_lower = isset($licitacion['estado']) ? strtolower(trim($licitacion['estado'])) : '';

    if ($estado_lower === 'adjudicada') {
        return 'adjudicada';
    }

    // MÉTODO 4: Calcular basado en fecha de cierre
    $fecha_cierre = null;
    if (!empty($licitacion['fecha_cierre_recepcion'])) {
        $fecha_cierre = $licitacion['fecha_cierre_recepcion'];
    } elseif (!empty($licitacion['fecha_cierre_ofertas'])) {
        $fecha_cierre = $licitacion['fecha_cierre_ofertas'];
    } elseif (!empty($licitacion['fecha_cierre'])) {
        $fecha_cierre = $licitacion['fecha_cierre'];
    }

    if (empty($fecha_cierre)) {
        return $estado_lower ?: 'abierta';
    }

    try {
        $dt_cierre = new DateTime($fecha_cierre);

        if ($fecha_actual > $dt_cierre) {
            return 'cerrada';
        } else {
            return 'abierta';
        }
    } catch (Exception $e) {
        return $estado_lower ?: 'abierta';
    }
}

<?php
// ═══════════════════════════════════════════════════════════════════════
// PASO 2: HACER $pdo ACCESIBLE GLOBALMENTE
// ═══════════════════════════════════════════════════════════════════════
?>

// Busca estas líneas en tu dashboard.php (alrededor de la línea 300-320):
//
// try {
//     require_once __DIR__ . '/../config/db.php';
//
//     if (!isset($pdo) && isset($conn)) {
//         $pdo = $conn;
//     }
// }
//
// Y AGREGA JUSTO DESPUÉS:

    // Hacer $pdo accesible globalmente para la función calcularEstadoReal
    global $pdo;

<?php
// ═══════════════════════════════════════════════════════════════════════
// PASO 3 (OPCIONAL): CONSULTA SQL MEJORADA CON LEFT JOIN
// ═══════════════════════════════════════════════════════════════════════
?>

// Si quieres mejorar el rendimiento, puedes hacer el JOIN directamente en la consulta SQL.
// Busca esta línea en tu dashboard.php (alrededor de la línea 350-380):
//
// $sql = "SELECT DISTINCT l.id, l.*, ic.nombre_institucion, ic.provincia as zona_geografica
//         FROM licitaciones l
//         LEFT JOIN instituciones_compradoras ic ON l.cedula_institucion = ic.cedula";
//
// Y REEMPLÁZALA con:

$sql = "SELECT DISTINCT l.id, l.*, ic.nombre_institucion, ic.provincia as zona_geografica,
        CASE
            WHEN la.numero_sicop IS NOT NULL THEN 1
            ELSE 0
        END as tiene_adjudicacion
        FROM licitaciones l
        LEFT JOIN instituciones_compradoras ic ON l.cedula_institucion = ic.cedula
        LEFT JOIN (
            SELECT DISTINCT numero_sicop
            FROM lineas_adjudicadas
        ) la ON l.numero_sicop = la.numero_sicop";

// Luego, en la función calcularEstadoReal, AGREGA al inicio (antes del MÉTODO 1):

    // MÉTODO 0: Si ya viene precalculado del SQL
    if (isset($licitacion['tiene_adjudicacion']) && $licitacion['tiene_adjudicacion'] == 1) {
        return 'adjudicada';
    }

<?php
// ═══════════════════════════════════════════════════════════════════════
// RESUMEN DE CAMBIOS
// ═══════════════════════════════════════════════════════════════════════
/*

CAMBIO 1: Función calcularEstadoReal mejorada
- Ahora verifica la tabla lineas_adjudicadas
- Hace un SELECT COUNT para ver si hay adjudicaciones
- Si encuentra registros, marca como 'adjudicada'

CAMBIO 2: Variable $pdo global
- Permite que la función acceda a la conexión de BD
- Necesario para hacer la consulta a lineas_adjudicadas

CAMBIO 3 (Opcional): JOIN en la consulta principal
- Mejora el rendimiento
- Evita hacer 1 consulta por cada licitación
- Precalcula si tiene adjudicación

IMPORTANTE:
- Si ejecutas el SQL de actualización (actualizar_licitaciones_adjudicadas.sql),
  NO necesitas estos cambios en el dashboard
- Si prefieres NO modificar la tabla, usa estos cambios en el dashboard

*/
?>
