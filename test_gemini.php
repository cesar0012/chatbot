<?php
// Script para probar la API de Gemini directamente

$apiKey = "YOUR_API_KEY_HERE"; // Reemplazar con tu API key para testing local

echo "<h1>Prueba de API de Gemini</h1>";

// Probar con un mensaje simple
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;

$data = [
    "contents" => [
        [
            "parts" => [
                ["text" => "Hola, responde con un simple 'Hola'"]
            ]
        ]
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<h2>Resultado de la Prueba</h2>";
echo "<p><strong>Código HTTP:</strong> $httpCode</p>";

if ($error) {
    echo "<p style='color:red'><strong>Error de cURL:</strong> $error</p>";
}

echo "<h3>Respuesta Completa:</h3>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

if ($httpCode === 200) {
    echo "<p style='color:green; font-size:18px;'>✅ ¡La API Key funciona correctamente!</p>";
    $responseData = json_decode($response, true);
    if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
        echo "<p><strong>Respuesta de Gemini:</strong> " . htmlspecialchars($responseData['candidates'][0]['content']['parts'][0]['text']) . "</p>";
    }
} elseif ($httpCode === 403) {
    echo "<p style='color:red; font-size:18px;'>❌ Error 403: Acceso Prohibido</p>";
    echo "<p><strong>Posibles causas:</strong></p>";
    echo "<ul>";
    echo "<li>La API Key no es válida o está deshabilitada</li>";
    echo "<li>La API Key no tiene permisos para usar Gemini API</li>";
    echo "<li>Has excedido la cuota gratuita</li>";
    echo "<li>La API Key está restringida por IP o dominio</li>";
    echo "</ul>";
    echo "<p><strong>Soluciones:</strong></p>";
    echo "<ul>";
    echo "<li>Verifica tu API Key en: <a href='https://aistudio.google.com/apikey' target='_blank'>https://aistudio.google.com/apikey</a></li>";
    echo "<li>Asegúrate de que la API de Generative Language esté habilitada en tu proyecto de Google Cloud</li>";
    echo "<li>Revisa las restricciones de la API Key (si las tiene)</li>";
    echo "</ul>";
} else {
    echo "<p style='color:orange'>⚠️ Error HTTP $httpCode</p>";
}

// Probar también con gemini-pro (modelo alternativo)
echo "<hr><h2>Probando con gemini-pro (modelo alternativo)</h2>";
$url2 = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=" . $apiKey;

$ch2 = curl_init($url2);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_POST, true);
curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch2, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch2, CURLOPT_TIMEOUT, 30);

$response2 = curl_exec($ch2);
$httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

echo "<p><strong>Código HTTP:</strong> $httpCode2</p>";
echo "<pre>" . htmlspecialchars($response2) . "</pre>";

if ($httpCode2 === 200) {
    echo "<p style='color:green'>✅ gemini-pro funciona!</p>";
}
?>