<?php
session_start();

// Definir la URL base del proyecto si aún no está definida
if (!defined('BASE_URL')) {
    define('BASE_URL', '/flujo_caja/');
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: " . BASE_URL . "vistas/inicio_sesion.php");
    exit();
}
?>
<!-- Desktop Menu -->
<header class="header">
    <div class="logo">MERCE TIENDAS</div>
    <nav>
        <a href="<?= BASE_URL ?>vistas/panel_control.php" class="active">
            <i class="fas fa-home"></i> Inicio
        </a>
        <a href="<?= BASE_URL ?>vistas/flujo_caja.php">
            <i class="fa-solid fa-rotate"></i> FLUJO
        </a>
        <a href="<?= BASE_URL ?>vistas/cierre_caja.php">
            <i class="fa-solid fa-hand-holding-dollar"></i> CIERRE
        </a>
        <a href="<?= BASE_URL ?>vistas/reportes.php">
            <i class="fa-solid fa-right-from-bracket"></i> REPORTES
        </a>
        <a href="<?= BASE_URL ?>controladores/AutenticacionControlador.php?accion=cerrar_sesion">
            <i class="fa-solid fa-right-from-bracket"></i> SALIR
        </a>
    </nav>
</header>

<!-- Mobile Bottom Navigation -->
<nav class="mobile-nav">
    <a href="<?= BASE_URL ?>vistas/panel_control.php" class="active">
        <i class="fas fa-home"></i>
        <span>INICIO</span>
    </a>
    <a href="<?= BASE_URL ?>vistas/flujo_caja.php">
        <i class="fa-solid fa-rotate"></i>
        <span>FLUJO</span>
    </a>
    <a href="<?= BASE_URL ?>vistas/cierre_caja.php">
        <i class="fa-solid fa-hand-holding-dollar"></i>
        <span>CIERRE</span>
    </a>
    <a href="<?= BASE_URL ?>vistas/reportes.php">
        <i class="fa-solid fa-file-invoice-dollar"></i>
        <span>REPORTES</span>
    </a>
    <a href="<?= BASE_URL ?>controladores/AutenticacionControlador.php?accion=cerrar_sesion">
        <i class="fa-solid fa-right-from-bracket"></i>
        <span>SALIR</span>
    </a>
</nav>
