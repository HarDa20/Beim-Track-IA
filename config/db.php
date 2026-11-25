<?php
// @spec RNF-SEG-07
// Conexión PDO a la base de datos.
// Las credenciales se leen del archivo .env (ver .env.example). Si no existe,
// se usan los valores por defecto de XAMPP: localhost / beimtrack_ia / root / sin clave.

require_once __DIR__ . '/env.php';

date_default_timezone_set(env('APP_TIMEZONE', 'America/Bogota'));

if (!isset($pdo)) {
    $opciones = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        if (env('DB_DRIVER', 'mysql') === 'sqlite') {
            // Solo para las pruebas automáticas (tests/).
            $pdo = new PDO('sqlite:' . env('DB_PATH'), null, null, $opciones);
            $pdo->exec('PRAGMA foreign_keys = ON');
        } else {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                env('DB_HOST', 'localhost'),
                env('DB_PORT', '3306'),
                env('DB_NAME', 'beimtrack_ia')
            );
            $pdo = new PDO($dsn, env('DB_USER', 'root'), env('DB_PASS', ''), $opciones);
            // Misma zona horaria en PHP y MySQL.
            $pdo->exec("SET time_zone = '" . date('P') . "'");
        }
    } catch (PDOException $e) {
        // El detalle técnico va al log del servidor, nunca a la pantalla.
        error_log('[BEIM Track] Error de conexión a la base de datos: ' . $e->getMessage());
        http_response_code(503);
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>BEIM Track IA · Servicio no disponible</title>'
           . '<style>body{background:#070b14;color:#e6ebf5;font-family:system-ui,sans-serif;display:grid;place-items:center;min-height:100vh;margin:0}'
           . 'div{max-width:440px;padding:32px;border:1px solid #1f2a44;border-radius:16px;background:#0e1424}h1{font-size:20px}p{color:#8a94ad}</style></head>'
           . '<body><div><h1>No fue posible conectar con la base de datos</h1>'
           . '<p>Verifica que MySQL esté iniciado en XAMPP y que la base de datos <strong>beimtrack_ia</strong> haya sido importada desde <code>database/schema.sql</code>.</p></div></body></html>';
        exit;
    }
}
