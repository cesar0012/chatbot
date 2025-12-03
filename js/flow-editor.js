// Variables globales
let editor;
let isDragging = false;
let dragStartTime = 0;
let lastDropTime = 0;
const DRAG_THROTTLE = 50; // ms entre operaciones de arrastre para mejorar rendimiento

// Referencia a los elementos del formulario (inicializados en initFlowEditor)
let flowNodesInput;
let flowConnectionsInput;

// Inicializar el editor de flujos
function initFlowEditor() {
    console.log('Inicializando editor de flujos');
    
    // Inicializar referencias a los elementos del formulario
    flowNodesInput = document.getElementById('flow_nodes');
    flowConnectionsInput = document.getElementById('flow_connections');
    
    if (!flowNodesInput || !flowConnectionsInput) {
        console.error('No se encontraron los elementos del formulario');
        return;
    }
    
    // Obtener los datos del flujo actual
    const flowId = document.getElementById('flow_id').value;
    const flowName = document.getElementById('flow_name').value;
    const flowDescription = document.getElementById('flow_description').value;
    
    console.log(`Inicializando flujo: ${flowName} (ID: ${flowId})`);
    
    // Obtener los nodos y conexiones
    let nodes = [];
    let connections = [];
    
    try {
        // Intentar parsear los nodos y conexiones si existen
        if (flowNodesInput.value && flowNodesInput.value !== '[]') {
            nodes = JSON.parse(flowNodesInput.value);
            console.log(`Cargando ${nodes.length} nodos existentes`);
        }
        
        if (flowConnectionsInput.value && flowConnectionsInput.value !== '[]') {
            connections = JSON.parse(flowConnectionsInput.value);
            console.log(`Cargando ${connections.length} conexiones existentes`);
        }
    } catch (e) {
        console.error('Error al parsear los datos del flujo:', e);
    }
    
    // Inicializar DrawFlow
    initDrawFlow(nodes, connections);
    
    // Configurar eventos de arrastre después de un pequeño retraso para asegurar que el DOM esté listo
    setTimeout(() => {
        setupDragEvents();
        console.log('Eventos de arrastre configurados');
    }, 300);
}

