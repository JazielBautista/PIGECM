<?php
// controllers/CuestionarioController.php
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/Cuestionario.php';
require_once __DIR__ . '/../models/Pregunta.php';
require_once __DIR__ . '/../models/Instrumento.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../views/auth/login.php');
    exit;
}

$accion = $_GET['accion'] ?? '';

// Función auxiliar
function generarSlug($texto) {
    $slug = strtolower(trim($texto));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-');
}

// 1. Crear Cuestionario (Con Blindaje y Prefijo)
if ($accion === 'crear' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo      = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $usuario_id  = $_SESSION['usuario_id'];
    $es_admin    = ($_SESSION['usuario_rol'] === 'admin');
    
    // Recibir y sanitizar el prefijo (sin espacios ni caracteres raros)
    $prefijo_crudo = $_POST['prefijo'] ?? '';
    $prefijo = preg_replace('/[^a-zA-Z0-9_]/', '', $prefijo_crudo);

    // Blindaje backend: verificar autorización del investigador
    if (!$es_admin) {
        $pdo = Conexion::conectar();
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM solicitudes WHERE usuario_id = ? AND estado = 'aprobada'");
        $stmtCheck->execute([$usuario_id]);
        if ($stmtCheck->fetchColumn() == 0) {
            header('Location: ../views/admin/cuestionarios.php?error=' . urlencode('No tienes un espacio de investigación aprobado para crear encuestas.'));
            exit;
        }
    }

    if (empty($titulo) || empty($prefijo)) {
        header('Location: ../views/admin/cuestionarios.php?error=El título y el prefijo son obligatorios (sin espacios)');
        exit;
    }

    $slug_base = generarSlug($titulo);
    $slug_final = $slug_base;
    $contador = 1;

    while (Cuestionario::existeSlug($slug_final)) {
        $slug_final = $slug_base . '-' . $contador;
        $contador++;
    }

    if (Cuestionario::crear($usuario_id, $titulo, $descripcion, $prefijo, $slug_final)) {
        header('Location: ../views/admin/cuestionarios.php?exito=Cuestionario creado con éxito');
    } else {
        header('Location: ../views/admin/cuestionarios.php?error=Error al registrar el cuestionario');
    }
    exit;
}

// 2. Alternar Estado (Activar / Desactivar)
if ($accion === 'toggle_estado') {
    $id = $_GET['id'] ?? null;
    $estado_actual = $_GET['estado'] ?? 0;
    $nuevo_estado = ($estado_actual == 1) ? 0 : 1;

    if ($id) {
        Cuestionario::cambiarEstado($id, $nuevo_estado);
        header('Location: ../views/admin/cuestionarios.php?exito=Estado modificado');
    }
    exit;
}

// 3. Guardar nueva pregunta con sus opciones
if ($accion === 'guardar_pregunta' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $cuestionario_id = (int)($_POST['cuestionario_id'] ?? 0);
    $texto_pregunta  = trim($_POST['texto_pregunta'] ?? '');
    $tipo_reactivo   = $_POST['tipo_reactivo'] ?? 'opcion_multiple';
    $opciones        = $_POST['opciones'] ?? [];
    $puntos          = $_POST['puntos'] ?? [];

    if ($cuestionario_id > 0 && !empty($texto_pregunta)) {
        $pregunta_id = Pregunta::crear($cuestionario_id, $texto_pregunta, $tipo_reactivo);

        if (in_array($tipo_reactivo, ['opcion_multiple', 'escala_likert'])) {
            foreach ($opciones as $index => $texto_opcion) {
                $texto_opcion = trim($texto_opcion);
                if (!empty($texto_opcion)) {
                    $valor_punto = isset($puntos[$index]) ? (int)$puntos[$index] : 0;
                    Pregunta::agregarOpcion($pregunta_id, $texto_opcion, $valor_punto);
                }
            }
        }
        header("Location: ../views/builder/index.php?cuestionario_id={$cuestionario_id}&exito=Reactivo agregado");
    } else {
        header("Location: ../views/builder/index.php?cuestionario_id={$cuestionario_id}&error=Completa el texto de la pregunta");
    }
    exit;
}

// 4. Eliminar pregunta
if ($accion === 'eliminar_pregunta') {
    $pregunta_id     = (int)($_GET['pregunta_id'] ?? 0);
    $cuestionario_id = (int)($_GET['cuestionario_id'] ?? 0);

    if ($pregunta_id > 0) {
        Pregunta::eliminar($pregunta_id);
        header("Location: ../views/builder/index.php?cuestionario_id={$cuestionario_id}&exito=Reactivo eliminado");
    }
    exit;
}

// 5. Guardar el ensamblado modular del cuestionario (Con Exclusión Mutua PHQ-9 / PHQ-A)
if ($accion === 'guardar_ensamble' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $cuestionario_id  = (int)($_POST['cuestionario_id'] ?? 0);
    $instrumentos     = $_POST['instrumentos'] ?? [];
    $preguntas_grales = $_POST['preguntas_grales'] ?? [];

    if ($cuestionario_id > 0) {
        // Validar exclusión mutua entre PHQ-9 (ID 8) y PHQ-A (ID 9)
        $inst_ids = array_map('intval', $instrumentos);
        if (in_array(8, $inst_ids) && in_array(9, $inst_ids)) {
            header("Location: ../views/builder/index.php?cuestionario_id={$cuestionario_id}&error=" . urlencode('PHQ-9 y PHQ-A son mutuamente excluyentes; no puedes seleccionar ambos al mismo tiempo.'));
            exit;
        }

        Instrumento::guardarConfiguracion($cuestionario_id, $instrumentos, $preguntas_grales);
        header("Location: ../views/builder/index.php?cuestionario_id={$cuestionario_id}&exito=Configuración guardada y enlazada correctamente");
    } else {
        header("Location: ../views/admin/cuestionarios.php?error=Error de validación");
    }
    exit;
}
?>