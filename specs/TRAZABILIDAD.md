# Matriz de trazabilidad SDD · BEIM Track

> Archivo generado automáticamente por `php tests/verificar_specs.php`. **No editar a mano**: cambia las specs, el código o las pruebas y vuelve a generarlo.

- Generado: 2026-11-24 20:51:58
- Resultados de `tests/pruebas_funcionales.php`: 2026-11-24 20:51:56
- Resultados de `tests/pruebas_spec.php`: 2026-11-22 19:59:55
- Resultados de `tests/arnes_chatbot.php`: 2026-11-24 19:53:50
- Requisitos: **83** · Criterios de aceptación: **123** · Pruebas automáticas: **113** · Etiquetas `@spec` en el código: **198**
- Verificación: ❌ **3 error(es)**

Leyenda: ✅ prueba aprobada · ❌ prueba fallida · ⚪ sin ejecutar · 🟡 solo verificación manual

## Resumen por especificación

| Spec | Funcionalidad | Origen | Requisitos | Criterios (auto / manual) | Tareas | Estado |
|---|---|---|---|---|---|---|
| [001-autenticacion](001-autenticacion/spec.md) | Autenticación y control de acceso | Retroactiva | 8 | 15 (13 / 2) | 9/9 | ✅ |
| [002-pedidos-cliente](002-pedidos-cliente/spec.md) | Pedidos del cliente | Retroactiva | 6 | 15 (15 / 0) | 7/7 | ✅ |
| [003-flujo-produccion](003-flujo-produccion/spec.md) | Flujo de producción por áreas | Retroactiva | 8 | 13 (12 / 1) | 9/9 | ✅ |
| [004-registro-recepcion](004-registro-recepcion/spec.md) | Registro de pedidos por Recepción | Retroactiva | 3 | 3 (2 / 1) | 3/3 | ✅ |
| [005-administracion](005-administracion/spec.md) | Administración | Retroactiva | 6 | 11 (10 / 1) | 6/6 | ✅ |
| [006-alertas-notificaciones](006-alertas-notificaciones/spec.md) | Alertas internas y notificaciones por correo | Retroactiva | 3 | 6 (5 / 1) | 3/4 | ❌ |
| [007-archivos](007-archivos/spec.md) | Archivos de diseño | Retroactiva | 3 | 7 (6 / 1) | 3/3 | ✅ |
| [008-seguridad-transversal](008-seguridad-transversal/spec.md) | Seguridad transversal | Retroactiva | 7 | 7 (6 / 1) | 7/7 | ✅ |
| [009-exportar-pedidos](009-exportar-pedidos/spec.md) | Exportar pedidos a CSV | SDD completo | 6 | 6 (5 / 1) | 7/8 | ✅ |
| [010-multisesion](010-multisesion/spec.md) | Multisesión | SDD completo | 5 | 6 (6 / 0) | 5/5 | ✅ |
| [011-temas](011-temas/spec.md) | Tema claro/oscuro y color de acento | SDD completo | 4 | 4 (3 / 1) | 4/5 | ✅ |
| [012-identidad-visual](012-identidad-visual/spec.md) | Inicio de sesión con lámpara y logo de la empresa | SDD completo | 4 | 5 (4 / 1) | 6/6 | ✅ |
| [013-foto-perfil](013-foto-perfil/spec.md) | Foto de perfil | SDD completo | 4 | 5 (5 / 0) | 6/6 | ✅ |
| [014-chatbot](014-chatbot/spec.md) | Asistente virtual "Beimy" (chatbot con IA) | SDD completo | 7 | 10 (9 / 1) | 8/9 | ✅ |
| [015-panel-gerencial](015-panel-gerencial/spec.md) | Panel gerencial | SDD completo | 4 | 5 (4 / 1) | 4/4 | ✅ |
| [016-visualizacion-3d](016-visualizacion-3d/spec.md) | Visualización 3D del pedido | SDD completo | 5 | 5 (4 / 1) | 5/5 | ✅ |

