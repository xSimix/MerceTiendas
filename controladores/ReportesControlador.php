<?php
require_once "../configuracion/base_datos.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion'])) {
    if ($_POST['accion'] == 'generar_reporte') {
        // Recuperar filtros desde el formulario
        $fecha_inicio  = isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : '';
        $fecha_fin     = isset($_POST['fecha_fin']) ? $_POST['fecha_fin'] : '';
        $saldo_negativo = isset($_POST['saldo_negativo']) ? 1 : 0;
        
        // Redirigir a reportes.php pasando los parámetros por GET
        header("Location: ../vistas/reportes.php?fecha_inicio=" . urlencode($fecha_inicio) . "&fecha_fin=" . urlencode($fecha_fin) . "&saldo_negativo=" . $saldo_negativo);
        exit();
    }
}
?>
