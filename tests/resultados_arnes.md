# Resultado del arnés del asistente Beimy · BEIM Track IA

- Fecha de ejecución: 2026-11-24 19:53:50
- API: simulada en 127.0.0.1:8765 + modo real
- Resultado: **5 de 5 aprobadas**

| ID | Criterio | Prueba | Resultado obtenido | Estado |
|---|---|---|---|---|
| H01 | CA-BOT-02 | Conversación por menú: cada rol recorre todas sus opciones sin errores | 18 opciones recorridas por 3 roles | ✅ Aprobada |
| H02 | CA-BOT-03 | Respuestas dinámicas: cada rol ve solo sus datos | Cliente: su pedido · Diseño: 2 en área, 1 suyo, 1 retrasado · Admin: 2 en proceso | ✅ Aprobada |
| H03 | CA-BOT-05 | Pregunta libre con la API simulada | Petición con clave, modelo, instrucciones y contexto; respuesta limpia de markdown | ✅ Aprobada |
| H04 | CA-BOT-06 | Respaldo cuando no hay clave o la API falla | Sin clave: menú · API caída (500): mensaje amable + menú | ✅ Aprobada |
| H05 | CA-BOT-08 | Lo que se envía a la IA no incluye datos sensibles ni ajenos | Sin correos, teléfonos, apellidos ni pedidos ajenos | ✅ Aprobada |
