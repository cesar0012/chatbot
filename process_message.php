<?php
session_start();

// Incluir el núcleo del chat
require_once 'ChatCore.php';

// Establecer la codificación UTF-8
header('Content-Type: application/json; charset=utf-8');

// Verificar que la solicitud sea POST y de tipo JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener el contenido JSON de la solicitud
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Verificar que se recibió un mensaje
    if (isset($data['message'])) {
        $userMessage = $data['message'];

        // Usar ID de sesión de PHP para usuarios web
        $sessionId = session_id();

        try {
            // Procesar el mensaje usando el núcleo compartido
            $result = ChatCore::process($sessionId, $userMessage);

            // Devolver la respuesta como JSON
            echo json_encode([
                'response' => $result['response'],
                'flow_action' => $result['flow_action'],
                'flow_data' => $result['flow_data']
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        } catch (Exception $e) {
            // Manejo de errores
            http_response_code(500);
            echo json_encode([
                'response' => "Error interno: " . $e->getMessage(),
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }
}

// Si llegamos aquí, la solicitud no es válida
http_response_code(400);
echo json_encode(['error' => 'Solicitud inválida']);
?>