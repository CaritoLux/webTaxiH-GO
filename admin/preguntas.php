<?php
// ADMINISTRADOR · Preguntas frecuentes (lista por categoría)
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
requerir_rol(['ADMINISTRADOR']);

$categorias = consultar($conexion, "SELECT * FROM categorias_pregunta ORDER BY orden, nombre");
$idCategoria = (isset($_GET['cat']) && is_numeric($_GET['cat'])) ? (int) $_GET['cat'] : 0;

$sql = "SELECT p.*, c.nombre AS categoria FROM preguntas_frecuentes p
        JOIN categorias_pregunta c ON c.id_categoria = p.id_categoria";
$tipos = '';
$valores = [];
if ($idCategoria) {
    $sql .= " WHERE p.id_categoria = ?";
    $tipos = 'i';
    $valores = [$idCategoria];
}
$sql .= " ORDER BY c.orden, p.orden, p.id_pregunta";
$preguntas = consultar($conexion, $sql, $tipos, $valores);

$tituloPagina = 'Preguntas frecuentes';
$paginaActiva = 'preguntas';
require __DIR__ . '/../includes/panel_arriba.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="t-headline-s mb-1">Preguntas frecuentes</h1>
        <p class="texto-secundario mb-0">Las que están visibles salen en la página Preguntas y las tres primeras en Inicio.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="categorias.php" class="btn btn-texto">Categorías</a>
        <a href="pregunta.php" class="btn btn-principal">Agregar pregunta</a>
    </div>
</div>

<nav class="chips mb-4" aria-label="Filtrar por categoría">
    <a href="preguntas.php" class="chip <?= $idCategoria === 0 ? 'chip-activo' : '' ?>"><?= $idCategoria === 0 ? icono('check') : '' ?> Todas</a>
    <?php foreach ($categorias as $c): ?>
        <a href="preguntas.php?cat=<?= $c['id_categoria'] ?>" class="chip <?= $idCategoria === (int) $c['id_categoria'] ? 'chip-activo' : '' ?>">
            <?= $idCategoria === (int) $c['id_categoria'] ? icono('check') : '' ?> <?= e($c['nombre']) ?>
        </a>
    <?php endforeach; ?>
</nav>

<?php if (!$preguntas): ?>
    <div class="vacio tarjeta"><?= icono('help') ?><p class="mb-0">No hay preguntas en esta categoría.</p></div>
<?php else: ?>
    <div class="tabla-contenedor">
        <div class="table-responsive">
            <table class="table tabla">
                <thead><tr><th>Pregunta</th><th>Categoría</th><th>Orden</th><th>Visible</th><th><span class="visually-hidden">Acciones</span></th></tr></thead>
                <tbody>
                    <?php foreach ($preguntas as $p): ?>
                        <tr>
                            <td style="max-width: 460px;">
                                <div class="t-body-l"><?= e($p['pregunta']) ?></div>
                                <div class="meta"><?= e(mb_strimwidth($p['respuesta'], 0, 110, '…')) ?></div>
                            </td>
                            <td><?= e($p['categoria']) ?></td>
                            <td><?= $p['orden'] ?></td>
                            <td><?= $p['activo'] ? '<span class="estado estado-exito">Sí</span>' : '<span class="estado estado-neutro">Oculta</span>' ?></td>
                            <td class="text-end text-nowrap">
                                <a href="pregunta.php?id=<?= $p['id_pregunta'] ?>" class="btn btn-contorno btn-sm">Editar</a>
                                <form method="POST" action="pregunta_eliminar.php" class="d-inline" onsubmit="return confirm('¿Eliminar esta pregunta? No se puede deshacer.');">
                                    <input type="hidden" name="id" value="<?= $p['id_pregunta'] ?>">
                                    <button type="submit" class="btn btn-texto-rojo btn-sm">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/panel_abajo.php'; ?>
