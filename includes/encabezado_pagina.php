<?php
// Encabezado de una página pública: título, texto de entrada e imagen
// opcional. Todo viene de la tabla paginas ($pagina).
?>
<section class="encabezado-pagina">
    <div class="container contenedor">
        <div class="row align-items-center g-4">
            <div class="<?= $pagina['imagen'] ? 'col-lg-7' : 'col-lg-9' ?>">
                <h1 class="t-display mb-3"><?= e($pagina['titulo']) ?></h1>
                <?php if (!empty($pagina['subtitulo'])): ?>
                    <p class="lead mb-0"><?= e($pagina['subtitulo']) ?></p>
                <?php endif; ?>
            </div>
            <?php if ($pagina['imagen']): ?>
                <div class="col-lg-5">
                    <img src="<?= RAIZ ?>uploads/sitio/<?= e($pagina['imagen']) ?>" class="imagen-pagina" alt="">
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
