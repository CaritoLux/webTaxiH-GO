// =====================================================================
//  Funciones de mapas con Leaflet (gratis, sin llave de API).
//  Mapa base: OpenStreetMap estándar (gratis, sin clave). Uso permitido
//  para sitios con poco tráfico como este: tile.openstreetmap.org
// =====================================================================

// Parque central de Huejutla de Reyes
const HUEJUTLA = [21.1405, -98.4197];

function crearMapa(idElemento, centro, zoom) {
    const mapa = L.map(idElemento).setView(centro || HUEJUTLA, zoom || 15);
    // Mosaicos de OpenStreetMap: gratis y sin clave (los de CARTO ya piden API key)
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(mapa);
    return mapa;
}

// Marcadores de la guía: origen (círculo azul) y destino (pin azul)
function iconoOrigen() {
    return L.divIcon({ className: '', html: '<div class="marcador-origen"></div>', iconSize: [20, 20], iconAnchor: [10, 10] });
}

function iconoDestino() {
    return L.divIcon({
        className: '',
        html: '<div class="marcador-destino"><span class="material-symbols-outlined" aria-hidden="true">location_on</span></div>',
        iconSize: [40, 40], iconAnchor: [20, 38]
    });
}
