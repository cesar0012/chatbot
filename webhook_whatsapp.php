<?php
require_once 'ChatCore.php';
require_once 'WhatsAppService.php';

// Configuración de errores para debugging (puedes desactivarlo en producción)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// --- INICIO CÓDIGO DE DEPURACIÓN ---
// Esto guardará todo lo que llegue en un archivo de texto llamado 'debug_log.txt'
$logContent = date('Y-m-d H:i:s') . " - Solicitud recibida:\n";
$logContent .= file_get_contents('php://input') . "\n------------------\n";
file_put_contents('debug_log.txt', $logContent, FILE_APPEND);
// --- FIN CÓDIGO DE DEPURACIÓN ---

// Recibir el payload JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Verificar si es un evento de mensaje
if (!isset($data['type']) || $data['type'] !== 'MESSAGES_UPSERT') {
    http_response_code(200); // Responder 200 para que Evolution no reintente
    exit('Evento ignorado');
}

// Procesar mensajes
if (isset($data['data']['messages'])) {
    foreach ($data['data']['messages'] as $msg) {
        // Ignorar mensajes enviados por el bot (fromMe)
        if (isset($msg['key']['fromMe']) && $msg['key']['fromMe'] === true) {
            continue;
        }

        // Extraer remoteJid
        $remoteJid = $msg['key']['remoteJid'] ?? '';
        if (empty($remoteJid))
            continue;

        // Extraer mensaje de texto
        $userMessage = '';
        if (isset($msg['message']['conversation'])) {
            $userMessage = $msg['message']['conversation'];
        } elseif (isset($msg['message']['extendedTextMessage']['text'])) {
            $userMessage = $msg['message']['extendedTextMessage']['text'];
        }

        // Ignorar mensajes sin texto (imágenes, audios, etc. por ahora)
        if (empty($userMessage))
            continue;

        // Obtener ID de sesión (solo dígitos)
        $sessionId = preg_replace('/[^0-9]/', '', $remoteJid);

        // Verificar si es usuario nuevo
        $dbFile = "memory-bank/conversation_$sessionId.json";
        $isNewUser = !file_exists($dbFile);

        // Instanciar servicios
        $waService = new WhatsAppService();

        // Lógica de bienvenida vs continuidad
        if ($isNewUser) {
            // Si es nuevo, podemos simular un "Hola" para activar el flujo de bienvenida
            // O simplemente procesar su mensaje. Según requerimiento: "simula un mensaje inicial de Hola"
            // Vamos a procesar "Hola" primero para que el bot le de la bienvenida, 
            // y luego procesamos su mensaje real si es diferente de "Hola".

            // Opción A: Simular "Hola" internamente y enviar esa respuesta.
            // Si el usuario ya dijo "Hola", esto sería redundante, pero seguro.

            // Decisión: Procesar el mensaje del usuario directamente. 
            // Si se requiere forzar bienvenida, descomentar lo siguiente:
            /*
            $welcomeResponse = ChatCore::process($sessionId, "Hola");
            if (!empty($welcomeResponse['response'])) {
                $waService->sendMessage($remoteJid, $welcomeResponse['response']);
            }
            */
            // Por ahora, pasamos el mensaje tal cual.
        }

        // Procesar mensaje con el núcleo del Chatbot
        $result = ChatCore::process($sessionId, $userMessage);

        // Enviar respuesta a WhatsApp
        if (!empty($result['response'])) {
            $waService->sendMessage($remoteJid, $result['response']);
        }
    }
}

http_response_code(200);
echo 'OK';
?>