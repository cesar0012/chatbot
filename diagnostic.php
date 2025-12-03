<?php
// Script todo-en-uno para diagnóstico y visualización de logs

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Diagnóstico Webhook</title>";
echo "<style>body{font-family:Arial;padding:20px;} .section{background:#f5f5f5;padding:15px;margin:15px 0;border-left:4px solid #007bff;} pre{background:#fff;padding:10px;overflow:auto;max-height:300px;} .success{color:green;} .error{color:red;} .warning{color:orange;}</style>";
echo "</head><body>";

echo "<h1>🔍 Diagnóstico Completo del Webhook</h1>";

// 1. Debug Log
echo "<div class='section'>";
echo "<h2>1. Log de Debug (Peticiones Recibidas)</h2>";
if (file_exists('debug_log.txt')) {
    $debugContent = file_get_contents('debug_log.txt');
    $entries = explode("------------------", $debugContent);
    $count = count(array_filter($entries, 'trim'));
    echo "<p>Total de peticiones: <strong>$count</strong></p>";
    if ($count > 0) {
        echo "<h3>Últimas 3 peticiones:</h3>";
        $last = array_slice(array_filter($entries, 'trim'), -3);
        foreach ($last as $entry) {
            echo "<pre>" . htmlspecialchars($entry) . "</pre>";
        }
    } else {
        echo "<p class='warning'>⚠️ No hay peticiones registradas</p>";
    }
} else {
    echo "<p class='error'>❌ debug_log.txt no existe - El webhook nunca ha recibido peticiones</p>";
}
echo "</div>";

// 2. Process Log
echo "<div class='section'>";
echo "<h2>2. Log de Procesamiento</h2>";
if (file_exists('process_log.txt')) {
    $processContent = file_get_contents('process_log.txt');
    echo "<pre>" . htmlspecialchars($processContent) . "</pre>";
} else {
    echo "<p class='warning'>⚠️ process_log.txt no existe todavía</p>";
}
echo "</div>";

// 3. Configuración de Evolution
echo "<div class='section'>";
echo "<h2>3. Configuración en Evolution API</h2>";
$baseUrl = 'https://evolution.neox.site';
$globalKey = '8CUSCumwgEJ43ydsEz1QB2kWNhxloneE';
$instanceName = 'Chatbot';

$ch = curl_init("$baseUrl/webhook/find/$instanceName");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'apikey: ' . $globalKey]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $data = json_decode($response, true);
    if (isset($data['webhook'])) {
        $webhook = $data['webhook'];
        $expectedUrl = 'https://chatbot.neox.site/webhook_whatsapp.php';

        echo "<table border='1' cellpadding='8' style='border-collapse:collapse;'>";
        echo "<tr><th>Propiedad</th><th>Valor</th><th>Estado</th></tr>";

        $currentUrl = $webhook['url'] ?? 'No definida';
        $urlOk = ($currentUrl === $expectedUrl);
        echo "<tr><td>URL</td><td>$currentUrl</td><td>" . ($urlOk ? "✅" : "❌") . "</td></tr>";

        $enabled = $webhook['enabled'] ?? false;
        echo "<tr><td>Habilitado</td><td>" . ($enabled ? 'Sí' : 'No') . "</td><td>" . ($enabled ? "✅" : "❌") . "</td></tr>";

        $events = $webhook['events'] ?? [];
        $hasEvent = in_array('MESSAGES_UPSERT', $events);
        echo "<tr><td>Eventos</td><td>" . implode(', ', $events) . "</td><td>" . ($hasEvent ? "✅" : "❌") . "</td></tr>";
        echo "</table>";

        if (!$urlOk || !$enabled || !$hasEvent) {
            echo "<p class='error'>❌ Configuración incorrecta. <a href='set_webhook.php'>Haz clic aquí para reconfigurar</a></p>";
        } else {
            echo "<p class='success'>✅ Configuración correcta</p>";
        }
    }
} else {
    echo "<p class='error'>❌ Error consultando Evolution API (HTTP $httpCode)</p>";
}
echo "</div>";

// 4. Diagnóstico
echo "<div class='section'>";
echo "<h2>4. Diagnóstico</h2>";
$debugExists = file_exists('debug_log.txt');
$hasEntries = false;
if ($debugExists) {
    $content = file_get_contents('debug_log.txt');
    $hasEntries = !empty(trim($content));
}

if (!$debugExists || !$hasEntries) {
    echo "<p class='error'><strong>PROBLEMA PRINCIPAL: El webhook NO está recibiendo peticiones de Evolution API</strong></p>";
    echo "<p>Posibles causas:</p>";
    echo "<ul>";
    echo "<li>Cloudflare está bloqueando las peticiones (Bot Fight Mode activo)</li>";
    echo "<li>Firewall del servidor bloqueando peticiones POST externas</li>";
    echo "<li>La URL del webhook en Evolution API es incorrecta</li>";
    echo "<li>El webhook no está habilitado en Evolution API</li>";
    echo "</ul>";
    echo "<p><strong>Soluciones:</strong></p>";
    echo "<ol>";
    echo "<li>Verifica Cloudflare: Security > Bots > Desactiva 'Bot Fight Mode'</li>";
    echo "<li>Ejecuta <a href='set_webhook.php'>set_webhook.php</a> para reconfigurar</li>";
    echo "<li>Contacta a tu proveedor de hosting para verificar firewall</li>";
    echo "</ol>";
} else {
    echo "<p class='success'>✅ El webhook SÍ está recibiendo peticiones</p>";
    echo "<p>Revisa el log de procesamiento arriba para ver por qué no responde.</p>";
}
echo "</div>";

echo "</body></html>";
?>