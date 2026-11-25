<?php
// @spec RF-AUT-01, RNF-AUT-01
require __DIR__ . '/includes/bootstrap.php';
requierePostConCsrf('/index.php');

$nombre   = trim((string) ($_POST['nombre'] ?? ''));
$correo   = mb_strtolower(trim((string) ($_POST['correo'] ?? '')));
$telefono = trim((string) ($_POST['telefono'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$password2 = (string) ($_POST['password2'] ?? '');
$acepta   = ($_POST['acepta_datos'] ?? '') === '1';

guardarOld($_POST);
$_SESSION['tab_activa'] = 'registro';

$errores = validarDatosUsuario($nombre, $correo, $telefono);
$errores = array_merge($errores, validarPassword($password, $password2));
if (!$acepta) {
    $errores[] = 'Debes autorizar el tratamiento de datos personales para crear la cuenta.';
}
if (!$errores && correoRegistrado($pdo, $correo)) {
    $errores[] = 'Ya existe una cuenta registrada con ese correo.';
}

if ($errores) {
    flash('error', implode(' ', $errores));
    redirigir('/index.php');
}

try {
    $pdo->prepare("INSERT INTO usuarios (nombre, correo, telefono, password_hash, rol, activo, acepta_datos_en, ultimo_acceso, creado_en) VALUES (?, ?, ?, ?, 'cliente', 1, ?, ?, ?)")
        ->execute([$nombre, $correo, $telefono ?: null, password_hash($password, PASSWORD_DEFAULT), ahora(), ahora(), ahora()]);
} catch (PDOException $ex) {
    // Doble envío simultáneo con el mismo correo (restricción UNIQUE).
    flash('error', 'Ya existe una cuenta registrada con ese correo.');
    redirigir('/index.php');
}

$id = (int) $pdo->lastInsertId();
limpiarOld();
iniciarSesionUsuario(['id' => $id, 'nombre' => $nombre, 'correo' => $correo, 'telefono' => $telefono ?: null, 'rol' => 'cliente', 'area' => null]);
flash('exito', '¡Tu cuenta fue creada! Ya puedes registrar tu primer pedido.');
redirigirSegunRol('cliente');
