<?php
// @spec RF-ARC-03
// Agrega archivos de diseño a un pedido existente.
require __DIR__ . '/includes/bootstrap.php';
$u = requiereLogin();
$pedidoId = (int) ($_POST['pedido_id'] ?? 0);
requierePostConCsrf('/pedido.php?id=' . $pedidoId);

$p = obtenerPedido($pdo, $pedidoId);
if (!$p || !puedeVerPedido($pdo, $u, $p) || in_array($p['estado'], ['Entregado', 'Cancelado'], true)) {
    flash('error', 'No es posible agregar archivos a este pedido.');
    redirigirSegunRol($u['rol']);
}

[$errores, $archivos] = validarArchivosSubidos($_FILES['archivos'] ?? null);
if ($errores) {
    flash('error', implode(' ', $errores));
    redirigir('/pedido.php?id=' . $pedidoId);
}
if (!$archivos) {
    flash('error', 'Selecciona al menos un archivo.');
    redirigir('/pedido.php?id=' . $pedidoId);
}

$pdo->beginTransaction();
$movidos = [];
try {
    foreach ($archivos as $a) {
        $movidos[] = guardarArchivo($pdo, $pedidoId, $a, (int) $u['id']);
    }
    registrarHistorial($pdo, $pedidoId, (int) $u['id'], null, null, $u['nombre'] . ' adjuntó ' . count($archivos) . ' archivo(s)');
    $pdo->commit();
} catch (Throwable $ex) {
    $pdo->rollBack();
    foreach ($movidos as $m) {
        @unlink(directorioUploads() . $m);
    }
    throw $ex;
}
flash('exito', count($archivos) . ' archivo(s) agregado(s) al pedido.');
redirigir('/pedido.php?id=' . $pedidoId);
