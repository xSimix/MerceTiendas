<?php
class Usuario {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function obtenerPorUsername($username) {
        $query = "SELECT * FROM usuarios WHERE username = :username";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function registrarUsuario($nombre, $username, $password, $rol) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO usuarios (nombre, username, password, rol) VALUES (:nombre, :username, :password, :rol)";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(":nombre", $nombre);
        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":password", $hashedPassword);
        $stmt->bindParam(":rol", $rol);
        return $stmt->execute();
    }
}
?>
