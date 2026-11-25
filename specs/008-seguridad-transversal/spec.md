# Especificación · 008 · Seguridad transversal

**Código:** SEG · **Estado:** Implementada · **Origen:** Retroactiva (v2) · **Requisitos del documento original:** RNF-01, RNF-02, RNF-11, RNF-15

## 1. Objetivo
Aplicar en todo el sistema el Artículo III de la constitución, frente a las amenazas comunes de una aplicación web (OWASP Top 10): falsificación de peticiones, inyección de código en pantalla, *clickjacking*, redirecciones abiertas y exposición de archivos internos.

## 2. Requisitos no funcionales
- **RNF-SEG-01** · Todo formulario que modifica datos exige POST con token CSRF de 64 caracteres hexadecimales.
- **RNF-SEG-02** · Toda salida de datos en HTML se escapa con `htmlspecialchars` (función `e()`).
- **RNF-SEG-03** · Todas las respuestas incluyen `Content-Security-Policy`, `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff` y `Referrer-Policy: same-origin`.
- **RNF-SEG-04** · Las redirecciones con destino enviado por el usuario solo pueden apuntar a rutas internas.
- **RNF-SEG-05** · Las carpetas `config/`, `includes/`, `database/`, `tests/`, `docs/`, `uploads/`, `specs/`, `.opencode/` y los archivos `.env`, `.sql`, `.md` y `opencode.json` no se sirven al navegador.
- **RNF-SEG-06** · Los errores técnicos se registran en el log y el usuario ve un mensaje comprensible, nunca un error 500 con detalles.
- **RNF-SEG-07** · Todas las consultas a la base de datos son preparadas con PDO y las credenciales se leen de `.env`.

## 3. Criterios de aceptación
- **CA-SEG-01** (RNF-SEG-01) Dado un login sin token CSRF, entonces se rechaza y no se crea sesión. ⟶ Pruebas: A05
- **CA-SEG-02** (RNF-SEG-02) Dado un nombre de trabajo con `<script>`, cuando se muestra, entonces aparece escapado. ⟶ Pruebas: P15
- **CA-SEG-03** (RNF-SEG-03) Dada cualquier página, entonces trae las cabeceras de seguridad. ⟶ Pruebas: A14
- **CA-SEG-04** (RNF-SEG-04) Dado `volver=//sitio-externo.com`, entonces la redirección queda dentro del sistema. ⟶ Pruebas: X02
- **CA-SEG-05** (RNF-SEG-05) Dadas peticiones a `/database/schema.sql`, `/includes/pedidos.php`, `/.env.example`, al contrato en `specs/` y a `/opencode.json`, entonces responden 403. ⟶ Pruebas: X03
- **CA-SEG-06** (RNF-SEG-06) Dado un pedido inexistente o un empleado inexistente, entonces hay un mensaje y no un error 500. ⟶ Pruebas: X01, F06
- **CA-SEG-07** (RNF-SEG-07) Dado el código fuente, entonces no hay datos del usuario concatenados en SQL. ⟶ Verificación manual: revisión con el agente `auditor-seguridad` de OpenCode (`/auditar`).
