<?php
// ADMINISTRADOR · Categorías de las preguntas frecuentes
// En la misma página: lista, formulario (nuevo o ?editar=ID) y eliminar.
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
requerir_rol(['ADMINISTRADOR']);

$errores = [];
$accion = $_POST['accion'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $accion === 'guardar') {
    $id = (int) ($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $orden = (int) ($_POST['orden'] ?? 0);

    if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 40) {
        $errores['nombre'] = 'Escribe el nombre (3 a 40 caracteres).';
    } elseif (consultar_uno($conexion, "SELECT 1 FROM categorias_pregunta WHERE nombre = ? AND id_categoria <> ?", "si", [$nombre, $id])) {
        $errores['nombre'] = 'Ya existe una categoría con ese nombre.';
    }
    if (!$errores) {
        if ($id) {
            ejecutar($conexion, "UPDATE categorias_pregunta SET nombre = ?, orden = ? WHERE id_categoria = ?", "sii", [$nombre, $orden, $id]);
        } else {
            ejecutar($conexion, "INSERT INTO categorias_pregunta (nombre, orden) VALUES (?, ?)", "si", [$nombre, $orden]);
        }
        avisar('Guardamos la categoría.');
        header('Location: categorias.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $accion === 'eliminar' && is_numeric($_POST['id'] ?? '')) {
    $usadas = consultar_uno($conexion, "SELECT COUNT(*) AS n FROM preguntas_frecuentes WHERE id_categoria = ?", "i", [$_POST['id']])['n'];
    if ($usadas > 0) {
        avisar('No se puede eliminar: la categoría tiene ' . $usadas . ' preguntas. Muévelas a otra categoría primero.', 'error');
    } else {
        ejecutar($conexion, "DELETE FROM categorias_pregunta WHERE id_categoria = ?", "i", [$_POST['id']]);
        avisar('Eliminamos la categoría.');
    }
    header('Location: categorias.php');
    exit;
}

$categorias = consultar($conexion, "SELECT c.*, (SELECT COUNT(*) FROM preguntas_frecuentes p WHERE p.id_categoria = c.id_categoria) AS preguntas
                                    FROM categorias_pregunta c ORDER BY c.orden, c.nombre");
$editar = isset($_GET['editar']) ? consultar_uno($conexion, "SELECT * FROM categorias_pregunta WHERE id_categoria = ?", "i", [$_GET['editar']]) : null;

$tituloPagina = 'Categorías de preguntas';
$paginaActiva = 'preguntas';
require __DIR__ . '/../includes/panel_arriba.php';
?>

<nav class="migas"><a href="preguntas.php"><?= icono('arrow_back') ?> Preguntas frecuentes</a></nav>
<h1 class="t-headline-s mb-4">Categorías de preguntas</h1>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="tabla-contenedor">
            <table class="table tabla">
                <thead><tr><th>Orden</th><th>Categoría</th><th>Preguntas</th><th><span class="visually-hidden">Acciones</span></th></tr></thead>
                <tbody>
                    <?php foreach ($categorias as $c): ?>
                        <tr>
                            <td><?= $c['orden'] ?></td>
                            <td><?= e($c['nombre']) ?></td>
                            <td><?= $c['preguntas'] ?></td>
                            <td class="text-end text-nowrap">
                                <a href="categorias.php?editar=<?= $c['id_categoria'] ?>" class="btn btn-contorno btn-sm">Editar</a>
                                <?php if ($c['preguntas'] == 0): ?>
                                    <form method="POST" action="categorias.php" class="d-inline" onsubmit="return confirm('¿Eliminar esta categoría?');">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id" value="<?= $c['id_categoria'] ?>">
                                        <button type="submit" class="btn btn-texto-rojo btn-sm">Eliminar</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-lg-5">
        <form method="POST" action="categorias.php<?= $editar ? '?editar=' . $editar['id_categoria'] : '' ?>" class="tarjeta js-validar" novalidate>
            <input type="hidden" name="accion" value="guardar">
            <input type="hidden" name="id" value="<?= $editar['id_categoria'] ?? 0 ?>">
            <h2 class="t-title-l mb-3"><?= $editar ? 'Editar categoría' : 'Nueva categoría' ?></h2>
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre</label>
                <input type="text" id="nombre" name="nombre" class="form-control <?= isset($errores['nombre']) ? 'is-invalid' : '' ?>" required minlength="3" maxlength="40"
                       value="<?= e($_POST['nombre'] ?? $editar['nombre'] ?? '') ?>" data-error="Escribe el nombre (3 a 40 caracteres).">
                <div class="invalid-feedback" id="error-nombre"><?= e($errores['nombre'] ?? '') ?></div>
            </div>
            <div class="mb-4">
                <label for="orden" class="form-label">Orden</label>
                <input type="number" id="orden" name="orden" min="0" max="99" class="form-control" value="<?= e($_POST['orden'] ?? $editar['orden'] ?? count($categorias) + 1) ?>">
            </div>
            <button type="submit" class="btn btn-principal"><?= $editar ? 'Guardar cambios' : 'Agregar categoría' ?></button>
            <?php if ($editar): ?><a href="categorias.php" class="btn btn-texto">Cancelar</a><?php endif; ?>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/panel_abajo.php'; ?>
