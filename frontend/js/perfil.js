const formulario = document.getElementById('formPerfil');
const mensaje = document.getElementById('mensajePerfil');

function mostrarMensaje(texto, tipo = 'error') {
    mensaje.textContent = texto;
    mensaje.className = tipo === 'exito'
        ? 'mt-4 rounded-xl bg-green-100 px-4 py-3 text-sm font-semibold text-green-700'
        : 'mt-4 rounded-xl bg-red-100 px-4 py-3 text-sm font-semibold text-red-700';
}

async function cargarPerfil() {
    try {
        const respuesta = await fetch('../backend/api/obtener_perfil.php', {
            credentials: 'same-origin'
        });

        if (respuesta.status === 401) {
            window.location.href = 'login.html';
            return;
        }

        const datos = await respuesta.json();

        if (!datos.ok) {
            mostrarMensaje(datos.mensaje);
            return;
        }

        const usuario = datos.usuario;

        document.getElementById('nombreCompleto').value = usuario.nombre_completo ?? '';
        document.getElementById('tipoDocumento').value = usuario.tipo_documento ?? '';
        document.getElementById('numeroDocumento').value = usuario.numero_documento ?? '';
        document.getElementById('correo').value = usuario.correo ?? '';

    } catch (error) {
        mostrarMensaje('No se pudo cargar tu información. Intenta nuevamente.');
    }
}

formulario.addEventListener('submit', async (evento) => {
    evento.preventDefault();

    const boton = document.getElementById('botonGuardar');
    boton.disabled = true;
    boton.textContent = 'Guardando...';

    const datos = {
        nombreCompleto: document.getElementById('nombreCompleto').value.trim(),
        tipoDocumento: document.getElementById('tipoDocumento').value,
        numeroDocumento: document.getElementById('numeroDocumento').value.trim(),
        correo: document.getElementById('correo').value.trim()
    };

    try {
        const respuesta = await fetch('../backend/api/actualizar_perfil.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(datos)
        });

        const resultado = await respuesta.json();

        if (!resultado.ok) {
            mostrarMensaje(resultado.mensaje);
            return;
        }

        mostrarMensaje(resultado.mensaje, 'exito');

    } catch (error) {
        mostrarMensaje('No se pudo guardar la información. Intenta nuevamente.');
    } finally {
        boton.disabled = false;
        boton.textContent = 'Guardar cambios';
    }
});

cargarPerfil();