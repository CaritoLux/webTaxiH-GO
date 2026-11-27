<?php
// <head> común de todas las páginas.
// La letra es system-ui (la del sistema), igual que auth.css: no se descarga ninguna fuente de texto.
// Antes de incluirlo, la página define: $sitio (obtener_sitio), $tituloPagina
// y, si muestra un mapa, $usaMapa = true.

// Íconos de Material Symbols que usa el sitio. Google solo descarga estos
// (la lista debe ir en orden alfabético; sort() lo hace por nosotros).
$iconosSitio = [
    'arrow_back', 'article', 'badge', 'call', 'chat', 'check', 'check_circle', 'close',
    'dashboard', 'directions_car', 'download', 'error', 'group', 'help', 'history',
    'home', 'how_to_reg', 'link', 'local_taxi', 'location_on', 'logout',
    'mail', 'menu', 'navigation', 'open_in_new', 'payments', 'person',
    'report', 'schedule', 'search', 'sos', 'star',
    'trip_origin', 'visibility', 'visibility_off', 'web',
];
sort($iconosSitio);
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($tituloPagina) ?> · <?= e($sitio['nombre']) ?></title>
<link rel="icon" href="<?= e($sitio['favicon_url']) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0..1,0&icon_names=<?= implode(',', $iconosSitio) ?>&display=block" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<?php if (!empty($usaMapa)): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<?php endif; ?>
<link href="<?= RAIZ ?>assets/css/taxihgo.css" rel="stylesheet">
