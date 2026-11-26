<?php
// =====================================================================
//  PASAJERO · Inicio de su cuenta en la web
//  En la web el pasajero NO pide taxi (eso se hace en la app). Aquí ve
//  el aviso de sus reportes, sus viajes sin calificar y su último viaje.
// =====================================================================
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
requerir_rol(['PASAJERO']);

$idPasajero = $_SESSION['usuario_id'];

// Viaje que está pasando ahora (se pidió y se sigue en la app)
$activo = consultar_uno($conexion, "SELECT folio, estado, destino_direccion FROM viajes
                                    WHERE id_pasajero = ? AND estado IN ('BUSCANDO','ASIGNADO','EN_ORIGEN','EN_CURSO')
                                    ORDER BY fecha_solicitud DESC LIMIT 1", "i", [$idPasajero]);

// Reportes: los que la central ya contestó y los que siguen abiertos
$reportes = consultar($conexion, "SELECT i.folio, i.estado, i.resolucion, i.fecha_reporte, t.nombre AS tipo, v.folio AS folio_viaje
                                  FROM incidentes i
                                  JOIN tipos_incidente t ON t.id_tipo_incidente = i.id_tipo_incidente
                                  JOIN viajes v ON v.id_viaje = i.id_viaje
                                  WHERE i.id_reportante = ?
                                  ORDER BY i.estado = 'CERRADO', i.fecha_reporte DESC LIMIT 3", "i", [$idPasajero]);
$abiertos = consultar_uno($conexion, "SELECT COUNT(*) AS n FROM incidentes WHERE id_reportante = ? AND estado <> 'CERRADO'",
    "i", [$idPasajero])['n'];

// Viajes terminados que todavía no califica
$sinCalificar = consultar($conexion, "SELECT v.folio, v.destino_direccion, v.fecha_solicitud
                                      FROM viajes v
                                      LEFT JOIN calificaciones c ON c.id_viaje = v.id_viaje AND c.id_autor = v.id_pasajero
                                      WHERE v.id_pasajero = ? AND v.estado = 'FINALIZADO' AND v.id_conductor IS NOT NULL
                                            AND c.id_calificacion IS NULL
                                      ORDER BY v.fecha_solicitud DESC LIMIT 3", "i", [$idPasajero]);

$ultimo = consultar_uno($conexion, "SELECT folio, origen_direccion, destino_direccion, tarifa_estimada, estado, fecha_solicitud
                                    FROM viajes WHERE id_pasajero = ? ORDER BY fecha_solicitud DESC LIMIT 1", "i", [$idPasajero]);

$tituloPagina = 'Mi cuenta';
$paginaActiva = 'inicio';
require __DIR__ . '/../includes/app_arriba.php';
?>

<h1 class="t-headline-s mb-4">Hola, <?= e($_SESSION['nombre']) ?></h1>

<?php if ($activo): ?>
    <div class="aviso aviso-info mb-4" role="status">
        <?= icono('local_taxi') ?>
        <div>Tienes un viaje en curso a <?= e(explode(',', $activo['destino_direccion'])[0]) ?>. Síguelo en la app.</div>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-7">
        <!-- Aviso de los reportes que hizo -->
        <section class="tarjeta p-0 mb-4" aria-labelledby="tituloReportes">
            <div class="d-flex justify-content-between align-items-center p-3 pb-2">
                <h2 class="t-title-m mb-0" id="tituloReportes">
                    Mis reportes
                    <?php if ($abiertos > 0): ?><span class="contador ms-1" aria-label="<?= $abiertos ?> en revisión"><?= $abiertos ?></span><?php endif; ?>
                </h2>
                <?php if ($reportes): ?><a href="reportes.php" class="btn btn-texto btn-sm">Ver todos</a><?php endif; ?>
            </div>
            <?php if (!$reportes): ?>
                <p class="px-3 pb-3 texto-secundario mb-0">No has reportado ningún problema. Si algo sale mal en un viaje, repórtalo desde <a href="viajes.php">Mis viajes</a>.</p>
            <?php else: ?>
                <ul class="lista">
                    <?php foreach ($reportes as $r): ?>
                        <li>
                            <a class="lista-fila" href="reportes.php#<?= e($r['folio']) ?>">
                                <?= icono('report') ?>
                                <div class="lista-texto">
                                    <div class="lista-titulo"><?= e($r['tipo']) ?></div>
                                    <div class="lista-detalle">
                                        <?= $r['resolucion'] ? 'La central respondió: ' . e(mb_strimwidth($r['resolucion'], 0, 90, '…')) : 'Folio ' . e($r['folio']) . ' · ' . fecha_corta($r['fecha_reporte']) . ' · la central lo está revisando' ?>
                                    </div>
                                </div>
                                <?= etiqueta($r['estado']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <!-- Viajes sin calificar -->
        <?php if ($sinCalificar): ?>
            <section class="tarjeta p-0 mb-4" aria-labelledby="tituloCalificar">
                <h2 class="t-title-m p-3 pb-2 mb-0" id="tituloCalificar">Califica a tu conductor</h2>
                <ul class="lista">
                    <?php foreach ($sinCalificar as $v): ?>
                        <li>
                            <a class="lista-fila" href="detalle.php?folio=<?= urlencode($v['folio']) ?>">
                                <?= icono('star') ?>
                                <div class="lista-texto">
                                    <div class="lista-titulo"><?= e(explode(',', $v['destino_direccion'])[0]) ?></div>
                                    <div class="lista-detalle"><?= fecha_hora($v['fecha_solicitud']) ?></div>
                                </div>
                                <span class="t-label-l">Calificar</span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <!-- Último viaje -->
        <section class="tarjeta p-0" aria-labelledby="tituloUltimo">
            <div class="d-flex justify-content-between align-items-center p-3 pb-2">
                <h2 class="t-title-m mb-0" id="tituloUltimo">Tu último viaje</h2>
                <?php if ($ultimo): ?><a href="viajes.php" class="btn btn-texto btn-sm">Mis viajes</a><?php endif; ?>
            </div>
            <?php if (!$ultimo): ?>
                <p class="px-3 pb-3 texto-secundario mb-0">Aún no tienes viajes. Cuando pidas un taxi desde la app aparecerán aquí.</p>
            <?php else: ?>
                <ul class="lista">
                    <li>
                        <a class="lista-fila" href="detalle.php?folio=<?= urlencode($ultimo['folio']) ?>">
                            <?= icono('local_taxi') ?>
                            <div class="lista-texto">
                                <div class="lista-titulo"><?= e(explode(',', $ultimo['origen_direccion'])[0]) ?> → <?= e(explode(',', $ultimo['destino_direccion'])[0]) ?></div>
                                <div class="lista-detalle"><?= fecha_hora($ultimo['fecha_solicitud']) ?> · <?= dinero($ultimo['tarifa_estimada']) ?></div>
                            </div>
                            <?= etiqueta($ultimo['estado']) ?>
                        </a>
                    </li>
                </ul>
            <?php endif; ?>
        </section>
    </div>

    <div class="col-lg-5">
        <!-- El taxi se pide en la app -->
        <section class="tarjeta" aria-labelledby="tituloApp">
            <div class="d-flex align-items-center gap-3 mb-3">
                <img src="<?= e($sitio['favicon_url']) ?>" alt="" width="48" height="48">
                <h2 class="t-title-m mb-0" id="tituloApp">Pide tu taxi desde la app</h2>
            </div>
            <p class="texto-secundario">En la app escribes a dónde vas, ves la tarifa antes de pedir y sigues tu taxi en el mapa. Entra con el mismo correo y contraseña de esta página.</p>
            <?php if (!empty($sitio['enlace_app'])): ?>
                <a href="<?= e($sitio['enlace_app']) ?>" rel="noopener" class="btn btn-principal w-100"><?= icono('download') ?> Descarga la app</a>
            <?php else: ?>
                <p class="t-body-m mb-0">La app para Android estará disponible muy pronto.</p>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php require __DIR__ . '/../includes/app_abajo.php'; ?>
