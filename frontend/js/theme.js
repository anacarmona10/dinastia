/**
 * ============================================================================
 * Dinastía AMV - Controlador de Tema Claro / Oscuro (theme.js)
 * Maneja la alternancia de modo claro/oscuro, persistencia en localStorage,
 * actualización de íconos (Material Symbols y Font Awesome) y soporte FOUC.
 * ============================================================================
 */

(function () {
  'use strict';

  const STORAGE_KEY = 'dinastia_theme';

  function getSavedTheme() {
    try {
      const guardado = localStorage.getItem(STORAGE_KEY);
      if (guardado === 'dark' || guardado === 'light') {
        return guardado;
      }
    } catch (_) {}
    return (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)
      ? 'dark'
      : 'light';
  }

  function aplicarTema(tema, guardar = true) {
    const esOscuro = tema === 'dark';
    const root = document.documentElement;

    if (esOscuro) {
      root.classList.add('dark');
      root.classList.remove('light');
    } else {
      root.classList.remove('dark');
      root.classList.add('light');
    }

    if (guardar) {
      try {
        localStorage.setItem(STORAGE_KEY, tema);
      } catch (_) {}
    }

    actualizarBotonesTema(esOscuro);

    window.dispatchEvent(new CustomEvent('dinastia-theme-changed', {
      detail: { theme: tema, isDark: esOscuro }
    }));
  }

  function actualizarBotonesTema(esOscuro) {
    const textoAccion = esOscuro ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro';
    const botones = document.querySelectorAll('#btnToggleTheme, .theme-toggle-btn, [data-theme-toggle]');

    botones.forEach(btn => {
      btn.setAttribute('title', textoAccion);
      btn.setAttribute('aria-label', textoAccion);

      // Icono Material Symbols
      const matIcon = btn.querySelector('.material-symbols-outlined');
      if (matIcon) {
        matIcon.textContent = esOscuro ? 'light_mode' : 'dark_mode';
      }

      // Icono Font Awesome
      const faIcon = btn.querySelector('i.fa-moon, i.fa-sun, i.fas');
      if (faIcon) {
        if (esOscuro) {
          faIcon.classList.remove('fa-moon');
          faIcon.classList.add('fa-sun');
        } else {
          faIcon.classList.remove('fa-sun');
          faIcon.classList.add('fa-moon');
        }
      }

      // Etiqueta de texto si es un botón de menú móvil
      const labelTexto = btn.querySelector('.theme-label-text');
      if (labelTexto) {
        labelTexto.textContent = esOscuro ? 'Modo Claro' : 'Modo Oscuro';
      }
    });
  }

  function alternarTema() {
    const actualmenteOscuro = document.documentElement.classList.contains('dark');
    aplicarTema(actualmenteOscuro ? 'light' : 'dark', true);
  }

  function inicializarBotones() {
    const esOscuro = document.documentElement.classList.contains('dark');
    actualizarBotonesTema(esOscuro);

    const botones = document.querySelectorAll('#btnToggleTheme, .theme-toggle-btn, [data-theme-toggle]');
    botones.forEach(btn => {
      if (!btn.dataset.themeBound) {
        btn.dataset.themeBound = 'true';
        btn.addEventListener('click', function (e) {
          e.preventDefault();
          alternarTema();
        });
      }
    });
  }

  // Aplicar inmediatamente al cargar el script para evitar parpadeo
  const temaInicial = getSavedTheme();
  aplicarTema(temaInicial, false);

  // Vincular eventos cuando el DOM esté listo
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarBotones);
  } else {
    inicializarBotones();
  }

  // Exponer API global segura
  window.DinastiaTheme = {
    toggle: alternarTema,
    set: aplicarTema,
    isDark: () => document.documentElement.classList.contains('dark')
  };
})();
