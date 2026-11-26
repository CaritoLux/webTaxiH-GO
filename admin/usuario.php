<?php
// ADMINISTRADOR · Crear o editar una cuenta de la base
//   usuario.php         -> nueva cuenta de la base
//   usuario.php?id=ID   -> editar datos, suspender o cambiar la contraseña
// (Pasajeros y conductores se registran solos; su administración es en la app de escritorio.)
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
requerir_rol(['ADMINISTRADOR']);

$idUsuario = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$usuario = $idUsuario ? consultar_uno($conexion, "SELECT * FROM usuarios WHERE id_usuario = ? AND rol = 'BASE'", "i", [$idUsuario]) : null;
if ($idUsuario && !$usuario) {
    header('Location: usuarios.php');
    exit;
}
$esNuevo = !$usuario;
$esYo = false; // el administrador no es cuenta de la base
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre     = trim($_POST['nombre'] ?? '');
    $paterno    = trim($_POST['apellido_paterno'] ?? '');
    $materno    = trim($_POST['apellido_materno'] ?? '');
    $telefono   = trim($_POST['telefono'] ?? '');
    $correo     = strtolower(trim($_POST['correo'] ?? ''));
    $estado     = ($esYo || ($_POST['estado'] ?? '') !== 'SUSPENDIDO') ? 'ACTIVO' : 'SUSPENDIDO';
    $contrasena = $_POST['contrasena'] ?? '';

    $soloLetras = '/^[\p{L} .\'-]{2,50}$/u';
    if (!preg_match($soloLetras, $nombre)) {
        $errores['nombre'] = 'Escribe el nombre (solo letras).';
    }
    if (!preg_match($soloLetras, $paterno)) {
        $errores['apellido_paterno'] = 'Escribe el apellido paterno (solo letras).';
    }
    if ($materno !== '' && !preg_match($soloLetras, $materno)) {
        $errores['apellido_materno'] = 'El apellido materno solo lleva letras.';
    }
    if (!preg_match('/^\d{10}$/', $telefono)) {
        $errores['telefono'] = 'El teléfono lleva 10 dígitos, sin espacios.';
    } elseif (consultar_uno($conexion, "SELECT 1 FROM usuarios WHERE telefono = ? AND id_usuario <> ?", "si", [$telefono, $idUsuario])) {
        $errores['telefono'] = 'Ese teléfono ya lo usa otra cuenta.';
    }
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores['correo'] = 'Escribe un correo válido.';
    } elseif (consultar_uno($conexion, "SELECT 1 FROM usuarios WHERE correo = ? AND id_usuario <> ?", "si", [$correo, $idUsuario])) {
        $errores['correo'] = 'Ese correo ya lo usa otra cuenta.';
    }
    // La contraseña es obligatoria al crear; al editar solo si se quiere cambiar
    // Misma regla que registro.php: 8 a 20 caracteres con mayúscula, minúscula, número y símbolo
    $regexContrasena = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_])[A-Za-z\d\W_]{8,20}$/';
    if (($esNuevo || $contrasena !== '') && !preg_match($regexContrasena, $contrasena)) {
        $errores['contrasena'] = 'Usa de 8 a 20 caracteres, con mayúscula, minúscula, número y símbolo.';
    }

    if (!$errores) {
        $maternoGuardar = $materno !== '' ? $materno : null;
        if ($esNuevo) {
            ejecutar($conexion, "INSERT INTO usuarios (rol, nombre, apellido_paterno, apellido_materno, telefono, correo, contrasena_hash)
                                 VALUES ('BASE', ?, ?, ?, ?, ?, ?)",
                "ssssss", [$nombre, $paterno, $maternoGuardar, $telefono, $correo, password_hash($contrasena, PASSWORD_BCRYPT)]);
            avisar('Creamos la cuenta de ' . $nombre . '. Dale su correo y su contraseña para que entre al panel de la base.');
        } else {
            // Al guardar también se quita el bloqueo por intentos fallidos
            ejecutar($conexion, "UPDATE usuarios SET nombre = ?, apellido_paterno = ?, apellido_materno = ?, telefono = ?, correo = ?, estado = ?,
                                        intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id_usuario = ?",
                "ssssssi", [$nombre, $paterno, $maternoGuardar, $telefono, $correo, $estado, $idUsuario]);
            if ($contrasena !== '') {
                ejecutar($conexion, "UPDATE usuarios SET contrasena_hash = ? WHERE id_usuario = ?", "si", [password_hash($contrasena, PASSWORD_BCRYPT), $idUsuario]);
            }
            if ($estado === 'SUSPENDIDO') {
                // Se cierra su sesión en la app y, si es conductor, deja de recibir viajes
                ejecutar($conexion, "DELETE FROM sesiones WHERE id_usuario = ?", "i", [$idUsuario]);
                ejecutar($conexion, "UPDATE conductores SET disponibilidad = 'DESCONECTADO' WHERE id_usuario = ?", "i", [$idUsuario]);
            }
            avisar('Guardamos la cuenta de ' . $nombre . '.');
        }
        header('Location: usuarios.php');
        exit;
    }
}

$valor = function ($campo) use ($usuario) {
    return $_POST[$campo] ?? ($usuario[$campo] ?? '');
};
$clase = function ($campo) use ($errores) {
    return isset($errores[$campo]) ? 'is-invalid' : '';
};

$tituloPagina = $esNuevo ? 'Nueva cuenta de la base' : 'Editar cuenta';
$paginaActiva = 'panel';
require __DIR__ . '/../includes/panel_arriba.php';
?>

