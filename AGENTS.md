# BEIM Track · Instrucciones para agentes de IA (OpenCode)

Este repositorio se desarrolla con **Desarrollo Dirigido por Especificaciones (SDD)**. Antes de tocar cualquier archivo, lee `specs/constitucion.md` y la spec de la funcionalidad en `specs/NNN-*/spec.md`.

## Proyecto
- Aplicación web de gestión y seguimiento de pedidos para una empresa de publicidad (Bogotá).
- PHP 8 sin frameworks ni Composer · MySQL/MariaDB con PDO · HTML/CSS/JS propios · XAMPP.
- Idioma de la interfaz, comentarios, mensajes y specs: **español de Colombia**.

## Mapa del código
| Carpeta | Contenido |
|---|---|
| `specs/` | Constitución, especificaciones, planes, tareas, contrato del flujo y `TRAZABILIDAD.md` (generado) |
| `includes/pedidos.php` | **Todas** las reglas del negocio de pedidos (único lugar permitido) |
| `includes/` | auth (multisesión), security, helpers (tema, avatar), usuarios (foto), archivos, alertas, exportacion, gerencia, notificaciones/ |
| `includes/chatbot_menu.php` | **Menú parametrizado del asistente Beimy** (solo configuración) |
| `includes/chatbot.php` | Motor del asistente: menú por rol, respuestas dinámicas, contexto y llamada a Gemini |
| `cliente/`, `empleado/`, `admin/` | Páginas por rol: solo reciben datos, llaman a `includes/` y muestran |
| `database/` | `schema.sql` (MySQL) y migraciones |
| `tests/` | `pruebas_spec.php`, `pruebas_funcionales.php`, `verificar_specs.php`, `schema_sqlite.sql` |

## Flujo de trabajo obligatorio (SDD)
1. **Especificar** (`/especificar`): crear o cambiar `specs/NNN-*/spec.md`. Sin código.
2. **Planear** (`/planear`): `plan.md` con archivos, datos y decisiones, revisado contra la constitución.
3. **Tareas** (`/tareas`): `tasks.md`, cada tarea con `Cumple: RF-…` y `Prueba: …`.
4. **Implementar** (`/implementar`): una tarea a la vez. Primero la prueba, luego el código.
5. **Verificar** (`/verificar`): las tres órdenes de abajo deben terminar sin errores.

## Reglas que no se negocian
- Pon `@spec RF-XXX-NN` en el comentario de cada función o página que cumple un requisito.
- No cambies una spec y su código en el mismo paso sin aprobación humana de la spec.
- No modifiques una prueba para que pase: corrige el código (Art. VIII).
- Consultas preparadas PDO siempre; salida HTML con `e()`; POST + `requierePostConCsrf()` para cambiar datos.
- Si cambias el flujo de estados, cambia primero `specs/003-flujo-produccion/contracts/flujo_estados.json`.
- No agregues librerías, frameworks ni CDNs (Art. V).
- Nunca leas, muestres ni subas `.env` (contiene la clave de Gemini).
- Toda dirección interna se arma con `url()`: así se conserva la ranura de multisesión `?u=N`.
- Para cambiar el chatbot, edita primero `includes/chatbot_menu.php`; el motor solo se toca si cambia la lógica.

## Comandos de verificación
```bash
php -l archivo.php                                   # sintaxis
php tests/pruebas_spec.php                           # reglas y contratos (sin servidor)
php tests/pruebas_funcionales.php http://localhost/beimtrack_ia   # HTTP (requiere XAMPP)
php tests/arnes_chatbot.php                          # arnés del chatbot (API de Gemini simulada)
php tests/verificar_specs.php                        # trazabilidad spec ↔ código ↔ pruebas
```

Sin XAMPP, las pruebas HTTP se pueden correr con SQLite:
```bash
php -r '$p=new PDO("sqlite:/tmp/qa.sqlite"); $p->exec(file_get_contents("tests/schema_sqlite.sql"));'
DB_DRIVER=sqlite DB_PATH=/tmp/qa.sqlite php -S 127.0.0.1:8100 tests/router.php &
DB_DRIVER=sqlite DB_PATH=/tmp/qa.sqlite php tests/pruebas_funcionales.php http://127.0.0.1:8100
```

## Agentes del proyecto (`.opencode/agents/`)
| Agente | Modo | Para qué |
|---|---|---|
| `arquitecto-spec` | principal | Conversa con el equipo y escribe specs, planes y tareas. No toca código. |
| `implementador-sdd` | principal | Implementa tareas de `tasks.md` con prueba primero. |
| `revisor-spec` | subagente | Revisa un cambio contra la spec y la constitución. Solo lectura. |
| `qa-tester` | subagente | Escribe y ejecuta pruebas a partir de los criterios de aceptación. |
| `auditor-seguridad` | subagente | Auditoría OWASP del código. Solo lectura. |
| `documentador-academico` | subagente | Mantiene al día los documentos del curso (MER, C4, BPMN, arquitectura) desde el código y las specs. |
| `probador-chatbot` | subagente | Arnés del asistente Beimy: lo prueba contra la API de Gemini simulada y la real (`/probar-chatbot`). |
