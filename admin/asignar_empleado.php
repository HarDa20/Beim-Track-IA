<?php
// @spec RF-ADM-03
// Asignación del responsable de la etapa actual (solo administrador).
require __DIR__ . '/../includes/bootstrap.php';
$u = requiereRol('administrador');

$pedidoId = (int) ($_POST['pedido_id'] ?? 0);
$volver = rutaSegura($_POST['volver'] ?? '', '/admin/pedidos.php');
requierePostConCsrf($volver);

$valor = (string) ($_POST['empleado_id'] ?? '');
$empleadoId = ctype_digit($valor) ? (int) $valor : null;

[$ok, $mensaje] = asignarResponsable($pdo, $u, $pedidoId, $empleadoId);
flash($ok ? 'exito' : 'error', $mensaje);
redirigir($volver);
