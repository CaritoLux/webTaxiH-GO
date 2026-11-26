<?php
// Datos de la solicitud del conductor que usan panel.php, taxi.php,
// documento.php y enviar.php. Necesita $conexion y $idConductor.

$conductor = consultar_uno($conexion, "SELECT * FROM conductores WHERE id_usuario = ?", "i", [$idConductor]);

// registro.php solo crea el usuario; la primera vez que el conductor entra
// se crea su fila en conductores (estado SIN_DOCUMENTOS)
if (!$conductor) {
    ejecutar($conexion, "INSERT INTO conductores (id_usuario) VALUES (?)", "i", [$idConductor]);
    $conductor = consultar_uno($conexion, "SELECT * FROM conductores WHERE id_usuario = ?", "i", [$idConductor]);
}

$vehiculo = consultar_uno($conexion, "SELECT v.*, m.nombre AS marca, c.nombre AS color
                                      FROM vehiculos v
                                      JOIN marcas m ON m.id_marca = v.id_marca
                                      JOIN colores c ON c.id_color = v.id_color
                                      WHERE v.id_conductor = ?", "i", [$idConductor]);

// Los 7 tipos de documento con lo que el conductor ya subió (si subió algo)
$documentos = consultar($conexion, "SELECT t.*, d.id_documento, d.folio, d.vigencia, d.archivo, d.estado, d.observacion, d.fecha_carga
                                    FROM tipos_documento t
                                    LEFT JOIN documentos d ON d.id_tipo_documento = t.id_tipo_documento AND d.id_conductor = ?
                                    ORDER BY t.orden", "i", [$idConductor]);

$hoy = date('Y-m-d');
$enUnMes = date('Y-m-d', strtotime('+30 days'));

// ¿Puede cambiar su taxi y sus documentos? Solo mientras arma su solicitud o si se la rechazaron
$editable = in_array($conductor['estado_revision'], ['SIN_DOCUMENTOS', 'RECHAZADO']);

// Lo que le falta para poder enviar la solicitud
$faltantes = [];
if (!$vehiculo) {
    $faltantes[] = 'los datos del taxi';
}
foreach ($documentos as $d) {
    if (!$d['id_documento']) {
        $faltantes[] = $d['nombre'];
    } elseif ($d['estado'] === 'RECHAZADO') {
        $faltantes[] = $d['nombre'] . ' (corregir)';
    } elseif ($d['vigencia'] && $d['vigencia'] < $hoy) {
        $faltantes[] = $d['nombre'] . ' (vencido)';
    }
}
?>
