$formattedText = $this->htmlToWhatsApp($text);

// El campo 'number' en Evolution API suele aceptar el número o el JID.
// Si remoteJid viene como '123456@s.whatsapp.net', podemos enviarlo así o solo el número.
// Para mayor compatibilidad, intentaremos extraer solo los dígitos si es necesario,
// pero muchas versiones de Evolution aceptan el JID completo.
// Probaremos enviando el remoteJid tal cual, ya que suele ser lo estándar.

$data = [
'number' => $remoteJid,
'text' => $formattedText
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
'Content-Type: application/json',
'apikey: ' . $this->instanceToken
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
error_log("Error enviando mensaje a WhatsApp: $error");
return false;
}

if ($httpCode >= 200 && $httpCode < 300) { return json_decode($response, true); } else { error_log("Error API WhatsApp
    ($httpCode): $response"); return false; } } public function htmlToWhatsApp($html) { // Reemplazos de estructura
    $html=str_replace(['<br>', '<br />', '<br />'], "\n", $html);
    $html = str_replace(['</p>', '</div>'], "\n\n", $html);
    $html = str_replace(['<li>'], "• ", $html);
        $html = str_replace(['</li>'], "\n", $html);

    // Reemplazos de formato
    $html = preg_replace('/<b>(.*?)<\ /b>/i', '*$1*', $html);
            $html = preg_replace('/<strong>(.*?)<\ /strong>/i', '*$1*', $html);
                    $html = preg_replace('/<i>(.*?)<\ /i>/i', '_$1_', $html);
                            $html = preg_replace('/<em>(.*?)<\ /em>/i', '_$1_', $html);

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