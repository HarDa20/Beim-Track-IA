<?php
// @spec RF-PED-05, RF-FLU-02
// Detalle de un pedido, compartido por todos los roles con control de acceso.
require __DIR__ . '/includes/bootstrap.php';
$u = requiereLogin();

$id = (int) ($_GET['id'] ?? 0);
$p = $id ? obtenerPedido($pdo, $id) : null;
if (!$p || !puedeVerPedido($pdo, $u, $p)) {
    flash('error', 'El pedido no existe o no tienes acceso a él.');
    redirigirSegunRol($u['rol']);
}

$historial = historialPedido($pdo, $id);
$archivos = archivosDePedido($pdo, $id);
$acciones = accionesDisponibles($u, $p);
$pasos = pasosEstado();
$idxActual = array_search($p['estado'], $pasos, true);
$areaActual = areaDeEstado($p['estado']);
$enProceso = in_array($p['estado'], ESTADOS_ACTIVOS, true);
$puedeTomar = $u['rol'] === 'empleado' && $enProceso && $u['area'] === $areaActual && empty($p['empleado_id']);
$puedeSubir = $p['estado'] !== 'Entregado' && $p['estado'] !== 'Cancelado'
    && ($u['rol'] !== 'cliente' || (int) $p['cliente_id'] === (int) $u['id']);
$empleadosArea = (esAdmin($u) && $areaActual) ? empleadosActivos($pdo, $areaActual) : [];
$volver = ['cliente' => '/cliente/panel.php', 'empleado' => '/empleado/panel.php', 'administrador' => '/admin/pedidos.php'][$u['rol']];
$activo = ['cliente' => 'pedidos', 'empleado' => 'bandeja', 'administrador' => 'pedidos'][$u['rol']];

layoutInicio('Pedido ' . codigoPedido($id), $activo, $p['tipo_trabajo']);
?>
<div class="page-head">
  <a href="<?= e(url($volver)) ?>" class="link-back"><?= icono('back', 16) ?> Volver</a>
  <div class="page-head-actions">
    <?php if ($p['estado'] === 'Borrador' && $u['rol'] === 'cliente'): ?>
      <a class="btn btn-primary" href="<?= e(url('/cliente/crear_pedido.php?borrador=' . $id)) ?>"><?= icono('edit', 16) ?> Continuar borrador</a>
    <?php endif; ?>
  </div>
</div>

<section class="card hero-card">
  <div class="hero-card-top">
    <div>
      <div class="order-code"><?= e(codigoPedido($id)) ?> <?= badgeEstado($p['estado']) ?> <?= etiquetaEntrega($p) ?></div>
      <h2 class="order-title"><?= e($p['tipo_trabajo']) ?></h2>
      <div class="chips">
        <span class="chip"><?= icono('tag', 14) ?> <?= e($p['categoria']) ?></span>
        <span class="chip"><?= icono('layers', 14) ?> <?= e(formatoCantidad($p['cantidad'])) ?> <?= e($p['unidad']) ?></span>
        <span class="chip"><?= icono($p['canal_recepcion'] === 'Llamada' ? 'phone' : ($p['canal_recepcion'] === 'Web' ? 'zap' : 'inbox'), 14) ?> Canal: <?= e($p['canal_recepcion']) ?></span>
      </div>
    </div>
    <div class="cost-box">
      <small>Costo estimado</small>
      <strong><?= e(formatoMoneda($p['costo_estimado'])) ?></strong>
      <span><?= e(formatoMoneda($p['precio_unitario'])) ?> × <?= e(formatoCantidad($p['cantidad'])) ?> <?= e($p['unidad']) ?></span>
    </div>
  </div>

  <?php if ($p['estado'] === 'Cancelado'): ?>
    <div class="banner banner-danger"><?= icono('x', 18) ?> Este pedido fue cancelado. El motivo está registrado en el historial.</div>
  <?php elseif ($p['estado'] === 'Borrador'): ?>
    <div class="banner banner-muted"><?= icono('edit', 18) ?> Borrador sin enviar: la empresa todavía no lo ha recibido.</div>
  <?php else: ?>
    <ol class="stepper">
      <?php foreach ($pasos as $i => $paso):
          $clase = $i < $idxActual ? 'done' : ($i === $idxActual ? ($paso === 'Entregado' ? 'done' : 'current') : '');
      ?>
        <li class="step <?= $clase ?>">
          <span class="step-dot"><?= $clase === 'done' ? icono('check', 14) : $i + 1 ?></span>
          <span class="step-label"><?= e($paso) ?></span>
          <span class="step-area"><?= e(areaDeEstado($paso) ?? 'Cliente') ?></span>
        </li>
      <?php endforeach; ?>
    </ol>
  <?php endif; ?>

  <dl class="meta-grid">
    <div><dt>Cliente</dt><dd><?= e($p['cliente_nombre']) ?></dd></div>
    <div><dt>Entrega solicitada</dt><dd><?= e(formatoFecha($p['fecha_entrega'])) ?></dd></div>
    <div><dt>Área actual</dt><dd><?= e($areaActual ?? '—') ?></dd></div>
    <div><dt>Responsable</dt><dd><?= $p['empleado_nombre'] ? e($p['empleado_nombre']) : '<span class="muted">En bandeja del área</span>' ?></dd></div>
    <div><dt>Registrado</dt><dd><?= e(formatoFechaHora($p['creado_en'])) ?></dd></div>
    <div><dt>Registrado por</dt><dd><?= e($p['registrado_nombre'] ?? '—') ?></dd></div>
    <?php if ($p['fecha_entregado']): ?>
      <div><dt>Entregado</dt><dd><?= e(formatoFechaHora($p['fecha_entregado'])) ?></dd></div>
    <?php endif; ?>
  </dl>
