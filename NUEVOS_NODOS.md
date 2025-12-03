# Nuevos Tipos de Nodos para Flujos del Chatbot

Este documento describe los 6 nuevos tipos de nodos que se han agregado al sistema de flujos del chatbot, proporcionando funcionalidades avanzadas para la automatización y mejora de la experiencia del usuario.

## 1. Nodo de Petición API (API Request)

### Descripción
Permite que el chatbot se comunique con sistemas externos a través de APIs REST.

### Propiedades
- **URL del Endpoint**: La dirección completa de la API (ej: `https://api.crm.com/v1/leads`)
- **Método HTTP**: GET, POST, PUT, DELETE
- **Headers**: Cabeceras HTTP en formato JSON para autenticación y configuración
- **Body/Payload**: Datos a enviar en formato JSON (para POST/PUT)
- **Variable de Respuesta**: Nombre de la variable donde se guardará la respuesta

### Casos de Uso
- Registrar leads en un CRM
- Consultar estado de pedidos
- Obtener datos del clima
- Integrar con sistemas de inventario

### Ejemplo de Configuración
```json
{
  "api_url": "https://api.ejemplo.com/v1/leads",
  "api_method": "POST",
  "api_headers": {
    "Authorization": "Bearer {{token}}",
    "Content-Type": "application/json"
  },
  "api_body": "{\"name\": \"{{userName}}\", \"email\": \"{{userEmail}}\"}",
  "response_variable": "lead_id"
}
```

## 2. Nodo de Generación con IA (AI Generate)

### Descripción
Utiliza inteligencia artificial para generar contenido basado en prompts personalizados.

### Propiedades
- **Proveedor de IA**: Gemini (actualmente), GPT y Claude (futuro)
- **Prompt (Plantilla)**: Instrucción para la IA con soporte para variables
- **Variable de Salida**: Donde se guardará el texto generado

### Casos de Uso
- Análisis de sentimientos
- Resumen de textos largos
- Generación de emails personalizados
- Traducción de contenido

### Ejemplo de Configuración
```json
{
  "ai_provider": "gemini",
  "ai_prompt": "Analiza el sentimiento del siguiente mensaje y clasifícalo como positivo, negativo o neutro: {{user_message}}",
  "output_variable": "sentimiento"
}
```

## 3. Nodo de Búsqueda RAG (RAG Search)

### Descripción
Busca información relevante en la base de conocimientos del chatbot (Memory Bank).

### Propiedades
- **Término de Búsqueda**: Consulta a buscar (puede usar variables)
- **Fuente de Datos**: Todo, Archivos Subidos, o URLs Extraídas
- **Número de Resultados**: Cantidad de fragmentos relevantes a devolver
- **Variable de Salida**: Donde se guardarán los resultados

### Casos de Uso
- Responder preguntas sobre políticas de la empresa
- Buscar información en manuales técnicos
- Encontrar datos específicos en documentos

### Ejemplo de Configuración
```json
{
  "search_term": "{{user_message}}",
  "data_source": "files",
  "max_results": 3,
  "output_variable": "contexto_relevante"
}
```

## 4. Nodo de Carrusel (Carousel)

### Descripción
Muestra información de forma visual usando tarjetas con imágenes, títulos y botones.

### Propiedades
- **Tarjetas**: Array de objetos con imagen, título, descripción y botones

### Estructura de Tarjeta
```json
{
  "image": "https://ejemplo.com/imagen.jpg",
  "title": "Título de la tarjeta",
  "description": "Descripción del contenido",
  "buttons": [
    {
      "text": "Ver más",
      "value": "ver_producto_123"
    }
  ]
}
```

### Casos de Uso
- Catálogo de productos
- Menú de restaurante
- Galería de servicios
- Lista de artículos de blog

## 5. Nodo de Sub-flujo (Subflow)

### Descripción
Ejecuta otro flujo como parte del flujo actual, permitiendo reutilización de lógica.

### Propiedades
- **ID del Sub-flujo**: Identificador del flujo a ejecutar
- **Mapeo de Variables**: Cómo pasar variables entre flujos

### Casos de Uso
- Validación de direcciones reutilizable
- Proceso de autenticación común
- Lógica de cálculo compartida

### Ejemplo de Configuración
```json
{
  "subflow_id": "validacion_direccion",
  "variable_mapping": [
    {
      "source": "direccion_usuario",
      "target": "direccion_input"
    },
    {
      "source": "resultado_validacion",
      "target": "direccion_valida"
    }
  ]
}
```

## 6. Nodo de Pausa (Delay)

### Descripción
Introduce una pausa en la conversación para hacer la interacción más natural.

### Propiedades
- **Duración**: Tiempo en segundos (0.1 - 30)
- **Mostrar Indicador de Escritura**: Si mostrar "..." mientras espera

### Casos de Uso
- Simular tiempo de "pensamiento"
- Pausas dramáticas antes de respuestas importantes
- Mejorar el ritmo de la conversación

### Ejemplo de Configuración
```json
{
  "duration": 2.5,
  "show_typing": true
}
```

## Implementación Técnica

### Backend (process_flow.php)
Se han agregado nuevos casos en el switch principal y métodos específicos para cada tipo de nodo:
- `executeApiRequest()`
- `executeAiGenerate()`
- `executeRagSearch()`
- `executeCarousel()`
- `executeSubflow()`
- `executeDelay()`

### Frontend (flow-editor.js)
Se han actualizado las siguientes funciones:
- `createNodeHTML()` - Para mostrar los nodos en el editor
- `getNodePreview()` - Para mostrar vista previa de configuración
- `createModalContent()` - Para formularios de edición
- `saveNodeData()` - Para guardar configuraciones
- `addNode()` - Para crear nuevos nodos

### Interfaz de Usuario
Los nuevos nodos están disponibles en:
- Panel lateral del editor de flujos (`flow_editor.php`)
- Panel de administración (`admin.php`)
- Con iconos distintivos y colores únicos

## Variables del Sistema

Los nuevos nodos pueden usar y crear variables del sistema:

### Variables de Entrada Comunes
- `{{user_message}}` - Último mensaje del usuario
- `{{user_name}}` - Nombre del usuario
- `{{session_id}}` - ID de la sesión

### Variables de Salida
Cada nodo puede crear nuevas variables que otros nodos pueden usar:
- API Request → `{{api_response}}`
- AI Generate → `{{ai_output}}`
- RAG Search → `{{search_results}}`
- Delay → No genera variables

## Consideraciones de Seguridad

1. **API Keys**: Nunca hardcodear claves en el código
2. **Validación**: Todos los inputs son validados
3. **Rate Limiting**: Las llamadas a APIs externas están limitadas
4. **Sanitización**: Los datos de entrada son sanitizados

## Próximos Pasos

1. Implementar más proveedores de IA (GPT, Claude)
2. Agregar más tipos de interfaces visuales
3. Mejorar el sistema de variables
4. Implementar analytics para los flujos
5. Agregar sistema de versionado de flujos

---

*Documentación actualizada: Diciembre 2024*