// Inicializar DrawFlow
function initDrawFlow(nodes = [], connections = []) {
    // Limpiar el contenedor
    const drawflowContainer = document.getElementById('drawflow');
    drawflowContainer.innerHTML = '';
    
    // Crear una nueva instancia de DrawFlow
    editor = new Drawflow(drawflowContainer);
    
    // Configurar el editor
    editor.reroute = true;
    editor.curvature = 0.5;
    editor.force_first_input = false;
    editor.zoom_max = 1.5;
    editor.zoom_min = 0.5;
    editor.zoom_value = 1;
    
    // Importante: Configurar el modo de conexión antes de iniciar
    editor.connectionMode = 'strict'; // Esto asegura que las conexiones sigan reglas estrictas
    
    // Iniciar el editor
    editor.start();
    
    // Configurar eventos del editor
    editor.on('nodeCreated', function(id) {
        console.log('Nodo creado:', id);
        // Añadir clases específicas al nodo recién creado
        const nodeElement = document.querySelector(`.drawflow-node[id="node-${id}"]`);
        if (nodeElement) {
            const nodeData = editor.getNodeFromId(id);
            if (nodeData) {
                nodeElement.classList.add(nodeData.name);
                console.log(`Clase ${nodeData.name} añadida al nodo ${id}`);
            }
        }
        updateFormData();
    });
    
    // Configurar event listener delegado para botones de editar
    drawflowContainer.addEventListener('click', function(e) {
        if (e.target.closest('.node-edit-btn')) {
            e.stopPropagation();
            e.preventDefault();
            
            const button = e.target.closest('.node-edit-btn');
            const nodeType = button.getAttribute('data-node-type');
            openNodeEditModal(nodeType, button);
        }
    });
    
    editor.on('nodeRemoved', function(id) {
        console.log('Nodo eliminado:', id);
        updateFormData();
    });
    
    editor.on('connectionCreated', function(connection) {
        console.log('Conexión creada:', connection);
        // Destacar visualmente la conexión creada
        setTimeout(() => {
            const connectionElement = document.querySelector(`.connection.node_in_${connection.input_id}.node_out_${connection.output_id}`);
            if (connectionElement) {
                connectionElement.classList.add('connection-active');
                setTimeout(() => {
                    connectionElement.classList.remove('connection-active');
                }, 1000);
            }
            updateFormData();
        }, 100);
    });
    
    editor.on('connectionRemoved', function(connection) {
        console.log('Conexión eliminada:', connection);
        updateFormData();
    });
    
    // Agregar evento para detectar cambios en las conexiones
    editor.on('connectionChange', function(connection) {
        console.log('Conexión modificada:', connection);
        updateFormData();
    });
    
    // Evento adicional para capturar cualquier cambio en el editor
    editor.on('zoom', function(zoom) {
        // Actualizar datos cuando hay cambios en el zoom (puede indicar cambios en el editor)
        setTimeout(updateFormData, 50);
    });
    
    editor.on('mouseMove', function(position) {
        // Esto ayuda a mejorar la experiencia de conexión
        document.querySelectorAll('.drawflow .drawflow-node .input, .drawflow .drawflow-node .output').forEach(point => {
            point.classList.remove('connecting');
        });
    });
    
    editor.on('connectionStart', function(position) {
        console.log('Inicio de conexión');
        document.body.classList.add('connecting-mode');
        
        // Resaltar todos los puntos de conexión válidos
        document.querySelectorAll('.drawflow .drawflow-node .input').forEach(point => {
            point.classList.add('highlight-connection');
        });
    });
    
    editor.on('connectionCancel', function(position) {
        console.log('Conexión cancelada');
        document.body.classList.remove('connecting-mode');
        
        // Quitar resaltado de los puntos de conexión
        document.querySelectorAll('.drawflow .drawflow-node .input, .drawflow .drawflow-node .output').forEach(point => {
            point.classList.remove('highlight-connection');
        });
    });
    
    // Configurar el modo de conexión (ya configurado antes de iniciar)
    
    // Cargar flujo existente usando el formato de Drawflow
    if (nodes && nodes.length > 0) {
        // Convertir nodos al formato esperado por Drawflow
        const drawflowData = {
            drawflow: {
                Home: {
                    data: {}
                }
            }
        };
        
        // Convertir nodos
        nodes.forEach(node => {
            // Configurar inputs/outputs según el tipo de nodo
            let inputs = {};
            let outputs = {};
            
            switch(node.type) {
                case 'start':
                    outputs = { "output_1": { "connections": [] } };
                    break;
                case 'condition':
                    inputs = { "input_1": { "connections": [] } };
                    outputs = { 
                        "output_1": { "connections": [] },
                        "output_2": { "connections": [] }
                    };
                    break;
                case 'action':
                    inputs = { "input_1": { "connections": [] } };
                    outputs = { "output_1": { "connections": [] } };
                    break;
                case 'api_request':
                    inputs = { "input_1": { "connections": [] } };
                    outputs = { "output_1": { "connections": [] } };
                    break;
                case 'ai_generate':
                    inputs = { "input_1": { "connections": [] } };
                    outputs = { "output_1": { "connections": [] } };
                    break;
                case 'rag':
                    inputs = { "input_1": { "connections": [] } };
                    outputs = { "output_1": { "connections": [] } };
                    break;
                case 'carousel':
                    inputs = { "input_1": { "connections": [] } };
                    outputs = { "output_1": { "connections": [] } };
                    break;
                case 'subflow':
                    inputs = { "input_1": { "connections": [] } };
                    outputs = { "output_1": { "connections": [] } };
                    break;
                case 'delay':
                    inputs = { "input_1": { "connections": [] } };
                    outputs = { "output_1": { "connections": [] } };
                    break;
            }
            
            drawflowData.drawflow.Home.data[node.id] = {
                id: node.id,
                name: node.type,
                data: node.data || {},
                class: node.type,
                html: createNodeHTML(node.type, node.data),
                typenode: false,
                inputs: inputs,
                outputs: outputs,
                pos_x: node.posX || 100,
                pos_y: node.posY || 100
            };
        });
        
        // Agregar conexiones al formato Drawflow
        if (connections && connections.length > 0) {
            connections.forEach(conn => {
                const sourceId = parseInt(conn.nodeSource);
                const targetId = parseInt(conn.nodeTarget);
                
                // Manejar casos donde outputSource/inputTarget son null
                let outputIndex = 0;
                let inputIndex = 0;
                
                if (conn.outputSource !== null && conn.outputSource !== undefined) {
                    outputIndex = parseInt(conn.outputSource);
                }
                
                if (conn.inputTarget !== null && conn.inputTarget !== undefined) {
                    inputIndex = parseInt(conn.inputTarget);
                }
                
                const outputKey = `output_${outputIndex + 1}`;
                const inputKey = `input_${inputIndex + 1}`;
                
                console.log(`Cargando conexión: ${sourceId} -> ${targetId} (${outputKey} -> ${inputKey})`);
                
                // Agregar conexión en el nodo fuente
                if (drawflowData.drawflow.Home.data[sourceId] && 
                    drawflowData.drawflow.Home.data[sourceId].outputs[outputKey]) {
                    drawflowData.drawflow.Home.data[sourceId].outputs[outputKey].connections.push({
                        node: targetId.toString(),
                        output: inputKey
                    });
                } else {
                    console.warn(`No se pudo agregar conexión de salida: nodo ${sourceId}, output ${outputKey}`);
                }
                
                // Agregar conexión en el nodo destino
                if (drawflowData.drawflow.Home.data[targetId] && 
                    drawflowData.drawflow.Home.data[targetId].inputs[inputKey]) {
                    drawflowData.drawflow.Home.data[targetId].inputs[inputKey].connections.push({
                        node: sourceId.toString(),
                        input: outputKey
                    });
                } else {
                    console.warn(`No se pudo agregar conexión de entrada: nodo ${targetId}, input ${inputKey}`);
                }
            });
        }
        
        console.log('Importando datos del flujo:', drawflowData);
        
        // Importar los datos al editor
        editor.import(drawflowData);
        
        console.log('Flujo importado exitosamente');
    }
    
    // Configurar observer para detectar cambios en las conexiones
    setupConnectionObserver();
}

// Función para configurar un observer que detecte cambios en las conexiones
function setupConnectionObserver() {
    const drawflowContainer = document.querySelector('#drawflow');
    if (!drawflowContainer) return;
    
    // Observer para detectar cambios en el DOM de las conexiones
    const observer = new MutationObserver(function(mutations) {
        let connectionChanged = false;
        
        mutations.forEach(function(mutation) {
            // Detectar si se agregaron o eliminaron conexiones
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function(node) {
                    if (node.classList && (node.classList.contains('connection') || node.classList.contains('main-path'))) {
                        connectionChanged = true;
                    }
                });
                
                mutation.removedNodes.forEach(function(node) {
                    if (node.classList && (node.classList.contains('connection') || node.classList.contains('main-path'))) {
                        connectionChanged = true;
                    }
                });
            }
        });
        
        if (connectionChanged) {
            console.log('Cambio en conexiones detectado por observer');
            setTimeout(updateFormData, 100);
        }
    });
    
    // Configurar el observer
    observer.observe(drawflowContainer, {
        childList: true,
        subtree: true,
        attributes: false
    });
    
    console.log('Observer de conexiones configurado');
}

// Configurar eventos de arrastre
function setupDragEvents() {
    // Configurar eventos de arrastre para los nodos (una sola vez)
    // Buscar tanto las clases antiguas como las nuevas para compatibilidad
    const nodeItems = document.querySelectorAll('.node-item, .flow-node-item');
    
    console.log('Configurando eventos de arrastre para', nodeItems.length, 'elementos');
    
    // Eliminar eventos anteriores para evitar duplicados
    nodeItems.forEach(item => {
        item.removeEventListener('dragstart', handleDragStart);
        item.addEventListener('dragstart', handleDragStart);
        console.log('Evento dragstart configurado para', item.textContent.trim());
    });
    
    // Configurar el contenedor para recibir elementos
    const drawflowContainer = document.getElementById('drawflow');
    
    if (!drawflowContainer) {
        console.error('No se encontró el contenedor drawflow');
        return;
    }
    
    // Eliminar eventos anteriores para evitar duplicados
    drawflowContainer.removeEventListener('dragover', handleDragOver);
    drawflowContainer.removeEventListener('drop', handleDrop);
    
    // Añadir nuevos eventos
    drawflowContainer.addEventListener('dragover', handleDragOver);
    drawflowContainer.addEventListener('drop', handleDrop);
    console.log('Eventos dragover y drop configurados para el contenedor');
}

