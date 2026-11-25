# Tareas · 003 · Flujo de producción por áreas

- [x] **T-FLU-01** · Escribir el contrato `flujo_estados.json` acordado con el equipo · Cumple: RF-FLU-01 · Prueba: S01
- [x] **T-FLU-02** · `transiciones()` y `areaDeEstado()` iguales al contrato · Cumple: RF-FLU-01, RF-FLU-02 · Prueba: S01, S08
- [x] **T-FLU-03** · `accionesDisponibles()` por rol, área y responsable · Cumple: RF-FLU-02, RF-FLU-03 · Prueba: S02, F02, F08
- [x] **T-FLU-04** · `transicionRequiereObservacion()` y validación del motivo · Cumple: RF-FLU-05 · Prueba: S03, F10
- [x] **T-FLU-05** · `cambiarEstado()` transaccional con historial y bloqueo optimista · Cumple: RF-FLU-01, RF-FLU-06, RF-FLU-08 · Prueba: F03, F04, F12, F13
- [x] **T-FLU-06** · Liberar responsable al cambiar de área · Cumple: RF-FLU-04 · Prueba: F04
- [x] **T-FLU-07** · Guardar `fecha_entregado` · Cumple: RF-FLU-07 · Prueba: F11
- [x] **T-FLU-08** · `tomarPedido()` y bandeja por área · Cumple: RF-FLU-03 · Prueba: F04
- [x] **T-FLU-09** · Prueba de contrato `tests/pruebas_spec.php` (S01, S02, S03, S08) · Cumple: RF-FLU-01, RF-FLU-02 · Prueba: S01
