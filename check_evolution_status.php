<?php
// Script para verificar el estado de la instancia en Evolution API
// y probar la conexión saliente.

$baseUrl = 'https://evolution.neox.site';
$instanceName = 'app-php';
$apikey = 'CCA5FF063C1C-463D-AC45-F264614A1D4A'; // Instance Token
$globalKey = '8CUSCumwgEJ43ydsEz1QB2kWNhxloneE'; // Global API Key

function makeRequest($url, $apiKey)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'apikey: ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    return ['code' => $httpCode, 'response' => $response, 'error' => $error];
}

echo "<h2>Diagnóstico de Conexión Evolution API</h2>";

// 1. Verificar estado de conexión de la instancia
$urlState = "$baseUrl/instance/connectionState/$instanceName";
echo "<p><strong>1. Verificando estado de la instancia ($instanceName)...</strong></p>";
$result = makeRequest($urlState, $apikey);

if ($result['code'] == 200) {
    $stateData = json_decode($result['response'], true);
    echo "<pre>" . json_encode($stateData, JSON_PRETTY_PRINT) . "</pre>";

    if (isset($stateData['instance']['state']) && $stateData['instance']['state'] === 'open') {
        echo "<p style='color:green'>✅ La instancia está CONECTADA y lista.</p>";
    } else {
        echo "<p style='color:orange'>⚠️ La instancia existe pero no parece estar conectada (State: " . ($stateData['instance']['state'] ?? 'Desconocido') . "). Escanea el QR.</p>";
    }
} else {
    echo "<p style='color:red'>❌ Error al conectar con Evolution API: " . $result['code'] . "</p>";
    echo "Respuesta: " . $result['response'];
    echo "<br>Error: " . $result['error'];
}

// 2. Verificar configuración del Webhook (si es posible leerla)
// Nota: No siempre se puede leer la config del webhook vía API fácilmente sin la Global Key
echo "<hr><p><strong>2. Verificando Webhook configurado...</strong></p>";
$urlWebhook = "$baseUrl/webhook/find/$instanceName";
$resultWebhook = makeRequest($urlWebhook, $globalKey); // Usamos Global Key aquí

if ($resultWebhook['code'] == 200) {
    $webhookData = json_decode($resultWebhook['response'], true);
    echo "<pre>" . json_encode($webhookData, JSON_PRETTY_PRINT) . "</pre>";

    if (isset($webhookData['webhook']['url'])) {
        $currentUrl = $webhookData['webhook']['url'];
        echo "<p>URL Actual del Webhook: <strong>$currentUrl</strong></p>";

        $expectedUrl = 'https://chatbot.neox.site/webhook_whatsapp.php';
        if ($currentUrl === $expectedUrl) {
            echo "<p style='color:green'>✅ La URL del webhook coincide.</p>";
        } else {
            echo "<p style='color:red'>❌ La URL del webhook NO coincide.</p>";
            echo "<p>Debería ser: <strong>$expectedUrl</strong></p>";
        }

        if (isset($webhookData['webhook']['events']) && in_array('MESSAGES_UPSERT', $webhookData['webhook']['events'])) {
            echo "<p style='color:green'>✅ El evento MESSAGES_UPSERT está activado.</p>";
        } else {
            echo "<p style='color:red'>❌ El evento MESSAGES_UPSERT NO está activado.</p>";
        }
    } else {
        echo "<p>No se encontró configuración de webhook activa.</p>";
    }
} else {
    echo "<p>No se pudo leer la configuración del webhook (posiblemente falta permisos o endpoint diferente).</p>";
    echo "Código: " . $resultWebhook['code'];
}

?>