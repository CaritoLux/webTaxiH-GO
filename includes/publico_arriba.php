<?php
// Parte de arriba de las páginas públicas: <head>, franja de temporada y menú.
// Es el mismo encabezado de login.php, registro.php y perfil.php.
// La página define antes: $tituloPagina y opcionalmente $usaMapa = true.
$sitio    = obtener_sitio($conexion);
$empresa  = obtener_empresa($conexion);
$logueado = isset($_SESSION['usuario_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require __DIR__ . '/head.php'; ?>
</head>
<body class="d-flex flex-column min-vh-100">
    <a class="saltar" href="#contenido">Saltar al contenido</a>

    <?php if (!empty($sitio['mensaje_temporada'])): ?>
        <div class="franja-temporada"><?= e($sitio['mensaje_temporada']) ?></div>
    <?php endif; ?>

    <!-- Barra de navegación -->
    <header class="navbar navbar-expand-lg bg-white border-bottom sticky-top py-3 barra-publica">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="<?= RAIZ ?>index.php">
                <img src="<?= e($sitio['logo_url']) ?>" alt="" width="42">
                <span><?= e($sitio['nombre']) ?></span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal"
                    aria-controls="menuPrincipal" aria-expanded="false" aria-label="Abrir el menú">
                <span class="navbar-toggler-icon"></span>
            </button>

            <nav class="collapse navbar-collapse" id="menuPrincipal" aria-label="Menú principal">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
                    <li class="nav-item"><a class="nav-link text-dark fw-medium" href="<?= RAIZ ?>index.php#tarifa">Tarifa</a></li>
                    <li class="nav-item"><a class="nav-link text-dark fw-medium" href="<?= RAIZ ?>index.php#contacto">Contacto</a></li>

                    <?php if ($logueado): ?>
                        <!-- Menú cuando el usuario ya inició sesión -->
                        <li class="nav-item dropdown ms-lg-2">
                            <button class="btn btn-outline-dark rounded-pill px-3 fw-semibold dropdown-toggle d-inline-flex align-items-center gap-2"
                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <?= icono('person') ?> <?= e($_SESSION['nombre']) ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 mt-2 menu-usuario">
                                <?php if ($_SESSION['rol'] === 'PASAJERO'): ?>
                                    <li><a class="dropdown-item py-2" href="<?= RAIZ ?>pasajero/viajes.php"><?= icono('history') ?> Mis viajes</a></li>
                                    <li><a class="dropdown-item py-2" href="<?= RAIZ ?>pasajero/reportes.php"><?= icono('report') ?> Mis reportes</a></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item py-2" href="<?= RAIZ . panel_de_rol($_SESSION['rol']) ?>"><?= icono('dashboard') ?> Mi panel</a></li>
                                <?php endif; ?>
                                <?php if ($_SESSION['rol'] !== 'ADMINISTRADOR'): ?>
                                    <li><a class="dropdown-item py-2" href="<?= RAIZ ?>perfil.php"><?= icono('person') ?> Mi perfil</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item py-2 text-danger" href="<?= RAIZ ?>logout.php"><?= icono('logout') ?> Cerrar sesión</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- Botón cuando no hay sesión -->
                        <li class="nav-item ms-lg-2">
                            <a href="<?= RAIZ ?>login.php" class="btn btn-outline-dark rounded-pill px-4 fw-semibold">Iniciar sesión</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main id="contenido" class="flex-grow-1">
