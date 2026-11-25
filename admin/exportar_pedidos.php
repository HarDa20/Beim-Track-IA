<?php
// Descarga del listado de pedidos en CSV con los filtros de la pantalla.
// Especificación: specs/009-exportar-pedidos/spec.md
// @spec RF-EXP-01
// @spec RF-EXP-03
// @spec RNF-EXP-01
require __DIR__ . '/../includes/bootstrap.php';
$u = requiereRol('administrador');

$pedidos = consultarPedidosExportacion($pdo, filtrosPedidosAdmin($_GET));

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="pedidos_beimtrack_' . hoy() . '.csv"');
header('Cache-Control: no-store');

$salida = fopen('php://output', 'wb');
fwrite($salida, "\xEF\xBB\xBF"); // BOM: Excel reconoce UTF-8 y muestra bien las tildes.
fputcsv($salida, encabezadosExportacion(), ';', '"', '');
foreach ($pedidos as $p) {
    fputcsv($salida, filaExportacion($p), ';', '"', '');
}
fclose($salida);
