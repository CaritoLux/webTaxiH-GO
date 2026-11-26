<?php
// ADMINISTRADOR · Redes sociales (salen en el pie y en Contacto)
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
    $url = trim($_POST['url'] ?? '');
    $orden = (int) ($_POST['orden'] ?? 0);
    $activo = isset($_POST['activo']) ? 1 : 0;

    if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 30) {
        $errores['nombre'] = 'Escribe el nombre de la red (2 a 30 caracteres).';
    }
    if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#', $url)) {
        $errores['url'] = 'Escribe el enlace completo, por ejemplo https://www.facebook.com/taxihgo';
    }
    if (!$errores) {
        if ($id) {
            ejecutar($conexion, "UPDATE redes_sociales SET nombre = ?, url = ?, orden = ?, activo = ? WHERE id_red = ?", "ssiii", [$nombre, $url, $orden, $activo, $id]);
        } else {
            ejecutar($conexion, "INSERT INTO redes_sociales (nombre, url, orden, activo) VALUES (?, ?, ?, ?)", "ssii", [$nombre, $url, $orden, $activo]);
        }
        avisar('Guardamos la red social.');
        header('Location: redes.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $accion === 'eliminar' && is_numeric($_POST['id'] ?? '')) {
    ejecutar($conexion, "DELETE FROM redes_sociales WHERE id_red = ?", "i", [$_POST['id']]);
    avisar('Eliminamos la red social.');
    header('Location: redes.php');
    exit;
}

$redes = consultar($conexion, "SELECT * FROM redes_sociales ORDER BY orden, id_red");
$editar = isset($_GET['editar']) ? consultar_uno($conexion, "SELECT * FROM redes_sociales WHERE id_red = ?", "i", [$_GET['editar']]) : null;
$activoMarcado = $_SERVER['REQUEST_METHOD'] === 'POST' ? isset($_POST['activo']) : ($editar ? $editar['activo'] : true);

$tituloPagina = 'Redes sociales';
$paginaActiva = 'redes';
require __DIR__ . '/../includes/panel_arriba.php';
?>

<h1 class="t-headline-s mb-1">Redes sociales</h1>
<p class="texto-secundario mb-4">Los enlaces visibles aparecen en el pie de página y en Contacto.</p>

<div class="row g-4">
    <div class="col-lg-7">
        <?php if (!$redes): ?>
            <div class="vacio tarjeta"><?= icono('link') ?><p class="mb-0">Aún no hay redes sociales. Agrega la primera.</p></div>
        <?php else: ?>
            <div class="tabla-contenedor">
                <table class="table tabla">
                    <thead><tr><th>Red</th><th>Enlace</th><th>Visible</th><th><span class="visually-hidden">Acciones</span></th></tr></thead>
                    <tbody>
                        <?php foreach ($redes as $r): ?>
                            <tr>
                                <td><?= e($r['nombre']) ?></td>
                                <td style="max-width: 240px;" class="text-truncate"><a href="<?= e($r['url']) ?>" target="_blank" rel="noopener"><?= e($r['url']) ?></a></td>
                                <td><?= $r['activo'] ? '<span class="estado estado-exito">Sí</span>' : '<span class="estado estado-neutro">Oculta</span>' ?></td>
                                <td class="text-end text-nowrap">
                                    <a href="redes.php?editar=<?= $r['id_red'] ?>" class="btn btn-contorno btn-sm">Editar</a>
                                    <form method="POST" action="redes.php" class="d-inline" onsubmit="return confirm('¿Eliminar esta red social?');">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id" value="<?= $r['id_red'] ?>">
                                        <button type="submit" class="btn btn-texto-rojo btn-sm">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <div class="col-lg-5">
        <form method="POST" action="redes.php<?= $editar ? '?editar=' . $editar['id_red'] : '' ?>" class="tarjeta js-validar" novalidate>
            <input type="hidden" name="accion" value="guardar">
            <input type="hidden" name="id" value="<?= $editar['id_red'] ?? 0 ?>">
            <h2 class="t-title-l mb-3"><?= $editar ? 'Editar red social' : 'Nueva red social' ?></h2>
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre</label>
                <input type="text" id="nombre" name="nombre" class="form-control <?= isset($errores['nombre']) ? 'is-invalid' : '' ?>" required maxlength="30"
                       value="<?= e($_POST['nombre'] ?? $editar['nombre'] ?? '') ?>" placeholder="Facebook" data-error="Escribe el nombre de la red.">
                <div class="invalid-feedback" id="error-nombre"><?= e($errores['nombre'] ?? '') ?></div>
            </div>
            <div class="mb-3">
                <label for="url" class="form-label">Enlace</label>
                <input type="url" id="url" name="url" class="form-control <?= isset($errores['url']) ? 'is-invalid' : '' ?>" required maxlength="255"
                       value="<?= e($_POST['url'] ?? $editar['url'] ?? '') ?>" placeholder="https://www.facebook.com/..." data-error="Escribe el enlace completo que empiece con https://">
                <div class="invalid-feedback" id="error-url"><?= e($errores['url'] ?? '') ?></div>
            </div>
            <div class="mb-3">
                <label for="orden" class="form-label">Orden</label>
                <input type="number" id="orden" name="orden" min="0" max="99" class="form-control" value="<?= e($_POST['orden'] ?? $editar['orden'] ?? count($redes) + 1) ?>">
            </div>
            <div class="form-check form-switch mb-4">
                <input class="form-check-input" type="checkbox" role="switch" id="activo" name="activo" value="1" <?= $activoMarcado ? 'checked' : '' ?>>
                <label class="form-check-label" for="activo">Mostrar en el sitio</label>
            </div>
            <button type="submit" class="btn btn-principal"><?= $editar ? 'Guardar cambios' : 'Agregar red social' ?></button>
            <?php if ($editar): ?><a href="redes.php" class="btn btn-texto">Cancelar</a><?php endif; ?>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/panel_abajo.php'; ?>
