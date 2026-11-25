<?php
// =====================================================================
// BEIM Track · Pruebas funcionales y de seguridad automatizadas
//
// Uso (con el sistema corriendo):
//     php tests/pruebas_funcionales.php http://localhost/beimtrack_ia
//
// Ejecuta peticiones HTTP reales contra la aplicación (como un navegador)
// y verifica los resultados en la base de datos configurada en .env.
// Crea usuarios temporales con correos @qa.beimtrack.test y los elimina,
// junto con sus pedidos, al terminar. No modifica otros datos.
// El resultado se guarda en tests/resultados_ultima_ejecucion.md
// =====================================================================

if (PHP_SAPI !== 'cli') {
    exit('Ejecuta este archivo desde la terminal.');
}
$BASE = rtrim($argv[1] ?? 'http://localhost/beimtrack_ia', '/');
require __DIR__ . '/../config/db.php';

// ---------------------------------------------------------------------
// Cliente HTTP con cookies por usuario
// ---------------------------------------------------------------------
final class Navegador
{
    private $ch;
    public array $ultima = [];
    public function __construct(private string $base)
    {
        $this->ch = curl_init();
        curl_setopt_array($this->ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEFILE     => '',
            CURLOPT_TIMEOUT        => 20,
        ]);
    }
    private function pedir(string $ruta, ?array $post = null): array
    {
        $url = str_starts_with($ruta, 'http') ? $ruta : $this->base . $ruta;
        curl_setopt($this->ch, CURLOPT_URL, $url);
        if ($post === null) {
            curl_setopt($this->ch, CURLOPT_HTTPGET, true);
        } else {
            curl_setopt($this->ch, CURLOPT_POST, true);
            $multipart = (bool) array_filter($post, fn($v) => $v instanceof CURLFile);
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, $multipart ? $post : http_build_query($post));
        }
        $raw = curl_exec($this->ch);
        $tam = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);
        $cab = substr($raw, 0, $tam);
        $loc = preg_match('/^Location:\s*(\S+)/mi', $cab, $m) ? $m[1] : null;
        if ($loc && !str_starts_with($loc, 'http')) {
            $loc = preg_replace('#^(https?://[^/]+).*#', '$1', $this->base) . $loc;
        }
        return $this->ultima = ['code' => curl_getinfo($this->ch, CURLINFO_HTTP_CODE), 'headers' => $cab, 'body' => substr($raw, $tam), 'location' => $loc];
    }
    public function get(string $ruta): array { return $this->pedir($ruta); }
    /** Cookies extra para la siguiente petición (tema, acento). */
    public function cookies(string $valor): void { curl_setopt($this->ch, CURLOPT_COOKIE, $valor); }
    public function post(string $ruta, array $datos): array { return $this->pedir($ruta, $datos); }
    /** POST y sigue la redirección (hasta 3 saltos) para leer el mensaje resultante. */
    public function postSeguir(string $ruta, array $datos): array
    {
        $r = $this->post($ruta, $datos);
        for ($i = 0; $i < 3 && $r['location']; $i++) {
            $r = $this->get($r['location']);
        }
        return $r;
    }
    public function token(string $ruta = '/perfil.php'): string
    {
        $r = $this->get($ruta);
        return preg_match('/name="csrf" value="([a-f0-9]{64})"/', $r['body'], $m) ? $m[1] : '';
    }
    public function sesion(): ?string
    {
        foreach (curl_getinfo($this->ch, CURLINFO_COOKIELIST) as $c) {
            $p = explode("\t", $c);
            if (($p[5] ?? '') === 'BEIMTRACK_IA') return $p[6];
        }
        return null;
    }
}

// ---------------------------------------------------------------------
// Utilidades de prueba
// ---------------------------------------------------------------------
$resultados = [];
function prueba(string $id, string $desc, string $esperado, callable $fn): void
{
    global $resultados;
    try {
        [$ok, $obtenido] = $fn();
    } catch (Throwable $ex) {
        [$ok, $obtenido] = [false, 'Excepción: ' . $ex->getMessage()];
    }
    $resultados[] = compact('id', 'desc', 'esperado', 'obtenido', 'ok');
    printf("%s %-5s %s\n      -> %s\n", $ok ? '[OK]  ' : '[FALLA]', $id, $desc, $obtenido);
}
function val(string $sql, array $p = [])
{
    global $pdo;
    $s = $pdo->prepare($sql);
    $s->execute($p);
    return $s->fetchColumn();
}
function fila(string $sql, array $p = []): ?array
{
    global $pdo;
    $s = $pdo->prepare($sql);
    $s->execute($p);
    return $s->fetch() ?: null;
}
function contiene(array $r, string $texto): bool { return str_contains(html_entity_decode($r['body'], ENT_QUOTES, 'UTF-8'), $texto); }
/**
 * Ruta de una redirección relativa a la aplicación. Quita la subcarpeta de
 * instalación (por ejemplo /beimtrack_ia en XAMPP) para que las pruebas
 * funcionen igual en la raíz del servidor o dentro de una carpeta.
 */
function ruta(?string $loc): string
{
    global $BASE;
    if (!$loc) {
        return '(sin redirección)';
    }
    $camino = (string) parse_url($loc, PHP_URL_PATH);
    $prefijo = rtrim((string) parse_url($BASE, PHP_URL_PATH), '/');
    if ($prefijo !== '' && str_starts_with($camino, $prefijo . '/')) {
        $camino = substr($camino, strlen($prefijo));
    }
    $consulta = parse_url($loc, PHP_URL_QUERY);
    return $camino . ($consulta ? '?' . $consulta : '');
}

const DOMINIO = '@qa.beimtrack.test';
const CLAVE = 'PruebaQA2026';

