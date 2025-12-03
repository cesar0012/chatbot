<?php
session_start();

// Verificar si hay mensajes en la sesión
if (isset($_SESSION['instruction_message'])) {
    $instruction_message = $_SESSION['instruction_message'];
    unset($_SESSION['instruction_message']);
}

if (isset($_SESSION['welcome_message'])) {
    $welcome_message = $_SESSION['welcome_message'];
    unset($_SESSION['welcome_message']);
}

if (isset($_SESSION['flow_message'])) {
    $flow_message = $_SESSION['flow_message'];
    unset($_SESSION['flow_message']);
}

// Verificar si hay mensajes de URL en la sesión
if (isset($_SESSION['url_message'])) {
    $url_message = $_SESSION['url_message'];
    $url_message_type = isset($_SESSION['url_message_type']) ? $_SESSION['url_message_type'] : 'success';
    unset($_SESSION['url_message']);
    unset($_SESSION['url_message_type']);
}

// Verificar si se ha subido un archivo
if (isset($_POST['submit'])) {
    $target_dir = "memory-bank/";
    
    // Crear el directorio si no existe
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
    $uploadOk = 1;
    $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Verificar si el archivo es un documento válido (txt, pdf, doc, docx, csv)
    if($fileType != "txt" && $fileType != "pdf" && $fileType != "doc" && $fileType != "docx" && $fileType != "csv") {
        $message = "Lo siento, solo se permiten archivos TXT, PDF, DOC, DOCX y CSV.";
        $uploadOk = 0;
    }
    
    // Verificar si $uploadOk está establecido en 0 por un error
    if ($uploadOk == 0) {
        $message = "Tu archivo no fue subido.";
    } else {
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            $message = "El archivo ". htmlspecialchars(basename($_FILES["fileToUpload"]["name"])). " ha sido subido.";
        } else {
            $message = "Lo siento, hubo un error al subir tu archivo.";
        }
    }
}

