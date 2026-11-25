<?php
// Se conserva por compatibilidad con enlaces anteriores: el seguimiento
// ahora vive en /pedido.php, compartido por todos los roles con permisos.
require __DIR__ . '/../includes/bootstrap.php';
requiereRol('cliente');
redirigir('/pedido.php?id=' . (int) ($_GET['id'] ?? 0));
