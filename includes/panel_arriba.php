<?php
// Parte de arriba del panel de la BASE y del ADMINISTRADOR:
// menú lateral fijo (en celular se abre con el botón de menú).
// La página define antes: $tituloPagina y $paginaActiva (clave del menú).
//  - Base: la operación (solicitudes de conductores, incidentes y tarifa).
//  - Administrador: solo el contenido de la página web. Los catálogos y
//    los parámetros se manejan en la app de escritorio.
$sitio = obtener_sitio($conexion);
$esAdmin = ($_SESSION['rol'] === 'ADMINISTRADOR');
$inicioPanel = $esAdmin ? 'admin/dashboard.php' : 'base/operacion.php';

// Opciones del menú: [clave, texto, ícono, archivo, contador]
if ($esAdmin) {
    $numMensajes = consultar_uno($conexion, "SELECT COUNT(*) AS n FROM mensajes_contacto WHERE leido = 0")['n'];
    $numEmergencias = 0;

    $grupos = [
        '' => [
            ['panel', 'Panel', 'dashboard', 'admin/dashboard.php', 0],
        ],
        'Sitio web' => [
            ['sitio', 'Identidad y contacto', 'web', 'admin/sitio.php', 0],
            ['paginas', 'Páginas', 'article', 'admin/paginas.php', 0],
            ['preguntas', 'Preguntas frecuentes', 'help', 'admin/preguntas.php', 0],
            ['redes', 'Redes sociales', 'link', 'admin/redes.php', 0],
            ['mensajes', 'Mensajes', 'mail', 'admin/mensajes.php', $numMensajes],
        ],
    ];
} else {
    // Números que se muestran junto a Solicitudes e Incidentes
    $numSolicitudes = consultar_uno($conexion, "SELECT COUNT(*) AS n FROM conductores WHERE estado_revision = 'EN_REVISION'")['n'];
    $numIncidentes  = consultar_uno($conexion, "SELECT COUNT(*) AS n FROM incidentes WHERE estado = 'ABIERTO'")['n'];

    // Alertas SOS sin cerrar (llegan desde la app): se avisan en todas las páginas de la base
    $numEmergencias = (int) consultar_uno($conexion, "SELECT COUNT(*) AS n FROM incidentes i
                                                      JOIN tipos_incidente t ON t.id_tipo_incidente = i.id_tipo_incidente
                                                      WHERE t.es_emergencia = 1 AND i.estado <> 'CERRADO'")['n'];

    $grupos = [
        '' => [
            ['panel', 'Operación', 'dashboard', 'base/operacion.php', 0],
            ['solicitudes', 'Solicitudes', 'how_to_reg', 'base/solicitudes.php', $numSolicitudes],
            ['incidentes', 'Incidentes', 'report', 'base/incidentes.php', $numIncidentes],
            ['tarifa', 'Tarifa', 'payments', 'base/tarifa.php', 0],
        ],
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

    <div class="panel">
        <aside class="panel-lateral">
            <div class="offcanvas-lg offcanvas-start" tabindex="-1" id="menuPanel" aria-label="Menú del panel">
                <nav class="menu-panel" aria-label="Menú del panel">
                    <div class="logo-panel">
                        <a href="<?= RAIZ . $inicioPanel ?>">
                            <img src="<?= e($sitio['logo_url']) ?>" alt="<?= e($sitio['nombre']) ?>, ir al inicio del panel" class="logo-menu">
                        </a>
                        <button type="button" class="btn-icono cerrar-menu d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#menuPanel" aria-label="Cerrar el menú">
                            <?= icono('close') ?>
                        </button>
                    </div>

                    <?php foreach ($grupos as $titulo => $opciones): ?>
                        <?php if ($titulo !== ''): ?>
                            <div class="grupo-menu"><?= e($titulo) ?></div>
                        <?php endif; ?>
                        <?php foreach ($opciones as $op): ?>
                            <a href="<?= RAIZ . $op[3] ?>" class="enlace-menu <?= $op[0] === $paginaActiva ? 'active' : '' ?>"
                               <?= $op[0] === $paginaActiva ? 'aria-current="page"' : '' ?>>
                                <?= icono($op[2]) ?>
                                <span><?= e($op[1]) ?></span>
                                <?php if ($op[4] > 0): ?>
                                    <span class="contador" aria-label="<?= $op[4] ?> pendientes"><?= $op[4] ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endforeach; ?>

                    <div class="final-menu">
                        <a href="<?= RAIZ ?>logout.php" class="enlace-menu"><?= icono('logout') ?> <span>Cerrar sesión</span></a>
                    </div>
                </nav>
            </div>
        </aside>

        <div class="panel-cuerpo">
            <header class="panel-barra">
                <button class="btn-icono d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuPanel" aria-controls="menuPanel" aria-label="Abrir el menú">
                    <?= icono('menu') ?>
                </button>
                <div class="dropdown usuario-barra">
                    <button class="btn btn-texto dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?= icono('person') ?> <?= e($_SESSION['nombre']) ?> · <?= $esAdmin ? 'Administrador' : 'Base' ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end menu-usuario">
                        <?php if (!$esAdmin): ?>
                            <li><a class="dropdown-item" href="<?= RAIZ ?>perfil.php"><?= icono('person') ?> Mi perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="<?= RAIZ ?>logout.php"><?= icono('logout') ?> Cerrar sesión</a></li>
                    </ul>
                </div>
            </header>

            <main class="panel-contenido" id="contenido">
                <?php if ($numEmergencias > 0 && empty($ocultarAlerta)): ?>
                    <div class="aviso aviso-error mb-4" role="alert">
                        <?= icono('sos') ?>
                        <div><strong><?= $numEmergencias === 1 ? 'Hay 1 alerta de emergencia' : "Hay $numEmergencias alertas de emergencia" ?> sin cerrar.</strong>
                            <a href="<?= RAIZ ?>base/incidentes.php?filtro=emergencias">Atender ahora</a></div>
                    </div>
                <?php endif; ?>
                <?php mostrar_aviso(); ?>
