<?php
session_start();
require_once "../configuracion/base_datos.php";

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {

        // Función auxiliar para obtener el apertura_id
        function obtenerAperturaId() {
            if (isset($_POST['apertura_id']) && !empty($_POST['apertura_id'])) {
                return $_POST['apertura_id'];
            } elseif (isset($_SESSION['apertura_id']) && !empty($_SESSION['apertura_id'])) {
                return $_SESSION['apertura_id'];
            } else {
                throw new Exception("No hay una apertura de caja activa.");
            }
        }

        // --- EDITAR MOVIMIENTO ---
        if ($_POST['accion'] === 'editar_movimiento') {
            $apertura_id = obtenerAperturaId();
            $movimiento_id = $_POST['movimiento_id'] ?? null;
            if (!$movimiento_id) {
                throw new Exception("ID de movimiento no proporcionado.");
            }

            // Validar que el movimiento exista y pertenezca a la apertura
            $query = "SELECT id FROM movimientos_caja WHERE id = :movimiento_id AND apertura_id = :apertura_id";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(":movimiento_id", $movimiento_id, PDO::PARAM_INT);
            $stmt->bindParam(":apertura_id", $apertura_id, PDO::PARAM_INT);
            $stmt->execute();
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                throw new Exception("Movimiento no encontrado o no pertenece a la caja activa.");
            }

            // Procesar y validar datos para la edición
            $tipo = $_POST['tipo'] ?? '';
            $metodo_pago = $_POST['metodo_pago'] ?? '';
            $metodo_pago_destino = $_POST['metodo_pago_destino'] ?? null;
            $monto = (float) ($_POST['monto'] ?? 0);
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
                    $tipo = 'ingreso';
                } elseif (in_array($metodo_pago, ['yape', 'plin', 'transferencia']) && $metodo_pago_destino === 'efectivo') {
                    $tipo = 'egreso';
                } else {
                    throw new Exception("El movimiento de cambio no es válido.");
                }
            }

            // Validar métodos de pago para ingresos
            if ($tipo === 'ingreso') {
                if (!in_array($metodo_pago, ['efectivo', 'yape', 'plin', 'transferencia'])) {
                    throw new Exception("El método de pago para ingresos no es válido.");
                }
                if (in_array($metodo_pago, ['yape', 'plin', 'transferencia'])) {
                    $tipo = 'virtual';
                }
            }

            // Validar métodos de pago para egresos
            if ($tipo === 'egreso') {
                if (!in_array($metodo_pago, ['efectivo', 'yape', 'plin'])) {
                    throw new Exception("El método de pago para egresos no es válido.");
                }
            }

            // Actualizar el movimiento
            $query = "UPDATE movimientos_caja 
                      SET tipo = :tipo, 
                          metodo_pago = :metodo_pago, 
                          metodo_pago_destino = :metodo_pago_destino, 
                          monto = :monto, 
                          descripcion = :descripcion 
                      WHERE id = :movimiento_id";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(":tipo", $tipo);
            $stmt->bindParam(":metodo_pago", $metodo_pago);
            $stmt->bindParam(":metodo_pago_destino", $metodo_pago_destino);
            $stmt->bindParam(":monto", $monto);
            $stmt->bindParam(":descripcion", $descripcion);
            $stmt->bindParam(":movimiento_id", $movimiento_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $cierre_id = $_POST['cierre_id'] ?? '';
                header("Location: /flujo_caja/vistas/detalle_cierre.php?id=" . urlencode($cierre_id) . "&status=updated");
                exit();
            } else {
                throw new Exception("Error al actualizar el movimiento.");
            }
        }
        // --- CREAR NUEVO MOVIMIENTO DESDE DETALLE (acción: crear_movimiento_detalle) ---
        else if ($_POST['accion'] === 'crear_movimiento_detalle') {
            $apertura_id = obtenerAperturaId();

            // Validar que la apertura exista y esté activa
            $query = "SELECT id FROM apertura_caja WHERE id = :apertura_id AND estado = 'abierta'";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(":apertura_id", $apertura_id, PDO::PARAM_INT);
            $stmt->execute();
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                throw new Exception("El ID de apertura no existe o no está activa.");
            }

            // Procesar y validar datos para el nuevo movimiento
            $tipo = $_POST['tipo'] ?? '';
            $metodo_pago = $_POST['metodo_pago'] ?? '';
            $metodo_pago_destino = $_POST['metodo_pago_destino'] ?? null;
            $monto = (float) ($_POST['monto'] ?? 0);
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
                    $tipo = 'ingreso';
                } elseif (in_array($metodo_pago, ['yape', 'plin', 'transferencia']) && $metodo_pago_destino === 'efectivo') {
                    $tipo = 'egreso';
                } else {
                    throw new Exception("El movimiento de cambio no es válido.");
                }
            }

            // Validar métodos de pago para ingresos
            if ($tipo === 'ingreso') {
                if (!in_array($metodo_pago, ['efectivo', 'yape', 'plin', 'transferencia'])) {
                    throw new Exception("El método de pago para ingresos no es válido.");
                }
                if (in_array($metodo_pago, ['yape', 'plin', 'transferencia'])) {
                    $tipo = 'virtual';
                }
            }

            // Validar métodos de pago para egresos
            if ($tipo === 'egreso') {
                if (!in_array($metodo_pago, ['efectivo', 'yape', 'plin'])) {
                    throw new Exception("El método de pago para egresos no es válido.");
                }
            }

            // Insertar el nuevo movimiento
            $query = "INSERT INTO movimientos_caja 
                      (apertura_id, tipo, metodo_pago, metodo_pago_destino, monto, descripcion, fecha_movimiento) 
                      VALUES (:apertura_id, :tipo, :metodo_pago, :metodo_pago_destino, :monto, :descripcion, NOW())";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(":apertura_id", $apertura_id, PDO::PARAM_INT);
            $stmt->bindParam(":tipo", $tipo);
            $stmt->bindParam(":metodo_pago", $metodo_pago);
            $stmt->bindParam(":metodo_pago_destino", $metodo_pago_destino);
            $stmt->bindParam(":monto", $monto);
            $stmt->bindParam(":descripcion", $descripcion);

            if ($stmt->execute()) {
                $cierre_id = $_POST['cierre_id'] ?? '';
                header("Location: /flujo_caja/vistas/detalle_cierre.php?id=" . urlencode($cierre_id) . "&status=created");
                exit();
            } else {
                throw new Exception("Error al crear el nuevo movimiento.");
            }
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
