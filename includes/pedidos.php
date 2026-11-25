<?php
// Servicio de pedidos: reglas del negocio, flujo de estados por área,
// historial y validaciones. Todas las páginas usan estas funciones para que
// las reglas existan en un solo lugar del servidor.

const ESTADOS_PEDIDO = ['Borrador', 'Recibido', 'En diseño', 'En producción', 'Control de calidad', 'Listo para entrega', 'Entregado', 'Cancelado'];
const ESTADOS_ACTIVOS = ['Recibido', 'En diseño', 'En producción', 'Control de calidad', 'Listo para entrega'];
const AREAS = ['Recepción', 'Diseño', 'Producción', 'Calidad'];
const CANALES = ['Web', 'Llamada', 'Chat', 'Presencial'];
const MAX_CANTIDAD = 100000;

function hoy(): string
{
    return date('Y-m-d');
}

function ahora(): string
{
    return date('Y-m-d H:i:s');
}

/**
 * Área responsable de cada etapa del flujo.
 *
 * @spec RF-FLU-02
 */
function areaDeEstado(?string $estado): ?string
{
    return [
        'Recibido'           => 'Recepción',
        'En diseño'          => 'Diseño',
        'En producción'      => 'Producción',
        'Control de calidad' => 'Calidad',
        'Listo para entrega' => 'Recepción',
    ][$estado] ?? null;
}

/**
 * Transiciones válidas del flujo (estado actual => [estado nuevo => acción]).
 *
 * @spec RF-FLU-01
 */
function transiciones(): array
{
    return [
        'Recibido'           => ['En diseño' => 'Enviar a diseño', 'Cancelado' => 'Cancelar pedido'],
        'En diseño'          => ['En producción' => 'Aprobar diseño y enviar a producción'],
        'En producción'      => ['Control de calidad' => 'Enviar a control de calidad'],
        'Control de calidad' => ['Listo para entrega' => 'Aprobar calidad', 'En producción' => 'Devolver a producción'],
        'Listo para entrega' => ['Entregado' => 'Registrar entrega al cliente'],
    ];
}

/**
 * Transiciones que exigen una observación (motivo).
 *
 * @spec RF-FLU-05
 */
function transicionRequiereObservacion(string $actual, string $nuevo): bool
{
    return $nuevo === 'Cancelado' || ($actual === 'Control de calidad' && $nuevo === 'En producción');
}

/**
 * Acciones de cambio de estado que el usuario puede ejecutar sobre el pedido.
 * - Administrador: cualquier transición válida y cancelar pedidos activos.
 * - Empleado: solo si la etapa actual pertenece a su área y el pedido no
 *   tiene otro responsable asignado. Solo Recepción puede cancelar.
 * - Cliente: puede cancelar su propio pedido mientras está "Recibido".
 * @spec RF-FLU-02, RF-FLU-03, RF-PED-06
 */
function accionesDisponibles(array $u, array $p): array
{
    $estado = $p['estado'];
    $base = transiciones()[$estado] ?? [];

    if ($u['rol'] === 'cliente') {
        return ((int) $p['cliente_id'] === (int) $u['id'] && $estado === 'Recibido')
            ? ['Cancelado' => 'Cancelar mi pedido'] : [];
    }

    if (esAdmin($u)) {
        if (in_array($estado, ESTADOS_ACTIVOS, true)) {
            $base['Cancelado'] = 'Cancelar pedido';
        }
        return $base;
    }

    if ($u['rol'] !== 'empleado' || $u['area'] !== areaDeEstado($estado)) {
        return [];
    }
    if (!empty($p['empleado_id']) && (int) $p['empleado_id'] !== (int) $u['id']) {
        return [];
    }
    if ($u['area'] !== 'Recepción') {
        unset($base['Cancelado']);
    }
    return $base;
}

