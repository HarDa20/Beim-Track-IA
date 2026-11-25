<?php
// Centro de alertas internas del usuario.
require __DIR__ . '/includes/bootstrap.php';
$u = requiereLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requierePostConCsrf('/alertas.php');
    if (($_POST['accion'] ?? '') === 'todas') {
        $pdo->prepare('UPDATE alertas SET leida = 1 WHERE usuario_id = ?')->execute([$u['id']]);
        flash('exito', 'Todas las alertas quedaron marcadas como leídas.');
        redirigir('/alertas.php');
    }
    // Abrir una alerta: se marca como leída y se va al pedido.
    $stmt = $pdo->prepare('SELECT * FROM alertas WHERE id = ? AND usuario_id = ?');
    $stmt->execute([(int) ($_POST['alerta_id'] ?? 0), $u['id']]);
    $a = $stmt->fetch();
    if ($a) {
        $pdo->prepare('UPDATE alertas SET leida = 1 WHERE id = ?')->execute([$a['id']]);
        if ($a['pedido_id']) {
            redirigir('/pedido.php?id=' . (int) $a['pedido_id']);
        }
    }
    redirigir('/alertas.php');
}

if ($u['rol'] !== 'cliente') {
    revisarVencimientos($pdo);
}
$filtro = ($_GET['ver'] ?? '') === 'no_leidas';
$alertas = alertasDeUsuario($pdo, (int) $u['id'], 100, $filtro);
$noLeidas = contarAlertasNoLeidas($pdo, (int) $u['id']);

layoutInicio('Alertas', 'alertas', $noLeidas . ' sin leer');
?>
<div class="page-head">
  <div class="pill-tabs">
    <a href="<?= e(url('/alertas.php')) ?>" class="<?= !$filtro ? 'active' : '' ?>">Todas</a>
    <a href="<?= e(url('/alertas.php?ver=no_leidas')) ?>" class="<?= $filtro ? 'active' : '' ?>">Sin leer <span class="count"><?= $noLeidas ?></span></a>
  </div>
  <?php if ($noLeidas): ?>
  <form method="post" action="<?= e(url('/alertas.php')) ?>">
    <?= campoCsrf() ?><input type="hidden" name="accion" value="todas">
    <button class="btn btn-ghost"><?= icono('check', 16) ?> Marcar todas como leídas</button>
  </form>
  <?php endif; ?>
</div>

<section class="card">
  <?php if (!$alertas): ?>
    <?= estadoVacio('bell', 'Sin alertas', $filtro ? 'No tienes alertas pendientes por leer.' : 'Aquí verás los avisos de pedidos nuevos, asignaciones, cambios de estado y vencimientos.') ?>
  <?php else: ?>
    <ul class="alert-list">
      <?php foreach ($alertas as $a): ?>
        <li class="alert-item tipo-<?= e($a['tipo']) ?> <?= $a['leida'] ? '' : 'unread' ?>">
          <span class="alert-ico"><?= icono(iconoAlerta($a['tipo'])) ?></span>
          <div class="grow">
            <p><?= e($a['mensaje']) ?></p>
            <small><?= e(tiempoRelativo($a['creado_en'])) ?> · <?= e(formatoFechaHora($a['creado_en'])) ?></small>
          </div>
          <form method="post" action="<?= e(url('/alertas.php')) ?>">
            <?= campoCsrf() ?><input type="hidden" name="alerta_id" value="<?= (int) $a['id'] ?>">
            <button class="btn btn-ghost btn-sm"><?= $a['pedido_id'] ? 'Ver pedido' : 'Marcar leída' ?></button>
          </form>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>
<?php layoutFin(); ?>
