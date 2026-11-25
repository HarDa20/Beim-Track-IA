<?php
// Estructura visual común: cabecera HTML, menú lateral, barra superior,
// mensajes, asistente y pie. Todas las pantallas internas usan este layout.
// @spec RF-MSE-03
// @spec RF-BOT-01

/** Versión de un archivo estático según su fecha: el navegador descarga la nueva al cambiarlo. */
function versionArchivo(string $ruta): string
{
    $archivo = dirname(__DIR__) . '/' . $ruta;
    return is_file($archivo) ? (string) filemtime($archivo) : '1';
}

/**
 * Logo de la empresa. Si existe img/logo.png (el logo real) se usa esa imagen;
 * si no, el logo vectorial del sistema.
 *
 * @spec RF-IDV-03
 */
function logoBeim(int $tam = 36): string
{
    if (is_file(dirname(__DIR__) . '/img/logo.png')) {
        return '<img class="logo-mark logo-img" src="' . e(url('/img/logo.png')) . '" width="' . $tam . '" height="' . $tam . '" alt="Logo de la empresa">';
    }
    return '<svg class="logo-mark" width="' . $tam . '" height="' . $tam . '" viewBox="0 0 40 40" aria-hidden="true">'
        . '<defs><linearGradient id="lg-beim" x1="0" y1="0" x2="1" y2="1"><stop offset="0" style="stop-color:var(--acc-1)"/><stop offset=".55" style="stop-color:var(--acc-2)"/><stop offset="1" style="stop-color:var(--acc-3)"/></linearGradient></defs>'
        . '<rect x="1" y="1" width="38" height="38" rx="11" fill="url(#lg-beim)"/>'
        . '<path d="M12 10h9.5a5 5 0 0 1 0 10H12zM12 20h11a5 5 0 0 1 0 10H12z" fill="none" stroke="#fff" stroke-width="3" stroke-linejoin="round"/>'
        . '<circle cx="29.5" cy="11" r="2.4" fill="#fff"/></svg>';
}

/**
 * Logo horizontal (mascota + texto "BEIM Track"). Si existe img/logo_ancho.png
 * reemplaza al logo pequeño y al nombre escrito en el menú y en el ingreso.
 *
 * @spec RF-IDV-04
 */
function logoAncho(string $clase = ''): ?string
{
    $ruta = 'img/logo_ancho.png';
    if (!is_file(dirname(__DIR__) . '/' . $ruta)) {
        return null;
    }
    return '<img class="logo-ancho ' . e($clase) . '" src="' . e(url('/' . $ruta)) . '?v=' . versionArchivo($ruta) . '" alt="BEIM Track">';
}

