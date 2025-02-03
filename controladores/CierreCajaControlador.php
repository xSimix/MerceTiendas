<?php
session_start();
require_once '../configuracion/base_datos.php';

// Definir la URL base del proyecto para evitar problemas con rutas relativas
if (!defined('BASE_URL')) {
    define('BASE_URL', '/flujo_caja/');
}

try {
    // Validar que la solicitud sea POST y que se haya enviado la acción
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['accion'])) {
        throw new Exception("Solicitud no válida.");
    }
    
    $accion = $_POST['accion'];
    
    // Verificar que exista una apertura de caja activa en sesión
    if (empty($_SESSION['apertura_id'])) {
        throw new Exception("No hay una apertura de caja activa.");
    }
    $apertura_id = $_SESSION['apertura_id'];
    
    switch ($accion) {
        case 'eliminar_movimiento':
            $movimiento_id = $_POST['movimiento_id'] ?? null;
            if (empty($movimiento_id)) {
                throw new Exception("ID de movimiento no proporcionado.");
            }
            
            $queryDelete = "DELETE FROM movimientos_caja WHERE id = :movimiento_id";
            $stmtDelete = $pdo->prepare($queryDelete);
            $stmtDelete->bindParam(":movimiento_id", $movimiento_id, PDO::PARAM_INT);
            $stmtDelete->execute();
            
            header("Location: " . BASE_URL . "vistas/cierre_caja.php?status=success&mensaje=" . urlencode("Movimiento eliminado."));
            exit();
            
        case 'cerrar_caja':
            // Recoger y convertir los datos enviados
            $total_ventas_diarias = isset($_POST['total_ventas_diarias']) ? (float) $_POST['total_ventas_diarias'] : 0;
            $total_efectivo_caja  = isset($_POST['total_efectivo_caja'])  ? (float) $_POST['total_efectivo_caja']  : 0;
            $codigo_cierre        = $_POST['codigo_cierre'] ?? '';
            
            // Validar el código de cierre
            $queryCodigo = "SELECT codigo_cierre FROM configuracion WHERE id = 1";
            $stmtCodigo  = $pdo->query($queryCodigo);
            $config      = $stmtCodigo->fetch(PDO::FETCH_ASSOC);
            if (!$config || $codigo_cierre !== $config['codigo_cierre']) {
                throw new Exception("Código de cierre inválido.");
            }
            
            // Obtener la apertura de caja activa
            $queryApertura = "SELECT monto_apertura FROM apertura_caja WHERE id = :apertura_id AND estado = 'abierta'";
            $stmtApertura = $pdo->prepare($queryApertura);
            $stmtApertura->bindParam(':apertura_id', $apertura_id, PDO::PARAM_INT);
            $stmtApertura->execute();
            $apertura = $stmtApertura->fetch(PDO::FETCH_ASSOC);
            
            if (!$apertura) {
                throw new Exception("No se encontró una apertura de caja activa.");
            }
            
            $monto_apertura = (float) $apertura['monto_apertura'];
            
            // Calcular totales a partir de los movimientos de caja
            $total_ingresos = 0;
            $total_egresos  = 0;
            $queryMov = "SELECT tipo, monto FROM movimientos_caja WHERE apertura_id = :apertura_id";
            $stmtMov = $pdo->prepare($queryMov);
            $stmtMov->bindParam(':apertura_id', $apertura_id, PDO::PARAM_INT);
            $stmtMov->execute();
            $movimientos = $stmtMov->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($movimientos as $mov) {
                if ($mov['tipo'] === 'ingreso') {
                    $total_ingresos += (float) $mov['monto'];
                } elseif ($mov['tipo'] === 'egreso') {
                    $total_egresos += (float) $mov['monto'];
                }
            }
            
            // Calcular el total esperado y el arqueo
            $total_calculado = $monto_apertura + $total_ingresos + $total_ventas_diarias - $total_egresos;
            $arqueo          = $total_efectivo_caja - $total_calculado;
            
            // Insertar el registro del cierre en la tabla cierre_caja
            $queryCierre = "INSERT INTO cierre_caja 
                (apertura_id, total_ingresos, total_egresos, saldo_final, total_ventas_diarias, total_efectivo_caja, total_calculado, arqueo, fecha_cierre) 
                VALUES 
                (:apertura_id, :total_ingresos, :total_egresos, :saldo_final, :total_ventas_diarias, :total_efectivo_caja, :total_calculado, :arqueo, NOW())";
            $stmtCierre = $pdo->prepare($queryCierre);
            $stmtCierre->bindParam(':apertura_id',         $apertura_id,           PDO::PARAM_INT);
            $stmtCierre->bindParam(':total_ingresos',      $total_ingresos);
            $stmtCierre->bindParam(':total_egresos',       $total_egresos);
            $stmtCierre->bindParam(':saldo_final',         $total_efectivo_caja);
            $stmtCierre->bindParam(':total_ventas_diarias',$total_ventas_diarias);
            $stmtCierre->bindParam(':total_efectivo_caja', $total_efectivo_caja);
            $stmtCierre->bindParam(':total_calculado',     $total_calculado);
            $stmtCierre->bindParam(':arqueo',              $arqueo);
            $stmtCierre->execute();
            
            // Actualizar el estado de la apertura de caja a 'cerrada'
            $queryUpdateApertura = "UPDATE apertura_caja SET estado = 'cerrada' WHERE id = :apertura_id";
            $stmtUpdate = $pdo->prepare($queryUpdateApertura);
            $stmtUpdate->bindParam(':apertura_id', $apertura_id, PDO::PARAM_INT);
            $stmtUpdate->execute();
            
            // Eliminar la variable de sesión de apertura
            unset($_SESSION['apertura_id']);
            
            header("Location: " . BASE_URL . "vistas/inicio.php?status=success&mensaje=" . urlencode("Caja cerrada correctamente."));
            exit();
            
        default:
            throw new Exception("Acción no válida.");
    }
} catch (Exception $e) {
    header("Location: " . BASE_URL . "vistas/cierre_caja.php?status=error&mensaje=" . urlencode($e->getMessage()));
    exit();
}
