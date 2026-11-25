-- =====================================================================
-- BEIM Track · Migración 001: esquema v1 -> v2 (MySQL 8 / MariaDB 10.4+)
--
-- QUÉ SOLUCIONA
--   * Categorías administrables con llave foránea (antes ENUM fijo).
--   * Historial estructurado: usuario, estado anterior y estado nuevo.
--   * Flujo por áreas: se agrega "En diseño", "Borrador" y "Cancelado";
--     "Confirmado" se convierte en "En diseño".
--   * Varios archivos por pedido (tabla archivos_pedido).
--   * Alertas internas, registro de avisos por correo e intentos de login.
--   * Usuarios con teléfono de contacto, estado activo/inactivo y área restringida.
--   * Fecha real de entrega para medir cumplimiento.
--   * Se elimina el borrado en cascada de pedidos al borrar un cliente
--     (conservación de registros, Ley 527 de 1999).
--
-- ANTES DE EJECUTAR
--   1. Haz un respaldo completo:  phpMyAdmin > Exportar  (o mysqldump).
--   2. Verifica el nombre de las llaves foráneas de "pedidos":
--          SHOW CREATE TABLE pedidos;
--      Este script asume los nombres automáticos pedidos_ibfk_1 (cliente_id)
--      y pedidos_ibfk_2 (empleado_id). Si son distintos, ajústalos abajo.
--   3. Ejecuta este archivo completo una sola vez.
--   4. Ejecuta database/verificacion.sql para comprobar el resultado.
-- =====================================================================

USE beimtrack_ia;

-- 0. Copias de seguridad dentro de la misma base --------------------------
CREATE TABLE respaldo_v1_usuarios           AS SELECT * FROM usuarios;
CREATE TABLE respaldo_v1_pedidos            AS SELECT * FROM pedidos;
CREATE TABLE respaldo_v1_historial_pedido   AS SELECT * FROM historial_pedido;
CREATE TABLE respaldo_v1_categorias_precios AS SELECT * FROM categorias_precios;

-- 1. Usuarios ------------------------------------------------------------
ALTER TABLE usuarios
    ADD COLUMN telefono        VARCHAR(20) NULL AFTER correo,
    ADD COLUMN activo          TINYINT(1) NOT NULL DEFAULT 1 AFTER area,
    ADD COLUMN acepta_datos_en DATETIME NULL AFTER activo,
    ADD COLUMN ultimo_acceso   DATETIME NULL AFTER acepta_datos_en,
    MODIFY creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

UPDATE usuarios SET area = 'Calidad'    WHERE rol = 'empleado' AND area IN ('Control de calidad', 'Calidad');
UPDATE usuarios SET area = 'Producción' WHERE rol = 'empleado' AND (area IS NULL OR area NOT IN ('Recepción','Diseño','Producción','Calidad'));
UPDATE usuarios SET area = NULL         WHERE rol <> 'empleado';

ALTER TABLE usuarios
    MODIFY area ENUM('Recepción','Diseño','Producción','Calidad') NULL,
    ADD INDEX idx_usuarios_rol_area (rol, area, activo);

-- 2. Categorías ----------------------------------------------------------
ALTER TABLE categorias_precios
    MODIFY categoria VARCHAR(80) NOT NULL,
    MODIFY precio_por_unidad DECIMAL(12,2) NOT NULL,
    ADD COLUMN descripcion    VARCHAR(255) NULL AFTER categoria,
    ADD COLUMN unidad         VARCHAR(20) NOT NULL DEFAULT 'm2' AFTER descripcion,
    ADD COLUMN activa         TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN actualizado_en DATETIME NULL;

UPDATE categorias_precios SET descripcion = 'Vinilos, lonas y letreros para exteriores' WHERE categoria = 'Fachadas';
UPDATE categorias_precios SET descripcion = 'Papel mural y vinilos decorativos'         WHERE categoria = 'Interiores';
UPDATE categorias_precios SET descripcion = 'Pendones, banners y pasacalles'            WHERE categoria = 'Gran formato';

-- 3. Pedidos -------------------------------------------------------------
ALTER TABLE pedidos
    ADD COLUMN categoria_id    INT NULL AFTER empleado_id,
    ADD COLUMN precio_unitario DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER notas,
    ADD COLUMN canal_recepcion ENUM('Web','Llamada','Chat','Presencial') NOT NULL DEFAULT 'Web' AFTER estado,
    ADD COLUMN registrado_por  INT NULL AFTER canal_recepcion,
    ADD COLUMN fecha_entregado DATETIME NULL AFTER registrado_por,
    ADD COLUMN actualizado_en  DATETIME NULL,
    MODIFY categoria VARCHAR(80) NOT NULL,
    MODIFY costo_estimado DECIMAL(14,2) NOT NULL DEFAULT 0,
    MODIFY creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

UPDATE pedidos p
JOIN categorias_precios c ON c.categoria = p.categoria
SET p.categoria_id = c.id,
    p.precio_unitario = c.precio_por_unidad;

UPDATE pedidos SET registrado_por = cliente_id, actualizado_en = creado_en;

-- Estados: se amplía el ENUM, se convierte "Confirmado" y luego se reduce.
ALTER TABLE pedidos MODIFY estado ENUM('Recibido','Confirmado','En producción','Control de calidad','Listo para entrega','Entregado','Borrador','En diseño','Cancelado') NOT NULL DEFAULT 'Recibido';
UPDATE pedidos SET estado = 'En diseño' WHERE estado = 'Confirmado';
ALTER TABLE pedidos MODIFY estado ENUM('Borrador','Recibido','En diseño','En producción','Control de calidad','Listo para entrega','Entregado','Cancelado') NOT NULL DEFAULT 'Recibido';

