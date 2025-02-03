<?php
require '../configuracion/base_datos.php';
require '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$fecha_inicio = $_GET['fecha_inicio'];
$fecha_fin = $_GET['fecha_fin'];

$query = "SELECT tipo, categoria, monto, descripcion, fecha_movimiento FROM movimientos_caja WHERE fecha_movimiento BETWEEN :fecha_inicio AND :fecha_fin ORDER BY fecha_movimiento ASC";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':fecha_inicio', $fecha_inicio);
$stmt->bindParam(':fecha_fin', $fecha_fin);
$stmt->execute();
$movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$html = "<h1>Reporte de Movimientos</h1><table><thead><tr><th>Tipo</th><th>Categoría</th><th>Monto</th><th>Descripción</th><th>Fecha</th></tr></thead><tbody>";

foreach ($movimientos as $movimiento) {
    $html .= "<tr><td>{$movimiento['tipo']}</td><td>{$movimiento['categoria']}</td><td>S/ " . number_format($movimiento['monto'], 2) . "</td><td>{$movimiento['descripcion']}</td><td>{$movimiento['fecha_movimiento']}</td></tr>";
}

$html .= "</tbody></table>";

$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream('reporte_movimientos.pdf', ['Attachment' => true]);
?>
