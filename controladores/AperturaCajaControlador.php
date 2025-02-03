<?php
session_start();
require_once "../configuracion/base_datos.php";

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'abrir_caja') {
        $usuario_id = $_SESSION['usuario_id'];
        $monto_apertura = $_POST['monto_apertura'] ?? 0;

        if (empty($monto_apertura) || $monto_apertura <= 0) {
            throw new Exception("El monto de apertura es obligatorio y debe ser mayor a 0.");
        }

        $query = "INSERT INTO apertura_caja (usuario_id, monto_apertura, fecha_apertura, estado) 
                  VALUES (:usuario_id, :monto_apertura, NOW(), 'abierta')";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':monto_apertura', $monto_apertura);

        if ($stmt->execute()) {
            $_SESSION['apertura_id'] = $pdo->lastInsertId(); // Establece la apertura activa
            header("Location: ../vistas/flujo_caja.php?status=opened");
            exit();
        } else {
            throw new Exception("Error al abrir la caja.");
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
