# Plan técnico · 010 · Multisesión

**Spec:** [spec.md](spec.md) · **Revisión contra la constitución:** ☑ Art. III (misma sesión segura y CSRF) ☑ Art. V (sin librerías)

## 1. Enfoque
Igual que las cuentas de Google (`/u/0`, `/u/1`): una sola cookie de sesión guarda un arreglo de cuentas `$_SESSION['cuentas'][ranura]`. La ranura de cada petición se lee de `?u=N`. Como **todas** las direcciones del sistema se construyen con `url()`, basta con que `url()` agregue `u=N` cuando la ranura no es 0 para que enlaces, formularios y redirecciones la conserven.

## 2. Archivos
| Archivo | Responsabilidad | Requisitos |
|---|---|---|
| `includes/auth.php` · `ranuraActual`, `usuarioActual`, `iniciarSesionUsuario`, `cerrarCuenta`, `cuentasAbiertas` | Ranuras y cuentas | RF-MSE-01, RF-MSE-02, RF-MSE-04, RNF-MSE-01 |
| `includes/helpers.php` · `url()` | Conservar `u` | RF-MSE-02 |
| `includes/layout.php` | Selector de cuentas | RF-MSE-03 |
| `index.php`, `login_process.php` | Formulario "agregar cuenta" | RF-MSE-01 |
| `logout.php` | Cerrar una o todas | RF-MSE-04 |

## 4. Decisiones
- Ranura en la URL y no en otra cookie: las cookies se comparten entre pestañas, la URL no.
- El token CSRF es común a la sesión del navegador: sigue protegiendo contra sitios externos.
