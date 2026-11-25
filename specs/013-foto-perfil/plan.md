# Plan técnico · 013 · Foto de perfil

**Spec:** [spec.md](spec.md) · **Revisión contra la constitución:** ☑ Art. III (misma validación de 007) ☑ Art. VII (la foto es dato personal: acceso restringido)

## 2. Archivos
| Archivo | Responsabilidad | Requisitos |
|---|---|---|
| `includes/usuarios.php` · `guardarFotoPerfil`, `quitarFotoPerfil`, `puedeVerFotoDe` | Validación, guardado y permisos | RF-FOT-01, RF-FOT-02, RF-FOT-04 |
| `foto_perfil.php` | Entrega controlada de la imagen | RF-FOT-02 |
| `includes/helpers.php` · `avatarHtml` | Foto o iniciales | RF-FOT-03 |
| `perfil.php` | Formulario de subir y quitar | RF-FOT-01, RF-FOT-04 |

## 3. Datos
Nueva columna `usuarios.foto_perfil VARCHAR(80) NULL`. Migración `database/migraciones/002_v3_ia.sql` para bases ya creadas; `schema.sql` y `tests/schema_sqlite.sql` actualizados.

## 4. Decisiones
- Se reutiliza `firmaValida()` de 007 para no duplicar la validación de imágenes.