-- Fecha real de entrega a partir del historial v1 (si existe ese registro).
UPDATE pedidos
SET fecha_entregado = (
    SELECT MAX(h.fecha) FROM historial_pedido h
    WHERE h.pedido_id = pedidos.id AND h.descripcion LIKE 'Estado actualizado a "Entregado"%'
)
WHERE estado = 'Entregado';

ALTER TABLE pedidos MODIFY categoria_id INT NOT NULL;

-- Llaves foráneas: el cliente pasa de CASCADE a RESTRICT.
ALTER TABLE pedidos DROP FOREIGN KEY pedidos_ibfk_1;
ALTER TABLE pedidos DROP FOREIGN KEY pedidos_ibfk_2;
ALTER TABLE pedidos
    ADD CONSTRAINT fk_pedidos_cliente    FOREIGN KEY (cliente_id)     REFERENCES usuarios(id)           ON DELETE RESTRICT,
    ADD CONSTRAINT fk_pedidos_empleado   FOREIGN KEY (empleado_id)    REFERENCES usuarios(id)           ON DELETE SET NULL,
    ADD CONSTRAINT fk_pedidos_categoria  FOREIGN KEY (categoria_id)   REFERENCES categorias_precios(id) ON DELETE RESTRICT,
    ADD CONSTRAINT fk_pedidos_registrado FOREIGN KEY (registrado_por) REFERENCES usuarios(id)           ON DELETE SET NULL,
    ADD CONSTRAINT chk_cantidad_positiva CHECK (cantidad > 0),
    ADD INDEX idx_pedidos_estado (estado),
    ADD INDEX idx_pedidos_entrega (fecha_entrega),
    ADD INDEX idx_pedidos_creado (creado_en);

-- 4. Historial -----------------------------------------------------------
ALTER TABLE historial_pedido
    ADD COLUMN usuario_id      INT NULL AFTER pedido_id,
    ADD COLUMN estado_anterior VARCHAR(30) NULL AFTER usuario_id,
    ADD COLUMN estado_nuevo    VARCHAR(30) NULL AFTER estado_anterior,
    MODIFY fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ADD CONSTRAINT fk_historial_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    ADD INDEX idx_historial_fecha (fecha);

UPDATE historial_pedido SET estado_nuevo = 'Recibido' WHERE descripcion = 'Pedido recibido';
UPDATE historial_pedido
SET estado_nuevo = SUBSTRING_INDEX(SUBSTRING_INDEX(descripcion, '"', 2), '"', -1)
WHERE descripcion LIKE 'Estado actualizado a "%';
UPDATE historial_pedido SET estado_nuevo = 'En diseño' WHERE estado_nuevo = 'Confirmado';

-- 5. Archivos: se pasan de la columna única a la tabla nueva --------------
CREATE TABLE archivos_pedido (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id        INT NOT NULL,
    nombre_original  VARCHAR(255) NOT NULL,
    ruta             VARCHAR(255) NOT NULL,
    mime             VARCHAR(100) DEFAULT NULL,
    tamano           INT DEFAULT NULL,
    subido_por       INT DEFAULT NULL,
    fecha_subida     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_archivos_pedido  FOREIGN KEY (pedido_id)  REFERENCES pedidos(id)  ON DELETE CASCADE,
    CONSTRAINT fk_archivos_usuario FOREIGN KEY (subido_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO archivos_pedido (pedido_id, nombre_original, ruta, subido_por, fecha_subida)
SELECT id,
       SUBSTRING_INDEX(archivo_diseno, '/', -1),
       SUBSTRING_INDEX(archivo_diseno, '/', -1),
       cliente_id,
       creado_en
FROM pedidos
WHERE archivo_diseno IS NOT NULL AND archivo_diseno <> '';

ALTER TABLE pedidos DROP COLUMN archivo_diseno;

-- 6. Tablas nuevas -------------------------------------------------------
CREATE TABLE alertas (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT NOT NULL,
    pedido_id   INT DEFAULT NULL,
    tipo        ENUM('nuevo_pedido','asignado','cambio_estado','por_vencer','retrasado','finalizado') NOT NULL,
    mensaje     VARCHAR(255) NOT NULL,
    clave       VARCHAR(100) DEFAULT NULL,
    leida       TINYINT(1) NOT NULL DEFAULT 0,
    creado_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_alertas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_alertas_pedido  FOREIGN KEY (pedido_id)  REFERENCES pedidos(id)  ON DELETE CASCADE,
    UNIQUE KEY uq_alertas_clave (usuario_id, clave),
    INDEX idx_alertas_usuario (usuario_id, leida)
) ENGINE=InnoDB;

CREATE TABLE notificaciones_envios (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id     INT DEFAULT NULL,
    canal         ENUM('correo') NOT NULL DEFAULT 'correo',
    destinatario  VARCHAR(150) NOT NULL,
    evento        VARCHAR(40) NOT NULL,
    mensaje       VARCHAR(500) NOT NULL,
    estado        ENUM('enviado','fallido','omitido') NOT NULL,
    detalle_error VARCHAR(500) DEFAULT NULL,
    creado_en     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_envios_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE SET NULL,
    INDEX idx_envios_estado (estado)
) ENGINE=InnoDB;

CREATE TABLE intentos_login (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    correo    VARCHAR(150) NOT NULL,
    ip        VARCHAR(45)  NOT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_intentos (correo, creado_en)
) ENGINE=InnoDB;

-- Fin de la migración. Ejecuta ahora database/verificacion.sql.
-- Cuando confirmes que todo está bien puedes borrar las tablas respaldo_v1_*.
