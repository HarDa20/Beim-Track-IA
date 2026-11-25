<?php
// Formulario de pedido reutilizado por el cliente (cliente/crear_pedido.php)
// y por Recepción/Administración (empleado/registrar_pedido.php).
// Variables esperadas: $categorias, $valores, $accionForm, $modoStaff (bool),
// $borradorId (int|null) y, en modo staff, $clientes.

$catSel = (int) ($valores['categoria_id'] ?? ($categorias[0]['id'] ?? 0));
?>
<form action="<?= e(url($accionForm)) ?>" method="post" enctype="multipart/form-data" class="order-form" id="form-pedido" data-order-form>
  <?= campoCsrf() ?>
  <?php if (!empty($borradorId)): ?><input type="hidden" name="borrador_id" value="<?= (int) $borradorId ?>"><?php endif; ?>

  <div class="stack-lg">
    <?php if ($modoStaff): ?>
    <section class="card">
      <div class="card-head"><h3><span class="step-num">0</span> Cliente y canal de recepción</h3></div>
      <div class="seg" role="radiogroup">
        <label><input type="radio" name="tipo_cliente" value="existente" <?= ($valores['tipo_cliente'] ?? 'existente') === 'existente' ? 'checked' : '' ?> data-toggle-cliente> Cliente registrado</label>
        <label><input type="radio" name="tipo_cliente" value="nuevo" <?= ($valores['tipo_cliente'] ?? '') === 'nuevo' ? 'checked' : '' ?> data-toggle-cliente> Cliente nuevo</label>
      </div>
      <div data-cliente="existente" class="stack">
        <label class="field"><span>Cliente</span>
          <select name="cliente_id">
            <option value="">Selecciona un cliente…</option>
            <?php foreach ($clientes as $c): ?>
              <option value="<?= (int) $c['id'] ?>" <?= (int) ($valores['cliente_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['nombre']) ?> — <?= e($c['correo']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>
      <div data-cliente="nuevo" class="stack" hidden>
        <div class="grid-3">
          <label class="field"><span>Nombre o empresa</span><input type="text" name="cliente_nombre" value="<?= e($valores['cliente_nombre'] ?? '') ?>" maxlength="120"></label>
          <label class="field"><span>Correo</span><input type="email" name="cliente_correo" value="<?= e($valores['cliente_correo'] ?? '') ?>" maxlength="150"></label>
          <label class="field"><span>Celular</span><input type="tel" name="cliente_telefono" value="<?= e($valores['cliente_telefono'] ?? '') ?>" maxlength="20"></label>
        </div>
        <label class="check"><input type="checkbox" name="cliente_acepta" value="1"> <span>El cliente autorizó el tratamiento de sus datos personales (Ley 1581 de 2012).</span></label>
        <p class="hint">Se crea la cuenta del cliente sin contraseña de acceso; el administrador puede habilitarla desde Usuarios.</p>
      </div>
      <label class="field"><span>¿Cómo llegó el pedido?</span>
        <select name="canal_recepcion">
          <?php foreach (['Llamada', 'Chat', 'Presencial', 'Web'] as $canal): ?>
            <option value="<?= $canal ?>" <?= ($valores['canal_recepcion'] ?? 'Llamada') === $canal ? 'selected' : '' ?>><?= $canal ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    </section>
    <?php endif; ?>

    <section class="card">
      <div class="card-head"><h3><span class="step-num">1</span> Categoría</h3></div>
      <?php if (!$categorias): ?>
        <?= estadoVacio('tag', 'No hay categorías activas', 'El administrador debe crear al menos una categoría con su precio.') ?>
      <?php else: ?>
      <div class="category-grid">
        <?php foreach ($categorias as $c): ?>
          <label class="category-card">
            <input type="radio" name="categoria_id" value="<?= (int) $c['id'] ?>" data-precio="<?= e($c['precio_por_unidad']) ?>" data-unidad="<?= e($c['unidad']) ?>" data-nombre="<?= e($c['categoria']) ?>" data-modelo="<?= e(modeloParaCategoria($c['categoria'])) ?>" <?= $catSel === (int) $c['id'] ? 'checked' : '' ?> required>
            <span class="cat-check"><?= icono('check', 14) ?></span>
            <strong><?= e($c['categoria']) ?></strong>
            <small><?= e($c['descripcion'] ?? '') ?></small>
            <em><?= e(formatoMoneda($c['precio_por_unidad'])) ?> / <?= e($c['unidad']) ?></em>
          </label>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </section>

    <section class="card">
      <div class="card-head"><h3><span class="step-num">2</span> Trabajo y especificaciones</h3></div>
      <label class="field"><span>Nombre del trabajo</span>
        <input type="text" name="tipo_trabajo" value="<?= e($valores['tipo_trabajo'] ?? '') ?>" placeholder="Ej: Vinilo impreso adhesivo para fachada principal" required minlength="3" maxlength="200">
      </label>
      <div class="grid-2">
        <label class="field"><span>Cantidad</span>
          <div class="input-suffix"><input type="number" name="cantidad" value="<?= e($valores['cantidad'] ?? '') ?>" step="0.01" min="0.01" max="<?= MAX_CANTIDAD ?>" placeholder="0" required data-cantidad><span data-unidad-label>m2</span></div>
        </label>
        <label class="field"><span>Fecha de entrega deseada</span>
          <input type="date" name="fecha_entrega" value="<?= e($valores['fecha_entrega'] ?? '') ?>" min="<?= hoy() ?>" max="<?= date('Y-m-d', strtotime('+1 year')) ?>" required data-fecha>
        </label>
      </div>
      <label class="field"><span>Descripción y especificaciones <em>(medidas, colores, materiales, instalación…)</em></span>
        <textarea name="notas" rows="5" maxlength="2000" placeholder="Describe el trabajo con el mayor detalle posible."><?= e($valores['notas'] ?? '') ?></textarea>
      </label>
    </section>

    <section class="card">
      <div class="card-head"><h3><span class="step-num">3</span> Archivos de diseño <em class="muted">(opcional)</em></h3></div>
      <label class="dropzone">
        <input type="file" name="archivos[]" multiple accept=".pdf,.ai,.jpg,.jpeg,.png,.webp" data-file-list>
        <span class="dropzone-ico"><?= icono('upload', 26) ?></span>
        <strong>Arrastra tus archivos o haz clic para elegirlos</strong>
        <small>PDF, AI, JPG, PNG o WEBP · hasta 10 MB cada uno · máximo 5</small>
      </label>
      <ul class="file-preview"></ul>
    </section>

    <?php // @spec RF-V3D-01, RF-V3D-02
      $catActual = array_values(array_filter($categorias, fn($c) => (int) $c['id'] === $catSel))[0]['categoria'] ?? ''; ?>
    <section class="card">
      <div class="card-head"><h3><span class="step-num">4</span> Visualización 3D</h3><span class="muted">Mira cómo quedaría tu diseño</span></div>
      <?= visualizador3D(modeloParaCategoria($catActual), null, (string) ($valores['tipo_trabajo'] ?? ''), true) ?>
    </section>
  </div>

  <aside class="summary card">
    <h3>Resumen del pedido</h3>
    <dl>
      <div><dt>Categoría</dt><dd data-r-categoria>—</dd></div>
      <div><dt>Cantidad</dt><dd data-r-cantidad>—</dd></div>
      <div><dt>Precio unitario</dt><dd data-r-precio>—</dd></div>
      <div><dt>Entrega</dt><dd data-r-fecha>—</dd></div>
    </dl>
    <div class="summary-total">
      <small>Costo estimado</small>
      <strong data-r-costo>$ 0</strong>
      <span class="hint">Valor de referencia calculado con el precio vigente. El valor final se confirma con la empresa.</span>
    </div>
    <div class="stack">
      <button type="submit" name="accion" value="enviar" class="btn btn-primary btn-block btn-lg"><?= icono('arrow', 16) ?> <?= $modoStaff ? 'Registrar pedido' : 'Enviar pedido' ?></button>
      <?php if (!$modoStaff): ?>
        <button type="submit" name="accion" value="borrador" class="btn btn-ghost btn-block"><?= icono('edit', 16) ?> Guardar como borrador</button>
        <?php if (!empty($borradorId)): ?>
          <button type="submit" name="accion" value="eliminar" formnovalidate class="btn btn-danger-ghost btn-block" data-confirm-button="¿Eliminar este borrador? Esta acción no se puede deshacer."><?= icono('trash', 16) ?> Eliminar borrador</button>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </aside>
</form>
