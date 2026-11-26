<?php
session_start();
require_once __DIR__ . '/../taxihgo_api/config/conexion.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

$errorGeneral = '';
$errores = ['correo' => ''];$correoIngresado = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correoIngresado = strtolower(trim($_POST['correo'] ?? ''));

    if ($correoIngresado === '') {$errores['correo'] = 'Escribe tu correo electrónico.';
    } elseif (!filter_var($correoIngresado, FILTER_VALIDATE_EMAIL)) {$errores['correo'] = 'Escribe un correo electrónico válido.';
    }

    if ($errores['correo'] === '') {$sql = "SELECT id_usuario, nombre FROM usuarios WHERE correo = ?";
        $stmt = mysqli_prepare($conexion,$sql);
        mysqli_stmt_bind_param($stmt, "s", $correoIngresado);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $usuario = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if ($usuario) {$id_usuario = (int)$usuario['id_usuario'];$codigo = sprintf("%06d", mt_rand(1, 999999));
            $codigo_hash = password_hash($codigo, PASSWORD_BCRYPT);

            // Invalidar códigos anteriores que no se hayan usado
            $sqlOld = "UPDATE recuperaciones_contrasena SET usado = 1 WHERE id_usuario = ? AND usado = 0";
            $stmtOld = mysqli_prepare($conexion,$sqlOld);
            mysqli_stmt_bind_param($stmtOld, "i", $id_usuario);
            mysqli_stmt_execute($stmtOld);
            mysqli_stmt_close($stmtOld);

            // Insertar el nuevo código
            $sqlIns = "INSERT INTO recuperaciones_contrasena (id_usuario, codigo_hash, intentos, usado, vence, fecha_creacion) 
                       VALUES (?, ?, 0, 0, DATE_ADD(NOW(), INTERVAL 15 MINUTE), NOW())";
            $stmtIns = mysqli_prepare($conexion,$sqlIns);
            mysqli_stmt_bind_param($stmtIns, "is", $id_usuario,$codigo_hash);
            mysqli_stmt_execute($stmtIns);
            mysqli_stmt_close($stmtIns);

            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.hostinger.com';$mail->SMTPAuth   = true;
                $mail->Username   = 'soporte@bandline.com.mx'; 
                $mail->Password   = 'Contrasenia67$';      
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = 465;
                $mail->Timeout    = 10;
                $mail->CharSet    = 'UTF-8';

                $mail->setFrom('soporte@bandline.com.mx', 'Soporte TaxiH-GO!');
                $mail->addAddress($correoIngresado,$usuario['nombre']);

                $mail->isHTML(true);$mail->Subject = 'Código de recuperación - TaxiH-GO!';
                $mail->Body    = 'Hola ' . htmlspecialchars($usuario['nombre']) . ',<br><br>Tu código de recuperación es: <b>' . $codigo . '</b><br><br>Este código expira en 15 minutos.';
                $mail->AltBody = 'Hola ' .$usuario['nombre'] . ', tu código de recuperación es: ' . $codigo;

                $mail->send();
                $_SESSION['correo_recuperacion'] =$correoIngresado;
                
                // Limpiar cualquier sesión previa de código aprobado por si el usuario regresó
                unset($_SESSION['codigo_aprobado'], $_SESSION['id_recuperacion'],$_SESSION['id_usuario_recuperacion']);
                
                header('Location: nueva_contrasena.php');
                exit;
            } catch (Exception $e) {$errorGeneral = "No se pudo enviar el correo de recuperación.";
            }
        } else {
            $errorGeneral = 'No encontramos una cuenta registrada con ese correo.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña - TaxiH-GO!</title>
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
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuRecuperar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="menuRecuperar">
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
                                <a href="login.php" class="btn-regresar" title="Regresar" aria-label="Regresar">
                                    <i class="bi bi-arrow-left fs-4"></i>
                                </a>
                            </div>

                            <div class="text-center mb-3">
                                <img src="../imagenes/logo.png" alt="TaxiH-GO!" width="95">
                            </div>

                            <h3 class="fw-bold mb-1">Recuperar contraseña</h3>
                            <p class="text-muted small mb-4">Escribe el correo de tu cuenta. Te enviaremos un código de 6 dígitos para crear una contraseña nueva.</p>

                            <?php if ($errorGeneral !== ''): ?>
                                <div class="alert alert-danger py-2 small rounded-3"><?= htmlspecialchars($errorGeneral) ?></div>
                            <?php endif; ?>

                            <form id="formRecuperar" method="POST" action="" novalidate>
                                <div class="mb-4">
                                    <label for="correo" class="form-label fw-semibold small text-secondary">Correo electrónico</label>
                                    <input type="email" id="correo" name="correo" class="form-control campo-input <?= $errores['correo'] !== '' ? 'is-invalid' : '' ?>" placeholder="correo@ejemplo.com" value="<?= htmlspecialchars($correoIngresado) ?>">
                                    <div id="error-correo" class="texto-error-campo <?= $errores['correo'] === '' ? 'd-none' : '' ?>">
                                        <?= htmlspecialchars($errores['correo']) ?>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-amarillo w-100 rounded-pill py-2 mb-2">Enviar código</button>
                            </form>

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
        const formRecuperar = document.getElementById('formRecuperar');
        const inputCorreo = document.getElementById('correo');
        const errorCorreo = document.getElementById('error-correo');

        formRecuperar.addEventListener('submit', function (e) {
            let valido = true;
            const correoVal = inputCorreo.value.trim();

            inputCorreo.classList.remove('is-invalid');
            errorCorreo.classList.add('d-none');

            if (correoVal === '') {
                inputCorreo.classList.add('is-invalid');
                errorCorreo.textContent = 'Escribe tu correo electrónico.';
                errorCorreo.classList.remove('d-none');
                valido = false;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correoVal)) {
                inputCorreo.classList.add('is-invalid');
                errorCorreo.textContent = 'Escribe un correo electrónico válido.';
                errorCorreo.classList.remove('d-none');
                valido = false;
            }

            if (!valido) e.preventDefault();
        });

        inputCorreo.addEventListener('input', function() {
            this.classList.remove('is-invalid');
            errorCorreo.classList.add('d-none');
        });
    </script>
</body>
</html>