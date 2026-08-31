<?php
// controllers/EvaluacionController.php
require_once __DIR__ . '/../config/conexion.php';

$accion = $_GET['accion'] ?? '';

if ($accion === 'responder' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $cuestionario_id = (int)($_POST['cuestionario_id'] ?? 0);
    $respuestas      = $_POST['respuestas'] ?? []; // Array con [pregunta_id => opcion_id_o_texto]
    
    $pdo = Conexion::conectar();
    $puntaje_total = 0;
    
    // 1. Crear la cabecera de la evaluación
    $stmt = $pdo->prepare("INSERT INTO evaluaciones (cuestionario_id, identificador_encuestado) VALUES (?, 'Anónimo')");
    $stmt->execute([$cuestionario_id]);
    $evaluacion_id = $pdo->lastInsertId();

    // 2. Procesar cada respuesta enviada
    foreach ($respuestas as $pregunta_id => $valor) {
        $pregunta_id = (int)$pregunta_id;
        $opcion_id = null;
        $respuesta_texto = null;
        $puntos_obtenidos = 0;

        if (is_numeric($valor)) {
            // Es una opción de selección (radio button)
            $opcion_id = (int)$valor;
            // Buscar cuántos puntos vale esa opción en la BD
            $stmtPts = $pdo->prepare("SELECT puntos_valor FROM opciones_base WHERE id = ?");
            $stmtPts->execute([$opcion_id]);
            $res = $stmtPts->fetch(PDO::FETCH_ASSOC);
            if ($res) {
                $puntos_obtenidos = (int)$res['puntos_valor'];
                $puntaje_total += $puntos_obtenidos; // Sumar al total del paciente
            }
        } else {
            // Es un campo de texto abierto (ej. Nombre, Edad, Lengua Materna)
            $respuesta_texto = trim($valor);
        }

        // 3. Guardar el detalle de la respuesta
        $stmtDetalle = $pdo->prepare("INSERT INTO respuestas_detalle (evaluacion_id, pregunta_id, opcion_id, respuesta_texto, puntos_obtenidos) VALUES (?, ?, ?, ?, ?)");
        $stmtDetalle->execute([$evaluacion_id, $pregunta_id, $opcion_id, $respuesta_texto, $puntos_obtenidos]);
    }

    // 4. Actualizar el puntaje final en la cabecera
    $nivel = ($puntaje_total > 9) ? 'Riesgo Moderado/Alto' : 'Riesgo Bajo'; // Lógica básica de ejemplo
    $stmtUpdate = $pdo->prepare("UPDATE evaluaciones SET puntaje_total = ?, nivel_resultado = ? WHERE id = ?");
    $stmtUpdate->execute([$puntaje_total, $nivel, $evaluacion_id]);

    // 5. Redirigir a la pantalla de agradecimiento
    header("Location: ../views/encuestas/gracias.php?puntos={$puntaje_total}");
    exit;
}
?>