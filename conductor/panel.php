<?php
// =====================================================================
//  CONDUCTOR · Mi solicitud
//  El conductor registra su taxi, sube sus 7 documentos y ve si están
//  pendientes, aceptados o rechazados. Cuando la central lo aprueba,
//  ya puede conectarse en la app y recibir viajes.
// =====================================================================
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
requerir_rol(['CONDUCTOR']);

$idConductor = $_SESSION['usuario_id'];
require __DIR__ . '/../includes/conductor_datos.php';

$estado = $conductor['estado_revision'];
$subidos = count(array_filter($documentos, function ($d) { return $d['id_documento']; }));

// Pasos de la solicitud (barra de progreso)
$pasos = [
    ['Datos del taxi', (bool) $vehiculo],
    ['Documentos', $subidos === count($documentos)],
    ['Revisión', in_array($estado, ['EN_REVISION', 'APROBADO'])],
    ['Autorizado', $estado === 'APROBADO'],
];

$revisor = $conductor['id_revisor']
    ? consultar_uno($conexion, "SELECT nombre FROM usuarios WHERE id_usuario = ?", "i", [$conductor['id_revisor']])
    : null;

$tituloPagina = 'Mi solicitud';
$paginaActiva = 'solicitud';
require __DIR__ . '/../includes/app_arriba.php';
?>

<div class="d-flex flex-wrap align-items-center gap-3 mb-2">
    <h1 class="t-headline-s mb-0">Mi solicitud</h1>
    <?= etiqueta($estado) ?>
</div>
<p class="texto-secundario mb-4">Para trabajar con <?= e($sitio['nombre']) ?> la central revisa tu taxi y tus documentos.</p>

<ol class="pasos-solicitud mb-4" aria-label="Avance de tu solicitud">
    <?php foreach ($pasos as $paso): ?>
        <li class="<?= $paso[1] ? 'hecho' : '' ?>">
            <div class="barra"></div>
            <div class="texto"><?= $paso[0] ?><span class="visually-hidden"><?= $paso[1] ? ': listo' : ': pendiente' ?></span></div>
        </li>
    <?php endforeach; ?>
</ol>

<?php if ($estado === 'SIN_DOCUMENTOS'): ?>
    <div class="aviso aviso-info mb-4"><?= icono('badge') ?>
        <div>Registra tu taxi y sube tus 7 documentos. Cuando tengas todo, envía tu solicitud a revisión.</div>
    </div>
<?php elseif ($estado === 'EN_REVISION'): ?>
    <div class="aviso aviso-info mb-4"><?= icono('schedule') ?>
        <div>La central está revisando tu solicitud (enviada el <?= fecha_hora($conductor['fecha_envio']) ?>).
            Mientras tanto no puedes cambiar tu taxi ni tus documentos.</div>
    </div>
<?php elseif ($estado === 'RECHAZADO'): ?>
    <div class="aviso aviso-error mb-4"><?= icono('error') ?>
        <div><strong>La central rechazó tu solicitud.</strong>
            <?= $conductor['motivo_rechazo'] ? e($conductor['motivo_rechazo']) : '' ?>
            Cambia los documentos marcados como rechazados y vuelve a enviarla.</div>
    </div>
<?php else: ?>
    <div class="aviso aviso-exito mb-4"><?= icono('check_circle') ?>
        <div><strong>¡Estás autorizado!</strong> Ya puedes conectarte en la app con tu correo y tu contraseña y recibir viajes.
            <?= $revisor ? 'Te aprobó ' . e($revisor['nombre']) . ' el ' . fecha_corta($conductor['fecha_revision']) . '.' : '' ?>
            <?php if (!empty($sitio['enlace_app'])): ?><a href="<?= e($sitio['enlace_app']) ?>" rel="noopener">Descarga la app</a><?php endif; ?></div>
    </div>
<?php endif; ?>

<!-- Datos del taxi -->
<section class="tarjeta mb-4" aria-labelledby="tituloTaxi">
    <div class="d-flex align-items-start justify-content-between gap-3">
        <div class="d-flex gap-3">
            <?= icono('directions_car', 'texto-secundario') ?>
            <div>
                <h2 class="t-title-m mb-1" id="tituloTaxi">Mi taxi</h2>
                <?php if ($vehiculo): ?>
                    <p class="mb-0"><?= e($vehiculo['marca'] . ' ' . $vehiculo['modelo'] . ' ' . $vehiculo['anio'] . ' · ' . mb_strtolower($vehiculo['color'])) ?></p>
                    <p class="t-body-m texto-secundario mb-0">No. económico <?= e($vehiculo['numero_economico']) ?> · placas <?= e($vehiculo['placas']) ?></p>
                <?php else: ?>
                    <p class="t-body-m texto-secundario mb-0">Aún no registras tu taxi.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($editable): ?>
            <a href="taxi.php" class="btn btn-contorno btn-sm"><?= $vehiculo ? 'Cambiar' : 'Registrar' ?></a>
        <?php endif; ?>
    </div>
