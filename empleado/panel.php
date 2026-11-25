<?php
// @spec RF-FLU-02, RF-FLU-03
require __DIR__ . '/../includes/bootstrap.php';
$u = requiereRol('empleado');
revisarVencimientos($pdo);

$area = $u['area'];
$estadosArea = array_values(array_filter(ESTADOS_ACTIVOS, fn($e) => areaDeEstado($e) === $area));
$marcadores = implode(',', array_fill(0, count($estadosArea), '?'));
$hoy = hoy();
$limite = date('Y-m-d', strtotime('+2 days'));

$stmt = $pdo->prepare(
    "SELECT p.*, c.nombre AS cliente_nombre, e.nombre AS empleado_nombre
     FROM pedidos p JOIN usuarios c ON c.id = p.cliente_id LEFT JOIN usuarios e ON e.id = p.empleado_id
     WHERE p.estado IN ($marcadores) OR (p.empleado_id = ? AND p.estado IN ('" . implode("','", ESTADOS_ACTIVOS) . "'))
     ORDER BY p.fecha_entrega ASC, p.id ASC"
);
$stmt->execute(array_merge($estadosArea, [$u['id']]));
$activos = $stmt->fetchAll();

$mios = array_values(array_filter($activos, fn($p) => (int) $p['empleado_id'] === (int) $u['id']));
$bandeja = array_values(array_filter($activos, fn($p) => empty($p['empleado_id'])));
$porVencer = count(array_filter($activos, fn($p) => $p['fecha_entrega'] >= $hoy && $p['fecha_entrega'] <= $limite));
$retrasados = count(array_filter($activos, fn($p) => $p['fecha_entrega'] < $hoy));

$inicioMes = date('Y-m-01 00:00:00');
$stmt = $pdo->prepare('SELECT COUNT(*) FROM historial_pedido WHERE usuario_id = ? AND estado_nuevo IS NOT NULL AND fecha >= ?');
$stmt->execute([$u['id'], $inicioMes]);
$avancesMes = (int) $stmt->fetchColumn();

$vistas = ['mios' => 'Mi trabajo', 'bandeja' => 'Bandeja del área', 'area' => 'Toda el área'];
$ver = array_key_exists($_GET['ver'] ?? '', $vistas) ? $_GET['ver'] : ($mios ? 'mios' : 'bandeja');
$lista = ['mios' => $mios, 'bandeja' => $bandeja, 'area' => $activos][$ver];
$conteos = ['mios' => count($mios), 'bandeja' => count($bandeja), 'area' => count($activos)];

layoutInicio('Mi bandeja', 'bandeja', 'Área de ' . $area . ' · etapas: ' . implode(', ', $estadosArea));
?>
<div class="kpi-grid kpi-grid-5">
  <div class="kpi kpi-cyan"><span class="kpi-ico"><?= icono('user') ?></span><div><small>Asignados a mí</small><strong><?= count($mios) ?></strong></div></div>
  <div class="kpi kpi-violet"><span class="kpi-ico"><?= icono('inbox') ?></span><div><small>En bandeja del área</small><strong><?= count($bandeja) ?></strong></div></div>
  <div class="kpi kpi-amber"><span class="kpi-ico"><?= icono('clock') ?></span><div><small>Vencen en 2 días</small><strong><?= $porVencer ?></strong></div></div>
  <div class="kpi kpi-red"><span class="kpi-ico"><?= icono('alert') ?></span><div><small>Retrasados</small><strong><?= $retrasados ?></strong></div></div>
  <div class="kpi kpi-green"><span class="kpi-ico"><?= icono('zap') ?></span><div><small>Avances este mes</small><strong><?= $avancesMes ?></strong></div></div>
</div>

<section class="card">
  <div class="card-head wrap">
    <div class="pill-tabs">
      <?php foreach ($vistas as $k => $t): ?>
        <a href="<?= e(url('/empleado/panel.php?ver=' . $k)) ?>" class="<?= $ver === $k ? 'active' : '' ?>"><?= $t ?> <span class="count"><?= $conteos[$k] ?></span></a>
      <?php endforeach; ?>
    </div>
    <?php if (puedeRegistrarPedidos($u)): ?>
      <a href="<?= e(url('/empleado/registrar_pedido.php')) ?>" class="btn btn-primary"><?= icono('plus', 16) ?> Registrar pedido</a>
    <?php endif; ?>
  </div>

  <?php if (!$lista): ?>
    <?= estadoVacio('inbox', 'Nada pendiente por aquí', $ver === 'mios' ? 'No tienes pedidos asignados. Revisa la bandeja del área para tomar uno.' : 'No hay pedidos en las etapas de tu área en este momento.') ?>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Pedido</th><th>Cliente</th><th>Trabajo</th><th>Entrega</th><th>Estado</th><th>Responsable</th><th class="right">Acción</th></tr></thead>
      <tbody>
      <?php foreach ($lista as $p):
          $acciones = accionesDisponibles($u, $p);
          $siguiente = null;
          foreach ($acciones as $nuevo => $etq) {
              if (!transicionRequiereObservacion($p['estado'], $nuevo)) { $siguiente = [$nuevo, $etq]; break; }
          }
          $volver = '/empleado/panel.php?ver=' . $ver;
      ?>
        <tr class="<?= $p['fecha_entrega'] < $hoy ? 'row-danger' : '' ?>">
          <td class="mono"><a href="<?= e(url('/pedido.php?id=' . $p['id'])) ?>"><?= e(codigoPedido($p['id'])) ?></a></td>
          <td><?= e($p['cliente_nombre']) ?></td>
          <td><strong><?= e($p['tipo_trabajo']) ?></strong><small class="block muted"><?= e($p['categoria']) ?> · <?= e(formatoCantidad($p['cantidad'])) ?> <?= e($p['unidad']) ?></small></td>
          <td><?= e(formatoFecha($p['fecha_entrega'])) ?> <?= etiquetaEntrega($p) ?></td>
          <td><?= badgeEstado($p['estado']) ?></td>
          <td><?= $p['empleado_nombre'] ? e($p['empleado_nombre']) : '<span class="muted">Sin tomar</span>' ?></td>
          <td class="right nowrap">
            <?php if (empty($p['empleado_id']) && areaDeEstado($p['estado']) === $area): ?>
              <form action="<?= e(url('/empleado/tomar_pedido.php')) ?>" method="post" class="inline">
                <?= campoCsrf() ?><input type="hidden" name="pedido_id" value="<?= (int) $p['id'] ?>"><input type="hidden" name="volver" value="<?= e($volver) ?>">
                <button class="btn btn-secondary btn-sm"><?= icono('user', 14) ?> Tomar</button>
              </form>
            <?php elseif ($siguiente): ?>
              <form action="<?= e(url('/empleado/actualizar_estado.php')) ?>" method="post" class="inline" data-confirm="<?= e($siguiente[1] . ': ' . codigoPedido($p['id']) . ' pasará a "' . $siguiente[0] . '".') ?>">
                <?= campoCsrf() ?><input type="hidden" name="pedido_id" value="<?= (int) $p['id'] ?>"><input type="hidden" name="nuevo_estado" value="<?= e($siguiente[0]) ?>"><input type="hidden" name="volver" value="<?= e($volver) ?>">
                <button class="btn btn-primary btn-sm"><?= icono('arrow', 14) ?> <?= e($siguiente[1]) ?></button>
              </form>
            <?php endif; ?>
            <a class="btn btn-ghost btn-sm" href="<?= e(url('/pedido.php?id=' . $p['id'])) ?>">Detalle</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>
<?php layoutFin(); ?>
