# Knowledge Database - Chatbot Avanzado con Sistema de Flujos

## Descripción General

Este chatbot es una aplicación web avanzada desarrollada en PHP que combina capacidades de conversación tradicional con un sistema sofisticado de flujos conversacionales. Está diseñado para proporcionar experiencias de chat personalizadas y estructuradas a través de una interfaz web moderna y un panel de administración completo.

## Arquitectura del Sistema

### Componentes Principales

1. **Frontend (Interfaz de Usuario)**
   - `chatbot.php` - Interfaz principal del chat
   - `admin.php` - Panel de administración
   - `flow_editor.php` - Editor visual de flujos

2. **Backend (Procesamiento)**
   - `process_message.php` - Procesamiento de mensajes del usuario
   - `process_flow.php` - Motor de procesamiento de flujos
   - `save_flow.php` - Guardado de flujos
   - `get_flow.php` - Recuperación de flujos
   - `delete_flow.php` - Eliminación de flujos
   - `toggle_flow.php` - Activación/desactivación de flujos

3. **Utilidades**
   - `scrape_url.php` - Extracción de contenido web
   - `delete_file.php` - Gestión de archivos
   - `save_instructions.php` - Guardado de instrucciones

## Funcionalidades del Chatbot

### 1. Sistema de Conversación Básico

#### Características:
- **Interfaz de chat en tiempo real** con diseño moderno y responsivo
- **Historial de conversaciones** persistente por sesión
- **Soporte para contenido HTML** en las respuestas del bot
- **Scroll automático** a los mensajes más recientes
- **Indicadores visuales** de estado de carga

#### Componentes técnicos:
- Clase `TinyDB` para almacenamiento de conversaciones en JSON
- Sistema de sesiones PHP para mantener contexto
- AJAX para comunicación asíncrona
- Sanitización de entrada para seguridad

### 2. Sistema de Flujos Conversacionales

#### ¿Qué son los Flujos?
Los flujos son secuencias estructuradas de interacciones que permiten crear conversaciones guiadas y personalizadas. Cada flujo está compuesto por nodos que representan diferentes estados o acciones en la conversación.

#### Características de los Flujos:

##### Estructura de Nodos:
- **Nodo de Inicio**: Punto de entrada del flujo
- **Nodos de Mensaje**: Envían mensajes específicos al usuario
- **Nodos de Pregunta**: Solicitan información al usuario
- **Nodos de Condición**: Evalúan respuestas y dirigen el flujo
- **Nodos de Acción**: Ejecutan operaciones específicas
- **Nodo de Fin**: Termina el flujo

##### Propiedades de los Nodos:
- **ID único**: Identificador del nodo
- **Tipo**: Categoría del nodo (mensaje, pregunta, condición, etc.)
- **Contenido**: Texto o instrucciones del nodo
- **Conexiones**: Enlaces a otros nodos
- **Condiciones**: Reglas para la navegación
- **Variables**: Almacenamiento de datos del usuario

#### Editor Visual de Flujos

##### Funcionalidades del Editor:
- **Interfaz drag-and-drop** para crear nodos
- **Conexiones visuales** entre nodos
- **Edición en tiempo real** de propiedades
- **Vista previa** del flujo
- **Validación** de estructura del flujo
- **Exportación/Importación** de flujos en JSON

##### Herramientas de Edición:
- **Paleta de nodos**: Diferentes tipos de nodos disponibles
- **Panel de propiedades**: Edición detallada de cada nodo
- **Zoom y navegación**: Control de vista del canvas
- **Deshacer/Rehacer**: Historial de cambios
- **Guardado automático**: Persistencia de cambios

### 3. Panel de Administración

#### Gestión de Contenido:

##### Instrucciones del Sistema:
- **Editor de texto** para instrucciones del chatbot
- **Guardado automático** de cambios
- **Versionado** de instrucciones
- **Vista previa** de formato

##### Mensaje de Bienvenida:
- **Personalización** del mensaje inicial
- **Soporte HTML** para formato avanzado
- **Variables dinámicas** (nombre del usuario, fecha, etc.)
- **Múltiples idiomas** (preparado para internacionalización)

##### Gestión de Flujos:
- **Lista completa** de flujos disponibles
- **Estados de activación** (activo/inactivo)
- **Métricas de uso** por flujo
- **Duplicación** de flujos existentes
- **Importación/Exportación** masiva

#### Banco de Memoria (Memory Bank):

##### Gestión de Archivos:
- **Subida de documentos** (PDF, TXT, DOC, etc.)
- **Organización por categorías**
- **Búsqueda de contenido**
- **Control de versiones**
- **Límites de tamaño** y tipo de archivo

##### Procesamiento de URLs:
- **Extracción automática** de contenido web
- **Análisis de metadatos**
- **Almacenamiento** de contenido extraído
- **Validación** de URLs
- **Manejo de errores** de conexión

### 4. Capacidades Avanzadas

