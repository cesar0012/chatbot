<?php
// Script para configurar Waha directamente por API

$wahaUrl = 'https://waha.neox.site';
$apiKey = 'MiClaveSecreta2024';
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

    // 2. Mostrar QR
    echo "<h2>2. Código QR para Escanear</h2>";
    echo "<p>Escanea este código QR con WhatsApp:</p>";

    // La URL directa de la imagen del QR
    $qrImageUrl = "$wahaUrl/api/$sessionName/auth/qr";
    echo "<img src='$qrImageUrl' alt='QR Code' style='max-width:400px; border:2px solid #333; padding:10px; background:white;'>";

    echo "<p><small>Ve a WhatsApp > Configuración > Dispositivos vinculados > Vincular un dispositivo</small></p>";

    echo "<hr>";
    echo "<p><strong>Si el QR no se muestra correctamente:</strong></p>";
    echo "<p>1. Ve al dashboard de Waha: <a href='https://waha.neox.site' target='_blank'>https://waha.neox.site</a></p>";
    echo "<p>2. Busca la sesión 'default' y escanea el QR desde ahí</p>";

} else {
    echo "<p style='color:red'>❌ Error al crear sesión</p>";
    if ($httpCode == 401) {
        echo "<p><strong>Error 401: API Key incorrecta.</strong></p>";
        echo "<p>Verifica que la variable WHATSAPP_API_KEY en Coolify sea: <code>$apiKey</code></p>";
    } elseif ($httpCode == 409) {
        echo "<p style='color:orange'>⚠️ La sesión ya existe. Ve al paso 2 para ver el QR.</p>";

        echo "<h2>2. Código QR para Escanear</h2>";
        $qrImageUrl = "$wahaUrl/api/$sessionName/auth/qr";
        echo "<img src='$qrImageUrl' alt='QR Code' style='max-width:400px; border:2px solid #333; padding:10px; background:white;'>";
        echo "<p><small>Ve a WhatsApp > Configuración > Dispositivos vinculados > Vincular un dispositivo</small></p>";
    }
}

// 3. Verificar estado
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

if ($statusHttpCode == 200) {
    $sessions = json_decode($statusResponse, true);
    if (!empty($sessions)) {
        foreach ($sessions as $session) {
            if ($session['name'] == $sessionName) {
                $status = $session['status'] ?? 'UNKNOWN';
                echo "<p>Estado de la sesión '$sessionName': <strong>$status</strong></p>";
                if ($status == 'WORKING') {
                    echo "<p style='color:green'>✅ ¡WhatsApp conectado! Ya puedes enviar mensajes.</p>";
                }
            }
        }
    }
}
?>