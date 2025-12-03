<?php
// Script para cambiar la URL del Webhook en Waha

$wahaUrl = 'https://waha.neox.site';
$apiKey = 'MiClaveSecreta2024';
$sessionName = 'default';

echo "<h1>Configurar Webhook de Waha</h1>";

// Opciones de URL
$options = [
    'External (Actual)' => 'https://chatbot.neox.site/webhook_waha.php',
    'Internal Docker (Opción A)' => 'http://chatbot/webhook_waha.php',
    'Internal Docker (Opción B)' => 'http://chatbot:80/webhook_waha.php',
    'Internal Host (Opción C)' => 'http://host.docker.internal:2000/webhook_waha.php',
    'Internal IP (Opción D)' => 'http://172.17.0.1:2000/webhook_waha.php'
];

if (isset($_POST['webhook_url'])) {
    $newUrl = $_POST['webhook_url'];
    echo "<h2>Configurando: $newUrl ...</h2>";

    $url = "$wahaUrl/api/sessions/$sessionName";
    $data = [
        'config' => [
            'webhooks' => [
                [
                    'url' => $newUrl,
                    'events' => ['message']
                ]
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH'); // Usamos PATCH para actualizar
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

    if ($httpCode == 200) {
        echo "<p style='color:green'>✅ Webhook actualizado correctamente.</p>";
        echo "<p><strong>¡Ahora envía un mensaje de prueba a WhatsApp!</strong></p>";
    } else {
        echo "<p style='color:red'>❌ Error al actualizar.</p>";
    }
    echo "<hr>";
}

echo "<form method='post'>";
echo "<p>Selecciona una URL para probar:</p>";
echo "<select name='webhook_url' style='padding:10px; width:100%; max-width:500px;'>";
foreach ($options as $label => $val) {
    echo "<option value='$val'>$label - $val</option>";
}
echo "</select>";
echo "<br><br>";
echo "<button type='submit' style='padding:10px 20px; background:#007bff; color:white; border:none; border-radius:5px; cursor:pointer;'>Actualizar Webhook</button>";
echo "</form>";

// Mostrar configuración actual
echo "<h3>Configuración Actual en Waha:</h3>";
$ch = curl_init("$wahaUrl/api/sessions/$sessionName");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Api-Key: ' . $apiKey]);
$currentConfig = curl_exec($ch);
curl_close($ch);
$data = json_decode($currentConfig, true);
$currentWebhook = $data['config']['webhooks'][0]['url'] ?? 'No configurado';
echo "<p>URL Actual: <strong>$currentWebhook</strong></p>";
?>