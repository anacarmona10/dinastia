// menu.js - Control del menú hamburguesa
document.addEventListener('DOMContentLoaded', function() {
  const menuBtn = document.getElementById('menu-btn');
  const mobileMenu = document.getElementById('mobile-menu');

  // Abrir/cerrar menú al hacer clic en el botón
  menuBtn.addEventListener('click', function(e) {
    e.stopPropagation();
    mobileMenu.classList.toggle('hidden');
  });

  // Cerrar menú al hacer clic en cualquier enlace dentro del menú
  const menuLinks = mobileMenu.querySelectorAll('a');
  menuLinks.forEach(link => {
    link.addEventListener('click', function() {
      mobileMenu.classList.add('hidden');
    });
  });

  // Cerrar menú al hacer clic fuera de él (en cualquier parte de la página)
  document.addEventListener('click', function(e) {
    if (!mobileMenu.classList.contains('hidden') &&
        !mobileMenu.contains(e.target) &&
        !menuBtn.contains(e.target)) {
      mobileMenu.classList.add('hidden');
    }
  });
});