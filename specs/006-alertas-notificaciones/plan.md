# Plan técnico · 006 · Alertas internas y notificaciones por correo

**Spec:** [spec.md](spec.md) · **Revisión contra la constitución:** ☑ Art. IV.4 (nunca falso "enviado") ☑ Art. V (SMTP propio sin PHPMailer)

## 2. Archivos
| Archivo | Responsabilidad | Requisitos |
|---|---|---|
| `includes/alertas.php` · `crearAlerta`, `alertarArea`, `alertarAdministradores` | Alertas internas con clave anti-duplicados | RF-ALE-01 |
| `includes/alertas.php` · `revisarVencimientos` | Revisión cada 5 min por sesión | RF-ALE-02 |
| `includes/notificaciones/notificador.php` | Decide canal y registra el resultado | RF-ALE-03 |
| `includes/notificaciones/correo.php` | Cliente SMTP con STARTTLS/SSL | RF-ALE-03 |
| `alertas.php`, campana en `includes/layout.php` | Lectura de alertas | RF-ALE-01 |

## 4. Decisiones
- Revisión de vencimientos "perezosa" al cargar paneles: XAMPP en Windows no tiene cron fácil de configurar.
- Cliente SMTP propio de ~80 líneas para cumplir "sin Composer" (Art. V.1).
