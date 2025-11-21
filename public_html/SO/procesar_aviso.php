<?php
require_once 'config.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: nuevo_aviso.php');
    exit;
}

// Verificar token CSRF
if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
    die('Error de seguridad: Token CSRF inválido.');
}

// Inicializar array de errores
$errores = [];
$mensajeExito = '';

try {
    // Validar y limpiar datos del formulario

    // 1. Información personal
    $nombre_completo = limpiarEntrada($_POST['nombre_completo'] ?? '');
    $cedula = limpiarEntrada($_POST['cedula'] ?? '');
    $puesto = limpiarEntrada($_POST['puesto'] ?? '');
    $departamento = limpiarEntrada($_POST['departamento'] ?? '');
    $supervisor_inmediato = limpiarEntrada($_POST['supervisor_inmediato'] ?? '');
    $fecha_incidente = $_POST['fecha_incidente'] ?? '';
    $hora_incidente = $_POST['hora_incidente'] ?? '';

    // Validaciones básicas
    if (empty($nombre_completo)) $errores[] = "El nombre completo es requerido.";
    if (empty($cedula)) $errores[] = "La cédula es requerida.";
    if (empty($puesto)) $errores[] = "El puesto es requerido.";
    if (empty($departamento)) $errores[] = "El departamento es requerido.";
    if (empty($supervisor_inmediato)) $errores[] = "El supervisor inmediato es requerido.";
    if (empty($fecha_incidente)) $errores[] = "La fecha del incidente es requerida.";
    if (empty($hora_incidente)) $errores[] = "La hora del incidente es requerida.";

    // 2. Información del lugar
    $lugar_exacto = limpiarEntrada($_POST['lugar_exacto'] ?? '');
    if (empty($lugar_exacto)) $errores[] = "El lugar exacto es requerido.";

    // 3. Actividad que realizaba
    $actividad_realizaba = limpiarEntrada($_POST['actividad_realizaba'] ?? '');
    if (empty($actividad_realizaba)) $errores[] = "La actividad que realizaba es requerida.";

    // 4. Descripción del accidente
    $descripcion_accidente = limpiarEntrada($_POST['descripcion_accidente'] ?? '');
    if (empty($descripcion_accidente)) $errores[] = "La descripción del accidente es requerida.";

    // 5. Partes del cuerpo afectadas
    $partes_cuerpo = $_POST['partes_cuerpo'] ?? [];
    if (empty($partes_cuerpo)) {
        $errores[] = "Debe seleccionar al menos una parte del cuerpo afectada.";
    }
    $partes_cuerpo_json = json_encode($partes_cuerpo);
    $otra_parte_cuerpo = limpiarEntrada($_POST['otra_parte_cuerpo'] ?? '');

    // 6. Tipo de lesión
    $tipo_lesion = limpiarEntrada($_POST['tipo_lesion'] ?? '');
    if (empty($tipo_lesion)) $errores[] = "El tipo de lesión es requerido.";
    $otra_lesion = limpiarEntrada($_POST['otra_lesion'] ?? '');

    // 7. Atención inmediata
    $recibio_atencion = $_POST['recibio_atencion'] ?? '';
    if (empty($recibio_atencion)) $errores[] = "Debe indicar si recibió atención inmediata.";
    $descripcion_atencion = limpiarEntrada($_POST['descripcion_atencion'] ?? '');

    // 8. Testigos
    $hubo_testigos = $_POST['hubo_testigos'] ?? '';
    if (empty($hubo_testigos)) $errores[] = "Debe indicar si hubo testigos.";
    $testigo_1 = limpiarEntrada($_POST['testigo_1'] ?? '');
    $testigo_2 = limpiarEntrada($_POST['testigo_2'] ?? '');

    // 9. Declaración del afectado
    $declaracion_afectado = limpiarEntrada($_POST['declaracion_afectado'] ?? '');
    if (empty($declaracion_afectado)) $errores[] = "La declaración del afectado es requerida.";

    // 10. Procesar archivos de evidencias
    $foto_lesion_nombre = null;
    $foto_area_nombre = null;

    if (isset($_FILES['foto_lesion']) && $_FILES['foto_lesion']['error'] !== UPLOAD_ERR_NO_FILE) {
        $resultado_lesion = guardarImagen($_FILES['foto_lesion'], 'lesion');
        if ($resultado_lesion['exito']) {
            $foto_lesion_nombre = $resultado_lesion['ruta'];
        } else {
            $errores[] = "Foto de lesión: " . $resultado_lesion['mensaje'];
        }
    }

    if (isset($_FILES['foto_area']) && $_FILES['foto_area']['error'] !== UPLOAD_ERR_NO_FILE) {
        $resultado_area = guardarImagen($_FILES['foto_area'], 'area');
        if ($resultado_area['exito']) {
            $foto_area_nombre = $resultado_area['ruta'];
        } else {
            $errores[] = "Foto del área: " . $resultado_area['mensaje'];
        }
    }

    // 11. Confirmación
    if (!isset($_POST['confirmo_veracidad'])) {
        $errores[] = "Debe confirmar la veracidad de la información.";
    }

    $fecha_llenado = date('Y-m-d H:i:s');

    // Si hay errores, mostrarlos
    if (!empty($errores)) {
        $_SESSION['errores'] = $errores;
        $_SESSION['form_data'] = $_POST;
        header('Location: nuevo_aviso.php');
        exit;
    }

    // Insertar en la base de datos
    $sql = "INSERT INTO avisos_accidentes (
        nombre_completo,
        cedula,
        puesto,
        departamento,
        supervisor_inmediato,
        fecha_incidente,
        hora_incidente,
        lugar_exacto,
        actividad_realizaba,
        descripcion_accidente,
        partes_cuerpo_afectadas,
        otra_parte_cuerpo,
        tipo_lesion,
        otra_lesion,
        recibio_atencion_inmediata,
        descripcion_atencion,
        hubo_testigos,
        testigo_1,
        testigo_2,
        declaracion_afectado,
        foto_lesion,
        foto_area,
        fecha_llenado,
        estado
    ) VALUES (
        :nombre_completo,
        :cedula,
        :puesto,
        :departamento,
        :supervisor_inmediato,
        :fecha_incidente,
        :hora_incidente,
        :lugar_exacto,
        :actividad_realizaba,
        :descripcion_accidente,
        :partes_cuerpo_afectadas,
        :otra_parte_cuerpo,
        :tipo_lesion,
        :otra_lesion,
        :recibio_atencion_inmediata,
        :descripcion_atencion,
        :hubo_testigos,
        :testigo_1,
        :testigo_2,
        :declaracion_afectado,
        :foto_lesion,
        :foto_area,
        :fecha_llenado,
        'pendiente'
    )";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':nombre_completo' => $nombre_completo,
        ':cedula' => $cedula,
        ':puesto' => $puesto,
        ':departamento' => $departamento,
        ':supervisor_inmediato' => $supervisor_inmediato,
        ':fecha_incidente' => $fecha_incidente,
        ':hora_incidente' => $hora_incidente,
        ':lugar_exacto' => $lugar_exacto,
        ':actividad_realizaba' => $actividad_realizaba,
        ':descripcion_accidente' => $descripcion_accidente,
        ':partes_cuerpo_afectadas' => $partes_cuerpo_json,
        ':otra_parte_cuerpo' => $otra_parte_cuerpo,
        ':tipo_lesion' => $tipo_lesion,
        ':otra_lesion' => $otra_lesion,
        ':recibio_atencion_inmediata' => $recibio_atencion,
        ':descripcion_atencion' => $descripcion_atencion,
        ':hubo_testigos' => $hubo_testigos,
        ':testigo_1' => $testigo_1,
        ':testigo_2' => $testigo_2,
        ':declaracion_afectado' => $declaracion_afectado,
        ':foto_lesion' => $foto_lesion_nombre,
        ':foto_area' => $foto_area_nombre,
        ':fecha_llenado' => $fecha_llenado
    ]);

    $aviso_id = $pdo->lastInsertId();

    // Registrar seguimiento
    $sql_seguimiento = "INSERT INTO avisos_accidentes_seguimiento (aviso_id, usuario, accion, comentario)
                        VALUES (:aviso_id, :usuario, 'Creado', 'Aviso de accidente creado por el afectado')";
    $stmt_seguimiento = $pdo->prepare($sql_seguimiento);
    $stmt_seguimiento->execute([
        ':aviso_id' => $aviso_id,
        ':usuario' => $nombre_completo
    ]);

    // Redirigir a página de éxito
    $_SESSION['mensaje_exito'] = "El aviso de accidente ha sido registrado exitosamente. Número de aviso: " . $aviso_id;
    header('Location: exito.php?id=' . $aviso_id);
    exit;

} catch (PDOException $e) {
    error_log("Error al guardar aviso de accidente: " . $e->getMessage());
    $_SESSION['errores'] = ["Error al guardar el aviso: " . $e->getMessage()];
    header('Location: nuevo_aviso.php');
    exit;
} catch (Exception $e) {
    error_log("Error general: " . $e->getMessage());
    $_SESSION['errores'] = ["Error inesperado: " . $e->getMessage()];
    header('Location: nuevo_aviso.php');
    exit;
}
?>
