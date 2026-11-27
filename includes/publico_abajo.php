<?php
// Parte de abajo de las páginas públicas: pie de página y scripts.
// Las páginas del pie salen de la tabla paginas (el admin cambia el nombre y el orden).
$paginasPie = consultar($conexion, "SELECT archivo, nombre_menu FROM paginas ORDER BY orden");
$redes = consultar($conexion, "SELECT nombre, url FROM redes_sociales WHERE activo = 1 ORDER BY orden, id_red");
?>
    </main>

    <footer class="pie mt-auto">
        <div class="container contenedor">
            <div class="row g-4">
                <div class="col-lg-4">
                    <img src="<?= e($sitio['logo_url']) ?>" alt="<?= e($sitio['nombre']) ?>" width="96" class="mb-3">
                    <p class="texto-secundario mb-0" style="max-width: 320px;"><?= e($sitio['texto_pie']) ?></p>
                </div>

                <div class="col-6 col-lg-2">
                    <h2>Sitio</h2>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($paginasPie as $opcion): ?>
                            <li><a href="<?= RAIZ . e($opcion['archivo']) ?>"><?= e($opcion['nombre_menu']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="col-6 col-lg-3">
                    <h2>Cuenta</h2>
                    <ul class="list-unstyled mb-0">
                        <?php if ($logueado): ?>
                            <?php if ($_SESSION['rol'] === 'PASAJERO'): ?>
                                <li><a href="<?= RAIZ ?>pasajero/viajes.php">Mis viajes</a></li>
                                <li><a href="<?= RAIZ ?>pasajero/reportes.php">Mis reportes</a></li>
                            <?php else: ?>
                                <li><a href="<?= RAIZ . panel_de_rol($_SESSION['rol']) ?>">Mi panel</a></li>
                            <?php endif; ?>
                            <li><a href="<?= RAIZ ?>logout.php">Cerrar sesión</a></li>
                        <?php else: ?>
                            <li><a href="<?= RAIZ ?>login.php">Iniciar sesión</a></li>
                            <li><a href="<?= RAIZ ?>registro.php?rol=PASAJERO">Crear cuenta de pasajero</a></li>
                            <li><a href="<?= RAIZ ?>registro.php?rol=CONDUCTOR">Registrarme como taxista</a></li>
                        <?php endif; ?>
                        <?php if (!empty($sitio['enlace_app'])): ?>
                            <li><a href="<?= e($sitio['enlace_app']) ?>" rel="noopener">Descarga la app</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="col-lg-3">
                    <h2>Contacto</h2>
                    <ul class="list-unstyled mb-0">
                        <li><a href="tel:<?= e($empresa['telefono']) ?>"><?= e(telefono_bonito($empresa['telefono'])) ?></a></li>
                        <li><a href="mailto:<?= e($empresa['correo']) ?>"><?= e($empresa['correo']) ?></a></li>
                        <li class="texto-secundario"><?= e($empresa['horario']) ?></li>
                        <?php foreach ($redes as $red): ?>
                            <li><a href="<?= e($red['url']) ?>" target="_blank" rel="noopener"><?= e($red['nombre']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="pie-inferior d-flex flex-column flex-sm-row justify-content-between gap-2">
                <span>&copy; <?= date('Y') ?> <?= e($sitio['nombre']) ?> · Huejutla de Reyes, Hidalgo</span>
                <a href="<?= RAIZ ?>politicas.php">Aviso de privacidad</a>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php require __DIR__ . '/scripts.php'; ?>
</body>
</html>