## Detalle por requisito

### 001-autenticacion · Autenticación y control de acceso

| Requisito | Criterios | Pruebas | Código | Estado |
|---|---|---|---|---|
| **RF-AUT-01** El sistema debe permitir el registro de clientes con nombre (3–120), correo válido y únic… | CA-AUT-01, CA-AUT-02, CA-AUT-03, CA-AUT-04 | A10 ✅ A11 ✅ A12 ✅ S04 ✅ A13 ✅ | `includes/usuarios.php`<br>`register_process.php` | ✅ Cumple |
| **RF-AUT-02** El sistema debe autenticar con correo y contraseña y redirigir al panel del rol: cliente … | CA-AUT-05, CA-AUT-06 | A01 ✅ A02 ✅ | `includes/auth.php`<br>`login_process.php` | ✅ Cumple |
| **RF-AUT-03** El sistema debe bloquear durante 15 minutos el inicio de sesión de un correo tras 5 inten… | CA-AUT-07 | A06 ✅ | `includes/auth.php`<br>`login_process.php` | ✅ Cumple |
| **RF-AUT-04** El cierre de sesión solo debe ocurrir por POST con token CSRF, y la sesión debe cerrarse … | CA-AUT-08, CA-AUT-09 | A07 ✅ · 1 manual | `includes/auth.php`<br>`logout.php` | ✅ Cumple (+ verificación manual) |
| **RF-AUT-05** Cada página privada debe exigir sesión y el rol correspondiente; un visitante sin sesión … | CA-AUT-10, CA-AUT-11, CA-AUT-12 | A08 ✅ A09 ✅ D05 ✅ | `includes/auth.php` | ✅ Cumple |
| **RF-AUT-06** Al iniciar sesión se debe regenerar el identificador de sesión, y la cookie debe ser Http… | CA-AUT-13 | A03 ✅ A04 ✅ | `includes/auth.php`<br>`includes/security.php` | ✅ Cumple |
| **RF-AUT-07** En cada petición se debe releer al usuario desde la base de datos; si fue desactivado o c… | CA-AUT-14 | D02 ✅ | `admin/usuarios.php`<br>`includes/auth.php` | ✅ Cumple |
| **RNF-AUT-01** Las contraseñas se guardan con `password_hash` y el hash se actualiza automáticamente si … | CA-AUT-15 | — · 1 manual | `login_process.php`<br>`register_process.php` | 🟡 Solo manual |

### 002-pedidos-cliente · Pedidos del cliente