#### Sistema de Variables:
- **Almacenamiento temporal** de datos del usuario
- **Persistencia** entre sesiones
- **Tipos de datos** múltiples (texto, número, booleano)
- **Operaciones** matemáticas y lógicas
- **Validación** de entrada

#### Lógica Condicional:
- **Evaluación de expresiones** complejas
- **Comparaciones** múltiples
- **Operadores lógicos** (AND, OR, NOT)
- **Ramificación** del flujo basada en condiciones
- **Manejo de excepciones**

#### Integración de Contenido:
- **Referencia a archivos** del memory bank
- **Inserción dinámica** de contenido
- **Formateo automático** de respuestas
- **Soporte multimedia** (imágenes, videos)
- **Enlaces externos** y referencias

## Casos de Uso Específicos

### 1. Atención al Cliente
- **Flujos de soporte** técnico
- **Escalamiento** a agentes humanos
- **Base de conocimientos** integrada
- **Seguimiento** de tickets
- **Satisfacción** del cliente

### 2. Educación y Entrenamiento
- **Cursos interactivos** paso a paso
- **Evaluaciones** y quizzes
- **Progreso del estudiante**
- **Certificaciones**
- **Recursos adicionales**

### 3. Ventas y Marketing
- **Calificación de leads**
- **Demostraciones** de productos
- **Cotizaciones** automáticas
- **Seguimiento** de prospectos
- **Análisis de interés**

### 4. Procesos Internos
- **Onboarding** de empleados
- **Procedimientos** corporativos
- **Solicitudes** internas
- **Reportes** automáticos
- **Workflows** de aprobación

## Especificaciones Técnicas

### Requisitos del Sistema:
- **PHP 7.4+** con extensiones JSON y cURL
- **Servidor web** (Apache/Nginx)
- **Almacenamiento** en archivos JSON
- **Navegador moderno** con JavaScript habilitado

### Seguridad:
- **Validación** de entrada en todos los puntos
- **Sanitización** de datos
- **Control de sesiones**
- **Protección CSRF**
- **Límites de subida** de archivos
- **Filtrado** de tipos de archivo

### Performance:
- **Carga asíncrona** de contenido
- **Caché** de flujos frecuentes
- **Optimización** de consultas
- **Compresión** de respuestas
- **Lazy loading** de recursos

## Estructura de Archivos

### Directorios Principales:
```
chatbot-1/
├── css/                    # Estilos CSS
│   ├── styles.css         # Estilos principales
│   ├── flow-editor-custom.css # Editor de flujos
│   ├── modal.css          # Modales
│   └── ...
├── js/                     # JavaScript
│   ├── chatbot.js         # Lógica del chat
│   ├── flow-editor.js     # Editor de flujos
│   └── ...
├── memory-bank/            # Almacenamiento
│   ├── flows/             # Flujos guardados
│   ├── instructions.txt   # Instrucciones
│   └── ...
└── *.php                   # Archivos PHP principales
```

### Archivos de Configuración:
- `.htaccess` - Configuración del servidor
- `composer.json` - Dependencias PHP
- `README.md` - Documentación básica

## Personalización y Extensibilidad

### Temas y Estilos:
- **CSS modular** para fácil personalización
- **Variables CSS** para colores y tipografía
- **Responsive design** para todos los dispositivos
- **Temas oscuro/claro** (preparado)

### Plugins y Extensiones:
- **Sistema de hooks** para funcionalidad adicional
- **API REST** para integraciones externas
- **Webhooks** para notificaciones
- **Módulos personalizados**

### Internacionalización:
- **Soporte multi-idioma** preparado
- **Archivos de traducción**
- **Detección automática** de idioma
- **Formatos locales** (fechas, números)

## Métricas y Analytics

### Datos Recopilados:
- **Conversaciones** por sesión
- **Flujos más utilizados**
- **Puntos de abandono**
- **Tiempo de respuesta**
- **Satisfacción del usuario**

### Reportes Disponibles:
- **Dashboard** de actividad
- **Análisis de flujos**
- **Rendimiento** del sistema
- **Uso de recursos**
- **Tendencias** temporales

## Mantenimiento y Soporte

### Logs del Sistema:
- **Registro de errores** detallado
- **Auditoría** de cambios
- **Monitoreo** de performance
- **Alertas** automáticas

### Backup y Recuperación:
- **Respaldo automático** de flujos
- **Versionado** de configuraciones
- **Restauración** punto en tiempo
- **Migración** de datos

## Roadmap de Desarrollo

### Funcionalidades Planificadas:
1. **Integración con IA** (GPT, Claude)
2. **Analytics avanzados**
3. **API REST completa**
4. **Aplicación móvil**
5. **Integración con CRM**
6. **Chatbots multicanal**
7. **Machine Learning** para optimización
8. **Reconocimiento de voz**
9. **Procesamiento de imágenes**
10. **Blockchain** para verificación

---

*Este documento representa el estado actual del sistema y se actualiza regularmente con nuevas funcionalidades y mejoras.*