# Plan técnico · 005 · Administración

**Spec:** [spec.md](spec.md) · **Revisión contra la constitución:** ☑ Art. II ☑ Art. IV.2 y IV.3 ☑ Art. V (gráficos sin librerías)

## 2. Archivos
| Archivo | Responsabilidad | Requisitos |
|---|---|---|
| `admin/panel.php` | Indicadores y gráficos SVG/CSS calculados con SQL | RF-ADM-01 |
| `admin/pedidos.php` + `consultarPedidos()` | Filtros parametrizados | RF-ADM-02 |
| `admin/asignar_empleado.php` + `asignarResponsable()` | Asignación validada por área | RF-ADM-03 |
| `admin/usuarios.php` | Gestión de cuentas y liberación de pedidos | RF-ADM-04 |
| `admin/categorias.php` | Categorías y precios | RF-ADM-05 |
| `admin/actividad.php` | Historial global y registro de notificaciones | RF-ADM-06 |

## 4. Decisiones
- Los gráficos se dibujan en SVG y CSS propios (Art. V.2) en lugar de Chart.js.
- Desactivar en lugar de borrar cuentas (Art. IV.3).
- El cumplimiento se mide solo con pedidos entregados que tienen `fecha_entregado`.
