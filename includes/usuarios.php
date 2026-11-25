<?php
// Validaciones y consultas de usuarios.

/** @spec RF-AUT-01 */
function validarDatosUsuario(string $nombre, string $correo, string $telefono): array
{
    $errores = [];
    if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 120) {
        $errores[] = 'El nombre debe tener entre 3 y 120 caracteres.';
    }
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($correo) > 150) {
        $errores[] = 'Escribe un correo electrónico válido.';
    }
    if ($telefono !== '' && !preg_match('/^\+?[0-9\s-]{7,20}$/', $telefono)) {
        $errores[] = 'El celular solo puede contener números, espacios o guiones (7 a 20 caracteres).';
    }
    return $errores;
}

/** @spec RF-AUT-01 */
function validarPassword(string $password, string $confirmacion): array
{
    $errores = [];
    if (strlen($password) < 8 || !preg_match('/[A-Za-zÁÉÍÓÚáéíóúÑñ]/u', $password) || !preg_match('/\d/', $password)) {
        $errores[] = 'La contraseña debe tener mínimo 8 caracteres, con letras y números.';
    }
    if (strlen($password) > 72) {
        $errores[] = 'La contraseña no puede superar 72 caracteres.';
    }
    if ($password !== $confirmacion) {
        $errores[] = 'Las contraseñas no coinciden.';
    }
    return $errores;
}

function correoRegistrado(PDO $pdo, string $correo, ?int $exceptoId = null): bool
{
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE correo = ?' . ($exceptoId ? ' AND id <> ?' : ''));
    $stmt->execute($exceptoId ? [$correo, $exceptoId] : [$correo]);
    return (bool) $stmt->fetch();
}

function empleadosActivos(PDO $pdo, ?string $area = null): array
{
    $sql = "SELECT id, nombre, area FROM usuarios WHERE rol = 'empleado' AND activo = 1";
    $params = [];
    if ($area) {
        $sql .= ' AND area = ?';
        $params[] = $area;
    }
    $stmt = $pdo->prepare($sql . ' ORDER BY area, nombre');
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// ---------------------------------------------------------------------
// Foto de perfil (spec 013)
// ---------------------------------------------------------------------
const MAX_TAMANO_FOTO = 2 * 1024 * 1024; // 2 MB
const EXTENSIONES_FOTO = ['jpg', 'jpeg', 'png', 'webp'];
const MIME_FOTO = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];

function directorioFotos(): string
{
    $dir = dirname(__DIR__) . '/uploads/perfiles/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

/**
 * Valida y guarda la foto de perfil, reemplazando la anterior.
 * Devuelve la lista de errores (vacía si se guardó).
 *
 * @spec RF-FOT-01, RF-FOT-02
 */
function guardarFotoPerfil(PDO $pdo, int $usuarioId, ?array $archivo): array
{
    if (!$archivo || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['Selecciona una imagen.'];
    }
    if (in_array($archivo['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || ($archivo['size'] ?? 0) > MAX_TAMANO_FOTO) {
        return ['La foto supera el tamaño máximo de 2 MB.'];
    }
    if ($archivo['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'])) {
        return ['No se pudo recibir la imagen. Inténtalo de nuevo.'];
    }
    $ext = strtolower(pathinfo((string) $archivo['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, EXTENSIONES_FOTO, true)) {
        return ['La foto debe ser JPG, PNG o WEBP.'];
    }
    if (!firmaValida($archivo['tmp_name'], $ext)) {
        return ['El contenido del archivo no corresponde a una imagen ' . strtoupper($ext) . '.'];
    }
    $nombre = 'u' . $usuarioId . '_' . bin2hex(random_bytes(12)) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
    if (!move_uploaded_file($archivo['tmp_name'], directorioFotos() . $nombre)) {
        return ['No se pudo guardar la foto en el servidor.'];
    }
    quitarFotoPerfil($pdo, $usuarioId);
    $pdo->prepare('UPDATE usuarios SET foto_perfil = ? WHERE id = ?')->execute([$nombre, $usuarioId]);
    return [];
}

/** @spec RF-FOT-04 */
function quitarFotoPerfil(PDO $pdo, int $usuarioId): void
{
    $stmt = $pdo->prepare('SELECT foto_perfil FROM usuarios WHERE id = ?');
    $stmt->execute([$usuarioId]);
    $actual = (string) $stmt->fetchColumn();
    if ($actual !== '' && preg_match('/^[A-Za-z0-9_]+\.(jpg|png|webp)$/', $actual) && is_file(directorioFotos() . $actual)) {
        unlink(directorioFotos() . $actual);
    }
    $pdo->prepare('UPDATE usuarios SET foto_perfil = NULL WHERE id = ?')->execute([$usuarioId]);
}

/**
 * Cada quien ve su foto; el personal ve todas; un cliente ve las del
 * personal pero no las de otros clientes.
 *
 * @spec RF-FOT-02
 */
function puedeVerFotoDe(array $quien, array $de): bool
{
    return (int) $quien['id'] === (int) $de['id'] || $quien['rol'] !== 'cliente' || $de['rol'] !== 'cliente';
}
