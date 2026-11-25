# Especificación · 002 · Pedidos del cliente

**Código:** PED · **Estado:** Implementada · **Origen:** Retroactiva (v2) · **Requisitos del documento original:** RF-06 a RF-14

## 1. Problema y objetivo
Los pedidos llegan por chat, llamada o en persona y se anotan en papel; el cliente no tiene cómo saber en qué va su trabajo. El cliente debe poder **registrar su pedido con todas las especificaciones, conocer el costo estimado y seguir su avance** sin llamar a la empresa.

## 2. Historias de usuario
- **HU-PED-01** · Como cliente, quiero crear un pedido con categoría, cantidad, fecha y archivos para que la empresa tenga toda la información desde el inicio.
- **HU-PED-02** · Como cliente, quiero ver el costo estimado antes de enviar.
- **HU-PED-03** · Como cliente, quiero guardar un borrador y terminarlo después.
- **HU-PED-04** · Como cliente, quiero ver la etapa actual y el historial de mi pedido.
- **HU-PED-05** · Como cliente, quiero cancelar mi pedido si todavía no empezaron a trabajarlo.

## 3. Requisitos funcionales
- **RF-PED-01** · El sistema debe validar el pedido en el servidor: categoría activa existente; nombre del trabajo de 3 a 200 caracteres; cantidad mayor que 0 y hasta 100.000; fecha de entrega válida entre hoy y un año; descripción de hasta 2.000 caracteres. Un pedido inválido no se guarda.
- **RF-PED-02** · El costo estimado debe ser `precio_unitario × cantidad` redondeado a 2 decimales, y el precio unitario se copia al pedido para que un cambio posterior de precios no lo altere.
- **RF-PED-03** · El cliente debe poder guardar un pedido como **Borrador**, retomarlo, enviarlo (pasa a Recibido) o eliminarlo. Los borradores no son visibles para empleados.
- **RF-PED-04** · Un cliente solo puede ver y descargar los archivos de sus propios pedidos.
- **RF-PED-05** · El cliente debe ver en su panel sus pedidos con estado y costo, y en el detalle la línea de etapas y el historial completo.
- **RF-PED-06** · El cliente puede cancelar su propio pedido únicamente mientras está en **Recibido**, escribiendo el motivo.

## 4. Criterios de aceptación
- **CA-PED-01** (RF-PED-01) Dado un pedido válido con un PNG real, cuando se envía, entonces queda Recibido con historial y archivo. ⟶ Pruebas: P01
- **CA-PED-02** (RF-PED-01) Dada una fecha de entrega pasada, cuando se envía, entonces se rechaza con "anterior a hoy" y no se crea. ⟶ Pruebas: P02
- **CA-PED-03** (RF-PED-01) Dada la fecha "hola", cuando se envía, entonces se rechaza y no se crea. ⟶ Pruebas: P03
- **CA-PED-04** (RF-PED-01) Dada una cantidad 0, cuando se envía, entonces se rechaza y no se crea. ⟶ Pruebas: P04
- **CA-PED-05** (RF-PED-01) Dada una categoría inexistente, cuando se envía, entonces se rechaza y no se crea. ⟶ Pruebas: P05
- **CA-PED-06** (RF-PED-01) Dados los valores límite (cantidad 100.000 y 100.000,01; fecha hoy y un año y un día; nombre de 2 caracteres), cuando se validan, entonces se aceptan o rechazan según la regla. ⟶ Pruebas: S05
- **CA-PED-07** (RF-PED-02) Dada una categoría con precio P y cantidad C, cuando se guarda, entonces el costo es P × C. ⟶ Pruebas: P01, S06
- **CA-PED-08** (RF-PED-02) Dado un pedido existente, cuando el administrador cambia el precio de su categoría, entonces el costo del pedido no cambia. ⟶ Pruebas: D04
- **CA-PED-09** (RF-PED-03) Dado un borrador guardado, cuando el cliente lo retoma y lo envía, entonces pasa de Borrador a Recibido. ⟶ Pruebas: P08
- **CA-PED-10** (RF-PED-03) Dado un borrador propio, cuando el cliente lo elimina, entonces desaparece. ⟶ Pruebas: P09
- **CA-PED-11** (RF-PED-04) Dado el pedido de otro cliente, cuando un cliente intenta verlo o descargar su archivo, entonces se le niega. ⟶ Pruebas: P10, P11
- **CA-PED-12** (RF-PED-05) Dado un pedido creado, cuando el cliente abre su panel, entonces ve el costo estimado. ⟶ Pruebas: P14
- **CA-PED-13** (RF-PED-05) Dado un pedido que recorrió todo el flujo, cuando se abre el detalle, entonces el historial muestra todos los cambios en orden. ⟶ Pruebas: F13
- **CA-PED-14** (RF-PED-06) Dado un pedido propio en Recibido, cuando el cliente lo cancela con motivo, entonces queda Cancelado. ⟶ Pruebas: F15
- **CA-PED-15** (RF-PED-06) Dado un pedido propio en cualquier otro estado, entonces el cliente no tiene la acción de cancelar. ⟶ Pruebas: S02

## 5. Fuera de alcance
- Editar las especificaciones de un pedido ya enviado (se agregan archivos y las correcciones se anotan como observaciones).
- Pagos en línea.
