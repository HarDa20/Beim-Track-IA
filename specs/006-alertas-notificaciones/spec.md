# Especificación · 006 · Alertas internas y notificaciones por correo

**Código:** ALE · **Estado:** Implementada (correo desactivado hasta configurar SMTP) · **Origen:** Retroactiva (v2) · **Requisitos del documento original:** RF-27 a RF-29 (RF-26 WhatsApp retirado del alcance)

## 1. Problema y objetivo
Hoy los retrasos se descubren cuando el cliente llama molesto. El sistema debe **avisar a la persona correcta en el momento correcto**: dentro de la aplicación (campana de alertas) y al cliente por correo.

## 2. Historias de usuario
- **HU-ALE-01** · Como empleado, quiero enterarme cuando llega un pedido a mi área o me lo asignan.
- **HU-ALE-02** · Como cliente, quiero que me avisen cuando mi pedido cambia de estado, se retrasa o se entrega.
- **HU-ALE-03** · Como administrador, quiero saber qué avisos salieron y cuáles fallaron.

## 3. Requisitos funcionales
- **RF-ALE-01** · El sistema debe crear alertas internas: pedido nuevo → Recepción y administradores; cambio de estado → cliente y área siguiente; asignación → empleado asignado; entrega o cancelación → cliente y administradores.
- **RF-ALE-02** · El sistema debe detectar pedidos activos que vencen en 2 días o menos ("por vencer", una vez por día) y retrasados (una sola vez por pedido), sin tareas programadas del servidor, al cargar los paneles.
- **RF-ALE-03** · El sistema debe enviar correo al cliente cuando su pedido se registra, cambia de estado, se retrasa o se entrega, **después** de guardar el cambio, y registrar cada intento como `enviado`, `fallido` (con el error) u `omitido` (sin credenciales). Nunca se registra como enviado algo que no salió.

## 4. Criterios de aceptación
- **CA-ALE-01** (RF-ALE-01) Dado un pedido nuevo, entonces Recepción tiene una alerta "nuevo_pedido". ⟶ Pruebas: F01
- **CA-ALE-02** (RF-ALE-01) Dada una asignación, entonces el empleado recibe la alerta. ⟶ Pruebas: F08
- **CA-ALE-03** (RF-ALE-01) Dada una entrega, entonces el cliente recibe la alerta. ⟶ Pruebas: F11
- **CA-ALE-04** (RF-ALE-02) Dado un pedido activo con fecha vencida, cuando el administrador carga su panel, entonces recibe una alerta "retrasado". ⟶ Pruebas: D06
- **CA-ALE-05** (RF-ALE-03) Dado el correo sin credenciales, cuando ocurren eventos, entonces se registran como "omitido" y ninguno como "enviado". ⟶ Pruebas: N01
- **CA-ALE-06** (RF-ALE-03) Dada una cuenta SMTP real en `.env`, cuando se crea un pedido, entonces el cliente recibe el correo y el registro dice "enviado". ⟶ Verificación manual: pendiente de cuenta SMTP (ver README, sección 5).

## 5. Fuera de alcance
- WhatsApp Business (retirado por la complejidad de cuenta Meta Business, plantillas y tokens). La columna `canal` del registro permite agregarlo después.
- Notificaciones push del navegador.
