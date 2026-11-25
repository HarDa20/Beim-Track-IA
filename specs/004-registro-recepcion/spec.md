# Especificación · 004 · Registro de pedidos por Recepción

**Código:** REC · **Estado:** Implementada · **Origen:** Retroactiva (v2) · **Requisitos del documento original:** RF-09, RF-10

## 1. Problema y objetivo
La mayoría de los pedidos de la empresa **no llegan por la web**: llegan por llamada, chat o en persona. Si solo el cliente pudiera crear pedidos, el sistema no reemplazaría la hoja de papel. Recepción debe poder registrar cualquier pedido, indicando por qué canal llegó, incluso para un cliente que nunca ha usado la plataforma.

## 2. Historias de usuario
- **HU-REC-01** · Como recepcionista, quiero registrar un pedido que me hicieron por teléfono para que entre al flujo como cualquier otro.
- **HU-REC-02** · Como recepcionista, quiero crear la cuenta del cliente en el mismo paso si es nuevo.
- **HU-REC-03** · Como administrador, quiero saber por qué canal llega cada pedido.

## 3. Requisitos funcionales
- **RF-REC-01** · Recepción y el administrador deben poder registrar un pedido a nombre de un cliente existente o de un cliente nuevo (nombre, correo, celular y autorización de datos), indicando el canal: Llamada, Chat o Presencial. Los pedidos del cliente desde la web quedan con canal Web.
- **RF-REC-02** · Las áreas de Diseño, Producción y Calidad no pueden registrar pedidos.
- **RF-REC-03** · El pedido registrado por el personal guarda quién lo registró (`registrado_por`) y el cliente recibe una alerta de que su pedido fue creado.

## 4. Criterios de aceptación
- **CA-REC-01** (RF-REC-01) Dado un cliente nuevo que llama, cuando Recepción registra el pedido con canal Llamada, entonces se crean el cliente y el pedido con ese canal. ⟶ Pruebas: R01
- **CA-REC-02** (RF-REC-02) Dado un empleado de Producción, cuando abre el registro de pedidos, entonces se le niega el acceso. ⟶ Pruebas: R02
- **CA-REC-03** (RF-REC-03) Dado un pedido registrado por Recepción, cuando el cliente entra, entonces ve la alerta "Registramos tu pedido" y el detalle indica quién lo registró. ⟶ Verificación manual: registrar desde Recepción para el cliente demo y entrar como cliente.

## 5. Fuera de alcance
- Integración automática con WhatsApp o con la central telefónica (retirada por complejidad de configuración).
