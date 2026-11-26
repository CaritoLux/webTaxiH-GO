<?php
// CONDUCTOR · Subir o cambiar uno de sus documentos (documento.php?tipo=ID)
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
requerir_rol(['CONDUCTOR']);

$idConductor = $_SESSION['usuario_id'];
require __DIR__ . '/../includes/conductor_datos.php';

// Buscar el tipo de documento pedido entre los 7
$idTipo = (int) ($_GET['tipo'] ?? $_POST['tipo'] ?? 0);
$doc = null;
foreach ($documentos as $d) {
    if ((int) $d['id_tipo_documento'] === $idTipo) {
        $doc = $d;
    }
}
if (!$doc) {
    header('Location: panel.php');
    exit;
}

// Si ya está aprobado, solo puede renovar un documento vencido o que vence en 30 días
$esRenovacion = ($conductor['estado_revision'] === 'APROBADO' && $doc['vigencia'] && $doc['vigencia'] <= $enUnMes);
if (!$editable && !$esRenovacion) {
    avisar('En este momento no puedes cambiar tus documentos.', 'error');
    header('Location: panel.php');
    exit;
}

$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $folio = strtoupper(trim($_POST['folio'] ?? ''));
    $vigencia = $_POST['vigencia'] ?? '';

    if ($doc['pide_folio'] && !preg_match('/^[A-Z0-9-]{4,20}$/', $folio)) {
        $errores['folio'] = 'Escribe el folio como aparece en el documento (4 a 20 letras, números o guiones).';
    }
    if ($doc['pide_vigencia']) {
        $fecha = DateTime::createFromFormat('Y-m-d', $vigencia);
        if (!$fecha || $fecha->format('Y-m-d') !== $vigencia) {
            $errores['vigencia'] = 'Elige la fecha de vencimiento.';
        } elseif ($vigencia <= $hoy) {
            $errores['vigencia'] = 'El documento está vencido. Sube uno vigente.';
        }
    }

    if (!$errores) {
        $prefijo = 'c' . $idConductor . '_' . strtolower($doc['clave']);
        $subida = subir_archivo($_FILES['archivo'] ?? null, 'documentos', $prefijo, !$doc['solo_imagen'], 5);

        if ($subida['error']) {
            $errores['archivo'] = $subida['error'];
        } else {
            $folioGuardar = $doc['pide_folio'] ? $folio : null;
            $vigenciaGuardar = $doc['pide_vigencia'] ? $vigencia : null;

            if ($doc['id_documento']) {
                // Se reemplaza: se borra el archivo anterior y el documento vuelve a quedar pendiente
                $anterior = consultar_uno($conexion, "SELECT archivo FROM documentos WHERE id_documento = ?", "i", [$doc['id_documento']]);
                borrar_archivo('documentos', $anterior['archivo']);
                ejecutar($conexion, "UPDATE documentos SET folio = ?, vigencia = ?, archivo = ?, estado = 'PENDIENTE', observacion = NULL, fecha_carga = NOW()
                                     WHERE id_documento = ?", "sssi", [$folioGuardar, $vigenciaGuardar, $subida['nombre'], $doc['id_documento']]);
            } else {
                ejecutar($conexion, "INSERT INTO documentos (id_conductor, id_tipo_documento, folio, vigencia, archivo) VALUES (?, ?, ?, ?, ?)",
                    "iisss", [$idConductor, $idTipo, $folioGuardar, $vigenciaGuardar, $subida['nombre']]);
            }

            if ($esRenovacion) {
                // La renovación la revisa la central; mientras tanto la app no le asigna viajes
                ejecutar($conexion, "UPDATE conductores SET estado_revision = 'EN_REVISION', fecha_envio = NOW() WHERE id_usuario = ?", "i", [$idConductor]);
                avisar('Subimos tu ' . $doc['nombre'] . '. La central la revisará para que sigas trabajando.');
            } else {
                avisar('Subimos tu ' . $doc['nombre'] . '.');
            }
            header('Location: panel.php');
            exit;
        }
    }
}

