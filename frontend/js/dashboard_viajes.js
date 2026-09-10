function escaparHtml(valor) {
  return String(valor ?? '').replace(/[&<>"']/g, (c) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  }[c]));
}

function formatearFecha(fecha) {
  if (!fecha) return 'Fecha por confirmar';
  const [anio, mes, dia] = fecha.split('-');
  return `${dia}/${mes}/${anio}`;
}

function crearTarjetaViaje(viaje) {
  const imagen = viaje.imagenes?.length
    ? `imagenes/${encodeURIComponent(viaje.imagenes[0].url)}`
    : 'https://via.placeholder.com/600x400?text=Sin+imagen';

  const precio = Number(viaje.precio).toLocaleString('es-CO', {
    style: 'currency',
    currency: 'COP',
    minimumFractionDigits: 0
  });

  return `
    <article class="group bg-white rounded-2xl overflow-hidden border border-primary/10 shadow-sm card-hover">
      <img
        src="${imagen}"
        alt="${escaparHtml(viaje.destino)}"
        class="w-full aspect-[4/3] object-cover"
        onerror="this.src='https://via.placeholder.com/600x400?text=Sin+imagen'"
      >

      <div class="p-4">
        <h4 class="text-lg font-bold">${escaparHtml(viaje.destino)}</h4>

        <p class="text-sm text-slate-500 mt-1">
          ${escaparHtml(viaje.descripcion || 'Plan turístico Dinastía AMV')}
        </p>

        <p class="text-xs text-slate-500 mt-3">
          ${formatearFecha(viaje.fecha_salida)} - ${formatearFecha(viaje.fecha_regreso)}
        </p>

        <p class="text-xl font-black text-primary mt-3">${precio}</p>

        <a
          href="pagos.html?viaje_id=${encodeURIComponent(viaje.id)}"
          class="mt-4 w-full py-2.5 rounded-full gradient-btn text-white font-bold text-sm block text-center"
        >
          Reservar y pagar
        </a>
      </div>
    </article>
  `;
}

async function cargarViajesDashboard() {
  const contenedor = document.getElementById('listaDestinos');

  try {
    const respuesta = await fetch('../backend/api/listar_viajes_publicos.php', {
      cache: 'no-store'
    });

    const datos = await respuesta.json();

    if (!respuesta.ok || !datos.success) {
      throw new Error(datos.error || 'No fue posible cargar los viajes.');
    }

    if (!datos.viajes?.length) {
      contenedor.innerHTML = '<p class="text-slate-500">Aún no hay viajes disponibles.</p>';
      return;
    }

    contenedor.innerHTML = datos.viajes.map(crearTarjetaViaje).join('');
  } catch (error) {
    contenedor.innerHTML = `
      <p class="text-red-600">
        Error al cargar viajes: ${escaparHtml(error.message)}
      </p>
    `;
  }
}

document.addEventListener('DOMContentLoaded', cargarViajesDashboard);