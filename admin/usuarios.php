<?php
// @spec RF-ADM-04, RF-AUT-07
// @spec RF-FOT-03
// Gestión de usuarios: crear, editar, cambiar rol/área, restablecer
// contraseña y activar/desactivar cuentas (nunca se borran, para conservar
// la trazabilidad de los pedidos).
require __DIR__ . '/../includes/bootstrap.php';
$u = requiereRol('administrador');

const ROLES = ['cliente', 'empleado', 'administrador'];

/** Libera los pedidos activos de un empleado que deja de poder atenderlos. */
function liberarPedidosDeEmpleado(PDO $pdo, array $admin, int $empleadoId, string $motivo): int
{
    $estados = "'" . implode("','", ESTADOS_ACTIVOS) . "'";
    $stmt = $pdo->prepare("SELECT id FROM pedidos WHERE empleado_id = ? AND estado IN ($estados)");
    $stmt->execute([$empleadoId]);
    $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($ids as $id) {
        $pdo->prepare('UPDATE pedidos SET empleado_id = NULL, actualizado_en = ? WHERE id = ?')->execute([ahora(), $id]);
        registrarHistorial($pdo, (int) $id, (int) $admin['id'], null, null, 'El pedido volvió a la bandeja del área: ' . $motivo);
    }
    return count($ids);
}

function adminsActivos(PDO $pdo): int
{
    return (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'administrador' AND activo = 1")->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requierePostConCsrf('/admin/usuarios.php');
    $accion = $_POST['accion'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($accion === 'estado') {
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);
        $obj = $stmt->fetch();
        if (!$obj) {
            flash('error', 'El usuario no existe.');
        } elseif ($id === (int) $u['id']) {
            flash('error', 'No puedes desactivar tu propia cuenta.');
        } elseif ((int) $obj['activo'] === 1 && $obj['rol'] === 'administrador' && adminsActivos($pdo) <= 1) {
            flash('error', 'Debe existir al menos un administrador activo.');
        } else {
            $nuevo = (int) $obj['activo'] === 1 ? 0 : 1;
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE usuarios SET activo = ? WHERE id = ?')->execute([$nuevo, $id]);
            $liberados = ($nuevo === 0 && $obj['rol'] === 'empleado') ? liberarPedidosDeEmpleado($pdo, $u, $id, $obj['nombre'] . ' fue desactivado') : 0;
            $pdo->commit();
            flash('exito', $obj['nombre'] . ($nuevo ? ' fue activado.' : ' fue desactivado.') . ($liberados ? " $liberados pedido(s) volvieron a la bandeja de su área." : ''));
        }
        redirigir('/admin/usuarios.php');
    }

    if ($accion === 'guardar') {
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $correo = mb_strtolower(trim((string) ($_POST['correo'] ?? '')));
        $telefono = trim((string) ($_POST['telefono'] ?? ''));
        $rol = (string) ($_POST['rol'] ?? '');
        $area = $rol === 'empleado' ? (string) ($_POST['area'] ?? '') : null;
        $password = (string) ($_POST['password'] ?? '');

        $actual = null;
        if ($id) {
            $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = ?');
            $stmt->execute([$id]);
            $actual = $stmt->fetch();
            if (!$actual) {
                flash('error', 'El usuario no existe.');
                redirigir('/admin/usuarios.php');
            }
        }

        $errores = validarDatosUsuario($nombre, $correo, $telefono);
        if (!in_array($rol, ROLES, true)) {
            $errores[] = 'Selecciona un rol válido.';
        }
        if ($rol === 'empleado' && !in_array($area, AREAS, true)) {
            $errores[] = 'Los empleados deben tener un área: ' . implode(', ', AREAS) . '.';
        }
        if (correoRegistrado($pdo, $correo, $id ?: null)) {
            $errores[] = 'Ya existe otro usuario con ese correo.';
        }
        if (!$id || $password !== '') {
            $errores = array_merge($errores, validarPassword($password, (string) ($_POST['password2'] ?? '')));
        }
        if ($actual && $id === (int) $u['id'] && $rol !== 'administrador') {
            $errores[] = 'No puedes quitarte el rol de administrador a ti mismo.';
        }
        if ($actual && $actual['rol'] === 'administrador' && $rol !== 'administrador' && (int) $actual['activo'] === 1 && adminsActivos($pdo) <= 1) {
            $errores[] = 'Debe existir al menos un administrador activo.';
        }

        if ($errores) {
            guardarOld($_POST);
            flash('error', implode(' ', $errores));
            redirigir('/admin/usuarios.php' . ($id ? '?editar=' . $id : ''));
        }

        $pdo->beginTransaction();
        if ($actual) {
            $pdo->prepare('UPDATE usuarios SET nombre = ?, correo = ?, telefono = ?, rol = ?, area = ? WHERE id = ?')
                ->execute([$nombre, $correo, $telefono ?: null, $rol, $area, $id]);
            if ($password !== '') {
                $pdo->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
            }
            $liberados = 0;
            if ($actual['rol'] === 'empleado' && ($rol !== 'empleado' || $area !== $actual['area'])) {
                $liberados = liberarPedidosDeEmpleado($pdo, $u, $id, $nombre . ' cambió de área o rol');
            }
            $msg = 'Usuario actualizado' . ($password !== '' ? ' y contraseña restablecida' : '') . '.' . ($liberados ? " $liberados pedido(s) volvieron a la bandeja." : '');
        } else {
            $pdo->prepare('INSERT INTO usuarios (nombre, correo, telefono, password_hash, rol, area, activo, creado_en) VALUES (?, ?, ?, ?, ?, ?, 1, ?)')
                ->execute([$nombre, $correo, $telefono ?: null, password_hash($password, PASSWORD_DEFAULT), $rol, $area, ahora()]);
            $msg = 'Usuario ' . $nombre . ' creado como ' . nombreRol($rol) . ($area ? ' de ' . $area : '') . '.';
        }
        $pdo->commit();
        limpiarOld();
        flash('exito', $msg);
        redirigir('/admin/usuarios.php');
    }
    redirigir('/admin/usuarios.php');
}

// ---------------------------------------------------------------------
$filtroRol = in_array($_GET['rol'] ?? '', ROLES, true) ? $_GET['rol'] : '';
$q = trim((string) ($_GET['q'] ?? ''));
$sql = 'SELECT u.*, (SELECT COUNT(*) FROM pedidos p WHERE p.cliente_id = u.id AND p.estado <> \'Borrador\') AS pedidos_cliente,
               (SELECT COUNT(*) FROM pedidos p WHERE p.empleado_id = u.id) AS pedidos_asignados
        FROM usuarios u WHERE 1 = 1';
$params = [];
if ($filtroRol) {
    $sql .= ' AND u.rol = ?';
    $params[] = $filtroRol;
}
if ($q !== '') {
    $sql .= " AND (u.nombre LIKE ? ESCAPE '!' OR u.correo LIKE ? ESCAPE '!')";
    $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $q) . '%';
    array_push($params, $like, $like);
}
$stmt = $pdo->prepare($sql . ' ORDER BY u.activo DESC, u.rol, u.nombre');
$stmt->execute($params);
$usuarios = $stmt->fetchAll();