// Manejar el inicio del arrastre
function handleDragStart(e) {
    // Usar efectos de arrastre para mejor rendimiento
    e.dataTransfer.effectAllowed = 'copy';
    
    // Obtener el tipo de nodo
    const nodeType = this.getAttribute('data-node-type');
    console.log('Iniciando arrastre de tipo:', nodeType);
    
    if (!nodeType) {
        console.error('Error: No se pudo obtener el tipo de nodo');
        return;
    }
    
    // Guardar el tipo de nodo en el dataTransfer en múltiples formatos para mayor compatibilidad
    e.dataTransfer.setData('text/plain', nodeType);
    e.dataTransfer.setData('node-type', nodeType);
    e.dataTransfer.setData('application/json', JSON.stringify({type: nodeType}));
    
    try {
        // Establecer una imagen de arrastre personalizada para mejor feedback visual
        const dragIcon = document.createElement('div');
        dragIcon.className = `flow-node-item ${nodeType}`;
        dragIcon.style.width = '100px';
        dragIcon.style.height = '40px';
        dragIcon.style.borderRadius = '8px';
        dragIcon.style.opacity = '0.7';
        dragIcon.textContent = nodeType.charAt(0).toUpperCase() + nodeType.slice(1);
        document.body.appendChild(dragIcon);
        e.dataTransfer.setDragImage(dragIcon, 50, 20);
        setTimeout(() => document.body.removeChild(dragIcon), 0);
    } catch (error) {
        console.error('Error al crear imagen de arrastre:', error);
    }
    
    // Registrar el tiempo de inicio del arrastre
    dragStartTime = Date.now();
    
    // Añadir clase de arrastre para feedback visual
    this.classList.add('dragging');
    
    console.log('Datos de arrastre configurados:', nodeType);
}

// Manejar el evento dragover
function handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'copy';
    
    // Añadir indicador visual de dónde se soltará el elemento
    const drawflowContainer = document.getElementById('drawflow');
    drawflowContainer.classList.add('drag-over');
}

// Manejar el evento drop
function handleDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    
    console.log('Evento drop recibido');
    
    // Eliminar clase de arrastre
    const drawflowContainer = document.getElementById('drawflow');
    if (!drawflowContainer) {
        console.error('No se encontró el contenedor drawflow');
        return;
    }
    
    drawflowContainer.classList.remove('drag-over');
    document.querySelectorAll('.node-item, .flow-node-item').forEach(item => {
        item.classList.remove('dragging');
    });
    
    // Evitar múltiples operaciones de drop en un corto período
    const now = Date.now();
    if (now - lastDropTime < DRAG_THROTTLE) {
        console.log('Ignorando drop por throttle');
        return;
    }
    lastDropTime = now;
    
    // Si ya estamos en medio de una operación de arrastre, ignorar este evento
    if (isDragging) {
        console.log('Ignorando drop porque ya estamos arrastrando');
        return;
    }
    
    // Intentar obtener el tipo de nodo de diferentes formatos
    let nodeType = null;
    
    try {
        // Intentar obtener el tipo de nodo de diferentes formatos
        nodeType = e.dataTransfer.getData('text/plain');
        if (!nodeType) {
            nodeType = e.dataTransfer.getData('node-type');
        }
        
        // Intentar obtener desde JSON si los anteriores fallaron
        if (!nodeType) {
            const jsonData = e.dataTransfer.getData('application/json');
            if (jsonData) {
                const data = JSON.parse(jsonData);
                nodeType = data.type;
            }
        }
        
        // Último recurso: intentar obtener el tipo desde el elemento que se está arrastrando
        if (!nodeType) {
            const draggingElement = document.querySelector('.dragging');
            if (draggingElement) {
                nodeType = draggingElement.getAttribute('data-node-type');
            }
        }
    } catch (error) {
        console.error('Error al obtener el tipo de nodo:', error);
    }
    
    console.log('Tipo de nodo recibido en drop:', nodeType);
    
    if (nodeType) {
        // Marcar que estamos en proceso de arrastre
        isDragging = true;
        
        const rect = drawflowContainer.getBoundingClientRect();
        const posX = e.clientX - rect.left;
        const posY = e.clientY - rect.top;
        
        console.log('Posición del drop:', posX, posY);
        
        // Añadir el nodo con un pequeño retraso para asegurar que el DOM esté listo
        setTimeout(() => {
            // Añadir el nodo
            const nodeId = addNode(nodeType, posX, posY);
            console.log(`Nodo añadido con ID: ${nodeId}`);
            
            // Restablecer el estado de arrastre
            isDragging = false;
            console.log('Estado de arrastre restablecido');
        }, 50);
    } else {
        console.error('No se pudo obtener el tipo de nodo del evento drop');
    }
}

