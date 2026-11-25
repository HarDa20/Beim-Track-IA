<?php
// Alertas internas: se guardan en la base de datos y se muestran en la
// campana de cada usuario según su rol y área.

/** @spec RF-ALE-01 */
function crearAlerta(PDO $pdo, int $usuarioId, ?int $pedidoId, string $tipo, string $mensaje, ?string $clave = null): void
{
    if ($clave !== null) {
        $stmt = $pdo->prepare('SELECT 1 FROM alertas WHERE usuario_id = ? AND clave = ?');
        $stmt->execute([$usuarioId, $clave]);
        if ($stmt->fetchColumn()) {
            return; // ya existe: evita duplicados de vencimientos
        }
    }
    $pdo->prepare('INSERT INTO alertas (usuario_id, pedido_id, tipo, mensaje, clave, leida, creado_en) VALUES (?, ?, ?, ?, ?, 0, ?)')
        ->execute([$usuarioId, $pedidoId, $tipo, mb_substr($mensaje, 0, 255), $clave, ahora()]);
}

/** @spec RF-ALE-01 */
function alertarArea(PDO $pdo, string $area, ?int $pedidoId, string $tipo, string $mensaje, ?int $excepto = null, ?string $clave = null): void
{
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE rol = 'empleado' AND area = ? AND activo = 1");
    $stmt->execute([$area]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
        if ((int) $id !== (int) $excepto) {
            crearAlerta($pdo, (int) $id, $pedidoId, $tipo, $mensaje, $clave);
        }
    }
}

/** @spec RF-ALE-01 */
function alertarAdministradores(PDO $pdo, ?int $pedidoId, string $tipo, string $mensaje, ?int $excepto = null, ?string $clave = null): void
{
    $ids = $pdo->query("SELECT id FROM usuarios WHERE rol = 'administrador' AND activo = 1")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($ids as $id) {
        if ((int) $id !== (int) $excepto) {
            crearAlerta($pdo, (int) $id, $pedidoId, $tipo, $mensaje, $clave);
        }
    }
}

function contarAlertasNoLeidas(PDO $pdo, int $usuarioId): int
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM alertas WHERE usuario_id = ? AND leida = 0');
    $stmt->execute([$usuarioId]);
    return (int) $stmt->fetchColumn();
}

function alertasDeUsuario(PDO $pdo, int $usuarioId, int $limite = 50, bool $soloNoLeidas = false): array
{
    $sql = 'SELECT * FROM alertas WHERE usuario_id = ?' . ($soloNoLeidas ? ' AND leida = 0' : '') . ' ORDER BY creado_en DESC, id DESC LIMIT ' . (int) $limite;
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuarioId]);
    return $stmt->fetchAll();
}

/**
 * Revisa los pedidos próximos a vencer (2 días o menos) y retrasados, y
 * genera las alertas una sola vez por pedido (y por día en el caso de
 * "por vencer"). Se ejecuta al cargar los paneles, como máximo cada 5 minutos
 * por sesión; no requiere tareas programadas del servidor.
 * @spec RF-ALE-02
 */
function revisarVencimientos(PDO $pdo): void
{
    if (isset($_SESSION['ultima_revision_vencimientos']) && time() - $_SESSION['ultima_revision_vencimientos'] < 300) {
        return;
    }
    $_SESSION['ultima_revision_vencimientos'] = time();

    $limite = date('Y-m-d', strtotime('+2 days'));
    $estados = "'" . implode("','", ESTADOS_ACTIVOS) . "'";
    $stmt = $pdo->prepare("SELECT id, cliente_id, empleado_id, estado, fecha_entrega, tipo_trabajo FROM pedidos WHERE estado IN ($estados) AND fecha_entrega <= ?");
    $stmt->execute([$limite]);

    foreach ($stmt->fetchAll() as $p) {
        $codigo = codigoPedido($p['id']);
        $area = areaDeEstado($p['estado']);
        if ($p['fecha_entrega'] < hoy()) {
            $clave = 'retrasado:' . $p['id'];
            $msg = "Pedido $codigo retrasado (entrega: " . formatoFecha($p['fecha_entrega']) . ')';
            alertarAdministradores($pdo, (int) $p['id'], 'retrasado', $msg, null, $clave);
            if ($p['empleado_id']) {
                crearAlerta($pdo, (int) $p['empleado_id'], (int) $p['id'], 'retrasado', $msg, $clave);
            } elseif ($area) {
                alertarArea($pdo, $area, (int) $p['id'], 'retrasado', $msg, null, $clave);
            }
            // Aviso externo al cliente una sola vez (registrado en notificaciones_envios).
            $ya = $pdo->prepare("SELECT 1 FROM notificaciones_envios WHERE pedido_id = ? AND evento = 'pedido_retrasado' LIMIT 1");
            $ya->execute([$p['id']]);
            if (!$ya->fetchColumn()) {
                $pedido = obtenerPedido($pdo, (int) $p['id']);
                notificarCliente($pdo, $pedido, 'pedido_retrasado',
                    "BEIM Track: tu pedido $codigo presenta un retraso. Nuestro equipo te contactará con la nueva fecha.");
            }
        } else {
            $clave = 'por_vencer:' . $p['id'] . ':' . hoy();
            $dias = diasParaEntrega($p['fecha_entrega']);
            $msg = "Pedido $codigo " . ($dias === 0 ? 'vence hoy' : "vence en $dias día(s)") . " ({$p['estado']})";
            if ($p['empleado_id']) {
                crearAlerta($pdo, (int) $p['empleado_id'], (int) $p['id'], 'por_vencer', $msg, $clave);
            } elseif ($area) {
                alertarArea($pdo, $area, (int) $p['id'], 'por_vencer', $msg, null, $clave);
            }
        }
    }
}

function iconoAlerta(string $tipo): string
{
    return [
        'nuevo_pedido'  => 'inbox',
        'asignado'      => 'user',
        'cambio_estado' => 'layers',
        'por_vencer'    => 'clock',
        'retrasado'     => 'alert',
        'finalizado'    => 'check',
    ][$tipo] ?? 'bell';
}
