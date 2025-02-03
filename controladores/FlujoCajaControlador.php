<?php
session_start();
require_once "../configuracion/base_datos.php";

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {

        // Registrar movimiento
        if ($_POST['accion'] === 'registrar_movimiento') {

            if (!isset($_SESSION['apertura_id'])) {
                throw new Exception("No hay una apertura de caja activa.");
            }

            $apertura_id = $_SESSION['apertura_id'];

            // Validar que apertura_id exista en la base de datos
            $query = "SELECT id FROM apertura_caja WHERE id = :apertura_id AND estado = 'abierta'";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(":apertura_id", $apertura_id);
            $stmt->execute();

            if (!$stmt->fetch()) {
                throw new Exception("El ID de apertura no existe o no está activa.");
            }

            $tipo = $_POST['tipo'] ?? '';
            $metodo_pago = $_POST['metodo_pago'] ?? '';
            $metodo_pago_destino = $_POST['metodo_pago_destino'] ?? null;
            $monto = (float)($_POST['monto'] ?? 0);
            $descripcion = $_POST['descripcion'] ?? '';

            if (empty($tipo) || empty($metodo_pago) || $monto <= 0) {
                throw new Exception("Todos los campos son obligatorios y el monto debe ser mayor a 0.");
            }

            // Lógica para "cambio"
            if ($tipo === 'cambio') {
                if (empty($metodo_pago_destino)) {
                    throw new Exception("Debe seleccionar el método de pago destino para un cambio.");
                }

                if ($metodo_pago === 'efectivo' && in_array($metodo_pago_destino, ['yape', 'plin', 'transferencia'])) {
                    $tipo = 'ingreso'; // Cambio de efectivo a virtual
                } elseif (in_array($metodo_pago, ['yape', 'plin', 'transferencia']) && $metodo_pago_destino === 'efectivo') {
                    $tipo = 'egreso'; // Cambio de virtual a efectivo
                } else {
                    throw new Exception("El movimiento de cambio no es válido.");
                }
            }

            // Validar métodos de pago para ingresos
            if ($tipo === 'ingreso') {
                if (!in_array($metodo_pago, ['efectivo', 'yape', 'plin', 'transferencia'])) {
                    throw new Exception("El método de pago para ingresos no es válido.");
                }

                // Clasificar ingresos virtuales
                if (in_array($metodo_pago, ['yape', 'plin', 'transferencia'])) {
                    $tipo = 'virtual'; // Ingresos virtuales no afectan efectivo
                }
            }

            // Validar métodos de pago para egresos
            if ($tipo === 'egreso') {
                if (!in_array($metodo_pago, ['efectivo', 'yape', 'plin'])) {
                    throw new Exception("El método de pago para egresos no es válido.");
                }
            }

            // Insertar movimiento en la base de datos
            $query = "INSERT INTO movimientos_caja (apertura_id, tipo, metodo_pago, metodo_pago_destino, monto, descripcion, fecha_movimiento) 
                      VALUES (:apertura_id, :tipo, :metodo_pago, :metodo_pago_destino, :monto, :descripcion, NOW())";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(":apertura_id", $apertura_id);
            $stmt->bindParam(":tipo", $tipo);
            $stmt->bindParam(":metodo_pago", $metodo_pago);
            $stmt->bindParam(":metodo_pago_destino", $metodo_pago_destino);
            $stmt->bindParam(":monto", $monto);
            $stmt->bindParam(":descripcion", $descripcion);

            if ($stmt->execute()) {
                header("Location: ../vistas/flujo_caja.php?status=success");
                exit();
            } else {
                throw new Exception("Error al registrar el movimiento.");
            }
        }
        // Eliminar movimiento
        else if ($_POST['accion'] === 'eliminar_movimiento') {
            if (!isset($_SESSION['apertura_id'])) {
                throw new Exception("No hay una apertura de caja activa.");
            }

            $apertura_id = $_SESSION['apertura_id'];
            $movimiento_id = $_POST['movimiento_id'] ?? null;

            if (!$movimiento_id) {
                throw new Exception("ID de movimiento no proporcionado.");
            }

            // Validar que el movimiento existe y pertenece a la apertura actual
            $query = "SELECT id FROM movimientos_caja WHERE id = :movimiento_id AND apertura_id = :apertura_id";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(":movimiento_id", $movimiento_id);
            $stmt->bindParam(":apertura_id", $apertura_id);
            $stmt->execute();

            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                throw new Exception("Movimiento no encontrado o no pertenece a la caja activa.");
            }

            // Eliminar el movimiento
            $query = "DELETE FROM movimientos_caja WHERE id = :movimiento_id";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(":movimiento_id", $movimiento_id);

            if ($stmt->execute()) {
                header("Location: ../vistas/flujo_caja.php?status=deleted");
                exit();
            } else {
                throw new Exception("Error al eliminar el movimiento.");
            }
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
