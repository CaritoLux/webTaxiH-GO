<?php
// PASAJERO · Reportar un problema de un viaje (incidente)
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
requerir_rol(['PASAJERO']);

$idPasajero = $_SESSION['usuario_id'];
$folio = $_GET['folio'] ?? $_POST['folio'] ?? '';
$viaje = consultar_uno($conexion, "SELECT id_viaje, folio, estado, id_conductor, origen_direccion, destino_direccion, fecha_solicitud
                                   FROM viajes WHERE folio = ? AND id_pasajero = ?", "si", [$folio, $idPasajero]);

if (!$viaje || !$viaje['id_conductor'] || in_array($viaje['estado'], ['BUSCANDO', 'ASIGNADO', 'EN_ORIGEN', 'EN_CURSO'])) {
    avisar('Solo puedes reportar viajes que ya terminaron y que tuvieron conductor.', 'error');
    header('Location: viajes.php');
    exit;
}

$tipos = consultar($conexion, "SELECT id_tipo_incidente, nombre FROM tipos_incidente WHERE activo = 1 AND es_emergencia = 0 ORDER BY id_tipo_incidente");
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idTipo = $_POST['id_tipo_incidente'] ?? '';
    $descripcion = trim($_POST['descripcion'] ?? '');
    $evidencia = null;

    $tipoValido = is_numeric($idTipo) && consultar_uno($conexion, "SELECT 1 FROM tipos_incidente WHERE id_tipo_incidente = ? AND activo = 1 AND es_emergencia = 0", "i", [$idTipo]);
    if (!$tipoValido) {
        $errores['id_tipo_incidente'] = 'Elige qué pasó.';
    }
    if (mb_strlen($descripcion) < 10 || mb_strlen($descripcion) > 500) {
        $errores['descripcion'] = 'Cuéntanos qué pasó (10 a 500 caracteres).';
    }

    // La foto es opcional
    if (!$errores && isset($_FILES['evidencia']) && $_FILES['evidencia']['error'] !== UPLOAD_ERR_NO_FILE) {
        $subida = subir_archivo($_FILES['evidencia'], 'evidencias', 'evidencia', false, 5);
        if ($subida['error']) {
            $errores['evidencia'] = $subida['error'];
        } else {
            $evidencia = $subida['nombre'];
        }
    }

    if (!$errores) {
        $folioIncidente = nuevo_folio($conexion, 'incidentes');
        ejecutar($conexion, "INSERT INTO incidentes (folio, id_viaje, id_reportante, id_tipo_incidente, descripcion, archivo_evidencia)
                             VALUES (?, ?, ?, ?, ?, ?)",
            "siiiss", [$folioIncidente, $viaje['id_viaje'], $idPasajero, $idTipo, $descripcion, $evidencia]);
        avisar('Enviamos tu reporte con el folio ' . $folioIncidente . '. La respuesta de la central aparecerá en «Mis reportes».');
        header('Location: detalle.php?folio=' . urlencode($viaje['folio']));
        exit;
    }
}

$tituloPagina = 'Reportar un problema';
$paginaActiva = 'viajes';
require __DIR__ . '/../includes/app_arriba.php';
?>

<nav class="migas"><a href="detalle.php?folio=<?= urlencode($viaje['folio']) ?>"><?= icono('arrow_back') ?> Detalle del viaje</a></nav>
<h1 class="t-headline-s mb-1">Reportar un problema</h1>
<p class="texto-secundario mb-4">Viaje del <?= fecha_hora($viaje['fecha_solicitud']) ?> · <?= e(explode(',', $viaje['destino_direccion'])[0]) ?></p>

<form method="POST" action="reportar.php" enctype="multipart/form-data" class="tarjeta js-validar" novalidate style="max-width: 640px;">
    <input type="hidden" name="folio" value="<?= e($viaje['folio']) ?>">

    <div class="mb-3">
        <label for="id_tipo_incidente" class="form-label">¿Qué pasó?</label>
        <select id="id_tipo_incidente" name="id_tipo_incidente" class="form-select <?= isset($errores['id_tipo_incidente']) ? 'is-invalid' : '' ?>" required data-error="Elige qué pasó.">
            <option value="">Elige una opción</option>
            <?php foreach ($tipos as $t): ?>
                <option value="<?= $t['id_tipo_incidente'] ?>" <?= ($_POST['id_tipo_incidente'] ?? '') == $t['id_tipo_incidente'] ? 'selected' : '' ?>><?= e($t['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="invalid-feedback" id="error-id_tipo_incidente"><?= e($errores['id_tipo_incidente'] ?? '') ?></div>
    </div>

    <div class="mb-3">
        <label for="descripcion" class="form-label">Cuéntanos qué pasó</label>
        <textarea id="descripcion" name="descripcion" rows="4" class="form-control <?= isset($errores['descripcion']) ? 'is-invalid' : '' ?>"
                  required minlength="10" maxlength="500" data-error="Cuéntanos qué pasó (10 a 500 caracteres)."><?= e($_POST['descripcion'] ?? '') ?></textarea>
        <div class="form-text">Entre más detalles nos des, más rápido lo resolvemos.</div>
        <div class="invalid-feedback" id="error-descripcion"><?= e($errores['descripcion'] ?? '') ?></div>
    </div>

    <div class="mb-4">
        <label for="evidencia" class="form-label">Foto <span class="texto-secundario fw-normal">(opcional)</span></label>
        <input type="file" id="evidencia" name="evidencia" class="form-control <?= isset($errores['evidencia']) ? 'is-invalid' : '' ?>" accept="image/jpeg,image/png,image/webp">
        <div class="form-text">JPG, PNG o WEBP de hasta 5 MB. Por ejemplo, el ticket o el objeto olvidado.</div>
        <div class="invalid-feedback" id="error-evidencia"><?= e($errores['evidencia'] ?? '') ?></div>
    </div>

    <button type="submit" class="btn btn-principal btn-lg">Enviar reporte</button>
</form>

<?php require __DIR__ . '/../includes/app_abajo.php'; ?>
