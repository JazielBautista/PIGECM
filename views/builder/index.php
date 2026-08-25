<?php
// views/builder/index.php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once '../../config/conexion.php';
require_once '../../models/Cuestionario.php';
require_once '../../models/Pregunta.php';
require_once '../../includes/header.php';

$cuestionario_id = (int)($_GET['cuestionario_id'] ?? 0);
$cuestionario = Cuestionario::obtenerPorId($cuestionario_id);

if (!$cuestionario) {
    echo "<div class='container'><div class='alert alert-danger'>Cuestionario no encontrado.</div></div>";
    require_once '../../includes/footer.php';
    exit;
}

$preguntas = Pregunta::obtenerPorCuestionario($cuestionario_id);
?>

<div class="container" style="max-width: 950px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h2><?= htmlspecialchars($cuestionario['titulo']) ?></h2>
            <p style="color: #666; font-size: 0.9rem;">Tipo: <strong><?= ucfirst($cuestionario['tipo']) ?></strong> | Slug: <code>/<?= htmlspecialchars($cuestionario['url_slug']) ?></code></p>
        </div>
        <a href="../admin/cuestionarios.php" class="btn" style="background-color: #546e7a;">Volver a Cuestionarios</a>
    </div>

    <?php if (isset($_GET['exito'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['exito']) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <!-- Formulario para agregar reactivo -->
    <div style="background: #fdfdfd; border: 1px solid #ccd0d4; padding: 20px; border-radius: 6px; margin-bottom: 30px;">
        <h3>Agregar Nueva Pregunta</h3>
        <form action="../../controllers/CuestionarioController.php?accion=guardar_pregunta" method="POST" style="margin-top: 15px;">
            <input type="hidden" name="cuestionario_id" value="<?= $cuestionario['id'] ?>">

            <div class="form-group">
                <label for="texto_pregunta">Texto de la Pregunta / Enunciado:</label>
                <input type="text" name="texto_pregunta" id="texto_pregunta" placeholder="Ej. ¿Con qué frecuencia utiliza el transporte público?" required>
            </div>

            <div class="form-group">
                <label for="tipo_reactivo">Tipo de Respuesta:</label>
                <select name="tipo_reactivo" id="tipo_reactivo" onchange="toggleOpciones(this.value)">
                    <option value="opcion_multiple">Opción Múltiple (Radio)</option>
                    <option value="escala_likert">Escala Likert / Valoración</option>
                    <option value="texto_abierto">Texto Abierto (Cualitativa)</option>
                </select>
            </div>

            <!-- Contenedor dinámico de opciones -->
            <div id="contenedor_opciones" style="margin-top: 15px; padding: 15px; background: #f0f4f8; border-radius: 4px;">
                <label style="font-weight: bold; margin-bottom: 10px; display: block;">Opciones de Respuesta y Puntos:</label>
                
                <div id="lista_opciones">
                    <div style="display: flex; gap: 10px; margin-bottom: 8px;">
                        <input type="text" name="opciones[]" placeholder="Opción 1" style="flex: 3;">
                        <input type="number" name="puntos[]" placeholder="Puntos (ej. 0)" value="0" style="flex: 1;">
                    </div>
                    <div style="display: flex; gap: 10px; margin-bottom: 8px;">
                        <input type="text" name="opciones[]" placeholder="Opción 2" style="flex: 3;">
                        <input type="number" name="puntos[]" placeholder="Puntos (ej. 5)" value="0" style="flex: 1;">
                    </div>
                </div>

                <button type="button" class="btn" style="background-color: #0288d1; padding: 5px 10px; font-size: 0.85rem; margin-top: 5px;" onclick="agregarFilaOpcion()">+ Agregar Otra Opción</button>
            </div>

            <button type="submit" class="btn" style="background-color: #2e7d32; margin-top: 15px;">Guardar Pregunta</button>
        </form>
    </div>

    <!-- Listado de reactivos actuales -->
    <h3>Preguntas del Cuestionario (<?= count($preguntas) ?>)</h3>
    <?php if (empty($preguntas)): ?>
        <p style="color: #777; margin-top: 10px;">Aún no has agregado preguntas a este instrumento.</p>
    <?php else: ?>
        <div style="margin-top: 15px;">
            <?php foreach ($preguntas as $idx => $p): ?>
                <div style="border: 1px solid #ddd; background: white; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
                    <div style="display: flex; justify-content: space-between;">
                        <strong style="font-size: 1.05rem;"><?= ($idx + 1) ?>. <?= htmlspecialchars($p['texto_pregunta']) ?></strong>
                        <a href="../../controllers/CuestionarioController.php?accion=eliminar_pregunta&pregunta_id=<?= $p['id'] ?>&cuestionario_id=<?= $cuestionario['id'] ?>" 
                           style="color: #d32f2f; text-decoration: none; font-size: 0.85rem;" 
                           onclick="return confirm('¿Eliminar esta pregunta?')">Eliminar</a>
                    </div>
                    <small style="color: #666; text-transform: uppercase; font-size: 0.75rem;">Tipo: <?= $p['tipo_reactivo'] ?></small>

                    <?php if (!empty($p['opciones'])): ?>
                        <ul style="margin: 10px 0 0 20px; font-size: 0.9rem; color: #333;">
                            <?php foreach ($p['opciones'] as $op): ?>
                                <li><?= htmlspecialchars($op['texto_opcion']) ?> <em style="color: #0288d1;">(<?= $op['puntos_valor'] ?> pts)</em></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleOpciones(tipo) {
    const cont = document.getElementById('contenedor_opciones');
    cont.style.display = (tipo === 'texto_abierto') ? 'none' : 'block';
}

function agregarFilaOpcion() {
    const lista = document.getElementById('lista_opciones');
    const div = document.createElement('div');
    div.style.display = 'flex';
    div.style.gap = '10px';
    div.style.marginBottom = '8px';
    div.innerHTML = `
        <input type="text" name="opciones[]" placeholder="Nueva Opción" style="flex: 3;">
        <input type="number" name="puntos[]" placeholder="Puntos" value="0" style="flex: 1;">
    `;
    lista.appendChild(div);
}
</script>

<?php require_once '../../includes/footer.php'; ?>