(() => {
  'use strict';
  const nav = document.querySelector('.main-nav');
  const menuToggle = document.querySelector('.menu-toggle');
  const dropdowns = [...document.querySelectorAll('.nav-dropdown')];

  const closeDropdown = (item, returnFocus = false) => {
    if (!item) return;
    item.classList.remove('is-open');
    const trigger = item.querySelector('.nav-trigger');
    trigger?.setAttribute('aria-expanded', 'false');
    if (returnFocus) trigger?.focus();
  };
  const closeAll = (except = null) => dropdowns.forEach(item => { if (item !== except) closeDropdown(item); });
  const openDropdown = item => {
    closeAll(item);
    item.classList.add('is-open');
    item.querySelector('.nav-trigger')?.setAttribute('aria-expanded', 'true');
  };

  menuToggle?.addEventListener('click', () => {
    const opened = nav?.classList.toggle('open') ?? false;
    menuToggle.setAttribute('aria-expanded', String(opened));
    if (!opened) closeAll();
  });

  dropdowns.forEach(item => {
    const trigger = item.querySelector('.nav-trigger');
    trigger?.addEventListener('click', () => {
      const shouldOpen = !item.classList.contains('is-open');
      if (shouldOpen) openDropdown(item); else closeDropdown(item);
    });
    item.addEventListener('keydown', event => {
      if (!['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) return;
      const links = [...item.querySelectorAll('.dropdown-panel a')];
      if (!links.length) return;
      event.preventDefault();
      openDropdown(item);
      const current = links.indexOf(document.activeElement);
      let next = event.key === 'Home' ? 0 : event.key === 'End' ? links.length - 1 : event.key === 'ArrowDown' ? (current + 1) % links.length : (current <= 0 ? links.length - 1 : current - 1);
      links[next].focus();
    });
  });

  document.addEventListener('click', event => {
    if (!event.target.closest('.nav-dropdown')) closeAll();
    if (event.target.closest('.dropdown-panel a')) {
      closeAll();
      nav?.classList.remove('open');
      menuToggle?.setAttribute('aria-expanded', 'false');
    }
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
      const active = dropdowns.find(item => item.classList.contains('is-open'));
      closeAll();
      nav?.classList.remove('open');
      menuToggle?.setAttribute('aria-expanded', 'false');
      if (active) active.querySelector('.nav-trigger')?.focus();
    }
  });
  window.addEventListener('resize', () => {
    if (window.innerWidth > 800) {
      nav?.classList.remove('open');
      menuToggle?.setAttribute('aria-expanded', 'false');
    }
    closeAll();
  });

  const searchForm = document.querySelector('.search');
  const searchInput = searchForm?.querySelector('input[name="q"]');
  searchForm?.addEventListener('submit', event => {
    if (!searchInput?.value.trim()) { event.preventDefault(); searchInput?.focus(); }
  });

  const accountType = document.getElementById('account_type');
  const residentPanel = document.getElementById('resident-fields');
  if (accountType && residentPanel) {
    const fields = [...residentPanel.querySelectorAll('input, select, textarea')];
    const syncResidentFields = () => {
      const isResident = accountType.value === 'RESIDENT';
      residentPanel.hidden = !isResident;
      fields.forEach(field => {
        field.disabled = !isResident;
        field.required = isResident && field.dataset.optional !== 'true';
      });
    };
    accountType.addEventListener('change', syncResidentFields);
    syncResidentFields();
  }

  const registrationForm = document.querySelector('.portal-form[action*="register.php"]');
  registrationForm?.addEventListener('submit', event => {
    const password = registrationForm.querySelector('[name="password"]');
    const confirm = registrationForm.querySelector('[name="confirm_password"]');
    if (password && confirm && password.value !== confirm.value) {
      event.preventDefault();
      confirm.setCustomValidity('The password confirmation does not match.');
      confirm.reportValidity();
    } else confirm?.setCustomValidity('');
  });
  document.querySelector('[name="confirm_password"]')?.addEventListener('input', event => event.currentTarget.setCustomValidity(''));

  const backToTop = document.createElement('button');
  backToTop.className = 'back-to-top'; backToTop.type = 'button'; backToTop.textContent = '↑';
  backToTop.setAttribute('aria-label', 'Back to top'); document.body.append(backToTop);
  const updateBackToTop = () => backToTop.classList.toggle('visible', window.scrollY > 500);
  window.addEventListener('scroll', updateBackToTop, { passive: true }); updateBackToTop();
  backToTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' }));
})();

// Expand exactly three additional article cards in place.
(() => {
  const button = document.getElementById('view-more-articles');
  if (!button) return;
  const extras = [...document.querySelectorAll('.article-extra')];
  button.addEventListener('click', () => {
    const expanded = button.getAttribute('aria-expanded') === 'true';
    extras.forEach(card => { card.hidden = expanded; });
    button.setAttribute('aria-expanded', String(!expanded));
    button.textContent = expanded ? 'View More Articles' : 'Show Fewer Articles';
  });
})();

// Accessibility controls: text scaling, high contrast, and basic English/Filipino UI labels.
(() => {
  'use strict';
  const root = document.documentElement;
  const body = document.body;
  const storedScale = Number(localStorage.getItem('portal-font-scale') || '1');
  let scale = Number.isFinite(storedScale) ? Math.min(1.25, Math.max(.9, storedScale)) : 1;
  const applyScale = () => root.style.setProperty('--portal-font-scale', String(scale));
  applyScale();
  document.querySelectorAll('[data-font]').forEach(button => button.addEventListener('click', () => {
    scale = button.dataset.font === 'up' ? Math.min(1.25, scale + .05) : Math.max(.9, scale - .05);
    applyScale(); localStorage.setItem('portal-font-scale', String(scale));
  }));
  const contrast = document.getElementById('contrast-toggle');
  if (localStorage.getItem('portal-high-contrast') === '1') { body.classList.add('high-contrast'); contrast?.setAttribute('aria-pressed', 'true'); }
  contrast?.addEventListener('click', () => {
    const active = body.classList.toggle('high-contrast');
    contrast.setAttribute('aria-pressed', String(active));
    localStorage.setItem('portal-high-contrast', active ? '1' : '0');
  });
  const translations = {
    'Skip to main content': 'Lumaktaw sa pangunahing nilalaman',
    'Request Documents': 'Humiling ng Dokumento',
    'File a Report': 'Magsumite ng Ulat',
    'Request Medicine': 'Humiling ng Gamot',
    'Transparency & Open Data': 'Transparency at Bukas na Datos',
    'Citizen’s Charter': 'Karta ng Mamamayan',
    'Laws & Issuances': 'Mga Batas at Kautusan',
    'Freedom of Information': 'Kalayaan sa Impormasyon',
    'Appointments': 'Mga Appointment',
    'Feedback & Complaints': 'Puna at Reklamo',
    'Privacy Notice': 'Paunawa sa Pagkapribado',
    'Create an Account': 'Gumawa ng Account',
    'Sign in': 'Mag-sign in',
    'Search': 'Maghanap'
  };
  const lang = document.getElementById('language-select');
  const savedLang = localStorage.getItem('portal-language') || 'en';
  if (lang) lang.value = savedLang;
  const original = new Map();
  document.querySelectorAll('[data-i18n]').forEach(el => original.set(el, el.textContent.trim()));
  const applyLanguage = () => {
    if (!lang) return;
    const fil = lang.value === 'fil'; root.lang = fil ? 'fil' : 'en';
    original.forEach((english, el) => { el.textContent = fil ? (translations[english] || english) : english; });
    document.body.dataset.language = lang.value; localStorage.setItem('portal-language', lang.value);
  };
  applyLanguage();
  lang?.addEventListener('change', applyLanguage);
  if ('serviceWorker' in navigator && location.protocol === 'https:') {
    window.addEventListener('load', () => navigator.serviceWorker.register('service-worker.js').catch(() => {}));
  }
})();

// Guided service form interactions: accessible validation, live count, and final review.
(() => {
  document.querySelectorAll('.guided-service-form').forEach(form => {
    const details = form.querySelector('[name="details"]');
    const counter = form.querySelector('[data-character-count]');
    const feedback = form.querySelector('.form-feedback');
    const submit = form.querySelector('button[type="submit"]');
    details?.addEventListener('input', () => { if (counter) counter.textContent = String(details.value.length); });
    form.addEventListener('submit', event => {
      feedback.textContent = '';
      if (!form.checkValidity()) {
        event.preventDefault();
        form.reportValidity();
        const firstInvalid = form.querySelector(':invalid');
        firstInvalid?.focus();
        feedback.textContent = 'Please complete the required fields and confirmations.';
        return;
      }
      const kind = form.dataset.serviceKind;
      const title = form.querySelector('[name="title"]')?.value.trim() || 'your request';
      const summary = kind === 'DOCUMENT' ? form.querySelector('[name="document_type"]')?.value : kind === 'REPORT' ? form.querySelector('[name="incident_category"]')?.value : form.querySelector('[name="medicine_name"]')?.value;
      if (!window.confirm(`Please confirm your ${kind.toLowerCase()} submission.

${title}
${summary || ''}

Submit this request to Barangay San Antonio?`)) {
        event.preventDefault(); return;
      }
      submit.disabled = true;
      submit.textContent = 'Submitting…';
    });
  });
})();

// Wireframe service-page dialogs: document access and crime-report choices.
(() => {
  const accountDialog = document.getElementById('account-choice');
  const crimeDialog = document.getElementById('crime-choice-modal');
  document.querySelectorAll('[data-account-choice]').forEach(link => link.addEventListener('click', event => {
    if (!accountDialog) return;
    event.preventDefault();
    if (typeof accountDialog.showModal === 'function') accountDialog.showModal();
    else window.location.href = link.href;
  }));
  document.querySelectorAll('[data-crime-choice]').forEach(card => card.addEventListener('click', () => {
    const label = card.dataset.crimeChoice || 'this report';
    const description = document.getElementById('crime-choice-description');
    if (description) description.textContent = `Choose whether to submit ${label} anonymously or as a formal complaint.`;
    if (crimeDialog?.showModal) crimeDialog.showModal();
  }));
  document.querySelectorAll('[data-close-modal]').forEach(button => button.addEventListener('click', () => button.closest('dialog')?.close()));
  [accountDialog, crimeDialog].forEach(dialog => dialog?.addEventListener('click', event => {
    if (event.target === dialog) dialog.close();
  }));
  document.querySelector('.guided-anonymous-form')?.addEventListener('submit', event => {
    const form = event.currentTarget;
    if (!form.checkValidity()) { event.preventDefault(); form.reportValidity(); return; }
    if (!window.confirm('Submit this report anonymously to Barangay San Antonio?')) event.preventDefault();
    else {
      const submit = form.querySelector('button[type="submit"]');
      if (submit) { submit.disabled = true; submit.textContent = 'Submitting…'; }
    }
  });
})();

// CSP-compatible confirmation for sensitive POST forms (no inline event handlers).
(() => {
  document.addEventListener('submit', event => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    const message = form.dataset.confirm;
    if (message && !window.confirm(message)) event.preventDefault();
  }, true);
})();
