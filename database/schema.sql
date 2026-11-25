-- =====================================================================
-- BEIM Track · Esquema de base de datos v2 (MySQL 8 / MariaDB 10.4+)
-- Instalación NUEVA. Si ya tienes la base v1 con datos, NO uses este
-- archivo: ejecuta database/migraciones/001_v1_a_v2.sql.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS beimtrack_ia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE beimtrack_ia;

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Usuarios: clientes, empleados (por área) y administradores
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(120) NOT NULL,
    correo          VARCHAR(150) NOT NULL UNIQUE,
    telefono        VARCHAR(20)  DEFAULT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    rol             ENUM('cliente','empleado','administrador') NOT NULL DEFAULT 'cliente',
    area            ENUM('Recepción','Diseño','Producción','Calidad') DEFAULT NULL,
    activo          TINYINT(1) NOT NULL DEFAULT 1,
    acepta_datos_en DATETIME DEFAULT NULL,      -- autorización de tratamiento de datos (Ley 1581 de 2012)
    ultimo_acceso   DATETIME DEFAULT NULL,
    foto_perfil     VARCHAR(80)  DEFAULT NULL,  -- nombre aleatorio en uploads/perfiles/ (spec 013)
    creado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuarios_rol_area (rol, area, activo)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Categorías y precios (se conserva el nombre de tabla de la v1)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categorias_precios (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    categoria         VARCHAR(80)  NOT NULL UNIQUE,
    descripcion       VARCHAR(255) DEFAULT NULL,
    unidad            VARCHAR(20)  NOT NULL DEFAULT 'm2',
    precio_por_unidad DECIMAL(12,2) NOT NULL,
    activa            TINYINT(1) NOT NULL DEFAULT 1,
    actualizado_en    DATETIME DEFAULT NULL,
    CONSTRAINT chk_precio_positivo CHECK (precio_por_unidad >= 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Pedidos
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pedidos (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id       INT NOT NULL,
    empleado_id      INT DEFAULT NULL,              -- responsable de la etapa actual
    categoria_id     INT NOT NULL,
    categoria        VARCHAR(80) NOT NULL,          -- nombre de la categoría al momento del pedido
    tipo_trabajo     VARCHAR(200) NOT NULL,
    cantidad         DECIMAL(10,2) NOT NULL,
    unidad           VARCHAR(20) NOT NULL DEFAULT 'm2',
    fecha_entrega    DATE NOT NULL,
    notas            TEXT,
    precio_unitario  DECIMAL(12,2) NOT NULL DEFAULT 0, -- precio vigente al crear el pedido
    costo_estimado   DECIMAL(14,2) NOT NULL DEFAULT 0,
    estado           ENUM('Borrador','Recibido','En diseño','En producción','Control de calidad','Listo para entrega','Entregado','Cancelado') NOT NULL DEFAULT 'Recibido',
    canal_recepcion  ENUM('Web','Llamada','Chat','Presencial') NOT NULL DEFAULT 'Web',
    registrado_por   INT DEFAULT NULL,
    fecha_entregado  DATETIME DEFAULT NULL,
    creado_en        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en   DATETIME DEFAULT NULL,
    CONSTRAINT fk_pedidos_cliente    FOREIGN KEY (cliente_id)     REFERENCES usuarios(id)           ON DELETE RESTRICT,
    CONSTRAINT fk_pedidos_empleado   FOREIGN KEY (empleado_id)    REFERENCES usuarios(id)           ON DELETE SET NULL,
    CONSTRAINT fk_pedidos_categoria  FOREIGN KEY (categoria_id)   REFERENCES categorias_precios(id) ON DELETE RESTRICT,
    CONSTRAINT fk_pedidos_registrado FOREIGN KEY (registrado_por) REFERENCES usuarios(id)           ON DELETE SET NULL,
    CONSTRAINT chk_cantidad_positiva CHECK (cantidad > 0),
    INDEX idx_pedidos_estado (estado),
    INDEX idx_pedidos_entrega (fecha_entrega),
    INDEX idx_pedidos_creado (creado_en)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Historial (trazabilidad de cada pedido)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS historial_pedido (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id        INT NOT NULL,
    usuario_id       INT DEFAULT NULL,
    estado_anterior  VARCHAR(30) DEFAULT NULL,
    estado_nuevo     VARCHAR(30) DEFAULT NULL,
    descripcion      VARCHAR(255) NOT NULL,
    fecha            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_historial_pedido  FOREIGN KEY (pedido_id)  REFERENCES pedidos(id)  ON DELETE CASCADE,
    CONSTRAINT fk_historial_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_historial_fecha (fecha)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Archivos de diseño (varios por pedido)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS archivos_pedido (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id        INT NOT NULL,
    nombre_original  VARCHAR(255) NOT NULL,
    ruta             VARCHAR(255) NOT NULL,      -- nombre generado dentro de uploads/
    mime             VARCHAR(100) DEFAULT NULL,
    tamano           INT DEFAULT NULL,
    subido_por       INT DEFAULT NULL,
    fecha_subida     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_archivos_pedido  FOREIGN KEY (pedido_id)  REFERENCES pedidos(id)  ON DELETE CASCADE,
    CONSTRAINT fk_archivos_usuario FOREIGN KEY (subido_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Alertas internas
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS alertas (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT NOT NULL,
    pedido_id   INT DEFAULT NULL,
    tipo        ENUM('nuevo_pedido','asignado','cambio_estado','por_vencer','retrasado','finalizado') NOT NULL,
    mensaje     VARCHAR(255) NOT NULL,
    clave       VARCHAR(100) DEFAULT NULL,       -- evita alertas duplicadas (vencimientos)
    leida       TINYINT(1) NOT NULL DEFAULT 0,
    creado_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_alertas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_alertas_pedido  FOREIGN KEY (pedido_id)  REFERENCES pedidos(id)  ON DELETE CASCADE,
    UNIQUE KEY uq_alertas_clave (usuario_id, clave),
    INDEX idx_alertas_usuario (usuario_id, leida)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Registro de avisos por correo electrónico, incluidos los fallidos
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notificaciones_envios (
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

-- ---------------------------------------------------------------------
-- Intentos de inicio de sesión (protección contra fuerza bruta)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS intentos_login (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    correo    VARCHAR(150) NOT NULL,
    ip        VARCHAR(45)  NOT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_intentos (correo, creado_en)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- Datos iniciales: categorías y precios de referencia de la v1
-- ---------------------------------------------------------------------
INSERT INTO categorias_precios (categoria, descripcion, unidad, precio_por_unidad, activa) VALUES
('Fachadas',     'Vinilos, lonas y letreros para exteriores', 'm2', 30000, 1),
('Interiores',   'Papel mural y vinilos decorativos',         'm2', 25000, 1),
('Gran formato', 'Pendones, banners y pasacalles',            'm2', 45000, 1)
ON DUPLICATE KEY UPDATE categoria = categoria;

-- ---------------------------------------------------------------------
-- Cuentas de acceso para la demostración (CAMBIAR ANTES DE USAR EN LA EMPRESA)
-- Contraseñas: Admin2026*, Recepcion2026*, Diseno2026*, Produccion2026*,
--              Calidad2026*, Cliente2026*
-- No se cargan pedidos de ejemplo: los indicadores se calculan solo con
-- pedidos reales registrados en el sistema.
-- ---------------------------------------------------------------------
INSERT INTO usuarios (nombre, correo, telefono, password_hash, rol, area, activo, acepta_datos_en) VALUES
('Administrador BEIM',  'admin@beimtrack.com',      NULL, '$2y$10$2ueGiuxYGy/FWkZplz752.80h.JbPaGCoUxA20VcgrueeW4AMs8cS', 'administrador', NULL,         1, NOW()),
('Laura Recepción',     'recepcion@beimtrack.com',  NULL, '$2y$10$er3anhO8KK0sEWsQSfNMKeICq3nEUL5pVa2HjH27hmjTnBO9SQIpu', 'empleado',      'Recepción',  1, NOW()),
('Daniel Diseño',       'diseno@beimtrack.com',     NULL, '$2y$10$oRf1aWU7Vou9uzKpwq8TfO87CXuxADNLY8Di5Sp3vWSG8J/vzcWMq', 'empleado',      'Diseño',     1, NOW()),
('Andrea Producción',   'produccion@beimtrack.com', NULL, '$2y$10$2yxYuPjztO5t1cs6jUx1kuPvWr8jVOZrrIdo49zSJrGaZmtgihdbW', 'empleado',      'Producción', 1, NOW()),
('Camilo Calidad',      'calidad@beimtrack.com',    NULL, '$2y$10$i6x7dZPzx.J1JcyXn6KxX.Jkt9q.yoZ/Mdlp2LaTTbiYDD9yOVWOK', 'empleado',      'Calidad',    1, NOW()),
('Cliente Demo',        'cliente@correo.com',       NULL, '$2y$10$N62iJqfkvVLsTUXgVI76rucOxy/W/8hSV4EUuBIKHWsuCth9AYJoK', 'cliente',       NULL,         1, NOW())
ON DUPLICATE KEY UPDATE correo = correo;
