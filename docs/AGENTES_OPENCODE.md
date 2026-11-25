# Agentes de OpenCode para BEIM Track

OpenCode es un agente de programación de código abierto que trabaja en la terminal. BEIM Track trae lista su configuración para que el agente **siga el ciclo SDD** en lugar de escribir código sin especificación.

## 1. Instalación y arranque

```bash
npm install -g opencode-ai       # requiere Node.js
cd C:\xampp\htdocs\beimtrack_ia
opencode                         # abre la interfaz en la carpeta del proyecto
```

La primera vez, conecta un proveedor de modelos con `/connect` (o el comando que indique tu versión). OpenCode lee automáticamente:

| Archivo | Para qué |
|---|---|
| `AGENTS.md` | Reglas del proyecto: mapa del código, flujo SDD y comandos de prueba |
| `opencode.json` | Carga la constitución como instrucción permanente y define qué comandos de terminal se permiten sin preguntar |
| `.opencode/agents/*.md` | Los 6 agentes especializados |
| `.opencode/commands/*.md` | Los comandos `/especificar`, `/planear`, `/tareas`, `/implementar`, `/verificar`, `/auditar`, `/documentar` |

Para el **arnés del chatbot** con la API pública: pon tu clave en `.env` y ejecuta `/probar-chatbot real`. El agente corre `tests/arnes_chatbot.php --real`, lee cada respuesta de Gemini y la califica contra la spec 014.

Con la tecla **Tab** cambias entre agentes principales (`arquitecto-spec`, `implementador-sdd`, `build`, `plan`). Los subagentes se invocan escribiendo `@nombre` en el mensaje o los llaman los comandos.

## 2. Los agentes que ya están configurados

| Agente | Tipo | Puede editar | Terminal | Idea central |
|---|---|---|---|---|
| `arquitecto-spec` | Principal | Solo `specs/` (por instrucción) | Solo el verificador | Convierte una necesidad de la empresa en spec, plan y tareas |
| `implementador-sdd` | Principal | Sí | Pruebas y `php -l` sin preguntar; lo demás pregunta | Implementa una tarea a la vez, prueba primero |
| `revisor-spec` | Subagente | **No** | `git diff` y pruebas de spec | Dice si el cambio cumple la spec y la constitución |
| `qa-tester` | Subagente | Solo `tests/` (por instrucción) | Pruebas | Convierte cada `CA-` en una prueba con su ID |
| `auditor-seguridad` | Subagente | **No** | `grep` | Auditoría OWASP + Ley 1581 con escenarios de ataque |
| `documentador-academico` | Subagente | Solo `docs/` (por instrucción) | Verificador | Regenera MER, C4 y BPMN en Mermaid desde el código real |
| `probador-chatbot` | Subagente | **No** | Solo el arnés del chatbot | **Arnés con la API pública**: prueba a Beimy contra Gemini simulado y real (`/probar-chatbot real`) |

Los permisos de OpenCode (`edit: deny`, lista de comandos `allow`/`ask`/`deny`) **limitan de verdad** lo que el agente puede hacer; las restricciones "por instrucción" dependen de que el modelo obedezca el prompt, por eso el revisor y el auditor tienen la edición bloqueada.

## 3. Guion de demostración para la clase (10 minutos)

1. **Mostrar la constitución y la trazabilidad**: abrir `specs/constitucion.md` y `specs/TRAZABILIDAD.md`.
2. **Especificar en vivo** con el agente arquitecto:
   ```
   /especificar Los clientes quieren calificar de 1 a 5 estrellas el pedido cuando se entrega, con un comentario opcional, y el administrador quiere ver el promedio en el dashboard
   ```
   El agente pregunta lo indispensable y crea `specs/010-calificacion-pedidos/spec.md`.
3. `/planear specs/010-calificacion-pedidos` y `/tareas specs/010-calificacion-pedidos`.
4. Cambiar con **Tab** a `implementador-sdd` y ejecutar `/implementar specs/010-calificacion-pedidos/tasks.md`. Mostrar que la prueba se escribe primero y falla.
5. `/verificar`: el verificador muestra la funcionalidad nueva en la matriz.
6. Cerrar con `@revisor-spec revisa el último cambio` y `/auditar admin/` para mostrar que la IA también controla la calidad.

Mensaje para el profesor: **la IA no decide qué construir; ejecuta lo que el equipo especificó y el verificador demuestra que lo cumplió**.

## 4. Más ideas de agentes para el proyecto

| # | Agente | Qué haría con el código de BEIM Track | Dificultad |
|---|---|---|---|
| 1 | **Traductor de quejas a specs** | Recibe un mensaje real de la empresa ("los pendones salen tarde porque diseño no avisa") y propone qué `RF-` cambiar o agregar, sin tocar código | Baja |
| 2 | **Analista de cuellos de botella** | Ejecuta un script PHP de solo lectura sobre `historial_pedido` y redacta un informe semanal: tiempo promedio por área, pedidos retrasados, empleado con más carga | Media |
| 3 | **Generador de datos para la feria** | Crea `database/demo_feria.sql` con 40 pedidos realistas repartidos en todos los estados para que el dashboard se vea vivo en la presentación (nunca sobre la base real) | Baja |
| 4 | **Guardián de migraciones** | Cuando una spec cambia el modelo de datos, escribe `database/migraciones/00N_*.sql` con respaldo previo y actualiza `tests/schema_sqlite.sql` para que ambos esquemas sigan iguales | Media |
| 5 | **Revisor de accesibilidad y tema oscuro** | Revisa `css/style.css` y las vistas: contraste de colores, tamaños táctiles en celular, etiquetas de formularios (Art. VI) | Baja |
| 6 | **Explicador para el jurado** | Dado un archivo, genera la explicación en lenguaje sencillo de qué hace, qué requisito cumple y por qué se hizo así, para preparar la sustentación | Baja |
| 7 | **Asistente de pull requests** | Antes de subir a GitHub, arma la descripción del cambio con los `RF-` cumplidos, las pruebas ejecutadas y el extracto de `TRAZABILIDAD.md` | Baja |
| 8 | **Vigilante de la constitución** (comando programado) | Corre `/auditar` y `/verificar` sobre todo el repositorio y deja un informe en `docs/` antes de cada entrega | Baja |

Para crear uno nuevo: copia un archivo de `.opencode/agents/`, cambia `description`, `mode` y `permission`, y escribe su instrucción en el cuerpo. Mantén la regla: **si el agente no necesita editar, ponle `edit: deny`**.
