<?php

class WahaService
{
    private $baseUrl = 'https://waha.neox.site';
    private $apiKey = 'MiClaveSecreta2024'; // Cambia esto por tu WHATSAPP_API_KEY
    private $sessionName = 'default'; // Cambia esto por el nombre de tu sesión

    public function sendMessage($chatId, $text)
    {
        $url = $this->baseUrl . '/api/sendText';

        // Convertir HTML a formato WhatsApp
        $formattedText = $this->htmlToWhatsApp($text);

        $data = [
            'session' => $this->sessionName,
            'chatId' => $chatId,
            'text' => $formattedText
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Api-Key: ' . $this->apiKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("Error enviando mensaje a WhatsApp (Waha): $error");
            return false;
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        } else {
            error_log("Error API WhatsApp Waha ($httpCode): $response");
            return false;
        }
    }

    public function htmlToWhatsApp($html)
    {
        // Reemplazos de estructura
        $html = str_replace(['<br>', '<br/>', '<br />'], "\n", $html);
        $html = str_replace(['</p>', '</div>'], "\n\n", $html);
        $html = str_replace(['<li>'], "• ", $html);
        $html = str_replace(['</li>'], "\n", $html);

        // Reemplazos de formato
        $html = preg_replace('/<b>(.*?)<\/b>/i', '*$1*', $html);
        $html = preg_replace('/<strong>(.*?)<\/strong>/i', '*$1*', $html);
        $html = preg_replace('/<i>(.*?)<\/i>/i', '_$1_', $html);
        $html = preg_replace('/<em>(.*?)<\/em>/i', '_$1_', $html);

        // Eliminar resto de etiquetas HTML
        $text = strip_tags($html);

        // Decodificar entidades HTML
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Limpiar espacios múltiples y saltos de línea excesivos
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        $text = trim($text);

        return $text;
    }
}
?>