<?php
// Verificar configuración de sesión en Waha

$wahaUrl = 'https://waha.neox.site';
$apiKey = 'MiClaveSecreta2024';
$sessionName = 'default';

$url = "$wahaUrl/api/sessions/$sessionName";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-Api-Key: ' . $apiKey
]);

$response = curl_exec($ch);
curl_close($ch);

echo "<h2>Configuración de Sesión Waha</h2>";
$data = json_decode($response, true);

if (isset($data['config'])) {
    echo "<pre>" . json_encode($data['config'], JSON_PRETTY_PRINT) . "</pre>";

    // Verificar URL del webhook
    $webhooks = $data['config']['webhooks'] ?? [];
    if (!empty($webhooks)) {
        foreach ($webhooks as $wh) {
            echo "<p>Webhook URL: <strong>" . $wh['url'] . "</strong></p>";
            echo "<p>Eventos: " . implode(', ', $wh['events']) . "</p>";
        }
    } else {
        echo "<p style='color:red'>❌ No hay webhooks configurados</p>";
    }
} else {
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}
?>