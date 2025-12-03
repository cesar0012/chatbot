# Chatbot Asistente con Gemini API

Este proyecto implementa un chatbot asistente que utiliza la API de Gemini 2.5 para procesar consultas y proporcionar respuestas basadas en documentos de referencia cargados por el administrador.

## Características

- Interfaz de usuario amigable para interactuar con el chatbot
- Panel de administración para gestionar documentos de referencia
- Almacenamiento de conversaciones mediante TinyDB
- Procesamiento de consultas utilizando la API de Gemini 2.5
- Diseño responsivo para dispositivos móviles y de escritorio

## Estructura del Proyecto

```
├── index.php              # Página de inicio (redirección)
├── chatbot.php            # Interfaz principal del chatbot
├── admin.php              # Panel de administración
├── process_message.php    # Procesamiento de mensajes con Gemini API
├── delete_file.php        # Manejo de eliminación de archivos
├── css/
│   └── styles.css         # Estilos CSS para la aplicación
├── js/
│   └── chatbot.js         # Funcionalidades JavaScript del chatbot
└── memory-bank/           # Almacenamiento de documentos y conversaciones
```

## Requisitos

- Servidor web con soporte para PHP (Apache, Nginx, etc.)
- PHP 7.4 o superior
- Conexión a Internet para comunicarse con la API de Gemini
- Clave API de Gemini (debe configurarse en process_message.php)

## Instalación

1. Clone o descargue este repositorio en su servidor web
2. Asegúrese de que el directorio `memory-bank` tenga permisos de escritura
3. Obtenga una clave API de Gemini desde la consola de Google AI Studio
4. Reemplace `TU_API_KEY_DE_GEMINI` en el archivo `process_message.php` con su clave API real
5. Acceda a la aplicación a través de su navegador web

## Uso

### Interfaz de Usuario

1. Acceda a la página principal para interactuar con el chatbot
2. Escriba sus consultas en el campo de texto y presione Enter o haga clic en el botón de envío
3. El chatbot procesará su consulta y mostrará una respuesta basada en los documentos de referencia disponibles

### Panel de Administración

1. Haga clic en "Administración" en la esquina superior derecha de la interfaz del chatbot
2. Configure las instrucciones personalizadas para el chatbot en la sección superior
3. Utilice el formulario para cargar documentos de referencia (formatos admitidos: TXT, PDF, DOC, DOCX)
4. Gestione los documentos existentes (ver o eliminar)

## Personalización

Existen dos formas de personalizar el comportamiento del chatbot:

1. **Instrucciones personalizadas**: Utilice el panel de administración para configurar instrucciones específicas que guiarán el comportamiento del chatbot. Estas instrucciones se combinarán con la base de conocimientos para proporcionar respuestas más precisas y contextualizadas.

2. **Ajustes técnicos**: Puede modificar los parámetros de generación como temperatura, maxOutputTokens, topP y topK en el archivo `process_message.php` según sus necesidades.

## Seguridad

Este proyecto implementa medidas básicas de seguridad, como la validación de archivos y la protección contra la navegación de directorios. Sin embargo, se recomienda implementar medidas adicionales como autenticación de usuarios para el panel de administración en un entorno de producción.

## Licencia

Este proyecto está disponible para uso personal y comercial. Siéntase libre de modificarlo según sus necesidades.

---

**Nota:** Asegúrese de reemplazar la clave API de ejemplo con su propia clave API de Gemini para que el chatbot funcione correctamente.