<?php
session_start();

// Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['url'])) {
    $url = trim($_POST['url']);
    $target_dir = "memory-bank/";
    
    // Crear el directorio si no existe
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Validar URL
    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
        $_SESSION['url_message'] = "La URL proporcionada no es válida.";
        $_SESSION['url_message_type'] = "error";
        header("Location: admin.php");
        exit;
    }
    
    // Configurar opciones de contexto para simular un navegador
    $options = [
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language: es-ES,es;q=0.8,en-US;q=0.5,en;q=0.3',
            ]
        ]
    ];
    
    $context = stream_context_create($options);
    
    // Intentar obtener el contenido de la URL
    $html = @file_get_contents($url, false, $context);
    
    if ($html === false) {
        $_SESSION['url_message'] = "No se pudo acceder al contenido de la URL.";
        $_SESSION['url_message_type'] = "error";
        header("Location: admin.php");
        exit;
    }
    
    // Extraer el título de la página
    preg_match('/<title>(.*?)<\/title>/i', $html, $matches);
    $title = isset($matches[1]) ? $matches[1] : "pagina_" . date("Y-m-d_H-i-s");
    $title = preg_replace('/[^a-zA-Z0-9_-]/', '_', $title); // Sanitizar el título para usarlo como nombre de archivo
    
    // Intentar extraer el contenido principal
    $mainContent = "";
    
    // Buscar contenido en etiquetas comunes de contenido principal
    $contentTags = ['article', 'main', 'div[class*="content"]', 'div[class*="main"]', 'div[id*="content"]', 'div[id*="main"]'];
    
    // Extraer el texto principal (eliminando HTML)
    $text = strip_tags($html);
    
    // Eliminar scripts y estilos que pueden contener mucho texto irrelevante
    $text = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $text);
    $text = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $text);
    
    // Eliminar espacios en blanco excesivos
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);
    
    // Extraer metadatos si están disponibles
    $metadata = [];
    
    // Extraer descripción
    preg_match('/<meta\s+name=["\']description["\']\s+content=["\']([^"\']*)["\']/i', $html, $descMatches);
    if (!empty($descMatches[1])) {
        $metadata['description'] = $descMatches[1];
    }
    
    // Extraer palabras clave
    preg_match('/<meta\s+name=["\']keywords["\']\s+content=["\']([^"\']*)["\']/i', $html, $keywordsMatches);
    if (!empty($keywordsMatches[1])) {
        $metadata['keywords'] = $keywordsMatches[1];
    }
    
    // Preparar el contenido para guardar
    $content = "URL: $url\n\n";
    
    // Agregar metadatos si existen
    if (!empty($metadata)) {
        $content .= "Metadatos:\n";
        foreach ($metadata as $key => $value) {
            $content .= "- $key: $value\n";
        }
        $content .= "\n";
    }
    
    $content .= "Título: $title\n\nContenido extraído:\n$text";
    
    // Guardar el contenido en un archivo
    $filename = $target_dir . "url_" . substr(md5($url), 0, 8) . "_" . substr($title, 0, 30) . ".txt";
    $result = file_put_contents($filename, $content);
    
    if ($result !== false) {
        $_SESSION['url_message'] = "El contenido de la URL ha sido extraído y guardado correctamente en la base de conocimiento.";
        $_SESSION['url_message_type'] = "success";
    } else {
        $_SESSION['url_message'] = "Error al guardar el contenido de la URL. Verifica los permisos de escritura.";
        $_SESSION['url_message_type'] = "error";
    }
    
    // Redirigir de vuelta al panel de administración
    header("Location: admin.php");
    exit;
}

// Si llegamos aquí, la solicitud no es válida
header("Location: admin.php");
exit;
?>