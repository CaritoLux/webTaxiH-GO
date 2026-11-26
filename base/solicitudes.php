<?php
// BASE · Solicitudes de conductores (lista con filtro por estado y búsqueda)
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
requerir_rol(['BASE']);

$filtros = [
    'EN_REVISION'    => 'Por revisar',
    'APROBADO'       => 'Aprobados',
    'RECHAZADO'      => 'Rechazados',
    'SIN_DOCUMENTOS' => 'Sin enviar',
    'TODOS'          => 'Todos',
];
$estado = array_key_exists($_GET['estado'] ?? '', $filtros) ? $_GET['estado'] : 'EN_REVISION';
$busqueda = trim($_GET['q'] ?? '');

$sql = "SELECT u.id_usuario, u.nombre, u.apellido_paterno, u.apellido_materno, u.telefono, u.estado AS estado_cuenta,
               c.estado_revision, c.fecha_envio, v.numero_economico, v.placas,
               (SELECT COUNT(*) FROM documentos d WHERE d.id_conductor = c.id_usuario) AS docs,
               (SELECT COUNT(*) FROM documentos d WHERE d.id_conductor = c.id_usuario AND d.estado = 'ACEPTADO') AS aceptados
        FROM conductores c
        JOIN usuarios u ON u.id_usuario = c.id_usuario
        LEFT JOIN vehiculos v ON v.id_conductor = c.id_usuario
        WHERE 1 = 1";
$tipos = '';
$valores = [];
if ($estado !== 'TODOS') {
    $sql .= " AND c.estado_revision = ?";
    $tipos .= 's';
    $valores[] = $estado;
}
if ($busqueda !== '') {
    $sql .= " AND (CONCAT(u.nombre, ' ', u.apellido_paterno) LIKE ? OR u.correo LIKE ? OR v.placas LIKE ? OR v.numero_economico = ?)";
    $tipos .= 'ssss';
    array_push($valores, "%$busqueda%", "%$busqueda%", "%$busqueda%", $busqueda);
}
$sql .= " ORDER BY c.fecha_envio IS NULL, c.fecha_envio, u.fecha_registro DESC";
$conductores = consultar($conexion, $sql, $tipos, $valores);
$totalDocs = consultar_uno($conexion, "SELECT COUNT(*) AS n FROM tipos_documento")['n'];

$tituloPagina = 'Solicitudes de conductores';
$paginaActiva = 'solicitudes';
require __DIR__ . '/../includes/panel_arriba.php';
?>

<h1 class="t-headline-s mb-1">Solicitudes de conductores</h1>
<p class="texto-secundario mb-4">Revisa los documentos de cada taxista antes de autorizarlo para trabajar con la app.</p>

<div class="d-flex flex-wrap gap-3 justify-content-between align-items-center mb-4">
    <nav class="chips" aria-label="Filtrar por estado">
        <?php foreach ($filtros as $valor => $texto): ?>
            <a href="solicitudes.php?estado=<?= $valor ?><?= $busqueda !== '' ? '&q=' . urlencode($busqueda) : '' ?>"
               class="chip <?= $estado === $valor ? 'chip-activo' : '' ?>" <?= $estado === $valor ? 'aria-current="true"' : '' ?>>
                <?= $estado === $valor ? icono('check') : '' ?> <?= $texto ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <form method="GET" action="solicitudes.php" class="d-flex gap-2" role="search">
        <input type="hidden" name="estado" value="<?= e($estado) ?>">
        <label for="q" class="visually-hidden">Buscar conductor</label>
        <input type="search" id="q" name="q" value="<?= e($busqueda) ?>" class="form-control form-control-sm" placeholder="Nombre, correo, placas o número">
        <button type="submit" class="btn btn-contorno btn-sm">Buscar</button>
    </form>
</div>

<?php if (!$conductores): ?>
    <div class="vacio tarjeta"><?= icono('how_to_reg') ?><p class="mb-0">No hay solicitudes en «<?= e($filtros[$estado]) ?>».</p></div>
<?php else: ?>
    <div class="tabla-contenedor">
        <div class="table-responsive">
            <table class="table tabla">
                <thead>
                    <tr><th>Conductor</th><th>Taxi</th><th>Documentos</th><th>Enviada</th><th>Estado</th><th><span class="visually-hidden">Acción</span></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($conductores as $c): ?>
                        <tr>
                            <td>
                                <div class="t-body-l"><?= e(nombre_completo($c)) ?></div>
                                <div class="meta"><?= e(telefono_bonito($c['telefono'])) ?><?= $c['estado_cuenta'] === 'SUSPENDIDO' ? ' · cuenta suspendida' : '' ?></div>
                            </td>
                            <td><?= $c['numero_economico'] ? 'No. ' . e($c['numero_economico']) . '<div class="meta">' . e($c['placas']) . '</div>' : '<span class="texto-secundario">Sin registrar</span>' ?></td>
                            <td><?= $c['docs'] < $totalDocs ? $c['docs'] . ' de ' . $totalDocs . ' subidos' : $c['aceptados'] . ' de ' . $totalDocs . ' aceptados' ?></td>
                            <td><?= $c['fecha_envio'] ? fecha_corta($c['fecha_envio']) : '—' ?></td>
                            <td><?= etiqueta($c['estado_revision']) ?></td>
                            <td class="text-end">
                                <a href="solicitud.php?id=<?= $c['id_usuario'] ?>" class="btn btn-texto btn-sm">
                                    <?= $c['estado_revision'] === 'EN_REVISION' ? 'Revisar' : 'Ver' ?><span class="visually-hidden"> a <?= e($c['nombre']) ?></span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/panel_abajo.php'; ?>
