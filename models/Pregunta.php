<?php
// models/Pregunta.php
require_once __DIR__ . '/../config/conexion.php';

class Pregunta {
    public static function crear($cuestionario_id, $texto_pregunta, $tipo_reactivo, $orden = 1, $puntaje = 0) {
        $pdo = Conexion::conectar();
        $stmt = $pdo->prepare("
            INSERT INTO preguntas (cuestionario_id, texto_pregunta, tipo_reactivo, orden, puntaje) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$cuestionario_id, $texto_pregunta, $tipo_reactivo, $orden, $puntaje]);
        return $pdo->lastInsertId();
    }

    public static function agregarOpcion($pregunta_id, $texto_opcion, $puntos_valor = 0) {
        $pdo = Conexion::conectar();
        $stmt = $pdo->prepare("
            INSERT INTO opciones (pregunta_id, texto_opcion, puntos_valor) 
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$pregunta_id, $texto_opcion, $puntos_valor]);
    }

    public static function obtenerPorCuestionario($cuestionario_id) {
        $pdo = Conexion::conectar();
        $stmt = $pdo->prepare("SELECT * FROM preguntas WHERE cuestionario_id = ? ORDER BY orden ASC, id ASC");
        $stmt->execute([$cuestionario_id]);
        $preguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($preguntas as &$p) {
            $stmtOpciones = $pdo->prepare("SELECT * FROM opciones WHERE pregunta_id = ? ORDER BY id ASC");
            $stmtOpciones->execute([$p['id']]);
            $p['opciones'] = $stmtOpciones->fetchAll(PDO::FETCH_ASSOC);
        }
        return $preguntas;
    }

    public static function eliminar($id) {
        $pdo = Conexion::conectar();
        $stmt = $pdo->prepare("DELETE FROM preguntas WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
?>