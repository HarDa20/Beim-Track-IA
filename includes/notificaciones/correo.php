<?php
// Cliente SMTP mínimo (sin librerías externas) con STARTTLS (puerto 587)
// o SSL implícito (puerto 465) y autenticación AUTH LOGIN.
// Para Gmail se requiere una "contraseña de aplicación".

/**
 * Devuelve [ok, error]. Con $html envía un mensaje multipart (HTML + texto).
 *
 * @spec RF-ALE-03, RF-ALE-04
 */
function enviarCorreoSmtp(string $para, string $nombre, string $asunto, string $texto, ?string $html = null): array
{
    if (!filter_var($para, FILTER_VALIDATE_EMAIL)) {
        return [false, 'Correo de destino inválido.'];
    }
    $host = env('MAIL_HOST');
    $puerto = (int) env('MAIL_PORT', 587);
    $remoto = ($puerto === 465 ? 'ssl://' : 'tcp://') . $host . ':' . $puerto;

    $socket = @stream_socket_client($remoto, $errno, $errstr, 8, STREAM_CLIENT_CONNECT);
    if (!$socket) {
        return [false, "No se pudo conectar a $host:$puerto ($errstr)"];
    }
    stream_set_timeout($socket, 10);

    $leer = function () use ($socket): string {
        $datos = '';
        while (($linea = fgets($socket, 515)) !== false) {
            $datos .= $linea;
            if (isset($linea[3]) && $linea[3] === ' ') {
                break;
            }
        }
        return $datos;
    };
    $comando = function (string $cmd, array $esperados) use ($socket, $leer): string {
        fwrite($socket, $cmd . "\r\n");
        $r = $leer();
        if (!in_array((int) substr($r, 0, 3), $esperados, true)) {
            throw new RuntimeException('SMTP respondió: ' . trim($r));
        }
        return $r;
    };

    try {
        $saludo = $leer();
        if ((int) substr($saludo, 0, 3) !== 220) {
            throw new RuntimeException('Saludo SMTP inesperado: ' . trim($saludo));
        }
        $dominio = parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), PHP_URL_HOST) ?: 'localhost';
        $comando('EHLO ' . $dominio, [250]);
        if ($puerto !== 465) {
            $comando('STARTTLS', [220]);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('No se pudo iniciar TLS.');
            }
            $comando('EHLO ' . $dominio, [250]);
        }
        $comando('AUTH LOGIN', [334]);
        $comando(base64_encode((string) env('MAIL_USER')), [334]);
        $comando(base64_encode((string) env('MAIL_PASS')), [235]);

        $desde = (string) env('MAIL_FROM');
        $comando('MAIL FROM:<' . $desde . '>', [250]);
        $comando('RCPT TO:<' . $para . '>', [250, 251]);
        $comando('DATA', [354]);

        $codificar = fn(string $t) => '=?UTF-8?B?' . base64_encode($t) . '?=';
        $cabeceras = [
            'Date: ' . date('r'),
            'From: ' . $codificar((string) env('MAIL_FROM_NAME', 'BEIM Track')) . ' <' . $desde . '>',
            'To: ' . ($nombre !== '' ? $codificar($nombre) . ' ' : '') . '<' . $para . '>',
            'Subject: ' . $codificar($asunto),
            'MIME-Version: 1.0',
        ];
        if ($html === null) {
            $cabeceras[] = 'Content-Type: text/plain; charset=UTF-8';
            $cabeceras[] = 'Content-Transfer-Encoding: base64';
            $contenido = chunk_split(base64_encode($texto . "\n\n— BEIM Track"));
        } else {
            // multipart/alternative: el cliente de correo muestra el HTML y, si no puede, el texto.
            $limite = 'beim_' . bin2hex(random_bytes(12));
            $cabeceras[] = 'Content-Type: multipart/alternative; boundary="' . $limite . '"';
            $contenido = "--$limite\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($texto))
                . "--$limite\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($html))
                . "--$limite--";
        }
        $cuerpo = implode("\r\n", $cabeceras) . "\r\n\r\n" . $contenido;
        fwrite($socket, $cuerpo . "\r\n.\r\n");
        $r = $leer();
        if ((int) substr($r, 0, 3) !== 250) {
            throw new RuntimeException('El servidor rechazó el mensaje: ' . trim($r));
        }
        @fwrite($socket, "QUIT\r\n");
        fclose($socket);
        return [true, null];
    } catch (Throwable $ex) {
        @fclose($socket);
        return [false, $ex->getMessage()];
    }
}
