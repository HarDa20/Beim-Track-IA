<?php
// @spec RF-ADM-01
// Dashboard del administrador: todos los indicadores se calculan con
// consultas sobre la base de datos; si no hay datos se muestra un estado vacío.
require __DIR__ . '/../includes/bootstrap.php';
$u = requiereRol('administrador');
revisarVencimientos($pdo);

$hoy = hoy();
$activosSql = "'" . implode("','", ESTADOS_ACTIVOS) . "'";

// Conteo por estado
$porEstado = array_fill_keys(ESTADOS_PEDIDO, 0);
foreach ($pdo->query('SELECT estado, COUNT(*) AS n FROM pedidos GROUP BY estado') as $r) {
    $porEstado[$r['estado']] = (int) $r['n'];
}
unset($porEstado['Borrador']); // los borradores no son pedidos recibidos por la empresa
$total = array_sum($porEstado);
$enProceso = $porEstado['En diseño'] + $porEstado['En producción'] + $porEstado['Control de calidad'];

$stmt = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE estado IN ($activosSql) AND fecha_entrega < ?");
$stmt->execute([$hoy]);
$retrasados = (int) $stmt->fetchColumn();

$valorProceso = (float) $pdo->query("SELECT COALESCE(SUM(costo_estimado), 0) FROM pedidos WHERE estado IN ($activosSql)")->fetchColumn();

