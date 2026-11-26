<?php
// =====================================================================
//  BASE · Tarifa
//  Cuando el gobierno (Semot) publica una tarifa nueva, la base la
//  captura aquí con la fecha desde la que aplica. No se toca el código:
//  la web, la app y el cálculo de cada viaje leen la tabla tarifas.
//  Las tarifas anteriores se conservan como historial.
// =====================================================================
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
requerir_rol(['BASE']);

$hoy = date('Y-m-d');
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'guardar') {
    $banderazo   = $_POST['banderazo'] ?? '';
    $kmIncluidos = $_POST['km_incluidos'] ?? '';
    $costoKm     = $_POST['costo_km'] ?? '';
    $desde       = $_POST['vigente_desde'] ?? '';
    $nota        = trim($_POST['nota'] ?? '');

    if (!is_numeric($banderazo) || $banderazo < 1 || $banderazo > 999) {
        $errores['banderazo'] = 'Escribe el banderazo en pesos (1 a 999).';
    }
    if (!is_numeric($kmIncluidos) || $kmIncluidos < 0 || $kmIncluidos > 20) {
        $errores['km_incluidos'] = 'Escribe los km que incluye el banderazo (0 a 20).';
    }
    if (!is_numeric($costoKm) || $costoKm < 0.5 || $costoKm > 99) {
        $errores['costo_km'] = 'Escribe el costo por km adicional (0.50 a 99).';
    }
    $fecha = DateTime::createFromFormat('Y-m-d', $desde);
    if (!$fecha || $fecha->format('Y-m-d') !== $desde) {
        $errores['vigente_desde'] = 'Elige desde qué día aplica.';
    } elseif ($desde < $hoy) {
        $errores['vigente_desde'] = 'La tarifa no puede aplicar a días que ya pasaron.';
    }
    if (mb_strlen($nota) > 200) {
        $errores['nota'] = 'La nota es de máximo 200 caracteres.';
    }

    if (!$errores) {
        ejecutar($conexion, "INSERT INTO tarifas (banderazo, km_incluidos, costo_km, vigente_desde, nota, id_registro) VALUES (?, ?, ?, ?, ?, ?)",
            "dddssi", [$banderazo, $kmIncluidos, $costoKm, $desde, $nota !== '' ? $nota : null, $_SESSION['usuario_id']]);
        avisar($desde === $hoy
            ? 'Guardamos la nueva tarifa. Ya se aplica a los viajes que se pidan desde ahora.'
            : 'Guardamos la nueva tarifa. Se aplicará desde el ' . fecha_corta($desde) . '.');
        header('Location: tarifa.php');
        exit;
    }
}

// Una tarifa programada (que aún no empieza) se puede quitar si se capturó mal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar' && is_numeric($_POST['id'] ?? '')) {
    ejecutar($conexion, "DELETE FROM tarifas WHERE id_tarifa = ? AND vigente_desde > CURDATE()
                         AND id_tarifa NOT IN (SELECT id_tarifa FROM viajes)", "i", [$_POST['id']]);
    avisar('Quitamos la tarifa programada.');
    header('Location: tarifa.php');
    exit;
}

