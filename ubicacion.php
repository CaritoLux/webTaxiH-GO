<?php
session_start();
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/includes/funciones.php';

$pagina = obtener_pagina($conexion, 'ubicacion');
$secciones = obtener_secciones($conexion, $pagina['id_pagina']);

$tituloPagina = 'Ubicación';
$paginaActiva = 'ubicacion';
$usaMapa = true;
$scripts = ['assets/js/ubicacion.js'];
require __DIR__ . '/includes/publico_arriba.php';
require __DIR__ . '/includes/encabezado_pagina.php';

// Enlace para que Google Maps trace la ruta desde donde esté la persona
$comoLlegar = 'https://www.google.com/maps/dir/?api=1&destination=' . $empresa['latitud'] . ',' . $empresa['longitud'];
?>

<section class="seccion pt-4 pt-lg-5">
    <div class="container contenedor">
        <div class="row g-4">
            <div class="col-lg-8">
                <div id="mapaCentral" class="mapa" style="height: 440px;"
                     data-lat="<?= e($empresa['latitud']) ?>" data-lng="<?= e($empresa['longitud']) ?>"
                     role="region" aria-label="Mapa con la ubicación de la central: <?= e($empresa['direccion']) ?>"></div>
            </div>
            <div class="col-lg-4">
                <div class="tarjeta d-flex flex-column">
                    <div class="d-flex gap-3 mb-4">
                        <?= icono('location_on', 'texto-secundario') ?>
                        <div><div class="dato-etiqueta">Dirección</div><?= e($empresa['direccion']) ?></div>
                    </div>
                    <a href="<?= e($comoLlegar) ?>" target="_blank" rel="noopener" class="btn btn-contorno">
                        <?= icono('navigation') ?> Cómo llegar
                    </a>
                </div>
            </div>
        </div>

        <?php if ($secciones): ?>
            <div class="mt-5">
                <?php require __DIR__ . '/includes/bloques_secciones.php'; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/publico_abajo.php'; ?>
