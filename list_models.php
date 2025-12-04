<?php
// Script para listar todos los modelos disponibles en tu API Key

$apiKey = "AIzaSyC8IryVBBTgyhiKyjYpRY4JJ2hYsr-zvxE";

echo "<h1>Modelos Disponibles en tu API Key</h1>";

// Listar modelos con v1beta
$url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $apiKey;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<h2>API v1beta - Código HTTP: $httpCode</h2>";

if ($error) {
    echo "<p style='color:red'><strong>Error de cURL:</strong> $error</p>";
}

if ($httpCode === 200) {
    $data = json_decode($response, true);

    if (isset($data['models'])) {
        echo "<p style='color:green'>✅ Se encontraron " . count($data['models']) . " modelos</p>";

        echo "<h3>Modelos que soportan generateContent:</h3>";
        echo "<table border='1' cellpadding='10' style='border-collapse:collapse; width:100%;'>";
        echo "<tr style='background:#f0f0f0'><th>Nombre del Modelo</th><th>Métodos Soportados</th></tr>";

        foreach ($data['models'] as $model) {
            $modelName = $model['name'];
            $supportedMethods = isset($model['supportedGenerationMethods']) ? $model['supportedGenerationMethods'] : [];

            // Solo mostrar modelos que soporten generateContent
            if (in_array('generateContent', $supportedMethods)) {
                echo "<tr>";
                echo "<td><strong>" . htmlspecialchars($modelName) . "</strong></td>";
                echo "<td>" . implode(', ', $supportedMethods) . "</td>";
                echo "</tr>";
            }
        }
        echo "</table>";

        echo "<h3>Respuesta Completa (JSON):</h3>";
        echo "<pre style='background:#f5f5f5; padding:10px; overflow:auto; max-height:400px;'>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";
    } else {
        echo "<p style='color:orange'>No se encontraron modelos en la respuesta</p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
    }
} else {
    echo "<p style='color:red'>❌ Error HTTP $httpCode</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

// Intentar también con v1
echo "<hr><h2>API v1 - Listado de Modelos</h2>";
$url2 = "https://generativelanguage.googleapis.com/v1/models?key=" . $apiKey;

$ch2 = curl_init($url2);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch2, CURLOPT_TIMEOUT, 30);

$response2 = curl_exec($ch2);
$httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

echo "<p><strong>Código HTTP:</strong> $httpCode2</p>";

if ($httpCode2 === 200) {
    $data2 = json_decode($response2, true);
    if (isset($data2['models'])) {
        echo "<p style='color:green'>✅ Se encontraron " . count($data2['models']) . " modelos en v1</p>";
        echo "<pre style='background:#f5f5f5; padding:10px; overflow:auto; max-height:400px;'>" . htmlspecialchars(json_encode($data2, JSON_PRETTY_PRINT)) . "</pre>";
    }
} else {
    echo "<pre>" . htmlspecialchars($response2) . "</pre>";
}
?>