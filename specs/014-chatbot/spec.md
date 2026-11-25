# Especificación · 014 · Asistente virtual "Beimy" (chatbot con IA)

**Código:** BOT · **Estado:** Implementada (IA activa al configurar `GEMINI_API_KEY`) · **Origen:** SDD completo (pedido del profesor, 22/11/2026)

## 1. Problema y objetivo
Los clientes llaman para preguntar "¿en qué va mi pedido?" o "¿cuánto cuesta un pendón?", y los empleados preguntan cómo usar el sistema. El profesor pidió **un chatbot animado con inteligencia artificial, con el menú parametrizado en el código, de modo que según la opción elegida se responda de una forma distinta**, y **un arnés hecho con OpenCode para probarlo contra la API pública**.

## 2. Historias de usuario
- **HU-BOT-01** · Como cliente, quiero preguntarle al asistente en qué estado están mis pedidos sin llamar a la empresa.
- **HU-BOT-02** · Como empleado, quiero que el asistente me diga qué tengo pendiente en mi área.
- **HU-BOT-03** · Como cualquier usuario, quiero escribir una pregunta con mis palabras y recibir una respuesta útil.

## 3. Requisitos funcionales
- **RF-BOT-01** · En todas las pantallas internas aparece un asistente animado ("Beimy") en la esquina inferior derecha; al abrirlo saluda por el nombre y muestra el menú.
- **RF-BOT-02** · El menú está **parametrizado en código** en `includes/chatbot_menu.php`: cada opción define su texto, ícono, roles que la ven, tipo de respuesta (texto fijo o consulta dinámica) y opciones siguientes. Agregar o quitar una opción no exige tocar la lógica.
- **RF-BOT-03** · Las respuestas dinámicas usan datos reales y **solo del usuario que pregunta**: "Mis pedidos" (cliente), "Mi bandeja" (empleado), "Resumen del día" (administrador) y "Precios" (categorías activas).
- **RF-BOT-04** · Si el usuario escribe una pregunta libre, el sistema la envía a la API de Google Gemini con un contexto mínimo (rol, primer nombre, sus pedidos resumidos y la lista de precios). Sin clave configurada, o si la API falla, responde con un mensaje amable y vuelve a mostrar el menú.
- **RF-BOT-05** · Límites: mensajes de hasta 500 caracteres, máximo 20 preguntas a la IA cada 10 minutos por sesión y memoria de las últimas 6 intervenciones.

## 4. Requisitos no funcionales
- **RNF-BOT-01** · A la IA nunca se envían correos, teléfonos, contraseñas ni datos de otros usuarios (constitución, Art. VII.3).
- **RNF-BOT-02** · El servicio `chatbot_api.php` exige sesión y POST con CSRF, responde JSON, y el navegador muestra las respuestas como texto (nunca como HTML).

## 5. Criterios de aceptación
- **CA-BOT-01** (RF-BOT-01, RF-BOT-02) Dado un cliente, cuando abre el asistente, entonces recibe el saludo con su nombre y solo las opciones de cliente. ⟶ Pruebas: B01, S12
- **CA-BOT-02** (RF-BOT-02) Dada la configuración del menú, entonces cada rol ve exactamente las opciones que la configuración le asigna y toda opción siguiente existe. ⟶ Pruebas: S12, H01
- **CA-BOT-03** (RF-BOT-03) Dado un cliente con pedidos, cuando elige "Mis pedidos", entonces ve sus pedidos y ninguno de otro cliente. ⟶ Pruebas: B02, H02
- **CA-BOT-04** (RF-BOT-03) Dado un cliente, cuando pide una opción de administrador por el servicio, entonces se rechaza. ⟶ Pruebas: B06
- **CA-BOT-05** (RF-BOT-04) Dada una pregunta libre y la API simulada, entonces la petición lleva el contexto y la respuesta de la IA llega al usuario. ⟶ Pruebas: H03
- **CA-BOT-06** (RF-BOT-04) Dada una pregunta libre sin clave o con la API caída, entonces responde con el mensaje alterno y el menú. ⟶ Pruebas: B05, H04
- **CA-BOT-07** (RF-BOT-05) Dado un mensaje de más de 500 caracteres o más de 20 preguntas en 10 minutos, entonces se rechaza con un aviso. ⟶ Pruebas: S13
- **CA-BOT-08** (RNF-BOT-01) Dado el contexto que se envía a la IA, entonces no contiene correos, teléfonos ni pedidos ajenos. ⟶ Pruebas: S14, H05
- **CA-BOT-09** (RNF-BOT-02) Dado un visitante sin sesión o una petición sin CSRF, entonces el servicio responde 401/403 en JSON. ⟶ Pruebas: B03, B04
- **CA-BOT-10** (RF-BOT-04) Dada una clave real de Gemini, cuando se ejecuta el arnés en modo real, entonces las respuestas llegan en español y sin datos ajenos. ⟶ Verificación manual: `php tests/arnes_chatbot.php --real` o el comando `/probar-chatbot` de OpenCode.

## 6. Fuera de alcance
- Que el asistente cree o modifique pedidos (solo consulta).
- Voz.
