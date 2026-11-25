<?php
// Servidor que imita la API de Google Gemini para el arnés del chatbot.
// Uso (lo levanta el arnés):  php -S 127.0.0.1:8765 tests/simulador_gemini.php
// Guarda cada petición recibida en el archivo temporal beim_gemini_captura.json.
// Con la clave "falla" responde 500 para probar el respaldo del asistente.

$cuerpo = file_get_contents('php://input');
$clave = $_SERVER['HTTP_X_GOOG_API_KEY'] ?? '';
file_put_contents(sys_get_temp_dir() . '/beim_gemini_captura.json', json_encode([
    'ruta'   => $_SERVER['REQUEST_URI'],
    'clave'  => $clave,
    'cuerpo' => json_decode($cuerpo, true),
], JSON_UNESCAPED_UNICODE));

header('Content-Type: application/json');
if ($clave === 'falla') {
    http_response_code(500);
    echo '{"error":{"code":500,"message":"Simulación de falla"}}';
    exit;
}
echo json_encode(['candidates' => [['content' => ['role' => 'model', 'parts' => [
    ['text' => "**Hola** desde el simulador.\n* Tu pedido va bien."],
]]]]], JSON_UNESCAPED_UNICODE);
