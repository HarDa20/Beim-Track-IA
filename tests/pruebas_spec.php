<?php
// =====================================================================
// BEIM Track · Pruebas de especificación (reglas del negocio y contratos)
//
// Uso:  php tests/pruebas_spec.php
//
// No necesitan servidor web ni MySQL: cargan las funciones del sistema y
// las comparan con lo que dicen las especificaciones de specs/ y con el
// contrato specs/003-flujo-produccion/contracts/flujo_estados.json.
// Usan una base SQLite en memoria creada con tests/schema_sqlite.sql.
// El resultado se guarda en tests/resultados_spec.md
// =====================================================================

if (PHP_SAPI !== 'cli') {
    exit('Ejecuta este archivo desde la terminal.');
}
$RAIZ = dirname(__DIR__);
date_default_timezone_set('America/Bogota');

require $RAIZ . '/config/env.php';
require $RAIZ . '/includes/helpers.php';
require $RAIZ . '/includes/auth.php';
require $RAIZ . '/includes/usuarios.php';
require $RAIZ . '/includes/pedidos.php';
require $RAIZ . '/includes/archivos.php';
require $RAIZ . '/includes/alertas.php';
require $RAIZ . '/includes/notificaciones/notificador.php';
require $RAIZ . '/includes/exportacion.php';
require $RAIZ . '/includes/chatbot_menu.php';
require $RAIZ . '/includes/chatbot.php';
require $RAIZ . '/includes/gerencia.php';
require $RAIZ . '/includes/visualizacion.php';

