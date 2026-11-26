<?php
// =====================================================================
//  BASE · Operación: lo pendiente de un vistazo (aquí entra la base al iniciar sesión).
//  Cada tarjeta lleva a su módulo.
// =====================================================================
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
requerir_rol(['BASE']);

$porRevisar = consultar_uno($conexion, "SELECT COUNT(*) AS n FROM conductores WHERE estado_revision = 'EN_REVISION'")['n'];
$abiertos   = consultar_uno($conexion, "SELECT COUNT(*) AS n FROM incidentes WHERE estado <> 'CERRADO'")['n'];
$viajesHoy  = consultar_uno($conexion, "SELECT COUNT(*) AS n FROM viajes WHERE DATE(fecha_solicitud) = CURDATE()")['n'];
$autorizados = consultar_uno($conexion, "SELECT COUNT(*) AS n FROM conductores WHERE estado_revision = 'APROBADO'")['n'];
$tarifa = tarifa_vigente($conexion);

$solicitudes = consultar($conexion, "SELECT u.id_usuario, u.nombre, u.apellido_paterno, c.fecha_envio, v.numero_economico
                                     FROM conductores c
                                     JOIN usuarios u ON u.id_usuario = c.id_usuario
                                     LEFT JOIN vehiculos v ON v.id_conductor = c.id_usuario
                                     WHERE c.estado_revision = 'EN_REVISION'
                                     ORDER BY c.fecha_envio LIMIT 5");

$incidentes = consultar($conexion, "SELECT i.id_incidente, i.folio, i.estado, i.fecha_reporte, t.nombre AS tipo, t.prioridad
                                    FROM incidentes i JOIN tipos_incidente t ON t.id_tipo_incidente = i.id_tipo_incidente
                                    WHERE i.estado <> 'CERRADO'
                                    ORDER BY t.prioridad = 'ALTA' DESC, i.fecha_reporte LIMIT 5");

$tituloPagina = 'Panel de la base';
$paginaActiva = 'panel';
require __DIR__ . '/../includes/panel_arriba.php';
?>

<h1 class="t-headline-s mb-1">Hola, <?= e($_SESSION['nombre']) ?></h1>
<p class="texto-secundario mb-4">Esto es lo que tiene pendiente la central hoy, <?= fecha_corta(date('Y-m-d')) ?>.</p>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <a href="solicitudes.php" class="tarjeta kpi">
            <div class="kpi-numero"><?= $porRevisar ?></div>
            <div class="kpi-texto">solicitudes de conductores por revisar</div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="incidentes.php" class="tarjeta kpi">
            <div class="kpi-numero"><?= $abiertos ?></div>
            <div class="kpi-texto">incidentes sin cerrar</div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="solicitudes.php?estado=APROBADO" class="tarjeta kpi">
            <div class="kpi-numero"><?= $autorizados ?></div>
            <div class="kpi-texto">taxis autorizados · <?= $viajesHoy ?> viajes hoy</div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="tarifa.php" class="tarjeta kpi">
            <div class="kpi-numero"><?= $tarifa ? dinero($tarifa['banderazo']) : '—' ?></div>
            <div class="kpi-texto">banderazo vigente<?= $tarifa ? ' · ' . dinero($tarifa['costo_km']) . ' por km' : '' ?></div>
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <section class="tarjeta p-0 h-100">
            <div class="d-flex justify-content-between align-items-center p-3 pb-2">
                <h2 class="t-title-m mb-0">Solicitudes en espera</h2>
                <a href="solicitudes.php" class="btn btn-texto btn-sm">Ver todas</a>
            </div>
            <?php if (!$solicitudes): ?>
                <p class="px-3 pb-3 texto-secundario mb-0">No hay solicitudes pendientes.</p>
            <?php else: ?>
                <ul class="lista">
                    <?php foreach ($solicitudes as $s): ?>
                        <li>
                            <a class="lista-fila" href="solicitud.php?id=<?= $s['id_usuario'] ?>">
                                <?= icono('how_to_reg') ?>
                                <div class="lista-texto">
                                    <div class="lista-titulo"><?= e($s['nombre'] . ' ' . $s['apellido_paterno']) ?></div>
                                    <div class="lista-detalle">No. económico <?= e($s['numero_economico'] ?? '—') ?> · enviada <?= fecha_corta($s['fecha_envio']) ?></div>
                                </div>
                                <span class="t-label-l" style="color: var(--color-primary);">Revisar</span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>
    <div class="col-lg-6">
        <section class="tarjeta p-0 h-100">
            <div class="d-flex justify-content-between align-items-center p-3 pb-2">
                <h2 class="t-title-m mb-0">Incidentes por atender</h2>
                <a href="incidentes.php" class="btn btn-texto btn-sm">Ver todos</a>
            </div>
            <?php if (!$incidentes): ?>
                <p class="px-3 pb-3 texto-secundario mb-0">No hay incidentes abiertos.</p>
            <?php else: ?>
                <ul class="lista">
                    <?php foreach ($incidentes as $i): ?>
                        <li>
                            <a class="lista-fila" href="incidente.php?id=<?= $i['id_incidente'] ?>">
                                <?= icono($i['prioridad'] === 'ALTA' ? 'sos' : 'report') ?>
                                <div class="lista-texto">
                                    <div class="lista-titulo"><?= e($i['tipo']) ?></div>
                                    <div class="lista-detalle"><?= e($i['folio']) ?> · <?= fecha_hora($i['fecha_reporte']) ?></div>
                                </div>
                                <?php if ($i['prioridad'] === 'ALTA'): ?><span class="estado estado-error">Prioridad alta</span><?php endif; ?>
                                <?= etiqueta($i['estado']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php require __DIR__ . '/../includes/panel_abajo.php'; ?>
