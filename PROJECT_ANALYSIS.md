# Análisis Detallado del Proyecto Chatbot con Gemini

Este documento proporciona un análisis exhaustivo de la arquitectura, flujos de datos y lógica del sistema de chatbot actual. Está diseñado para que cualquier IA o desarrollador pueda comprender perfectamente el funcionamiento del proyecto.

## 1. Visión General del Sistema

El proyecto es un **Chatbot Asistente Inteligente** construido en **PHP** que integra la **API de Gemini 2.5** de Google. Su arquitectura es híbrida, combinando dos modos de operación principales:

1.  **Modo Conversacional Libre (RAG)**: Utiliza Recuperación Aumentada por Generación (RAG) para responder preguntas basándose en documentos cargados.
2.  **Modo Flujo Estructurado**: Ejecuta flujos de conversación predefinidos (árboles de decisión) para guiar al usuario a través de procesos específicos.

### Tecnologías Clave
*   **Backend**: PHP 7.4+
*   **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
*   **IA**: Google Gemini 2.5 Flash API
*   **Almacenamiento**: Sistema de archivos (JSON) - "TinyDB"
*   **Gestión de Estado**: Sesiones PHP y archivos JSON en `memory-bank/`

---

## 2. Arquitectura de Archivos y Directorios

### Raíz del Proyecto
*   **`chatbot.php`**: Punto de entrada principal para la interfaz de usuario del chat.
*   **`admin.php`**: Panel de administración para subir documentos y configurar instrucciones.
*   **`process_message.php`**: Controlador principal. Recibe los mensajes del usuario, gestiona el contexto y decide si usar el procesador de flujos o la API de Gemini directamente.
*   **`process_flow.php`**: Motor de ejecución de flujos. Contiene la clase `FlowProcessor` que interpreta y ejecuta los nodos de los flujos JSON.
*   **`flow_editor.php`**: Interfaz visual para crear y editar flujos de conversación.
*   **`*_flow.php`**: Scripts auxiliares para la gestión de flujos (save, get, delete, toggle).

### Directorio `memory-bank/`
Este directorio actúa como la base de datos y memoria del sistema:
*   **`conversation_{SESSION_ID}.json`**: Historial de chat persistente por sesión.
*   **`flow_state_{SESSION_ID}.json`**: Estado actual del usuario dentro de un flujo activo (nodo actual, variables).
*   **`flows/*.json`**: Definiciones de los flujos de conversación (estructura de nodos y conexiones).
*   **Documentos (`.txt`, `.csv`, `.pdf`, etc.)**: Archivos subidos que sirven como base de conocimiento para el RAG.
*   **`instructions.txt`**: Prompt del sistema (System Prompt) personalizado.

---

## 3. Flujos de Datos y Procesos

### 3.1. Proceso de Manejo de Mensajes (`process_message.php`)

1.  **Recepción**: El frontend (`js/chatbot.js`) envía el mensaje del usuario vía POST.
2.  **Inicialización de Sesión**: Se inicia/recupera la sesión PHP y se instancia `TinyDB` para cargar el historial de `memory-bank/conversation_{ID}.json`.
3.  **Carga de Contexto (RAG)**:
    *   Escanea `memory-bank/` buscando archivos de referencia.
    *   Procesa CSVs (limita a 100 filas) y extrae texto de otros documentos.
    *   Construye un string de contexto con esta información.
4.  **Evaluación de Flujo**:
    *   Instancia `FlowProcessor`.
    *   Llama a `processMessage($userMessage)`.
    *   Si el `FlowProcessor` devuelve una acción (diferente de 'none'), se ejecuta esa acción y se devuelve la respuesta, saltando la llamada directa a Gemini.
5.  **Fallback a Gemini (Modo Libre)**:
    *   Si no hay flujo activo, construye el prompt final:
        *   `Instrucciones del Sistema` + `Contexto RAG` + `Historial de Chat` + `Mensaje Usuario`.
    *   Envía la solicitud a la API de Gemini (`gemini-2.5-flash`).
    *   Recibe, limpia y guarda la respuesta.

### 3.2. Motor de Flujos (`process_flow.php`)

