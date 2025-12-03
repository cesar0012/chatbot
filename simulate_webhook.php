<?php
// Script para simular una petición de Evolution API al webhook localmente
// Esto nos ayudará a verificar si el webhook funciona correctamente cuando recibe datos.

$webhookUrl = 'https://chatbot.neox.site/webhook_whatsapp.php'; // Ajusta la ruta si es necesario
// Si estás en el mismo servidor, puedes intentar incluir el archivo directamente o usar cURL a localhost

// Payload de ejemplo que enviaría Evolution API
$payload = [
    "type" => "MESSAGES_UPSERT",
    "data" => [
        "messages" => [
            [
                "key" => [
                    "remoteJid" => "5215551234567@s.whatsapp.net",
                    "fromMe" => false
                ],
                "message" => [
                    "conversation" => "Hola, esto es una prueba simulada"
                ]
            ]
        ]
    ]
];

$jsonData = json_encode($payload);

echo "<h2>Simulando petición al Webhook...</h2>";
echo "<p>Enviando datos a: <strong>$webhookUrl</strong></p>";

// Inicializar cURL
$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($jsonData)
]);

// Ejecutar
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<h3>Resultados:</h3>";
if ($error) {
    echo "<p style='color:red'>Error cURL: $error</p>";
    echo "<p>Intenta ejecutar este script en el navegador y revisa si se crea 'debug_log.txt'.</p>";

    // Fallback: Intentar llamar a la lógica directamente si cURL falla (común en local)
    echo "<hr><p>Intentando inyección directa (bypass de red)...</p>";

    // Simular variables de entorno para el script
    $_SERVER['REQUEST_METHOD'] = 'POST';

    // Guardar el payload en un archivo temporal para que php://input lo pueda leer (truco complejo)
    // O mejor, modificamos temporalmente el webhook para leer de una variable si existe.
    // Pero como no queremos modificar el webhook, simplemente indicamos que cURL falló.
} else {
    echo "<p>Código HTTP: <strong>$httpCode</strong></p>";
    echo "<p>Respuesta del Webhook: <pre>$response</pre></p>";

    if ($httpCode == 200) {
        echo "<p style='color:green'>¡La simulación fue enviada con éxito!</p>";
        echo "<p>Ahora verifica si se creó el archivo <strong>debug_log.txt</strong> en el servidor.</p>";
    } else {
        echo "<p style='color:orange'>El webhook respondió con un código inesperado.</p>";
    }
}
?>