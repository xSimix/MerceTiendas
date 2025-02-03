<?php
session_start();
if (isset($_SESSION['usuario_id'])) {
    header("Location: vistas/panel_control.php");
    exit();
} else {
    header("Location: vistas/inicio_sesion.php");
    exit();
}
?>
