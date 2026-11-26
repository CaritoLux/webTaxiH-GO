<?php
session_start();
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/includes/funciones.php';

// Página y secciones (Historia, Misión, Visión, Valores...) desde la BD
$pagina = obtener_pagina($conexion, 'quienes-somos');
$secciones = obtener_secciones($conexion, $pagina['id_pagina']);

// Cifras reales del servicio
$taxis = consultar_uno($conexion, "SELECT COUNT(*) AS n FROM conductores WHERE estado_revision = 'APROBADO'")['n'];
$viajes = consultar_uno($conexion, "SELECT COUNT(*) AS n FROM viajes WHERE estado = 'FINALIZADO'")['n'];
$calificacion = consultar_uno($conexion, "SELECT AVG(c.estrellas) AS promedio FROM calificaciones c
                                          JOIN viajes v ON v.id_viaje = c.id_viaje
                                          WHERE c.id_autor = v.id_pasajero")['promedio'];

$tituloPagina = 'Quiénes somos';
$paginaActiva = 'quienes-somos';
require __DIR__ . '/includes/publico_arriba.php';
require __DIR__ . '/includes/encabezado_pagina.php';
?>

<section class="seccion pt-4 pt-lg-5">
    <div class="container contenedor">
        <ul class="row g-3 list-unstyled mb-5">
            <li class="col-sm-4">
                <div class="tarjeta h-100">
                    <div class="dato-grande"><?= $taxis ?></div>
                    <div class="texto-secundario">taxis autorizados por la central</div>
                </div>
            </li>
            <li class="col-sm-4">
                <div class="tarjeta h-100">
                    <div class="dato-grande"><?= $viajes ?></div>
                    <div class="texto-secundario">viajes terminados con la app</div>
                </div>
            </li>
            <li class="col-sm-4">
                <div class="tarjeta h-100">
                    <div class="dato-grande d-flex align-items-center gap-1">
                        <?= $calificacion ? number_format($calificacion, 1) : '—' ?>
                        <?= icono('star', 'estrella-llena') ?>
                    </div>
                    <div class="texto-secundario">calificación promedio de los pasajeros</div>
                </div>
            </li>
        </ul>

        <?php require __DIR__ . '/includes/bloques_secciones.php'; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/publico_abajo.php'; ?>
