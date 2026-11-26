// Mapa para elegir dónde está la central (Identidad y contacto)
const campoLat = document.getElementById('latitud');
const campoLng = document.getElementById('longitud');

if (document.getElementById('mapaCentral')) {
    const inicio = (campoLat.value && campoLng.value) ? [parseFloat(campoLat.value), parseFloat(campoLng.value)] : HUEJUTLA;
    const mapa = crearMapa('mapaCentral', inicio, 16);
    const marcador = L.marker(inicio, { icon: iconoDestino(), draggable: true, title: 'Ubicación de la central (arrastra para moverla)' }).addTo(mapa);

    function guardarPunto(latlng) {
        campoLat.value = latlng.lat.toFixed(7);
        campoLng.value = latlng.lng.toFixed(7);
    }
    marcador.on('dragend', function () { guardarPunto(marcador.getLatLng()); });
    mapa.on('click', function (e) {
        marcador.setLatLng(e.latlng);
        guardarPunto(e.latlng);
    });
}
