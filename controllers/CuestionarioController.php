<?php
// controllers/CuestionarioController.php
session_start();
require_once __DIR__ . '/../models/Cuestionario.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../views/auth/login.php');
    exit;
}

$accion = $_GET['accion'] ?? '';

// Función auxiliar para convertir títulos en slugs legibles (ej: "Encuesta 2026" -> "encuesta-2026")
function generarSlug($texto) {
    $slug = strtolower(trim($texto));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-');
}

// 1. Crear Cuestionario
if ($accion === 'crear' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo      = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $tipo        = $_POST['tipo'] ?? 'diagnostico';
    $usuario_id  = $_SESSION['usuario_id'];

    if (empty($titulo)) {
        header('Location: ../views/admin/cuestionarios.php?error=El título es obligatorio');
        exit;
    }

    // Generar slug base y verificar colisiones
    $slug_base = generarSlug($titulo);
    $slug_final = $slug_base;
    $contador = 1;

    while (Cuestionario::existeSlug($slug_final)) {
        $slug_final = $slug_base . '-' . $contador;
        $contador++;
    }

    if (Cuestionario::crear($usuario_id, $titulo, $descripcion, $tipo, $slug_final)) {
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
?>