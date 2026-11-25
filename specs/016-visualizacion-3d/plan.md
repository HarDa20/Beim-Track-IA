# Plan técnico · 016 · Visualización 3D del pedido

**Spec:** [spec.md](spec.md) · **Revisión contra la constitución:** ☑ Art. II ☑ Art. III (misma descarga controlada) ☑ Art. V (CSS 3D sin librerías)

## 1. Enfoque
Cada modelo es un conjunto de caras (`div`) posicionadas con `transform: translate3d / rotateY` dentro de un contenedor con `transform-style: preserve-3d` y `perspective`. El diseño se pinta con la variable CSS `--arte: url(...)`. En el formulario la imagen sale de `URL.createObjectURL()` (no viaja al servidor); en el detalle sale de `archivo.php?id=N&ver=1`, que entrega imágenes en línea con su tipo real y el mismo `puedeVerPedido()`.

## 2. Archivos
| Archivo | Responsabilidad | Requisitos |
|---|---|---|
| `includes/visualizacion.php` · `modeloParaCategoria`, `visualizador3D` | Asignación de modelo y HTML | RF-V3D-01, RF-V3D-03 |
| `includes/form_pedido.php` | Sección y `data-modelo` por categoría | RF-V3D-01, RF-V3D-02 |
| `pedido.php`, `archivo.php` (`ver=1`) | 3D en el detalle | RF-V3D-04 |
| `js/app.js` · módulo visualizador | Giro, teclado, zoom, día/noche, imagen local | RF-V3D-02, RF-V3D-03 |
| `css/style.css` · sección 3D | Modelos | RF-V3D-01, RNF-V3D-01 |
| `includes/security.php` | CSP `img-src` + `blob:` para la vista previa local | RF-V3D-02 |
