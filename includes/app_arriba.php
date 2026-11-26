<?php
// Parte de arriba de las páginas del PASAJERO y del CONDUCTOR
// (barra superior en computadora y barra inferior en celular).
// La página define antes: $tituloPagina y $paginaActiva.
// En la web el pasajero solo ve su cuenta, sus viajes y sus reportes:
// el taxi se pide desde la app.
$sitio = obtener_sitio($conexion);

if ($_SESSION['rol'] === 'PASAJERO') {
    $opcionesApp = [
        ['inicio', 'Inicio', 'home', 'pasajero/panel.php'],
        ['viajes', 'Mis viajes', 'history', 'pasajero/viajes.php'],
        ['reportes', 'Mis reportes', 'report', 'pasajero/reportes.php'],
        ['cuenta', 'Cuenta', 'person', 'perfil.php'],
    ];
} else {
    $opcionesApp = [
        ['solicitud', 'Mi solicitud', 'badge', 'conductor/panel.php'],
        ['cuenta', 'Cuenta', 'person', 'perfil.php'],
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require __DIR__ . '/head.php'; ?>
</head>
<body>
    <a class="saltar" href="#contenido">Saltar al contenido</a>

    <header class="barra-app">
        <div class="container-fluid px-3 px-lg-4 d-flex align-items-center gap-3" style="min-height: 64px;">
            <a class="marca" href="<?= RAIZ . panel_de_rol($_SESSION['rol']) ?>">
                <img src="<?= e($sitio['logo_url']) ?>" alt="">
                <span><?= e($sitio['nombre']) ?></span>
            </a>

            <nav class="d-none d-md-flex gap-1 ms-3" aria-label="Menú principal">
                <?php foreach ($opcionesApp as $op): ?>
                    <?php if ($op[0] !== 'cuenta'): ?>
                        <a href="<?= RAIZ . $op[3] ?>" class="enlace-app <?= $op[0] === $paginaActiva ? 'active' : '' ?>"
                           <?= $op[0] === $paginaActiva ? 'aria-current="page"' : '' ?>><?= icono($op[2]) ?> <?= e($op[1]) ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>

            <div class="dropdown ms-auto">
                <button class="btn btn-texto dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menú de la cuenta de <?= e($_SESSION['nombre']) ?>">
                    <?= icono('person') ?> <span class="d-none d-sm-inline"><?= e($_SESSION['nombre']) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end menu-usuario">
                    <li><a class="dropdown-item" href="<?= RAIZ ?>perfil.php"><?= icono('person') ?> Mi perfil</a></li>
                    <?php if ($_SESSION['rol'] === 'PASAJERO'): ?>
                        <li><a class="dropdown-item" href="<?= RAIZ ?>index.php"><?= icono('web') ?> Página de inicio</a></li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?= RAIZ ?>logout.php"><?= icono('logout') ?> Cerrar sesión</a></li>
                </ul>
            </div>
        </div>
    </header>

    <main id="contenido">
        <div class="container app-contenido" style="max-width: 960px;">
            <?php mostrar_aviso(); ?>
