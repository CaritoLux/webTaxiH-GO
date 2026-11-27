<?php
// =====================================================================
//  Muestra un documento de conductor o la evidencia de un incidente.
//  Las carpetas uploads/documentos y uploads/evidencias están cerradas
//  (.htaccess), así que estos archivos SOLO se ven por aquí y con permiso:
//   - la base y el administrador ven todo;
//   - el conductor ve sus propios documentos;
//   - el pasajero ve la foto de perfil y la del taxi del conductor de sus viajes;
//   - quien reportó un incidente ve su evidencia.
//  Uso: archivo.php?doc=ID   o   archivo.php?evidencia=ID
// =====================================================================
session_start();
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/includes/funciones.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    exit('Inicia sesión para ver este archivo.');
}

$idUsuario = $_SESSION['usuario_id'];
$rol = $_SESSION['rol'];
$esCentral = ($rol === 'BASE');
$ruta = null;

if (isset($_GET['doc']) && is_numeric($_GET['doc'])) {
    $doc = consultar_uno($conexion, "SELECT d.archivo, d.id_conductor, t.clave
                                     FROM documentos d
                                     JOIN tipos_documento t ON t.id_tipo_documento = d.id_tipo_documento
                                     WHERE d.id_documento = ?", "i", [$_GET['doc']]);
    if ($doc) {
        $permitido = $esCentral || $doc['id_conductor'] == $idUsuario;

        if (!$permitido && $rol === 'PASAJERO' && in_array($doc['clave'], ['FOTO_PERFIL', 'FOTO_TAXI'])) {
            $viaje = consultar_uno($conexion, "SELECT id_viaje FROM viajes WHERE id_pasajero = ? AND id_conductor = ? LIMIT 1",
                "ii", [$idUsuario, $doc['id_conductor']]);
            $permitido = ($viaje !== null);
        }

        if ($permitido) {
            $ruta = __DIR__ . '/uploads/documentos/' . basename($doc['archivo']);
        }
    }
} elseif (isset($_GET['evidencia']) && is_numeric($_GET['evidencia'])) {
    $incidente = consultar_uno($conexion, "SELECT archivo_evidencia, id_reportante FROM incidentes WHERE id_incidente = ?",
        "i", [$_GET['evidencia']]);
    if ($incidente && $incidente['archivo_evidencia'] && ($esCentral || $incidente['id_reportante'] == $idUsuario)) {
        $ruta = __DIR__ . '/uploads/evidencias/' . basename($incidente['archivo_evidencia']);
    }
}

if (!$ruta || !file_exists($ruta)) {
    http_response_code(404);
    exit('No encontramos el archivo o no tienes permiso para verlo.');
}

// Se envía el archivo con su tipo real (imagen o PDF)
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $ruta);
finfo_close($finfo);

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($ruta));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=600');
readfile($ruta);
exit;
?>
