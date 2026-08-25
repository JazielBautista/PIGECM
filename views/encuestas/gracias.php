<?php
// views/encuestas/gracias.php
$puntos = (int)($_GET['puntos'] ?? 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Encuesta Completada</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body style="background-color: #f4f6f9;">
    <div class="container" style="max-width: 550px; text-align: center; margin-top: 80px;">
        <div style="background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
            <h2 style="color: #2e7d32;">¡Muchas Gracias!</h2>
            <p style="color: #666; margin: 15px 0 20px;">Tus respuestas han sido procesadas y almacenadas correctamente.</p>
            <div style="background: #e8f5e9; padding: 15px; border-radius: 6px; margin-bottom: 25px;">
                <strong>Puntaje Obtenido:</strong> <?= $puntos ?> puntos
            </div>
            <a href="../../index.php" class="btn" style="background-color: #0f2b48;">Volver al Inicio</a>
        </div>
    </div>
</body>
</html>