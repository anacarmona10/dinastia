// ==============================================
// VALIDACIONES PARA REGISTRO Y LOGIN
// ==============================================

// Función que valida la contraseña y devuelve un array de errores
function validarContrasena(pass) {
    const errores = [];

    if (pass.length < 8) {
        errores.push('La contraseña debe tener mínimo 8 caracteres.');
    }
    if (!/[A-Z]/.test(pass)) {
        errores.push('La contraseña debe tener mínimo una mayúscula.');
    }
    if (!/[a-z]/.test(pass)) {
        errores.push('La contraseña debe tener mínimo una minúscula.');
    }
    if (!/[0-9]/.test(pass)) {
        errores.push('La contraseña debe tener mínimo un número.');
    }
    if (!/[!@#$%^&*()_+\-=\[\]{};:'"\\|,.<>\/?]/.test(pass)) {
        errores.push('La contraseña debe tener mínimo un carácter especial.');
    }

    return errores;
}

// Validación para el formulario de registro
function validarRegistro(event) {
    event.preventDefault();

    // Obtener campos
    const nombre = document.getElementById('nombreCompleto').value.trim();
    const tipoDoc = document.getElementById('tipoDocumento').value;
    const numDoc = document.getElementById('numeroDocumento').value.trim();
    const correo = document.getElementById('correo').value.trim();
    const pass = document.getElementById('contraseña').value;
    const confirm = document.getElementById('confirm_password').value;

    const mensajeDiv = document.getElementById('mensaje');
    mensajeDiv.innerHTML = '';

    let errores = [];

    // --- Validaciones básicas ---
    if (nombre === '') errores.push('El nombre completo es obligatorio.');
    if (tipoDoc === '') errores.push('Debes seleccionar un tipo de documento.');
    if (numDoc === '') errores.push('El número de documento es obligatorio.');

    // Correo
    if (correo === '') {
        errores.push('El correo electrónico es obligatorio.');
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo)) {
        errores.push('El correo electrónico no tiene un formato válido.');
    }

    // Contraseña (usamos la función de validación)
    if (pass === '') {
        errores.push('La contraseña es obligatoria.');
    } else {
        const erroresPass = validarContrasena(pass);
        errores.push(...erroresPass); // Agregamos todos los errores de la contraseña
    }

    // Confirmación
    if (confirm === '') {
        errores.push('Debes confirmar la contraseña.');
    } else if (pass !== confirm) {
        errores.push('Las contraseñas no coinciden.');
    }

    // --- Mostrar errores o enviar ---
    if (errores.length > 0) {
        let html = '<ul style="color:red; text-align:left; padding-left:20px;">';
        errores.forEach(function(err) {
            html += '<li>' + err + '</li>';
        });
        html += '</ul>';
        mensajeDiv.innerHTML = html;
        return false;
    } else {
        document.getElementById('formRegistro').submit();
    }
}

// Validación para el formulario de login
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

    // Validación de contraseña (también aplicamos los mismos requisitos)
    if (pass === '') {
        errores.push('La contraseña es obligatoria.');
    } else {
        const erroresPass = validarContrasena(pass);
        errores.push(...erroresPass);
    }

    if (errores.length > 0) {
        let html = '<ul style="color:red; text-align:left; padding-left:20px;">';
        errores.forEach(function(err) {
            html += '<li>' + err + '</li>';
        });
        html += '</ul>';
        mensajeDiv.innerHTML = html;
        return false;
    } else {
        document.getElementById('formLogin').submit();
    }
}

// ==============================================
// ASIGNAR EVENTOS CUANDO EL DOM ESTÉ LISTO
// ==============================================
document.addEventListener('DOMContentLoaded', function() {

    // --- Registro ---
    const formReg = document.getElementById('formRegistro');
    if (formReg) {
        formReg.addEventListener('submit', validarRegistro);
    }

    // --- Login ---
    const formLog = document.getElementById('formLogin');
    if (formLog) {
        formLog.addEventListener('submit', validarLogin);
    }

    // --- Mostrar mensajes de éxito desde URL ---
    const params = new URLSearchParams(window.location.search);

    // Registro
    if (window.location.pathname.includes('Registro.html')) {
        const mensajeDiv = document.getElementById('mensaje');
        if (params.get('success') === '1') {
            let html = '<p style="color:green;">✅ Usuario registrado con éxito</p>';
            if (params.get('login') === '1') {
                html += '<p style="color:blue;">✅ Sesión iniciada con éxito</p>';
            }
            if (mensajeDiv) mensajeDiv.innerHTML = html;
        }
    }

    // Login
    if (window.location.pathname.includes('Login.html')) {
        const mensajeDiv = document.getElementById('mensajeLogin');
        if (params.get('login') === '1') {
            if (mensajeDiv) {
                mensajeDiv.innerHTML = '<p style="color:green; font-weight:bold;">✅ Sesión iniciada con éxito</p>';
            }
        }
    }
});