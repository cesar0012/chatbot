<?php
echo "<h2>Log de Procesamiento de Waha (desde /tmp)</h2>";

$logFile = '/tmp/process_log_waha.txt';
$debugFile = '/tmp/debug_log_waha.txt';

echo "<h3>Últimos eventos (Process Log)</h3>";
if (file_exists($logFile)) {
    $content = file_get_contents($logFile);
    if (empty($content)) {
        echo "<p>El archivo existe pero está vacío.</p>";
    } else {
        echo "<textarea style='width:100%; height:300px; font-family:monospace; background:#f0f0f0;'>" . htmlspecialchars($content) . "</textarea>";
    }
} else {
    echo "<p style='color:red'>El archivo $logFile NO existe todavía.</p>";
}

echo "<h3>Payloads Recibidos (Debug Log)</h3>";
if (file_exists($debugFile)) {
    $content = file_get_contents($debugFile);
    if (empty($content)) {
        echo "<p>El archivo existe pero está vacío.</p>";
    } else {
        echo "<textarea style='width:100%; height:300px; font-family:monospace; background:#e0e0e0;'>" . htmlspecialchars($content) . "</textarea>";
    }
} else {
    echo "<p style='color:red'>El archivo $debugFile NO existe todavía.</p>";
}

echo "<hr>";
echo "<p><a href='?clear=1'>Limpiar logs</a></p>";

if (isset($_GET['clear'])) {
    file_put_contents($logFile, '');
    file_put_contents($debugFile, '');
    echo "<p style='color:green'>Logs limpiados.</p>";
    echo "<meta http-equiv='refresh' content='1;url=read_waha_log.php'>";
}
?>