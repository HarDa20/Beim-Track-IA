# Especificación · 011 · Tema claro/oscuro y color de acento

**Código:** TEM · **Estado:** Implementada · **Origen:** SDD completo (pedido del profesor, 22/11/2026)

## 1. Problema y objetivo
El profesor pidió poder **cambiar el tono de la aplicación entre oscuro y claro**. Además, el diseño de referencia del inicio de sesión trae puntos de color para elegir el tono de la interfaz.

## 2. Requisitos funcionales
- **RF-TEM-01** · El usuario puede cambiar entre tema **oscuro** y **claro** desde el inicio de sesión y desde la barra superior. La elección se guarda en el navegador (cookie de un año) y el servidor la aplica al generar la página, sin parpadeo.
- **RF-TEM-02** · El usuario puede elegir el **color de acento** entre cian, violeta, rosa, ámbar y verde.
- **RF-TEM-03** · Por defecto el tema es oscuro y el acento cian. Cualquier valor que no esté en la lista se ignora.

## 3. Requisitos no funcionales
- **RNF-TEM-01** · En ambos temas el texto principal mantiene contraste suficiente para leerse (WCAG AA).

## 4. Criterios de aceptación
- **CA-TEM-01** (RF-TEM-01) Dada la cookie `tema=claro`, cuando se abre cualquier página, entonces el HTML trae `data-tema="claro"`. ⟶ Pruebas: T01
- **CA-TEM-02** (RF-TEM-03) Dada la cookie `tema=<script>`, entonces la página usa el tema oscuro y no refleja el valor. ⟶ Pruebas: T02, S11
- **CA-TEM-03** (RF-TEM-02) Dada la cookie `acento=rosa`, entonces el HTML trae `data-acento="rosa"`. ⟶ Pruebas: T03, S11
- **CA-TEM-04** (RNF-TEM-01) Dados ambos temas, cuando se revisan login, panel y formularios, entonces el texto se lee bien. ⟶ Verificación manual: revisar las capturas de `docs/capturas/` en tema claro y oscuro.

## 5. Fuera de alcance
- Guardar el tema en la base de datos por usuario.
