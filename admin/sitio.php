<?php
// =====================================================================
//  ADMINISTRADOR · Identidad y contacto del sitio
//  Logo, ícono de la pestaña y mensaje de temporada (Navidad, Halloween...),
//  enlace de la app, datos de contacto con su punto en el mapa y pie de página.
//  Cada bloque se guarda por separado.
// =====================================================================
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
requerir_rol(['ADMINISTRADOR']);

$idAdmin = $_SESSION['usuario_id'];
$sitioBd = consultar_uno($conexion, "SELECT s.*, u.nombre AS modifico FROM sitio s LEFT JOIN usuarios u ON u.id_usuario = s.id_modifico WHERE id_sitio = 1");
$empresaBd = consultar_uno($conexion, "SELECT e.*, u.nombre AS modifico FROM empresa e LEFT JOIN usuarios u ON u.id_usuario = e.id_modifico WHERE id_empresa = 1");
$errores = [];
$bloque = $_POST['bloque'] ?? '';

// ---------------------------------------------------- 1. Identidad
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $bloque === 'identidad') {
    $nombre = trim($_POST['nombre'] ?? '');
    $mensaje = trim($_POST['mensaje_temporada'] ?? '');
    $enlace = trim($_POST['enlace_app'] ?? '');
    $logo = $sitioBd['logo'];
    $favicon = $sitioBd['favicon'];

    if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 40) {
        $errores['nombre'] = 'Escribe el nombre (2 a 40 caracteres).';
    }
    if (mb_strlen($mensaje) > 160) {
        $errores['mensaje_temporada'] = 'El mensaje es de máximo 160 caracteres.';
    }
    if ($enlace !== '' && !filter_var($enlace, FILTER_VALIDATE_URL)) {
        $errores['enlace_app'] = 'Escribe un enlace completo, por ejemplo https://play.google.com/...';
    }

    // Logo: subir uno nuevo o volver al original
    if (!$errores && isset($_POST['logo_original'])) {
        borrar_archivo('sitio', $logo);
        $logo = null;
    } elseif (!$errores && ($_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $subida = subir_archivo($_FILES['logo'], 'sitio', 'logo', false, 2);
        if ($subida['error']) {
            $errores['logo'] = $subida['error'];
        } else {
            borrar_archivo('sitio', $logo);
            $logo = $subida['nombre'];
        }
    }
    // Ícono de la pestaña
    if (!$errores && isset($_POST['favicon_original'])) {
        borrar_archivo('sitio', $favicon);
        $favicon = null;
    } elseif (!$errores && ($_FILES['favicon']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $subida = subir_archivo($_FILES['favicon'], 'sitio', 'icono', false, 1);
        if ($subida['error']) {
            $errores['favicon'] = $subida['error'];
        } else {
            borrar_archivo('sitio', $favicon);
            $favicon = $subida['nombre'];
        }
    }

    if (!$errores) {
        ejecutar($conexion, "UPDATE sitio SET nombre = ?, logo = ?, favicon = ?, mensaje_temporada = ?, enlace_app = ?, id_modifico = ?, fecha_actualizacion = NOW()
                             WHERE id_sitio = 1",
            "sssssi", [$nombre, $logo, $favicon, $mensaje !== '' ? $mensaje : null, $enlace !== '' ? $enlace : null, $idAdmin]);
        avisar('Guardamos la identidad del sitio.');
        header('Location: sitio.php');
        exit;
    }
}

// ---------------------------------------------------- 2. Contacto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $bloque === 'contacto') {
    $telefono = preg_replace('/\D/', '', $_POST['telefono'] ?? '');
    $whatsapp = preg_replace('/\D/', '', $_POST['whatsapp'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $horario = trim($_POST['horario'] ?? '');
    $lat = $_POST['latitud'] ?? '';
    $lng = $_POST['longitud'] ?? '';

    if (strlen($telefono) !== 10) {
        $errores['telefono'] = 'El teléfono lleva 10 dígitos.';
    }
    if ($whatsapp !== '' && strlen($whatsapp) !== 10) {
        $errores['whatsapp'] = 'El WhatsApp lleva 10 dígitos o déjalo vacío.';
    }
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores['correo'] = 'Escribe un correo válido.';
    }
    if (mb_strlen($direccion) < 5 || mb_strlen($direccion) > 200) {
        $errores['direccion'] = 'Escribe la dirección (5 a 200 caracteres).';
    }
    if (mb_strlen($horario) < 3 || mb_strlen($horario) > 100) {
        $errores['horario'] = 'Escribe el horario de atención.';
    }
    if (!is_numeric($lat) || !is_numeric($lng) || $lat < 14 || $lat > 33 || $lng < -119 || $lng > -86) {
        $errores['latitud'] = 'Marca en el mapa dónde está la central.';
    }

    if (!$errores) {
        ejecutar($conexion, "UPDATE empresa SET telefono = ?, whatsapp = ?, correo = ?, direccion = ?, horario = ?, latitud = ?, longitud = ?,
                                    id_modifico = ?, fecha_actualizacion = NOW() WHERE id_empresa = 1",
            "sssssddi", [$telefono, $whatsapp !== '' ? $whatsapp : null, $correo, $direccion, $horario, $lat, $lng, $idAdmin]);
        avisar('Guardamos los datos de contacto.');
        header('Location: sitio.php#contacto');
        exit;
    }
}

// ---------------------------------------------------- 3. Pie de página
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $bloque === 'pie') {
    $textoPie = trim($_POST['texto_pie'] ?? '');
    if (mb_strlen($textoPie) < 5 || mb_strlen($textoPie) > 200) {
        $errores['texto_pie'] = 'Escribe el texto del pie (5 a 200 caracteres).';
    } else {
        ejecutar($conexion, "UPDATE sitio SET texto_pie = ?, id_modifico = ?, fecha_actualizacion = NOW() WHERE id_sitio = 1", "si", [$textoPie, $idAdmin]);
        avisar('Guardamos el pie de página.');
        header('Location: sitio.php#pie');
        exit;
    }
}

