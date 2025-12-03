<?php
session_start();

// Verificar si se recibió un ID de flujo
if (isset($_POST['flow_id'])) {
    $flow_id = $_POST['flow_id'];
    $flows_dir = "memory-bank/flows/";
    $flow_file = $flows_dir . $flow_id . ".json";
    
    // Verificar si el archivo existe
    if (file_exists($flow_file)) {
        // Leer el contenido del archivo
        $flow_data = json_decode(file_get_contents($flow_file), true);
        
        // Cambiar el estado activo del flujo
        $flow_data['active'] = isset($flow_data['active']) ? !$flow_data['active'] : true;
        
        // Guardar los cambios
        file_put_contents($flow_file, json_encode($flow_data, JSON_PRETTY_PRINT));
        
        // Devolver el nuevo estado como JSON
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'active' => $flow_data['active']]);
        exit;
    } else {
        // El archivo no existe
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Flujo no encontrado']);
        exit;
    }
} else {
    // No se recibió un ID de flujo
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID de flujo no especificado']);
    exit;
}
?>