$conteoRoles = array_fill_keys(ROLES, 0);
foreach ($pdo->query('SELECT rol, COUNT(*) AS n FROM usuarios GROUP BY rol') as $r) {
    $conteoRoles[$r['rol']] = (int) $r['n'];
}

$editar = null;
if (!empty($_GET['editar'])) {
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = ?');
    $stmt->execute([(int) $_GET['editar']]);
    $editar = $stmt->fetch() ?: null;
}
$f = $_SESSION['old'] ?? ($editar ?: ['rol' => 'empleado', 'area' => 'Recepción']);

layoutInicio('Usuarios', 'usuarios', 'Clientes, empleados por área y administradores');
?>
<div class="grid-main grid-main-wide">
  <section class="card">
    <div class="card-head wrap">
      <div class="pill-tabs">
        <a href="<?= e(url('/admin/usuarios.php')) ?>" class="<?= !$filtroRol ? 'active' : '' ?>">Todos <span class="count"><?= array_sum($conteoRoles) ?></span></a>
        <?php foreach (ROLES as $r): ?>
          <a href="<?= e(url('/admin/usuarios.php?rol=' . $r)) ?>" class="<?= $filtroRol === $r ? 'active' : '' ?>"><?= e(nombreRol($r)) ?>s <span class="count"><?= $conteoRoles[$r] ?></span></a>
        <?php endforeach; ?>
      </div>
      <form method="get" class="input-icon">
        <?php if ($filtroRol): ?><input type="hidden" name="rol" value="<?= e($filtroRol) ?>"><?php endif; ?>
        <?= icono('search', 16) ?><input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar nombre o correo">
      </form>
    </div>

    <?php if (!$usuarios): ?>
      <?= estadoVacio('users', 'Sin resultados', 'No hay usuarios que coincidan con la búsqueda.') ?>
    <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Usuario</th><th>Rol</th><th>Contacto</th><th>Pedidos</th><th>Último acceso</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($usuarios as $usr): ?>
          <tr class="<?= (int) $usr['activo'] ? '' : 'row-muted' ?>">
            <td><div class="user-cell"><?= avatarHtml($usr, 'avatar-sm') ?><div><strong><?= e($usr['nombre']) ?></strong><small class="block muted"><?= e($usr['correo']) ?></small></div></div></td>
            <td><span class="role role-<?= e($usr['rol']) ?>"><?= e(nombreRol($usr['rol'])) ?></span><?= $usr['area'] ? '<small class="block muted">' . e($usr['area']) . '</small>' : '' ?></td>
            <td><?= $usr['telefono'] ? e($usr['telefono']) : '<span class="muted">—</span>' ?></td>
            <td class="mono"><?= $usr['rol'] === 'cliente' ? (int) $usr['pedidos_cliente'] : ($usr['rol'] === 'empleado' ? (int) $usr['pedidos_asignados'] . ' asig.' : '—') ?></td>
            <td><?= $usr['ultimo_acceso'] ? e(tiempoRelativo($usr['ultimo_acceso'])) : '<span class="muted">Nunca</span>' ?></td>
            <td><?= (int) $usr['activo'] ? '<span class="chip chip-ok">Activo</span>' : '<span class="chip">Inactivo</span>' ?></td>
            <td class="right nowrap">
              <a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/usuarios.php?editar=' . $usr['id'])) ?>"><?= icono('edit', 14) ?></a>
              <?php if ((int) $usr['id'] !== (int) $u['id']): ?>
              <form method="post" class="inline" data-confirm="<?= e(((int) $usr['activo'] ? '¿Desactivar a ' : '¿Activar a ') . $usr['nombre'] . '?' . ((int) $usr['activo'] && $usr['rol'] === 'empleado' ? ' Sus pedidos en curso volverán a la bandeja del área.' : '')) ?>">
                <?= campoCsrf() ?><input type="hidden" name="accion" value="estado"><input type="hidden" name="id" value="<?= (int) $usr['id'] ?>">
                <button class="btn btn-sm <?= (int) $usr['activo'] ? 'btn-danger-ghost' : 'btn-secondary' ?>"><?= (int) $usr['activo'] ? 'Desactivar' : 'Activar' ?></button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </section>

  <section class="card sticky">
    <div class="card-head"><h3><?= icono($editar ? 'edit' : 'plus', 18) ?> <?= $editar ? 'Editar usuario' : 'Nuevo usuario' ?></h3>
      <?php if ($editar): ?><a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/usuarios.php')) ?>">Cancelar</a><?php endif; ?></div>
    <form method="post" class="stack" data-user-form>
      <?= campoCsrf() ?>
      <input type="hidden" name="accion" value="guardar">
      <input type="hidden" name="id" value="<?= (int) ($editar['id'] ?? 0) ?>">
      <label class="field"><span>Nombre completo</span><input type="text" name="nombre" value="<?= e($f['nombre'] ?? '') ?>" required minlength="3" maxlength="120"></label>
      <label class="field"><span>Correo</span><input type="email" name="correo" value="<?= e($f['correo'] ?? '') ?>" required maxlength="150"></label>
      <label class="field"><span>Celular</span><input type="tel" name="telefono" value="<?= e($f['telefono'] ?? '') ?>" maxlength="20"></label>
      <div class="grid-2">
        <label class="field"><span>Rol</span>
          <select name="rol" data-rol-select>
            <?php foreach (ROLES as $r): ?><option value="<?= $r ?>" <?= ($f['rol'] ?? '') === $r ? 'selected' : '' ?>><?= e(nombreRol($r)) ?></option><?php endforeach; ?>
          </select>
        </label>
        <label class="field" data-area-field><span>Área</span>
          <select name="area">
            <?php foreach (AREAS as $a): ?><option <?= ($f['area'] ?? '') === $a ? 'selected' : '' ?>><?= e($a) ?></option><?php endforeach; ?>
          </select>
        </label>
      </div>
      <div class="grid-2">
        <label class="field"><span><?= $editar ? 'Nueva contraseña' : 'Contraseña' ?></span><input type="password" name="password" <?= $editar ? '' : 'required' ?> minlength="8" autocomplete="new-password" placeholder="<?= $editar ? 'Dejar vacío = sin cambio' : '' ?>"></label>
        <label class="field"><span>Confirmar</span><input type="password" name="password2" <?= $editar ? '' : 'required' ?> minlength="8" autocomplete="new-password"></label>
      </div>
      <p class="hint">Mínimo 8 caracteres con letras y números. Comparte la contraseña inicial por un medio seguro.</p>
      <button class="btn btn-primary btn-block"><?= icono('check', 16) ?> <?= $editar ? 'Guardar cambios' : 'Crear usuario' ?></button>
    </form>
  </section>
</div>
<?php layoutFin(); ?>
