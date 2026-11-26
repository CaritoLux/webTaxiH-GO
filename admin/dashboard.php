<?php
// =====================================================================
//  ADMINISTRADOR · Dashboard
//  Acceso directo a todo lo que se edita de la página web (nada de la
//  página pública está escrito en el código). La operación la hace la
//  base y los catálogos se manejan en la app de escritorio.
// =====================================================================
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
requerir_rol(['ADMINISTRADOR']);

$sinLeer     = consultar_uno($conexion, "SELECT COUNT(*) AS n FROM mensajes_contacto WHERE leido = 0")['n'];
$cuentasBase = consultar_uno($conexion, "SELECT COUNT(*) AS n FROM usuarios WHERE rol = 'BASE' AND estado = 'ACTIVO'")['n'];

$sitioActual = obtener_sitio($conexion);
$mensajes = consultar($conexion, "SELECT id_mensaje, nombre, asunto, fecha, leido FROM mensajes_contacto ORDER BY fecha DESC LIMIT 4");
$paginas = consultar($conexion, "SELECT id_pagina, nombre_menu, fecha_actualizacion FROM paginas ORDER BY orden");

$tituloPagina = 'Panel del administrador';
$paginaActiva = 'panel';
require __DIR__ . '/../includes/panel_arriba.php';
?>

<h1 class="t-headline-s mb-4">Hola, <?= e($_SESSION['nombre']) ?></h1>

<div class="row g-4">
    <div class="col-lg-7">
        <h2 class="t-title-l mb-3">Sitio web</h2>

        <a href="sitio.php" class="tarjeta tarjeta-enlace mb-3">
            <div class="d-flex align-items-center gap-3">
                <img src="<?= e($sitioActual['logo_url']) ?>" alt="" width="56" height="56" style="object-fit: contain;">
                <div class="flex-grow-1">
                    <div class="t-title-m">Logo, ícono, mensaje de temporada y contacto</div>
                    <div class="t-body-m texto-secundario">
                        <?= $sitioActual['mensaje_temporada'] ? 'Mensaje activo: «' . e($sitioActual['mensaje_temporada']) . '»' : 'Sin mensaje de temporada. Úsalo en Navidad, Halloween o un aviso importante.' ?>
                    </div>
                </div>
                <?= icono('web', 'texto-secundario') ?>
            </div>
        </a>

        <div class="tarjeta p-0 mb-3">
            <div class="d-flex justify-content-between align-items-center p-3 pb-2">
                <h3 class="t-title-m mb-0">Páginas</h3>
                <a href="paginas.php" class="btn btn-texto btn-sm">Ver todas</a>
            </div>
            <ul class="lista">
                <?php foreach ($paginas as $p): ?>
                    <li>
                        <a class="lista-fila" style="min-height: 56px;" href="pagina.php?id=<?= $p['id_pagina'] ?>">
                            <?= icono('article') ?>
                            <div class="lista-texto"><div class="lista-titulo"><?= e($p['nombre_menu']) ?></div></div>
                            <span class="meta">editada <?= fecha_corta($p['fecha_actualizacion']) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="row g-3">
            <div class="col-sm-6"><a href="preguntas.php" class="tarjeta tarjeta-enlace h-100 d-flex gap-3"><?= icono('help', 'texto-secundario') ?><div><div class="t-title-m">Preguntas frecuentes</div><div class="t-body-m texto-secundario">Agregar, editar u ocultar</div></div></a></div>
            <div class="col-sm-6"><a href="redes.php" class="tarjeta tarjeta-enlace h-100 d-flex gap-3"><?= icono('link', 'texto-secundario') ?><div><div class="t-title-m">Redes sociales</div><div class="t-body-m texto-secundario">Enlaces del pie y de Contacto</div></div></a></div>
        </div>
    </div>

    <div class="col-lg-5">
        <h2 class="t-title-l mb-3">Pendientes</h2>
        <section class="tarjeta p-0 mb-3">
            <div class="d-flex justify-content-between align-items-center p-3 pb-2">
                <h3 class="t-title-m mb-0">Mensajes de contacto <?php if ($sinLeer): ?><span class="contador ms-1"><?= $sinLeer ?></span><?php endif; ?></h3>
                <a href="mensajes.php" class="btn btn-texto btn-sm">Ver todos</a>
            </div>
            <?php if (!$mensajes): ?>
                <p class="px-3 pb-3 texto-secundario mb-0">Aún no llegan mensajes.</p>
            <?php else: ?>
                <ul class="lista">
                    <?php foreach ($mensajes as $m): ?>
                        <li>
                            <a class="lista-fila" href="mensaje.php?id=<?= $m['id_mensaje'] ?>">
                                <?= icono('mail') ?>
                                <div class="lista-texto">
                                    <div class="lista-titulo <?= $m['leido'] ? '' : 'fw-bold' ?>"><?= e($m['asunto']) ?></div>
                                    <div class="lista-detalle"><?= e($m['nombre']) ?> · <?= fecha_corta($m['fecha']) ?></div>
                                </div>
                                <?php if (!$m['leido']): ?><span class="estado estado-activo">Nuevo</span><?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <a href="usuarios.php" class="tarjeta tarjeta-enlace d-flex gap-3">
            <?= icono('group', 'texto-secundario') ?>
            <div>
                <div class="t-title-m">Cuentas de la base</div>
                <div class="t-body-m texto-secundario"><?= $cuentasBase ?> <?= $cuentasBase == 1 ? 'cuenta activa' : 'cuentas activas' ?>. Crea, suspende o cambia la contraseña del personal de la central.</div>
            </div>
        </a>
    </div>
</div>

<?php require __DIR__ . '/../includes/panel_abajo.php'; ?>
