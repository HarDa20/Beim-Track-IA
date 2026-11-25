# Plan técnico · 001 · Autenticación y control de acceso

**Spec:** [spec.md](spec.md) · **Revisión contra la constitución:** ☑ Art. II ☑ Art. III ☑ Art. IV ☑ Art. VII

## 1. Enfoque
Sesiones nativas de PHP endurecidas (modo estricto, solo cookies, HttpOnly, SameSite) y un único punto de control (`requiereLogin` / `requiereRol`) que cada página invoca en su primera línea a través de `includes/bootstrap.php`.

## 2. Archivos
| Archivo | Responsabilidad | Requisitos |
|---|---|---|
| `index.php` | Formularios de login y registro | RF-AUT-01, RF-AUT-02 |
| `register_process.php` | Valida y crea la cuenta como cliente, guarda `acepta_datos_en` | RF-AUT-01, RNF-AUT-01 |
| `login_process.php` | Verifica credenciales, bloqueo por intentos, rehash | RF-AUT-02, RF-AUT-03, RNF-AUT-01 |
| `logout.php` | Cierre solo por POST + CSRF | RF-AUT-04 |
| `includes/auth.php` | `requiereLogin`, `requiereRol`, `iniciarSesionUsuario`, bloqueo de intentos | RF-AUT-02 a RF-AUT-07 |
| `includes/security.php` | `iniciarSesionSegura` (cookie y modo estricto) | RF-AUT-06 |
| `includes/usuarios.php` | `validarDatosUsuario`, `validarPassword` | RF-AUT-01 |

## 3. Datos
- `usuarios` (rol, área, activo, acepta_datos_en, ultimo_acceso, password_hash).
- `intentos_login` (correo, ip, fecha) para el bloqueo.

## 4. Decisiones
- **Releer al usuario en cada petición** (RF-AUT-07): una consulta extra por página a cambio de que desactivar o cambiar el rol tenga efecto inmediato. Aceptado por el tamaño de la empresa.
- **Bloqueo por correo y no por IP**: en la empresa varios empleados comparten la misma red.
- Descartado: JWT o tokens propios; la sesión de PHP es suficiente para un monolito (Art. V).
