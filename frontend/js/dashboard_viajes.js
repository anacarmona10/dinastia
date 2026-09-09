function escaparHtml(valor) {
  return String(valor ?? '').replace(/[&<>"']/g, (caracter) => {
    const caracteres = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    };

    return caracteres[caracter];
  });
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
    <div class="group bg-white dark:bg-white/5 rounded-2xl overflow-hidden border border-primary/10 shadow-sm card-hover">
      <div class="relative w-full aspect-[4/3] overflow-hidden">
        <img
          src="${imagen}"
          alt="${escaparHtml(viaje.destino)}"
          class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
          onerror="this.src='https://via.placeholder.com/600x400?text=Sin+imagen'"
        >
      </div>

      <div class="p-4">
        <div class="flex justify-between items-start gap-4 mb-2">
          <div>
            <h4 class="text-lg font-bold">${escaparHtml(viaje.destino)}</h4>
            <p class="text-sm text-slate-500 dark:text-slate-400">
              ${escaparHtml(viaje.descripcion || 'Plan turístico Dinastía AMV')}
            </p>
          </div>

          <div class="text-right shrink-0">
            <p class="text-xl font-black text-primary">${precio}</p>
          </div>
        </div>

        <p class="text-xs text-slate-500 mb-3">
          ${formatearFecha(viaje.fecha_salida)} - ${formatearFecha(viaje.fecha_regreso)}
        </p>

        <a
          href="pagos.html?viaje_id=${viaje.id}"
          class="w-full py-2.5 rounded-full gradient-btn text-white font-bold text-sm block text-center shadow-lg shadow-primary/30"
        >
          Reservar y pagar
        </a>
      </div>
    </div>
  `;
}

async function cargarViajesDashboard() {
  const contenedor = document.getElementById('listaDestinos');

  try {
    const respuesta = await fetch('../backend/api/listar_viajes_publicos.php');
    const datos = await respuesta.json();

    if (!respuesta.ok || !datos.success) {
      throw new Error(datos.error || 'No fue posible cargar los viajes.');
    }

    if (datos.viajes.length === 0) {
      contenedor.innerHTML = '<p class="text-slate-500">Aún no hay viajes disponibles.</p>';
      return;
    }

    contenedor.innerHTML = datos.viajes.map(crearTarjetaViaje).join('');
  } catch (error) {
    contenedor.innerHTML = `
      <p class="text-red-600">
        Error al cargar los viajes: ${escaparHtml(error.message)}
      </p>
    `;
  }
}

document.addEventListener('DOMContentLoaded', cargarViajesDashboard);