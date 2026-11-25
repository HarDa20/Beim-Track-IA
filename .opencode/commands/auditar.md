---
description: Auditoría de seguridad OWASP del código o de un cambio
agent: auditor-seguridad
subtask: true
---

Audita la seguridad de: $ARGUMENTS

Si no se indica nada, audita los archivos cambiados (`git diff --name-only`) y, si no hay cambios, todo `includes/`, `cliente/`, `empleado/`, `admin/` y las páginas de la raíz. Entrega la tabla por severidad y el requisito `RNF-SEG-…` afectado.
