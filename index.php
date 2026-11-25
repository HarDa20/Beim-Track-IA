<?php
// Inicio de sesión: escena con lámpara (spec 012) y "agregar otra cuenta" (spec 010).
// @spec RF-IDV-01, RF-IDV-02, RF-IDV-03
// @spec RF-MSE-01
require __DIR__ . '/includes/bootstrap.php';

$agregar = !empty($_GET['agregar']);
if (usuarioActual() && !$agregar) {
    redirigirSegunRol(usuarioActual()['rol']);
}
// Ranura donde se abrirá la cuenta: la primera libre al agregar, o la de esta pestaña.
$ranuraLogin = $agregar ? primeraRanuraLibre() : ranuraActual();
if ($ranuraLogin === null) {
    flash('error', 'Ya tienes ' . MAX_CUENTAS . ' cuentas abiertas. Cierra una para agregar otra.');
    redirigirSegunRol(usuarioActual()['rol']);
}

$tab = $_SESSION['tab_activa'] ?? 'login';
unset($_SESSION['tab_activa']);
$mensajes = tomarFlash();
// Con mensajes que mostrar (o al agregar cuenta) la lámpara arranca encendida.
$lampara = ($mensajes || $agregar || $tab === 'registro') ? 'encendida' : 'apagada';
htmlHead('Ingreso');
?>
<body class="lamp-body">
<main class="escena" data-escena data-lampara="<?= $lampara ?>">
  <div class="escena-luz-ambiente" aria-hidden="true"></div>
  <header class="escena-top">
    <div class="escena-marca"><?php if ($logoAncho = logoAncho('logo-ancho-escena')): ?><?= $logoAncho ?><?php else: ?><?= logoBeim(34) ?><div><strong>BEIM Track IA</strong><small>Gestión y seguimiento de pedidos publicitarios</small></div><?php endif; ?></div>
    <?= selectorTema() ?>
  </header>

  <div class="lampara" aria-hidden="false">
    <div class="lampara-luz" aria-hidden="true"></div>
    <svg class="lampara-svg" viewBox="0 0 220 360" aria-hidden="true">
      <defs>
        <linearGradient id="lp-metal" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#3a3f4b"/><stop offset=".5" stop-color="#1d2028"/><stop offset="1" stop-color="#0d0f14"/></linearGradient>
        <linearGradient id="lp-oro" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#b8892f"/><stop offset=".5" stop-color="#f3d48a"/><stop offset="1" stop-color="#a87a25"/></linearGradient>
        <radialGradient id="lp-bombillo" cx=".5" cy=".5" r=".5"><stop offset="0" stop-color="#fffbe9"/><stop offset=".6" stop-color="#ffe9a8"/><stop offset="1" stop-color="#ffd36b" stop-opacity="0"/></radialGradient>
      </defs>
      <ellipse cx="62" cy="346" rx="58" ry="11" class="lp-sombra"/>
      <path d="M14 344c0-12 22-20 48-20s48 8 48 20c0 6-22 9-48 9s-48-3-48-9z" fill="url(#lp-metal)"/>
      <path d="M16 342c4-8 24-13 46-13s42 5 46 13" fill="none" stroke="url(#lp-oro)" stroke-width="2"/>
      <path d="M62 328L40 204" stroke="url(#lp-metal)" stroke-width="9" stroke-linecap="round"/>
      <path d="M62 328L40 204" stroke="url(#lp-oro)" stroke-width="2" stroke-linecap="round" opacity=".8"/>
      <path d="M40 204L110 70" stroke="url(#lp-metal)" stroke-width="8" stroke-linecap="round"/>
      <path d="M40 204L110 70" stroke="url(#lp-oro)" stroke-width="2" stroke-linecap="round" opacity=".8"/>
      <circle cx="40" cy="204" r="8" fill="#15171d" stroke="url(#lp-oro)" stroke-width="2.5"/>
      <circle cx="62" cy="328" r="6" fill="#15171d" stroke="url(#lp-oro)" stroke-width="2"/>
      <g transform="translate(53.2 85.4) rotate(25)">
        <ellipse class="lp-bombillo" cx="45" cy="3" rx="30" ry="11" fill="url(#lp-bombillo)"/>
        <path d="M0 0C6-48 84-48 90 0z" fill="url(#lp-metal)"/>
        <path d="M2-6C14-40 76-40 88-6" fill="none" stroke="rgba(255,255,255,.12)" stroke-width="3"/>
        <path d="M0 0h90" stroke="url(#lp-oro)" stroke-width="3" stroke-linecap="round"/>
        <circle cx="45" cy="-37" r="6.5" fill="#15171d" stroke="url(#lp-oro)" stroke-width="2.5"/>
      </g>
    </svg>
    <button type="button" class="lampara-cordon" data-cordon aria-pressed="<?= $lampara === 'encendida' ? 'true' : 'false' ?>" aria-label="Tirar del cordón para encender la lámpara">
      <span class="cordon-hilo"></span><span class="cordon-bola"></span>
    </button>
    <p class="lampara-pista" aria-hidden="true">Tira del cordón <span>↓</span></p>
  </div>

  <section class="login-card" aria-label="Formulario de ingreso">
    <div class="auth-card">
      <div class="auth-card-head">
        <h2><?= $agregar ? 'Agregar otra cuenta' : 'Bienvenido' ?></h2>
        <p><?= $agregar ? 'Las cuentas que ya tienes abiertas siguen activas en sus pestañas.' : 'Ingresa con tu cuenta. El sistema te llevará al panel de tu rol.' ?></p>
      </div>

      <?php foreach ($mensajes as $m): ?>
        <div class="alert alert-<?= e($m['tipo']) ?>"><?= icono($m['tipo'] === 'exito' ? 'check' : 'alert', 16) ?><span><?= e($m['mensaje']) ?></span></div>
      <?php endforeach; ?>

      <div class="tabs" role="tablist">
        <button type="button" class="tab <?= $tab === 'login' ? 'active' : '' ?>" data-tab="login" role="tab">Iniciar sesión</button>
        <button type="button" class="tab <?= $tab === 'registro' ? 'active' : '' ?>" data-tab="registro" role="tab">Crear cuenta</button>
      </div>

      <form id="panel-login" class="tab-panel stack" action="<?= e(url('/login_process.php', $ranuraLogin)) ?>" method="post" <?= $tab !== 'login' ? 'hidden' : '' ?>>
        <?= campoCsrf() ?>
        <input type="hidden" name="ranura" value="<?= (int) $ranuraLogin ?>">
        <label class="field">
          <span>Correo electrónico</span>
          <div class="input-icon"><?= icono('mail', 16) ?><input type="email" name="correo" value="<?= e(old('correo_login')) ?>" placeholder="tucorreo@empresa.com" autocomplete="username" required maxlength="150"></div>
        </label>
        <label class="field">
          <span>Contraseña</span>
          <div class="input-icon"><?= icono('shield', 16) ?><input type="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
            <button type="button" class="input-action" data-toggle-password aria-label="Mostrar contraseña"><?= icono('eye', 16) ?></button></div>
        </label>
        <button type="submit" class="btn btn-primary btn-block btn-lg">Entrar <?= icono('arrow', 16) ?></button>
      </form>

      <form id="panel-registro" class="tab-panel stack" action="<?= e(url('/register_process.php')) ?>" method="post" <?= $tab !== 'registro' ? 'hidden' : '' ?>>
        <?= campoCsrf() ?>
        <label class="field">
          <span>Nombre completo o empresa</span>
          <input type="text" name="nombre" value="<?= e(old('nombre')) ?>" required minlength="3" maxlength="120" autocomplete="name">
        </label>
        <div class="grid-2">
          <label class="field">
            <span>Correo electrónico</span>
            <input type="email" name="correo" value="<?= e(old('correo')) ?>" required maxlength="150" autocomplete="email">
          </label>
          <label class="field">
            <span>Celular <em>(opcional)</em></span>
            <input type="tel" name="telefono" value="<?= e(old('telefono')) ?>" maxlength="20" placeholder="300 123 4567" autocomplete="tel">
          </label>
        </div>
        <div class="grid-2">
          <label class="field">
            <span>Contraseña</span>
            <input type="password" name="password" required minlength="8" autocomplete="new-password">
          </label>
          <label class="field">
            <span>Confirmar contraseña</span>
            <input type="password" name="password2" required minlength="8" autocomplete="new-password">
          </label>
        </div>
        <p class="hint">Mínimo 8 caracteres, con al menos una letra y un número.</p>
        <label class="check">
          <input type="checkbox" name="acepta_datos" value="1" required>
          <span>Autorizo el tratamiento de mis datos personales para gestionar mis pedidos, conforme a la Ley 1581 de 2012.</span>
        </label>
        <button type="submit" class="btn btn-primary btn-block btn-lg">Crear cuenta de cliente</button>
        <p class="hint center">Las cuentas de empleados y administradores las crea el administrador.</p>
      </form>
      <p class="auth-foot"><?= icono('shield', 14) ?> Conexión protegida · Ley 1581 de 2012</p>
    </div>
  </section>
  <noscript><style>.escena .login-card{opacity:1!important;transform:none!important;filter:none!important;pointer-events:auto!important}.lampara-pista,.lampara-cordon{display:none}</style></noscript>
</main>
</body>
</html>
<?php limpiarOld(); ?>
