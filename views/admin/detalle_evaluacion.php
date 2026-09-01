<?php
// views/admin/detalle_evaluacion.php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../../config/conexion.php';
$pdo = Conexion::conectar();

$evaluacion_id = intval($_GET['id'] ?? 0);

// 1. Obtener la evaluación principal
$stmtEv = $pdo->prepare("
    SELECT e.*, c.titulo AS cuestionario_titulo 
    FROM evaluaciones e
    JOIN cuestionarios c ON e.cuestionario_id = c.id
    WHERE e.id = ?
");
$stmtEv->execute([$evaluacion_id]);
$evaluacion = $stmtEv->fetch(PDO::FETCH_ASSOC);

if (!$evaluacion) {
    die("<div style='text-align:center; padding:40px; font-family:sans-serif;'><h2>Evaluación no encontrada</h2><p><a href='cuestionarios.php'>Volver</a></p></div>");
}

// 2. Obtener el detalle de todas las respuestas contestadas
$stmtDet = $pdo->prepare("
    SELECT rd.*, pb.texto_pregunta, pb.tipo_reactivo, ob.texto_opcion, ib.nombre AS instrumento_nombre
    FROM respuestas_detalle rd
    JOIN preguntas_base pb ON rd.pregunta_id = pb.id
    JOIN instrumentos_base ib ON pb.instrumento_id = ib.id
    LEFT JOIN opciones_base ob ON rd.opcion_id = ob.id
    WHERE rd.evaluacion_id = ?
    ORDER BY ib.id ASC, pb.orden ASC
");
$stmtDet->execute([$evaluacion_id]);
$respuestas = $stmtDet->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Evaluación #<?= $evaluacion['id'] ?> | PIGECM</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>

    <div class="container" style="max-width: 900px; margin: 30px auto 60px;">
        <div style="margin-bottom: 20px;">
            <a href="resultados.php?id=<?= $evaluacion['cuestionario_id'] ?>" style="color: #0277bd; text-decoration: none; font-weight: bold;">&larr; Volver al listado de resultados</a>
        </div>

        <!-- Ficha de Encabezado -->
        <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 25px; border-top: 4px solid #0f2b48;">
            <h3 style="margin: 0 0 10px; color: #0f2b48;">Expediente de Evaluación #<?= $evaluacion['id'] ?></h3>
            <p style="margin: 5px 0; color: #555;"><strong>Cuestionario:</strong> <?= htmlspecialchars($evaluacion['cuestionario_titulo']) ?></p>
            <p style="margin: 5px 0; color: #555;"><strong>Identificador / Folio:</strong> <?= htmlspecialchars($evaluacion['identificador_encuestado']) ?></p>
            <p style="margin: 5px 0; color: #555;"><strong>Fecha de Envío:</strong> <?= date('d/m/Y H:i:s', strtotime($evaluacion['fecha_envio'])) ?></p>
            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee; display: flex; gap: 30px;">
                <div>
                    <span style="font-size: 0.85rem; color: #777;">Puntaje Acumulado:</span>
                    <p style="margin: 0; font-size: 1.4rem; font-weight: bold; color: #0f2b48;"><?= $evaluacion['puntaje_total'] ?> puntos</p>
                </div>
                <div>
                    <span style="font-size: 0.85rem; color: #777;">Diagnóstico / Nivel:</span>
                    <p style="margin: 0; font-size: 1.4rem; font-weight: bold; color: #0277bd;"><?= htmlspecialchars($evaluacion['nivel_resultado'] ?: 'N/A') ?></p>
                </div>
            </div>
        </div>

        <!-- Desglose de Reactivos -->
        <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
            <h4 style="margin: 0 0 20px; color: #0f2b48; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;">Respuestas Registradas</h4>
            
            <?php foreach ($respuestas as $idx => $r): ?>
                <div style="margin-bottom: 18px; padding-bottom: 15px; border-bottom: 1px solid #f5f5f5;">
                    <span style="font-size: 0.75rem; font-weight: bold; color: #0277bd; text-transform: uppercase;">
                        <?= htmlspecialchars($r['instrumento_nombre']) ?>
                    </span>
                    <p style="margin: 5px 0 8px; font-weight: 600; color: #333;">
                        <?= htmlspecialchars($r['texto_pregunta']) ?>
                    </p>
                    <div style="background-color: #f9fbfd; padding: 10px 14px; border-radius: 4px; border-left: 3px solid #0288d1; display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: #222;">
                            <?= htmlspecialchars($r['tipo_reactivo'] === 'texto_abierto' ? $r['respuesta_texto'] : $r['texto_opcion']) ?>
                        </span>
                        <?php if ($r['tipo_reactivo'] !== 'texto_abierto'): ?>
                            <span style="font-size: 0.85rem; font-weight: bold; color: #666;">
                                Valor: <?= $r['puntos_obtenidos'] ?> pts
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>