// ---------------------------------------------------------------------
// Consulta y permisos
// ---------------------------------------------------------------------
function obtenerPedido(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT p.*, c.nombre AS cliente_nombre, c.correo AS cliente_correo, c.telefono AS cliente_telefono,
                e.nombre AS empleado_nombre, e.area AS empleado_area, r.nombre AS registrado_nombre, r.rol AS registrado_rol
         FROM pedidos p
         JOIN usuarios c ON c.id = p.cliente_id
         LEFT JOIN usuarios e ON e.id = p.empleado_id
         LEFT JOIN usuarios r ON r.id = p.registrado_por
         WHERE p.id = ?'
    );
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/** @spec RF-PED-04, RF-FLU-02 */
function puedeVerPedido(PDO $pdo, array $u, array $p): bool
{
    if (esAdmin($u)) {
        return true;
    }
    if ($u['rol'] === 'cliente') {
        return (int) $p['cliente_id'] === (int) $u['id'];
    }
    if ($p['estado'] === 'Borrador') {
        return false;
    }
    if ((int) $p['empleado_id'] === (int) $u['id']
        || (int) $p['registrado_por'] === (int) $u['id']
        || areaDeEstado($p['estado']) === $u['area']) {
        return true;
    }
    // Empleados que ya trabajaron en el pedido pueden consultarlo.
    $stmt = $pdo->prepare('SELECT 1 FROM historial_pedido WHERE pedido_id = ? AND usuario_id = ? LIMIT 1');
    $stmt->execute([$p['id'], $u['id']]);
    return (bool) $stmt->fetchColumn();
}

/** @spec RF-PED-05 */
function historialPedido(PDO $pdo, int $pedidoId): array
{
    $stmt = $pdo->prepare(
        'SELECT h.*, u.nombre AS usuario_nombre, u.rol AS usuario_rol, u.area AS usuario_area
         FROM historial_pedido h LEFT JOIN usuarios u ON u.id = h.usuario_id
         WHERE h.pedido_id = ? ORDER BY h.fecha DESC, h.id DESC'
    );
    $stmt->execute([$pedidoId]);
    return $stmt->fetchAll();
}

/** @spec RF-FLU-06 */
function registrarHistorial(PDO $pdo, int $pedidoId, ?int $usuarioId, ?string $anterior, ?string $nuevo, string $descripcion): void
{
    $pdo->prepare('INSERT INTO historial_pedido (pedido_id, usuario_id, estado_anterior, estado_nuevo, descripcion, fecha) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([$pedidoId, $usuarioId, $anterior, $nuevo, mb_substr($descripcion, 0, 255), ahora()]);
}

function categoriasActivas(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM categorias_precios WHERE activa = 1 ORDER BY categoria')->fetchAll();
}

/**
 * Lista de pedidos con filtros. $filtros admite: estado, categoria_id,
 * empleado_id, q (texto o número), retrasados (bool), cliente_id, area,
 * incluir_borradores (bool).
 * @spec RF-ADM-02
 */
function consultarPedidos(PDO $pdo, array $filtros = [], int $limite = 500): array
{
    $sql = 'SELECT p.*, c.nombre AS cliente_nombre, e.nombre AS empleado_nombre
            FROM pedidos p
            JOIN usuarios c ON c.id = p.cliente_id
            LEFT JOIN usuarios e ON e.id = p.empleado_id
            WHERE 1 = 1';
    $params = [];

    if (empty($filtros['incluir_borradores'])) {
        $sql .= " AND p.estado <> 'Borrador'";
    }
    if (!empty($filtros['cliente_id'])) {
        $sql .= ' AND p.cliente_id = ?';
        $params[] = (int) $filtros['cliente_id'];
    }
    if (!empty($filtros['estado']) && in_array($filtros['estado'], ESTADOS_PEDIDO, true)) {
        $sql .= ' AND p.estado = ?';
        $params[] = $filtros['estado'];
    }
    if (!empty($filtros['estados'])) {
        $lista = array_values(array_intersect((array) $filtros['estados'], ESTADOS_PEDIDO));
        if ($lista) {
            $sql .= ' AND p.estado IN (' . implode(',', array_fill(0, count($lista), '?')) . ')';
            array_push($params, ...$lista);
        }
    }
    if (!empty($filtros['categoria_id'])) {
        $sql .= ' AND p.categoria_id = ?';
        $params[] = (int) $filtros['categoria_id'];
    }
    if (isset($filtros['empleado_id']) && $filtros['empleado_id'] !== '') {
        if ($filtros['empleado_id'] === 'ninguno') {
            $sql .= ' AND p.empleado_id IS NULL';
        } else {
            $sql .= ' AND p.empleado_id = ?';
            $params[] = (int) $filtros['empleado_id'];
        }
    }
    if (!empty($filtros['retrasados'])) {
        $sql .= " AND p.estado IN ('" . implode("','", ESTADOS_ACTIVOS) . "') AND p.fecha_entrega < ?";
        $params[] = hoy();
    }
    if (!empty($filtros['q'])) {
        $q = trim((string) $filtros['q']);
        $numero = (int) preg_replace('/\D/', '', $q);
        $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $q) . '%';
        $sql .= " AND (p.id = ? OR c.nombre LIKE ? ESCAPE '!' OR p.tipo_trabajo LIKE ? ESCAPE '!')";
        array_push($params, $numero, $like, $like);
    }
    $sql .= ' ORDER BY p.creado_en DESC, p.id DESC LIMIT ' . (int) $limite;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// ---------------------------------------------------------------------
// Validación y registro de pedidos
// ---------------------------------------------------------------------
function validarFecha(string $valor): ?string
{
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);
    return ($d && $d->format('Y-m-d') === $valor) ? $valor : null;
}

