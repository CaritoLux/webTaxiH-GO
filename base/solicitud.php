<?php
// =====================================================================
//  BASE · Revisión de la solicitud de un conductor (solicitud.php?id=)
//  Cada documento se acepta o se rechaza con motivo. Si todos quedan
//  aceptados se puede autorizar; si alguno se rechaza, solo se puede
//  rechazar la solicitud (el conductor corrige y la vuelve a enviar).
// =====================================================================
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
requerir_rol(['BASE']);

$idConductor = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$datos = consultar_uno($conexion, "SELECT u.*, c.estado_revision, c.fecha_envio, c.fecha_revision, c.motivo_rechazo, r.nombre AS revisor
                                   FROM conductores c
                                   JOIN usuarios u ON u.id_usuario = c.id_usuario
                                   LEFT JOIN usuarios r ON r.id_usuario = c.id_revisor
                                   WHERE c.id_usuario = ?", "i", [$idConductor]);
if (!$datos) {
    header('Location: solicitudes.php');
    exit;
}

// La parte del conductor (taxi y documentos) es la misma que ve él en su panel
require __DIR__ . '/../includes/conductor_datos.php';
$enRevision = ($datos['estado_revision'] === 'EN_REVISION');
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $enRevision) {
    $accion = $_POST['accion'] ?? '';
    $decisiones = $_POST['doc'] ?? [];
    $motivos = $_POST['motivo'] ?? [];
    $mensaje = trim($_POST['mensaje'] ?? '');
    $rechazados = 0;
    $faltaAlgo = !$vehiculo;   // sin taxi o sin algún documento solo se puede rechazar

    foreach ($documentos as $d) {
        $id = $d['id_documento'];
        if (!$id) {
            $faltaAlgo = true;
            continue;
        }
        $decision = $decisiones[$id] ?? '';
        if (!in_array($decision, ['ACEPTADO', 'RECHAZADO'])) {
            $errores['doc' . $id] = 'Acepta o rechaza este documento.';
        } elseif ($decision === 'RECHAZADO') {
            $rechazados++;
            if (mb_strlen(trim($motivos[$id] ?? '')) < 5) {
                $errores['motivo' . $id] = 'Escribe qué debe corregir el conductor.';
            }
        } elseif ($d['vigencia'] && $d['vigencia'] < $hoy) {
            $errores['doc' . $id] = 'Este documento está vencido; no se puede aceptar.';
        }
    }
    if ($accion === 'autorizar' && ($rechazados > 0 || $faltaAlgo)) {
        $errores['general'] = 'Hay documentos rechazados o faltantes: solo puedes rechazar la solicitud.';
    }
    if ($accion === 'rechazar' && $rechazados === 0 && !$faltaAlgo) {
        $errores['general'] = 'Para rechazar la solicitud marca qué documento debe corregir.';
    }

    if (!in_array($accion, ['autorizar', 'rechazar'])) {
        $errores['general'] = 'Elige si autorizas o rechazas la solicitud.';
    }

    if (!$errores) {
        // Se guarda la decisión de cada documento
        foreach ($documentos as $d) {
            if (!$d['id_documento']) {
                continue;
            }
            $decision = $decisiones[$d['id_documento']] ?? 'PENDIENTE';
            $observacion = $decision === 'RECHAZADO' ? mb_substr(trim($motivos[$d['id_documento']]), 0, 300) : null;
            ejecutar($conexion, "UPDATE documentos SET estado = ?, observacion = ? WHERE id_documento = ?",
                "ssi", [$decision, $observacion, $d['id_documento']]);
        }

        if ($accion === 'autorizar') {
            ejecutar($conexion, "UPDATE conductores SET estado_revision = 'APROBADO', id_revisor = ?, fecha_revision = NOW(), motivo_rechazo = NULL
                                 WHERE id_usuario = ?", "ii", [$_SESSION['usuario_id'], $idConductor]);
            avisar('Autorizaste a ' . $datos['nombre'] . ' ' . $datos['apellido_paterno'] . '. Ya puede conectarse en la app.');
        } else {
            $motivoGeneral = $mensaje !== '' ? mb_substr($mensaje, 0, 500) : 'Revisa los documentos marcados como rechazados.';
            ejecutar($conexion, "UPDATE conductores SET estado_revision = 'RECHAZADO', id_revisor = ?, fecha_revision = NOW(), motivo_rechazo = ?
                                 WHERE id_usuario = ?", "isi", [$_SESSION['usuario_id'], $motivoGeneral, $idConductor]);
            avisar('Rechazaste la solicitud de ' . $datos['nombre'] . '. Verá en su panel qué debe corregir.');
        }
        header('Location: solicitudes.php');
        exit;
    }
}

$tituloPagina = 'Revisión de ' . $datos['nombre'];
$paginaActiva = 'solicitudes';
$scripts = ['assets/js/revision.js'];
require __DIR__ . '/../includes/panel_arriba.php';
?>

<nav class="migas"><a href="solicitudes.php"><?= icono('arrow_back') ?> Solicitudes de conductores</a></nav>
<div class="d-flex flex-wrap align-items-center gap-3 mb-1">
    <h1 class="t-headline-s mb-0">Revisión de <?= e(nombre_completo($datos)) ?></h1>
    <?= etiqueta($datos['estado_revision']) ?>
</div>
<p class="texto-secundario mb-4">
    <?php if ($vehiculo): ?>
        <?= e($vehiculo['marca'] . ' ' . $vehiculo['modelo'] . ' ' . $vehiculo['anio'] . ' ' . mb_strtolower($vehiculo['color'])) ?>
        · No. económico <?= e($vehiculo['numero_economico']) ?> · placas <?= e($vehiculo['placas']) ?>
    <?php else: ?>
        Sin taxi registrado
    <?php endif; ?>
    <?= $datos['fecha_envio'] ? ' · enviada el ' . date('d/m/Y', strtotime($datos['fecha_envio'])) : '' ?>
</p>

<div class="d-flex flex-wrap gap-4 mb-4 t-body-m">
    <span class="d-flex align-items-center gap-2"><?= icono('call', 'texto-secundario') ?> <a href="tel:<?= e($datos['telefono']) ?>"><?= e(telefono_bonito($datos['telefono'])) ?></a></span>
    <span class="d-flex align-items-center gap-2"><?= icono('mail', 'texto-secundario') ?> <a href="mailto:<?= e($datos['correo']) ?>"><?= e($datos['correo']) ?></a></span>
    <span class="d-flex align-items-center gap-2"><?= icono('schedule', 'texto-secundario') ?> registrado el <?= fecha_corta($datos['fecha_registro']) ?></span>
</div>

<?php if (!$enRevision && $datos['fecha_revision']): ?>
    <div class="aviso <?= $datos['estado_revision'] === 'APROBADO' ? 'aviso-exito' : 'aviso-info' ?> mb-4">
        <?= icono($datos['estado_revision'] === 'APROBADO' ? 'check_circle' : 'error') ?>
        <div>
            <?= $datos['estado_revision'] === 'APROBADO' ? 'Autorizado' : 'Revisado' ?> por <?= e($datos['revisor'] ?? 'la central') ?> el <?= fecha_hora($datos['fecha_revision']) ?>.
            <?= $datos['estado_revision'] === 'RECHAZADO' && $datos['motivo_rechazo'] ? 'Mensaje al conductor: ' . e($datos['motivo_rechazo']) : '' ?>
        </div>
    </div>
<?php elseif (!$enRevision): ?>
    <div class="aviso aviso-info mb-4"><?= icono('schedule') ?><div>El conductor aún no envía su solicitud a revisión.</div></div>
<?php endif; ?>

<?php if (isset($errores['general'])): ?>
    <div class="aviso aviso-error mb-4" role="alert"><?= icono('error') ?><div><?= e($errores['general']) ?></div></div>
<?php elseif ($errores): ?>
    <div class="aviso aviso-error mb-4" role="alert"><?= icono('error') ?><div>Revisa los documentos marcados en rojo.</div></div>
<?php endif; ?>

<form method="POST" action="solicitud.php" id="formRevision" class="js-validar" novalidate data-total="<?= count($documentos) ?>" data-taxi="<?= $vehiculo ? 1 : 0 ?>">
    <input type="hidden" name="id" value="<?= $idConductor ?>">

    <div class="row g-3">
        <?php foreach ($documentos as $d): ?>
            <?php
            $id = $d['id_documento'];
            $vencido = $d['vigencia'] && $d['vigencia'] < $hoy;
            $elegido = $_POST['doc'][$id] ?? ($d['estado'] === 'PENDIENTE' ? '' : $d['estado']);
            ?>
            <div class="col-md-6 col-xl-4">
                <article class="tarjeta h-100 p-3 d-flex flex-column <?= isset($errores['doc' . $id]) || isset($errores['motivo' . $id]) ? 'border-danger' : '' ?>">
                    <?php if ($id): ?>
                        <a href="../archivo.php?doc=<?= $id ?>" target="_blank" class="d-block mb-3" title="Abrir en otra pestaña">
                            <?php if (strtolower(pathinfo($d['archivo'], PATHINFO_EXTENSION)) === 'pdf'): ?>
                                <span class="vista-documento flex-column gap-2"><?= icono('badge') ?> Abrir PDF</span>
                            <?php else: ?>
                                <img src="../archivo.php?doc=<?= $id ?>" class="vista-documento" alt="<?= e($d['nombre']) ?> de <?= e($datos['nombre']) ?>" loading="lazy">
                            <?php endif; ?>
                        </a>
                    <?php else: ?>
                        <span class="vista-documento mb-3 flex-column gap-2"><?= icono('badge') ?> No lo ha subido</span>
                    <?php endif; ?>

                    <h2 class="t-title-m mb-1"><?= e($d['nombre']) ?></h2>
                    <p class="t-body-m texto-secundario mb-3">
                        <?php if ($d['folio']): ?><?= e($d['folio']) ?> · <?php endif; ?>
                        <?php if ($d['vigencia']): ?>
                            vence <?= date('d/m/Y', strtotime($d['vigencia'])) ?>
                            <?php if ($vencido): ?><span class="estado estado-error ms-1">Vencido</span><?php endif; ?>
                        <?php elseif ($d['clave'] === 'TARJETA_CIRCULACION' && $vehiculo): ?>
                            Placas registradas: <?= e($vehiculo['placas']) ?>
                        <?php elseif ($d['clave'] === 'FOTO_TAXI' && $vehiculo): ?>
                            No. económico <?= e($vehiculo['numero_economico']) ?>
                        <?php elseif ($id): ?>
                            Subido el <?= fecha_corta($d['fecha_carga']) ?>
                        <?php endif; ?>
                    </p>

                    <div class="mt-auto">
                        <?php if ($enRevision && $id): ?>
                            <div class="segmentado" role="radiogroup" aria-label="Decisión sobre <?= e($d['nombre']) ?>">
                                <input type="radio" class="btn-check js-decision" name="doc[<?= $id ?>]" id="acepta<?= $id ?>" value="ACEPTADO"
                                       data-doc="<?= $id ?>" <?= $elegido === 'ACEPTADO' ? 'checked' : '' ?> <?= $vencido ? 'disabled' : '' ?>>
                                <label class="btn" for="acepta<?= $id ?>"><?= icono('check', 'marca-check') ?> Aceptar</label>
                                <input type="radio" class="btn-check js-decision" name="doc[<?= $id ?>]" id="rechaza<?= $id ?>" value="RECHAZADO"
                                       data-doc="<?= $id ?>" <?= $elegido === 'RECHAZADO' || $vencido ? 'checked' : '' ?>>
                                <label class="btn" for="rechaza<?= $id ?>"><?= icono('check', 'marca-check') ?> Rechazar</label>
                            </div>
                            <?php if (isset($errores['doc' . $id])): ?>
                                <div class="invalid-feedback d-block"><?= e($errores['doc' . $id]) ?></div>
                            <?php endif; ?>
                            <div class="mt-3 <?= ($elegido === 'RECHAZADO' || $vencido) ? '' : 'd-none' ?>" id="cajaMotivo<?= $id ?>">
                                <label for="motivo<?= $id ?>" class="form-label">Motivo del rechazo</label>
                                <textarea name="motivo[<?= $id ?>]" id="motivo<?= $id ?>" rows="2" maxlength="300"
                                          class="form-control <?= isset($errores['motivo' . $id]) ? 'is-invalid' : '' ?>" minlength="5"
                                          data-error="Escribe qué debe corregir el conductor."
                                          placeholder="Ej. La foto no se lee. Tómala con más luz."><?= e($_POST['motivo'][$id] ?? ($vencido ? 'El documento está vencido. Sube uno vigente.' : '')) ?></textarea>
                                <div class="invalid-feedback" id="error-motivo<?= $id ?>"><?= e($errores['motivo' . $id] ?? '') ?></div>
                            </div>
                        <?php elseif ($id): ?>
                            <?= etiqueta($d['estado']) ?>
                            <?php if ($d['observacion']): ?><p class="t-body-m mt-2 mb-0" style="color: var(--color-error);"><?= e($d['observacion']) ?></p><?php endif; ?>
                        <?php endif; ?>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($enRevision): ?>
        <div class="tarjeta tarjeta-gris mt-4">
            <div class="mb-3 d-none" id="cajaMensaje">
                <label for="mensaje" class="form-label">Mensaje para el conductor <span class="texto-secundario fw-normal">(opcional)</span></label>
                <textarea name="mensaje" id="mensaje" rows="2" maxlength="500" class="form-control bg-white"
                          placeholder="Ej. Corrige los documentos marcados y vuelve a enviar tu solicitud."><?= e($_POST['mensaje'] ?? '') ?></textarea>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-3">
                <p class="t-body-m mb-0 me-auto" id="resumenRevision" aria-live="polite">Acepta o rechaza cada documento.</p>
                <button type="submit" name="accion" value="rechazar" class="btn btn-contorno btn-lg" id="btnRechazar">Rechazar solicitud</button>
                <button type="submit" name="accion" value="autorizar" class="btn btn-principal btn-lg" id="btnAutorizar">Autorizar conductor</button>
            </div>
        </div>
    <?php endif; ?>
</form>

<?php require __DIR__ . '/../includes/panel_abajo.php'; ?>
