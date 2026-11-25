<?php
// Plantilla HTML de los correos al cliente (spec 006 · RF-ALE-04).
// HTML "a prueba de correo": tablas y estilos en línea, sin CSS externo,
// sin SVG ni JavaScript, para que se vea bien en Gmail, Outlook y celular.

/** Datos visuales de cada tipo de aviso. */
function estiloEventoCorreo(string $evento, string $estado): array
{
    if ($estado === 'Cancelado') {
        return ['emoji' => '🛑', 'color' => '#ef4444', 'suave' => '#fef2f2', 'titulo' => 'Tu pedido fue cancelado', 'asunto' => 'Tu pedido {codigo} fue cancelado'];
    }
    return match ($evento) {
        'pedido_registrado' => ['emoji' => '✨', 'color' => '#6366f1', 'suave' => '#eef2ff', 'titulo' => '¡Recibimos tu pedido!', 'asunto' => '✨ Recibimos tu pedido {codigo}'],
        'pedido_finalizado' => ['emoji' => '🎉', 'color' => '#16a34a', 'suave' => '#f0fdf4', 'titulo' => '¡Tu pedido fue entregado!', 'asunto' => '🎉 ¡Tu pedido {codigo} fue entregado!'],
        'pedido_retrasado'  => ['emoji' => '⏰', 'color' => '#d97706', 'suave' => '#fffbeb', 'titulo' => 'Tu pedido tiene un retraso', 'asunto' => '⏰ Tu pedido {codigo} tiene un retraso'],
        default             => ['emoji' => '🚀', 'color' => '#0891b2', 'suave' => '#ecfeff', 'titulo' => '¡Tu pedido avanzó!', 'asunto' => '🚀 Tu pedido {codigo} ahora está en {estado}'],
    };
}

/** Dirección pública del sistema para el botón del correo (APP_URL o la del servidor actual). */
function urlPublicaSistema(string $ruta): ?string
{
    $base = rtrim((string) env('APP_URL', ''), '/');
    if ($base === '' && !empty($_SERVER['HTTP_HOST'])) {
        $esquema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $base = $esquema . '://' . $_SERVER['HTTP_HOST'] . (defined('BASE_URL') ? BASE_URL : '');
    }
    return $base !== '' ? $base . '/' . ltrim($ruta, '/') : null;
}

/**
 * Arma el correo de un aviso: [asunto, html, texto plano].
 *
 * @spec RF-ALE-04
 */
