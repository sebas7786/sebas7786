<?php
// Función mejorada para calcular el estado REAL de la licitación
function calcularEstadoReal($licitacion) {
    $fecha_actual = new DateTime();

    // 1. Verificar si está adjudicada
    if (!empty($licitacion['fecha_adjudicacion']) || strtolower($licitacion['estado']) === 'adjudicada') {
        return 'adjudicada';
    }

    // 2. Obtener fecha de cierre
    $fecha_cierre = null;
    if (!empty($licitacion['fecha_cierre_recepcion'])) {
        $fecha_cierre = $licitacion['fecha_cierre_recepcion'];
    } elseif (!empty($licitacion['fecha_cierre_ofertas'])) {
        $fecha_cierre = $licitacion['fecha_cierre_ofertas'];
    } elseif (!empty($licitacion['fecha_cierre'])) {
        $fecha_cierre = $licitacion['fecha_cierre'];
    }

    // 3. Si no tiene fecha de cierre, considerar estado de BD
    if (empty($fecha_cierre)) {
        return strtolower($licitacion['estado']) === 'cerrada' ? 'cerrada' : 'abierta';
    }

    // 4. Comparar fecha de cierre con fecha actual
    try {
        $dt_cierre = new DateTime($fecha_cierre);

        if ($fecha_actual > $dt_cierre) {
            return 'cerrada';
        } else {
            return 'abierta';
        }
    } catch (Exception $e) {
        return strtolower($licitacion['estado']) ?? 'abierta';
    }
}

// Función mejorada para formatear montos
function formatMontoMejorado($monto) {
    if (empty($monto) || !is_numeric($monto)) {
        return null;
    }

    $monto = floatval($monto);

    // Corregir valores multiplicados por 1,000,000
    if ($monto > 100000000000) {
        $monto = $monto / 1000000;
    }

    // Formato con millones
    if ($monto >= 1000000) {
        return '₡' . number_format($monto / 1000000, 2, '.', ',') . 'M';
    }

    return '₡' . number_format($monto, 2, '.', ',');
}

// Función para obtener clase CSS según monto
function getMontoClase($monto) {
    if (empty($monto)) return '';

    $monto = floatval($monto);
    if ($monto > 100000000000) {
        $monto = $monto / 1000000;
    }

    if ($monto >= 100000000) return 'monto-alto'; // ₡100M+
    if ($monto >= 10000000) return 'monto-medio'; // ₡10M+
    return 'monto-bajo'; // < ₡10M
}
?>
