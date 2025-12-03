<?php
session_start();

// Verificar que la solicitud incluya un ID de flujo
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $flow_id = $_GET['id'];
    $flows_dir = "memory-bank/flows/";
    $flow_file = $flows_dir . $flow_id . '.json';
    
    // Verificar que el archivo exista
    if (file_exists($flow_file)) {
        // Leer el contenido del archivo
        $flow_data = json_decode(file_get_contents($flow_file), true);
        
        if ($flow_data) {
            // Devolver los datos del flujo como JSON
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'flow' => $flow_data
            ]);
            exit;
        } else {
            // Error al decodificar el JSON
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error al decodificar los datos del flujo.'
            ]);
            exit;
        }
    } else {
        // El archivo no existe
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'El flujo solicitado no existe.'
        ]);
        exit;
    }
} else {
    // ID de flujo no válido
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'ID de flujo no válido.'
    ]);
    exit;
}
?>
