<?php
session_start();
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/includes/funciones.php';

// Aviso de privacidad: página y secciones desde la BD
$pagina = obtener_pagina($conexion, 'privacidad');
$secciones = obtener_secciones($conexion, $pagina['id_pagina']);

// Fecha de la última actualización (de la página o de cualquiera de sus secciones)
$ultima = $pagina['fecha_actualizacion'];
foreach ($secciones as $s) {
    if ($s['fecha_actualizacion'] > $ultima) {
        $ultima = $s['fecha_actualizacion'];
    }
}

$tituloPagina = 'Aviso de privacidad';
$paginaActiva = 'privacidad';
require __DIR__ . '/includes/publico_arriba.php';
require __DIR__ . '/includes/encabezado_pagina.php';
?>

<section class="seccion pt-4 pt-lg-5">
    <div class="container contenedor">
        <p class="meta mb-4">Última actualización: <?= fecha_corta($ultima) ?></p>
        <?php require __DIR__ . '/includes/bloques_secciones.php'; ?>

        <div class="aviso aviso-info mt-4">
            <?= icono('mail') ?>
            <div>¿Dudas sobre tus datos? Escribe a <a href="mailto:<?= e($empresa['correo']) ?>"><?= e($empresa['correo']) ?></a>
                o usa el <a href="contacto.php">formulario de contacto</a>.</div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/publico_abajo.php'; ?>
