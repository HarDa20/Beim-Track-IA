# Plan técnico · 003 · Flujo de producción por áreas

**Spec:** [spec.md](spec.md) · **Revisión contra la constitución:** ☑ Art. II (reglas en un solo lugar) ☑ Art. IV (historial transaccional)

## 1. Enfoque
Máquina de estados declarada en `includes/pedidos.php` (`transiciones()`, `areaDeEstado()`, `transicionRequiereObservacion()`) y una función de permisos (`accionesDisponibles()`) que la interfaz usa para pintar los botones **y** el servidor usa para aceptar o rechazar el cambio. El contrato JSON describe el mismo flujo en un formato que se puede leer y probar: `tests/pruebas_spec.php` falla si el código y el contrato se separan.

## 2. Archivos
| Archivo | Responsabilidad | Requisitos |
|---|---|---|
| `contracts/flujo_estados.json` | Fuente de verdad del flujo | RF-FLU-01, RF-FLU-02, RF-FLU-05 |
| `includes/pedidos.php` · `transiciones`, `areaDeEstado`, `transicionRequiereObservacion` | Máquina de estados | RF-FLU-01, RF-FLU-05 |
| `includes/pedidos.php` · `accionesDisponibles`, `puedeVerPedido` | Permisos por rol, área y responsable | RF-FLU-02, RF-FLU-03 |
| `includes/pedidos.php` · `cambiarEstado` | Transacción, liberación del responsable, historial, fecha de entrega, bloqueo optimista | RF-FLU-01, RF-FLU-04 a RF-FLU-08 |
| `includes/pedidos.php` · `tomarPedido` | Tomar de la bandeja | RF-FLU-03 |
| `empleado/panel.php`, `empleado/actualizar_estado.php`, `empleado/tomar_pedido.php` | Bandeja y acciones | RF-FLU-02, RF-FLU-03 |

## 3. Datos
`pedidos.estado`, `pedidos.empleado_id`, `pedidos.fecha_entregado`, `historial_pedido (usuario_id, estado_anterior, estado_nuevo, descripcion, fecha)`.

## 4. Decisiones
- **Bloqueo optimista** (`UPDATE … WHERE id = ? AND estado = ?` y revisar `rowCount`) en vez de bloqueos de fila: suficiente para pocos usuarios simultáneos y funciona igual en MySQL y SQLite.
- **El contrato no se lee en tiempo de ejecución**: el código conserva sus constantes para no añadir lectura de archivos en cada petición; la coherencia la garantiza la prueba S01. Alternativa evaluada para una v3: generar `transiciones()` desde el JSON.
- Las alertas y el correo se disparan **después** del `commit` para que un fallo externo no deshaga el cambio de estado.
