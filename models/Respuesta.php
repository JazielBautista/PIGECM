<?php
// models/Respuesta.php
require_once __DIR__ . '/../config/conexion.php';

class Respuesta {
    public static function registrarEvaluacion($cuestionario_id, $identificador, $puntaje_total, $nivel_resultado) {
        $pdo = Conexion::conectar();
        $stmt = $pdo->prepare("
            INSERT INTO evaluaciones (cuestionario_id, identificador_encuestado, puntaje_total, nivel_resultado) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$cuestionario_id, $identificador, $puntaje_total, $nivel_resultado]);
        return $pdo->lastInsertId();
    }

    public static function guardarDetalle($evaluacion_id, $pregunta_id, $opcion_id, $texto, $puntos) {
        $pdo = Conexion::conectar();
        $stmt = $pdo->prepare("
            INSERT INTO respuestas_detalle (evaluacion_id, pregunta_id, opcion_id, respuesta_texto, puntos_obtenidos) 
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$evaluacion_id, $pregunta_id, $opcion_id, $texto, $puntos]);
    }

    public static function obtenerPuntosOpcion($opcion_id) {
        $pdo = Conexion::conectar();
        $stmt = $pdo->prepare("SELECT puntos_valor FROM opciones WHERE id = ?");
        $stmt->execute([$opcion_id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ? (int)$res['puntos_valor'] : 0;
    }
}
?>