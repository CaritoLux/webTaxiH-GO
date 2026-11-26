<?php
// ADMINISTRADOR · Agregar o editar una pregunta frecuente (pregunta.php?id=ID para editar)
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
requerir_rol(['ADMINISTRADOR']);

$idPregunta = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$pregunta = $idPregunta ? consultar_uno($conexion, "SELECT * FROM preguntas_frecuentes WHERE id_pregunta = ?", "i", [$idPregunta]) : null;
if ($idPregunta && !$pregunta) {
    header('Location: preguntas.php');
    exit;
}
$categorias = consultar($conexion, "SELECT * FROM categorias_pregunta ORDER BY orden, nombre");
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idCategoria = $_POST['id_categoria'] ?? '';
    $textoPregunta = trim($_POST['pregunta'] ?? '');
    $respuesta = trim($_POST['respuesta'] ?? '');
    $orden = (int) ($_POST['orden'] ?? 0);
    $activo = isset($_POST['activo']) ? 1 : 0;

    if (!is_numeric($idCategoria) || !consultar_uno($conexion, "SELECT 1 FROM categorias_pregunta WHERE id_categoria = ?", "i", [$idCategoria])) {
        $errores['id_categoria'] = 'Elige la categoría.';
    }
    if (mb_strlen($textoPregunta) < 5 || mb_strlen($textoPregunta) > 200) {
        $errores['pregunta'] = 'Escribe la pregunta (5 a 200 caracteres).';
    }
    if (mb_strlen($respuesta) < 5 || mb_strlen($respuesta) > 3000) {
        $errores['respuesta'] = 'Escribe la respuesta (5 a 3000 caracteres).';
    }

    if (!$errores) {
        if ($pregunta) {
            ejecutar($conexion, "UPDATE preguntas_frecuentes SET id_categoria = ?, pregunta = ?, respuesta = ?, orden = ?, activo = ? WHERE id_pregunta = ?",
                "issiii", [$idCategoria, $textoPregunta, $respuesta, $orden, $activo, $idPregunta]);
        } else {
            ejecutar($conexion, "INSERT INTO preguntas_frecuentes (id_categoria, pregunta, respuesta, orden, activo) VALUES (?, ?, ?, ?, ?)",
                "issii", [$idCategoria, $textoPregunta, $respuesta, $orden, $activo]);
        }
        avisar('Guardamos la pregunta.');
        header('Location: preguntas.php');
        exit;
    }
}

$valor = function ($campo, $porDefecto = '') use ($pregunta) {
    return $_POST[$campo] ?? ($pregunta[$campo] ?? $porDefecto);
};
$activoMarcado = $_SERVER['REQUEST_METHOD'] === 'POST' ? isset($_POST['activo']) : ($pregunta ? $pregunta['activo'] : true);

$tituloPagina = $pregunta ? 'Editar pregunta' : 'Nueva pregunta';
$paginaActiva = 'preguntas';
require __DIR__ . '/../includes/panel_arriba.php';
?>

<nav class="migas"><a href="preguntas.php"><?= icono('arrow_back') ?> Preguntas frecuentes</a></nav>
<h1 class="t-headline-s mb-4"><?= $pregunta ? 'Editar pregunta' : 'Nueva pregunta' ?></h1>

<?php if (!$categorias): ?>
    <div class="aviso aviso-info mb-4"><?= icono('help') ?><div>Primero <a href="categorias.php">crea una categoría</a>.</div></div>
<?php endif; ?>

<form method="POST" action="pregunta.php" class="tarjeta js-validar" novalidate style="max-width: 800px;">
    <input type="hidden" name="id" value="<?= $idPregunta ?>">
    <div class="row g-3">
        <div class="col-md-8">
            <label for="id_categoria" class="form-label">Categoría</label>
            <select id="id_categoria" name="id_categoria" class="form-select <?= isset($errores['id_categoria']) ? 'is-invalid' : '' ?>" required data-error="Elige la categoría.">
                <option value="">Elige la categoría</option>
                <?php foreach ($categorias as $c): ?>
                    <option value="<?= $c['id_categoria'] ?>" <?= $valor('id_categoria') == $c['id_categoria'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="invalid-feedback" id="error-id_categoria"><?= e($errores['id_categoria'] ?? '') ?></div>
        </div>
        <div class="col-md-4">
            <label for="orden" class="form-label">Orden</label>
            <input type="number" id="orden" name="orden" min="0" max="99" class="form-control" value="<?= e($valor('orden', 0)) ?>">
        </div>
        <div class="col-12">
            <label for="pregunta" class="form-label">Pregunta</label>
            <input type="text" id="pregunta" name="pregunta" class="form-control <?= isset($errores['pregunta']) ? 'is-invalid' : '' ?>" required minlength="5" maxlength="200"
                   value="<?= e($valor('pregunta')) ?>" placeholder="Ej. ¿Puedo pagar con tarjeta?" data-error="Escribe la pregunta (5 a 200 caracteres).">
            <div class="invalid-feedback" id="error-pregunta"><?= e($errores['pregunta'] ?? '') ?></div>
        </div>
        <div class="col-12">
            <label for="respuesta" class="form-label">Respuesta</label>
            <textarea id="respuesta" name="respuesta" rows="5" class="form-control <?= isset($errores['respuesta']) ? 'is-invalid' : '' ?>" required minlength="5" maxlength="3000"
                      data-error="Escribe la respuesta."><?= e($valor('respuesta')) ?></textarea>
            <div class="invalid-feedback" id="error-respuesta"><?= e($errores['respuesta'] ?? '') ?></div>
        </div>
        <div class="col-12">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="activo" name="activo" value="1" <?= $activoMarcado ? 'checked' : '' ?>>
                <label class="form-check-label" for="activo">Mostrar en la página</label>
            </div>
        </div>
    </div>
    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-principal">Guardar pregunta</button>
        <a href="preguntas.php" class="btn btn-texto">Cancelar</a>
    </div>
</form>

<?php require __DIR__ . '/../includes/panel_abajo.php'; ?>
