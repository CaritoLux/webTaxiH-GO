<?php
// CONDUCTOR · Registrar o cambiar los datos de su taxi
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
requerir_rol(['CONDUCTOR']);

$idConductor = $_SESSION['usuario_id'];
require __DIR__ . '/../includes/conductor_datos.php';

if (!$editable) {
    avisar('Mientras tu solicitud está en revisión o aprobada no puedes cambiar tu taxi. Si cambiaste de taxi, comunícate con la central.', 'error');
    header('Location: panel.php');
    exit;
}

$marcas = consultar($conexion, "SELECT id_marca, nombre FROM marcas WHERE activo = 1 ORDER BY nombre = 'Otra', nombre");
$colores = consultar($conexion, "SELECT id_color, nombre FROM colores WHERE activo = 1 ORDER BY nombre = 'Otro', nombre");
$anioMax = (int) date('Y') + 1;
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $economico = strtoupper(trim($_POST['numero_economico'] ?? ''));
    $placas    = strtoupper(trim($_POST['placas'] ?? ''));
    $idMarca   = $_POST['id_marca'] ?? '';
    $modelo    = trim($_POST['modelo'] ?? '');
    $anio      = $_POST['anio'] ?? '';
    $idColor   = $_POST['id_color'] ?? '';
    $idVehiculo = $vehiculo['id_vehiculo'] ?? 0;

    if (!preg_match('/^[A-Z0-9]{1,6}$/', $economico)) {
        $errores['numero_economico'] = 'Escribe el número económico (hasta 6 letras o números).';
    } elseif (consultar_uno($conexion, "SELECT 1 FROM vehiculos WHERE numero_economico = ? AND id_vehiculo <> ?", "si", [$economico, $idVehiculo])) {
        $errores['numero_economico'] = 'Ese número económico ya está registrado con otro conductor.';
    }
    if (!preg_match('/^[A-Z0-9-]{5,10}$/', $placas)) {
        $errores['placas'] = 'Escribe las placas como aparecen en la tarjeta de circulación, por ejemplo HGO-123-A.';
    } elseif (consultar_uno($conexion, "SELECT 1 FROM vehiculos WHERE placas = ? AND id_vehiculo <> ?", "si", [$placas, $idVehiculo])) {
        $errores['placas'] = 'Esas placas ya están registradas con otro conductor.';
    }
    if (!is_numeric($idMarca) || !consultar_uno($conexion, "SELECT 1 FROM marcas WHERE id_marca = ? AND activo = 1", "i", [$idMarca])) {
        $errores['id_marca'] = 'Elige la marca.';
    }
    if (mb_strlen($modelo) < 2 || mb_strlen($modelo) > 30) {
        $errores['modelo'] = 'Escribe el modelo, por ejemplo Versa o Aveo.';
    }
    if (!is_numeric($anio) || $anio < 1990 || $anio > $anioMax) {
        $errores['anio'] = "Escribe el año del modelo (1990 a $anioMax).";
    }
    if (!is_numeric($idColor) || !consultar_uno($conexion, "SELECT 1 FROM colores WHERE id_color = ? AND activo = 1", "i", [$idColor])) {
        $errores['id_color'] = 'Elige el color.';
    }

    if (!$errores) {
        if ($vehiculo) {
            ejecutar($conexion, "UPDATE vehiculos SET numero_economico = ?, placas = ?, id_marca = ?, modelo = ?, anio = ?, id_color = ? WHERE id_vehiculo = ?",
                "ssisiii", [$economico, $placas, $idMarca, $modelo, $anio, $idColor, $idVehiculo]);
        } else {
            ejecutar($conexion, "INSERT INTO vehiculos (id_conductor, numero_economico, placas, id_marca, modelo, anio, id_color) VALUES (?, ?, ?, ?, ?, ?, ?)",
                "issisii", [$idConductor, $economico, $placas, $idMarca, $modelo, $anio, $idColor]);
        }
        avisar('Guardamos los datos de tu taxi.');
        header('Location: panel.php');
        exit;
    }
}

// Valor a mostrar: lo que escribió (si hubo error) o lo guardado
function valor_taxi($campo, $vehiculo)
{
    return $_POST[$campo] ?? ($vehiculo[$campo] ?? '');
}

$tituloPagina = 'Mi taxi';
$paginaActiva = 'solicitud';
require __DIR__ . '/../includes/app_arriba.php';
?>

