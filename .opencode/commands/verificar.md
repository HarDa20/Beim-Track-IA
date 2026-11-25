---
description: SDD paso 5 · Ejecuta pruebas de spec y el verificador de trazabilidad, y explica el resultado
agent: qa-tester
subtask: true
---

Fase **VERIFICAR** del ciclo SDD.

Resultado de las pruebas de especificación:
!`php tests/pruebas_spec.php`

Resultado del verificador:
!`php tests/verificar_specs.php`

Explica en una tabla corta qué requisitos cumplen, cuáles fallan y cuáles dependen de verificación manual. Si las pruebas HTTP no se ejecutaron hoy, indica cómo correrlas (XAMPP o SQLite según `AGENTS.md`). Para cada error, di qué archivo hay que corregir: la spec, el código o la prueba. $ARGUMENTS
