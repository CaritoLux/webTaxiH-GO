<?php
session_start();
require_once __DIR__ . '/../taxihgo_api/config/conexion.php';

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
$mensajeExito = '';
$errores = [
    'correo' => '',
    'contrasena' => ''
];

if (isset($_GET['error'])) {
    if ($_GET['error'] === 'acceso_denegado') {
        $errorGeneral = 'Inicia sesión para acceder a esta área.';
    } elseif ($_GET['error'] === 'rol_invalido') {
        $errorGeneral = 'No tienes permiso para acceder a esa sección.';
    }
}

if (isset($_GET['recuperacion']) && $_GET['recuperacion'] === 'ok') {
    $mensajeExito = 'Contraseña actualizada. Ya puedes iniciar sesión.';
}

$correoIngresado = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correoIngresado = trim($_POST['correo'] ?? '');
    $contrasena = trim($_POST['contrasena'] ?? '');

    // 1. Validación por campo (errores debajo de cada input)
    if ($correoIngresado === '') {
        $errores['correo'] = 'Escribe tu correo electrónico.';
    } elseif (!filter_var($correoIngresado, FILTER_VALIDATE_EMAIL)) {
        $errores['correo'] = 'Escribe un correo electrónico válido.';
    }

    if ($contrasena === '') {
        $errores['contrasena'] = 'Escribe tu contraseña.';
    }

    // 2. Consultar BD solo si ambos campos vienen llenos
    if ($errores['correo'] === '' && $errores['contrasena'] === '') {
        $sql = "SELECT id_usuario, nombre, rol, contrasena_hash, estado, intentos_fallidos, bloqueado_hasta 
                FROM usuarios 
                WHERE correo = ?";
        $stmt = mysqli_prepare($conexion, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $correoIngresado);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $usuario = mysqli_fetch_assoc($res);
            mysqli_stmt_close($stmt);

            if ($usuario) {
                // Revisar si la cuenta está bloqueada por tiempo
                $bloqueado = false;
                if (!empty($usuario['bloqueado_hasta'])) {
                    $fechaBloqueo = strtotime($usuario['bloqueado_hasta']);
                    if ($fechaBloqueo > time()) {
                        $bloqueado = true;
                        $faltan = ceil(($fechaBloqueo - time()) / 60);
                        $errorGeneral = "Demasiados intentos. Cuenta bloqueada. Intenta en $faltan minuto(s).";
                    }
                }

                if (!$bloqueado) {
                    // Revisar si la cuenta está suspendida o en revisión
                    if ($usuario['estado'] !== 'ACTIVO') {
                        $errorGeneral = 'Tu cuenta está suspendida o en revisión. Comunícate con la base.';
                    }
                    // Validar contraseña
                    elseif (password_verify($contrasena, $usuario['contrasena_hash'])) {
                        // Resetear intentos fallidos
                        $sqlReset = "UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id_usuario = ?";
                        $stmtReset = mysqli_prepare($conexion, $sqlReset);
                        mysqli_stmt_bind_param($stmtReset, "i", $usuario['id_usuario']);
                        mysqli_stmt_execute($stmtReset);
                        mysqli_stmt_close($stmtReset);

                        // Guardar sesión
                        $_SESSION['usuario_id'] = $usuario['id_usuario'];
                        $_SESSION['nombre'] = $usuario['nombre'];
                        $_SESSION['rol'] = $usuario['rol'];

                        // Redirigir según el rol
                        switch ($usuario['rol']) {
                            case 'ADMINISTRADOR':
                                header('Location: admin/dashboard.php');
                                break;
                            case 'BASE':
                                header('Location: base/operacion.php');
                                break;
                            case 'CONDUCTOR':
                                header('Location: conductor/panel.php');
                                break;
                            case 'PASAJERO':
                                header('Location: index.php');
                                break;
                            default:
                                $errorGeneral = 'Rol desconocido.';
                                session_destroy();
                        }
                        exit;
                    }
                    // Contraseña incorrecta: sumar intento y bloquear a los 5
                    else {
                        $intentos = (int)$usuario['intentos_fallidos'] + 1;

                        if ($intentos >= 5) {
                            $sqlLock = "UPDATE usuarios SET intentos_fallidos = ?, bloqueado_hasta = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id_usuario = ?";
                            $stmtLock = mysqli_prepare($conexion, $sqlLock);
                            mysqli_stmt_bind_param($stmtLock, "ii", $intentos, $usuario['id_usuario']);
                            mysqli_stmt_execute($stmtLock);
                            mysqli_stmt_close($stmtLock);

                            $errorGeneral = 'Agotaste tus 5 intentos. Tu cuenta ha sido bloqueada por 15 minutos.';
                        } else {
                            $sqlUpd = "UPDATE usuarios SET intentos_fallidos = ? WHERE id_usuario = ?";
                            $stmtUpd = mysqli_prepare($conexion, $sqlUpd);
                            mysqli_stmt_bind_param($stmtUpd, "ii", $intentos, $usuario['id_usuario']);
                            mysqli_stmt_execute($stmtUpd);
                            mysqli_stmt_close($stmtUpd);

                            $restantes = 5 - $intentos;
                            $errorGeneral = "Correo o contraseña incorrectos. Te quedan $restantes intento(s).";
                        }
                    }
                }
            } else {
                $errorGeneral = 'Correo o contraseña incorrectos.';
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
    <title>Iniciar Sesión - TaxiH-GO!</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="assets/css/auth.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- Encabezado (mismo que index.php) -->
    <nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="index.php">
                <img src="../imagenes/logo.png" alt="TaxiH-GO!" width="42">
                <span>TaxiH-GO!</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuLogin">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="menuLogin">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
                    <li class="nav-item"><a class="nav-link text-dark fw-medium" href="index.php#pasajeros">Pasajeros</a></li>
                    <li class="nav-item"><a class="nav-link text-dark fw-medium" href="index.php#taxistas">Taxistas</a></li>
                    <li class="nav-item"><a class="nav-link text-dark fw-medium" href="index.php#tarifa">Tarifa</a></li>
                    <li class="nav-item"><a class="nav-link text-dark fw-medium" href="index.php#contacto">Contacto</a></li>
                    <li class="nav-item ms-lg-2">
                        <a href="registro.php" class="btn btn-outline-dark rounded-pill px-4 fw-semibold">Crear cuenta</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contenido principal -->
    <main class="flex-grow-1 d-flex align-items-center py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-11 col-sm-9 col-md-6 col-lg-5 col-xl-4">
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

                            <h3 class="fw-bold mb-1">Iniciar sesión</h3>
                            <p class="text-muted small mb-4">Pasajeros, conductores, base y administración.</p>

                            <!-- Alertas generales (intentos fallidos, cuenta bloqueada o suspendida) -->
                            <?php if ($errorGeneral !== ''): ?>
                                <div class="alert alert-danger py-2 small rounded-3"><?= htmlspecialchars($errorGeneral) ?></div>
                            <?php endif; ?>

                            <?php if ($mensajeExito !== ''): ?>
                                <div class="alert alert-success py-2 small rounded-3"><?= htmlspecialchars($mensajeExito) ?></div>
                            <?php endif; ?>

                            <form id="formLogin" method="POST" action="" novalidate>
                                <!-- Correo electrónico -->
                                <div class="mb-3">
                                    <label for="correo" class="form-label fw-semibold small text-secondary">Correo electrónico</label>
                                    <input
                                        type="email"
                                        id="correo"
                                        name="correo"
                                        class="form-control campo-input <?= $errores['correo'] !== '' ? 'is-invalid' : '' ?>"
                                        placeholder="correo@ejemplo.com"
                                        value="<?= htmlspecialchars($correoIngresado) ?>"
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
                                        <button type="button" id="btnTogglePass" class="btn-ojito" aria-label="Mostrar u ocultar contraseña">
                                            <i id="iconoOjito" class="bi bi-eye fs-5"></i>
                                        </button>
                                    </div>
                                    <div id="error-contrasena" class="texto-error-campo <?= $errores['contrasena'] === '' ? 'd-none' : '' ?>">
                                        <?= htmlspecialchars($errores['contrasena']) ?>
                                    </div>
                                </div>

                                <div class="mb-4 text-end">
                                    <a href="recuperar.php" class="text-dark text-decoration-none small fw-semibold">¿Olvidaste tu contraseña?</a>
                                </div>

                                <button type="submit" class="btn btn-amarillo w-100 rounded-pill py-2 mb-3">
                                    Iniciar sesión
                                </button>

                                <div class="text-center small text-muted">
                                    ¿No tienes cuenta?
                                    <a href="registro.php" class="text-dark fw-bold text-decoration-underline">Crear cuenta</a>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Pie de página (mismo que index.php) -->
    <footer class="border-top py-4 bg-white mt-auto">
        <div class="container d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 small text-muted">
            <span>TaxiH-GO! · Central de taxis por aplicación de Huejutla de Reyes, Hidalgo.</span>
            <a href="#" class="text-muted text-decoration-underline">Aviso de privacidad</a>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const formLogin = document.getElementById('formLogin');
        const inputCorreo = document.getElementById('correo');
        const inputContrasena = document.getElementById('contrasena');
        const errorCorreo = document.getElementById('error-correo');
        const errorContrasena = document.getElementById('error-contrasena');
        const btnTogglePass = document.getElementById('btnTogglePass');
        const iconoOjito = document.getElementById('iconoOjito');

        function mostrarErrorCampo(input, contenedorError, mensaje) {
            input.classList.add('is-invalid');
            contenedorError.textContent = mensaje;
            contenedorError.classList.remove('d-none');
        }

        function limpiarErrorCampo(input, contenedorError) {
            input.classList.remove('is-invalid');
            contenedorError.textContent = '';
            contenedorError.classList.add('d-none');
        }

        // Validación al enviar el formulario (marca borde rojo y texto debajo sin recargar)
        formLogin.addEventListener('submit', function (e) {
            let valido = true;
            const correoVal = inputCorreo.value.trim();
            const passVal = inputContrasena.value.trim();

            limpiarErrorCampo(inputCorreo, errorCorreo);
            limpiarErrorCampo(inputContrasena, errorContrasena);

            if (correoVal === '') {
                mostrarErrorCampo(inputCorreo, errorCorreo, 'Escribe tu correo electrónico.');
                valido = false;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correoVal)) {
                mostrarErrorCampo(inputCorreo, errorCorreo, 'Escribe un correo electrónico válido.');
                valido = false;
            }

            if (passVal === '') {
                mostrarErrorCampo(inputContrasena, errorContrasena, 'Escribe tu contraseña.');
                valido = false;
            }

            if (!valido) {
                e.preventDefault();
            }
        });

        // Quitar el borde rojo automáticamente en cuanto el usuario empieza a escribir
        inputCorreo.addEventListener('input', () => limpiarErrorCampo(inputCorreo, errorCorreo));
        inputContrasena.addEventListener('input', () => limpiarErrorCampo(inputContrasena, errorContrasena));

        // Mostrar / ocultar contraseña con el ícono del ojo
        btnTogglePass.addEventListener('click', function () {
            const esPassword = inputContrasena.type === 'password';
            inputContrasena.type = esPassword ? 'text' : 'password';
            iconoOjito.classList.toggle('bi-eye', !esPassword);
            iconoOjito.classList.toggle('bi-eye-slash', esPassword);
        });
    </script>
</body>
</html>