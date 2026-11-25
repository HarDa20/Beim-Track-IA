# Plan técnico · 011 · Tema claro/oscuro y color de acento

**Spec:** [spec.md](spec.md) · **Revisión contra la constitución:** ☑ Art. V ☑ Art. VI (enmienda 1)

## 1. Enfoque
Todo el diseño usa variables CSS. El tema claro redefine esas variables bajo `html[data-tema="claro"]` y cada acento bajo `html[data-acento="…"]`. `htmlHead()` lee las cookies con una lista blanca (`temaActual()`, `acentoActual()`) y escribe los atributos en `<html>`, así no hay parpadeo. `js/app.js` cambia el atributo y la cookie al hacer clic.

## 2. Archivos
| Archivo | Responsabilidad | Requisitos |
|---|---|---|
| `includes/helpers.php` · `temaActual`, `acentoActual` | Lista blanca | RF-TEM-01, RF-TEM-02, RF-TEM-03 |
| `includes/layout.php` · `htmlHead`, `selectorTema` | Atributos y controles | RF-TEM-01, RF-TEM-02 |
| `css/style.css` | Variables del tema claro y acentos | RF-TEM-01, RF-TEM-02, RNF-TEM-01 |
| `js/app.js` | Cambio en vivo y cookie | RF-TEM-01 |