// Función para añadir un nodo al editor
function addNode(type, posX, posY) {
    console.log(`Añadiendo nodo de tipo: ${type} en posición (${posX}, ${posY})`);
    
    // Configurar entradas y salidas según el tipo de nodo
    let inputs = 0;
    let outputs = 0;
    let data = {};
    
    switch(type) {
        case 'start':
            inputs = 0;
            outputs = 1;
            data = { name: 'Inicio', description: 'Punto de entrada del flujo' };
            break;
        case 'condition':
            inputs = 1;
            outputs = 2;
            data = { 
                name: 'Condición', 
                condition_text: '', 
                condition_type: 'text',
                output_labels: ['Verdadero', 'Falso']
            };
            break;
        case 'action':
            inputs = 1;
            outputs = 1;
            data = { 
                name: 'Acción', 
                action_type: 'message', 
                action_content: '' 
            };
            break;
        case 'api_request':
            inputs = 1;
            outputs = 1;
            data = {
                name: 'API Request',
                api_url: '',
                api_method: 'GET',
                api_headers: {},
                api_body: '',
                response_variable: 'api_response'
            };
            break;
        case 'ai_generate':
            inputs = 1;
            outputs = 1;
            data = {
                name: 'IA Generate',
                ai_provider: 'gemini',
                ai_prompt: '',
                output_variable: 'ai_output'
            };
            break;
        case 'rag_search':
            inputs = 1;
            outputs = 1;
            data = {
                name: 'RAG Search',
                search_term: '{{user_message}}',
                data_source: 'all',
                max_results: 3,
                output_variable: 'search_results'
            };
            break;
        case 'carousel':
            inputs = 1;
            outputs = 1;
            data = {
                name: 'Carrusel',
                cards: []
            };
            break;
        case 'subflow':
            inputs = 1;
            outputs = 1;
            data = {
                name: 'Sub-flujo',
                subflow_id: '',
                variable_mapping: []
            };
            break;
        case 'delay':
            inputs = 1;
            outputs = 1;
            data = {
                name: 'Pausa',
                duration: 2.0,
                show_typing: true
            };
            break;
        default:
            console.error('Tipo de nodo desconocido:', type);
            return null;
    }
    
    try {
        // Añadir el nodo al editor
        const nodeId = editor.addNode(
            type,           // Tipo de nodo
            inputs,         // Número de entradas
            outputs,        // Número de salidas
            posX,           // Posición X
            posY,           // Posición Y
            type,           // Clase CSS
            data,           // Datos iniciales
            createNodeHTML(type, data)  // Contenido HTML
        );
        
        console.log(`Nodo añadido con éxito, ID: ${nodeId}, Tipo: ${type}`);
        
        // Aplicar estilos específicos al nodo recién creado
        const nodeElement = document.querySelector(`.drawflow-node[id="node-${nodeId}"]`);
        if (nodeElement) {
            // Añadir clase para estilos específicos
            nodeElement.classList.add(type);
            
            // Mejorar la visibilidad de los puntos de conexión
            const inputs = nodeElement.querySelectorAll('.input');
            const outputs = nodeElement.querySelectorAll('.output');
            
            inputs.forEach((input, index) => {
                input.setAttribute('title', 'Conectar entrada');
                input.classList.add('input-point');
            });
            
            outputs.forEach((output, index) => {
                let title = 'Conectar salida';
                if (type === 'condition' && data.output_labels && data.output_labels[index]) {
                    title = data.output_labels[index];
                }
                output.setAttribute('title', title);
                output.classList.add('output-point');
                output.classList.add(`output-${index}`);
            });
            
            console.log(`Estilos aplicados al nodo ${nodeId}`);
        } else {
            console.error(`No se pudo encontrar el elemento del nodo ${nodeId}`);
        }
        
        // Actualizar el formulario con los nuevos datos
        setTimeout(updateFormData, 100);
        
        return nodeId;
    } catch (error) {
        console.error('Error al añadir nodo:', error);
        return null;
    }
}

// Crear HTML para cada tipo de nodo (versión compacta)
function createNodeHTML(type, data = {}) {
    console.log('Creando HTML para nodo de tipo:', type);
    let title = '';
    let icon = '';
    
    switch(type) {
        case 'start':
            title = 'Inicio';
            icon = '<i class="fas fa-play-circle"></i>';
            break;
        case 'condition':
            title = 'Condición';
            icon = '<i class="fas fa-code-branch"></i>';
            break;
        case 'action':
            title = 'Acción';
            icon = '<i class="fas fa-bolt"></i>';
            break;
        case 'api_request':
            title = 'API Request';
            icon = '<i class="fas fa-exchange-alt"></i>';
            break;
        case 'ai_generate':
            title = 'IA Generate';
            icon = '<i class="fas fa-brain"></i>';
            break;
        case 'rag_search':
            title = 'RAG Search';
            icon = '<i class="fas fa-search"></i>';
            break;
        case 'carousel':
            title = 'Carrusel';
            icon = '<i class="fas fa-images"></i>';
            break;
        case 'subflow':
            title = 'Sub-flujo';
            icon = '<i class="fas fa-sitemap"></i>';
            break;
        case 'delay':
            title = 'Pausa';
            icon = '<i class="fas fa-clock"></i>';
            break;
    }
    
    return `
        <div class="flow-node-content-compact">
            <div class="flow-node-header-compact">
                ${icon}
                <span class="node-title-compact">${title}</span>
                <button class="node-edit-btn" data-node-type="${type}" title="Editar nodo">
            <i class="fas fa-edit"></i>
        </button>
            </div>
            <div class="node-data-preview" id="preview-${type}">
                ${getNodePreview(type, data)}
            </div>
        </div>
    `;
}

// Función para obtener vista previa del nodo
function getNodePreview(type, data = {}) {
    switch(type) {
        case 'start':
            return '<small class="text-muted">Punto de entrada</small>';
        case 'condition':
            return `<small class="text-muted">${data.condition_text || 'Sin condición'}</small>`;
        case 'action':
            return `<small class="text-muted">${data.action_content || 'Sin acción'}</small>`;
        case 'api_request':
            return `<small class="text-muted">${data.api_method || 'GET'} ${data.api_url || 'Sin URL'}</small>`;
        case 'ai_generate':
            return `<small class="text-muted">${data.ai_provider || 'Gemini'}: ${data.ai_prompt ? data.ai_prompt.substring(0, 30) + '...' : 'Sin prompt'}</small>`;
        case 'rag_search':
            return `<small class="text-muted">Buscar: ${data.search_term || '{{user_message}}'}</small>`;
        case 'carousel':
            return `<small class="text-muted">${data.cards ? data.cards.length + ' tarjetas' : 'Sin tarjetas'}</small>`;
        case 'subflow':
            return `<small class="text-muted">Flujo: ${data.subflow_id || 'Sin seleccionar'}</small>`;
        case 'delay':
            return `<small class="text-muted">${data.duration || '2.0'}s ${data.show_typing ? '(con indicador)' : ''}</small>`;
        default:
            return '';
    }
}

