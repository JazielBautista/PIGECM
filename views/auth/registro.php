<?php require_once '../../includes/header.php'; ?>

<div class="container" style="max-width: 500px; margin-top: 50px;">
    <h2>Registro de Investigador</h2>
    
    <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <form action="../../controllers/AuthController.php?accion=registro" method="POST">
        <div class="form-group">
            <label>Nombre Completo:</label>
            <input type="text" name="nombre" required>
        </div>
        <div class="form-group">
            <label>Correo Electrónico:</label>
            <input type="email" name="email" required>
        </div>
        <div class="form-group">
            <label>Contraseña:</label>
            <input type="password" name="password" required>
        </div>
        
        <!-- Rol forzado internamente sin que el usuario pueda cambiarlo -->
        <input type="hidden" name="rol" value="investigador">
        
        <button type="submit" class="btn" style="background-color: #0f2b48; color: white; width: 100%;">Crear Cuenta</button>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>