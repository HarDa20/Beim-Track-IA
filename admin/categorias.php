<?php
// @spec RF-ADM-05
// Categorías y precios de referencia. Los pedidos guardan el precio vigente
// al crearse, así que cambiar un precio no altera pedidos anteriores.
require __DIR__ . '/../includes/bootstrap.php';
$u = requiereRol('administrador');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requierePostConCsrf('/admin/categorias.php');
    $accion = $_POST['accion'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($accion === 'estado') {
        $stmt = $pdo->prepare('SELECT categoria, activa FROM categorias_precios WHERE id = ?');
        $stmt->execute([$id]);
        $cat = $stmt->fetch();
        if ($cat) {
            $nuevo = (int) $cat['activa'] ? 0 : 1;
            if (!$nuevo && (int) $pdo->query('SELECT COUNT(*) FROM categorias_precios WHERE activa = 1')->fetchColumn() <= 1) {
                flash('error', 'Debe quedar al menos una categoría activa para recibir pedidos.');
            } else {
                $pdo->prepare('UPDATE categorias_precios SET activa = ?, actualizado_en = ? WHERE id = ?')->execute([$nuevo, ahora(), $id]);
                flash('exito', 'La categoría ' . $cat['categoria'] . ($nuevo ? ' está disponible para nuevos pedidos.' : ' dejó de estar disponible para nuevos pedidos.'));
            }
        }
        redirigir('/admin/categorias.php');
    }

    if ($accion === 'guardar') {
        $nombre = trim((string) ($_POST['categoria'] ?? ''));
        $descripcion = trim((string) ($_POST['descripcion'] ?? ''));
        $unidad = trim((string) ($_POST['unidad'] ?? ''));
        $precioTxt = str_replace(['.', ' ', '$'], '', (string) ($_POST['precio_por_unidad'] ?? ''));
        $precioTxt = str_replace(',', '.', $precioTxt);

        $errores = [];
        if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 80) $errores[] = 'El nombre debe tener entre 3 y 80 caracteres.';
        if (mb_strlen($descripcion) > 255) $errores[] = 'La descripción admite máximo 255 caracteres.';
        if (!in_array($unidad, ['m2', 'unidad', 'ml', 'juego'], true)) $errores[] = 'Selecciona una unidad válida.';
        if (!is_numeric($precioTxt) || (float) $precioTxt < 0 || (float) $precioTxt > 100000000) $errores[] = 'Escribe un precio válido.';

        $stmt = $pdo->prepare('SELECT id FROM categorias_precios WHERE categoria = ?' . ($id ? ' AND id <> ?' : ''));
        $stmt->execute($id ? [$nombre, $id] : [$nombre]);
        if ($stmt->fetch()) $errores[] = 'Ya existe una categoría con ese nombre.';

        if ($errores) {
            guardarOld($_POST);
            flash('error', implode(' ', $errores));
            redirigir('/admin/categorias.php' . ($id ? '?editar=' . $id : ''));
        }
        if ($id) {
            $pdo->prepare('UPDATE categorias_precios SET categoria = ?, descripcion = ?, unidad = ?, precio_por_unidad = ?, actualizado_en = ? WHERE id = ?')
                ->execute([$nombre, $descripcion ?: null, $unidad, round((float) $precioTxt, 2), ahora(), $id]);
            flash('exito', 'Categoría actualizada. El nuevo precio aplica a los pedidos que se registren desde ahora.');
        } else {
            $pdo->prepare('INSERT INTO categorias_precios (categoria, descripcion, unidad, precio_por_unidad, activa, actualizado_en) VALUES (?, ?, ?, ?, 1, ?)')
                ->execute([$nombre, $descripcion ?: null, $unidad, round((float) $precioTxt, 2), ahora()]);
            flash('exito', 'Categoría ' . $nombre . ' creada.');
        }
        limpiarOld();
        redirigir('/admin/categorias.php');
    }
    redirigir('/admin/categorias.php');
}

$categorias = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM pedidos p WHERE p.categoria_id = c.id AND p.estado <> 'Borrador') AS pedidos
                           FROM categorias_precios c ORDER BY c.activa DESC, c.categoria")->fetchAll();
$editar = null;
if (!empty($_GET['editar'])) {
    $stmt = $pdo->prepare('SELECT * FROM categorias_precios WHERE id = ?');
    $stmt->execute([(int) $_GET['editar']]);
    $editar = $stmt->fetch() ?: null;
}
$f = $_SESSION['old'] ?? ($editar ?: ['unidad' => 'm2']);