// Función para abrir el modal de edición de nodo
function openNodeEditModal(type, buttonElement) {
    console.log('Abriendo modal de edición para nodo tipo:', type);
    
    // Encontrar el nodo padre
    const nodeElement = buttonElement.closest('.drawflow-node');
    if (!nodeElement) {
        console.error('No se pudo encontrar el elemento del nodo');
        return;
    }
    
    const nodeId = nodeElement.id.replace('node-', '');
    
    // Obtener datos actuales del nodo
    const nodeData = editor.getNodeFromId(nodeId);
    if (!nodeData) {
        console.error('No se pudieron obtener los datos del nodo');
        return;
    }
    
    // Crear contenido del modal según el tipo
    let modalContent = createModalContent(type, nodeData.data || {});
    
    // Mostrar modal
    showNodeEditModal(modalContent, nodeId, type);
}

// Crear contenido del modal según el tipo de nodo
function createModalContent(type, data = {}) {
    switch(type) {
        case 'start':
            return `
                <div class="modal-form-group">
                    <label>Nombre del nodo:</label>
                    <input type="text" id="modal-start-name" value="${data.name || 'Inicio'}" class="form-control">
                </div>
                <div class="modal-form-group">
                    <label>Descripción:</label>
                    <textarea id="modal-start-description" class="form-control" rows="2">${data.description || 'Punto de entrada del flujo'}</textarea>
                </div>
            `;
        case 'condition':
            return `
                <div class="modal-form-group">
                    <label>Nombre del nodo:</label>
                    <input type="text" id="modal-condition-name" value="${data.name || 'Condición'}" class="form-control">
                </div>
                <div class="modal-form-group">
                    <label>Condición a evaluar:</label>
                    <input type="text" id="modal-condition-text" value="${data.condition_text || ''}" class="form-control" placeholder="Ej: usuario dice 'hola'">
                </div>
                <div class="modal-form-group">
                    <label>Tipo de evaluación:</label>
                    <select id="modal-condition-type" class="form-control">
                        <option value="text" ${data.condition_type === 'text' ? 'selected' : ''}>Texto exacto</option>
                        <option value="contains" ${data.condition_type === 'contains' ? 'selected' : ''}>Contiene texto</option>
                        <option value="ai" ${data.condition_type === 'ai' ? 'selected' : ''}>Evaluación con IA</option>
                    </select>
                </div>
                <div class="modal-form-group">
                    <label>Descripción:</label>
                    <textarea id="modal-condition-description" class="form-control" rows="2">${data.description || ''}</textarea>
                </div>
            `;
        case 'action':
            return `
                <div class="modal-form-group">
                    <label>Nombre del nodo:</label>
                    <input type="text" id="modal-action-name" value="${data.name || 'Acción'}" class="form-control">
                </div>
                <div class="modal-form-group">
                    <label>Tipo de acción:</label>
                    <select id="modal-action-type" class="form-control">
                        <option value="message" ${data.action_type === 'message' ? 'selected' : ''}>Enviar mensaje</option>
                        <option value="redirect" ${data.action_type === 'redirect' ? 'selected' : ''}>Redireccionar</option>
                    </select>
                </div>
                <div class="modal-form-group">
                    <label>Contenido/Configuración:</label>
                    <textarea id="modal-action-content" class="form-control" rows="4" placeholder="Contenido del mensaje. Puedes usar variables como {{user_message}}, {{user_name}}, {{quote_data.content}}, etc.">${data.action_content || ''}</textarea>
                    <small class="form-text text-muted">💡 Tip: Usa {{variable_name}} para insertar contenido de variables del flujo</small>
                </div>
                <div class="modal-form-group">
                    <label>Descripción:</label>
                    <textarea id="modal-action-description" class="form-control" rows="2">${data.description || ''}</textarea>
                </div>
            `;
        case 'api_request':
            return `
                <div class="modal-form-group">
                    <label>Nombre del nodo:</label>
                    <input type="text" id="modal-api-name" value="${data.name || 'API Request'}" class="form-control">
                </div>
                <div class="modal-form-group">
                    <label>URL del Endpoint:</label>
                    <input type="text" id="modal-api-url" value="${data.api_url || ''}" class="form-control" placeholder="https://api.ejemplo.com/v1/endpoint">
                </div>
                <div class="modal-form-group">
                    <label>Método HTTP:</label>
                    <select id="modal-api-method" class="form-control">
                        <option value="GET" ${data.api_method === 'GET' ? 'selected' : ''}>GET</option>
                        <option value="POST" ${data.api_method === 'POST' ? 'selected' : ''}>POST</option>
                        <option value="PUT" ${data.api_method === 'PUT' ? 'selected' : ''}>PUT</option>
                        <option value="DELETE" ${data.api_method === 'DELETE' ? 'selected' : ''}>DELETE</option>
                    </select>
                </div>
                <div class="modal-form-group">
                    <label>Headers (JSON):</label>
                    <textarea id="modal-api-headers" class="form-control" rows="3" placeholder='{"Authorization": "Bearer {{token}}", "Content-Type": "application/json"}'>${JSON.stringify(data.api_headers || {}, null, 2)}</textarea>
                </div>
                <div class="modal-form-group">
                    <label>Body/Payload (JSON):</label>
                    <textarea id="modal-api-body" class="form-control" rows="3" placeholder='{"name": "{{userName}}", "email": "{{userEmail}}"}'>${data.api_body || ''}</textarea>
                </div>
                <div class="modal-form-group">
                    <label>Variable de Respuesta:</label>
                    <input type="text" id="modal-api-response-var" value="${data.response_variable || 'api_response'}" class="form-control" placeholder="api_response">
                </div>
                <div class="modal-form-group">
                    <button type="button" id="test-api-button" class="btn btn-info btn-sm" onclick="testApiRequest()">🧪 Probar API</button>
                    <div id="api-test-result" class="mt-2" style="display: none;"></div>
                </div>
            `;
        case 'ai_generate':
            return `
                <div class="modal-form-group">
                    <label>Nombre del nodo:</label>
                    <input type="text" id="modal-ai-name" value="${data.name || 'IA Generate'}" class="form-control">
                </div>
                <div class="modal-form-group">
                    <label>Proveedor de IA:</label>
                    <select id="modal-ai-provider" class="form-control">
                        <option value="gemini" ${data.ai_provider === 'gemini' ? 'selected' : ''}>Gemini</option>
                        <option value="gpt" ${data.ai_provider === 'gpt' ? 'selected' : ''}>GPT (futuro)</option>
                        <option value="claude" ${data.ai_provider === 'claude' ? 'selected' : ''}>Claude (futuro)</option>
                    </select>
                </div>
                <div class="modal-form-group">
                    <label>Prompt (Plantilla):</label>
                    <textarea id="modal-ai-prompt" class="form-control" rows="4" placeholder="Analiza el sentimiento de: {{user_message}}">${data.ai_prompt || ''}</textarea>
                    <small class="text-muted">Usa {{variable}} para insertar variables del chat</small>
                </div>
                <div class="modal-form-group">
                    <label>Variable de Salida:</label>
                    <input type="text" id="modal-ai-output-var" value="${data.output_variable || 'ai_output'}" class="form-control" placeholder="ai_output">
                </div>
            `;
        case 'rag_search':
            return `
                <div class="modal-form-group">
                    <label>Nombre del nodo:</label>
                    <input type="text" id="modal-rag-name" value="${data.name || 'RAG Search'}" class="form-control">
                </div>
                <div class="modal-form-group">
                    <label>Término de Búsqueda:</label>
                    <input type="text" id="modal-rag-search-term" value="${data.search_term || '{{user_message}}'}" class="form-control" placeholder="{{user_message}}">
                    <small class="text-muted">Usa {{variable}} para insertar variables del chat</small>
                </div>
                <div class="modal-form-group">
                    <label>Fuente de Datos:</label>
                    <select id="modal-rag-data-source" class="form-control">
                        <option value="all" ${data.data_source === 'all' ? 'selected' : ''}>Todo</option>
                        <option value="files" ${data.data_source === 'files' ? 'selected' : ''}>Archivos Subidos</option>
                        <option value="urls" ${data.data_source === 'urls' ? 'selected' : ''}>URLs Extraídas</option>
                    </select>
                </div>
                <div class="modal-form-group">
                    <label>Número de Resultados:</label>
                    <input type="number" id="modal-rag-max-results" value="${data.max_results || 3}" class="form-control" min="1" max="10">
                </div>
                <div class="modal-form-group">
                    <label>Variable de Salida:</label>
                    <input type="text" id="modal-rag-output-var" value="${data.output_variable || 'search_results'}" class="form-control" placeholder="search_results">
                </div>
            `;
        case 'carousel':
            return `
                <div class="modal-form-group">
                    <label>Nombre del nodo:</label>
                    <input type="text" id="modal-carousel-name" value="${data.name || 'Carrusel'}" class="form-control">
                </div>
                <div class="modal-form-group">
                    <label>Tarjetas (JSON):</label>
                    <textarea id="modal-carousel-cards" class="form-control" rows="8" placeholder='[{"image": "url", "title": "Título", "description": "Descripción", "buttons": [{"text": "Botón", "value": "valor"}]}]'>${JSON.stringify(data.cards || [], null, 2)}</textarea>
                    <small class="text-muted">Formato: Array de objetos con image, title, description y buttons</small>
                </div>
                <div class="modal-form-group">
                    <button type="button" class="btn btn-secondary" onclick="addCarouselCard()">Agregar Tarjeta</button>
                </div>
            `;
        case 'subflow':
            return `
                <div class="modal-form-group">
                    <label>Nombre del nodo:</label>
                    <input type="text" id="modal-subflow-name" value="${data.name || 'Sub-flujo'}" class="form-control">
                </div>
                <div class="modal-form-group">
                    <label>ID del Sub-flujo:</label>
                    <input type="text" id="modal-subflow-id" value="${data.subflow_id || ''}" class="form-control" placeholder="nombre_del_subflujo">
                    <small class="text-muted">ID del flujo que se ejecutará como sub-flujo</small>
                </div>
                <div class="modal-form-group">
                    <label>Mapeo de Variables (JSON):</label>
                    <textarea id="modal-subflow-mapping" class="form-control" rows="4" placeholder='[{"source": "variable_origen", "target": "variable_destino"}]'>${JSON.stringify(data.variable_mapping || [], null, 2)}</textarea>
                    <small class="text-muted">Mapeo de variables entre el flujo actual y el sub-flujo</small>
                </div>
            `;
        case 'delay':
            return `
                <div class="modal-form-group">
                    <label>Nombre del nodo:</label>
                    <input type="text" id="modal-delay-name" value="${data.name || 'Pausa'}" class="form-control">
                </div>
                <div class="modal-form-group">
                    <label>Duración (segundos):</label>
                    <input type="number" id="modal-delay-duration" value="${data.duration || 2.0}" class="form-control" step="0.1" min="0.1" max="30">
                </div>
                <div class="modal-form-group">
                    <label>
                        <input type="checkbox" id="modal-delay-typing" ${data.show_typing ? 'checked' : ''}>
                        Mostrar indicador de escritura
                    </label>
                </div>
            `;
        default:
            return '<p>Tipo de nodo no reconocido</p>';
    }
}

