<?php
// Script para CREAR la instancia 'app-php' en Evolution API

$baseUrl = 'https://evolution.neox.site';
$globalKey = '8CUSCumwgEJ43ydsEz1QB2kWNhxloneE';
$instanceName = 'app-php';
$instanceToken = 'CCA5FF063C1C-463D-AC45-F264614A1D4A'; // El token que queremos usar

echo "<h2>Creando instancia '$instanceName'...</h2>";

$url = "$baseUrl/instance/create";

$data = [
    "instanceName" => $instanceName,
    "token" => $instanceToken,
    "qrcode" => true,
    "integration" => "WHATSAPP-BAILEYS" // O el motor por defecto
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

    if ($httpCode == 201 || $httpCode == 200) {
        echo "<p style='color:green'>¡Instancia creada con éxito!</p>";
        echo "<p>Ahora debes escanear el código QR. Ve a tu panel de Evolution o usa el endpoint de connect.</p>";
    }
}
?>