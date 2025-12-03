// Funcionalidad para el procesamiento de URL
document.addEventListener('DOMContentLoaded', function() {
    const urlForm = document.getElementById('url-form');
    const loadingIndicator = document.getElementById('loading-indicator');
    
    if (urlForm && loadingIndicator) {
        urlForm.addEventListener('submit', function() {
            // Validar la URL antes de procesar
            const urlInput = document.getElementById('url');
            if (urlInput && urlInput.value) {
                try {
                    // Verificar si la URL es válida
                    new URL(urlInput.value);
                    
                    // Mostrar indicador de carga
                    loadingIndicator.classList.add('active');
                    
                    // Deshabilitar el botón de envío para evitar múltiples envíos
                    const submitButton = urlForm.querySelector('button[type="submit"]');
                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Procesando...';
                    }
                    
                    return true;
                } catch (e) {
                    // URL inválida
                    alert('Por favor, introduce una URL válida (incluyendo http:// o https://)');
                    return false;
                }
            }
        });
    }
});
