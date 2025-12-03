<?php
echo "<h2>Log de Procesamiento del Webhook</h2>";

$logFile = 'process_log.txt';

if (file_exists($logFile)) {
    $content = file_get_contents($logFile);
    if (empty($content)) {
        echo "<p>El archivo existe pero está vacío.</p>";
    } else {
        echo "<textarea style='width:100%; height:500px; font-family:monospace;'>" . htmlspecialchars($content) . "</textarea>";
        echo "<hr>";
        echo "<p><a href='?clear=1'>Limpiar log</a></p>";

        if (isset($_GET['clear'])) {
            file_put_contents($logFile, '');
            echo "<p style='color:green'>Log limpiado.</p>";
            echo "<meta http-equiv='refresh' content='1'>";
        }
    }
} else {
    echo "<p style='color:red'>El archivo process_log.txt NO existe todavía.</p>";
    echo "<p>Se creará cuando el webhook procese su primer mensaje.</p>";
}
?>