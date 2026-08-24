<?php
// index.php
session_start();

// Si el usuario ya inició sesión, mandarlo al dashboard del admin
if (isset($_SESSION['usuario_id'])) {
    header('Location: views/admin/dashboard.php');
} else {
    // Si no ha iniciado sesión, mandarlo al login
    header('Location: views/auth/login.php');
}
exit;
?>