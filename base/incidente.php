<?php
// BASE · Detalle de un incidente: atenderlo y cerrarlo con una resolución
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
requerir_rol(['BASE']);

$idIncidente = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$sql = "SELECT i.*, t.nombre AS tipo, t.prioridad, t.es_emergencia,
               r.nombre AS r_nombre, r.apellido_paterno AS r_paterno, r.telefono AS r_telefono, r.rol AS r_rol,
               a.nombre AS a_nombre,
               v.folio AS v_folio, v.estado AS v_estado, v.origen_direccion, v.destino_direccion, v.tarifa_estimada, v.fecha_solicitud,
               v.origen_lat, v.origen_lng, v.destino_lat, v.destino_lng, v.ruta,
               p.nombre AS p_nombre, p.apellido_paterno AS p_paterno, p.telefono AS p_telefono,
               c.nombre AS c_nombre, c.apellido_paterno AS c_paterno, c.telefono AS c_telefono,
               ve.numero_economico, ve.placas, ma.nombre AS marca, ve.modelo, co.nombre AS color
        FROM incidentes i
        JOIN tipos_incidente t ON t.id_tipo_incidente = i.id_tipo_incidente
        JOIN usuarios r ON r.id_usuario = i.id_reportante
        LEFT JOIN usuarios a ON a.id_usuario = i.id_atiende
        JOIN viajes v ON v.id_viaje = i.id_viaje
        JOIN usuarios p ON p.id_usuario = v.id_pasajero
        LEFT JOIN usuarios c ON c.id_usuario = v.id_conductor
        LEFT JOIN vehiculos ve ON ve.id_conductor = v.id_conductor
        LEFT JOIN marcas ma ON ma.id_marca = ve.id_marca
        LEFT JOIN colores co ON co.id_color = ve.id_color
        WHERE i.id_incidente = ?";
