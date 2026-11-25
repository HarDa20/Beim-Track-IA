# Plan técnico · 008 · Seguridad transversal

**Spec:** [spec.md](spec.md) · **Revisión contra la constitución:** ☑ Art. III completo

## 2. Archivos
| Archivo | Responsabilidad | Requisitos |
|---|---|---|
| `includes/security.php` · `csrfToken`, `campoCsrf`, `requierePostConCsrf` | CSRF | RNF-SEG-01 |
| `includes/helpers.php` · `e()` | Escape de salida | RNF-SEG-02 |
| `includes/security.php` · `enviarCabecerasSeguridad` | Cabeceras HTTP | RNF-SEG-03 |
| `includes/helpers.php` · `rutaSegura` | Redirecciones internas | RNF-SEG-04 |
| `.htaccess` (raíz y carpetas), `tests/router.php` | Bloqueo de archivos internos | RNF-SEG-05 |
| `includes/security.php` · `registrarManejadorErrores` | Errores al log | RNF-SEG-06 |
| `config/db.php`, `config/env.php` | PDO sin emulación de preparadas y `.env` | RNF-SEG-07 |