/**
 * Devuelve [errores, datos normalizados].
 *
 * @spec RF-PED-01, RF-PED-02
 */
function validarDatosPedido(PDO $pdo, array $src): array
{
    $errores = [];
    $datos = [];

    $categoriaId = (int) ($src['categoria_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM categorias_precios WHERE id = ? AND activa = 1');
    $stmt->execute([$categoriaId]);
    $categoria = $stmt->fetch();
    if (!$categoria) {
        $errores[] = 'Selecciona una categoría válida.';
    }

    $tipo = trim((string) ($src['tipo_trabajo'] ?? ''));
    if (mb_strlen($tipo) < 3 || mb_strlen($tipo) > 200) {
        $errores[] = 'El nombre del trabajo debe tener entre 3 y 200 caracteres.';
    }

    $cantidadTxt = str_replace(',', '.', trim((string) ($src['cantidad'] ?? '')));
    $cantidad = is_numeric($cantidadTxt) ? round((float) $cantidadTxt, 2) : 0;
    if ($cantidad <= 0 || $cantidad > MAX_CANTIDAD) {
        $errores[] = 'La cantidad debe ser mayor que 0 y menor o igual a ' . number_format(MAX_CANTIDAD, 0, ',', '.') . '.';
    }

    $fecha = validarFecha(trim((string) ($src['fecha_entrega'] ?? '')));
    if (!$fecha) {
        $errores[] = 'Indica una fecha de entrega válida.';
    } elseif ($fecha < hoy()) {
        $errores[] = 'La fecha de entrega no puede ser anterior a hoy.';
    } elseif ($fecha > date('Y-m-d', strtotime('+1 year'))) {
        $errores[] = 'La fecha de entrega no puede superar un año.';
    }

    $notas = trim((string) ($src['notas'] ?? ''));
    if (mb_strlen($notas) > 2000) {
        $errores[] = 'La descripción no puede superar 2.000 caracteres.';
    }

    if (!$errores) {
        $datos = [
            'categoria_id'    => (int) $categoria['id'],
            'categoria'       => $categoria['categoria'],
            'unidad'          => $categoria['unidad'],
            'precio_unitario' => (float) $categoria['precio_por_unidad'],
            'tipo_trabajo'    => $tipo,
            'cantidad'        => $cantidad,
            'fecha_entrega'   => $fecha,
            'notas'           => $notas,
            'costo_estimado'  => round((float) $categoria['precio_por_unidad'] * $cantidad, 2),
        ];
    }
    return [$errores, $datos];
}

/**
 * Crea un pedido nuevo o actualiza un borrador, en una sola transacción
 * junto con su historial, sus archivos y sus alertas.
 * @spec RF-PED-01, RF-PED-02, RF-PED-03, RF-REC-01, RF-REC-03
 */
function guardarPedido(PDO $pdo, array $actor, int $clienteId, array $datos, string $canal, bool $borrador, array $archivos, ?int $borradorId = null): int
{
    $estado = $borrador ? 'Borrador' : 'Recibido';
    $canal = in_array($canal, CANALES, true) ? $canal : 'Web';
    $ahora = ahora();
    $archivosMovidos = [];

    $pdo->beginTransaction();
    try {
        if ($borradorId) {
            $stmt = $pdo->prepare(
                "UPDATE pedidos SET categoria_id = ?, categoria = ?, tipo_trabajo = ?, cantidad = ?, unidad = ?, fecha_entrega = ?,
                        notas = ?, precio_unitario = ?, costo_estimado = ?, estado = ?, actualizado_en = ?, creado_en = ?
                 WHERE id = ? AND cliente_id = ? AND estado = 'Borrador'"
            );
            $stmt->execute([
                $datos['categoria_id'], $datos['categoria'], $datos['tipo_trabajo'], $datos['cantidad'], $datos['unidad'],
                $datos['fecha_entrega'], $datos['notas'], $datos['precio_unitario'], $datos['costo_estimado'], $estado, $ahora,
                $ahora, $borradorId, $clienteId,
            ]);
            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('El borrador no existe o ya fue enviado.');
            }
            $pedidoId = $borradorId;
            if (!$borrador) {
                registrarHistorial($pdo, $pedidoId, (int) $actor['id'], 'Borrador', 'Recibido', 'Pedido enviado por el cliente desde un borrador');
            }
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO pedidos (cliente_id, categoria_id, categoria, tipo_trabajo, cantidad, unidad, fecha_entrega, notas,
                                      precio_unitario, costo_estimado, estado, canal_recepcion, registrado_por, creado_en, actualizado_en)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $clienteId, $datos['categoria_id'], $datos['categoria'], $datos['tipo_trabajo'], $datos['cantidad'], $datos['unidad'],
                $datos['fecha_entrega'], $datos['notas'], $datos['precio_unitario'], $datos['costo_estimado'], $estado, $canal,
                (int) $actor['id'], $ahora, $ahora,
            ]);
            $pedidoId = (int) $pdo->lastInsertId();
            if ($borrador) {
                registrarHistorial($pdo, $pedidoId, (int) $actor['id'], null, 'Borrador', 'Borrador creado por el cliente');
            } else {
                $quien = $actor['rol'] === 'cliente' ? 'el cliente desde la web' : $actor['nombre'] . ' (canal: ' . $canal . ')';
                registrarHistorial($pdo, $pedidoId, (int) $actor['id'], null, 'Recibido', 'Pedido registrado por ' . $quien);
            }
        }

        foreach ($archivos as $archivo) {
            $archivosMovidos[] = guardarArchivo($pdo, $pedidoId, $archivo, (int) $actor['id']);
        }

        if (!$borrador) {
            $codigo = codigoPedido($pedidoId);
            alertarArea($pdo, 'Recepción', $pedidoId, 'nuevo_pedido', "Nuevo pedido $codigo: {$datos['tipo_trabajo']}");
            alertarAdministradores($pdo, $pedidoId, 'nuevo_pedido', "Nuevo pedido $codigo registrado ({$datos['categoria']})");
            if ($actor['rol'] !== 'cliente') {
                crearAlerta($pdo, $clienteId, $pedidoId, 'nuevo_pedido', "Registramos tu pedido $codigo. Puedes seguir su avance aquí.");
            }
        }
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        foreach ($archivosMovidos as $ruta) {
            @unlink(directorioUploads() . $ruta);
        }
        throw $ex;
    }

    if (!$borrador) {
        $pedido = obtenerPedido($pdo, $pedidoId);
        notificarCliente($pdo, $pedido, 'pedido_registrado',
            'BEIM Track: registramos tu pedido ' . codigoPedido($pedidoId) . ' (' . $datos['tipo_trabajo'] . '). Entrega estimada: ' . formatoFecha($datos['fecha_entrega']) . '.');
    }
    return $pedidoId;
}

