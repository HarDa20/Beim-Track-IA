# Resultado de pruebas de especificación · BEIM Track

- Fecha de ejecución: 2026-11-22 19:59:55
- Contrato: specs/003-flujo-produccion/contracts/flujo_estados.json (versión 2.0)
- Resultado: **17 de 17 aprobadas**

| ID | Criterio | Prueba | Resultado obtenido | Estado |
|---|---|---|---|---|
| S01 | CA-FLU-01 | Transiciones y áreas del código = contrato flujo_estados.json | 7 transiciones iguales · áreas iguales | ✅ Aprobada |
| S02 | CA-FLU-05 | Acciones disponibles por estado y usuario = roles del contrato | 112 combinaciones estado × usuario coinciden | ✅ Aprobada |
| S03 | CA-FLU-01 | Transiciones que exigen motivo = requiere_motivo del contrato | 11 transiciones revisadas | ✅ Aprobada |
| S08 | CA-FLU-01 | Estados y estados activos del código = contrato | 8 estados en el mismo orden | ✅ Aprobada |
| S04 | CA-AUT-03 | Reglas de contraseña (8–72, letras y números, confirmación) | 7 casos correctos | ✅ Aprobada |
| S05 | CA-PED-06 | Valores límite de RF-PED-01 | 13 límites correctos | ✅ Aprobada |
| S06 | CA-PED-07 | Costo estimado = precio unitario × cantidad | Costo 112500 (esperado 112500), precio copiado 30000 | ✅ Aprobada |
| S07 | CA-ARC-03 | Solo se aceptan archivos cuya firma real coincide con la extensión | 8 firmas correctas | ✅ Aprobada |
| S09 | CA-EXP-04 | celdaCsv() neutraliza fórmulas y conserva el resto | 9 celdas correctas | ✅ Aprobada |
| S10 | CA-EXP-06 | La exportación respeta el límite de 5.000 filas y excluye borradores | 5000 filas exportadas (límite 5000), 0 borradores | ✅ Aprobada |
| S11 | CA-TEM-02 | Tema y acento solo aceptan valores de la lista blanca | 8 casos correctos | ✅ Aprobada |
| S12 | CA-BOT-02 | Menú del asistente por rol = configuración y opciones siguientes válidas | 10 opciones válidas para 3 roles | ✅ Aprobada |
| S13 | CA-BOT-07 | Límites de las preguntas a la IA (500 caracteres, 20 cada 10 min) | 6 límites correctos | ✅ Aprobada |
| S14 | CA-BOT-08 | El contexto para la IA no lleva correos, teléfonos ni pedidos ajenos | Solo primer nombre, sus pedidos y precios | ✅ Aprobada |
| S15 | CA-GER-03 | Indicadores gerenciales y cuello de botella con tiempos conocidos | Horas {"Recibido":3,"En diseño":12,"En producción":40,"Control de calidad":5,"Listo para entrega":4} · cuello En producción · ingresos 400000 · a tiempo 50% · ciclo 2.7 d · cancelación 25% | ✅ Aprobada |
| S16 | CA-GER-04 | Top de clientes ordenado por valor y con máximo 5 | 5 clientes: 90000, 70000, 50000, 30000, 20000 | ✅ Aprobada |
| S17 | CA-V3D-02 | Modelo 3D asignado a cada categoría | fachada, pared, pendon, pendon, pendon | ✅ Aprobada |
