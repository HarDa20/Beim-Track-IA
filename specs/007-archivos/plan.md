# Plan técnico · 007 · Archivos de diseño

**Spec:** [spec.md](spec.md) · **Revisión contra la constitución:** ☑ Art. III

## 2. Archivos
| Archivo | Responsabilidad | Requisitos |
|---|---|---|
| `includes/archivos.php` · `validarArchivosSubidos`, `firmaValida` | Lista blanca, tamaño, cantidad y firma | RF-ARC-01 |
| `includes/archivos.php` · `guardarArchivo` | Nombre aleatorio con `random_bytes` | RF-ARC-02 |
| `archivo.php` | Descarga con `puedeVerPedido` | RF-ARC-02 |
| `uploads/.htaccess`, `tests/router.php` | Bloqueo del acceso directo | RF-ARC-02 |
| `subir_archivo.php` | Archivos adicionales | RF-ARC-03 |

## 4. Decisiones
- Firma por bytes iniciales: la validación no depende de la extensión `fileinfo` de PHP ni de lo que diga el navegador sobre el tipo de archivo.
