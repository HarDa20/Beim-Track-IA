# BEIM Track IA · Gestión y seguimiento de pedidos publicitarios

Aplicación web monolítica cliente-servidor de tres capas (HTML5/CSS3/JavaScript · PHP 8 · MySQL con PDO) que centraliza los pedidos de una empresa de publicidad: los registra desde la web o desde Recepción (llamada, chat o atención presencial), los hace pasar por las áreas de **Recepción → Diseño → Producción → Calidad → Entrega** y deja trazabilidad completa de cada cambio.

> **Esta es la versión IA del proyecto** (desarrollo dirigido por especificaciones + agentes de OpenCode). Es independiente de la versión principal `imprimex`: usa su propia carpeta (`beimtrack_ia`), su propia base de datos (`beimtrack_ia`) y su propia cookie de sesión (`BEIMTRACK_IA`), así que las dos se pueden instalar a la vez en el mismo XAMPP.

Proyecto académico · UNINPAHU · Ingeniería de Software · 2026
Equipo: Jorge Junior Miranda Gómez, Haider Stive Pava López y Harold David Hernández Vásquez.

> **Metodología: Desarrollo Dirigido por Especificaciones (SDD).** Cada funcionalidad está gobernada por una especificación en [`specs/`](specs/README.md): constitución del proyecto, 16 especificaciones con 82 requisitos y 122 criterios de aceptación, un contrato ejecutable del flujo de estados y una [matriz de trazabilidad](specs/TRAZABILIDAD.md) generada automáticamente que une cada requisito con su código (`@spec`) y sus pruebas. El proyecto incluye agentes de **OpenCode** configurados para trabajar con este ciclo ([`AGENTS.md`](AGENTS.md) y [`docs/AGENTES_OPENCODE.md`](docs/AGENTES_OPENCODE.md)).

---

## 1. Instalación en XAMPP

1. Copia la carpeta `beimtrack_ia` en `C:\xampp\htdocs\beimtrack_ia`.
2. Inicia **Apache** y **MySQL** desde el panel de XAMPP.
3. Abre phpMyAdmin (`http://localhost/phpmyadmin`) e importa:
   - **Instalación nueva:** `database/schema.sql`.
   - **Si ya tenías la base `beimtrack_ia` de la versión anterior:** haz un respaldo y ejecuta `database/migraciones/002_v3_ia.sql` (agrega la foto de perfil).
   - **Si vienes de la v1 con datos:** ejecuta primero `001_v1_a_v2.sql` (lee su cabecera), luego `002_v3_ia.sql` y por último `database/verificacion.sql`.
4. (Opcional) Copia `.env.example` como `.env` si tu MySQL tiene usuario o contraseña distintos de `root` sin clave, o para activar la IA del asistente (sección 5b).
5. Abre `http://localhost/beimtrack_ia/`.

Requisitos: PHP 8.0 o superior (XAMPP 8.x) y MySQL 8 o MariaDB 10.4+ (incluido en XAMPP). No requiere Composer, frameworks ni librerías externas.

## 2. Cuentas de demostración

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | admin@beimtrack.com | Admin2026* |
| Empleado · Recepción | recepcion@beimtrack.com | Recepcion2026* |
| Empleado · Diseño | diseno@beimtrack.com | Diseno2026* |
| Empleado · Producción | produccion@beimtrack.com | Produccion2026* |
| Empleado · Calidad | calidad@beimtrack.com | Calidad2026* |
| Cliente | cliente@correo.com | Cliente2026* |

> Cambia estas contraseñas antes de usar el sistema con datos reales de la empresa. La base se instala **sin pedidos de ejemplo**: los indicadores solo muestran datos que se registren en el sistema.

## 3. Funcionalidades

**Clientes:** registro con autorización de tratamiento de datos (Ley 1581 de 2012), creación de pedidos con costo estimado en vivo, varios archivos de diseño por pedido, borradores, seguimiento con línea de etapas e historial, cancelación mientras el pedido está en "Recibido" y alertas.

**Empleados (por área):** bandeja del área, "Tomar pedido", avance de estado solo en las etapas de su área, detalle con especificaciones, archivos e historial, indicadores de pedidos por vencer y retrasados. Recepción además registra pedidos de llamadas, chats o atención presencial, incluso para clientes nuevos.

