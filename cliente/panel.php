<?php
// @spec RF-PED-05
require __DIR__ . '/../includes/bootstrap.php';
$u = requiereRol('cliente');

$todos = consultarPedidos($pdo, ['cliente_id' => $u['id'], 'incluir_borradores' => true]);

$grupos = [
    'todos'      => fn($p) => $p['estado'] !== 'Borrador',
    'proceso'    => fn($p) => in_array($p['estado'], ESTADOS_ACTIVOS, true),
    'entregados' => fn($p) => $p['estado'] === 'Entregado',
    'cancelados' => fn($p) => $p['estado'] === 'Cancelado',
    'borradores' => fn($p) => $p['estado'] === 'Borrador',
];
$conteo = array_map(fn($f) => count(array_filter($todos, $f)), $grupos);
$ver = array_key_exists($_GET['ver'] ?? '', $grupos) ? $_GET['ver'] : 'todos';
$pedidos = array_values(array_filter($todos, $grupos[$ver]));

$listos = count(array_filter($todos, fn($p) => $p['estado'] === 'Listo para entrega'));
$pasos = pasosEstado();

layoutInicio('Mis pedidos', 'pedidos', 'Consulta el estado y el historial de tus solicitudes');
?>
<div class="kpi-grid">
  <div class="kpi kpi-cyan"><span class="kpi-ico"><?= icono('layers') ?></span><div><small>En proceso</small><strong><?= $conteo['proceso'] ?></strong></div></div>
  <div class="kpi kpi-teal"><span class="kpi-ico"><?= icono('truck') ?></span><div><small>Listos para entrega</small><strong><?= $listos ?></strong></div></div>
  <div class="kpi kpi-green"><span class="kpi-ico"><?= icono('check') ?></span><div><small>Entregados</small><strong><?= $conteo['entregados'] ?></strong></div></div>
  <div class="kpi kpi-violet"><span class="kpi-ico"><?= icono('edit') ?></span><div><small>Borradores</small><strong><?= $conteo['borradores'] ?></strong></div></div>
</div>

<section class="card">
  <div class="card-head wrap">
    <div class="pill-tabs">
      <?php foreach (['todos' => 'Todos', 'proceso' => 'En proceso', 'entregados' => 'Entregados', 'cancelados' => 'Cancelados', 'borradores' => 'Borradores'] as $k => $t): ?>
        <a href="<?= e(url('/cliente/panel.php?ver=' . $k)) ?>" class="<?= $ver === $k ? 'active' : '' ?>"><?= $t ?> <span class="count"><?= $conteo[$k] ?></span></a>
      <?php endforeach; ?>
    </div>
    <a href="<?= e(url('/cliente/crear_pedido.php')) ?>" class="btn btn-primary"><?= icono('plus', 16) ?> Nuevo pedido</a>
  </div>

  <?php if (!$pedidos): ?>
    <?= estadoVacio('box', $ver === 'todos' ? 'Aún no tienes pedidos' : 'No hay pedidos en esta vista',
        $ver === 'todos' ? 'Crea tu primer pedido y síguelo en tiempo real desde aquí.' : 'Prueba con otro filtro.',
        $ver === 'todos' ? '<a class="btn btn-primary" href="' . e(url('/cliente/crear_pedido.php')) . '">' . icono('plus', 16) . ' Crear pedido</a>' : '') ?>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Pedido</th><th>Trabajo</th><th>Cantidad</th><th>Entrega</th><th>Costo estimado</th><th>Progreso</th><th>Estado</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($pedidos as $p):
          $idx = array_search($p['estado'], $pasos, true);
          $pct = $idx === false ? 0 : (int) round(($idx / (count($pasos) - 1)) * 100);
          $destino = $p['estado'] === 'Borrador' ? '/cliente/crear_pedido.php?borrador=' . $p['id'] : '/pedido.php?id=' . $p['id'];
      ?>
        <tr data-href="<?= e(url($destino)) ?>">
          <td class="mono"><?= e(codigoPedido($p['id'])) ?></td>
          <td><strong><?= e($p['tipo_trabajo']) ?></strong><small class="block muted"><?= e($p['categoria']) ?></small></td>
          <td><?= e(formatoCantidad($p['cantidad'])) ?> <?= e($p['unidad']) ?></td>
          <td><?= e(formatoFecha($p['fecha_entrega'])) ?> <?= etiquetaEntrega($p) ?></td>
          <td class="mono"><?= e(formatoMoneda($p['costo_estimado'])) ?></td>
          <td><div class="progress <?= $p['estado'] === 'Cancelado' ? 'progress-off' : '' ?>"><span style="width: <?= $pct ?>%"></span></div></td>
          <td><?= badgeEstado($p['estado']) ?></td>
          <td><a class="btn btn-ghost btn-sm" href="<?= e(url($destino)) ?>"><?= $p['estado'] === 'Borrador' ? 'Continuar' : 'Ver' ?></a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>
<?php layoutFin(); ?>
