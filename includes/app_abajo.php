        </div>
    </main>

    <!-- Navegación inferior en celular: destinos con ícono y texto -->
    <nav class="nav-inferior d-md-none" aria-label="Navegación">
        <?php foreach ($opcionesApp as $op): ?>
            <a href="<?= RAIZ . $op[3] ?>" class="<?= $op[0] === $paginaActiva ? 'active' : '' ?>"
               <?= $op[0] === $paginaActiva ? 'aria-current="page"' : '' ?>>
                <span class="indicador"><?= icono($op[2]) ?></span>
                <?= e($op[1]) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php require __DIR__ . '/scripts.php'; ?>
</body>
</html>
