<?php
session_start();
require '../configuracion/base_datos.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: inicio_sesion.php");
    exit();
}

// Verificar si la caja ya fue aperturada
$query = "SELECT * FROM apertura_caja WHERE estado = 'abierta' AND usuario_id = :usuario_id LIMIT 1";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':usuario_id', $_SESSION['usuario_id']);
$stmt->execute();
$caja_abierta = $stmt->fetch(PDO::FETCH_ASSOC);

$mostrar_modal = !$caja_abierta; // Mostrar el modal si no hay caja abierta

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['monto_apertura'])) {
    $monto_apertura = $_POST['monto_apertura'];

    $query = "INSERT INTO apertura_caja (usuario_id, monto_apertura, estado, fecha_apertura) VALUES (:usuario_id, :monto_apertura, 'abierta', NOW())";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':usuario_id', $_SESSION['usuario_id']);
    $stmt->bindParam(':monto_apertura', $monto_apertura);

    if ($stmt->execute()) {
        // Refrescar la página para actualizar el estado
        header("Location: panel_control.php");
        exit();
    } else {
        $error = "Error al aperturar la caja. Intente nuevamente.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control</title>
    <link rel="stylesheet" href="../publico/css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <script>
        function abrirModal() {
            document.getElementById('modalApertura').style.display = 'block';
        }

        function cerrarModal() {
            document.getElementById('modalApertura').style.display = 'none';
        }
    </script>
</head>
<body>
    <?php include "menu.php"; ?>
    <h1>Bienvenido, <?= htmlspecialchars($_SESSION['nombre']) ?></h1>

    <?php if ($caja_abierta): ?>
        <p>El monto de apertura de caja actual es: S/<?= number_format($caja_abierta['monto_apertura'], 2) ?></p>
        <p>Fecha de Apertura: <?= htmlspecialchars($caja_abierta['fecha_apertura']) ?></p>
    <?php else: ?>
        <p>No hay caja aperturada. Ingrese un monto de apertura para continuar.</p>
    <?php endif; ?>

    <!-- Modal para Apertura de Caja -->
    <div id="modalApertura" class="modal" style="display: <?= $mostrar_modal ? 'block' : 'none' ?>;">
        <div class="modal-contenido">
            <h2>Apertura de Caja</h2>
            <form action="panel_control.php" method="POST">
                <label for="monto_apertura">Monto de Apertura:</label>
                <input type="number" name="monto_apertura" id="monto_apertura" step="0.01" required>
                <button type="submit">Aperturar Caja</button>
                <button type="button" onclick="cerrarModal()">Cancelar</button>
            </form>
            <?php if (isset($error)): ?>
                <p style="color: red;"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
