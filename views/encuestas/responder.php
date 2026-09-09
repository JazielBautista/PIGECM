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

// 2. Obtener los instrumentos activos
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
    <style>
        /* Estilos del Wizard y UI Interactiva */
        #progress-container { width: 100%; background: #e0e0e0; border-radius: 8px; height: 10px; margin-bottom: 25px; overflow: hidden; }
        #progress-bar { height: 100%; background: #2e7d32; width: 0%; transition: width 0.4s ease; }
        .step-instrument { display: none; animation: fadeIn 0.4s; }
        .step-instrument.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        /* Tarjetas táctiles para opciones */
        .radio-label { display: block; margin-bottom: 8px; }
        .radio-input { display: none; }
        .radio-card { display: block; padding: 14px 18px; border: 2px solid #e0e0e0; border-radius: 8px; cursor: pointer; transition: all 0.2s ease; background: white; color: #444; font-size: 0.95rem; }
        .radio-card:hover { border-color: #0288d1; background: #f4fafe; }
        .radio-input:checked + .radio-card { border-color: #0277bd; background: #e1f5fe; color: #0277bd; font-weight: bold; box-shadow: 0 2px 6px rgba(2,119,189,0.2); }
        
        .nav-buttons { display: flex; justify-content: space-between; margin-top: 30px; gap: 15px; }
        .btn-wizard { padding: 12px 25px; font-size: 1.05rem; border-radius: 6px; cursor: pointer; font-weight: bold; border: none; }
        .btn-prev { background: #78909c; color: white; }
        .btn-next { background: #0f2b48; color: white; flex-grow: 1; text-align: center; }
        .btn-submit { background: #2e7d32; color: white; flex-grow: 1; text-align: center; }
    </style>
</head>
<body style="background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <div class="container" style="max-width: 800px; margin: 30px auto 60px;">
        <div style="background: white; padding: 35px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
            
            <div style="border-bottom: 2px solid #0f2b48; padding-bottom: 15px; margin-bottom: 20px;">
                <h2 style="color: #0f2b48; margin: 0 0 10px;"><?= htmlspecialchars($cuestionario['titulo']) ?></h2>
                <p style="color: #666; margin: 0;"><?= nl2br(htmlspecialchars($cuestionario['descripcion'])) ?></p>
            </div>

            <!-- Barra de Progreso -->
            <div id="progress-container"><div id="progress-bar"></div></div>

            <form id="encuesta-form" action="../../controllers/EvaluacionController.php?accion=responder" method="POST" onsubmit="prepararEnvio(this)">
                <input type="hidden" name="cuestionario_id" value="<?= $cuestionario['id'] ?>">

                <?php if (empty($instrumentos_activos)): ?>
                    <p style="color: #c62828;">Esta encuesta no tiene instrumentos asignados actualmente.</p>
                <?php endif; ?>

                <?php foreach ($instrumentos_activos as $index => $inst): ?>
                    <div class="step-instrument" id="step-<?= $index ?>">
                        <div style="margin-bottom: 25px; background: #fafafa; border: 1px solid #e0e0e0; border-radius: 6px; padding: 20px;">
                            <h3 style="color: #0277bd; margin-top: 0; margin-bottom: 5px;"><?= htmlspecialchars($inst['nombre']) ?></h3>
                            <p style="color: #777; font-size: 0.85rem; margin-bottom: 20px;"><?= htmlspecialchars($inst['descripcion']) ?></p>

                            <?php
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

                            <?php foreach ($preguntas as $p): ?>
                                <?php 
                                // Bloqueo: El Folio (ID 1) ya no se pregunta, se genera en backend
                                if ($p['id'] == 1) continue; 
                                ?>

                                <?php if ($p['tipo_reactivo'] === 'seccion'): ?>
                                    <div style="background-color: #e3f2fd; padding: 15px; border-radius: 4px; margin-top: 25px; margin-bottom: 15px; border-left: 4px solid #0288d1;">
                                        <h4 style="margin: 0; color: #0277bd; font-size: 1.05rem;"><?= nl2br(htmlspecialchars($p['texto_pregunta'])) ?></h4>
                                    </div>
                                <?php else: ?>
                                    <div style="margin-bottom: 20px; padding: 12px; background: white; border-radius: 4px; border-left: 3px solid #0277bd;">
                                        <label style="font-weight: 600; display: block; margin-bottom: 12px; color: #333;">
                                            <?= htmlspecialchars($p['texto_pregunta']) ?>
                                        </label>

                                        <?php if ($p['tipo_reactivo'] === 'texto_abierto'): ?>
                                            <?php 
                                            // Lógica: Solo exigimos 'required' en Datos Generales. En ASQ/PHQ las de texto son opcionales.
                                            $es_req = ($p['instrumento_id'] == 1) ? 'required' : ''; 
                                            ?>
                                            <input type="text" name="respuestas[<?= $p['id'] ?>]" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;" placeholder="Escribe tu respuesta..." <?= $es_req ?>>
                                        <?php else: ?>
                                            <?php
                                            $stmtOp = $pdo->prepare("SELECT * FROM opciones_base WHERE pregunta_base_id = ? ORDER BY id ASC");
                                            $stmtOp->execute([$p['id']]);
                                            $opciones = $stmtOp->fetchAll(PDO::FETCH_ASSOC);
                                            ?>
                                            
                                            <?php if (count($opciones) > 5): ?>
                                                <select name="respuestas[<?= $p['id'] ?>]" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;" required>
                                                    <option value="">-- Selecciona una opción --</option>
                                                    <?php foreach ($opciones as $op): ?>
                                                        <option value="<?= $op['id'] ?>"><?= htmlspecialchars($op['texto_opcion']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php else: ?>
                                                <div style="display: flex; flex-direction: column; gap: 6px;">
                                                    <?php foreach ($opciones as $op): ?>
                                                        <label class="radio-label">
                                                            <input type="radio" class="radio-input" name="respuestas[<?= $p['id'] ?>]" value="<?= $op['id'] ?>" required>
                                                            <span class="radio-card"><?= htmlspecialchars($op['texto_opcion']) ?></span>
                                                        </label>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (!empty($instrumentos_activos)): ?>
                    <div class="nav-buttons">
                        <button type="button" id="btn-prev" class="btn-wizard btn-prev" onclick="cambiarPaso(-1)">Anterior</button>
                        <button type="button" id="btn-next" class="btn-wizard btn-next" onclick="cambiarPaso(1)">Siguiente Módulo</button>
                        <button type="submit" id="btn-submit" class="btn-wizard btn-submit">Enviar Respuestas</button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <script>
        let currentStep = 0;
        const steps = document.querySelectorAll('.step-instrument');
        const btnPrev = document.getElementById('btn-prev');
        const btnNext = document.getElementById('btn-next');
        const btnSubmit = document.getElementById('btn-submit');
        const progressBar = document.getElementById('progress-bar');

        function mostrarPaso(n) {
            steps.forEach((step, index) => {
                step.classList.remove('active');
                if (index === n) step.classList.add('active');
            });

            // Actualizar barra de progreso
            const percent = ((n + 1) / steps.length) * 100;
            progressBar.style.width = percent + '%';

            // Lógica de botones
            btnPrev.style.display = (n === 0) ? 'none' : 'inline-block';
            if (n === (steps.length - 1)) {
                btnNext.style.display = 'none';
                btnSubmit.style.display = 'inline-block';
            } else {
                btnNext.style.display = 'inline-block';
                btnSubmit.style.display = 'none';
            }
            window.scrollTo(0, 0);
        }

        function cambiarPaso(n) {
            // Validar campos requeridos antes de avanzar
            if (n === 1) {
                const currentInputs = steps[currentStep].querySelectorAll('input, select');
                for (let input of currentInputs) {
                    if (!input.checkValidity()) {
                        input.reportValidity(); // Muestra el globo nativo del navegador
                        return false; 
                    }
                }
            }
            currentStep += n;
            mostrarPaso(currentStep);
        }

        function prepararEnvio(form) {
            btnSubmit.disabled = true;
            btnSubmit.style.backgroundColor = '#757575';
            btnSubmit.innerText = 'Guardando y evaluando...';
            btnPrev.style.display = 'none';
        }

        if(steps.length > 0) mostrarPaso(0);
    </script>
</body>
</html>