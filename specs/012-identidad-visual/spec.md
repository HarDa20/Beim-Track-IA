# Especificación · 012 · Inicio de sesión con lámpara y logo de la empresa

**Código:** IDV · **Estado:** Implementada · **Origen:** SDD completo (diseño de referencia aportado por el equipo, 22/11/2026)

## 1. Problema y objetivo
El inicio de sesión es lo primero que verá el jurado en la feria. El equipo eligió un diseño de referencia: **una habitación oscura con una lámpara de escritorio; al tirar del cordón la lámpara se enciende y su luz revela el formulario**. Además, el profesor pidió poner el logo de la empresa.

## 2. Requisitos funcionales
- **RF-IDV-01** · El inicio de sesión muestra una lámpara en una escena oscura con la indicación "Tira del cordón". Al tirar del cordón (clic, arrastre hacia abajo, toque o tecla Enter/Espacio) la lámpara se enciende con un cono de luz y el formulario aparece con una transición. Tirar de nuevo la apaga.
- **RF-IDV-02** · El formulario nunca queda inaccesible: sin JavaScript, con "reducir movimiento" activo o cuando hay mensajes que mostrar (por ejemplo, un error de contraseña), la página carga con la lámpara encendida.
- **RF-IDV-03** · El logo de la empresa aparece en el inicio de sesión, en el menú lateral y como ícono de la pestaña. Se reemplaza poniendo la imagen real en `img/logo.png`; si no existe, se usa el logo vectorial del sistema.
- **RF-IDV-04** · Si existe `img/logo_ancho.png` (mascota con el nombre "BEIM Track"), se muestra en el menú lateral y en el ingreso en lugar del logo pequeño y el nombre escrito.

## 3. Criterios de aceptación
- **CA-IDV-01** (RF-IDV-01, RF-IDV-03) Dada la página de ingreso sin mensajes, entonces trae la lámpara, el cordón accesible por teclado, el logo y el formulario dentro de la escena apagada. ⟶ Pruebas: L01
- **CA-IDV-02** (RF-IDV-02) Dado un intento con contraseña incorrecta, cuando vuelve al ingreso, entonces la lámpara ya está encendida y el error es visible. ⟶ Pruebas: L02
- **CA-IDV-03** (RF-IDV-01) Dada la página en un navegador, cuando se tira del cordón, entonces la luz se enciende y aparece el formulario. ⟶ Verificación manual: probar con mouse, con el dedo en el celular y con la tecla Tab + Enter.
- **CA-IDV-04** (RF-IDV-03) Dado un archivo `img/logo.png`, cuando se recarga, entonces el sistema muestra ese logo. ⟶ Pruebas: L03
- **CA-IDV-05** (RF-IDV-04) Dado un archivo `img/logo_ancho.png`, cuando se abre el ingreso, entonces se ve ese logo horizontal en la marca. ⟶ Pruebas: L04

## 4. Fuera de alcance
- Modelos 3D reales (por ejemplo, generados con Tripo 3D): requieren una librería de visualización 3D; se deja como mejora futura.
