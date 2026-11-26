<?php
// PASAJERO · Calificar al conductor de un viaje terminado (POST)
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
requerir_rol(['PASAJERO']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: viajes.php');
    exit;
}

$idPasajero = $_SESSION['usuario_id'];
$folio = $_POST['folio'] ?? '';
$estrellas = (int) ($_POST['estrellas'] ?? 0);
$comentario = trim($_POST['comentario'] ?? '');

$viaje = consultar_uno($conexion, "SELECT id_viaje, estado, id_conductor FROM viajes WHERE folio = ? AND id_pasajero = ?",
    "si", [$folio, $idPasajero]);
if (!$viaje) {
    header('Location: viajes.php');
    exit;
}

$yaCalifico = consultar_uno($conexion, "SELECT id_calificacion FROM calificaciones WHERE id_viaje = ? AND id_autor = ?",
    "ii", [$viaje['id_viaje'], $idPasajero]);

if ($viaje['estado'] !== 'FINALIZADO' || !$viaje['id_conductor']) {
    avisar('Solo puedes calificar viajes terminados.', 'error');
} elseif ($yaCalifico) {
    avisar('Ya calificaste este viaje.', 'info');
} elseif ($estrellas < 1 || $estrellas > 5) {
    avisar('Elige de 1 a 5 estrellas.', 'error');
} else {
    // El pasajero (autor) califica al conductor del viaje (calificado)
    ejecutar($conexion, "INSERT INTO calificaciones (id_viaje, id_autor, id_calificado, estrellas, comentario) VALUES (?, ?, ?, ?, ?)",
        "iiiis", [$viaje['id_viaje'], $idPasajero, $viaje['id_conductor'], $estrellas, $comentario !== '' ? mb_substr($comentario, 0, 200) : null]);
    avisar('Gracias por calificar tu viaje.');
}

header('Location: detalle.php?folio=' . urlencode($folio));
exit;
?>
