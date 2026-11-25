<?php
// @spec RF-FLU-01
// Cambio de estado de un pedido (empleados y administradores).
// Las reglas del flujo se validan en includes/pedidos.php::cambiarEstado().
require __DIR__ . '/../includes/bootstrap.php';
$u = requiereRol(['empleado', 'administrador']);

$pedidoId = (int) ($_POST['pedido_id'] ?? 0);
$volver = rutaSegura($_POST['volver'] ?? '', '/pedido.php?id=' . $pedidoId);
requierePostConCsrf($volver);

[$ok, $mensaje] = cambiarEstado($pdo, $u, $pedidoId, (string) ($_POST['nuevo_estado'] ?? ''), (string) ($_POST['observacion'] ?? ''));
flash($ok ? 'exito' : 'error', $mensaje);
redirigir($volver);
