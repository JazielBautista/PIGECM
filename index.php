<?php
require_once 'config/conexion.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PIGECM - CentroGeo</title>
</head>
<body>
    <h1>PIGECM - CentroGeo 🚀</h1>
    <?php if (isset($pdo)): ?>
        <p style="color: green; font-weight: bold;">
            ¡Conexión a la base de datos MySQL establecida con éxito! 🎉
        </p>
    <?php endif; ?>
</body>
</html>