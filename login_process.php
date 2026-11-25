<?php
// @spec RF-AUT-02, RF-AUT-03, RNF-AUT-01
// @spec RF-MSE-01
require __DIR__ . '/includes/bootstrap.php';
requierePostConCsrf('/index.php');

// Ranura de multisesión donde se abrirá la cuenta (spec 010).
$ranura = (string) ($_POST['ranura'] ?? '0');
ranuraActual(ctype_digit($ranura) && (int) $ranura < MAX_CUENTAS ? (int) $ranura : 0);

$correo = mb_strtolower(trim((string) ($_POST['correo'] ?? '')));
$password = (string) ($_POST['password'] ?? '');
guardarOld(['correo_login' => $correo]);
$_SESSION['tab_activa'] = 'login';

if ($correo === '' || $password === '') {
    flash('error', 'Escribe tu correo y tu contraseña.');
    redirigir('/index.php');
}

if (loginBloqueado($pdo, $correo)) {
    flash('error', 'Demasiados intentos fallidos. Espera ' . MINUTOS_BLOQUEO_LOGIN . ' minutos e inténtalo de nuevo.');
    redirigir('/index.php');
}

$stmt = $pdo->prepare('SELECT id, nombre, correo, telefono, password_hash, rol, area, activo, foto_perfil FROM usuarios WHERE correo = ?');
$stmt->execute([$correo]);
$usuario = $stmt->fetch();

if (!$usuario || !password_verify($password, $usuario['password_hash'])) {
    registrarIntentoFallido($pdo, $correo);
    flash('error', 'Correo o contraseña incorrectos.');
    redirigir('/index.php');
}

if ((int) $usuario['activo'] !== 1) {
    flash('error', 'Tu cuenta está desactivada. Comunícate con el administrador.');
    redirigir('/index.php');
}

// Actualiza el hash si PHP recomienda un algoritmo o costo más reciente.
if (password_needs_rehash($usuario['password_hash'], PASSWORD_DEFAULT)) {
    $pdo->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $usuario['id']]);
}

// Si la cuenta ya está abierta en otra pestaña, se reutiliza su ranura.
$yaAbierta = ranuraDeUsuario((int) $usuario['id']);
if ($yaAbierta !== null) {
    limpiarOld();
    ranuraActual($yaAbierta);
    flash('info', 'Esa cuenta ya estaba abierta en este navegador.');
    redirigirSegunRol($usuario['rol']);
}

limpiarIntentos($pdo, $correo);
$pdo->prepare('UPDATE usuarios SET ultimo_acceso = ? WHERE id = ?')->execute([ahora(), $usuario['id']]);
limpiarOld();
iniciarSesionUsuario($usuario, ranuraActual());
flash('exito', 'Hola, ' . explode(' ', $usuario['nombre'])[0] . '. ¡Bienvenido a BEIM Track IA!');
redirigirSegunRol($usuario['rol']);
