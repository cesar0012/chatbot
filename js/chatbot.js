document.addEventListener('DOMContentLoaded', function() {
    const chatForm = document.getElementById('chat-form');
    const userInput = document.getElementById('user-input');
    const chatMessages = document.getElementById('chat-messages');
    
    // Función para agregar mensajes al chat
    function addMessage(message, isUser = false) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${isUser ? 'user' : 'bot'}`;
        
        const messageContent = document.createElement('div');
        messageContent.className = 'message-content';
        
        // Verificar si el mensaje contiene código HTML
        if (!isUser && (message.includes('<table') || message.includes('<ul') || message.includes('<ol') || message.includes('<div') || message.includes('<p>') || message.includes('<h') || message.includes('<tr') || message.includes('<td') || message.includes('<th'))) {
            // Si es una respuesta del bot y contiene HTML, usar innerHTML para renderizarlo
            
            // Corregir problemas de etiquetas mal escapadas (como <\/th>)
            message = message.replace(/\\\//g, '/');
            
            // Decodificar entidades HTML para caracteres especiales
            const textArea = document.createElement('textarea');
            textArea.innerHTML = message;
            message = textArea.value;
            
            // Convertir códigos unicode escapados (\u00f1 -> ñ)
            message = message.replace(/\\u00([0-9a-f]{2})/gi, function(match, p1) {
                return String.fromCharCode(parseInt(p1, 16));
            });
            
            // Asegurar que los caracteres especiales se muestren correctamente
            const parser = new DOMParser();
            const doc = parser.parseFromString(message, 'text/html');
            
            // Si hay errores de parseo, usar el mensaje original pero con entidades HTML decodificadas
            if (doc.body) {
                messageContent.innerHTML = doc.body.innerHTML;
            } else {
                messageContent.innerHTML = message;
            }
        } else {
            // Si es un mensaje del usuario o no contiene HTML, usar textContent
            messageContent.textContent = message;
        }
        
        messageDiv.appendChild(messageContent);
        chatMessages.appendChild(messageDiv);
        
        // Scroll al último mensaje
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    // Función para mostrar indicador de carga
    function showLoadingIndicator() {
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'message bot';
        loadingDiv.id = 'loading-indicator';
        
        const loadingContent = document.createElement('div');
        loadingContent.className = 'message-content';
        loadingContent.textContent = 'Pensando...';
        
        loadingDiv.appendChild(loadingContent);
        chatMessages.appendChild(loadingDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    // Función para eliminar indicador de carga
    function removeLoadingIndicator() {
        const loadingIndicator = document.getElementById('loading-indicator');
        if (loadingIndicator) {
            loadingIndicator.remove();
        }
    }
    
    // Manejar envío del formulario
    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const userMessage = userInput.value.trim();
        if (userMessage === '') return;
        
        // Agregar mensaje del usuario al chat
        addMessage(userMessage, true);
        
        // Limpiar input
        userInput.value = '';
        
        // Mostrar indicador de carga
        showLoadingIndicator();
        
        // Enviar mensaje al servidor
        fetch('process_message.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ message: userMessage })
        })
        .then(response => response.json())
        .then(data => {
            // Eliminar indicador de carga
            removeLoadingIndicator();
            
            // Agregar respuesta del bot
            addMessage(data.response);
            
            // Procesar acciones especiales de flujos si existen
            if (data.flow_action) {
                handleFlowAction(data.flow_action, data.flow_data);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            removeLoadingIndicator();
            addMessage('Lo siento, ha ocurrido un error al procesar tu mensaje. Por favor, intenta de nuevo.');
        });
    });
    
    // Función para manejar acciones especiales de flujos
    function handleFlowAction(action, data) {
        switch (action) {
            case 'redirect':
                // Redireccionar a otra página después de un breve retraso
                if (data && typeof data === 'string') {
                    setTimeout(() => {
                        window.location.href = data;
                    }, 2000); // Esperar 2 segundos para que el usuario pueda leer el mensaje
                }
                break;
                
            case 'api':
                // Aquí se podría implementar lógica adicional para manejar respuestas de API
                console.log('API action with data:', data);
                break;
                
            case 'function':
                // Aquí se podría implementar lógica adicional para ejecutar funciones del lado del cliente
                console.log('Function action with data:', data);
                break;
        }
    }
});