// Cumplimiento: entregados en o antes de la fecha solicitada
$c = $pdo->query("SELECT COUNT(*) AS total,
                         SUM(CASE WHEN DATE(fecha_entregado) <= fecha_entrega THEN 1 ELSE 0 END) AS a_tiempo
                  FROM pedidos WHERE estado = 'Entregado' AND fecha_entregado IS NOT NULL")->fetch();
$entregadosMedibles = (int) $c['total'];
$cumplimiento = $entregadosMedibles ? round(((int) $c['a_tiempo'] / $entregadosMedibles) * 100) : null;

// Tiempo promedio de ciclo (días desde el registro hasta la entrega)
$ciclos = $pdo->query("SELECT creado_en, fecha_entregado FROM pedidos WHERE estado = 'Entregado' AND fecha_entregado IS NOT NULL")->fetchAll();
$promedioDias = $ciclos
    ? round(array_sum(array_map(fn($r) => (strtotime($r['fecha_entregado']) - strtotime($r['creado_en'])) / 86400, $ciclos)) / count($ciclos), 1)
    : null;

// Pedidos por categoría
$porCategoria = $pdo->query("SELECT c.categoria, COUNT(*) AS n, COALESCE(SUM(p.costo_estimado), 0) AS valor
                             FROM pedidos p JOIN categorias_precios c ON c.id = p.categoria_id
                             WHERE p.estado <> 'Borrador' GROUP BY c.id, c.categoria ORDER BY n DESC")->fetchAll();
$maxCategoria = $porCategoria ? max(array_column($porCategoria, 'n')) : 0;

// Pedidos registrados y entregados por mes (últimos 6 meses)
$meses = [];
for ($i = 5; $i >= 0; $i--) {
    $meses[date('Y-m', strtotime(date('Y-m-01') . " -$i months"))] = ['creados' => 0, 'entregados' => 0];
}
$desde = array_key_first($meses) . '-01 00:00:00';
$stmt = $pdo->prepare("SELECT SUBSTR(creado_en, 1, 7) AS mes, COUNT(*) AS n FROM pedidos WHERE estado <> 'Borrador' AND creado_en >= ? GROUP BY SUBSTR(creado_en, 1, 7)");
$stmt->execute([$desde]);
foreach ($stmt as $r) {
    if (isset($meses[$r['mes']])) $meses[$r['mes']]['creados'] = (int) $r['n'];
}
$stmt = $pdo->prepare("SELECT SUBSTR(fecha_entregado, 1, 7) AS mes, COUNT(*) AS n FROM pedidos WHERE fecha_entregado >= ? GROUP BY SUBSTR(fecha_entregado, 1, 7)");
$stmt->execute([$desde]);
foreach ($stmt as $r) {
    if (isset($meses[$r['mes']])) $meses[$r['mes']]['entregados'] = (int) $r['n'];
}
$maxMes = max(1, ...array_values(array_map(fn($m) => max($m["creados"], $m["entregados"]), $meses)));
$hayMeses = array_sum(array_map(fn($m) => $m['creados'] + $m['entregados'], $meses)) > 0;

// Carga de trabajo por área
$carga = [];
foreach (AREAS as $a) {
    $carga[$a] = ['total' => 0, 'sin' => 0];
}
foreach ($pdo->query("SELECT estado, empleado_id FROM pedidos WHERE estado IN ($activosSql)") as $r) {
    $a = areaDeEstado($r['estado']);
    $carga[$a]['total']++;
    if (!$r['empleado_id']) $carga[$a]['sin']++;
}
$maxCarga = max(1, ...array_values(array_column($carga, "total")));

// Próximas entregas y actividad reciente
$stmt = $pdo->prepare("SELECT p.id, p.tipo_trabajo, p.estado, p.fecha_entrega, c.nombre AS cliente_nombre
                       FROM pedidos p JOIN usuarios c ON c.id = p.cliente_id
                       WHERE p.estado IN ($activosSql) ORDER BY p.fecha_entrega ASC LIMIT 6");
$stmt->execute();
$proximos = $stmt->fetchAll();

$actividad = $pdo->query('SELECT h.*, u.nombre AS usuario_nombre FROM historial_pedido h LEFT JOIN usuarios u ON u.id = h.usuario_id ORDER BY h.fecha DESC, h.id DESC LIMIT 8')->fetchAll();
$empleados = (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'empleado' AND activo = 1")->fetchColumn();
$clientes = (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'cliente' AND activo = 1")->fetchColumn();

$coloresEstado = [
    'Recibido' => '#38bdf8', 'En diseño' => '#a78bfa', 'En producción' => '#fbbf24', 'Control de calidad' => '#f472b6',
    'Listo para entrega' => '#2dd4bf', 'Entregado' => '#4ade80', 'Cancelado' => '#f87171',
];

layoutInicio('Dashboard', 'dashboard', 'Indicadores calculados en tiempo real · ' . formatoFecha($hoy));
?>
<div class="kpi-grid kpi-grid-6">
  <a class="kpi kpi-indigo" href="<?= e(url('/admin/pedidos.php')) ?>"><span class="kpi-ico"><?= icono('box') ?></span><div><small>Total de pedidos</small><strong><?= $total ?></strong></div></a>
  <a class="kpi kpi-cyan" href="<?= e(url('/admin/pedidos.php?estado=Recibido')) ?>"><span class="kpi-ico"><?= icono('inbox') ?></span><div><small>Pendientes (recibidos)</small><strong><?= $porEstado['Recibido'] ?></strong></div></a>
  <a class="kpi kpi-amber" href="<?= e(url('/admin/pedidos.php?vista=proceso')) ?>"><span class="kpi-ico"><?= icono('cog') ?></span><div><small>En producción</small><strong><?= $enProceso ?></strong><em>diseño · producción · calidad</em></div></a>
  <a class="kpi kpi-green" href="<?= e(url('/admin/pedidos.php?estado=Entregado')) ?>"><span class="kpi-ico"><?= icono('check') ?></span><div><small>Finalizados</small><strong><?= $porEstado['Entregado'] ?></strong></div></a>
  <a class="kpi kpi-red" href="<?= e(url('/admin/pedidos.php?retrasados=1')) ?>"><span class="kpi-ico"><?= icono('alert') ?></span><div><small>Retrasados</small><strong><?= $retrasados ?></strong></div></a>
  <div class="kpi kpi-violet"><span class="kpi-ico"><?= icono('shield') ?></span><div><small>Cumplimiento</small><strong><?= $cumplimiento === null ? '—' : $cumplimiento . '%' ?></strong><em><?= $cumplimiento === null ? 'sin entregas aún' : $entregadosMedibles . ' entrega(s) medidas' ?></em></div></div>
</div>

<div class="dash-grid">
  <section class="card">
    <div class="card-head"><h3>Distribución por estado</h3><span class="muted"><?= $total ?> pedidos</span></div>
    <?php if (!$total): ?>
      <?= estadoVacio('chart', 'Sin datos todavía', 'La gráfica aparecerá cuando se registre el primer pedido.') ?>
    <?php else: ?>
      <div class="donut-wrap">
        <svg viewBox="0 0 42 42" class="donut" role="img" aria-label="Distribución de pedidos por estado">
          <circle cx="21" cy="21" r="15.915" fill="none" stroke="rgba(255,255,255,.06)" stroke-width="5"/>
          <?php $acum = 0; foreach ($porEstado as $estado => $n): if (!$n) continue; $pct = $n / $total * 100; ?>
            <circle cx="21" cy="21" r="15.915" fill="none" stroke="<?= $coloresEstado[$estado] ?>" stroke-width="5"
                    stroke-dasharray="<?= round($pct, 3) ?> <?= round(100 - $pct, 3) ?>" stroke-dashoffset="<?= round(25 - $acum, 3) ?>"><title><?= e($estado) ?>: <?= $n ?></title></circle>
          <?php $acum += $pct; endforeach; ?>
          <text x="21" y="20.5" class="donut-num"><?= $total ?></text>
          <text x="21" y="25.5" class="donut-lbl">pedidos</text>
        </svg>
        <ul class="legend">
          <?php foreach ($porEstado as $estado => $n): ?>
            <li title="<?= e($estado) ?>: <?= $total ? round($n / $total * 100) : 0 ?>%"><i style="background: <?= $coloresEstado[$estado] ?>"></i><span><?= e($estado) ?></span><strong><?= $n ?></strong><em><?= $total ? round($n / $total * 100) : 0 ?>%</em></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
  </section>

  <section class="card">
    <div class="card-head"><h3>Pedidos por mes</h3><div class="legend-inline"><span><i class="c-cyan"></i>Registrados</span><span><i class="c-green"></i>Entregados</span></div></div>
    <?php if (!$hayMeses): ?>
      <?= estadoVacio('chart', 'Sin movimiento en los últimos 6 meses', 'Aquí verás cuántos pedidos se registran y entregan cada mes.') ?>
    <?php else: ?>
      <div class="columns">
        <?php foreach ($meses as $mes => $v): ?>
          <div class="col">
            <div class="col-bars">
              <span class="bar-cyan" style="height: <?= round($v['creados'] / $maxMes * 100) ?>%" title="<?= $v['creados'] ?> registrados"><b><?= $v['creados'] ?: '' ?></b></span>
              <span class="bar-green" style="height: <?= round($v['entregados'] / $maxMes * 100) ?>%" title="<?= $v['entregados'] ?> entregados"><b><?= $v['entregados'] ?: '' ?></b></span>
            </div>
            <small><?= e(mesCorto((int) substr($mes, 5, 2))) ?></small>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="card">
    <div class="card-head"><h3>Pedidos por categoría</h3></div>
    <?php if (!$porCategoria): ?>
      <?= estadoVacio('tag', 'Sin pedidos por categoría', 'Se mostrará la demanda de cada categoría.') ?>
    <?php else: ?>
      <ul class="hbars">
        <?php foreach ($porCategoria as $cat): ?>
          <li>
            <div class="hbar-top"><span><?= e($cat['categoria']) ?></span><strong><?= (int) $cat['n'] ?></strong></div>
            <div class="hbar"><span style="width: <?= round($cat['n'] / $maxCategoria * 100) ?>%"></span></div>
            <small class="muted">Valor estimado: <?= e(formatoMonedaCorta($cat['valor'])) ?></small>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>

  <section class="card">
    <div class="card-head"><h3>Carga por área</h3><span class="muted"><?= $empleados ?> empleados · <?= $clientes ?> clientes</span></div>
    <ul class="hbars">
      <?php foreach ($carga as $a => $v): ?>
        <li>
          <div class="hbar-top"><span><?= e($a) ?></span><strong><?= $v['total'] ?></strong></div>
          <div class="hbar hbar-violet"><span style="width: <?= round($v['total'] / $maxCarga * 100) ?>%"></span></div>
          <small class="muted"><?= $v['sin'] ?> sin responsable</small>
        </li>
      <?php endforeach; ?>
    </ul>
    <div class="mini-stats">
      <div><small>Tiempo promedio de entrega</small><strong><?= $promedioDias === null ? '—' : ($promedioDias < 1 ? 'menos de 1 día' : $promedioDias . ' días') ?></strong></div>
      <div><small>Valor estimado en proceso</small><strong><?= e(formatoMonedaCorta($valorProceso)) ?></strong></div>
    </div>
  </section>

  <section class="card span-2">
    <div class="card-head"><h3><?= icono('clock', 18) ?> Próximas entregas</h3><a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/pedidos.php?vista=activos')) ?>">Ver todos</a></div>
    <?php if (!$proximos): ?>
      <?= estadoVacio('truck', 'No hay pedidos en proceso', 'Cuando existan pedidos activos verás aquí los más urgentes.') ?>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>Pedido</th><th>Cliente</th><th>Trabajo</th><th>Entrega</th><th>Estado</th></tr></thead>
          <tbody>
          <?php foreach ($proximos as $p): ?>
            <tr data-href="<?= e(url('/pedido.php?id=' . $p['id'])) ?>">
              <td class="mono"><?= e(codigoPedido($p['id'])) ?></td>
              <td><?= e($p['cliente_nombre']) ?></td>
              <td><?= e($p['tipo_trabajo']) ?></td>
              <td><?= e(formatoFecha($p['fecha_entrega'])) ?> <?= etiquetaEntrega($p) ?></td>
              <td><?= badgeEstado($p['estado']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>

  <section class="card span-3">
    <div class="card-head"><h3><?= icono('activity', 18) ?> Actividad reciente</h3><a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/actividad.php')) ?>">Ver todo</a></div>
    <?php if (!$actividad): ?>
      <?= estadoVacio('activity', 'Sin actividad', 'Los movimientos de los pedidos aparecerán aquí.') ?>
    <?php else: ?>
      <ol class="timeline timeline-compact timeline-cols">
        <?php foreach ($actividad as $h): ?>
          <li><span class="tl-dot"></span><div>
            <p><a href="<?= e(url('/pedido.php?id=' . $h['pedido_id'])) ?>" class="mono"><?= e(codigoPedido($h['pedido_id'])) ?></a> · <?= e($h['descripcion']) ?></p>
            <small><?= e(tiempoRelativo($h['fecha'])) ?></small>
          </div></li>
        <?php endforeach; ?>
      </ol>
    <?php endif; ?>
  </section>
</div>
<?php layoutFin(); ?>