/** @spec RF-PED-03 */
function eliminarBorrador(PDO $pdo, array $cliente, int $id): bool
{
    $stmt = $pdo->prepare("SELECT id FROM pedidos WHERE id = ? AND cliente_id = ? AND estado = 'Borrador'");
    $stmt->execute([$id, $cliente['id']]);
    if (!$stmt->fetch()) {
        return false;
    }
    $rutas = $pdo->prepare('SELECT ruta FROM archivos_pedido WHERE pedido_id = ?');
    $rutas->execute([$id]);
    $lista = $rutas->fetchAll(PDO::FETCH_COLUMN);

    $pdo->prepare("DELETE FROM pedidos WHERE id = ? AND cliente_id = ? AND estado = 'Borrador'")->execute([$id, $cliente['id']]);
    foreach ($lista as $ruta) {
        @unlink(directorioUploads() . basename($ruta));
    }
    return true;
}

// ---------------------------------------------------------------------
// Flujo de producción
// ---------------------------------------------------------------------
/**
 * Devuelve [ok, mensaje].
 *
 * @spec RF-FLU-01, RF-FLU-04, RF-FLU-05, RF-FLU-06, RF-FLU-07, RF-FLU-08
 */
function cambiarEstado(PDO $pdo, array $u, int $pedidoId, string $nuevo, string $observacion = ''): array
{
    $p = obtenerPedido($pdo, $pedidoId);
    if (!$p) {
        return [false, 'El pedido no existe.'];
    }
    if (!puedeVerPedido($pdo, $u, $p)) {
        return [false, 'No tienes acceso a este pedido.'];
    }
    $acciones = accionesDisponibles($u, $p);
    if (!isset($acciones[$nuevo])) {
        return [false, 'Cambio no permitido: el pedido está en "' . $p['estado'] . '" y no puedes pasarlo a "' . $nuevo . '".'];
    }
    $observacion = trim(mb_substr($observacion, 0, 150));
    if (transicionRequiereObservacion($p['estado'], $nuevo) && $observacion === '') {
        return [false, 'Escribe el motivo para realizar este cambio.'];
    }

    $anterior = $p['estado'];
    $areaActual = areaDeEstado($anterior);
    $areaNueva = areaDeEstado($nuevo);
    // Al pasar a otra área, el pedido queda en la bandeja de esa área sin responsable.
    $responsable = ($areaNueva !== null && $areaNueva === $areaActual) ? $p['empleado_id'] : null;
    $fechaEntregado = $nuevo === 'Entregado' ? ahora() : $p['fecha_entregado'];
    $codigo = codigoPedido($pedidoId);

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('UPDATE pedidos SET estado = ?, empleado_id = ?, fecha_entregado = ?, actualizado_en = ? WHERE id = ? AND estado = ?');
        $stmt->execute([$nuevo, $responsable, $fechaEntregado, ahora(), $pedidoId, $anterior]);
        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            return [false, 'Otra persona actualizó este pedido. Recarga la página.'];
        }

        $desc = $u['nombre'] . ' cambió el estado de "' . $anterior . '" a "' . $nuevo . '"';
        if ($observacion !== '') {
            $desc .= '. Motivo: ' . $observacion;
        }
        registrarHistorial($pdo, $pedidoId, (int) $u['id'], $anterior, $nuevo, $desc);

        if ($nuevo === 'Entregado') {
            crearAlerta($pdo, (int) $p['cliente_id'], $pedidoId, 'finalizado', "Tu pedido $codigo fue entregado. ¡Gracias por confiar en nosotros!");
            alertarAdministradores($pdo, $pedidoId, 'finalizado', "Pedido $codigo entregado al cliente", (int) $u['id']);
        } elseif ($nuevo === 'Cancelado') {
            crearAlerta($pdo, (int) $p['cliente_id'], $pedidoId, 'cambio_estado', "Tu pedido $codigo fue cancelado.");
            alertarAdministradores($pdo, $pedidoId, 'cambio_estado', "Pedido $codigo cancelado por {$u['nombre']}", (int) $u['id']);
        } else {
            crearAlerta($pdo, (int) $p['cliente_id'], $pedidoId, 'cambio_estado', "Tu pedido $codigo avanzó a: $nuevo");
            if ($areaNueva) {
                alertarArea($pdo, $areaNueva, $pedidoId, 'cambio_estado', "El pedido $codigo llegó a $areaNueva ($nuevo)", (int) $u['id']);
            }
        }
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }

    $evento = $nuevo === 'Entregado' ? 'pedido_finalizado' : 'cambio_estado';
    notificarCliente($pdo, obtenerPedido($pdo, $pedidoId), $evento,
        "BEIM Track: tu pedido $codigo ahora está en \"$nuevo\".");

    return [true, 'Pedido ' . $codigo . ' actualizado a "' . $nuevo . '".'];
}