function plantillaCorreo(array $pedido, string $evento, string $mensaje): array
{
    $h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    $codigo = codigoPedido((int) $pedido['id']);
    $estado = (string) $pedido['estado'];
    $estilo = estiloEventoCorreo($evento, $estado);
    $nombre = explode(' ', trim((string) ($pedido['cliente_nombre'] ?? '')))[0] ?: 'cliente';
    $asunto = str_replace(['{codigo}', '{estado}'], [$codigo, $estado], $estilo['asunto']) . ' · BEIM Track';
    $enlace = urlPublicaSistema('/pedido.php?id=' . (int) $pedido['id']);
    $color = $estilo['color'];
    $cuerpo = preg_replace('/^BEIM Track:\s*/', '', $mensaje);
    $cuerpo = mb_strtoupper(mb_substr($cuerpo, 0, 1)) . mb_substr($cuerpo, 1);

    // Línea de etapas
    $etapas = ['Recibido' => 'Recibido', 'En diseño' => 'Diseño', 'En producción' => 'Producción', 'Control de calidad' => 'Calidad', 'Listo para entrega' => 'Listo', 'Entregado' => 'Entregado'];
    $posActual = array_search($estado, array_keys($etapas), true);
    $celdas = '';
    if ($estado !== 'Cancelado' && $posActual !== false) {
        $i = 0;
        foreach ($etapas as $clave => $etiqueta) {
            $hecho = $i < $posActual || $estado === 'Entregado';
            $actual = $i === $posActual && $estado !== 'Entregado';
            $fondo = $hecho ? $color : ($actual ? '#ffffff' : '#e5e7eb');
            $borde = $hecho || $actual ? $color : '#e5e7eb';
            $marca = $hecho ? '✓' : ($actual ? '●' : '');
            $colorMarca = $hecho ? '#ffffff' : $color;
            $texto = $hecho || $actual ? '#111827' : '#9ca3af';
            $celdas .= '<td align="center" valign="top" style="padding:0 2px;width:16.66%;">'
                . '<div style="width:26px;height:26px;line-height:26px;border-radius:50%;background:' . $fondo . ';border:2px solid ' . $borde . ';color:' . $colorMarca . ';font-size:13px;font-weight:700;margin:0 auto;">' . $marca . '</div>'
                . '<div style="font-size:11px;line-height:14px;color:' . $texto . ';margin-top:6px;font-weight:' . ($actual ? '700' : '500') . ';">' . $h($etiqueta) . '</div></td>';
            $i++;
        }
    }
    $linea = $celdas ? '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:6px 0 26px;"><tr>' . $celdas . '</tr></table>' : '';

    // Detalle del pedido
    $filas = [
        'Pedido'           => '<strong style="font-family:Consolas,monospace;color:' . $color . ';">' . $h($codigo) . '</strong>',
        'Trabajo'          => $h($pedido['tipo_trabajo']),
        'Categoría'        => $h($pedido['categoria']),
        'Cantidad'         => $h(rtrim(rtrim(number_format((float) $pedido['cantidad'], 2, ',', '.'), '0'), ',') . ' ' . ($pedido['unidad'] ?? '')),
        'Estado actual'    => '<span style="display:inline-block;padding:3px 10px;border-radius:999px;background:' . $estilo['suave'] . ';color:' . $color . ';font-weight:700;font-size:12px;">' . $h($estado) . '</span>',
        'Entrega estimada' => $h(formatoFecha($pedido['fecha_entrega'])),
        'Valor estimado'   => $h(formatoMoneda($pedido['costo_estimado'])),
    ];
    $tabla = '';
    foreach ($filas as $etiqueta => $valor) {
        $tabla .= '<tr><td style="padding:9px 0;border-bottom:1px solid #f1f5f9;color:#64748b;font-size:13px;width:42%;">' . $h($etiqueta) . '</td>'
            . '<td style="padding:9px 0;border-bottom:1px solid #f1f5f9;color:#0f172a;font-size:14px;text-align:right;">' . $valor . '</td></tr>';
    }

    $boton = $enlace
        ? '<table role="presentation" cellpadding="0" cellspacing="0" align="center" style="margin:28px auto 6px;"><tr><td align="center" bgcolor="' . $color . '" style="border-radius:12px;background:' . $color . ';">'
          . '<a href="' . $h($enlace) . '" style="display:inline-block;padding:14px 30px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:12px;">Ver mi pedido →</a></td></tr></table>'
        : '';

    $html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . $h($asunto) . '</title></head>'
        . '<body style="margin:0;padding:0;background:#eef1f7;font-family:\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;">'
        . '<div style="display:none;max-height:0;overflow:hidden;opacity:0;">' . $h($mensaje) . '</div>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef1f7;padding:28px 12px;"><tr><td align="center">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 10px 30px rgba(15,23,42,.08);">'
        // Encabezado con la marca
        . '<tr><td bgcolor="#1e1b4b" style="background:#1e1b4b;background-image:linear-gradient(135deg,#0891b2 0%,#6366f1 55%,#a855f7 100%);padding:26px 30px;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>'
        . '<td valign="middle" style="width:44px;"><div style="width:42px;height:42px;line-height:42px;border-radius:12px;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.35);color:#ffffff;font-size:22px;font-weight:800;text-align:center;">B</div></td>'
        . '<td valign="middle" style="padding-left:12px;color:#ffffff;"><div style="font-size:18px;font-weight:800;letter-spacing:-.2px;">BEIM Track</div><div style="font-size:12px;opacity:.85;">Seguimiento de pedidos publicitarios</div></td>'
        . '<td align="right" valign="middle" style="color:#ffffff;font-size:12px;opacity:.85;">' . $h($codigo) . '</td>'
        . '</tr></table></td></tr>'
        // Cuerpo
        . '<tr><td style="padding:34px 30px 8px;">'
        . '<div style="width:64px;height:64px;line-height:64px;border-radius:20px;background:' . $estilo['suave'] . ';font-size:32px;text-align:center;margin:0 auto 18px;">' . $estilo['emoji'] . '</div>'
        . '<h1 style="margin:0 0 10px;font-size:24px;line-height:30px;color:#0f172a;text-align:center;font-weight:800;">' . $h($estilo['titulo']) . '</h1>'
        . '<p style="margin:0 0 26px;font-size:15px;line-height:23px;color:#475569;text-align:center;">Hola, <strong style="color:#0f172a;">' . $h($nombre) . '</strong>. ' . $h($cuerpo) . '</p>'
        . $linea
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:14px;"><tr><td style="padding:8px 20px;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0">' . $tabla . '</table></td></tr></table>'
        . $boton
        . '</td></tr>'
        // Pie
        . '<tr><td style="padding:22px 30px 28px;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #eef2f7;"><tr><td style="padding-top:18px;font-size:12px;line-height:19px;color:#94a3b8;text-align:center;">'
        . '¿Tienes dudas? Pregúntale a <strong style="color:#6366f1;">Beimy</strong>, nuestro asistente, dentro del sistema, o comunícate con Recepción de lunes a viernes de 8:00 a. m. a 6:00 p. m.<br><br>'
        . 'Recibes este correo porque tienes un pedido en BEIM Track. Tus datos se tratan según la Ley 1581 de 2012.'
        . '</td></tr></table></td></tr>'
        . '</table></td></tr></table></body></html>';

    $texto = $estilo['titulo'] . "\n\nHola, $nombre. " . $cuerpo . "\n\n"
        . "Pedido: $codigo\nTrabajo: {$pedido['tipo_trabajo']}\nEstado actual: $estado\nEntrega estimada: " . formatoFecha($pedido['fecha_entrega'])
        . "\nValor estimado: " . formatoMoneda($pedido['costo_estimado']) . ($enlace ? "\n\nVer mi pedido: $enlace" : '') . "\n\n— BEIM Track";

    return [$asunto, $html, $texto];
}
