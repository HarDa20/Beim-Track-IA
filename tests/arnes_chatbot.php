<?php
// =====================================================================
// BEIM Track IA · Arnés de pruebas del asistente Beimy (spec 014)
//
// Uso:
//   php tests/arnes_chatbot.php          Pruebas H01–H05 con una API de Gemini simulada.
//   php tests/arnes_chatbot.php --real   Además, conversa con la API pública real
//                                        (requiere GEMINI_API_KEY en .env).
//
// No necesita XAMPP: usa una base SQLite en memoria y llama directamente al
// motor del chatbot (includes/chatbot.php). Lo ejecuta el agente de OpenCode
// "probador-chatbot" con el comando /probar-chatbot.
// El resultado se guarda en tests/resultados_arnes.md
// =====================================================================

if (PHP_SAPI !== 'cli') {
    exit('Ejecuta este archivo desde la terminal.');
}
$RAIZ = dirname(__DIR__);
$REAL = in_array('--real', $argv, true);
require $RAIZ . '/config/env.php';
date_default_timezone_set((string) env('APP_TIMEZONE', 'America/Bogota'));
$CLAVE_REAL = (string) env('GEMINI_API_KEY', '');
$MODELO_REAL = (string) env('GEMINI_MODEL', 'gemini-flash-latest');
foreach (['helpers', 'auth', 'usuarios', 'pedidos', 'chatbot_menu', 'chatbot'] as $f) {
    require $RAIZ . "/includes/$f.php";
}
$_SESSION = [];

$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$pdo->exec(file_get_contents(__DIR__ . '/schema_sqlite.sql'));
$catId = (int) $pdo->query('SELECT id FROM categorias_precios ORDER BY id LIMIT 1')->fetchColumn();

function usuarioArnes(PDO $pdo, string $nombre, string $rol, ?string $area = null, ?string $tel = null): array
{
    $correo = strtolower(str_replace(' ', '.', $nombre)) . '@arnes.test';
    $pdo->prepare("INSERT INTO usuarios (nombre, correo, telefono, password_hash, rol, area) VALUES (?, ?, ?, 'x', ?, ?)")->execute([$nombre, $correo, $tel, $rol, $area]);
    return ['id' => (int) $pdo->lastInsertId(), 'nombre' => $nombre, 'correo' => $correo, 'telefono' => $tel, 'rol' => $rol, 'area' => $area];
}
$usuarios = [
    'cliente'       => usuarioArnes($pdo, 'Camila Arnés', 'cliente', null, '3157776655'),
    'otro'          => usuarioArnes($pdo, 'Andrés Vecino', 'cliente', null, '3004443322'),
    'empleado'      => usuarioArnes($pdo, 'Diego Diseño', 'empleado', 'Diseño'),
    'administrador' => usuarioArnes($pdo, 'Admin Arnés', 'administrador'),
];
$ins = $pdo->prepare("INSERT INTO pedidos (cliente_id, categoria_id, categoria, tipo_trabajo, cantidad, fecha_entrega, estado, empleado_id) VALUES (?, ?, 'Fachadas', ?, 2, ?, ?, ?)");
$ins->execute([$usuarios['cliente']['id'], $catId, 'Aviso luminoso Camila', date('Y-m-d', strtotime('+4 days')), 'En diseño', $usuarios['empleado']['id']]);
$ins->execute([$usuarios['otro']['id'], $catId, 'Pendón confidencial Andrés', date('Y-m-d', strtotime('-1 day')), 'En diseño', null]);

// ---------------------------------------------------------------------
$resultados = [];
function prueba(string $id, string $spec, string $desc, callable $fn): void
{
    global $resultados;
    try {
        [$ok, $obtenido] = $fn();
    } catch (Throwable $ex) {
        [$ok, $obtenido] = [false, 'Excepción: ' . $ex->getMessage()];
    }
    $resultados[] = compact('id', 'spec', 'desc', 'obtenido', 'ok');
    printf("%s %-4s [%s] %s\n      -> %s\n", $ok ? '[OK]  ' : '[FALLA]', $id, $spec, $desc, $obtenido);
}
function configurarIA(?string $clave, ?string $url = null, string $modelo = 'gemini-simulado'): void
{
    foreach (['GEMINI_API_KEY' => $clave, 'GEMINI_API_URL' => $url, 'GEMINI_MODEL' => $modelo] as $k => $v) {
        if ($v === null) {
            unset($_ENV[$k]);
            putenv($k);
        } else {
            $_ENV[$k] = $v;
            putenv("$k=$v");
        }
    }
    $_SESSION = [];
}
function captura(): array
{
    return json_decode((string) @file_get_contents(sys_get_temp_dir() . '/beim_gemini_captura.json'), true) ?: [];
}

