# Plan técnico · 002 · Pedidos del cliente

**Spec:** [spec.md](spec.md) · **Revisión contra la constitución:** ☑ Art. II ☑ Art. III ☑ Art. IV ☑ Art. VI

## 1. Enfoque
Un único servicio (`includes/pedidos.php`) valida y guarda; las páginas del cliente solo reciben el formulario, llaman al servicio y redirigen. El costo estimado se muestra en vivo con JavaScript, pero el valor que se guarda **siempre se recalcula en el servidor** (Art. II.2).

## 2. Archivos
| Archivo | Responsabilidad | Requisitos |
|---|---|---|
| `includes/pedidos.php` · `validarDatosPedido` | Validación y cálculo del costo | RF-PED-01, RF-PED-02 |
| `includes/pedidos.php` · `guardarPedido` | Crear o enviar en una transacción con historial, archivos y alertas | RF-PED-01, RF-PED-03 |
| `includes/pedidos.php` · `eliminarBorrador` | Borrar borrador propio y sus archivos | RF-PED-03 |
| `includes/pedidos.php` · `puedeVerPedido` | Propiedad del pedido | RF-PED-04 |
| `includes/form_pedido.php`, `cliente/crear_pedido.php`, `cliente/guardar_pedido.php` | Formulario compartido y recepción del POST | RF-PED-01, RF-PED-03 |
| `cliente/panel.php`, `pedido.php` | Lista, línea de etapas e historial | RF-PED-05 |
| `cliente/cancelar_pedido.php` + `accionesDisponibles` | Cancelación con motivo | RF-PED-06 |
| `js/app.js` | Costo estimado en vivo (solo visual) | RF-PED-02 |

## 3. Datos
`pedidos` (categoria_id, categoria, precio_unitario, costo_estimado, estado, fecha_entrega…), `historial_pedido`, `archivos_pedido`, `categorias_precios`.

## 4. Decisiones
- **Copiar `categoria` y `precio_unitario` al pedido** (Art. IV.2) en vez de leerlos siempre por la llave foránea.
- **Borrador como estado real** del pedido y no como tabla aparte: el mismo formulario sirve para ambos casos.
- Límite de 100.000 unidades y de un año: evita errores de digitación que dispararían el costo.
