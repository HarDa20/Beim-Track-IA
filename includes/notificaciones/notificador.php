<?php
// Módulo de notificaciones externas por correo electrónico (SMTP).
//
// Diseño:
//  * Se ejecuta DESPUÉS de guardar los cambios: si un envío falla, el pedido
//    y su historial ya quedaron guardados.
//  * Cada intento queda registrado en notificaciones_envios con estado
//    "enviado", "fallido" (con el detalle del error) u "omitido".
//  * Mientras el correo no tenga credenciales en .env, se registra como
//    "omitido". El sistema NUNCA marca como enviado algo que no se envió.
//  * La columna "canal" permite agregar otros medios en el futuro sin
//    cambiar la estructura del registro.

require_once __DIR__ . '/correo.php';
require_once __DIR__ . '/plantilla_correo.php';

function estadoCanales(): array
{
    return [
        'correo' => [
            'activo'     => envBool('MAIL_ENABLED') && env('MAIL_HOST') && env('MAIL_USER') && env('MAIL_PASS') && env('MAIL_FROM'),
            'habilitado' => envBool('MAIL_ENABLED'),
            'requisitos' => 'MAIL_ENABLED=true, MAIL_HOST, MAIL_PORT, MAIL_USER, MAIL_PASS y MAIL_FROM (cuenta SMTP)',
        ],
    ];
}

/** @spec RF-ALE-03 */
function registrarEnvio(PDO $pdo, ?int $pedidoId, string $canal, string $destinatario, string $evento, string $mensaje, string $estado, ?string $error = null): void
{
    $pdo->prepare('INSERT INTO notificaciones_envios (pedido_id, canal, destinatario, evento, mensaje, estado, detalle_error, creado_en) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
        ->execute([$pedidoId, $canal, mb_substr($destinatario, 0, 150), $evento, mb_substr($mensaje, 0, 500), $estado, $error ? mb_substr($error, 0, 500) : null, ahora()]);
}

/**
 * Avisa por correo al cliente del pedido.
 *
 * @spec RF-ALE-03
 */
function notificarCliente(PDO $pdo, ?array $pedido, string $evento, string $mensaje): void
{
    if (!$pedido) {
        return;
    }
    $correo = trim((string) ($pedido['cliente_correo'] ?? ''));
    if ($correo === '') {
        return;
    }
    $pedidoId = (int) $pedido['id'];
    $canal = estadoCanales()['correo'];

    if (!$canal['activo']) {
        registrarEnvio($pdo, $pedidoId, 'correo', $correo, $evento, $mensaje, 'omitido', 'Correo no configurado: ' . $canal['requisitos']);
        return;
    }
    [$asunto, $html, $texto] = plantillaCorreo($pedido, $evento, $mensaje);
    [$ok, $error] = enviarCorreoSmtp($correo, $pedido['cliente_nombre'] ?? '', $asunto, $texto, $html);
    registrarEnvio($pdo, $pedidoId, 'correo', $correo, $evento, $mensaje, $ok ? 'enviado' : 'fallido', $error);
}