El `FlowProcessor` es una máquina de estados que navega por grafos definidos en JSON.

#### Estructura de un Flujo (JSON)
*   **Nodes**: Lista de nodos con `id`, `type`, `data` (configuración) y posición visual.
*   **Connections**: Lista de enlaces entre nodos (`source` -> `target`).

#### Tipos de Nodos Soportados
1.  **`start`**: Punto de inicio del flujo.
2.  **`condition`**: Ramificación lógica.
    *   *Tipos*: Texto exacto, Contiene texto, **IA (Gemini)**.
    *   La evaluación por IA envía un prompt específico a Gemini para que responda `true` o `false` sobre si el mensaje cumple una condición semántica.
3.  **`action`**: Ejecuta una acción simple (responder mensaje).
4.  **`redirect`**: Indica al frontend que redirija a una URL.
5.  **`api_request`**: Realiza una petición HTTP externa (GET/POST).
6.  **`ai_generate`**: Usa Gemini dentro del flujo para generar contenido dinámico.
7.  **`rag_search`**: Realiza una búsqueda específica en la base de conocimiento dentro del flujo.
8.  **`carousel`**: Muestra tarjetas interactivas (UI rica).
9.  **`subflow`**: Ejecuta otro flujo definido, permitiendo modularidad.
10. **`delay`**: Introduce una pausa artificial.

#### Lógica de Ejecución
1.  **Carga de Estado**: Verifica si existe `flow_state_{ID}.json` para saber en qué nodo se quedó el usuario.
2.  **Activación**: Si no hay flujo activo, busca flujos marcados como `active` y verifica sus nodos de inicio.
3.  **Navegación**:
    *   Procesa el nodo actual.
    *   Si es una condición, evalúa el input del usuario y decide el camino (Output 0: True, Output 1: False).
    *   Si es una acción automática, la ejecuta y avanza al siguiente nodo inmediatamente (hasta un límite de iteraciones para evitar bucles).
    *   Guarda el nuevo estado en `flow_state_{ID}.json`.

---

## 4. Integración con Gemini API

El sistema utiliza el modelo `gemini-2.5-flash` para dos propósitos:

1.  **Generación de Respuestas (Chat Libre)**:
    *   Endpoint: `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent`
    *   Configuración: Temperature 0.7, TopP 0.95, TopK 40.
    *   Contexto: Incluye todo el contenido de los archivos en `memory-bank/`.

2.  **Evaluación Lógica (Nodos de Condición)**:
    *   Endpoint: `https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent` (Nota: Usa 1.5 Flash para mayor velocidad en lógica).
    *   Prompt: "Evalúa la siguiente condición y responde SOLO con 'true' o 'false'..."
    *   Configuración: Temperature 0 (determinista).

---

## 5. Frontend (`js/chatbot.js`)

*   **Renderizado**: Soporta HTML en las respuestas del bot (tablas, listas, encabezados).
*   **Acciones de Flujo**:
    *   Escucha la propiedad `flow_action` en la respuesta JSON.
    *   `redirect`: Redirige `window.location` tras 2 segundos.
    *   `api` / `function`: Placeholders para lógica futura en cliente.
*   **UX**: Indicador de "Pensando...", scroll automático, manejo de errores.

## 6. Puntos Críticos para Mantenimiento

1.  **API Key**: Está hardcodeada en `process_message.php` y `process_flow.php`. Debería moverse a un archivo de configuración o variable de entorno.
2.  **Gestión de Archivos**: El sistema carga *todos* los archivos de `memory-bank/` en cada request. Con muchos archivos, esto podría exceder el límite de tokens o ralentizar el sistema.
3.  **Seguridad**: No hay autenticación robusta para el panel de administración (`admin.php`), solo seguridad por oscuridad o acceso local.
4.  **Concurrencia**: El uso de archivos JSON planos para "base de datos" puede causar condiciones de carrera en entornos de alto tráfico.

---

Este análisis cubre la totalidad de la lógica operativa del proyecto actual. Cualquier IA que lea este documento tendrá el contexto completo para realizar modificaciones, depuraciones o expansiones del sistema.
