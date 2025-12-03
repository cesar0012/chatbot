<?php
/**
 * Clase para procesar flujos de conversación
 * 
 * Esta clase maneja la lógica de los flujos de conversación definidos por el usuario
 * Evalúa condiciones y ejecuta acciones basadas en el mensaje del usuario
 */
class FlowProcessor
{
    private $flows = [];
    private $activeFlow = null;
    private $activeNode = null;
    private $sessionId;
    private $geminiApiKey;

    /**
     * Constructor
     * 
     * @param string $sessionId ID de la sesión actual
     * @param string $geminiApiKey Clave de API de Gemini para evaluación de condiciones
     */
    public function __construct($sessionId, $geminiApiKey)
    {
        $this->sessionId = $sessionId;
        $this->geminiApiKey = $geminiApiKey;
        $this->loadFlows();
        $this->loadActiveFlow();
    }

    /**
     * Cargar todos los flujos disponibles
     */
    private function loadFlows()
    {
        $flows_dir = "memory-bank/flows/";
        if (file_exists($flows_dir) && is_dir($flows_dir)) {
            $flow_files = glob($flows_dir . "*.json");
            foreach ($flow_files as $flow_file) {
                $flow_data = json_decode(file_get_contents($flow_file), true);
                if ($flow_data) {
                    // Asegurarse de que el campo 'active' esté definido
                    if (!isset($flow_data['active'])) {
                        $flow_data['active'] = false; // Por defecto, los flujos están inactivos
                    }

                    $this->flows[basename($flow_file, '.json')] = $flow_data;
                }
            }
        }
    }

    /**
     * Cargar el flujo activo de la sesión
     */
    private function loadActiveFlow()
    {
        $flow_state_file = "/tmp/flow_state_{$this->sessionId}.json";
        if (file_exists($flow_state_file)) {
            $flow_state = json_decode(file_get_contents($flow_state_file), true);
            if ($flow_state && isset($flow_state['active_flow']) && isset($flow_state['active_node'])) {
                $this->activeFlow = $flow_state['active_flow'];
                $this->activeNode = $flow_state['active_node'];
            }
        }
    }

