<?php
// Script para CONFIGURAR el webhook de la instancia 'Chatbot'

$baseUrl = 'https://evolution.neox.site';
$globalKey = '8CUSCumwgEJ43ydsEz1QB2kWNhxloneE';
$instanceName = 'Chatbot';
$webhookUrl = 'https://neoxygen2.com/chatbot/webhook_whatsapp.php';

echo "<h2>Configurando Webhook para '$instanceName'...</h2>";

$url = "$baseUrl/webhook/set/$instanceName";

// Estructura corregida según el error "instance requires property 'webhook'"
$data = [
    "webhook" => [
        "url" => $webhookUrl,
        "webhookByEvents" => false,
        "webhookBase64" => false,
        "events" => [
            "MESSAGES_UPSERT"
        ],
        "enabled" => true
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'apikey: ' . $globalKey
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "<p style='color:red'>Error: $error</p>";
} else {
    echo "<p>Código HTTP: $httpCode</p>";
    echo "<pre>" . json_encode(json_decode($response), JSON_PRETTY_PRINT) . "</pre>";

    if ($httpCode == 200 || $httpCode == 201) {
        echo "<p style='color:green'>¡Webhook configurado con éxito!</p>";
        echo "<p>URL: <strong>$webhookUrl</strong></p>";
        echo "<p>Eventos: MESSAGES_UPSERT</p>";
        echo "<hr>";
        echo "<p><strong>¡Ahora envía un mensaje de WhatsApp para probar!</strong></p>";
    }
}
?>