**Novedades de la versión IA (v3):**
- **Ingreso con lámpara:** la pantalla arranca a oscuras; al tirar del cordón la lámpara se enciende y aparece el formulario (spec 012).
- **Tema claro/oscuro y 5 colores de acento** desde el ingreso o la barra superior (spec 011).
- **Multisesión:** varias cuentas abiertas a la vez en el mismo navegador, una por pestaña (`?u=0`, `?u=1`…). Se agregan desde el menú de la cuenta → "Agregar otra cuenta" (spec 010).
- **Foto de perfil** en "Mi perfil" (spec 013) y **logo de la empresa** reemplazable en `img/logo.png` (spec 012).
- **Asistente Beimy:** chatbot animado con menú parametrizado en `includes/chatbot_menu.php`, respuestas con datos reales del usuario y preguntas libres a Google Gemini (spec 014).
- **Visualización 3D:** al crear el pedido, el diseño aparece montado en 3D según la categoría (local con letrero, pared con mural o pendón); se gira con el mouse o el dedo y tiene modo noche. También en el detalle del pedido (spec 016).
- **Panel gerencial:** ingresos, ticket promedio, entregas a tiempo, ciclo, cancelación, tiempo por etapa con cuello de botella, mejores clientes y carga del equipo; imprimible en PDF (spec 015).

**Administrador:** dashboard con indicadores y gráficos calculados desde la base de datos (totales, pendientes, en producción, finalizados, retrasados, cumplimiento, distribución por estado, por categoría, por mes y carga por área), gestión de pedidos con filtros, asignación de responsables por área, gestión de usuarios (crear, editar, cambiar rol o área, restablecer contraseña, activar o desactivar), categorías y precios, historial global, registro de notificaciones y **exportación del listado de pedidos a CSV para Excel** con los filtros aplicados (funcionalidad desarrollada con el ciclo SDD completo, ver `specs/009-exportar-pedidos`).

### Flujo de estados

| Estado | Área responsable | Transiciones permitidas |
|---|---|---|
| Borrador | Cliente | Enviar (pasa a Recibido) o eliminar |
| Recibido | Recepción | En diseño · Cancelado |
| En diseño | Diseño | En producción |
| En producción | Producción | Control de calidad |
| Control de calidad | Calidad | Listo para entrega · En producción (con motivo) |
| Listo para entrega | Recepción | Entregado |
| Entregado / Cancelado | — | Estados finales |

Las reglas se validan en el servidor (`includes/pedidos.php`). Cada cambio guarda en el historial el usuario, el estado anterior, el estado nuevo y la fecha, dentro de una transacción.

## 4. Seguridad implementada

- Contraseñas con `password_hash`/`password_verify` y actualización automática del hash.
- Consultas preparadas PDO en todo el sistema.
- Token CSRF en todos los formularios; cierre de sesión solo por POST.
- Sesión con `session_regenerate_id`, cookie HttpOnly y SameSite, modo estricto y cierre por inactividad (60 min).
- Bloqueo de 15 minutos tras 5 intentos fallidos de inicio de sesión.
- Control de acceso por rol, área y propiedad del pedido. Las cuentas desactivadas pierden el acceso de inmediato.
- Escape de toda la salida HTML con `htmlspecialchars` y cabeceras CSP, X-Frame-Options y nosniff.
- Archivos: lista de extensiones permitidas, verificación de la firma real del contenido, máximo 10 MB, nombre aleatorio y descarga solo mediante `archivo.php` con control de acceso. `uploads/` bloqueada con `.htaccess`.
- Credenciales en `.env` (no versionado) y errores registrados en el log sin mostrar detalles técnicos al usuario.

## 5. Avisos por correo electrónico

El módulo `includes/notificaciones/` avisa al cliente por correo cuando su pedido se registra, cambia de estado, se retrasa o se entrega. Viene **desactivado** hasta que configures una cuenta SMTP en `.env`:

```
MAIL_ENABLED=true
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USER=tucorreo@gmail.com
MAIL_PASS=contraseña_de_aplicación
MAIL_FROM=tucorreo@gmail.com
```

Con Gmail se necesita la verificación en dos pasos y una "contraseña de aplicación" (myaccount.google.com/apppasswords). Mientras el correo esté desactivado, cada aviso queda registrado como **"omitido"** en *Administración > Actividad > Notificaciones externas*. El sistema nunca marca como enviado un mensaje que no salió. **El envío real no se ha probado con una cuenta SMTP.**

