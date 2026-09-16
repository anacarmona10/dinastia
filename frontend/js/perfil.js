/**
 * ============================================================================
 * Dinastía AMV - Sistema de Perfil de Usuario Estandarizado (perfil.js)
 * Estilo unificado con dashboard.html, index.html y pagos.html
 * ============================================================================
 */

(function () {
  'use strict';

  // ==========================================================================
  // 1. ESTADO Y SERVICIO DE DATOS (MOCK BACKEND & LOCAL STORAGE)
  // ==========================================================================

  const STORAGE_KEYS = {
    USER_PROFILE: 'dinastia_user_profile',
    RESERVAS: 'dinastia_reservas',
    AUTH_TOKEN: 'dinastia_auth_jwt_token',
    USER_ROLE: 'dinastia_user_role'
  };

  const DEFAULT_USER = {
    id: 101,
    nombreCompleto: 'Ana María Valencia',
    tipoDocumento: 'CC',
    numeroDocumento: '1023456789',
    correo: 'anamaria.valencia@dinastia.com',
    telefono: '+57 312 456 7890',
    ciudad: 'Bogotá D.C., Colombia',
    rol: 'usuario',
    passwordHash: 'Demo123*',
    fechaRegistro: '15 de enero de 2024'
  };

  // =========================================================================
  // Estados soportados: 'pagado' | 'pendiente de pago' | 'no pagado' |
  //                     'en proceso de pago' | 'cancelado'
  // =========================================================================
  const DEFAULT_RESERVAS = [
    {
      id: 'RES-001',
      codigo: 'AMV-2026-CTG-882',
      destino: 'Cartagena de Indias',
      departamento: 'Bolívar · Costa Caribe',
      imagen: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBvExoATYYi5sDh08TyPPpKcGBFiwHlycMmkBk5FC0OdZHqlImdFAXBLAxvA5dJzz0UzTvI6sns2Y4gg9yNq5PC5vDCQsjUqUxP3MrXpKQ0vcjYULysOsHoF1LCxQIAYzrcAXOsFo0_NpASTBhkFMThKRPE9WDT3fv2k8u5TF4thJQgKsXAX-AG_XlUx_uqxTAjmwvEcFJoKXsSvJ49YJIye1LHjRQw13CrFFvBDC8WvO78oaKxO8_9D9YyzfgoboksblDE0tcvsEo',
      fechaSalida: '2026-10-12',
      fechaRegreso: '2026-10-16',
      fechasFormato: '12-16 Oct · 4 noches',
      personas: 2,
      personasTexto: '2 personas',
      estado: 'pagado',
      tipoTab: 'proximos',
      descuento: '-35% Dcto',
      precioAnterior: '$850.000',
      alojamiento: 'Hotel Boutique Las Carretas',
      incluye: [
        'Tiquetes aéreos ida y vuelta',
        'Hospedaje 4 noches con desayunos buffet',
        'Tour en lancha rápida a Islas del Rosario',
        'Pasadía en Isla Cholón con almuerzo típico',
        'Asistencia médica y seguro de viaje'
      ],
      desglose: {
        tarifaBase: 462100,
        impuestosIva: 87800,
        totalCOP: 549900
      },
      metodoPago: 'PSE - Bancolombia',
      fechaPago: '2026-08-15 14:22'
    },
    {
      id: 'RES-002',
      codigo: 'AMV-2026-EJE-419',
      destino: 'Eje Cafetero',
      departamento: 'Quindío · Paisaje Cafetero',
      imagen: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCvPrz-UJLgGXIH0Xj9xFFkT2pAErtARb9_kUsDaWTeNYoK90WuIr-r4f2Z5NE5T77dRYqDNb4PGgKUraUCh0Hnvx1jS8_zSwPsWNzowYY8gwjFPiVUfmWhF4wZtVb9aAV_fLElFnKQb44pSi34O-KCUpy8K3_eMvLHUQ6owRySIsVcdQDtOQUKamSenZHRMrBXSDSmnMgpmFyvVtuwSrpIxgrgPo2W70ONswYkq_4l3RS0eX0wLZf0xdtAbT2t0jQUZRqM9teIQik',
      fechaSalida: '2026-12-05',
      fechaRegreso: '2026-12-08',
      fechasFormato: '10-13 Jul · 3 noches',
      personas: 2,
      personasTexto: '2 personas',
      estado: 'pendiente de pago',
      tipoTab: 'proximos',
      descuento: '-45% Dcto',
      precioAnterior: '$620.000',
      alojamiento: 'Finca Hotel Cafetera Los Álamos',
      incluye: [
        'Alojamiento campestre típico 3 noches',
        'Tour especializado del café con catación',
        'Caminata guiada en el Valle de Cocora',
        'Entrada al Parque Nacional del Café'
      ],
      desglose: {
        tarifaBase: 286554,
        impuestosIva: 54446,
        totalCOP: 341000
      },
      metodoPago: 'Pendiente de confirmación bancaria',
      fechaPago: 'Pendiente'
    },
    {
      id: 'RES-003',
      codigo: 'AMV-2026-MDE-304',
      destino: 'Medellín',
      departamento: 'Antioquia · Ciudad de la Primavera',
      imagen: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDmvzIkEu7h8e83Z31QwWqQ7n5su1eKBEyRUobk1YJybkCWUwvqHDMfmC0jbhWQPCxubuHzk8cjKAfVr5eRMpk2COpC666qd6uUTkr8VnoYPZi0fhVpwoL1qD-1OHHHgcuPZqGOmcfp5ou0eF1sRahYPKZlSjxkwOsbRAnxHu3e_HcHZQutsJ24A30ECbAROL3MDJXjdnIa1mSqVJlcw6d-czAR56c8jxneUMLQzgBO1q3KrXJXRW911pQmSPrVyWNToinxExApd3M',
      fechaSalida: '2026-05-09',
      fechaRegreso: '2026-05-13',
      fechasFormato: '05-09 Ago · 4 noches',
      personas: 2,
      personasTexto: '2 personas',
      estado: 'pagado',
      tipoTab: 'pasados',
      descuento: '-30% Dcto',
      precioAnterior: '$710.000',
      alojamiento: 'Hotel Poblado Plaza Medellín',
      incluye: [
        'Hospedaje 4 noches en El Poblado',
        'Tour Comuna 13 y Graffitour con guía local',
        'Visita a Guatapé y Piedra del Peñol',
        'Paseo en Metrocable y Parque Arví'
      ],
      desglose: {
        tarifaBase: 417647,
        impuestosIva: 79353,
        totalCOP: 497000
      },
      metodoPago: 'Tarjeta de Crédito Visa **** 4589',
      fechaPago: '2026-04-10 16:30'
    },
    {
      id: 'RES-004',
      codigo: 'AMV-2025-SMR-655',
      destino: 'Santa Marta & Tayrona',
      departamento: 'Magdalena · Caribe',
      imagen: 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80',
      fechaSalida: '2025-11-18',
      fechaRegreso: '2025-11-22',
      fechasFormato: '18-22 Nov · 4 noches',
      personas: 4,
      personasTexto: '4 personas',
      estado: 'cancelado',
      tipoTab: 'pasados',
      descuento: '-25% Dcto',
      precioAnterior: '$1.200.000',
      alojamiento: 'Ecohabs Tayrona & Hotel Bahía',
      incluye: [
        'Transporte privado climatizado',
        'Entradas al Parque Nacional Tayrona',
        'Caminata a Cabo San Juan',
        'Tour a Playa Cristal con almuerzo típico'
      ],
      desglose: {
        tarifaBase: 798319,
        impuestosIva: 151681,
        totalCOP: 950000
      },
      metodoPago: 'PSE - Davivienda',
      fechaPago: '2025-10-15 09:30'
    }
  ];

  const ApiService = {
    getAuthToken() {
      return null;
    },

    async getPerfil() {
      const response = await fetch('../backend/api/obtener_perfil.php', {
        credentials: 'same-origin',
        cache: 'no-store'
      });
      const result = await response.json();

      if (!response.ok || !result.ok) {
        return { ok: false, message: result.mensaje || 'No fue posible cargar el perfil.' };
      }

      return {
        ok: true,
        data: {
          id: result.usuario.id,
          nombreCompleto: result.usuario.nombre_completo,
          tipoDocumento: result.usuario.tipo_documento,
          numeroDocumento: result.usuario.numero_documento,
          correo: result.usuario.correo,
          rol: 'usuario'
        }
      };
    },

    async actualizarPerfil(datosActualizados) {
      const response = await fetch('../backend/api/actualizar_perfil.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(datosActualizados)
      });
      const result = await response.json();

      return {
        ok: response.ok && result.ok,
        message: result.mensaje || 'No fue posible actualizar el perfil.'
      };
    },

    async cambiarPassword(actual, nueva) {
      const response = await fetch('../backend/api/cambiar_contrasena.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ actual, nueva })
      });
      const result = await response.json();

      return {
        ok: response.ok && result.ok,
        message: result.mensaje || 'No fue posible actualizar la contraseña.'
      };
    },

    async getReservas(filtroEstado = 'proximos') {
      const response = await fetch('../backend/api/obtener_reservas.php', {
        credentials: 'same-origin',
        cache: 'no-store'
      });
      const result = await response.json();

      if (!response.ok || !result.ok) {
        return { ok: false, message: result.mensaje || 'No fue posible cargar las reservas.' };
      }

      const reservas = filtroEstado === 'todos'
        ? result.reservas
        : result.reservas.filter(reserva => reserva.tipoTab === filtroEstado);

      return {
        ok: true,
        data: reservas,
        totalProximos: result.totalProximos,
        totalPasados: result.totalPasados
      };
    }
  };

  // ==========================================================================
  // 2. ELEMENTOS DEL DOM
  // ==========================================================================
  const DOM = {
    formPerfil: document.getElementById('formPerfil'),
    inputNombre: document.getElementById('nombreCompleto'),
    selectTipoDoc: document.getElementById('tipoDocumento'),
    inputNumDoc: document.getElementById('numeroDocumento'),
    inputCorreo: document.getElementById('correo'),
    btnEditarPerfil: document.getElementById('btnEditarPerfil'),
    btnGuardarPerfil: document.getElementById('btnGuardarPerfil'),
    btnCancelarPerfil: document.getElementById('btnCancelarPerfil'),
    contenedorBotonesEdicion: document.getElementById('contenedorBotonesEdicion'),
    avatarIniciales: document.getElementById('avatarIniciales'),
    perfilNombreDisplay: document.getElementById('perfilNombreDisplay'),
    perfilCorreoDisplay: document.getElementById('perfilCorreoDisplay'),
    saludoUsuario: document.getElementById('saludoUsuario'),
    badgeRolUsuario: document.getElementById('badgeRolUsuario'),
    statReservasActivas: document.getElementById('statReservasActivas'),
    statViajesRealizados: document.getElementById('statViajesRealizados'),
    errorNombre: document.getElementById('errorNombre'),
    errorTipoDoc: document.getElementById('errorTipoDoc'),
    errorNumDoc: document.getElementById('errorNumDoc'),
    errorCorreo: document.getElementById('errorCorreo'),

    btnAbrirModalPassword: document.getElementById('btnAbrirModalPassword'),
    modalPassword: document.getElementById('modalPassword'),
    formPassword: document.getElementById('formPassword'),
    inputPassActual: document.getElementById('passwordActual'),
    inputPassNueva: document.getElementById('passwordNueva'),
    inputPassConfirmar: document.getElementById('passwordConfirmar'),
    btnCerrarModalPassword: document.getElementById('btnCerrarModalPassword'),
    btnCancelarModalPassword: document.getElementById('btnCancelarModalPassword'),
    strengthFill: document.getElementById('strengthFill'),
    strengthLabel: document.getElementById('strengthLabel'),
    reqLength: document.getElementById('reqLength'),
    reqUpper: document.getElementById('reqUpper'),
    reqNumber: document.getElementById('reqNumber'),
    mensajeModalPassword: document.getElementById('mensajeModalPassword'),

    tabProximos: document.getElementById('tabProximos'),
    tabPasados: document.getElementById('tabPasados'),
    badgeCountProximos: document.getElementById('badgeCountProximos'),
    badgeCountPasados: document.getElementById('badgeCountPasados'),
    contenedorReservas: document.getElementById('contenedorReservas'),
    emptyStateReservas: document.getElementById('emptyStateReservas'),

    modalDetalleReserva: document.getElementById('modalDetalleReserva'),
    btnCerrarModalDetalle: document.getElementById('btnCerrarModalDetalle'),
    detalleReservaContenido: document.getElementById('detalleReservaContenido'),
    btnDescargarDesdeDetalle: document.getElementById('btnDescargarDesdeDetalle'),

    bloqueAdmin: document.getElementById('bloqueAdmin'),
    btnIrAdmin: document.getElementById('btnIrAdmin'),
    selectSimuladorRol: document.getElementById('selectSimuladorRol'),

    btnCerrarSesion: document.getElementById('btnCerrarSesion'),
    modalConfirmarCierre: document.getElementById('modalConfirmarCierre'),
    btnNoCerrar: document.getElementById('btnNoCerrar'),
    btnSiCerrar: document.getElementById('btnSiCerrar'),

    toastContainer: document.getElementById('toast-container')
  };

  let backupPerfilData = null;
  let tabActual = 'proximos';
  let reservaSeleccionadaParaDetalle = null;

  // ==========================================================================
  // 3. FUNCIONES AUXILIARES DE ESTADOS
  // ==========================================================================

  function normalizarEstado(estado) {
    if (!estado) return 'no pagado';
    const e = String(estado).toLowerCase().trim()
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '');

    if (['pagado', 'pagada', 'valido', 'aprobado', 'aprobada', 'confirmado', 'confirmada'].includes(e)) {
      return 'pagado';
    }

    if (['pendiente de pago', 'pendiente_pago', 'pendiente', 'pendiente pago'].includes(e)) {
      return 'pendiente de pago';
    }

    if (['no pagado', 'no_pagado', 'sin pagar', 'rechazado', 'rechazada'].includes(e)) {
      return 'no pagado';
    }

    if (['en proceso', 'en proceso de pago', 'en_proceso', 'en_proceso_de_pago', 'procesando'].includes(e)) {
      return 'en proceso de pago';
    }

    if (['cancelado', 'cancelada', 'anulado', 'anulada'].includes(e)) {
      return 'cancelado';
    }

    return 'no pagado';
  }

  function obtenerBadgeEstado(estado) {
    const normalizado = normalizarEstado(estado);
    switch (normalizado) {
      case 'pagado':
        return '<span class="badge-estado badge-pagado">PAGADO / VÁLIDO</span>';
      case 'pendiente de pago':
        return '<span class="badge-estado badge-pendiente-pago">PENDIENTE DE PAGO</span>';
      case 'en proceso de pago':
        return '<span class="badge-estado badge-en-proceso">EN PROCESO DE PAGO</span>';
      case 'cancelado':
        return '<span class="badge-estado badge-cancelado">CANCELADO</span>';
      case 'no pagado':
      default:
        return '<span class="badge-estado badge-no-pagado">NO PAGADO</span>';
    }
  }

  function obtenerTextoEstado(estado) {
    const normalizado = normalizarEstado(estado);
    switch (normalizado) {
      case 'pagado': return 'Pagado / Válido';
      case 'pendiente de pago': return 'Pendiente de pago';
      case 'en proceso de pago': return 'En proceso de pago';
      case 'cancelado': return 'Cancelado';
      case 'no pagado':
      default: return 'No pagado';
    }
  }

  // ==========================================================================
  // 4. UTILIDADES Y NOTIFICACIONES TOAST
  // ==========================================================================

  function mostrarToast(mensaje, tipo = 'info', duracion = 3500) {
    if (!DOM.toastContainer) return;

    const toast = document.createElement('div');
    toast.className = `toast-item toast-${tipo}`;

    let icono = 'info';
    let iconClass = 'text-primary';
    if (tipo === 'success') { icono = 'check_circle'; iconClass = 'text-green-600'; }
    if (tipo === 'error') { icono = 'error'; iconClass = 'text-red-600'; }
    if (tipo === 'warning') { icono = 'warning'; iconClass = 'text-amber-500'; }

    toast.innerHTML = `
      <span class="material-symbols-outlined text-2xl ${iconClass}">
        ${icono}
      </span>
      <div class="flex-1">${mensaje}</div>
      <button type="button" class="text-slate-400 hover:text-slate-600 text-lg font-bold" aria-label="Cerrar">&times;</button>
    `;

    toast.querySelector('button').onclick = () => {
      if (toast.parentElement) toast.parentElement.removeChild(toast);
    };

    DOM.toastContainer.appendChild(toast);

    setTimeout(() => {
      if (toast.parentElement) {
        toast.style.transition = 'all 0.3s ease';
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        setTimeout(() => {
          if (toast.parentElement) toast.parentElement.removeChild(toast);
        }, 300);
      }
    }, duracion);
  }

  function formatearMonedaCOP(valor) {
    return new Intl.NumberFormat('es-CO', {
      style: 'currency',
      currency: 'COP',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(valor);
  }

  function obtenerIniciales(nombre) {
    if (!nombre) return 'AM';
    const partes = nombre.trim().split(' ');
    if (partes.length >= 2) {
      return (partes[0][0] + partes[1][0]).toUpperCase();
    }
    return nombre.substring(0, 2).toUpperCase();
  }

  // ==========================================================================
  // 5. LÓGICA DEL BLOQUE 1: INFORMACIÓN PERSONAL
  // ==========================================================================

  async function cargarDatosPerfil() {
    try {
      const res = await ApiService.getPerfil();
      if (!res.ok) throw new Error('Error al obtener perfil');

      const user = res.data;
      DOM.inputNombre.value = user.nombreCompleto || '';
      DOM.selectTipoDoc.value = user.tipoDocumento || 'CC';
      DOM.inputNumDoc.value = user.numeroDocumento || '';
      DOM.inputCorreo.value = user.correo || '';

      if (DOM.perfilNombreDisplay) DOM.perfilNombreDisplay.textContent = user.nombreCompleto;
      if (DOM.perfilCorreoDisplay) DOM.perfilCorreoDisplay.textContent = user.correo;
      if (DOM.saludoUsuario) DOM.saludoUsuario.textContent = '¡Hola, ' + user.nombreCompleto.split(' ')[0] + '!';
      if (DOM.avatarIniciales) DOM.avatarIniciales.textContent = obtenerIniciales(user.nombreCompleto);

      actualizarVisualizacionRol(user.rol);

    } catch (err) {
      console.error(err);
      mostrarToast('No se pudieron cargar los datos del perfil.', 'error');
    }
  }

  function toggleModoEdicion(habilitar) {
    const campos = [DOM.inputNombre, DOM.selectTipoDoc, DOM.inputNumDoc, DOM.inputCorreo];

    campos.forEach(campo => {
      if (habilitar) {
        campo.removeAttribute('readonly');
        campo.removeAttribute('disabled');
      } else {
        campo.setAttribute('readonly', 'true');
        if (campo.tagName === 'SELECT') campo.setAttribute('disabled', 'true');
      }
    });

    if (habilitar) {
      DOM.btnEditarPerfil.classList.add('hidden');
      DOM.contenedorBotonesEdicion.classList.remove('hidden');
      DOM.inputNombre.focus();
      backupPerfilData = {
        nombreCompleto: DOM.inputNombre.value,
        tipoDocumento: DOM.selectTipoDoc.value,
        numeroDocumento: DOM.inputNumDoc.value,
        correo: DOM.inputCorreo.value
      };
    } else {
      DOM.btnEditarPerfil.classList.remove('hidden');
      DOM.contenedorBotonesEdicion.classList.add('hidden');
      limpiarErroresPerfil();
    }
  }

  function limpiarErroresPerfil() {
    [DOM.errorNombre, DOM.errorTipoDoc, DOM.errorNumDoc, DOM.errorCorreo].forEach(el => {
      if (el) {
        el.textContent = '';
        el.classList.add('hidden');
      }
    });
  }

  function mostrarErrorCampo(elementoError, mensaje) {
    if (elementoError) {
      elementoError.textContent = mensaje;
      elementoError.classList.remove('hidden');
    }
  }

  function validarFormularioPerfil() {
    limpiarErroresPerfil();
    let esValido = true;

    const nombre = DOM.inputNombre.value.trim();
    const tipoDoc = DOM.selectTipoDoc.value;
    const numDoc = DOM.inputNumDoc.value.trim();
    const correo = DOM.inputCorreo.value.trim();

    if (!nombre || nombre.length < 3) {
      mostrarErrorCampo(DOM.errorNombre, 'El nombre debe tener al menos 3 caracteres.');
      esValido = false;
    }

    if (!tipoDoc) {
      mostrarErrorCampo(DOM.errorTipoDoc, 'Selecciona un tipo de documento.');
      esValido = false;
    }

    if (!numDoc || !/^[0-9a-zA-Z\-]{5,20}$/.test(numDoc)) {
      mostrarErrorCampo(DOM.errorNumDoc, 'Número de documento no válido.');
      esValido = false;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!correo || !emailRegex.test(correo)) {
      mostrarErrorCampo(DOM.errorCorreo, 'Ingresa un correo electrónico válido.');
      esValido = false;
    }

    return esValido;
  }

  async function guardarCambiosPerfil(e) {
    if (e) e.preventDefault();
    if (!validarFormularioPerfil()) return;

    DOM.btnGuardarPerfil.disabled = true;
    DOM.btnGuardarPerfil.innerHTML = `Guardando...`;

    try {
      const datos = {
        nombreCompleto: DOM.inputNombre.value.trim(),
        tipoDocumento: DOM.selectTipoDoc.value,
        numeroDocumento: DOM.inputNumDoc.value.trim(),
        correo: DOM.inputCorreo.value.trim()
      };

      const res = await ApiService.actualizarPerfil(datos);
      if (!res.ok) {
        mostrarToast(res.message || 'Error al guardar cambios.', 'error');
        mostrarErrorCampo(DOM.errorCorreo, res.message);
        return;
      }

      mostrarToast('✅ Perfil actualizado correctamente.', 'success');
      toggleModoEdicion(false);
      await cargarDatosPerfil();

    } catch (err) {
      console.error(err);
      mostrarToast('Error inesperado al conectar con el servidor.', 'error');
    } finally {
      DOM.btnGuardarPerfil.disabled = false;
      DOM.btnGuardarPerfil.innerHTML = `<span class="material-symbols-outlined text-sm">save</span> Guardar Cambios`;
    }
  }

  function cancelarEdicionPerfil() {
    if (backupPerfilData) {
      DOM.inputNombre.value = backupPerfilData.nombreCompleto;
      DOM.selectTipoDoc.value = backupPerfilData.tipoDocumento;
      DOM.inputNumDoc.value = backupPerfilData.numeroDocumento;
      DOM.inputCorreo.value = backupPerfilData.correo;
    }
    toggleModoEdicion(false);
    mostrarToast('Edición cancelada.', 'info', 2000);
  }

  // ==========================================================================
  // 6. LÓGICA DEL BLOQUE 2: SEGURIDAD (CAMBIO DE CONTRASEÑA)
  // ==========================================================================

  function abrirModalPassword() {
    DOM.formPassword.reset();
    DOM.mensajeModalPassword.innerHTML = '';
    actualizarMedidorFortaleza('');
    DOM.modalPassword.classList.add('active');
    DOM.inputPassActual.focus();
  }

  function cerrarModalPassword() {
    DOM.modalPassword.classList.remove('active');
  }

  function evaluarFortalezaContraseña(pass) {
    let score = 0;
    const hasMinLength = pass.length >= 6;
    const hasUpper = /[A-Z]/.test(pass);
    const hasNumber = /[0-9]/.test(pass);

    if (hasMinLength) score++;
    if (hasUpper) score++;
    if (hasNumber) score++;

    return { score, hasMinLength, hasUpper, hasNumber };
  }

  function actualizarMedidorFortaleza(pass) {
    if (!pass) {
      DOM.strengthFill.className = 'strength-fill';
      DOM.strengthFill.style.width = '0%';
      DOM.strengthLabel.textContent = 'Seguridad: -';
      DOM.strengthLabel.className = 'font-bold text-slate-400';
      actualizarChecklistReq(false, false, false);
      return;
    }

    const { score, hasMinLength, hasUpper, hasNumber } = evaluarFortalezaContraseña(pass);
    actualizarChecklistReq(hasMinLength, hasUpper, hasNumber);

    if (score <= 1) {
      DOM.strengthFill.className = 'strength-fill strength-debil';
      DOM.strengthLabel.textContent = 'Seguridad: Débil';
      DOM.strengthLabel.className = 'font-bold text-red-500';
    } else if (score === 2) {
      DOM.strengthFill.className = 'strength-fill strength-media';
      DOM.strengthLabel.textContent = 'Seguridad: Media';
      DOM.strengthLabel.className = 'font-bold text-amber-500';
    } else {
      DOM.strengthFill.className = 'strength-fill strength-fuerte';
      DOM.strengthLabel.textContent = 'Seguridad: Fuerte';
      DOM.strengthLabel.className = 'font-bold text-green-600';
    }
  }

  function actualizarChecklistReq(lengthOk, upperOk, numberOk) {
    if (DOM.reqLength) {
      DOM.reqLength.className = `flex items-center gap-1.5 ${lengthOk ? 'text-green-600 font-semibold' : ''}`;
      DOM.reqLength.querySelector('.material-symbols-outlined').textContent = lengthOk ? 'check_circle' : 'radio_button_unchecked';
    }
    if (DOM.reqUpper) {
      DOM.reqUpper.className = `flex items-center gap-1.5 ${upperOk ? 'text-green-600 font-semibold' : ''}`;
      DOM.reqUpper.querySelector('.material-symbols-outlined').textContent = upperOk ? 'check_circle' : 'radio_button_unchecked';
    }
    if (DOM.reqNumber) {
      DOM.reqNumber.className = `flex items-center gap-1.5 ${numberOk ? 'text-green-600 font-semibold' : ''}`;
      DOM.reqNumber.querySelector('.material-symbols-outlined').textContent = numberOk ? 'check_circle' : 'radio_button_unchecked';
    }
  }

  async function procesarCambioPassword(e) {
    e.preventDefault();
    DOM.mensajeModalPassword.innerHTML = '';

    const actual = DOM.inputPassActual.value;
    const nueva = DOM.inputPassNueva.value;
    const confirm = DOM.inputPassConfirmar.value;

    if (!actual) {
      DOM.mensajeModalPassword.innerHTML = `<span style="color:#dc3545;">Ingresa tu contraseña actual.</span>`;
      DOM.inputPassActual.focus();
      return;
    }

    const { hasMinLength, hasUpper, hasNumber } = evaluarFortalezaContraseña(nueva);
    if (!hasMinLength || !hasUpper || !hasNumber) {
      DOM.mensajeModalPassword.innerHTML = `<span style="color:#dc3545;">La nueva contraseña debe cumplir con todos los requisitos (6+ caracteres, 1 mayúscula y 1 número).</span>`;
      DOM.inputPassNueva.focus();
      return;
    }

    if (nueva !== confirm) {
      DOM.mensajeModalPassword.innerHTML = `<span style="color:#dc3545;">Las nuevas contraseñas no coinciden.</span>`;
      DOM.inputPassConfirmar.focus();
      return;
    }

    const btnSubmit = DOM.formPassword.querySelector('button[type="submit"]');
    btnSubmit.disabled = true;
    btnSubmit.textContent = 'Guardando...';

    try {
      const res = await ApiService.cambiarPassword(actual, nueva);
      if (!res.ok) {
        DOM.mensajeModalPassword.innerHTML = `<span style="color:#dc3545;">${res.message}</span>`;
        return;
      }

      DOM.mensajeModalPassword.innerHTML = `<span style="color:#28a745;">✅ ${res.message}</span>`;
      mostrarToast('🔐 Contraseña actualizada con éxito.', 'success');

      setTimeout(() => {
        cerrarModalPassword();
      }, 1400);

    } catch (err) {
      console.error(err);
      DOM.mensajeModalPassword.innerHTML = `<span style="color:#dc3545;">Error en el servidor.</span>`;
    } finally {
      btnSubmit.disabled = false;
      btnSubmit.textContent = 'Guardar cambios';
    }
  }

  // ==========================================================================
  // 7. LÓGICA DEL BLOQUE 3: HISTORIAL DE RESERVAS
  // ==========================================================================

  async function cargarReservas(filtro = tabActual) {
    tabActual = filtro;

    if (filtro === 'proximos') {
      DOM.tabProximos.classList.add('active');
      DOM.tabProximos.setAttribute('aria-selected', 'true');
      DOM.tabPasados.classList.remove('active');
      DOM.tabPasados.setAttribute('aria-selected', 'false');
    } else {
      DOM.tabPasados.classList.add('active');
      DOM.tabPasados.setAttribute('aria-selected', 'true');
      DOM.tabProximos.classList.remove('active');
      DOM.tabProximos.setAttribute('aria-selected', 'false');
    }

    DOM.contenedorReservas.innerHTML = `
      <div class="col-span-full py-12 text-center text-slate-400">
        <span class="material-symbols-outlined text-4xl animate-spin text-primary block mb-2">progress_activity</span>
        <p class="font-medium text-sm">Cargando tus reservas...</p>
      </div>
    `;

    try {
      const res = await ApiService.getReservas(filtro);
      if (!res.ok) throw new Error('Error al cargar reservas');

      if (DOM.badgeCountProximos) DOM.badgeCountProximos.textContent = res.totalProximos;
      if (DOM.badgeCountPasados) DOM.badgeCountPasados.textContent = res.totalPasados;
      if (DOM.statReservasActivas) DOM.statReservasActivas.textContent = res.totalProximos;
      if (DOM.statViajesRealizados) DOM.statViajesRealizados.textContent = res.totalPasados;

      renderizarTarjetasReservas(res.data);

    } catch (err) {
      console.error(err);
      DOM.contenedorReservas.innerHTML = `
        <div class="col-span-full p-6 text-center text-red-500 font-semibold bg-red-50 rounded-2xl">
          Error al consultar reservas.
        </div>
      `;
    }
  }

  function renderizarTarjetasReservas(reservas) {
    if (!reservas || reservas.length === 0) {
      DOM.contenedorReservas.classList.add('hidden');
      DOM.emptyStateReservas.classList.remove('hidden');
      return;
    }

    DOM.contenedorReservas.classList.remove('hidden');
    DOM.emptyStateReservas.classList.add('hidden');
    DOM.contenedorReservas.innerHTML = '';

    reservas.forEach(res => {
      const card = document.createElement('div');
      card.className = 'group bg-white dark:bg-white/5 rounded-2xl overflow-hidden border border-primary/10 shadow-sm card-hover flex flex-col justify-between';

      const badgeEstadoHTML = obtenerBadgeEstado(res.estado);
      const descuentoHTML = res.descuento
        ? `<div class="absolute top-3 right-3 bg-yellow-400 text-background-dark font-black px-3 py-1 rounded-lg text-xs shadow-md">${res.descuento}</div>`
        : '';

      card.innerHTML = `
        <div>
          <div class="relative w-full aspect-[4/3] overflow-hidden">
            <img 
              alt="${res.destino}" 
              class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" 
              src="${res.imagen}" 
            />
            ${descuentoHTML}
            <div class="absolute bottom-3 left-3 bg-black/60 backdrop-blur-sm text-white px-3 py-1 rounded-full text-xs font-semibold flex items-center gap-1">
              <span class="material-symbols-outlined text-sm text-primary">location_on</span>
              ${res.departamento}
            </div>
          </div>

          <div class="p-5">
            <div class="flex justify-between items-start mb-2 gap-2">
              <div class="flex-1 min-w-0">
                <h4 class="text-xl font-bold text-slate-900 dark:text-slate-100">${res.destino}</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1 mt-0.5">
                  <span class="material-symbols-outlined text-primary text-xs">calendar_month</span> ${res.fechasFormato} · ${res.personasTexto}
                </p>
              </div>
              <div class="text-right flex-shrink-0">
                <p class="text-xs text-slate-400 line-through">${res.precioAnterior || ''}</p>
                <p class="text-xl font-black text-primary">${formatearMonedaCOP(res.desglose.totalCOP)}</p>
              </div>
            </div>

            <div class="mt-3 flex items-center justify-between gap-2 flex-wrap">
              ${badgeEstadoHTML}
              <span class="text-[11px] text-slate-500 dark:text-slate-400 font-mono">${res.codigo}</span>
            </div>

            <div class="mt-3 text-xs text-slate-600 dark:text-slate-300 bg-primary/5 p-2.5 rounded-xl border border-primary/10">
              <strong>Hospedaje:</strong> ${res.alojamiento}
            </div>
          </div>
        </div>

        <div class="p-5 pt-0 grid grid-cols-2 gap-3">
          <button 
            type="button" 
            class="py-2.5 rounded-full btn-outline text-xs font-bold text-center"
            data-accion="detalles"
          >
            <span class="material-symbols-outlined text-sm">visibility</span> Ver detalles
          </button>
          
          <button 
            type="button" 
            class="py-2.5 rounded-full gradient-btn text-white font-bold text-xs shadow-lg shadow-primary/30 text-center"
            data-accion="comprobante"
          >
            <span class="material-symbols-outlined text-sm">download</span> Comprobante
          </button>
        </div>
      `;

      card.querySelector('[data-accion="detalles"]').addEventListener('click', () => abrirModalDetalleReserva(res));
      card.querySelector('[data-accion="comprobante"]').addEventListener('click', () => generarYDescargarPDF(res));

      DOM.contenedorReservas.appendChild(card);
    });
  }

  function abrirModalDetalleReserva(reserva) {
    reservaSeleccionadaParaDetalle = reserva;
    if (!DOM.detalleReservaContenido) return;

    const badgeEstadoHTML = obtenerBadgeEstado(reserva.estado);
    const textoEstado = obtenerTextoEstado(reserva.estado);

    DOM.detalleReservaContenido.innerHTML = `
      <div class="relative h-44 rounded-2xl overflow-hidden mb-3">
        <img src="${reserva.imagen}" alt="${reserva.destino}" class="w-full h-full object-cover"/>
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent flex items-end p-4">
          <div>
            <span class="text-xs font-bold text-yellow-400 uppercase tracking-wide">${reserva.departamento}</span>
            <h4 class="text-xl font-black text-white leading-tight">${reserva.destino}</h4>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-3 bg-primary/5 p-4 rounded-xl text-xs text-slate-700">
        <div>
          <span class="text-slate-400 block mb-0.5 font-semibold">Código de Reserva</span>
          <strong class="font-mono text-primary text-sm">${reserva.codigo}</strong>
        </div>
        <div>
          <span class="text-slate-400 block mb-0.5 font-semibold">Estado de Pago</span>
          <div class="mt-1">${badgeEstadoHTML}</div>
        </div>
        <div>
          <span class="text-slate-400 block mb-0.5 font-semibold">Itinerario</span>
          <strong>${reserva.fechasFormato}</strong>
        </div>
        <div>
          <span class="text-slate-400 block mb-0.5 font-semibold">Pasajeros</span>
          <strong>${reserva.personasTexto}</strong>
        </div>
      </div>

      <div>
        <h5 class="text-sm font-bold text-slate-800 mb-1.5 flex items-center gap-1 text-primary">
          <span class="material-symbols-outlined text-base">hotel</span> Alojamiento Confirmado
        </h5>
        <p class="text-xs text-slate-600 bg-white p-3 rounded-xl border border-primary/10">
          ${reserva.alojamiento}
        </p>
      </div>

      <div>
        <h5 class="text-sm font-bold text-slate-800 mb-1.5 flex items-center gap-1 text-primary">
          <span class="material-symbols-outlined text-base">checklist</span> El Plan Turístico Incluye
        </h5>
        <ul class="space-y-1.5 text-xs text-slate-600">
          ${reserva.incluye.map(item => `
            <li class="flex items-start gap-2">
              <span class="material-symbols-outlined text-green-500 text-sm mt-0.5">check_circle</span>
              <span>${item}</span>
            </li>
          `).join('')}
        </ul>
      </div>

      <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
        <div class="flex justify-between text-xs text-slate-500 mb-1">
          <span>Método de Transacción:</span>
          <span class="font-semibold text-slate-700">${reserva.metodoPago}</span>
        </div>
        <div class="flex justify-between text-xs text-slate-500 mb-2">
          <span>Fecha de Aprobación:</span>
          <span class="font-semibold text-slate-700">${reserva.fechaPago}</span>
        </div>
        <div class="flex justify-between text-xs text-slate-500 mb-2">
          <span>Estado actual:</span>
          <span class="font-semibold text-slate-700">${textoEstado}</span>
        </div>
        <div class="flex justify-between items-center pt-2 border-t border-slate-200 text-sm">
          <strong class="text-slate-800">Total Liquidado:</strong>
          <strong class="text-xl font-black text-primary">${formatearMonedaCOP(reserva.desglose.totalCOP)}</strong>
        </div>
      </div>
    `;

    DOM.modalDetalleReserva.classList.add('active');
  }

  function cerrarModalDetalleReserva() {
    DOM.modalDetalleReserva.classList.remove('active');
    reservaSeleccionadaParaDetalle = null;
  }

  // ==========================================================================
  // 8. GENERADOR DE COMPROBANTES DE PAGO EN PDF (jsPDF)
  // ==========================================================================

  async function generarYDescargarPDF(reserva) {
    mostrarToast('📄 Generando comprobante de pago oficial...', 'info', 2000);

    try {
      const resUser = await ApiService.getPerfil();
      const user = resUser.data;

      if (typeof window.jspdf === 'undefined') {
        throw new Error('Librería jsPDF no disponible.');
      }

      const { jsPDF } = window.jspdf;
      const doc = new jsPDF({
        orientation: 'portrait',
        unit: 'mm',
        format: 'a4'
      });

      const pageWidth = doc.internal.pageSize.getWidth();
      const margin = 16;
      let y = 18;

      // ENCABEZADO
      doc.setFillColor(200, 0, 255);
      doc.rect(0, 0, pageWidth, 6, 'F');

      doc.setFont('helvetica', 'bold');
      doc.setFontSize(20);
      doc.setTextColor(200, 0, 255);
      doc.text('DINASTÍA AMV', margin, y);

      doc.setFontSize(9);
      doc.setFont('helvetica', 'normal');
      doc.setTextColor(100, 116, 139);
      doc.text('Agencia de Viajes & Experiencias Turísticas en Colombia', margin, y + 5);
      doc.text('NIT: 901.458.789-2 | RNT: 45291 | Bogotá, Colombia', margin, y + 9);

      doc.setFillColor(248, 246, 246);
      doc.roundedRect(pageWidth - margin - 60, y - 4, 60, 20, 2, 2, 'F');
      doc.setFont('helvetica', 'bold');
      doc.setFontSize(10);
      doc.setTextColor(239, 29, 156);
      doc.text('COMPROBANTE OFICIAL', pageWidth - margin - 56, y + 2);
      doc.setFontSize(8);
      doc.setTextColor(30, 41, 59);
      doc.text(`N°: ${reserva.codigo}`, pageWidth - margin - 56, y + 8);
      doc.text(`Fecha: ${new Date().toLocaleDateString('es-CO')}`, pageWidth - margin - 56, y + 13);

      y += 24;
      doc.setDrawColor(226, 232, 240);
      doc.line(margin, y, pageWidth - margin, y);
      y += 8;

      // DATOS DEL TITULAR
      doc.setFont('helvetica', 'bold');
      doc.setFontSize(11);
      doc.setTextColor(30, 41, 59);
      doc.text('DATOS DEL VIAJERO / TITULAR', margin, y);
      y += 6;

      doc.setFont('helvetica', 'normal');
      doc.setFontSize(9);
      doc.setTextColor(71, 85, 105);

      const col2 = margin + 85;
      doc.text(`Nombre Completo:`, margin, y);
      doc.setFont('helvetica', 'bold');
      doc.text(`${user.nombreCompleto}`, margin + 32, y);
      doc.setFont('helvetica', 'normal');

      doc.text(`Documento:`, col2, y);
      doc.setFont('helvetica', 'bold');
      doc.text(`${user.tipoDocumento} ${user.numeroDocumento}`, col2 + 22, y);
      doc.setFont('helvetica', 'normal');
      y += 5;

      doc.text(`Correo Electrónico:`, margin, y);
      doc.setFont('helvetica', 'bold');
      doc.text(`${user.correo}`, margin + 32, y);
      doc.setFont('helvetica', 'normal');

      doc.text(`Teléfono:`, col2, y);
      doc.setFont('helvetica', 'bold');
      doc.text(`${user.telefono || '+57 312 456 7890'}`, col2 + 22, y);
      doc.setFont('helvetica', 'normal');
      y += 10;

      // DETALLE DEL PLAN
      doc.setFont('helvetica', 'bold');
      doc.setFontSize(11);
      doc.setTextColor(30, 41, 59);
      doc.text('DETALLE DEL PLAN TURÍSTICO ADQUIRIDO', margin, y);
      y += 6;

      doc.setFillColor(248, 250, 252);
      doc.setDrawColor(203, 213, 225);
      doc.roundedRect(margin, y, pageWidth - (margin * 2), 26, 2, 2, 'FD');

      doc.setFontSize(9);
      doc.setTextColor(200, 0, 255);
      doc.setFont('helvetica', 'bold');
      doc.text(`Destino: ${reserva.destino}`, margin + 4, y + 6);

      doc.setFont('helvetica', 'normal');
      doc.setTextColor(51, 65, 85);
      doc.text(`Departamento / Región: ${reserva.departamento}`, margin + 4, y + 12);
      doc.text(`Itinerario: ${reserva.fechasFormato}`, margin + 4, y + 18);
      doc.text(`Alojamiento: ${reserva.alojamiento}`, margin + 4, y + 23);

      doc.text(`Pasajeros: ${reserva.personasTexto}`, col2, y + 12);
      doc.text(`Estado: ${obtenerTextoEstado(reserva.estado).toUpperCase()}`, col2, y + 18);
      y += 32;

      // DESGLOSE DE PAGO
      doc.setFont('helvetica', 'bold');
      doc.setFontSize(11);
      doc.setTextColor(30, 41, 59);
      doc.text('DESGLOSE DE PAGO Y FACTURACIÓN', margin, y);
      y += 5;

      doc.setFillColor(200, 0, 255);
      doc.rect(margin, y, pageWidth - (margin * 2), 7, 'F');
      doc.setTextColor(255, 255, 255);
      doc.setFontSize(8.5);
      doc.text('CONCEPTO', margin + 4, y + 5);
      doc.text('CANT.', margin + 110, y + 5);
      doc.text('VALOR COP', pageWidth - margin - 30, y + 5);
      y += 7;

      doc.setTextColor(51, 65, 85);
      doc.setFont('helvetica', 'normal');
      doc.setFillColor(255, 255, 255);
      doc.rect(margin, y, pageWidth - (margin * 2), 7, 'F');
      doc.text(`Paquete Turístico Todo Incluido - ${reserva.destino}`, margin + 4, y + 5);
      doc.text('1', margin + 114, y + 5);
      doc.text(`${formatearMonedaCOP(reserva.desglose.tarifaBase)}`, pageWidth - margin - 30, y + 5);
      y += 7;

      doc.setFillColor(248, 250, 252);
      doc.rect(margin, y, pageWidth - (margin * 2), 7, 'F');
      doc.text('IVA y Tasas de Turismo aplicables (19%)', margin + 4, y + 5);
      doc.text('1', margin + 114, y + 5);
      doc.text(`${formatearMonedaCOP(reserva.desglose.impuestosIva)}`, pageWidth - margin - 30, y + 5);
      y += 7;

      doc.setFillColor(241, 245, 249);
      doc.rect(margin, y, pageWidth - (margin * 2), 9, 'F');
      doc.setFont('helvetica', 'bold');
      doc.setFontSize(10);
      doc.setTextColor(200, 0, 255);
      doc.text('TOTAL PAGADO (COP)', margin + 4, y + 6);
      doc.text(`${formatearMonedaCOP(reserva.desglose.totalCOP)}`, pageWidth - margin - 35, y + 6);
      y += 15;

      // MÉTODO DE PAGO
      doc.setFont('helvetica', 'normal');
      doc.setFontSize(8.5);
      doc.setTextColor(100, 116, 139);
      doc.text(`Método de Transacción: ${reserva.metodoPago}`, margin, y);
      doc.text(`Fecha y Hora de Pago: ${reserva.fechaPago}`, margin, y + 5);

      // SELLO DINÁMICO SEGÚN ESTADO (5 estados)
      const estadoNormalizado = normalizarEstado(reserva.estado);

      let selloTexto = 'PAGADO / VÁLIDO';
      let selloColor = [16, 185, 129];

      if (estadoNormalizado === 'pendiente de pago') {
        selloTexto = 'PENDIENTE DE PAGO';
        selloColor = [202, 138, 4];
      } else if (estadoNormalizado === 'no pagado') {
        selloTexto = 'NO PAGADO';
        selloColor = [217, 119, 6];
      } else if (estadoNormalizado === 'en proceso de pago') {
        selloTexto = 'EN PROCESO';
        selloColor = [37, 99, 235];
      } else if (estadoNormalizado === 'cancelado') {
        selloTexto = 'CANCELADO';
        selloColor = [220, 38, 38];
      }

      doc.setDrawColor(selloColor[0], selloColor[1], selloColor[2]);
      doc.setTextColor(selloColor[0], selloColor[1], selloColor[2]);
      doc.setFont('helvetica', 'bold');
      doc.setFontSize(13);

      const selloAncho = 55;
      const selloAlto = 16;
      const selloX = pageWidth - margin - selloAncho;
      const selloY = y - 2;

      doc.roundedRect(selloX, selloY, selloAncho, selloAlto, 2, 2);

      doc.setFontSize(11);
      const textoAncho = doc.getTextWidth(selloTexto);
      doc.text(selloTexto, selloX + (selloAncho - textoAncho) / 2, selloY + 11);

      y += 24;

      // TÉRMINOS Y CONDICIONES
      doc.setDrawColor(226, 232, 240);
      doc.line(margin, y, pageWidth - margin, y);
      y += 6;

      doc.setFont('helvetica', 'bold');
      doc.setFontSize(8);
      doc.setTextColor(71, 85, 105);
      doc.text('TÉRMINOS Y CONDICIONES:', margin, y);
      y += 4;

      doc.setFont('helvetica', 'normal');
      doc.setFontSize(7);
      doc.setTextColor(148, 163, 184);
      const clausula = 'Este documento constituye el comprobante oficial de su compra en Dinastía AMV. Para soporte o modificaciones comuníquese con soporte@dinastia.com.';
      doc.text(doc.splitTextToSize(clausula, pageWidth - (margin * 2)), margin, y);

      // GUARDAR PDF
      const nombreArchivo = `Comprobante_AMV_${reserva.codigo}.pdf`;
      doc.save(nombreArchivo);
      mostrarToast(`✅ Comprobante descargado: ${nombreArchivo}`, 'success');

    } catch (err) {
      console.error(err);
      mostrarToast('Error al generar comprobante PDF.', 'error');
    }
  }

  // ==========================================================================
  // 9. LÓGICA DEL BLOQUE 4: ACCESO ADMIN & SIMULADOR DE ROL
  // ==========================================================================

  function actualizarVisualizacionRol(rol) {
    const esAdmin = rol === 'admin';

    if (DOM.bloqueAdmin) {
      if (esAdmin) {
        DOM.bloqueAdmin.classList.remove('hidden');
      } else {
        DOM.bloqueAdmin.classList.add('hidden');
      }
    }

    if (DOM.badgeRolUsuario) {
      if (esAdmin) {
        DOM.badgeRolUsuario.textContent = 'Administrador';
        DOM.badgeRolUsuario.className = 'inline-block mt-1 text-[11px] font-bold px-2 py-0.5 rounded-full bg-accent/20 text-accent';
      } else {
        DOM.badgeRolUsuario.textContent = 'Viajero Registrado';
        DOM.badgeRolUsuario.className = 'inline-block mt-1 text-[11px] font-bold px-2 py-0.5 rounded-full bg-primary/10 text-primary';
      }
    }

    // ==========================================================
    // BLOQUEAR SELECTOR DE ROL SEGÚN EL TIPO DE USUARIO
    // ==========================================================
    if (DOM.selectSimuladorRol) {
      DOM.selectSimuladorRol.value = rol;

      if (esAdmin) {
        // Administrador: puede cambiar el rol (para pruebas)
        DOM.selectSimuladorRol.disabled = false;
        DOM.selectSimuladorRol.classList.remove('cursor-not-allowed', 'opacity-70');
        DOM.selectSimuladorRol.classList.add('cursor-pointer');
        DOM.selectSimuladorRol.title = 'Cambiar rol (modo administrador)';
      } else {
        // Viajero: el selector se bloquea y no se puede cambiar
        DOM.selectSimuladorRol.disabled = true;
        DOM.selectSimuladorRol.classList.add('cursor-not-allowed', 'opacity-70');
        DOM.selectSimuladorRol.classList.remove('cursor-pointer');
        DOM.selectSimuladorRol.title = 'Rol asignado automáticamente (solo lectura)';
      }
    }
  }

  function cambiarRolSimulado(nuevoRol) {
    // Seguridad adicional: no permitir cambiar si no es admin
    if (DOM.selectSimuladorRol && DOM.selectSimuladorRol.disabled) {
      return;
    }

    localStorage.setItem(STORAGE_KEYS.USER_ROLE, nuevoRol);
    const localData = localStorage.getItem(STORAGE_KEYS.USER_PROFILE);
    if (localData) {
      const user = JSON.parse(localData);
      user.rol = nuevoRol;
      localStorage.setItem(STORAGE_KEYS.USER_PROFILE, JSON.stringify(user));
    }

    actualizarVisualizacionRol(nuevoRol);
    mostrarToast(`Rol simulado cambiado a: ${nuevoRol.toUpperCase()}`, 'info', 2000);
  }

  // ==========================================================================
  // 10. LÓGICA DEL BLOQUE 5: CIERRE DE SESIÓN SEGURO
  // ==========================================================================

  function abrirModalCierreSesion() {
    DOM.modalConfirmarCierre.classList.add('active');
  }

  function cerrarModalCierreSesion() {
    DOM.modalConfirmarCierre.classList.remove('active');
  }

  function ejecutarCierreSesion() {
    mostrarToast('Cerrando sesión...', 'info', 1000);
    sessionStorage.clear();
    localStorage.removeItem(STORAGE_KEYS.AUTH_TOKEN);
    setTimeout(() => {
      window.location.href = 'index.html';
    }, 800);
  }

  // ==========================================================================
  // 11. REGISTRO DE EVENTOS Y CICLO DE VIDA
  // ==========================================================================

  function registrarEventos() {
    if (DOM.btnEditarPerfil) DOM.btnEditarPerfil.addEventListener('click', () => toggleModoEdicion(true));
    if (DOM.btnCancelarPerfil) DOM.btnCancelarPerfil.addEventListener('click', cancelarEdicionPerfil);
    if (DOM.formPerfil) DOM.formPerfil.addEventListener('submit', guardarCambiosPerfil);

    if (DOM.btnAbrirModalPassword) DOM.btnAbrirModalPassword.addEventListener('click', abrirModalPassword);
    if (DOM.btnCerrarModalPassword) DOM.btnCerrarModalPassword.addEventListener('click', cerrarModalPassword);
    if (DOM.btnCancelarModalPassword) DOM.btnCancelarModalPassword.addEventListener('click', cerrarModalPassword);
    if (DOM.inputPassNueva) {
      DOM.inputPassNueva.addEventListener('input', (e) => actualizarMedidorFortaleza(e.target.value));
    }
    if (DOM.formPassword) DOM.formPassword.addEventListener('submit', procesarCambioPassword);

    if (DOM.tabProximos) DOM.tabProximos.addEventListener('click', () => cargarReservas('proximos'));
    if (DOM.tabPasados) DOM.tabPasados.addEventListener('click', () => cargarReservas('pasados'));

    if (DOM.btnCerrarModalDetalle) DOM.btnCerrarModalDetalle.addEventListener('click', cerrarModalDetalleReserva);
    if (DOM.btnDescargarDesdeDetalle) {
      DOM.btnDescargarDesdeDetalle.addEventListener('click', () => {
        if (reservaSeleccionadaParaDetalle) generarYDescargarPDF(reservaSeleccionadaParaDetalle);
      });
    }

    if (DOM.btnIrAdmin) {
      DOM.btnIrAdmin.addEventListener('click', () => {
        window.location.href = 'interfazAdmin.html';
      });
    }
    if (DOM.selectSimuladorRol) {
      DOM.selectSimuladorRol.addEventListener('change', (e) => cambiarRolSimulado(e.target.value));
    }

    if (DOM.btnCerrarSesion) DOM.btnCerrarSesion.addEventListener('click', abrirModalCierreSesion);
    if (DOM.btnNoCerrar) DOM.btnNoCerrar.addEventListener('click', cerrarModalCierreSesion);
    if (DOM.btnSiCerrar) DOM.btnSiCerrar.addEventListener('click', ejecutarCierreSesion);

    [DOM.modalPassword, DOM.modalDetalleReserva, DOM.modalConfirmarCierre].forEach(modal => {
      if (modal) {
        modal.addEventListener('click', (e) => {
          if (e.target === modal) modal.classList.remove('active');
        });
      }
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        [DOM.modalPassword, DOM.modalDetalleReserva, DOM.modalConfirmarCierre].forEach(m => {
          if (m && m.classList.contains('active')) m.classList.remove('active');
        });
      }
    });
  }

  // ==========================================================================
  // 12. INICIALIZACIÓN
  // ==========================================================================

  async function inicializar() {
    ApiService.getAuthToken();
    registrarEventos();
    await cargarDatosPerfil();

    // Detectar si viene con ?tab=pasados para abrir directamente esa pestaña
    const params = new URLSearchParams(window.location.search);
    const tabInicial = params.get('tab') === 'pasados' ? 'pasados' : 'proximos';

    await cargarReservas(tabInicial);

    // Si viene con tab=pasados, hacer scroll suave hasta la sección de reservas
    if (params.get('tab') === 'pasados') {
      setTimeout(() => {
        const seccionReservas = document.getElementById('tituloHistorialViajes');
        if (seccionReservas) {
          seccionReservas.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      }, 400);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializar);
  } else {
    inicializar();
  }

})();