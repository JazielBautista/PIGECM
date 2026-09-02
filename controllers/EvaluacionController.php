<?php
// controllers/EvaluacionController.php
session_start();
require_once '../config/conexion.php';

$accion = $_GET['accion'] ?? '';
$pdo = Conexion::conectar();

switch ($accion) {
    case 'responder':
        procesarRespuesta($pdo);
        break;

    case 'exportar':
        exportarCSV($pdo);
        break;

    default:
        header("Location: ../index.php");
        exit;
}

/**
 * 1. Procesa las respuestas enviadas desde views/encuestas/responder.php
 */
function procesarRespuesta($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: ../index.php");
        exit;
    }

    $cuestionario_id = intval($_POST['cuestionario_id'] ?? 0);
    $respuestas = $_POST['respuestas'] ?? [];

    if ($cuestionario_id <= 0 || empty($respuestas)) {
        die("Error: Formulario incompleto o datos no recibidos.");
    }

    // Identificar al encuestado
    $identificador = 'Anónimo';
    if (isset($respuestas[1]) && !empty(trim($respuestas[1]))) {
        $identificador = trim($respuestas[1]); 
    } elseif (isset($respuestas[2]) && !empty(trim($respuestas[2]))) {
        $identificador = trim($respuestas[2]); 
    }

    $puntaje_total = 0;
    $detalle_a_insertar = [];
    $alerta_riesgo_critico = false;

    // Recorrer cada respuesta enviada
    foreach ($respuestas as $pregunta_id => $valor) {
        $pregunta_id = intval($pregunta_id);
        
        $stmtP = $pdo->prepare("SELECT tipo_reactivo FROM preguntas_base WHERE id = ?");
        $stmtP->execute([$pregunta_id]);
        $pregunta = $stmtP->fetch(PDO::FETCH_ASSOC);

        if (!$pregunta || $pregunta['tipo_reactivo'] === 'seccion') {
            continue;
        }

        if ($pregunta['tipo_reactivo'] === 'texto_abierto') {
            $texto_limpio = trim(strip_tags($valor));
            $detalle_a_insertar[] = [
                'pregunta_id' => $pregunta_id,
                'opcion_id' => null,
                'respuesta_texto' => $texto_limpio,
                'puntos_obtenidos' => 0
            ];
        } else {
            $opcion_id = intval($valor);
            $stmtOp = $pdo->prepare("SELECT puntos_valor, texto_opcion FROM opciones_base WHERE id = ? AND pregunta_base_id = ?");
            $stmtOp->execute([$opcion_id, $pregunta_id]);
            $opcion = $stmtOp->fetch(PDO::FETCH_ASSOC);

            $puntos = $opcion ? intval($opcion['puntos_valor']) : 0;
            $puntaje_total += $puntos;

            if ($opcion && in_array($pregunta_id, [22, 126, 127, 128, 137, 140, 141]) && stripos($opcion['texto_opcion'], 'Sí') !== false) {
                $alerta_riesgo_critico = true;
            }

            $detalle_a_insertar[] = [
                'pregunta_id' => $pregunta_id,
                'opcion_id' => $opcion ? $opcion_id : null,
                'respuesta_texto' => null,
                'puntos_obtenidos' => $puntos
            ];
        }
    }

    // Clasificación de baremos clínicos
    $nivel_resultado = 'Completado';
    if ($alerta_riesgo_critico) {
        $nivel_resultado = 'Riesgo Alto (Alerta Clínica)';
    } elseif ($puntaje_total >= 20) {
        $nivel_resultado = 'Riesgo Severo';
    } elseif ($puntaje_total >= 15) {
        $nivel_resultado = 'Riesgo Moderadamente Severo';
    } elseif ($puntaje_total >= 10) {
        $nivel_resultado = 'Riesgo Moderado';
    } elseif ($puntaje_total >= 5) {
        $nivel_resultado = 'Riesgo Leve';
    } elseif ($puntaje_total > 0) {
        $nivel_resultado = 'Riesgo Mínimo';
    }

    // --- INICIO DE TRANSACCIÓN SQL ---
    try {
        $pdo->beginTransaction();

        $stmtEv = $pdo->prepare("
            INSERT INTO evaluaciones (cuestionario_id, identificador_encuestado, puntaje_total, nivel_resultado, fecha_envio) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmtEv->execute([$cuestionario_id, $identificador, $puntaje_total, $nivel_resultado]);
        $evaluacion_id = $pdo->lastInsertId();

        $stmtDet = $pdo->prepare("
            INSERT INTO respuestas_detalle (evaluacion_id, pregunta_id, opcion_id, respuesta_texto, puntos_obtenidos) 
            VALUES (?, ?, ?, ?, ?)
        ");
        foreach ($detalle_a_insertar as $item) {
            $stmtDet->execute([
                $evaluacion_id,
                $item['pregunta_id'],
                $item['opcion_id'],
                $item['respuesta_texto'],
                $item['puntos_obtenidos']
            ]);
        }

        $pdo->commit();
        header("Location: ../views/encuestas/gracias.php");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack(); // Deshace los cambios si algo falla
        error_log("Error en PIGECM: " . $e->getMessage());
        die("<div style='text-align:center; padding:50px; font-family:sans-serif;'><h2>Ocurrió un error al procesar tu encuesta.</h2><p>Por favor, intenta nuevamente.</p></div>");
    }
}
/**
 * 2. Genera y descarga la matriz tabulada en formato CSV
 */
function exportarCSV($pdo) {
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: ../views/auth/login.php");
        exit;
    }

    $cuestionario_id = intval($_GET['cuestionario_id'] ?? 0);

    $stmtC = $pdo->prepare("SELECT titulo FROM cuestionarios WHERE id = ?");
    $stmtC->execute([$cuestionario_id]);
    $cuestionario = $stmtC->fetch(PDO::FETCH_ASSOC);

    if (!$cuestionario) {
        die("Cuestionario no encontrado.");
    }

    $stmtEv = $pdo->prepare("SELECT * FROM evaluaciones WHERE cuestionario_id = ? ORDER BY id ASC");
    $stmtEv->execute([$cuestionario_id]);
    $evaluaciones = $stmtEv->fetchAll(PDO::FETCH_ASSOC);

    $filename = "PIGECM_Resultados_" . preg_replace('/[^A-Za-z0-9_]/', '_', $cuestionario['titulo']) . "_" . date('Ymd_His') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    fputcsv($output, ['ID Evaluación', 'Identificador / Folio', 'Puntaje Total', 'Nivel / Diagnóstico', 'Fecha de Envío', 'Instrumento', 'Pregunta', 'Respuesta Registrada', 'Puntos Reactivo']);

    foreach ($evaluaciones as $ev) {
        $stmtDet = $pdo->prepare("
            SELECT rd.puntos_obtenidos, rd.respuesta_texto, pb.texto_pregunta, pb.tipo_reactivo, ob.texto_opcion, ib.nombre AS instrumento_nombre
            FROM respuestas_detalle rd
            JOIN preguntas_base pb ON rd.pregunta_id = pb.id
            JOIN instrumentos_base ib ON pb.instrumento_id = ib.id
            LEFT JOIN opciones_base ob ON rd.opcion_id = ob.id
            WHERE rd.evaluacion_id = ?
            ORDER BY ib.id ASC, pb.orden ASC
        ");
        $stmtDet->execute([$ev['id']]);
        $detalles = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

        foreach ($detalles as $d) {
            $respuesta = ($d['tipo_reactivo'] === 'texto_abierto') ? $d['respuesta_texto'] : $d['texto_opcion'];
            fputcsv($output, [
                $ev['id'],
                $ev['identificador_encuestado'],
                $ev['puntaje_total'],
                $ev['nivel_resultado'],
                $ev['fecha_envio'],
                $d['instrumento_nombre'],
                $d['texto_pregunta'],
                $respuesta,
                $d['puntos_obtenidos']
            ]);
        }
    }

    fclose($output);
    exit;
}