    /**
     * Guardar el estado del flujo activo
     */
    private function saveActiveFlow()
    {
        $flow_state_file = "/tmp/flow_state_{$this->sessionId}.json";
        $flow_state = [
            'active_flow' => $this->activeFlow,
            'active_node' => $this->activeNode,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        file_put_contents($flow_state_file, json_encode($flow_state, JSON_PRETTY_PRINT));
    }

    /**
     * Procesar un mensaje del usuario a través de los flujos
     * 
     * @param string $userMessage Mensaje del usuario
     * @return array Resultado del procesamiento con acción y contenido
     */
    public function processMessage($userMessage)
    {
        $result = [
            'action' => 'none',
            'content' => null,
            'flow_processed' => false
        ];

        // Reiniciar el flujo activo si no existe o si ya no está activo
        if ($this->activeFlow !== null && isset($this->flows[$this->activeFlow])) {
            $currentFlow = $this->flows[$this->activeFlow];
            if (!isset($currentFlow['active']) || !$currentFlow['active']) {
                // El flujo activo ya no está marcado como activo, reiniciarlo
                $this->activeFlow = null;
                $this->activeNode = null;
                $this->saveActiveFlow();
            }
        }

        // Si no hay flujo activo, intentar encontrar uno
        if ($this->activeFlow === null) {
            // Crear una lista de flujos activos
            $activeFlows = [];
            foreach ($this->flows as $flowId => $flow) {
                if (isset($flow['active']) && $flow['active']) {
                    $activeFlows[$flowId] = $flow;
                }
            }

            // Si hay flujos activos, seleccionar uno para procesar
            if (!empty($activeFlows)) {
                foreach ($activeFlows as $flowId => $flow) {
                    $startNode = $this->findStartNode($flow);
                    if ($startNode) {
                        $this->activeFlow = $flowId;
                        $this->activeNode = $startNode['id'];
                        $this->saveActiveFlow();
                        break;
                    }
                }
            }
        }

        // Intentar procesar el flujo activo actual
        $processedFlow = false;

        if ($this->activeFlow !== null && isset($this->flows[$this->activeFlow])) {
            $currentFlow = $this->flows[$this->activeFlow];

            // Verificar si el flujo está activo
            if (isset($currentFlow['active']) && $currentFlow['active']) {
                $currentNode = $this->findNodeById($currentFlow, $this->activeNode);

                if ($currentNode) {
                    // Procesar según el tipo de nodo
                    process_node:
                    switch ($currentNode['type']) {
                        case 'start':
                            // Buscar el siguiente nodo conectado al inicio
                            $nextNode = $this->findNextNode($currentFlow, $currentNode['id'], 0);
                            if ($nextNode) {
                                $this->activeNode = $nextNode;
                                $this->saveActiveFlow();
                                // Avanzar al siguiente nodo sin recursión
                                $currentNode = $this->findNodeById($currentFlow, $nextNode);
                                if ($currentNode) {
                                    // Reiniciar el switch con el nuevo nodo
                                    // No podemos usar continue 2 aquí, así que usamos un enfoque diferente
                                    goto process_node;
                                }
                            }
                            break;

                        case 'condition':
                            // Evaluar la condición
                            $conditionResult = $this->evaluateCondition($currentNode, $userMessage);
                            // Buscar el siguiente nodo según el resultado (0 para verdadero, 1 para falso)
                            $outputIndex = $conditionResult ? 0 : 1;
                            $nextNode = $this->findNextNode($currentFlow, $currentNode['id'], $outputIndex);

                            if ($nextNode) {
                                $this->activeNode = $nextNode;
                                $this->saveActiveFlow();
                                $result['flow_processed'] = true;

                                // Avanzar al siguiente nodo sin recursión
                                $currentNode = $this->findNodeById($currentFlow, $nextNode);
                                if ($currentNode) {
                                    // Reiniciar el switch con el nuevo nodo
                                    goto process_node;
                                }
                            }
                            break;

                        case 'action':
                            // Ejecutar la acción
                            $actionResult = $this->executeAction($currentNode);
                            $result['action'] = $actionResult['action'];
                            $result['content'] = $actionResult['content'];
                            $result['flow_processed'] = true;

                            // Buscar el siguiente nodo
                            $nextNode = $this->findNextNode($currentFlow, $currentNode['id'], 0);
                            if ($nextNode) {
                                $this->activeNode = $nextNode;
                            } else {
                                // Si no hay siguiente nodo, finalizar el flujo
                                $this->activeFlow = null;
                                $this->activeNode = null;
                            }
                            $this->saveActiveFlow();
                            $processedFlow = true;
                            break;

                        case 'api_request':
                            // Ejecutar petición API
                            $apiResult = $this->executeApiRequest($currentNode);
                            $result['action'] = $apiResult['action'];
                            $result['content'] = $apiResult['content'];
                            $result['flow_processed'] = true;

                            // Buscar el siguiente nodo
                            $nextNode = $this->findNextNode($currentFlow, $currentNode['id'], 0);
                            if ($nextNode) {
                                $this->activeNode = $nextNode;
                            } else {
                                $this->activeFlow = null;
                                $this->activeNode = null;
                            }
                            $this->saveActiveFlow();
                            $processedFlow = true;
                            break;

                        case 'ai_generate':
                            // Generar contenido con IA
                            $aiResult = $this->executeAiGenerate($currentNode, $userMessage);
                            $result['action'] = $aiResult['action'];
                            $result['content'] = $aiResult['content'];
                            $result['flow_processed'] = true;

                            // Buscar el siguiente nodo
                            $nextNode = $this->findNextNode($currentFlow, $currentNode['id'], 0);
                            if ($nextNode) {
                                $this->activeNode = $nextNode;
                            } else {
                                $this->activeFlow = null;
                                $this->activeNode = null;
                            }
                            $this->saveActiveFlow();
                            $processedFlow = true;
                            break;

                        case 'rag_search':
                            // Búsqueda en memoria (RAG)
                            $ragResult = $this->executeRagSearch($currentNode, $userMessage);
                            $result['action'] = $ragResult['action'];
                            $result['content'] = $ragResult['content'];
                            $result['flow_processed'] = true;

                            // Buscar el siguiente nodo
                            $nextNode = $this->findNextNode($currentFlow, $currentNode['id'], 0);
                            if ($nextNode) {
                                $this->activeNode = $nextNode;
                            } else {
                                $this->activeFlow = null;
                                $this->activeNode = null;
                            }
                            $this->saveActiveFlow();
                            $processedFlow = true;
                            break;

                        case 'carousel':
                            // Mostrar carrusel/tarjetas
                            $carouselResult = $this->executeCarousel($currentNode);
                            $result['action'] = $carouselResult['action'];
                            $result['content'] = $carouselResult['content'];
                            $result['flow_processed'] = true;

                            // Buscar el siguiente nodo
                            $nextNode = $this->findNextNode($currentFlow, $currentNode['id'], 0);
                            if ($nextNode) {
                                $this->activeNode = $nextNode;
                            } else {
                                $this->activeFlow = null;
                                $this->activeNode = null;
                            }
                            $this->saveActiveFlow();
                            $processedFlow = true;
                            break;

                        case 'subflow':
                            // Ejecutar sub-flujo
                            $subflowResult = $this->executeSubflow($currentNode, $userMessage);
                            $result['action'] = $subflowResult['action'];
                            $result['content'] = $subflowResult['content'];
                            $result['flow_processed'] = true;

                            // Buscar el siguiente nodo
                            $nextNode = $this->findNextNode($currentFlow, $currentNode['id'], 0);
                            if ($nextNode) {
                                $this->activeNode = $nextNode;
                            } else {
                                $this->activeFlow = null;
                                $this->activeNode = null;
                            }
                            $this->saveActiveFlow();
                            $processedFlow = true;
                            break;

                        case 'delay':
                            // Ejecutar pausa/retardo
                            $delayResult = $this->executeDelay($currentNode);
                            $result['action'] = $delayResult['action'];
                            $result['content'] = $delayResult['content'];
                            $result['flow_processed'] = true;

                            // Buscar el siguiente nodo
                            $nextNode = $this->findNextNode($currentFlow, $currentNode['id'], 0);
                            if ($nextNode) {
                                $this->activeNode = $nextNode;
                            } else {
                                $this->activeFlow = null;
                                $this->activeNode = null;
                            }
                            $this->saveActiveFlow();
                            $processedFlow = true;
                            break;
                    }
                }
            }
        }

        // Si no se procesó ningún flujo o no se produjo una acción, intentar con otros flujos activos
        if (!$processedFlow || $result['action'] === 'none') {
            // Usar un enfoque iterativo en lugar de recursivo
            $processedFlows = []; // Mantener registro de los flujos ya procesados
            if ($this->activeFlow !== null) {
                $processedFlows[] = $this->activeFlow;
            }

            foreach ($this->flows as $flowId => $flow) {
                // Saltar flujos ya procesados
                if (in_array($flowId, $processedFlows)) {
                    continue;
                }

                // Solo considerar flujos marcados como activos
                if (isset($flow['active']) && $flow['active']) {
                    // Buscar un nodo de inicio
                    $startNode = $this->findStartNode($flow);
                    if ($startNode) {
                        // Guardar estado actual
                        $previousActiveFlow = $this->activeFlow;
                        $previousActiveNode = $this->activeNode;

                        // Activar temporalmente este flujo
                        $this->activeFlow = $flowId;
                        $this->activeNode = $startNode['id'];
                        $processedFlows[] = $flowId;

                        // Procesar el flujo manualmente sin recursión
                        $tempResult = [
                            'action' => 'none',
                            'content' => null,
                            'flow_processed' => false
                        ];

                        // Obtener el nodo inicial
                        $node = $this->findNodeById($flow, $startNode['id']);

                        // Procesar hasta 10 nodos como máximo para evitar bucles infinitos
                        $maxIterations = 10;
                        $iterations = 0;

                        while ($node && $iterations < $maxIterations) {
                            $iterations++;

                            switch ($node['type']) {
                                case 'start':
                                    $nextNodeId = $this->findNextNode($flow, $node['id'], 0);
                                    if ($nextNodeId) {
                                        $this->activeNode = $nextNodeId;
                                        $node = $this->findNodeById($flow, $nextNodeId);
                                    } else {
                                        $node = null;
                                    }
                                    break;

                                case 'condition':
                                    $conditionResult = $this->evaluateCondition($node, $userMessage);
                                    $outputIndex = $conditionResult ? 0 : 1;
                                    $nextNodeId = $this->findNextNode($flow, $node['id'], $outputIndex);

                                    if ($nextNodeId) {
                                        $this->activeNode = $nextNodeId;
                                        $node = $this->findNodeById($flow, $nextNodeId);
                                        $tempResult['flow_processed'] = true;
                                    } else {
                                        $node = null;
                                    }
                                    break;

                                case 'action':
                                    $actionResult = $this->executeAction($node);
                                    $tempResult['action'] = $actionResult['action'];
                                    $tempResult['content'] = $actionResult['content'];
                                    $tempResult['flow_processed'] = true;

                                    $nextNodeId = $this->findNextNode($flow, $node['id'], 0);
                                    if ($nextNodeId) {
                                        $this->activeNode = $nextNodeId;
                                        $node = $this->findNodeById($flow, $nextNodeId);
                                    } else {
                                        $node = null;
                                    }
                                    break;

                                case 'api_request':
                                    $apiResult = $this->executeApiRequest($node);
                                    $tempResult['action'] = $apiResult['action'];
                                    $tempResult['content'] = $apiResult['content'];
                                    $tempResult['flow_processed'] = true;

                                    $nextNodeId = $this->findNextNode($flow, $node['id'], 0);
                                    if ($nextNodeId) {
                                        $this->activeNode = $nextNodeId;
                                        $node = $this->findNodeById($flow, $nextNodeId);
                                    } else {
                                        $node = null;
                                    }
                                    break;

                                case 'ai_generate':
                                    $aiResult = $this->executeAiGenerate($node, $userMessage);
                                    $tempResult['action'] = $aiResult['action'];
                                    $tempResult['content'] = $aiResult['content'];
                                    $tempResult['flow_processed'] = true;

                                    $nextNodeId = $this->findNextNode($flow, $node['id'], 0);
                                    if ($nextNodeId) {
                                        $this->activeNode = $nextNodeId;
                                        $node = $this->findNodeById($flow, $nextNodeId);
                                    } else {
                                        $node = null;
                                    }
                                    break;

                                case 'rag_search':
                                    $ragResult = $this->executeRagSearch($node, $userMessage);
                                    $tempResult['action'] = $ragResult['action'];
                                    $tempResult['content'] = $ragResult['content'];
                                    $tempResult['flow_processed'] = true;

                                    $nextNodeId = $this->findNextNode($flow, $node['id'], 0);
                                    if ($nextNodeId) {
                                        $this->activeNode = $nextNodeId;
                                        $node = $this->findNodeById($flow, $nextNodeId);
                                    } else {
                                        $node = null;
                                    }
                                    break;

                                case 'carousel':
                                    $carouselResult = $this->executeCarousel($node);
                                    $tempResult['action'] = $carouselResult['action'];
                                    $tempResult['content'] = $carouselResult['content'];
                                    $tempResult['flow_processed'] = true;

                                    $nextNodeId = $this->findNextNode($flow, $node['id'], 0);
                                    if ($nextNodeId) {
                                        $this->activeNode = $nextNodeId;
                                        $node = $this->findNodeById($flow, $nextNodeId);
                                    } else {
                                        $node = null;
                                    }
                                    break;

                                case 'subflow':
                                    $subflowResult = $this->executeSubflow($node, $userMessage);
                                    $tempResult['action'] = $subflowResult['action'];
                                    $tempResult['content'] = $subflowResult['content'];
                                    $tempResult['flow_processed'] = true;

                                    $nextNodeId = $this->findNextNode($flow, $node['id'], 0);
                                    if ($nextNodeId) {
                                        $this->activeNode = $nextNodeId;
                                        $node = $this->findNodeById($flow, $nextNodeId);
                                    } else {
                                        $node = null;
                                    }
                                    break;

                                case 'delay':
                                    $delayResult = $this->executeDelay($node);
                                    $tempResult['action'] = $delayResult['action'];
                                    $tempResult['content'] = $delayResult['content'];
                                    $tempResult['flow_processed'] = true;

                                    $nextNodeId = $this->findNextNode($flow, $node['id'], 0);
                                    if ($nextNodeId) {
                                        $this->activeNode = $nextNodeId;
                                        $node = $this->findNodeById($flow, $nextNodeId);
                                    } else {
                                        $node = null;
                                    }
                                    break;

                                default:
                                    $node = null; // Tipo de nodo desconocido
                                    break;
                            }
                        }

                        // Si este flujo produjo una acción, usar su resultado
                        if ($tempResult['action'] !== 'none' && $tempResult['flow_processed']) {
                            // Guardar el estado actual si el flujo fue exitoso
                            $this->saveActiveFlow();
                            return $tempResult;
                        }

                        // Restaurar el flujo activo anterior
                        $this->activeFlow = $previousActiveFlow;
                        $this->activeNode = $previousActiveNode;
                        $this->saveActiveFlow();
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Encontrar el nodo de inicio en un flujo
     * 
     * @param array $flow Datos del flujo
     * @return array|null Nodo de inicio o null si no se encuentra
     */
    private function findStartNode($flow)
    {
        if (isset($flow['nodes'])) {
            foreach ($flow['nodes'] as $node) {
                if ($node['type'] === 'start') {
                    return $node;
                }
            }
        }
        return null;
    }

    /**
     * Encontrar un nodo por su ID
     * 
     * @param array $flow Datos del flujo
     * @param int $nodeId ID del nodo a buscar
     * @return array|null Nodo encontrado o null si no existe
     */
    private function findNodeById($flow, $nodeId)
    {
        if (isset($flow['nodes'])) {
            foreach ($flow['nodes'] as $node) {
                if ($node['id'] == $nodeId) {
                    return $node;
                }
            }
        }
        return null;
    }

    /**
     * Encontrar el siguiente nodo conectado
     * 
     * @param array $flow Datos del flujo
     * @param int $currentNodeId ID del nodo actual
     * @param int $outputIndex Índice de la salida (0 para verdadero, 1 para falso en condiciones)
     * @return int|null ID del siguiente nodo o null si no hay conexión
     */
    private function findNextNode($flow, $currentNodeId, $outputIndex)
    {
        if (isset($flow['connections'])) {
            foreach ($flow['connections'] as $connection) {
                if ($connection['nodeSource'] == $currentNodeId && $connection['outputSource'] == $outputIndex) {
                    return $connection['nodeTarget'];
                }
            }
        }
        return null;
    }

    /**
     * Evaluar una condición basada en el tipo
     * 
     * @param array $nodeData Datos del nodo de condición
     * @param string $userMessage Mensaje del usuario
     * @return bool Resultado de la evaluación
     */
    private function evaluateCondition($nodeData, $userMessage)
    {
        $conditionType = $nodeData['data']['condition_type'] ?? 'text';
        $conditionText = $nodeData['data']['condition_text'] ?? '';

        // Log para debugging
        error_log("[FlowProcessor] Evaluando condición - Tipo: '$conditionType', Texto: '$conditionText', Mensaje usuario: '$userMessage'");

        switch ($conditionType) {
            case 'text':
                // Comparación exacta de texto
                $result = strtolower(trim($userMessage)) === strtolower(trim($conditionText));
                error_log("[FlowProcessor] Resultado condición 'text': " . ($result ? 'true' : 'false'));
                return $result;

            case 'contains':
                // Verificar si el mensaje contiene el texto
                $result = stripos($userMessage, $conditionText) !== false;
                error_log("[FlowProcessor] Resultado condición 'contains': " . ($result ? 'true' : 'false'));
                return $result;

            case 'ai':
                // Evaluación usando IA (Gemini)
                error_log("[FlowProcessor] Iniciando evaluación con IA");
                $result = $this->evaluateWithGemini($conditionText, $userMessage);
                error_log("[FlowProcessor] Resultado condición 'ai': " . ($result ? 'true' : 'false'));
                return $result;

            default:
                error_log("[FlowProcessor] Tipo de condición desconocido: '$conditionType'");
                return false;
        }
    }

    /**
     * Escribir log específico para debugging de IA
     * 
     * @param string $message Mensaje a escribir en el log
     */
    private function writeAILog($message)
    {
        $logFile = '/tmp/ai_evaluation_log.txt';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message" . PHP_EOL;
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }

    /**
     * Evaluar una condición usando la API de Gemini
     * 
     * @param string $condition Condición a evaluar
     * @param string $userMessage Mensaje del usuario
     * @return bool Resultado de la evaluación
     */
    private function evaluateWithGemini($condition, $userMessage)
    {
        // Log específico para IA
        $this->writeAILog("=== NUEVA EVALUACIÓN CON IA ===");
        $this->writeAILog("Condición a evaluar: '$condition'");
        $this->writeAILog("Mensaje del usuario: '$userMessage'");

        // Log para debugging general
        error_log("[FlowProcessor] Evaluando con IA - Condición: '$condition', Mensaje: '$userMessage'");

        // Verificar que tenemos una API key
        if (empty($this->geminiApiKey)) {
            $this->writeAILog("ERROR: API key de Gemini no configurada");
            error_log("[FlowProcessor] Error: API key de Gemini no configurada");
            return false;
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$this->geminiApiKey}";

        $prompt = "Evalúa la siguiente condición y responde SOLO con 'true' o 'false'.\n\n";
        $prompt .= "Condición a evaluar: $condition\n";
        $prompt .= "Mensaje del usuario: $userMessage\n\n";
        $prompt .= "Responde únicamente con 'true' si la condición se cumple o 'false' si no se cumple.";

        $this->writeAILog("Prompt enviado a Gemini:");
        $this->writeAILog($prompt);

        $data = [
            "contents" => [
                [
                    "parts" => [
                        ["text" => $prompt]
                    ]
                ]
            ],
            "generationConfig" => [
                "temperature" => 0,
                "maxOutputTokens" => 100,
                "topP" => 1,
                "topK" => 1
            ],
            "safetySettings" => [
                [
                    "category" => "HARM_CATEGORY_HARASSMENT",
                    "threshold" => "BLOCK_NONE"
                ],
                [
                    "category" => "HARM_CATEGORY_HATE_SPEECH",
                    "threshold" => "BLOCK_NONE"
                ],
                [
                    "category" => "HARM_CATEGORY_SEXUALLY_EXPLICIT",
                    "threshold" => "BLOCK_NONE"
                ],
                [
                    "category" => "HARM_CATEGORY_DANGEROUS_CONTENT",
                    "threshold" => "BLOCK_NONE"
                ]
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $this->writeAILog("Enviando petición a Gemini...");

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Log de la respuesta para debugging
        $this->writeAILog("Respuesta HTTP Code: $httpCode");
        error_log("[FlowProcessor] Respuesta de Gemini - HTTP Code: $httpCode");

        if ($curlError) {
            $this->writeAILog("Error de cURL: $curlError");
            error_log("[FlowProcessor] Error de cURL: $curlError");
            return false;
        }

        if ($httpCode === 200) {
            $responseData = json_decode($response, true);
            $this->writeAILog("Respuesta completa de Gemini:");
            $this->writeAILog(json_encode($responseData, JSON_PRETTY_PRINT));
            error_log("[FlowProcessor] Respuesta JSON: " . json_encode($responseData));

            // Intentar extraer el texto de diferentes estructuras de respuesta
            $aiResponse = null;

            if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                $aiResponse = $responseData['candidates'][0]['content']['parts'][0]['text'];
            } elseif (isset($responseData['candidates'][0]['content']['text'])) {
                $aiResponse = $responseData['candidates'][0]['content']['text'];
            } elseif (isset($responseData['candidates'][0]['text'])) {
                $aiResponse = $responseData['candidates'][0]['text'];
            } elseif (isset($responseData['text'])) {
                $aiResponse = $responseData['text'];
            }

            if ($aiResponse !== null) {
                $result = strtolower(trim($aiResponse));

                $this->writeAILog("Respuesta de la IA (original): '$aiResponse'");
                $this->writeAILog("Respuesta de la IA (procesada): '$result'");

                error_log("[FlowProcessor] Resultado de evaluación: '$result'");

                // Ser más flexible con la respuesta
                if (strpos($result, 'true') !== false) {
                    $this->writeAILog("RESULTADO FINAL: TRUE (condición se cumple)");
                    $this->writeAILog("=== FIN DE EVALUACIÓN ===");
                    $this->writeAILog("");
                    return true;
                } else if (strpos($result, 'false') !== false) {
                    $this->writeAILog("RESULTADO FINAL: FALSE (condición NO se cumple)");
                    $this->writeAILog("=== FIN DE EVALUACIÓN ===");
                    $this->writeAILog("");
                    return false;
                } else {
                    $this->writeAILog("ERROR: La IA no respondió con 'true' o 'false'. Respuesta: '$aiResponse'");
                }
            } else {
                $this->writeAILog("ERROR: No se pudo extraer texto de la respuesta de Gemini");
                $this->writeAILog("Estructura disponible: " . json_encode(array_keys($responseData)));
                if (isset($responseData['candidates'][0])) {
                    $this->writeAILog("Estructura del candidato: " . json_encode(array_keys($responseData['candidates'][0])));
                    if (isset($responseData['candidates'][0]['content'])) {
                        $this->writeAILog("Estructura del contenido: " . json_encode(array_keys($responseData['candidates'][0]['content'])));
                    }
                }
                error_log("[FlowProcessor] Error: No se pudo extraer texto de la respuesta");
            }
        } else {
            $this->writeAILog("ERROR HTTP: $httpCode");
            $this->writeAILog("Respuesta del servidor: $response");
            error_log("[FlowProcessor] Error HTTP: $httpCode - Respuesta: $response");
        }

        // Por defecto, devolver falso si hay algún error
        $this->writeAILog("RESULTADO FINAL: FALSE (por error en evaluación)");
        $this->writeAILog("=== FIN DE EVALUACIÓN ===");
        $this->writeAILog("");
        error_log("[FlowProcessor] Evaluación con IA falló, devolviendo false");
        return false;
    }

    /**
     * Ejecutar una acción
     * 
     * @param array $node Nodo de acción
     * @return array Resultado de la acción
     */
    private function executeAction($node)
    {
        $actionType = isset($node['data']['action_type']) ? $node['data']['action_type'] : 'message';
        $actionContent = isset($node['data']['action_content']) ? $node['data']['action_content'] : '';

        // Procesar variables en el contenido de la acción
        $actionContent = $this->processVariables($actionContent);

        $result = [
            'action' => $actionType,
            'content' => $actionContent
        ];

        return $result;
    }

    /**
     * Ejecutar petición API
     * 
     * @param array $node Nodo de petición API
     * @return array Resultado de la petición
     */
    private function executeApiRequest($node)
    {
        $url = $node['data']['api_url'] ?? '';
        $method = strtoupper($node['data']['api_method'] ?? 'GET');
        $headers = $node['data']['api_headers'] ?? [];
        $body = $node['data']['api_body'] ?? '';
        $responseVar = $node['data']['response_variable'] ?? 'api_response';

        // Procesar variables en URL, headers y body
        $url = $this->processVariables($url);
        $body = $this->processVariables($body);

        // Procesar headers
        $processedHeaders = [];
        if (is_array($headers)) {
            foreach ($headers as $key => $value) {
                $processedHeaders[] = $key . ': ' . $this->processVariables($value);
            }
        }

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $processedHeaders);

            switch ($method) {
                case 'POST':
                    curl_setopt($ch, CURLOPT_POST, true);
                    if (!empty($body)) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                    }
                    break;
                case 'PUT':
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                    if (!empty($body)) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                    }
                    break;
                case 'DELETE':
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                    break;
            }

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new Exception("Error cURL: " . $error);
            }

            // Guardar respuesta en variable de sesión
            $this->setSessionVariable($responseVar, $response);

            // Log para depuración
            error_log("API Response saved to variable '{$responseVar}': " . substr($response, 0, 200));

            $result = [
                'action' => 'message',
                'content' => "Petición API ejecutada exitosamente. Código HTTP: {$httpCode}. Variable '{$responseVar}' guardada."
            ];

            // Si la respuesta es JSON, intentar decodificarla
            $jsonResponse = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->setSessionVariable($responseVar . '_json', $jsonResponse);
                error_log("JSON response also saved to variable '{$responseVar}_json'");
            }

        } catch (Exception $e) {
            $result = [
                'action' => 'message',
                'content' => "Error en petición API: " . $e->getMessage()
            ];
        }

        return $result;
    }

    /**
     * Ejecutar generación con IA
     * 
     * @param array $node Nodo de generación IA
     * @param string $userMessage Mensaje del usuario
     * @return array Resultado de la generación
     */
    private function executeAiGenerate($node, $userMessage)
    {
        $provider = $node['data']['ai_provider'] ?? 'gemini';
        $prompt = $node['data']['ai_prompt'] ?? '';
        $outputVar = $node['data']['output_variable'] ?? 'ai_output';

        // Procesar variables en el prompt
        $prompt = $this->processVariables($prompt);
        $prompt = str_replace('{{user_message}}', $userMessage, $prompt);

        try {
            $aiResponse = '';

            switch ($provider) {
                case 'gemini':
                    $aiResponse = $this->callGeminiAPI($prompt);
                    break;
                default:
                    throw new Exception("Proveedor de IA no soportado: {$provider}");
            }

            // Guardar respuesta en variable de sesión
            $this->setSessionVariable($outputVar, $aiResponse);

            $result = [
                'action' => 'message',
                'content' => $aiResponse
            ];

        } catch (Exception $e) {
            $result = [
                'action' => 'message',
                'content' => "Error en generación IA: " . $e->getMessage()
            ];
        }

        return $result;
    }

    /**
     * Ejecutar búsqueda RAG en memoria
     * 
     * @param array $node Nodo de búsqueda RAG
     * @param string $userMessage Mensaje del usuario
     * @return array Resultado de la búsqueda
     */
    private function executeRagSearch($node, $userMessage)
    {
        $searchTerm = $node['data']['search_term'] ?? '{{user_message}}';
        $dataSource = $node['data']['data_source'] ?? 'all';
        $maxResults = $node['data']['max_results'] ?? 3;
        $outputVar = $node['data']['output_variable'] ?? 'search_results';

        // Procesar variables en el término de búsqueda
        $searchTerm = $this->processVariables($searchTerm);
        $searchTerm = str_replace('{{user_message}}', $userMessage, $searchTerm);

        try {
            $results = $this->searchInMemoryBank($searchTerm, $dataSource, $maxResults);

            // Guardar resultados en variable de sesión
            $this->setSessionVariable($outputVar, $results);

            $content = "Resultados de búsqueda encontrados:\n\n";
            foreach ($results as $i => $result) {
                $content .= ($i + 1) . ". " . $result . "\n\n";
            }

            $result = [
                'action' => 'message',
                'content' => $content
            ];

        } catch (Exception $e) {
            $result = [
                'action' => 'message',
                'content' => "Error en búsqueda: " . $e->getMessage()
            ];
        }

        return $result;
    }

    /**
     * Ejecutar carrusel/tarjetas
     * 
     * @param array $node Nodo de carrusel
     * @return array Resultado del carrusel
     */
    private function executeCarousel($node)
    {
        $cards = $node['data']['cards'] ?? [];

        $carouselHtml = '<div class="carousel-container">';

        foreach ($cards as $card) {
            $image = $this->processVariables($card['image'] ?? '');
            $title = $this->processVariables($card['title'] ?? '');
            $description = $this->processVariables($card['description'] ?? '');
            $buttons = $card['buttons'] ?? [];

            $carouselHtml .= '<div class="card">';

            if (!empty($image)) {
                $carouselHtml .= '<img src="' . htmlspecialchars($image) . '" alt="' . htmlspecialchars($title) . '" class="card-image">';
            }

            $carouselHtml .= '<div class="card-content">';
            $carouselHtml .= '<h3 class="card-title">' . htmlspecialchars($title) . '</h3>';
            $carouselHtml .= '<p class="card-description">' . htmlspecialchars($description) . '</p>';

            if (!empty($buttons)) {
                $carouselHtml .= '<div class="card-buttons">';
                foreach ($buttons as $button) {
                    $buttonText = htmlspecialchars($button['text'] ?? '');
                    $buttonValue = htmlspecialchars($button['value'] ?? '');
                    $buttonUrl = $button['url'] ?? '';

                    if (!empty($buttonUrl)) {
                        $carouselHtml .= '<a href="' . htmlspecialchars($buttonUrl) . '" class="card-button" target="_blank">' . $buttonText . '</a>';
                    } else {
                        $carouselHtml .= '<button class="card-button" onclick="sendMessage(\'' . $buttonValue . '\')">' . $buttonText . '</button>';
                    }
                }
                $carouselHtml .= '</div>';
            }

            $carouselHtml .= '</div></div>';
        }

        $carouselHtml .= '</div>';

        return [
            'action' => 'html',
            'content' => $carouselHtml
        ];
    }

    /**
     * Ejecutar sub-flujo
     * 
     * @param array $node Nodo de sub-flujo
     * @param string $userMessage Mensaje del usuario
     * @return array Resultado del sub-flujo
     */
    private function executeSubflow($node, $userMessage)
    {
        $subflowId = $node['data']['subflow_id'] ?? '';
        $variableMapping = $node['data']['variable_mapping'] ?? [];

        try {
            // Verificar que el sub-flujo existe
            if (!isset($this->flows[$subflowId])) {
                throw new Exception("Sub-flujo no encontrado: {$subflowId}");
            }

            $subflow = $this->flows[$subflowId];

            // Mapear variables de entrada
            foreach ($variableMapping as $mapping) {
                $sourceVar = $mapping['source'] ?? '';
                $targetVar = $mapping['target'] ?? '';
                if (!empty($sourceVar) && !empty($targetVar)) {
                    $value = $this->getSessionVariable($sourceVar);
                    $this->setSessionVariable($targetVar, $value);
                }
            }

            // Crear una nueva instancia del procesador para el sub-flujo
            $subProcessor = new FlowProcessor($this->sessionId, $this->geminiApiKey);
            $subProcessor->activeFlow = $subflowId;
            $subProcessor->activeNode = $this->findStartNode($subflow)['id'];

            // Procesar el sub-flujo
            $subResult = $subProcessor->processMessage($userMessage);

            return $subResult;

        } catch (Exception $e) {
            return [
                'action' => 'message',
                'content' => "Error en sub-flujo: " . $e->getMessage()
            ];
        }
    }

    /**
     * Ejecutar pausa/retardo
     * 
     * @param array $node Nodo de pausa
     * @return array Resultado de la pausa
     */
    private function executeDelay($node)
    {
        $duration = floatval($node['data']['duration'] ?? 2.0);
        $showTyping = $node['data']['show_typing'] ?? true;

        // En un entorno web, no podemos hacer una pausa real del servidor
        // En su lugar, enviamos instrucciones al frontend
        $result = [
            'action' => 'delay',
            'content' => [
                'duration' => $duration,
                'show_typing' => $showTyping
            ]
        ];

        return $result;
    }

    /**
     * Procesar variables en texto
     * 
     * @param string $text Texto con variables
     * @return string Texto con variables reemplazadas
     */
    private function processVariables($text)
    {
        // Log para depuración
        error_log("Processing variables in text: " . $text);
        error_log("Available variables: " . json_encode($_SESSION['flow_variables'] ?? []));

        // Buscar patrones {{variable}}
        return preg_replace_callback('/\{\{([^}]+)\}\}/', function ($matches) {
            $varName = trim($matches[1]);
            $value = $this->getSessionVariable($varName, $matches[0]);
            error_log("Variable '{$varName}' resolved to: " . (is_string($value) ? substr($value, 0, 100) : json_encode($value)));
            return $value;
        }, $text);
    }

    /**
     * Obtener variable de sesión
     * 
     * @param string $name Nombre de la variable
     * @param mixed $default Valor por defecto
     * @return mixed Valor de la variable
     */
    private function getSessionVariable($name, $default = '')
    {
        if (!isset($_SESSION['flow_variables'])) {
            $_SESSION['flow_variables'] = [];
        }

        // Verificar si es una variable con notación de punto (ej: api_response_json.origin)
        if (strpos($name, '.') !== false) {
            $parts = explode('.', $name);
            $baseVar = array_shift($parts);

            // Obtener la variable base
            $value = $_SESSION['flow_variables'][$baseVar] ?? null;

            if ($value === null) {
                return $default;
            }

            // Si es un string JSON, decodificarlo
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $value = $decoded;
                }
            }

            // Navegar por las propiedades anidadas
            foreach ($parts as $part) {
                error_log("[DEBUG] Looking for part: '" . $part . "' in value type: " . gettype($value));
                if (is_array($value)) {
                    error_log("[DEBUG] Available keys: " . json_encode(array_keys($value)));
                    if (isset($value[$part])) {
                        $value = $value[$part];
                        error_log("[DEBUG] Found value: " . (is_string($value) ? substr($value, 0, 100) : json_encode($value)));
                    } else {
                        error_log("[DEBUG] Part '" . $part . "' not found in array");
                        return $default;
                    }
                } else {
                    error_log("[DEBUG] Value is not an array, cannot access property '" . $part . "'");
                    return $default;
                }
            }

            return $value;
        }

