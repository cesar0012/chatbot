<?php
session_start();

// Verificar si se ha especificado un archivo para eliminar
if (isset($_GET['file'])) {
    $filename = $_GET['file'];
    $file_path = "memory-bank/" . $filename;
    
    // Verificar que el archivo existe y está dentro del directorio memory-bank
    if (file_exists($file_path) && is_file($file_path) && dirname(realpath($file_path)) === realpath("memory-bank")) {
        // Intentar eliminar el archivo
        if (unlink($file_path)) {
            $_SESSION['message'] = "El archivo $filename ha sido eliminado correctamente.";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error al eliminar el archivo $filename.";
            $_SESSION['message_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "El archivo especificado no existe o no es válido.";
        $_SESSION['message_type'] = "error";
    }
} else {
    $_SESSION['message'] = "No se ha especificado ningún archivo para eliminar.";
    $_SESSION['message_type'] = "error";
}

// Redirigir de vuelta a la página de administración
header("Location: admin.php");
exit;
?>