function limpiarQA(): void
{
    global $pdo;
    $ids = $pdo->query("SELECT id FROM usuarios WHERE correo LIKE '%" . DOMINIO . "'")->fetchAll(PDO::FETCH_COLUMN);
    if ($ids) {
        $in = implode(',', array_map('intval', $ids));
        foreach ($pdo->query("SELECT ruta FROM archivos_pedido WHERE pedido_id IN (SELECT id FROM pedidos WHERE cliente_id IN ($in))")->fetchAll(PDO::FETCH_COLUMN) as $r) {
            @unlink(__DIR__ . '/../uploads/' . basename($r));
        }
        foreach ($pdo->query("SELECT foto_perfil FROM usuarios WHERE id IN ($in) AND foto_perfil IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN) as $f) {
            @unlink(__DIR__ . '/../uploads/perfiles/' . basename($f));
        }
        $pdo->exec("DELETE FROM pedidos WHERE cliente_id IN ($in)");
        $pdo->exec("UPDATE pedidos SET empleado_id = NULL WHERE empleado_id IN ($in)");
        $pdo->exec("UPDATE pedidos SET registrado_por = NULL WHERE registrado_por IN ($in)");
        $pdo->exec("UPDATE historial_pedido SET usuario_id = NULL WHERE usuario_id IN ($in)");
        $pdo->exec("DELETE FROM alertas WHERE usuario_id IN ($in)");
        $pdo->exec("DELETE FROM usuarios WHERE id IN ($in)");
    }
    $pdo->exec("DELETE FROM intentos_login WHERE correo LIKE '%" . DOMINIO . "'");
    $pdo->exec("DELETE FROM categorias_precios WHERE categoria LIKE 'QA %'");
}

function crearUsuarioQA(string $alias, string $rol, ?string $area = null, ?string $tel = null): int
{
    global $pdo;
    $pdo->prepare('INSERT INTO usuarios (nombre, correo, telefono, password_hash, rol, area, activo, creado_en) VALUES (?, ?, ?, ?, ?, ?, 1, ?)')
        ->execute(['QA ' . ucfirst($alias), $alias . DOMINIO, $tel, password_hash(CLAVE, PASSWORD_DEFAULT), $rol, $area, date('Y-m-d H:i:s')]);
    return (int) $pdo->lastInsertId();
}

function entrar(string $alias, string $clave = CLAVE): Navegador
{
    global $BASE;
    $n = new Navegador($BASE);
    $t = $n->token('/index.php');
    $n->post('/login_process.php', ['csrf' => $t, 'correo' => $alias . DOMINIO, 'password' => $clave]);
    return $n;
}

/** Crea archivos reales de prueba. */
function archivoPrueba(string $nombre, string $contenido): CURLFile
{
    $dir = __DIR__ . '/tmp';
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    file_put_contents("$dir/$nombre", $contenido);
    return new CURLFile("$dir/$nombre", 'application/octet-stream', $nombre);
}
$PNG = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');

// ---------------------------------------------------------------------
echo "BEIM Track · Pruebas automáticas contra $BASE\n" . str_repeat('=', 70) . "\n";
limpiarQA();
$ids = [
    'admin'   => crearUsuarioQA('admin', 'administrador'),
    'recep'   => crearUsuarioQA('recepcion', 'empleado', 'Recepción'),
    'dis'     => crearUsuarioQA('diseno', 'empleado', 'Diseño'),
    'dis2'    => crearUsuarioQA('diseno2', 'empleado', 'Diseño'),
    'prod'    => crearUsuarioQA('produccion', 'empleado', 'Producción'),
    'cal'     => crearUsuarioQA('calidad', 'empleado', 'Calidad'),
    'cli1'    => crearUsuarioQA('cliente1', 'cliente', null, '3001234567'),
    'cli2'    => crearUsuarioQA('cliente2', 'cliente'),
];
$catId = (int) val('SELECT id FROM categorias_precios WHERE activa = 1 ORDER BY id LIMIT 1');
$catPrecio = (float) val('SELECT precio_por_unidad FROM categorias_precios WHERE id = ?', [$catId]);
$manana = date('Y-m-d', strtotime('+10 days'));

// =====================================================================
// 1. Autenticación y sesiones
// =====================================================================
prueba('A01', 'Inicio de sesión con credenciales válidas (cliente)', 'Redirige a /cliente/panel.php', function () {
    global $BASE;
    $n = new Navegador($BASE);
    $t = $n->token('/index.php');
    $r = $n->post('/login_process.php', ['csrf' => $t, 'correo' => 'cliente1' . DOMINIO, 'password' => CLAVE]);
    return [ruta($r['location']) === '/cliente/panel.php', 'Redirección a ' . ruta($r['location'])];
});
prueba('A02', 'Inicio de sesión con contraseña incorrecta', 'Vuelve al login con mensaje de error', function () {
    global $BASE;
    $n = new Navegador($BASE);
    $t = $n->token('/index.php');
    $r = $n->postSeguir('/login_process.php', ['csrf' => $t, 'correo' => 'cliente2' . DOMINIO, 'password' => 'Incorrecta1']);
    return [contiene($r, 'Correo o contraseña incorrectos'), contiene($r, 'Correo o contraseña incorrectos') ? 'Mensaje de error mostrado' : 'Sin mensaje'];
});
prueba('A03', 'El ID de sesión cambia al iniciar sesión (fijación)', 'ID distinto antes y después', function () {
    global $BASE;
    $n = new Navegador($BASE);
    $t = $n->token('/index.php');
    $antes = $n->sesion();
    $n->post('/login_process.php', ['csrf' => $t, 'correo' => 'cliente1' . DOMINIO, 'password' => CLAVE]);
    $despues = $n->sesion();
    return [$antes && $despues && $antes !== $despues, $antes !== $despues ? 'ID regenerado' : 'ID igual'];
});
prueba('A04', 'Cookie de sesión con HttpOnly y SameSite', 'HttpOnly; SameSite=Lax', function () {
    global $BASE;
    $r = (new Navegador($BASE))->get('/index.php');
    $ok = preg_match('/Set-Cookie: BEIMTRACK_IA=[^\r\n]*HttpOnly[^\r\n]*SameSite=Lax/i', $r['headers']);
    return [(bool) $ok, $ok ? 'Cookie segura' : 'Cookie sin banderas'];
});
prueba('A05', 'Login enviado sin token CSRF', 'Rechazado, sin sesión', function () {
    global $BASE;
    $n = new Navegador($BASE);
    $n->get('/index.php');
    $n->postSeguir('/login_process.php', ['correo' => 'cliente1' . DOMINIO, 'password' => CLAVE]);
    $r = $n->get('/cliente/panel.php');
    return [ruta($r['location']) === '/index.php', 'Panel protegido -> ' . ruta($r['location'])];
});
prueba('A06', 'Bloqueo tras 5 intentos fallidos', 'El 6.º intento con clave correcta es bloqueado', function () {
    global $BASE;
    $n = new Navegador($BASE);
    for ($i = 0; $i < 5; $i++) {
        $t = $n->token('/index.php');
        $n->post('/login_process.php', ['csrf' => $t, 'correo' => 'cliente2' . DOMINIO, 'password' => 'Mala' . $i . 'xx']);
    }
    $t = $n->token('/index.php');
    $r = $n->postSeguir('/login_process.php', ['csrf' => $t, 'correo' => 'cliente2' . DOMINIO, 'password' => CLAVE]);
    $ok = contiene($r, 'Demasiados intentos');
    global $pdo;
    $pdo->exec("DELETE FROM intentos_login WHERE correo = 'cliente2" . DOMINIO . "'");
    return [$ok, $ok ? 'Cuenta bloqueada temporalmente' : 'No hubo bloqueo'];
});
prueba('A07', 'Cierre de sesión por GET no cierra; por POST con CSRF sí', 'GET ignorado, POST cierra sesión', function () {
    $n = entrar('cliente1');
    $n->get('/logout.php');
    $sigue = $n->get('/cliente/panel.php')['code'] === 200;
    $t = $n->token('/cliente/panel.php');
    $n->post('/logout.php', ['csrf' => $t]);
    $fuera = ruta($n->get('/cliente/panel.php')['location']) === '/index.php';
    return [$sigue && $fuera, ($sigue ? 'GET no cerró' : 'GET cerró') . ' / ' . ($fuera ? 'POST cerró sesión' : 'POST no cerró')];
});
prueba('A08', 'Cliente intenta abrir el panel administrativo', 'Redirigido a su propio panel', function () {
    $r = entrar('cliente1')->get('/admin/panel.php');
    return [ruta($r['location']) === '/cliente/panel.php', 'Redirección a ' . ruta($r['location'])];
});
prueba('A09', 'Visitante sin sesión abre una página privada', 'Redirigido al login', function () {
    global $BASE;
    $r = (new Navegador($BASE))->get('/admin/usuarios.php');
    return [ruta($r['location']) === '/index.php', 'Redirección a ' . ruta($r['location'])];
});
foreach ([
    ['A10', 'Registro con correo inválido', ['nombre' => 'QA Prueba', 'correo' => 'abc', 'password' => 'Segura123', 'password2' => 'Segura123', 'acepta_datos' => '1'], 'correo electrónico válido'],
    ['A11', 'Registro sin autorizar tratamiento de datos', ['nombre' => 'QA Prueba', 'correo' => 'sinacepta' . DOMINIO, 'password' => 'Segura123', 'password2' => 'Segura123'], 'tratamiento de datos'],
    ['A12', 'Registro con contraseña débil', ['nombre' => 'QA Prueba', 'correo' => 'debil' . DOMINIO, 'password' => '123', 'password2' => '123', 'acepta_datos' => '1'], 'mínimo 8 caracteres'],
] as [$id, $desc, $datos, $msg]) {
    prueba($id, $desc, 'Rechazado con mensaje', function () use ($datos, $msg) {
        global $BASE;
        $n = new Navegador($BASE);
        $r = $n->postSeguir('/register_process.php', ['csrf' => $n->token('/index.php')] + $datos);
        $creado = (int) val('SELECT COUNT(*) FROM usuarios WHERE correo = ?', [$datos['correo']]);
        return [contiene($r, $msg) && !$creado, contiene($r, $msg) ? 'Rechazado: "' . $msg . '"' : 'No rechazado'];
    });
}
prueba('A13', 'Registro enviando rol=administrador', 'La cuenta se crea como cliente', function () {
    global $BASE;
    $n = new Navegador($BASE);
    $n->post('/register_process.php', ['csrf' => $n->token('/index.php'), 'nombre' => 'QA Intruso', 'correo' => 'intruso' . DOMINIO, 'password' => 'Segura123', 'password2' => 'Segura123', 'acepta_datos' => '1', 'rol' => 'administrador']);
    $rol = val('SELECT rol FROM usuarios WHERE correo = ?', ['intruso' . DOMINIO]);
    return [$rol === 'cliente', 'Rol asignado: ' . var_export($rol, true)];
});
prueba('A14', 'Cabeceras de seguridad HTTP', 'CSP, X-Frame-Options y nosniff', function () {
    global $BASE;
    $h = (new Navegador($BASE))->get('/index.php')['headers'];
    $ok = stripos($h, 'Content-Security-Policy') !== false && stripos($h, 'X-Frame-Options: DENY') !== false && stripos($h, 'nosniff') !== false;
    return [$ok, $ok ? 'Cabeceras presentes' : 'Faltan cabeceras'];
});

// =====================================================================
// 2. Pedidos del cliente
// =====================================================================
$cli1 = entrar('cliente1');
$pedidoA = 0;
prueba('P01', 'Crear pedido válido con archivo PNG real', 'Estado Recibido, costo = precio × cantidad, historial y archivo', function () use ($cli1, $catId, $catPrecio, $manana, $PNG, &$pedidoA) {
    $r = $cli1->post('/cliente/guardar_pedido.php', [
        'csrf' => $cli1->token('/cliente/crear_pedido.php'), 'accion' => 'enviar', 'categoria_id' => $catId,
        'tipo_trabajo' => 'Aviso luminoso QA', 'cantidad' => '12.5', 'fecha_entrega' => $manana, 'notas' => 'Prueba automática',
        'archivos[0]' => archivoPrueba('diseno.png', $PNG),
    ]);
    $pedidoA = (int) (preg_match('/id=(\d+)/', (string) $r['location'], $m) ? $m[1] : 0);
    $p = fila('SELECT * FROM pedidos WHERE id = ?', [$pedidoA]);
    $hist = (int) val('SELECT COUNT(*) FROM historial_pedido WHERE pedido_id = ? AND estado_nuevo = ?', [$pedidoA, 'Recibido']);
    $arch = (int) val('SELECT COUNT(*) FROM archivos_pedido WHERE pedido_id = ?', [$pedidoA]);
    $ok = $p && $p['estado'] === 'Recibido' && abs((float) $p['costo_estimado'] - $catPrecio * 12.5) < 0.01 && $hist === 1 && $arch === 1;
    return [$ok, $p ? "Pedido #$pedidoA {$p['estado']}, costo {$p['costo_estimado']} (esperado " . ($catPrecio * 12.5) . "), historial $hist, archivos $arch" : 'No se creó'];
});
foreach ([
    ['P02', 'Pedido con fecha de entrega pasada', ['fecha_entrega' => '2020-01-01'], 'anterior a hoy'],
    ['P03', 'Pedido con fecha inválida ("hola")', ['fecha_entrega' => 'hola'], 'fecha de entrega válida'],
    ['P04', 'Pedido con cantidad 0', ['cantidad' => '0'], 'cantidad debe ser mayor'],
    ['P05', 'Pedido con categoría inexistente', ['categoria_id' => '999999'], 'categoría válida'],
    ['P06', 'Pedido con archivo .php', ['archivos[0]' => 'php'], 'no es un formato permitido'],
    ['P07', 'Archivo con extensión .png pero contenido de texto', ['archivos[0]' => 'falso'], 'no corresponde'],
] as [$id, $desc, $cambios, $msg]) {
    prueba($id, $desc, 'Rechazado con mensaje, no se crea el pedido', function () use ($cli1, $catId, $manana, $cambios, $msg) {
        $antes = (int) val('SELECT COUNT(*) FROM pedidos');
        $datos = array_merge(['csrf' => $cli1->token('/cliente/crear_pedido.php'), 'accion' => 'enviar', 'categoria_id' => $catId, 'tipo_trabajo' => 'Prueba negativa', 'cantidad' => '2', 'fecha_entrega' => $manana], $cambios);
        if (($cambios['archivos[0]'] ?? '') === 'php') $datos['archivos[0]'] = archivoPrueba('ataque.php', '<?php echo 1;');
        if (($cambios['archivos[0]'] ?? '') === 'falso') $datos['archivos[0]'] = archivoPrueba('falso.png', 'esto no es una imagen');
        $r = $cli1->postSeguir('/cliente/guardar_pedido.php', $datos);
        $despues = (int) val('SELECT COUNT(*) FROM pedidos');
        return [contiene($r, $msg) && $antes === $despues, (contiene($r, $msg) ? 'Mensaje: "' . $msg . '"' : 'Sin mensaje esperado') . ($antes === $despues ? ', sin registro' : ', SE CREÓ UN PEDIDO')];
    });
}
prueba('P08', 'Guardar borrador, retomarlo y enviarlo', 'Borrador -> Recibido', function () use ($cli1, $catId, $manana) {
    $r = $cli1->post('/cliente/guardar_pedido.php', ['csrf' => $cli1->token('/cliente/crear_pedido.php'), 'accion' => 'borrador', 'categoria_id' => $catId, 'tipo_trabajo' => 'Borrador QA', 'cantidad' => '3', 'fecha_entrega' => $manana]);
    $id = (int) (preg_match('/id=(\d+)/', (string) $r['location'], $m) ? $m[1] : 0);
    $e1 = val('SELECT estado FROM pedidos WHERE id = ?', [$id]);
    $cli1->post('/cliente/guardar_pedido.php', ['csrf' => $cli1->token('/cliente/crear_pedido.php?borrador=' . $id), 'accion' => 'enviar', 'borrador_id' => $id, 'categoria_id' => $catId, 'tipo_trabajo' => 'Borrador QA enviado', 'cantidad' => '4', 'fecha_entrega' => $manana]);
    $e2 = val('SELECT estado FROM pedidos WHERE id = ?', [$id]);
    return [$e1 === 'Borrador' && $e2 === 'Recibido', "Estado inicial: $e1, después de enviar: $e2"];
});
prueba('P09', 'Eliminar un borrador propio', 'El borrador desaparece', function () use ($cli1, $catId, $manana) {
    $r = $cli1->post('/cliente/guardar_pedido.php', ['csrf' => $cli1->token('/cliente/crear_pedido.php'), 'accion' => 'borrador', 'categoria_id' => $catId, 'tipo_trabajo' => 'Borrador a eliminar', 'cantidad' => '1', 'fecha_entrega' => $manana]);
    $id = (int) (preg_match('/id=(\d+)/', (string) $r['location'], $m) ? $m[1] : 0);
    $cli1->post('/cliente/guardar_pedido.php', ['csrf' => $cli1->token('/cliente/crear_pedido.php?borrador=' . $id), 'accion' => 'eliminar', 'borrador_id' => $id]);
    $existe = (int) val('SELECT COUNT(*) FROM pedidos WHERE id = ?', [$id]);
    return [$id > 0 && $existe === 0, $existe ? 'Sigue existiendo' : 'Eliminado'];
});
$cli2 = entrar('cliente2');
prueba('P10', 'Otro cliente intenta ver el pedido', 'Acceso denegado', function () use ($cli2, &$pedidoA) {
    $r = $cli2->get('/pedido.php?id=' . $pedidoA);
    return [$r['code'] === 302 && ruta($r['location']) === '/cliente/panel.php', 'HTTP ' . $r['code'] . ' -> ' . ruta($r['location'])];
});
prueba('P11', 'Otro cliente intenta descargar el archivo del pedido', 'Acceso denegado', function () use ($cli2, &$pedidoA) {
    $aid = (int) val('SELECT id FROM archivos_pedido WHERE pedido_id = ?', [$pedidoA]);
    $r = $cli2->get('/archivo.php?id=' . $aid);
    return [$r['code'] !== 200, 'HTTP ' . $r['code']];
});
prueba('P12', 'El dueño descarga su archivo', 'HTTP 200 y contenido idéntico', function () use ($cli1, &$pedidoA, $PNG) {
    $aid = (int) val('SELECT id FROM archivos_pedido WHERE pedido_id = ?', [$pedidoA]);
    $r = $cli1->get('/archivo.php?id=' . $aid);
    return [$r['code'] === 200 && $r['body'] === $PNG, 'HTTP ' . $r['code'] . ($r['body'] === $PNG ? ', contenido correcto' : ', contenido distinto')];
});
prueba('P13', 'Acceso directo a /uploads/ sin pasar por la aplicación', 'Bloqueado (403)', function () use (&$pedidoA) {
    global $BASE;
    $rutaArchivo = val('SELECT ruta FROM archivos_pedido WHERE pedido_id = ?', [$pedidoA]);
    $r = (new Navegador($BASE))->get('/uploads/' . $rutaArchivo);
    return [$r['code'] === 403, 'HTTP ' . $r['code']];
});
prueba('P14', 'El cliente ve el costo estimado en su panel', 'Costo visible', function () use ($cli1, &$pedidoA) {
    $costo = formatoMonedaTest((float) val('SELECT costo_estimado FROM pedidos WHERE id = ?', [$pedidoA]));
    $r = $cli1->get('/cliente/panel.php');
    return [contiene($r, $costo), contiene($r, $costo) ? "Muestra $costo" : 'No se muestra'];
});
function formatoMonedaTest(float $v): string { return '$ ' . number_format($v, 0, ',', '.'); }
prueba('P15', 'Texto con HTML/JavaScript en el nombre del trabajo (XSS)', 'Se muestra escapado', function () use ($cli1, $catId, $manana) {
    $r = $cli1->post('/cliente/guardar_pedido.php', ['csrf' => $cli1->token('/cliente/crear_pedido.php'), 'accion' => 'enviar', 'categoria_id' => $catId, 'tipo_trabajo' => '<script>alert(1)</script>', 'cantidad' => '1', 'fecha_entrega' => $manana]);
    $html = $cli1->get(ruta($r['location']))['body'];
    $ok = !str_contains($html, '<script>alert(1)</script>') && str_contains($html, '&lt;script&gt;');
    return [$ok, $ok ? 'Escapado con htmlspecialchars' : 'Se inyectó el script'];
});

// =====================================================================
// 3. Flujo por áreas
// =====================================================================
$recep = entrar('recepcion');
$dis = entrar('diseno');
$dis2 = entrar('diseno2');
$prod = entrar('produccion');
$cal = entrar('calidad');
$admin = entrar('admin');
$avanzar = function (Navegador $n, int $id, string $nuevo, string $obs = '') {
    return $n->postSeguir('/empleado/actualizar_estado.php', ['csrf' => $n->token('/pedido.php?id=' . $id), 'pedido_id' => $id, 'nuevo_estado' => $nuevo, 'observacion' => $obs]);
};
$estado = fn(int $id) => val('SELECT estado FROM pedidos WHERE id = ?', [$id]);

prueba('F01', 'Recepción recibe alerta del pedido nuevo', 'Alerta "nuevo_pedido"', function () use (&$pedidoA, $ids) {
    $n = (int) val("SELECT COUNT(*) FROM alertas WHERE usuario_id = ? AND pedido_id = ? AND tipo = 'nuevo_pedido'", [$ids['recep'], $pedidoA]);
    return [$n === 1, "$n alerta(s)"];
});
prueba('F02', 'Diseño intenta avanzar un pedido que está en Recepción', 'Rechazado', function () use ($avanzar, $dis, &$pedidoA, $estado) {
    $avanzar($dis, $pedidoA, 'En diseño');
    return [$estado($pedidoA) === 'Recibido', 'Estado: ' . $estado($pedidoA)];
});
prueba('F03', 'Recepción salta de Recibido a Entregado manipulando el formulario', 'Rechazado con mensaje', function () use ($avanzar, $recep, &$pedidoA, $estado) {
    $r = $avanzar($recep, $pedidoA, 'Entregado');
    return [$estado($pedidoA) === 'Recibido' && contiene($r, 'Cambio no permitido'), 'Estado: ' . $estado($pedidoA)];
});
prueba('F04', 'Recepción toma el pedido y lo envía a Diseño', 'En diseño, sin responsable, historial con usuario y estados', function () use ($recep, $avanzar, &$pedidoA, $estado, $ids) {
    $recep->post('/empleado/tomar_pedido.php', ['csrf' => $recep->token('/pedido.php?id=' . $pedidoA), 'pedido_id' => $pedidoA]);
    $tomado = (int) val('SELECT empleado_id FROM pedidos WHERE id = ?', [$pedidoA]) === $ids['recep'];
    $avanzar($recep, $pedidoA, 'En diseño', 'Datos verificados');
    $h = fila('SELECT * FROM historial_pedido WHERE pedido_id = ? AND estado_nuevo = ? ORDER BY id DESC', [$pedidoA, 'En diseño']);
    $resp = val('SELECT empleado_id FROM pedidos WHERE id = ?', [$pedidoA]);
    $ok = $tomado && $estado($pedidoA) === 'En diseño' && $resp === null && $h && (int) $h['usuario_id'] === $ids['recep'] && $h['estado_anterior'] === 'Recibido';
    return [$ok, 'Tomado: ' . ($tomado ? 'sí' : 'no') . ', estado ' . $estado($pedidoA) . ', historial ' . ($h ? "{$h['estado_anterior']} -> {$h['estado_nuevo']} por usuario {$h['usuario_id']}" : 'sin registro')];
});
prueba('F05', 'Admin asigna el pedido a un cliente', 'Rechazado', function () use ($admin, &$pedidoA, $ids) {
    $r = $admin->postSeguir('/admin/asignar_empleado.php', ['csrf' => $admin->token('/admin/pedidos.php'), 'pedido_id' => $pedidoA, 'empleado_id' => $ids['cli2']]);
    $resp = val('SELECT empleado_id FROM pedidos WHERE id = ?', [$pedidoA]);
    return [$resp === null && contiene($r, 'empleado activo'), 'Responsable: ' . var_export($resp, true)];
});
prueba('F06', 'Admin asigna un empleado inexistente', 'Mensaje de error, sin error 500', function () use ($admin, &$pedidoA) {
    $r = $admin->postSeguir('/admin/asignar_empleado.php', ['csrf' => $admin->token('/admin/pedidos.php'), 'pedido_id' => $pedidoA, 'empleado_id' => 999999]);
    return [$r['code'] === 200 && contiene($r, 'empleado activo'), 'HTTP ' . $r['code']];
});
prueba('F07', 'Admin asigna un empleado de otra área (Producción) a un pedido en Diseño', 'Rechazado', function () use ($admin, &$pedidoA, $ids) {
    $r = $admin->postSeguir('/admin/asignar_empleado.php', ['csrf' => $admin->token('/admin/pedidos.php'), 'pedido_id' => $pedidoA, 'empleado_id' => $ids['prod']]);
    return [val('SELECT empleado_id FROM pedidos WHERE id = ?', [$pedidoA]) === null && contiene($r, 'área de Diseño'), 'Rechazado por área'];
});
prueba('F08', 'Admin asigna a Diseño 1; Diseño 2 intenta avanzarlo', 'Asignado con alerta; Diseño 2 rechazado', function () use ($admin, $avanzar, $dis2, &$pedidoA, $ids, $estado) {
    $admin->post('/admin/asignar_empleado.php', ['csrf' => $admin->token('/admin/pedidos.php'), 'pedido_id' => $pedidoA, 'empleado_id' => $ids['dis']]);
    $asig = (int) val('SELECT empleado_id FROM pedidos WHERE id = ?', [$pedidoA]) === $ids['dis'];
    $alerta = (int) val("SELECT COUNT(*) FROM alertas WHERE usuario_id = ? AND pedido_id = ? AND tipo = 'asignado'", [$ids['dis'], $pedidoA]);
    $avanzar($dis2, $pedidoA, 'En producción');
    return [$asig && $alerta === 1 && $estado($pedidoA) === 'En diseño', 'Asignado: ' . ($asig ? 'sí' : 'no') . ", alerta $alerta, estado " . $estado($pedidoA)];
});
prueba('F09', 'Recorrido Diseño -> Producción -> Calidad', 'Control de calidad', function () use ($avanzar, $dis, $prod, &$pedidoA, $estado) {
    $avanzar($dis, $pedidoA, 'En producción');
    $avanzar($prod, $pedidoA, 'Control de calidad');
    return [$estado($pedidoA) === 'Control de calidad', 'Estado: ' . $estado($pedidoA)];
});
prueba('F10', 'Calidad devuelve a producción sin motivo / con motivo', 'Sin motivo rechazado; con motivo En producción', function () use ($avanzar, $cal, $prod, &$pedidoA, $estado) {
    $avanzar($cal, $pedidoA, 'En producción', '');
    $sin = $estado($pedidoA);
    $avanzar($cal, $pedidoA, 'En producción', 'Color fuera de tono');
    $con = $estado($pedidoA);
    $avanzar($prod, $pedidoA, 'Control de calidad');
    return [$sin === 'Control de calidad' && $con === 'En producción', "Sin motivo: $sin; con motivo: $con"];
});
prueba('F11', 'Calidad aprueba y Recepción registra la entrega', 'Entregado con fecha real y alerta al cliente', function () use ($avanzar, $cal, $recep, &$pedidoA, $estado, $ids) {
    $avanzar($cal, $pedidoA, 'Listo para entrega');
    $avanzar($recep, $pedidoA, 'Entregado');
    $p = fila('SELECT estado, fecha_entregado FROM pedidos WHERE id = ?', [$pedidoA]);
    $alerta = (int) val("SELECT COUNT(*) FROM alertas WHERE usuario_id = ? AND pedido_id = ? AND tipo = 'finalizado'", [$ids['cli1'], $pedidoA]);
    return [$p['estado'] === 'Entregado' && $p['fecha_entregado'] && $alerta === 1, "Estado {$p['estado']}, entregado " . ($p['fecha_entregado'] ?? 'NULL') . ", alerta cliente $alerta"];
});
prueba('F12', 'Retroceder un pedido entregado (incluso como admin)', 'Rechazado', function () use ($avanzar, $admin, &$pedidoA, $estado) {
    $avanzar($admin, $pedidoA, 'Recibido');
    return [$estado($pedidoA) === 'Entregado', 'Estado: ' . $estado($pedidoA)];
});
prueba('F13', 'Historial completo y ordenado del pedido', '≥ 8 cambios de estado registrados', function () use (&$pedidoA) {
    $n = (int) val('SELECT COUNT(*) FROM historial_pedido WHERE pedido_id = ? AND estado_nuevo IS NOT NULL', [$pedidoA]);
    return [$n >= 8, "$n cambios de estado"];
});
prueba('F14', 'Empleado de Calidad abre un pedido nuevo en Recepción', 'Acceso denegado', function () use ($cal, $cli1, $catId, $manana) {
    $r = $cli1->post('/cliente/guardar_pedido.php', ['csrf' => $cli1->token('/cliente/crear_pedido.php'), 'accion' => 'enviar', 'categoria_id' => $catId, 'tipo_trabajo' => 'Pedido para F14', 'cantidad' => '1', 'fecha_entrega' => $manana]);
    $id = (int) (preg_match('/id=(\d+)/', (string) $r['location'], $m) ? $m[1] : 0);
    $v = $cal->get('/pedido.php?id=' . $id);
    return [$v['code'] === 302, 'HTTP ' . $v['code']];
});
prueba('F15', 'Cliente cancela su pedido en Recibido (con motivo)', 'Cancelado', function () use ($cli1, $catId, $manana, $estado) {
    $r = $cli1->post('/cliente/guardar_pedido.php', ['csrf' => $cli1->token('/cliente/crear_pedido.php'), 'accion' => 'enviar', 'categoria_id' => $catId, 'tipo_trabajo' => 'Pedido a cancelar', 'cantidad' => '1', 'fecha_entrega' => $manana]);
    $id = (int) (preg_match('/id=(\d+)/', (string) $r['location'], $m) ? $m[1] : 0);
    $cli1->post('/cliente/cancelar_pedido.php', ['csrf' => $cli1->token('/pedido.php?id=' . $id), 'pedido_id' => $id, 'observacion' => 'Ya no lo necesito']);
    return [$estado($id) === 'Cancelado', 'Estado: ' . $estado($id)];
});

// =====================================================================
// 4. Recepción, administración y dashboard
// =====================================================================
prueba('R01', 'Recepción registra un pedido por llamada para un cliente nuevo', 'Cliente creado y pedido con canal Llamada', function () use ($recep, $catId, $manana) {
    $r = $recep->post('/empleado/registrar_pedido.php', [
        'csrf' => $recep->token('/empleado/registrar_pedido.php'), 'tipo_cliente' => 'nuevo', 'cliente_nombre' => 'QA Cliente Telefónico',
        'cliente_correo' => 'telefonico' . DOMINIO, 'cliente_telefono' => '3109876543', 'cliente_acepta' => '1', 'canal_recepcion' => 'Llamada',
        'accion' => 'enviar', 'categoria_id' => $catId, 'tipo_trabajo' => 'Pendón por llamada', 'cantidad' => '2', 'fecha_entrega' => $manana,
    ]);
    $id = (int) (preg_match('/id=(\d+)/', (string) $r['location'], $m) ? $m[1] : 0);
    $p = fila('SELECT p.canal_recepcion, u.correo FROM pedidos p JOIN usuarios u ON u.id = p.cliente_id WHERE p.id = ?', [$id]);
    return [$p && $p['canal_recepcion'] === 'Llamada' && $p['correo'] === 'telefonico' . DOMINIO, $p ? "Pedido #$id canal {$p['canal_recepcion']} para {$p['correo']}" : 'No creado'];
});
prueba('R02', 'Producción intenta registrar pedidos', 'Acceso denegado', function () use ($prod) {
    $r = $prod->get('/empleado/registrar_pedido.php');
    return [ruta($r['location']) === '/empleado/panel.php', 'Redirección a ' . ruta($r['location'])];
});
prueba('D01', 'Crear empleado sin área / con área', 'Sin área rechazado; con área creado', function () use ($admin) {
    $base = ['accion' => 'guardar', 'id' => 0, 'nombre' => 'QA Nuevo Empleado', 'correo' => 'nuevoemp' . DOMINIO, 'telefono' => '', 'rol' => 'empleado', 'password' => 'Empleado2026', 'password2' => 'Empleado2026'];
    $admin->post('/admin/usuarios.php', ['csrf' => $admin->token('/admin/usuarios.php'), 'area' => 'Inventada'] + $base);
    $sin = (int) val('SELECT COUNT(*) FROM usuarios WHERE correo = ?', ['nuevoemp' . DOMINIO]);
    $admin->post('/admin/usuarios.php', ['csrf' => $admin->token('/admin/usuarios.php'), 'area' => 'Producción'] + $base);
    $con = val('SELECT area FROM usuarios WHERE correo = ?', ['nuevoemp' . DOMINIO]);
    return [$sin === 0 && $con === 'Producción', "Sin área: $sin creados; con área: " . var_export($con, true)];
});
prueba('D02', 'Desactivar un empleado con pedidos asignados', 'Pedidos liberados y el empleado no puede entrar', function () use ($admin, $cli1, $recep, $catId, $manana, $ids) {
    $r = $cli1->post('/cliente/guardar_pedido.php', ['csrf' => $cli1->token('/cliente/crear_pedido.php'), 'accion' => 'enviar', 'categoria_id' => $catId, 'tipo_trabajo' => 'Pedido D02', 'cantidad' => '1', 'fecha_entrega' => $manana]);
    $id = (int) (preg_match('/id=(\d+)/', (string) $r['location'], $m) ? $m[1] : 0);
    $recep->post('/empleado/tomar_pedido.php', ['csrf' => $recep->token('/pedido.php?id=' . $id), 'pedido_id' => $id]);
    $admin->post('/admin/usuarios.php', ['csrf' => $admin->token('/admin/usuarios.php'), 'accion' => 'estado', 'id' => $ids['recep']]);
    $libre = val('SELECT empleado_id FROM pedidos WHERE id = ?', [$id]) === null;
    $bloq = ruta($recep->get('/empleado/panel.php')['location']) === '/index.php';
    $admin->post('/admin/usuarios.php', ['csrf' => $admin->token('/admin/usuarios.php'), 'accion' => 'estado', 'id' => $ids['recep']]);
    return [$libre && $bloq, ($libre ? 'Pedido liberado' : 'Pedido sigue asignado') . ', ' . ($bloq ? 'sesión expulsada' : 'sigue con acceso')];
});
prueba('D03', 'El administrador intenta desactivarse a sí mismo', 'Rechazado', function () use ($admin, $ids) {
    $admin->post('/admin/usuarios.php', ['csrf' => $admin->token('/admin/usuarios.php'), 'accion' => 'estado', 'id' => $ids['admin']]);
    return [(int) val('SELECT activo FROM usuarios WHERE id = ?', [$ids['admin']]) === 1, 'Sigue activo'];
});
prueba('D04', 'Cambiar el precio de una categoría no altera pedidos anteriores', 'Costo de pedidos existentes intacto', function () use ($admin, &$pedidoA, $catId) {
    $antes = val('SELECT costo_estimado FROM pedidos WHERE id = ?', [$pedidoA]);
    $c = fila('SELECT * FROM categorias_precios WHERE id = ?', [$catId]);
    $admin->post('/admin/categorias.php', ['csrf' => $admin->token('/admin/categorias.php'), 'accion' => 'guardar', 'id' => $catId, 'categoria' => $c['categoria'], 'descripcion' => $c['descripcion'], 'unidad' => $c['unidad'], 'precio_por_unidad' => (int) $c['precio_por_unidad'] + 1000]);
    $nuevoPrecio = (float) val('SELECT precio_por_unidad FROM categorias_precios WHERE id = ?', [$catId]);
    $despues = val('SELECT costo_estimado FROM pedidos WHERE id = ?', [$pedidoA]);
    $admin->post('/admin/categorias.php', ['csrf' => $admin->token('/admin/categorias.php'), 'accion' => 'guardar', 'id' => $catId, 'categoria' => $c['categoria'], 'descripcion' => $c['descripcion'], 'unidad' => $c['unidad'], 'precio_por_unidad' => (int) $c['precio_por_unidad']]);
    return [(float) $antes === (float) $despues && $nuevoPrecio === (float) $c['precio_por_unidad'] + 1000, "Precio cambió a $nuevoPrecio; costo del pedido: $antes -> $despues"];
});
prueba('D05', 'Un cliente intenta crear usuarios por POST', 'Acceso denegado, sin usuario creado', function () use ($cli1) {
    $cli1->post('/admin/usuarios.php', ['csrf' => $cli1->token('/cliente/panel.php'), 'accion' => 'guardar', 'id' => 0, 'nombre' => 'QA Hacker', 'correo' => 'hacker' . DOMINIO, 'rol' => 'administrador', 'password' => 'Hacker2026', 'password2' => 'Hacker2026']);
    $n = (int) val('SELECT COUNT(*) FROM usuarios WHERE correo = ?', ['hacker' . DOMINIO]);
    return [$n === 0, "$n usuarios creados"];
});
prueba('D06', 'Pedido vencido aparece como retrasado y genera alerta', 'Alerta "retrasado" y fila marcada', function () use ($admin, $cli1, $catId, $manana, $ids) {
    global $pdo;
    $r = $cli1->post('/cliente/guardar_pedido.php', ['csrf' => $cli1->token('/cliente/crear_pedido.php'), 'accion' => 'enviar', 'categoria_id' => $catId, 'tipo_trabajo' => 'Pedido D06 retrasado', 'cantidad' => '1', 'fecha_entrega' => $manana]);
    $id = (int) (preg_match('/id=(\d+)/', (string) $r['location'], $m) ? $m[1] : 0);
    $pdo->prepare('UPDATE pedidos SET fecha_entrega = ? WHERE id = ?')->execute([date('Y-m-d', strtotime('-3 days')), $id]); // simula el paso del tiempo
    $nuevo = entrar('admin'); // sesión nueva: la revisión de vencimientos corre al cargar el panel
    $html = $nuevo->get('/admin/pedidos.php?retrasados=1')['body'];
    $nuevo->get('/admin/panel.php');
    $alerta = (int) val("SELECT COUNT(*) FROM alertas WHERE usuario_id = ? AND pedido_id = ? AND tipo = 'retrasado'", [$ids['admin'], $id]);
    return [$alerta === 1 && str_contains($html, 'Pedido D06 retrasado'), "Alerta $alerta, listado de retrasados " . (str_contains($html, 'Pedido D06 retrasado') ? 'lo incluye' : 'no lo incluye')];
});
prueba('D07', 'Indicadores del dashboard coinciden con la base de datos', 'Total y finalizados iguales a COUNT(*)', function () use ($admin) {
    $html = $admin->get('/admin/panel.php')['body'];
    $total = (int) val("SELECT COUNT(*) FROM pedidos WHERE estado <> 'Borrador'");
    $fin = (int) val("SELECT COUNT(*) FROM pedidos WHERE estado = 'Entregado'");
    $okT = preg_match('/Total de pedidos<\/small><strong>(\d+)</', $html, $a) && (int) $a[1] === $total;
    $okF = preg_match('/Finalizados<\/small><strong>(\d+)</', $html, $b) && (int) $b[1] === $fin;
    return [$okT && $okF, 'Dashboard: total ' . ($a[1] ?? '?') . " (BD $total), finalizados " . ($b[1] ?? '?') . " (BD $fin)"];
});
// =====================================================================
// 4b. Exportación de pedidos (specs/009-exportar-pedidos, escritas antes del código)
// =====================================================================
/** Separa el CSV exportado en filas (sin BOM ni líneas vacías). */
function filasCsv(string $csv): array
{
    $csv = str_starts_with($csv, "\xEF\xBB\xBF") ? substr($csv, 3) : $csv;
    return array_values(array_filter(preg_split('/\r?\n/', $csv), fn($l) => $l !== ''));
}
prueba('E01', 'Administrador exporta los pedidos a CSV', 'HTTP 200, text/csv, BOM, encabezado y un registro por pedido', function () use ($admin) {
    $r = $admin->get('/admin/exportar_pedidos.php');
    $filas = filasCsv($r['body']);
    $total = (int) val("SELECT COUNT(*) FROM pedidos WHERE estado <> 'Borrador'");
    $okTipo = stripos($r['headers'], 'Content-Type: text/csv') !== false && str_starts_with($r['body'], "\xEF\xBB\xBF");
    $okEnc = ($filas[0] ?? '') === 'Pedido;Registrado;Cliente;Correo;Canal;Categoría;Trabajo;Cantidad;Unidad;"Precio unitario";"Costo estimado";"Entrega pactada";Estado;Responsable;Retrasado;"Entregado el"';
    return [$r['code'] === 200 && $okTipo && $okEnc && count($filas) - 1 === $total,
        'HTTP ' . $r['code'] . ', ' . ($okTipo ? 'CSV con BOM' : 'sin tipo CSV') . ', encabezado ' . ($okEnc ? 'correcto' : 'distinto') . ', ' . (count($filas) - 1) . " filas (BD $total)"];
});
prueba('E02', 'Exportar con el filtro estado = Entregado', 'Solo los pedidos entregados', function () use ($admin) {
    $filas = filasCsv($admin->get('/admin/exportar_pedidos.php?estado=' . rawurlencode('Entregado'))['body']);
    array_shift($filas);
    $bd = (int) val("SELECT COUNT(*) FROM pedidos WHERE estado = 'Entregado'");
    $todosEntregados = $filas && !array_filter($filas, fn($l) => (str_getcsv($l, ';', '"', '')[12] ?? '') !== 'Entregado');
    return [count($filas) === $bd && $todosEntregados, count($filas) . " filas (BD $bd), " . ($todosEntregados ? 'todas Entregado' : 'hay otros estados')];
});
prueba('E03', 'Un cliente intenta exportar pedidos', 'No recibe el archivo', function () use ($cli1) {
    $r = $cli1->get('/admin/exportar_pedidos.php');
    return [$r['code'] === 302 && stripos($r['headers'], 'text/csv') === false, 'HTTP ' . $r['code'] . ' -> ' . ruta($r['location'])];
});
prueba('E04', 'Trabajo que empieza por "=" (inyección de fórmulas en Excel)', 'La celda sale con apóstrofo', function () use ($admin, $cli1, $catId, $manana) {
    $cli1->post('/cliente/guardar_pedido.php', ['csrf' => $cli1->token('/cliente/crear_pedido.php'), 'accion' => 'enviar', 'categoria_id' => $catId, 'tipo_trabajo' => '=HYPERLINK("http://malicioso.test")', 'cantidad' => '1', 'fecha_entrega' => $manana]);
    $csv = $admin->get('/admin/exportar_pedidos.php?q=' . rawurlencode('HYPERLINK'))['body'];
    $ok = str_contains($csv, "'=HYPERLINK") && !preg_match('/;"?=HYPERLINK/', $csv);
    return [$ok, $ok ? 'Neutralizada con apóstrofo' : 'La fórmula quedó ejecutable'];
});

// =====================================================================
// 5. Versión IA (specs 010 a 015)
// =====================================================================
/** Agrega una cuenta en la ranura indicada usando el formulario "Agregar otra cuenta". */
function agregarCuenta(Navegador $n, string $alias, int $ranura): array
{
    $t = $n->token('/index.php?agregar=1');
    return $n->post('/login_process.php?u=' . $ranura, ['csrf' => $t, 'ranura' => $ranura, 'correo' => $alias . DOMINIO, 'password' => CLAVE]);
}
/** Llama al servicio del chatbot y devuelve [código, JSON]. */
function chatbot(Navegador $n, array $datos, bool $conCsrf = true): array
{
    if ($conCsrf) {
        $datos['csrf'] = $n->token('/perfil.php');
    }
    $r = $n->post('/chatbot_api.php', $datos);
    return [$r['code'], json_decode($r['body'], true) ?? []];
}

$multi = entrar('cliente1');
prueba('M01', 'Cliente en la ranura 0 agrega la cuenta del administrador', 'Ambos paneles abiertos en el mismo navegador', function () use ($multi) {
    $r = agregarCuenta($multi, 'admin', 1);
    $cli = $multi->get('/cliente/panel.php')['code'];
    $adm = $multi->get('/admin/panel.php?u=1')['code'];
    return [ruta($r['location']) === '/admin/panel.php?u=1' && $cli === 200 && $adm === 200, 'Login -> ' . ruta($r['location']) . "; cliente HTTP $cli, admin (u=1) HTTP $adm"];
});
prueba('M02', 'Los enlaces y formularios conservan la ranura', 'Contienen u=1', function () use ($multi) {
    $html = $multi->get('/admin/panel.php?u=1')['body'];
    $ok = str_contains($html, '/admin/pedidos.php?u=1') && str_contains($html, '/logout.php?u=1') && str_contains($html, '/chatbot_api.php?u=1');
    return [$ok, $ok ? 'Menú, cierre de sesión y asistente con u=1' : 'Hay enlaces sin la ranura'];
});
prueba('M03', 'Agregar una cuenta que ya está abierta', 'Reutiliza su ranura', function () use ($multi) {
    $r = agregarCuenta($multi, 'admin', 2);
    $libre = ruta($multi->get('/admin/panel.php?u=2')['location']);
    return [ruta($r['location']) === '/admin/panel.php?u=1' && str_starts_with($libre, '/index.php'), 'Login -> ' . ruta($r['location']) . "; ranura 2 -> $libre"];
});
prueba('M06', 'Selector de cuentas', 'Muestra las dos cuentas y "Agregar otra cuenta"', function () use ($multi) {
    $html = html_entity_decode($multi->get('/cliente/panel.php')['body']);
    $ok = str_contains($html, 'QA Cliente1') && str_contains($html, 'QA Admin') && str_contains($html, 'Agregar otra cuenta') && str_contains($html, 'Esta pestaña');
    return [$ok, $ok ? 'Cliente (esta pestaña) y administrador listados' : 'Faltan cuentas en el selector'];
});
prueba('M04', 'Cerrar una cuenta y luego cerrar todas', 'Solo se cierra la ranura indicada; "todas" cierra ambas', function () {
    $n = entrar('cliente1');
    agregarCuenta($n, 'recepcion', 1);
    $n->post('/logout.php?u=1', ['csrf' => $n->token('/empleado/panel.php?u=1')]);
    $cliente = $n->get('/cliente/panel.php')['code'];
    $recep = ruta($n->get('/empleado/panel.php?u=1')['location']);
    agregarCuenta($n, 'recepcion', 1);
    $n->post('/logout.php', ['csrf' => $n->token('/cliente/panel.php'), 'todas' => '1']);
    $a = ruta($n->get('/cliente/panel.php')['location']);
    $b = ruta($n->get('/empleado/panel.php?u=1')['location']);
    $ok = $cliente === 200 && str_starts_with($recep, '/index.php') && str_starts_with($a, '/index.php') && str_starts_with($b, '/index.php');
    return [$ok, "Tras cerrar u=1: cliente HTTP $cliente, recepción -> $recep; tras 'todas': $a y $b"];
});
prueba('M05', 'Abrir una ranura vacía', 'Va al inicio de sesión', function () {
    $r = entrar('cliente1')->get('/cliente/panel.php?u=3');
    return [str_starts_with(ruta($r['location']), '/index.php'), 'Redirección a ' . ruta($r['location'])];
});

prueba('T01', 'Cookie beim_tema=claro', 'La página se genera con data-tema="claro"', function () {
    global $BASE;
    $n = new Navegador($BASE);
    $n->cookies('beim_tema=claro');
    $ok = str_contains($n->get('/index.php')['body'], 'data-tema="claro"');
    return [$ok, $ok ? 'Tema claro aplicado desde el servidor' : 'No se aplicó'];
});
prueba('T02', 'Cookie de tema con un valor malicioso', 'Se ignora (tema oscuro) y no se refleja', function () {
    global $BASE;
    $n = new Navegador($BASE);
    $n->cookies('beim_tema=' . rawurlencode('"><script>alert(1)</script>'));
    $html = $n->get('/index.php')['body'];
    $ok = str_contains($html, 'data-tema="oscuro"') && !str_contains($html, 'alert(1)');
    return [$ok, $ok ? 'Oscuro por defecto, valor descartado' : 'El valor se reflejó'];
});
prueba('T03', 'Cookie beim_acento=rosa', 'data-acento="rosa"', function () {
    global $BASE;
    $n = new Navegador($BASE);
    $n->cookies('beim_acento=rosa');
    $ok = str_contains($n->get('/index.php')['body'], 'data-acento="rosa"');
    return [$ok, $ok ? 'Acento rosa aplicado' : 'No se aplicó'];
});

prueba('L01', 'Página de ingreso con la lámpara', 'Escena apagada, cordón, logo, formulario y respaldo sin JavaScript', function () {
    global $BASE;
    $html = (new Navegador($BASE))->get('/index.php')['body'];
    $faltan = array_filter(['data-lampara="apagada"', 'data-cordon', 'lampara-svg', is_file(__DIR__ . '/../img/logo_ancho.png') ? 'logo-ancho' : 'logo-mark', 'name="correo"', '<noscript>', 'Tira del cordón'], fn($x) => !str_contains($html, $x));
    return [!$faltan, $faltan ? 'Falta: ' . implode(', ', $faltan) : 'Todos los elementos presentes'];
});
prueba('L02', 'Contraseña incorrecta y volver al ingreso', 'La lámpara ya está encendida y se ve el error', function () {
    global $BASE;
    $n = new Navegador($BASE);
    $r = $n->postSeguir('/login_process.php', ['csrf' => $n->token('/index.php'), 'correo' => 'cliente2' . DOMINIO, 'password' => 'Equivocada1']);
    $ok = str_contains($r['body'], 'data-lampara="encendida"') && contiene($r, 'Correo o contraseña incorrectos');
    return [$ok, $ok ? 'Encendida con el mensaje de error' : 'La lámpara arrancó apagada o sin mensaje'];
});
prueba('L03', 'Logo real en img/logo.png', 'El sistema usa esa imagen', function () use ($PNG) {
    global $BASE;
    $logo = __DIR__ . '/../img/logo.png';
    $temporal = !is_file($logo);
    if ($temporal) {
        @mkdir(dirname($logo), 0755, true);
        file_put_contents($logo, $PNG);
    }
    $html = (new Navegador($BASE))->get('/index.php')['body'];
    if ($temporal) {
        unlink($logo);
    }
    // Con logo horizontal (L04) el logo pequeño queda solo como ícono de la pestaña.
    $ok = str_contains($html, '/img/logo.png') && (str_contains($html, 'logo-img') || is_file(__DIR__ . '/../img/logo_ancho.png'));
    return [$ok, ($ok ? 'Logo PNG mostrado' : 'Se siguió usando el logo vectorial') . ($temporal ? ' (logo de prueba temporal)' : ' (logo real del proyecto)')];
});

prueba('L04', 'Logo horizontal en img/logo_ancho.png', 'Se ve en el ingreso y en el menú en lugar del nombre escrito', function () use ($PNG, $cli1) {
    global $BASE;
    $logo = __DIR__ . '/../img/logo_ancho.png';
    $temporal = !is_file($logo);
    if ($temporal) {
        file_put_contents($logo, $PNG);
    }
    $ingreso = (new Navegador($BASE))->get('/index.php')['body'];
    $menu = $cli1->get('/cliente/panel.php')['body'];
    if ($temporal) {
        unlink($logo);
    }
    $ok = str_contains($ingreso, '/img/logo_ancho.png') && str_contains($menu, 'class="logo-ancho');
    return [$ok, ($ok ? 'Logo horizontal visible en ingreso y menú' : 'No apareció el logo horizontal') . ($temporal ? ' (logo de prueba temporal)' : ' (logo real del proyecto)')];
});

prueba('U01', 'Cliente sube un PNG real como foto de perfil', 'Se guarda y aparece en la barra superior', function () use ($cli1, $ids, $PNG) {
    $cli1->post('/perfil.php', ['csrf' => $cli1->token('/perfil.php'), 'accion' => 'foto', 'foto' => archivoPrueba('foto.png', $PNG)]);
    $foto = val('SELECT foto_perfil FROM usuarios WHERE id = ?', [$ids['cli1']]);
    $html = $cli1->get('/cliente/panel.php')['body'];
    $ok = $foto && is_file(__DIR__ . '/../uploads/perfiles/' . $foto) && str_contains($html, 'foto_perfil.php?id=' . $ids['cli1']);
    return [$ok, $foto ? "Guardada como $foto" . ($ok ? ' y visible' : ' pero no se muestra') : 'No se guardó'];
});
prueba('U02', 'Foto con extensión .php o PNG falso', 'Rechazadas', function () use ($cli1, $ids) {
    $antes = val('SELECT foto_perfil FROM usuarios WHERE id = ?', [$ids['cli1']]);
    $a = $cli1->postSeguir('/perfil.php', ['csrf' => $cli1->token('/perfil.php'), 'accion' => 'foto', 'foto' => archivoPrueba('malo.php', '<?php echo 1;')]);
    $b = $cli1->postSeguir('/perfil.php', ['csrf' => $cli1->token('/perfil.php'), 'accion' => 'foto', 'foto' => archivoPrueba('falso.png', 'texto plano')]);
    $ok = contiene($a, 'JPG, PNG o WEBP') && contiene($b, 'no corresponde') && val('SELECT foto_perfil FROM usuarios WHERE id = ?', [$ids['cli1']]) === $antes;
    return [$ok, $ok ? 'Ambas rechazadas; la foto anterior se conserva' : 'Se aceptó un archivo inválido'];
});
prueba('U05', 'Foto de más de 2 MB', 'Rechazada por tamaño', function () use ($cli1, $PNG) {
    $r = $cli1->postSeguir('/perfil.php', ['csrf' => $cli1->token('/perfil.php'), 'accion' => 'foto', 'foto' => archivoPrueba('grande.png', $PNG . str_repeat("\0", 2 * 1024 * 1024 + 10))]);
    return [contiene($r, '2 MB'), contiene($r, '2 MB') ? 'Mensaje: "supera el tamaño máximo de 2 MB"' : 'Se aceptó'];
});
prueba('U03', 'Permisos de la foto de un cliente', 'Otro cliente no la ve; el dueño y el administrador sí', function () use ($cli1, $cli2, $admin, $ids) {
    $ruta = '/foto_perfil.php?id=' . $ids['cli1'];
    $c = [$cli2->get($ruta)['code'], $cli1->get($ruta)['code'], $admin->get($ruta)['code']];
    return [$c === [404, 200, 200], "Otro cliente HTTP {$c[0]}, dueño HTTP {$c[1]}, administrador HTTP {$c[2]}"];
});
prueba('U04', 'Quitar la foto de perfil', 'Se borra el archivo y vuelven las iniciales', function () use ($cli1, $ids) {
    $foto = val('SELECT foto_perfil FROM usuarios WHERE id = ?', [$ids['cli1']]);
    $cli1->post('/perfil.php', ['csrf' => $cli1->token('/perfil.php'), 'accion' => 'quitar_foto']);
    $ok = val('SELECT foto_perfil FROM usuarios WHERE id = ?', [$ids['cli1']]) === null && !is_file(__DIR__ . '/../uploads/perfiles/' . $foto);
    return [$ok, $ok ? 'Foto eliminada' : 'La foto sigue guardada'];
});

$pdo->prepare("INSERT INTO pedidos (cliente_id, categoria_id, categoria, tipo_trabajo, cantidad, fecha_entrega, estado, creado_en) VALUES (?, ?, 'QA', 'Trabajo secreto del cliente 2', 1, ?, 'Recibido', ?)")
    ->execute([$ids['cli2'], $catId, $manana, date('Y-m-d H:i:s')]);
prueba('B01', 'Cliente abre el asistente Beimy', 'Saludo con su nombre y solo opciones de cliente', function () use ($cli1) {
    [$code, $j] = chatbot($cli1, ['accion' => 'menu']);
    $ops = array_column($j['opciones'] ?? [], 'id');
    $ok = $code === 200 && str_contains($j['texto'] ?? '', '¡Hola, QA!') && in_array('mis_pedidos', $ops, true) && !array_intersect(['resumen', 'mi_bandeja'], $ops);
    return [$ok, "HTTP $code · opciones: " . implode(', ', $ops)];
});
prueba('B02', 'Opción "Mis pedidos"', 'Solo pedidos propios', function () use ($cli1, $ids) {
    [, $j] = chatbot($cli1, ['accion' => 'opcion', 'id' => 'mis_pedidos']);
    $propio = 'BT-' . str_pad((string) val("SELECT MAX(id) FROM pedidos WHERE cliente_id = ? AND estado <> 'Borrador'", [$ids['cli1']]), 4, '0', STR_PAD_LEFT);
    $texto = $j['texto'] ?? '';
    $ok = str_contains($texto, $propio) && !str_contains($texto, 'secreto');
    return [$ok, $ok ? "Muestra $propio y ningún pedido ajeno" : 'Respuesta: ' . mb_substr($texto, 0, 80)];
});
prueba('B06', 'Cliente pide una opción de administrador manipulando la petición', 'Rechazada (403)', function () use ($cli1) {
    [$code, $j] = chatbot($cli1, ['accion' => 'opcion', 'id' => 'resumen']);
    return [$code === 403 && ($j['error'] ?? '') === 'opcion_no_permitida', "HTTP $code · " . ($j['error'] ?? 'sin error')];
});
prueba('B05', 'Pregunta libre', 'Sin clave de IA responde con el menú (con clave responde Gemini)', function () use ($cli1) {
    [$code, $j] = chatbot($cli1, ['accion' => 'mensaje', 'mensaje' => '¿Cuánto cuesta un pendón?']);
    $ok = $code === 200 && (($j['ia'] ?? false) || (str_contains($j['texto'] ?? '', 'menú') && !empty($j['opciones'])));
    return [$ok, ($j['ia'] ?? false) ? 'IA configurada: respondió Gemini' : 'Respuesta alterna con el menú'];
});
prueba('B03', 'Visitante sin sesión llama al servicio del asistente', '401 en JSON', function () {
    global $BASE;
    $r = (new Navegador($BASE))->post('/chatbot_api.php', ['accion' => 'menu']);
    return [$r['code'] === 401 && (json_decode($r['body'], true)['error'] ?? '') === 'sin_sesion', 'HTTP ' . $r['code']];
});
prueba('B04', 'Petición al asistente sin token CSRF', '403 en JSON', function () use ($cli1) {
    [$code, $j] = chatbot($cli1, ['accion' => 'menu'], false);
    return [$code === 403 && ($j['error'] ?? '') === 'csrf', "HTTP $code"];
});

prueba('G01', 'Administrador abre el panel gerencial (último año)', 'Ingresos = suma de los entregados en la BD', function () use ($admin) {
    $html = $admin->get('/admin/gerencia.php?dias=365')['body'];
    preg_match('/data-ingresos="([\d.]+)"/', $html, $m);
    $desde = date('Y-m-d 00:00:00', strtotime(date('Y-m-d') . ' -365 days'));
    $bd = (float) val("SELECT COALESCE(SUM(costo_estimado), 0) FROM pedidos WHERE estado = 'Entregado' AND fecha_entregado >= ?", [$desde]);
    $ok = isset($m[1]) && abs((float) $m[1] - $bd) < 0.01 && str_contains($html, 'Tiempo promedio por etapa');
    return [$ok, 'Panel ' . ($m[1] ?? '¿?') . " · BD $bd"];
});
prueba('G02', 'Cliente y empleado abren el panel gerencial', 'Acceso negado', function () use ($cli1) {
    $a = ruta($cli1->get('/admin/gerencia.php')['location']);
    $b = ruta(entrar('produccion')->get('/admin/gerencia.php')['location']);
    return [$a === '/cliente/panel.php' && $b === '/empleado/panel.php', "Cliente -> $a, Producción -> $b"];
});

prueba('V01', 'Formulario de pedido con visualización 3D', 'Sección presente y un modelo por categoría', function () use ($cli1) {
    $html = $cli1->get('/cliente/crear_pedido.php')['body'];
    $esperado = ['Fachadas' => 'fachada', 'Interiores' => 'pared', 'Gran formato' => 'pendon'];
    $mal = [];
    foreach ($esperado as $cat => $modelo) {
        if (str_contains($html, 'data-nombre="' . $cat . '"') && !str_contains($html, 'data-nombre="' . $cat . '" data-modelo="' . $modelo . '"')) {
            $mal[] = $cat;
        }
    }
    $ok = !$mal && str_contains($html, 'data-viz-formulario') && str_contains($html, 'viz-fachada') && str_contains($html, 'Visualización 3D');
    return [$ok, $ok ? 'Fachadas→fachada, Interiores→pared, Gran formato→pendón' : 'Faltan modelos: ' . implode(', ', $mal)];
});
prueba('V04', 'Recursos que carga el formulario con 3D', 'Ningún script ni CSS externo', function () use ($cli1) {
    $html = $cli1->get('/cliente/crear_pedido.php')['body'];
    preg_match_all('/<(?:script[^>]+src|link[^>]+href)="([^"]+)"/', $html, $m);
    $externos = array_filter($m[1], fn($u) => preg_match('#^(https?:)?//#', $u));
    return [!$externos && count($m[1]) > 0, $externos ? 'Externos: ' . implode(', ', $externos) : count($m[1]) . ' recursos, todos propios'];
});
$imgQA = fila("SELECT a.id, p.id AS pedido FROM archivos_pedido a JOIN pedidos p ON p.id = a.pedido_id WHERE p.cliente_id = ? AND a.ruta LIKE '%.png' ORDER BY a.id LIMIT 1", [$ids['cli1']]);
prueba('V02', 'Detalle de un pedido con imagen', 'Visualización 3D con la imagen servida en línea', function () use ($cli1, $imgQA) {
    if (!$imgQA) return [false, 'No hay pedido con imagen'];
    $html = $cli1->get('/pedido.php?id=' . $imgQA['pedido'])['body'];
    $img = $cli1->get('/archivo.php?id=' . $imgQA['id'] . '&ver=1');
    $ok = str_contains($html, 'archivo.php?id=' . $imgQA['id'] . '&amp;ver=1') && $img['code'] === 200 && stripos($img['headers'], 'Content-Type: image/png') !== false;
    return [$ok, 'Detalle ' . (str_contains($html, 'data-viz') ? 'con 3D' : 'sin 3D') . ', imagen HTTP ' . $img['code']];
});
prueba('V03', 'Otro cliente pide la imagen en línea', 'No la obtiene', function () use ($cli2, $imgQA) {
    if (!$imgQA) return [false, 'No hay pedido con imagen'];
    $r = $cli2->get('/archivo.php?id=' . $imgQA['id'] . '&ver=1');
    return [$r['code'] !== 200 && stripos($r['headers'], 'image/png') === false, 'HTTP ' . $r['code']];
});

prueba('N01', 'Notificaciones externas sin credenciales configuradas', 'Se registran como "omitido", nunca como "enviado"', function () {
    $omitidos = (int) val("SELECT COUNT(*) FROM notificaciones_envios n JOIN pedidos p ON p.id = n.pedido_id JOIN usuarios u ON u.id = p.cliente_id WHERE u.correo LIKE '%" . DOMINIO . "' AND n.estado = 'omitido'");
    $enviados = (int) val("SELECT COUNT(*) FROM notificaciones_envios n JOIN pedidos p ON p.id = n.pedido_id JOIN usuarios u ON u.id = p.cliente_id WHERE u.correo LIKE '%" . DOMINIO . "' AND n.estado = 'enviado'");
    return [$omitidos > 0 && $enviados === 0, "$omitidos omitidos, $enviados enviados"];
});
prueba('X01', 'Abrir un pedido inexistente', 'Mensaje de error, sin error 500', function () use ($admin) {
    $r = $admin->get('/pedido.php?id=99999999');
    return [$r['code'] === 302, 'HTTP ' . $r['code'] . ' -> ' . ruta($r['location'])];
});
prueba('X02', 'Redirección abierta con volver=//sitio-externo', 'La redirección queda dentro del sistema', function () use ($admin, &$pedidoA) {
    $r = $admin->post('/empleado/actualizar_estado.php', ['csrf' => $admin->token('/admin/pedidos.php'), 'pedido_id' => $pedidoA, 'nuevo_estado' => 'x', 'volver' => '//sitio-externo.com/robo']);
    $host = parse_url((string) $r['location'], PHP_URL_HOST);
    return [$host !== 'sitio-externo.com', 'Destino: ' . ruta($r['location'])];
});
prueba('X03', 'Archivos internos (.env, schema.sql, includes/, specs/, opencode.json) desde el navegador', 'Bloqueados (403)', function () {
    global $BASE;
    $n = new Navegador($BASE);
    $c = [$n->get('/database/schema.sql')['code'], $n->get('/includes/pedidos.php')['code'], $n->get('/.env.example')['code'],
          $n->get('/specs/003-flujo-produccion/contracts/flujo_estados.json')['code'], $n->get('/opencode.json')['code']];
    return [$c === [403, 403, 403, 403, 403], 'HTTP ' . implode(', ', $c)];
});

// ---------------------------------------------------------------------
// Resumen y limpieza
// ---------------------------------------------------------------------
limpiarQA();
@array_map('unlink', glob(__DIR__ . '/tmp/*') ?: []);
@rmdir(__DIR__ . '/tmp');

$aprobadas = count(array_filter($resultados, fn($r) => $r['ok']));
$total = count($resultados);
echo str_repeat('=', 70) . "\nResultado: $aprobadas de $total pruebas aprobadas.\n";

$md = "# Resultado de pruebas automáticas · BEIM Track\n\n";
$md .= "- Fecha de ejecución: " . date('Y-m-d H:i:s') . "\n- Servidor probado: $BASE\n- Motor de base de datos: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n- Resultado: **$aprobadas de $total aprobadas**\n\n";
$md .= "| ID | Prueba | Resultado esperado | Resultado obtenido | Estado |\n|---|---|---|---|---|\n";
foreach ($resultados as $r) {
    $md .= sprintf("| %s | %s | %s | %s | %s |\n", $r['id'], $r['desc'], $r['esperado'], str_replace('|', '/', $r['obtenido']), $r['ok'] ? '✅ Aprobada' : '❌ Fallida');
}
file_put_contents(__DIR__ . '/resultados_ultima_ejecucion.md', $md);
exit($aprobadas === $total ? 0 : 1);