        return $_SESSION['flow_variables'][$name] ?? $default;
    }

    /**
     * Establecer variable de sesión
     * 
     * @param string $name Nombre de la variable
     * @param mixed $value Valor de la variable
     */
    private function setSessionVariable($name, $value)
    {
        if (!isset($_SESSION['flow_variables'])) {
            $_SESSION['flow_variables'] = [];
        }
        $_SESSION['flow_variables'][$name] = $value;
    }

    /**
     * Llamar a la API de Gemini
     * 
     * @param string $prompt Prompt para la IA
     * @return string Respuesta de la IA
     */
    private function callGeminiAPI($prompt)
    {
        if (empty($this->geminiApiKey)) {
            throw new Exception("API key de Gemini no configurada");
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$this->geminiApiKey}";

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
                "maxOutputTokens" => 1000,
                "topP" => 1,
                "topK" => 1
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("Error cURL: " . $error);
        }

        if ($httpCode !== 200) {
            throw new Exception("Error HTTP: {$httpCode} - {$response}");
        }

        $responseData = json_decode($response, true);

        if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            return $responseData['candidates'][0]['content']['parts'][0]['text'];
        }

        throw new Exception("Respuesta inesperada de la API de Gemini");
    }

    /**
     * Buscar en el banco de memoria
     * 
     * @param string $searchTerm Término de búsqueda
     * @param string $dataSource Fuente de datos
     * @param int $maxResults Máximo número de resultados
     * @return array Resultados de la búsqueda
     */
    private function searchInMemoryBank($searchTerm, $dataSource, $maxResults)
    {
        $results = [];
        $memoryBankPath = "memory-bank/";

        // Buscar en archivos de texto
        if ($dataSource === 'files' || $dataSource === 'all') {
            $files = glob($memoryBankPath . "*.txt");
            foreach ($files as $file) {
                $content = file_get_contents($file);
                if (stripos($content, $searchTerm) !== false) {
                    // Extraer fragmento relevante
                    $fragment = $this->extractRelevantFragment($content, $searchTerm);
                    $results[] = "[" . basename($file) . "] " . $fragment;

                    if (count($results) >= $maxResults) {
                        break;
                    }
                }
            }
        }

        // Buscar en URLs extraídas (implementar según necesidad)
        if ($dataSource === 'urls' || $dataSource === 'all') {
            // Implementar búsqueda en contenido de URLs
        }

        return array_slice($results, 0, $maxResults);
    }

    /**
     * Extraer fragmento relevante de texto
     * 
     * @param string $content Contenido completo
     * @param string $searchTerm Término de búsqueda
     * @return string Fragmento relevante
     */
    private function extractRelevantFragment($content, $searchTerm)
    {
        $pos = stripos($content, $searchTerm);
        if ($pos === false) {
            return substr($content, 0, 200) . "...";
        }

        $start = max(0, $pos - 100);
        $length = 200;
        $fragment = substr($content, $start, $length);

        if ($start > 0) {
            $fragment = "..." . $fragment;
        }

        if ($start + $length < strlen($content)) {
            $fragment .= "...";
        }

        return $fragment;
    }
}
?>