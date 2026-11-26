<?php
// ADMINISTRADOR · Editar una página: encabezado y lista de sus secciones
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
requerir_rol(['ADMINISTRADOR']);

$idPagina = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$pagina = consultar_uno($conexion, "SELECT p.*, u.nombre AS modifico FROM paginas p LEFT JOIN usuarios u ON u.id_usuario = p.id_modifico WHERE id_pagina = ?", "i", [$idPagina]);
if (!$pagina) {
    header('Location: paginas.php');
    exit;
}
$esInicio = ($pagina['clave'] === 'inicio');
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombreMenu = trim($_POST['nombre_menu'] ?? '');
    $titulo = trim($_POST['titulo'] ?? '');
    $subtitulo = trim($_POST['subtitulo'] ?? '');
    $orden = (int) ($_POST['orden'] ?? 0);
    $imagen = $pagina['imagen'];

    if (mb_strlen($nombreMenu) < 2 || mb_strlen($nombreMenu) > 30) {
        $errores['nombre_menu'] = 'Escribe el nombre corto de la página (2 a 30 caracteres).';
    }
    if (mb_strlen($titulo) < 3 || mb_strlen($titulo) > 120) {
        $errores['titulo'] = 'Escribe el título (3 a 120 caracteres).';
    }
    if (mb_strlen($subtitulo) > 300) {
        $errores['subtitulo'] = 'El texto de entrada es de máximo 300 caracteres.';
    }
    if ($orden < 0 || $orden > 99) {
        $errores['orden'] = 'El orden va de 0 a 99.';
    }

    if (!$errores && isset($_POST['quitar_imagen'])) {
        borrar_archivo('sitio', $imagen);
        $imagen = null;
    } elseif (!$errores && ($_FILES['imagen']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $subida = subir_archivo($_FILES['imagen'], 'sitio', 'pagina', false, 2);
        if ($subida['error']) {
            $errores['imagen'] = $subida['error'];
        } else {
            borrar_archivo('sitio', $imagen);
            $imagen = $subida['nombre'];
        }
    }

    if (!$errores) {
        ejecutar($conexion, "UPDATE paginas SET nombre_menu = ?, titulo = ?, subtitulo = ?, imagen = ?, orden = ?,
                                    id_modifico = ?, fecha_actualizacion = NOW() WHERE id_pagina = ?",
            "ssssiii", [$nombreMenu, $titulo, $subtitulo !== '' ? $subtitulo : null, $imagen, $orden, $_SESSION['usuario_id'], $idPagina]);
        avisar('Guardamos la página ' . $nombreMenu . '.');
        header('Location: pagina.php?id=' . $idPagina);
        exit;
    }
}

