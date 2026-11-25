# Especificación · 015 · Panel gerencial

**Código:** GER · **Estado:** Implementada · **Origen:** SDD completo (pedido del profesor, 22/11/2026)

## 1. Problema y objetivo
El dashboard muestra la operación del día. El gerente de la empresa necesita además una **vista gerencial**: cuánto se factura, dónde se demoran los pedidos y quiénes son los mejores clientes, para tomar decisiones.

## 2. Requisitos funcionales
- **RF-GER-01** · Una página solo para el administrador muestra, para el periodo elegido (últimos 30, 90 o 365 días): ingresos de pedidos entregados, valor de los pedidos en proceso, ticket promedio, porcentaje de entregas a tiempo, ciclo promedio (de registrado a entregado, en días) y tasa de cancelación.
- **RF-GER-02** · Calcula, desde el historial, el **tiempo promedio que pasa un pedido en cada etapa** y señala el **cuello de botella** (la etapa más lenta).
- **RF-GER-03** · Muestra los 5 clientes con mayor valor, las ventas por categoría, los pedidos por canal de recepción y la carga de trabajo activa por empleado.
- **RF-GER-04** · La página se puede imprimir o guardar en PDF desde el navegador con un diseño limpio.

## 3. Criterios de aceptación
- **CA-GER-01** (RF-GER-01) Dados pedidos de prueba, cuando el administrador abre el panel, entonces los ingresos mostrados son la suma de los costos entregados en la base de datos. ⟶ Pruebas: G01
- **CA-GER-02** (RF-GER-01) Dado un cliente o un empleado, cuando abre el panel gerencial, entonces se le niega. ⟶ Pruebas: G02
- **CA-GER-03** (RF-GER-01, RF-GER-02) Dados pedidos con tiempos conocidos por etapa, cuando se calculan los indicadores, entonces los promedios, el cumplimiento y el cuello de botella son los esperados. ⟶ Pruebas: S15
- **CA-GER-04** (RF-GER-03) Dados pedidos de varios clientes, entonces el top de clientes está ordenado por valor y tiene máximo 5. ⟶ Pruebas: S16
- **CA-GER-05** (RF-GER-04) Dado el botón "Imprimir / PDF", cuando se usa, entonces se imprime sin menú lateral ni asistente. ⟶ Verificación manual: imprimir a PDF desde Chrome.

## 4. Fuera de alcance
- Proyecciones o metas de ventas.
