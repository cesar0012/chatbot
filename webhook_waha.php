<?php
require_once 'ChatCore.php';
require_once 'WahaService.php';

// Configuración de errores para debugging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// --- INICIO CÓDIGO DE DEPURACIÓN ---
$logContent = date('Y-m-d H:i:s') . " - Solicitud recibida (WAHA):\n";
$logContent .= file_get_contents('php://input') . "\n------------------\n";
file_put_contents('debug_log_waha.txt', $logContent, FILE_APPEND);
// --- FIN CÓDIGO DE DEPURACIÓN ---

// Recibir el payload JSON de Waha
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Log adicional para debugging
$processLog = date('Y-m-d H:i:s') . " - Procesando (WAHA): ";

// Waha envía el evento en el campo "event"
$event = $data['event'] ?? '';
$processLog .= "Evento recibido: $event\n";

// Solo procesar mensajes
if ($event !== 'message') {
    file_put_contents('process_log_waha.txt', $processLog . "Evento ignorado\n", FILE_APPEND);
    http_response_code(200);
    exit('OK');
}

// Extraer datos del mensaje
$payload = $data['payload'] ?? [];

// Ignorar mensajes enviados por el bot
if (isset($payload['fromMe']) && $payload['fromMe'] === true) {
    $processLog .= "Mensaje ignorado - fromMe: true\n";
    file_put_contents('process_log_waha.txt', $processLog, FILE_APPEND);
    http_response_code(200);
    exit('OK');
}

// Extraer remoteJid (from)
$remoteJid = $payload['from'] ?? '';
if (empty($remoteJid)) {
    $processLog .= "Mensaje ignorado - Sin remoteJid\n";
    file_put_contents('process_log_waha.txt', $processLog, FILE_APPEND);
    http_response_code(200);
    exit('OK');
}

// Extraer mensaje de texto
$userMessage = '';
if (isset($payload['body'])) {
    $userMessage = $payload['body'];
} elseif (isset($payload['text'])) {
    $userMessage = $payload['text'];
}

// Ignorar mensajes sin texto
if (empty($userMessage)) {
    $processLog .= "Mensaje ignorado - Sin texto\n";
    file_put_contents('process_log_waha.txt', $processLog, FILE_APPEND);
    http_response_code(200);
    exit('OK');
}

// Obtener ID de sesión (solo dígitos)
$sessionId = preg_replace('/[^0-9]/', '', $remoteJid);

$processLog .= "Procesando mensaje de $remoteJid (Session: $sessionId): $userMessage\n";

// Instanciar servicios (necesitamos crear WahaService)
$wahaService = new WahaService();

try {
    // Procesar mensaje con el núcleo del Chatbot
    $result = ChatCore::process($sessionId, $userMessage);

    $processLog .= "Respuesta generada: " . substr($result['response'], 0, 100) . "...\n";

    // Enviar respuesta a WhatsApp
    if (!empty($result['response'])) {
        $sendResult = $wahaService->sendMessage($remoteJid, $result['response']);
        if ($sendResult) {
            $processLog .= "Mensaje enviado exitosamente\n";
        } else {
            $processLog .= "ERROR: No se pudo enviar el mensaje\n";
        }
    }
} catch (Exception $e) {
    $processLog .= "ERROR: " . $e->getMessage() . "\n";
}

file_put_contents('process_log_waha.txt', $processLog, FILE_APPEND);

http_response_code(200);
echo 'OK';
?>