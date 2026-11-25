<?php
// =====================================================================
// BEIM Track · Verificador SDD (especificación ↔ código ↔ pruebas)
//
// Uso:  php tests/verificar_specs.php
//
// 1. Lee los requisitos (RF/RNF) y criterios de aceptación (CA) de specs/*/spec.md
// 2. Lee las etiquetas @spec del código fuente
// 3. Lee el catálogo de pruebas y los resultados de su última ejecución
// 4. Cruza todo, informa los errores y genera specs/TRAZABILIDAD.md
//
// Termina con código 1 si hay errores (Art. VIII de la constitución).
// Ejecuta antes las pruebas para que los resultados estén al día:
//     php tests/pruebas_spec.php
//     php tests/arnes_chatbot.php
//     php tests/pruebas_funcionales.php http://localhost/beimtrack_ia
// =====================================================================

if (PHP_SAPI !== 'cli') {
    exit('Ejecuta este archivo desde la terminal.');
}
$RAIZ = dirname(__DIR__);
require $RAIZ . '/config/env.php';
cargarEnv($RAIZ . '/.env');
date_default_timezone_set(env('APP_TIMEZONE', 'America/Bogota'));
$ID = '(?:RF|RNF)-[A-Z][A-Z0-9]*-\d+';

$errores = [];
$avisos = [];

// ---------------------------------------------------------------------
// 1. Especificaciones
// ---------------------------------------------------------------------
$specs = [];       // carpeta => [titulo, estado, origen]
$requisitos = [];  // id => [spec, texto, cas => []]
$criterios = [];   // id => [spec, requisitos, texto, tipo, pruebas, manual]
$tareas = [];      // carpeta => [hechas, total]

foreach (glob("$RAIZ/specs/[0-9][0-9][0-9]-*/spec.md") as $archivo) {
    $carpeta = basename(dirname($archivo));
    $texto = file_get_contents($archivo);
    preg_match('/^# Especificación · \d+ · (.+)$/m', $texto, $t);
    preg_match('/\*\*Estado:\*\* ([^·\n]+)/', $texto, $e);
    preg_match('/\*\*Origen:\*\* ([^·\n]+)/', $texto, $o);
    $specs[$carpeta] = ['titulo' => trim($t[1] ?? $carpeta), 'estado' => trim($e[1] ?? '—'), 'origen' => trim(preg_replace('/\s*\(.*$/', '', str_replace('*', '', $o[1] ?? '—')))];

    foreach (explode("\n", $texto) as $n => $linea) {
        if (preg_match("/^- \*\*($ID)\*\* · (.+)$/u", $linea, $m)) {
            if (isset($requisitos[$m[1]])) {
                $errores[] = "$carpeta/spec.md:" . ($n + 1) . " requisito duplicado {$m[1]}";
            }
            $requisitos[$m[1]] = ['spec' => $carpeta, 'texto' => $m[2], 'cas' => []];
        } elseif (preg_match('/^- \*\*(CA-[A-Z][A-Z0-9]*-\d+)\*\* \(([^)]+)\) (.+?) ⟶ (Pruebas|Verificación manual): (.+)$/u', $linea, $m)) {
            preg_match_all("/$ID/", $m[2], $reqs);
            $pruebas = $m[4] === 'Pruebas' ? array_map('trim', explode(',', $m[5])) : [];
            $criterios[$m[1]] = ['spec' => $carpeta, 'requisitos' => $reqs[0], 'texto' => $m[3], 'pruebas' => $pruebas,
                                 'manual' => $m[4] === 'Pruebas' ? null : $m[5], 'linea' => $n + 1];
        } elseif (preg_match('/^- \*\*CA-/', $linea)) {
            $errores[] = "$carpeta/spec.md:" . ($n + 1) . ' criterio con formato inválido (falta "(RF-…)" o "⟶ Pruebas:")';
        }
    }
    $tasks = @file_get_contents(dirname($archivo) . '/tasks.md') ?: '';
    $tareas[$carpeta] = [preg_match_all('/^- \[x\]/mi', $tasks), preg_match_all('/^- \[[ x]\]/mi', $tasks)];
    foreach (['plan.md', 'tasks.md'] as $req) {
        if (!is_file(dirname($archivo) . "/$req")) {
            $errores[] = "$carpeta: falta $req";
        }
    }
}
foreach ($criterios as $ca => $c) {
    foreach ($c['requisitos'] as $r) {
        if (!isset($requisitos[$r])) {
            $errores[] = "{$c['spec']}/spec.md:{$c['linea']} $ca cita el requisito inexistente $r";
        } else {
            $requisitos[$r]['cas'][] = $ca;
        }
    }
}
foreach ($requisitos as $r => $d) {
    if (!$d['cas']) {
        $errores[] = "{$d['spec']}: el requisito $r no tiene criterios de aceptación";
    }
}

