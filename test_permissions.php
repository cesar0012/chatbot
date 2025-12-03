<?php
echo "<h1>Prueba de Permisos de Escritura</h1>";

$files = ['debug_log_waha.txt', 'process_log_waha.txt'];
$dir = __DIR__;

echo "<p>Directorio actual: $dir</p>";
echo "<p>Usuario PHP: " . get_current_user() . "</p>";

foreach ($files as $file) {
    echo "<hr><h3>Probando archivo: $file</h3>";

    // Intentar crear/escribir
    $result = @file_put_contents($file, "Prueba de escritura: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

    if ($result === false) {
        echo "<p style='color:red'>❌ ERROR: No se pudo escribir en el archivo.</p>";
        $error = error_get_last();
        echo "<p>Detalle del error: " . ($error['message'] ?? 'Desconocido') . "</p>";

        // Intentar ver permisos
        if (file_exists($file)) {
            echo "<p>Permisos actuales: " . substr(sprintf('%o', fileperms($file)), -4) . "</p>";
        } else {
            echo "<p>El archivo no existe y no se pudo crear.</p>";
        }
    } else {
        echo "<p style='color:green'>✅ ÉXITO: Se escribieron $result bytes.</p>";
        echo "<p>Permisos actuales: " . substr(sprintf('%o', fileperms($file)), -4) . "</p>";
    }
}
?>