<?php
require_once 'process_flow.php';

// Clase para manejar la conversación y almacenar el historial
// Movida desde process_message.php para ser reutilizable
class TinyDB
{
    private $dbFile;
    private $data;

    public function __construct($sessionId)
    {
        // Usar /tmp/ para asegurar permisos de escritura en entornos restringidos
        $this->dbFile = "/tmp/conversation_$sessionId.json";
        $this->loadData();
    }

    private function loadData()
    {
        if (file_exists($this->dbFile)) {
            $content = file_get_contents($this->dbFile);
            $this->data = json_decode($content, true) ?: ['messages' => []];
        } else {
            $this->data = ['messages' => []];
        }
    }

    public function saveData()
    {
        $dir = dirname($this->dbFile);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($this->dbFile, json_encode($this->data, JSON_PRETTY_PRINT));
    }

    public function addMessage($role, $content)
    {
        $this->data['messages'][] = [
            'role' => $role,
            'content' => $content,
            'timestamp' => time()
        ];
        $this->saveData();
    }

    public function getMessages($limit = 10)
    {
        // Obtener los últimos X mensajes para contexto
        $messages = array_slice($this->data['messages'], -$limit);
        return $messages;
    }

    public function exists()
    {
        return file_exists($this->dbFile);
    }
}

class ChatCore
{
    private static $geminiApiKey = null; // Se cargará desde variable de entorno

    private static function getApiKey()
    {
        if (self::$geminiApiKey === null) {
            // Intentar obtener desde variable de entorno
            self::$geminiApiKey = getenv('GEMINI_API_KEY');

            // Si no está en variable de entorno, usar valor por defecto (solo para desarrollo local)
            if (empty(self::$geminiApiKey)) {
                self::$geminiApiKey = "YOUR_API_KEY_HERE"; // Reemplazar localmente, nunca subir a git
            }
        }
        return self::$geminiApiKey;
    }

    public static function process($sessionId, $userMessage)
    {
        // Inicializar TinyDB con ID de sesión
        $db = new TinyDB($sessionId);

        // Agregar mensaje del usuario a la base de datos
        $db->addMessage('user', $userMessage);

        // Cargar contenido de archivos de referencia
        $referenceContent = self::loadReferenceFiles();

        // Preparar el contexto para la API de Gemini
        $conversationHistory = $db->getMessages();
        $conversationText = "";

        foreach ($conversationHistory as $msg) {
            $role = $msg['role'] === 'user' ? 'Usuario' : 'Asistente';
            $conversationText .= "$role: {$msg['content']}\n";
        }

        // Construir el prompt para Gemini
        $prompt = "";

        // Agregar contexto de archivos de referencia si existe
        if (!empty($referenceContent)) {
            $prompt .= "Información de referencia disponible:\n$referenceContent\n\n";
        }

        // Agregar historial de conversación
        $prompt .= "Historial de conversación reciente:\n$conversationText\n";

        // Cargar instrucciones personalizadas si existen
        $customInstructions = "";
        $instructionsFile = "memory-bank/instructions.txt";
        if (file_exists($instructionsFile)) {
            $customInstructions = file_get_contents($instructionsFile);
        }

        // Agregar instrucciones para el modelo
        $prompt .= "\nEres un asistente virtual amigable y servicial. ";

        // Agregar instrucciones personalizadas si existen
        if (!empty($customInstructions)) {
            $prompt .= "$customInstructions \n";
        } else {
            $prompt .= "Responde de manera concisa y útil basándote en la información de referencia proporcionada cuando sea relevante. Si no conoces la respuesta, indícalo honestamente.\n";
        }

        $prompt .= "\n";

        // Agregar la pregunta actual
        $prompt .= "Usuario: $userMessage\nAsistente:";

        // Procesar flujos de conversación si existen
        $flowProcessor = new FlowProcessor($sessionId, self::getApiKey());
        $flowResult = $flowProcessor->processMessage($userMessage);

        $botResponse = '';
        $flowAction = null;
        $flowData = null;

        // Si el flujo ha procesado el mensaje y generado una respuesta
        if ($flowResult['flow_processed'] && $flowResult['action'] !== 'none') {

            // Manejar diferentes tipos de acciones
            switch ($flowResult['action']) {
                case 'message':
                    // Respuesta directa del flujo
                    $botResponse = $flowResult['content'];
                    break;

                case 'redirect':
                    // Redirección a otra página
                    $botResponse = "Te voy a redireccionar a: " . $flowResult['content'];
                    // Nota: La redirección web se maneja en el frontend, aquí solo devolvemos el texto
                    break;

                case 'api':
                    // Llamada a una API externa
                    $botResponse = "Estoy procesando tu solicitud...";
                    break;

                case 'function':
                    // Ejecutar una función personalizada
                    $botResponse = "Ejecutando función personalizada...";
                    break;

                default:
                    // Usar Gemini para generar una respuesta
                    $botResponse = "No entiendo qué acción realizar.";
            }

            $flowAction = $flowResult['action'];
            $flowData = $flowResult['content'];

        } else {
            // Si no hay flujo activo o el flujo no generó una respuesta, usar Gemini
            $botResponse = self::callGemini($prompt);
        }

        // Guardar la respuesta en la base de datos
        $db->addMessage('assistant', $botResponse);

        return [
            'response' => $botResponse,
            'flow_action' => $flowAction,
            'flow_data' => $flowData
        ];
    }

    private static function callGemini($prompt)
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . self::getApiKey();

        $data = [
            "contents" => [
                [
                    "parts" => [
                        ["text" => $prompt]
                    ]
                ]
            ],
            "generationConfig" => [
                "temperature" => 0.7,
                "maxOutputTokens" => 65536,
                "topP" => 0.95,
                "topK" => 40
            ]
        ];

        // Inicializar cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        // Ejecutar la solicitud
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return "Error de conexión: $error";
        }

        if ($httpCode === 200) {
            $responseData = json_decode($response, true);

            if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                $botResponse = $responseData['candidates'][0]['content']['parts'][0]['text'];
                $botResponse = trim($botResponse);
                $botResponse = str_replace('\/', '/', $botResponse);
                return $botResponse;
            } else {
                return 'Respuesta inesperada de la API de Gemini';
            }
        }

        return "Error de API (HTTP $httpCode)";
    }

    // Función para cargar el contenido de los archivos de referencia
    private static function loadReferenceFiles()
    {
        $directory = 'memory-bank/';
        $content = "";

        if (is_dir($directory)) {
            $files = scandir($directory);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) !== 'json') {
                    $filePath = $directory . $file;
                    if (is_file($filePath)) {
                        $fileContent = file_get_contents($filePath);
                        $content .= "Archivo: $file\nContenido:\n$fileContent\n\n";
                    }
                }
            }
        }

        return $content;
    }
}
?>