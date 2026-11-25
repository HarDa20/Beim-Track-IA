# Tareas · 009 · Exportar pedidos a CSV

Orden de trabajo acordado. Cada tarea se marcó `[x]` solo después de que su prueba pasó.

- [x] **T-EXP-01** · Escribir las pruebas E01–E04 y S09–S10 **antes** del código (fallan en rojo) · Cumple: todos · Prueba: E01, E02, E03, E04, S09, S10
- [x] **T-EXP-02** · `celdaCsv()` con neutralización de fórmulas · Cumple: RNF-EXP-02 · Prueba: S09
- [x] **T-EXP-03** · `filtrosPedidosAdmin()` y refactor de `admin/pedidos.php` sin cambiar su comportamiento · Cumple: RF-EXP-01 · Prueba: D06 (sigue pasando)
- [x] **T-EXP-04** · `encabezadosExportacion()`, `filaExportacion()` y `consultarPedidosExportacion()` con límite · Cumple: RF-EXP-02, RNF-EXP-03 · Prueba: S10
- [x] **T-EXP-05** · `admin/exportar_pedidos.php` con `requiereRol('administrador')`, BOM, `;` y nombre con fecha · Cumple: RF-EXP-01, RF-EXP-03, RNF-EXP-01 · Prueba: E01, E02, E03
- [x] **T-EXP-06** · Botón "Exportar CSV" en la barra de filtros · Cumple: RF-EXP-01 · Prueba: E01
- [x] **T-EXP-07** · Ejecutar `verificar_specs.php` y actualizar `TRAZABILIDAD.md` · Cumple: Art. VIII · Prueba: verificador
- [ ] **T-EXP-08** · Abrir el archivo en Excel real con configuración de Colombia · Cumple: RNF-EXP-01 · Prueba: verificación manual
