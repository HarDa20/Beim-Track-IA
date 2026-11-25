# Especificación · 010 · Multisesión

**Código:** MSE · **Estado:** Implementada · **Origen:** SDD completo (pedido del profesor, 22/11/2026)

## 1. Problema y objetivo
Para demostrar el flujo entre áreas hoy hay que cerrar sesión y entrar con otra cuenta cada vez, o usar varios navegadores. El profesor pidió poder **abrir varias sesiones en el mismo navegador**: por ejemplo, el cliente en una pestaña, Recepción en otra y el administrador en una tercera.

## 2. Historias de usuario
- **HU-MSE-01** · Como persona que presenta el sistema, quiero tener abiertas varias cuentas a la vez en pestañas distintas para mostrar el flujo completo sin cerrar sesión.
- **HU-MSE-02** · Como usuario con dos cuentas, quiero cambiar de una a otra desde la barra superior.

## 3. Requisitos funcionales
- **RF-MSE-01** · Desde la barra superior se debe poder **agregar otra cuenta** sin cerrar la actual, hasta 5 cuentas por navegador. Si la cuenta ya está abierta, se reutiliza su ranura.
- **RF-MSE-02** · Cada cuenta ocupa una ranura numerada (0 a 4). La ranura viaja en la dirección (`?u=N`) y todos los enlaces, formularios y redirecciones la conservan, de modo que cada pestaña trabaja con su propia cuenta.
- **RF-MSE-03** · Un selector en la barra superior muestra las cuentas abiertas (foto o iniciales, nombre y rol) y abre cualquiera en una pestaña nueva.
- **RF-MSE-04** · "Cerrar sesión" cierra solo la cuenta de la pestaña actual; "Cerrar todas" cierra todas las cuentas del navegador.

## 4. Requisitos no funcionales
- **RNF-MSE-01** · Cada cuenta conserva todos los controles de 001: tiempo de inactividad propio, desactivación inmediata y permisos por rol. Una ranura vacía o inválida lleva al inicio de sesión.

## 5. Criterios de aceptación
- **CA-MSE-01** (RF-MSE-01, RF-MSE-02) Dado un cliente con sesión en la ranura 0, cuando agrega la cuenta del administrador, entonces el cliente sigue viendo su panel y el administrador ve el suyo con `?u=1` en el mismo navegador. ⟶ Pruebas: M01
- **CA-MSE-02** (RF-MSE-02) Dada la cuenta de la ranura 1, cuando abre su panel, entonces los enlaces y formularios de la página conservan `u=1`. ⟶ Pruebas: M02
- **CA-MSE-03** (RF-MSE-01) Dada una cuenta ya abierta, cuando se vuelve a agregar, entonces no ocupa una ranura nueva. ⟶ Pruebas: M03
- **CA-MSE-04** (RF-MSE-04) Dadas dos cuentas abiertas, cuando la ranura 1 cierra sesión, entonces la ranura 0 sigue activa; y "Cerrar todas" cierra ambas. ⟶ Pruebas: M04
- **CA-MSE-05** (RNF-MSE-01) Dada una ranura vacía (`?u=3`), cuando se abre una página privada, entonces va al inicio de sesión. ⟶ Pruebas: M05
- **CA-MSE-06** (RF-MSE-03) Dadas dos cuentas abiertas, cuando se abre el selector, entonces aparecen ambas con su rol. ⟶ Pruebas: M06

## 6. Fuera de alcance
- Sesiones compartidas entre navegadores o dispositivos distintos.
