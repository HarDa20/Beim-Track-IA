<?php
// @spec RF-ARC-02, RF-PED-04
// @spec RF-V3D-04
// Descarga controlada de archivos de diseño: solo usuarios con acceso al pedido.
require __DIR__ . '/includes/bootstrap.php';
$u = requiereLogin();

$stmt = $pdo->prepare('SELECT * FROM archivos_pedido WHERE id = ?');
$stmt->execute([(int) ($_GET['id'] ?? 0)]);
$archivo = $stmt->fetch();
$pedido = $archivo ? obtenerPedido($pdo, (int) $archivo['pedido_id']) : null;

if (!$archivo || !$pedido || !puedeVerPedido($pdo, $u, $pedido)) {
    http_response_code(404);
    flash('error', 'El archivo no existe o no tienes acceso a él.');
    redirigirSegunRol($u['rol']);
}

$ruta = directorioUploads() . basename($archivo['ruta']);
if (!is_file($ruta)) {
    flash('error', 'El archivo ya no se encuentra en el servidor.');
    redirigir('/pedido.php?id=' . (int) $pedido['id']);
}

// ?ver=1: entrega las imágenes en línea para la visualización 3D (spec 016).
$ext = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
$tiposImagen = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
if (!empty($_GET['ver']) && isset($tiposImagen[$ext])) {
    header('Content-Type: ' . $tiposImagen[$ext]);
    header('Content-Length: ' . filesize($ruta));
    header('Cache-Control: private, max-age=3600');
    readfile($ruta);
    exit;
}

$nombre = str_replace(['"', "\r", "\n"], '', $archivo['nombre_original']);
header('Content-Type: application/octet-stream');
header('Content-Length: ' . filesize($ruta));
header('Content-Disposition: attachment; filename="' . $nombre . '"; filename*=UTF-8\'\'' . rawurlencode($nombre));
header('Cache-Control: private, no-store');
readfile($ruta);
exit;
