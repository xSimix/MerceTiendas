<?php
class Reporte {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function generarReportePorFecha($fecha_inicio, $fecha_fin) {
        $query = "SELECT tipo, categoria, monto, descripcion, fecha_movimiento 
                  FROM movimientos_caja 
                  WHERE fecha_movimiento BETWEEN :fecha_inicio AND :fecha_fin 
                  ORDER BY fecha_movimiento ASC";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(":fecha_inicio", $fecha_inicio);
        $stmt->bindParam(":fecha_fin", $fecha_fin);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
