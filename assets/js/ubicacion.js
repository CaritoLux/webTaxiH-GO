// Mapa de la página Ubicación: un marcador en la central
const cajaMapa = document.getElementById('mapaCentral');

if (cajaMapa) {
    const punto = [parseFloat(cajaMapa.dataset.lat), parseFloat(cajaMapa.dataset.lng)];
    const mapa = crearMapa('mapaCentral', punto, 17);
    L.marker(punto, { icon: iconoDestino(), keyboard: false }).addTo(mapa);
}
