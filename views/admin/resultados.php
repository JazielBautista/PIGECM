<?php
// views/admin/resultados.php
session_start();

// 1. Blindaje de seguridad: Validación de sesión activa
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../../config/conexion.php';
$pdo = Conexion::conectar();

$cuestionario_id = intval($_GET['id'] ?? 0);

// 2. Obtener datos del cuestionario seleccionado
$stmtC = $pdo->prepare("SELECT * FROM cuestionarios WHERE id = ?");
$stmtC->execute([$cuestionario_id]);
$cuestionario = $stmtC->fetch(PDO::FETCH_ASSOC);

if (!$cuestionario) {
    die("<div style='text-align:center; padding:40px; font-family:sans-serif;'><h2>Cuestionario no encontrado</h2><p><a href='cuestionarios.php'>Regresar a la biblioteca</a></p></div>");
}

// 3. Obtener listado de evaluaciones recibidas
$stmtEv = $pdo->prepare("
    SELECT * FROM evaluaciones 
    WHERE cuestionario_id = ? 
    ORDER BY fecha_envio DESC
");
$stmtEv->execute([$cuestionario_id]);
$evaluaciones = $stmtEv->fetchAll(PDO::FETCH_ASSOC);

// 4. Métricas cuantitativas rápidas (KPIs)
$total_respuestas = count($evaluaciones);
$promedio_puntos = $total_respuestas > 0 ? round(array_sum(array_column($evaluaciones, 'puntaje_total')) / $total_respuestas, 1) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados: <?= htmlspecialchars($cuestionario['titulo']) ?> | PIGECM</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>

    <div class="container" style="max-width: 1100px; margin: 30px auto;">
        
        <!-- Cabecera de navegación -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <div>
                <a href="cuestionarios.php" style="color: #0277bd; text-decoration: none; font-weight: bold;">&larr; Volver a Cuestionarios</a>
                <h2 style="margin: 10px 0 5px; color: #0f2b48;"><?= htmlspecialchars($cuestionario['titulo']) ?></h2>
                <p style="margin: 0; color: #666;">Tipo: <strong><?= strtoupper($cuestionario['tipo'] ?: 'INFORMATIVO') ?></strong> | Enlace: <code><?= htmlspecialchars($cuestionario['url_slug']) ?></code></p>
            </div>
            <div>
                <a href="../../controllers/EvaluacionController.php?accion=exportar&cuestionario_id=<?= $cuestionario['id'] ?>" class="btn" style="background-color: #2e7d32; padding: 10px 18px; text-decoration: none; color: white; border-radius: 4px; font-weight: bold;">
                    &darr; Exportar Datos (CSV)
                </a>
            </div>
        </div>

        <!-- Tarjetas de Métricas (KPIs) -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border-left: 4px solid #0277bd;">
                <h4 style="margin: 0; color: #666; font-size: 0.9rem;">Total de Evaluaciones</h4>
                <p style="margin: 10px 0 0; font-size: 2rem; font-weight: bold; color: #0f2b48;"><?= $total_respuestas ?></p>
            </div>
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border-left: 4px solid #2e7d32;">
                <h4 style="margin: 0; color: #666; font-size: 0.9rem;">Puntaje Promedio</h4>
                <p style="margin: 10px 0 0; font-size: 2rem; font-weight: bold; color: #2e7d32;"><?= $promedio_puntos ?> pts</p>
            </div>
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border-left: 4px solid #ef6c00;">
                <h4 style="margin: 0; color: #666; font-size: 0.9rem;">Estado del Cuestionario</h4>
                <p style="margin: 10px 0 0; font-size: 1.2rem; font-weight: bold; color: <?= $cuestionario['activo'] ? '#2e7d32' : '#c62828' ?>;">
                    <?= $cuestionario['activo'] ? 'Activo (Recibiendo datos)' : 'Inactivo' ?>
                </p>
            </div>
        </div>

        <!-- Tabla de Registros -->
        <div style="background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead style="background-color: #0f2b48; color: white;">
                    <tr>
                        <th style="padding: 14px 16px;">ID</th>
                        <th style="padding: 14px 16px;">Identificador / Folio</th>
                        <th style="padding: 14px 16px;">Puntaje Obtenido</th>
                        <th style="padding: 14px 16px;">Nivel de Resultado</th>
                        <th style="padding: 14px 16px;">Fecha y Hora</th>
                        <th style="padding: 14px 16px; text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($evaluaciones)): ?>
                        <tr>
                            <td colspan="6" style="padding: 30px; text-align: center; color: #888;">
                                Aún no se han registrado respuestas para esta encuesta.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($evaluaciones as $ev): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 12px 16px; color: #555;">#<?= $ev['id'] ?></td>
                                <td style="padding: 12px 16px; font-weight: 600; color: #333;">
                                    <?= htmlspecialchars($ev['identificador_encuestado']) ?>
                                </td>
                                <td style="padding: 12px 16px;">
                                    <span style="font-weight: bold; font-size: 1.05rem;"><?= $ev['puntaje_total'] ?></span> pts
                                </td>
                                <td style="padding: 12px 16px;">
                                    <?php
                                    $colorBadge = '#757575';
                                    if (stripos($ev['nivel_resultado'], 'Alto') !== false || stripos($ev['nivel_resultado'], 'Severo') !== false) {
                                        $colorBadge = '#c62828';
                                    } elseif (stripos($ev['nivel_resultado'], 'Moderado') !== false) {
                                        $colorBadge = '#ef6c00';
                                    } elseif (stripos($ev['nivel_resultado'], 'Bajo') !== false || stripos($ev['nivel_resultado'], 'Mínimo') !== false) {
                                        $colorBadge = '#2e7d32';
                                    }
                                    ?>
                                    <span style="background-color: <?= $colorBadge ?>; color: white; padding: 4px 10px; border-radius: 12px; font-size: 0.85rem; font-weight: bold;">
                                        <?= htmlspecialchars($ev['nivel_resultado'] ?: 'Sin Diagnóstico') ?>
                                    </span>
                                </td>
                                <td style="padding: 12px 16px; color: #777; font-size: 0.9rem;">
                                    <?= date('d/m/Y H:i', strtotime($ev['fecha_envio'])) ?>
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    <a href="detalle_evaluacion.php?id=<?= $ev['id'] ?>" style="background-color: #0277bd; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 0.85rem; font-weight: bold;">
                                        Ver Respuestas
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>