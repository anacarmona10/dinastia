// ==============================================
// VALIDACIONES PARA REGISTRO Y LOGIN (sin fetch)
// ==============================================

function validarContrasena(pass) {
    const errores = [];
    if (pass.length < 8) errores.push('La contraseña debe tener mínimo 8 caracteres.');
    if (!/[A-Z]/.test(pass)) errores.push('La contraseña debe tener mínimo una mayúscula.');
    if (!/[a-z]/.test(pass)) errores.push('La contraseña debe tener mínimo una minúscula.');
    if (!/[0-9]/.test(pass)) errores.push('La contraseña debe tener mínimo un número.');
    if (!/[!@#$%^&*()_+\-=\[\]{};:'"\\|,.<>\/?]/.test(pass)) errores.push('La contraseña debe tener mínimo un carácter especial.');
    return errores;
}

let recaptchaWidgetId = null;
let recaptchaListo = false;
let recaptchaSiteKey = '';

function mostrarErrorRegistro(mensaje) {
    const mensajeDiv = document.getElementById('mensaje');
    if (mensajeDiv) {
        mensajeDiv.innerHTML = `<p style="color:red; text-align:left;">${mensaje}</p>`;
    }
}

async function cargarRecaptchaRegistro() {
    const contenedor = document.getElementById('recaptchaRegistro');
    if (!contenedor) return;

    try {
        const response = await fetch('../backend/api/configuracion_publica.php', {
            cache: 'no-store'
        });
        const configuracion = await response.json();

        if (!response.ok || !configuracion.recaptchaSiteKey) {
            throw new Error('reCAPTCHA no está configurado.');
        }

        recaptchaSiteKey = configuracion.recaptchaSiteKey;

        window.onRecaptchaRegistroCargado = () => {
            if (
                recaptchaWidgetId !== null ||
                !window.grecaptcha ||
                typeof window.grecaptcha.render !== 'function'
            ) {
                return;
            }
            try {
                recaptchaWidgetId = window.grecaptcha.render(contenedor, {
                    sitekey: recaptchaSiteKey
                });
                recaptchaListo = true;
            } catch (error) {
                console.error('Error al renderizar reCAPTCHA:', error);
                mostrarErrorRegistro('reCAPTCHA rechazó la configuración. Verifica que la clave sea v2 y que incluya el dominio localhost.');
            }
        };

        const script = document.createElement('script');
        script.src = 'https://www.recaptcha.net/recaptcha/api.js?onload=onRecaptchaRegistroCargado&render=explicit';
        script.async = true;
        script.defer = true;
        script.onload = () => {
            // Respaldo: algunos navegadores descargan el script, pero no ejecutan
            // el callback incluido en la URL.
            window.onRecaptchaRegistroCargado();
        };
        script.onerror = () => {
            mostrarErrorRegistro('No fue posible cargar la verificación reCAPTCHA. Revisa tu conexión a internet.');
        };
        document.head.appendChild(script);

        let intentosCarga = 0;
        const esperaRecaptcha = setInterval(() => {
            intentosCarga++;
            window.onRecaptchaRegistroCargado();

            if (recaptchaListo) {
                clearInterval(esperaRecaptcha);
            } else if (intentosCarga >= 20) {
                clearInterval(esperaRecaptcha);
                mostrarErrorRegistro('reCAPTCHA no terminó de iniciar. Revisa que la clave sea reCAPTCHA v2 con la casilla y que localhost esté autorizado.');
            }
        }, 500);
    } catch (error) {
        mostrarErrorRegistro('El registro no está disponible porque reCAPTCHA no fue configurado.');
    }
}

// ==============================================
// VALIDACIÓN REGISTRO (redirige a verificación)
// ==============================================
function validarRegistro(event) {
    event.preventDefault();

    const nombre = document.getElementById('nombreCompleto').value.trim();
    const tipoDoc = document.getElementById('tipoDocumento').value;
    const numDoc = document.getElementById('numeroDocumento').value.trim();
    const correo = document.getElementById('correo').value.trim();
    const pass = document.getElementById('contraseña').value;
    const confirm = document.getElementById('confirm_password').value;

    const mensajeDiv = document.getElementById('mensaje');
    mensajeDiv.innerHTML = '';
    let errores = [];

    if (nombre === '') errores.push('El nombre completo es obligatorio.');
    if (tipoDoc === '') errores.push('Debes seleccionar un tipo de documento.');
    if (numDoc === '') errores.push('El número de documento es obligatorio.');
    if (correo === '') {
        errores.push('El correo electrónico es obligatorio.');
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo)) {
        errores.push('El correo electrónico no tiene un formato válido.');
    }
    if (pass === '') {
        errores.push('La contraseña es obligatoria.');
    } else {
        errores.push(...validarContrasena(pass));
    }
    if (confirm === '') {
        errores.push('Debes confirmar la contraseña.');
    } else if (pass !== confirm) {
        errores.push('Las contraseñas no coinciden.');
    }

    if (errores.length > 0) {
        let html = '<ul style="color:red; text-align:left; padding-left:20px;">';
        errores.forEach(err => html += '<li>' + err + '</li>');
        html += '</ul>';
        mensajeDiv.innerHTML = html;
        return;
    }

    if (!recaptchaListo || recaptchaWidgetId === null || !window.grecaptcha) {
        mostrarErrorRegistro('Espera a que cargue la verificación reCAPTCHA.');
        return;
    }

    if (!window.grecaptcha.getResponse(recaptchaWidgetId)) {
        mostrarErrorRegistro('Completa la verificación reCAPTCHA antes de registrarte.');
        return;
    }

    event.currentTarget.submit();
}

// ==============================================
// VALIDACIÓN LOGIN (envío tradicional)
// ==============================================
function validarLogin(event) {
    event.preventDefault();
    const correo = document.getElementById('correoLogin').value.trim();
    const pass = document.getElementById('contraseñaLogin').value;
    const mensajeDiv = document.getElementById('mensajeLogin');
    mensajeDiv.innerHTML = '';
    let errores = [];

    if (correo === '') {
        errores.push('El correo electrónico es obligatorio.');
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo)) {
        errores.push('El correo no tiene un formato válido.');
    }
    if (pass === '') errores.push('La contraseña es obligatoria.');

    if (errores.length > 0) {
        let html = '<ul style="color:red; text-align:left; padding-left:20px;">';
        errores.forEach(err => html += '<li>' + err + '</li>');
        html += '</ul>';
        mensajeDiv.innerHTML = html;
        return;
    }

    // Si todo válido, redirigir al dashboard (simulación)
    event.currentTarget.submit();
}

// ==============================================
// ASIGNAR EVENTOS
// ==============================================
document.addEventListener('DOMContentLoaded', function() {
    const formReg = document.getElementById('formRegistro');
    if (formReg) {
        formReg.addEventListener('submit', validarRegistro);
        cargarRecaptchaRegistro();
    }

    const formLog = document.getElementById('formLogin');
    if (formLog) formLog.addEventListener('submit', validarLogin);
});
