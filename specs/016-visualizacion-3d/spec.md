# Especificación · 016 · Visualización 3D del pedido

**Código:** V3D · **Estado:** Implementada · **Origen:** SDD completo (pedido del equipo, 22/11/2026)

## 1. Problema y objetivo
El cliente no se imagina cómo quedará su diseño montado: un aviso en la fachada, un mural en la pared o un pendón. Muchas correcciones nacen de eso. Al crear el pedido, el cliente debe poder **ver su diseño en 3D sobre el tipo de producto que eligió y girarlo**, antes de enviarlo.

## 2. Historias de usuario
- **HU-V3D-01** · Como cliente, quiero ver mi diseño montado en 3D según la categoría que elegí, para saber cómo quedará.
- **HU-V3D-02** · Como empleado, quiero ver en 3D el diseño de un pedido ya registrado, para explicarle al cliente cómo quedará.

## 3. Requisitos funcionales
- **RF-V3D-01** · El formulario de pedido tiene la sección "Visualización 3D". El modelo cambia según la categoría elegida: **Fachadas** → local con letrero sobre la puerta; **Interiores** → pared de una habitación con mural; **Gran formato** → pendón en su soporte. Una categoría nueva usa el pendón hasta que se le asigne un modelo.
- **RF-V3D-02** · Al elegir un archivo JPG, PNG o WEBP, el diseño aparece sobre el modelo al instante, sin subirlo todavía al servidor. Con PDF o AI se muestra un marcador "Vista previa disponible solo para imágenes".
- **RF-V3D-03** · El modelo se gira arrastrando con el mouse o el dedo, con las flechas del teclado y con botones; tiene "Vista frontal", acercar/alejar y modo **día/noche** (de noche el letrero se ilumina).
- **RF-V3D-04** · El detalle de un pedido muestra la visualización 3D con la primera imagen del pedido, entregada por `archivo.php` con el mismo control de acceso.

## 4. Requisitos no funcionales
- **RNF-V3D-01** · Sin librerías: modelos hechos con CSS 3D y JavaScript propios (Art. V). Con "reducir movimiento" no hay giro automático.

## 5. Criterios de aceptación
- **CA-V3D-01** (RF-V3D-01) Dado el formulario de pedido, entonces cada categoría trae su modelo y la sección de visualización está presente. ⟶ Pruebas: V01
- **CA-V3D-02** (RF-V3D-01) Dadas las categorías Fachadas, Interiores, Gran formato y una nueva, entonces el modelo asignado es fachada, pared, pendón y pendón. ⟶ Pruebas: S17
- **CA-V3D-03** (RF-V3D-02, RF-V3D-03) Dado un PNG elegido en el formulario, entonces aparece sobre el modelo, que se puede girar y pasar a modo noche. ⟶ Verificación manual: crear un pedido, elegir una imagen y girar el modelo con el mouse, el dedo y las flechas.
- **CA-V3D-04** (RF-V3D-04) Dado un pedido con una imagen, cuando su dueño abre el detalle, entonces ve la visualización con la imagen servida en línea por `archivo.php`; otro cliente no puede obtenerla. ⟶ Pruebas: V02, V03
- **CA-V3D-05** (RNF-V3D-01) Dado el formulario con la visualización, entonces la página no carga ningún script ni hoja de estilos externos (solo los del sistema). ⟶ Pruebas: V04

## 6. Fuera de alcance
- Modelos 3D importados (GLB/OBJ, por ejemplo de Tripo 3D): requieren una librería 3D (three.js o model-viewer) y una enmienda de la constitución.
- Medidas reales del lugar de instalación.
