<?php
session_start();

// Inicializar variables
$flow_id = '';
$flow_name = '';
$flow_description = '';
$flow_nodes = [];
$flow_connections = [];
$is_new = true;

// Directorio para guardar los flujos
$flows_dir = "memory-bank/flows/";
if (!file_exists($flows_dir)) {
    mkdir($flows_dir, 0777, true);
}

// Verificar si estamos editando un flujo existente
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $flow_id = $_GET['id'];
    $flow_file = $flows_dir . $flow_id . '.json';
    
    if (file_exists($flow_file)) {
        $flow_data = json_decode(file_get_contents($flow_file), true);
        if ($flow_data) {
            $flow_name = $flow_data['name'] ?? '';
            $flow_description = $flow_data['description'] ?? '';
            $flow_nodes = $flow_data['nodes'] ?? [];
            $flow_connections = $flow_data['connections'] ?? [];
            $is_new = false;
        }
    }
}

// Si es un flujo nuevo, generar un ID único
if ($is_new) {
    $flow_id = uniqid('flow_');
}

// Procesar el guardado del flujo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_flow'])) {
    // Obtener los datos del formulario
    $flow_id = $_POST['flow_id'];
    $flow_name = $_POST['flow_name'];
    $flow_description = $_POST['flow_description'];
    
    // Obtener los nodos y conexiones del diagrama
    $flow_nodes = json_decode($_POST['flow_nodes'], true) ?: [];
    $flow_connections = json_decode($_POST['flow_connections'], true) ?: [];
    
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
    
    // Redirigir al panel de administración
    header("Location: admin.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editor de Flujos</title>
    <link rel="stylesheet" href="css/styles.css">
    <!-- Incluir Drawflow para el editor de diagramas -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/drawflow@0.0.59/dist/drawflow.min.css">
    <script src="https://cdn.jsdelivr.net/npm/drawflow@0.0.59/dist/drawflow.min.js"></script>
    <style>
        .editor-container {
            width: 100%;
            height: 70vh;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            margin-bottom: 20px;
        }
        
        #drawflow {
            width: 100%;
            height: 100%;
            position: relative;
            background-color: #f9f9f9;
            background-image: radial-gradient(#e3e3e3 1px, transparent 1px);
            background-size: 20px 20px;
        }
        
        .node-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .node-item {
            padding: 8px 15px;
            background-color: #4CAF50;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            user-select: none;
        }
        
        .node-item.condition {
            background-color: #2196F3;
        }
        
        .node-item.action {
            background-color: #FF9800;
        }
        
        .drawflow-node {
            border-radius: 8px;
            padding: 10px;
            min-width: 200px;
        }
        
        .drawflow-node.condition {
            background-color: #e3f2fd;
            border: 2px solid #2196F3;
        }
        
        .drawflow-node.action {
            background-color: #fff3e0;
            border: 2px solid #FF9800;
        }
        
        .drawflow-node.start {
            background-color: #e8f5e9;
            border: 2px solid #4CAF50;
        }
        
        .node-content {
            padding: 10px;
        }
        
        .node-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .node-form {
            margin-top: 10px;
        }
        
        .node-form input, .node-form select, .node-form textarea {
            width: 100%;
            margin-bottom: 5px;
            padding: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Editor de Flujos de Conversación</h1>
            <a href="admin.php" class="back-link">Volver al Panel</a>
        </header>
        
        <div class="flow-editor">
            <form id="flow-form" action="flow_editor.php" method="post">
                <input type="hidden" name="flow_id" value="<?php echo htmlspecialchars($flow_id); ?>">
                <input type="hidden" id="flow_nodes" name="flow_nodes" value="<?php echo htmlspecialchars(json_encode($flow_nodes)); ?>">
                <input type="hidden" id="flow_connections" name="flow_connections" value="<?php echo htmlspecialchars(json_encode($flow_connections)); ?>">
                
                <div class="form-group">
                    <label for="flow_name">Nombre del Flujo:</label>
                    <input type="text" id="flow_name" name="flow_name" value="<?php echo htmlspecialchars($flow_name); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="flow_description">Descripción:</label>
                    <textarea id="flow_description" name="flow_description" rows="2"><?php echo htmlspecialchars($flow_description); ?></textarea>
                </div>
                
                <h3>Componentes del Flujo</h3>
                <div class="node-container">
                    <div class="node-item start" draggable="true" data-node-type="start">Inicio</div>
                    <div class="node-item condition" draggable="true" data-node-type="condition">Condición</div>
                    <div class="node-item action" draggable="true" data-node-type="action">Acción</div>
                    <div class="node-item api_request" draggable="true" data-node-type="api_request">API Request</div>
                    <div class="node-item ai_generate" draggable="true" data-node-type="ai_generate">IA Generate</div>
                    <div class="node-item rag_search" draggable="true" data-node-type="rag_search">RAG Search</div>
                    <div class="node-item carousel" draggable="true" data-node-type="carousel">Carrusel</div>
                    <div class="node-item subflow" draggable="true" data-node-type="subflow">Sub-flujo</div>
                    <div class="node-item delay" draggable="true" data-node-type="delay">Pausa</div>
                </div>
                
                <div class="editor-container">
                    <div id="drawflow"></div>
                </div>
                
                <button type="submit" name="save_flow" class="btn">Guardar Flujo</button>
            </form>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar el editor Drawflow
            const editor = new Drawflow(document.getElementById('drawflow'));
            editor.start();
            
            // Configurar eventos de arrastre para los nodos
            const nodeItems = document.querySelectorAll('.node-item');
            nodeItems.forEach(item => {
                item.addEventListener('dragstart', function(e) {
                    e.dataTransfer.setData('node-type', this.getAttribute('data-node-type'));
                });
            });
            
            // Permitir soltar nodos en el editor
            const drawflowContainer = document.getElementById('drawflow');
            drawflowContainer.addEventListener('dragover', function(e) {
                e.preventDefault();
            });
            
            drawflowContainer.addEventListener('drop', function(e) {
                e.preventDefault();
                const nodeType = e.dataTransfer.getData('node-type');
                if (nodeType) {
                    const rect = drawflowContainer.getBoundingClientRect();
                    const posX = e.clientX - rect.left;
                    const posY = e.clientY - rect.top;
                    
                    addNode(nodeType, posX, posY);
                }
            });
            
            // Función para añadir un nodo al editor
            function addNode(type, posX, posY) {
                let nodeId = editor.addNode(
                    type, // Nombre del nodo
                    0,    // Entradas
                    1,    // Salidas
                    posX, // Posición X
                    posY, // Posición Y
                    type, // Clase
                    {}, // Datos
                    createNodeHTML(type) // HTML del nodo
                );
                
                // Si es un nodo de condición, añadir una salida adicional
                if (type === 'condition') {
                    editor.addNodeOutput(nodeId);
                }
            }
            
            // Crear HTML para cada tipo de nodo
            function createNodeHTML(type) {
                let title = '';
                let content = '';
                
                switch(type) {
                    case 'start':
                        title = 'Inicio del Flujo';
                        content = '<p>Punto de entrada del flujo</p>';
                        break;
                    case 'condition':
                        title = 'Condición';
                        content = `
                            <div class="node-form">
                                <input type="text" placeholder="Condición a evaluar" class="condition-text">
                                <select class="condition-type">
                                    <option value="text">Texto exacto</option>
                                    <option value="contains">Contiene texto</option>
                                    <option value="ai">Evaluación con IA</option>
                                </select>
                                <div class="outputs-label">
                                    <span>Si es verdadero →</span>
                                    <span>Si es falso →</span>
                                </div>
                            </div>
                        `;
                        break;
                    case 'action':
                        title = 'Acción';
                        content = `
                            <div class="node-form">
                                <select class="action-type">
                                    <option value="message">Enviar mensaje</option>
                                    <option value="redirect">Redireccionar</option>
                                    <option value="api">Llamar API</option>
                                    <option value="function">Ejecutar función</option>
                                </select>
                                <textarea placeholder="Contenido de la acción" class="action-content"></textarea>
                            </div>
                        `;
                        break;
                }
                
                return `
                    <div class="node-content">
                        <div class="node-title">${title}</div>
                        ${content}
                    </div>
                `;
            }
            
            // Cargar flujo existente si hay datos
            const flowNodes = <?php echo json_encode($flow_nodes); ?>;
            const flowConnections = <?php echo json_encode($flow_connections); ?>;
            
            if (flowNodes && flowNodes.length > 0) {
                flowNodes.forEach(node => {
                    editor.addNode(
                        node.type,
                        node.inputs || 0,
                        node.outputs || 1,
                        node.posX,
                        node.posY,
                        node.class || node.type,
                        node.data || {},
                        node.html || createNodeHTML(node.type)
                    );
                });
                
                if (flowConnections && flowConnections.length > 0) {
                    flowConnections.forEach(conn => {
                        editor.addConnection(
                            conn.nodeSource,
                            conn.nodeTarget,
                            conn.outputSource,
                            conn.inputTarget
                        );
                    });
                }
            }
            
            // Guardar el estado del editor cuando se envía el formulario
            document.getElementById('flow-form').addEventListener('submit', function(e) {
                const editorData = editor.export();
                
                document.getElementById('flow_nodes').value = JSON.stringify(editorData.drawflow.Home.data);
                
                // Extraer las conexiones
                const connections = [];
                Object.values(editorData.drawflow.Home.data).forEach(node => {
                    if (node.outputs) {
                        Object.entries(node.outputs).forEach(([outputIndex, output]) => {
                            if (output.connections) {
                                output.connections.forEach(conn => {
                                    connections.push({
                                        nodeSource: node.id,
                                        nodeTarget: conn.node,
                                        outputSource: parseInt(outputIndex),
                                        inputTarget: conn.input
                                    });
                                });
                            }
                        });
                    }
                });
                
                document.getElementById('flow_connections').value = JSON.stringify(connections);
            });
        });
    </script>
</body>
</html>
