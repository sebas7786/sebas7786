<?php
/**
 * ═══════════════════════════════════════════════════════════════════════
 * GENERADOR DE HASH DE CONTRASEÑA
 * ═══════════════════════════════════════════════════════════════════════
 * Genera el hash bcrypt para la contraseña del administrador
 * ═══════════════════════════════════════════════════════════════════════
 */

// Contraseña a hashear
$password = 'Sebas778';

// Generar hash usando bcrypt (PASSWORD_DEFAULT usa bcrypt)
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "═══════════════════════════════════════════════════════════════════════\n";
echo "HASH GENERADO PARA LA CONTRASEÑA\n";
echo "═══════════════════════════════════════════════════════════════════════\n\n";
echo "Contraseña original: $password\n\n";
echo "Hash generado:\n";
echo "$hash\n\n";
echo "═══════════════════════════════════════════════════════════════════════\n";
echo "COPIA ESTE HASH Y ÚSALO EN EL SCRIPT SQL\n";
echo "═══════════════════════════════════════════════════════════════════════\n\n";

// Verificar que el hash funciona
if (password_verify($password, $hash)) {
    echo "✅ VERIFICACIÓN: El hash es correcto y coincide con la contraseña\n\n";
} else {
    echo "❌ ERROR: El hash no coincide con la contraseña\n\n";
}

// SQL listo para copiar
echo "═══════════════════════════════════════════════════════════════════════\n";
echo "SQL LISTO PARA COPIAR:\n";
echo "═══════════════════════════════════════════════════════════════════════\n\n";

$sql = "INSERT INTO usuarios (
    nombre,
    email,
    correo,
    contrasena,
    es_admin,
    estado_pago,
    recibir_alertas_email,
    frecuencia_alertas,
    tiene_perfil_directorio,
    fecha_creacion
) VALUES (
    'Administrador',
    'sebas@licitacionesya.com',
    'sebas@licitacionesya.com',
    '$hash',
    1,
    1,
    1,
    'inmediata',
    0,
    NOW()
) ON DUPLICATE KEY UPDATE
    nombre = 'Administrador',
    contrasena = '$hash',
    es_admin = 1,
    estado_pago = 1;";

echo $sql . "\n\n";

echo "═══════════════════════════════════════════════════════════════════════\n";
?>
