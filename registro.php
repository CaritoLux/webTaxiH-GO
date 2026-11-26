<?php
session_start();
require_once __DIR__ . '/../taxihgo/taxihgo_api/config/conexion.php';

// Si ya hay sesión activa, redirigir según el rol
if (isset($_SESSION['usuario_id']) && isset($_SESSION['rol'])) {
    switch ($_SESSION['rol']) {
        case 'ADMINISTRADOR':
            header('Location: admin/dashboard.php');
            exit;
        case 'BASE':
            header('Location: base/operacion.php');
            exit;
        case 'CONDUCTOR':
            header('Location: conductor/panel.php');
            exit;
        case 'PASAJERO':
            header('Location: index.php');
            exit;
    }
}

$errorGeneral = '';
$errores = [
    'tipo_cuenta' => '',
    'nombre' => '',
    'apellido_paterno' => '',
    'apellido_materno' => '',
    'telefono' => '',
    'correo' => '',
    'contrasena' => '',
    'confirmar_contrasena' => '',
    'acepta_aviso' => ''
];

$valores = [
    'tipo_cuenta' => $_GET['rol'] ?? '', // Permite preseleccionar ?rol=CONDUCTOR desde el index
    'nombre' => '',
    'apellido_paterno' => '',
    'apellido_materno' => '',
    'telefono' => '',
    'correo' => '',
    'acepta_aviso' => false
];

