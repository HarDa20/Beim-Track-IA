<?php
// @spec RF-ADM-02
// Gestión de todos los pedidos con filtros y asignación de responsables.
require __DIR__ . '/../includes/bootstrap.php';
$u = requiereRol('administrador');

$filtros = filtrosPedidosAdmin($_GET); // @spec RF-EXP-01: misma lectura de filtros que la exportación
$vista = (string) ($_GET['vista'] ?? '');

$pedidos = consultarPedidos($pdo, $filtros);
$categorias = $pdo->query('SELECT id, categoria FROM categorias_precios ORDER BY categoria')->fetchAll();
$empleados = empleadosActivos($pdo);
$porArea = [];
foreach ($empleados as $emp) {
    $porArea[$emp['area']][] = $emp;
}
$volver = '/admin/pedidos.php' . (($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : '');
$hayFiltros = $filtros['q'] !== '' || $filtros['estado'] !== '' || $filtros['categoria_id'] || $filtros['empleado_id'] !== '' || $filtros['retrasados'] || $vista;

layoutInicio('Pedidos', 'pedidos', count($pedidos) . ' resultado(s)');
?>
<section class="card">
  <form method="get" class="filters" data-autosubmit>
    <div class="input-icon grow"><?= icono('search', 16) ?><input type="search" name="q" value="<?= e($filtros['q']) ?>" placeholder="Buscar por número, cliente o trabajo…"></div>
    <select name="estado">
      <option value="">Todos los estados</option>
      <?php foreach (ESTADOS_PEDIDO as $est): if ($est === 'Borrador') continue; ?>
        <option <?= $filtros['estado'] === $est ? 'selected' : '' ?>><?= e($est) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="categoria_id">
      <option value="0">Todas las categorías</option>
      <?php foreach ($categorias as $c): ?>
        <option value="<?= (int) $c['id'] ?>" <?= $filtros['categoria_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['categoria']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="empleado_id">
      <option value="">Cualquier responsable</option>
      <option value="ninguno" <?= $filtros['empleado_id'] === 'ninguno' ? 'selected' : '' ?>>Sin responsable</option>
      <?php foreach ($empleados as $emp): ?>
        <option value="<?= (int) $emp['id'] ?>" <?= $filtros['empleado_id'] === (string) $emp['id'] ? 'selected' : '' ?>><?= e($emp['nombre']) ?> (<?= e($emp['area']) ?>)</option>
      <?php endforeach; ?>
    </select>
    <label class="check check-inline"><input type="checkbox" name="retrasados" value="1" <?= $filtros['retrasados'] ? 'checked' : '' ?>> <span>Solo retrasados</span></label>
    <?php if ($vista): ?><input type="hidden" name="vista" value="<?= e($vista) ?>"><?php endif; ?>
    <button class="btn btn-secondary"><?= icono('filter', 16) ?> Filtrar</button>
    <?php if ($hayFiltros): ?><a class="btn btn-ghost" href="<?= e(url('/admin/pedidos.php')) ?>">Limpiar</a><?php endif; ?>
    <a class="btn btn-secondary" href="<?= e(url('/admin/exportar_pedidos.php' . (($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : ''))) ?>" title="Descargar en CSV para Excel los pedidos de esta lista"><?= icono('download', 16) ?> Exportar CSV</a>
  </form>

  <?php if ($vista): ?>
    <p class="hint">Vista: <strong><?= $vista === 'proceso' ? 'En producción (diseño, producción y calidad)' : 'Pedidos activos' ?></strong></p>
  <?php endif; ?>

  <?php if (!$pedidos): ?>
    <?= estadoVacio('box', $hayFiltros ? 'Ningún pedido coincide' : 'Todavía no hay pedidos', $hayFiltros ? 'Ajusta o limpia los filtros.' : 'Los pedidos de clientes y los registrados por Recepción aparecerán aquí.',
        '<a class="btn btn-primary" href="' . e(url('/empleado/registrar_pedido.php')) . '">' . icono('plus', 16) . ' Registrar pedido</a>') ?>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Pedido</th><th>Cliente</th><th>Trabajo</th><th>Entrega</th><th>Costo</th><th>Estado</th><th>Responsable de la etapa</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($pedidos as $p): $area = areaDeEstado($p['estado']); ?>
        <tr class="<?= in_array($p['estado'], ESTADOS_ACTIVOS, true) && $p['fecha_entrega'] < hoy() ? 'row-danger' : '' ?>">
          <td class="mono"><a href="<?= e(url('/pedido.php?id=' . $p['id'])) ?>"><?= e(codigoPedido($p['id'])) ?></a><small class="block muted"><?= e($p['canal_recepcion']) ?></small></td>
          <td><?= e($p['cliente_nombre']) ?></td>
          <td><strong><?= e($p['tipo_trabajo']) ?></strong><small class="block muted"><?= e($p['categoria']) ?> · <?= e(formatoCantidad($p['cantidad'])) ?> <?= e($p['unidad']) ?></small></td>
          <td class="nowrap"><?= e(formatoFecha($p['fecha_entrega'])) ?> <?= etiquetaEntrega($p) ?></td>
          <td class="mono"><?= e(formatoMoneda($p['costo_estimado'])) ?></td>
          <td><?= badgeEstado($p['estado']) ?></td>
          <td>
            <?php if ($area): ?>
              <form action="<?= e(url('/admin/asignar_empleado.php')) ?>" method="post" class="assign">
                <?= campoCsrf() ?>
                <input type="hidden" name="pedido_id" value="<?= (int) $p['id'] ?>">
                <input type="hidden" name="volver" value="<?= e($volver) ?>">
                <select name="empleado_id" class="input-sm" aria-label="Responsable">
                  <option value="">— <?= e($area) ?> (sin asignar)</option>
                  <?php foreach ($porArea[$area] ?? [] as $emp): ?>
                    <option value="<?= (int) $emp['id'] ?>" <?= (int) $p['empleado_id'] === (int) $emp['id'] ? 'selected' : '' ?>><?= e($emp['nombre']) ?></option>
                  <?php endforeach; ?>
                </select>
                <button class="btn btn-ghost btn-sm" title="Guardar responsable"><?= icono('check', 14) ?></button>
              </form>
            <?php else: ?>
              <span class="muted"><?= $p['empleado_nombre'] ? e($p['empleado_nombre']) : '—' ?></span>
            <?php endif; ?>
          </td>
          <td><a class="btn btn-ghost btn-sm" href="<?= e(url('/pedido.php?id=' . $p['id'])) ?>">Ver</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>
<?php layoutFin(); ?>
