# Plan técnico · 015 · Panel gerencial

**Spec:** [spec.md](spec.md) · **Revisión contra la constitución:** ☑ Art. II (cálculo en `includes/gerencia.php`) ☑ Art. V (gráficos CSS propios)

## 2. Archivos
| Archivo | Responsabilidad | Requisitos |
|---|---|---|
| `includes/gerencia.php` · `indicadoresGerenciales`, `tiemposPorEtapa` | Cálculos | RF-GER-01, RF-GER-02, RF-GER-03 |
| `admin/gerencia.php` | Vista y filtro de periodo | RF-GER-01 a RF-GER-04 |
| `css/style.css` · `@media print` | Impresión limpia | RF-GER-04 |

## 3. Datos
Solo lectura de `pedidos`, `historial_pedido` y `usuarios`. Sin migración.

## 4. Decisiones
- Los tiempos por etapa se calculan en PHP recorriendo el historial ordenado, para que funcione igual en MySQL y SQLite.
- "A tiempo" = `fecha_entregado` (solo la fecha) ≤ `fecha_entrega` pactada.
