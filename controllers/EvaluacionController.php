<?php
// controllers/EvaluacionController.php
require_once __DIR__ . '/../models/Respuesta.php';
require_once __DIR__ . '/../models/Pregunta.php';

$accion = $_GET['accion'] ?? '';

if ($accion === 'responder' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $cuestionario_id = (int)($_POST['cuestionario_id'] ?? 0);
    $identificador   = trim($_POST['identificador'] ?? 'Anónimo');
    $respuestas      = $_POST['respuestas'] ?? []; // Array con formato [pregunta_id => valor]

    if ($cuestionario_id <= 0) {
        die('Cuestionario inválido.');
    }

    $puntaje_total = 0;
    $detalles = [];

    // Calcular puntajes por pregunta
    foreach ($respuestas as $pregunta_id => $valor) {
        $pregunta_id = (int)$pregunta_id;
        $opcion_id = null;
        $texto_abierto = null;
        $puntos = 0;

        if (is_numeric($valor)) {
            // Es una opción seleccionada (Radio/Likert)
            $opcion_id = (int)$valor;
            $puntos = Respuesta::obtenerPuntosOpcion($opcion_id);
            $puntaje_total += $puntos;
        } else {
            // Es texto abierto
            $texto_abierto = trim($valor);
        }

        $detalles[] = [
            'pregunta_id' => $pregunta_id,
            'opcion_id'   => $opcion_id,
            'texto'       => $texto_abierto,
            'puntos'      => $puntos
        ];
    }

    // Determinar resultado cualitativo base
    $nivel = ($puntaje_total >= 10) ? 'Satisfactorio / Alto' : 'Regular / Inicial';

    // Guardar en base de datos
    $evaluacion_id = Respuesta::registrarEvaluacion($cuestionario_id, $identificador, $puntaje_total, $nivel);

    foreach ($detalles as $d) {
        Respuesta::guardarDetalle($evaluacion_id, $d['pregunta_id'], $d['opcion_id'], $d['texto'], $d['puntos']);
    }

    header("Location: ../views/encuestas/gracias.php?puntos={$puntaje_total}&eval_id={$evaluacion_id}");
    exit;
}
?>