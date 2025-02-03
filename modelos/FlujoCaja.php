<?php
class FlujoCaja {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function registrarMovimiento($apertura_id, $tipo, $categoria, $monto, $descripcion) {
        $query = "INSERT INTO movimientos_caja (apertura_id, tipo, categoria, monto, descripcion) VALUES (:apertura_id, :tipo, :categoria, :monto, :descripcion)";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(":apertura_id", $apertura_id);
        $stmt->bindParam(":tipo", $tipo);
        $stmt->bindParam(":categoria", $categoria);
        $stmt->bindParam(":monto", $monto);
        $stmt->bindParam(":descripcion", $descripcion);
        return $stmt->execute();
    }

    public function obtenerMovimientosPorApertura($apertura_id) {
        $query = "SELECT * FROM movimientos_caja WHERE apertura_id = :apertura_id ORDER BY fecha_movimiento ASC";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(":apertura_id", $apertura_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
