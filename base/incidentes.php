<?php
// BASE · Incidentes y alertas de emergencia (las de prioridad alta van primero)
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
requerir_rol(['BASE']);

$filtros = [
    'pendientes'  => 'Por atender',
    'emergencias' => 'Emergencias',
    'cerrados'    => 'Cerrados',
    'todos'       => 'Todos',
];
$filtro = array_key_exists($_GET['filtro'] ?? '', $filtros) ? $_GET['filtro'] : 'pendientes';

$sql = "SELECT i.id_incidente, i.folio, i.estado, i.fecha_reporte, t.nombre AS tipo, t.prioridad, t.es_emergencia,
               u.nombre, u.apellido_paterno, u.rol, v.folio AS folio_viaje
        FROM incidentes i
        JOIN tipos_incidente t ON t.id_tipo_incidente = i.id_tipo_incidente
        JOIN usuarios u ON u.id_usuario = i.id_reportante
        JOIN viajes v ON v.id_viaje = i.id_viaje";
if ($filtro === 'pendientes') {
    $sql .= " WHERE i.estado <> 'CERRADO'";
} elseif ($filtro === 'emergencias') {
    $sql .= " WHERE t.es_emergencia = 1";
} elseif ($filtro === 'cerrados') {
    $sql .= " WHERE i.estado = 'CERRADO'";
}
$sql .= " ORDER BY i.estado = 'CERRADO', t.prioridad = 'ALTA' DESC, i.fecha_reporte DESC";
$incidentes = consultar($conexion, $sql);

$tituloPagina = 'Incidentes';
$paginaActiva = 'incidentes';
require __DIR__ . '/../includes/panel_arriba.php';
?>

<h1 class="t-headline-s mb-1">Incidentes y alertas</h1>
<p class="texto-secundario mb-4">Reportes de pasajeros y conductores sobre un viaje. Las emergencias y la prioridad alta aparecen primero.</p>

<nav class="chips mb-4" aria-label="Filtrar incidentes">
    <?php foreach ($filtros as $valor => $texto): ?>
        <a href="incidentes.php?filtro=<?= $valor ?>" class="chip <?= $filtro === $valor ? 'chip-activo' : '' ?>" <?= $filtro === $valor ? 'aria-current="true"' : '' ?>>
            <?= $filtro === $valor ? icono('check') : '' ?> <?= $texto ?>
        </a>
    <?php endforeach; ?>
</nav>

<?php if (!$incidentes): ?>
    <div class="vacio tarjeta"><?= icono('check_circle') ?><p class="mb-0">No hay incidentes en «<?= e($filtros[$filtro]) ?>».</p></div>
<?php else: ?>
    <div class="tabla-contenedor">
        <div class="table-responsive">
            <table class="table tabla">
                <thead>
                    <tr><th>Tipo</th><th>Folio</th><th>Reportó</th><th>Viaje</th><th>Fecha</th><th>Estado</th><th><span class="visually-hidden">Acción</span></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($incidentes as $i): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <?= icono($i['es_emergencia'] ? 'sos' : 'report', $i['prioridad'] === 'ALTA' ? '' : 'texto-secundario') ?>
                                    <span><?= e($i['tipo']) ?></span>
                                </div>
                                <?php if ($i['prioridad'] === 'ALTA'): ?><span class="estado estado-error mt-1">Prioridad alta</span><?php endif; ?>
                            </td>
                            <td><?= e($i['folio']) ?></td>
                            <td><?= e($i['nombre'] . ' ' . $i['apellido_paterno']) ?><div class="meta"><?= nombre_rol($i['rol']) ?></div></td>
                            <td><?= e($i['folio_viaje']) ?></td>
                            <td><?= fecha_hora($i['fecha_reporte']) ?></td>
                            <td><?= etiqueta($i['estado']) ?></td>
                            <td class="text-end"><a href="incidente.php?id=<?= $i['id_incidente'] ?>" class="btn btn-texto btn-sm">
                                <?= $i['estado'] === 'CERRADO' ? 'Ver' : 'Atender' ?><span class="visually-hidden"> <?= e($i['folio']) ?></span></a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/panel_abajo.php'; ?>