</section>

<!-- Documentos -->
<section aria-labelledby="tituloDocs">
    <div class="d-flex justify-content-between align-items-baseline mb-2">
        <h2 class="t-title-l mb-0" id="tituloDocs">Mis documentos</h2>
        <span class="t-body-m texto-secundario"><?= $subidos ?> de <?= count($documentos) ?> subidos</span>
    </div>
    <p class="t-body-m texto-secundario">Fotos o PDF legibles de hasta 5 MB. Solo la central los ve.</p>

    <ul class="lista tarjeta p-0 mb-4">
        <?php foreach ($documentos as $d): ?>
            <?php
            $vencido = $d['vigencia'] && $d['vigencia'] < $hoy;
            $porVencer = $d['vigencia'] && !$vencido && $d['vigencia'] <= $enUnMes;
            // Puede subirlo si su solicitud se puede editar, o renovarlo si ya está aprobado y vence pronto
            $puedeSubir = $editable || ($estado === 'APROBADO' && ($vencido || $porVencer));
            ?>
            <li class="lista-fila flex-wrap">
                <?= icono('badge') ?>
                <div class="lista-texto">
                    <div class="t-body-l"><?= e($d['nombre']) ?></div>
                    <div class="lista-detalle">
                        <?php if ($d['id_documento']): ?>
                            <?= $d['folio'] ? e($d['folio']) . ' · ' : '' ?>
                            <?= $d['vigencia'] ? 'vence ' . date('d/m/Y', strtotime($d['vigencia'])) . ' · ' : '' ?>
                            subido el <?= fecha_corta($d['fecha_carga']) ?>
                        <?php else: ?>
                            <?= e($d['descripcion']) ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($d['estado'] === 'RECHAZADO' && $d['observacion']): ?>
                        <div class="t-body-m mt-1" style="color: var(--color-error);">Motivo: <?= e($d['observacion']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <?php if (!$d['id_documento']): ?>
                        <span class="estado estado-neutro">Falta</span>
                    <?php elseif ($vencido): ?>
                        <span class="estado estado-error">Vencido</span>
                    <?php elseif ($porVencer): ?>
                        <span class="estado estado-activo">Vence pronto</span>
                    <?php else: ?>
                        <?= etiqueta($d['estado']) ?>
                    <?php endif; ?>

                    <?php if ($d['id_documento']): ?>
                        <a href="../archivo.php?doc=<?= $d['id_documento'] ?>" target="_blank" class="btn btn-texto btn-sm">Ver<span class="visually-hidden"> <?= e($d['nombre']) ?></span></a>
                    <?php endif; ?>
                    <?php if ($puedeSubir): ?>
                        <a href="documento.php?tipo=<?= $d['id_tipo_documento'] ?>" class="btn btn-contorno btn-sm">
                            <?= $d['id_documento'] ? ($estado === 'APROBADO' ? 'Renovar' : 'Cambiar') : 'Subir' ?><span class="visually-hidden"> <?= e($d['nombre']) ?></span>
                        </a>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</section>

<?php if ($editable): ?>
    <form method="POST" action="enviar.php" class="tarjeta tarjeta-gris"
          onsubmit="return confirm('¿Enviar tu solicitud a revisión? Mientras la revisan no podrás cambiar tu taxi ni tus documentos.');">
        <?php if ($faltantes): ?>
            <p class="t-body-m mb-3"><strong>Para enviar tu solicitud falta:</strong> <?= e(implode(', ', $faltantes)) ?>.</p>
        <?php else: ?>
            <p class="t-body-m mb-3">Todo listo. Revisa que tus datos sean correctos y envía tu solicitud.</p>
        <?php endif; ?>
        <button type="submit" class="btn btn-principal btn-lg" <?= $faltantes ? 'disabled' : '' ?>>Enviar a revisión</button>
    </form>
<?php endif; ?>

<?php require __DIR__ . '/../includes/app_abajo.php'; ?>
