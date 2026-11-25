# BEIM Track · Desarrollo Dirigido por Especificaciones (SDD)

En BEIM Track **la especificación es la fuente de verdad**. El código y las pruebas existen para cumplir lo que dicen las especificaciones de esta carpeta, y un verificador automático comprueba que las tres cosas (spec ↔ código ↔ pruebas) estén alineadas.

> "Primero acordamos qué vamos a construir, después lo construimos y por último demostramos que lo construido cumple lo acordado."

## 1. El ciclo SDD que usamos

```
 0. CONSTITUCIÓN ─► 1. ESPECIFICAR ─► 2. PLANEAR ─► 3. TAREAS ─► 4. IMPLEMENTAR ─► 5. VERIFICAR
   (principios)      spec.md (QUÉ       plan.md       tasks.md      código con        pruebas +
   constitucion.md    y POR QUÉ)        (CÓMO)        (pasos)       etiquetas @spec   verificar_specs.php
                         ▲                                                               │
                         └──────────────── si algo no cumple, se corrige la spec o el código ◄┘
```

| Fase | Artefacto | Pregunta que responde | Quién decide |
|---|---|---|---|
| 0 · Constitución | `constitucion.md` | ¿Qué reglas nunca se rompen? | Equipo |
| 1 · Especificar | `NNN-funcionalidad/spec.md` | ¿Qué necesita el usuario y cómo sabremos que funciona? | Equipo + cliente (empresa) |
| 2 · Planear | `NNN-funcionalidad/plan.md` | ¿Con qué archivos, tablas y decisiones técnicas? | Equipo técnico |
| 3 · Tareas | `NNN-funcionalidad/tasks.md` | ¿En qué orden se construye y se prueba? | Equipo técnico |
| 4 · Implementar | código PHP / SQL / CSS / JS | — | Desarrollador o agente de IA (OpenCode) |
| 5 · Verificar | `tests/` + `TRAZABILIDAD.md` | ¿Lo construido cumple lo especificado? | Automático |

## 2. Convenciones de identificadores

| Prefijo | Significado | Ejemplo | Dónde vive |
|---|---|---|---|
| `RF-XXX-NN` | Requisito funcional de la funcionalidad XXX | `RF-FLU-05` | `spec.md` |
| `RNF-XXX-NN` | Requisito no funcional | `RNF-SEG-02` | `spec.md` |
| `CA-XXX-NN` | Criterio de aceptación (Dado / Cuando / Entonces) | `CA-FLU-07` | `spec.md` |
| `T-XXX-NN` | Tarea de implementación | `T-EXP-03` | `tasks.md` |
| `@spec RF-…` | Etiqueta en el código que implementa el requisito | `@spec RF-FLU-01` | archivos `.php` |
| `A01`, `P07`, `S03`… | Identificador de una prueba automática | `F10` | `tests/` |

Cada criterio de aceptación termina con `⟶ Pruebas: …` (IDs de pruebas automáticas) o con `⟶ Verificación manual: …` cuando todavía no se automatiza. El verificador lee estas líneas, así que **el formato importa**.

## 3. Mapa de especificaciones