$inc = consultar_uno($conexion, $sql, "i", [$idIncidente]);
if (!$inc) {
    header('Location: incidentes.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'atender' && $inc['estado'] === 'ABIERTO') {
        ejecutar($conexion, "UPDATE incidentes SET estado = 'EN_ATENCION', id_atiende = ?, fecha_atencion = NOW() WHERE id_incidente = ?",
            "ii", [$_SESSION['usuario_id'], $idIncidente]);
        avisar('Marcaste el incidente como en atención.');
        header('Location: incidente.php?id=' . $idIncidente);
        exit;
    }
    if ($accion === 'cerrar' && $inc['estado'] !== 'CERRADO') {
        $resolucion = trim($_POST['resolucion'] ?? '');
        if (mb_strlen($resolucion) < 10 || mb_strlen($resolucion) > 500) {
            $error = 'Escribe cómo se resolvió (10 a 500 caracteres). Lo verá quien hizo el reporte.';
        } else {
            ejecutar($conexion, "UPDATE incidentes SET estado = 'CERRADO', resolucion = ?, fecha_cierre = NOW(),
                                        id_atiende = COALESCE(id_atiende, ?), fecha_atencion = COALESCE(fecha_atencion, NOW())
                                 WHERE id_incidente = ?", "sii", [$resolucion, $_SESSION['usuario_id'], $idIncidente]);
            avisar('Cerraste el incidente ' . $inc['folio'] . '.');
            header('Location: incidentes.php');
            exit;
        }
    }
}

$tieneMapa = ($inc['latitud'] !== null);
$tituloPagina = 'Incidente ' . $inc['folio'];
$paginaActiva = 'incidentes';
$ocultarAlerta = true;
$usaMapa = $tieneMapa;
$scripts = $tieneMapa ? ['assets/js/incidente.js'] : [];
require __DIR__ . '/../includes/panel_arriba.php';
?>

<nav class="migas"><a href="incidentes.php"><?= icono('arrow_back') ?> Incidentes</a></nav>
<div class="d-flex flex-wrap align-items-center gap-3 mb-1">
    <h1 class="t-headline-s mb-0"><?= e($inc['tipo']) ?></h1>
    <?php if ($inc['prioridad'] === 'ALTA'): ?><span class="estado estado-error">Prioridad alta</span><?php endif; ?>
    <?= etiqueta($inc['estado']) ?>
</div>
<p class="texto-secundario mb-4">Folio <?= e($inc['folio']) ?> · reportado el <?= fecha_hora($inc['fecha_reporte']) ?></p>

<div class="row g-4">
    <div class="col-lg-7">
        <section class="tarjeta mb-4">
            <h2 class="t-title-m mb-3">Qué pasó</h2>
            <p class="mb-3"><?= nl2br(e($inc['descripcion'] ?? 'Sin descripción.')) ?></p>
            <p class="t-body-m texto-secundario mb-0">
                Reportó <?= e($inc['r_nombre'] . ' ' . $inc['r_paterno']) ?> (<?= mb_strtolower(nombre_rol($inc['r_rol'])) ?>) ·
                <a href="tel:<?= e($inc['r_telefono']) ?>"><?= e(telefono_bonito($inc['r_telefono'])) ?></a>
            </p>
            <?php if ($inc['archivo_evidencia']): ?>
                <a href="../archivo.php?evidencia=<?= $inc['id_incidente'] ?>" target="_blank" class="d-block mt-3" style="max-width: 360px;">
                    <img src="../archivo.php?evidencia=<?= $inc['id_incidente'] ?>" class="vista-documento" alt="Foto que envió quien reportó">
                </a>
            <?php endif; ?>
        </section>

        <?php if ($tieneMapa): ?>
            <section class="tarjeta mb-4">
                <h2 class="t-title-m mb-3">Ubicación al enviar la alerta</h2>
                <div id="mapaIncidente" class="mapa" style="height: 320px;"
                     data-punto="<?= e(json_encode([(float) $inc['latitud'], (float) $inc['longitud']])) ?>"
                     data-origen="<?= e(json_encode([(float) $inc['origen_lat'], (float) $inc['origen_lng']])) ?>"
                     data-destino="<?= e(json_encode([(float) $inc['destino_lat'], (float) $inc['destino_lng']])) ?>"
                     role="region" aria-label="Mapa con la ubicación de la alerta"></div>
                <a href="https://www.google.com/maps?q=<?= e($inc['latitud']) ?>,<?= e($inc['longitud']) ?>" target="_blank" rel="noopener" class="btn btn-texto mt-2">
                    <?= icono('open_in_new') ?> Abrir en Google Maps
                </a>
            </section>
        <?php endif; ?>

        <section class="tarjeta">
            <h2 class="t-title-m mb-3">Viaje <?= e($inc['v_folio']) ?></h2>
            <ul class="list-unstyled mb-0 t-body-m">
                <li class="mb-2"><span class="texto-secundario">Fecha:</span> <?= fecha_hora($inc['fecha_solicitud']) ?> · <?= etiqueta($inc['v_estado']) ?></li>
                <li class="mb-2"><span class="texto-secundario">De:</span> <?= e($inc['origen_direccion']) ?></li>
                <li class="mb-2"><span class="texto-secundario">A:</span> <?= e($inc['destino_direccion']) ?></li>
                <li class="mb-2"><span class="texto-secundario">Tarifa estimada:</span> <?= dinero($inc['tarifa_estimada']) ?></li>
                <li class="mb-2"><span class="texto-secundario">Pasajero:</span> <?= e($inc['p_nombre'] . ' ' . $inc['p_paterno']) ?> ·
                    <a href="tel:<?= e($inc['p_telefono']) ?>"><?= e(telefono_bonito($inc['p_telefono'])) ?></a></li>
                <?php if ($inc['c_nombre']): ?>
                    <li><span class="texto-secundario">Conductor:</span> <?= e($inc['c_nombre'] . ' ' . $inc['c_paterno']) ?> ·
                        <a href="tel:<?= e($inc['c_telefono']) ?>"><?= e(telefono_bonito($inc['c_telefono'])) ?></a>
                        · <?= e($inc['marca'] . ' ' . $inc['modelo'] . ' ' . mb_strtolower($inc['color'])) ?>, No. <?= e($inc['numero_economico']) ?>, <?= e($inc['placas']) ?></li>
                <?php endif; ?>
            </ul>
        </section>
    </div>

    <div class="col-lg-5">
        <?php if ($inc['estado'] === 'CERRADO'): ?>
            <section class="tarjeta tarjeta-gris">
                <h2 class="t-title-m mb-2">Resolución</h2>
                <p class="mb-2"><?= nl2br(e($inc['resolucion'])) ?></p>
                <p class="meta mb-0">Cerró <?= e($inc['a_nombre'] ?? 'la central') ?> el <?= fecha_hora($inc['fecha_cierre']) ?></p>
            </section>
        <?php else: ?>
            <?php if ($inc['estado'] === 'ABIERTO'): ?>
                <form method="POST" action="incidente.php" class="tarjeta mb-4">
                    <input type="hidden" name="id" value="<?= $idIncidente ?>">
                    <input type="hidden" name="accion" value="atender">
                    <h2 class="t-title-m mb-2">Atender</h2>
                    <p class="t-body-m texto-secundario">Márcalo para que el resto de la central sepa que tú lo estás atendiendo.</p>
                    <button type="submit" class="btn btn-contorno">Lo estoy atendiendo</button>
                </form>
            <?php else: ?>
                <div class="aviso aviso-info mb-4"><?= icono('schedule') ?>
                    <div>Lo atiende <?= e($inc['a_nombre']) ?> desde el <?= fecha_hora($inc['fecha_atencion']) ?>.</div></div>
            <?php endif; ?>

            <form method="POST" action="incidente.php" class="tarjeta js-validar" novalidate>
                <input type="hidden" name="id" value="<?= $idIncidente ?>">
                <input type="hidden" name="accion" value="cerrar">
                <h2 class="t-title-m mb-3">Cerrar incidente</h2>
                <label for="resolucion" class="form-label">¿Cómo se resolvió?</label>
                <textarea name="resolucion" id="resolucion" rows="4" class="form-control <?= $error ? 'is-invalid' : '' ?>" required minlength="10" maxlength="500"
                          data-error="Escribe cómo se resolvió (10 a 500 caracteres)."><?= e($_POST['resolucion'] ?? '') ?></textarea>
                <div class="form-text">Lo verá quien hizo el reporte.</div>
                <div class="invalid-feedback" id="error-resolucion"><?= e($error) ?></div>
                <button type="submit" class="btn btn-principal btn-lg w-100 mt-3">Cerrar incidente</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/panel_abajo.php'; ?>
