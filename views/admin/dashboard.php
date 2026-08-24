<?php
// views/dashboard.php
require_once '../../config/conexion.php';
require_once '../../includes/header.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
?>

<div class="container">
    <h2>Panel de Control</h2>
    <p>Bienvenido, <strong><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></strong> (Rol: <em><?= htmlspecialchars($_SESSION['usuario_rol']) ?></em>).</p>
    <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">

    <div style="display: flex; gap: 20px; margin-top: 20px;">
        <div style="flex: 1; padding: 20px; background: #e3f2fd; border-radius: 6px;">
            <h3>Solicitudes de Espacio</h3>
            <p style="font-size: 0.9rem; margin: 10px 0;">Gestiona las solicitudes institucionales de nuevas encuestas.</p>
            <a href="#" class="btn" style="background: #0288d1;">Ver Solicitudes</a>
        </div>
        <div style="flex: 1; padding: 20px; background: #e8f5e9; border-radius: 6px;">
            <h3>Constructor de Cuestionarios</h3>
            <p style="font-size: 0.9rem; margin: 10px 0;">Crea y administra tus reactivos y encuestas modulares.</p>
            <a href="#" class="btn" style="background: #388e3c;">Crear Encuesta</a>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>