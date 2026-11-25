-- =====================================================================
-- BEIM Track · Verificación posterior a la instalación o migración
-- Cada consulta indica el resultado esperado.
-- =====================================================================
USE beimtrack_ia;

-- 1. Deben existir 8 tablas del sistema (más las respaldo_v1_* si migraste).
SELECT table_name FROM information_schema.tables
WHERE table_schema = 'beimtrack_ia' ORDER BY table_name;

-- 2. Esperado: 0. Pedidos sin categoría válida.
SELECT COUNT(*) AS pedidos_sin_categoria
FROM pedidos p LEFT JOIN categorias_precios c ON c.id = p.categoria_id
WHERE c.id IS NULL;

-- 3. Esperado: 0. Estados fuera del flujo v2.
SELECT COUNT(*) AS estados_invalidos FROM pedidos
WHERE estado NOT IN ('Borrador','Recibido','En diseño','En producción','Control de calidad','Listo para entrega','Entregado','Cancelado');

-- 4. Esperado: mismo número de pedidos antes y después (solo si migraste).
SELECT (SELECT COUNT(*) FROM pedidos) AS pedidos_v2,
       (SELECT COUNT(*) FROM respaldo_v1_pedidos) AS pedidos_v1;

-- 5. Esperado: mismo número de entradas de historial (solo si migraste).
SELECT (SELECT COUNT(*) FROM historial_pedido) AS historial_v2,
       (SELECT COUNT(*) FROM respaldo_v1_historial_pedido) AS historial_v1;

-- 6. Esperado: igual al número de pedidos v1 que tenían archivo adjunto.
SELECT (SELECT COUNT(*) FROM archivos_pedido) AS archivos_v2,
       (SELECT COUNT(*) FROM respaldo_v1_pedidos WHERE archivo_diseno IS NOT NULL AND archivo_diseno <> '') AS archivos_v1;

-- 7. Esperado: 0. Empleados sin área válida.
SELECT COUNT(*) AS empleados_sin_area FROM usuarios WHERE rol = 'empleado' AND area IS NULL;

-- 8. Esperado: la llave del cliente con DELETE_RULE = RESTRICT.
SELECT constraint_name, delete_rule FROM information_schema.referential_constraints
WHERE constraint_schema = 'beimtrack_ia' AND table_name = 'pedidos';
