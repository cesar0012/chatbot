document.addEventListener('DOMContentLoaded', function() {
    // Obtener todos los botones de pestañas
    const tabButtons = document.querySelectorAll('.tab-btn');
    
    // Agregar evento de clic a cada botón
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remover clase active de todos los botones
            tabButtons.forEach(btn => btn.classList.remove('active'));
            
            // Agregar clase active al botón actual
            this.classList.add('active');
            
            // Obtener el id de la pestaña a mostrar
            const tabId = this.getAttribute('data-tab');
            
            // Ocultar todas las pestañas
            const tabPanes = document.querySelectorAll('.tab-pane');
            tabPanes.forEach(pane => pane.classList.remove('active'));
            
            // Mostrar la pestaña seleccionada
            document.getElementById(tabId).classList.add('active');
        });
    });
});