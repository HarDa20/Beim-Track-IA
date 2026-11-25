<?php
// Funciones comunes de formato, navegación y mensajes.

/** @spec RNF-SEG-02 */
function e($valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Dirección interna. Conserva la ranura de multisesión (?u=N) para que cada
 * pestaña siga trabajando con su propia cuenta.
 *
 * @spec RF-MSE-02
 */
function url(string $ruta = '', ?int $ranura = null): string
{
    $direccion = BASE_URL . '/' . ltrim($ruta, '/');
    $ranura ??= function_exists('ranuraActual') ? ranuraActual() : 0;
    if ($ranura > 0 && !preg_match('/\.(css|js|svg|png|jpg|webp)(\?|$)/', $direccion)) {
        $direccion = preg_replace('/([?&])u=\d+(&|$)/', '$1', $direccion);
        $direccion = rtrim($direccion, '?&');
        $direccion .= (str_contains($direccion, '?') ? '&' : '?') . 'u=' . $ranura;
    }
    return $direccion;
}

function redirigir(string $ruta, ?int $ranura = null): void
{
    header('Location: ' . (str_starts_with($ruta, 'http') ? $ruta : url($ruta, $ranura)));
    exit;
}

/**
 * Acepta solo rutas internas relativas (evita redirecciones abiertas).
 *
 * @spec RNF-SEG-04
 */
function rutaSegura($ruta, string $defecto): string
{
    $ruta = (string) $ruta;
    if ($ruta === '' || $ruta[0] !== '/' || str_starts_with($ruta, '//') || preg_match('/[\r\n\\\\]/', $ruta)) {
        return $defecto;
    }
    return $ruta;
}

// ---------------------------------------------------------------------
// Mensajes flash y valores anteriores de formularios
// ---------------------------------------------------------------------
function flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function tomarFlash(): array
{
    $mensajes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $mensajes;
}

function guardarOld(array $datos): void
{
    unset($datos['csrf'], $datos['password'], $datos['password2'], $datos['password_actual']);
    $_SESSION['old'] = $datos;
}

function old(string $clave, $defecto = '')
{
    return $_SESSION['old'][$clave] ?? $defecto;
}

function limpiarOld(): void
{
    unset($_SESSION['old']);
}

// ---------------------------------------------------------------------
// Estados del pedido
// ---------------------------------------------------------------------
function claseBadgeEstado(?string $estado): string
{
    $mapa = [
        'Borrador'           => 'badge-borrador',
        'Recibido'           => 'badge-recibido',
        'En diseño'          => 'badge-diseno',
        'En producción'      => 'badge-produccion',
        'Control de calidad' => 'badge-calidad',
        'Listo para entrega' => 'badge-listo',
        'Entregado'          => 'badge-entregado',
        'Cancelado'          => 'badge-cancelado',
    ];
    return $mapa[$estado] ?? 'badge-borrador';
}

function badgeEstado(?string $estado): string
{
    return '<span class="badge ' . claseBadgeEstado($estado) . '"><i></i>' . e($estado) . '</span>';
}

/** Etapas del flujo productivo en orden (sin Borrador ni Cancelado). */
function pasosEstado(): array
{
    return ['Recibido', 'En diseño', 'En producción', 'Control de calidad', 'Listo para entrega', 'Entregado'];
}

// ---------------------------------------------------------------------
// Formatos
// ---------------------------------------------------------------------
function formatoMoneda($valor): string
{
    return '$ ' . number_format((float) $valor, 0, ',', '.');
}

function formatoMonedaCorta($valor): string
{
    $v = (float) $valor;
    if ($v >= 1000000) {
        return '$ ' . rtrim(rtrim(number_format($v / 1000000, 1, ',', '.'), '0'), ',') . ' M';
    }
    return formatoMoneda($v);
}

function formatoCantidad($valor): string
{
    return rtrim(rtrim(number_format((float) $valor, 2, ',', '.'), '0'), ',');
}

function mesCorto(int $mes): string
{
    return ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'][$mes - 1] ?? '';
}

function formatoFecha(?string $fecha): string
{
    if (!$fecha) {
        return '—';
    }
    $ts = strtotime($fecha);
    return date('d', $ts) . ' ' . mesCorto((int) date('n', $ts)) . ' ' . date('Y', $ts);
}

function formatoFechaHora(?string $fecha): string
{
    if (!$fecha) {
        return '—';
    }
    $ts = strtotime($fecha);
    return date('d', $ts) . ' ' . mesCorto((int) date('n', $ts)) . ' ' . date('Y', $ts) . ', ' . date('g:i a', $ts);
}

function tiempoRelativo(?string $fecha): string
{
    if (!$fecha) {
        return '';
    }
    $seg = time() - strtotime($fecha);
    if ($seg < 60)     return 'hace un momento';
    if ($seg < 3600)   return 'hace ' . intdiv($seg, 60) . ' min';
    if ($seg < 86400)  return 'hace ' . intdiv($seg, 3600) . ' h';
    if ($seg < 604800) return 'hace ' . intdiv($seg, 86400) . ' d';
    return formatoFecha($fecha);
}

function codigoPedido($id): string
{
    return 'BT-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
}

/** Días que faltan para la entrega (negativo = retrasado). */
function diasParaEntrega(?string $fechaEntrega): ?int
{
    if (!$fechaEntrega) {
        return null;
    }
    $hoy = new DateTimeImmutable('today');
    $f = new DateTimeImmutable($fechaEntrega);
    return (int) $hoy->diff($f)->format('%r%a');
}

function etiquetaEntrega(array $pedido): string
{
    if (in_array($pedido['estado'], ['Entregado', 'Cancelado', 'Borrador'], true)) {
        return '';
    }
    $d = diasParaEntrega($pedido['fecha_entrega']);
    if ($d === null) return '';
    if ($d < 0)  return '<span class="chip chip-danger">' . abs($d) . ' d de retraso</span>';
    if ($d === 0) return '<span class="chip chip-warn">Vence hoy</span>';
    if ($d <= 2) return '<span class="chip chip-warn">Vence en ' . $d . ' d</span>';
    return '';
}

function iniciales(string $nombre): string
{
    $partes = preg_split('/\s+/', trim($nombre)) ?: [];
    $ini = '';
    foreach (array_slice($partes, 0, 2) as $p) {
        $ini .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $ini ?: '?';
}

/** Etiqueta legible del rol. */
function nombreRol(string $rol): string
{
    return ['cliente' => 'Cliente', 'empleado' => 'Empleado', 'administrador' => 'Administrador'][$rol] ?? $rol;
}

/** Iconos SVG en línea (trazos simples propios). */
function icono(string $nombre, int $tam = 18): string
{
    $p = [
        'home'     => '<path d="M3 11l9-7 9 7"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/>',
        'plus'     => '<path d="M12 5v14M5 12h14"/>',
        'bell'     => '<path d="M6 16V11a6 6 0 1 1 12 0v5l2 2H4z"/><path d="M10 20a2 2 0 0 0 4 0"/>',
        'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>',
        'users'    => '<circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-5.5 7-5.5s7 2 7 5.5"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14.5c2.4.6 4 2.3 4 5.5"/>',
        'box'      => '<path d="M3 7l9-4 9 4v10l-9 4-9-4z"/><path d="M3 7l9 4 9-4M12 11v10"/>',
        'chart'    => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'tag'      => '<path d="M3 12V3h9l9 9-9 9z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
        'activity' => '<path d="M3 12h4l3-8 4 16 3-8h4"/>',
        'logout'   => '<path d="M15 4h4v16h-4"/><path d="M10 8l-4 4 4 4M6 12h10"/>',
        'search'   => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/>',
        'file'     => '<path d="M6 2h9l5 5v15H6z"/><path d="M14 2v6h6"/>',
        'download' => '<path d="M12 4v11M7 10l5 5 5-5M4 20h16"/>',
        'sun'      => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'moon'     => '<path d="M20 14.5A8 8 0 1 1 9.5 4a6.5 6.5 0 0 0 10.5 10.5z"/>',
        'send'     => '<path d="M4 12l16-8-6 16-2.5-6.5z"/><path d="M11.5 13.5L20 4"/>',
        'chat'     => '<path d="M4 5h16v11H9l-5 4z"/><path d="M8 10h8M8 13h5"/>',
        'swap'     => '<path d="M7 4l-3 3 3 3M4 7h13M17 20l3-3-3-3M20 17H7"/>',
        'briefcase'=> '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M3 13h18"/>',
        'printer'  => '<path d="M6 9V3h12v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M6 14h12v7H6z"/>',
        'trend'    => '<path d="M3 17l6-6 4 4 8-8"/><path d="M15 7h6v6"/>',
        'upload'   => '<path d="M12 20V9M7 14l5-5 5 5M4 4h16"/>',
        'check'    => '<path d="M5 12l5 5 9-10"/>',
        'x'        => '<path d="M6 6l12 12M18 6L6 18"/>',
        'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'alert'    => '<path d="M12 3l10 18H2z"/><path d="M12 10v5M12 18v.5"/>',
        'menu'     => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'inbox'    => '<path d="M3 13l3-9h12l3 9v7H3z"/><path d="M3 13h5l1 3h6l1-3h5"/>',
        'layers'   => '<path d="M12 3l9 5-9 5-9-5z"/><path d="M3 13l9 5 9-5"/>',
        'truck'    => '<path d="M2 6h12v10H2zM14 10h4l3 3v3h-7"/><circle cx="6" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
        'palette'  => '<path d="M12 3a9 9 0 1 0 0 18c1.5 0 2-1 2-2s-1-2 0-3 2-1 3-1a4 4 0 0 0 4-4c0-4.4-4-8-9-8z"/><circle cx="7.5" cy="11" r="1"/><circle cx="10" cy="7" r="1"/><circle cx="15" cy="7.5" r="1"/>',
        'cog'      => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M4.9 19.1L7 17M17 7l2.1-2.1"/>',
        'arrow'    => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'back'     => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
        'phone'    => '<path d="M5 3h4l2 5-2.5 1.5a11 11 0 0 0 6 6L16 13l5 2v4a2 2 0 0 1-2 2A17 17 0 0 1 3 5a2 2 0 0 1 2-2z"/>',
        'mail'     => '<path d="M3 5h18v14H3z"/><path d="M3 6l9 7 9-7"/>',
        'eye'      => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'shield'   => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/>',
        'sparkle'  => '<path d="M12 3l2 6 6 2-6 2-2 6-2-6-6-2 6-2z"/>',
        'calendar' => '<path d="M4 5h16v16H4zM4 10h16M8 3v4M16 3v4"/>',
        'edit'     => '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="M13 7l4 4"/>',
        'trash'    => '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/>',
        'filter'   => '<path d="M3 5h18l-7 8v6l-4 2v-8z"/>',
        'zap'      => '<path d="M13 2L4 14h7l-1 8 9-12h-7z"/>',
    ];
    $contenido = $p[$nombre] ?? $p['box'];
    return '<svg class="ico" width="' . $tam . '" height="' . $tam . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $contenido . '</svg>';
}

// ---------------------------------------------------------------------
// Tema claro/oscuro y color de acento (spec 011)
// ---------------------------------------------------------------------
const TEMAS = ['oscuro', 'claro'];
const ACENTOS = ['cian' => '#22d3ee', 'violeta' => '#a855f7', 'rosa' => '#f472b6', 'ambar' => '#fbbf24', 'verde' => '#4ade80'];

/**
 * Tema guardado en la cookie, validado con lista blanca.
 *
 * @spec RF-TEM-01, RF-TEM-03
 */
function temaActual(?string $valor = null): string
{
    $valor ??= $_COOKIE['beim_tema'] ?? '';
    return in_array($valor, TEMAS, true) ? $valor : 'oscuro';
}

/** @spec RF-TEM-02, RF-TEM-03 */
function acentoActual(?string $valor = null): string
{
    $valor ??= $_COOKIE['beim_acento'] ?? '';
    return is_string($valor) && isset(ACENTOS[$valor]) ? $valor : 'cian';
}

// ---------------------------------------------------------------------
// Foto de perfil (spec 013)
// ---------------------------------------------------------------------
/**
 * Foto de perfil o iniciales del usuario.
 *
 * @spec RF-FOT-03
 */
function avatarHtml(array $usuario, string $clase = ''): string
{
    $clases = trim('avatar ' . $clase);
    if (!empty($usuario['foto_perfil']) && !empty($usuario['id'])) {
        $src = url('/foto_perfil.php?id=' . (int) $usuario['id'] . '&v=' . substr(md5((string) $usuario['foto_perfil']), 0, 8));
        return '<span class="' . e($clases) . ' has-foto"><img src="' . e($src) . '" alt="" loading="lazy"></span>';
    }
    return '<span class="' . e($clases) . '">' . e(iniciales((string) ($usuario['nombre'] ?? '?'))) . '</span>';
}