$vigente = tarifa_vigente($conexion);
$historial = consultar($conexion, "SELECT t.*, u.nombre AS registro FROM tarifas t
                                   LEFT JOIN usuarios u ON u.id_usuario = t.id_registro
                                   ORDER BY t.vigente_desde DESC, t.id_tarifa DESC");
$programada = consultar_uno($conexion, "SELECT * FROM tarifas WHERE vigente_desde > CURDATE() ORDER BY vigente_desde LIMIT 1");

$tituloPagina = 'Tarifa';
$paginaActiva = 'tarifa';
$scripts = ['assets/js/tarifa.js'];
require __DIR__ . '/../includes/panel_arriba.php';
?>

<h1 class="t-headline-s mb-1">Tarifa</h1>
<p class="texto-secundario mb-4">Si la Semot publica una tarifa oficial para Huejutla, captúrala aquí. Se usa en la página, en la app y en el cálculo de cada viaje.</p>

<?php if ($programada): ?>
    <div class="aviso aviso-info mb-4"><?= icono('schedule') ?>
        <div>Hay una tarifa programada: banderazo <?= dinero($programada['banderazo']) ?> y <?= dinero($programada['costo_km']) ?> por km desde el <?= fecha_corta($programada['vigente_desde']) ?>.</div>
    </div>
<?php endif; ?>

<div class="row g-4 mb-5">
    <div class="col-lg-5">
        <section class="tarjeta h-100" aria-labelledby="tituloVigente">
            <h2 class="t-title-m mb-3" id="tituloVigente">Tarifa vigente</h2>
            <?php if ($vigente): ?>
                <div class="dato-etiqueta">Banderazo (incluye los primeros <?= e(rtrim(rtrim($vigente['km_incluidos'], '0'), '.')) ?> km)</div>
                <div class="precio-grande"><?= dinero($vigente['banderazo']) ?></div>
                <div class="t-title-m mb-3">más <?= dinero($vigente['costo_km']) ?> por cada km adicional</div>
                <p class="meta mb-0">Desde el <?= fecha_corta($vigente['vigente_desde']) ?><?= $vigente['nota'] ? ' · ' . e($vigente['nota']) : '' ?></p>
            <?php else: ?>
                <p class="texto-secundario mb-0">No hay tarifa vigente. Captura una para que los pasajeros puedan pedir taxi.</p>
            <?php endif; ?>
        </section>
    </div>

    <div class="col-lg-7">
        <form method="POST" action="tarifa.php" class="tarjeta js-validar" novalidate id="formTarifa"
              onsubmit="return confirm('¿Guardar la nueva tarifa? Se aplicará a los viajes que se pidan desde la fecha que elegiste.');">
            <input type="hidden" name="accion" value="guardar">
            <h2 class="t-title-m mb-3">Capturar tarifa nueva</h2>
            <div class="row g-3">
                <div class="col-sm-4">
                    <label for="banderazo" class="form-label">Banderazo ($)</label>
                    <input type="number" id="banderazo" name="banderazo" step="0.50" min="1" max="999" inputmode="decimal" required
                           class="form-control <?= isset($errores['banderazo']) ? 'is-invalid' : '' ?>"
                           value="<?= e($_POST['banderazo'] ?? $vigente['banderazo'] ?? '') ?>" data-error="Escribe el banderazo en pesos (1 a 999).">
                    <div class="invalid-feedback" id="error-banderazo"><?= e($errores['banderazo'] ?? '') ?></div>
                </div>
                <div class="col-sm-4">
                    <label for="km_incluidos" class="form-label">Km incluidos</label>
                    <input type="number" id="km_incluidos" name="km_incluidos" step="0.1" min="0" max="20" inputmode="decimal" required
                           class="form-control <?= isset($errores['km_incluidos']) ? 'is-invalid' : '' ?>"
                           value="<?= e($_POST['km_incluidos'] ?? $vigente['km_incluidos'] ?? '') ?>" data-error="De 0 a 20 km.">
                    <div class="invalid-feedback" id="error-km_incluidos"><?= e($errores['km_incluidos'] ?? '') ?></div>
                </div>
                <div class="col-sm-4">
                    <label for="costo_km" class="form-label">Por km adicional ($)</label>
                    <input type="number" id="costo_km" name="costo_km" step="0.10" min="0.5" max="99" inputmode="decimal" required
                           class="form-control <?= isset($errores['costo_km']) ? 'is-invalid' : '' ?>"
                           value="<?= e($_POST['costo_km'] ?? $vigente['costo_km'] ?? '') ?>" data-error="De $0.50 a $99.">
                    <div class="invalid-feedback" id="error-costo_km"><?= e($errores['costo_km'] ?? '') ?></div>
                </div>
                <div class="col-sm-6">
                    <label for="vigente_desde" class="form-label">Aplica desde</label>
                    <input type="date" id="vigente_desde" name="vigente_desde" min="<?= $hoy ?>" required
                           class="form-control <?= isset($errores['vigente_desde']) ? 'is-invalid' : '' ?>"
                           value="<?= e($_POST['vigente_desde'] ?? $hoy) ?>" data-error="Elige hoy o un día futuro.">
                    <div class="invalid-feedback" id="error-vigente_desde"><?= e($errores['vigente_desde'] ?? '') ?></div>
                </div>
                <div class="col-sm-6">
                    <label for="nota" class="form-label">Nota <span class="texto-secundario fw-normal">(opcional)</span></label>
                    <input type="text" id="nota" name="nota" maxlength="200" class="form-control <?= isset($errores['nota']) ? 'is-invalid' : '' ?>"
                           value="<?= e($_POST['nota'] ?? '') ?>" placeholder="Ej. Periódico Oficial del 1/12/2026">
                    <div class="invalid-feedback" id="error-nota"><?= e($errores['nota'] ?? '') ?></div>
                </div>
            </div>

            <div class="resumen-viaje mt-3 t-body-m" aria-live="polite">
                Con esta tarifa, un viaje de <strong>3 km</strong> cuesta <strong id="ejemplo3">—</strong>
                y uno de <strong>6 km</strong>, <strong id="ejemplo6">—</strong>.
            </div>

            <button type="submit" class="btn btn-principal btn-lg mt-4">Guardar tarifa</button>
        </form>
    </div>
</div>

<h2 class="t-title-l mb-3">Historial</h2>
<div class="tabla-contenedor">
    <div class="table-responsive">
        <table class="table tabla">
            <thead>
                <tr><th>Aplica desde</th><th>Banderazo</th><th>Km incluidos</th><th>Por km</th><th>Nota</th><th>Capturó</th><th>Estado</th><th><span class="visually-hidden">Acción</span></th></tr>
            </thead>
            <tbody>
                <?php foreach ($historial as $t): ?>
                    <?php
                    if ($vigente && $t['id_tarifa'] == $vigente['id_tarifa']) {
                        $estadoTarifa = '<span class="estado estado-exito">Vigente</span>';
                    } elseif ($t['vigente_desde'] > $hoy) {
                        $estadoTarifa = '<span class="estado estado-activo">Programada</span>';
                    } else {
                        $estadoTarifa = '<span class="estado estado-neutro">Anterior</span>';
                    }
                    ?>
                    <tr>
                        <td><?= fecha_corta($t['vigente_desde']) ?></td>
                        <td><?= dinero($t['banderazo']) ?></td>
                        <td><?= e(rtrim(rtrim($t['km_incluidos'], '0'), '.')) ?> km</td>
                        <td><?= dinero($t['costo_km']) ?></td>
                        <td class="texto-secundario"><?= e($t['nota'] ?? '') ?></td>
                        <td><?= e($t['registro'] ?? '—') ?><div class="meta"><?= fecha_corta($t['fecha_registro']) ?></div></td>
                        <td><?= $estadoTarifa ?></td>
                        <td class="text-end">
                            <?php if ($t['vigente_desde'] > $hoy): ?>
                                <form method="POST" action="tarifa.php" onsubmit="return confirm('¿Quitar esta tarifa programada?');">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id" value="<?= $t['id_tarifa'] ?>">
                                    <button type="submit" class="btn btn-texto-rojo btn-sm">Quitar</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/panel_abajo.php'; ?>
