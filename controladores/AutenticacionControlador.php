<?php
session_start();
require_once "../configuracion/base_datos.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion'])) {
    if ($_POST['accion'] == 'iniciar_sesion') {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $query = "SELECT * FROM usuarios WHERE username = :username";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($password, $usuario['password'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['nombre'] = $usuario['nombre'];
            header("Location: ../vistas/panel_control.php");
            exit();
        } else {
            echo "Usuario o contraseña incorrectos.";
        }        
    } elseif ($_POST['accion'] == 'registrar') {
        $nombre = $_POST['nombre'];
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $query = "INSERT INTO usuarios (nombre, username, password) VALUES (:nombre, :username, :password)";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(":nombre", $nombre);
        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":password", $password);

        if ($stmt->execute()) {
            echo "Usuario registrado correctamente. <a href='../vistas/inicio_sesion.php'>Iniciar sesión</a>";
        } else {
            echo "Error al registrar el usuario.";
        }
    }
} elseif (isset($_GET['accion']) && $_GET['accion'] == 'cerrar_sesion') {
    session_destroy();
    header("Location: ../vistas/inicio_sesion.php");
    exit();
}
?>
