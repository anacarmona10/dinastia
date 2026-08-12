// verificacion.js - Lógica de la página de verificación

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formVerificacion');
    const inputCodigo = document.getElementById('codigoVerificacion');
    const mensajeDiv = document.getElementById('mensajeVerificacion');
    const correoDestino = document.getElementById('correoDestino');
    const btnVerificar = document.getElementById('btnVerificar');
    const btnReenviar = document.getElementById('btnReenviar');
    const mensajeReenvio = document.getElementById('mensajeReenvio');
    const contadorSpan = document.getElementById('contador');
    const temporizadorDiv = document.getElementById('temporizador');

    // --- Variables del temporizador ---
    let tiempoRestante = 60;
    let intervalo = null;

    // Obtener el correo del localStorage (guardado al registrarse)
    const correo = localStorage.getItem('correoRegistro') || 'tu correo electrónico';
    correoDestino.textContent = `📧 ${correo}`;

    // --- Simular envío de código (al cargar la página) ---
    let codigoEnviado = '';
    function generarCodigo() {
        return Math.floor(100000 + Math.random() * 900000).toString();
    }

    function enviarCodigo() {
        codigoEnviado = generarCodigo();
        console.log('Código enviado (simulado):', codigoEnviado);
        // Mostrar mensaje de reenvío exitoso
        mensajeReenvio.innerHTML = '<span style="color: #28a745;">✅ Se ha reenviado el código a tu correo.</span>';
        mensajeReenvio.style.color = '#28a745';
        setTimeout(() => {
            mensajeReenvio.innerHTML = '';
        }, 4000);

        // Reiniciar el temporizador
        reiniciarTemporizador();
    }

    // --- Funciones del temporizador ---
    function iniciarTemporizador() {
        // Si ya hay un intervalo, lo limpiamos
        if (intervalo) {
            clearInterval(intervalo);
        }
        // Deshabilitar botón de reenvío
        btnReenviar.disabled = true;
        // Actualizar el contador en la UI
        contadorSpan.textContent = tiempoRestante;

        intervalo = setInterval(function() {
            tiempoRestante--;
            contadorSpan.textContent = tiempoRestante;

            if (tiempoRestante <= 0) {
                clearInterval(intervalo);
                intervalo = null;
                btnReenviar.disabled = false;
                contadorSpan.textContent = '0';
                // Mostrar mensaje de que ya se puede reenviar
                mensajeReenvio.innerHTML = '<span style="color: #c800ff;">✅ Ya puedes reenviar el código nuevamente.</span>';
                setTimeout(() => {
                    mensajeReenvio.innerHTML = '';
                }, 3000);
            }
        }, 1000);
    }

    function reiniciarTemporizador() {
        // Detener el intervalo actual
        if (intervalo) {
            clearInterval(intervalo);
            intervalo = null;
        }
        // Reiniciar tiempo a 60 segundos
        tiempoRestante = 60;
        // Iniciar de nuevo
        iniciarTemporizador();
    }

    // --- Enviar código al cargar la página por primera vez ---
    enviarCodigo();

    // --- Evento de reenvío ---
    btnReenviar.addEventListener('click', function() {
        // Deshabilitar el botón inmediatamente para evitar múltiples clics
        btnReenviar.disabled = true;
        btnReenviar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
        setTimeout(() => {
            enviarCodigo();
            btnReenviar.innerHTML = '<i class="fas fa-redo-alt"></i> Reenviar código';
            // Nota: enviarCodigo() ya reinicia el temporizador
        }, 1500);
    });

    // --- Evento de verificación ---
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const codigoIngresado = inputCodigo.value.trim();

        // Validar que no esté vacío
        if (codigoIngresado === '') {
            mensajeDiv.innerHTML = '<span style="color: #dc3545;">Por favor ingresa el código de verificación.</span>';
            return;
        }

        // Validar que sean 6 dígitos
        if (!/^\d{6}$/.test(codigoIngresado)) {
            mensajeDiv.innerHTML = '<span style="color: #dc3545;">El código debe tener 6 dígitos numéricos.</span>';
            return;
        }

        // Verificar si coincide con el código enviado (simulado)
        if (codigoIngresado === codigoEnviado) {
            // Código correcto
            mensajeDiv.innerHTML = '<span style="color: #28a745;">✅ Código verificado correctamente. Serás redirigido...</span>';
            btnVerificar.disabled = true;
            // Limpiar el correo del localStorage (opcional)
            localStorage.removeItem('correoRegistro');
            // Detener el temporizador
            if (intervalo) {
                clearInterval(intervalo);
                intervalo = null;
            }
            // Redirigir a login después de 2 segundos
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 2000);
        } else {
            // Código incorrecto
            mensajeDiv.innerHTML = '<span style="color: #dc3545;">❌ Código incorrecto. Intenta de nuevo o reenvía el código.</span>';
            inputCodigo.value = '';
            inputCodigo.focus();
        }
    });

    // --- Limpiar mensaje al escribir ---
    inputCodigo.addEventListener('input', function() {
        mensajeDiv.innerHTML = '';
        // Solo permitir dígitos
        this.value = this.value.replace(/\D/g, '');
    });
});