<?php
session_start();

// Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_instructions'])) {
    $instructions = $_POST['instructions'];
    $welcome_message = $_POST['welcome_message'];
    $target_dir = "memory-bank/";
    
    // Crear el directorio si no existe
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Guardar las instrucciones en un archivo
    $result1 = file_put_contents($target_dir . "instructions.txt", $instructions);
    
    // Guardar el mensaje de bienvenida en un archivo
    $result2 = file_put_contents($target_dir . "welcome_message.txt", $welcome_message);
    
    if ($result1 !== false && $result2 !== false) {
        // Redirigir con mensaje de éxito
        $_SESSION['instruction_message'] = "La configuración se ha guardado correctamente.";
    } else {
        // Redirigir con mensaje de error
        $_SESSION['instruction_message'] = "Error al guardar la configuración. Verifica los permisos de escritura.";
    }
    
    // Redirigir de vuelta al panel de administración
    header("Location: admin.php");
    exit;
}

// Si llegamos aquí, la solicitud no es válida
header("Location: admin.php");
exit;
?>