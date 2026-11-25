<?php
// Menú lateral según el rol. Variables esperadas: $u (usuario) y $activo.
$u = usuarioActual();
$item = function (string $clave, string $ruta, string $ico, string $texto) use ($activo) {
    $clase = $activo === $clave ? 'nav-link active' : 'nav-link';
    return '<a href="' . e(url($ruta)) . '" class="' . $clase . '">' . icono($ico) . '<span>' . e($texto) . '</span></a>';
};
?>
<div class="brand<?= logoAncho() ? ' brand-ancha' : '' ?>">
  <?php if ($logoAncho = logoAncho()): ?>
    <div class="brand-text"><?= $logoAncho ?></div>
  <?php else: ?>
    <?= logoBeim(38) ?>
    <div class="brand-text"><strong>BEIM Track IA</strong><small>Gestión de pedidos</small></div>
  <?php endif; ?>
  <button type="button" class="icon-btn only-mobile" data-close-sidebar aria-label="Cerrar menú"><?= icono('x') ?></button>
</div>

<nav class="nav">
  <?php if ($u['rol'] === 'cliente'): ?>
    <div class="nav-section">Mi cuenta</div>
    <?= $item('pedidos', '/cliente/panel.php', 'home', 'Mis pedidos') ?>
    <?= $item('nuevo', '/cliente/crear_pedido.php', 'plus', 'Nuevo pedido') ?>
  <?php elseif ($u['rol'] === 'empleado'): ?>
    <div class="nav-section">Área de <?= e($u['area']) ?></div>
    <?= $item('bandeja', '/empleado/panel.php', 'inbox', 'Mi bandeja') ?>
    <?php if (puedeRegistrarPedidos($u)): ?>
      <?= $item('registrar', '/empleado/registrar_pedido.php', 'plus', 'Registrar pedido') ?>
    <?php endif; ?>
  <?php else: ?>
    <div class="nav-section">Supervisión</div>
    <?= $item('dashboard', '/admin/panel.php', 'chart', 'Dashboard') ?>
    <?= $item('gerencia', '/admin/gerencia.php', 'briefcase', 'Panel gerencial') ?>
    <?= $item('pedidos', '/admin/pedidos.php', 'box', 'Pedidos') ?>
    <?= $item('registrar', '/empleado/registrar_pedido.php', 'plus', 'Registrar pedido') ?>
    <div class="nav-section">Administración</div>
    <?= $item('usuarios', '/admin/usuarios.php', 'users', 'Usuarios') ?>
    <?= $item('categorias', '/admin/categorias.php', 'tag', 'Categorías y precios') ?>
    <?= $item('actividad', '/admin/actividad.php', 'activity', 'Actividad') ?>
  <?php endif; ?>

  <div class="nav-section">General</div>
  <?= $item('alertas', '/alertas.php', 'bell', 'Alertas') ?>
  <?= $item('perfil', '/perfil.php', 'user', 'Mi perfil') ?>
</nav>

<form action="<?= e(url('/logout.php')) ?>" method="post" class="nav-logout">
  <?= campoCsrf() ?>
  <button type="submit" class="nav-link nav-link-danger"><?= icono('logout') ?><span>Cerrar sesión</span></button>
</form>
