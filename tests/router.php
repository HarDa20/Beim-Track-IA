<?php
// Enrutador para el servidor integrado de PHP (php -S) usado en las pruebas.
// Reproduce las reglas de los archivos .htaccess, que Apache aplica en XAMPP
// pero que php -S ignora.
$ruta = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
if (preg_match('#^/(config|includes|database|tests|docs|uploads|specs|\.opencode)(/|$)#', $ruta)
    || preg_match('#(^|/)\.(env|git|htaccess)|(^|/)opencode\.json$#', $ruta)
    || preg_match('#\.(sql|md|log|sh|example)$#', $ruta)) {
    http_response_code(403);
    echo 'Acceso denegado';
    return true;
}
return false;
