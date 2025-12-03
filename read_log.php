<?php
// Script para leer el archivo de log de depuración
$logFile = 'debug_log.txt';

echo "<h2>Contenido de debug_log.txt</h2>";

if (file_exists($logFile)) {
    $content = file_get_contents($logFile);
    if (empty($content)) {
        echo "<p>El archivo existe pero está vacío.</p>";
    } else {
        echo "<textarea style='width:100%; height:500px; font-family:monospace;'>" . htmlspecialchars($content) . "</textarea>";
    }
} else {
    echo "<p style='color:red'>El archivo debug_log.txt NO existe.</p>";
    echo "<p>Esto significa que el webhook no ha recibido ninguna petición todavía (o no tiene permisos para crear el archivo).</p>";
}
?>