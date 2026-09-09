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
$pdo = Conexion::conectar();

// Verificación de permisos para crear cuestionarios
$puede_crear = false;
if ($es_admin) {
    $puede_crear = true;
} else {
    $stmtSol = $pdo->prepare("SELECT COUNT(*) FROM solicitudes WHERE usuario_id = ? AND estado = 'aprobada'");
    $stmtSol->execute([$_SESSION['usuario_id']]);
    $puede_crear = ($stmtSol->fetchColumn() > 0);
}

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

    <!-- Formulario Condicional -->
    <?php if ($puede_crear): ?>
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
                    <label for="prefijo">Prefijo Institucional (Identificador):</label>
                    <input type="text" name="prefijo" id="prefijo" placeholder="Ej. saludgeo2026 (Sin espacios)" pattern="[a-zA-Z0-9_]+" title="Solo letras, números y guiones bajos, sin espacios." required>
                    <small style="color: #666; display:block; margin-top:5px;">Este nombre se usará para formar los folios de los encuestados (Ej. prefijo_sofia...).</small>
                </div>

                <button type="submit" class="btn" style="background-color: #2e7d32;">Registrar Encuesta</button>
            </form>
        </div>
    <?php else: ?>
        <div style="background: #fff8e1; border-left: 5px solid #ffa000; padding: 18px 22px; border-radius: 6px; margin-bottom: 30px;">
            <h4 style="margin: 0 0 8px; color: #8d6e00;">Creación de Cuestionarios Restringida</h4>
            <p style="margin: 0; color: #555; line-height: 1.5;">
                Para registrar un nuevo cuestionario, primero debes contar con un espacio de investigación autorizado. 
                Dirígete a <a href="solicitudes.php" style="color: #0277bd; font-weight: bold; text-decoration: underline;">Gestión de Solicitudes</a> para solicitarlo.
            </p>
        </div>
    <?php endif; ?>

    <!-- Tabla de cuestionarios registrados -->
    <h3>Cuestionarios Disponibles</h3>
    <table style="width: 100%; border-collapse: collapse; margin-top: 15px; background: white;">
        <thead>
            <tr style="background-color: #0f2b48; color: white; text-align: left;">
                <th style="padding: 10px; border: 1px solid #ddd;">Título</th>
                <?php if ($es_admin): ?><th style="padding: 10px; border: 1px solid #ddd;">Autor</th><?php endif; ?>
                <th style="padding: 10px; border: 1px solid #ddd;">Prefijo</th>
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
                        <td style="padding: 10px; border: 1px solid #ddd; font-family: monospace; font-size: 0.9rem; color: #0277bd; font-weight: bold;"><?= htmlspecialchars($c['prefijo'] ?? '') ?></td>
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
                            <a href="../builder/index.php?cuestionario_id=<?= $c['id'] ?>" class="btn" style="background-color: #1976d2; padding: 4px 8px; font-size: 0.8rem; margin-bottom: 4px; display: inline-block;">Editar</a>
                            <a href="resultados.php?id=<?= $c['id'] ?>" class="btn" style="background-color: #00796b; padding: 4px 8px; font-size: 0.8rem; color: white; text-decoration: none; border-radius: 4px; margin-bottom: 4px; display: inline-block;">Resultados</a>
                            <a href="../../controllers/CuestionarioController.php?accion=toggle_estado&id=<?= $c['id'] ?>&estado=<?= $c['activo'] ?>" class="btn" style="background-color: #f57c00; padding: 4px 8px; font-size: 0.8rem; display: inline-block;">
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