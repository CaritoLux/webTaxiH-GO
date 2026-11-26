<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php?error=acceso_denegado');
    exit;
}

require_once __DIR__ . '/config/conexion.php';

$id_usuario = (int)$_SESSION['usuario_id'];

$sql = "SELECT id_usuario, rol, nombre, apellido_paterno, apellido_materno, telefono, correo, fecha_registro 
        FROM usuarios WHERE id_usuario = ?";
$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_usuario);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$usuario = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

$calificacion = 5.0;
if ($usuario && $usuario['rol'] === 'CONDUCTOR') {
    $sqlCal = "SELECT AVG(estrellas) AS promedio FROM calificaciones WHERE id_calificado = ?";
    $stmtCal = mysqli_prepare($conexion, $sqlCal);
    mysqli_stmt_bind_param($stmtCal, "i", $id_usuario);
    mysqli_stmt_execute($stmtCal);
    $resCal = mysqli_stmt_get_result($stmtCal);
    $filaCal = mysqli_fetch_assoc($resCal);
    if ($filaCal && $filaCal['promedio']) {
        $calificacion = round((float)$filaCal['promedio'], 1);
    }
    mysqli_stmt_close($stmtCal);
}

$iniciales = strtoupper(substr($usuario['nombre'], 0, 1) . substr($usuario['apellido_paterno'], 0, 1));
$nombreCompleto = trim($usuario['nombre'] . ' ' . $usuario['apellido_paterno'] . ' ' . ($usuario['apellido_materno'] ?? ''));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - TaxiH-GO!</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="assets/css/perfil.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- Encabezado (mismo que index.php) -->
    <nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="index.php">
                <img src="assets/img/logo.png" alt="" width="42">
                <span>TaxiH-GO!</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPerfil">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="menuPerfil">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
                    <li class="nav-item"><a class="nav-link text-dark fw-medium" href="index.php#tarifa">Tarifa</a></li>
                    <li class="nav-item"><a class="nav-link text-dark fw-medium" href="index.php#contacto">Contacto</a></li>

                    <li class="nav-item dropdown ms-lg-2">
                        <a class="btn btn-outline-dark rounded-pill px-3 fw-semibold dropdown-toggle d-inline-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i>
                            <?= htmlspecialchars($usuario['nombre']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 mt-2">
                            <li>
                                <a class="dropdown-item py-2 active bg-warning-subtle text-dark fw-semibold" href="perfil.php">
                                    <i class="bi bi-person me-2"></i>Mi perfil
                                </a>
                            </li>
                            <?php if ($usuario['rol'] === 'ADMINISTRADOR'): ?>
                                <li>
                                    <a class="dropdown-item py-2" href="admin/dashboard.php">
                                        <i class="bi bi-speedometer2 me-2"></i>Panel Admin
                                    </a>
                                </li>
                            <?php elseif ($usuario['rol'] === 'BASE'): ?>
                                <li>
                                    <a class="dropdown-item py-2" href="base/operacion.php">
                                        <i class="bi bi-grid me-2"></i>Panel Base
                                    </a>
                                </li>
                            <?php elseif ($usuario['rol'] === 'CONDUCTOR'): ?>
                                <li>
                                    <a class="dropdown-item py-2" href="conductor/panel.php">
                                        <i class="bi bi-car-front me-2"></i>Panel Conductor
                                    </a>
                                </li>
                            <?php elseif ($usuario['rol'] === 'PASAJERO'): ?>
                                <li>
                                    <a class="dropdown-item py-2" href="pasajero/viajes.php">
                                        <i class="bi bi-clock-history me-2"></i>Mis viajes
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item py-2 text-danger" href="logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contenido principal -->
    <main class="flex-grow-1 d-flex align-items-center py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-11 col-sm-9 col-md-7 col-lg-5">
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-body p-4 p-md-5">

                            <!-- Flecha para regresar al index y botón cerrar sesión -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <a href="index.php" class="btn-regresar" title="Volver al inicio" aria-label="Volver al inicio">
                                    <i class="bi bi-arrow-left fs-4"></i>
                                </a>
                                <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-semibold">
                                    Cerrar sesión
                                </a>
                            </div>

                            <!-- Avatar y nombre -->
                            <div class="d-flex align-items-center mb-4">
                                <div class="avatar-circulo me-3"><?= htmlspecialchars($iniciales) ?></div>
                                <div>
                                    <h1 class="h4 fw-bold mb-1"><?= htmlspecialchars($nombreCompleto) ?></h1>
                                    <span class="badge rounded-pill px-3 py-1" style="background-color: #FEF3C7; color: #111827;">
                                        <?= htmlspecialchars($usuario['rol']) ?>
                                    </span>
                                    <?php if ($usuario['rol'] === 'CONDUCTOR'): ?>
                                        <span class="ms-2 text-muted fw-semibold">★ <?= $calificacion ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Tarjeta de datos personales -->
                            <div class="caja-datos">
                                <div class="mb-3">
                                    <small class="text-muted d-block">Correo electrónico</small>
                                    <span class="fw-medium text-dark"><?= htmlspecialchars($usuario['correo']) ?></span>
                                </div>
                                <hr class="my-2">
                                <div class="my-3">
                                    <small class="text-muted d-block">Teléfono celular</small>
                                    <span class="fw-medium text-dark"><?= htmlspecialchars($usuario['telefono']) ?></span>
                                </div>
                                <?php if (!empty($usuario['fecha_registro'])): ?>
                                    <hr class="my-2">
                                    <div class="mt-3">
                                        <small class="text-muted d-block">Miembro desde</small>
                                        <span class="fw-medium text-dark">
                                            <?= date('d/m/Y', strtotime($usuario['fecha_registro'])) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>

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
            <a href="politicas.php" class="text-muted text-decoration-underline">Aviso de privacidad</a>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>