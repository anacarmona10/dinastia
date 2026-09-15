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
    : 'https://images.unsplash.com/photo-1583531352515-8884af319dc1?auto=format&fit=crop&w=800&q=80';

  const precio = Number(viaje.precio).toLocaleString('es-CO', {
    style: 'currency',
    currency: 'COP',
    minimumFractionDigits: 0
  });

  return `
    <article class="group bg-white rounded-2xl overflow-hidden border border-primary/15 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between card-hover">
      <div>
        <!-- Imagen y Badges estilo Catálogo -->
        <div class="relative w-full aspect-[4/3] overflow-hidden bg-slate-100">
          <img
            src="${imagen}"
            alt="${escaparHtml(viaje.destino)}"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
            onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1583531352515-8884af319dc1?auto=format&fit=crop&w=800&q=80';"
          >
          <div class="absolute top-3 right-3 bg-primary text-white font-bold px-2.5 py-0.5 rounded-full text-xs shadow-sm flex items-center gap-1">
            <span class="material-symbols-outlined text-xs">group</span>
            10 cupos
          </div>
          <div class="absolute bottom-3 left-3 bg-black/65 backdrop-blur-sm text-white px-3 py-1 rounded-full text-xs font-semibold flex items-center gap-1">
            <span class="material-symbols-outlined text-sm text-primary">location_on</span>
            ${escaparHtml(viaje.destino)}
          </div>
        </div>

        <!-- Contenido -->
        <div class="p-5">
          <div class="flex items-center justify-between text-xs text-slate-500 mb-2 font-medium">
            <span class="flex items-center gap-1">
              <span class="material-symbols-outlined text-xs text-primary">flight_takeoff</span>
              Salida: <strong class="text-slate-700">${formatearFecha(viaje.fecha_salida)}</strong>
            </span>
            <span class="flex items-center gap-1 bg-purple-50 text-primary px-2 py-0.5 rounded-full font-bold text-[11px]">
              <span class="material-symbols-outlined text-xs">schedule</span>
              Hasta ${formatearFecha(viaje.fecha_regreso)}
            </span>
          </div>

          <h4 class="text-lg font-black text-slate-900 line-clamp-1 mb-2 group-hover:text-primary transition-colors">
            ${escaparHtml(viaje.destino)}
          </h4>

          <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed mb-4">
            ${escaparHtml(viaje.descripcion || 'Plan turístico Dinastía AMV con tiquetes, hospedaje y actividades incluidas.')}
          </p>

          <!-- Precios -->
          <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
            <div>
              <span class="text-xs text-slate-400 font-semibold block">Tarifa por persona</span>
            </div>
            <span class="text-xl font-black text-primary">${precio}</span>
          </div>
        </div>
      </div>

      <!-- Botones de Acción idénticos al Catálogo -->
      <div class="p-5 pt-0 grid grid-cols-2 gap-2 mt-2">
        <a
          href="catalogo.html"
          class="py-2.5 rounded-full btn-outline text-xs font-bold text-center flex items-center justify-center gap-1"
        >
          <span class="material-symbols-outlined text-sm">visibility</span> Ver catálogo
        </a>

        <a
          href="pagos.html?viaje_id=${encodeURIComponent(viaje.id)}"
          class="py-2.5 rounded-full gradient-btn text-white font-bold text-xs shadow-md shadow-primary/20 text-center flex items-center justify-center gap-1"
        >
          <span class="material-symbols-outlined text-sm">shopping_bag</span> Reservar
        </a>
      </div>
    </article>
  `;
}

async function cargarViajesDashboard() {
  const contenedor = document.getElementById('listaDestinos');

  try {
    // Intentar consultar reservas reales del usuario para la métrica
    try {
      const respReservas = await fetch('../backend/api/obtener_reservas.php');
      if (respReservas.ok) {
        const datosRes = await respReservas.json();
        if (datosRes.ok && Array.isArray(datosRes.reservas)) {
          const activas = datosRes.reservas.filter(r => r.estado === 'pagada' || r.estado === 'pendiente').length;
          const pasadas = datosRes.reservas.filter(r => r.tipo === 'pasados').length;
          const elReservas = document.getElementById('statReservas');
          const elViajes = document.getElementById('statViajes');
          if (elReservas) elReservas.textContent = activas;
          if (elViajes) elViajes.textContent = pasadas;
        }
      }
    } catch (_) {
      // Ignorar si no hay sesión activa
    }

    const respuesta = await fetch('../backend/api/listar_viajes_publicos.php', {
      cache: 'no-store'
    });

    const datos = await respuesta.json();

    if (!respuesta.ok || !datos.success) {
      throw new Error(datos.error || 'No fue posible cargar los viajes.');
    }

    if (!datos.viajes?.length) {
      contenedor.innerHTML = '<p class="text-slate-500 col-span-full py-8 text-center">Aún no hay viajes disponibles.</p>';
      return;
    }

    contenedor.innerHTML = datos.viajes.map(crearTarjetaViaje).join('');
  } catch (error) {
    contenedor.innerHTML = `
      <p class="text-red-600 col-span-full py-8 text-center">
        Error al cargar viajes: ${escaparHtml(error.message)}
      </p>
    `;
  }
}

document.addEventListener('DOMContentLoaded', cargarViajesDashboard);