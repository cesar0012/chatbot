<?php
// Script para listar todas las instancias disponibles en Evolution API
// Usando la Global API Key

$baseUrl = 'https://evolution.neox.site';
$globalKey = '8CUSCumwgEJ43ydsEz1QB2kWNhxloneE'; // Global API Key

echo "<h2>Listado de Instancias Evolution API</h2>";

$url = "$baseUrl/instance/fetchInstances";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'apikey: ' . $globalKey
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "<p style='color:red'>Error de conexión: $error</p>";
} else {
    echo "<p>Código HTTP: <strong>$httpCode</strong></p>";

    $data = json_decode($response, true);

    if ($httpCode == 200) {
        echo "<p>Instancias encontradas:</p>";
        if (empty($data)) {
            echo "<p><em>No hay instancias creadas.</em></p>";
        } else {
            echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
        }
    } else {
        echo "<p style='color:orange'>Error al obtener instancias:</p>";
        echo "<pre>$response</pre>";
    }
}
?>