// Obtener lista de archivos en memory-bank
$files = [];
$memory_bank_dir = "memory-bank/";
if (file_exists($memory_bank_dir) && is_dir($memory_bank_dir)) {
    $files = array_diff(scandir($memory_bank_dir), array('..', '.'));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración del Chatbot</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Estilos personalizados para la barra de navegación -->
    <link rel="stylesheet" href="css/navbar-custom.css">
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/modal.css">
    <link rel="stylesheet" href="css/flow-editor-custom.css">
    <link rel="stylesheet" href="css/file-upload.css">
    <link rel="stylesheet" href="css/tabs-custom.css">
    <link rel="stylesheet" href="css/flow-toggle.css">
    <link rel="stylesheet" href="css/notifications.css">
    <!-- Incluir Drawflow para el editor de diagramas -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/drawflow@0.0.59/dist/drawflow.min.css">
    <script src="https://cdn.jsdelivr.net/npm/drawflow@0.0.59/dist/drawflow.min.js"></script>
    <script src="js/flow-editor.js"></script>
    <script src="js/file-upload.js"></script>
    <script src="js/url-processor.js"></script>
    <script src="js/tabs-initializer.js"></script>
    <script src="js/flow-toggle.js"></script>
    <!-- Bootstrap JS Bundle con Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
    <div class="container-fluid px-0">
        <div class="custom-navbar">
            <div class="custom-navbar-container">
                <a class="custom-navbar-brand" href="#">
                    <i class="fas fa-robot"></i>Panel de Administración
                </a>
                <div class="custom-navbar-menu">
                    <a class="custom-navbar-link" href="chatbot.php">
                        <i class="fas fa-comments"></i>Volver al Chatbot
                    </a>
                </div>
            </div>
        </div>
        
        <div class="container py-4">
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h1 class="h3 mb-0">Configuración del Asistente Virtual</h1>
                        <span class="badge bg-success py-2 px-3">Activo</span>
                    </div>
                    <p class="text-muted">Personaliza tu asistente virtual y administra su base de conocimientos</p>
                </div>
            </div>
        
        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-cog text-primary me-2 fs-4"></i>
                            <h2 class="h5 mb-0 fw-bold">Instrucciones Personalizadas</h2>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if(isset($instruction_message)): ?>
                        <div class="alert alert-success d-flex align-items-center" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <div><?php echo $instruction_message; ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if(isset($welcome_message)): ?>
                        <div class="alert alert-success d-flex align-items-center" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <div><?php echo $welcome_message; ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <form action="save_instructions.php" method="post">
                            <div class="mb-3">
                                <label for="instructions" class="form-label fw-medium">Instrucciones para el chatbot:</label>
                                <textarea name="instructions" id="instructions" rows="6" class="form-control bg-light"><?php echo file_exists('memory-bank/instructions.txt') ? htmlspecialchars(file_get_contents('memory-bank/instructions.txt')) : ''; ?></textarea>
                                <div class="form-text text-muted small">
                                    <i class="fas fa-info-circle me-1"></i> Estas instrucciones guiarán el comportamiento del chatbot. Serán combinadas con la base de conocimientos.
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="welcome_message" class="form-label fw-medium">Mensaje inicial del chatbot:</label>
                                <textarea name="welcome_message" id="welcome_message" rows="3" class="form-control bg-light"><?php echo file_exists('memory-bank/welcome_message.txt') ? htmlspecialchars(file_get_contents('memory-bank/welcome_message.txt')) : '¡Hola! Soy tu asistente virtual. ¿En qué puedo ayudarte hoy?'; ?></textarea>
                                <div class="form-text text-muted small">
                                    <i class="fas fa-info-circle me-1"></i> Este es el primer mensaje que el chatbot mostrará al usuario.
                                </div>
                            </div>
                            <button type="submit" name="save_instructions" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Guardar Configuración
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-project-diagram text-primary me-2 fs-4"></i>
                                <h2 class="h5 mb-0 fw-bold">Flujos de Conversación</h2>
                            </div>
                            <button id="create-flow-btn" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus me-1"></i> Nuevo Flujo
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if(isset($flow_message)): ?>
                        <div class="alert alert-success d-flex align-items-center" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <div><?php echo $flow_message; ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <p class="text-muted small mb-3">
                            <i class="fas fa-info-circle me-1"></i> Crea y edita flujos de conversación con condiciones y acciones automatizadas.
                        </p>
                        
                        <?php 
                        $flows = [];
                        $flows_dir = "memory-bank/flows/";
                        if (file_exists($flows_dir) && is_dir($flows_dir)) {
                            $flow_files = glob($flows_dir . "*.json");
                            foreach ($flow_files as $flow_file) {
                                $flow_data = json_decode(file_get_contents($flow_file), true);
                                if ($flow_data) {
                                    $flows[] = [
                                        'id' => basename($flow_file, '.json'),
                                        'name' => $flow_data['name'] ?? 'Flujo sin nombre',
                                        'description' => $flow_data['description'] ?? 'Sin descripción',
                                        'active' => isset($flow_data['active']) ? $flow_data['active'] : false
                                    ];
                                }
                            }
                        }
                        ?>
                        
                        <?php if(count($flows) > 0): ?>
                        <div class="list-group">
                            <?php foreach($flows as $flow): ?>
                            <div class="list-group-item list-group-item-action border-0 mb-2 rounded-3 p-3 bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <div class="flow-icon bg-primary bg-opacity-10 p-2 rounded-circle me-3">
                                            <i class="fas fa-random text-primary"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1 fw-semibold"><?php echo htmlspecialchars($flow['name']); ?></h6>
                                            <p class="text-muted small mb-0"><?php echo htmlspecialchars($flow['description']); ?></p>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <!-- Toggle switch animado -->
                                        <div class="flow-toggle-wrapper me-2">
                                            <label class="flow-toggle" for="flow-toggle-<?php echo $flow['id']; ?>">
                                                <input type="checkbox" class="flow-toggle-checkbox" id="flow-toggle-<?php echo $flow['id']; ?>" data-flow-id="<?php echo $flow['id']; ?>" <?php echo $flow['active'] ? 'checked' : ''; ?>>
                                                <div class="flow-toggle-switch"></div>
                                            </label>
                                        </div>
                                        <!-- Botones de acción -->
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary edit-flow-btn" data-flow-id="<?php echo urlencode($flow['id']); ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-danger delete-flow-btn" data-flow-id="<?php echo urlencode($flow['id']); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4 bg-light rounded-3">
                            <i class="fas fa-info-circle text-muted fs-3 mb-2"></i>
                            <p class="text-muted mb-0">No hay flujos de conversación configurados.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-database text-primary me-2 fs-4"></i>
                                <h2 class="h5 mb-0 fw-bold">Base de Conocimiento</h2>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if(isset($message)): ?>
                            <div class="alert <?php echo (strpos($message, 'ha sido subido') !== false) ? 'alert-success' : 'alert-danger'; ?> d-flex align-items-center" role="alert">
                                <i class="fas <?php echo (strpos($message, 'ha sido subido') !== false) ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
                                <div><?php echo $message; ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if(isset($_SESSION['url_message'])): ?>
                            <div class="alert <?php echo ($_SESSION['url_message_type'] === 'success') ? 'alert-success' : 'alert-danger'; ?> d-flex align-items-center" role="alert">
                                <i class="fas <?php echo ($_SESSION['url_message_type'] === 'success') ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
                                <div><?php echo $_SESSION['url_message']; ?></div>
                            </div>
                            <?php unset($_SESSION['url_message']); unset($_SESSION['url_message_type']); ?>
                            <?php endif; ?>
                            
                            <div class="row mb-4">
                                <div class="col-lg-6">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body p-4">
                                            <h5 class="card-title mb-3"><i class="fas fa-database text-primary me-2"></i> Añadir a la Base de Conocimiento</h5>
                                            
                                            <div class="d-flex mb-3">
                                                <button class="btn btn-primary me-2" id="btn-file-tab" onclick="showTab('pills-file')">
                                                    <i class="fas fa-file-upload me-1"></i> Subir Archivo
                                                </button>
                                                <button class="btn btn-outline-primary" id="btn-url-tab" onclick="showTab('pills-url')">
                                                    <i class="fas fa-globe me-1"></i> Extraer URL
                                                </button>
                                            </div>
                                            
                                            <script>
                                            function showTab(tabId) {
                                                // Ocultar todos los paneles
                                                document.querySelectorAll('.tab-pane').forEach(function(pane) {
                                                    pane.style.display = 'none';
                                                });
                                                
                                                // Mostrar el panel seleccionado
                                                document.getElementById(tabId).style.display = 'block';
                                                
                                                // Actualizar estilos de los botones
                                                if (tabId === 'pills-file') {
                                                    document.getElementById('btn-file-tab').className = 'btn btn-primary me-2';
                                                    document.getElementById('btn-url-tab').className = 'btn btn-outline-primary';
                                                } else {
                                                    document.getElementById('btn-file-tab').className = 'btn btn-outline-primary me-2';
                                                    document.getElementById('btn-url-tab').className = 'btn btn-primary';
                                                }
                                            }
                                            </script>
                                            
                                            <div class="tab-content" id="pills-tabContent">
                                                <div class="tab-pane" id="pills-file" style="display:block;">
                                                    <form action="admin.php" method="post" enctype="multipart/form-data" class="mt-3" id="upload-form">
                                                        <!-- Área para arrastrar y soltar archivos -->
                                                        <div id="drop-area" class="drop-area mb-3">
                                                            <div class="drop-icon">
                                                                <i class="fas fa-cloud-upload-alt"></i>
                                                            </div>
                                                            <h4 class="mb-2">Arrastra y suelta archivos aquí</h4>
                                                            <p class="text-muted small">o haz clic para seleccionar</p>
                                                            
                                                            <div class="file-input-wrapper">
                                                                <div class="file-input-button">
                                                                    <i class="fas fa-folder-open me-1"></i> Seleccionar archivo
                                                                </div>
                                                                <input type="file" name="fileToUpload" id="fileToUpload" class="form-control" accept=".txt,.pdf,.doc,.docx,.csv">
                                                            </div>
                                                        </div>
                                                        
                                                        <!-- Lista de archivos seleccionados -->
                                                        <div id="file-list" class="file-list mb-3"></div>
                                                        
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div class="form-text text-muted small">
                                                                <i class="fas fa-info-circle me-1"></i> Formatos permitidos: TXT, PDF, DOC, DOCX, CSV
                                                            </div>
                                                            <button type="submit" name="submit" id="upload-button" class="btn btn-primary" disabled>
                                                                <i class="fas fa-upload me-1"></i> Subir archivo
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                                
                                                <div class="tab-pane" id="pills-url" style="display:none;">
                                                    <?php if(isset($url_message)): ?>
                                                    <div class="alert alert-<?php echo ($url_message_type == 'success') ? 'success' : 'danger'; ?> d-flex align-items-center mt-3" role="alert">
                                                        <i class="fas <?php echo ($url_message_type == 'success') ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
                                                        <div><?php echo $url_message; ?></div>
                                                    </div>
                                                    <?php endif; ?>
                                                    
                                                    <form action="scrape_url.php" method="post" class="mt-3" id="url-form">
                                                        <div class="card bg-white border-0 shadow-sm p-3 mb-3">
                                                            <div class="card-body p-2">
                                                                <h5 class="card-title fw-semibold mb-3">
                                                                    <i class="fas fa-globe text-primary me-2"></i> Extraer contenido de una página web
                                                                </h5>
                                                                
                                                                <div class="mb-3">
                                                                    <label for="url" class="form-label fw-medium">URL a procesar:</label>
                                                                    <div class="url-input-wrapper">
                                                                        <i class="fas fa-link url-icon"></i>
                                                                        <input type="url" class="form-control" name="url" id="url" placeholder="https://ejemplo.com" required>
                                                                    </div>
                                                                </div>
                                                                
                                                                <div class="form-text text-muted small mb-3">
                                                                    <i class="fas fa-info-circle me-1"></i> Se extraerá el contenido de la página web y se guardará como parte de la base de conocimiento.
                                                                </div>
                                                                
                                                                <div class="d-flex justify-content-end">
                                                                    <button type="submit" class="btn btn-primary">
                                                                        <i class="fas fa-download me-1"></i> Procesar URL
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <!-- Indicador de carga -->
                                                        <div id="loading-indicator" class="loading-indicator">
                                                            <div class="spinner mb-2"></div>
                                                            <p class="text-muted">Procesando URL, por favor espera...</p>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-lg-6">
                                    <div class="card h-100 border-0 bg-light">
                                        <div class="card-body p-4">
                                            <div class="d-flex align-items-center justify-content-between mb-3">
                                                <h5 class="fw-semibold mb-0 fs-6">
                                                    <i class="fas fa-brain text-primary me-2"></i> Elementos en la Base de Conocimiento
                                                </h5>
                                                <span class="badge bg-primary rounded-pill"><?php echo count($files); ?></span>
                                            </div>
                                            
                                            <?php if(count($files) > 0): ?>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-hover align-middle">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th scope="col" class="text-nowrap">Tipo</th>
                                                            <th scope="col">Nombre</th>
                                                            <th scope="col" class="text-end">Acciones</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach($files as $file): ?>
                                                        <?php 
                                                            $fileType = 'Documento';
                                                            $fileIcon = 'fa-file-alt';
                                                            $fileClass = 'text-secondary';
                                                            
                                                            if (strpos($file, 'url_') === 0) {
                                                                $fileType = 'URL';
                                                                $fileIcon = 'fa-globe';
                                                                $fileClass = 'text-info';
                                                            } elseif ($file === 'instructions.txt') {
                                                                $fileType = 'Instrucciones';
                                                                $fileIcon = 'fa-file-code';
                                                                $fileClass = 'text-success';
                                                            } elseif ($file === 'welcome_message.txt') {
                                                                $fileType = 'Mensaje';
                                                                $fileIcon = 'fa-comment';
                                                                $fileClass = 'text-warning';
                                                            } elseif (strpos($file, '.pdf') !== false) {
                                                                $fileIcon = 'fa-file-pdf';
                                                                $fileClass = 'text-danger';
                                                            } elseif (strpos($file, '.doc') !== false) {
                                                                $fileIcon = 'fa-file-word';
                                                                $fileClass = 'text-primary';
                                                            } elseif (strpos($file, '.csv') !== false) {
                                                                $fileIcon = 'fa-file-csv';
                                                                $fileClass = 'text-success';
                                                            }
                                                        ?>
                                                        <tr>
                                                            <td class="text-nowrap">
                                                                <i class="fas <?php echo $fileIcon; ?> <?php echo $fileClass; ?> me-1"></i>
                                                                <span class="small"><?php echo $fileType; ?></span>
                                                            </td>
                                                            <td class="text-truncate" style="max-width: 200px;">
                                                                <span class="small"><?php echo $file; ?></span>
                                                            </td>
                                                            <td class="text-end">
                                                                <div class="btn-group btn-group-sm">
                                                                    <a href="memory-bank/<?php echo $file; ?>" target="_blank" class="btn btn-outline-primary btn-sm" title="Ver">
                                                                        <i class="fas fa-eye"></i>
                                                                    </a>
                                                                    <a href="delete_file.php?file=<?php echo urlencode($file); ?>" class="btn btn-outline-danger btn-sm" title="Eliminar" onclick="return confirm('¿Estás seguro de eliminar este archivo?')">
                                                                        <i class="fas fa-trash"></i>
                                                                    </a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <?php else: ?>
                                            <div class="text-center py-4">
                                                <i class="fas fa-database text-muted opacity-25 fs-1 mb-2"></i>
                                                <p class="text-muted mb-0">No hay elementos en la base de conocimiento.</p>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Modal del Editor de Flujos -->
    <div id="flow-editor-modal" class="flow-editor-modal">
        <div class="flow-editor-content">
            <div class="flow-editor-header">
                <h2 id="flow-editor-title" class="flow-editor-title">Editor de Flujos de Conversación</h2>
                <span class="flow-editor-close close-modal">&times;</span>
            </div>
            
            <div class="flow-editor-body">
                <form id="flow-form" action="save_flow.php" method="post">
                    <input type="hidden" id="flow_id" name="flow_id" value="">
                    <input type="hidden" id="flow_nodes" name="flow_nodes" value="[]">
                    <input type="hidden" id="flow_connections" name="flow_connections" value="[]">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="flow-form-group">
                                <label for="flow_name">Nombre del Flujo:</label>
                                <input type="text" id="flow_name" name="flow_name" value="" required placeholder="Ej: Flujo de Bienvenida">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="flow-form-group">
                                <label for="flow_description">Descripción:</label>
                                <textarea id="flow_description" name="flow_description" rows="1" placeholder="Breve descripción del flujo"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <h3 class="flow-components-title">Componentes del Flujo</h3>
                    <p class="text-muted small mb-2">Arrastra los componentes al área de trabajo para crear tu flujo</p>
                    
                    <div class="flow-node-container">
                        <div class="node-item flow-node-item start" draggable="true" data-node-type="start">
                            <i class="fas fa-play-circle"></i> Inicio
                        </div>
                        <div class="node-item flow-node-item condition" draggable="true" data-node-type="condition">
                            <i class="fas fa-code-branch"></i> Condición
                        </div>
                        <div class="node-item flow-node-item action" draggable="true" data-node-type="action">
                            <i class="fas fa-bolt"></i> Acción
                        </div>
                        <div class="node-item flow-node-item api_request" draggable="true" data-node-type="api_request">
                            <i class="fas fa-exchange-alt"></i> API Request
                        </div>
                        <div class="node-item flow-node-item ai_generate" draggable="true" data-node-type="ai_generate">
                            <i class="fas fa-brain"></i> IA Generate
                        </div>
                        <div class="node-item flow-node-item rag_search" draggable="true" data-node-type="rag_search">
                            <i class="fas fa-search"></i> RAG Search
                        </div>
                        <div class="node-item flow-node-item carousel" draggable="true" data-node-type="carousel">
                            <i class="fas fa-images"></i> Carrusel
                        </div>
                        <div class="node-item flow-node-item subflow" draggable="true" data-node-type="subflow">
                            <i class="fas fa-sitemap"></i> Sub-flujo
                        </div>
                        <div class="node-item flow-node-item delay" draggable="true" data-node-type="delay">
                            <i class="fas fa-clock"></i> Pausa
                        </div>
                    </div>
                    
                    <div class="flow-editor-container">
                        <div id="drawflow"></div>
                    </div>
                    
                    <div class="flow-form-actions">
                        <button type="submit" name="save_flow" class="flow-btn flow-btn-primary">
                            <i class="fas fa-save"></i> Guardar Flujo
                        </button>
                        <button type="button" class="flow-btn flow-btn-light close-modal-btn">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal de Confirmación de Eliminación -->
    <div id="delete-confirm-modal" class="flow-editor-modal">
        <div class="flow-editor-content" style="max-width: 500px;">
            <div class="flow-editor-header">
                <h2 class="flow-editor-title">Confirmar Eliminación</h2>
                <span class="flow-editor-close close-modal">&times;</span>
            </div>
            
            <div class="flow-editor-body">
                <div class="text-center mb-4">
                    <i class="fas fa-exclamation-triangle text-danger fs-1 mb-3"></i>
                    <p>¿Estás seguro de que deseas eliminar este flujo?</p>
                    <p class="text-muted small">Esta acción no se puede deshacer.</p>
                </div>
                
                <div class="flow-form-actions">
                    <button id="confirm-delete-btn" class="flow-btn flow-btn-danger">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                    <button class="flow-btn flow-btn-light close-modal-btn">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
                <input type="hidden" id="delete_flow_id" value="">
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Referencias a los modales
            const flowEditorModal = document.getElementById('flow-editor-modal');
            const deleteConfirmModal = document.getElementById('delete-confirm-modal');
            
            // Referencias a los elementos del formulario
            const flowForm = document.getElementById('flow-form');
            const flowIdInput = document.getElementById('flow_id');
            const flowNameInput = document.getElementById('flow_name');
            const flowDescriptionInput = document.getElementById('flow_description');
            const flowNodesInput = document.getElementById('flow_nodes');
            const flowConnectionsInput = document.getElementById('flow_connections');
            const deleteFlowIdInput = document.getElementById('delete_flow_id');
            
            // Botón para crear un nuevo flujo
            document.getElementById('create-flow-btn').addEventListener('click', function() {
                // Limpiar el formulario
                flowIdInput.value = 'flow_' + Date.now();
                flowNameInput.value = '';
                flowDescriptionInput.value = '';
                flowNodesInput.value = '[]';
                flowConnectionsInput.value = '[]';
                
                // Actualizar título del modal
                document.getElementById('flow-editor-title').textContent = 'Crear Nuevo Flujo';
                
                // Inicializar el editor
                initDrawFlow();
                
                // Mostrar el modal
                flowEditorModal.style.display = 'block';
            });
            
            // Botones para editar flujos existentes
            document.querySelectorAll('.edit-flow-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const flowId = this.getAttribute('data-flow-id');
                    
                    // Cargar los datos del flujo mediante AJAX
                    fetch('get_flow.php?id=' + encodeURIComponent(flowId))
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Rellenar el formulario con los datos del flujo
                                flowIdInput.value = flowId;
                                flowNameInput.value = data.flow.name || '';
                                flowDescriptionInput.value = data.flow.description || '';
                                flowNodesInput.value = JSON.stringify(data.flow.nodes || []);
                                flowConnectionsInput.value = JSON.stringify(data.flow.connections || []);
                                
                                // Actualizar título del modal
                                document.getElementById('flow-editor-title').textContent = 'Editar Flujo';
                                
                                // Inicializar el editor con los datos cargados
                                initDrawFlow(data.flow.nodes, data.flow.connections);
                                
                                // Mostrar el modal
                                flowEditorModal.style.display = 'block';
                            } else {
                                alert('Error al cargar el flujo: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Error al cargar el flujo. Por favor, intenta de nuevo.');
                        });
                });
            });
            
            // Botones para eliminar flujos
            document.querySelectorAll('.delete-flow-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const flowId = this.getAttribute('data-flow-id');
                    deleteFlowIdInput.value = flowId;
                    deleteConfirmModal.style.display = 'block';
                });
            });
            
            // Botón de confirmación de eliminación
            document.getElementById('confirm-delete-btn').addEventListener('click', function() {
                const flowId = deleteFlowIdInput.value;
                if (flowId) {
                    window.location.href = 'delete_flow.php?id=' + encodeURIComponent(flowId);
                }
            });
            
            // Cerrar modales
            document.querySelectorAll('.close-modal, .close-modal-btn').forEach(element => {
                element.addEventListener('click', function() {
                    flowEditorModal.style.display = 'none';
                    deleteConfirmModal.style.display = 'none';
                });
            });
            
            // Cerrar modal al hacer clic fuera del contenido
            window.addEventListener('click', function(event) {
                if (event.target === flowEditorModal) {
                    flowEditorModal.style.display = 'none';
                } else if (event.target === deleteConfirmModal) {
                    deleteConfirmModal.style.display = 'none';
                }
            });
            // El código JavaScript para el editor de flujos se ha movido al archivo js/flow-editor.js
            
            // Actualizar datos del formulario al enviar
            flowForm.addEventListener('submit', function(e) {
                updateFormData();
            });
        });
    </script>
</body>
</html>