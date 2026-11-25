<?php
// Panel gerencial: indicadores para la toma de decisiones (spec 015).
// @spec RF-GER-01, RF-GER-02, RF-GER-03, RF-GER-04
require __DIR__ . '/../includes/bootstrap.php';
$u = requiereRol('administrador');

$dias = (int) ($_GET['dias'] ?? 90);
if (!isset(PERIODOS_GERENCIA[$dias])) {
    $dias = 90;
}
$g = indicadoresGerenciales($pdo, $dias);
$maxCategoria = max([1] + array_map(fn($c) => (float) $c['valor'], $g['por_categoria']));
$maxCanal = max([1] + array_map(fn($c) => (int) $c['pedidos'], $g['por_canal']));
$maxCarga = max([1] + array_map(fn($c) => (int) $c['activos'], $g['carga']));
$maxHoras = max([1] + array_filter(array_column($g['etapas'], 'horas')));
$formatoHoras = fn(?float $h) => $h === null ? 'sin datos' : ($h >= 24 ? number_format($h / 24, 1, ',', '.') . ' días' : number_format($h, 1, ',', '.') . ' h');

layoutInicio('Panel gerencial', 'gerencia', 'Indicadores para la toma de decisiones · ' . PERIODOS_GERENCIA[$dias]);
?>
<div class="page-head no-print">
  <nav class="pill-tabs ger-periodo" aria-label="Periodo">
    <?php foreach (PERIODOS_GERENCIA as $d => $txt): ?>
      <a href="<?= e(url('/admin/gerencia.php?dias=' . $d)) ?>" class="<?= $d === $dias ? 'active' : '' ?>"><?= e($txt) ?></a>
    <?php endforeach; ?>
  </nav>
  <button type="button" class="btn btn-secondary" data-imprimir><?= icono('printer', 16) ?> Imprimir / PDF</button>
</div>

<div class="kpi-grid kpi-grid-6">
  <div class="kpi kpi-green"><span class="kpi-ico"><?= icono('trend') ?></span><div><small>Ingresos entregados</small><strong data-ingresos="<?= e(number_format($g['ingresos'], 2, '.', '')) ?>"><?= formatoMonedaCorta($g['ingresos']) ?></strong><em><?= $g['entregados'] ?> pedido(s) entregados</em></div></div>
  <div class="kpi kpi-cyan"><span class="kpi-ico"><?= icono('cog') ?></span><div><small>Valor en proceso</small><strong><?= formatoMonedaCorta($g['valor_proceso']) ?></strong><em><?= $g['en_proceso'] ?> pedido(s) activos hoy</em></div></div>
  <div class="kpi kpi-indigo"><span class="kpi-ico"><?= icono('tag') ?></span><div><small>Ticket promedio</small><strong><?= $g['ticket_promedio'] === null ? '—' : formatoMonedaCorta($g['ticket_promedio']) ?></strong><em>por pedido entregado</em></div></div>
  <div class="kpi kpi-violet"><span class="kpi-ico"><?= icono('shield') ?></span><div><small>Entregas a tiempo</small><strong><?= $g['a_tiempo_pct'] === null ? '—' : $g['a_tiempo_pct'] . '%' ?></strong><em>frente a la fecha pactada</em></div></div>
  <div class="kpi kpi-amber"><span class="kpi-ico"><?= icono('clock') ?></span><div><small>Ciclo promedio</small><strong><?= $g['ciclo_dias'] === null ? '—' : number_format($g['ciclo_dias'], 1, ',', '.') . ' d' ?></strong><em>de registrado a entregado</em></div></div>
  <div class="kpi kpi-red"><span class="kpi-ico"><?= icono('x') ?></span><div><small>Cancelación</small><strong><?= $g['cancelacion_pct'] === null ? '—' : $g['cancelacion_pct'] . '%' ?></strong><em><?= $g['cancelados'] ?> de <?= $g['pedidos_periodo'] ?> pedido(s)</em></div></div>
</div>

