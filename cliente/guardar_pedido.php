<?php
// @spec RF-PED-01, RF-PED-03
// Procesa el formulario del cliente: enviar, guardar borrador o eliminar borrador.
require __DIR__ . '/../includes/bootstrap.php';
$u = requiereRol('cliente');

$borradorId = (int) ($_POST['borrador_id'] ?? 0) ?: null;
$volver = '/cliente/crear_pedido.php' . ($borradorId ? '?borrador=' . $borradorId : '');
requierePostConCsrf($volver);

$accion = $_POST['accion'] ?? 'enviar';

if ($accion === 'eliminar' && $borradorId) {
    if (eliminarBorrador($pdo, $u, $borradorId)) {
        flash('exito', 'El borrador fue eliminado.');
    } else {
        flash('error', 'No se encontró el borrador.');
    }
    redirigir('/cliente/panel.php');
}

[$errores, $datos] = validarDatosPedido($pdo, $_POST);
[$erroresArchivos, $archivos] = validarArchivosSubidos($_FILES['archivos'] ?? null);
$errores = array_merge($errores, $erroresArchivos);

if ($errores) {
    guardarOld($_POST);
    flash('error', implode(' ', $errores));
    redirigir($volver);
}

$esBorrador = $accion === 'borrador';
try {
    $id = guardarPedido($pdo, $u, (int) $u['id'], $datos, 'Web', $esBorrador, $archivos, $borradorId);
} catch (RuntimeException $ex) {
    flash('error', $ex->getMessage());
    redirigir('/cliente/panel.php');
}

limpiarOld();
flash('exito', $esBorrador
    ? 'Borrador guardado. Puedes continuarlo cuando quieras.'
    : '¡Pedido ' . codigoPedido($id) . ' enviado! Ya puedes seguir su avance.');
redirigir('/pedido.php?id=' . $id);
