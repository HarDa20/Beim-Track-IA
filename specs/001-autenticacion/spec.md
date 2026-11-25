# Especificación · 001 · Autenticación y control de acceso

**Código:** AUT · **Estado:** Implementada · **Origen:** Retroactiva (v2) · **Requisitos del documento original:** RF-01 a RF-05, RNF-01

## 1. Problema y objetivo
Hoy cualquier persona de la empresa puede "mover" un pedido de palabra y nadie sabe quién lo hizo. El sistema necesita saber **quién es cada usuario y qué puede hacer** para que el seguimiento sea confiable y cada cliente vea solo lo suyo.

## 2. Historias de usuario
- **HU-AUT-01** · Como cliente nuevo, quiero registrarme con mi correo para crear y seguir mis pedidos.
- **HU-AUT-02** · Como usuario, quiero iniciar sesión y llegar directamente a mi panel.
- **HU-AUT-03** · Como empresa, quiero que cada rol vea solo las opciones que le corresponden.
- **HU-AUT-04** · Como administrador, quiero que una cuenta desactivada pierda el acceso de inmediato.

## 3. Requisitos funcionales
- **RF-AUT-01** · El sistema debe permitir el registro de clientes con nombre (3–120), correo válido y único, celular opcional, contraseña de mínimo 8 caracteres con letras y números (máx. 72) y autorización expresa del tratamiento de datos (Ley 1581). Toda cuenta registrada desde la web es de rol **cliente**, aunque se envíe otro rol.
- **RF-AUT-02** · El sistema debe autenticar con correo y contraseña y redirigir al panel del rol: cliente → `/cliente/panel.php`, empleado → `/empleado/panel.php`, administrador → `/admin/panel.php`.
- **RF-AUT-03** · El sistema debe bloquear durante 15 minutos el inicio de sesión de un correo tras 5 intentos fallidos.
- **RF-AUT-04** · El cierre de sesión solo debe ocurrir por POST con token CSRF, y la sesión debe cerrarse sola tras 60 minutos de inactividad.
- **RF-AUT-05** · Cada página privada debe exigir sesión y el rol correspondiente; un visitante sin sesión va al login y un usuario con otro rol va a su propio panel.
- **RF-AUT-06** · Al iniciar sesión se debe regenerar el identificador de sesión, y la cookie debe ser HttpOnly y SameSite=Lax.
- **RF-AUT-07** · En cada petición se debe releer al usuario desde la base de datos; si fue desactivado o cambió de rol, el cambio se aplica de inmediato.

## 4. Requisitos no funcionales
- **RNF-AUT-01** · Las contraseñas se guardan con `password_hash` y el hash se actualiza automáticamente si el algoritmo cambia.

## 5. Criterios de aceptación
- **CA-AUT-01** (RF-AUT-01) Dado un formulario de registro con correo inválido, cuando se envía, entonces se rechaza con el mensaje "correo electrónico válido". ⟶ Pruebas: A10
- **CA-AUT-02** (RF-AUT-01) Dado un registro sin marcar la autorización de datos, cuando se envía, entonces se rechaza. ⟶ Pruebas: A11
- **CA-AUT-03** (RF-AUT-01) Dada una contraseña débil, cuando se registra, entonces se rechaza indicando el mínimo de 8 caracteres. ⟶ Pruebas: A12, S04
- **CA-AUT-04** (RF-AUT-01) Dado un registro que envía `rol=administrador`, cuando se crea la cuenta, entonces queda como cliente. ⟶ Pruebas: A13
- **CA-AUT-05** (RF-AUT-02) Dadas credenciales válidas de un cliente, cuando inicia sesión, entonces llega a `/cliente/panel.php`. ⟶ Pruebas: A01
- **CA-AUT-06** (RF-AUT-02) Dada una contraseña incorrecta, cuando inicia sesión, entonces vuelve al login con "Correo o contraseña incorrectos". ⟶ Pruebas: A02
- **CA-AUT-07** (RF-AUT-03) Dados 5 intentos fallidos, cuando se intenta con la clave correcta, entonces la cuenta sigue bloqueada. ⟶ Pruebas: A06
- **CA-AUT-08** (RF-AUT-04) Dada una sesión abierta, cuando se pide el cierre por GET no se cierra, y cuando se pide por POST con CSRF sí. ⟶ Pruebas: A07
- **CA-AUT-09** (RF-AUT-04) Dada una sesión sin actividad por más de 60 minutos, cuando el usuario vuelve, entonces debe iniciar sesión de nuevo. ⟶ Verificación manual: cambiar `MINUTOS_INACTIVIDAD` a 1 en `includes/auth.php`, esperar un minuto y recargar el panel.
- **CA-AUT-10** (RF-AUT-05) Dado un cliente, cuando abre el panel administrativo, entonces es enviado a su propio panel. ⟶ Pruebas: A08
- **CA-AUT-11** (RF-AUT-05) Dado un visitante sin sesión, cuando abre una página privada, entonces va al login. ⟶ Pruebas: A09
- **CA-AUT-12** (RF-AUT-05) Dado un cliente, cuando envía por POST la creación de un usuario, entonces no se crea nada. ⟶ Pruebas: D05
- **CA-AUT-13** (RF-AUT-06) Dado un inicio de sesión, entonces el ID de sesión cambia y la cookie es HttpOnly y SameSite=Lax. ⟶ Pruebas: A03, A04
- **CA-AUT-14** (RF-AUT-07) Dado un empleado con sesión abierta, cuando el administrador lo desactiva, entonces su siguiente petición lo saca del sistema. ⟶ Pruebas: D02
- **CA-AUT-15** (RNF-AUT-01) Dado un usuario registrado, cuando se consulta la base de datos, entonces la contraseña aparece solo como hash. ⟶ Verificación manual: en phpMyAdmin, la columna `password_hash` empieza por `$2y$`.

## 6. Fuera de alcance
- Recuperación de contraseña por correo (depende de tener SMTP configurado; hoy la restablece el administrador).
- Autenticación de doble factor.
- Inicio de sesión con Google u otras cuentas externas.
