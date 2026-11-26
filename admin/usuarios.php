<?php
// ADMINISTRADOR · Cuentas de la base: el personal de la central.
// No existen en el registro público; se crean aquí. Pasajeros y conductores
// se registran solos y se administran en la app de escritorio.
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
requerir_rol(['ADMINISTRADOR']);

$cuentas = consultar($conexion, "SELECT id_usuario, nombre, apellido_paterno, apellido_materno, telefono, correo, estado, fecha_registro, bloqueado_hasta
                                 FROM usuarios WHERE rol = 'BASE' ORDER BY estado, nombre");

$tituloPagina = 'Cuentas de la base';
$paginaActiva = 'panel';
require __DIR__ . '/../includes/panel_arriba.php';
?>

<nav class="migas"><a href="dashboard.php"><?= icono('arrow_back') ?> Panel</a></nav>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="t-headline-s mb-1">Cuentas de la base</h1>
        <p class="texto-secundario mb-0">El personal de la central que revisa conductores, atiende incidentes y cambia la tarifa.</p>
    </div>
    <a href="usuario.php" class="btn btn-principal">Nueva cuenta de la base</a>
</div>

<?php if (!$cuentas): ?>
    <div class="vacio tarjeta"><?= icono('group') ?><p class="mb-0">Aún no hay cuentas de la base.</p></div>
<?php else: ?>
    <div class="tabla-contenedor">
        <div class="table-responsive">
            <table class="table tabla">
                <thead><tr><th>Nombre</th><th>Contacto</th><th>Estado</th><th>Registro</th><th><span class="visually-hidden">Acciones</span></th></tr></thead>
                <tbody>
                    <?php foreach ($cuentas as $u): ?>
                        <tr>
                            <td><?= e(nombre_completo($u)) ?></td>
                            <td><?= e($u['correo']) ?><div class="meta"><?= e(telefono_bonito($u['telefono'])) ?></div></td>
                            <td>
                                <?= etiqueta($u['estado']) ?>
                                <?php if ($u['bloqueado_hasta'] && strtotime($u['bloqueado_hasta']) > time()): ?>
                                    <div class="meta">Bloqueada hasta las <?= date('H:i', strtotime($u['bloqueado_hasta'])) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= fecha_corta($u['fecha_registro']) ?></td>
                            <td class="text-end text-nowrap">
                                <a href="usuario.php?id=<?= $u['id_usuario'] ?>" class="btn btn-contorno btn-sm">Editar<span class="visually-hidden"> a <?= e($u['nombre']) ?></span></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/panel_abajo.php'; ?>
