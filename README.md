# Chatbot con WhatsApp (Waha) y Gemini

## Configuración Local

1. **Clonar el repositorio**
2. **Configurar la API Key de Gemini**:
   - En tu archivo local, edita `ChatCore.php` línea 71
   - Reemplaza `"YOUR_API_KEY_HERE"` con tu API key real
   - **NUNCA subas este cambio a Git**

## Configuración en Coolify (Producción)

1. **Variables de Entorno en Coolify**:
   - Ve a tu aplicación en Coolify
   - Sección "Environment Variables"
   - Agrega: `GEMINI_API_KEY=AIzaSyAr2JZdLQwoQdQZTZO-PFbz120AccKnR6A`

2. **Redeploy** la aplicación

## Estructura

- `ChatCore.php` - Lógica principal del chatbot
- `webhook_waha.php` - Recibe mensajes de WhatsApp vía Waha
- `WahaService.php` - Envía mensajes a WhatsApp
- `process_flow.php` - Maneja flujos de conversación

## Seguridad

⚠️ **IMPORTANTE**: La API key de Gemini NUNCA debe subirse a GitHub. Se maneja mediante:
- Variable de entorno `GEMINI_API_KEY` en producción (Coolify)
- Valor local en desarrollo (no se sube a git)