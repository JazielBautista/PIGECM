<?php
// models/Instrumento.php
require_once __DIR__ . '/../config/conexion.php';

class Instrumento {
    public static function obtenerTodos() {
        $pdo = Conexion::conectar();
        $stmt = $pdo->query("SELECT * FROM instrumentos_base ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function obtenerPreguntasBase($instrumento_id) {
        $pdo = Conexion::conectar();
        $stmt = $pdo->prepare("SELECT * FROM preguntas_base WHERE instrumento_id = ? ORDER BY orden ASC");
        $stmt->execute([$instrumento_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function guardarConfiguracion($cuestionario_id, $instrumentos, $preguntas_grales) {
        $pdo = Conexion::conectar();
        
        // Limpiar configuración anterior
        $stmt = $pdo->prepare("DELETE FROM proyecto_instrumentos WHERE cuestionario_id = ?");
        $stmt->execute([$cuestionario_id]);
        
        $stmt = $pdo->prepare("DELETE FROM proyecto_preguntas_generales WHERE cuestionario_id = ?");
        $stmt->execute([$cuestionario_id]);

        // Guardar instrumentos seleccionados (Ej. GAD-7, PHQ-9)
        if (!empty($instrumentos)) {
            $stmt = $pdo->prepare("INSERT INTO proyecto_instrumentos (cuestionario_id, instrumento_id) VALUES (?, ?)");
            foreach ($instrumentos as $inst_id) {
                $stmt->execute([$cuestionario_id, $inst_id]);
            }
        }

        // Guardar preguntas específicas de "Datos Generales"
        if (!empty($preguntas_grales)) {
            $stmt = $pdo->prepare("INSERT INTO proyecto_preguntas_generales (cuestionario_id, pregunta_base_id) VALUES (?, ?)");
            foreach ($preguntas_grales as $preg_id) {
                $stmt->execute([$cuestionario_id, $preg_id]);
            }
        }
        return true;
    }

    // Funciones para cargar checkboxes ya marcados al volver a entrar
    public static function obtenerInstrumentosProyecto($cuestionario_id) {
        $pdo = Conexion::conectar();
        $stmt = $pdo->prepare("SELECT instrumento_id FROM proyecto_instrumentos WHERE cuestionario_id = ?");
        $stmt->execute([$cuestionario_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function obtenerPreguntasGeneralesProyecto($cuestionario_id) {
        $pdo = Conexion::conectar();
        $stmt = $pdo->prepare("SELECT pregunta_base_id FROM proyecto_preguntas_generales WHERE cuestionario_id = ?");
        $stmt->execute([$cuestionario_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
?>