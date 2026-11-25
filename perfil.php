<?php
// Perfil del usuario: foto, datos de contacto y cambio de contraseña.
// @spec RF-FOT-01, RF-FOT-03, RF-FOT-04
require __DIR__ . '/includes/bootstrap.php';
$u = requiereLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requierePostConCsrf('/perfil.php');
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'datos') {
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $telefono = trim((string) ($_POST['telefono'] ?? ''));
        $errores = validarDatosUsuario($nombre, $u['correo'], $telefono);
        if ($errores) {
            flash('error', implode(' ', $errores));
        } else {
            $pdo->prepare('UPDATE usuarios SET nombre = ?, telefono = ? WHERE id = ?')->execute([$nombre, $telefono ?: null, $u['id']]);
            flash('exito', 'Tus datos fueron actualizados.');
        }
    } elseif ($accion === 'foto') {
        $errores = guardarFotoPerfil($pdo, (int) $u['id'], $_FILES['foto'] ?? null);
        flash($errores ? 'error' : 'exito', $errores ? implode(' ', $errores) : 'Tu foto de perfil fue actualizada.');
    } elseif ($accion === 'quitar_foto') {
        quitarFotoPerfil($pdo, (int) $u['id']);
        flash('exito', 'Quitaste tu foto de perfil.');
    } elseif ($accion === 'password') {
        $stmt = $pdo->prepare('SELECT password_hash FROM usuarios WHERE id = ?');
        $stmt->execute([$u['id']]);
        $hash = (string) $stmt->fetchColumn();
        $nueva = (string) ($_POST['password'] ?? '');
        $errores = password_verify((string) ($_POST['password_actual'] ?? ''), $hash) ? [] : ['La contraseña actual no es correcta.'];
        $errores = array_merge($errores, validarPassword($nueva, (string) ($_POST['password2'] ?? '')));
        if ($errores) {
            flash('error', implode(' ', $errores));
        } else {
            $pdo->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?')->execute([password_hash($nueva, PASSWORD_DEFAULT), $u['id']]);
            session_regenerate_id(true);
            flash('exito', 'Tu contraseña fue cambiada.');
        }
    }
    redirigir('/perfil.php');
}

layoutInicio('Mi perfil', 'perfil', 'Datos de contacto y seguridad de tu cuenta');
?>
<div class="grid-2 align-start">
  <section class="card">
    <div class="profile-head">
      <?= avatarHtml($u, 'avatar-xl') ?>
      <div class="profile-head-info">
        <h3><?= e($u['nombre']) ?></h3>
        <p class="muted"><?= e($u['correo']) ?> · <?= e(nombreRol($u['rol'])) ?><?= $u['area'] ? ' · ' . e($u['area']) : '' ?></p>
        <div class="photo-actions">
          <form method="post" enctype="multipart/form-data" class="photo-form">
            <?= campoCsrf() ?><input type="hidden" name="accion" value="foto">
            <label class="btn btn-secondary btn-sm">
              <?= icono('upload', 15) ?> <?= empty($u['foto_perfil']) ? 'Subir foto' : 'Cambiar foto' ?>
              <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp" data-autosubmit hidden>
            </label>
          </form>
          <?php if (!empty($u['foto_perfil'])): ?>
            <form method="post">
              <?= campoCsrf() ?><input type="hidden" name="accion" value="quitar_foto">
              <button class="btn btn-ghost btn-sm"><?= icono('x', 15) ?> Quitar</button>
            </form>
          <?php endif; ?>
        </div>
        <small class="hint">JPG, PNG o WEBP · máximo 2 MB</small>
      </div>
    </div>
    <form method="post" class="stack">
      <?= campoCsrf() ?><input type="hidden" name="accion" value="datos">
      <label class="field"><span>Nombre</span><input type="text" name="nombre" value="<?= e($u['nombre']) ?>" required minlength="3" maxlength="120"></label>
      <label class="field"><span>Correo</span><input type="email" value="<?= e($u['correo']) ?>" disabled><small class="hint">Para cambiar el correo, comunícate con el administrador.</small></label>
      <label class="field"><span>Celular de contacto</span><input type="tel" name="telefono" value="<?= e($u['telefono']) ?>" maxlength="20" placeholder="300 123 4567"></label>
      <button class="btn btn-primary"><?= icono('check', 16) ?> Guardar cambios</button>
    </form>
  </section>

  <section class="card">
    <div class="card-head"><h3><?= icono('shield', 18) ?> Cambiar contraseña</h3></div>
    <form method="post" class="stack">
      <?= campoCsrf() ?><input type="hidden" name="accion" value="password">
      <label class="field"><span>Contraseña actual</span><input type="password" name="password_actual" required autocomplete="current-password"></label>
      <label class="field"><span>Nueva contraseña</span><input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
      <label class="field"><span>Confirmar nueva contraseña</span><input type="password" name="password2" required minlength="8" autocomplete="new-password"></label>
      <p class="hint">Mínimo 8 caracteres, con letras y números.</p>
      <button class="btn btn-secondary"><?= icono('shield', 16) ?> Actualizar contraseña</button>
    </form>
  </section>
</div>
<?php layoutFin(); ?>
