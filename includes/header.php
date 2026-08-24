<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PIGECM - CentroGeo</title>
    <link rel="stylesheet" href="/pigecm/assets/css/style.css">
</head>
<body>
    <header>
        <h2>PIGECM | CentroGeo</h2>
        <nav>
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <a href="/pigecm/views/dashboard.php">Panel Principal</a>
                <a href="/pigecm/views/logout.php" style="color: #ff8a80;">Cerrar Sesión (<?= htmlspecialchars($_SESSION['usuario_nombre']) ?>)</a>
            <?php else: ?>
                <a href="/pigecm/views/login.php">Iniciar Sesión</a>
                <a href="/pigecm/views/registro.php">Registrarse</a>
            <?php endif; ?>
        </nav>
    </header>