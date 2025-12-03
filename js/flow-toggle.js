// Funcionalidad para el toggle de flujos de conversación
document.addEventListener('DOMContentLoaded', function() {
    // Obtener todos los checkboxes de toggle
    const toggleCheckboxes = document.querySelectorAll('.flow-toggle-checkbox');
    
    // Agregar evento a cada checkbox
    toggleCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const flowId = this.getAttribute('data-flow-id');
            const isActive = this.checked;
            
            // Obtener el elemento del flujo para animación
            const flowItem = this.closest('.list-group-item');
            
            // Deshabilitar el toggle mientras se procesa
            this.disabled = true;
            
            // Agregar clase de animación
            flowItem.classList.add('flow-activating');
            
            // Cambiar apariencia según el estado
            if (isActive) {
                flowItem.classList.remove('flow-inactive');
            } else {
                flowItem.classList.add('flow-inactive');
            }
            
            // Mostrar indicador de carga
            const loadingIndicator = document.createElement('div');
            loadingIndicator.className = 'toggle-loading-indicator';
            flowItem.appendChild(loadingIndicator);
            
            // Enviar solicitud AJAX para cambiar el estado del flujo
            fetch('toggle_flow.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'flow_id=' + encodeURIComponent(flowId),
                cache: 'no-cache' // Evitar caché
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    console.log('Estado del flujo cambiado:', data.active);
                    
                    // Asegurar que el checkbox refleje el estado actual
                    this.checked = data.active;
                    
                    // Actualizar la apariencia
                    if (data.active) {
                        flowItem.classList.remove('flow-inactive');
                    } else {
                        flowItem.classList.add('flow-inactive');
                    }
                    
                    // Notificar al usuario
                    showNotification(
                        data.active ? 'Flujo activado correctamente' : 'Flujo desactivado correctamente',
                        data.active ? 'success' : 'info'
                    );
                    
                    // Recargar la página para asegurar que todos los cambios se reflejen
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    console.error('Error al cambiar el estado del flujo:', data.message);
                    
                    // Revertir el cambio en la UI
                    this.checked = !isActive;
                    
                    if (!isActive) {
                        flowItem.classList.remove('flow-inactive');
                    } else {
                        flowItem.classList.add('flow-inactive');
                    }
                    
                    // Notificar al usuario
                    showNotification('Error al cambiar el estado del flujo: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error en la solicitud:', error);
                
                // Revertir el cambio en la UI
                this.checked = !isActive;
                
                if (!isActive) {
                    flowItem.classList.remove('flow-inactive');
                } else {
                    flowItem.classList.add('flow-inactive');
                }
                
                // Notificar al usuario
                showNotification('Error en la conexión: ' + error.message, 'error');
            })
            .finally(() => {
                // Quitar clase de animación
                flowItem.classList.remove('flow-activating');
                
                // Eliminar indicador de carga
                if (loadingIndicator && loadingIndicator.parentNode) {
                    loadingIndicator.parentNode.removeChild(loadingIndicator);
                }
                
                // Habilitar el toggle
                this.disabled = false;
            });
        });
    });
    
    // Inicializar el estado visual de los flujos
    toggleCheckboxes.forEach(checkbox => {
        const flowItem = checkbox.closest('.list-group-item');
        if (!checkbox.checked) {
            flowItem.classList.add('flow-inactive');
        }
    });
    
    // Función para mostrar notificaciones
    function showNotification(message, type = 'info') {
        // Crear elemento de notificación
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        
        // Icono según el tipo
        let icon = 'info-circle';
        if (type === 'success') icon = 'check-circle';
        if (type === 'error') icon = 'exclamation-circle';
        
        notification.innerHTML = `
            <div class="notification-icon">
                <i class="fas fa-${icon}"></i>
            </div>
            <div class="notification-content">
                ${message}
            </div>
        `;
        
        // Agregar al contenedor de notificaciones (crear si no existe)
        let notificationContainer = document.getElementById('notification-container');
        if (!notificationContainer) {
            notificationContainer = document.createElement('div');
            notificationContainer.id = 'notification-container';
            document.body.appendChild(notificationContainer);
        }
        
        notificationContainer.appendChild(notification);
        
        // Animar entrada
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        // Eliminar después de un tiempo
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }
});