/**
 * Un empleado toma un pedido de la bandeja de su área.
 *
 * @spec RF-FLU-03
 */
function tomarPedido(PDO $pdo, array $u, int $pedidoId): array
{
    $p = obtenerPedido($pdo, $pedidoId);
    if (!$p || $u['rol'] !== 'empleado') {
        return [false, 'Pedido no disponible.'];
    }
    if (areaDeEstado($p['estado']) !== $u['area']) {
        return [false, 'Este pedido no está en una etapa de tu área.'];
    }
    if (!empty($p['empleado_id'])) {
        return [false, (int) $p['empleado_id'] === (int) $u['id'] ? 'Ya eres el responsable de este pedido.' : 'Otro compañero ya tomó este pedido.'];
    }
    $stmt = $pdo->prepare('UPDATE pedidos SET empleado_id = ?, actualizado_en = ? WHERE id = ? AND empleado_id IS NULL AND estado = ?');
    $stmt->execute([$u['id'], ahora(), $pedidoId, $p['estado']]);
    if ($stmt->rowCount() !== 1) {
        return [false, 'Otro compañero tomó el pedido primero.'];
    }
    registrarHistorial($pdo, $pedidoId, (int) $u['id'], null, null, $u['nombre'] . ' tomó el pedido (' . $u['area'] . ')');
    return [true, 'Ahora eres responsable del pedido ' . codigoPedido($pedidoId) . '.'];
}