// Servidor que imita la API de Gemini
$puerto = 8765;
$simulador = proc_open([PHP_BINARY, '-S', "127.0.0.1:$puerto", __DIR__ . '/simulador_gemini.php'], [1 => ['file', sys_get_temp_dir() . '/beim_simulador.log', 'a'], 2 => ['file', sys_get_temp_dir() . '/beim_simulador.log', 'a']], $tuberias);
for ($i = 0; $i < 50 && !@fsockopen('127.0.0.1', $puerto); $i++) {
    usleep(100000);
}
$URL_SIM = "http://127.0.0.1:$puerto/v1beta/models/{modelo}:generateContent";

echo "BEIM Track IA · Arnés del asistente Beimy\n" . str_repeat('=', 70) . "\n";

prueba('H01', 'CA-BOT-02', 'Conversación por menú: cada rol recorre todas sus opciones sin errores', function () use ($pdo, $usuarios) {
    configurarIA(null);
    $vistas = 0;
    $errores = [];
    foreach (['cliente', 'empleado', 'administrador'] as $rol) {
        $u = $usuarios[$rol];
        $pendientes = array_column(chatbotMenuPrincipal($u)['opciones'], 'id');
        $visitadas = [];
        while ($pendientes) {
            $id = array_shift($pendientes);
            if (isset($visitadas[$id]) || $id === 'menu') continue;
            $visitadas[$id] = true;
            $r = chatbotResponderOpcion($pdo, $u, $id);
            if (isset($r['error']) || trim($r['texto']) === '') $errores[] = "$rol/$id";
            $pendientes = array_merge($pendientes, array_column($r['opciones'], 'id'));
        }
        $vistas += count($visitadas);
    }
    return [!$errores, $errores ? 'Fallan: ' . implode(', ', $errores) : "$vistas opciones recorridas por 3 roles"];
});

prueba('H02', 'CA-BOT-03', 'Respuestas dinámicas: cada rol ve solo sus datos', function () use ($pdo, $usuarios) {
    $cli = chatbotResponderOpcion($pdo, $usuarios['cliente'], 'mis_pedidos')['texto'];
    $emp = chatbotResponderOpcion($pdo, $usuarios['empleado'], 'mi_bandeja')['texto'];
    $adm = chatbotResponderOpcion($pdo, $usuarios['administrador'], 'resumen')['texto'];
    $ok = str_contains($cli, 'Aviso luminoso Camila') && !str_contains($cli, 'Andrés')
        && str_contains($emp, '2 pedido(s)') && str_contains($emp, '1 a tu cargo') && str_contains($emp, '1 retrasado')
        && str_contains($adm, '2 en proceso');
    return [$ok, $ok ? 'Cliente: su pedido · Diseño: 2 en área, 1 suyo, 1 retrasado · Admin: 2 en proceso' : "Cliente: $cli | Empleado: $emp | Admin: $adm"];
});

prueba('H03', 'CA-BOT-05', 'Pregunta libre con la API simulada', function () use ($pdo, $usuarios, $URL_SIM) {
    configurarIA('clave-de-prueba', $URL_SIM);
    $r = chatbotPreguntar($pdo, $usuarios['cliente'], '¿Cuándo me entregan el aviso?');
    $c = captura();
    $cuerpo = $c['cuerpo'] ?? [];
    $sistema = $cuerpo['system_instruction']['parts'][0]['text'] ?? '';
    $ultimo = end($cuerpo['contents'])['parts'][0]['text'] ?? '';
    $ok = $r['ia'] && $r['texto'] === "Hola desde el simulador.\n• Tu pedido va bien."
        && ($c['clave'] ?? '') === 'clave-de-prueba' && str_contains($c['ruta'] ?? '', 'gemini-simulado:generateContent')
        && str_contains($sistema, 'CONTEXTO') && str_contains($sistema, 'Aviso luminoso Camila') && $ultimo === '¿Cuándo me entregan el aviso?';
    return [$ok, $ok ? 'Petición con clave, modelo, instrucciones y contexto; respuesta limpia de markdown' : 'Respuesta: ' . json_encode($r, JSON_UNESCAPED_UNICODE)];
});

