<?php require_once '../../includes/header.php'; ?>

<div class="container">
    <h2>Registro de Usuario</h2>
    
    <!-- Mostrar alertas si el controlador nos las mandó por la URL -->
    <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <!-- El formulario apunta al Controlador -->
    <form action="../../controllers/AuthController.php?accion=registro" method="POST">
        <div class="form-group">
            <label>Nombre Completo:</label>
            <input type="text" name="nombre" required>
        </div>
        <div class="form-group">
            <label>Correo:</label>
            <input type="email" name="email" required>
        </div>
        <div class="form-group">
            <label>Contraseña:</label>
            <input type="password" name="password" required>
        </div>
        <div class="form-group">
            <label>Rol:</label>
            <select name="rol">
                <option value="investigador">Investigador</option>
                <option value="admin">Administrador</option>
            </select>
        </div>
        <button type="submit" class="btn">Registrar</button>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>