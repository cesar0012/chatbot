// Funcionalidad de arrastrar y soltar para subida de archivos
document.addEventListener('DOMContentLoaded', function() {
    const dropArea = document.getElementById('drop-area');
    const fileInput = document.getElementById('fileToUpload');
    const fileList = document.getElementById('file-list');
    const uploadForm = document.getElementById('upload-form');
    const uploadButton = document.getElementById('upload-button');
    
    if (!dropArea || !fileInput) return;
    
    // Prevenir comportamiento predeterminado para eventos de arrastrar y soltar
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    // Resaltar área de soltar cuando se arrastra un archivo sobre ella
    ['dragenter', 'dragover'].forEach(eventName => {
        dropArea.addEventListener(eventName, highlight, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, unhighlight, false);
    });
    
    function highlight() {
        dropArea.classList.add('highlight');
    }
    
    function unhighlight() {
        dropArea.classList.remove('highlight');
    }
    
    // Manejar archivos soltados
    dropArea.addEventListener('drop', handleDrop, false);
    
    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        
        if (files.length > 0) {
            fileInput.files = files;
            updateFileList(files);
        }
    }
    
    // Manejar selección de archivos mediante el input tradicional
    fileInput.addEventListener('change', function() {
        updateFileList(this.files);
    });
    
    // Actualizar lista de archivos seleccionados
    function updateFileList(files) {
        if (!fileList) return;
        
        fileList.innerHTML = '';
        
        if (files.length > 0) {
            const file = files[0]; // Solo mostramos el primer archivo ya que es un input single
            const fileSize = (file.size / 1024).toFixed(2);
            const fileType = file.type || getFileExtension(file.name);
            
            const fileItem = document.createElement('div');
            fileItem.className = 'selected-file';
            fileItem.innerHTML = `
                <div class="file-icon">
                    <i class="fas ${getFileIcon(fileType)}"></i>
                </div>
                <div class="file-info">
                    <div class="file-name">${file.name}</div>
                    <div class="file-meta">${fileSize} KB - ${getFileTypeName(fileType)}</div>
                </div>
                <button type="button" class="btn-remove-file" aria-label="Eliminar archivo">
                    <i class="fas fa-times-circle"></i>
                </button>
            `;
            
            fileList.appendChild(fileItem);
            
            // Habilitar botón de subida
            if (uploadButton) {
                uploadButton.disabled = false;
            }
            
            // Evento para eliminar el archivo
            const removeButton = fileItem.querySelector('.btn-remove-file');
            if (removeButton) {
                removeButton.addEventListener('click', function() {
                    fileInput.value = '';
                    fileList.innerHTML = '';
                    if (uploadButton) {
                        uploadButton.disabled = true;
                    }
                });
            }
        } else {
            // Deshabilitar botón de subida
            if (uploadButton) {
                uploadButton.disabled = true;
            }
        }
    }
    
    // Obtener extensión del archivo
    function getFileExtension(filename) {
        return filename.split('.').pop().toLowerCase();
    }
    
    // Obtener icono según tipo de archivo
    function getFileIcon(fileType) {
        if (fileType.includes('pdf')) return 'fa-file-pdf';
        if (fileType.includes('word') || fileType.includes('doc')) return 'fa-file-word';
        if (fileType.includes('csv') || fileType.includes('excel') || fileType.includes('spreadsheet')) return 'fa-file-excel';
        if (fileType.includes('text') || fileType.includes('txt')) return 'fa-file-alt';
        return 'fa-file';
    }
    
    // Obtener nombre legible del tipo de archivo
    function getFileTypeName(fileType) {
        if (fileType.includes('pdf')) return 'PDF';
        if (fileType.includes('word') || fileType.includes('doc')) return 'Documento Word';
        if (fileType.includes('csv')) return 'CSV';
        if (fileType.includes('excel') || fileType.includes('spreadsheet')) return 'Excel';
        if (fileType.includes('text') || fileType.includes('txt')) return 'Texto';
        return fileType;
    }
    
    // Validar formulario antes de enviar
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            if (!fileInput.files || fileInput.files.length === 0) {
                e.preventDefault();
                alert('Por favor, selecciona un archivo para subir.');
            }
        });
    }
});
