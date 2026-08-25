<?php
// controllers/SolicitudController.php
session_start();
require_once __DIR__ . '/../models/Solicitud.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../views/auth/login.php');
    exit;
}

$accion = $_GET['accion'] ?? '';

// 1. Crear una nueva solicitud (Investigador)
if ($accion === 'crear' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_espacio = trim($_POST['nombre_espacio'] ?? '');
    $justificacion  = trim($_POST['justificacion'] ?? '');
    $usuario_id     = $_SESSION['usuario_id'];

    if (!empty($nombre_espacio) && !empty($justificacion)) {
        Solicitud::crear($usuario_id, $nombre_espacio, $justificacion);
        header('Location: ../views/admin/solicitudes.php?exito=Solicitud enviada correctamente');
    } else {
        header('Location: ../views/admin/solicitudes.php?error=Todos los campos son obligatorios');
    }
    exit;
}

// 2. Cambiar estado (Aprobar / Rechazar - Solo Admin)
if ($accion === 'cambiar_estado' && $_SESSION['usuario_rol'] === 'admin') {
    $solicitud_id = $_GET['id'] ?? null;
    $nuevo_estado = $_GET['estado'] ?? null;

    if ($solicitud_id && in_array($nuevo_estado, ['aprobada', 'rechazada'])) {
        Solicitud::cambiarEstado($solicitud_id, $nuevo_estado);
        header('Location: ../views/admin/solicitudes.php?exito=Estado actualizado');
    } else {
        header('Location: ../views/admin/solicitudes.php?error=Acción no válida');
    }
    exit;
}
?>