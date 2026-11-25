<?php
// Gestión segura de archivos de diseño: extensión permitida, firma real del
// contenido, tamaño máximo, nombre aleatorio y descarga con control de acceso.

const MAX_TAMANO_ARCHIVO = 10 * 1024 * 1024; // 10 MB por archivo
const MAX_ARCHIVOS_POR_ENVIO = 5;
const EXTENSIONES_PERMITIDAS = ['pdf', 'ai', 'jpg', 'jpeg', 'png', 'webp'];

function directorioUploads(): string
{
    $dir = dirname(__DIR__) . '/uploads/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

/**
 * Comprueba la firma (bytes iniciales) del archivo según su extensión.
 *
 * @spec RF-ARC-01
 */
function firmaValida(string $rutaTemporal, string $ext): bool
{
    $fh = fopen($rutaTemporal, 'rb');
    if (!$fh) {
        return false;
    }
    $cabecera = fread($fh, 16) ?: '';
    fclose($fh);

    return match ($ext) {
        'pdf'         => str_starts_with($cabecera, '%PDF'),
        'ai'          => str_starts_with($cabecera, '%PDF') || str_starts_with($cabecera, '%!PS'),
        'jpg', 'jpeg' => str_starts_with($cabecera, "\xFF\xD8\xFF"),
        'png'         => str_starts_with($cabecera, "\x89PNG\r\n\x1A\n"),
        'webp'        => str_starts_with($cabecera, 'RIFF') && substr($cabecera, 8, 4) === 'WEBP',
        default       => false,
    };
}

/**
 * Valida los archivos enviados en un campo múltiple (name="archivos[]").
 * Devuelve [errores, archivos válidos listos para guardar].
 * @spec RF-ARC-01
 */
function validarArchivosSubidos(?array $campo): array
{
    $errores = [];
    $validos = [];
    if (!$campo || !isset($campo['name'])) {
        return [$errores, $validos];
    }

    $nombres = (array) $campo['name'];
    $total = 0;
    foreach ($nombres as $i => $nombre) {
        $error = ((array) $campo['error'])[$i] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE || $nombre === '') {
            continue;
        }
        $total++;
        $nombreSeguro = mb_substr(preg_replace('/[^\p{L}\p{N}\s._-]/u', '_', basename((string) $nombre)), 0, 200);

        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            $errores[] = "«{$nombreSeguro}» supera el tamaño máximo permitido (10 MB).";
            continue;
        }
        if ($error !== UPLOAD_ERR_OK) {
            $errores[] = "No se pudo recibir «{$nombreSeguro}». Inténtalo de nuevo.";
            continue;
        }
        $tmp = ((array) $campo['tmp_name'])[$i];
        $tamano = (int) (((array) $campo['size'])[$i] ?? 0);
        $ext = strtolower(pathinfo($nombreSeguro, PATHINFO_EXTENSION));

        if (!in_array($ext, EXTENSIONES_PERMITIDAS, true)) {
            $errores[] = "«{$nombreSeguro}» no es un formato permitido. Usa PDF, AI, JPG, PNG o WEBP.";
            continue;
        }
        if ($tamano <= 0 || $tamano > MAX_TAMANO_ARCHIVO) {
            $errores[] = "«{$nombreSeguro}» supera el tamaño máximo permitido (10 MB).";
            continue;
        }
        if (!is_uploaded_file($tmp) || !firmaValida($tmp, $ext)) {
            $errores[] = "El contenido de «{$nombreSeguro}» no corresponde a un archivo $ext válido.";
            continue;
        }
        $mime = null;
        if (class_exists('finfo')) {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: null;
        }
        $validos[] = ['tmp' => $tmp, 'nombre' => $nombreSeguro, 'ext' => $ext, 'tamano' => $tamano, 'mime' => $mime];
    }

    if ($total > MAX_ARCHIVOS_POR_ENVIO) {
        $errores[] = 'Puedes adjuntar máximo ' . MAX_ARCHIVOS_POR_ENVIO . ' archivos por envío.';
    }
    return [$errores, $validos];
}

/**
 * Mueve el archivo a uploads/ con un nombre aleatorio y lo registra. Devuelve el nombre generado.
 *
 * @spec RF-ARC-02
 */
function guardarArchivo(PDO $pdo, int $pedidoId, array $archivo, int $usuarioId): string
{
    $nombreGenerado = 'p' . $pedidoId . '_' . bin2hex(random_bytes(16)) . '.' . $archivo['ext'];
    if (!move_uploaded_file($archivo['tmp'], directorioUploads() . $nombreGenerado)) {
        throw new RuntimeException('No se pudo guardar el archivo en el servidor.');
    }
    $pdo->prepare('INSERT INTO archivos_pedido (pedido_id, nombre_original, ruta, mime, tamano, subido_por, fecha_subida) VALUES (?, ?, ?, ?, ?, ?, ?)')
        ->execute([$pedidoId, $archivo['nombre'], $nombreGenerado, $archivo['mime'], $archivo['tamano'], $usuarioId, ahora()]);
    return $nombreGenerado;
}

function archivosDePedido(PDO $pdo, int $pedidoId): array
{
    $stmt = $pdo->prepare('SELECT a.*, u.nombre AS subido_por_nombre FROM archivos_pedido a LEFT JOIN usuarios u ON u.id = a.subido_por WHERE a.pedido_id = ? ORDER BY a.fecha_subida');
    $stmt->execute([$pedidoId]);
    return $stmt->fetchAll();
}

function formatoTamano(?int $bytes): string
{
    if (!$bytes) return '—';
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024) . ' KB';
    return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
}
