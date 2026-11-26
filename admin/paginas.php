<?php
// ADMINISTRADOR · Páginas del sitio público (cada una con sus secciones)
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
requerir_rol(['ADMINISTRADOR']);

$paginas = consultar($conexion, "SELECT p.*, u.nombre AS modifico,
                                        (SELECT COUNT(*) FROM secciones s WHERE s.id_pagina = p.id_pagina) AS secciones
                                 FROM paginas p LEFT JOIN usuarios u ON u.id_usuario = p.id_modifico
                                 ORDER BY p.orden");

$tituloPagina = 'Páginas';
$paginaActiva = 'paginas';
require __DIR__ . '/../includes/panel_arriba.php';
?>

<h1 class="t-headline-s mb-1">Páginas</h1>
<p class="texto-secundario mb-4">Cambia el título, el texto de entrada, la imagen y las secciones de cada página pública. En Inicio están la portada, «Cómo funciona» y la tarifa.</p>

<div class="tabla-contenedor">
    <div class="table-responsive">
        <table class="table tabla">
            <thead>
                <tr><th>Página</th><th>Título</th><th>Secciones</th><th>Última edición</th><th><span class="visually-hidden">Acciones</span></th></tr>
            </thead>
            <tbody>
                <?php foreach ($paginas as $p): ?>
                    <tr>
                        <td><span class="t-body-l"><?= e($p['nombre_menu']) ?></span><div class="meta"><?= e($p['archivo']) ?></div></td>
                        <td style="max-width: 320px;"><?= e(mb_strimwidth($p['titulo'], 0, 70, '…')) ?></td>
                        <td><?= $p['secciones'] ?></td>
                        <td><?= fecha_corta($p['fecha_actualizacion']) ?><div class="meta"><?= e($p['modifico'] ?? '') ?></div></td>
                        <td class="text-end text-nowrap">
                            <a href="../<?= e($p['archivo']) ?>" target="_blank" class="btn btn-texto btn-sm">Ver<span class="visually-hidden"> <?= e($p['nombre_menu']) ?></span></a>
                            <a href="pagina.php?id=<?= $p['id_pagina'] ?>" class="btn btn-contorno btn-sm">Editar<span class="visually-hidden"> <?= e($p['nombre_menu']) ?></span></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/panel_abajo.php'; ?>
