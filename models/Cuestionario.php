<?php
// models/Cuestionario.php
require_once __DIR__ . '/../config/conexion.php';

class Cuestionario {
    public static function crear($usuario_id, $titulo, $descripcion, $tipo, $url_slug) {
        $pdo = Conexion::conectar();
        $stmt = $pdo->prepare("
            INSERT INTO cuestionarios (usuario_id, titulo, descripcion, tipo, url_slug, activo) 
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        return $stmt->execute([$usuario_id, $titulo, $descripcion, $tipo, $url_slug]);
    }

    public static function obtenerPorUsuario($usuario_id) {
        $pdo = Conexion::conectar();
        $stmt = $pdo->prepare("SELECT * FROM cuestionarios WHERE usuario_id = ? ORDER BY fecha_creacion DESC");
        $stmt->execute([$usuario_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function obtenerTodos() {
        $pdo = Conexion::conectar();
        $stmt = $pdo->prepare("
            SELECT c.*, u.nombre as autor 
            FROM cuestionarios c 
            JOIN usuarios u ON c.usuario_id = u.id 
            ORDER BY c.fecha_creacion DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function obtenerPorId($id) {
        $pdo = Conexion::conectar();
        $stmt = $pdo->prepare("SELECT * FROM cuestionarios WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function existeSlug($slug) {
        $pdo = Conexion::conectar();
        $stmt = $pdo->prepare("SELECT id FROM cuestionarios WHERE url_slug = ?");
        $stmt->execute([$slug]);
        return (bool)$stmt->fetch();
    }

    public static function cambiarEstado($id, $activo) {
        $pdo = Conexion::conectar();
        $stmt = $pdo->prepare("UPDATE cuestionarios SET activo = ? WHERE id = ?");
        return $stmt->execute([$activo, $id]);
    }
}
?>