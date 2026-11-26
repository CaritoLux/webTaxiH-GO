<?php
// ADMINISTRADOR · Eliminar una pregunta frecuente (POST)
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
requerir_rol(['ADMINISTRADOR']);

$id = $_POST['id'] ?? null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_numeric($id)) {
    ejecutar($conexion, "DELETE FROM preguntas_frecuentes WHERE id_pregunta = ?", "i", [$id]);
    avisar('Eliminamos la pregunta.');
}

header('Location: preguntas.php');
exit;
?>