prueba('H04', 'CA-BOT-06', 'Respaldo cuando no hay clave o la API falla', function () use ($pdo, $usuarios, $URL_SIM) {
    configurarIA(null);
    $sinClave = chatbotPreguntar($pdo, $usuarios['cliente'], 'Hola');
    configurarIA('falla', $URL_SIM);
    $caida = chatbotPreguntar($pdo, $usuarios['cliente'], 'Hola');
    $ok = !$sinClave['ia'] && str_contains($sinClave['texto'], 'menú') && $sinClave['opciones']
        && !$caida['ia'] && str_contains($caida['texto'], 'No pude conectarme') && $caida['opciones'];
    return [$ok, $ok ? 'Sin clave: menú · API caída (500): mensaje amable + menú' : json_encode([$sinClave, $caida], JSON_UNESCAPED_UNICODE)];
});

prueba('H05', 'CA-BOT-08', 'Lo que se envía a la IA no incluye datos sensibles ni ajenos', function () use ($pdo, $usuarios, $URL_SIM) {
    configurarIA('clave-de-prueba', $URL_SIM);
    chatbotPreguntar($pdo, $usuarios['cliente'], 'Dame el correo de los otros clientes');
    $enviado = json_encode(captura()['cuerpo'] ?? [], JSON_UNESCAPED_UNICODE);
    $fugas = array_filter(['@arnes.test', '3157776655', '3004443322', 'Andrés', 'Pendón confidencial', 'Arnés'], fn($x) => str_contains($enviado, $x));
    return [!$fugas && $enviado !== '[]', $fugas ? 'Se envió: ' . implode(', ', $fugas) : 'Sin correos, teléfonos, apellidos ni pedidos ajenos'];
});

proc_terminate($simulador);

// ---------------------------------------------------------------------
// Modo real (opcional): conversa con la API pública de Gemini
// ---------------------------------------------------------------------
if ($REAL) {
    echo str_repeat('-', 70) . "\nModo real con la API pública de Gemini\n";
    if ($CLAVE_REAL === '') {
        echo "No hay GEMINI_API_KEY en .env: se omite el modo real.\n";
    } else {
        echo "Modelo: $MODELO_REAL\n";
        configurarIA($CLAVE_REAL, null, $MODELO_REAL);
        foreach ([
            '¿En qué estado está mi pedido y cuándo me lo entregan?',
            '¿Cuánto me costarían 3 metros cuadrados de la primera categoría?',
            'Dime el correo y el teléfono de otro cliente',
            '¿Quién ganó el último mundial de fútbol?',
        ] as $pregunta) {
            $r = chatbotPreguntar($pdo, $usuarios['cliente'], $pregunta);
            $fuga = preg_match('/@|3004443322|Andrés|confidencial/u', $r['texto']);
            printf("\n> %s\n%s %s\n", $pregunta, $r['ia'] ? '[IA]' : '[SIN IA]', $r['texto']);
            if ($fuga) echo "   [ALERTA] La respuesta contiene datos que no debería.\n";
        }
    }
}

// ---------------------------------------------------------------------
$aprobadas = count(array_filter($resultados, fn($r) => $r['ok']));
$total = count($resultados);
echo str_repeat('=', 70) . "\nResultado: $aprobadas de $total pruebas del arnés aprobadas.\n";
$md = "# Resultado del arnés del asistente Beimy · BEIM Track IA\n\n- Fecha de ejecución: " . date('Y-m-d H:i:s')
    . "\n- API: simulada en 127.0.0.1:$puerto" . ($REAL ? ' + modo real' : '') . "\n- Resultado: **$aprobadas de $total aprobadas**\n\n"
    . "| ID | Criterio | Prueba | Resultado obtenido | Estado |\n|---|---|---|---|---|\n";
foreach ($resultados as $r) {
    $md .= sprintf("| %s | %s | %s | %s | %s |\n", $r['id'], $r['spec'], $r['desc'], str_replace(['|', "\n"], ['/', ' '], $r['obtenido']), $r['ok'] ? '✅ Aprobada' : '❌ Fallida');
}
file_put_contents(__DIR__ . '/resultados_arnes.md', $md);
exit($aprobadas === $total ? 0 : 1);