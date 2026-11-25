# BEIM Track v2 · Informe de cambios por fase

## Decisiones tomadas (recomendaciones de la auditoría)

1. **Marca:** la interfaz se llama BEIM Track. La base de datos conserva el nombre `imprimex` para no romper instalaciones existentes.
2. **Flujo de estados:** Recibido → En diseño → En producción → Control de calidad → Listo para entrega → Entregado, más Borrador y Cancelado. El estado "Confirmado" de la v1 se migra a "En diseño".
3. **Responsable por etapa:** el pedido pasa por la bandeja de cada área; un empleado lo toma o el administrador asigna un responsable del área correspondiente. Al cambiar de área, el pedido queda sin responsable en la bandeja de la siguiente área.
4. **Registro por el personal:** Recepción y Administración registran pedidos de llamadas, chats o atención presencial, con el canal de recepción.
5. **Borrador:** se implementó de verdad: guardar, retomar, enviar y eliminar.

## Fase 3 · Base de datos

| Cambio | Problema que soluciona | Tablas |
|---|---|---|
| `categoria_id` con llave foránea y categorías administrables (activa, unidad, descripción) | Las categorías eran un ENUM fijo repetido en 3 archivos | pedidos, categorias_precios |
| `precio_unitario` guardado en cada pedido | Cambiar un precio alteraba el sentido de los costos anteriores | pedidos |
| `usuario_id`, `estado_anterior` y `estado_nuevo` en el historial | El historial era texto libre, sin autor | historial_pedido |
| Tabla `archivos_pedido` | Solo se permitía un archivo y nadie podía verlo | nueva |
| Tablas `alertas` y `notificaciones_envios` | Alertas internas y registro de avisos por correo, incluidos los fallidos (RF-27 a RF-29) | nuevas |
| Tabla `intentos_login` | Protección contra fuerza bruta | nueva |
| `telefono`, `activo`, `acepta_datos_en` y `ultimo_acceso` en usuarios; área como ENUM | Teléfono de contacto, desactivar en lugar de borrar, Ley 1581 | usuarios |
| `canal_recepcion`, `registrado_por`, `fecha_entregado` y `actualizado_en` en pedidos | Canal del pedido y medición del cumplimiento | pedidos |
| Llave del cliente con `ON DELETE RESTRICT` (antes `CASCADE`) | Borrar un usuario eliminaba sus pedidos e historial | pedidos |
| Índices en estado, fecha de entrega y fecha de creación; `CHECK cantidad > 0` | Rendimiento de los filtros e integridad de los datos | pedidos |

Migración segura: `database/migraciones/001_v1_a_v2.sql` (crea tablas `respaldo_v1_*` antes de modificar nada). Verificación: `database/verificacion.sql`. Respaldo del esquema original: `database/schema_v1_respaldo.sql`.

## Fase 4 · Backend

- **Servicios nuevos:** `includes/pedidos.php` (reglas del flujo, transacciones e historial), `includes/archivos.php`, `includes/alertas.php`, `includes/usuarios.php`, `includes/security.php`, `includes/notificaciones/` y `config/env.php`.
- **Páginas nuevas:** `pedido.php`, `archivo.php`, `subir_archivo.php`, `alertas.php`, `perfil.php`, `empleado/registrar_pedido.php`, `empleado/tomar_pedido.php`, `cliente/cancelar_pedido.php`, `admin/pedidos.php`, `admin/usuarios.php`, `admin/categorias.php` y `admin/actividad.php`.
- **Archivos existentes reescritos, con la misma ruta:** todos los de la v1. `cliente/estado_pedido.php` se conserva y redirige a `pedido.php`.

## Fase 5 · Frontend

