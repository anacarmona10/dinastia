// ==============================================
// VALIDACIONES PARA REGISTRO Y LOGIN (con toast)
// ==============================================

function validarContrasena(pass) {
    const errores = [];
    if (pass.length < 8) errores.push('La contraseña debe tener mínimo 8 caracteres.');
    if (!/[A-Z]/.test(pass)) errores.push('La contraseña debe tener mínimo una mayúscula.');
    if (!/[a-z]/.test(pass)) errores.push('La contraseña debe tener mínimo una minúscula.');
    if (!/[0-9]/.test(pass)) errores.push('La contraseña debe tener mínimo un número.');
    if (!/[!@#$%^&*()_+\-=\[\]{};:'"\\|,.<>\/?]/.test(pass)) errores.push('La contraseña debe tener mínimo un caracter especial.');
    return errores;
}

// ==============================================
// TOAST DE ÉXITO
// ==============================================
function mostrarToast(mensaje, destino) {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');
    const closeBtn = document.getElementById('toastClose');

    toastMessage.textContent = mensaje;
    toast.classList.add('show');

    const timeout = setTimeout(() => {
        toast.classList.remove('show');
        window.location.href = destino;
    }, 2500);

    closeBtn.onclick = function() {
        clearTimeout(timeout);
        toast.classList.remove('show');
        window.location.href = destino;
    };

    toast.onclick = function(e) {
        if (e.target === toast) {
            clearTimeout(timeout);
            toast.classList.remove('show');
            window.location.href = destino;
        }
    };
}

// ==============================================
// VALIDACIÓN REGISTRO
// ==============================================
async function validarRegistro(event) {
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

    const form = document.getElementById('formRegistro');
    const formData = new FormData(form);

    try {
        const response = await fetch(form.action, { method: 'POST', body: formData });
        const text = await response.text();
        console.log('📦 Respuesta:', text);
        const resultado = JSON.parse(text);

        if (resultado.success) {
            mostrarToast(
                '✅ ¡Te has registrado con éxito!',
                'login.html?registro=exito'
            );
        } else {
            mensajeDiv.innerHTML = `<p style="color:red;">${resultado.error}</p>`;
        }
    } catch (error) {
        mensajeDiv.innerHTML = `<p style="color:red;">Error: ${error.message}</p>`;
    }
}

// ==============================================
// VALIDACIÓN LOGIN
// ==============================================
async function validarLogin(event) {
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

    const form = document.getElementById('formLogin');
    const formData = new FormData(form);

    try {
        const response = await fetch(form.action, { method: 'POST', body: formData });
        const text = await response.text();
        console.log('📦 Respuesta login:', text);
        const resultado = JSON.parse(text);

        if (resultado.success) {
            localStorage.setItem('usuarioNombre', resultado.nombre);
            localStorage.setItem('tipoUsuario', resultado.tipo_usuario);
            if (resultado.tipo_usuario === 'admin') {
                window.location.href = 'InterfazAdmin.html';
            } else {
                window.location.href = 'Dashboard.html';
            }
        } else {
            mensajeDiv.innerHTML = `<p style="color:red;">${resultado.error}</p>`;
        }
    } catch (error) {
        mensajeDiv.innerHTML = `<p style="color:red;">Error: ${error.message}</p>`;
    }
}

// ==============================================
// ASIGNAR EVENTOS
// ==============================================
document.addEventListener('DOMContentLoaded', function() {
    const formReg = document.getElementById('formRegistro');
    if (formReg) formReg.addEventListener('submit', validarRegistro);

    const formLog = document.getElementById('formLogin');
    if (formLog) formLog.addEventListener('submit', validarLogin);

    // Mostrar mensaje de éxito en login si viene de registro
    const params = new URLSearchParams(window.location.search);
    if (params.get('registro') === 'exito') {
        const mensajeDiv = document.getElementById('mensajeLogin');
        if (mensajeDiv) {
            mensajeDiv.innerHTML = '<p style="color:green; font-weight:bold;">✅ Te has registrado con éxito. Ahora inicia sesión.</p>';
        }
    }
});