<?php
// views/auth/login.php
session_start();
require_once '../../config/conexion.php';

$pdo = Conexion::conectar();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Por favor ingresa tu correo y contraseña.';
    } else {
        $stmt = $pdo->prepare("SELECT id, nombre, email, password, rol FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($password, $usuario['password'])) {
            $_SESSION['usuario_id']     = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_rol']    = $usuario['rol'];

            header('Location: ../admin/dashboard.php');
            exit;
        } else {
            $error = 'Credenciales incorrectas.';
        }
    }
}

require_once '../../includes/header.php';
?>

<div class="container" style="max-width: 500px;">
    <h2>Iniciar Sesión</h2>
    <p style="margin-bottom: 20px; color: #666;">Ingresa tus credenciales para acceder a PIGECM.</p>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div class="form-group">
            <label for="email">Correo Electrónico:</label>
            <input type="email" name="email" id="email" required>
        </div>

        <div class="form-group">
            <label for="password">Contraseña:</label>
            <input type="password" name="password" id="password" required>
        </div>

        <button type="submit" class="btn" style="width: 100%;">Ingresar</button>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>