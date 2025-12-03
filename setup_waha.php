<?php
// Script para configurar Waha con opción de reiniciar sesión

$wahaUrl = 'https://waha.neox.site';
$apiKey = 'MiClaveSecreta2024';
$sessionName = 'default';

echo "<h1>Configuración de Waha por API</h1>";

// Verificar si se solicitó reiniciar
if (isset($_GET['restart']) && $_GET['restart'] == '1') {
    echo "<h2>🔄 Reiniciando Sesión...</h2>";

    // 1. Detener y eliminar sesión existente
    $stopUrl = "$wahaUrl/api/sessions/$sessionName/stop";
    $ch = curl_init($stopUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-Api-Key: ' . $apiKey
    ]);
    curl_exec($ch);
    curl_close($ch);

    echo "<p>✅ Sesión detenida</p>";

    // Esperar un momento
    sleep(2);

    // 2. Eliminar sesión
    $deleteUrl = "$wahaUrl/api/sessions/$sessionName";
    $ch = curl_init($deleteUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-Api-Key: ' . $apiKey
    ]);
    curl_exec($ch);
    curl_close($ch);

    echo "<p>✅ Sesión eliminada</p>";
    echo "<p>Redirigiendo para crear nueva sesión...</p>";
    echo "<meta http-equiv='refresh' content='2;url=setup_waha.php'>";
    exit;
}

// Verificar estado actual
echo "<h2>📊 Estado Actual</h2>";
$statusUrl = "$wahaUrl/api/sessions";
$ch = curl_init($statusUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-Api-Key: ' . $apiKey
]);
$statusResponse = curl_exec($ch);
$statusHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$sessionExists = false;
$sessionStatus = 'UNKNOWN';

if ($statusHttpCode == 200) {
    $sessions = json_decode($statusResponse, true);
    if (!empty($sessions)) {
        foreach ($sessions as $session) {
            if ($session['name'] == $sessionName) {
                $sessionExists = true;
                $sessionStatus = $session['status'] ?? 'UNKNOWN';
                echo "<p>Sesión encontrada: <strong>$sessionName</strong></p>";
                echo "<p>Estado: <strong style='color:" . ($sessionStatus == 'WORKING' ? 'green' : 'orange') . "'>$sessionStatus</strong></p>";

                if ($sessionStatus == 'WORKING') {
                    echo "<p style='color:green; font-size:18px;'>✅ ¡WhatsApp ya está conectado y funcionando!</p>";
                    echo "<p>Puedes enviar un mensaje de prueba al número conectado.</p>";
                    echo "<hr>";
                    echo "<p><a href='?restart=1' style='background:red; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>🔄 Reiniciar Sesión</a></p>";
                    exit;
                }
            }
        }
    }
}

if ($sessionExists && $sessionStatus != 'WORKING') {
    echo "<p style='color:orange'>⚠️ La sesión existe pero no está conectada</p>";
    echo "<p><a href='?restart=1' style='background:orange; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>🔄 Reiniciar Sesión</a></p>";
    echo "<hr>";
}

// Crear o mostrar sesión
echo "<h2>1. " . ($sessionExists ? "Sesión Existente" : "Crear Nueva Sesión") . "</h2>";

if (!$sessionExists) {
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

    if ($httpCode == 201 || $httpCode == 200) {
        echo "<p style='color:green'>✅ Sesión creada exitosamente!</p>";
    } else {
        echo "<p style='color:red'>❌ Error al crear sesión</p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";

        if ($httpCode == 401) {
            echo "<p><strong>Error 401: API Key incorrecta.</strong></p>";
            echo "<p>Verifica que WHATSAPP_API_KEY en Coolify sea: <code>$apiKey</code></p>";
        }
    }
}

// Mostrar QR
echo "<h2>2. Código QR para Escanear</h2>";
echo "<p>Escanea este código QR con WhatsApp:</p>";

$qrImageUrl = "$wahaUrl/api/$sessionName/auth/qr";
echo "<div style='text-align:center; padding:20px; background:#f5f5f5;'>";
echo "<img src='$qrImageUrl' alt='QR Code' style='max-width:400px; border:2px solid #333; padding:10px; background:white;' onerror=\"this.style.display='none'; document.getElementById('qr-error').style.display='block';\">";
echo "<div id='qr-error' style='display:none; color:red;'>";
echo "<p>❌ No se pudo cargar el QR</p>";
echo "<p>Posibles razones:</p>";
echo "<ul style='text-align:left; display:inline-block;'>";
echo "<li>La sesión aún no está lista (espera 5 segundos y recarga)</li>";
echo "<li>El QR expiró (haz clic en 'Reiniciar Sesión')</li>";
echo "<li>Problema de autenticación con la API</li>";
echo "</ul>";
echo "<p><a href='?restart=1' style='background:orange; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>🔄 Reiniciar Sesión</a></p>";
echo "</div>";
echo "</div>";

echo "<p style='margin-top:20px;'><small>Ve a WhatsApp > Configuración > Dispositivos vinculados > Vincular un dispositivo</small></p>";

echo "<hr>";
echo "<p><button onclick='location.reload()' style='background:#007bff; color:white; padding:10px 20px; border:none; border-radius:5px; cursor:pointer;'>🔄 Recargar Página</button></p>";
echo "<p><a href='?restart=1' style='background:red; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>🔄 Reiniciar Sesión Completa</a></p>";

// Auto-reload cada 10 segundos si no está conectado
if ($sessionStatus != 'WORKING') {
    echo "<script>setTimeout(function(){ location.reload(); }, 10000);</script>";
    echo "<p><small>Esta página se recargará automáticamente cada 10 segundos hasta que escanees el QR.</small></p>";
}
?>