<nav class="migas"><a href="panel.php"><?= icono('arrow_back') ?> Mi solicitud</a></nav>
<h1 class="t-headline-s mb-1"><?= $vehiculo ? 'Cambiar mi taxi' : 'Registrar mi taxi' ?></h1>
<p class="texto-secundario mb-4">Escribe los datos tal como aparecen en la tarjeta de circulación.</p>

<form method="POST" action="taxi.php" class="tarjeta js-validar" novalidate style="max-width: 640px;">
    <div class="row g-3">
        <div class="col-sm-6">
            <label for="numero_economico" class="form-label">Número económico</label>
            <input type="text" id="numero_economico" name="numero_economico" class="form-control <?= isset($errores['numero_economico']) ? 'is-invalid' : '' ?>"
                   value="<?= e(valor_taxi('numero_economico', $vehiculo)) ?>" required maxlength="6" pattern="[A-Za-z0-9]{1,6}"
                   inputmode="numeric" data-error="Escribe el número económico (hasta 6 letras o números).">
            <div class="form-text">El número pintado en el taxi.</div>
            <div class="invalid-feedback" id="error-numero_economico"><?= e($errores['numero_economico'] ?? '') ?></div>
        </div>
        <div class="col-sm-6">
            <label for="placas" class="form-label">Placas</label>
            <input type="text" id="placas" name="placas" class="form-control text-uppercase <?= isset($errores['placas']) ? 'is-invalid' : '' ?>"
                   value="<?= e(valor_taxi('placas', $vehiculo)) ?>" required maxlength="10" pattern="[A-Za-z0-9-]{5,10}"
                   data-error="Escribe las placas, por ejemplo HGO-123-A.">
            <div class="invalid-feedback" id="error-placas"><?= e($errores['placas'] ?? '') ?></div>
        </div>
        <div class="col-sm-6">
            <label for="id_marca" class="form-label">Marca</label>
            <select id="id_marca" name="id_marca" class="form-select <?= isset($errores['id_marca']) ? 'is-invalid' : '' ?>" required data-error="Elige la marca.">
                <option value="">Elige la marca</option>
                <?php foreach ($marcas as $m): ?>
                    <option value="<?= $m['id_marca'] ?>" <?= valor_taxi('id_marca', $vehiculo) == $m['id_marca'] ? 'selected' : '' ?>><?= e($m['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="invalid-feedback" id="error-id_marca"><?= e($errores['id_marca'] ?? '') ?></div>
        </div>
        <div class="col-sm-6">
            <label for="modelo" class="form-label">Modelo</label>
            <input type="text" id="modelo" name="modelo" class="form-control <?= isset($errores['modelo']) ? 'is-invalid' : '' ?>"
                   value="<?= e(valor_taxi('modelo', $vehiculo)) ?>" required minlength="2" maxlength="30" data-error="Escribe el modelo, por ejemplo Versa o Aveo.">
            <div class="invalid-feedback" id="error-modelo"><?= e($errores['modelo'] ?? '') ?></div>
        </div>
        <div class="col-sm-6">
            <label for="anio" class="form-label">Año</label>
            <input type="number" id="anio" name="anio" class="form-control <?= isset($errores['anio']) ? 'is-invalid' : '' ?>"
                   value="<?= e(valor_taxi('anio', $vehiculo)) ?>" required min="1990" max="<?= $anioMax ?>" inputmode="numeric"
                   data-error="Escribe el año del modelo (1990 a <?= $anioMax ?>).">
            <div class="invalid-feedback" id="error-anio"><?= e($errores['anio'] ?? '') ?></div>
        </div>
        <div class="col-sm-6">
            <label for="id_color" class="form-label">Color</label>
            <select id="id_color" name="id_color" class="form-select <?= isset($errores['id_color']) ? 'is-invalid' : '' ?>" required data-error="Elige el color.">
                <option value="">Elige el color</option>
                <?php foreach ($colores as $c): ?>
                    <option value="<?= $c['id_color'] ?>" <?= valor_taxi('id_color', $vehiculo) == $c['id_color'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="invalid-feedback" id="error-id_color"><?= e($errores['id_color'] ?? '') ?></div>
        </div>
    </div>
    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-principal btn-lg">Guardar mi taxi</button>
        <a href="panel.php" class="btn btn-texto btn-lg">Cancelar</a>
    </div>
</form>

<?php require __DIR__ . '/../includes/app_abajo.php'; ?>
