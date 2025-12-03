<?php
require_once 'process_flow.php';

// Clase para manejar la conversación y almacenar el historial
// Movida desde process_message.php para ser reutilizable
class TinyDB {
    private $dbFile;
    private $data;
    
    public function __construct($sessionId) {
        $this->dbFile = "memory-bank/conversation_$sessionId.json";
        $this->loadData();
    }
    
    private function loadData() {
        if (file_exists($this->dbFile)) {
            $content = file_get_contents($this->dbFile);
            $this->data = json_decode($content, true) ?: ['messages' => []];
        } else {
            $this->data = ['messages' => []];
        }
    }
    
    public function saveData() {
        $dir = dirname($this->dbFile);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($this->dbFile, json_encode($this->data, JSON_PRETTY_PRINT));
    }
    
    public function addMessage($role, $content) {
        $this->data['messages'][] = [
            'role' => $role,
            'content' => $content,
            'timestamp' => time()
        ];
        $this->saveData();
    }
    
    public function getMessages($limit = 10) {
        // Obtener los últimos X mensajes para contexto
        $messages = array_slice($this->data['messages'], -$limit);
        return $messages;
    }

    public function exists() {
        return file_exists($this->dbFile);
    }
}

class ChatCore {
    private static $geminiApiKey = "AIzaSyD23m-ttYgegugSZhH3CjxYsc2EhYs02Mg"; // API Key de Gemini

    public static function process($sessionId, $userMessage) {
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
        $flowProcessor = new FlowProcessor($sessionId, self::$geminiApiKey);
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

    private static function callGemini($prompt) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . self::$geminiApiKey;
        
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
    private static function loadReferenceFiles() {
        $content = "";
        $dir = "memory-bank/";
        
        if (file_exists($dir) && is_dir($dir)) {
            $files = array_diff(scandir($dir), array('..', '.'));
            
            // Organizar archivos por tipo
            $instructions = [];
            $urls = [];
            $documents = [];
            $csvFiles = [];
            
            foreach ($files as $file) {
                if (is_file($dir . $file) && !strpos($file, 'conversation_') && !strpos($file, 'flow_state_') && !strpos($file, '.json')) {
                    $fileExt = strtolower(pathinfo($dir . $file, PATHINFO_EXTENSION));
                    
                    if ($file === 'instructions.txt') {
                        $instructions[] = $file;
                    } elseif (strpos($file, 'url_') === 0) {
                        $urls[] = $file;
                    } elseif ($fileExt === 'csv') {
                        $csvFiles[] = $file;
                    } else {
                        $documents[] = $file;
                    }
                }
            }
            
            // Procesar URLs extraídas
            if (!empty($urls)) {
                $content .= "\n\n=== CONTENIDO DE URLS EXTRAÍDAS ===\n";
                foreach ($urls as $file) {
                    $fileContent = file_get_contents($dir . $file);
                    $urlLine = strtok($fileContent, "\n");
                    $url = str_replace("URL: ", "", $urlLine);
                    
                    $content .= "\n--- Contenido de $url ---\n";
                    $content .= substr($fileContent, strpos($fileContent, "\n\n") + 2);
                }
            }
            
            // Procesar archivos CSV
            if (!empty($csvFiles)) {
                $content .= "\n\n=== CONTENIDO DE ARCHIVOS CSV ===\n";
                foreach ($csvFiles as $file) {
                    $content .= "\n--- Contenido de $file ---\n";
                    
                    $csvData = [];
                    if (($handle = fopen($dir . $file, "r")) !== FALSE) {
                        $headers = fgetcsv($handle, 1000, ",");
                        $headerCount = count($headers);
                        
                        $rowCount = 0;
                        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE && $rowCount < 100) {
                            $csvData[] = $data;
                            $rowCount++;
                        }
                        fclose($handle);
                        
                        $content .= "Encabezados: " . implode(", ", $headers) . "\n\n";
                        $content .= "Datos (mostrando hasta 100 filas):\n";
                        
                        foreach ($csvData as $row) {
                            $formattedRow = [];
                            for ($i = 0; $i < $headerCount && $i < count($row); $i++) {
                                $formattedRow[] = $headers[$i] . ": " . $row[$i];
                            }
                            $content .= implode(" | ", $formattedRow) . "\n";
                        }
                        
                        if ($rowCount >= 100) {
                            $content .= "\n[Nota: Este archivo contiene más de 100 filas. Solo se muestran las primeras 100.]\n";
                        }
                    } else {
                        $content .= "Error al leer el archivo CSV.\n";
                    }
                }
            }
            
            // Procesar documentos subidos
            if (!empty($documents)) {
                $content .= "\n\n=== CONTENIDO DE DOCUMENTOS ===\n";
                foreach ($documents as $file) {
                    $fileContent = file_get_contents($dir . $file);
                    $content .= "\n--- Contenido de $file ---\n";
                    $content .= $fileContent;
                }
            }
        }
        
        return $content;
    }
}
?>
