<?php
// Exportación del listado de pedidos a CSV para Excel.
// Especificación: specs/009-exportar-pedidos/spec.md (escrita antes de este código).

const LIMITE_EXPORTACION = 5000;

/**
 * Lee los filtros del listado de pedidos del administrador desde la consulta
 * (GET). La usan la pantalla y la exportación para que ambas muestren
 * exactamente los mismos pedidos.
 *
 * @spec RF-EXP-01
 * @spec RF-ADM-02
 */
function filtrosPedidosAdmin(array $get): array
{
    $filtros = [
        'q'            => trim((string) ($get['q'] ?? '')),
        'estado'       => (string) ($get['estado'] ?? ''),
        'categoria_id' => (int) ($get['categoria_id'] ?? 0),
        'empleado_id'  => (string) ($get['empleado_id'] ?? ''),
        'retrasados'   => !empty($get['retrasados']),
    ];
    $vista = (string) ($get['vista'] ?? '');
    if ($vista === 'proceso') {
        $filtros['estados'] = ['En diseño', 'En producción', 'Control de calidad'];
    } elseif ($vista === 'activos') {
        $filtros['estados'] = ESTADOS_ACTIVOS;
    }
    return $filtros;
}

/**
 * Prepara un valor para una celda CSV. Los textos que empiezan por =, +, -, @,
 * tabulador o retorno de carro se anteponen con un apóstrofo para que Excel no
 * los ejecute como fórmula (CSV injection, OWASP).
 *
 * @spec RNF-EXP-02
 */
function celdaCsv($valor): string
{
    if ($valor === null) {
        return '';
    }
    if (is_int($valor) || is_float($valor)) {
        return (string) $valor;
    }
    $texto = (string) $valor;
    if ($texto !== '' && in_array($texto[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
        return "'" . $texto;
    }
    return $texto;
}

/** @spec RF-EXP-02 */
function encabezadosExportacion(): array
{
    return ['Pedido', 'Registrado', 'Cliente', 'Correo', 'Canal', 'Categoría', 'Trabajo', 'Cantidad', 'Unidad',
            'Precio unitario', 'Costo estimado', 'Entrega pactada', 'Estado', 'Responsable', 'Retrasado', 'Entregado el'];
}

/**
 * Pedidos a exportar con los filtros dados, sin borradores y con límite.
 *
 * @spec RF-EXP-01
 * @spec RNF-EXP-03
 */
function consultarPedidosExportacion(PDO $pdo, array $filtros): array
{
    unset($filtros['incluir_borradores']);
    $pedidos = consultarPedidos($pdo, $filtros, LIMITE_EXPORTACION);
    if (!$pedidos) {
        return [];
    }
    // El correo del cliente no viene en el listado: se agrega en una sola consulta.
    $ids = array_values(array_unique(array_map(fn($p) => (int) $p['cliente_id'], $pedidos)));
    $stmt = $pdo->prepare('SELECT id, correo FROM usuarios WHERE id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')');
    $stmt->execute($ids);
    $correos = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    foreach ($pedidos as &$p) {
        $p['cliente_correo'] = $correos[(int) $p['cliente_id']] ?? '';
    }
    return $pedidos;
}

/**
 * Convierte un pedido en la fila del CSV, en el orden de encabezadosExportacion().
 *
 * @spec RF-EXP-02
 * @spec RNF-EXP-01
 */
function filaExportacion(array $p): array
{
    $retrasado = in_array($p['estado'], ESTADOS_ACTIVOS, true) && $p['fecha_entrega'] < hoy();
    return array_map('celdaCsv', [
        codigoPedido($p['id']),
        substr((string) $p['creado_en'], 0, 10),
        $p['cliente_nombre'],
        $p['cliente_correo'] ?? '',
        $p['canal_recepcion'],
        $p['categoria'],
        $p['tipo_trabajo'],
        (float) $p['cantidad'],
        $p['unidad'],
        (float) $p['precio_unitario'],
        (float) $p['costo_estimado'],
        $p['fecha_entrega'],
        $p['estado'],
        $p['empleado_nombre'] ?? '',
        $retrasado ? 'Sí' : 'No',
        $p['fecha_entregado'] ? substr((string) $p['fecha_entregado'], 0, 10) : '',
    ]);
}
