<?php
session_start();
require_once "../configuracion/base_datos.php";

/**
 * Recalcula y actualiza los totales del cierre de caja en función de los movimientos asociados.
 * Se utiliza para cuando se agregan, editan o eliminan movimientos.
 *
 * @param PDO $pdo
 * @param int $apertura_id
 * @throws Exception
 */
function recalcCierre($pdo, $apertura_id) {
    // Obtener el ID del cierre asociado a la apertura
    $queryCierre = "SELECT id FROM cierre_caja WHERE apertura_id = :apertura_id";
    $stmtCierre = $pdo->prepare($queryCierre);
    $stmtCierre->bindParam(":apertura_id", $apertura_id, PDO::PARAM_INT);
    $stmtCierre->execute();
    $cierre = $stmtCierre->fetch(PDO::FETCH_ASSOC);
    
    if (!$cierre) {
        // No existe cierre: no hay nada que recalcular.
        return;
    }
    $cierre_id = (int)$cierre['id'];
    
    // Obtener el monto de apertura
    $queryApertura = "SELECT monto_apertura FROM apertura_caja WHERE id = :apertura_id";
    $stmtApertura = $pdo->prepare($queryApertura);
    $stmtApertura->bindParam(":apertura_id", $apertura_id, PDO::PARAM_INT);
    $stmtApertura->execute();
    $apertura = $stmtApertura->fetch(PDO::FETCH_ASSOC);
    if (!$apertura) {
        throw new Exception("No se encontró la apertura de caja.");
    }
    $monto_apertura = (float)$apertura['monto_apertura'];
    
    // Sumar ingresos y egresos de los movimientos
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
    
    // Obtener los valores actuales de ventas diarias y efectivo ya registrados en el cierre.
    $queryCierreValores = "SELECT total_ventas_diarias, total_efectivo_caja FROM cierre_caja WHERE id = :cierre_id";
    $stmtCierreValores = $pdo->prepare($queryCierreValores);
    $stmtCierreValores->bindParam(":cierre_id", $cierre_id, PDO::PARAM_INT);
    $stmtCierreValores->execute();
    $valoresCierre = $stmtCierreValores->fetch(PDO::FETCH_ASSOC);
    if (!$valoresCierre) {
        throw new Exception("No se encontraron valores para el cierre de caja.");
    }
    $total_ventas_diarias = (float)$valoresCierre['total_ventas_diarias'];
    $total_efectivo_caja = (float)$valoresCierre['total_efectivo_caja'];
    
    // Calcular el total calculado y el arqueo.
    $total_calculado = $monto_apertura + $total_ingresos + $total_ventas_diarias - $total_egresos;
    $arqueo = $total_efectivo_caja - $total_calculado;
    
    // Actualizar el cierre con los nuevos valores.
    $queryUpdate = "UPDATE cierre_caja SET 
        total_ingresos = :total_ingresos, 
        total_egresos = :total_egresos, 
        saldo_final = :saldo_final, 
        total_calculado = :total_calculado, 
        arqueo = :arqueo 
        WHERE id = :cierre_id";
    $stmtUpdate = $pdo->prepare($queryUpdate);
    $stmtUpdate->bindParam(":total_ingresos", $total_ingresos);
    $stmtUpdate->bindParam(":total_egresos", $total_egresos);
    $stmtUpdate->bindParam(":saldo_final", $total_calculado);
    $stmtUpdate->bindParam(":total_calculado", $total_calculado);
    $stmtUpdate->bindParam(":arqueo", $arqueo);
    $stmtUpdate->bindParam(":cierre_id", $cierre_id, PDO::PARAM_INT);
    $stmtUpdate->execute();
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['accion'])) {
        throw new Exception("Solicitud no válida.");
    }
    $accion = $_POST['accion'];
    $apertura_id = isset($_POST['apertura_id']) ? (int)$_POST['apertura_id'] : ($_SESSION['apertura_id'] ?? null);
    if (!$apertura_id) {
        throw new Exception("No se encontró el ID de apertura.");
    }
    
    switch ($accion) {
        case 'eliminar_movimiento':
            $movimiento_id = isset($_POST['movimiento_id']) ? (int)$_POST['movimiento_id'] : null;
            if (!$movimiento_id) {
                throw new Exception("ID de movimiento no proporcionado.");
            }
            $queryDelete = "DELETE FROM movimientos_caja WHERE id = :movimiento_id";
            $stmtDelete = $pdo->prepare($queryDelete);
            $stmtDelete->bindParam(":movimiento_id", $movimiento_id, PDO::PARAM_INT);
            $stmtDelete->execute();
            
            recalcCierre($pdo, $apertura_id);
            header("Location: /flujo_caja/vistas/detalle_cierre.php?id=" . $_POST['cierre_id'] . "&status=success&mensaje=Movimiento eliminado");
            exit();
            
        case 'crear_movimiento_detalle':
            $tipo = $_POST['tipo'] ?? '';
            $metodo_pago = $_POST['metodo_pago'] ?? '';
            $metodo_pago_destino = $_POST['metodo_pago_destino'] ?? '';
            $monto = isset($_POST['monto']) ? (float)$_POST['monto'] : 0;
            $descripcion = $_POST['descripcion'] ?? '';
            
            $queryInsert = "INSERT INTO movimientos_caja 
                (apertura_id, tipo, metodo_pago, metodo_pago_destino, monto, descripcion, fecha_movimiento) 
                VALUES (:apertura_id, :tipo, :metodo_pago, :metodo_pago_destino, :monto, :descripcion, NOW())";
            $stmtInsert = $pdo->prepare($queryInsert);
            $stmtInsert->bindParam(":apertura_id", $apertura_id, PDO::PARAM_INT);
            $stmtInsert->bindParam(":tipo", $tipo);
            $stmtInsert->bindParam(":metodo_pago", $metodo_pago);
            $stmtInsert->bindParam(":metodo_pago_destino", $metodo_pago_destino);
            $stmtInsert->bindParam(":monto", $monto);
            $stmtInsert->bindParam(":descripcion", $descripcion);
            $stmtInsert->execute();
            
            recalcCierre($pdo, $apertura_id);
            
            $redirect = $_POST['redirect'] ?? '';
            if ($redirect === 'detalle') {
                header("Location: /flujo_caja/vistas/detalle_cierre.php?id=" . $_POST['cierre_id'] . "&status=success&mensaje=Movimiento creado");
            } else {
                header("Location: /flujo_caja/vistas/cierre_caja.php?status=success&mensaje=Movimiento creado");
            }
            exit();
            
        case 'editar_movimiento':
            $movimiento_id = isset($_POST['movimiento_id']) ? (int)$_POST['movimiento_id'] : null;
            if (!$movimiento_id) {
                throw new Exception("ID de movimiento no proporcionado para edición.");
            }
            $tipo = $_POST['tipo'] ?? '';
            $metodo_pago = $_POST['metodo_pago'] ?? '';
            $metodo_pago_destino = $_POST['metodo_pago_destino'] ?? '';
            $monto = isset($_POST['monto']) ? (float)$_POST['monto'] : 0;
            $descripcion = $_POST['descripcion'] ?? '';
            
            $queryUpdateMov = "UPDATE movimientos_caja SET 
                tipo = :tipo, 
                metodo_pago = :metodo_pago, 
                metodo_pago_destino = :metodo_pago_destino, 
                monto = :monto, 
                descripcion = :descripcion, 
                fecha_movimiento = NOW() 
                WHERE id = :movimiento_id";
            $stmtUpdateMov = $pdo->prepare($queryUpdateMov);
            $stmtUpdateMov->bindParam(":tipo", $tipo);
            $stmtUpdateMov->bindParam(":metodo_pago", $metodo_pago);
            $stmtUpdateMov->bindParam(":metodo_pago_destino", $metodo_pago_destino);
            $stmtUpdateMov->bindParam(":monto", $monto);
            $stmtUpdateMov->bindParam(":descripcion", $descripcion);
            $stmtUpdateMov->bindParam(":movimiento_id", $movimiento_id, PDO::PARAM_INT);
            $stmtUpdateMov->execute();
            
            recalcCierre($pdo, $apertura_id);
            header("Location: /flujo_caja/vistas/detalle_cierre.php?id=" . $_POST['cierre_id'] . "&status=success&mensaje=Movimiento actualizado");
            exit();
            
        case 'editar_cierre':
            // Usamos el campo "cierre_id" de forma consistente
            $cierre_id = isset($_POST['cierre_id']) ? (int)$_POST['cierre_id'] : null;
            if (!$cierre_id) {
                throw new Exception("ID de cierre no proporcionado para edición.");
            }
            $total_ingresos = isset($_POST['total_ingresos']) ? (float)$_POST['total_ingresos'] : 0;
            $total_egresos = isset($_POST['total_egresos']) ? (float)$_POST['total_egresos'] : 0;
            $saldo_final = isset($_POST['saldo_final']) ? (float)$_POST['saldo_final'] : 0;
            $total_ventas_diarias = isset($_POST['total_ventas_diarias']) ? (float)$_POST['total_ventas_diarias'] : 0;
            $total_efectivo_caja = isset($_POST['total_efectivo_caja']) ? (float)$_POST['total_efectivo_caja'] : 0;
            $total_calculado = isset($_POST['total_calculado']) ? (float)$_POST['total_calculado'] : 0;
            $arqueo = isset($_POST['arqueo']) ? (float)$_POST['arqueo'] : 0;
            
            $queryUpdateCierreManual = "UPDATE cierre_caja SET 
                total_ingresos = :total_ingresos, 
                total_egresos = :total_egresos, 
                saldo_final = :saldo_final, 
                total_ventas_diarias = :total_ventas_diarias, 
                total_efectivo_caja = :total_efectivo_caja, 
                total_calculado = :total_calculado, 
                arqueo = :arqueo 
                WHERE id = :cierre_id";
            $stmtUpdateCierreManual = $pdo->prepare($queryUpdateCierreManual);
            $stmtUpdateCierreManual->bindParam(":total_ingresos", $total_ingresos);
            $stmtUpdateCierreManual->bindParam(":total_egresos", $total_egresos);
            $stmtUpdateCierreManual->bindParam(":saldo_final", $saldo_final);
            $stmtUpdateCierreManual->bindParam(":total_ventas_diarias", $total_ventas_diarias);
            $stmtUpdateCierreManual->bindParam(":total_efectivo_caja", $total_efectivo_caja);
            $stmtUpdateCierreManual->bindParam(":total_calculado", $total_calculado);
            $stmtUpdateCierreManual->bindParam(":arqueo", $arqueo);
            $stmtUpdateCierreManual->bindParam(":cierre_id", $cierre_id, PDO::PARAM_INT);
            $stmtUpdateCierreManual->execute();
            
            // Si se envía vía AJAX, retornar JSON con los valores actualizados.
            if (isset($_POST['ajax']) && $_POST['ajax'] === 'true') {
                $queryGet = "SELECT total_ingresos, total_egresos, saldo_final, total_ventas_diarias, total_efectivo_caja, total_calculado, arqueo 
                             FROM cierre_caja WHERE id = :cierre_id";
                $stmtGet = $pdo->prepare($queryGet);
                $stmtGet->bindParam(":cierre_id", $cierre_id, PDO::PARAM_INT);
                $stmtGet->execute();
                $updated = $stmtGet->fetch(PDO::FETCH_ASSOC);
                header("Content-Type: application/json");
                echo json_encode([
                    "status" => "success",
                    "mensaje" => "Detalles del cierre actualizados",
                    "data" => $updated
                ]);
                exit();
            } else {
                header("Location: /flujo_caja/vistas/detalle_cierre.php?id=" . $cierre_id . "&status=success&mensaje=Detalles del cierre actualizados");
                exit();
            }
            
        default:
            throw new Exception("Acción no válida.");
    }
} catch (Exception $e) {
    // Si es una petición AJAX se retorna JSON con el error
    if (isset($_POST['ajax']) && $_POST['ajax'] === 'true') {
        header("Content-Type: application/json");
        echo json_encode([
            "status" => "error",
            "mensaje" => $e->getMessage()
        ]);
    } else {
        echo "Error: " . $e->getMessage();
    }
    exit();
}
?>
