// Ejemplo en vivo de cuánto costaría un viaje con la tarifa que se está capturando
const formTarifa = document.getElementById('formTarifa');

if (formTarifa) {
    const campos = ['banderazo', 'km_incluidos', 'costo_km'].map(function (id) { return document.getElementById(id); });

    function costo(km) {
        const banderazo = parseFloat(campos[0].value);
        const incluidos = parseFloat(campos[1].value);
        const porKm = parseFloat(campos[2].value);
        if (isNaN(banderazo) || isNaN(incluidos) || isNaN(porKm)) {
            return '—';
        }
        const exacto = banderazo + Math.max(0, km - incluidos) * porKm;
        return '$' + Math.ceil(Math.round(exacto * 100) / 100);
    }

    function actualizar() {
        document.getElementById('ejemplo3').textContent = costo(3);
        document.getElementById('ejemplo6').textContent = costo(6);
    }

    campos.forEach(function (campo) { campo.addEventListener('input', actualizar); });
    actualizar();
}
