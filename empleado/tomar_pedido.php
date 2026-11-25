<?php
// @spec RF-FLU-03
// Un empleado toma un pedido de la bandeja de su área.
require __DIR__ . '/../includes/bootstrap.php';
$u = requiereRol('empleado');

$pedidoId = (int) ($_POST['pedido_id'] ?? 0);
$volver = rutaSegura($_POST['volver'] ?? '', '/pedido.php?id=' . $pedidoId);
requierePostConCsrf($volver);

[$ok, $mensaje] = tomarPedido($pdo, $u, $pedidoId);
flash($ok ? 'exito' : 'error', $mensaje);
redirigir($volver);
