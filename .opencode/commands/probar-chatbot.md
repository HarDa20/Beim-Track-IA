---
description: Arnés del asistente Beimy · prueba el chatbot contra la API de Gemini (simulada y, opcionalmente, la real)
agent: probador-chatbot
subtask: true
---

Prueba el asistente Beimy. $ARGUMENTS

Resultado del arnés con la API simulada:
!`php tests/arnes_chatbot.php`

Si en los argumentos dice "real", ejecuta además `php tests/arnes_chatbot.php --real` y evalúa cada respuesta de la API pública de Gemini según la spec 014.
