<?php
// Sesión, autenticación y control de acceso por rol.

// URL base detectada automáticamente: '' en la raíz o '/beimtrack_ia' en subcarpeta.
if (!defined('BASE_URL')) {
    $raizProyecto = str_replace('\\', '/', dirname(__DIR__));
    $raizServidor = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/'));
    $base = '';
    if ($raizServidor !== '' && stripos($raizProyecto, $raizServidor) === 0) {
        $base = substr($raizProyecto, strlen($raizServidor));
    }
    define('BASE_URL', rtrim($base, '/'));
}

const MINUTOS_INACTIVIDAD = 60;
const MAX_INTENTOS_LOGIN = 5;
const MINUTOS_BLOQUEO_LOGIN = 15;
const MAX_CUENTAS = 5;

// ---------------------------------------------------------------------
// Multisesión (spec 010): varias cuentas en el mismo navegador.
// $_SESSION['cuentas'][ranura] = ['usuario' => [...], 'ultima_actividad' => t]
// La ranura de cada petición viaja en la dirección como ?u=N.
// ---------------------------------------------------------------------

/**
 * Ranura (0 a MAX_CUENTAS-1) con la que trabaja esta petición.
 *
 * @spec RF-MSE-02
 */
function ranuraActual(?int $fijar = null): int
{
    static $ranura = null;
    if ($fijar !== null) {
        $ranura = max(0, min(MAX_CUENTAS - 1, $fijar));
    }
    if ($ranura === null) {
        $u = $_GET['u'] ?? '0';
        $ranura = (is_string($u) && ctype_digit($u) && (int) $u < MAX_CUENTAS) ? (int) $u : 0;
    }
    return $ranura;
}

/** @spec RF-MSE-03 */
function cuentasAbiertas(): array
{
    $cuentas = $_SESSION['cuentas'] ?? [];
    ksort($cuentas);
    return $cuentas;
}

/** Primera ranura libre o null si ya hay MAX_CUENTAS abiertas. @spec RF-MSE-01 */
function primeraRanuraLibre(): ?int
{
    for ($r = 0; $r < MAX_CUENTAS; $r++) {
        if (empty($_SESSION['cuentas'][$r])) {
            return $r;
        }
    }
    return null;
}

/** Ranura donde ya está abierta una cuenta, o null. @spec RF-MSE-01 */
function ranuraDeUsuario(int $usuarioId): ?int
{
    foreach (cuentasAbiertas() as $r => $c) {
        if ((int) $c['usuario']['id'] === $usuarioId) {
            return (int) $r;
        }
    }
    return null;
}

function usuarioActual(): ?array
{
    return $_SESSION['cuentas'][ranuraActual()]['usuario'] ?? null;
}

/**
 * Exige sesión iniciada en la ranura actual. En cada petición vuelve a leer al
 * usuario desde la base de datos: si fue desactivado o cambió su rol, se
 * aplica de inmediato. La inactividad se mide por cuenta.
 * @spec RF-AUT-04, RF-AUT-05, RF-AUT-07
 * @spec RNF-MSE-01
 */
function requiereLogin(): array
{
    global $pdo;
    $r = ranuraActual();
    $u = usuarioActual();
    if (!$u) {
        flash('info', 'Inicia sesión para continuar.');
        redirigir('/index.php');
    }

    $ultimo = $_SESSION['cuentas'][$r]['ultima_actividad'] ?? time();
    if (time() - $ultimo > MINUTOS_INACTIVIDAD * 60) {
        cerrarCuenta($r);
        flash('info', 'Tu sesión se cerró por inactividad.');
        redirigir('/index.php');
    }
    $_SESSION['cuentas'][$r]['ultima_actividad'] = time();

    $stmt = $pdo->prepare('SELECT id, nombre, correo, telefono, rol, area, activo, foto_perfil FROM usuarios WHERE id = ?');
    $stmt->execute([$u['id']]);
    $fresco = $stmt->fetch();
    if (!$fresco || (int) $fresco['activo'] !== 1) {
        cerrarCuenta($r);
        flash('error', 'Tu cuenta no está activa. Comunícate con el administrador.');
        redirigir('/index.php');
    }
    unset($fresco['activo']);
    $fresco['id'] = (int) $fresco['id'];
    $_SESSION['cuentas'][$r]['usuario'] = $fresco;
    return $fresco;
}

