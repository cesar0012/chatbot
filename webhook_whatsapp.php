<?php
require_once 'ChatCore.php';
require_once 'WhatsAppService.php';

// Configuración de errores para debugging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// --- INICIO CÓDIGO DE DEPURACIÓN ---
$logContent = date('Y-m-d H:i:s') . " - Solicitud recibida:\n";
$logContent .= file_get_contents('php://input') . "\n------------------\n";
file_put_contents('debug_log.txt', $logContent, FILE_APPEND);
// --- FIN CÓDIGO DE DEPURACIÓN ---

// Recibir el payload JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Log adicional para debugging
$processLog = date('Y-m-d H:i:s') . " - Procesando: ";

// Verificar tipo de evento
if (!isset($data['type'])) {
    $processLog .= "Evento sin tipo recibido\n";
    file_put_contents('process_log.txt', $processLog, FILE_APPEND);
} else {
    $processLog .= "Evento recibido: " . $data['type'] . "\n";
    file_put_contents('process_log.txt', $processLog, FILE_APPEND);
}

// Si no es MESSAGES_UPSERT, terminamos aquí pero ya quedó registrado
if (!isset($data['type']) || $data['type'] !== 'MESSAGES_UPSERT') {
    http_response_code(200);
    exit('Evento registrado pero ignorado (no es mensaje)');
}

$processLog .= "Evento MESSAGES_UPSERT confirmado\n";

// Procesar mensajes
if (isset($data['data']['messages'])) {
    foreach ($data['data']['messages'] as $msg) {
        // Ignorar mensajes enviados por el bot (fromMe)
        if (isset($msg['key']['fromMe']) && $msg['key']['fromMe'] === true) {
            $processLog .= "Mensaje ignorado - fromMe: true\n";
            file_put_contents('process_log.txt', $processLog, FILE_APPEND);
            continue;
        }

        // Extraer remoteJid
        $remoteJid = $msg['key']['remoteJid'] ?? '';
        if (empty($remoteJid)) {
            $processLog .= "Mensaje ignorado - Sin remoteJid\n";
            file_put_contents('process_log.txt', $processLog, FILE_APPEND);
            continue;
        }

        // Extraer mensaje de texto
        $userMessage = '';
        if (isset($msg['message']['conversation'])) {
            $userMessage = $msg['message']['conversation'];
        } elseif (isset($msg['message']['extendedTextMessage']['text'])) {
            $userMessage = $msg['message']['extendedTextMessage']['text'];
        }

        // Ignorar mensajes sin texto
        if (empty($userMessage)) {
            $processLog .= "Mensaje ignorado - Sin texto\n";
            file_put_contents('process_log.txt', $processLog, FILE_APPEND);
            continue;
        }

        // Obtener ID de sesión (solo dígitos)
        $sessionId = preg_replace('/[^0-9]/', '', $remoteJid);

        $processLog .= "Procesando mensaje de $remoteJid (Session: $sessionId): $userMessage\n";

        // Instanciar servicios
        $waService = new WhatsAppService();

        try {
            // Procesar mensaje con el núcleo del Chatbot
            $result = ChatCore::process($sessionId, $userMessage);

            $processLog .= "Respuesta generada: " . substr($result['response'], 0, 100) . "...\n";

            // Enviar respuesta a WhatsApp
            if (!empty($result['response'])) {
                $sendResult = $waService->sendMessage($remoteJid, $result['response']);
                if ($sendResult) {
                    $processLog .= "Mensaje enviado exitosamente\n";
                } else {
                    $processLog .= "ERROR: No se pudo enviar el mensaje\n";
                }
            }
        } catch (Exception $e) {
            $processLog .= "ERROR: " . $e->getMessage() . "\n";
        }

        file_put_contents('process_log.txt', $processLog, FILE_APPEND);
    }
} else {
    $processLog .= "No se encontraron mensajes en el payload\n";
    file_put_contents('process_log.txt', $processLog, FILE_APPEND);
}

http_response_code(200);
echo 'OK';
?>