// Valor del campo: lo que escribió (si hubo error en ese bloque) o lo guardado
function valor_sitio($campo, $fila, $deBloque)
{
    return (($_POST['bloque'] ?? '') === $deBloque && isset($_POST[$campo])) ? $_POST[$campo] : ($fila[$campo] ?? '');
}
function error_campo($errores, $campo)
{
    return isset($errores[$campo]) ? 'is-invalid' : '';
}

$tituloPagina = 'Identidad y contacto';
$paginaActiva = 'sitio';
$usaMapa = true;
$scripts = ['assets/js/sitio.js'];
require __DIR__ . '/../includes/panel_arriba.php';
$actual = obtener_sitio($conexion);
?>

<h1 class="t-headline-s mb-1">Identidad y contacto</h1>
<p class="texto-secundario mb-4">Para Navidad, Halloween u otra temporada cambia el logo, el ícono de la pestaña y el mensaje de temporada. El logo también lo usa la app.</p>

<?php if ($errores): ?>
    <div class="aviso aviso-error mb-4" role="alert"><?= icono('error') ?><div>No se guardó: revisa los campos marcados en rojo.</div></div>
<?php endif; ?>

<!-- 1. Identidad -->
<form method="POST" action="sitio.php" enctype="multipart/form-data" class="tarjeta mb-4 js-validar" novalidate id="identidad">
    <input type="hidden" name="bloque" value="identidad">
    <h2 class="t-title-l mb-1">Identidad</h2>
    <p class="meta mb-4">Actualizado el <?= fecha_hora($sitioBd['fecha_actualizacion']) ?><?= $sitioBd['modifico'] ? ' por ' . e($sitioBd['modifico']) : '' ?></p>

    <div class="row g-4">
        <div class="col-md-6">
            <label for="nombre" class="form-label">Nombre del sitio</label>
            <input type="text" id="nombre" name="nombre" class="form-control <?= error_campo($errores, 'nombre') ?>" required maxlength="40"
                   value="<?= e(valor_sitio('nombre', $sitioBd, 'identidad')) ?>" data-error="Escribe el nombre (2 a 40 caracteres).">
            <div class="form-text">Aparece en la pestaña del navegador y junto al ícono del menú.</div>
            <div class="invalid-feedback" id="error-nombre"><?= e($errores['nombre'] ?? '') ?></div>
        </div>
        <div class="col-md-6">
            <label for="enlace_app" class="form-label">Enlace para descargar la app <span class="texto-secundario fw-normal">(opcional)</span></label>
            <input type="url" id="enlace_app" name="enlace_app" class="form-control <?= error_campo($errores, 'enlace_app') ?>" maxlength="255"
                   value="<?= e(valor_sitio('enlace_app', $sitioBd, 'identidad')) ?>" placeholder="https://play.google.com/store/apps/details?id=..."
                   data-error="Escribe un enlace completo que empiece con https://">
            <div class="form-text">Google Play o el archivo APK. Con enlace aparece el botón «Descarga la app» en el inicio, en el pie y en la cuenta del pasajero.</div>
            <div class="invalid-feedback" id="error-enlace_app"><?= e($errores['enlace_app'] ?? '') ?></div>
        </div>

        <div class="col-12">
            <label for="mensaje_temporada" class="form-label">Mensaje de temporada <span class="texto-secundario fw-normal">(opcional)</span></label>
            <input type="text" id="mensaje_temporada" name="mensaje_temporada" class="form-control <?= error_campo($errores, 'mensaje_temporada') ?>" maxlength="160"
                   value="<?= e(valor_sitio('mensaje_temporada', $sitioBd, 'identidad')) ?>" placeholder="Ej. ¡Feliz Navidad! El 24 y 31 de diciembre atendemos hasta las 20:00.">
            <div class="form-text">Sale en una franja azul arriba de todas las páginas públicas. Déjalo vacío para ocultarla.</div>
            <div class="invalid-feedback" id="error-mensaje_temporada"><?= e($errores['mensaje_temporada'] ?? '') ?></div>
        </div>

        <div class="col-md-6">
            <span class="form-label d-block">Logotipo</span>
            <div class="d-flex align-items-center gap-3 mb-2">
                <div class="tarjeta-gris rounded-3 p-2"><img src="<?= e($actual['logo_url']) ?>" alt="Logotipo actual" height="72"></div>
                <span class="t-body-m texto-secundario"><?= $sitioBd['logo'] ? 'Logo de temporada' : 'Logo original' ?></span>
            </div>
            <label for="logo" class="visually-hidden">Subir logotipo nuevo</label>
            <input type="file" id="logo" name="logo" class="form-control <?= error_campo($errores, 'logo') ?>" accept="image/png,image/jpeg,image/webp">
            <div class="form-text">PNG con fondo transparente, de hasta 2 MB.</div>
            <div class="invalid-feedback" id="error-logo"><?= e($errores['logo'] ?? '') ?></div>
            <?php if ($sitioBd['logo']): ?>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" id="logo_original" name="logo_original" value="1">
                    <label class="form-check-label" for="logo_original">Volver al logo original</label>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-md-6">
            <span class="form-label d-block">Ícono de la pestaña</span>
            <div class="d-flex align-items-center gap-3 mb-2">
                <div class="tarjeta-gris rounded-3 p-2"><img src="<?= e($actual['favicon_url']) ?>" alt="Ícono actual" width="72" height="72" style="object-fit: contain;"></div>
                <span class="t-body-m texto-secundario"><?= $sitioBd['favicon'] ? 'Ícono de temporada' : 'Ícono original' ?></span>
            </div>
            <label for="favicon" class="visually-hidden">Subir ícono nuevo</label>
            <input type="file" id="favicon" name="favicon" class="form-control <?= error_campo($errores, 'favicon') ?>" accept="image/png,image/jpeg,image/webp">
            <div class="form-text">Imagen cuadrada (PNG de 256 × 256), de hasta 1 MB. También aparece junto al nombre en el menú.</div>
            <div class="invalid-feedback" id="error-favicon"><?= e($errores['favicon'] ?? '') ?></div>
            <?php if ($sitioBd['favicon']): ?>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" id="favicon_original" name="favicon_original" value="1">
                    <label class="form-check-label" for="favicon_original">Volver al ícono original</label>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <button type="submit" class="btn btn-principal mt-4">Guardar identidad</button>
