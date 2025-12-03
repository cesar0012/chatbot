<?php
// Script para configurar Waha directamente por API (sin usar el Dashboard)

$wahaUrl = 'https://waha.neox.site';
$apiKey = 'MiClaveSecreta2024'; // Cambia esto por tu WHATSAPP_API_KEY
$sessionName = 'default';

echo "<h1>Configuración de Waha por API</h1>";

// 1. Crear sesión
echo "<h2>1. Crear Sesión</h2>";
$url = "$wahaUrl/api/sessions/start";
$data = [
    'name' => $sessionName,
    'config' => [
        'webhooks' => [
            [
                'url' => 'https://chatbot.neox.site/webhook_waha.php',
                'events' => ['message']
            ]
        ]
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Api-Key: ' . $apiKey
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>Código HTTP: $httpCode</p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

if ($httpCode == 201 || $httpCode == 200) {
    echo "<p style='color:green'>✅ Sesión creada exitosamente!</p>";

    // 2. Obtener QR
    echo "<h2>2. Código QR para Escanear</h2>";
    $qrUrl = "$wahaUrl/api/$sessionName/auth/qr";

    $ch = curl_init($qrUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-Api-Key: ' . $apiKey
    ]);

    $qrResponse = curl_exec($ch);
    $qrHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($qrHttpCode == 200) {
        $qrData = json_decode($qrResponse, true);
        if (isset($qrData['qr'])) {
            echo "<p>Escanea este código QR con WhatsApp:</p>";
            echo "<img src='" . $qrData['qr'] . "' alt='QR Code' style='max-width:400px;'>";
            echo "<p><small>Ve a WhatsApp > Configuración > Dispositivos vinculados > Vincular un dispositivo</small></p>";
        } else {
            echo "<p>Respuesta del QR:</p>";
            echo "<pre>" . htmlspecialchars($qrResponse) . "</pre>";
        }
    } else {
        echo "<p style='color:orange'>⚠️ No se pudo obtener el QR (HTTP $qrHttpCode)</p>";
        echo "<pre>" . htmlspecialchars($qrResponse) . "</pre>";
    }
} else {
    echo "<p style='color:red'>❌ Error al crear sesión</p>";
    if ($httpCode == 401) {
        echo "<p><strong>Error 401: API Key incorrecta.</strong></p>";
        echo "<p>Verifica que la variable WHATSAPP_API_KEY en Coolify sea: <code>$apiKey</code></p>";
    }
}

// 3. Verificar estado de la sesión
echo "<hr><h2>3. Estado de la Sesión</h2>";
$statusUrl = "$wahaUrl/api/sessions";

$ch = curl_init($statusUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-Api-Key: ' . $apiKey
]);

$statusResponse = curl_exec($ch);
$statusHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>Código HTTP: $statusHttpCode</p>";
echo "<pre>" . htmlspecialchars($statusResponse) . "</pre>";
?>