</section>

<div class="grid-main">
  <div class="stack-lg">
    <?php if ($acciones || $puedeTomar || ($empleadosArea && esAdmin($u))): ?>
    <section class="card">
      <div class="card-head"><h3><?= icono('zap', 18) ?> Acciones</h3></div>

      <?php if ($puedeTomar): ?>
        <form action="<?= e(url('/empleado/tomar_pedido.php')) ?>" method="post" class="action-row">
          <?= campoCsrf() ?>
          <input type="hidden" name="pedido_id" value="<?= $id ?>">
          <div><strong>Pedido en la bandeja de <?= e($areaActual) ?></strong><p class="muted">Tómalo para quedar como responsable de esta etapa.</p></div>
          <button class="btn btn-secondary"><?= icono('user', 16) ?> Tomar pedido</button>
        </form>
      <?php endif; ?>

      <?php foreach ($acciones as $nuevo => $etiqueta):
          $requiere = transicionRequiereObservacion($p['estado'], $nuevo);
          $peligro = $nuevo === 'Cancelado' || ($p['estado'] === 'Control de calidad' && $nuevo === 'En producción');
          $destino = $u['rol'] === 'cliente' ? '/cliente/cancelar_pedido.php' : '/empleado/actualizar_estado.php';
      ?>
        <form action="<?= e(url($destino)) ?>" method="post" class="action-row"
              data-confirm="<?= e('¿Confirmas: ' . $etiqueta . '? El pedido pasará a "' . $nuevo . '".') ?>">
          <?= campoCsrf() ?>
          <input type="hidden" name="pedido_id" value="<?= $id ?>">
          <input type="hidden" name="nuevo_estado" value="<?= e($nuevo) ?>">
          <input type="hidden" name="volver" value="<?= e('/pedido.php?id=' . $id) ?>">
          <div class="grow">
            <strong><?= e($etiqueta) ?></strong>
            <p class="muted"><?= e($p['estado']) ?> → <?= e($nuevo) ?><?= areaDeEstado($nuevo) ? ' · pasa a ' . e(areaDeEstado($nuevo)) : '' ?></p>
            <input type="text" name="observacion" maxlength="150" class="input-sm" placeholder="<?= $requiere ? 'Motivo (obligatorio)' : 'Observación (opcional)' ?>" <?= $requiere ? 'required' : '' ?>>
          </div>
          <button class="btn <?= $peligro ? 'btn-danger' : 'btn-primary' ?>"><?= icono($peligro ? 'x' : 'arrow', 16) ?> <?= $peligro ? 'Confirmar' : 'Avanzar' ?></button>
        </form>
      <?php endforeach; ?>

      <?php if (esAdmin($u) && $areaActual): ?>
        <form action="<?= e(url('/admin/asignar_empleado.php')) ?>" method="post" class="action-row">
          <?= campoCsrf() ?>
          <input type="hidden" name="pedido_id" value="<?= $id ?>">
          <input type="hidden" name="volver" value="<?= e('/pedido.php?id=' . $id) ?>">
          <div class="grow">
            <strong>Responsable de <?= e($areaActual) ?></strong>
            <select name="empleado_id" class="input-sm">
              <option value="">Sin responsable (bandeja del área)</option>
              <?php foreach ($empleadosArea as $emp): ?>
                <option value="<?= (int) $emp['id'] ?>" <?= (int) $p['empleado_id'] === (int) $emp['id'] ? 'selected' : '' ?>><?= e($emp['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (!$empleadosArea): ?><p class="muted">No hay empleados activos en <?= e($areaActual) ?>. Créalos en Usuarios.</p><?php endif; ?>
          </div>
          <button class="btn btn-secondary"><?= icono('users', 16) ?> Asignar</button>
        </form>
      <?php endif; ?>
    </section>
    <?php endif; ?>

    <section class="card">
      <div class="card-head"><h3><?= icono('file', 18) ?> Descripción y especificaciones</h3></div>
      <?php if (trim((string) $p['notas']) !== ''): ?>
        <p class="prewrap"><?= e($p['notas']) ?></p>
      <?php else: ?>
        <p class="muted">El pedido no tiene especificaciones adicionales.</p>
      <?php endif; ?>
    </section>

    <section class="card">
      <div class="card-head"><h3><?= icono('download', 18) ?> Archivos de diseño</h3><span class="muted"><?= count($archivos) ?> archivo(s)</span></div>
      <?php if ($archivos): ?>
        <ul class="file-list">
          <?php foreach ($archivos as $a): ?>
            <li>
              <span class="file-ext"><?= e(strtoupper(pathinfo($a['ruta'], PATHINFO_EXTENSION))) ?></span>
              <div class="grow"><strong><?= e($a['nombre_original']) ?></strong><small><?= e(formatoTamano($a['tamano'] ? (int) $a['tamano'] : null)) ?> · <?= e($a['subido_por_nombre'] ?? '—') ?> · <?= e(tiempoRelativo($a['fecha_subida'])) ?></small></div>
              <a class="btn btn-ghost btn-sm" href="<?= e(url('/archivo.php?id=' . $a['id'])) ?>"><?= icono('download', 16) ?> Descargar</a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="muted">No hay archivos adjuntos.</p>
      <?php endif; ?>
      <?php if ($puedeSubir): ?>
        <form action="<?= e(url('/subir_archivo.php')) ?>" method="post" enctype="multipart/form-data" class="upload-inline">
          <?= campoCsrf() ?>
          <input type="hidden" name="pedido_id" value="<?= $id ?>">
          <label class="dropzone dropzone-sm">
            <input type="file" name="archivos[]" multiple accept=".pdf,.ai,.jpg,.jpeg,.png,.webp" data-file-list>
            <?= icono('upload', 18) ?><span>Agregar archivos (PDF, AI, JPG, PNG, WEBP · máx. 10 MB)</span>
          </label>
          <ul class="file-preview"></ul>
          <button class="btn btn-secondary btn-sm"><?= icono('upload', 16) ?> Subir</button>
        </form>
      <?php endif; ?>
    </section>
  </div>

  <?php // @spec RF-V3D-04
    $imagen = array_values(array_filter($archivos, fn($a) => in_array(strtolower(pathinfo($a['ruta'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true)))[0] ?? null;
    if ($imagen): ?>
    <section class="card">
      <div class="card-head"><h3><?= icono('layers', 18) ?> Visualización 3D</h3><span class="muted"><?= e($imagen['nombre_original']) ?></span></div>
      <?= visualizador3D(modeloParaCategoria($p['categoria']), url('/archivo.php?id=' . $imagen['id'] . '&ver=1'), $p['tipo_trabajo']) ?>
    </section>
  <?php endif; ?>

  <section class="card">
    <div class="card-head"><h3><?= icono('activity', 18) ?> Historial de seguimiento</h3></div>
    <?php if ($historial): ?>
      <ol class="timeline">
        <?php foreach ($historial as $h): ?>
          <li class="<?= $h['estado_nuevo'] ? 'tl-estado' : '' ?>">
            <span class="tl-dot"></span>
            <div>
              <?php if ($h['estado_nuevo']): ?><?= badgeEstado($h['estado_nuevo']) ?><?php endif; ?>
              <p><?= e($h['descripcion']) ?></p>
              <small><?= e(formatoFechaHora($h['fecha'])) ?><?= $h['usuario_area'] ? ' · ' . e($h['usuario_area']) : '' ?></small>
            </div>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php else: ?>
      <p class="muted">Sin actividad registrada.</p>
    <?php endif; ?>
  </section>
</div>
<?php layoutFin(); ?>
