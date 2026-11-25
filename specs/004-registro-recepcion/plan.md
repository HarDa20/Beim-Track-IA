# Plan técnico · 004 · Registro de pedidos por Recepción

**Spec:** [spec.md](spec.md) · **Revisión contra la constitución:** ☑ Art. II ☑ Art. VII (autorización de datos del cliente nuevo)

## 1. Enfoque
Reutilizar el mismo servicio `guardarPedido()` del cliente (Art. II.1) pasando como actor al empleado y el canal elegido. El formulario de pedido es el mismo componente (`includes/form_pedido.php`) con una sección extra para elegir o crear el cliente.

## 2. Archivos
| Archivo | Responsabilidad | Requisitos |
|---|---|---|
| `empleado/registrar_pedido.php` | Formulario y POST; crea el cliente nuevo y llama a `guardarPedido` | RF-REC-01 |
| `includes/auth.php` · `puedeRegistrarPedidos` | Solo Recepción y administración | RF-REC-02 |
| `includes/pedidos.php` · `guardarPedido` | Guarda `canal_recepcion`, `registrado_por` y alerta al cliente | RF-REC-01, RF-REC-03 |

## 3. Datos
`pedidos.canal_recepcion` (`Web`, `Llamada`, `Chat`, `Presencial`), `pedidos.registrado_por`; `usuarios.acepta_datos_en` del cliente nuevo.

## 4. Decisiones
- El cliente creado por Recepción recibe una contraseña aleatoria que el administrador puede restablecer; no se envía por correo mientras no haya SMTP.