/** @spec RF-IDV-03 */
function faviconDataUri(): string
{
    if (is_file(dirname(__DIR__) . '/img/logo.png')) {
        return url('/img/logo.png');
    }
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#22d3ee"/><stop offset="1" stop-color="#a855f7"/></linearGradient></defs><rect width="40" height="40" rx="11" fill="url(#g)"/><path d="M12 10h9.5a5 5 0 0 1 0 10H12zM12 20h11a5 5 0 0 1 0 10H12z" fill="none" stroke="#fff" stroke-width="3"/></svg>';
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

/**
 * Cabecera HTML. El tema y el acento se aplican desde el servidor para que
 * la página no parpadee al cargar.
 *
 * @spec RF-TEM-01, RF-TEM-02
 */
function htmlHead(string $titulo): void
{
    $tema = temaActual();
    ?>
<!DOCTYPE html>
<html lang="es" data-tema="<?= e($tema) ?>" data-acento="<?= e(acentoActual()) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="<?= $tema === 'claro' ? '#f4f6fb' : '#070b14' ?>">
<title><?= e($titulo) ?> · BEIM Track IA</title>
<link rel="icon" href="<?= e(faviconDataUri()) ?>">
<link rel="stylesheet" href="<?= e(url('/css/style.css')) ?>?v=<?= versionArchivo('css/style.css') ?>">
<script src="<?= e(url('/js/app.js')) ?>?v=<?= versionArchivo('js/app.js') ?>" defer></script>
</head>
    <?php
}

/**
 * Selector de tema (claro/oscuro) y color de acento.
 *
 * @spec RF-TEM-01, RF-TEM-02
 */
function selectorTema(): string
{
    $html = '<div class="theme-picker" role="group" aria-label="Tono de la interfaz">';
    foreach (ACENTOS as $clave => $color) {
        $html .= '<button type="button" class="theme-dot' . (acentoActual() === $clave ? ' is-active' : '') . '" data-acento="' . e($clave) . '" style="--dot:' . e($color) . '" aria-label="Acento ' . e($clave) . '" title="Acento ' . e($clave) . '"></button>';
    }
    $html .= '<button type="button" class="theme-toggle" data-tema-toggle aria-label="Cambiar entre tema claro y oscuro" title="Tema claro / oscuro">'
        . '<span class="ico-moon">' . icono('moon', 16) . '</span><span class="ico-sun">' . icono('sun', 16) . '</span></button>';
    return $html . '</div>';
}

/**
 * Mascota del asistente. Si existe img/mascota.png (por ejemplo, creada con
 * Nano Banana) se usa esa imagen; si no, la mascota animada en SVG.
 *
 * @spec RF-BOT-01
 */
function mascotaBeimy(string $clase = ''): string
{
    if (is_file(dirname(__DIR__) . '/img/mascota.png')) {
        return '<span class="beimy-bot ' . e($clase) . '"><img src="' . e(url('/img/mascota.png')) . '" alt=""></span>';
    }
    static $n = 0;
    $id = 'bm' . (++$n);
    return str_replace(['bm-cuerpo', 'bm-brillo'], [$id . 'c', $id . 'b'], '<span class="beimy-bot ' . e($clase) . '" aria-hidden="true"><svg viewBox="0 0 120 130">'
        . '<defs><linearGradient id="bm-cuerpo" x1="0" y1="0" x2="1" y2="1"><stop offset="0" style="stop-color:var(--acc-1)"/><stop offset="1" style="stop-color:var(--acc-3)"/></linearGradient>'
        . '<radialGradient id="bm-brillo" cx=".5" cy=".5" r=".5"><stop offset="0" style="stop-color:var(--acc-1);stop-opacity:.55"/><stop offset="1" style="stop-color:var(--acc-1);stop-opacity:0"/></radialGradient></defs>'
        . '<ellipse class="bm-sombra" cx="60" cy="122" rx="30" ry="5"/>'
        . '<g class="bm-flota">'
        . '<circle cx="60" cy="62" r="56" fill="url(#bm-brillo)" class="bm-aura"/>'
        . '<line x1="60" y1="14" x2="60" y2="26" class="bm-trazo"/><circle cx="60" cy="11" r="5" class="bm-antena"/>'
        . '<rect x="20" y="26" width="80" height="62" rx="26" fill="url(#bm-cuerpo)"/>'
        . '<rect x="29" y="38" width="62" height="36" rx="18" class="bm-visor"/>'
        . '<g class="bm-ojos"><ellipse cx="47" cy="56" rx="6" ry="8"/><ellipse cx="73" cy="56" rx="6" ry="8"/></g>'
        . '<path d="M52 66q8 6 16 0" class="bm-boca"/>'
        . '<rect x="12" y="50" width="9" height="18" rx="4.5" fill="url(#bm-cuerpo)"/><rect x="99" y="50" width="9" height="18" rx="4.5" fill="url(#bm-cuerpo)"/>'
        . '<path d="M38 90h44l-6 16H44z" fill="url(#bm-cuerpo)" opacity=".85"/>'
        . '<g class="bm-mano"><rect x="101" y="40" width="8" height="16" rx="4" fill="url(#bm-cuerpo)"/></g>'
        . '</g></svg></span>');
}

function layoutInicio(string $titulo, string $activo, string $subtitulo = ''): void
{
    global $pdo;
    $u = usuarioActual();
    $noLeidas = $u ? contarAlertasNoLeidas($pdo, (int) $u['id']) : 0;
    htmlHead($titulo);
    ?>
<body class="app-body" data-ranura="<?= ranuraActual() ?>">
<div class="app">
  <aside class="sidebar" id="sidebar">
    <?php include __DIR__ . '/nav_sidebar.php'; ?>
  </aside>
  <div class="sidebar-backdrop" data-close-sidebar></div>

  <div class="content">
    <header class="topbar">
      <button type="button" class="icon-btn only-mobile" data-toggle-sidebar aria-label="Abrir menú"><?= icono('menu') ?></button>
      <div class="topbar-title">
        <h1><?= e($titulo) ?></h1>
        <?php if ($subtitulo): ?><p><?= e($subtitulo) ?></p><?php endif; ?>
      </div>
      <div class="topbar-actions">
        <a href="<?= e(url('/alertas.php')) ?>" class="icon-btn bell <?= $activo === 'alertas' ? 'is-active' : '' ?>" aria-label="Alertas (<?= $noLeidas ?> sin leer)">
          <?= icono('bell') ?>
          <?php if ($noLeidas > 0): ?><span class="bell-count"><?= $noLeidas > 99 ? '99+' : $noLeidas ?></span><?php endif; ?>
        </a>
        <?= selectorTema() ?>
        <details class="account-menu">
          <summary class="user-chip" aria-label="Cuentas abiertas">
            <?= avatarHtml($u) ?>
            <span class="user-chip-text"><strong><?= e($u['nombre']) ?></strong><small><?= e(nombreRol($u['rol'])) ?><?= $u['area'] ? ' · ' . e($u['area']) : '' ?></small></span>
          </summary>
          <div class="account-pop">
            <p class="account-pop-title"><?= icono('swap', 14) ?> Cuentas abiertas en este navegador</p>
            <?php foreach (cuentasAbiertas() as $ranura => $c): $cu = $c['usuario']; ?>
              <a class="account-item<?= $ranura === ranuraActual() ? ' is-current' : '' ?>" href="<?= e(url(rutaPanel($cu['rol']), (int) $ranura)) ?>"<?= $ranura === ranuraActual() ? '' : ' target="_blank" rel="noopener"' ?> data-ranura="<?= (int) $ranura ?>">
                <?= avatarHtml($cu, 'avatar-sm') ?>
                <span><strong><?= e($cu['nombre']) ?></strong><small><?= e(nombreRol($cu['rol'])) ?><?= $cu['area'] ? ' · ' . e($cu['area']) : '' ?></small></span>
                <?php if ($ranura === ranuraActual()): ?><em>Esta pestaña</em><?php endif; ?>
              </a>
            <?php endforeach; ?>
            <?php if (primeraRanuraLibre() !== null): ?>
              <a class="account-add" href="<?= e(url('/index.php?agregar=1')) ?>" target="_blank" rel="noopener"><?= icono('plus', 15) ?> Agregar otra cuenta</a>
            <?php endif; ?>
            <a class="account-link" href="<?= e(url('/perfil.php')) ?>"><?= icono('user', 15) ?> Mi perfil</a>
            <form action="<?= e(url('/logout.php')) ?>" method="post" class="account-logout">
              <?= campoCsrf() ?>
              <button type="submit" class="account-link danger"><?= icono('logout', 15) ?> Cerrar esta cuenta</button>
              <?php if (count(cuentasAbiertas()) > 1): ?>
                <button type="submit" name="todas" value="1" class="account-link danger"><?= icono('logout', 15) ?> Cerrar todas</button>
              <?php endif; ?>
            </form>
          </div>
        </details>
      </div>
    </header>

    <div class="toasts" aria-live="polite">
      <?php foreach (tomarFlash() as $f): ?>
        <div class="toast toast-<?= e($f['tipo']) ?>" role="status">
          <?= icono($f['tipo'] === 'exito' ? 'check' : ($f['tipo'] === 'error' ? 'alert' : 'bell')) ?>
          <span><?= e($f['mensaje']) ?></span>
          <button type="button" class="toast-close" aria-label="Cerrar"><?= icono('x', 14) ?></button>
        </div>
      <?php endforeach; ?>
    </div>

    <main class="page">
    <?php
}

function layoutFin(): void
{
    ?>
    </main>
    <footer class="footer">
      <span>BEIM Track IA · Plataforma de gestión y seguimiento de pedidos publicitarios</span>
      <span>UNINPAHU · Ingeniería de Software · <?= date('Y') ?></span>
    </footer>
  </div>
</div>

<?php $yo = usuarioActual(); if ($yo): ?>
<div class="beimy" data-chatbot data-api="<?= e(url('/chatbot_api.php')) ?>" data-csrf="<?= e(csrfToken()) ?>">
  <button type="button" class="beimy-launcher" data-chatbot-abrir aria-label="Abrir asistente Beimy">
    <?= mascotaBeimy('beimy-bot-sm') ?>
    <span class="beimy-hola">¡Hola, <?= e(explode(' ', $yo['nombre'])[0]) ?>! ¿Te ayudo?</span>
  </button>
  <section class="beimy-panel" role="dialog" aria-label="Asistente Beimy" hidden>
    <header class="beimy-head">
      <?= mascotaBeimy('beimy-bot-head') ?>
      <div><strong>Beimy</strong><small data-chatbot-modo><?= chatbotIaDisponible() ? 'Asistente con IA · Gemini' : 'Asistente · modo menú' ?></small></div>
      <button type="button" class="icon-btn" data-chatbot-cerrar aria-label="Cerrar asistente"><?= icono('x', 16) ?></button>
    </header>
    <div class="beimy-mensajes" aria-live="polite"></div>
    <div class="beimy-opciones"></div>
    <form class="beimy-form" data-chatbot-form>
      <input type="text" name="mensaje" maxlength="<?= CHATBOT_MAX_CARACTERES ?>" placeholder="Escribe tu pregunta…" autocomplete="off" aria-label="Mensaje para Beimy">
      <button type="submit" class="btn btn-primary btn-icon" aria-label="Enviar"><?= icono('send', 16) ?></button>
    </form>
  </section>
</div>
<script src="<?= e(url('/js/chatbot.js')) ?>?v=<?= versionArchivo('js/chatbot.js') ?>" defer></script>
<?php endif; ?>

<div class="modal" id="modal-confirmar" hidden>
  <div class="modal-backdrop" data-modal-cerrar></div>
  <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="modal-titulo">
    <div class="modal-icon"><?= icono('alert', 22) ?></div>
    <h3 id="modal-titulo">¿Confirmas esta acción?</h3>
    <p id="modal-texto"></p>
    <div class="modal-actions">
      <button type="button" class="btn btn-ghost" data-modal-cerrar>Cancelar</button>
      <button type="button" class="btn btn-primary" id="modal-aceptar">Confirmar</button>
    </div>
  </div>
</div>
</body>
</html>
    <?php
    limpiarOld();
}

/** Bloque de estado vacío reutilizable. */
function estadoVacio(string $icono, string $titulo, string $texto, string $accionHtml = ''): string
{
    return '<div class="empty"><div class="empty-icon">' . icono($icono, 28) . '</div><h3>' . e($titulo) . '</h3><p>' . e($texto) . '</p>' . $accionHtml . '</div>';
}
