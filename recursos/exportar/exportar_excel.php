<?php
require '../configuracion/base_datos.php';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="reporte_movimientos.xls"');
header('Cache-Control: max-age=0');

$fecha_inicio = $_GET['fecha_inicio'];
$fecha_fin = $_GET['fecha_fin'];

$query = "SELECT tipo, categoria, monto, descripcion, fecha_movimiento FROM movimientos_caja WHERE fecha_movimiento BETWEEN :fecha_inicio AND :fecha_fin ORDER BY fecha_movimiento ASC";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':fecha_inicio', $fecha_inicio);
$stmt->bindParam(':fecha_fin', $fecha_fin);
$stmt->execute();
$movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'>";
echo "<tr><th>Tipo</th><th>Categoría</th><th>Monto</th><th>Descripción</th><th>Fecha</th></tr>";

foreach ($movimientos as $movimiento) {
    echo "<tr>";
    echo "<td>{$movimiento['tipo']}</td>";
    echo "<td>{$movimiento['categoria']}</td>";
    echo "<td>S/ " . number_format($movimiento['monto'], 2) . "</td>";
    echo "<td>{$movimiento['descripcion']}</td>";
    echo "<td>{$movimiento['fecha_movimiento']}</td>";
    echo "</tr>";
}

echo "</table>";
?>
z|