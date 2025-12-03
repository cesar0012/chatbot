<?php
// Proxy para obtener el QR de Waha con autenticación

$wahaUrl = 'https://waha.neox.site';
$apiKey = 'MiClaveSecreta2024';
$sessionName = isset($_GET['session']) ? $_GET['session'] : 'default';

// Obtener el QR con autenticación
$qrUrl = "$wahaUrl/api/$sessionName/auth/qr";

$ch = curl_init($qrUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-Api-Key: ' . $apiKey
]);

$qrData = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($httpCode == 200) {
    // Establecer el tipo de contenido correcto
    header('Content-Type: ' . ($contentType ?: 'image/png'));
    echo $qrData;
} else {
    // Si falla, mostrar una imagen de error
    header('Content-Type: image/svg+xml');
    echo '<?xml version="1.0" encoding="UTF-8"?>
    <svg width="400" height="400" xmlns="http://www.w3.org/2000/svg">
        <rect width="400" height="400" fill="#f8d7da"/>
        <text x="200" y="180" text-anchor="middle" font-family="Arial" font-size="16" fill="#721c24">
            QR no disponible
        </text>
        <text x="200" y="210" text-anchor="middle" font-family="Arial" font-size="14" fill="#721c24">
            HTTP ' . $httpCode . '
        </text>
        <text x="200" y="240" text-anchor="middle" font-family="Arial" font-size="12" fill="#721c24">
            Haz clic en "Reiniciar Sesión"
        </text>
    </svg>';
}
?>