// ---------------------------------------------------------------------
// 2. Etiquetas @spec en el código
// ---------------------------------------------------------------------
$codigo = []; // requisito => [archivo:línea]
$iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($RAIZ, FilesystemIterator::SKIP_DOTS));
foreach ($iter as $f) {
    $rel = ltrim(str_replace('\\', '/', substr($f->getPathname(), strlen($RAIZ))), '/');
    if (preg_match('#^(tests|specs|uploads|docs|\.opencode)/#', $rel) || !preg_match('/(\.php|\.htaccess|\.css|\.js)$/', $rel)) {
        continue;
    }
    foreach (file($f->getPathname()) as $n => $linea) {
        if (preg_match('/@spec\s+([A-Z0-9, \-]+)/', $linea, $m)) {
            preg_match_all("/$ID/", $m[1], $ids);
            foreach ($ids[0] as $r) {
                if (!isset($requisitos[$r])) {
                    $errores[] = "$rel:" . ($n + 1) . " la etiqueta @spec $r no corresponde a ningún requisito";
                }
                $codigo[$r][] = "$rel:" . ($n + 1);
            }
        }
    }
}
foreach ($requisitos as $r => $d) {
    if (empty($codigo[$r])) {
        $errores[] = "{$d['spec']}: el requisito $r no tiene ninguna etiqueta @spec en el código";
    }
}

// ---------------------------------------------------------------------
// 3. Pruebas y resultados
// ---------------------------------------------------------------------
$catalogo = [];
foreach (['pruebas_funcionales.php', 'pruebas_spec.php', 'arnes_chatbot.php'] as $suite) {
    // Pruebas declaradas con prueba('X01', …) o en tablas de casos ['X01', …].
    preg_match_all("/(?:prueba\\(|^\\s*\\[)'([A-Z]\\d{2})',/m", file_get_contents(__DIR__ . "/$suite"), $m);
    foreach ($m[1] as $id) {
        $catalogo[$id] = $suite;
    }
}
$resultados = [];
$fechas = [];
foreach (['resultados_ultima_ejecucion.md' => 'pruebas_funcionales.php', 'resultados_spec.md' => 'pruebas_spec.php', 'resultados_arnes.md' => 'arnes_chatbot.php'] as $md => $suite) {
    if (!is_file(__DIR__ . "/$md")) {
        $avisos[] = "No hay resultados de $suite: ejecútalo antes del verificador.";
        continue;
    }
    $txt = file_get_contents(__DIR__ . "/$md");
    $fechas[$suite] = preg_match('/Fecha de ejecución: (.+)/', $txt, $f) ? trim($f[1]) : '¿?';
    preg_match_all('/^\| ([A-Z]\d{2}) \|.*\| (✅ Aprobada|❌ Fallida) \|$/mu', $txt, $m, PREG_SET_ORDER);
    foreach ($m as $fila) {
        $resultados[$fila[1]] = str_starts_with($fila[2], '✅');
    }
}
$citadas = [];
foreach ($criterios as $ca => $c) {
    foreach ($c['pruebas'] as $p) {
        $citadas[$p] = true;
        if (!isset($catalogo[$p])) {
            $errores[] = "{$c['spec']}/spec.md:{$c['linea']} $ca cita la prueba $p, que no existe";
        } elseif (array_key_exists($p, $resultados) && !$resultados[$p]) {
            $errores[] = "$ca: la prueba $p falló en la última ejecución";
        } elseif (!array_key_exists($p, $resultados)) {
            $avisos[] = "$ca: la prueba $p no tiene resultado registrado (sin ejecutar).";
        }
    }
}
foreach (array_diff_key($catalogo, $citadas) as $p => $suite) {
    $avisos[] = "La prueba $p ($suite) no está citada por ningún criterio de aceptación.";
}

// ---------------------------------------------------------------------
// 4. Estado por requisito y reporte
// ---------------------------------------------------------------------
function estadoPrueba(string $p, array $resultados): string
{
    return !array_key_exists($p, $resultados) ? "$p ⚪" : ($resultados[$p] ? "$p ✅" : "$p ❌");
}
$estadoReq = [];
foreach ($requisitos as $r => $d) {
    $auto = $manual = $falla = $pendiente = 0;
    foreach ($d['cas'] as $ca) {
        $c = $criterios[$ca];
        if ($c['manual'] !== null) {
            $manual++;
        }
        foreach ($c['pruebas'] as $p) {
            $auto++;
            if (!array_key_exists($p, $resultados)) {
                $pendiente++;
            } elseif (!$resultados[$p]) {
                $falla++;
            }
        }
    }
    $estadoReq[$r] = $falla ? '❌ Falla' : ($pendiente ? '⚪ Sin ejecutar' : ($auto === 0 ? '🟡 Solo manual' : ($manual ? '✅ Cumple (+ verificación manual)' : '✅ Cumple')));
}

