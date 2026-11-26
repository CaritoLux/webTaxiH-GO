// =====================================================================
//  Validación de formularios en el navegador (antes de enviar a PHP)
//  Igual que en clase: novalidate en el form, is-invalid en el campo y
//  el mensaje en el div .invalid-feedback. PHP vuelve a validar todo.
//
//  Cómo se usa en el HTML:
//   <form class="js-validar" novalidate> ... </form>
//   <input id="correo" type="email" required data-error="Escribe un correo válido.">
//   <div class="invalid-feedback" id="error-correo"></div>
//   data-igual-a="contrasena"  -> el campo debe ser igual a otro (confirmar contraseña)
// =====================================================================

document.querySelectorAll('form.js-validar').forEach(function (form) {
    form.addEventListener('submit', function (e) {
        let primero = null;

        form.querySelectorAll('input, select, textarea').forEach(function (campo) {
            if (campo.type === 'hidden' || campo.disabled || !campo.id) {
                return;
            }
            campo.classList.remove('is-invalid');

            let valido = campo.checkValidity();

            // Solo espacios en blanco cuenta como vacío
            if (valido && campo.required && campo.value.trim() === '' &&
                campo.type !== 'file' && campo.type !== 'checkbox' && campo.type !== 'radio') {
                valido = false;
            }

            // Confirmar contraseña
            if (valido && campo.dataset.igualA) {
                const otro = document.getElementById(campo.dataset.igualA);
                if (otro && otro.value !== campo.value) {
                    valido = false;
                }
            }

            if (!valido) {
                marcarError(campo);
                if (!primero) {
                    primero = campo;
                }
            }
        });

        if (primero) {
            e.preventDefault();
            primero.focus();
        }
    });
});

function marcarError(campo) {
    campo.classList.add('is-invalid');
    const feedback = document.getElementById('error-' + campo.id);
    if (feedback) {
        feedback.textContent = campo.dataset.error || 'Revisa este campo.';
        campo.setAttribute('aria-describedby', feedback.id);
    }
}

// Al corregir un campo se quita el borde rojo
document.querySelectorAll('.form-control, .form-select, .form-check-input').forEach(function (campo) {
    campo.addEventListener('input', function () {
        campo.classList.remove('is-invalid');
    });
});

// Botón para mostrar u ocultar la contraseña
document.querySelectorAll('.btn-ver').forEach(function (boton) {
    boton.addEventListener('click', function () {
        const campo = document.getElementById(boton.dataset.campo);
        const icono = boton.querySelector('.material-symbols-outlined');
        if (campo.type === 'password') {
            campo.type = 'text';
            icono.textContent = 'visibility_off';
            boton.setAttribute('aria-label', 'Ocultar contraseña');
        } else {
            campo.type = 'password';
            icono.textContent = 'visibility';
            boton.setAttribute('aria-label', 'Mostrar contraseña');
        }
    });
});
