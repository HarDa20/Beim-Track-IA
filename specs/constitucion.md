# Constitución de BEIM Track

**Versión:** 1.1 · **Ratificada:** 18 de noviembre de 2026 · **Enmendada:** 22 de noviembre de 2026 · **Equipo:** Jorge Junior Miranda Gómez, Haider Stive Pava López, Harold David Hernández Vásquez

La constitución reúne los principios que **ninguna especificación, plan o cambio de código puede contradecir**. Si una spec nueva entra en conflicto con un artículo, se modifica la spec o se enmienda la constitución de forma explícita (sección final), nunca se ignora en silencio.

---

## Artículo I · La especificación manda

1. Toda funcionalidad nace en `specs/NNN-nombre/spec.md` antes de escribir código.
2. Cada función que cumple un requisito lleva la etiqueta `@spec RF-…` en su comentario.
3. Cada criterio de aceptación se demuestra con una prueba automática o, mientras no sea posible, con una verificación manual descrita por escrito.
4. Un cambio que no se puede rastrear a un requisito no se integra.

## Artículo II · Las reglas del negocio viven en el servidor

1. Las reglas del flujo de pedidos están en un solo lugar: `includes/pedidos.php`. Las páginas solo las llaman.
2. El navegador nunca decide permisos, estados ni precios; solo muestra lo que el servidor decide.
3. El flujo de estados está descrito en el contrato `specs/003-flujo-produccion/contracts/flujo_estados.json` y el código debe coincidir con él (lo comprueba `tests/pruebas_spec.php`).

## Artículo III · Seguridad desde el diseño

1. Consultas preparadas PDO en todo acceso a datos; está prohibido concatenar datos del usuario en SQL.
2. Toda salida HTML pasa por `e()` (`htmlspecialchars`).
3. Todo formulario que cambia datos usa POST con token CSRF (`requierePostConCsrf`).
4. Contraseñas con `password_hash` / `password_verify`; nunca en texto plano ni en registros.
5. Control de acceso por rol, área y propiedad del pedido en cada petición.
6. Credenciales solo en `.env`, que nunca se versiona.
7. Los errores técnicos van al log del servidor; el usuario ve mensajes comprensibles.

## Artículo IV · Trazabilidad e integridad de los datos

1. Cada cambio de estado de un pedido guarda usuario, estado anterior, estado nuevo y fecha, dentro de la misma transacción del cambio.
2. Los datos que dan sentido a un registro histórico (precio unitario, nombre de la categoría) se copian al pedido; cambiar un precio no altera pedidos anteriores.
3. Las cuentas se desactivan, no se borran, para no perder historial.
4. El sistema nunca reporta como enviado un aviso que no salió.

## Artículo V · Simplicidad acorde con el alcance

1. Monolito PHP 8 en tres capas, MySQL/MariaDB, sin frameworks ni Composer, instalable en XAMPP.
2. Sin librerías de terceros en el frontend; CSS y JavaScript propios.
3. Se prefiere la solución que el equipo puede explicar ante el jurado sobre la más sofisticada.
4. *(Enmienda 1)* Se permite consumir **desde el servidor** la API pública de Google Gemini para el asistente virtual. Es opcional: la clave vive en `.env` y el sistema debe funcionar completo sin ella.

## Artículo VI · Experiencia de uso

1. Interfaz en español de Colombia, diseño responsive (escritorio, tablet y celular) y tema oscuro por defecto, con tema claro opcional *(Enmienda 1)*.
2. Mensajes de error que dicen qué pasó y cómo corregirlo.
3. Formatos locales: pesos colombianos y fechas `dd/mm/aaaa`.

## Artículo VII · Cumplimiento legal

1. Ley 1581 de 2012: el registro exige autorización expresa del tratamiento de datos y guarda la fecha de esa autorización.
2. Ley 527 de 1999: el historial de pedidos se conserva de forma consultable y sin edición posterior.
3. *(Enmienda 1)* A un servicio de IA externo solo se envía lo mínimo para responder al usuario que pregunta: nunca correos, teléfonos, contraseñas ni datos de otros clientes.

## Artículo VIII · Calidad verificable

1. Ninguna entrega se considera terminada si `php tests/verificar_specs.php` termina con errores.
2. Las pruebas automáticas crean sus propios datos (`@qa.beimtrack.test`) y los eliminan al terminar.
3. Todo error encontrado por una prueba se corrige en el código, no ajustando la prueba para que pase.

## Artículo IX · Uso de agentes de IA

1. Los agentes (OpenCode u otros) siguen esta constitución igual que una persona del equipo; `AGENTS.md` la resume para ellos.
2. Un agente no modifica una spec y su código en el mismo paso sin que una persona apruebe el cambio de la spec.
3. El código generado por IA se revisa con el agente `revisor-spec` y se prueba antes de integrarse.

---

## Enmiendas

| Fecha | Artículo | Cambio | Motivo |
|---|---|---|---|
| 2026-11-18 | — | Versión inicial | Adopción de SDD en el curso de Inteligencia Artificial |
| 2026-11-22 | V.4, VI.1, VII.3 | API de Gemini opcional desde el servidor, tema claro y regla de datos mínimos para IA | Requisitos del profesor: chatbot con IA y cambio de tono claro/oscuro |