/** @spec RF-AUT-05 */
function requiereRol($rolesPermitidos): array
{
    $u = requiereLogin();
    if (!in_array($u['rol'], (array) $rolesPermitidos, true)) {
        flash('error', 'No tienes permiso para acceder a esa sección.');
        redirigir(rutaPanel($u['rol']));
    }
    return $u;
}

/** @spec RF-AUT-02 */
function rutaPanel(string $rol): string
{
    return [
        'cliente'       => '/cliente/panel.php',
        'empleado'      => '/empleado/panel.php',
        'administrador' => '/admin/panel.php',
    ][$rol] ?? '/index.php';
}

function redirigirSegunRol(string $rol): void
{
    redirigir(rutaPanel($rol));
}

/**
 * Abre la cuenta en una ranura y la deja como ranura de esta petición.
 *
 * @spec RF-AUT-02, RF-AUT-06
 * @spec RF-MSE-01
 */
function iniciarSesionUsuario(array $usuario, int $ranura = 0): void
{
    session_regenerate_id(true); // evita la fijación de sesión
    $_SESSION['cuentas'][$ranura] = [
        'usuario' => [
            'id'          => (int) $usuario['id'],
            'nombre'      => $usuario['nombre'],
            'correo'      => $usuario['correo'],
            'telefono'    => $usuario['telefono'] ?? null,
            'rol'         => $usuario['rol'],
            'area'        => $usuario['area'] ?? null,
            'foto_perfil' => $usuario['foto_perfil'] ?? null,
        ],
        'ultima_actividad' => time(),
    ];
    ranuraActual($ranura);
    unset($_SESSION['csrf']); // token nuevo para la sesión autenticada
}

/**
 * Cierra solo la cuenta de una ranura; si no queda ninguna, destruye la sesión.
 *
 * @spec RF-MSE-04
 */
function cerrarCuenta(int $ranura): void
{
    unset($_SESSION['cuentas'][$ranura]);
    if (empty($_SESSION['cuentas'])) {
        cerrarSesion();
        iniciarSesionSegura();
    }
}

/**
 * Cierra todas las cuentas del navegador.
 *
 * @spec RF-AUT-04
 * @spec RF-MSE-04
 */
function cerrarSesion(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function esAdmin(array $u): bool
{
    return $u['rol'] === 'administrador';
}

/**
 * Recepción y administración pueden registrar pedidos a nombre de un cliente.
 *
 * @spec RF-REC-01, RF-REC-02
 */
function puedeRegistrarPedidos(array $u): bool
{
    return esAdmin($u) || ($u['rol'] === 'empleado' && $u['area'] === 'Recepción');
}

// ---------------------------------------------------------------------
// Protección contra fuerza bruta
// ---------------------------------------------------------------------
/** @spec RF-AUT-03 */
function loginBloqueado(PDO $pdo, string $correo): bool
{
    $desde = date('Y-m-d H:i:s', time() - MINUTOS_BLOQUEO_LOGIN * 60);
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM intentos_login WHERE correo = ? AND creado_en >= ?');
    $stmt->execute([mb_strtolower($correo), $desde]);
    return (int) $stmt->fetchColumn() >= MAX_INTENTOS_LOGIN;
}

/** @spec RF-AUT-03 */
function registrarIntentoFallido(PDO $pdo, string $correo): void
{
    $pdo->prepare('INSERT INTO intentos_login (correo, ip, creado_en) VALUES (?, ?, ?)')
        ->execute([mb_strtolower(mb_substr($correo, 0, 150)), ipCliente(), date('Y-m-d H:i:s')]);
}

function limpiarIntentos(PDO $pdo, string $correo): void
{
    $pdo->prepare('DELETE FROM intentos_login WHERE correo = ?')->execute([mb_strtolower($correo)]);
}
