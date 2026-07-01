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

// ==============================================
// VALIDACIÓN REGISTRO (envío tradicional)
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

    // Si todo válido, envía el formulario normalmente
    document.getElementById('formRegistro').submit();
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

    document.getElementById('formLogin').submit();
}

// ==============================================
// ASIGNAR EVENTOS
// ==============================================
document.addEventListener('DOMContentLoaded', function() {
    const formReg = document.getElementById('formRegistro');
    if (formReg) formReg.addEventListener('submit', validarRegistro);

    const formLog = document.getElementById('formLogin');
    if (formLog) formLog.addEventListener('submit', validarLogin);
});