| Carpeta | Funcionalidad | Código | Origen |
|---|---|---|---|
| [`001-autenticacion`](001-autenticacion/spec.md) | Registro, inicio y cierre de sesión, control de acceso | AUT | Retroactiva (v2) |
| [`002-pedidos-cliente`](002-pedidos-cliente/spec.md) | Crear, guardar como borrador, seguir y cancelar pedidos | PED | Retroactiva (v2) |
| [`003-flujo-produccion`](003-flujo-produccion/spec.md) | Máquina de estados por áreas, responsables e historial | FLU | Retroactiva (v2) + contrato ejecutable |
| [`004-registro-recepcion`](004-registro-recepcion/spec.md) | Pedidos por llamada, chat o presencial | REC | Retroactiva (v2) |
| [`005-administracion`](005-administracion/spec.md) | Dashboard, asignación, usuarios, categorías | ADM | Retroactiva (v2) |
| [`006-alertas-notificaciones`](006-alertas-notificaciones/spec.md) | Alertas internas, vencimientos y correo | ALE | Retroactiva (v2) |
| [`007-archivos`](007-archivos/spec.md) | Archivos de diseño seguros | ARC | Retroactiva (v2) |
| [`008-seguridad-transversal`](008-seguridad-transversal/spec.md) | CSRF, XSS, cabeceras, redirecciones, errores | SEG | Retroactiva (v2) |
| [`009-exportar-pedidos`](009-exportar-pedidos/spec.md) | Exportar el listado de pedidos a CSV (Excel) | EXP | **SDD completo: spec escrita antes del código** |
| [`010-multisesion`](010-multisesion/spec.md) | Varias cuentas abiertas en el mismo navegador | MSE | SDD completo (pedido del profesor) |
| [`011-temas`](011-temas/spec.md) | Tema claro/oscuro y color de acento | TEM | SDD completo (pedido del profesor) |
| [`012-identidad-visual`](012-identidad-visual/spec.md) | Ingreso con lámpara y logo de la empresa | IDV | SDD completo (pedido del profesor) |
| [`013-foto-perfil`](013-foto-perfil/spec.md) | Foto de perfil de los usuarios | FOT | SDD completo (pedido del profesor) |
| [`014-chatbot`](014-chatbot/spec.md) | Asistente Beimy con menú parametrizado e IA (Gemini) + arnés | BOT | SDD completo (pedido del profesor) |
| [`015-panel-gerencial`](015-panel-gerencial/spec.md) | Panel gerencial para la toma de decisiones | GER | SDD completo (pedido del profesor) |
| [`016-visualizacion-3d`](016-visualizacion-3d/spec.md) | Ver el diseño en 3D sobre el producto antes de pedirlo | V3D | SDD completo (pedido del equipo) |

**Retroactiva** significa que la funcionalidad ya existía en la v2 y su especificación se escribió por ingeniería inversa, para que el código existente quedara gobernado por specs. **009** es la demostración del ciclo completo: primero se escribió y acordó `spec.md`, luego `plan.md` y `tasks.md`, y solo entonces se programó `admin/exportar_pedidos.php` y sus pruebas. Las specs **010 a 016** (pedidos del profesor del 22/11/2026) siguieron el mismo ciclo completo: primero la spec y la enmienda de la constitución, luego el código y las pruebas.

## 4. Cómo verificar que el proyecto cumple sus especificaciones

Con XAMPP encendido y el sistema en `http://localhost/beimtrack_ia`:

```bash
# 1. Pruebas de reglas del negocio contra los contratos (no necesitan servidor)
php tests/pruebas_spec.php

# 2. Pruebas funcionales y de seguridad por HTTP
php tests/pruebas_funcionales.php http://localhost/beimtrack_ia

# 2b. Arnés del asistente Beimy (API de Gemini simulada; agrega --real para la API pública)
php tests/arnes_chatbot.php

# 3. Verificador SDD: cruza specs, código y resultados, y genera specs/TRAZABILIDAD.md
php tests/verificar_specs.php
```

El verificador falla (código de salida 1) si:

- un requisito no tiene criterios de aceptación;
- un criterio cita una prueba que no existe;
- un requisito no tiene ninguna etiqueta `@spec` en el código, o una etiqueta cita un requisito que no existe;
- alguna prueba citada falló en la última ejecución.

## 5. Cómo agregar una funcionalidad nueva con SDD

1. Copia `_plantillas/` como `specs/0NN-nombre/`.
2. Escribe `spec.md`: historias de usuario, requisitos `RF-…`, criterios `CA-…` y lo que queda fuera de alcance. **No escribas código todavía.**
3. Revisa la spec contra `constitucion.md` y acuérdala con el equipo.
4. Escribe `plan.md` (archivos, tablas, decisiones) y `tasks.md` (pasos con prueba asociada).
5. Implementa una tarea a la vez, poniendo `@spec RF-…` en las funciones que cumplen cada requisito.
6. Escribe las pruebas con los IDs citados en los criterios y ejecuta las tres órdenes de la sección 4.

Con OpenCode este ciclo está automatizado con los comandos `/especificar`, `/planear`, `/tareas`, `/implementar` y `/verificar` (ver `AGENTS.md` y `.opencode/`).