Sistema de diseño oscuro en una sola hoja (`css/style.css`): menú lateral, barra superior con campana de alertas, tarjetas de indicadores, tablas con desplazamiento en el celular, formularios, etiquetas de estado por color, línea de etapas, ventanas de confirmación, mensajes de éxito y error, gráficos en SVG y CSS sin librerías, y diseño responsive (escritorio, tablet y celular). Iconos SVG propios en lugar de emojis. Se eliminaron los textos del boceto que veía el usuario final.

## Fase 7 · Pruebas

57 pruebas automáticas aprobadas (ver `tests/resultados_ultima_ejecucion.md`). Las pruebas encontraron y permitieron corregir 3 errores durante el desarrollo: un error 500 en el dashboard, un aviso de variable no definida y un mensaje de archivo incompleto.

**Limitación:** en el entorno de desarrollo de esta entrega se usó SQLite como motor sustituto, porque no había un servidor MySQL disponible; el código SQL de la aplicación es compatible con ambos. **Falta ejecutar en XAMPP** con MySQL/MariaDB:

1. Importar `schema.sql`, o ejecutar la migración `001` sobre una copia de la base v1, más `verificacion.sql`.
2. `php tests/pruebas_funcionales.php http://localhost/imprimex`.
3. Revisión visual en un celular real.

## Fase 8 · Documentos académicos que deben actualizarse

| Documento | Qué actualizar |
|---|---|
| Modelo Entidad-Relación | Reemplazar por el esquema v2: 8 tablas, con los nombres reales de tablas y campos. Ahora coincide con su MER conceptual (categoría, archivo, alerta, medio de notificación e historial con usuario y estados). |
| Arquitectura de la aplicación | Tabla de componentes con los archivos nuevos; pasar Notificaciones de "Por crear" a "Implementado (correo desactivado hasta tener una cuenta SMTP)"; **retirar WhatsApp Business Platform** de los elementos, la comunicación entre componentes y la justificación, y marcar RF-26 como fuera de alcance; actualizar la trazabilidad RF (RF-05, RF-09/10, RF-12, RF-19, RF-21, RF-23, RF-25 a RF-29); en el flujo de creación, el redireccionamiento va al detalle del pedido. |
| Modelo C4 | Nivel 3: agregar Servicio de pedidos, Archivos, Alertas, Notificaciones y Seguridad. Nivel 1: quitar WhatsApp Business; el correo pasa a "integración preparada". Quitar la nota de que el historial no guarda usuario ni estados. |
| Diagrama BPMN | En el carril de servicios externos, dejar solo el envío de correo. Agregar el carril de Recepción (registro por llamada, chat o en persona) y las compuertas de Calidad (aprobar o devolver a producción) y de cancelación. |
| Stack tecnológico | Quitar WhatsApp Business Platform de servicios externos (decisión: complejidad de configuración). Las afirmaciones sobre transiciones, transacciones y CSRF ahora son ciertas; agregar `.env`, la verificación de archivos y las pruebas automáticas. Corregir los dos "¡Error! Marcador no definido" del índice. |
| Requerimientos / proyecto | El resumen dice que la app "permite crear usuarios, iniciar sesión y crear pedidos": actualizarlo con el alcance real. |
| GitHub | Subir el código con `.gitignore` (excluye `.env` y `uploads/`). |

## Fase 9 · Desarrollo Dirigido por Especificaciones (SDD)

