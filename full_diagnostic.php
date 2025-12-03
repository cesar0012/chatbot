<?php
// Script de diagnóstico completo para el webhook

echo "<h1>Diagnóstico Completo del Webhook</h1>";

// 1. Verificar que el archivo webhook existe y es accesible
echo "<h2>1. Verificación de Archivos</h2>";
$webhookFile = 'webhook_whatsapp.php';
if (file_exists($webhookFile)) {
    echo "<p style='color:green'>✅ El archivo webhook_whatsapp.php existe</p>";
    echo "<p>Permisos: " . substr(sprintf('%o', fileperms($webhookFile)), -4) . "</p>";
} else {
    echo "<p style='color:red'>❌ El archivo webhook_whatsapp.php NO existe</p>";
}

// 2. Verificar el log de debug
echo "<h2>2. Contenido del Log de Debug</h2>";
$logFile = 'debug_log.txt';
if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    $logLines = explode("------------------", $logContent);
    $count = count($logLines) - 1; // Restar 1 porque el último puede estar vacío

    echo "<p>Total de peticiones registradas: <strong>$count</strong></p>";

    if ($count > 0) {
        echo "<h3>Últimas 3 peticiones:</h3>";
        $lastThree = array_slice($logLines, -4, 3); // Tomar las últimas 3
        foreach ($lastThree as $entry) {
            if (trim($entry)) {
                echo "<div style='background:#f5f5f5; padding:10px; margin:10px 0; border-left:3px solid #007bff;'>";
                echo "<pre>" . htmlspecialchars($entry) . "</pre>";
                echo "</div>";
            }
        }
    } else {
        echo "<p style='color:orange'>⚠️ No hay peticiones registradas todavía</p>";
    }
} else {
    echo "<p style='color:red'>❌ El archivo debug_log.txt NO existe</p>";
    echo "<p>Esto significa que el webhook nunca ha recibido ninguna petición.</p>";
}

// 3. Verificar configuración de Evolution API
echo "<h2>3. Configuración en Evolution API</h2>";
$baseUrl = 'https://evolution.neox.site';
$globalKey = '8CUSCumwgEJ43ydsEz1QB2kWNhxloneE';
$instanceName = 'Chatbot';

$url = "$baseUrl/webhook/find/$instanceName";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'apikey: ' . $globalKey
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $data = json_decode($response, true);
    if (isset($data['webhook'])) {
        $webhook = $data['webhook'];
        echo "<table border='1' cellpadding='10' style='border-collapse:collapse;'>";
        echo "<tr><th>Propiedad</th><th>Valor</th><th>Estado</th></tr>";

        // URL
        $currentUrl = $webhook['url'] ?? 'No definida';
        $expectedUrl = 'https://chatbot.neox.site/webhook_whatsapp.php';
        $urlStatus = ($currentUrl === $expectedUrl) ? "✅" : "❌";
        echo "<tr><td>URL</td><td>$currentUrl</td><td>$urlStatus</td></tr>";

        // Enabled
        $enabled = $webhook['enabled'] ?? false;
        $enabledText = $enabled ? 'Sí' : 'No';
        $enabledStatus = $enabled ? "✅" : "❌";
        echo "<tr><td>Habilitado</td><td>$enabledText</td><td>$enabledStatus</td></tr>";

        // Events
        $events = $webhook['events'] ?? [];
        $eventsText = implode(', ', $events);
        $hasMessagesUpsert = in_array('MESSAGES_UPSERT', $events);
        $eventsStatus = $hasMessagesUpsert ? "✅" : "❌";
        echo "<tr><td>Eventos</td><td>$eventsText</td><td>$eventsStatus</td></tr>";

        echo "</table>";

        // Diagnóstico
        echo "<h3>Diagnóstico:</h3>";
        if ($currentUrl !== $expectedUrl) {
            echo "<p style='color:red'>❌ La URL del webhook es incorrecta. Ejecuta set_webhook.php para corregirla.</p>";
        }
        if (!$enabled) {
            echo "<p style='color:red'>❌ El webhook está DESHABILITADO. Ejecuta set_webhook.php para habilitarlo.</p>";
        }
        if (!$hasMessagesUpsert) {
            echo "<p style='color:red'>❌ El evento MESSAGES_UPSERT no está configurado. Ejecuta set_webhook.php.</p>";
        }
        if ($currentUrl === $expectedUrl && $enabled && $hasMessagesUpsert) {
            echo "<p style='color:green'>✅ La configuración del webhook parece correcta.</p>";
            echo "<p><strong>Si aún no recibes mensajes, el problema es de conectividad (Cloudflare/Firewall).</strong></p>";
        }
    } else {
        echo "<p style='color:red'>❌ No se encontró configuración de webhook.</p>";
    }
} else {
    echo "<p style='color:red'>❌ Error al consultar Evolution API (HTTP $httpCode)</p>";
}

// 4. Test de conectividad
echo "<h2>4. Test de Conectividad</h2>";
echo "<p>Probando si el webhook es accesible desde internet...</p>";

$testUrl = 'https://chatbot.neox.site/webhook_whatsapp.php';
$ch = curl_init($testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_NOBODY, true); // Solo HEAD request
curl_setopt($ch, CURLOPT_TIMEOUT, 5);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    echo "<p style='color:green'>✅ El webhook es accesible desde internet (HTTP 200)</p>";
} else {
    echo "<p style='color:orange'>⚠️ El webhook respondió con código HTTP $httpCode</p>";
    echo "<p>Esto es normal si el webhook solo acepta POST con datos específicos.</p>";
}

// 5. Recomendaciones
echo "<h2>5. Próximos Pasos</h2>";
echo "<ol>";
echo "<li>Si la configuración es correcta pero no hay peticiones en el log, el problema es <strong>Cloudflare o Firewall</strong>.</li>";
echo "<li>Verifica en Cloudflare: Security > Bots > Desactiva 'Bot Fight Mode'</li>";
echo "<li>O crea una regla de firewall para permitir peticiones de Evolution API</li>";
echo "<li>Prueba enviando un mensaje desde <strong>OTRO teléfono</strong> (no desde el mismo número del bot)</li>";
echo "<li>Revisa los logs de Evolution API para ver si está intentando enviar peticiones</li>";
echo "</ol>";
?>