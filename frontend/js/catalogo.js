/**
 * ============================================================================
 * Dinastía AMV - Motor Interactivo del Catálogo Público con Filtros (catalogo.js)
 * UI-006 / HU-04 / RF-002
 * Conectado exclusivamente al Backend y Base de Datos (sin planes precargados)
 * ============================================================================
 */

(function () {
  'use strict';

  // Configuración de Endpoint Backend
  const API_URL = '../backend/api/planes.php';

  // Elementos DOM
  const DOM = {
    // Formulario de Filtros (Desktop & Drawer)
    formFiltros: document.getElementById('formFiltros'),
    inputDestino: document.getElementById('filtroDestino'),
    autocompleteDropdown: document.getElementById('autocompleteDropdown'),
    selectLugarSalida: document.getElementById('filtroLugarSalida'),
    inputPrecioMin: document.getElementById('filtroPrecioMin'),
    inputPrecioMax: document.getElementById('filtroPrecioMax'),
    rangePrecioMax: document.getElementById('rangePrecioMax'),
    valorPrecioMaxDisplay: document.getElementById('valorPrecioMaxDisplay'),
    inputFechaSalida: document.getElementById('filtroFechaSalida'),
    selectOrden: document.getElementById('selectOrden'),
    btnAplicarFiltros: document.getElementById('btnAplicarFiltros'),
    btnLimpiarFiltros: document.getElementById('btnLimpiarFiltros'),

    // Contenedores de Resultados
    gridPlanes: document.getElementById('gridPlanes'),
    skeletonLoader: document.getElementById('skeletonLoader'),
    emptyState: document.getElementById('emptyState'),
    errorState: document.getElementById('errorState'),
    contadorResultados: document.getElementById('contadorResultados'),
    badgeFiltrosActivos: document.getElementById('badgeFiltrosActivos'),

    // Drawer Móvil
    btnAbrirDrawer: document.getElementById('btnAbrirDrawer'),
    btnCerrarDrawer: document.getElementById('btnCerrarDrawer'),
    drawerFiltros: document.getElementById('drawerFiltros'),

    // Modal Detalle
    modalDetalle: document.getElementById('modalDetallePlan'),
    btnCerrarModal: document.getElementById('btnCerrarModalPlan'),
    modalContenido: document.getElementById('modalPlanContenido'),
    btnReservarModal: document.getElementById('btnReservarModal')
  };

  // Variables de Estado
  let planesCargados = [];
  let planSeleccionado = null;
  let debounceTimeout = null;
  let listaDestinosUnicos = [];

  // ==========================================================================
  // 1. UTILIDADES Y FORMATEO
  // ==========================================================================

  function formatearCOP(valor) {
    return new Intl.NumberFormat('es-CO', {
      style: 'currency',
      currency: 'COP',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(valor);
  }

  function sanitizarHTML(str) {
    if (!str) return '';
    const temp = document.createElement('div');
    temp.textContent = str;
    return temp.innerHTML;
  }

  // ==========================================================================
  // 2. PETICIÓN Y CONSUMO EXCLUSIVO DE LA BASE DE DATOS VIA REST API
  // ==========================================================================

  async function consultarPlanes(filtros = {}) {
    mostrarSkeleton(true);
    DOM.emptyState.classList.add('hidden');
    DOM.errorState.classList.add('hidden');

    const params = new URLSearchParams();
    if (filtros.destino) params.append('destino', filtros.destino);
    if (filtros.precio_min) params.append('precio_min', filtros.precio_min);
    if (filtros.precio_max) params.append('precio_max', filtros.precio_max);
    if (filtros.duracion && filtros.duracion !== 'todas') params.append('duracion', filtros.duracion);
    if (filtros.fecha_salida) params.append('fecha_salida', filtros.fecha_salida);
    if (filtros.lugar_salida && filtros.lugar_salida !== 'todos') params.append('lugar_salida', filtros.lugar_salida);
    if (filtros.orden) params.append('orden', filtros.orden);

    // Sincronizar URL para compartir enlaces de búsqueda
    const nuevoQuery = params.toString();
    const nuevaURL = window.location.pathname + (nuevoQuery ? '?' + nuevoQuery : '');
    window.history.replaceState({}, '', nuevaURL);

    actualizarBadgeFiltrosActivos(filtros);

    try {
      const urlPeticion = `${API_URL}?${params.toString()}`;
      const response = await fetch(urlPeticion, { cache: 'no-store' });
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const resJson = await response.json();
      
      if (resJson && resJson.success) {
        planesCargados = resJson.data || [];
        renderizarPlanes(planesCargados);
        actualizarDestinosAutocompletado(planesCargados);
      } else {
        throw new Error(resJson.message || 'Error al obtener planes.');
      }

    } catch (err) {
      console.error('Error al consultar el catálogo desde la base de datos:', err);
      mostrarError();
    } finally {
      mostrarSkeleton(false);
    }
  }

  // ==========================================================================
  // 3. RENDERIZADO DE RESULTADOS DESDE LA BASE DE DATOS
  // ==========================================================================

  function renderizarPlanes(planes) {
    DOM.gridPlanes.innerHTML = '';

    if (!planes || planes.length === 0) {
      DOM.emptyState.classList.remove('hidden');
      DOM.contadorResultados.textContent = '0 planes encontrados';
      return;
    }

    DOM.emptyState.classList.add('hidden');
    DOM.contadorResultados.textContent = `${planes.length} ${planes.length === 1 ? 'plan registrado encontrado' : 'planes registrados encontrados'}`;

    planes.forEach(plan => {
      const card = document.createElement('article');
      card.className = 'group bg-white dark:bg-white/5 rounded-2xl overflow-hidden border border-primary/10 shadow-sm card-hover flex flex-col justify-between';
      card.setAttribute('aria-label', plan.destino);

      // Servicios listados como badges
      const serviciosArray = plan.servicios_incluidos 
        ? plan.servicios_incluidos.split(',').slice(0, 3).map(s => s.trim()) 
        : ['Hospedaje', 'Desayunos', 'Guía'];

      card.innerHTML = `
        <div>
          <!-- Imagen y Badges -->
          <div class="relative w-full aspect-[4/3] overflow-hidden bg-slate-100">
            <img 
              src="${sanitizarHTML(plan.imagen_url)}" 
              alt="${sanitizarHTML(plan.destino)}" 
              class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" 
              loading="lazy"
              onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1583531352515-8884af319dc1?auto=format&fit=crop&w=800&q=80';"
            />
            <div class="absolute top-3 left-3 bg-yellow-400 text-slate-950 font-black px-3 py-1 rounded-lg text-xs shadow-md">
              ${plan.descuento || 'Plan Activo'}
            </div>
            <div class="absolute top-3 right-3 bg-primary text-white font-bold px-2.5 py-0.5 rounded-full text-xs shadow-sm flex items-center gap-1">
              <span class="material-symbols-outlined text-xs">group</span>
              ${plan.cupos || 10} cupos
            </div>
            <div class="absolute bottom-3 left-3 bg-black/65 backdrop-blur-sm text-white px-3 py-1 rounded-full text-xs font-semibold flex items-center gap-1">
              <span class="material-symbols-outlined text-sm text-primary">location_on</span>
              ${sanitizarHTML(plan.departamento || plan.destino)}
            </div>
          </div>

          <!-- Contenido -->
          <div class="p-5">
            <div class="flex items-center justify-between text-xs text-slate-500 mb-1.5 font-medium">
              <span class="flex items-center gap-1">
                <span class="material-symbols-outlined text-xs text-primary">flight_takeoff</span>
                Salida: <strong class="text-slate-700 capitalize">${plan.lugar_salida || 'Medellín'}</strong>
              </span>
              <span class="flex items-center gap-1 bg-purple-50 text-primary px-2 py-0.5 rounded-full font-bold text-[11px]">
                <span class="material-symbols-outlined text-xs">schedule</span>
                ${plan.duracion} días
              </span>
            </div>

            <h3 class="text-lg font-black text-slate-900 leading-snug line-clamp-1 mb-2 group-hover:text-primary transition-colors">
              ${sanitizarHTML(plan.destino)}
            </h3>

            <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed mb-4">
              ${sanitizarHTML(plan.descripcion)}
            </p>

            <!-- Tags de Servicios -->
            <div class="flex flex-wrap gap-1.5 mb-4">
              ${serviciosArray.map(srv => `
                <span class="bg-slate-100 text-slate-600 text-[11px] font-semibold px-2 py-0.5 rounded-md flex items-center gap-0.5">
                  <span class="material-symbols-outlined text-[12px] text-green-600">check</span>
                  ${sanitizarHTML(srv)}
                </span>
              `).join('')}
            </div>

            <!-- Precios -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
              <div>
                <span class="text-[11px] text-slate-400 block line-through leading-none">
                  ${plan.precio_anterior && plan.precio_anterior > plan.precio ? formatearCOP(plan.precio_anterior) : ''}
                </span>
                <span class="text-xs text-slate-500 font-semibold">Tarifa por persona</span>
              </div>
              <span class="text-xl font-black text-primary">
                ${formatearCOP(plan.precio)}
              </span>
            </div>
          </div>
        </div>

        <!-- Botones de Acción -->
        <div class="p-5 pt-0 grid grid-cols-2 gap-2 mt-2">
          <button 
            type="button" 
            class="py-2.5 rounded-full btn-outline text-xs font-bold text-center"
            data-accion="detalles"
            data-id="${plan.id}"
          >
            <span class="material-symbols-outlined text-sm">visibility</span> Ver detalles
          </button>
          
          <button 
            type="button" 
            class="py-2.5 rounded-full gradient-btn text-white font-bold text-xs shadow-md shadow-primary/20 text-center"
            data-accion="reservar"
            data-id="${plan.id}"
          >
            <span class="material-symbols-outlined text-sm">shopping_bag</span> Reservar
          </button>
        </div>
      `;

      // Eventos
      card.querySelector('[data-accion="detalles"]').onclick = () => abrirModalDetalle(plan);
      card.querySelector('[data-accion="reservar"]').onclick = () => ejecutarFlujoReserva(plan);

      DOM.gridPlanes.appendChild(card);
    });
  }

  function mostrarSkeleton(mostrar) {
    if (mostrar) {
      DOM.skeletonLoader.classList.remove('hidden');
      DOM.gridPlanes.classList.add('hidden');
    } else {
      DOM.skeletonLoader.classList.add('hidden');
      DOM.gridPlanes.classList.remove('hidden');
    }
  }

  function mostrarError() {
    DOM.errorState.classList.remove('hidden');
    DOM.gridPlanes.innerHTML = '';
    DOM.contadorResultados.textContent = 'Error al cargar planes';
  }

  // ==========================================================================
  // 4. GESTIÓN DEL MODAL DE DETALLES
  // ==========================================================================

  function abrirModalDetalle(plan) {
    planSeleccionado = plan;

    const servicios = plan.servicios_incluidos 
      ? plan.servicios_incluidos.split(',').map(s => s.trim()) 
      : ['Tiquetes o traslados', 'Alojamiento seleccionado', 'Desayunos diarios', 'Guianza turística', 'Asistencia médica'];

    DOM.modalContenido.innerHTML = `
      <div class="relative h-56 w-full rounded-2xl overflow-hidden mb-4 shadow-sm">
        <img src="${sanitizarHTML(plan.imagen_url)}" alt="${sanitizarHTML(plan.destino)}" class="w-full h-full object-cover" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1583531352515-8884af319dc1?auto=format&fit=crop&w=800&q=80';"/>
        <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/20 to-transparent flex items-end p-5">
          <div>
            <span class="text-xs font-bold text-yellow-400 uppercase tracking-widest">${plan.departamento || 'Colombia'}</span>
            <h3 class="text-2xl font-black text-white leading-tight">${sanitizarHTML(plan.destino)}</h3>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 bg-purple-50/60 p-3.5 rounded-xl text-xs mb-4 border border-primary/10">
        <div>
          <span class="text-slate-400 block font-semibold text-[11px]">Duración</span>
          <strong class="text-slate-800 text-sm font-black">${plan.duracion} Días / ${Math.max(1, plan.duracion - 1)} Noches</strong>
        </div>
        <div>
          <span class="text-slate-400 block font-semibold text-[11px]">Origen</span>
          <strong class="text-slate-800 text-sm font-black capitalize">${plan.lugar_salida || 'Medellín'}</strong>
        </div>
        <div>
          <span class="text-slate-400 block font-semibold text-[11px]">Fecha Salida</span>
          <strong class="text-slate-800 text-sm font-black">${plan.fecha_salida}</strong>
        </div>
        <div>
          <span class="text-slate-400 block font-semibold text-[11px]">Cupos Disponibles</span>
          <strong class="text-primary text-sm font-black">${plan.cupos || 10} cupos</strong>
        </div>
      </div>

      <div class="mb-4">
        <h4 class="text-sm font-extrabold text-slate-900 mb-1.5 flex items-center gap-1.5">
          <span class="material-symbols-outlined text-primary text-base">description</span>
          Descripción de la Experiencia
        </h4>
        <p class="text-xs text-slate-600 leading-relaxed bg-white p-3 rounded-xl border border-slate-100">
          ${sanitizarHTML(plan.descripcion)}
        </p>
      </div>

      <div class="mb-4">
        <h4 class="text-sm font-extrabold text-slate-900 mb-1.5 flex items-center gap-1.5">
          <span class="material-symbols-outlined text-primary text-base">hotel</span>
          Hospedaje Incluido
        </h4>
        <p class="text-xs text-slate-700 bg-slate-50 p-3 rounded-xl border border-slate-200">
          <strong>${sanitizarHTML(plan.alojamiento || 'Hotel Seleccionado')}</strong> con acomodación según plan.
        </p>
      </div>

      <div>
        <h4 class="text-sm font-extrabold text-slate-900 mb-2 flex items-center gap-1.5">
          <span class="material-symbols-outlined text-green-600 text-base">verified</span>
          Servicios Incluidos en la Tarifa
        </h4>
        <ul class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-slate-600">
          ${servicios.map(srv => `
            <li class="flex items-center gap-2 bg-slate-50 p-2 rounded-lg border border-slate-100">
              <span class="material-symbols-outlined text-green-600 text-base">check_circle</span>
              <span class="font-medium">${sanitizarHTML(srv)}</span>
            </li>
          `).join('')}
        </ul>
      </div>

      <div class="mt-5 p-4 rounded-xl bg-gradient-to-r from-purple-50 to-pink-50 border border-primary/20 flex items-center justify-between">
        <div>
          <span class="text-xs text-slate-500 font-bold block">Tarifa por persona:</span>
          <span class="text-2xl font-black text-primary">${formatearCOP(plan.precio)}</span>
        </div>
        <button 
          id="btnReservarDesdeModal" 
          type="button" 
          class="px-6 py-3 rounded-full gradient-btn text-white text-xs font-black shadow-lg shadow-primary/30"
        >
          <span class="material-symbols-outlined text-sm">shopping_bag</span>
          Reservar Este Plan
        </button>
      </div>
    `;

    document.getElementById('btnReservarDesdeModal').onclick = () => ejecutarFlujoReserva(plan);
    DOM.modalDetalle.classList.add('active');
  }

  function cerrarModalDetalle() {
    DOM.modalDetalle.classList.remove('active');
    planSeleccionado = null;
  }

  function ejecutarFlujoReserva(plan) {
    const sesionToken = localStorage.getItem('dinastia_auth_jwt_token') || sessionStorage.getItem('dinastia_auth_jwt_token');
    const urlDestino = `pagos.html?viaje_id=${plan.id}&plan_id=${plan.id}&destino=${encodeURIComponent(plan.destino)}&precio=${plan.precio}`;
    
    if (!sesionToken) {
      sessionStorage.setItem('reserva_pendiente', JSON.stringify(plan));
    }
    
    window.location.href = urlDestino;
  }

  // ==========================================================================
  // 5. CAPTURA Y GESTIÓN DE FILTROS
  // ==========================================================================

  function obtenerFiltrosFormulario() {
    const duracionSeleccionada = document.querySelector('input[name="duracion"]:checked')?.value || 'todas';
    const precioMax = parseFloat(DOM.rangePrecioMax.value);

    return {
      destino: DOM.inputDestino.value.trim(),
      lugar_salida: DOM.selectLugarSalida.value,
      precio_min: DOM.inputPrecioMin.value ? parseFloat(DOM.inputPrecioMin.value) : 0,
      precio_max: precioMax,
      duracion: duracionSeleccionada,
      fecha_salida: DOM.inputFechaSalida.value,
      orden: DOM.selectOrden.value
    };
  }

  function aplicarFiltros() {
    const filtros = obtenerFiltrosFormulario();
    consultarPlanes(filtros);
    cerrarDrawer();
  }

  function limpiarFiltros() {
    DOM.formFiltros.reset();
    DOM.inputDestino.value = '';
    DOM.selectLugarSalida.value = 'todos';
    DOM.inputPrecioMin.value = '';
    DOM.inputPrecioMax.value = '2000000';
    DOM.rangePrecioMax.value = '2000000';
    DOM.valorPrecioMaxDisplay.textContent = formatearCOP(2000000);
    DOM.inputFechaSalida.value = '';
    DOM.selectOrden.value = 'destacados';

    const radioTodas = document.querySelector('input[name="duracion"][value="todas"]');
    if (radioTodas) radioTodas.checked = true;

    consultarPlanes({});
    cerrarDrawer();
  }

  function actualizarBadgeFiltrosActivos(filtros) {
    let activos = 0;
    if (filtros.destino) activos++;
    if (filtros.lugar_salida && filtros.lugar_salida !== 'todos') activos++;
    if (filtros.precio_min && filtros.precio_min > 0) activos++;
    if (filtros.precio_max && filtros.precio_max < 2000000) activos++;
    if (filtros.duracion && filtros.duracion !== 'todas') activos++;
    if (filtros.fecha_salida) activos++;

    if (DOM.badgeFiltrosActivos) {
      if (activos > 0) {
        DOM.badgeFiltrosActivos.textContent = activos;
        DOM.badgeFiltrosActivos.classList.remove('hidden');
      } else {
        DOM.badgeFiltrosActivos.classList.add('hidden');
      }
    }
  }

  // ==========================================================================
  // 6. AUTOCOMPLETADO DINÁMICO DESDE LA BASE DE DATOS
  // ==========================================================================

  function actualizarDestinosAutocompletado(planes) {
    if (!planes || planes.length === 0) return;
    const destinos = planes.map(p => p.destino.trim()).filter(Boolean);
    listaDestinosUnicos = Array.from(new Set(destinos));
  }

  function configurarAutocompletado() {
    DOM.inputDestino.addEventListener('input', (e) => {
      const q = e.target.value.trim().toLowerCase();
      if (!q || q.length < 2) {
        DOM.autocompleteDropdown.classList.remove('show');
        return;
      }

      const coincidencias = listaDestinosUnicos.filter(d => d.toLowerCase().includes(q));
      if (coincidencias.length === 0) {
        DOM.autocompleteDropdown.classList.remove('show');
        return;
      }

      DOM.autocompleteDropdown.innerHTML = coincidencias.map(d => `
        <div class="autocomplete-item" data-value="${sanitizarHTML(d)}">
          <span class="material-symbols-outlined text-sm text-primary">pin_drop</span>
          <span>${sanitizarHTML(d)}</span>
        </div>
      `).join('');

      DOM.autocompleteDropdown.classList.add('show');

      DOM.autocompleteDropdown.querySelectorAll('.autocomplete-item').forEach(item => {
        item.onclick = () => {
          DOM.inputDestino.value = item.dataset.value;
          DOM.autocompleteDropdown.classList.remove('show');
          aplicarFiltros();
        };
      });
    });

    document.addEventListener('click', (e) => {
      if (!DOM.inputDestino.contains(e.target) && !DOM.autocompleteDropdown.contains(e.target)) {
        DOM.autocompleteDropdown.classList.remove('show');
      }
    });
  }

  // ==========================================================================
  // 7. DRAWER RESPONSIVO MÓVIL
  // ==========================================================================

  function abrirDrawer() {
    DOM.drawerFiltros.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function cerrarDrawer() {
    DOM.drawerFiltros.classList.remove('active');
    document.body.style.overflow = '';
  }

  // ==========================================================================
  // 8. INICIALIZACIÓN DE EVENTOS Y URL PARAMS
  // ==========================================================================

  function registrarEventos() {
    DOM.formFiltros.addEventListener('submit', (e) => {
      e.preventDefault();
      aplicarFiltros();
    });

    DOM.btnAplicarFiltros.addEventListener('click', aplicarFiltros);
    DOM.btnLimpiarFiltros.addEventListener('click', limpiarFiltros);

    DOM.rangePrecioMax.addEventListener('input', (e) => {
      const valor = parseFloat(e.target.value);
      DOM.valorPrecioMaxDisplay.textContent = formatearCOP(valor);
      DOM.inputPrecioMax.value = valor;
    });

    DOM.inputPrecioMax.addEventListener('input', (e) => {
      const valor = parseFloat(e.target.value) || 2000000;
      DOM.rangePrecioMax.value = valor;
      DOM.valorPrecioMaxDisplay.textContent = formatearCOP(valor);
    });

    DOM.inputDestino.addEventListener('keyup', () => {
      clearTimeout(debounceTimeout);
      debounceTimeout = setTimeout(() => {
        aplicarFiltros();
      }, 350);
    });

    DOM.selectLugarSalida.addEventListener('change', aplicarFiltros);
    DOM.selectOrden.addEventListener('change', aplicarFiltros);
    DOM.inputFechaSalida.addEventListener('change', aplicarFiltros);

    document.querySelectorAll('input[name="duracion"]').forEach(radio => {
      radio.addEventListener('change', aplicarFiltros);
    });

    if (DOM.btnAbrirDrawer) DOM.btnAbrirDrawer.addEventListener('click', abrirDrawer);
    if (DOM.btnCerrarDrawer) DOM.btnCerrarDrawer.addEventListener('click', cerrarDrawer);
    DOM.drawerFiltros.addEventListener('click', (e) => {
      if (e.target === DOM.drawerFiltros) cerrarDrawer();
    });

    if (DOM.btnCerrarModal) DOM.btnCerrarModal.addEventListener('click', cerrarModalDetalle);
    DOM.modalDetalle.addEventListener('click', (e) => {
      if (e.target === DOM.modalDetalle) cerrarModalDetalle();
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        cerrarModalDetalle();
        cerrarDrawer();
      }
    });

    configurarAutocompletado();
  }

  function cargarFiltrosDesdeURL() {
    const urlParams = new URLSearchParams(window.location.search);
    const filtrosIniciales = {};

    if (urlParams.has('destino')) {
      DOM.inputDestino.value = urlParams.get('destino');
      filtrosIniciales.destino = urlParams.get('destino');
    }
    if (urlParams.has('lugar_salida')) {
      DOM.selectLugarSalida.value = urlParams.get('lugar_salida');
      filtrosIniciales.lugar_salida = urlParams.get('lugar_salida');
    }
    if (urlParams.has('precio_min')) {
      DOM.inputPrecioMin.value = urlParams.get('precio_min');
      filtrosIniciales.precio_min = parseFloat(urlParams.get('precio_min'));
    }
    if (urlParams.has('precio_max')) {
      const pMax = parseFloat(urlParams.get('precio_max'));
      DOM.inputPrecioMax.value = pMax;
      DOM.rangePrecioMax.value = pMax;
      DOM.valorPrecioMaxDisplay.textContent = formatearCOP(pMax);
      filtrosIniciales.precio_max = pMax;
    }
    if (urlParams.has('duracion')) {
      const dur = urlParams.get('duracion');
      const radio = document.querySelector(`input[name="duracion"][value="${dur}"]`);
      if (radio) radio.checked = true;
      filtrosIniciales.duracion = dur;
    }
    if (urlParams.has('fecha_salida')) {
      DOM.inputFechaSalida.value = urlParams.get('fecha_salida');
      filtrosIniciales.fecha_salida = urlParams.get('fecha_salida');
    }
    if (urlParams.has('orden')) {
      DOM.selectOrden.value = urlParams.get('orden');
      filtrosIniciales.orden = urlParams.get('orden');
    }

    consultarPlanes(filtrosIniciales);
  }

  function iniciar() {
    registrarEventos();
    cargarFiltrosDesdeURL();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciar);
  } else {
    iniciar();
  }

})();
