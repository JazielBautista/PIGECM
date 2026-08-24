<?php
// controllers/AuthController.php
session_start();
require_once '../models/Usuario.php';

// Si la acción es registrar un usuario
if (isset($_GET['accion']) && $_GET['accion'] == 'registro') {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $rol = $_POST['rol'];

    // Usamos el Modelo para checar si existe
    $existe = Usuario::buscarPorEmail($email);

    if ($existe) {
        header("Location: ../views/auth/registro.php?error=El correo ya existe");
    } else {
        Usuario::registrar($nombre, $email, $password, $rol);
        header("Location: ../views/auth/login.php?exito=Cuenta creada");
    }
}
?>