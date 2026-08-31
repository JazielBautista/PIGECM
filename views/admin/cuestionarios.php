<?php
// views/admin/cuestionarios.php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once '../../config/conexion.php';
require_once '../../models/Cuestionario.php';
require_once '../../includes/header.php';

$es_admin = ($_SESSION['usuario_rol'] === 'admin');
$cuestionarios = $es_admin ? Cuestionario::obtenerTodos() : Cuestionario::obtenerPorUsuario($_SESSION['usuario_id']);
?>

<div class="container" style="max-width: 1050px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Gestión de Cuestionarios y Encuestas</h2>
        <a href="dashboard.php" class="btn" style="background-color: #546e7a;">Volver al Panel</a>
    </div>

    <?php if (isset($_GET['exito'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['exito']) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <!-- Formulario para dar de alta nuevo Cuestionario -->
    <div style="background: #f8f9fa; border: 1px solid #e9ecef; padding: 20px; border-radius: 6px; margin-bottom: 30px;">
        <h3>Crear Nueva Encuesta / Cuestionario</h3>
        <form action="../../controllers/CuestionarioController.php?accion=crear" method="POST" style="margin-top: 15px;">
            <div class="form-group">
                <label for="titulo">Título de la Encuesta:</label>
                <input type="text" name="titulo" id="titulo" placeholder="Ej. Encuesta de Percepción de Seguridad 2026" required>
            </div>
            
            <div class="form-group">
                <label for="descripcion">Descripción / Instrucciones:</label>
                <textarea name="descripcion" id="descripcion" rows="2" placeholder="Breve explicación para los encuestados..."></textarea>
            </div>

            <div class="form-group">
                <label for="tipo">Tipo de Cuestionario:</label>
                <select name="tipo" id="tipo">
                    <option value="informativo">Informativo / Diagnóstico General</option>
                    <option value="evaluativo">Evaluativo (Con Ponderación y Puntajes Médicos)</option>
                </select>
            </div>

            <button type="submit" class="btn" style="background-color: #2e7d32;">Registrar Encuesta</button>
        </form>
    </div>

    <!-- Tabla de cuestionarios registrados -->
    <h3>Cuestionarios Disponibles</h3>
    <table style="width: 100%; border-collapse: collapse; margin-top: 15px; background: white;">
        <thead>
            <tr style="background-color: #0f2b48; color: white; text-align: left;">
                <th style="padding: 10px; border: 1px solid #ddd;">Título</th>
                <?php if ($es_admin): ?><th style="padding: 10px; border: 1px solid #ddd;">Autor</th><?php endif; ?>
                <th style="padding: 10px; border: 1px solid #ddd;">Tipo</th>
                <th style="padding: 10px; border: 1px solid #ddd;">URL Pública (Slug)</th>
                <th style="padding: 10px; border: 1px solid #ddd;">Estado</th>
                <th style="padding: 10px; border: 1px solid #ddd;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($cuestionarios)): ?>
                <tr><td colspan="<?= $es_admin ? '6' : '5' ?>" style="padding: 15px; text-align: center; color: #777;">No hay cuestionarios registrados aún.</td></tr>
            <?php else: ?>
                <?php foreach ($cuestionarios as $c): ?>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;"><?= htmlspecialchars($c['titulo']) ?></td>
                        <?php if ($es_admin): ?>
                            <td style="padding: 10px; border: 1px solid #ddd; font-size: 0.9rem;"><?= htmlspecialchars($c['autor']) ?></td>
                        <?php endif; ?>
                        <td style="padding: 10px; border: 1px solid #ddd; text-transform: capitalize; font-size: 0.9rem;"><?= htmlspecialchars($c['tipo']) ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd; font-family: monospace; font-size: 0.85rem;">
                            <a href="../encuestas/responder.php?slug=<?= htmlspecialchars($c['url_slug']) ?>" target="_blank" style="color: #0288d1; font-weight: bold; text-decoration: underline;">
                                /responder.php?slug=<?= htmlspecialchars($c['url_slug']) ?>
                            </a>
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd;">
                            <span style="background-color: <?= $c['activo'] ? '#388e3c' : '#d32f2f' ?>; color: white; padding: 3px 7px; border-radius: 4px; font-size: 0.8rem;">
                                <?= $c['activo'] ? 'Activo' : 'Pausado' ?>
                            </span>
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd;">
                            <a href="../builder/index.php?cuestionario_id=<?= $c['id'] ?>" class="btn" style="background-color: #1976d2; padding: 4px 8px; font-size: 0.8rem;">Diseñar Preguntas</a>
                            <a href="../../controllers/CuestionarioController.php?accion=toggle_estado&id=<?= $c['id'] ?>&estado=<?= $c['activo'] ?>" class="btn" style="background-color: #f57c00; padding: 4px 8px; font-size: 0.8rem;">
                                <?= $c['activo'] ? 'Pausar' : 'Activar' ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once '../../includes/footer.php'; ?>