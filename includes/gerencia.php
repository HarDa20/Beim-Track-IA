<?php
// Indicadores del panel gerencial (spec 015). Solo lectura.

const PERIODOS_GERENCIA = [30 => 'Últimos 30 días', 90 => 'Últimos 90 días', 365 => 'Último año'];

/**
 * Tiempo promedio (en horas) que pasan los pedidos en cada etapa activa,
 * calculado recorriendo el historial de cambios de estado en orden.
 *
 * @spec RF-GER-02
 */
function tiemposPorEtapa(PDO $pdo, string $desde): array
{
    $stmt = $pdo->prepare("SELECT h.pedido_id, h.estado_nuevo, h.fecha
        FROM historial_pedido h JOIN pedidos p ON p.id = h.pedido_id
        WHERE p.creado_en >= ? AND h.estado_nuevo IS NOT NULL
        ORDER BY h.pedido_id, h.fecha, h.id");
    $stmt->execute([$desde]);
    $suma = array_fill_keys(ESTADOS_ACTIVOS, 0.0);
    $veces = array_fill_keys(ESTADOS_ACTIVOS, 0);
    $anterior = null;
    foreach ($stmt as $fila) {
        if ($anterior && $anterior['pedido_id'] === $fila['pedido_id'] && isset($suma[$anterior['estado_nuevo']])) {
            $suma[$anterior['estado_nuevo']] += max(0, strtotime($fila['fecha']) - strtotime($anterior['fecha'])) / 3600;
            $veces[$anterior['estado_nuevo']]++;
        }
        $anterior = $fila;
    }
    $resultado = [];
    foreach (ESTADOS_ACTIVOS as $estado) {
        $resultado[$estado] = [
            'area'   => areaDeEstado($estado),
            'horas'  => $veces[$estado] ? round($suma[$estado] / $veces[$estado], 1) : null,
            'veces'  => $veces[$estado],
        ];
    }
    return $resultado;
}

/**
 * Todos los indicadores del periodo (días hacia atrás desde $hoy).
 *
 * @spec RF-GER-01, RF-GER-02, RF-GER-03
 */
function indicadoresGerenciales(PDO $pdo, int $dias, ?string $hoy = null): array
{
    $hoy ??= hoy();
    $desde = date('Y-m-d 00:00:00', strtotime($hoy . ' -' . $dias . ' days'));
    $activos = implode(',', array_fill(0, count(ESTADOS_ACTIVOS), '?'));

    $q = function (string $sql, array $params = []) use ($pdo) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    };

    $entregados = $q("SELECT costo_estimado, creado_en, fecha_entregado, fecha_entrega FROM pedidos
                      WHERE estado = 'Entregado' AND fecha_entregado >= ?", [$desde])->fetchAll();
    $ingresos = array_sum(array_map(fn($p) => (float) $p['costo_estimado'], $entregados));
    $aTiempo = count(array_filter($entregados, fn($p) => substr((string) $p['fecha_entregado'], 0, 10) <= $p['fecha_entrega']));
    $ciclos = array_map(fn($p) => max(0, strtotime($p['fecha_entregado']) - strtotime($p['creado_en'])) / 86400, $entregados);

    $periodo = $q("SELECT COUNT(*) AS total, SUM(CASE WHEN estado = 'Cancelado' THEN 1 ELSE 0 END) AS cancelados
                   FROM pedidos WHERE estado <> 'Borrador' AND creado_en >= ?", [$desde])->fetch();
    $proceso = $q("SELECT COUNT(*) AS n, COALESCE(SUM(costo_estimado), 0) AS valor FROM pedidos WHERE estado IN ($activos)", ESTADOS_ACTIVOS)->fetch();

    $topClientes = $q("SELECT u.id, u.nombre, u.foto_perfil, u.rol, COUNT(*) AS pedidos, SUM(p.costo_estimado) AS valor
                       FROM pedidos p JOIN usuarios u ON u.id = p.cliente_id
                       WHERE p.estado NOT IN ('Borrador', 'Cancelado') AND p.creado_en >= ?
                       GROUP BY u.id, u.nombre, u.foto_perfil, u.rol ORDER BY valor DESC, pedidos DESC LIMIT 5", [$desde])->fetchAll();
    $porCategoria = $q("SELECT categoria, COUNT(*) AS pedidos, SUM(costo_estimado) AS valor FROM pedidos
                        WHERE estado NOT IN ('Borrador', 'Cancelado') AND creado_en >= ?
                        GROUP BY categoria ORDER BY valor DESC", [$desde])->fetchAll();
    $porCanal = $q("SELECT canal_recepcion AS canal, COUNT(*) AS pedidos FROM pedidos
                    WHERE estado <> 'Borrador' AND creado_en >= ? GROUP BY canal_recepcion ORDER BY pedidos DESC", [$desde])->fetchAll();
    $carga = $q("SELECT u.id, u.nombre, u.area, u.foto_perfil, u.rol, COUNT(*) AS activos FROM pedidos p JOIN usuarios u ON u.id = p.empleado_id
                 WHERE p.estado IN ($activos) GROUP BY u.id, u.nombre, u.area, u.foto_perfil, u.rol ORDER BY activos DESC", ESTADOS_ACTIVOS)->fetchAll();

    $etapas = tiemposPorEtapa($pdo, $desde);
    $medidas = array_filter($etapas, fn($e) => $e['horas'] !== null);
    $cuello = null;
    if ($medidas) {
        $max = max(array_column($medidas, 'horas'));
        foreach ($medidas as $estado => $e) {
            if ($e['horas'] === $max) {
                $cuello = $estado;
                break;
            }
        }
    }

    return [
        'dias'            => $dias,
        'desde'           => $desde,
        'ingresos'        => $ingresos,
        'entregados'      => count($entregados),
        'ticket_promedio' => $entregados ? round($ingresos / count($entregados), 2) : null,
        'a_tiempo_pct'    => $entregados ? round($aTiempo / count($entregados) * 100) : null,
        'ciclo_dias'      => $ciclos ? round(array_sum($ciclos) / count($ciclos), 1) : null,
        'pedidos_periodo' => (int) $periodo['total'],
        'cancelados'      => (int) $periodo['cancelados'],
        'cancelacion_pct' => (int) $periodo['total'] ? round((int) $periodo['cancelados'] / (int) $periodo['total'] * 100) : null,
        'en_proceso'      => (int) $proceso['n'],
        'valor_proceso'   => (float) $proceso['valor'],
        'top_clientes'    => $topClientes,
        'por_categoria'   => $porCategoria,
        'por_canal'       => $porCanal,
        'carga'           => $carga,
        'etapas'          => $etapas,
        'cuello'          => $cuello,
    ];
}