> WhatsApp se retiró del alcance por la complejidad de su configuración (cuenta de Meta Business, plantillas aprobadas y tokens). El registro de avisos tiene una columna `canal` que permite agregarlo en el futuro.

## 5b. Asistente Beimy con inteligencia artificial (Google Gemini)

Sin configurar nada, Beimy funciona con su menú. Para activar las preguntas libres con IA:

1. Entra a [aistudio.google.com/apikey](https://aistudio.google.com/apikey) con una cuenta de Google y crea una clave (tiene capa gratuita).
2. Copia `.env.example` como `.env` y pega la clave en `GEMINI_API_KEY=`.
3. Recarga la página: el asistente dirá "Asistente con IA · Gemini".
4. Prueba con el arnés: `php tests/arnes_chatbot.php --real`.

Si en XAMPP aparece en el log *"SSL certificate problem"*, descarga `https://curl.se/ca/cacert.pem`, guárdalo por ejemplo en `C:\xampp\php\extras\ssl\cacert.pem` y pon esa ruta en `GEMINI_CA_BUNDLE=`.

Para cambiar las opciones del menú, edita solo `includes/chatbot_menu.php`. Para usar el muñeco hecho con Nano Banana, guárdalo como `img/mascota.png` (ver `img/LEEME.txt`).

## 6. Pruebas automáticas y verificación SDD

```bash
php tests/pruebas_spec.php                              # 17 pruebas de reglas y contratos (no necesitan servidor)
php tests/pruebas_funcionales.php http://localhost/beimtrack_ia   # 90 pruebas funcionales y de seguridad por HTTP
php tests/arnes_chatbot.php                             # 5 pruebas del asistente con la API de Gemini simulada
php tests/verificar_specs.php                           # cruza specs, código y resultados; genera specs/TRAZABILIDAD.md
```

- **`pruebas_spec.php`** compara el código con las especificaciones: el flujo de estados y la matriz de permisos (112 combinaciones de estado × usuario) contra el contrato `specs/003-flujo-produccion/contracts/flujo_estados.json`, los límites de validación de pedidos, las contraseñas, las firmas de archivos y la exportación.
- **`pruebas_funcionales.php`** hace peticiones HTTP reales: autenticación, permisos, flujo completo por áreas, archivos, CSRF, XSS, redirecciones, dashboard y exportación. Crea usuarios temporales `@qa.beimtrack.test` y los elimina al terminar.
- **`verificar_specs.php`** falla si un requisito no tiene criterios, código o pruebas, si un criterio cita una prueba inexistente o si alguna prueba citada falló.

Los resultados quedan en `tests/resultados_spec.md`, `tests/resultados_ultima_ejecucion.md` y `specs/TRAZABILIDAD.md`.

## 7. Estructura

```
beimtrack_ia/
├── AGENTS.md, opencode.json   Reglas y configuración para agentes de OpenCode
├── .opencode/            agents/ (6 agentes) y commands/ (/especificar, /planear, /tareas, /implementar, /verificar…)
├── specs/                SDD: constitucion.md, 001…009 (spec.md, plan.md, tasks.md), contrato del flujo, TRAZABILIDAD.md
├── index.php, login_process.php, register_process.php, logout.php
├── pedido.php            Detalle compartido por todos los roles (con permisos)
├── archivo.php           Descarga controlada de archivos
├── subir_archivo.php     Archivos adicionales para un pedido
├── alertas.php, perfil.php
├── cliente/              panel, crear_pedido, guardar_pedido, cancelar_pedido, estado_pedido (redirige)
├── empleado/             panel (bandeja), actualizar_estado, tomar_pedido, registrar_pedido
├── admin/                panel (dashboard), pedidos, exportar_pedidos, asignar_empleado, usuarios, categorias, actividad
├── includes/             bootstrap, auth, security, helpers, layout, nav_sidebar, pedidos,
│                         usuarios, archivos, alertas, exportacion, form_pedido, notificaciones/
├── config/               env.php, db.php
├── css/style.css         Sistema de diseño (tema oscuro, responsive)
├── js/app.js             Interacciones sin librerías
├── database/             schema.sql (v2), schema_v1_respaldo.sql, migraciones/, verificacion.sql
├── uploads/              Archivos de diseño (protegida)
└── tests/                pruebas_spec, pruebas_funcionales, verificar_specs, schema_sqlite
```