</form>

<!-- 2. Contacto -->
<form method="POST" action="sitio.php#contacto" class="tarjeta mb-4 js-validar" novalidate id="contacto">
    <input type="hidden" name="bloque" value="contacto">
    <h2 class="t-title-l mb-1">Contacto y ubicación</h2>
    <p class="meta mb-4">Se muestra en Contacto, Ubicación, Inicio y el pie. Actualizado el <?= fecha_hora($empresaBd['fecha_actualizacion']) ?><?= $empresaBd['modifico'] ? ' por ' . e($empresaBd['modifico']) : '' ?></p>

    <div class="row g-3">
        <div class="col-md-4">
            <label for="telefono" class="form-label">Teléfono de la central</label>
            <input type="tel" id="telefono" name="telefono" class="form-control <?= error_campo($errores, 'telefono') ?>" required pattern="[\d ]{10,13}"
                   value="<?= e(valor_sitio('telefono', $empresaBd, 'contacto')) ?>" inputmode="numeric" data-error="El teléfono lleva 10 dígitos.">
            <div class="invalid-feedback" id="error-telefono"><?= e($errores['telefono'] ?? '') ?></div>
        </div>
        <div class="col-md-4">
            <label for="whatsapp" class="form-label">WhatsApp <span class="texto-secundario fw-normal">(opcional)</span></label>
            <input type="tel" id="whatsapp" name="whatsapp" class="form-control <?= error_campo($errores, 'whatsapp') ?>" pattern="[\d ]{10,13}"
                   value="<?= e(valor_sitio('whatsapp', $empresaBd, 'contacto')) ?>" inputmode="numeric" data-error="El WhatsApp lleva 10 dígitos.">
            <div class="invalid-feedback" id="error-whatsapp"><?= e($errores['whatsapp'] ?? '') ?></div>
        </div>
        <div class="col-md-4">
            <label for="correo" class="form-label">Correo de la central</label>
            <input type="email" id="correo" name="correo" class="form-control <?= error_campo($errores, 'correo') ?>" required maxlength="100"
                   value="<?= e(valor_sitio('correo', $empresaBd, 'contacto')) ?>" data-error="Escribe un correo válido.">
            <div class="invalid-feedback" id="error-correo"><?= e($errores['correo'] ?? '') ?></div>
        </div>
        <div class="col-md-8">
            <label for="direccion" class="form-label">Dirección</label>
            <input type="text" id="direccion" name="direccion" class="form-control <?= error_campo($errores, 'direccion') ?>" required maxlength="200"
                   value="<?= e(valor_sitio('direccion', $empresaBd, 'contacto')) ?>" data-error="Escribe la dirección.">
            <div class="invalid-feedback" id="error-direccion"><?= e($errores['direccion'] ?? '') ?></div>
        </div>
        <div class="col-md-4">
            <label for="horario" class="form-label">Horario de atención</label>
            <input type="text" id="horario" name="horario" class="form-control <?= error_campo($errores, 'horario') ?>" required maxlength="100"
                   value="<?= e(valor_sitio('horario', $empresaBd, 'contacto')) ?>" data-error="Escribe el horario de atención.">
            <div class="invalid-feedback" id="error-horario"><?= e($errores['horario'] ?? '') ?></div>
        </div>
        <div class="col-12">
            <span class="form-label d-block">Ubicación de la central</span>
            <div id="mapaCentral" class="mapa" style="height: 300px;" role="application" aria-label="Mapa. Toca el lugar donde está la central."></div>
            <div class="form-text">Toca el mapa o arrastra el marcador al lugar exacto.</div>
            <input type="hidden" name="latitud" id="latitud" value="<?= e(valor_sitio('latitud', $empresaBd, 'contacto')) ?>">
            <input type="hidden" name="longitud" id="longitud" value="<?= e(valor_sitio('longitud', $empresaBd, 'contacto')) ?>">
            <?php if (isset($errores['latitud'])): ?><div class="invalid-feedback d-block"><?= e($errores['latitud']) ?></div><?php endif; ?>
        </div>
    </div>
    <button type="submit" class="btn btn-contorno mt-4">Guardar contacto</button>
</form>

<!-- 3. Pie de página -->
<form method="POST" action="sitio.php#pie" class="tarjeta js-validar" novalidate id="pie">
    <input type="hidden" name="bloque" value="pie">
    <h2 class="t-title-l mb-4">Pie de página</h2>
    <label for="texto_pie" class="form-label">Texto del pie</label>
    <input type="text" id="texto_pie" name="texto_pie" class="form-control <?= error_campo($errores, 'texto_pie') ?>" required minlength="5" maxlength="200"
           value="<?= e(valor_sitio('texto_pie', $sitioBd, 'pie')) ?>" data-error="Escribe el texto del pie (5 a 200 caracteres).">
    <div class="invalid-feedback" id="error-texto_pie"><?= e($errores['texto_pie'] ?? '') ?></div>
    <button type="submit" class="btn btn-contorno mt-4">Guardar pie</button>
</form>

<?php require __DIR__ . '/../includes/panel_abajo.php'; ?>