// Mostrar modal de edición
function showNodeEditModal(content, nodeId, nodeType) {
    // Crear modal si no existe
    let modal = document.getElementById('node-edit-modal');
    if (!modal) {
        modal = createNodeEditModal();
        document.body.appendChild(modal);
    }
    
    // Configurar contenido
    document.getElementById('node-edit-title').textContent = `Editar ${getNodeTypeName(nodeType)}`;
    document.getElementById('node-edit-content').innerHTML = content;
    
    // Configurar botón de guardar
    const saveBtn = document.getElementById('node-edit-save');
    saveBtn.onclick = () => saveNodeData(nodeId, nodeType);
    
    // Configurar botones de cerrar/cancelar
    const closeButtons = modal.querySelectorAll('.node-edit-close');
    closeButtons.forEach(btn => {
        btn.onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            modal.style.display = 'none';
        };
    });
    
    // Mostrar modal
    modal.style.display = 'block';
}

// Crear estructura del modal de edición
function createNodeEditModal() {
    const modal = document.createElement('div');
    modal.id = 'node-edit-modal';
    modal.className = 'node-edit-modal';
    modal.innerHTML = `
        <div class="node-edit-modal-content">
            <div class="node-edit-modal-header">
                <h3 id="node-edit-title">Editar Nodo</h3>
                <span class="node-edit-close">&times;</span>
            </div>
            <div class="node-edit-modal-body">
                <div id="node-edit-content"></div>
            </div>
            <div class="node-edit-modal-footer">
                <button id="node-edit-save" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar
                </button>
                <button class="btn cancel node-edit-close">
                    <i class="fas fa-times"></i> Cancelar
                </button>
            </div>
        </div>
    `;
    
    // Configurar eventos de cierre
    modal.addEventListener('click', function(e) {
        e.stopPropagation();
        if (e.target === modal || e.target.classList.contains('node-edit-close')) {
            modal.style.display = 'none';
        }
    });
    
    // Prevenir que el contenido del modal cierre el modal
    const modalContent = modal.querySelector('.node-edit-modal-content');
    if (modalContent) {
        modalContent.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
    
    return modal;
}

// Obtener nombre del tipo de nodo
function getNodeTypeName(type) {
    switch(type) {
        case 'start': return 'Nodo de Inicio';
        case 'condition': return 'Nodo de Condición';
        case 'action': return 'Nodo de Acción';
        case 'api_request': return 'Petición API';
        case 'ai_generate': return 'Generación IA';
        case 'rag_search': return 'Búsqueda RAG';
        case 'carousel': return 'Carrusel';
        case 'subflow': return 'Sub-flujo';
        case 'delay': return 'Pausa';
        default: return 'Nodo';
    }
}

// Guardar datos del nodo
function saveNodeData(nodeId, nodeType) {
    let data = {};
    
    switch(nodeType) {
        case 'start':
            data = {
                name: document.getElementById('modal-start-name').value,
                description: document.getElementById('modal-start-description').value
            };
            break;
        case 'condition':
            data = {
                name: document.getElementById('modal-condition-name').value,
                condition_text: document.getElementById('modal-condition-text').value,
                condition_type: document.getElementById('modal-condition-type').value,
                description: document.getElementById('modal-condition-description').value,
                output_labels: ['Verdadero', 'Falso']
            };
            break;
        case 'action':
            data = {
                name: document.getElementById('modal-action-name').value,
                action_type: document.getElementById('modal-action-type').value,
                action_content: document.getElementById('modal-action-content').value,
                description: document.getElementById('modal-action-description').value
            };
            break;
        case 'api_request':
            try {
                const headers = document.getElementById('modal-api-headers').value;
                data = {
                    name: document.getElementById('modal-api-name').value,
                    api_url: document.getElementById('modal-api-url').value,
                    api_method: document.getElementById('modal-api-method').value,
                    api_headers: headers ? JSON.parse(headers) : {},
                    api_body: document.getElementById('modal-api-body').value,
                    response_variable: document.getElementById('modal-api-response-var').value
                };
            } catch (e) {
                alert('Error en el formato JSON de los headers');
                return;
            }
            break;
        case 'ai_generate':
            data = {
                name: document.getElementById('modal-ai-name').value,
                ai_provider: document.getElementById('modal-ai-provider').value,
                ai_prompt: document.getElementById('modal-ai-prompt').value,
                output_variable: document.getElementById('modal-ai-output-var').value
            };
            break;
        case 'rag_search':
            data = {
                name: document.getElementById('modal-rag-name').value,
                search_term: document.getElementById('modal-rag-search-term').value,
                data_source: document.getElementById('modal-rag-data-source').value,
                max_results: parseInt(document.getElementById('modal-rag-max-results').value),
                output_variable: document.getElementById('modal-rag-output-var').value
            };
            break;
        case 'carousel':
            try {
                const cards = document.getElementById('modal-carousel-cards').value;
                data = {
                    name: document.getElementById('modal-carousel-name').value,
                    cards: cards ? JSON.parse(cards) : []
                };
            } catch (e) {
                alert('Error en el formato JSON de las tarjetas');
                return;
            }
            break;
        case 'subflow':
            try {
                const mapping = document.getElementById('modal-subflow-mapping').value;
                data = {
                    name: document.getElementById('modal-subflow-name').value,
                    subflow_id: document.getElementById('modal-subflow-id').value,
                    variable_mapping: mapping ? JSON.parse(mapping) : []
                };
            } catch (e) {
                alert('Error en el formato JSON del mapeo de variables');
                return;
            }
            break;
        case 'delay':
            data = {
                name: document.getElementById('modal-delay-name').value,
                duration: parseFloat(document.getElementById('modal-delay-duration').value),
                show_typing: document.getElementById('modal-delay-typing').checked
            };
            break;
    }
    
    // Actualizar datos del nodo en el editor
    editor.updateNodeDataFromId(nodeId, data);
    
    // Actualizar vista previa en el nodo
    updateNodePreview(nodeId, nodeType, data);
    
    // Cerrar modal
    document.getElementById('node-edit-modal').style.display = 'none';
    
    // Actualizar formulario
    updateFormData();
}

// Actualizar vista previa del nodo
function updateNodePreview(nodeId, nodeType, data) {
    const nodeElement = document.getElementById(`node-${nodeId}`);
    if (nodeElement) {
        const previewElement = nodeElement.querySelector('.node-data-preview');
        if (previewElement) {
            previewElement.innerHTML = getNodePreview(nodeType, data);
        }
        
        // Actualizar título si es necesario
        const titleElement = nodeElement.querySelector('.node-title-compact');
        if (titleElement && data.name) {
            titleElement.textContent = data.name;
        }
    }
}

// Actualizar los datos del formulario con el estado actual del editor
function updateFormData() {
    console.log('Actualizando datos del formulario');
    
    // Verificar que el editor esté inicializado
    if (!editor) {
        console.error('Editor no inicializado');
        return;
    }
    
    // Obtener referencias a los elementos del formulario si no están inicializados
    if (!flowNodesInput) {
        flowNodesInput = document.getElementById('flow_nodes');
        if (!flowNodesInput) {
            console.error('No se encontró el elemento flow_nodes');
            return;
        }
    }
    
    if (!flowConnectionsInput) {
        flowConnectionsInput = document.getElementById('flow_connections');
        if (!flowConnectionsInput) {
            console.error('No se encontró el elemento flow_connections');
            return;
        }
    }
    
    // Forzar una actualización del estado interno del editor
    try {
        editor.updateConnectionNodes('node-1'); // Esto fuerza una actualización interna
    } catch(e) {
        // Ignorar errores si el nodo no existe
    }
    
    // Exportar datos del editor
    const editorData = editor.export();
    console.log('Datos exportados del editor:', editorData);
    console.log('Timestamp de actualización:', new Date().toISOString());
    
    const nodes = [];
    const connections = [];
    
    // Extraer nodos y sus datos
    if (editorData.drawflow && editorData.drawflow.Home && editorData.drawflow.Home.data) {
        Object.values(editorData.drawflow.Home.data).forEach(node => {
            // Usar los datos almacenados en el nodo directamente
            let nodeData = node.data || {};
            
            // Los datos ya están almacenados en node.data, no necesitamos extraer de formularios
            
            // Guardar el nodo
            nodes.push({
                id: node.id,
                type: node.name,
                posX: node.pos_x,
                posY: node.pos_y,
                data: nodeData
            });
            
            // Extraer conexiones
            if (node.outputs) {
                Object.entries(node.outputs).forEach(([outputKey, output]) => {
                    if (output.connections && output.connections.length > 0) {
                        output.connections.forEach(conn => {
                            // Extraer el índice numérico del outputKey (ej: "output_1" -> 0)
                            const outputIndex = parseInt(outputKey.replace('output_', '')) - 1;
                            
                            // Extraer el índice numérico del input (ej: "input_1" -> 0)
                            let inputIndex = 0;
                            if (conn.output && typeof conn.output === 'string') {
                                inputIndex = parseInt(conn.output.replace('input_', '')) - 1;
                            } else if (conn.input !== undefined) {
                                inputIndex = parseInt(conn.input);
                            }
                            
                            connections.push({
                                nodeSource: parseInt(node.id),
                                nodeTarget: parseInt(conn.node),
                                outputSource: outputIndex,
                                inputTarget: inputIndex
                            });
                            
                            console.log(`Conexión guardada: ${node.id} -> ${conn.node} (output: ${outputIndex}, input: ${inputIndex})`);
                        });
                    }
                });
            }
        });
    }
    
    console.log('Nodos procesados:', nodes.length);
    console.log('Conexiones procesadas:', connections.length);
    
    // Actualizar los campos ocultos del formulario
    flowNodesInput.value = JSON.stringify(nodes);
    flowConnectionsInput.value = JSON.stringify(connections);
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado, configurando eventos iniciales');
    
    // Inicializar el editor cuando se abra el modal
    const createFlowBtn = document.getElementById('create-flow-btn');
    if (createFlowBtn) {
        createFlowBtn.addEventListener('click', function() {
            console.log('Botón crear flujo clickeado');
            document.getElementById('flow-editor-modal').style.display = 'block';
            setTimeout(() => {
                initFlowEditor();
            }, 100);
        });
    }
    
    // Agregar evento al formulario para actualizar datos antes del envío
    const flowForm = document.getElementById('flow-form');
    if (flowForm) {
        flowForm.addEventListener('submit', function(e) {
            console.log('Formulario enviándose, actualizando datos...');
            updateFormData();
            
            // Pequeña pausa para asegurar que los datos se actualicen
            setTimeout(() => {
                console.log('Datos actualizados antes del envío');
            }, 50);
        });
    }
    
    // Agregar evento a los botones de guardar
    document.querySelectorAll('button[name="save_flow"], input[name="save_flow"]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            console.log('Botón guardar clickeado, actualizando datos...');
            updateFormData();
        });
    });
    
    // Inicializar el editor cuando se edite un flujo existente
    document.querySelectorAll('.edit-flow-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            console.log('Botón editar flujo clickeado');
            document.getElementById('flow-editor-modal').style.display = 'block';
            setTimeout(() => {
                initFlowEditor();
            }, 100);
        });
    });
});

