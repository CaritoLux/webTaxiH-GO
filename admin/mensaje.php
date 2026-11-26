<?php
// ADMINISTRADOR · Ver un mensaje de contacto (al abrirlo queda como leído)
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
requerir_rol(['ADMINISTRADOR']);

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'no_leido') {
        ejecutar($conexion, "UPDATE mensajes_contacto SET leido = 0 WHERE id_mensaje = ?", "i", [$id]);
        avisar('Marcaste el mensaje como no leído.');
    } elseif ($accion === 'eliminar') {
        ejecutar($conexion, "DELETE FROM mensajes_contacto WHERE id_mensaje = ?", "i", [$id]);
        avisar('Eliminamos el mensaje.');
    }
    header('Location: mensajes.php');
    exit;
}

$mensaje = consultar_uno($conexion, "SELECT * FROM mensajes_contacto WHERE id_mensaje = ?", "i", [$id]);
if (!$mensaje) {
    header('Location: mensajes.php');
    exit;
}
if (!$mensaje['leido']) {
    ejecutar($conexion, "UPDATE mensajes_contacto SET leido = 1 WHERE id_mensaje = ?", "i", [$id]);
}

$responder = 'mailto:' . $mensaje['correo'] . '?subject=' . rawurlencode('Re: ' . $mensaje['asunto']);

$tituloPagina = 'Mensaje';
$paginaActiva = 'mensajes';
require __DIR__ . '/../includes/panel_arriba.php';
?>

<nav class="migas"><a href="mensajes.php"><?= icono('arrow_back') ?> Mensajes</a></nav>
<h1 class="t-headline-s mb-1"><?= e($mensaje['asunto']) ?></h1>
<p class="texto-secundario mb-4">Recibido el <?= fecha_hora($mensaje['fecha']) ?></p>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="tarjeta">
            <p class="bloque-texto mb-0"><?= nl2br(e($mensaje['mensaje'])) ?></p>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="tarjeta mb-3">
            <div class="dato-etiqueta">De</div>
            <div class="mb-2"><?= e($mensaje['nombre']) ?></div>
            <div class="dato-etiqueta">Correo</div>
            <div class="mb-2"><a href="mailto:<?= e($mensaje['correo']) ?>"><?= e($mensaje['correo']) ?></a></div>
            <?php if ($mensaje['telefono']): ?>
                <div class="dato-etiqueta">Teléfono</div>
                <div><a href="tel:<?= e($mensaje['telefono']) ?>"><?= e(telefono_bonito($mensaje['telefono'])) ?></a></div>
            <?php endif; ?>
        </div>
        <a href="<?= e($responder) ?>" class="btn btn-principal w-100 mb-2"><?= icono('mail') ?> Responder por correo</a>
        <div class="d-flex justify-content-between">
            <form method="POST" action="mensaje.php">
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="accion" value="no_leido">
                <button type="submit" class="btn btn-texto">Marcar como no leído</button>
            </form>
            <form method="POST" action="mensaje.php" onsubmit="return confirm('¿Eliminar este mensaje? No se puede deshacer.');">
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="accion" value="eliminar">
                <button type="submit" class="btn btn-texto-rojo">Eliminar</button>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/panel_abajo.php'; ?>
