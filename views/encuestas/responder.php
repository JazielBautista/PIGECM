<?php
// views/encuestas/responder.php
require_once '../../config/conexion.php';
require_once '../../models/Cuestionario.php';
require_once '../../models/Pregunta.php';

$slug = trim($_GET['slug'] ?? '');
$cuestionario = null;

if (!empty($slug)) {
    $pdo = Conexion::conectar();
    $stmt = $pdo->prepare("SELECT * FROM cuestionarios WHERE url_slug = ? AND activo = 1");
    $stmt->execute([$slug]);
    $cuestionario = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$cuestionario) {
    die("<div style='text-align:center; padding:50px; font-family:sans-serif;'><h2>Encuesta no disponible o finalizada.</h2><p>Verifica el enlace proporcionado.</p></div>");
}

$preguntas = Pregunta::obtenerPorCuestionario($cuestionario['id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($cuestionario['titulo']) ?> | PIGECM</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body style="background-color: #f4f6f9;">
    <div class="container" style="max-width: 750px; margin-top: 30px; margin-bottom: 50px;">
        <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
            <h2><?= htmlspecialchars($cuestionario['titulo']) ?></h2>
            <p style="color: #666; margin: 10px 0 25px;"><?= nl2br(htmlspecialchars($cuestionario['descripcion'])) ?></p>
            <hr style="border: 0; border-top: 1px solid #eee; margin-bottom: 25px;">

            <form action="../../controllers/EvaluacionController.php?accion=responder" method="POST">
                <input type="hidden" name="cuestionario_id" value="<?= $cuestionario['id'] ?>">

                <div class="form-group" style="margin-bottom: 25px;">
                    <label for="identificador">Nombre o Folio del Participante (Opcional):</label>
                    <input type="text" name="identificador" id="identificador" placeholder="Anónimo / Correo institucional">
                </div>

                <?php foreach ($preguntas as $idx => $p): ?>
                    <div style="margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid #f0f0f0;">
                        <p style="font-weight: bold; margin-bottom: 12px; font-size: 1.05rem;">
                            <?= ($idx + 1) ?>. <?= htmlspecialchars($p['texto_pregunta']) ?>
                        </p>

                        <?php if ($p['tipo_reactivo'] === 'texto_abierto'): ?>
                            <textarea name="respuestas[<?= $p['id'] ?>]" rows="3" style="width: 100%;" placeholder="Escribe tu respuesta..." required></textarea>
                        <?php else: ?>
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <?php foreach ($p['opciones'] as $op): ?>
                                    <label style="font-weight: normal; cursor: pointer;">
                                        <input type="radio" name="respuestas[<?= $p['id'] ?>]" value="<?= $op['id'] ?>" required>
                                        <?= htmlspecialchars($op['texto_opcion']) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <button type="submit" class="btn" style="width: 100%; background-color: #0288d1; font-size: 1rem; padding: 12px;">Enviar Respuestas</button>
            </form>
        </div>
    </div>
</body>
</html>