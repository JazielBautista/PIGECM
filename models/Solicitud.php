<?php
// models/Solicitud.php
require_once __DIR__ . '/../config/conexion.php';

class Solicitud {
    public static function crear($usuario_id, $nombre_espacio, $justificacion) {
        $pdo = Conexion::conectar();
        $stmt = $pdo->prepare("INSERT INTO solicitudes (usuario_id, nombre_espacio, justificacion, estado) VALUES (?, ?, ?, 'pendiente')");
        return $stmt->execute([$usuario_id, $nombre_espacio, $justificacion]);
    }

    public static function obtenerPorUsuario($usuario_id) {
        $pdo = Conexion::conectar();
        $stmt = $pdo->prepare("SELECT * FROM solicitudes WHERE usuario_id = ? ORDER BY fecha_solicitud DESC");
        $stmt->execute([$usuario_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function obtenerTodas() {
        $pdo = Conexion::conectar();
        $stmt = $pdo->prepare("
            SELECT s.*, u.nombre as solicitante, u.email 
            FROM solicitudes s 
            JOIN usuarios u ON s.usuario_id = u.id 
            ORDER BY s.fecha_solicitud DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function cambiarEstado($id, $nuevo_estado) {
        $pdo = Conexion::conectar();
        $stmt = $pdo->prepare("UPDATE solicitudes SET estado = ? WHERE id = ?");
        return $stmt->execute([$nuevo_estado, $id]);
    }
}
?>