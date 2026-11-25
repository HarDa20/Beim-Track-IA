<?php
// Entrega de fotos de perfil con control de acceso (spec 013).
// @spec RF-FOT-02
require __DIR__ . '/includes/bootstrap.php';
$u = requiereLogin();

$stmt = $pdo->prepare('SELECT id, rol, foto_perfil FROM usuarios WHERE id = ?');
$stmt->execute([(int) ($_GET['id'] ?? 0)]);
$de = $stmt->fetch();

if (!$de || !$de['foto_perfil'] || !puedeVerFotoDe($u, $de)) {
    http_response_code(404);
    exit;
}
$ruta = directorioFotos() . basename($de['foto_perfil']);
$ext = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
if (!is_file($ruta) || !isset(MIME_FOTO[$ext])) {
    http_response_code(404);
    exit;
}
header('Content-Type: ' . MIME_FOTO[$ext]);
header('Content-Length: ' . filesize($ruta));
header('Cache-Control: private, max-age=86400');
readfile($ruta);
