-- BEIM Track IA · Fotos de perfil (mascota por rol)
-- Antes copia las imágenes en uploads/perfiles/
USE beimtrack_ia;
UPDATE usuarios SET foto_perfil = 'demo_admin.png'      WHERE correo = 'admin@beimtrack.com';
UPDATE usuarios SET foto_perfil = 'demo_recepcion.png'  WHERE correo = 'recepcion@beimtrack.com';
UPDATE usuarios SET foto_perfil = 'demo_diseno.png'     WHERE correo = 'diseno@beimtrack.com';
UPDATE usuarios SET foto_perfil = 'demo_produccion.png' WHERE correo = 'produccion@beimtrack.com';
UPDATE usuarios SET foto_perfil = 'demo_calidad.png'    WHERE correo = 'calidad@beimtrack.com';
-- Todos los clientes con la foto de cliente
UPDATE usuarios SET foto_perfil = 'demo_cliente.png'    WHERE rol = 'cliente';