layoutInicio('Categorías y precios', 'categorias', 'Precios de referencia para el costo estimado');
?>
<div class="grid-main grid-main-wide">
  <section class="card">
    <?php if (!$categorias): ?>
      <?= estadoVacio('tag', 'Sin categorías', 'Crea la primera categoría para que los clientes puedan registrar pedidos.') ?>
    <?php else: ?>
    <div class="cat-admin-grid">
      <?php foreach ($categorias as $c): ?>
        <article class="cat-admin <?= (int) $c['activa'] ? '' : 'is-off' ?>">
          <div class="cat-admin-top">
            <span class="kpi-ico"><?= icono('tag') ?></span>
            <?= (int) $c['activa'] ? '<span class="chip chip-ok">Activa</span>' : '<span class="chip">Inactiva</span>' ?>
          </div>
          <h3><?= e($c['categoria']) ?></h3>
          <p class="muted"><?= e($c['descripcion'] ?? 'Sin descripción') ?></p>
          <div class="cat-price"><?= e(formatoMoneda($c['precio_por_unidad'])) ?><small> / <?= e($c['unidad']) ?></small></div>
          <small class="muted"><?= (int) $c['pedidos'] ?> pedido(s) · <?= $c['actualizado_en'] ? 'actualizado ' . e(tiempoRelativo($c['actualizado_en'])) : 'precio inicial' ?></small>
          <div class="cat-admin-actions">
            <a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/categorias.php?editar=' . $c['id'])) ?>"><?= icono('edit', 14) ?> Editar</a>
            <form method="post" class="inline" data-confirm="<?= e(((int) $c['activa'] ? '¿Desactivar ' : '¿Activar ') . $c['categoria'] . '? ' . ((int) $c['activa'] ? 'No se podrá elegir en pedidos nuevos; los existentes no cambian.' : '')) ?>">
              <?= campoCsrf() ?><input type="hidden" name="accion" value="estado"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
              <button class="btn btn-sm <?= (int) $c['activa'] ? 'btn-danger-ghost' : 'btn-secondary' ?>"><?= (int) $c['activa'] ? 'Desactivar' : 'Activar' ?></button>
            </form>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>

  <section class="card sticky">
    <div class="card-head"><h3><?= icono($editar ? 'edit' : 'plus', 18) ?> <?= $editar ? 'Editar categoría' : 'Nueva categoría' ?></h3>
      <?php if ($editar): ?><a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/categorias.php')) ?>">Cancelar</a><?php endif; ?></div>
    <form method="post" class="stack">
      <?= campoCsrf() ?>
      <input type="hidden" name="accion" value="guardar">
      <input type="hidden" name="id" value="<?= (int) ($editar['id'] ?? 0) ?>">
      <label class="field"><span>Nombre</span><input type="text" name="categoria" value="<?= e($f['categoria'] ?? '') ?>" required minlength="3" maxlength="80"></label>
      <label class="field"><span>Descripción</span><input type="text" name="descripcion" value="<?= e($f['descripcion'] ?? '') ?>" maxlength="255" placeholder="Ej: Vinilos, lonas y letreros"></label>
      <div class="grid-2">
        <label class="field"><span>Precio de referencia</span><div class="input-prefix"><span>$</span><input type="number" name="precio_por_unidad" value="<?= e(isset($f['precio_por_unidad']) ? (float) $f['precio_por_unidad'] : '') ?>" min="0" step="1" required></div></label>
        <label class="field"><span>Unidad</span>
          <select name="unidad"><?php foreach (['m2' => 'm² (metro cuadrado)', 'unidad' => 'Unidad', 'ml' => 'Metro lineal', 'juego' => 'Juego'] as $k => $t): ?><option value="<?= $k ?>" <?= ($f['unidad'] ?? '') === $k ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?></select>
        </label>
      </div>
      <p class="hint">Cambiar el precio no modifica los pedidos ya registrados: cada pedido conserva el precio con el que se creó.</p>
      <button class="btn btn-primary btn-block"><?= icono('check', 16) ?> <?= $editar ? 'Guardar cambios' : 'Crear categoría' ?></button>
    </form>
  </section>
</div>
<?php layoutFin(); ?>
