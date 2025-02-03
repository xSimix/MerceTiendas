<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: inicio_sesion.php");
    exit();
}
?>
    <!-- Desktop Menu -->
    <header class="header">
        <div class="logo">MERCE TIENDAS</div>
        <nav>
            <a href="panel_control.php" class="active"><i class="fas fa-home"></i> Inicio</a>
            <a href="flujo_caja.php"><i class="fa-solid fa-rotate"></i> FLUJO</a>
            <a href="cierre_caja.php"><i class="fa-solid fa-hand-holding-dollar"></i> CIERRE</a>
            <a href="reportes.php"><i class="fa-solid fa-right-from-bracket"></i> REPORTES</a>
            <a href="../controladores/AutenticacionControlador.php?accion=cerrar_sesion"><i class="fa-solid fa-right-from-bracket"></i> SALIR</a>
        </nav>
    </header>

    <!-- Mobile Bottom Navigation -->
    <nav class="mobile-nav">
        <a href="panel_control.php" class="active">
            <i class="fas fa-home"></i>
            <span>INICIO</span>
        </a>
        <a href="flujo_caja.php">
            <i class="fa-solid fa-rotate"></i>
            <span>FLUJO</span>
        </a>
        <a href="cierre_caja.php">
            <i class="fa-solid fa-hand-holding-dollar"></i>
            <span>CIERRE</span>
        </a>
        <a href="reportes.php">
            <i class="fa-solid fa-file-invoice-dollar"></i>
            <span>REPORTES</span>
        </a>
        <a href="../controladores/AutenticacionControlador.php?accion=cerrar_sesion">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>SALIR</span>
        </a>
    </nav>