<div class="ger-grid">
  <section class="card">
    <div class="card-head"><h3><?= icono('clock') ?> Tiempo promedio por etapa</h3></div>
    <ul class="ger-etapas">
      <?php foreach ($g['etapas'] as $estado => $e): ?>
        <li class="<?= $estado === $g['cuello'] ? 'is-cuello' : '' ?>">
          <div class="hbar-top"><span><?= e($estado) ?> <small class="muted">· <?= e($e['area']) ?></small></span><strong><?= e($formatoHoras($e['horas'])) ?></strong></div>
          <div class="hbar"><span style="width: <?= $e['horas'] === null ? 0 : round($e['horas'] / $maxHoras * 100) ?>%"></span></div>
        </li>
      <?php endforeach; ?>
    </ul>
    <?php if ($g['cuello']): ?>
      <div class="ger-cuello"><?= icono('alert', 18) ?><span>Cuello de botella: <strong><?= e($g['cuello']) ?></strong> (área de <?= e(areaDeEstado($g['cuello'])) ?>). Es la etapa donde más se demoran los pedidos.</span></div>
    <?php else: ?>
      <p class="ger-nota">Aún no hay suficientes cambios de estado en el periodo para medir tiempos.</p>
    <?php endif; ?>
  </section>

  <section class="card">
    <div class="card-head"><h3><?= icono('users') ?> Mejores clientes</h3></div>
    <?php if (!$g['top_clientes']): ?>
      <?= estadoVacio('users', 'Sin pedidos en el periodo', 'Cuando los clientes hagan pedidos aparecerán aquí.') ?>
    <?php else: ?>
      <div class="table-wrap"><table class="table">
        <thead><tr><th>#</th><th>Cliente</th><th>Pedidos</th><th>Valor</th></tr></thead>
        <tbody>
        <?php foreach ($g['top_clientes'] as $i => $c): ?>
          <tr><td><?= $i + 1 ?></td><td><div class="user-cell"><?= avatarHtml($c, 'avatar-sm') ?><span><?= e($c['nombre']) ?></span></div></td><td><?= (int) $c['pedidos'] ?></td><td><strong><?= formatoMoneda($c['valor']) ?></strong></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    <?php endif; ?>
  </section>

  <section class="card">
    <div class="card-head"><h3><?= icono('tag') ?> Ventas por categoría</h3></div>
    <?php if (!$g['por_categoria']): ?>
      <p class="ger-nota">Sin ventas en el periodo.</p>
    <?php else: ?>
      <ul class="hbars">
        <?php foreach ($g['por_categoria'] as $c): ?>
          <li><div class="hbar-top"><span><?= e($c['categoria']) ?></span><strong><?= formatoMonedaCorta($c['valor']) ?></strong></div>
            <div class="hbar"><span style="width: <?= round((float) $c['valor'] / $maxCategoria * 100) ?>%"></span></div><small class="muted"><?= (int) $c['pedidos'] ?> pedido(s)</small></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>

  <section class="card">
    <div class="card-head"><h3><?= icono('inbox') ?> Canal de llegada y carga del equipo</h3></div>
    <ul class="hbars">
      <?php foreach ($g['por_canal'] as $c): ?>
        <li><div class="hbar-top"><span><?= e($c['canal']) ?></span><strong><?= (int) $c['pedidos'] ?></strong></div>
          <div class="hbar hbar-violet"><span style="width: <?= round((int) $c['pedidos'] / $maxCanal * 100) ?>%"></span></div></li>
      <?php endforeach; ?>
    </ul>
    <div class="mini-stats" style="grid-template-columns: 1fr">
      <?php if (!$g['carga']): ?>
        <p class="ger-nota">Ningún empleado tiene pedidos asignados en este momento.</p>
      <?php else: ?>
        <ul class="hbars">
          <?php foreach ($g['carga'] as $c): ?>
            <li><div class="hbar-top"><span class="user-cell"><?= avatarHtml($c, 'avatar-sm') ?><?= e($c['nombre']) ?> <small class="muted">· <?= e($c['area'] ?? '') ?></small></span><strong><?= (int) $c['activos'] ?> activo(s)</strong></div>
              <div class="hbar"><span style="width: <?= round((int) $c['activos'] / $maxCarga * 100) ?>%"></span></div></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </section>
</div>
<p class="ger-nota">Ingresos = suma del costo estimado de los pedidos entregados en el periodo. "A tiempo" = entregado en o antes de la fecha pactada con el cliente.</p>
<?php layoutFin(); ?>
