# Especificación · 013 · Foto de perfil

**Código:** FOT · **Estado:** Implementada · **Origen:** SDD completo (pedido del profesor, 22/11/2026)

## 1. Problema y objetivo
Con varias cuentas abiertas y varios empleados por área, las iniciales no bastan para reconocer a las personas. El profesor pidió **poner foto de perfil a los usuarios**.

## 2. Requisitos funcionales
- **RF-FOT-01** · Cada usuario puede subir su foto desde "Mi perfil": formatos JPG, PNG o WEBP, máximo 2 MB, con la firma real del archivo coincidiendo con la extensión. La nueva foto reemplaza a la anterior.
- **RF-FOT-02** · La foto se guarda con nombre aleatorio en `uploads/perfiles/` (carpeta sin acceso directo) y solo se entrega por `foto_perfil.php`, que verifica permisos: cada quien ve la suya; empleados y administradores ven todas; un cliente ve las del personal, pero no las de otros clientes.
- **RF-FOT-03** · La foto se muestra en la barra superior, en "Mi perfil", en el selector de cuentas y en la lista de usuarios del administrador. Si no hay foto se muestran las iniciales.
- **RF-FOT-04** · El usuario puede quitar su foto; el archivo se borra del servidor.

## 3. Criterios de aceptación
- **CA-FOT-01** (RF-FOT-01, RF-FOT-03) Dado un PNG real, cuando el cliente lo sube, entonces queda guardado y la barra superior muestra la foto. ⟶ Pruebas: U01
- **CA-FOT-02** (RF-FOT-01) Dado un archivo `.php` o un PNG falso, cuando se sube, entonces se rechaza. ⟶ Pruebas: U02
- **CA-FOT-03** (RF-FOT-01) Dado un archivo de más de 2 MB, cuando se sube, entonces se rechaza. ⟶ Pruebas: U05
- **CA-FOT-04** (RF-FOT-02) Dado otro cliente, cuando pide la foto del primer cliente, entonces se le niega; el dueño y el administrador sí la ven. ⟶ Pruebas: U03
- **CA-FOT-05** (RF-FOT-04) Dada una foto guardada, cuando el usuario la quita, entonces el archivo desaparece y vuelven las iniciales. ⟶ Pruebas: U04

## 4. Fuera de alcance
- Recortar o editar la foto dentro del sistema.
