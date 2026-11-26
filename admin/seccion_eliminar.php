<?php
// ADMINISTRADOR · Eliminar una sección (POST). Las secciones fijas no se eliminan, solo se ocultan.
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
requerir_rol(['ADMINISTRADOR']);

$id = $_POST['id'] ?? null;
$idPagina = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_numeric($id)) {
    $seccion = consultar_uno($conexion, "SELECT id_pagina, clave, imagen, titulo FROM secciones WHERE id_seccion = ?", "i", [$id]);
    if ($seccion) {
        $idPagina = $seccion['id_pagina'];
        if ($seccion['clave']) {
            avisar('Esa sección la usa la página y no se puede eliminar. Puedes ocultarla.', 'error');
        } else {
            // Primero el archivo físico y luego el registro (los pasos se borran solos)
            borrar_archivo('sitio', $seccion['imagen']);
            ejecutar($conexion, "DELETE FROM secciones WHERE id_seccion = ?", "i", [$id]);
            avisar('Eliminamos la sección «' . $seccion['titulo'] . '».');
        }
    }
}

header('Location: ' . ($idPagina ? 'pagina.php?id=' . $idPagina : 'paginas.php'));
exit;
?>
