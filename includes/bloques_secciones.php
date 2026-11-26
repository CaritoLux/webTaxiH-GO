<?php
// Dibuja las secciones de una página ($secciones) una debajo de otra:
// título a la izquierda y contenido a la derecha. Si la sección tiene
// pasos, se muestran como lista numerada; si tiene imagen, a un lado.
?>
<?php foreach ($secciones as $i => $s): ?>
    <div class="row g-3 py-4 <?= $i > 0 ? 'border-top' : '' ?>">
        <div class="col-md-4">
            <h2 class="t-title-l mb-0"><?= e($s['titulo']) ?></h2>
        </div>
        <div class="<?= $s['imagen'] ? 'col-md-5' : 'col-md-8' ?>">
            <?php if (!empty($s['contenido'])): ?>
                <div class="bloque-texto">
                    <?php foreach (preg_split('/\R{1,}/', trim($s['contenido'])) as $parrafo): ?>
                        <p><?= e($parrafo) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php $pasos = obtener_pasos($conexion, $s['id_seccion']); ?>
            <?php if ($pasos): ?>
                <ol class="list-unstyled mt-3 mb-0">
                    <?php foreach ($pasos as $n => $paso): ?>
                        <li class="d-flex align-items-start gap-3 mb-3">
                            <span class="paso-num"><?= $n + 1 ?></span>
                            <span class="pt-1"><?= e($paso['texto']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </div>
        <?php if ($s['imagen']): ?>
            <div class="col-md-3">
                <img src="<?= RAIZ ?>uploads/sitio/<?= e($s['imagen']) ?>" class="imagen-seccion" alt="">
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