$formatos = $doc['solo_imagen'] ? 'JPG, PNG o WEBP' : 'JPG, PNG, WEBP o PDF';
$tituloPagina = $doc['nombre'];
$paginaActiva = 'solicitud';
require __DIR__ . '/../includes/app_arriba.php';
?>

<nav class="migas"><a href="panel.php"><?= icono('arrow_back') ?> Mi solicitud</a></nav>
<h1 class="t-headline-s mb-1"><?= e($doc['nombre']) ?></h1>
<p class="texto-secundario mb-4"><?= e($doc['descripcion']) ?></p>

<?php if ($esRenovacion): ?>
    <div class="aviso aviso-info mb-4" style="max-width: 640px;"><?= icono('schedule') ?>
        <div>Al subir el documento renovado tu cuenta pasa a revisión. La app no te asignará viajes hasta que la central lo acepte.</div>
    </div>
<?php endif; ?>

<form method="POST" action="documento.php" enctype="multipart/form-data" class="tarjeta js-validar" novalidate style="max-width: 640px;">
    <input type="hidden" name="tipo" value="<?= $idTipo ?>">

    <?php if ($doc['id_documento']): ?>
        <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
            <?= icono('badge', 'texto-secundario') ?>
            <div class="flex-grow-1">
                <div class="t-body-m">Documento actual · subido el <?= fecha_corta($doc['fecha_carga']) ?></div>
                <div><?= etiqueta($doc['estado']) ?></div>
            </div>
            <a href="../archivo.php?doc=<?= $doc['id_documento'] ?>" target="_blank" class="btn btn-texto btn-sm">Ver</a>
        </div>
    <?php endif; ?>

    <div class="mb-3">
        <label for="archivo" class="form-label"><?= $doc['solo_imagen'] ? 'Foto' : 'Foto o PDF' ?></label>
        <input type="file" id="archivo" name="archivo" class="form-control <?= isset($errores['archivo']) ? 'is-invalid' : '' ?>" required
               accept="<?= $doc['solo_imagen'] ? 'image/jpeg,image/png,image/webp' : 'image/jpeg,image/png,image/webp,application/pdf' ?>"
               <?= $doc['solo_imagen'] ? 'capture="environment"' : '' ?> data-error="Elige el archivo del documento.">
        <div class="form-text"><?= $formatos ?> de hasta 5 MB. Que se lea completo y sin reflejos.</div>
        <div class="invalid-feedback" id="error-archivo"><?= e($errores['archivo'] ?? '') ?></div>
    </div>

    <?php if ($doc['pide_folio']): ?>
        <div class="mb-3">
            <label for="folio" class="form-label">Folio</label>
            <input type="text" id="folio" name="folio" class="form-control text-uppercase <?= isset($errores['folio']) ? 'is-invalid' : '' ?>"
                   value="<?= e($_POST['folio'] ?? $doc['folio'] ?? '') ?>" required maxlength="20" pattern="[A-Za-z0-9-]{4,20}"
                   data-error="Escribe el folio como aparece en el documento.">
            <div class="invalid-feedback" id="error-folio"><?= e($errores['folio'] ?? '') ?></div>
        </div>
    <?php endif; ?>

    <?php if ($doc['pide_vigencia']): ?>
        <div class="mb-3">
            <label for="vigencia" class="form-label">Vence el</label>
            <input type="date" id="vigencia" name="vigencia" class="form-control <?= isset($errores['vigencia']) ? 'is-invalid' : '' ?>"
                   value="<?= e($_POST['vigencia'] ?? '') ?>" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                   data-error="Elige una fecha de vencimiento futura.">
            <div class="invalid-feedback" id="error-vigencia"><?= e($errores['vigencia'] ?? '') ?></div>
        </div>
    <?php endif; ?>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-principal btn-lg"><?= $doc['id_documento'] ? 'Subir nuevo documento' : 'Subir documento' ?></button>
        <a href="panel.php" class="btn btn-texto btn-lg">Cancelar</a>
    </div>
</form>

<?php require __DIR__ . '/../includes/app_abajo.php'; ?>
