<?php
// @spec RF-ADM-06
// Historial global de actividad y registro de notificaciones externas.
require __DIR__ . '/../includes/bootstrap.php';
$u = requiereRol('administrador');

$tab = ($_GET['tab'] ?? '') === 'notificaciones' ? 'notificaciones' : 'historial';
$pagina = max(1, (int) ($_GET['p'] ?? 1));
$porPagina = 50;
$offset = ($pagina - 1) * $porPagina;

if ($tab === 'historial') {
    $total = (int) $pdo->query('SELECT COUNT(*) FROM historial_pedido')->fetchColumn();
    $filas = $pdo->query('SELECT h.*, u.nombre AS usuario_nombre, u.rol AS usuario_rol, u.area AS usuario_area, p.tipo_trabajo
                          FROM historial_pedido h
                          LEFT JOIN usuarios u ON u.id = h.usuario_id
                          LEFT JOIN pedidos p ON p.id = h.pedido_id
                          ORDER BY h.fecha DESC, h.id DESC LIMIT ' . $porPagina . ' OFFSET ' . $offset)->fetchAll();
} else {
    $total = (int) $pdo->query('SELECT COUNT(*) FROM notificaciones_envios')->fetchColumn();
    $filas = $pdo->query('SELECT * FROM notificaciones_envios ORDER BY creado_en DESC, id DESC LIMIT ' . $porPagina . ' OFFSET ' . $offset)->fetchAll();
    $resumen = array_fill_keys(['enviado', 'fallido', 'omitido'], 0);
    foreach ($pdo->query('SELECT estado, COUNT(*) AS n FROM notificaciones_envios GROUP BY estado') as $r) {
        $resumen[$r['estado']] = (int) $r['n'];
    }
    $canales = estadoCanales();
}
$paginas = max(1, (int) ceil($total / $porPagina));

layoutInicio('Actividad', 'actividad', 'Trazabilidad completa del sistema');
?>
<div class="page-head">
  <div class="pill-tabs">
    <a href="<?= e(url('/admin/actividad.php')) ?>" class="<?= $tab === 'historial' ? 'active' : '' ?>">Historial de pedidos</a>
    <a href="<?= e(url('/admin/actividad.php?tab=notificaciones')) ?>" class="<?= $tab === 'notificaciones' ? 'active' : '' ?>">Notificaciones externas</a>
  </div>
</div>

<?php if ($tab === 'notificaciones'): ?>
<div class="kpi-grid kpi-grid-3">
  <?php foreach (['correo' => ['Correo SMTP', 'mail']] as $k => [$titulo, $ico]): $c = $canales[$k]; ?>
    <div class="kpi <?= $c['activo'] ? 'kpi-green' : 'kpi-amber' ?>"><span class="kpi-ico"><?= icono($ico) ?></span><div><small><?= $titulo ?></small><strong class="kpi-text"><?= $c['activo'] ? 'Configurado' : 'Sin configurar' ?></strong><em><?= $c['activo'] ? 'Envíos reales activos' : 'Requiere: ' . e($c['requisitos']) ?></em></div></div>
  <?php endforeach; ?>
  <div class="kpi kpi-cyan"><span class="kpi-ico"><?= icono('check') ?></span><div><small>Enviados</small><strong><?= $resumen['enviado'] ?></strong></div></div>
  <div class="kpi kpi-red"><span class="kpi-ico"><?= icono('alert') ?></span><div><small>Fallidos / omitidos</small><strong><?= $resumen['fallido'] ?> / <?= $resumen['omitido'] ?></strong></div></div>
</div>
<?php endif; ?>

<section class="card">
  <?php if (!$filas): ?>
    <?= $tab === 'historial'
        ? estadoVacio('activity', 'Sin actividad registrada', 'Cada registro, cambio de estado, asignación y archivo adjunto quedará aquí.')
        : estadoVacio('bell', 'Sin notificaciones', 'Cuando ocurran eventos de pedidos se registrará cada intento de aviso por correo electrónico.') ?>
  <?php elseif ($tab === 'historial'): ?>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Fecha</th><th>Pedido</th><th>Usuario</th><th>Cambio</th><th>Descripción</th></tr></thead>
        <tbody>
        <?php foreach ($filas as $h): ?>
          <tr>
            <td class="nowrap"><?= e(formatoFechaHora($h['fecha'])) ?></td>
            <td class="mono"><a href="<?= e(url('/pedido.php?id=' . $h['pedido_id'])) ?>"><?= e(codigoPedido($h['pedido_id'])) ?></a></td>
            <td><?= e($h['usuario_nombre'] ?? 'Sistema') ?><?= $h['usuario_area'] ? '<small class="block muted">' . e($h['usuario_area']) . '</small>' : '' ?></td>
            <td class="nowrap"><?= $h['estado_nuevo'] ? ($h['estado_anterior'] ? badgeEstado($h['estado_anterior']) . ' → ' : '') . badgeEstado($h['estado_nuevo']) : '<span class="muted">—</span>' ?></td>
            <td><?= e($h['descripcion']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Fecha</th><th>Canal</th><th>Destinatario</th><th>Evento</th><th>Estado</th><th>Detalle</th></tr></thead>
        <tbody>
        <?php foreach ($filas as $n): ?>
          <tr>
            <td class="nowrap"><?= e(formatoFechaHora($n['creado_en'])) ?></td>
            <td><?= icono('mail', 14) ?> Correo</td>
            <td><?= e($n['destinatario']) ?></td>
            <td><?= e(str_replace('_', ' ', $n['evento'])) ?><?= $n['pedido_id'] ? ' · <a class="mono" href="' . e(url('/pedido.php?id=' . $n['pedido_id'])) . '">' . e(codigoPedido($n['pedido_id'])) . '</a>' : '' ?></td>
            <td><span class="chip <?= ['enviado' => 'chip-ok', 'fallido' => 'chip-danger', 'omitido' => 'chip-warn'][$n['estado']] ?>"><?= e(ucfirst($n['estado'])) ?></span></td>
            <td class="muted small"><?= e($n['detalle_error'] ?? $n['mensaje']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <?php if ($paginas > 1): ?>
    <nav class="pager">
      <?php for ($i = 1; $i <= $paginas; $i++): ?>
        <a href="<?= e(url('/admin/actividad.php?tab=' . $tab . '&p=' . $i)) ?>" class="<?= $i === $pagina ? 'active' : '' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </nav>
  <?php endif; ?>
</section>
<?php layoutFin(); ?>