| Cambio | Para qué | Archivos |
|---|---|---|
| Constitución del proyecto | Principios que ninguna spec ni cambio puede contradecir | `specs/constitucion.md` |
| Especificaciones 001 a 008 (retroactivas) | Gobernar con specs todo el código existente: 44 requisitos y 77 criterios de aceptación con su prueba | `specs/00N-*/spec.md, plan.md, tasks.md` |
| Contrato ejecutable del flujo | El flujo de estados y los permisos por rol quedan en JSON y una prueba comprueba que el código coincide | `specs/003-flujo-produccion/contracts/flujo_estados.json` |
| Etiquetas `@spec` | Cada función y página indica qué requisito cumple | `includes/*.php`, páginas, `.htaccess` |
| Pruebas de especificación S01–S10 | Reglas del negocio sin servidor web | `tests/pruebas_spec.php` |
| Verificador SDD y matriz de trazabilidad | Demostrar automáticamente que spec, código y pruebas están alineados | `tests/verificar_specs.php`, `specs/TRAZABILIDAD.md` |
| **Funcionalidad 009 · Exportar pedidos a CSV** | Demostración del ciclo completo: spec → plan → tareas → pruebas en rojo → código → verde | `specs/009-exportar-pedidos/`, `admin/exportar_pedidos.php`, `includes/exportacion.php`, pruebas E01–E04, S09–S10 |
| Agentes y comandos de OpenCode | Que la IA trabaje dentro del ciclo SDD | `AGENTS.md`, `opencode.json`, `.opencode/`, `docs/AGENTES_OPENCODE.md` |

Resultado: 61/61 pruebas funcionales, 10/10 pruebas de especificación y verificador sin errores (ejecutado con SQLite; falta repetirlo en XAMPP con MySQL).

## Fase 10 · Versión IA: pedidos del profesor (22/11/2026)

Todas siguieron el ciclo SDD completo (spec → plan → tareas → pruebas → código) y una enmienda de la constitución (Art. V.4, VI.1 y VII.3).

| Spec | Cambio | Archivos principales |
|---|---|---|
| 010 | Multisesión: hasta 5 cuentas por navegador, una por pestaña con `?u=N` | `includes/auth.php`, `includes/helpers.php` (`url`), `index.php`, `login_process.php`, `logout.php`, `includes/layout.php` |
| 011 | Tema claro/oscuro y 5 colores de acento, aplicados desde el servidor | `includes/helpers.php`, `includes/layout.php`, `css/style.css`, `js/app.js` |
| 012 | Ingreso con lámpara (diseño de referencia del equipo) y logo reemplazable | `index.php`, `css/style.css`, `js/app.js`, `img/` |
| 013 | Foto de perfil con validación de firma y permisos | `includes/usuarios.php`, `foto_perfil.php`, `perfil.php`, migración `002_v3_ia.sql` |
| 014 | Asistente Beimy: menú parametrizado, respuestas por rol, Gemini opcional, arnés y agente de OpenCode | `includes/chatbot_menu.php`, `includes/chatbot.php`, `chatbot_api.php`, `js/chatbot.js`, `tests/arnes_chatbot.php`, `.opencode/agents/probador-chatbot.md` |
| 015 | Panel gerencial con tiempos por etapa y cuello de botella | `includes/gerencia.php`, `admin/gerencia.php` |
| 016 | Visualización 3D del diseño sobre el producto (CSS 3D propio) | `includes/visualizacion.php`, `includes/form_pedido.php`, `pedido.php`, `archivo.php?ver=1`, `js/app.js`, `css/style.css` |

Resultado: 90/90 pruebas funcionales (en la raíz del servidor y dentro de una subcarpeta como XAMPP), 17/17 de especificación, 5/5 del arnés y verificador sin errores. El verificador ahora también revisa etiquetas `@spec` en CSS y JS. Falta: probar el arnés con una clave real de Gemini y revisar el contraste del tema claro en todas las pantallas.

## Pendiente (no implementado, documentado con honestidad)

- Envío real por correo: requiere una cuenta SMTP con credenciales; no se ha probado con un servidor real.
- WhatsApp: **retirado del alcance** por decisión del equipo (complejidad de configuración en Meta). Debe quedar explicado así en los documentos.
- Recuperación de contraseña por correo: depende del canal SMTP. Hoy el administrador restablece las contraseñas.
- Edición del pedido después de enviado: solo se agregan archivos; los cambios de especificación se registran como observaciones.
- El catálogo formal de requerimientos (RF/RNF/RN) no estaba en el ZIP recibido, así que no se pudo comprobar la trazabilidad requisito por requisito.
