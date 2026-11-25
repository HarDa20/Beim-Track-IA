<?php
// @spec RF-PED-06
// El cliente cancela su propio pedido mientras está en "Recibido".
require __DIR__ . '/../includes/bootstrap.php';
$u = requiereRol('cliente');

$pedidoId = (int) ($_POST['pedido_id'] ?? 0);
requierePostConCsrf('/pedido.php?id=' . $pedidoId);

[$ok, $mensaje] = cambiarEstado($pdo, $u, $pedidoId, 'Cancelado', (string) ($_POST['observacion'] ?? ''));
flash($ok ? 'exito' : 'error', $mensaje);
redirigir('/pedido.php?id=' . $pedidoId);