// Función para probar API Request
function testApiRequest() {
    const url = document.getElementById('modal-api-url').value;
    const method = document.getElementById('modal-api-method').value;
    const headers = document.getElementById('modal-api-headers').value;
    const body = document.getElementById('modal-api-body').value;
    const resultDiv = document.getElementById('api-test-result');
    const testButton = document.getElementById('test-api-button');
    
    if (!url) {
        alert('Por favor ingresa una URL para probar');
        return;
    }
    
    // Mostrar loading
    testButton.disabled = true;
    testButton.innerHTML = '⏳ Probando...';
    resultDiv.style.display = 'block';
    resultDiv.innerHTML = '<div class="alert alert-info">Ejecutando petición...</div>';
    
    // Crear datos para enviar al backend
    const testData = {
        action: 'test_api',
        api_url: url,
        api_method: method,
        api_headers: headers,
        api_body: body
    };
    
    // Enviar petición al backend
    fetch('test_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(testData)
    })
    .then(response => response.json())
    .then(data => {
        testButton.disabled = false;
        testButton.innerHTML = '🧪 Probar API';
        
        if (data.success) {
            resultDiv.innerHTML = `
                <div class="alert alert-success">
                    <strong>✅ Éxito!</strong><br>
                    <strong>Código HTTP:</strong> ${data.http_code}<br>
                    <strong>Respuesta:</strong><br>
                    <pre style="max-height: 200px; overflow-y: auto; background: #f8f9fa; padding: 10px; border-radius: 4px;">${JSON.stringify(data.response, null, 2)}</pre>
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <div class="alert alert-danger">
                    <strong>❌ Error:</strong> ${data.error}
                </div>
            `;
        }
    })
    .catch(error => {
        testButton.disabled = false;
        testButton.innerHTML = '🧪 Probar API';
        resultDiv.innerHTML = `
            <div class="alert alert-danger">
                <strong>❌ Error de conexión:</strong> ${error.message}
            </div>
        `;
    });
}
