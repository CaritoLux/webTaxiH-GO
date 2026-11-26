<?php
// PASAJERO · Detalle de un viaje: datos, calificación y reportes
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
requerir_rol(['PASAJERO']);

$idPasajero = $_SESSION['usuario_id'];
$viaje = consultar_uno($conexion, "SELECT v.*, m.descripcion AS motivo, u.nombre AS c_nombre, u.apellido_paterno AS c_paterno,
                                          ve.numero_economico, ve.placas, ve.modelo, ma.nombre AS marca, co.nombre AS color
                                   FROM viajes v
                                   LEFT JOIN motivos_cancelacion m ON m.id_motivo = v.id_motivo_cancelacion
                                   LEFT JOIN usuarios u ON u.id_usuario = v.id_conductor
                                   LEFT JOIN vehiculos ve ON ve.id_conductor = v.id_conductor
                                   LEFT JOIN marcas ma ON ma.id_marca = ve.id_marca
                                   LEFT JOIN colores co ON co.id_color = ve.id_color
                                   WHERE v.folio = ? AND v.id_pasajero = ?", "si", [$_GET['folio'] ?? '', $idPasajero]);
if (!$viaje) {
    header('Location: viajes.php');
    exit;
}

$activo = in_array($viaje['estado'], ['BUSCANDO', 'ASIGNADO', 'EN_ORIGEN', 'EN_CURSO']);
$calificacion = consultar_uno($conexion, "SELECT estrellas, comentario FROM calificaciones WHERE id_viaje = ? AND id_autor = ?",
    "ii", [$viaje['id_viaje'], $idPasajero]);
$reportes = consultar($conexion, "SELECT i.folio, i.estado, i.fecha_reporte, i.resolucion, t.nombre AS tipo
                                  FROM incidentes i JOIN tipos_incidente t ON t.id_tipo_incidente = i.id_tipo_incidente
                                  WHERE i.id_viaje = ? AND i.id_reportante = ? ORDER BY i.fecha_reporte DESC",
    "ii", [$viaje['id_viaje'], $idPasajero]);

$tituloPagina = 'Detalle del viaje';
$paginaActiva = 'viajes';
require __DIR__ . '/../includes/app_arriba.php';
?>

<nav class="migas"><a href="viajes.php"><?= icono('arrow_back') ?> Mis viajes</a></nav>
<div class="d-flex flex-wrap align-items-center gap-3 mb-4">
    <h1 class="t-headline-s mb-0">Viaje del <?= fecha_corta($viaje['fecha_solicitud']) ?></h1>
    <?= etiqueta($viaje['estado']) ?>
</div>

<?php if ($activo): ?>
    <div class="aviso aviso-info mb-4"><?= icono('local_taxi') ?>
        <div>Este viaje está en curso. Síguelo en la app.</div>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-md-7">
        <ul class="lista tarjeta p-0 mb-4">
            <li class="lista-fila"><?= icono('trip_origin') ?>
                <div class="lista-texto"><div class="lista-detalle">Recogida · <?= date('H:i', strtotime($viaje['fecha_solicitud'])) ?></div><div class="t-body-m"><?= e($viaje['origen_direccion']) ?></div></div>
            </li>
            <li class="lista-fila"><?= icono('location_on') ?>
                <div class="lista-texto"><div class="lista-detalle">Destino<?= $viaje['fecha_fin'] ? ' · llegaste a las ' . date('H:i', strtotime($viaje['fecha_fin'])) : '' ?></div><div class="t-body-m"><?= e($viaje['destino_direccion']) ?></div></div>
            </li>
            <li class="lista-fila"><?= icono('payments') ?>
                <div class="lista-texto"><div class="lista-detalle">Tarifa · efectivo</div><div class="t-body-m"><?= dinero($viaje['tarifa_estimada']) ?> · <?= kilometros($viaje['distancia_m']) ?> · <?= max(1, round($viaje['duracion_s'] / 60)) ?> min</div></div>
            </li>
            <?php if ($viaje['estado'] === 'CANCELADO'): ?>
                <li class="lista-fila"><?= icono('close') ?>
                    <div class="lista-texto"><div class="lista-detalle">Cancelado por <?= $viaje['cancelado_por'] === 'CONDUCTOR' ? 'el conductor' : 'ti' ?></div>
                        <div class="t-body-m"><?= e($viaje['motivo']) ?><?= $viaje['comentario_cancelacion'] ? ': ' . e($viaje['comentario_cancelacion']) : '' ?></div></div>
                </li>
            <?php endif; ?>
        </ul>

        <?php if ($viaje['id_conductor']): ?>
            <div class="tarjeta mb-4">
                <h2 class="t-title-m mb-3">Conductor y taxi</h2>
                <p class="mb-1"><?= e($viaje['c_nombre'] . ' ' . $viaje['c_paterno']) ?></p>
                <p class="texto-secundario mb-0"><?= e($viaje['marca'] . ' ' . $viaje['modelo'] . ' ' . mb_strtolower($viaje['color'])) ?> · No. <?= e($viaje['numero_economico']) ?> · <?= e($viaje['placas']) ?></p>
            </div>
        <?php endif; ?>

        <p class="meta">Folio <?= e($viaje['folio']) ?></p>
    </div>

    <div class="col-md-5">
        <?php if ($viaje['estado'] === 'FINALIZADO' && $viaje['id_conductor']): ?>
            <div class="tarjeta mb-4">
                <h2 class="t-title-m mb-2">Tu calificación</h2>
                <?php if ($calificacion): ?>
                    <div class="d-flex gap-1 mb-2" role="img" aria-label="<?= $calificacion['estrellas'] ?> de 5 estrellas">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <?= icono('star', $i <= $calificacion['estrellas'] ? 'estrella-llena' : 'texto-secundario') ?>
                        <?php endfor; ?>
                    </div>
                    <?php if ($calificacion['comentario']): ?><p class="texto-secundario mb-0">«<?= e($calificacion['comentario']) ?>»</p><?php endif; ?>
                <?php else: ?>
                    <form method="POST" action="calificar.php">
                        <input type="hidden" name="folio" value="<?= e($viaje['folio']) ?>">
                        <fieldset>
                            <legend class="t-body-m texto-secundario mb-2">Aún no calificas este viaje.</legend>
                            <div class="estrellas mb-3">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" name="estrellas" id="estrella<?= $i ?>" value="<?= $i ?>" required>
                                    <label for="estrella<?= $i ?>" title="<?= $i ?> de 5"><?= icono('star') ?><span class="visually-hidden"><?= $i ?> de 5 estrellas</span></label>
                                <?php endfor; ?>
                            </div>
                        </fieldset>
                        <label for="comentario" class="visually-hidden">Comentario</label>
                        <textarea name="comentario" id="comentario" class="form-control mb-3" rows="2" maxlength="200" placeholder="Comentario (opcional)"></textarea>
                        <button type="submit" class="btn btn-principal w-100">Enviar calificación</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="tarjeta">
            <h2 class="t-title-m mb-2">¿Tuviste un problema?</h2>
            <?php foreach ($reportes as $r): ?>
                <div class="border-bottom pb-2 mb-2">
                    <div class="d-flex justify-content-between gap-2"><span class="t-body-m"><?= e($r['tipo']) ?></span><?= etiqueta($r['estado']) ?></div>
                    <div class="meta"><?= e($r['folio']) ?> · <?= fecha_corta($r['fecha_reporte']) ?></div>
                    <?php if ($r['resolucion']): ?><div class="t-body-m texto-secundario mt-1">Respuesta: <?= e($r['resolucion']) ?></div><?php endif; ?>
                </div>
            <?php endforeach; ?>
            <?php if ($viaje['id_conductor'] && !$activo): ?>
                <p class="t-body-m texto-secundario">Cuéntale a la central qué pasó: un cobro distinto, un objeto olvidado o el trato del conductor.</p>
                <a href="reportar.php?folio=<?= urlencode($viaje['folio']) ?>" class="btn btn-contorno w-100"><?= icono('report') ?> Reportar un problema</a>
            <?php elseif (!$reportes): ?>
                <p class="t-body-m texto-secundario mb-0">Si el viaje no tuvo conductor asignado no hay nada que reportar. Para otras dudas usa <a href="../contacto.php">Contacto</a>.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/app_abajo.php'; ?>
