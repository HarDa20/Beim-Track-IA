# Plan técnico · 014 · Asistente virtual "Beimy"

**Spec:** [spec.md](spec.md) · **Revisión contra la constitución:** ☑ Art. II (reglas en `includes/`) ☑ Art. III ☑ Art. V.4 (enmienda: Gemini opcional desde el servidor) ☑ Art. VII.3 (datos mínimos)

## 1. Enfoque
Tres capas:
1. **Configuración** (`includes/chatbot_menu.php`): un arreglo PHP con las opciones. Es el "menú parametrizado en código".
2. **Motor** (`includes/chatbot.php`): arma el menú por rol, resuelve cada opción (texto fijo o función dinámica), construye el contexto mínimo y llama a Gemini con cURL (o `file_get_contents` si cURL no está activo). La dirección de la API es configurable (`GEMINI_API_URL`) para poder probar con un servidor simulado.
3. **Interfaz**: `chatbot_api.php` (JSON) + `js/chatbot.js` + la mascota animada en SVG/CSS. Si existe `img/mascota.png` (por ejemplo, creada con Nano Banana) se usa esa imagen.

**Arnés** (`tests/arnes_chatbot.php`): levanta un servidor que imita la API de Gemini, ejecuta conversaciones de prueba por rol y revisa las reglas; con `--real` usa la API pública de verdad. El agente de OpenCode `probador-chatbot` y el comando `/probar-chatbot` lo ejecutan y analizan.

## 2. Archivos
| Archivo | Responsabilidad | Requisitos |
|---|---|---|
| `includes/chatbot_menu.php` | Menú parametrizado | RF-BOT-02 |
| `includes/chatbot.php` | Menú por rol, respuestas, contexto, Gemini, límites | RF-BOT-02 a RF-BOT-05, RNF-BOT-01 |
| `chatbot_api.php` | Servicio JSON | RNF-BOT-02 |
| `js/chatbot.js`, `includes/layout.php`, `css/style.css` | Widget y mascota animada | RF-BOT-01 |
| `tests/arnes_chatbot.php`, `.opencode/agents/probador-chatbot.md`, `.opencode/commands/probar-chatbot.md` | Arnés | CA-BOT-02, 03, 05, 06, 08, 10 |

## 3. Configuración (`.env`)
`GEMINI_API_KEY` (de aistudio.google.com), `GEMINI_MODEL` (por defecto `gemini-flash-latest`), `GEMINI_API_URL` (solo para pruebas).

## 4. Decisiones
- La IA nunca recibe la base de datos: el servidor le pasa un resumen ya filtrado del usuario.
- Respuestas cortas (máx. ~120 palabras) y en español de Colombia, por instrucción del sistema.