/**
 * El administrador asigna (o quita) el responsable de la etapa actual.
 *
 * @spec RF-ADM-03
 */
function asignarResponsable(PDO $pdo, array $admin, int $pedidoId, ?int $empleadoId): array
{
    $p = obtenerPedido($pdo, $pedidoId);
    if (!$p) {
        return [false, 'El pedido no existe.'];
    }
    if (!in_array($p['estado'], ESTADOS_ACTIVOS, true)) {
        return [false, 'Solo se asignan responsables a pedidos en proceso.'];
    }
    $area = areaDeEstado($p['estado']);
    $codigo = codigoPedido($pedidoId);

    if ($empleadoId === null) {
        if (empty($p['empleado_id'])) {
            return [false, 'El pedido ya está sin responsable.'];
        }
        $pdo->prepare('UPDATE pedidos SET empleado_id = NULL, actualizado_en = ? WHERE id = ?')->execute([ahora(), $pedidoId]);
        registrarHistorial($pdo, $pedidoId, (int) $admin['id'], null, null, $admin['nombre'] . ' quitó a ' . $p['empleado_nombre'] . ' como responsable');
        return [true, "El pedido $codigo quedó en la bandeja de $area."];
    }

    $stmt = $pdo->prepare("SELECT id, nombre, area FROM usuarios WHERE id = ? AND rol = 'empleado' AND activo = 1");
    $stmt->execute([$empleadoId]);
    $emp = $stmt->fetch();
    if (!$emp) {
        return [false, 'El responsable debe ser un empleado activo.'];
    }
    if ($emp['area'] !== $area) {
        return [false, "El pedido está en \"{$p['estado']}\": el responsable debe ser del área de $area."];
    }
    if ((int) $p['empleado_id'] === (int) $emp['id']) {
        return [false, $emp['nombre'] . ' ya es el responsable.'];
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE pedidos SET empleado_id = ?, actualizado_en = ? WHERE id = ?')->execute([$emp['id'], ahora(), $pedidoId]);
        registrarHistorial($pdo, $pedidoId, (int) $admin['id'], null, null, $admin['nombre'] . ' asignó a ' . $emp['nombre'] . ' como responsable (' . $area . ')');
        crearAlerta($pdo, (int) $emp['id'], $pedidoId, 'asignado', "Te asignaron el pedido $codigo ({$p['estado']})");
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
    return [true, $emp['nombre'] . " es ahora responsable del pedido $codigo."];
}
