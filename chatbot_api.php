<?php
// Servicio JSON del asistente Beimy (spec 014).
// @spec RNF-BOT-02
// @spec RF-BOT-02, RF-BOT-03, RF-BOT-04
require __DIR__ . '/includes/bootstrap.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
@set_time_limit(75); // la IA puede probar varios modelos (máx. ~40 s)

function responderJson(int $codigo, array $datos): void
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!usuarioActual()) {
    responderJson(401, ['ok' => false, 'error' => 'sin_sesion', 'texto' => 'Tu sesión terminó. Vuelve a iniciar sesión.']);
}
$token = $_POST['csrf'] ?? '';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_string($token) || !hash_equals(csrfToken(), $token)) {
    responderJson(403, ['ok' => false, 'error' => 'csrf', 'texto' => 'La petición no es válida. Recarga la página.']);
}
$u = requiereLogin();

$accion = (string) ($_POST['accion'] ?? 'menu');
$resultado = match ($accion) {
    'opcion'  => chatbotResponderOpcion($pdo, $u, (string) ($_POST['id'] ?? '')),
    'mensaje' => chatbotPreguntar($pdo, $u, (string) ($_POST['mensaje'] ?? '')),
    default   => chatbotMenuPrincipal($u),
};
responderJson(isset($resultado['error']) && $resultado['error'] === 'opcion_no_permitida' ? 403 : 200, ['ok' => !isset($resultado['error'])] + $resultado);
