<?php
// PASAJERO · Mis viajes (historial con filtro de periodo)
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
requerir_rol(['PASAJERO']);

// Periodo por URL: ?periodo=7, 30 o todo
$periodos = ['30' => 'Últimos 30 días', '7' => 'Últimos 7 días', 'todo' => 'Todo'];
$periodo = array_key_exists($_GET['periodo'] ?? '', $periodos) ? $_GET['periodo'] : '30';

$sql = "SELECT folio, origen_direccion, destino_direccion, tarifa_estimada, estado, fecha_solicitud
        FROM viajes WHERE id_pasajero = ?";
if ($periodo !== 'todo') {
    $sql .= " AND fecha_solicitud >= DATE_SUB(NOW(), INTERVAL " . (int) $periodo . " DAY)";
}
$sql .= " ORDER BY fecha_solicitud DESC";
$viajes = consultar($conexion, $sql, "i", [$_SESSION['usuario_id']]);

// Para mostrar solo la primera parte de una dirección larga
function lugar_corto($direccion)
{
    return explode(',', $direccion)[0];
}

$tituloPagina = 'Mis viajes';
$paginaActiva = 'viajes';
require __DIR__ . '/../includes/app_arriba.php';
?>

<h1 class="t-headline-s mb-4">Mis viajes</h1>

<nav class="chips mb-4" aria-label="Periodo">
    <?php foreach ($periodos as $valor => $texto): ?>
        <a href="viajes.php?periodo=<?= $valor ?>" class="chip <?= $periodo === (string) $valor ? 'chip-activo' : '' ?>"
           <?= $periodo === (string) $valor ? 'aria-current="true"' : '' ?>>
            <?= $periodo === (string) $valor ? icono('check') : '' ?> <?= $texto ?>
        </a>
    <?php endforeach; ?>
</nav>

<?php if (!$viajes): ?>
    <div class="vacio tarjeta">
        <?= icono('history') ?>
        <p class="t-title-m mb-1">Aún no tienes viajes en este periodo</p>
        <p class="mb-0">Cuando pidas un taxi desde la app aparecerá aquí.</p>
    </div>
<?php else: ?>
    <ul class="lista tarjeta p-0">
        <?php foreach ($viajes as $v): ?>
            <li>
                <a class="lista-fila" href="detalle.php?folio=<?= urlencode($v['folio']) ?>">
                    <?= icono('local_taxi') ?>
                    <div class="lista-texto">
                        <div class="lista-titulo"><?= e(lugar_corto($v['origen_direccion'])) ?> → <?= e(lugar_corto($v['destino_direccion'])) ?></div>
                        <div class="lista-detalle"><?= fecha_hora($v['fecha_solicitud']) ?></div>
                    </div>
                    <div class="text-end">
                        <div class="t-body-l"><?= dinero($v['tarifa_estimada']) ?></div>
                        <?= etiqueta($v['estado']) ?>
                    </div>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php require __DIR__ . '/../includes/app_abajo.php'; ?>
