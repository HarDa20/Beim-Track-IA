<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = requiereRol('cliente');

$categorias = categoriasActivas($pdo);
$borradorId = null;
$valores = $_SESSION['old'] ?? [];

// Continuar un borrador propio
if (!empty($_GET['borrador'])) {
    $b = obtenerPedido($pdo, (int) $_GET['borrador']);
    if (!$b || (int) $b['cliente_id'] !== (int) $u['id'] || $b['estado'] !== 'Borrador') {
        flash('error', 'El borrador no existe o ya fue enviado.');
        redirigir('/cliente/panel.php');
    }
    $borradorId = (int) $b['id'];
    if (!$valores) {
        $valores = $b;
        $valores['cantidad'] = (float) $b['cantidad'];
    }
}

$accionForm = '/cliente/guardar_pedido.php';
$modoStaff = false;

layoutInicio($borradorId ? 'Continuar borrador' : 'Nuevo pedido', 'nuevo', 'Completa los pasos y conoce el costo estimado al instante');
include __DIR__ . '/../includes/form_pedido.php';
layoutFin();
