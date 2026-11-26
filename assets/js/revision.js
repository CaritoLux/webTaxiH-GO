// =====================================================================
//  Revisión de documentos: muestra el motivo al rechazar y decide qué
//  botón se puede usar (PHP vuelve a revisar todo al guardar).
// =====================================================================
const formRevision = document.getElementById('formRevision');

if (formRevision && document.getElementById('btnAutorizar')) {
    const decisiones = formRevision.querySelectorAll('.js-decision');
    const total = new Set(Array.from(decisiones).map(function (r) { return r.dataset.doc; })).size;   // documentos subidos
    // Completo = subió todos los documentos y registró su taxi
    const completo = total === parseInt(formRevision.dataset.total, 10) && formRevision.dataset.taxi === '1';
    const btnAutorizar = document.getElementById('btnAutorizar');
    const btnRechazar = document.getElementById('btnRechazar');
    const resumen = document.getElementById('resumenRevision');
    const cajaMensaje = document.getElementById('cajaMensaje');

    function actualizar() {
        let aceptados = 0;
        let rechazados = 0;
        decisiones.forEach(function (radio) {
            if (radio.checked && radio.value === 'ACEPTADO') aceptados++;
            if (radio.checked && radio.value === 'RECHAZADO') rechazados++;
            // El motivo solo se pide cuando se rechaza
            if (radio.value === 'RECHAZADO') {
                const caja = document.getElementById('cajaMotivo' + radio.dataset.doc);
                caja.classList.toggle('d-none', !radio.checked);
                document.getElementById('motivo' + radio.dataset.doc).required = radio.checked;
            }
        });

        const faltan = total - aceptados - rechazados;
        btnAutorizar.disabled = !(completo && aceptados === total);
        btnRechazar.disabled = completo && rechazados === 0;
        cajaMensaje.classList.toggle('d-none', rechazados === 0);

        if (rechazados > 0) {
            resumen.textContent = rechazados + (rechazados === 1 ? ' documento rechazado' : ' documentos rechazados') + ': solo puedes rechazar la solicitud.';
        } else if (faltan > 0) {
            resumen.textContent = 'Faltan ' + faltan + ' de ' + total + ' documentos por revisar.';
        } else {
            resumen.textContent = 'Todos los documentos están aceptados.';
        }
    }

    decisiones.forEach(function (radio) { radio.addEventListener('change', actualizar); });
    actualizar();

    // Confirmar antes de guardar (solo si formularios.js no encontró errores)
    formRevision.addEventListener('submit', function (e) {
        if (e.defaultPrevented) {
            return;
        }
        const autorizar = e.submitter && e.submitter.value === 'autorizar';
        const pregunta = autorizar
            ? '¿Autorizar a este conductor? Podrá recibir viajes en la app.'
            : '¿Rechazar la solicitud? El conductor verá los motivos y podrá corregir.';
        if (!confirm(pregunta)) {
            e.preventDefault();
        }
    });
}
