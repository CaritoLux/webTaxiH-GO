<?php
session_start();
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/includes/funciones.php';

$pagina = obtener_pagina($conexion, 'contacto');
$errores = [];

// Guardar el mensaje del formulario (le llega al administrador en Mensajes)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre'] ?? '');
    $correo   = trim($_POST['correo'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $asunto   = trim($_POST['asunto'] ?? '');
    $mensaje  = trim($_POST['mensaje'] ?? '');

    if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 100) {
        $errores['nombre'] = 'Escribe tu nombre.';
    }
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores['correo'] = 'Escribe un correo válido, por ejemplo nombre@gmail.com.';
    }
    if ($telefono !== '' && !preg_match('/^\d{10}$/', $telefono)) {
        $errores['telefono'] = 'El teléfono lleva 10 dígitos, sin espacios.';
    }
    if (mb_strlen($asunto) < 3 || mb_strlen($asunto) > 100) {
        $errores['asunto'] = 'Escribe de qué se trata (3 a 100 caracteres).';
    }
    if (mb_strlen($mensaje) < 10 || mb_strlen($mensaje) > 2000) {
        $errores['mensaje'] = 'Escribe tu mensaje (10 a 2000 caracteres).';
    }

    if (!$errores) {
        ejecutar($conexion, "INSERT INTO mensajes_contacto (nombre, correo, telefono, asunto, mensaje) VALUES (?, ?, ?, ?, ?)",
            "sssss", [$nombre, $correo, $telefono !== '' ? $telefono : null, $asunto, $mensaje]);
        avisar('Recibimos tu mensaje. Te responderemos al correo que escribiste.');
        header('Location: contacto.php#formulario');
        exit;
    }
}

$tituloPagina = 'Contacto';
$paginaActiva = 'contacto';
require __DIR__ . '/includes/publico_arriba.php';
require __DIR__ . '/includes/encabezado_pagina.php';
$redes = consultar($conexion, "SELECT nombre, url FROM redes_sociales WHERE activo = 1 ORDER BY orden, id_red");
?>

<section class="seccion pt-4 pt-lg-5">
    <div class="container contenedor">
        <div class="row g-5">
            <div class="col-lg-5">
                <ul class="tarjeta list-unstyled p-0 mb-4 lista">
                    <li>
                        <a class="lista-fila" href="tel:<?= e($empresa['telefono']) ?>">
                            <?= icono('call') ?>
                            <div class="lista-texto"><div class="lista-detalle">Teléfono</div><div class="lista-titulo"><?= e(telefono_bonito($empresa['telefono'])) ?></div></div>
                        </a>
                    </li>
                    <?php if (!empty($empresa['whatsapp'])): ?>
                        <li>
                            <a class="lista-fila" href="https://wa.me/52<?= e($empresa['whatsapp']) ?>" target="_blank" rel="noopener">
                                <?= icono('chat') ?>
                                <div class="lista-texto"><div class="lista-detalle">WhatsApp</div><div class="lista-titulo"><?= e(telefono_bonito($empresa['whatsapp'])) ?></div></div>
                            </a>
                        </li>
                    <?php endif; ?>
                    <li>
                        <a class="lista-fila" href="mailto:<?= e($empresa['correo']) ?>">
                            <?= icono('mail') ?>
                            <div class="lista-texto"><div class="lista-detalle">Correo</div><div class="lista-titulo"><?= e($empresa['correo']) ?></div></div>
                        </a>
                    </li>
                    <li>
                        <a class="lista-fila" href="ubicacion.php">
                            <?= icono('location_on') ?>
                            <div class="lista-texto"><div class="lista-detalle">Dirección</div><div class="lista-titulo text-wrap"><?= e($empresa['direccion']) ?></div></div>
                        </a>
                    </li>
                    <li>
                        <div class="lista-fila">
                            <?= icono('schedule') ?>
                            <div class="lista-texto"><div class="lista-detalle">Horario</div><div class="lista-titulo"><?= e($empresa['horario']) ?></div></div>
                        </div>
                    </li>
                </ul>

                <?php if ($redes): ?>
                    <h2 class="t-title-m mb-2">Síguenos</h2>
                    <div class="chips">
                        <?php foreach ($redes as $red): ?>
                            <a class="chip" href="<?= e($red['url']) ?>" target="_blank" rel="noopener"><?= e($red['nombre']) ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-7" id="formulario">
                <?php mostrar_aviso(); ?>
                <form method="POST" action="contacto.php#formulario" class="tarjeta js-validar" novalidate>
                    <h2 class="t-title-l mb-4">Envíanos un mensaje</h2>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input type="text" id="nombre" name="nombre" class="form-control <?= isset($errores['nombre']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($_POST['nombre'] ?? ($_SESSION['nombre'] ?? '')) ?>" required minlength="2" maxlength="100"
                                   autocomplete="name" data-error="Escribe tu nombre.">
                            <div class="invalid-feedback" id="error-nombre"><?= e($errores['nombre'] ?? '') ?></div>
                        </div>
                        <div class="col-md-6">
                            <label for="telefono" class="form-label">Teléfono <span class="texto-secundario fw-normal">(opcional)</span></label>
                            <input type="tel" id="telefono" name="telefono" class="form-control <?= isset($errores['telefono']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($_POST['telefono'] ?? '') ?>" pattern="\d{10}" maxlength="10" inputmode="numeric"
                                   autocomplete="tel-national" data-error="El teléfono lleva 10 dígitos, sin espacios.">
                            <div class="invalid-feedback" id="error-telefono"><?= e($errores['telefono'] ?? '') ?></div>
                        </div>
                        <div class="col-12">
                            <label for="correo" class="form-label">Correo electrónico</label>
                            <input type="email" id="correo" name="correo" class="form-control <?= isset($errores['correo']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($_POST['correo'] ?? '') ?>" required maxlength="100" autocomplete="email"
                                   data-error="Escribe un correo válido, por ejemplo nombre@gmail.com.">
                            <div class="form-text">Aquí te respondemos.</div>
                            <div class="invalid-feedback" id="error-correo"><?= e($errores['correo'] ?? '') ?></div>
                        </div>
                        <div class="col-12">
                            <label for="asunto" class="form-label">Asunto</label>
                            <input type="text" id="asunto" name="asunto" class="form-control <?= isset($errores['asunto']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($_POST['asunto'] ?? '') ?>" required minlength="3" maxlength="100"
                                   data-error="Escribe de qué se trata (3 a 100 caracteres).">
                            <div class="invalid-feedback" id="error-asunto"><?= e($errores['asunto'] ?? '') ?></div>
                        </div>
                        <div class="col-12">
                            <label for="mensaje" class="form-label">Mensaje</label>
                            <textarea id="mensaje" name="mensaje" rows="5" class="form-control <?= isset($errores['mensaje']) ? 'is-invalid' : '' ?>"
                                      required minlength="10" maxlength="2000" data-error="Escribe tu mensaje (10 a 2000 caracteres)."><?= e($_POST['mensaje'] ?? '') ?></textarea>
                            <div class="invalid-feedback" id="error-mensaje"><?= e($errores['mensaje'] ?? '') ?></div>
                        </div>
                    </div>

                    <p class="form-text mt-3">Usamos tus datos solo para responderte. Consulta el <a href="politicas.php">aviso de privacidad</a>.</p>
                    <button type="submit" class="btn btn-principal btn-lg mt-2">Enviar mensaje</button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/publico_abajo.php'; ?>
