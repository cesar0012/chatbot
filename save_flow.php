<?php
session_start();

// Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_flow'])) {
    // Obtener los datos del formulario
    $flow_id = $_POST['flow_id'];
    $flow_name = $_POST['flow_name'];
    $flow_description = $_POST['flow_description'];
    
    // Obtener los nodos y conexiones del diagrama
    $flow_nodes = json_decode($_POST['flow_nodes'], true) ?: [];
    $flow_connections = json_decode($_POST['flow_connections'], true) ?: [];
    
    // Directorio para guardar los flujos
    $flows_dir = "memory-bank/flows/";
    if (!file_exists($flows_dir)) {
        mkdir($flows_dir, 0777, true);
    }
    
    // Crear el objeto de flujo
    $flow_data = [
        'name' => $flow_name,
        'description' => $flow_description,
        'nodes' => $flow_nodes,
        'connections' => $flow_connections,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Guardar el flujo
    $result = file_put_contents($flows_dir . $flow_id . '.json', json_encode($flow_data, JSON_PRETTY_PRINT));
    
    if ($result !== false) {
        $_SESSION['flow_message'] = "El flujo se ha guardado correctamente.";
    } else {
        $_SESSION['flow_message'] = "Error al guardar el flujo. Verifica los permisos de escritura.";
    }
}

// Redirigir de vuelta al panel de administración
header("Location: admin.php");
exit;
?>
