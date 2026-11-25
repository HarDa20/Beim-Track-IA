-- Esquema equivalente a database/schema.sql para ejecutar las pruebas
-- automáticas con SQLite (sin necesidad de MySQL). No se usa en producción.
PRAGMA foreign_keys = ON;

CREATE TABLE usuarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL CHECK (length(nombre) <= 120),
    correo TEXT NOT NULL UNIQUE CHECK (length(correo) <= 150),
    telefono TEXT,
    password_hash TEXT NOT NULL,
    rol TEXT NOT NULL DEFAULT 'cliente' CHECK (rol IN ('cliente','empleado','administrador')),
    area TEXT CHECK (area IS NULL OR area IN ('Recepción','Diseño','Producción','Calidad')),
    activo INTEGER NOT NULL DEFAULT 1,
    acepta_datos_en TEXT,
    ultimo_acceso TEXT,
    foto_perfil TEXT,
    creado_en TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categorias_precios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    categoria TEXT NOT NULL UNIQUE,
    descripcion TEXT,
    unidad TEXT NOT NULL DEFAULT 'm2',
    precio_por_unidad REAL NOT NULL CHECK (precio_por_unidad >= 0),
    activa INTEGER NOT NULL DEFAULT 1,
    actualizado_en TEXT
);

CREATE TABLE pedidos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cliente_id INTEGER NOT NULL REFERENCES usuarios(id) ON DELETE RESTRICT,
    empleado_id INTEGER REFERENCES usuarios(id) ON DELETE SET NULL,
    categoria_id INTEGER NOT NULL REFERENCES categorias_precios(id) ON DELETE RESTRICT,
    categoria TEXT NOT NULL,
    tipo_trabajo TEXT NOT NULL,
    cantidad REAL NOT NULL CHECK (cantidad > 0),
    unidad TEXT NOT NULL DEFAULT 'm2',
    fecha_entrega TEXT NOT NULL,
    notas TEXT,
    precio_unitario REAL NOT NULL DEFAULT 0,
    costo_estimado REAL NOT NULL DEFAULT 0,
    estado TEXT NOT NULL DEFAULT 'Recibido' CHECK (estado IN ('Borrador','Recibido','En diseño','En producción','Control de calidad','Listo para entrega','Entregado','Cancelado')),
    canal_recepcion TEXT NOT NULL DEFAULT 'Web' CHECK (canal_recepcion IN ('Web','Llamada','Chat','Presencial')),
    registrado_por INTEGER REFERENCES usuarios(id) ON DELETE SET NULL,
    fecha_entregado TEXT,
    creado_en TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TEXT
);

CREATE TABLE historial_pedido (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    pedido_id INTEGER NOT NULL REFERENCES pedidos(id) ON DELETE CASCADE,
    usuario_id INTEGER REFERENCES usuarios(id) ON DELETE SET NULL,
    estado_anterior TEXT,
    estado_nuevo TEXT,
    descripcion TEXT NOT NULL,
    fecha TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE archivos_pedido (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    pedido_id INTEGER NOT NULL REFERENCES pedidos(id) ON DELETE CASCADE,
    nombre_original TEXT NOT NULL,
    ruta TEXT NOT NULL,
    mime TEXT,
    tamano INTEGER,
    subido_por INTEGER REFERENCES usuarios(id) ON DELETE SET NULL,
    fecha_subida TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE alertas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id INTEGER NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    pedido_id INTEGER REFERENCES pedidos(id) ON DELETE CASCADE,
    tipo TEXT NOT NULL CHECK (tipo IN ('nuevo_pedido','asignado','cambio_estado','por_vencer','retrasado','finalizado')),
    mensaje TEXT NOT NULL,
    clave TEXT,
    leida INTEGER NOT NULL DEFAULT 0,
    creado_en TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (usuario_id, clave)
);

CREATE TABLE notificaciones_envios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    pedido_id INTEGER REFERENCES pedidos(id) ON DELETE SET NULL,
    canal TEXT NOT NULL CHECK (canal IN ('correo')),
    destinatario TEXT NOT NULL,
    evento TEXT NOT NULL,
    mensaje TEXT NOT NULL,
    estado TEXT NOT NULL CHECK (estado IN ('enviado','fallido','omitido')),
    detalle_error TEXT,
    creado_en TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE intentos_login (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    correo TEXT NOT NULL,
    ip TEXT NOT NULL,
    creado_en TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO categorias_precios (categoria, descripcion, unidad, precio_por_unidad, activa) VALUES
('Fachadas', 'Vinilos, lonas y letreros para exteriores', 'm2', 30000, 1),
('Interiores', 'Papel mural y vinilos decorativos', 'm2', 25000, 1),
('Gran formato', 'Pendones, banners y pasacalles', 'm2', 45000, 1);
