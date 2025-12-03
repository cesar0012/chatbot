<?php
// Script para configurar webhook con URL interna (Docker/Coolify)

$baseUrl = 'https://evolution.neox.site';
$globalKey = '8CUSCumwgEJ43ydsEz1QB2kWNhxloneE';
$instanceName = 'Chatbot';

echo "<h2>Configuración de Webhook para Docker/Coolify</h2>";

// Opciones de URL para probar (basadas en tu configuración de Coolify - Puerto 2000)
$webhookUrls = [
    'Localhost con puerto 2000 (RECOMENDADO)' => 'http://localhost:2000/webhook_whatsapp.php',
    'Interna con puerto 2000' => 'http://chatbot.neox.site:2000/webhook_whatsapp.php',
    'Externa HTTPS (actual)' => 'https://chatbot.neox.site/webhook_whatsapp.php',
    'Localhost sin puerto' => 'http://localhost/webhook_whatsapp.php',
];

echo "<p><strong>Tu aplicación está en el puerto 2000 según Coolify.</strong></p>";
echo "<p>Prueba primero la opción 'Localhost con puerto 2000'.</p>";
echo "<form method='post'>";
echo "<select name='webhook_url' style='padding:10px; font-size:14px;'>";
foreach ($webhookUrls as $label => $url) {
    echo "<option value='$url'>$label: $url</option>";
}
echo "</select><br><br>";
echo "<button type='submit' style='padding:10px 20px; font-size:14px;'>Configurar Webhook</button>";
echo "</form>";

if (isset($_POST['webhook_url'])) {
    $webhookUrl = $_POST['webhook_url'];

    echo "<hr><h3>Configurando webhook con URL: <code>$webhookUrl</code></h3>";

    $url = "$baseUrl/webhook/set/$instanceName";

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
    curl_close($ch);

    echo "<p>Código HTTP: <strong>$httpCode</strong></p>";
    echo "<pre>" . json_encode(json_decode($response), JSON_PRETTY_PRINT) . "</pre>";

    if ($httpCode == 200 || $httpCode == 201) {
        echo "<p style='color:green; font-size:18px;'>✅ Webhook configurado exitosamente!</p>";
        echo "<p><strong>Ahora envía un mensaje de WhatsApp desde OTRO teléfono y revisa:</strong></p>";
        echo "<p><a href='diagnostic.php' target='_blank'>Ver Diagnóstico Completo</a></p>";
    } else {
        echo "<p style='color:red;'>❌ Error al configurar. Prueba otra URL.</p>";
    }
}
?>