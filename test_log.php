<?php
// Archivo de prueba para verificar permisos de escritura y acceso
error_reporting(E_ALL);
ini_set('display_errors', 1);

$file = 'debug_test.txt';
$content = "Prueba de escritura exitosa: " . date('Y-m-d H:i:s');

if (file_put_contents($file, $content)) {
    echo "ÉXITO: Se ha creado el archivo '$file' correctamente.<br>";
    echo "Contenido: $content";
} else {
    echo "ERROR: No se pudo escribir en el archivo '$file'.<br>";
    echo "Verifica los permisos de la carpeta.";
}
?>