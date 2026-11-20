(() => {
  'use strict';
  const menuButton = document.querySelector('.dash-menu-toggle');
  const sidebar = document.getElementById('dashboard-sidebar');
  if (menuButton && sidebar) {
    menuButton.addEventListener('click', () => {
      const open = sidebar.classList.toggle('is-open');
      menuButton.setAttribute('aria-expanded', String(open));
    });
  }
  document.querySelectorAll('[data-carousel]').forEach((carousel) => {
    const slides = Array.from(carousel.querySelectorAll('.dash-slide'));
    const previous = carousel.querySelector('[data-carousel-prev]');
    const next = carousel.querySelector('[data-carousel-next]');
    if (!slides.length) return;
    let active = Math.max(0, slides.findIndex((slide) => slide.classList.contains('is-current')));
    const show = (index) => {
      active = (index + slides.length) % slides.length;
      slides.forEach((slide, i) => {
        const selected = i === active;
        slide.classList.toggle('is-current', selected);
        slide.setAttribute('aria-hidden', String(!selected));
      });
    };
    previous?.addEventListener('click', () => show(active - 1));
    next?.addEventListener('click', () => show(active + 1));
    show(active);
  });
})();
