<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Obtener datos JSON del cuerpo de la petición
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['action']) || $data['action'] !== 'test_api') {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit;
}

$url = $data['api_url'] ?? '';
$method = strtoupper($data['api_method'] ?? 'GET');
$headers = $data['api_headers'] ?? '';
$body = $data['api_body'] ?? '';

if (empty($url)) {
    echo json_encode(['success' => false, 'error' => 'URL requerida']);
    exit;
}

try {
    // Inicializar cURL
    $ch = curl_init();
    
    // Configurar opciones básicas
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Chatbot API Tester/1.0'
    ]);
    
    // Configurar método HTTP
    switch ($method) {
        case 'POST':
            curl_setopt($ch, CURLOPT_POST, true);
            break;
        case 'PUT':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            break;
        case 'DELETE':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            break;
        case 'GET':
        default:
            // GET es el método por defecto
            break;
    }
    
    // Procesar headers
    $processedHeaders = [];
    if (!empty($headers)) {
        $headersArray = json_decode($headers, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($headersArray)) {
            foreach ($headersArray as $key => $value) {
                $processedHeaders[] = $key . ': ' . $value;
            }
        } else {
            // Si no es JSON válido, intentar procesar como texto
            $lines = explode("\n", $headers);
            foreach ($lines as $line) {
                $line = trim($line);
                if (!empty($line) && strpos($line, ':') !== false) {
                    $processedHeaders[] = $line;
                }
            }
        }
    }
    
    if (!empty($processedHeaders)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $processedHeaders);
    }
    
    // Configurar body si es necesario
    if (!empty($body) && in_array($method, ['POST', 'PUT'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    
    // Ejecutar petición
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception("Error cURL: " . $error);
    }
    
    // Intentar decodificar como JSON
    $jsonResponse = json_decode($response, true);
    $responseData = (json_last_error() === JSON_ERROR_NONE) ? $jsonResponse : $response;
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'http_code' => $httpCode,
        'response' => $responseData,
        'raw_response' => $response
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>