$pdo = new PDO('sqlite::memory:', null, null, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$pdo->exec(file_get_contents(__DIR__ . '/schema_sqlite.sql'));

$CONTRATO = json_decode(file_get_contents($RAIZ . '/specs/003-flujo-produccion/contracts/flujo_estados.json'), true, 512, JSON_THROW_ON_ERROR);

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

/** Compara dos listas sin importar el orden y describe la diferencia. */
function diferencia(array $esperado, array $obtenido): string
{
    $falta = array_diff($esperado, $obtenido);
    $sobra = array_diff($obtenido, $esperado);
    return ($falta ? 'faltan [' . implode(', ', $falta) . '] ' : '') . ($sobra ? 'sobran [' . implode(', ', $sobra) . ']' : '');
}

echo "BEIM Track · Pruebas de especificación\n" . str_repeat('=', 70) . "\n";

// ---------------------------------------------------------------------
// 003 · Flujo de producción contra el contrato
// ---------------------------------------------------------------------
prueba('S01', 'CA-FLU-01', 'Transiciones y áreas del código = contrato flujo_estados.json', function () use ($CONTRATO) {
    $codigo = [];
    foreach (transiciones() as $desde => $hacias) {
        foreach (array_keys($hacias) as $hacia) {
            $codigo[] = "$desde → $hacia";
        }
    }
    // El código declara la cancelación del administrador aparte (en accionesDisponibles);
    // del contrato se comparan aquí las transiciones que no son exclusivas del administrador.
    $contrato = [];
    foreach ($CONTRATO['transiciones'] as $t) {
        if ($t['roles'] !== ['administrador']) {
            $contrato[] = "{$t['desde']} → {$t['hacia']}";
        }
    }
    $areas = [];
    foreach ($CONTRATO['estados'] as $e) {
        if (areaDeEstado($e) !== ($CONTRATO['area_por_estado'][$e] ?? null)) {
            $areas[] = $e;
        }
    }
    $dif = diferencia($contrato, $codigo);
    return [$dif === '' && !$areas, ($dif ?: count($codigo) . ' transiciones iguales') . ($areas ? ' · áreas distintas en: ' . implode(', ', $areas) : ' · áreas iguales')];
});

prueba('S02', 'CA-FLU-05', 'Acciones disponibles por estado y usuario = roles del contrato', function () use ($CONTRATO) {
    $actores = [
        'administrador'        => ['id' => 1, 'rol' => 'administrador', 'area' => null],
        'cliente:propietario'  => ['id' => 2, 'rol' => 'cliente', 'area' => null],
        'cliente:ajeno'        => ['id' => 3, 'rol' => 'cliente', 'area' => null],
        'empleado:Recepción'   => ['id' => 10, 'rol' => 'empleado', 'area' => 'Recepción'],
        'empleado:Diseño'      => ['id' => 11, 'rol' => 'empleado', 'area' => 'Diseño'],
        'empleado:Producción'  => ['id' => 12, 'rol' => 'empleado', 'area' => 'Producción'],
        'empleado:Calidad'     => ['id' => 13, 'rol' => 'empleado', 'area' => 'Calidad'],
    ];
    $errores = [];
    $casos = 0;
    foreach ($CONTRATO['estados'] as $estado) {
        // Escenario 1: pedido sin responsable. Escenario 2: con otro empleado responsable (id 99).
        foreach ([null, 99] as $responsable) {
            $pedido = ['estado' => $estado, 'cliente_id' => 2, 'empleado_id' => $responsable];
            foreach ($actores as $clave => $u) {
                $esperado = [];
                foreach ($CONTRATO['transiciones'] as $t) {
                    if ($t['desde'] !== $estado || !in_array($clave, $t['roles'], true)) {
                        continue;
                    }
                    $bloqueado = $u['rol'] === 'empleado' && $responsable !== null && $CONTRATO['reglas']['empleado_bloqueado_si_otro_responsable'];
                    if (!$bloqueado) {
                        $esperado[] = $t['hacia'];
                    }
                }
                $obtenido = array_keys(accionesDisponibles($u, $pedido));
                $casos++;
                if (($d = diferencia($esperado, $obtenido)) !== '') {
                    $errores[] = "$estado / $clave" . ($responsable ? ' (con otro responsable)' : '') . ": $d";
                }
            }
        }
    }
    return [!$errores, $errores ? implode(' | ', array_slice($errores, 0, 3)) : "$casos combinaciones estado × usuario coinciden"];
});

prueba('S03', 'CA-FLU-01', 'Transiciones que exigen motivo = requiere_motivo del contrato', function () use ($CONTRATO) {
    $mal = [];
    foreach ($CONTRATO['transiciones'] as $t) {
        if (transicionRequiereObservacion($t['desde'], $t['hacia']) !== $t['requiere_motivo']) {
            $mal[] = "{$t['desde']} → {$t['hacia']}";
        }
    }
    return [!$mal, $mal ? 'No coinciden: ' . implode(', ', $mal) : count($CONTRATO['transiciones']) . ' transiciones revisadas'];
});

prueba('S08', 'CA-FLU-01', 'Estados y estados activos del código = contrato', function () use ($CONTRATO) {
    $ok = ESTADOS_PEDIDO === $CONTRATO['estados'] && ESTADOS_ACTIVOS === $CONTRATO['estados_activos'];
    return [$ok, $ok ? count(ESTADOS_PEDIDO) . ' estados en el mismo orden' : diferencia($CONTRATO['estados'], ESTADOS_PEDIDO) . ' ' . diferencia($CONTRATO['estados_activos'], ESTADOS_ACTIVOS)];
});

// ---------------------------------------------------------------------
// 001 · Contraseñas
// ---------------------------------------------------------------------
prueba('S04', 'CA-AUT-03', 'Reglas de contraseña (8–72, letras y números, confirmación)', function () {
    $casos = [
        ['Clave2026', 'Clave2026', true],
        ['corta1', 'corta1', false],
        ['soloLetrasAqui', 'soloLetrasAqui', false],
        ['12345678', '12345678', false],
        ['Clave2026', 'Clave2027', false],
        [str_repeat('a', 72) . '1', str_repeat('a', 72) . '1', false],
        ['Ñandú2026', 'Ñandú2026', true],
    ];
    $mal = [];
    foreach ($casos as [$p, $c, $valida]) {
        if ((validarPassword($p, $c) === []) !== $valida) {
            $mal[] = $p;
        }
    }
    return [!$mal, $mal ? 'Fallan: ' . implode(', ', $mal) : count($casos) . ' casos correctos'];
});

// ---------------------------------------------------------------------
// 002 · Validación y costo del pedido
// ---------------------------------------------------------------------
$catId = (int) $pdo->query('SELECT id FROM categorias_precios ORDER BY id LIMIT 1')->fetchColumn();
$base = ['categoria_id' => $catId, 'tipo_trabajo' => 'Aviso fachada', 'cantidad' => '2', 'fecha_entrega' => date('Y-m-d', strtotime('+5 days')), 'notas' => ''];

prueba('S05', 'CA-PED-06', 'Valores límite de RF-PED-01', function () use ($pdo, $base) {
    $casos = [
        ['cantidad 100.000 (límite)', ['cantidad' => '100000'], true],
        ['cantidad 100.000,01', ['cantidad' => '100000.01'], false],
        ['cantidad con coma decimal 2,5', ['cantidad' => '2,5'], true],
        ['cantidad negativa', ['cantidad' => '-1'], false],
        ['fecha de hoy', ['fecha_entrega' => date('Y-m-d')], true],
        ['fecha de ayer', ['fecha_entrega' => date('Y-m-d', strtotime('-1 day'))], false],
        ['fecha a un año y un día', ['fecha_entrega' => date('Y-m-d', strtotime('+1 year +1 day'))], false],
        ['fecha 2026-02-30', ['fecha_entrega' => '2026-02-30'], false],
        ['nombre de 2 caracteres', ['tipo_trabajo' => 'ab'], false],
        ['nombre de 200 caracteres', ['tipo_trabajo' => str_repeat('x', 200)], true],
        ['nombre de 201 caracteres', ['tipo_trabajo' => str_repeat('x', 201)], false],
        ['descripción de 2.001 caracteres', ['notas' => str_repeat('x', 2001)], false],
        ['categoría inactiva o inexistente', ['categoria_id' => 999], false],
    ];
    $mal = [];
    foreach ($casos as [$nombre, $cambio, $valido]) {
        [$errores] = validarDatosPedido($pdo, array_merge($base, $cambio));
        if (($errores === []) !== $valido) {
            $mal[] = $nombre;
        }
    }
    return [!$mal, $mal ? 'Fallan: ' . implode(', ', $mal) : count($casos) . ' límites correctos'];
});

prueba('S06', 'CA-PED-07', 'Costo estimado = precio unitario × cantidad', function () use ($pdo, $base, $catId) {
    $precio = (float) $pdo->query("SELECT precio_por_unidad FROM categorias_precios WHERE id = $catId")->fetchColumn();
    [, $d] = validarDatosPedido($pdo, array_merge($base, ['cantidad' => '3.75']));
    $esperado = round($precio * 3.75, 2);
    return [abs($d['costo_estimado'] - $esperado) < 0.001 && $d['precio_unitario'] === $precio, "Costo {$d['costo_estimado']} (esperado $esperado), precio copiado {$d['precio_unitario']}"];
});

// ---------------------------------------------------------------------
// 007 · Firmas de archivos
// ---------------------------------------------------------------------
prueba('S07', 'CA-ARC-03', 'Solo se aceptan archivos cuya firma real coincide con la extensión', function () {
    $dir = sys_get_temp_dir() . '/beim_spec_' . bin2hex(random_bytes(4));
    mkdir($dir);
    $casos = [
        ['png', "\x89PNG\r\n\x1A\n" . str_repeat("\0", 8), true],
        ['pdf', '%PDF-1.7 contenido', true],
        ['ai', '%!PS-Adobe-3.0', true],
        ['jpg', "\xFF\xD8\xFF\xE0" . str_repeat("\0", 8), true],
        ['webp', 'RIFF' . "\0\0\0\0" . 'WEBPVP8 ', true],
        ['png', 'esto es texto plano', false],
        ['pdf', '<?php echo "hola";', false],
        ['php', '<?php echo "hola";', false],
    ];
    $mal = [];
    foreach ($casos as $i => [$ext, $contenido, $valido]) {
        file_put_contents("$dir/$i", $contenido);
        if (firmaValida("$dir/$i", $ext) !== $valido) {
            $mal[] = "$ext#$i";
        }
        unlink("$dir/$i");
    }
    rmdir($dir);
    return [!$mal, $mal ? 'Fallan: ' . implode(', ', $mal) : count($casos) . ' firmas correctas'];
});

// ---------------------------------------------------------------------
// 009 · Exportación (pruebas escritas antes del código, T-EXP-01)
// ---------------------------------------------------------------------
prueba('S09', 'CA-EXP-04', 'celdaCsv() neutraliza fórmulas y conserva el resto', function () {
    $casos = [
        ['=HYPERLINK("http://x")', '\'=HYPERLINK("http://x")'],
        ['+57 300', '\'+57 300'],
        ['-5', '\'-5'],
        ['@SUM(A1)', '\'@SUM(A1)'],
        ["\tcmd", "'\tcmd"],
        ['Pendón 2x1', 'Pendón 2x1'],
        ['', ''],
        [375000.5, '375000.5'],
        [null, ''],
    ];
    $mal = [];
    foreach ($casos as [$entrada, $esperado]) {
        if (celdaCsv($entrada) !== $esperado) {
            $mal[] = var_export($entrada, true) . ' → ' . var_export(celdaCsv($entrada), true);
        }
    }
    return [!$mal, $mal ? implode(' | ', $mal) : count($casos) . ' celdas correctas'];
});

prueba('S10', 'CA-EXP-06', 'La exportación respeta el límite de 5.000 filas y excluye borradores', function () use ($pdo, $catId) {
    $pdo->exec("INSERT INTO usuarios (nombre, correo, password_hash, rol) VALUES ('Cliente Spec', 'spec@qa.beimtrack.test', 'x', 'cliente')");
    $cli = (int) $pdo->lastInsertId();
    $pdo->beginTransaction();
    $ins = $pdo->prepare("INSERT INTO pedidos (cliente_id, categoria_id, categoria, tipo_trabajo, cantidad, fecha_entrega, estado) VALUES (?, ?, 'Fachadas', 'Carga', 1, '2030-01-01', ?)");
    for ($i = 0; $i < LIMITE_EXPORTACION + 5; $i++) {
        $ins->execute([$cli, $catId, $i < 3 ? 'Borrador' : 'Recibido']);
    }
    $pdo->commit();
    $filas = consultarPedidosExportacion($pdo, []);
    $borradores = count(array_filter($filas, fn($p) => $p['estado'] === 'Borrador'));
    return [LIMITE_EXPORTACION === 5000 && count($filas) === 5000 && $borradores === 0, count($filas) . " filas exportadas (límite " . LIMITE_EXPORTACION . "), $borradores borradores"];
});


// ---------------------------------------------------------------------
// Versión IA · specs 011, 014 y 015
// ---------------------------------------------------------------------
prueba('S11', 'CA-TEM-02', 'Tema y acento solo aceptan valores de la lista blanca', function () {
    $casos = [
        [temaActual('claro'), 'claro'], [temaActual('oscuro'), 'oscuro'], [temaActual('<script>'), 'oscuro'], [temaActual(''), 'oscuro'],
        [acentoActual('rosa'), 'rosa'], [acentoActual('verde'), 'verde'], [acentoActual('rojo'), 'cian'], [acentoActual('"><x>'), 'cian'],
    ];
    $mal = array_filter($casos, fn($c) => $c[0] !== $c[1]);
    return [!$mal, $mal ? count($mal) . ' valores mal filtrados' : count($casos) . ' casos correctos'];
});

/** Usuario y pedido de prueba en la base en memoria. */
function usuarioSpec(PDO $pdo, string $nombre, string $rol, ?string $area = null, ?string $tel = null): array
{
    $correo = strtolower(str_replace(' ', '.', $nombre)) . '@spec.test';
    $pdo->prepare("INSERT INTO usuarios (nombre, correo, telefono, password_hash, rol, area) VALUES (?, ?, ?, 'x', ?, ?)")->execute([$nombre, $correo, $tel, $rol, $area]);
    return ['id' => (int) $pdo->lastInsertId(), 'nombre' => $nombre, 'correo' => $correo, 'telefono' => $tel, 'rol' => $rol, 'area' => $area];
}
function pedidoSpec(PDO $pdo, int $cliente, string $trabajo, string $estado, float $costo, string $creado, ?string $entregado = null, string $pactada = '2030-01-01', string $canal = 'Web'): int
{
    global $catId;
    $pdo->prepare("INSERT INTO pedidos (cliente_id, categoria_id, categoria, tipo_trabajo, cantidad, costo_estimado, fecha_entrega, estado, canal_recepcion, creado_en, fecha_entregado) VALUES (?, ?, 'Fachadas', ?, 1, ?, ?, ?, ?, ?, ?)")
        ->execute([$cliente, $catId, $trabajo, $costo, $pactada, $estado, $canal, $creado, $entregado]);
    return (int) $pdo->lastInsertId();
}

prueba('S12', 'CA-BOT-02', 'Menú del asistente por rol = configuración y opciones siguientes válidas', function () {
    $config = chatbotMenuConfig();
    $errores = [];
    foreach (['cliente', 'empleado', 'administrador'] as $rol) {
        $u = ['id' => 1, 'nombre' => 'Ana Prueba', 'rol' => $rol, 'area' => $rol === 'empleado' ? 'Diseño' : null];
        $esperado = array_values(array_filter($config['principal'], fn($id) => in_array($rol, $config['opciones'][$id]['roles'], true)));
        $obtenido = array_column(chatbotMenuPrincipal($u)['opciones'], 'id');
        if ($esperado !== $obtenido) {
            $errores[] = "$rol: " . diferencia($esperado, $obtenido);
        }
    }
    foreach ($config['opciones'] as $id => $op) {
        foreach (['etiqueta', 'roles', 'tipo', 'siguientes'] as $campo) {
            if (!isset($op[$campo])) $errores[] = "$id sin '$campo'";
        }
        if ($op['tipo'] === 'dinamico' && !function_exists($op['funcion'] ?? '')) $errores[] = "$id: función inexistente";
        foreach ($op['siguientes'] as $sig) {
            if (!isset($config['opciones'][$sig])) $errores[] = "$id -> $sig no existe";
        }
    }
    return [!$errores, $errores ? implode(' | ', $errores) : count($config['opciones']) . ' opciones válidas para 3 roles'];
});

prueba('S13', 'CA-BOT-07', 'Límites de las preguntas a la IA (500 caracteres, 20 cada 10 min)', function () {
    $ahora = 1_000_000;
    $casos = [
        [chatbotValidarMensaje('', [], $ahora) !== null, 'vacío rechazado'],
        [chatbotValidarMensaje(str_repeat('a', 500), [], $ahora) === null, '500 aceptado'],
        [chatbotValidarMensaje(str_repeat('a', 501), [], $ahora) !== null, '501 rechazado'],
        [chatbotValidarMensaje('hola', array_fill(0, 19, $ahora - 10), $ahora) === null, '19 recientes aceptado'],
        [chatbotValidarMensaje('hola', array_fill(0, 20, $ahora - 10), $ahora) !== null, '20 recientes rechazado'],
        [chatbotValidarMensaje('hola', array_fill(0, 20, $ahora - 601), $ahora) === null, '20 antiguos aceptado'],
    ];
    $mal = array_column(array_filter($casos, fn($c) => !$c[0]), 1);
    return [!$mal, $mal ? 'Fallan: ' . implode(', ', $mal) : count($casos) . ' límites correctos'];
});

prueba('S14', 'CA-BOT-08', 'El contexto para la IA no lleva correos, teléfonos ni pedidos ajenos', function () use ($pdo) {
    $a = usuarioSpec($pdo, 'Laura Contexto', 'cliente', null, '3109998877');
    $b = usuarioSpec($pdo, 'Pedro Ajeno', 'cliente', null, '3201112233');
    pedidoSpec($pdo, $a['id'], 'Pendón de Laura', 'En diseño', 50000, date('Y-m-d H:i:s'));
    pedidoSpec($pdo, $b['id'], 'Valla secreta de Pedro', 'Recibido', 90000, date('Y-m-d H:i:s'));
    $ctx = chatbotInstrucciones(chatbotContextoIA($pdo, $a));
    $fugas = array_filter(['@', '3109998877', '3201112233', 'Pedro', 'Valla secreta', 'Contexto'], fn($x) => str_contains($ctx, $x));
    $ok = !$fugas && str_contains($ctx, 'Pendón de Laura') && str_contains($ctx, 'Laura');
    return [$ok, $fugas ? 'Se filtró: ' . implode(', ', $fugas) : 'Solo primer nombre, sus pedidos y precios'];
});

prueba('S15', 'CA-GER-03', 'Indicadores gerenciales y cuello de botella con tiempos conocidos', function () use ($pdo) {
    $pdo->exec('DELETE FROM historial_pedido');
    $pdo->exec('DELETE FROM pedidos');
    $c = usuarioSpec($pdo, 'Cliente Gerencia', 'cliente');
    $base = strtotime('-20 days 08:00');
    $f = fn(int $horas) => date('Y-m-d H:i:s', $base + $horas * 3600);
    // Pedido 1 (a tiempo): Recibido 2 h, Diseño 10 h, Producción 30 h, Calidad 4 h, Listo 2 h
    $p1 = pedidoSpec($pdo, $c['id'], 'Pedido 1', 'Entregado', 100000, $f(0), $f(48), date('Y-m-d', $base + 5 * 86400));
    // Pedido 2 (tarde): Recibido 4 h, Diseño 14 h, Producción 50 h, Calidad 6 h, Listo 6 h
    $p2 = pedidoSpec($pdo, $c['id'], 'Pedido 2', 'Entregado', 300000, $f(0), $f(80), date('Y-m-d', $base));
    pedidoSpec($pdo, $c['id'], 'Pedido 3', 'Cancelado', 10000, $f(1));
    pedidoSpec($pdo, $c['id'], 'Pedido 4', 'En producción', 70000, $f(2));
    $h = $pdo->prepare('INSERT INTO historial_pedido (pedido_id, estado_anterior, estado_nuevo, descripcion, fecha) VALUES (?, ?, ?, ?, ?)');
    foreach ([[$p1, [0, 2, 12, 42, 46, 48]], [$p2, [0, 4, 18, 68, 74, 80]]] as [$id, $t]) {
        $estados = ['Recibido', 'En diseño', 'En producción', 'Control de calidad', 'Listo para entrega', 'Entregado'];
        foreach ($estados as $i => $e) {
            $h->execute([$id, $i ? $estados[$i - 1] : null, $e, 'spec', $f($t[$i])]);
        }
    }
    $g = indicadoresGerenciales($pdo, 30);
    $horas = array_map(fn($e) => $e['horas'], $g['etapas']);
    $esperado = ['Recibido' => 3.0, 'En diseño' => 12.0, 'En producción' => 40.0, 'Control de calidad' => 5.0, 'Listo para entrega' => 4.0];
    $ok = $horas == $esperado && $g['cuello'] === 'En producción' && $g['ingresos'] == 400000.0 && $g['ticket_promedio'] == 200000.0
        && $g['a_tiempo_pct'] == 50 && $g['ciclo_dias'] == 2.7 && $g['cancelacion_pct'] == 25 && $g['valor_proceso'] == 70000.0;
    return [$ok, 'Horas ' . json_encode($horas, JSON_UNESCAPED_UNICODE) . " · cuello {$g['cuello']} · ingresos {$g['ingresos']} · a tiempo {$g['a_tiempo_pct']}% · ciclo {$g['ciclo_dias']} d · cancelación {$g['cancelacion_pct']}%"];
});

prueba('S16', 'CA-GER-04', 'Top de clientes ordenado por valor y con máximo 5', function () use ($pdo) {
    $pdo->exec('DELETE FROM historial_pedido');
    $pdo->exec('DELETE FROM pedidos');
    foreach ([30, 90, 10, 70, 50, 20] as $i => $valor) {
        $c = usuarioSpec($pdo, "Top Cliente $i", 'cliente');
        pedidoSpec($pdo, $c['id'], 'Trabajo', 'Recibido', $valor * 1000, date('Y-m-d H:i:s'));
    }
    $valores = array_map(fn($c) => (float) $c['valor'], indicadoresGerenciales($pdo, 30)['top_clientes']);
    $ok = $valores === [90000.0, 70000.0, 50000.0, 30000.0, 20000.0];
    return [$ok, count($valores) . ' clientes: ' . implode(', ', $valores)];
});

prueba('S17', 'CA-V3D-02', 'Modelo 3D asignado a cada categoría', function () {
    $obtenido = array_map('modeloParaCategoria', ['Fachadas', 'Interiores', 'Gran formato', 'Categoría nueva', null]);
    $esperado = ['fachada', 'pared', 'pendon', 'pendon', 'pendon'];
    return [$obtenido === $esperado, implode(', ', $obtenido)];
});

// ---------------------------------------------------------------------
$aprobadas = count(array_filter($resultados, fn($r) => $r['ok']));
$total = count($resultados);
echo str_repeat('=', 70) . "\nResultado: $aprobadas de $total pruebas de especificación aprobadas.\n";

$md = "# Resultado de pruebas de especificación · BEIM Track\n\n- Fecha de ejecución: " . date('Y-m-d H:i:s')
    . "\n- Contrato: specs/003-flujo-produccion/contracts/flujo_estados.json (versión {$CONTRATO['version']})\n- Resultado: **$aprobadas de $total aprobadas**\n\n"
    . "| ID | Criterio | Prueba | Resultado obtenido | Estado |\n|---|---|---|---|---|\n";
foreach ($resultados as $r) {
    $md .= sprintf("| %s | %s | %s | %s | %s |\n", $r['id'], $r['spec'], $r['desc'], str_replace('|', '/', $r['obtenido']), $r['ok'] ? '✅ Aprobada' : '❌ Fallida');
}
file_put_contents(__DIR__ . '/resultados_spec.md', $md);
exit($aprobadas === $total ? 0 : 1);
