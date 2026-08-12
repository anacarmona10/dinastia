// verificacion.js - Con conexión real al backend

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formVerificacion');
    const inputCodigo = document.getElementById('codigoVerificacion');
    const mensajeDiv = document.getElementById('mensajeVerificacion');
    const correoDestino = document.getElementById('correoDestino');
    const btnVerificar = document.getElementById('btnVerificar');
    const btnReenviar = document.getElementById('btnReenviar');
    const mensajeReenvio = document.getElementById('mensajeReenvio');
    const contadorSpan = document.getElementById('contador');

    // Obtener correo almacenado en localStorage (lo puso Registro.html)
    const correo = localStorage.getItem('correoRegistro');
    if (!correo) {
        // Si no hay correo, redirigir a registro
        window.location.href = 'Registro.html';
        return;
    }
    correoDestino.textContent = `📧 ${correo}`;

    // --- Temporizador (solo frontend, el backend también controla límites) ---
    let tiempoRestante = 60;
    let intervalo = null;

    function iniciarTemporizador() {
        if (intervalo) clearInterval(intervalo);
        btnReenviar.disabled = true;
        contadorSpan.textContent = tiempoRestante;
        intervalo = setInterval(() => {
            tiempoRestante--;
            contadorSpan.textContent = tiempoRestante;
            if (tiempoRestante <= 0) {
                clearInterval(intervalo);
                intervalo = null;
                btnReenviar.disabled = false;
                mensajeReenvio.innerHTML = '<span style="color: #c800ff;">✅ Ya puedes reenviar el código.</span>';
                setTimeout(() => { mensajeReenvio.innerHTML = ''; }, 3000);
            }
        }, 1000);
    }

    function reiniciarTemporizador() {
        if (intervalo) clearInterval(intervalo);
        tiempoRestante = 60;
        iniciarTemporizador();
    }

    // Al cargar la página, pedir al backend que envíe el código (si no se ha enviado)
    async function solicitarCodigoInicial() {
        try {
            const formData = new FormData();
            formData.append('correo', correo);
            const response = await fetch('../backend/api/enviar_codigo_inicial.php', {
                method: 'POST',
                body: formData
            });
            const resultado = await response.json();
            if (!resultado.success) {
                mensajeReenvio.innerHTML = `<span style="color: #dc3545;">${resultado.error}</span>`;
            } else {
                console.log('Código enviado correctamente');
            }
        } catch (error) {
            console.error('Error al solicitar código:', error);
        }
    }
    solicitarCodigoInicial();
    iniciarTemporizador(); // empieza el contador de 60s

    // --- Evento Reenviar código ---
    btnReenviar.addEventListener('click', async function() {
        btnReenviar.disabled = true;
        btnReenviar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

        try {
            const formData = new FormData();
            formData.append('correo', correo);
            const response = await fetch('../backend/api/reenviar_codigo.php', {
                method: 'POST',
                body: formData
            });
            const resultado = await response.json();
            if (resultado.success) {
                mensajeReenvio.innerHTML = `<span style="color: #28a745;">✅ ${resultado.message}</span>`;
                reiniciarTemporizador(); // reinicia el contador de 60s
            } else {
                mensajeReenvio.innerHTML = `<span style="color: #dc3545;">${resultado.error}</span>`;
            }
        } catch (error) {
            mensajeReenvio.innerHTML = `<span style="color: #dc3545;">Error de conexión con el servidor</span>`;
        } finally {
            btnReenviar.innerHTML = '<i class="fas fa-redo-alt"></i> Reenviar código';
            // Si el temporizador ya lo habilita, no lo deshabilitamos aquí
        }
    });

    // --- Evento Verificar código ---
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const codigo = inputCodigo.value.trim();
        if (codigo === '' || !/^\d{6}$/.test(codigo)) {
            mensajeDiv.innerHTML = '<span style="color: #dc3545;">Ingresa un código de 6 dígitos válido.</span>';
            return;
        }

        btnVerificar.disabled = true;
        mensajeDiv.innerHTML = '<span style="color: #555;">Verificando...</span>';

        try {
            const formData = new FormData();
            formData.append('correo', correo);
            formData.append('codigo', codigo);
            const response = await fetch('../backend/api/verificar_codigo.php', {
                method: 'POST',
                body: formData
            });
            const resultado = await response.json();

            if (resultado.success) {
                mensajeDiv.innerHTML = `<span style="color: #28a745;">✅ ${resultado.message}</span>`;
                localStorage.removeItem('correoRegistro');
                // Redirigir a login después de 2 segundos
                setTimeout(() => {
                    window.location.href = 'login.html';
                }, 2000);
            } else {
                mensajeDiv.innerHTML = `<span style="color: #dc3545;">${resultado.error}</span>`;
                inputCodigo.value = '';
                inputCodigo.focus();
                btnVerificar.disabled = false;
            }
        } catch (error) {
            mensajeDiv.innerHTML = `<span style="color: #dc3545;">Error de conexión con el servidor</span>`;
            btnVerificar.disabled = false;
        }
    });

    // Limpiar mensajes al escribir
    inputCodigo.addEventListener('input', function() {
        mensajeDiv.innerHTML = '';
        this.value = this.value.replace(/\D/g, '');
    });
});