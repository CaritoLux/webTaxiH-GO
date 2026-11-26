<?php
// PASAJERO · Mis reportes: los problemas que reportó y lo que respondió la central
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
requerir_rol(['PASAJERO']);

$reportes = consultar($conexion, "SELECT i.folio, i.estado, i.descripcion, i.resolucion, i.fecha_reporte, i.fecha_atencion, i.fecha_cierre,
                                         t.nombre AS tipo, v.folio AS folio_viaje, v.destino_direccion, v.fecha_solicitud
                                  FROM incidentes i
                                  JOIN tipos_incidente t ON t.id_tipo_incidente = i.id_tipo_incidente
                                  JOIN viajes v ON v.id_viaje = i.id_viaje
                                  WHERE i.id_reportante = ?
                                  ORDER BY i.estado = 'CERRADO', i.fecha_reporte DESC", "i", [$_SESSION['usuario_id']]);

$tituloPagina = 'Mis reportes';
$paginaActiva = 'reportes';
require __DIR__ . '/../includes/app_arriba.php';
?>

<h1 class="t-headline-s mb-1">Mis reportes</h1>
<p class="texto-secundario mb-4">Aquí ves si la central ya revisó lo que reportaste y qué te respondió.</p>

<?php if (!$reportes): ?>
    <div class="vacio tarjeta">
        <?= icono('report') ?>
        <p class="t-title-m mb-1">No has reportado ningún problema</p>
        <p class="mb-3">Si algo sale mal en un viaje, abre el viaje en «Mis viajes» y toca «Reportar un problema».</p>
        <a href="viajes.php" class="btn btn-contorno">Ir a mis viajes</a>
    </div>
<?php else: ?>
    <?php foreach ($reportes as $r): ?>
        <article class="tarjeta mb-3" id="<?= e($r['folio']) ?>">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                <div>
                    <h2 class="t-title-m mb-0"><?= e($r['tipo']) ?></h2>
                    <div class="meta">Folio <?= e($r['folio']) ?> · reportado el <?= fecha_hora($r['fecha_reporte']) ?></div>
                </div>
                <?= etiqueta($r['estado']) ?>
            </div>

            <?php if ($r['descripcion']): ?>
                <p class="t-body-m texto-secundario mb-3">«<?= e($r['descripcion']) ?>»</p>
            <?php endif; ?>

            <?php if ($r['resolucion']): ?>
                <div class="aviso aviso-exito mb-3">
                    <?= icono('check_circle') ?>
                    <div><strong>Respuesta de la central<?= $r['fecha_cierre'] ? ' (' . fecha_corta($r['fecha_cierre']) . ')' : '' ?>:</strong> <?= e($r['resolucion']) ?></div>
                </div>
            <?php elseif ($r['estado'] === 'EN_ATENCION'): ?>
                <div class="aviso aviso-info mb-3">
                    <?= icono('schedule') ?>
                    <div>La central ya lo está atendiendo<?= $r['fecha_atencion'] ? ' desde el ' . fecha_corta($r['fecha_atencion']) : '' ?>. Te responderemos aquí.</div>
                </div>
            <?php else: ?>
                <div class="aviso aviso-info mb-3">
                    <?= icono('schedule') ?>
                    <div>La central todavía no lo revisa. Te responderemos aquí.</div>
                </div>
            <?php endif; ?>

            <a href="detalle.php?folio=<?= urlencode($r['folio_viaje']) ?>" class="t-body-m">
                Viaje del <?= fecha_corta($r['fecha_solicitud']) ?> a <?= e(explode(',', $r['destino_direccion'])[0]) ?>
            </a>
        </article>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/../includes/app_abajo.php'; ?>
