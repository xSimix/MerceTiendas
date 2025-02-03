<?php
session_start();
require_once "../configuracion/base_datos.php";

/**
 * Función que recalcula y actualiza los totales del cierre de caja.
 *
 * @param PDO $pdo
 * @param int $apertura_id
 * @throws Exception
 */
function recalcCierre($pdo, $apertura_id) {
    $queryApertura = "SELECT monto_apertura FROM apertura_caja WHERE id = :apertura_id";
    $stmtApertura = $pdo->prepare($queryApertura);
    $stmtApertura->bindParam(":apertura_id", $apertura_id, PDO::PARAM_INT);
    $stmtApertura->execute();
    $apertura = $stmtApertura->fetch(PDO::FETCH_ASSOC);

    if (!$apertura) {
        throw new Exception("No se encontró la apertura de caja.");
    }

    $monto_apertura = (float)$apertura['monto_apertura'];

    $queryMov = "SELECT tipo, monto FROM movimientos_caja WHERE apertura_id = :apertura_id";
    $stmtMov = $pdo->prepare($queryMov);
    $stmtMov->bindParam(":apertura_id", $apertura_id, PDO::PARAM_INT);
    $stmtMov->execute();
    $movimientos = $stmtMov->fetchAll(PDO::FETCH_ASSOC);

    $total_ingresos = 0;
    $total_egresos = 0;
    foreach ($movimientos as $mov) {
        if ($mov['tipo'] === 'ingreso') {
            $total_ingresos += (float)$mov['monto'];
        } elseif ($mov['tipo'] === 'egreso') {
            $total_egresos += (float)$mov['monto'];
        }
    }

    $total_calculado = $monto_apertura + $total_ingresos - $total_egresos;

    $queryUpdate = "UPDATE apertura_caja SET 
        total_ingresos = :total_ingresos, 
        total_egresos = :total_egresos, 
        saldo_final = :saldo_final 
        WHERE id = :apertura_id";
    $stmtUpdate = $pdo->prepare($queryUpdate);
    $stmtUpdate->bindParam(":total_ingresos", $total_ingresos);
    $stmtUpdate->bindParam(":total_egresos", $total_egresos);
    $stmtUpdate->bindParam(":saldo_final", $total_calculado);
    $stmtUpdate->bindParam(":apertura_id", $apertura_id, PDO::PARAM_INT);
    $stmtUpdate->execute();
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
        $accion = $_POST['accion'];
        $apertura_id = $_SESSION['apertura_id'] ?? null;

        if (!$apertura_id) {
            throw new Exception("No se encontró el ID de apertura.");
        }

        switch ($accion) {
            case 'eliminar_movimiento':
                $movimiento_id = $_POST['movimiento_id'] ?? null;
                if (!$movimiento_id) {
                    throw new Exception("ID de movimiento no proporcionado.");
                }

                $queryDelete = "DELETE FROM movimientos_caja WHERE id = :movimiento_id";
                $stmtDelete = $pdo->prepare($queryDelete);
                $stmtDelete->bindParam(":movimiento_id", $movimiento_id, PDO::PARAM_INT);
                $stmtDelete->execute();

                recalcCierre($pdo, $apertura_id);

                header("Location: /flujo_caja/vistas/editar_movimientos.php?status=success&mensaje=Movimiento eliminado");
                exit();

            case 'editar_movimiento':
                $movimiento_id = $_POST['movimiento_id'] ?? null;
                $tipo = $_POST['tipo'] ?? '';
                $monto = (float)($_POST['monto'] ?? 0);
                $descripcion = $_POST['descripcion'] ?? '';

                if (!$movimiento_id || !$tipo || $monto <= 0) {
                    throw new Exception("Datos de movimiento incompletos.");
                }

                $queryUpdateMov = "UPDATE movimientos_caja SET 
                    tipo = :tipo, 
                    monto = :monto, 
                    descripcion = :descripcion 
                    WHERE id = :movimiento_id";
                $stmtUpdateMov = $pdo->prepare($queryUpdateMov);
                $stmtUpdateMov->bindParam(":tipo", $tipo);
                $stmtUpdateMov->bindParam(":monto", $monto);
                $stmtUpdateMov->bindParam(":descripcion", $descripcion);
                $stmtUpdateMov->bindParam(":movimiento_id", $movimiento_id, PDO::PARAM_INT);
                $stmtUpdateMov->execute();

                recalcCierre($pdo, $apertura_id);

                header("Location: /flujo_caja/vistas/editar_movimientos.php?status=success&mensaje=Movimiento editado");
                exit();

            case 'crear_movimiento':
                $tipo = $_POST['tipo'] ?? '';
                $monto = (float)($_POST['monto'] ?? 0);
                $descripcion = $_POST['descripcion'] ?? '';

                if (!$tipo || $monto <= 0) {
                    throw new Exception("Datos de movimiento incompletos.");
                }

                $queryInsertMov = "INSERT INTO movimientos_caja (apertura_id, tipo, monto, descripcion) 
                                   VALUES (:apertura_id, :tipo, :monto, :descripcion)";
                $stmtInsertMov = $pdo->prepare($queryInsertMov);
                $stmtInsertMov->bindParam(":apertura_id", $apertura_id, PDO::PARAM_INT);
                $stmtInsertMov->bindParam(":tipo", $tipo);
                $stmtInsertMov->bindParam(":monto", $monto);
                $stmtInsertMov->bindParam(":descripcion", $descripcion);
                $stmtInsertMov->execute();

                recalcCierre($pdo, $apertura_id);

                header("Location: /flujo_caja/vistas/editar_movimientos.php?status=success&mensaje=Movimiento creado");
                exit();

            default:
                throw new Exception("Acción no válida.");
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
