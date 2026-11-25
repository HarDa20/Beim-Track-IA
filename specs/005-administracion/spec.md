# Especificación · 005 · Administración

**Código:** ADM · **Estado:** Implementada · **Origen:** Retroactiva (v2) · **Requisitos del documento original:** RF-05, RF-16, RF-17, RF-22 a RF-25

## 1. Problema y objetivo
El dueño de la empresa no sabe cuántos pedidos tiene, cuáles van tarde ni quién está cargado de trabajo. El administrador necesita **indicadores reales, control de los responsables y gestión de cuentas y precios** desde un solo lugar.

## 2. Historias de usuario
- **HU-ADM-01** · Como administrador, quiero un dashboard con los números reales del negocio.
- **HU-ADM-02** · Como administrador, quiero filtrar los pedidos y ver los retrasados.
- **HU-ADM-03** · Como administrador, quiero asignar el responsable de la etapa de un pedido.
- **HU-ADM-04** · Como administrador, quiero crear, editar y desactivar cuentas del personal.
- **HU-ADM-05** · Como administrador, quiero actualizar categorías y precios.

## 3. Requisitos funcionales
- **RF-ADM-01** · El dashboard debe calcular desde la base de datos: total de pedidos (sin borradores), pendientes, en producción, finalizados, retrasados, porcentaje de cumplimiento, distribución por estado, por categoría, por mes y carga por área. Sin datos de ejemplo.
- **RF-ADM-02** · El listado de pedidos debe filtrarse por texto (número, cliente o trabajo), estado, categoría, responsable (incluido "sin responsable") y "solo retrasados".
- **RF-ADM-03** · El administrador puede asignar o quitar el responsable de un pedido en proceso; el responsable debe ser un empleado **activo del área de la etapa actual**. La asignación queda en el historial y alerta al empleado.
- **RF-ADM-04** · El administrador gestiona usuarios: crear (un empleado exige área), editar, cambiar rol o área, restablecer contraseña y activar o desactivar. Al desactivar a un empleado sus pedidos asignados quedan sin responsable. El administrador no puede desactivarse a sí mismo.
- **RF-ADM-05** · El administrador gestiona categorías (nombre, unidad, descripción, precio, activa). Cambiar un precio no altera los pedidos ya registrados.
- **RF-ADM-06** · El administrador consulta el historial global de cambios y el registro de notificaciones externas con su estado.

## 4. Criterios de aceptación
- **CA-ADM-01** (RF-ADM-01) Dados pedidos en la base de datos, cuando se abre el dashboard, entonces "Total de pedidos" y "Finalizados" son iguales a `COUNT(*)`. ⟶ Pruebas: D07
- **CA-ADM-02** (RF-ADM-02) Dado un pedido activo con fecha vencida, cuando se filtra "solo retrasados", entonces aparece en la lista. ⟶ Pruebas: D06
- **CA-ADM-03** (RF-ADM-03) Dado un pedido en proceso, cuando se intenta asignar a un cliente, entonces se rechaza. ⟶ Pruebas: F05
- **CA-ADM-04** (RF-ADM-03) Dado un empleado inexistente, cuando se asigna, entonces se muestra un mensaje sin error 500. ⟶ Pruebas: F06
- **CA-ADM-05** (RF-ADM-03) Dado un pedido En diseño, cuando se asigna a un empleado de Producción, entonces se rechaza por área. ⟶ Pruebas: F07
- **CA-ADM-06** (RF-ADM-03) Dado un pedido En diseño, cuando se asigna a Diseño 1, entonces queda asignado y Diseño 1 recibe una alerta. ⟶ Pruebas: F08
- **CA-ADM-07** (RF-ADM-04) Dado un empleado sin área, cuando se crea, entonces se rechaza; con área se crea. ⟶ Pruebas: D01
- **CA-ADM-08** (RF-ADM-04) Dado un empleado con pedidos asignados, cuando se desactiva, entonces sus pedidos quedan libres y no puede entrar. ⟶ Pruebas: D02
- **CA-ADM-09** (RF-ADM-04) Dado el administrador, cuando intenta desactivarse a sí mismo, entonces se rechaza. ⟶ Pruebas: D03
- **CA-ADM-10** (RF-ADM-05) Dado un pedido existente, cuando cambia el precio de su categoría, entonces su costo no cambia. ⟶ Pruebas: D04
- **CA-ADM-11** (RF-ADM-06) Dado cualquier cambio de estado o aviso, cuando el administrador abre Actividad, entonces lo ve con usuario, fecha y estado del envío. ⟶ Verificación manual: Administración > Actividad, pestañas Historial y Notificaciones externas.

## 5. Fuera de alcance
- Reportes en PDF (ver 009 para exportar a CSV).
- Varios administradores con permisos distintos.
