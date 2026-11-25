<?php
// Seguridad transversal: sesión, cabeceras HTTP, CSRF, manejo de errores.

/** @spec RF-AUT-06 */
function iniciarSesionSegura(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('BEIMTRACK_IA');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/** @spec RNF-SEG-03 */
function enviarCabecerasSeguridad(): void
{
    if (headers_sent()) {
        return;
    }
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
}

// ---------------------------------------------------------------------
// CSRF
// ---------------------------------------------------------------------
/** @spec RNF-SEG-01 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function campoCsrf(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrfToken()) . '">';
}

/**
 * Exige método POST y token CSRF válido. Si falla, vuelve con un mensaje.
 *
 * @spec RNF-SEG-01
 */
function requierePostConCsrf(string $volverA): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirigir($volverA);
    }
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals(csrfToken(), $token)) {
        flash('error', 'El formulario expiró o no es válido. Inténtalo de nuevo.');
        redirigir($volverA);
    }
}

// ---------------------------------------------------------------------
// Manejo centralizado de errores
// ---------------------------------------------------------------------
/** @spec RNF-SEG-06 */
function registrarManejadorErrores(): void
{
    $debug = envBool('APP_DEBUG', false);
    ini_set('display_errors', $debug ? '1' : '0');
    error_reporting(E_ALL);

    set_exception_handler(function (Throwable $ex) use ($debug) {
        error_log('[BEIM Track] ' . get_class($ex) . ': ' . $ex->getMessage() . ' en ' . $ex->getFile() . ':' . $ex->getLine());
        if (!headers_sent()) {
            http_response_code(500);
        }
        $detalle = $debug ? '<pre style="white-space:pre-wrap;color:#fca5a5">' . e($ex->getMessage()) . '</pre>' : '';
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>BEIM Track IA · Error</title>'
           . '<style>body{background:#070b14;color:#e6ebf5;font-family:system-ui,sans-serif;display:grid;place-items:center;min-height:100vh;margin:0}'
           . 'div{max-width:520px;padding:32px;border:1px solid #1f2a44;border-radius:16px;background:#0e1424}p{color:#8a94ad}a{color:#22d3ee}</style></head>'
           . '<body><div><h1>Ocurrió un error inesperado</h1><p>La operación no se completó. El error quedó registrado para revisión.</p>'
           . $detalle . '<p><a href="' . e(url('/index.php')) . '">Volver al inicio</a></p></div></body></html>';
    });
}

/** IP del cliente para el control de intentos de inicio de sesión. */
function ipCliente(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}
