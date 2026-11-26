<?php
session_start();
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/includes/funciones.php';

$pagina = obtener_pagina($conexion, 'preguntas');
$categorias = consultar($conexion, "SELECT * FROM categorias_pregunta ORDER BY orden, nombre");

// Filtros por URL (así el botón Atrás del navegador funciona): ?q=texto&cat=2
$busqueda = trim($_GET['q'] ?? '');
$idCategoria = (isset($_GET['cat']) && is_numeric($_GET['cat'])) ? (int) $_GET['cat'] : 0;

$sql = "SELECT p.id_pregunta, p.pregunta, p.respuesta, c.nombre AS categoria
        FROM preguntas_frecuentes p
        JOIN categorias_pregunta c ON c.id_categoria = p.id_categoria
        WHERE p.activo = 1";
$tipos = '';
$valores = [];
if ($idCategoria > 0) {
    $sql .= " AND p.id_categoria = ?";
    $tipos .= 'i';
    $valores[] = $idCategoria;
}
if ($busqueda !== '') {
    $sql .= " AND (p.pregunta LIKE ? OR p.respuesta LIKE ?)";
    $tipos .= 'ss';
    $valores[] = '%' . $busqueda . '%';
    $valores[] = '%' . $busqueda . '%';
}
$sql .= " ORDER BY c.orden, p.orden, p.id_pregunta";
$preguntas = consultar($conexion, $sql, $tipos, $valores);

// Se agrupan por categoría para mostrarlas en bloques
$grupos = [];
foreach ($preguntas as $p) {
    $grupos[$p['categoria']][] = $p;
}

$tituloPagina = 'Preguntas frecuentes';
$paginaActiva = 'preguntas';
require __DIR__ . '/includes/publico_arriba.php';
require __DIR__ . '/includes/encabezado_pagina.php';
?>

<section class="seccion pt-4 pt-lg-5">
    <div class="container contenedor" style="max-width: 960px;">
        <form method="GET" action="preguntas.php" class="mb-3" role="search">
            <label for="q" class="form-label">Buscar una pregunta</label>
            <div class="d-flex gap-2">
                <div class="campo-icono flex-grow-1">
                    <?= icono('search') ?>
                    <input type="search" id="q" name="q" class="form-control" value="<?= e($busqueda) ?>" placeholder="Por ejemplo: cancelar, documentos, tarifa">
                </div>
                <?php if ($idCategoria): ?>
                    <input type="hidden" name="cat" value="<?= $idCategoria ?>">
                <?php endif; ?>
                <button type="submit" class="btn btn-contorno btn-lg">Buscar</button>
            </div>
        </form>

        <nav class="chips mb-4" aria-label="Categorías">
            <a href="preguntas.php<?= $busqueda !== '' ? '?q=' . urlencode($busqueda) : '' ?>"
               class="chip <?= $idCategoria === 0 ? 'chip-activo' : '' ?>" <?= $idCategoria === 0 ? 'aria-current="true"' : '' ?>>
                <?= $idCategoria === 0 ? icono('check') : '' ?> Todas
            </a>
            <?php foreach ($categorias as $c): ?>
                <?php $activa = ($idCategoria === (int) $c['id_categoria']); ?>
                <a href="preguntas.php?cat=<?= $c['id_categoria'] ?><?= $busqueda !== '' ? '&q=' . urlencode($busqueda) : '' ?>"
                   class="chip <?= $activa ? 'chip-activo' : '' ?>" <?= $activa ? 'aria-current="true"' : '' ?>>
                    <?= $activa ? icono('check') : '' ?> <?= e($c['nombre']) ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <?php if (!$grupos): ?>
            <div class="vacio tarjeta">
                <?= icono('search') ?>
                <p class="t-title-m mb-1">No encontramos preguntas con «<?= e($busqueda) ?>»</p>
                <p class="mb-3">Prueba con otra palabra o escríbenos tu duda.</p>
                <a href="preguntas.php" class="btn btn-texto">Ver todas</a>
            </div>
        <?php endif; ?>

        <?php $n = 0; ?>
        <?php foreach ($grupos as $categoria => $lista): ?>
            <h2 class="t-title-l mt-4 mb-3"><?= e($categoria) ?></h2>
            <div class="accordion mb-2" id="grupo<?= $n ?>">
                <?php foreach ($lista as $p): ?>
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#p<?= $p['id_pregunta'] ?>" aria-expanded="false" aria-controls="p<?= $p['id_pregunta'] ?>">
                                <?= e($p['pregunta']) ?>
                            </button>
                        </h3>
                        <div id="p<?= $p['id_pregunta'] ?>" class="accordion-collapse collapse" data-bs-parent="#grupo<?= $n ?>">
                            <div class="accordion-body"><?= nl2br(e($p['respuesta'])) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php $n++; ?>
        <?php endforeach; ?>

        <div class="aviso aviso-info mt-5">
            <?= icono('help') ?>
            <div>¿No encontraste tu respuesta? <a href="contacto.php">Escríbenos</a> y te ayudamos.</div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/publico_abajo.php'; ?>