if (!in_array($valores['tipo_cuenta'], ['PASAJERO', 'CONDUCTOR'], true)) {
    $valores['tipo_cuenta'] = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valores['tipo_cuenta'] = trim($_POST['tipo_cuenta'] ?? '');
    $valores['nombre'] = trim($_POST['nombre'] ?? '');
    $valores['apellido_paterno'] = trim($_POST['apellido_paterno'] ?? '');
    $valores['apellido_materno'] = trim($_POST['apellido_materno'] ?? '');
    $valores['telefono'] = trim($_POST['telefono'] ?? '');
    $valores['correo'] = strtolower(trim($_POST['correo'] ?? ''));
    $contrasena = $_POST['contrasena'] ?? '';
    $confirmarContrasena = $_POST['confirmar_contrasena'] ?? '';
    $valores['acepta_aviso'] = isset($_POST['acepta_aviso']);

    // 1. Validaciones por campo (errores en rojo debajo de cada input)
    if (!in_array($valores['tipo_cuenta'], ['PASAJERO', 'CONDUCTOR'], true)) {
        $errores['tipo_cuenta'] = 'Elige si te registras como Pasajero o Conductor.';
    }

    if ($valores['nombre'] === '') {
        $errores['nombre'] = 'Escribe tu nombre.';
    }

    if ($valores['apellido_paterno'] === '') {
        $errores['apellido_paterno'] = 'Escribe tu apellido paterno.';
    }

    if (!preg_match('/^\d{10}$/', $valores['telefono'])) {
        $errores['telefono'] = 'Escribe un teléfono válido de 10 dígitos.';
    }

    if ($valores['correo'] === '') {
        $errores['correo'] = 'Escribe tu correo electrónico.';
    } elseif (!filter_var($valores['correo'], FILTER_VALIDATE_EMAIL)) {
        $errores['correo'] = 'Escribe un correo electrónico válido.';
    }

    $regexPass = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_])[A-Za-z\d\W_]{8,20}$/';
    if ($contrasena === '') {
        $errores['contrasena'] = 'Escribe una contraseña.';
    } elseif (!preg_match($regexPass, $contrasena)) {
        $errores['contrasena'] = 'Debe tener de 8 a 20 caracteres, mayúscula, minúscula, número y símbolo.';
    }

    if ($confirmarContrasena === '') {
        $errores['confirmar_contrasena'] = 'Confirma tu contraseña.';
    } elseif ($contrasena !== $confirmarContrasena) {
        $errores['confirmar_contrasena'] = 'Las contraseñas no coinciden.';
    }

    if (!$valores['acepta_aviso']) {
        $errores['acepta_aviso'] = 'Debes aceptar el aviso de privacidad para continuar.';
    }

    // 2. Verificar duplicados y registrar si no hay errores
    $sinErrores = implode('', $errores) === '';

    if ($sinErrores) {
        // Revisar si el correo ya existe
        $sqlCorreo = "SELECT id_usuario FROM usuarios WHERE correo = ?";
        $stmtCorreo = mysqli_prepare($conexion, $sqlCorreo);
        mysqli_stmt_bind_param($stmtCorreo, "s", $valores['correo']);
        mysqli_stmt_execute($stmtCorreo);
        mysqli_stmt_store_result($stmtCorreo);
        if (mysqli_stmt_num_rows($stmtCorreo) > 0) {
            $errores['correo'] = 'Este correo electrónico ya está registrado.';
            $sinErrores = false;
        }
        mysqli_stmt_close($stmtCorreo);

        // Revisar si el teléfono ya existe
        if ($sinErrores) {
            $sqlTel = "SELECT id_usuario FROM usuarios WHERE telefono = ?";
            $stmtTel = mysqli_prepare($conexion, $sqlTel);
            mysqli_stmt_bind_param($stmtTel, "s", $valores['telefono']);
            mysqli_stmt_execute($stmtTel);
            mysqli_stmt_store_result($stmtTel);
            if (mysqli_stmt_num_rows($stmtTel) > 0) {
                $errores['telefono'] = 'Este número de teléfono ya está registrado.';
                $sinErrores = false;
            }
            mysqli_stmt_close($stmtTel);
        }
    }

    if ($sinErrores) {
        $hash = password_hash($contrasena, PASSWORD_BCRYPT);
        $rol = $valores['tipo_cuenta'];
        $estado = 'ACTIVO';

        $sqlIns = "INSERT INTO usuarios (nombre, apellido_paterno, apellido_materno, telefono, correo, contrasena_hash, rol, estado) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmtIns = mysqli_prepare($conexion, $sqlIns);

        if ($stmtIns) {
            mysqli_stmt_bind_param(
                $stmtIns,
                "ssssssss",
                $valores['nombre'],
                $valores['apellido_paterno'],
                $valores['apellido_materno'],
                $valores['telefono'],
                $valores['correo'],
                $hash,
                $rol,
                $estado
            );

            if (mysqli_stmt_execute($stmtIns)) {
                $nuevoId = mysqli_insert_id($conexion);
                mysqli_stmt_close($stmtIns);

                // Iniciar sesión automáticamente y mandar a su ventana correspondiente
                $_SESSION['usuario_id'] = $nuevoId;
                $_SESSION['nombre'] = $valores['nombre'];
                $_SESSION['rol'] = $rol;

                if ($rol === 'CONDUCTOR') {
                    header('Location: conductor/panel.php');
                } else {
                    header('Location: index.php');
                }
                exit;
            } else {
                $errorGeneral = 'No se pudo completar el registro. Intenta de nuevo.';
                mysqli_stmt_close($stmtIns);
            }
        } else {
            $errorGeneral = 'Error al conectar con el servidor.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta - TaxiH-GO!</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="assets/css/auth.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- Encabezado (mismo que index.php y login.php) -->
    <nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="index.php">
                <img src="../imagenes/logo.png" alt="TaxiH-GO!" width="42">
                <span>TaxiH-GO!</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuRegistro">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="menuRegistro">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
                    <li class="nav-item"><a class="nav-link text-dark fw-medium" href="index.php#pasajeros">Pasajeros</a></li>
                    <li class="nav-item"><a class="nav-link text-dark fw-medium" href="index.php#taxistas">Taxistas</a></li>
                    <li class="nav-item"><a class="nav-link text-dark fw-medium" href="index.php#tarifa">Tarifa</a></li>
                    <li class="nav-item"><a class="nav-link text-dark fw-medium" href="index.php#contacto">Contacto</a></li>
                    <li class="nav-item ms-lg-2">
                        <a href="login.php" class="btn btn-outline-dark rounded-pill px-4 fw-semibold">Iniciar sesión</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contenido principal -->
    <main class="flex-grow-1 d-flex align-items-center py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-11 col-sm-10 col-md-8 col-lg-6 col-xl-5">
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-body p-4 p-md-5">

                            <!-- Flecha para regresar al index -->
                            <div class="mb-2">
                                <a href="index.php" class="btn-regresar" title="Volver al inicio" aria-label="Volver al inicio">
                                    <i class="bi bi-arrow-left fs-4"></i>
                                </a>
                            </div>

                            <!-- Logo centrado -->
                            <div class="text-center mb-3">
                                <img src="../imagenes/logo.png" alt="TaxiH-GO!" width="95">
                            </div>

                            <h3 class="fw-bold mb-1">Crear cuenta</h3>
                            <p class="text-muted small mb-4">Elige cómo vas a usar TaxiH-GO! y completa tus datos.</p>

                            <?php if ($errorGeneral !== ''): ?>
                                <div class="alert alert-danger py-2 small rounded-3"><?= htmlspecialchars($errorGeneral) ?></div>
                            <?php endif; ?>

                            <form id="formRegistro" method="POST" action="" novalidate>
                                <!-- Selector de Rol -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small text-secondary">Tipo de cuenta</label>
                                    <input type="hidden" id="tipo_cuenta" name="tipo_cuenta" value="<?= htmlspecialchars($valores['tipo_cuenta']) ?>">
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <div id="card-pasajero" class="rol-card text-center <?= $valores['tipo_cuenta'] === 'PASAJERO' ? 'seleccionado' : '' ?> <?= $errores['tipo_cuenta'] !== '' ? 'is-invalid' : '' ?>">
                                                <i class="bi bi-person fs-4 d-block mb-1"></i>
                                                <div class="fw-bold small">Pasajero</div>
                                                <div class="text-muted" style="font-size: 0.75rem;">Pedir taxis</div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div id="card-conductor" class="rol-card text-center <?= $valores['tipo_cuenta'] === 'CONDUCTOR' ? 'seleccionado' : '' ?> <?= $errores['tipo_cuenta'] !== '' ? 'is-invalid' : '' ?>">
                                                <i class="bi bi-car-front fs-4 d-block mb-1"></i>
                                                <div class="fw-bold small">Conductor</div>
                                                <div class="text-muted" style="font-size: 0.75rem;">Recibir viajes</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="error-tipo_cuenta" class="texto-error-campo <?= $errores['tipo_cuenta'] === '' ? 'd-none' : '' ?>">
                                        <?= htmlspecialchars($errores['tipo_cuenta']) ?>
                                    </div>
                                </div>

                                <!-- Nombre -->
                                <div class="mb-3">
                                    <label for="nombre" class="form-label fw-semibold small text-secondary">Nombre(s)</label>
                                    <input
                                        type="text"
                                        id="nombre"
                                        name="nombre"
                                        class="form-control campo-input <?= $errores['nombre'] !== '' ? 'is-invalid' : '' ?>"
                                        placeholder="Ej. Juan Carlos"
                                        value="<?= htmlspecialchars($valores['nombre']) ?>"
                                    >
                                    <div id="error-nombre" class="texto-error-campo <?= $errores['nombre'] === '' ? 'd-none' : '' ?>">
                                        <?= htmlspecialchars($errores['nombre']) ?>
                                    </div>
                                </div>

                                <!-- Apellidos -->
                                <div class="row g-3 mb-3">
                                    <div class="col-sm-6">
                                        <label for="apellido_paterno" class="form-label fw-semibold small text-secondary">Apellido paterno</label>
                                        <input
                                            type="text"
                                            id="apellido_paterno"
                                            name="apellido_paterno"
                                            class="form-control campo-input <?= $errores['apellido_paterno'] !== '' ? 'is-invalid' : '' ?>"
                                            placeholder="Ej. Hernández"
                                            value="<?= htmlspecialchars($valores['apellido_paterno']) ?>"
                                        >
                                        <div id="error-apellido_paterno" class="texto-error-campo <?= $errores['apellido_paterno'] === '' ? 'd-none' : '' ?>">
                                            <?= htmlspecialchars($errores['apellido_paterno']) ?>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="apellido_materno" class="form-label fw-semibold small text-secondary">Apellido materno <span class="fw-normal">(opcional)</span></label>
                                        <input
                                            type="text"
                                            id="apellido_materno"
                                            name="apellido_materno"
                                            class="form-control campo-input"
                                            placeholder="Ej. López"
                                            value="<?= htmlspecialchars($valores['apellido_materno']) ?>"
                                        >
                                    </div>
                                </div>

                                <!-- Teléfono -->
                                <div class="mb-3">
                                    <label for="telefono" class="form-label fw-semibold small text-secondary">Teléfono celular (10 dígitos)</label>
                                    <input
                                        type="tel"
                                        id="telefono"
                                        name="telefono"
                                        maxlength="10"
                                        inputmode="numeric"
                                        class="form-control campo-input <?= $errores['telefono'] !== '' ? 'is-invalid' : '' ?>"
                                        placeholder="7710000000"
                                        value="<?= htmlspecialchars($valores['telefono']) ?>"
                                    >
                                    <div id="error-telefono" class="texto-error-campo <?= $errores['telefono'] === '' ? 'd-none' : '' ?>">
                                        <?= htmlspecialchars($errores['telefono']) ?>
                                    </div>
                                </div>

                                <!-- Correo -->
                                <div class="mb-3">
                                    <label for="correo" class="form-label fw-semibold small text-secondary">Correo electrónico</label>
                                    <input
                                        type="email"
                                        id="correo"
                                        name="correo"
                                        class="form-control campo-input <?= $errores['correo'] !== '' ? 'is-invalid' : '' ?>"
                                        placeholder="correo@ejemplo.com"
                                        value="<?= htmlspecialchars($valores['correo']) ?>"
                                    >
                                    <div id="error-correo" class="texto-error-campo <?= $errores['correo'] === '' ? 'd-none' : '' ?>">
                                        <?= htmlspecialchars($errores['correo']) ?>
                                    </div>
                                </div>

                                <!-- Contraseña -->
                                <div class="mb-3">
                                    <label for="contrasena" class="form-label fw-semibold small text-secondary">Contraseña</label>
                                    <div class="position-relative">
                                        <input
                                            type="password"
                                            id="contrasena"
                                            name="contrasena"
                                            class="form-control campo-input pe-5 <?= $errores['contrasena'] !== '' ? 'is-invalid' : '' ?>"
                                            placeholder="••••••••"
                                        >
                                        <button type="button" id="btnTogglePass1" class="btn-ojito" aria-label="Mostrar u ocultar contraseña">
                                            <i id="iconoOjito1" class="bi bi-eye fs-5"></i>
                                        </button>
                                    </div>
                                    <div class="form-text" style="font-size: 0.78rem;">
                                        De 8 a 20 caracteres, con mayúscula, minúscula, número y símbolo.
                                    </div>
                                    <div id="error-contrasena" class="texto-error-campo <?= $errores['contrasena'] === '' ? 'd-none' : '' ?>">
                                        <?= htmlspecialchars($errores['contrasena']) ?>
                                    </div>
                                </div>

                                <!-- Confirmar contraseña -->
                                <div class="mb-3">
                                    <label for="confirmar_contrasena" class="form-label fw-semibold small text-secondary">Confirmar contraseña</label>
                                    <div class="position-relative">
                                        <input
                                            type="password"
                                            id="confirmar_contrasena"
                                            name="confirmar_contrasena"
                                            class="form-control campo-input pe-5 <?= $errores['confirmar_contrasena'] !== '' ? 'is-invalid' : '' ?>"
                                            placeholder="••••••••"
                                        >
                                        <button type="button" id="btnTogglePass2" class="btn-ojito" aria-label="Mostrar u ocultar confirmación">
                                            <i id="iconoOjito2" class="bi bi-eye fs-5"></i>
                                        </button>
                                    </div>
                                    <div id="error-confirmar_contrasena" class="texto-error-campo <?= $errores['confirmar_contrasena'] === '' ? 'd-none' : '' ?>">
                                        <?= htmlspecialchars($errores['confirmar_contrasena']) ?>
                                    </div>
                                </div>

                                <!-- Aviso de privacidad -->
                                <div class="mb-4">
                                    <div class="form-check">
                                        <input
                                            class="form-check-input <?= $errores['acepta_aviso'] !== '' ? 'is-invalid' : '' ?>"
                                            type="checkbox"
                                            id="acepta_aviso"
                                            name="acepta_aviso"
                                            <?= $valores['acepta_aviso'] ? 'checked' : '' ?>
                                        >
                                        <label class="form-check-label small text-secondary" for="acepta_aviso">
                                            He leído y acepto el
                                            <a href="#" class="text-dark fw-semibold text-decoration-underline" data-bs-toggle="modal" data-bs-target="#modalPrivacidad">
                                                aviso de privacidad
                                            </a>.
                                        </label>
                                    </div>
                                    <div id="error-acepta_aviso" class="texto-error-campo <?= $errores['acepta_aviso'] === '' ? 'd-none' : '' ?>">
                                        <?= htmlspecialchars($errores['acepta_aviso']) ?>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-amarillo w-100 rounded-pill py-2 mb-3">
                                    Crear cuenta
                                </button>

                                <div class="text-center small text-muted">
                                    ¿Ya tienes cuenta?
                                    <a href="login.php" class="text-dark fw-bold text-decoration-underline">Iniciar sesión</a>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Aviso de Privacidad -->
    <div class="modal fade" id="modalPrivacidad" tabindex="-1" aria-labelledby="modalPrivacidadLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold" id="modalPrivacidadLabel">Aviso de privacidad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body small text-secondary" style="line-height: 1.6;">
                    <p><strong>TaxiH-GO!</strong> (la central), con domicilio en Huejutla de Reyes, Hidalgo, es responsable del tratamiento de tus datos personales.</p>
                    <p><strong>Datos que recabamos:</strong> nombre, apellidos, teléfono celular, correo electrónico y, durante un viaje, tu ubicación. A los conductores además les pedimos los datos de su taxi y la fotografía de su licencia, tarjetón, INE, tarjeta de circulación, del taxi y de su rostro.</p>
                    <p><strong>Finalidades:</strong> crear tu cuenta, conectar a pasajeros con taxis registrados, mostrar a cada parte los datos de la otra mientras dura el viaje, atender incidentes y emergencias, y generar estadísticas de operación sin datos personales.</p>
                    <p class="mb-0">Tu teléfono solo lo ve la otra persona durante un viaje activo. Los documentos de los conductores solo los ve la central. No vendemos ni compartimos tus datos con terceros, salvo que una autoridad competente los requiera.</p>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-amarillo rounded-pill px-4" data-bs-dismiss="modal">Entendido</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Pie de página) -->
    <footer class="border-top py-4 bg-white mt-auto">
        <div class="container d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 small text-muted">
            <span>TaxiH-GO! · Central de taxis por aplicación de Huejutla de Reyes, Hidalgo.</span>
            <a href="#" class="text-muted text-decoration-underline" data-bs-toggle="modal" data-bs-target="#modalPrivacidad">Aviso de privacidad</a>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const formRegistro = document.getElementById('formRegistro');
        const inputTipoCuenta = document.getElementById('tipo_cuenta');
        const cardPasajero = document.getElementById('card-pasajero');
        const cardConductor = document.getElementById('card-conductor');
        const errorTipoCuenta = document.getElementById('error-tipo_cuenta');

        const inputNombre = document.getElementById('nombre');
        const inputPaterno = document.getElementById('apellido_paterno');
        const inputTelefono = document.getElementById('telefono');
        const inputCorreo = document.getElementById('correo');
        const inputPass = document.getElementById('contrasena');
        const inputConfirmar = document.getElementById('confirmar_contrasena');
        const checkAviso = document.getElementById('acepta_aviso');

        const errorNombre = document.getElementById('error-nombre');
        const errorPaterno = document.getElementById('error-apellido_paterno');
        const errorTelefono = document.getElementById('error-telefono');
        const errorCorreo = document.getElementById('error-correo');
        const errorPass = document.getElementById('error-contrasena');
        const errorConfirmar = document.getElementById('error-confirmar_contrasena');
        const errorAviso = document.getElementById('error-acepta_aviso');

        function mostrarErrorCampo(input, contenedorError, mensaje) {
            if (input) input.classList.add('is-invalid');
            contenedorError.textContent = mensaje;
            contenedorError.classList.remove('d-none');
        }

        function limpiarErrorCampo(input, contenedorError) {
            if (input) input.classList.remove('is-invalid');
            contenedorError.textContent = '';
            contenedorError.classList.add('d-none');
        }

        // Selección de Pasajero / Conductor
        function seleccionarRol(rol) {
            inputTipoCuenta.value = rol;
            cardPasajero.classList.toggle('seleccionado', rol === 'PASAJERO');
            cardConductor.classList.toggle('seleccionado', rol === 'CONDUCTOR');
            cardPasajero.classList.remove('is-invalid');
            cardConductor.classList.remove('is-invalid');
            limpiarErrorCampo(null, errorTipoCuenta);
        }

        cardPasajero.addEventListener('click', () => seleccionarRol('PASAJERO'));
        cardConductor.addEventListener('click', () => seleccionarRol('CONDUCTOR'));

        // Solo permitir números en el teléfono
        inputTelefono.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 10);
            limpiarErrorCampo(inputTelefono, errorTelefono);
        });

        // Limpiar bordes rojos al escribir
        inputNombre.addEventListener('input', () => limpiarErrorCampo(inputNombre, errorNombre));
        inputPaterno.addEventListener('input', () => limpiarErrorCampo(inputPaterno, errorPaterno));
        inputCorreo.addEventListener('input', () => limpiarErrorCampo(inputCorreo, errorCorreo));
        inputPass.addEventListener('input', () => limpiarErrorCampo(inputPass, errorPass));
        inputConfirmar.addEventListener('input', () => limpiarErrorCampo(inputConfirmar, errorConfirmar));
        checkAviso.addEventListener('change', () => limpiarErrorCampo(checkAviso, errorAviso));

        // Validación al enviar el formulario
        formRegistro.addEventListener('submit', function (e) {
            let valido = true;
            const regexPass = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_])[A-Za-z\d\W_]{8,20}$/;

            limpiarErrorCampo(null, errorTipoCuenta);
            cardPasajero.classList.remove('is-invalid');
            cardConductor.classList.remove('is-invalid');
            limpiarErrorCampo(inputNombre, errorNombre);
            limpiarErrorCampo(inputPaterno, errorPaterno);
            limpiarErrorCampo(inputTelefono, errorTelefono);
            limpiarErrorCampo(inputCorreo, errorCorreo);
            limpiarErrorCampo(inputPass, errorPass);
            limpiarErrorCampo(inputConfirmar, errorConfirmar);
            limpiarErrorCampo(checkAviso, errorAviso);

            if (inputTipoCuenta.value !== 'PASAJERO' && inputTipoCuenta.value !== 'CONDUCTOR') {
                cardPasajero.classList.add('is-invalid');
                cardConductor.classList.add('is-invalid');
                mostrarErrorCampo(null, errorTipoCuenta, 'Elige si te registras como Pasajero o Conductor.');
                valido = false;
            }

            if (inputNombre.value.trim() === '') {
                mostrarErrorCampo(inputNombre, errorNombre, 'Escribe tu nombre.');
                valido = false;
            }

            if (inputPaterno.value.trim() === '') {
                mostrarErrorCampo(inputPaterno, errorPaterno, 'Escribe tu apellido paterno.');
                valido = false;
            }

            if (!/^\d{10}$/.test(inputTelefono.value.trim())) {
                mostrarErrorCampo(inputTelefono, errorTelefono, 'Escribe un teléfono válido de 10 dígitos.');
                valido = false;
            }

            const correoVal = inputCorreo.value.trim();
            if (correoVal === '') {
                mostrarErrorCampo(inputCorreo, errorCorreo, 'Escribe tu correo electrónico.');
                valido = false;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correoVal)) {
                mostrarErrorCampo(inputCorreo, errorCorreo, 'Escribe un correo electrónico válido.');
                valido = false;
            }

            if (inputPass.value === '') {
                mostrarErrorCampo(inputPass, errorPass, 'Escribe una contraseña.');
                valido = false;
            } else if (!regexPass.test(inputPass.value)) {
                mostrarErrorCampo(inputPass, errorPass, 'Debe tener de 8 a 20 caracteres, mayúscula, minúscula, número y símbolo.');
                valido = false;
            }

            if (inputConfirmar.value === '') {
                mostrarErrorCampo(inputConfirmar, errorConfirmar, 'Confirma tu contraseña.');
                valido = false;
            } else if (inputPass.value !== inputConfirmar.value) {
                mostrarErrorCampo(inputConfirmar, errorConfirmar, 'Las contraseñas no coinciden.');
                valido = false;
            }

            if (!checkAviso.checked) {
                mostrarErrorCampo(checkAviso, errorAviso, 'Debes aceptar el aviso de privacidad para continuar.');
                valido = false;
            }

            if (!valido) {
                e.preventDefault();
            }
        });

        // Ojitos para mostrar/ocultar ambas contraseñas
        function configurarOjito(botonId, inputId, iconoId) {
            const btn = document.getElementById(botonId);
            const inp = document.getElementById(inputId);
            const ico = document.getElementById(iconoId);

            btn.addEventListener('click', function () {
                const esPassword = inp.type === 'password';
                inp.type = esPassword ? 'text' : 'password';
                ico.classList.toggle('bi-eye', !esPassword);
                ico.classList.toggle('bi-eye-slash', esPassword);
            });
        }

        configurarOjito('btnTogglePass1', 'contrasena', 'iconoOjito1');
        configurarOjito('btnTogglePass2', 'confirmar_contrasena', 'iconoOjito2');
    </script>
</body>
</html>