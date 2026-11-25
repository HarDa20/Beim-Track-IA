# Plan técnico · 009 · Exportar pedidos a CSV

**Spec:** [spec.md](spec.md) · **Revisión contra la constitución:** ☑ Art. II ☑ Art. III ☑ Art. V ☑ Art. VII

## 1. Enfoque
Reutilizar `consultarPedidos()` (la misma consulta de la pantalla) para garantizar RF-EXP-01. Para que la pantalla y la exportación lean los filtros de la misma manera, la lectura de filtros de `admin/pedidos.php` se mueve a una función compartida `filtrosPedidosAdmin()`. El CSV se escribe con `fputcsv` sobre `php://output`, sin librerías.

## 2. Archivos
| Archivo | Nuevo / modificado | Responsabilidad | Requisitos |
|---|---|---|---|
| `includes/exportacion.php` | Nuevo | `filtrosPedidosAdmin()`, `celdaCsv()`, `encabezadosExportacion()`, `filaExportacion()`, `consultarPedidosExportacion()` | RF-EXP-01, RF-EXP-02, RNF-EXP-02, RNF-EXP-03 |
| `admin/exportar_pedidos.php` | Nuevo | Control de acceso y envío del archivo | RF-EXP-01, RF-EXP-03, RNF-EXP-01 |
| `admin/pedidos.php` | Modificado | Usar `filtrosPedidosAdmin()` y mostrar el botón con la misma consulta | RF-EXP-01 |
| `includes/bootstrap.php` | Modificado | Cargar `exportacion.php` | — |
| `tests/pruebas_funcionales.php` | Modificado | Pruebas E01–E04 | — |
| `tests/pruebas_spec.php` | Modificado | Pruebas S09–S10 | — |

## 3. Datos
Solo lectura de `pedidos`, `usuarios` y `categorias_precios`. **No requiere migración.**

## 4. Decisiones y alternativas descartadas
- **CSV y no XLSX**: XLSX exige una librería (PhpSpreadsheet + Composer), prohibido por el Art. V.1. Excel abre el CSV directamente.
- **GET y no POST**: es una consulta sin efectos secundarios; no necesita CSRF (Art. III.3 aplica a formularios que cambian datos).
- **Apóstrofo** para neutralizar fórmulas, recomendado por OWASP; descartado eliminar los caracteres porque cambiaría el dato.

## 5. Riesgos
- Pedidos con miles de filas: mitigado con el límite de 5.000 (RNF-EXP-03).
