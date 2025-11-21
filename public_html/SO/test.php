<?php
// Archivo de prueba simple
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "PHP está funcionando correctamente.<br>";
echo "Versión de PHP: " . phpversion() . "<br>";
echo "Directorio actual: " . __DIR__ . "<br>";

phpinfo();
?>
