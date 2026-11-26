<?php
// Scripts al final de la página. Si la página tiene mapa ($usaMapa) carga
// Leaflet (mapas gratuitos de OpenStreetMap) y las funciones de mapas.js.
// $scripts es una lista de archivos JS propios de la página.
?>
<?php if (!empty($usaMapa)): ?>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="<?= RAIZ ?>assets/js/mapas.js"></script>
<?php endif; ?>
<script src="<?= RAIZ ?>assets/js/formularios.js"></script>
<?php foreach ($scripts ?? [] as $archivoJs): ?>
    <script src="<?= RAIZ . e($archivoJs) ?>"></script>
<?php endforeach; ?>