<nav class="migas"><a href="usuarios.php"><?= icono('arrow_back') ?> Cuentas de la base</a></nav>
<h1 class="t-headline-s mb-1"><?= $esNuevo ? 'Nueva cuenta de la base' : 'Editar cuenta' ?></h1>
<p class="texto-secundario mb-4">
    <?= $esNuevo
        ? 'El personal de la base revisa a los conductores, atiende incidentes y cambia la tarifa.'
        : 'Cuenta de la base · registrada el ' . fecha_corta($usuario['fecha_registro']) ?>
</p>

<form method="POST" action="usuario.php" class="tarjeta js-validar" novalidate style="max-width: 760px;">
    <input type="hidden" name="id" value="<?= $idUsuario ?>">
    <div class="row g-3">
        <div class="col-12">
            <label for="nombre" class="form-label">Nombre(s)</label>
            <input type="text" id="nombre" name="nombre" class="form-control <?= $clase('nombre') ?>" required maxlength="50" value="<?= e($valor('nombre')) ?>" data-error="Escribe el nombre (solo letras).">
            <div class="invalid-feedback" id="error-nombre"><?= e($errores['nombre'] ?? '') ?></div>
        </div>
        <div class="col-sm-6">
            <label for="apellido_paterno" class="form-label">Apellido paterno</label>
            <input type="text" id="apellido_paterno" name="apellido_paterno" class="form-control <?= $clase('apellido_paterno') ?>" required maxlength="50" value="<?= e($valor('apellido_paterno')) ?>" data-error="Escribe el apellido paterno.">
            <div class="invalid-feedback" id="error-apellido_paterno"><?= e($errores['apellido_paterno'] ?? '') ?></div>
        </div>
        <div class="col-sm-6">
            <label for="apellido_materno" class="form-label">Apellido materno <span class="texto-secundario fw-normal">(opcional)</span></label>
            <input type="text" id="apellido_materno" name="apellido_materno" class="form-control <?= $clase('apellido_materno') ?>" maxlength="50" value="<?= e($valor('apellido_materno')) ?>">
            <div class="invalid-feedback" id="error-apellido_materno"><?= e($errores['apellido_materno'] ?? '') ?></div>
        </div>
        <div class="col-sm-6">
            <label for="telefono" class="form-label">Teléfono</label>
            <input type="tel" id="telefono" name="telefono" class="form-control <?= $clase('telefono') ?>" required pattern="\d{10}" maxlength="10" inputmode="numeric"
                   value="<?= e($valor('telefono')) ?>" data-error="El teléfono lleva 10 dígitos, sin espacios.">
            <div class="invalid-feedback" id="error-telefono"><?= e($errores['telefono'] ?? '') ?></div>
        </div>
        <div class="col-sm-6">
            <label for="correo" class="form-label">Correo electrónico</label>
            <input type="email" id="correo" name="correo" class="form-control <?= $clase('correo') ?>" required maxlength="100"
                   value="<?= e($valor('correo')) ?>" data-error="Escribe un correo válido.">
            <div class="form-text">Con él inicia sesión.</div>
            <div class="invalid-feedback" id="error-correo"><?= e($errores['correo'] ?? '') ?></div>
        </div>
        <div class="col-sm-6">
            <label for="contrasena" class="form-label"><?= $esNuevo ? 'Contraseña' : 'Nueva contraseña' ?> <?= $esNuevo ? '' : '<span class="texto-secundario fw-normal">(opcional)</span>' ?></label>
            <div class="campo-contrasena">
                <input type="password" id="contrasena" name="contrasena" class="form-control <?= $clase('contrasena') ?>" <?= $esNuevo ? 'required' : '' ?>
                       autocomplete="new-password" minlength="8" maxlength="20" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,20}" data-error="Usa de 8 a 20 caracteres, con mayúscula, minúscula, número y símbolo.">
                <button type="button" class="btn-ver" data-campo="contrasena" aria-label="Mostrar contraseña"><?= icono('visibility') ?></button>
            </div>
            <div class="form-text"><?= $esNuevo ? '8 a 20 caracteres, con mayúscula, minúscula, número y símbolo.' : 'Escríbela solo si la persona olvidó la suya.' ?></div>
            <div class="invalid-feedback d-block" id="error-contrasena"><?= e($errores['contrasena'] ?? '') ?></div>
        </div>
        <?php if (!$esNuevo): ?>
            <div class="col-sm-6">
                <label for="estado" class="form-label">Estado de la cuenta</label>
                <select id="estado" name="estado" class="form-select" <?= $esYo ? 'disabled' : '' ?>>
                    <option value="ACTIVO" <?= $valor('estado') === 'ACTIVO' ? 'selected' : '' ?>>Activa</option>
                    <option value="SUSPENDIDO" <?= $valor('estado') === 'SUSPENDIDO' ? 'selected' : '' ?>>Suspendida (no puede entrar)</option>
                </select>
                <div class="form-text"><?= $esYo ? 'No puedes suspender tu propia cuenta.' : 'Al suspenderla se cierra su sesión en la app.' ?></div>
            </div>
        <?php endif; ?>
    </div>
    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-principal"><?= $esNuevo ? 'Crear cuenta de la base' : 'Guardar cambios' ?></button>
        <a href="usuarios.php" class="btn btn-texto">Cancelar</a>
    </div>
</form>

<?php require __DIR__ . '/../includes/panel_abajo.php'; ?>