$md = "# Matriz de trazabilidad SDD · BEIM Track\n\n";
$md .= "> Archivo generado automáticamente por `php tests/verificar_specs.php`. **No editar a mano**: cambia las specs, el código o las pruebas y vuelve a generarlo.\n\n";
$md .= '- Generado: ' . date('Y-m-d H:i:s') . "\n";
foreach ($fechas as $suite => $fecha) {
    $md .= "- Resultados de `tests/$suite`: $fecha\n";
}
$md .= '- Requisitos: **' . count($requisitos) . '** · Criterios de aceptación: **' . count($criterios) . '** · Pruebas automáticas: **' . count($catalogo) . '** · Etiquetas `@spec` en el código: **' . array_sum(array_map('count', $codigo)) . "**\n";
$md .= '- Verificación: ' . ($errores ? '❌ **' . count($errores) . ' error(es)**' : '✅ **sin errores**') . "\n\n";
$md .= "Leyenda: ✅ prueba aprobada · ❌ prueba fallida · ⚪ sin ejecutar · 🟡 solo verificación manual\n\n";

$md .= "## Resumen por especificación\n\n| Spec | Funcionalidad | Origen | Requisitos | Criterios (auto / manual) | Tareas | Estado |\n|---|---|---|---|---|---|---|\n";
foreach ($specs as $carpeta => $s) {
    $reqs = array_filter($requisitos, fn($d) => $d['spec'] === $carpeta);
    $cas = array_filter($criterios, fn($c) => $c['spec'] === $carpeta);
    $auto = count(array_filter($cas, fn($c) => $c['manual'] === null));
    $ok = !array_filter(array_keys($reqs), fn($r) => str_starts_with($estadoReq[$r], '❌') || str_starts_with($estadoReq[$r], '⚪'));
    [$hechas, $total] = $tareas[$carpeta];
    $md .= "| [$carpeta]($carpeta/spec.md) | {$s['titulo']} | {$s['origen']} | " . count($reqs) . ' | ' . count($cas) . " ($auto / " . (count($cas) - $auto) . ") | $hechas/$total | " . ($ok ? '✅' : '❌') . " |\n";
}

$md .= "\n## Detalle por requisito\n";
foreach ($specs as $carpeta => $s) {
    $md .= "\n### $carpeta · {$s['titulo']}\n\n| Requisito | Criterios | Pruebas | Código | Estado |\n|---|---|---|---|---|\n";
    foreach ($requisitos as $r => $d) {
        if ($d['spec'] !== $carpeta) {
            continue;
        }
        $pruebas = [];
        $manuales = 0;
        foreach ($d['cas'] as $ca) {
            foreach ($criterios[$ca]['pruebas'] as $p) {
                $pruebas[$p] = estadoPrueba($p, $resultados);
            }
            $manuales += $criterios[$ca]['manual'] !== null ? 1 : 0;
        }
        $archivos = array_unique(array_map(fn($x) => '`' . preg_replace('/:\d+$/', '', $x) . '`', $codigo[$r] ?? []));
        $md .= "| **$r** " . mb_strimwidth($d['texto'], 0, 90, '…') . ' | ' . implode(', ', $d['cas']) . ' | '
            . (implode(' ', $pruebas) ?: '—') . ($manuales ? " · $manuales manual" : '') . ' | ' . implode('<br>', $archivos) . " | {$estadoReq[$r]} |\n";
    }
}

$md .= "\n## Verificaciones manuales pendientes de evidencia\n\n| Criterio | Cómo se verifica |\n|---|---|\n";
foreach ($criterios as $ca => $c) {
    if ($c['manual'] !== null) {
        $md .= "| $ca ({$c['spec']}) | {$c['manual']} |\n";
    }
}
if ($errores || $avisos) {
    $md .= "\n## Hallazgos del verificador\n\n";
    foreach ($errores as $e) {
        $md .= "- ❌ $e\n";
    }
    foreach ($avisos as $a) {
        $md .= "- ⚠️ $a\n";
    }
}
file_put_contents("$RAIZ/specs/TRAZABILIDAD.md", $md);

// ---------------------------------------------------------------------
echo "BEIM Track · Verificador SDD\n" . str_repeat('=', 70) . "\n";
printf("Especificaciones: %d · Requisitos: %d · Criterios: %d · Pruebas: %d · Etiquetas @spec: %d\n",
    count($specs), count($requisitos), count($criterios), count($catalogo), array_sum(array_map('count', $codigo)));
foreach ($avisos as $a) {
    echo "[AVISO] $a\n";
}
foreach ($errores as $e) {
    echo "[ERROR] $e\n";
}
echo str_repeat('=', 70) . "\n" . ($errores ? 'Verificación FALLIDA: ' . count($errores) . ' error(es).' : 'Verificación correcta: especificación, código y pruebas están alineados.') . "\nMatriz generada en specs/TRAZABILIDAD.md\n";
exit($errores ? 1 : 0);
