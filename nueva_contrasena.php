<?php
session_start();
require_once __DIR__ . '/../taxihgo_api/config/conexion.php';

if (!isset($_SESSION['correo_recuperacion'])) {
    header('Location: recuperar.php');
    exit;
}

$correo = $_SESSION['correo_recuperacion'];
$errorGeneral = '';
$errores = ['codigo' => '', 'contrasena' => '', 'confirmar_contrasena' => ''];
$codigoIngresado = '';

// Fase 1: Validar código | Fase 2: Cambiar contraseña
$fase = isset($_SESSION['codigo_aprobado']) ? 2 : 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- PROCESAR FASE 1: VERIFICAR CÓDIGO ---
    if (isset($_POST['btn_verificar']) && $fase === 1) {
        $codigoIngresado = trim($_POST['codigo'] ?? '');

        if ($codigoIngresado === '') {
            $errores['codigo'] = 'Escribe el código de 6 dígitos.';
        } elseif (!preg_match('/^\d{6}$/', $codigoIngresado)) {
            $errores['codigo'] = 'El código debe tener exactamente 6 números.';
        } else {
            $sqlUser = "SELECT id_usuario FROM usuarios WHERE correo = ?";
            $stmtUser = mysqli_prepare($conexion, $sqlUser);
            mysqli_stmt_bind_param($stmtUser, "s", $correo);
            mysqli_stmt_execute($stmtUser);
            $resUser = mysqli_stmt_get_result($stmtUser);
            $usuario = mysqli_fetch_assoc($resUser);
            mysqli_stmt_close($stmtUser);

            if (!$usuario) {
                $errorGeneral = 'Usuario no encontrado.';
            } else {
                $id_usuario = (int)$usuario['id_usuario'];

                $sqlRec = "SELECT id_recuperacion, codigo_hash, intentos, (vence > NOW()) AS vigente 
                           FROM recuperaciones_contrasena 
                           WHERE id_usuario = ? AND usado = 0 
                           ORDER BY id_recuperacion DESC LIMIT 1";
                $stmtRec = mysqli_prepare($conexion, $sqlRec);
                mysqli_stmt_bind_param($stmtRec, "i", $id_usuario);
                mysqli_stmt_execute($stmtRec);
                $resRec = mysqli_stmt_get_result($stmtRec);
                $rec = mysqli_fetch_assoc($resRec);
                mysqli_stmt_close($stmtRec);

                if (!$rec) {
                    $errorGeneral = 'No hay un código activo. Solicita uno nuevo.';
                } elseif (!$rec['vigente']) {
                    $errorGeneral = 'El código ha expirado. Solicita uno nuevo.';
                } elseif ((int)$rec['intentos'] >= 5) {
                    $errorGeneral = 'Superaste el límite de intentos. Solicita un código nuevo.';
                } elseif (!password_verify($codigoIngresado, $rec['codigo_hash'])) {
                    $nuevosIntentos = (int)$rec['intentos'] + 1;
                    $marcarUsado = ($nuevosIntentos >= 5) ? 1 : 0;

                    $sqlInt = "UPDATE recuperaciones_contrasena SET intentos = ?, usado = ? WHERE id_recuperacion = ?";
                    $stmtInt = mysqli_prepare($conexion, $sqlInt);
                    mysqli_stmt_bind_param($stmtInt, "iii", $nuevosIntentos, $marcarUsado, $rec['id_recuperacion']);
                    mysqli_stmt_execute($stmtInt);
                    mysqli_stmt_close($stmtInt);

                    $errores['codigo'] = ($marcarUsado === 1) 
                        ? 'Agotaste los 5 intentos, solicita otro código.' 
                        : 'El código es incorrecto.';
                } else {
                    // Código correcto: pasamos a Fase 2 sin quemar el código aún
                    $_SESSION['codigo_aprobado'] = true;
                    $_SESSION['id_recuperacion'] = $rec['id_recuperacion'];
                    $_SESSION['id_usuario_recuperacion'] = $id_usuario;
                    $fase = 2;
                }
            }
        }
    }

    // --- PROCESAR FASE 2: GUARDAR NUEVA CONTRASEÑA ---
    elseif (isset($_POST['btn_cambiar']) && $fase === 2) {
        $contrasena = $_POST['contrasena'] ?? '';
        $confirmar = $_POST['confirmar_contrasena'] ?? '';
        $regexContrasena = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_])[A-Za-z\d\W_]{8,20}$/';

        if ($contrasena === '') {
            $errores['contrasena'] = 'Escribe tu nueva contraseña.';
        } elseif (!preg_match($regexContrasena, $contrasena)) {
            $errores['contrasena'] = 'Debe tener de 8 a 20 caracteres, mayúscula, minúscula, número y símbolo.';
        }

        if ($confirmar === '') {
            $errores['confirmar_contrasena'] = 'Confirma tu nueva contraseña.';
        } elseif ($contrasena !== $confirmar) {
            $errores['confirmar_contrasena'] = 'Las contraseñas no coinciden.';
        }

        if ($errores['contrasena'] === '' && $errores['confirmar_contrasena'] === '') {
            $id_recuperacion = $_SESSION['id_recuperacion'];
            $id_usuario = $_SESSION['id_usuario_recuperacion'];

            // Quemar el código
            $sqlUsado = "UPDATE recuperaciones_contrasena SET usado = 1 WHERE id_recuperacion = ?";
            $stmtUsado = mysqli_prepare($conexion, $sqlUsado);
            mysqli_stmt_bind_param($stmtUsado, "i", $id_recuperacion);
            mysqli_stmt_execute($stmtUsado);
            mysqli_stmt_close($stmtUsado);

            // Actualizar contraseña y resetear bloqueos
            $nuevoHash = password_hash($contrasena, PASSWORD_BCRYPT);
            $sqlUpd = "UPDATE usuarios SET contrasena_hash = ?, intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id_usuario = ?";
            $stmtUpd = mysqli_prepare($conexion, $sqlUpd);
            mysqli_stmt_bind_param($stmtUpd, "si", $nuevoHash, $id_usuario);
            mysqli_stmt_execute($stmtUpd);
            mysqli_stmt_close($stmtUpd);

            unset($_SESSION['correo_recuperacion'], $_SESSION['codigo_aprobado'], $_SESSION['id_recuperacion'], $_SESSION['id_usuario_recuperacion']);
            header('Location: login.php?recuperacion=ok');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva contraseña - TaxiH-GO!</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="assets/css/auth.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">

    <nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="index.php">
                <img src="../imagenes/logo.png" alt="TaxiH-GO!" width="42">
                <span>TaxiH-GO!</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuNuevaPass">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="menuNuevaPass">
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

    <main class="flex-grow-1 d-flex align-items-center py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-11 col-sm-9 col-md-6 col-lg-5 col-xl-4">
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-body p-4 p-md-5">

                            <div class="mb-2">
                                <a href="<?= $fase === 1 ? 'recuperar.php' : 'login.php' ?>" class="btn-regresar" title="Regresar">
                                    <i class="bi bi-arrow-left fs-4"></i>
                                </a>
                            </div>

                            <div class="text-center mb-3">
                                <img src="../imagenes/logo.png" alt="TaxiH-GO!" width="95">
                            </div>

                            <?php if ($fase === 1): ?>
                                <h3 class="fw-bold mb-1">Ingresa el código</h3>
                                <p class="text-muted small mb-4">Enviamos un código de 6 dígitos a <strong><?= htmlspecialchars($correo) ?></strong>.</p>
                            <?php else: ?>
                                <h3 class="fw-bold mb-1">Crea tu contraseña</h3>
                                <p class="text-muted small mb-4">El código fue verificado. Elige una nueva contraseña segura.</p>
                            <?php endif; ?>

                            <?php if ($errorGeneral !== ''): ?>
                                <div class="alert alert-danger py-2 small rounded-3"><?= htmlspecialchars($errorGeneral) ?></div>
                            <?php endif; ?>

                            <!-- FASE 1: FORMULARIO DEL CÓDIGO -->
                            <?php if ($fase === 1): ?>
                                <form id="formFase1" method="POST" action="" novalidate>
                                    <div class="mb-4">
                                        <label for="codigo" class="form-label fw-semibold small text-secondary">Código de verificación</label>
                                        <input type="text" id="codigo" name="codigo" maxlength="6" inputmode="numeric" class="form-control campo-input text-center fw-bold fs-4 letter-spacing-2 <?= $errores['codigo'] !== '' ? 'is-invalid' : '' ?>" placeholder="000000" value="<?= htmlspecialchars($codigoIngresado) ?>">
                                        <div id="error-codigo" class="texto-error-campo text-start <?= $errores['codigo'] === '' ? 'd-none' : '' ?>">
                                            <?= htmlspecialchars($errores['codigo']) ?>
                                        </div>
                                    </div>
                                    <button type="submit" name="btn_verificar" class="btn btn-amarillo w-100 rounded-pill py-2 mb-2">Verificar código</button>
                                </form>

                            <!-- FASE 2: FORMULARIO DE CONTRASEÑA -->
                            <?php else: ?>
                                <form id="formFase2" method="POST" action="" novalidate>
                                    <div class="mb-3">
                                        <label for="contrasena" class="form-label fw-semibold small text-secondary">Nueva contraseña</label>
                                        <div class="position-relative">
                                            <input type="password" id="contrasena" name="contrasena" class="form-control campo-input pe-5 <?= $errores['contrasena'] !== '' ? 'is-invalid' : '' ?>" placeholder="••••••••">
                                            <button type="button" id="btnToggle1" class="btn-ojito" aria-label="Mostrar contraseña">
                                                <i id="icoOjo1" class="bi bi-eye fs-5"></i>
                                            </button>
                                        </div>
                                        <div class="form-text" style="font-size: 0.78rem;">De 8 a 20 caracteres, con mayúscula, minúscula, número y símbolo.</div>
                                        <div id="error-contrasena" class="texto-error-campo <?= $errores['contrasena'] === '' ? 'd-none' : '' ?>">
                                            <?= htmlspecialchars($errores['contrasena']) ?>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label for="confirmar_contrasena" class="form-label fw-semibold small text-secondary">Confirmar contraseña</label>
                                        <div class="position-relative">
                                            <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" class="form-control campo-input pe-5 <?= $errores['confirmar_contrasena'] !== '' ? 'is-invalid' : '' ?>" placeholder="••••••••">
                                            <button type="button" id="btnToggle2" class="btn-ojito" aria-label="Mostrar confirmación">
                                                <i id="icoOjo2" class="bi bi-eye fs-5"></i>
                                            </button>
                                        </div>
                                        <div id="error-confirmar" class="texto-error-campo <?= $errores['confirmar_contrasena'] === '' ? 'd-none' : '' ?>">
                                            <?= htmlspecialchars($errores['confirmar_contrasena']) ?>
                                        </div>
                                    </div>

                                    <button type="submit" name="btn_cambiar" class="btn btn-amarillo w-100 rounded-pill py-2 mb-2">Cambiar contraseña</button>
                                </form>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="border-top py-4 bg-white mt-auto">
        <div class="container d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 small text-muted">
            <span>TaxiH-GO! · Central de taxis por aplicación de Huejutla de Reyes, Hidalgo.</span>
            <a href="#" class="text-muted text-decoration-underline">Aviso de privacidad</a>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if ($fase === 1): ?>
        const formFase1 = document.getElementById('formFase1');
        const inputCodigo = document.getElementById('codigo');
        const errorCodigo = document.getElementById('error-codigo');

        inputCodigo.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 6);
            this.classList.remove('is-invalid');
            errorCodigo.classList.add('d-none');
        });

        formFase1.addEventListener('submit', function (e) {
            if (inputCodigo.value.trim().length !== 6) {
                e.preventDefault();
                inputCodigo.classList.add('is-invalid');
                errorCodigo.textContent = 'El código debe tener exactamente 6 números.';
                errorCodigo.classList.remove('d-none');
            }
        });
        <?php else: ?>
        const formFase2 = document.getElementById('formFase2');
        const inputPass = document.getElementById('contrasena');
        const inputConf = document.getElementById('confirmar_contrasena');
        const errorPass = document.getElementById('error-contrasena');
        const errorConf = document.getElementById('error-confirmar');

        function configurarOjo(btnId, inputId, icoId) {
            document.getElementById(btnId).addEventListener('click', function() {
                const inp = document.getElementById(inputId);
                const ico = document.getElementById(icoId);
                const esPass = inp.type === 'password';
                inp.type = esPass ? 'text' : 'password';
                ico.classList.toggle('bi-eye', !esPass);
                ico.classList.toggle('bi-eye-slash', esPass);
            });
        }
        configurarOjo('btnToggle1', 'contrasena', 'icoOjo1');
        configurarOjo('btnToggle2', 'confirmar_contrasena', 'icoOjo2');

        [inputPass, inputConf].forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('is-invalid');
                const errDiv = this.id === 'contrasena' ? errorPass : errorConf;
                errDiv.classList.add('d-none');
            });
        });

        formFase2.addEventListener('submit', function (e) {
            let valido = true;
            const regexPass = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_])[A-Za-z\d\W_]{8,20}$/;

            if (inputPass.value === '') {
                inputPass.classList.add('is-invalid');
                errorPass.textContent = 'Escribe tu nueva contraseña.';
                errorPass.classList.remove('d-none');
                valido = false;
            } else if (!regexPass.test(inputPass.value)) {
                inputPass.classList.add('is-invalid');
                errorPass.textContent = 'Debe tener de 8 a 20 caracteres, mayúscula, minúscula, número y símbolo.';
                errorPass.classList.remove('d-none');
                valido = false;
            }

            if (inputConf.value === '') {
                inputConf.classList.add('is-invalid');
                errorConf.textContent = 'Confirma tu nueva contraseña.';
                errorConf.classList.remove('d-none');
                valido = false;
            } else if (inputPass.value !== inputConf.value) {
                inputConf.classList.add('is-invalid');
                errorConf.textContent = 'Las contraseñas no coinciden.';
                errorConf.classList.remove('d-none');
                valido = false;
            }

            if (!valido) e.preventDefault();
        });
        <?php endif; ?>
    </script>
</body>
</html>