$secciones = consultar($conexion, "SELECT s.*, (SELECT COUNT(*) FROM pasos p WHERE p.id_seccion = s.id_seccion) AS pasos
                                   FROM secciones s WHERE s.id_pagina = ? ORDER BY s.orden, s.id_seccion", "i", [$idPagina]);
$valor = function ($campo) use ($pagina) {
    return $_POST[$campo] ?? $pagina[$campo];
};

$tituloPagina = 'Editar ' . $pagina['nombre_menu'];
$paginaActiva = 'paginas';
require __DIR__ . '/../includes/panel_arriba.php';
?>

<nav class="migas"><a href="paginas.php"><?= icono('arrow_back') ?> Páginas</a></nav>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <h1 class="t-headline-s mb-0"><?= e($pagina['nombre_menu']) ?></h1>
    <a href="../<?= e($pagina['archivo']) ?>" target="_blank" class="btn btn-texto"><?= icono('open_in_new') ?> Ver la página</a>
</div>

<form method="POST" action="pagina.php" enctype="multipart/form-data" class="tarjeta mb-5 js-validar" novalidate>
    <input type="hidden" name="id" value="<?= $idPagina ?>">
    <h2 class="t-title-l mb-1"><?= $esInicio ? 'Portada' : 'Encabezado' ?></h2>
    <p class="meta mb-4">Actualizado el <?= fecha_hora($pagina['fecha_actualizacion']) ?><?= $pagina['modifico'] ? ' por ' . e($pagina['modifico']) : '' ?></p>

    <div class="row g-3">
        <div class="col-md-8">
            <label for="titulo" class="form-label">Título principal</label>
            <input type="text" id="titulo" name="titulo" class="form-control <?= isset($errores['titulo']) ? 'is-invalid' : '' ?>" required minlength="3" maxlength="120"
                   value="<?= e($valor('titulo')) ?>" data-error="Escribe el título (3 a 120 caracteres).">
            <div class="invalid-feedback" id="error-titulo"><?= e($errores['titulo'] ?? '') ?></div>
        </div>
        <div class="col-md-4">
            <label for="nombre_menu" class="form-label">Nombre en el pie de página</label>
            <input type="text" id="nombre_menu" name="nombre_menu" class="form-control <?= isset($errores['nombre_menu']) ? 'is-invalid' : '' ?>" required minlength="2" maxlength="30"
                   value="<?= e($valor('nombre_menu')) ?>" data-error="Escribe el nombre corto de la página (2 a 30 caracteres).">
            <div class="invalid-feedback" id="error-nombre_menu"><?= e($errores['nombre_menu'] ?? '') ?></div>
        </div>
        <div class="col-12">
            <label for="subtitulo" class="form-label">Texto debajo del título</label>
            <textarea id="subtitulo" name="subtitulo" rows="2" maxlength="300" class="form-control <?= isset($errores['subtitulo']) ? 'is-invalid' : '' ?>"><?= e($valor('subtitulo')) ?></textarea>
            <div class="invalid-feedback" id="error-subtitulo"><?= e($errores['subtitulo'] ?? '') ?></div>
        </div>
        <div class="col-md-6">
            <span class="form-label d-block">Imagen <?= $esInicio ? 'de la portada' : '' ?> <span class="texto-secundario fw-normal">(opcional)</span></span>
            <?php if ($pagina['imagen']): ?>
                <img src="../uploads/sitio/<?= e($pagina['imagen']) ?>" alt="Imagen actual" class="rounded-3 mb-2 d-block" style="max-height: 120px;">
            <?php elseif ($esInicio): ?>
                <img src="<?= e($sitio['logo_url'] ?? '../assets/img/logo.png') ?>" alt="Logo (se muestra si no subes imagen)" class="rounded-3 mb-2 d-block" style="max-height: 120px;">
            <?php endif; ?>
            <label for="imagen" class="visually-hidden">Subir imagen nueva</label>
            <input type="file" id="imagen" name="imagen" class="form-control <?= isset($errores['imagen']) ? 'is-invalid' : '' ?>" accept="image/png,image/jpeg,image/webp">
            <div class="form-text">JPG, PNG o WEBP horizontal, de hasta 2 MB.<?= $esInicio ? ' Sin imagen se muestra el logo del sitio.' : '' ?></div>
            <div class="invalid-feedback" id="error-imagen"><?= e($errores['imagen'] ?? '') ?></div>
            <?php if ($pagina['imagen']): ?>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" id="quitar_imagen" name="quitar_imagen" value="1">
                    <label class="form-check-label" for="quitar_imagen">Quitar la imagen</label>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-md-3">
            <label for="orden" class="form-label">Orden en el pie de página</label>
            <input type="number" id="orden" name="orden" min="0" max="99" class="form-control <?= isset($errores['orden']) ? 'is-invalid' : '' ?>" value="<?= e($valor('orden')) ?>">
            <div class="invalid-feedback" id="error-orden"><?= e($errores['orden'] ?? '') ?></div>
        </div>
    </div>
    <button type="submit" class="btn btn-principal mt-4">Guardar página</button>
</form>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div>
        <h2 class="t-title-l mb-0">Secciones</h2>
        <p class="t-body-m texto-secundario mb-0">Se muestran en este orden debajo del encabezado.</p>
    </div>
    <a href="seccion.php?pagina=<?= $idPagina ?>" class="btn btn-contorno">Agregar sección</a>
</div>

<?php if (!$secciones): ?>
    <div class="vacio tarjeta"><?= icono('article') ?><p class="mb-0">Esta página no tiene secciones.</p></div>
<?php else: ?>
    <div class="tabla-contenedor">
        <div class="table-responsive">
            <table class="table tabla">
                <thead><tr><th>Orden</th><th>Sección</th><th>Contenido</th><th>Visible</th><th><span class="visually-hidden">Acciones</span></th></tr></thead>
                <tbody>
                    <?php foreach ($secciones as $s): ?>
                        <tr>
                            <td><?= $s['orden'] ?></td>
                            <td>
                                <span class="t-body-l"><?= e($s['titulo']) ?></span>
                                <?php if ($s['clave']): ?><div class="meta">Fija: la usa la página de <?= e($pagina['nombre_menu']) ?></div><?php endif; ?>
                            </td>
                            <td class="texto-secundario" style="max-width: 360px;">
                                <?= e(mb_strimwidth($s['contenido'] ?? '', 0, 90, '…')) ?>
                                <?= $s['pasos'] ? '<div class="meta">' . $s['pasos'] . ' pasos</div>' : '' ?>
                            </td>
                            <td><?= $s['activo'] ? '<span class="estado estado-exito">Sí</span>' : '<span class="estado estado-neutro">Oculta</span>' ?></td>
                            <td class="text-end text-nowrap">
                                <a href="seccion.php?id=<?= $s['id_seccion'] ?>" class="btn btn-contorno btn-sm">Editar<span class="visually-hidden"> <?= e($s['titulo']) ?></span></a>
                                <?php if (!$s['clave']): ?>
                                    <form method="POST" action="seccion_eliminar.php" class="d-inline" onsubmit="return confirm('¿Eliminar esta sección? No se puede deshacer.');">
                                        <input type="hidden" name="id" value="<?= $s['id_seccion'] ?>">
                                        <button type="submit" class="btn btn-texto-rojo btn-sm">Eliminar<span class="visually-hidden"> <?= e($s['titulo']) ?></span></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/panel_abajo.php'; ?>
