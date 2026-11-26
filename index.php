<?php
session_start();
$logueado = isset($_SESSION['usuario_id']);
$nombreUsuario = $_SESSION['nombre'] ?? '';
$rolUsuario = $_SESSION['rol'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaxiH-GO! - Pide tu taxi en Huejutla</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; color: #111827; }
        .btn-amarillo { background-color: #F5B800; color: #111827; font-weight: 700; border: none; }
        .btn-amarillo:hover { background-color: #e0a800; color: #111827; }
        .paso-num {
            width: 30px; height: 30px; min-width: 30px;
            background-color: #FEF3C7; color: #92400E;
            font-weight: 700; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 0.85rem;
        }
        .card-suave { background-color: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 16px; }
    </style>
</head>
<body>

    <!-- Barra de navegación -->
    <nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="index.php">
                <img src="../imagenes/logo.png" alt="TaxiH-GO!" width="42">
                <span>TaxiH-GO!</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="menuPrincipal">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
                    <li class="nav-item"><a class="nav-link text-dark fw-medium" href="#pasajeros">Pasajeros</a></li>
                    <li class="nav-item"><a class="nav-link text-dark fw-medium" href="#taxistas">Taxistas</a></li>
                    <li class="nav-item"><a class="nav-link text-dark fw-medium" href="#tarifa">Tarifa</a></li>
                    <li class="nav-item"><a class="nav-link text-dark fw-medium" href="#contacto">Contacto</a></li>

                    <?php if ($logueado): ?>
                        <!-- Menú cuando el usuario ya inició sesión -->
                        <li class="nav-item dropdown ms-lg-2">
                            <a class="btn btn-outline-dark rounded-pill px-3 fw-semibold dropdown-toggle d-inline-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle"></i>
                                <?= htmlspecialchars($nombreUsuario) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 mt-2">
                                <li>
                                    <a class="dropdown-item py-2" href="perfil.php">
                                        <i class="bi bi-person me-2"></i>Mi perfil
                                    </a>
                                </li>
                                <?php if ($rolUsuario === 'ADMINISTRADOR'): ?>
                                    <li>
                                        <a class="dropdown-item py-2" href="admin/dashboard.php">
                                            <i class="bi bi-speedometer2 me-2"></i>Panel Admin
                                        </a>
                                    </li>
                                <?php elseif ($rolUsuario === 'CONDUCTOR'): ?>
                                    <li>
                                        <a class="dropdown-item py-2" href="conductor/panel.php">
                                            <i class="bi bi-car-front me-2"></i>Panel Conductor
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
                    <?php else: ?>
                        <!-- Botón cuando no hay sesión -->
                        <li class="nav-item ms-lg-2">
                            <a href="login.php" class="btn btn-outline-dark rounded-pill px-4 fw-semibold">Iniciar sesión</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Portada (Hero) -->
    <section class="py-5" style="background-color: #F8F9FA;">
        <div class="container py-lg-4">
            <div class="row align-items-center g-4">
                <div class="col-lg-6">
                    <h1 class="display-5 fw-bold mb-3">Pide tu taxi en Huejutla en pocos pasos.</h1>
                    <p class="text-muted fs-5 mb-4">
                        Taxis registrados ante Transporte, tarifa estimada antes de subir y el taxi en el mapa hasta que llega. Pagas en efectivo al conductor.
                    </p>
                    <?php if ($logueado): ?>
                        <a href="perfil.php" class="btn btn-amarillo rounded-pill px-4 py-2 fs-6">Ir a mi perfil</a>
                    <?php else: ?>
                        <a href="registro.php" class="btn btn-amarillo rounded-pill px-4 py-2 fs-6">Crear cuenta</a>
                    <?php endif; ?>
                </div>
                <div class="col-lg-6 text-center">
                    <div class="bg-white border rounded-4 p-4 shadow-sm d-inline-block w-100" style="max-width: 440px;">
                        <img src="../imagenes/logo.png" alt="TaxiH-GO!" class="img-fluid rounded-3" style="max-height: 260px; object-fit: contain;">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pasajeros y Taxistas -->
    <section class="py-5 border-bottom">
        <div class="container">
            <div class="row g-5">
                <!-- Columna Pasajeros -->
                <div class="col-md-6" id="pasajeros">
                    <h3 class="fw-bold mb-2">Para pasajeros</h3>
                    <p class="text-muted mb-4">Sabes quién te recoge, en qué taxi y cuánto vas a pagar antes de pedirlo.</p>

                    <div class="d-flex align-items-start gap-3 mb-3">
                        <span class="paso-num">1</span>
                        <p class="mb-0 pt-1">Crea tu cuenta aquí o en la app.</p>
                    </div>
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <span class="paso-num">2</span>
                        <p class="mb-0 pt-1">Escribe a dónde vas y revisa la tarifa estimada.</p>
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <span class="paso-num">3</span>
                        <p class="mb-0 pt-1">Sigue tu taxi en el mapa y paga en efectivo al llegar.</p>
                    </div>
                </div>

                <!-- Columna Taxistas -->
                <div class="col-md-6" id="taxistas">
                    <h3 class="fw-bold mb-2">Para taxistas</h3>
                    <p class="text-muted mb-4">Recibe viajes cerca de ti sin cuota semanal y sin multa por rechazar.</p>

                    <div class="d-flex align-items-start gap-3 mb-3">
                        <span class="paso-num">1</span>
                        <p class="mb-0 pt-1">Crea tu cuenta de conductor.</p>
                    </div>
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <span class="paso-num">2</span>
                        <p class="mb-0 pt-1">Sube en esta página los datos de tu taxi y tus documentos.</p>
                    </div>
                    <div class="d-flex align-items-start gap-3 mb-4">
                        <span class="paso-num">3</span>
                        <p class="mb-0 pt-1">Cuando la central te autorice, conéctate en la app y recibe viajes.</p>
                    </div>

                    <?php if (!$logueado): ?>
                        <a href="registro.php" class="btn btn-outline-dark rounded-pill px-4 fw-semibold">Registrarme como taxista</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Tarifa vigente -->
    <section id="tarifa" class="py-5" style="background-color: #F8F9FA;">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-6">
                    <h3 class="fw-bold mb-2">Tarifa vigente</h3>
                    <p class="text-muted mb-0">
                        La app calcula la tarifa estimada antes de pedir el viaje. Si la Semot publica una tarifa oficial para Huejutla, la central la actualiza aquí.
                    </p>
                </div>
                <div class="col-lg-6">
                    <div class="bg-white border rounded-4 p-4 shadow-sm">
                        <div class="text-muted small mb-1">Banderazo (incluye los primeros 2 km)</div>
                        <div class="display-6 fw-bold mb-1">$35.00</div>
                        <div class="fw-medium mb-2">más $4.00 por cada kilómetro adicional</div>
                        <div class="text-muted small">Pago en efectivo al conductor</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contacto -->
    <section id="contacto" class="py-5">
        <div class="container">
            <h3 class="fw-bold mb-4">Contacto</h3>
            <div class="row g-3">
                <div class="col-sm-6 col-lg-3">
                    <div class="card-suave p-3 h-100">
                        <i class="bi bi-telephone text-muted fs-5 d-block mb-2"></i>
                        <span class="fw-medium">789 100 0002</span>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card-suave p-3 h-100">
                        <i class="bi bi-envelope text-muted fs-5 d-block mb-2"></i>
                        <span class="fw-medium">contacto@bandline.com.mx</span>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card-suave p-3 h-100">
                        <i class="bi bi-clock text-muted fs-5 d-block mb-2"></i>
                        <span class="fw-medium">Lunes a domingo de 7:00 a 22:00</span>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card-suave p-3 h-100">
                        <i class="bi bi-geo-alt text-muted fs-5 d-block mb-2"></i>
                        <span class="fw-medium">Huejutla de Reyes, Hidalgo</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pie de página -->
    <footer class="border-top py-4 bg-white">
        <div class="container d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 small text-muted">
            <span>TaxiH-GO! · Central de taxis por aplicación de Huejutla de Reyes, Hidalgo.</span>
            <a href="#" class="text-muted text-decoration-underline">Aviso de privacidad</a>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
