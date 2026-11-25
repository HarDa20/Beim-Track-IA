# Especificación · 007 · Archivos de diseño

**Código:** ARC · **Estado:** Implementada · **Origen:** Retroactiva (v2) · **Requisitos del documento original:** RF-07, RF-11

## 1. Problema y objetivo
Los diseños llegan por chat y se pierden. Cada pedido debe guardar sus archivos (logos, artes, referencias) de forma **segura**: solo los formatos de diseño, sin permitir subir código, y visibles solo para quien tiene acceso al pedido.

## 2. Requisitos funcionales
- **RF-ARC-01** · Solo se aceptan archivos `pdf`, `ai`, `jpg`, `jpeg`, `png` y `webp`, de máximo 10 MB cada uno y hasta 5 por envío; la extensión debe coincidir con la **firma real** del contenido.
- **RF-ARC-02** · Los archivos se guardan con nombre aleatorio en `uploads/`, que no es accesible desde el navegador; se descargan solo por `archivo.php`, que verifica que el usuario pueda ver el pedido.
- **RF-ARC-03** · Quien puede ver un pedido activo puede agregarle archivos adicionales, y queda en el historial.

## 3. Criterios de aceptación
- **CA-ARC-01** (RF-ARC-01) Dado un archivo `.php`, cuando se adjunta, entonces se rechaza con "no es un formato permitido". ⟶ Pruebas: P06
- **CA-ARC-02** (RF-ARC-01) Dado un `.png` con contenido de texto, cuando se adjunta, entonces se rechaza con "no corresponde". ⟶ Pruebas: P07
- **CA-ARC-03** (RF-ARC-01) Dadas las firmas reales de PNG, PDF, JPG y WEBP y contenidos falsos, cuando se validan, entonces solo se aceptan las reales. ⟶ Pruebas: S07
- **CA-ARC-04** (RF-ARC-02) Dado el dueño del pedido, cuando descarga su archivo, entonces obtiene HTTP 200 y el contenido idéntico. ⟶ Pruebas: P12
- **CA-ARC-05** (RF-ARC-02) Dado otro cliente, cuando intenta descargar el archivo, entonces se le niega. ⟶ Pruebas: P11
- **CA-ARC-06** (RF-ARC-02) Dada una petición directa a `/uploads/`, entonces responde 403. ⟶ Pruebas: P13
- **CA-ARC-07** (RF-ARC-03) Dado un pedido en Diseño, cuando el diseñador sube una versión nueva del arte, entonces aparece en el detalle y en el historial. ⟶ Verificación manual: subir un PDF desde el detalle del pedido.

## 4. Fuera de alcance
- Vista previa de archivos `.ai`.
- Antivirus sobre los archivos subidos.
