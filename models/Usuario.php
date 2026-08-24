<?php
// models/Usuario.php
require_once '../config/conexion.php';

class Usuario {
    // Función para buscar si un correo existe
    public static function buscarPorEmail($email) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Función para guardar un nuevo usuario
    public static function registrar($nombre, $email, $password, $rol) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$nombre, $email, $password, $rol]);
    }
}
?>