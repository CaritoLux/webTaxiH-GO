<?php
// ADMINISTRADOR · Mensajes que llegan desde el formulario de Contacto
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
requerir_rol(['ADMINISTRADOR']);

$soloNuevos = ($_GET['ver'] ?? '') !== 'todos';
$mensajes = consultar($conexion, "SELECT id_mensaje, nombre, correo, asunto, mensaje, leido, fecha FROM mensajes_contacto"
    . ($soloNuevos ? " WHERE leido = 0" : "") . " ORDER BY fecha DESC");

$tituloPagina = 'Mensajes';
$paginaActiva = 'mensajes';
require __DIR__ . '/../includes/panel_arriba.php';
?>

<h1 class="t-headline-s mb-1">Mensajes de contacto</h1>
<p class="texto-secundario mb-4">Lo que escriben las personas en la página Contacto. Respóndeles a su correo.</p>

<nav class="chips mb-4" aria-label="Filtrar mensajes">
    <a href="mensajes.php" class="chip <?= $soloNuevos ? 'chip-activo' : '' ?>"><?= $soloNuevos ? icono('check') : '' ?> Sin leer</a>
    <a href="mensajes.php?ver=todos" class="chip <?= !$soloNuevos ? 'chip-activo' : '' ?>"><?= !$soloNuevos ? icono('check') : '' ?> Todos</a>
</nav>

<?php if (!$mensajes): ?>
    <div class="vacio tarjeta"><?= icono('mail') ?><p class="mb-0"><?= $soloNuevos ? 'No hay mensajes sin leer.' : 'Aún no llegan mensajes.' ?></p></div>
<?php else: ?>
    <ul class="lista tarjeta p-0">
        <?php foreach ($mensajes as $m): ?>
            <li>
                <a class="lista-fila" href="mensaje.php?id=<?= $m['id_mensaje'] ?>">
                    <?= icono('mail') ?>
                    <div class="lista-texto">
                        <div class="lista-titulo <?= $m['leido'] ? '' : 'fw-bold' ?>"><?= e($m['asunto']) ?></div>
                        <div class="lista-detalle text-truncate"><?= e($m['nombre']) ?> · <?= e(mb_strimwidth($m['mensaje'], 0, 90, '…')) ?></div>
                    </div>
                    <div class="text-end">
                        <div class="meta"><?= fecha_hora($m['fecha']) ?></div>
                        <?php if (!$m['leido']): ?><span class="estado estado-activo">Nuevo</span><?php endif; ?>
                    </div>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php require __DIR__ . '/../includes/panel_abajo.php'; ?>
