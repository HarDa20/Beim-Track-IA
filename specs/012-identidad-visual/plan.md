# Plan técnico · 012 · Inicio de sesión con lámpara y logo

**Spec:** [spec.md](spec.md) · **Revisión contra la constitución:** ☑ Art. V (SVG, CSS y JS propios) ☑ Art. VI

## 1. Enfoque
La lámpara es un SVG dibujado a mano dentro de `index.php`. El estado vive en `data-lampara="apagada|encendida"` del contenedor: el CSS hace la luz, el brillo y la aparición del formulario, y `js/app.js` maneja el cordón (pointer events para arrastrar, clic y teclado). El servidor decide el estado inicial: **encendida** si hay mensajes que mostrar y **apagada** si no. Para no dejar a nadie sin formulario: un bloque `<noscript>` lo hace visible cuando no hay JavaScript y la regla `@media (prefers-reduced-motion: reduce)` lo muestra sin animación.

## 2. Archivos
| Archivo | Responsabilidad | Requisitos |
|---|---|---|
| `index.php` | Escena, lámpara, cordón y formulario | RF-IDV-01, RF-IDV-02 |
| `js/app.js` · módulo lámpara | Arrastre del cordón y teclado | RF-IDV-01 |
| `css/style.css` · sección lámpara | Luz, cono, transición del formulario | RF-IDV-01 |
| `includes/layout.php` · `logoBeim`, `faviconDataUri` | Logo reemplazable | RF-IDV-03 |
| `img/logo.svg` | Logo vectorial provisional | RF-IDV-03 |
