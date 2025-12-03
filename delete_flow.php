<?php
session_start();

// Verificar que la solicitud incluya un ID de flujo
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $flow_id = $_GET['id'];
    $flows_dir = "memory-bank/flows/";
    $flow_file = $flows_dir . $flow_id . '.json';
    
    // Verificar que el archivo exista
    if (file_exists($flow_file)) {
        // Intentar eliminar el archivo
        if (unlink($flow_file)) {
            $_SESSION['flow_message'] = "El flujo ha sido eliminado correctamente.";
        } else {
            $_SESSION['flow_message'] = "Error al eliminar el flujo. Verifica los permisos.";
        }
    } else {
        $_SESSION['flow_message'] = "El flujo no existe.";
    }
} else {
    $_SESSION['flow_message'] = "ID de flujo no válido.";
}

// Redirigir de vuelta al panel de administración
header("Location: admin.php");
exit;
?>
