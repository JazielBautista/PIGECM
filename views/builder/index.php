<?php
// views/builder/index.php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once '../../config/conexion.php';
require_once '../../models/Cuestionario.php';
require_once '../../models/Instrumento.php';
require_once '../../includes/header.php';

$cuestionario_id = (int)($_GET['cuestionario_id'] ?? 0);
$cuestionario = Cuestionario::obtenerPorId($cuestionario_id);

if (!$cuestionario) {
    die("<div class='container'><div class='alert alert-danger'>Proyecto no encontrado.</div></div>");
}

$instrumentos = Instrumento::obtenerTodos();
$seleccionados = Instrumento::obtenerInstrumentosProyecto($cuestionario_id);
$preguntas_grales_seleccionadas = Instrumento::obtenerPreguntasGeneralesProyecto($cuestionario_id);
?>

<div class="container" style="max-width: 900px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h2>Configuración: <?= htmlspecialchars($cuestionario['titulo']) ?></h2>
            <p style="color: #666;">Selecciona los instrumentos científicos que deseas incluir en este enlace.</p>
        </div>
        <a href="../admin/cuestionarios.php" class="btn" style="background-color: #546e7a;">Volver</a>
    </div>

    <?php if (isset($_GET['exito'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['exito']) ?></div>
    <?php endif; ?>

    <form action="../../controllers/CuestionarioController.php?accion=guardar_ensamble" method="POST">
        <input type="hidden" name="cuestionario_id" value="<?= $cuestionario['id'] ?>">

        <div style="background: white; border: 1px solid #ddd; padding: 25px; border-radius: 6px;">
            <h3>Catálogo de Instrumentos</h3>
            <hr style="border:0; border-top: 1px solid #eee; margin-bottom: 20px;">

            <?php foreach ($instrumentos as $inst): ?>
                <?php $marcado = in_array($inst['id'], $seleccionados) ? 'checked' : ''; ?>
                
                <div style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px; border-left: 4px solid #0288d1;">
                    <label style="font-weight: bold; font-size: 1.1rem; display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" name="instrumentos[]" value="<?= $inst['id'] ?>" <?= $marcado ?> style="width: 20px; height: 20px;">
                        <?= htmlspecialchars($inst['nombre']) ?>
                    </label>
                    <p style="margin: 5px 0 0 30px; font-size: 0.9rem; color: #555;"><?= htmlspecialchars($inst['descripcion']) ?></p>

                    <!-- Lógica especial para el módulo de Datos Generales -->
                    <?php if ($inst['permite_seleccion_preguntas']): ?>
                        <?php $preguntas = Instrumento::obtenerPreguntasBase($inst['id']); ?>
                        <div style="margin: 15px 0 0 30px; padding: 15px; background: white; border: 1px dashed #ccc; border-radius: 4px;">
                            <p style="font-weight: bold; margin-bottom: 10px; font-size: 0.9rem;">Selecciona los campos específicos a solicitar:</p>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <?php foreach ($preguntas as $p): ?>
                                    <?php $p_marcada = in_array($p['id'], $preguntas_grales_seleccionadas) ? 'checked' : ''; ?>
                                    <label style="font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                        <input type="checkbox" name="preguntas_grales[]" value="<?= $p['id'] ?>" <?= $p_marcada ?>>
                                        <?= htmlspecialchars($p['texto_pregunta']) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <button type="submit" class="btn" style="background-color: #2e7d32; width: 100%; font-size: 1.1rem; padding: 12px; margin-top: 10px;">
                Guardar Configuración de Encuesta
            </button>
        </div>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>