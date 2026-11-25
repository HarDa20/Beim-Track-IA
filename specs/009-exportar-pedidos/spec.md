# Especificación · 009 · Exportar pedidos a CSV

**Código:** EXP · **Estado:** Implementada · **Origen:** **SDD completo** (esta spec se escribió y acordó antes de escribir el código)
**Autores:** Equipo BEIM Track · **Fecha:** 18/11/2026

> Esta funcionalidad es la demostración del ciclo SDD de principio a fin: **spec.md → plan.md → tasks.md → código → pruebas → verificación**. Su historial de decisiones queda en la sección 8.

## 1. Problema y objetivo
El dueño de la empresa lleva la contabilidad y los informes para sus socios en Excel. Hoy tendría que copiar a mano lo que ve en la pantalla de pedidos. Necesita **descargar en un clic el listado que está viendo**, con los mismos filtros, en un archivo que Excel en español abra correctamente.

## 2. Historias de usuario
- **HU-EXP-01** · Como administrador, quiero descargar en Excel los pedidos que estoy filtrando para hacer mis informes sin copiar a mano.
- **HU-EXP-02** · Como empresa, quiero que esa información solo la pueda sacar el administrador, porque contiene datos personales de los clientes (Ley 1581).

## 3. Requisitos funcionales
- **RF-EXP-01** · Desde *Administración > Pedidos*, un botón "Exportar CSV" debe descargar los pedidos **con exactamente los mismos filtros** aplicados en la pantalla (texto, estado, categoría, responsable, solo retrasados y vista).
- **RF-EXP-02** · El archivo debe tener una fila de encabezados y, por pedido, las columnas: Pedido, Registrado, Cliente, Correo, Canal, Categoría, Trabajo, Cantidad, Unidad, Precio unitario, Costo estimado, Entrega pactada, Estado, Responsable, Retrasado (Sí/No) y Entregado el. Nunca incluye borradores.
- **RF-EXP-03** · Solo el administrador puede exportar; cualquier otro rol o un visitante sin sesión no recibe el archivo.

## 4. Requisitos no funcionales
- **RNF-EXP-01** · Compatible con Excel en configuración regional de Colombia: UTF-8 con BOM, separador `;`, números con punto decimal y fechas `AAAA-MM-DD`. Nombre: `pedidos_beimtrack_AAAA-MM-DD.csv`.
- **RNF-EXP-02** · Protección contra **inyección de fórmulas** (CSV injection, OWASP): toda celda de texto que empiece por `=`, `+`, `-`, `@`, tabulador o retorno de carro se antepone con un apóstrofo (`'`) para que Excel no la ejecute.
- **RNF-EXP-03** · Máximo 5.000 filas por exportación.

## 5. Criterios de aceptación
- **CA-EXP-01** (RF-EXP-01, RF-EXP-02) Dado un administrador, cuando exporta sin filtros, entonces recibe `text/csv` con BOM, el encabezado acordado y los pedidos existentes. ⟶ Pruebas: E01
- **CA-EXP-02** (RF-EXP-01) Dado el filtro estado = Entregado, cuando exporta, entonces el archivo trae exactamente los pedidos entregados de la base de datos. ⟶ Pruebas: E02
- **CA-EXP-03** (RF-EXP-03) Dado un cliente con sesión, cuando abre la exportación, entonces no recibe el archivo. ⟶ Pruebas: E03
- **CA-EXP-04** (RNF-EXP-02) Dado un pedido cuyo trabajo empieza por `=`, cuando se exporta, entonces la celda empieza por `'=`. ⟶ Pruebas: E04, S09
- **CA-EXP-05** (RNF-EXP-01) Dado el archivo descargado, cuando se abre con doble clic en Excel, entonces las tildes y las columnas se ven bien. ⟶ Verificación manual: abrir el CSV en Excel con configuración regional de Colombia.
- **CA-EXP-06** (RNF-EXP-03) Dada la función de exportación, entonces consulta con un límite de 5.000 filas. ⟶ Pruebas: S10

## 6. Fuera de alcance
- Exportar a `.xlsx` nativo o PDF (requeriría librerías; Art. V de la constitución).
- Exportar el historial de cada pedido.
- Exportación para empleados o clientes.

## 7. Preguntas abiertas (resueltas)
- [x] ¿`,` o `;`? → `;`, porque Excel en español usa la coma como separador decimal.
- [x] ¿Incluir el correo del cliente? → Sí, es solo para el administrador (HU-EXP-02).

## 8. Historial de la especificación
| Fecha | Cambio | Quién |
|---|---|---|
| 18/11/2026 | Borrador inicial desde la necesidad del dueño | Equipo |
| 18/11/2026 | Se agrega RNF-EXP-02 (inyección de fórmulas) al revisar contra el Art. III | Revisión de la spec |
| 18/11/2026 | Acordada; se pasa a plan.md | Equipo |
