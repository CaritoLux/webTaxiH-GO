<?php
session_start();
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/includes/funciones.php';

// 1. Textos de la página de inicio (los edita el administrador en Páginas › Inicio)
$pagina = obtener_pagina($conexion, 'inicio');
$secciones = [];
foreach (obtener_secciones($conexion, $pagina['id_pagina']) as $s) {
    $secciones[$s['clave'] ?? 'extra' . $s['id_seccion']] = $s;
}

// 2. Tarifa vigente (la cambia la base cuando el gobierno publica una nueva)
$tarifa = tarifa_vigente($conexion);

$tituloPagina = 'Pide tu taxi en Huejutla';
require __DIR__ . '/includes/publico_arriba.php';

// Imagen de la portada: la que subió el administrador o, si no hay, el logo del sitio
$imagenPortada = $pagina['imagen'] ? 'uploads/sitio/' . $pagina['imagen'] : $sitio['logo_url'];
$rol = $_SESSION['rol'] ?? '';
?>

    <!-- Portada -->
    <section class="py-5 fondo-suave">
        <div class="container py-lg-4">
            <div class="row align-items-center g-4">
                <div class="col-lg-6">
                    <h1 class="display-5 fw-bold mb-3"><?= e($pagina['titulo']) ?></h1>
                    <?php if (!empty($pagina['subtitulo'])): ?>
                        <p class="text-muted fs-5 mb-4"><?= e($pagina['subtitulo']) ?></p>
                    <?php endif; ?>

                    <?php if (!$logueado): ?>
                        <a href="registro.php" class="btn btn-amarillo rounded-pill px-4 py-2 fs-6">Crear cuenta</a>
                    <?php elseif ($rol === 'PASAJERO'): ?>
                        <a href="pasajero/viajes.php" class="btn btn-amarillo rounded-pill px-4 py-2 fs-6">Ver mis viajes</a>
                    <?php else: ?>
                        <a href="<?= panel_de_rol($rol) ?>" class="btn btn-amarillo rounded-pill px-4 py-2 fs-6">Ir a mi panel</a>
                    <?php endif; ?>
                </div>
                <div class="col-lg-6 text-center">
                    <div class="bg-white border rounded-4 p-4 shadow-sm d-inline-block w-100" style="max-width: 440px;">
                        <img src="<?= e($imagenPortada) ?>" alt="<?= $pagina['imagen'] ? '' : e($sitio['nombre']) ?>" class="img-fluid rounded-3" style="max-height: 260px; object-fit: contain;">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pasajeros y Taxistas -->
    <?php if (isset($secciones['pasajeros']) || isset($secciones['conductores'])): ?>
    <section class="py-5 border-bottom" aria-label="Cómo funciona">
        <div class="container">
            <div class="row g-5">
                <?php foreach (['pasajeros', 'conductores'] as $clave): ?>
                    <?php if (!isset($secciones[$clave])) continue; ?>
                    <?php $s = $secciones[$clave]; ?>
                    <div class="col-md-6" id="<?= $clave === 'pasajeros' ? 'pasajeros' : 'taxistas' ?>">
                        <h2 class="h3 fw-bold mb-2"><?= e($s['titulo']) ?></h2>
                        <p class="text-muted mb-4"><?= e($s['contenido']) ?></p>

                        <ol class="list-unstyled mb-4">
                            <?php foreach (obtener_pasos($conexion, $s['id_seccion']) as $i => $paso): ?>
                                <li class="d-flex align-items-start gap-3 mb-3">
                                    <span class="paso-num"><?= $i + 1 ?></span>
                                    <span class="pt-1"><?= e($paso['texto']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ol>

                        <?php if ($clave === 'pasajeros'): ?>
                            <?php if (!empty($sitio['enlace_app'])): ?>
                                <a href="<?= e($sitio['enlace_app']) ?>" rel="noopener" class="btn btn-outline-dark rounded-pill px-4 fw-semibold">Descarga la app</a>
                            <?php else: ?>
                                <p class="text-muted small mb-0">La app para Android estará disponible muy pronto.</p>
                            <?php endif; ?>
                        <?php elseif (!$logueado): ?>
                            <a href="registro.php?rol=CONDUCTOR" class="btn btn-outline-dark rounded-pill px-4 fw-semibold">Registrarme como taxista</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Tarifa vigente -->
    <?php if (isset($secciones['tarifa']) && $tarifa): ?>
    <section id="tarifa" class="py-5 fondo-suave">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-6">
                    <h2 class="h3 fw-bold mb-2"><?= e($secciones['tarifa']['titulo']) ?></h2>
                    <p class="text-muted mb-0"><?= nl2br(e($secciones['tarifa']['contenido'])) ?></p>
                </div>
                <div class="col-lg-6">
                    <div class="bg-white border rounded-4 p-4 shadow-sm">
                        <div class="text-muted small mb-1">Banderazo (incluye los primeros <?= e(rtrim(rtrim($tarifa['km_incluidos'], '0'), '.')) ?> km)</div>
                        <div class="display-6 fw-bold mb-1"><?= dinero($tarifa['banderazo']) ?></div>
                        <div class="fw-medium mb-2">más <?= dinero($tarifa['costo_km']) ?> por cada kilómetro adicional</div>
                        <div class="text-muted small">Vigente desde el <?= fecha_corta($tarifa['vigente_desde']) ?> · pago en efectivo al conductor</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Contacto -->
    <section id="contacto" class="py-5">
        <div class="container">
            <h2 class="h3 fw-bold mb-4">Contacto</h2>
            <ul class="row g-3 list-unstyled mb-4">
                <li class="col-sm-6 col-lg-3">
                    <a href="tel:<?= e($empresa['telefono']) ?>" class="card-suave p-3 h-100 d-block">
                        <?= icono('call', 'text-muted d-block mb-2') ?>
                        <span class="fw-medium"><?= e(telefono_bonito($empresa['telefono'])) ?></span>
                    </a>
                </li>
                <li class="col-sm-6 col-lg-3">
                    <a href="mailto:<?= e($empresa['correo']) ?>" class="card-suave p-3 h-100 d-block">
                        <?= icono('mail', 'text-muted d-block mb-2') ?>
                        <span class="fw-medium text-break"><?= e($empresa['correo']) ?></span>
                    </a>
                </li>
                <li class="col-sm-6 col-lg-3">
                    <div class="card-suave p-3 h-100">
                        <?= icono('schedule', 'text-muted d-block mb-2') ?>
                        <span class="fw-medium"><?= e($empresa['horario']) ?></span>
                    </div>
                </li>
                <li class="col-sm-6 col-lg-3">
                    <a href="ubicacion.php" class="card-suave p-3 h-100 d-block">
                        <?= icono('location_on', 'text-muted d-block mb-2') ?>
                        <span class="fw-medium"><?= e($empresa['direccion']) ?></span>
                    </a>
                </li>
            </ul>
            <a href="contacto.php" class="btn btn-outline-dark rounded-pill px-4 fw-semibold">Escríbenos un mensaje</a>
        </div>
    </section>

<?php require __DIR__ . '/includes/publico_abajo.php'; ?>