| Requisito | Criterios | Pruebas | Código | Estado |
|---|---|---|---|---|
| **RF-PED-01** El sistema debe validar el pedido en el servidor: categoría activa existente; nombre del … | CA-PED-01, CA-PED-02, CA-PED-03, CA-PED-04, CA-PED-05, CA-PED-06 | P01 ✅ P02 ✅ P03 ✅ P04 ✅ P05 ✅ S05 ✅ | `cliente/guardar_pedido.php`<br>`includes/pedidos.php` | ✅ Cumple |
| **RF-PED-02** El costo estimado debe ser `precio_unitario × cantidad` redondeado a 2 decimales, y el pr… | CA-PED-07, CA-PED-08 | P01 ✅ S06 ✅ D04 ✅ | `includes/pedidos.php` | ✅ Cumple |
| **RF-PED-03** El cliente debe poder guardar un pedido como **Borrador**, retomarlo, enviarlo (pasa a Re… | CA-PED-09, CA-PED-10 | P08 ✅ P09 ✅ | `cliente/guardar_pedido.php`<br>`includes/pedidos.php` | ✅ Cumple |
| **RF-PED-04** Un cliente solo puede ver y descargar los archivos de sus propios pedidos. | CA-PED-11 | P10 ✅ P11 ✅ | `archivo.php`<br>`includes/pedidos.php` | ✅ Cumple |
| **RF-PED-05** El cliente debe ver en su panel sus pedidos con estado y costo, y en el detalle la línea … | CA-PED-12, CA-PED-13 | P14 ✅ F13 ✅ | `cliente/panel.php`<br>`includes/pedidos.php`<br>`pedido.php` | ✅ Cumple |
| **RF-PED-06** El cliente puede cancelar su propio pedido únicamente mientras está en **Recibido**, escr… | CA-PED-14, CA-PED-15 | F15 ✅ S02 ✅ | `cliente/cancelar_pedido.php`<br>`includes/pedidos.php` | ✅ Cumple |

### 003-flujo-produccion · Flujo de producción por áreas

| Requisito | Criterios | Pruebas | Código | Estado |
|---|---|---|---|---|
| **RF-FLU-01** El sistema debe permitir únicamente las transiciones del contrato `flujo_estados.json`; c… | CA-FLU-01, CA-FLU-02, CA-FLU-03, CA-FLU-04 | S01 ✅ S03 ✅ S08 ✅ F03 ✅ F12 ✅ F09 ✅ | `empleado/actualizar_estado.php`<br>`includes/pedidos.php` | ✅ Cumple |
| **RF-FLU-02** Cada etapa activa pertenece a un área (Recepción, Diseño, Producción, Calidad). Un emplea… | CA-FLU-05, CA-FLU-06, CA-FLU-07 | S02 ✅ F02 ✅ F14 ✅ | `empleado/panel.php`<br>`includes/pedidos.php`<br>`pedido.php` | ✅ Cumple |
| **RF-FLU-03** Un empleado puede **tomar** un pedido de la bandeja de su área si no tiene responsable; s… | CA-FLU-08, CA-FLU-09 | F08 ✅ S02 ✅ F04 ✅ | `empleado/panel.php`<br>`empleado/tomar_pedido.php`<br>`includes/pedidos.php` | ✅ Cumple |
| **RF-FLU-04** Cuando el pedido pasa a una etapa de otra área, queda **sin responsable** en la bandeja d… | CA-FLU-09 | F04 ✅ | `includes/pedidos.php` | ✅ Cumple |
| **RF-FLU-05** Cancelar y devolver de Calidad a Producción exigen escribir un motivo (máx. 150 caractere… | CA-FLU-10 | F10 ✅ | `includes/pedidos.php` | ✅ Cumple |
| **RF-FLU-06** Cada cambio guarda en `historial_pedido` el usuario, el estado anterior, el estado nuevo,… | CA-FLU-11 | F04 ✅ F13 ✅ | `includes/pedidos.php` | ✅ Cumple |
| **RF-FLU-07** Al pasar a Entregado se guarda la fecha real de entrega (`fecha_entregado`) para medir el… | CA-FLU-12 | F11 ✅ | `includes/pedidos.php` | ✅ Cumple |
| **RF-FLU-08** Si dos personas cambian el mismo pedido a la vez, solo el primer cambio se aplica y el se… | CA-FLU-13 | — · 1 manual | `includes/pedidos.php` | 🟡 Solo manual |

### 004-registro-recepcion · Registro de pedidos por Recepción

| Requisito | Criterios | Pruebas | Código | Estado |
|---|---|---|---|---|
| **RF-REC-01** Recepción y el administrador deben poder registrar un pedido a nombre de un cliente exist… | CA-REC-01 | R01 ✅ | `empleado/registrar_pedido.php`<br>`includes/auth.php`<br>`includes/pedidos.php` | ✅ Cumple |
| **RF-REC-02** Las áreas de Diseño, Producción y Calidad no pueden registrar pedidos. | CA-REC-02 | R02 ✅ | `empleado/registrar_pedido.php`<br>`includes/auth.php` | ✅ Cumple |
| **RF-REC-03** El pedido registrado por el personal guarda quién lo registró (`registrado_por`) y el cli… | CA-REC-03 | — · 1 manual | `includes/pedidos.php` | 🟡 Solo manual |

### 005-administracion · Administración

| Requisito | Criterios | Pruebas | Código | Estado |
|---|---|---|---|---|
| **RF-ADM-01** El dashboard debe calcular desde la base de datos: total de pedidos (sin borradores), pen… | CA-ADM-01 | D07 ✅ | `admin/panel.php` | ✅ Cumple |
| **RF-ADM-02** El listado de pedidos debe filtrarse por texto (número, cliente o trabajo), estado, categ… | CA-ADM-02 | D06 ✅ | `admin/pedidos.php`<br>`includes/exportacion.php`<br>`includes/pedidos.php` | ✅ Cumple |
| **RF-ADM-03** El administrador puede asignar o quitar el responsable de un pedido en proceso; el respon… | CA-ADM-03, CA-ADM-04, CA-ADM-05, CA-ADM-06 | F05 ✅ F06 ✅ F07 ✅ F08 ✅ | `admin/asignar_empleado.php`<br>`includes/pedidos.php` | ✅ Cumple |
| **RF-ADM-04** El administrador gestiona usuarios: crear (un empleado exige área), editar, cambiar rol o… | CA-ADM-07, CA-ADM-08, CA-ADM-09 | D01 ✅ D02 ✅ D03 ✅ | `admin/usuarios.php` | ✅ Cumple |
| **RF-ADM-05** El administrador gestiona categorías (nombre, unidad, descripción, precio, activa). Cambi… | CA-ADM-10 | D04 ✅ | `admin/categorias.php` | ✅ Cumple |
| **RF-ADM-06** El administrador consulta el historial global de cambios y el registro de notificaciones … | CA-ADM-11 | — · 1 manual | `admin/actividad.php` | 🟡 Solo manual |

### 006-alertas-notificaciones · Alertas internas y notificaciones por correo

| Requisito | Criterios | Pruebas | Código | Estado |
|---|---|---|---|---|
| **RF-ALE-01** El sistema debe crear alertas internas: pedido nuevo → Recepción y administradores; cambi… | CA-ALE-01, CA-ALE-02, CA-ALE-03 | F01 ✅ F08 ✅ F11 ✅ | `includes/alertas.php` | ✅ Cumple |
| **RF-ALE-02** El sistema debe detectar pedidos activos que vencen en 2 días o menos ("por vencer", una … | CA-ALE-04 | D06 ✅ | `includes/alertas.php` | ✅ Cumple |
| **RF-ALE-03** El sistema debe enviar correo al cliente cuando su pedido se registra, cambia de estado, … | CA-ALE-05, CA-ALE-06 | N01 ❌ · 1 manual | `includes/notificaciones/correo.php`<br>`includes/notificaciones/notificador.php` | ❌ Falla |

### 007-archivos · Archivos de diseño

| Requisito | Criterios | Pruebas | Código | Estado |
|---|---|---|---|---|
| **RF-ARC-01** Solo se aceptan archivos `pdf`, `ai`, `jpg`, `jpeg`, `png` y `webp`, de máximo 10 MB cada… | CA-ARC-01, CA-ARC-02, CA-ARC-03 | P06 ✅ P07 ✅ S07 ✅ | `includes/archivos.php` | ✅ Cumple |
| **RF-ARC-02** Los archivos se guardan con nombre aleatorio en `uploads/`, que no es accesible desde el … | CA-ARC-04, CA-ARC-05, CA-ARC-06 | P12 ✅ P11 ✅ P13 ✅ | `.htaccess`<br>`archivo.php`<br>`includes/archivos.php` | ✅ Cumple |
| **RF-ARC-03** Quien puede ver un pedido activo puede agregarle archivos adicionales, y queda en el hist… | CA-ARC-07 | — · 1 manual | `subir_archivo.php` | 🟡 Solo manual |

### 008-seguridad-transversal · Seguridad transversal

| Requisito | Criterios | Pruebas | Código | Estado |
|---|---|---|---|---|
| **RNF-SEG-01** Todo formulario que modifica datos exige POST con token CSRF de 64 caracteres hexadecimal… | CA-SEG-01 | A05 ✅ | `includes/security.php` | ✅ Cumple |
| **RNF-SEG-02** Toda salida de datos en HTML se escapa con `htmlspecialchars` (función `e()`). | CA-SEG-02 | P15 ✅ | `includes/helpers.php` | ✅ Cumple |
| **RNF-SEG-03** Todas las respuestas incluyen `Content-Security-Policy`, `X-Frame-Options: DENY`, `X-Cont… | CA-SEG-03 | A14 ✅ | `includes/security.php` | ✅ Cumple |
| **RNF-SEG-04** Las redirecciones con destino enviado por el usuario solo pueden apuntar a rutas internas. | CA-SEG-04 | X02 ✅ | `includes/helpers.php` | ✅ Cumple |
| **RNF-SEG-05** Las carpetas `config/`, `includes/`, `database/`, `tests/`, `docs/`, `uploads/`, `specs/`… | CA-SEG-05 | X03 ✅ | `.htaccess` | ✅ Cumple |
| **RNF-SEG-06** Los errores técnicos se registran en el log y el usuario ve un mensaje comprensible, nunc… | CA-SEG-06 | X01 ✅ F06 ✅ | `includes/security.php` | ✅ Cumple |
| **RNF-SEG-07** Todas las consultas a la base de datos son preparadas con PDO y las credenciales se leen … | CA-SEG-07 | — · 1 manual | `config/db.php`<br>`config/env.php` | 🟡 Solo manual |

### 009-exportar-pedidos · Exportar pedidos a CSV

| Requisito | Criterios | Pruebas | Código | Estado |
|---|---|---|---|---|
| **RF-EXP-01** Desde *Administración > Pedidos*, un botón "Exportar CSV" debe descargar los pedidos **co… | CA-EXP-01, CA-EXP-02 | E01 ✅ E02 ✅ | `admin/exportar_pedidos.php`<br>`admin/pedidos.php`<br>`includes/exportacion.php` | ✅ Cumple |
| **RF-EXP-02** El archivo debe tener una fila de encabezados y, por pedido, las columnas: Pedido, Regist… | CA-EXP-01 | E01 ✅ | `includes/exportacion.php` | ✅ Cumple |
| **RF-EXP-03** Solo el administrador puede exportar; cualquier otro rol o un visitante sin sesión no rec… | CA-EXP-03 | E03 ✅ | `admin/exportar_pedidos.php` | ✅ Cumple |
| **RNF-EXP-01** Compatible con Excel en configuración regional de Colombia: UTF-8 con BOM, separador `;`,… | CA-EXP-05 | — · 1 manual | `admin/exportar_pedidos.php`<br>`includes/exportacion.php` | 🟡 Solo manual |
| **RNF-EXP-02** Protección contra **inyección de fórmulas** (CSV injection, OWASP): toda celda de texto q… | CA-EXP-04 | E04 ✅ S09 ✅ | `includes/exportacion.php` | ✅ Cumple |
| **RNF-EXP-03** Máximo 5.000 filas por exportación. | CA-EXP-06 | S10 ✅ | `includes/exportacion.php` | ✅ Cumple |

### 010-multisesion · Multisesión

| Requisito | Criterios | Pruebas | Código | Estado |
|---|---|---|---|---|
| **RF-MSE-01** Desde la barra superior se debe poder **agregar otra cuenta** sin cerrar la actual, hasta… | CA-MSE-01, CA-MSE-03 | M01 ✅ M03 ✅ | `includes/auth.php`<br>`index.php`<br>`login_process.php` | ✅ Cumple |
| **RF-MSE-02** Cada cuenta ocupa una ranura numerada (0 a 4). La ranura viaja en la dirección (`?u=N`) y… | CA-MSE-01, CA-MSE-02 | M01 ✅ M02 ✅ | `includes/auth.php`<br>`includes/helpers.php` | ✅ Cumple |
| **RF-MSE-03** Un selector en la barra superior muestra las cuentas abiertas (foto o iniciales, nombre y… | CA-MSE-06 | M06 ✅ | `includes/auth.php`<br>`includes/layout.php` | ✅ Cumple |
| **RF-MSE-04** "Cerrar sesión" cierra solo la cuenta de la pestaña actual; "Cerrar todas" cierra todas l… | CA-MSE-04 | M04 ✅ | `includes/auth.php`<br>`logout.php` | ✅ Cumple |
| **RNF-MSE-01** Cada cuenta conserva todos los controles de 001: tiempo de inactividad propio, desactivac… | CA-MSE-05 | M05 ✅ | `includes/auth.php` | ✅ Cumple |

### 011-temas · Tema claro/oscuro y color de acento

| Requisito | Criterios | Pruebas | Código | Estado |
|---|---|---|---|---|
| **RF-TEM-01** El usuario puede cambiar entre tema **oscuro** y **claro** desde el inicio de sesión y de… | CA-TEM-01 | T01 ✅ | `css/style.css`<br>`includes/helpers.php`<br>`includes/layout.php`<br>`js/app.js` | ✅ Cumple |
| **RF-TEM-02** El usuario puede elegir el **color de acento** entre cian, violeta, rosa, ámbar y verde. | CA-TEM-03 | T03 ✅ S11 ✅ | `css/style.css`<br>`includes/helpers.php`<br>`includes/layout.php`<br>`js/app.js` | ✅ Cumple |
| **RF-TEM-03** Por defecto el tema es oscuro y el acento cian. Cualquier valor que no esté en la lista s… | CA-TEM-02 | T02 ✅ S11 ✅ | `includes/helpers.php` | ✅ Cumple |
| **RNF-TEM-01** En ambos temas el texto principal mantiene contraste suficiente para leerse (WCAG AA). | CA-TEM-04 | — · 1 manual | `css/style.css` | 🟡 Solo manual |

### 012-identidad-visual · Inicio de sesión con lámpara y logo de la empresa

| Requisito | Criterios | Pruebas | Código | Estado |
|---|---|---|---|---|
| **RF-IDV-01** El inicio de sesión muestra una lámpara en una escena oscura con la indicación "Tira del … | CA-IDV-01, CA-IDV-03 | L01 ✅ · 1 manual | `css/style.css`<br>`index.php`<br>`js/app.js` | ✅ Cumple (+ verificación manual) |
| **RF-IDV-02** El formulario nunca queda inaccesible: sin JavaScript, con "reducir movimiento" activo o … | CA-IDV-02 | L02 ✅ | `css/style.css`<br>`index.php` | ✅ Cumple |
| **RF-IDV-03** El logo de la empresa aparece en el inicio de sesión, en el menú lateral y como ícono de … | CA-IDV-01, CA-IDV-04 | L01 ✅ L03 ✅ | `css/style.css`<br>`includes/layout.php`<br>`index.php` | ✅ Cumple |
| **RF-IDV-04** Si existe `img/logo_ancho.png` (mascota con el nombre "BEIM Track"), se muestra en el men… | CA-IDV-05 | L04 ✅ | `css/style.css`<br>`includes/layout.php` | ✅ Cumple |

### 013-foto-perfil · Foto de perfil

| Requisito | Criterios | Pruebas | Código | Estado |
|---|---|---|---|---|
| **RF-FOT-01** Cada usuario puede subir su foto desde "Mi perfil": formatos JPG, PNG o WEBP, máximo 2 MB… | CA-FOT-01, CA-FOT-02, CA-FOT-03 | U01 ✅ U02 ✅ U05 ✅ | `includes/usuarios.php`<br>`perfil.php` | ✅ Cumple |
| **RF-FOT-02** La foto se guarda con nombre aleatorio en `uploads/perfiles/` (carpeta sin acceso directo… | CA-FOT-04 | U03 ✅ | `foto_perfil.php`<br>`includes/usuarios.php` | ✅ Cumple |
| **RF-FOT-03** La foto se muestra en la barra superior, en "Mi perfil", en el selector de cuentas y en l… | CA-FOT-01 | U01 ✅ | `admin/usuarios.php`<br>`includes/helpers.php`<br>`perfil.php` | ✅ Cumple |
| **RF-FOT-04** El usuario puede quitar su foto; el archivo se borra del servidor. | CA-FOT-05 | U04 ✅ | `includes/usuarios.php`<br>`perfil.php` | ✅ Cumple |

### 014-chatbot · Asistente virtual "Beimy" (chatbot con IA)

| Requisito | Criterios | Pruebas | Código | Estado |
|---|---|---|---|---|
| **RF-BOT-01** En todas las pantallas internas aparece un asistente animado ("Beimy") en la esquina infe… | CA-BOT-01 | B01 ✅ S12 ✅ | `css/style.css`<br>`includes/chatbot.php`<br>`includes/layout.php`<br>`js/chatbot.js` | ✅ Cumple |
| **RF-BOT-02** El menú está **parametrizado en código** en `includes/chatbot_menu.php`: cada opción defi… | CA-BOT-01, CA-BOT-02 | B01 ✅ S12 ✅ H01 ✅ | `chatbot_api.php`<br>`includes/chatbot.php`<br>`includes/chatbot_menu.php` | ✅ Cumple |
| **RF-BOT-03** Las respuestas dinámicas usan datos reales y **solo del usuario que pregunta**: "Mis pedi… | CA-BOT-03, CA-BOT-04 | B02 ✅ H02 ✅ B06 ✅ | `chatbot_api.php`<br>`includes/chatbot.php` | ✅ Cumple |
| **RF-BOT-04** Si el usuario escribe una pregunta libre, el sistema la envía a la API de Google Gemini c… | CA-BOT-05, CA-BOT-06, CA-BOT-10 | H03 ✅ B05 ✅ H04 ✅ · 1 manual | `chatbot_api.php`<br>`includes/chatbot.php` | ✅ Cumple (+ verificación manual) |
| **RF-BOT-05** Límites: mensajes de hasta 500 caracteres, máximo 20 preguntas a la IA cada 10 minutos po… | CA-BOT-07 | S13 ✅ | `includes/chatbot.php` | ✅ Cumple |
| **RNF-BOT-01** A la IA nunca se envían correos, teléfonos, contraseñas ni datos de otros usuarios (const… | CA-BOT-08 | S14 ✅ H05 ✅ | `includes/chatbot.php` | ✅ Cumple |
| **RNF-BOT-02** El servicio `chatbot_api.php` exige sesión y POST con CSRF, responde JSON, y el navegador… | CA-BOT-09 | B03 ✅ B04 ✅ | `chatbot_api.php`<br>`js/chatbot.js` | ✅ Cumple |

### 015-panel-gerencial · Panel gerencial

| Requisito | Criterios | Pruebas | Código | Estado |
|---|---|---|---|---|
| **RF-GER-01** Una página solo para el administrador muestra, para el periodo elegido (últimos 30, 90 o … | CA-GER-01, CA-GER-02, CA-GER-03 | G01 ✅ G02 ✅ S15 ✅ | `admin/gerencia.php`<br>`includes/gerencia.php` | ✅ Cumple |
| **RF-GER-02** Calcula, desde el historial, el **tiempo promedio que pasa un pedido en cada etapa** y se… | CA-GER-03 | S15 ✅ | `admin/gerencia.php`<br>`includes/gerencia.php` | ✅ Cumple |
| **RF-GER-03** Muestra los 5 clientes con mayor valor, las ventas por categoría, los pedidos por canal d… | CA-GER-04 | S16 ✅ | `admin/gerencia.php`<br>`includes/gerencia.php` | ✅ Cumple |
| **RF-GER-04** La página se puede imprimir o guardar en PDF desde el navegador con un diseño limpio. | CA-GER-05 | — · 1 manual | `admin/gerencia.php`<br>`css/style.css` | 🟡 Solo manual |

### 016-visualizacion-3d · Visualización 3D del pedido

| Requisito | Criterios | Pruebas | Código | Estado |
|---|---|---|---|---|
| **RF-V3D-01** El formulario de pedido tiene la sección "Visualización 3D". El modelo cambia según la ca… | CA-V3D-01, CA-V3D-02 | V01 ✅ S17 ✅ | `css/style.css`<br>`includes/form_pedido.php`<br>`includes/visualizacion.php` | ✅ Cumple |
| **RF-V3D-02** Al elegir un archivo JPG, PNG o WEBP, el diseño aparece sobre el modelo al instante, sin … | CA-V3D-03 | — · 1 manual | `includes/form_pedido.php`<br>`js/app.js` | 🟡 Solo manual |
| **RF-V3D-03** El modelo se gira arrastrando con el mouse o el dedo, con las flechas del teclado y con b… | CA-V3D-03 | — · 1 manual | `includes/visualizacion.php`<br>`js/app.js` | 🟡 Solo manual |
| **RF-V3D-04** El detalle de un pedido muestra la visualización 3D con la primera imagen del pedido, ent… | CA-V3D-04 | V02 ✅ V03 ✅ | `archivo.php`<br>`pedido.php` | ✅ Cumple |
| **RNF-V3D-01** Sin librerías: modelos hechos con CSS 3D y JavaScript propios (Art. V). Con "reducir movi… | CA-V3D-05 | V04 ✅ | `css/style.css` | ✅ Cumple |

## Verificaciones manuales pendientes de evidencia

| Criterio | Cómo se verifica |
|---|---|
| CA-AUT-09 (001-autenticacion) | cambiar `MINUTOS_INACTIVIDAD` a 1 en `includes/auth.php`, esperar un minuto y recargar el panel. |
| CA-AUT-15 (001-autenticacion) | en phpMyAdmin, la columna `password_hash` empieza por `$2y$`. |
| CA-FLU-13 (003-flujo-produccion) | abrir el pedido en dos navegadores con dos cuentas del área y pulsar el botón en ambos. |
| CA-REC-03 (004-registro-recepcion) | registrar desde Recepción para el cliente demo y entrar como cliente. |
| CA-ADM-11 (005-administracion) | Administración > Actividad, pestañas Historial y Notificaciones externas. |
| CA-ALE-06 (006-alertas-notificaciones) | pendiente de cuenta SMTP (ver README, sección 5). |
| CA-ARC-07 (007-archivos) | subir un PDF desde el detalle del pedido. |
| CA-SEG-07 (008-seguridad-transversal) | revisión con el agente `auditor-seguridad` de OpenCode (`/auditar`). |
| CA-EXP-05 (009-exportar-pedidos) | abrir el CSV en Excel con configuración regional de Colombia. |
| CA-TEM-04 (011-temas) | revisar las capturas de `docs/capturas/` en tema claro y oscuro. |
| CA-IDV-03 (012-identidad-visual) | probar con mouse, con el dedo en el celular y con la tecla Tab + Enter. |
| CA-BOT-10 (014-chatbot) | `php tests/arnes_chatbot.php --real` o el comando `/probar-chatbot` de OpenCode. |
| CA-GER-05 (015-panel-gerencial) | imprimir a PDF desde Chrome. |
| CA-V3D-03 (016-visualizacion-3d) | crear un pedido, elegir una imagen y girar el modelo con el mouse, el dedo y las flechas. |

## Hallazgos del verificador

- ❌ includes/notificaciones/correo.php:9 la etiqueta @spec RF-ALE-04 no corresponde a ningún requisito
- ❌ includes/notificaciones/plantilla_correo.php:34 la etiqueta @spec RF-ALE-04 no corresponde a ningún requisito
- ❌ CA-ALE-05: la prueba N01 falló en la última ejecución
