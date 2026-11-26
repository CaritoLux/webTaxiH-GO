// Mapa del incidente: dónde se envió la alerta, con el origen y el destino del viaje
const cajaMapa = document.getElementById('mapaIncidente');

if (cajaMapa) {
    const punto = JSON.parse(cajaMapa.dataset.punto);
    const origen = JSON.parse(cajaMapa.dataset.origen);
    const destino = JSON.parse(cajaMapa.dataset.destino);
    const mapa = crearMapa('mapaIncidente', punto, 16);

    L.marker(origen, { icon: iconoOrigen(), keyboard: false }).addTo(mapa);
    L.marker(destino, { icon: iconoDestino(), keyboard: false }).addTo(mapa);
    L.circleMarker(punto, { radius: 10, color: '#B3261E', weight: 3, fillColor: '#B3261E', fillOpacity: .35 })
        .addTo(mapa).bindTooltip('Alerta', { permanent: true, direction: 'top' });
    mapa.fitBounds(L.latLngBounds([punto, origen, destino]), { padding: [40, 40] });
}
