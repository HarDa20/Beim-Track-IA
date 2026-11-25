# Resultado de pruebas automáticas · BEIM Track

- Fecha de ejecución: 2026-11-24 20:51:56
- Servidor probado: http://localhost/beimtrack_ia
- Motor de base de datos: mysql
- Resultado: **90 de 91 aprobadas**

| ID | Prueba | Resultado esperado | Resultado obtenido | Estado |
|---|---|---|---|---|
| A01 | Inicio de sesión con credenciales válidas (cliente) | Redirige a /cliente/panel.php | Redirección a /cliente/panel.php | ✅ Aprobada |
| A02 | Inicio de sesión con contraseña incorrecta | Vuelve al login con mensaje de error | Mensaje de error mostrado | ✅ Aprobada |
| A03 | El ID de sesión cambia al iniciar sesión (fijación) | ID distinto antes y después | ID regenerado | ✅ Aprobada |
| A04 | Cookie de sesión con HttpOnly y SameSite | HttpOnly; SameSite=Lax | Cookie segura | ✅ Aprobada |
| A05 | Login enviado sin token CSRF | Rechazado, sin sesión | Panel protegido -> /index.php | ✅ Aprobada |
| A06 | Bloqueo tras 5 intentos fallidos | El 6.º intento con clave correcta es bloqueado | Cuenta bloqueada temporalmente | ✅ Aprobada |
| A07 | Cierre de sesión por GET no cierra; por POST con CSRF sí | GET ignorado, POST cierra sesión | GET no cerró / POST cerró sesión | ✅ Aprobada |
| A08 | Cliente intenta abrir el panel administrativo | Redirigido a su propio panel | Redirección a /cliente/panel.php | ✅ Aprobada |
| A09 | Visitante sin sesión abre una página privada | Redirigido al login | Redirección a /index.php | ✅ Aprobada |
| A10 | Registro con correo inválido | Rechazado con mensaje | Rechazado: "correo electrónico válido" | ✅ Aprobada |
| A11 | Registro sin autorizar tratamiento de datos | Rechazado con mensaje | Rechazado: "tratamiento de datos" | ✅ Aprobada |
| A12 | Registro con contraseña débil | Rechazado con mensaje | Rechazado: "mínimo 8 caracteres" | ✅ Aprobada |
| A13 | Registro enviando rol=administrador | La cuenta se crea como cliente | Rol asignado: 'cliente' | ✅ Aprobada |
| A14 | Cabeceras de seguridad HTTP | CSP, X-Frame-Options y nosniff | Cabeceras presentes | ✅ Aprobada |
| P01 | Crear pedido válido con archivo PNG real | Estado Recibido, costo = precio × cantidad, historial y archivo | Pedido #139 Recibido, costo 375000.00 (esperado 375000), historial 1, archivos 1 | ✅ Aprobada |
| P02 | Pedido con fecha de entrega pasada | Rechazado con mensaje, no se crea el pedido | Mensaje: "anterior a hoy", sin registro | ✅ Aprobada |
| P03 | Pedido con fecha inválida ("hola") | Rechazado con mensaje, no se crea el pedido | Mensaje: "fecha de entrega válida", sin registro | ✅ Aprobada |
| P04 | Pedido con cantidad 0 | Rechazado con mensaje, no se crea el pedido | Mensaje: "cantidad debe ser mayor", sin registro | ✅ Aprobada |
| P05 | Pedido con categoría inexistente | Rechazado con mensaje, no se crea el pedido | Mensaje: "categoría válida", sin registro | ✅ Aprobada |
| P06 | Pedido con archivo .php | Rechazado con mensaje, no se crea el pedido | Mensaje: "no es un formato permitido", sin registro | ✅ Aprobada |
| P07 | Archivo con extensión .png pero contenido de texto | Rechazado con mensaje, no se crea el pedido | Mensaje: "no corresponde", sin registro | ✅ Aprobada |
| P08 | Guardar borrador, retomarlo y enviarlo | Borrador -> Recibido | Estado inicial: Borrador, después de enviar: Recibido | ✅ Aprobada |
| P09 | Eliminar un borrador propio | El borrador desaparece | Eliminado | ✅ Aprobada |
| P10 | Otro cliente intenta ver el pedido | Acceso denegado | HTTP 302 -> /cliente/panel.php | ✅ Aprobada |
| P11 | Otro cliente intenta descargar el archivo del pedido | Acceso denegado | HTTP 302 | ✅ Aprobada |
| P12 | El dueño descarga su archivo | HTTP 200 y contenido idéntico | HTTP 200, contenido correcto | ✅ Aprobada |
| P13 | Acceso directo a /uploads/ sin pasar por la aplicación | Bloqueado (403) | HTTP 403 | ✅ Aprobada |
| P14 | El cliente ve el costo estimado en su panel | Costo visible | Muestra $ 375.000 | ✅ Aprobada |
| P15 | Texto con HTML/JavaScript en el nombre del trabajo (XSS) | Se muestra escapado | Escapado con htmlspecialchars | ✅ Aprobada |
| F01 | Recepción recibe alerta del pedido nuevo | Alerta "nuevo_pedido" | 1 alerta(s) | ✅ Aprobada |
| F02 | Diseño intenta avanzar un pedido que está en Recepción | Rechazado | Estado: Recibido | ✅ Aprobada |
| F03 | Recepción salta de Recibido a Entregado manipulando el formulario | Rechazado con mensaje | Estado: Recibido | ✅ Aprobada |
| F04 | Recepción toma el pedido y lo envía a Diseño | En diseño, sin responsable, historial con usuario y estados | Tomado: sí, estado En diseño, historial Recibido -> En diseño por usuario 92 | ✅ Aprobada |
| F05 | Admin asigna el pedido a un cliente | Rechazado | Responsable: NULL | ✅ Aprobada |
| F06 | Admin asigna un empleado inexistente | Mensaje de error, sin error 500 | HTTP 200 | ✅ Aprobada |
| F07 | Admin asigna un empleado de otra área (Producción) a un pedido en Diseño | Rechazado | Rechazado por área | ✅ Aprobada |
| F08 | Admin asigna a Diseño 1; Diseño 2 intenta avanzarlo | Asignado con alerta; Diseño 2 rechazado | Asignado: sí, alerta 1, estado En diseño | ✅ Aprobada |
| F09 | Recorrido Diseño -> Producción -> Calidad | Control de calidad | Estado: Control de calidad | ✅ Aprobada |
| F10 | Calidad devuelve a producción sin motivo / con motivo | Sin motivo rechazado; con motivo En producción | Sin motivo: Control de calidad; con motivo: En producción | ✅ Aprobada |
| F11 | Calidad aprueba y Recepción registra la entrega | Entregado con fecha real y alerta al cliente | Estado Entregado, entregado 2026-11-24 20:51:31, alerta cliente 1 | ✅ Aprobada |
| F12 | Retroceder un pedido entregado (incluso como admin) | Rechazado | Estado: Entregado | ✅ Aprobada |
| F13 | Historial completo y ordenado del pedido | ≥ 8 cambios de estado registrados | 8 cambios de estado | ✅ Aprobada |
| F14 | Empleado de Calidad abre un pedido nuevo en Recepción | Acceso denegado | HTTP 302 | ✅ Aprobada |
| F15 | Cliente cancela su pedido en Recibido (con motivo) | Cancelado | Estado: Cancelado | ✅ Aprobada |
| R01 | Recepción registra un pedido por llamada para un cliente nuevo | Cliente creado y pedido con canal Llamada | Pedido #145 canal Llamada para telefonico@qa.beimtrack.test | ✅ Aprobada |
| R02 | Producción intenta registrar pedidos | Acceso denegado | Redirección a /empleado/panel.php | ✅ Aprobada |
| D01 | Crear empleado sin área / con área | Sin área rechazado; con área creado | Sin área: 0 creados; con área: 'Producción' | ✅ Aprobada |
| D02 | Desactivar un empleado con pedidos asignados | Pedidos liberados y el empleado no puede entrar | Pedido liberado, sesión expulsada | ✅ Aprobada |
| D03 | El administrador intenta desactivarse a sí mismo | Rechazado | Sigue activo | ✅ Aprobada |
| D04 | Cambiar el precio de una categoría no altera pedidos anteriores | Costo de pedidos existentes intacto | Precio cambió a 31000; costo del pedido: 375000.00 -> 375000.00 | ✅ Aprobada |
| D05 | Un cliente intenta crear usuarios por POST | Acceso denegado, sin usuario creado | 0 usuarios creados | ✅ Aprobada |
| D06 | Pedido vencido aparece como retrasado y genera alerta | Alerta "retrasado" y fila marcada | Alerta 1, listado de retrasados lo incluye | ✅ Aprobada |
| D07 | Indicadores del dashboard coinciden con la base de datos | Total y finalizados iguales a COUNT(*) | Dashboard: total 64 (BD 64), finalizados 31 (BD 31) | ✅ Aprobada |
| E01 | Administrador exporta los pedidos a CSV | HTTP 200, text/csv, BOM, encabezado y un registro por pedido | HTTP 200, CSV con BOM, encabezado correcto, 64 filas (BD 64) | ✅ Aprobada |
| E02 | Exportar con el filtro estado = Entregado | Solo los pedidos entregados | 31 filas (BD 31), todas Entregado | ✅ Aprobada |
| E03 | Un cliente intenta exportar pedidos | No recibe el archivo | HTTP 302 -> /cliente/panel.php | ✅ Aprobada |
| E04 | Trabajo que empieza por "=" (inyección de fórmulas en Excel) | La celda sale con apóstrofo | Neutralizada con apóstrofo | ✅ Aprobada |
| M01 | Cliente en la ranura 0 agrega la cuenta del administrador | Ambos paneles abiertos en el mismo navegador | Login -> /admin/panel.php?u=1; cliente HTTP 200, admin (u=1) HTTP 200 | ✅ Aprobada |
| M02 | Los enlaces y formularios conservan la ranura | Contienen u=1 | Menú, cierre de sesión y asistente con u=1 | ✅ Aprobada |
| M03 | Agregar una cuenta que ya está abierta | Reutiliza su ranura | Login -> /admin/panel.php?u=1; ranura 2 -> /index.php?u=2 | ✅ Aprobada |
| M06 | Selector de cuentas | Muestra las dos cuentas y "Agregar otra cuenta" | Cliente (esta pestaña) y administrador listados | ✅ Aprobada |
| M04 | Cerrar una cuenta y luego cerrar todas | Solo se cierra la ranura indicada; "todas" cierra ambas | Tras cerrar u=1: cliente HTTP 200, recepción -> /index.php?u=1; tras 'todas': /index.php y /index.php?u=1 | ✅ Aprobada |
| M05 | Abrir una ranura vacía | Va al inicio de sesión | Redirección a /index.php?u=3 | ✅ Aprobada |
| T01 | Cookie beim_tema=claro | La página se genera con data-tema="claro" | Tema claro aplicado desde el servidor | ✅ Aprobada |
| T02 | Cookie de tema con un valor malicioso | Se ignora (tema oscuro) y no se refleja | Oscuro por defecto, valor descartado | ✅ Aprobada |
| T03 | Cookie beim_acento=rosa | data-acento="rosa" | Acento rosa aplicado | ✅ Aprobada |
| L01 | Página de ingreso con la lámpara | Escena apagada, cordón, logo, formulario y respaldo sin JavaScript | Todos los elementos presentes | ✅ Aprobada |
| L02 | Contraseña incorrecta y volver al ingreso | La lámpara ya está encendida y se ve el error | Encendida con el mensaje de error | ✅ Aprobada |
| L03 | Logo real en img/logo.png | El sistema usa esa imagen | Logo PNG mostrado (logo real del proyecto) | ✅ Aprobada |
| L04 | Logo horizontal en img/logo_ancho.png | Se ve en el ingreso y en el menú en lugar del nombre escrito | Logo horizontal visible en ingreso y menú (logo de prueba temporal) | ✅ Aprobada |
| U01 | Cliente sube un PNG real como foto de perfil | Se guarda y aparece en la barra superior | Guardada como u97_c05e5002f199cf167109d381.png y visible | ✅ Aprobada |
| U02 | Foto con extensión .php o PNG falso | Rechazadas | Ambas rechazadas; la foto anterior se conserva | ✅ Aprobada |
| U05 | Foto de más de 2 MB | Rechazada por tamaño | Mensaje: "supera el tamaño máximo de 2 MB" | ✅ Aprobada |
| U03 | Permisos de la foto de un cliente | Otro cliente no la ve; el dueño y el administrador sí | Otro cliente HTTP 404, dueño HTTP 200, administrador HTTP 200 | ✅ Aprobada |
| U04 | Quitar la foto de perfil | Se borra el archivo y vuelven las iniciales | Foto eliminada | ✅ Aprobada |
| B01 | Cliente abre el asistente Beimy | Saludo con su nombre y solo opciones de cliente | HTTP 200 · opciones: mis_pedidos, crear_pedido, precios, estados, pregunta_libre, contacto | ✅ Aprobada |
| B02 | Opción "Mis pedidos" | Solo pedidos propios | Muestra BT-0148 y ningún pedido ajeno | ✅ Aprobada |
| B06 | Cliente pide una opción de administrador manipulando la petición | Rechazada (403) | HTTP 403 · opcion_no_permitida | ✅ Aprobada |
| B05 | Pregunta libre | Sin clave de IA responde con el menú (con clave responde Gemini) | IA configurada: respondió Gemini | ✅ Aprobada |
| B03 | Visitante sin sesión llama al servicio del asistente | 401 en JSON | HTTP 401 | ✅ Aprobada |
| B04 | Petición al asistente sin token CSRF | 403 en JSON | HTTP 403 | ✅ Aprobada |
| G01 | Administrador abre el panel gerencial (último año) | Ingresos = suma de los entregados en la BD | Panel 9450000.00 · BD 9450000 | ✅ Aprobada |
| G02 | Cliente y empleado abren el panel gerencial | Acceso negado | Cliente -> /cliente/panel.php, Producción -> /empleado/panel.php | ✅ Aprobada |
| V01 | Formulario de pedido con visualización 3D | Sección presente y un modelo por categoría | Fachadas→fachada, Interiores→pared, Gran formato→pendón | ✅ Aprobada |
| V04 | Recursos que carga el formulario con 3D | Ningún script ni CSS externo | 4 recursos, todos propios | ✅ Aprobada |
| V02 | Detalle de un pedido con imagen | Visualización 3D con la imagen servida en línea | Detalle con 3D, imagen HTTP 200 | ✅ Aprobada |
| V03 | Otro cliente pide la imagen en línea | No la obtiene | HTTP 302 | ✅ Aprobada |
| N01 | Notificaciones externas sin credenciales configuradas | Se registran como "omitido", nunca como "enviado" | 0 omitidos, 18 enviados | ❌ Fallida |
| X01 | Abrir un pedido inexistente | Mensaje de error, sin error 500 | HTTP 302 -> /admin/panel.php | ✅ Aprobada |
| X02 | Redirección abierta con volver=//sitio-externo | La redirección queda dentro del sistema | Destino: /pedido.php?id=139 | ✅ Aprobada |
| X03 | Archivos internos (.env, schema.sql, includes/, specs/, opencode.json) desde el navegador | Bloqueados (403) | HTTP 403, 403, 403, 403, 403 | ✅ Aprobada |
