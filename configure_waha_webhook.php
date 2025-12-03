<?php
// Script para cambiar la URL del Webhook en Waha (Deteniendo sesión)

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
    'Internal IP (Opción D)' => 'http://172.17.0.1:2000/webhook_waha.php',
    'Custom URL' => 'custom'
];

if (isset($_POST['webhook_url'])) {
    $newUrl = $_POST['webhook_url'];
    if ($newUrl === 'custom') {
        $newUrl = $_POST['custom_url'];
    }

    echo "<h2>Configurando: $newUrl ...</h2>";

    // 1. Detener sesión
    echo "<p>1. Deteniendo sesión...</p>";
    $ch = curl_init("$wahaUrl/api/sessions/$sessionName/stop");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Api-Key: ' . $apiKey]);
    curl_exec($ch);
    curl_close($ch);
    sleep(2);

    // 2. Eliminar sesión
    echo "<p>2. Eliminando sesión para reconfigurar...</p>";
    $ch = curl_init("$wahaUrl/api/sessions/$sessionName");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Api-Key: ' . $apiKey]);
    curl_exec($ch);
    curl_close($ch);
    sleep(2);

    // 3. Crear nueva sesión
    echo "<p>3. Creando sesión con nuevo webhook...</p>";
    $data = [
        'name' => $sessionName,
        'config' => [
            'webhooks' => [
                [
                    'url' => $newUrl,
                    'events' => ['message']
                ]
            ]
        ]
    ];

    $ch = curl_init("$wahaUrl/api/sessions/start");
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
        echo "<p style='color:green'>✅ Webhook actualizado y sesión reiniciada.</p>";
        echo "<p><strong>IMPORTANTE: Es posible que necesites escanear el QR de nuevo.</strong></p>";
        echo "<p><a href='setup_waha.php' target='_blank' style='background:green; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Ir a escanear QR</a></p>";
    } else {
        echo "<p style='color:red'>❌ Error al actualizar.</p>";
    }
    echo "<hr>";
}

echo "<form method='post' id='configForm'>";
echo "<p>Selecciona una URL para probar:</p>";
echo "<select name='webhook_url' id='webhook_url' onchange='toggleCustom()' style='padding:10px; width:100%; max-width:500px;'>";
foreach ($options as $label => $val) {
    echo "<option value='$val'>$label - $val</option>";
}
echo "</select>";

echo "<div id='customInput' style='display:none; margin-top:10px;'>";
echo "<p>Ingresa tu URL personalizada (ej: URL de Coolify):</p>";
echo "<input type='text' name='custom_url' placeholder='https://tu-app.coolify.app/webhook_waha.php' style='padding:10px; width:100%; max-width:500px;'>";
echo "</div>";

echo "<br><br>";
echo "<button type='submit' style='padding:10px 20px; background:#007bff; color:white; border:none; border-radius:5px; cursor:pointer;'>Actualizar Webhook</button>";
echo "</form>";

echo "<script>
function toggleCustom() {
    var select = document.getElementById('webhook_url');
    var customInput = document.getElementById('customInput');
    if (select.value === 'custom') {
        customInput.style.display = 'block';
    } else {
        customInput.style.display = 'none';
    }
}
</script>";

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