<?php
// views/admin/solicitudes.php
require_once '../../config/conexion.php';
require_once '../../models/Solicitud.php';
require_once '../../includes/header.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$es_admin = ($_SESSION['usuario_rol'] === 'admin');
$solicitudes = $es_admin ? Solicitud::obtenerTodas() : Solicitud::obtenerPorUsuario($_SESSION['usuario_id']);
?>

<div class="container" style="max-width: 1000px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Gestión de Solicitudes de Espacio</h2>
        <a href="dashboard.php" class="btn" style="background-color: #546e7a;">Volver al Panel</a>
    </div>

    <?php if (isset($_GET['exito'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['exito']) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <!-- Formulario para investigadores -->
    <?php if (!$es_admin): ?>
        <div style="background: #f8f9fa; border: 1px solid #e9ecef; padding: 20px; border-radius: 6px; margin-bottom: 30px;">
            <h3>Nueva Solicitud de Cuestionario</h3>
            <p style="font-size: 0.9rem; color: #666; margin-bottom: 15px;">Envía los datos de tu área para habilitar una nueva batería de encuestas.</p>
            
            <form action="../../controllers/SolicitudController.php?accion=crear" method="POST">
                <div class="form-group">
                    <label for="nombre_espacio">Nombre del Espacio / Proyecto:</label>
                    <input type="text" name="nombre_espacio" id="nombre_espacio" placeholder="Ej. Evaluación de Movilidad Urbana 2026" required>
                </div>
                <div class="form-group">
                    <label for="justificacion">Justificación Institucional:</label>
                    <textarea name="justificacion" id="justificacion" rows="3" placeholder="Describe los objetivos y la población objetivo..." required></textarea>
                </div>
                <button type="submit" class="btn" style="background-color: #2e7d32;">Enviar Solicitud</button>
            </form>
        </div>
    <?php endif; ?>

    <!-- Listado de solicitudes -->
    <h3><?= $es_admin ? 'Todas las Solicitudes Recibidas' : 'Mis Solicitudes' ?></h3>
    <table style="width: 100%; border-collapse: collapse; margin-top: 15px; background: white;">
        <thead>
            <tr style="background-color: #0f2b48; color: white; text-align: left;">
                <th style="padding: 10px; border: 1px solid #ddd;">Espacio / Proyecto</th>
                <?php if ($es_admin): ?><th style="padding: 10px; border: 1px solid #ddd;">Solicitante</th><?php endif; ?>
                <th style="padding: 10px; border: 1px solid #ddd;">Justificación</th>
                <th style="padding: 10px; border: 1px solid #ddd;">Estado</th>
                <th style="padding: 10px; border: 1px solid #ddd;">Fecha</th>
                <?php if ($es_admin): ?><th style="padding: 10px; border: 1px solid #ddd;">Acciones</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($solicitudes)): ?>
                <tr><td colspan="<?= $es_admin ? '6' : '4' ?>" style="padding: 15px; text-align: center; color: #777;">No hay solicitudes registradas.</td></tr>
            <?php else: ?>
                <?php foreach ($solicitudes as $s): ?>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;"><?= htmlspecialchars($s['nombre_espacio']) ?></td>
                        <?php if ($es_admin): ?>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($s['solicitante']) ?><br><small style="color:#777;"><?= htmlspecialchars($s['email']) ?></small></td>
                        <?php endif; ?>
                        <td style="padding: 10px; border: 1px solid #ddd;"><?= nl2br(htmlspecialchars($s['justificacion'])) ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">
                            <?php 
                                $colores = ['pendiente' => '#f57c00', 'aprobada' => '#388e3c', 'rechazada' => '#d32f2f'];
                                $color = $colores[$s['estado']] ?? '#777';
                            ?>
                            <span style="background-color: <?= $color ?>; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; text-transform: uppercase;">
                                <?= htmlspecialchars($s['estado']) ?>
                            </span>
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd; font-size: 0.85rem; color: #555;"><?= $s['fecha_solicitud'] ?></td>
                        <?php if ($es_admin): ?>
                            <td style="padding: 10px; border: 1px solid #ddd;">
                                <?php if ($s['estado'] === 'pendiente'): ?>
                                    <a href="../../controllers/SolicitudController.php?accion=cambiar_estado&id=<?= $s['id'] ?>&estado=aprobada" class="btn" style="background-color: #388e3c; padding: 5px 10px; font-size: 0.8rem;">Aprobar</a>
                                    <a href="../../controllers/SolicitudController.php?accion=cambiar_estado&id=<?= $s['id'] ?>&estado=rechazada" class="btn" style="background-color: #d32f2f; padding: 5px 10px; font-size: 0.8rem;" onclick="return confirm('¿Seguro que deseas rechazar esta solicitud?');">Rechazar</a>
                                <?php else: ?>
                                    <span style="color: #999; font-size: 0.85rem;">Completado</span>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once '../../includes/footer.php'; ?>