<?php
// views/encuestas/responder.php
require_once '../../config/conexion.php';

$slug = trim($_GET['slug'] ?? '');
$pdo = Conexion::conectar();

// 1. Obtener la encuesta por su URL Slug
$stmt = $pdo->prepare("SELECT * FROM cuestionarios WHERE url_slug = ? AND activo = 1");
$stmt->execute([$slug]);
$cuestionario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cuestionario) {
    die("<div style='text-align:center; padding:50px; font-family:sans-serif;'><h2>Encuesta no disponible o inactiva.</h2><p>Verifica el enlace proporcionado.</p></div>");
}

$cuestionario_id = $cuestionario['id'];

// 2. Obtener los instrumentos activos para este proyecto
$stmtInst = $pdo->prepare("
    SELECT ib.* 
    FROM proyecto_instrumentos pi
    JOIN instrumentos_base ib ON pi.instrumento_id = ib.id
    WHERE pi.cuestionario_id = ?
    ORDER BY ib.id ASC
");
$stmtInst->execute([$cuestionario_id]);
$instrumentos_activos = $stmtInst->fetchAll(PDO::FETCH_ASSOC);

// 3. Obtener las preguntas seleccionadas de Datos Generales
$stmtGrales = $pdo->prepare("SELECT pregunta_base_id FROM proyecto_preguntas_generales WHERE cuestionario_id = ?");
$stmtGrales->execute([$cuestionario_id]);
$preguntas_grales_permitidas = $stmtGrales->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($cuestionario['titulo']) ?> | PIGECM</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body style="background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <div class="container" style="max-width: 800px; margin: 30px auto 60px;">
        <div style="background: white; padding: 35px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
            
            <div style="border-bottom: 2px solid #0f2b48; padding-bottom: 15px; margin-bottom: 25px;">
                <h2 style="color: #0f2b48; margin: 0 0 10px;"><?= htmlspecialchars($cuestionario['titulo']) ?></h2>
                <p style="color: #666; margin: 0;"><?= nl2br(htmlspecialchars($cuestionario['descripcion'])) ?></p>
            </div>

            <form action="../../controllers/EvaluacionController.php?accion=responder" method="POST">
                <input type="hidden" name="cuestionario_id" value="<?= $cuestionario['id'] ?>">

                <?php if (empty($instrumentos_activos)): ?>
                    <p style="color: #c62828;">Esta encuesta no tiene instrumentos asignados actualmente.</p>
                <?php endif; ?>

                <?php foreach ($instrumentos_activos as $inst): ?>
                    <div style="margin-bottom: 35px; background: #fafafa; border: 1px solid #e0e0e0; border-radius: 6px; padding: 20px;">
                        <h3 style="color: #0277bd; margin-top: 0; margin-bottom: 5px;"><?= htmlspecialchars($inst['nombre']) ?></h3>
                        <p style="color: #777; font-size: 0.85rem; margin-bottom: 20px;"><?= htmlspecialchars($inst['descripcion']) ?></p>

                        <?php
                        // Cargar preguntas según sea Datos Generales o un Instrumento Estándar
                        if ($inst['permite_seleccion_preguntas']) {
                            if (empty($preguntas_grales_permitidas)) continue;
                            $inClause = implode(',', array_fill(0, count($preguntas_grales_permitidas), '?'));
                            $stmtP = $pdo->prepare("SELECT * FROM preguntas_base WHERE id IN ($inClause) ORDER BY orden ASC");
                            $stmtP->execute($preguntas_grales_permitidas);
                        } else {
                            $stmtP = $pdo->prepare("SELECT * FROM preguntas_base WHERE instrumento_id = ? ORDER BY orden ASC");
                            $stmtP->execute([$inst['id']]);
                        }
                        $preguntas = $stmtP->fetchAll(PDO::FETCH_ASSOC);
                        ?>

                        <?php foreach ($preguntas as $idx => $p): ?>
                            <div style="margin-bottom: 20px; padding: 12px; background: white; border-radius: 4px; border-left: 3px solid #0277bd;">
                                <label style="font-weight: 600; display: block; margin-bottom: 8px; color: #333;">
                                    <?= htmlspecialchars($p['texto_pregunta']) ?>
                                </label>

                                <?php if ($p['tipo_reactivo'] === 'texto_abierto'): ?>
                                    <input type="text" name="respuestas[<?= $p['id'] ?>]" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" placeholder="Escribe aquí..." required>
                                <?php else: ?>
                                    <?php
                                    $stmtOp = $pdo->prepare("SELECT * FROM opciones_base WHERE pregunta_base_id = ? ORDER BY id ASC");
                                    $stmtOp->execute([$p['id']]);
                                    $opciones = $stmtOp->fetchAll(PDO::FETCH_ASSOC);
                                    ?>
                                    <div style="display: flex; flex-direction: column; gap: 6px; margin-top: 5px;">
                                        <?php foreach ($opciones as $op): ?>
                                            <label style="font-weight: normal; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 0.95rem;">
                                                <input type="radio" name="respuestas[<?= $p['id'] ?>]" value="<?= $op['id'] ?>" required>
                                                <?= htmlspecialchars($op['texto_opcion']) ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>

                <?php if (!empty($instrumentos_activos)): ?>
                    <button type="submit" class="btn" style="background-color: #2e7d32; width: 100%; font-size: 1.05rem; padding: 12px; margin-top: 10px;">
                        Enviar Respuestas
                    </button>
                <?php endif; ?>
            </form>
        </div>
    </div>
</body>
</html>