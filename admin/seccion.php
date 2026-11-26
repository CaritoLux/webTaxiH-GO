<?php
// ADMINISTRADOR · Agregar o editar una sección de una página
//   seccion.php?pagina=ID  -> nueva sección en esa página
//   seccion.php?id=ID      -> editar la sección
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
requerir_rol(['ADMINISTRADOR']);

$idSeccion = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$seccion = $idSeccion ? consultar_uno($conexion, "SELECT * FROM secciones WHERE id_seccion = ?", "i", [$idSeccion]) : null;
$idPagina = $seccion ? $seccion['id_pagina'] : (int) ($_GET['pagina'] ?? $_POST['pagina'] ?? 0);
$pagina = consultar_uno($conexion, "SELECT * FROM paginas WHERE id_pagina = ?", "i", [$idPagina]);
if (!$pagina || ($idSeccion && !$seccion)) {
    header('Location: paginas.php');
    exit;
}

// Las secciones fijas de Inicio no llevan imagen (la página no la muestra); la tarifa tampoco lleva pasos
$clave = $seccion['clave'] ?? null;
$llevaImagen = ($clave === null);
$llevaPasos = ($clave !== 'tarifa');

$pasosActuales = $seccion ? consultar($conexion, "SELECT texto FROM pasos WHERE id_seccion = ? ORDER BY orden, id_paso", "i", [$idSeccion]) : [];
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $contenido = trim($_POST['contenido'] ?? '');
    $orden = (int) ($_POST['orden'] ?? 0);
    $activo = isset($_POST['activo']) ? 1 : 0;
    // Pasos: uno por renglón, sin renglones vacíos
    $pasos = $llevaPasos ? array_values(array_filter(array_map('trim', preg_split('/\R/', $_POST['pasos'] ?? '')))) : [];
    $imagen = $seccion['imagen'] ?? null;

    if (mb_strlen($titulo) < 2 || mb_strlen($titulo) > 120) {
        $errores['titulo'] = 'Escribe el título (2 a 120 caracteres).';
    }
    if (mb_strlen($contenido) > 5000) {
        $errores['contenido'] = 'El texto es de máximo 5000 caracteres.';
    }
    if ($contenido === '' && !$pasos) {
        $errores['contenido'] = 'Escribe el texto de la sección o al menos un paso.';
    }
    foreach ($pasos as $paso) {
        if (mb_strlen($paso) > 160) {
            $errores['pasos'] = 'Cada paso es de máximo 160 caracteres.';
        }
    }
    if (count($pasos) > 10) {
        $errores['pasos'] = 'Escribe máximo 10 pasos.';
    }

    if ($llevaImagen && !$errores) {
        if (isset($_POST['quitar_imagen'])) {
            borrar_archivo('sitio', $imagen);
            $imagen = null;
        } elseif (($_FILES['imagen']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $subida = subir_archivo($_FILES['imagen'], 'sitio', 'seccion', false, 2);
            if ($subida['error']) {
                $errores['imagen'] = $subida['error'];
            } else {
                borrar_archivo('sitio', $imagen);
                $imagen = $subida['nombre'];
            }
        }
    }

    if (!$errores) {
        $contenidoGuardar = $contenido !== '' ? $contenido : null;
        if ($seccion) {
            ejecutar($conexion, "UPDATE secciones SET titulo = ?, contenido = ?, imagen = ?, orden = ?, activo = ?, id_modifico = ?, fecha_actualizacion = NOW()
                                 WHERE id_seccion = ?", "sssiiii", [$titulo, $contenidoGuardar, $imagen, $orden, $activo, $_SESSION['usuario_id'], $idSeccion]);
        } else {
            ejecutar($conexion, "INSERT INTO secciones (id_pagina, titulo, contenido, imagen, orden, activo, id_modifico) VALUES (?, ?, ?, ?, ?, ?, ?)",
                "isssiii", [$idPagina, $titulo, $contenidoGuardar, $imagen, $orden, $activo, $_SESSION['usuario_id']]);
            $idSeccion = mysqli_insert_id($conexion);
        }

        // Los pasos se reemplazan completos con lo que quedó en el cuadro de texto
        ejecutar($conexion, "DELETE FROM pasos WHERE id_seccion = ?", "i", [$idSeccion]);
        foreach ($pasos as $i => $paso) {
            ejecutar($conexion, "INSERT INTO pasos (id_seccion, texto, orden) VALUES (?, ?, ?)", "isi", [$idSeccion, $paso, $i + 1]);
        }

        avisar('Guardamos la sección «' . $titulo . '».');
        header('Location: pagina.php?id=' . $idPagina);
        exit;
    }
}

$siguienteOrden = consultar_uno($conexion, "SELECT COALESCE(MAX(orden), 0) + 1 AS n FROM secciones WHERE id_pagina = ?", "i", [$idPagina])['n'];
$valor = function ($campo, $porDefecto = '') use ($seccion) {
    return $_POST[$campo] ?? ($seccion[$campo] ?? $porDefecto);
};
$textoPasos = $_POST['pasos'] ?? implode("\n", array_column($pasosActuales, 'texto'));
$activoMarcado = $_SERVER['REQUEST_METHOD'] === 'POST' ? isset($_POST['activo']) : ($seccion ? $seccion['activo'] : true);

$tituloPagina = $seccion ? 'Editar sección' : 'Nueva sección';
$paginaActiva = 'paginas';
require __DIR__ . '/../includes/panel_arriba.php';
?>

<nav class="migas"><a href="pagina.php?id=<?= $idPagina ?>"><?= icono('arrow_back') ?> <?= e($pagina['nombre_menu']) ?></a></nav>
<h1 class="t-headline-s mb-4"><?= $seccion ? 'Editar sección' : 'Nueva sección' ?></h1>

<form method="POST" action="seccion.php" enctype="multipart/form-data" class="tarjeta js-validar" novalidate style="max-width: 800px;">
    <input type="hidden" name="id" value="<?= $idSeccion ?>">
    <input type="hidden" name="pagina" value="<?= $idPagina ?>">

    <div class="row g-3">
        <div class="col-md-9">
            <label for="titulo" class="form-label">Título</label>
            <input type="text" id="titulo" name="titulo" class="form-control <?= isset($errores['titulo']) ? 'is-invalid' : '' ?>" required minlength="2" maxlength="120"
                   value="<?= e($valor('titulo')) ?>" data-error="Escribe el título (2 a 120 caracteres).">
            <div class="invalid-feedback" id="error-titulo"><?= e($errores['titulo'] ?? '') ?></div>
        </div>
        <div class="col-md-3">
            <label for="orden" class="form-label">Orden</label>
            <input type="number" id="orden" name="orden" min="0" max="99" class="form-control" value="<?= e($valor('orden', $siguienteOrden)) ?>">
        </div>
        <div class="col-12">
            <label for="texto_seccion" class="form-label">Texto</label>
            <textarea id="texto_seccion" name="contenido" rows="6" maxlength="5000" class="form-control <?= isset($errores['contenido']) ? 'is-invalid' : '' ?>"><?= e($valor('contenido')) ?></textarea>
            <div class="form-text">Cada renglón se muestra como un párrafo.</div>
            <div class="invalid-feedback" id="error-texto_seccion"><?= e($errores['contenido'] ?? '') ?></div>
        </div>

        <?php if ($llevaPasos): ?>
            <div class="col-12">
                <label for="pasos" class="form-label">Pasos <span class="texto-secundario fw-normal">(opcional, uno por renglón)</span></label>
                <textarea id="pasos" name="pasos" rows="4" class="form-control <?= isset($errores['pasos']) ? 'is-invalid' : '' ?>"><?= e($textoPasos) ?></textarea>
                <div class="form-text">Se muestran como lista numerada, por ejemplo los pasos de «Cómo funciona».</div>
                <div class="invalid-feedback" id="error-pasos"><?= e($errores['pasos'] ?? '') ?></div>
            </div>
        <?php endif; ?>

        <?php if ($llevaImagen): ?>
            <div class="col-md-8">
                <span class="form-label d-block">Imagen <span class="texto-secundario fw-normal">(opcional)</span></span>
                <?php if (!empty($seccion['imagen'])): ?>
                    <img src="../uploads/sitio/<?= e($seccion['imagen']) ?>" alt="Imagen actual" class="rounded-3 mb-2 d-block" style="max-height: 120px;">
                <?php endif; ?>
                <label for="imagen" class="visually-hidden">Subir imagen</label>
                <input type="file" id="imagen" name="imagen" class="form-control <?= isset($errores['imagen']) ? 'is-invalid' : '' ?>" accept="image/png,image/jpeg,image/webp">
                <div class="form-text">JPG, PNG o WEBP de hasta 2 MB. Se muestra a un lado del texto.</div>
                <div class="invalid-feedback" id="error-imagen"><?= e($errores['imagen'] ?? '') ?></div>
                <?php if (!empty($seccion['imagen'])): ?>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="quitar_imagen" name="quitar_imagen" value="1">
                        <label class="form-check-label" for="quitar_imagen">Quitar la imagen</label>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="col-12">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="activo" name="activo" value="1" <?= $activoMarcado ? 'checked' : '' ?>>
                <label class="form-check-label" for="activo">Mostrar en la página</label>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-principal">Guardar sección</button>
        <a href="pagina.php?id=<?= $idPagina ?>" class="btn btn-texto">Cancelar</a>
    </div>
</form>

<?php require __DIR__ . '/../includes/panel_abajo.php'; ?>
