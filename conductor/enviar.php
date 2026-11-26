<?php
// CONDUCTOR · Enviar la solicitud a revisión (POST desde Mi solicitud)
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
requerir_rol(['CONDUCTOR']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: panel.php');
    exit;
}

$idConductor = $_SESSION['usuario_id'];
require __DIR__ . '/../includes/conductor_datos.php';

if (!$editable) {
    avisar('Tu solicitud ya fue enviada.', 'info');
} elseif ($faltantes) {
    avisar('Para enviar tu solicitud falta: ' . implode(', ', $faltantes) . '.', 'error');
} else {
    ejecutar($conexion, "UPDATE conductores SET estado_revision = 'EN_REVISION', fecha_envio = NOW(), motivo_rechazo = NULL WHERE id_usuario = ?",
        "i", [$idConductor]);
    avisar('Enviamos tu solicitud. La central la revisará en su horario de atención.');
}

header('Location: panel.php');
exit;
?>
