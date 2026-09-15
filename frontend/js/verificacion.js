document.addEventListener('DOMContentLoaded', async function () {
    const form = document.getElementById('formVerificacion');
    const inputCodigo = document.getElementById('codigoVerificacion');
    const mensajeDiv = document.getElementById('mensajeVerificacion');
    const correoDestino = document.getElementById('correoDestino');
    const btnVerificar = document.getElementById('btnVerificar');
    const btnReenviar = document.getElementById('btnReenviar');
    const mensajeReenvio = document.getElementById('mensajeReenvio');
    const contadorSpan = document.getElementById('contador');

    try {
        const response = await fetch('../backend/api/estado_verificacion.php', {
            cache: 'no-store',
            credentials: 'same-origin'
        });
        const resultado = await response.json();
        if (!response.ok || !resultado.success) {
            window.location.href = 'Registro.html';
            return;
        }
        correoDestino.textContent = `📧 ${resultado.correo}`;
    } catch (error) {
        window.location.href = 'Registro.html';
        return;
    }

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
                mensajeReenvio.textContent = 'Ya puedes reenviar el código.';
            }
        }, 1000);
    }

    btnReenviar.addEventListener('click', async function () {
        btnReenviar.disabled = true;
        mensajeReenvio.textContent = 'Enviando código…';
        try {
            const response = await fetch('../backend/api/reenviar_codigo.php', {
                method: 'POST',
                credentials: 'same-origin'
            });
            const resultado = await response.json();
            if (!response.ok || !resultado.success) {
                throw new Error(resultado.error || 'No fue posible reenviar el código.');
            }
            tiempoRestante = 60;
            mensajeReenvio.textContent = resultado.message;
            iniciarTemporizador();
        } catch (error) {
            mensajeReenvio.textContent = error.message;
            btnReenviar.disabled = false;
        }
    });

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        const codigo = inputCodigo.value.trim();
        if (!/^\d{6}$/.test(codigo)) {
            mensajeDiv.textContent = 'Ingresa un código válido de seis dígitos.';
            return;
        }

        btnVerificar.disabled = true;
        mensajeDiv.textContent = 'Verificando…';
        try {
            const formData = new FormData();
            formData.append('codigo', codigo);
            const response = await fetch('../backend/api/verificar_codigo.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            const resultado = await response.json();
            if (!response.ok || !resultado.success) {
                throw new Error(resultado.error || 'No fue posible verificar el código.');
            }

            mensajeDiv.textContent = resultado.message;
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 1200);
        } catch (error) {
            mensajeDiv.textContent = error.message;
            inputCodigo.value = '';
            inputCodigo.focus();
            btnVerificar.disabled = false;
        }
    });

    inputCodigo.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '');
        mensajeDiv.textContent = '';
    });

    iniciarTemporizador();
});
