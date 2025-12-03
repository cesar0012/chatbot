// Inicialización de pestañas Bootstrap 5
document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando pestañas...');
    
    // Función para inicializar manualmente las pestañas
    function initializeTabs() {
        const fileTab = document.getElementById('pills-file-tab');
        const urlTab = document.getElementById('pills-url-tab');
        const filePane = document.getElementById('pills-file');
        const urlPane = document.getElementById('pills-url');
        
        console.log('Elementos de pestañas:', {
            fileTab: fileTab,
            urlTab: urlTab,
            filePane: filePane,
            urlPane: urlPane
        });
        
        if (fileTab && urlTab && filePane && urlPane) {
            // Asegurarse de que el primer panel esté activo inicialmente
            filePane.classList.add('show', 'active');
            fileTab.classList.add('active');
            fileTab.setAttribute('aria-selected', 'true');
            
            // Evento para la pestaña de archivo
            fileTab.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Clic en pestaña de archivo');
                
                // Activar pestaña de archivo
                fileTab.classList.add('active');
                urlTab.classList.remove('active');
                fileTab.setAttribute('aria-selected', 'true');
                urlTab.setAttribute('aria-selected', 'false');
                
                // Mostrar panel de archivo
                filePane.classList.add('show', 'active');
                urlPane.classList.remove('show', 'active');
            });
            
            // Evento para la pestaña de URL
            urlTab.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Clic en pestaña de URL');
                
                // Activar pestaña de URL
                urlTab.classList.add('active');
                fileTab.classList.remove('active');
                urlTab.setAttribute('aria-selected', 'true');
                fileTab.setAttribute('aria-selected', 'false');
                
                // Mostrar panel de URL
                urlPane.classList.add('show', 'active');
                filePane.classList.remove('show', 'active');
            });
            
            console.log('Eventos de pestañas configurados manualmente');
        } else {
            console.error('No se encontraron todos los elementos necesarios para las pestañas');
        }
    }
    
    // Intentar inicializar con Bootstrap primero
    if (typeof bootstrap !== 'undefined') {
        try {
            // Inicializar con Bootstrap
            const tabElList = document.querySelectorAll('a[data-bs-toggle="tab"]');
            tabElList.forEach(function(tabEl) {
                new bootstrap.Tab(tabEl);
            });
            console.log('Pestañas inicializadas con Bootstrap');
        } catch (error) {
            console.error('Error al inicializar pestañas con Bootstrap:', error);
            // Si falla, usar inicialización manual
            initializeTabs();
        }
    } else {
        console.warn('Bootstrap no está disponible. Utilizando solución alternativa para las pestañas.');
        // Usar inicialización manual
        initializeTabs();
    }
    
    // Forzar la visualización de los paneles de pestañas
    setTimeout(function() {
        const activePane = document.querySelector('.tab-pane.active');
        if (activePane) {
            console.log('Forzando visualización del panel activo:', activePane.id);
            activePane.style.display = 'block';
        }
    }, 500);
});
