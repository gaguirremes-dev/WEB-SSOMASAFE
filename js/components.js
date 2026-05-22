/* ═══════════════════════════════════════════
   SSOMA SAFE — components.js
   Navbar mobile, formulario, microinteracciones
═══════════════════════════════════════════ */

document.addEventListener('DOMContentLoaded', () => {
  setupMobileMenu();
  setupContactForm();
  setupMicroInteractions();
  setupCursorGlow();
});

/* ─── Menú móvil ─── */
function setupMobileMenu() {
  const btn = document.getElementById('menu-btn');
  const menu = document.getElementById('mobile-menu');
  if (!btn || !menu) return;

  btn.addEventListener('click', () => {
    const isOpen = !menu.classList.contains('hidden');
    if (isOpen) {
      closeMenu();
    } else {
      openMenu();
    }
  });

  /* Cerrar al clic fuera */
  document.addEventListener('click', e => {
    if (!btn.contains(e.target) && !menu.contains(e.target)) {
      closeMenu();
    }
  });

  /* Cerrar con Escape */
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeMenu();
  });

  function openMenu() {
    menu.classList.remove('hidden');
    btn.classList.add('open');
    btn.setAttribute('aria-expanded', 'true');
    menu.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    if (typeof gsap !== 'undefined') {
      gsap.fromTo(menu,
        { opacity: 0, y: -10 },
        { opacity: 1, y: 0, duration: 0.25, ease: 'power2.out' }
      );
    }
  }

  function closeMenu() {
    btn.classList.remove('open');
    btn.setAttribute('aria-expanded', 'false');
    menu.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';

    if (typeof gsap !== 'undefined') {
      gsap.to(menu, {
        opacity: 0, y: -8, duration: 0.2, ease: 'power2.in',
        onComplete: () => menu.classList.add('hidden')
      });
    } else {
      menu.classList.add('hidden');
    }
  }
}

/* Función global para links dentro del menú móvil */
window.closeMobileMenu = function () {
  document.getElementById('mobile-menu')?.classList.add('hidden');
  document.getElementById('menu-btn')?.classList.remove('open');
  document.getElementById('menu-btn')?.setAttribute('aria-expanded', 'false');
  document.body.style.overflow = '';
};

/* ─── Formulario de contacto ─── */
function setupContactForm() {
  const form = document.getElementById('contact-form');
  if (!form) return;

  const fields = {
    nombre: { required: true, minLength: 2 },
    empresa: { required: true, minLength: 2 },
    email: { required: true, type: 'email' },
    servicio: { required: true },
  };

  /* Validación en tiempo real */
  Object.keys(fields).forEach(name => {
    const el = form.querySelector(`[name="${name}"]`);
    if (!el) return;
    el.addEventListener('blur', () => validateField(el, fields[name]));
    el.addEventListener('input', () => {
      if (el.classList.contains('error')) validateField(el, fields[name]);
    });
  });

  form.addEventListener('submit', async e => {
    e.preventDefault();
    let isValid = true;

    Object.keys(fields).forEach(name => {
      const el = form.querySelector(`[name="${name}"]`);
      if (!validateField(el, fields[name])) isValid = false;
    });

    if (!isValid) return;

    const btn = form.querySelector('button[type="submit"]');
    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Enviando...';

    /* Simulación de envío — reemplazar con endpoint real */
    await new Promise(res => setTimeout(res, 1800));

    btn.disabled = false;
    btn.innerHTML = originalContent;

    form.reset();
    const success = document.getElementById('form-success');
    if (success) {
      success.classList.remove('hidden');
      setTimeout(() => success.classList.add('hidden'), 5000);
    }
  });

  function validateField(el, rules) {
    if (!el) return true;
    const val = el.value.trim();
    let error = '';

    if (rules.required && !val) {
      error = 'Este campo es obligatorio';
    } else if (rules.type === 'email' && val && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
      error = 'Correo inválido';
    } else if (rules.minLength && val.length < rules.minLength) {
      error = `Mínimo ${rules.minLength} caracteres`;
    }

    const errEl = el.nextElementSibling;
    if (error) {
      el.classList.add('error');
      if (errEl?.classList.contains('form-error')) {
        errEl.textContent = error;
        errEl.classList.remove('hidden');
      }
      return false;
    } else {
      el.classList.remove('error');
      if (errEl?.classList.contains('form-error')) errEl.classList.add('hidden');
      return true;
    }
  }
}

/* ─── Microinteracciones de botones ─── */
function setupMicroInteractions() {
  /* Ripple en botones primarios */
  document.querySelectorAll('.cta-primary, #contact-form button[type="submit"]').forEach(btn => {
    btn.addEventListener('click', function (e) {
      const rect = this.getBoundingClientRect();
      const ripple = document.createElement('span');
      const size = Math.max(rect.width, rect.height);
      ripple.style.cssText = `
        position: absolute;
        border-radius: 50%;
        background: rgba(255,255,255,0.25);
        width: ${size}px;
        height: ${size}px;
        left: ${e.clientX - rect.left - size / 2}px;
        top: ${e.clientY - rect.top - size / 2}px;
        transform: scale(0);
        pointer-events: none;
      `;
      this.style.position = 'relative';
      this.style.overflow = 'hidden';
      this.appendChild(ripple);

      if (typeof gsap !== 'undefined') {
        gsap.to(ripple, {
          scale: 2.5, opacity: 0, duration: 0.6, ease: 'power2.out',
          onComplete: () => ripple.remove()
        });
      } else {
        setTimeout(() => ripple.remove(), 600);
      }
    });
  });

  /* Magnetic effect en links de redes sociales */
  document.querySelectorAll('footer a[aria-label]').forEach(el => {
    el.addEventListener('mousemove', e => {
      const rect = el.getBoundingClientRect();
      const x = (e.clientX - rect.left - rect.width / 2) * 0.25;
      const y = (e.clientY - rect.top - rect.height / 2) * 0.25;
      if (typeof gsap !== 'undefined') {
        gsap.to(el, { x, y, duration: 0.3, ease: 'power2.out' });
      }
    });
    el.addEventListener('mouseleave', () => {
      if (typeof gsap !== 'undefined') {
        gsap.to(el, { x: 0, y: 0, duration: 0.4, ease: 'elastic.out(1, 0.5)' });
      }
    });
  });
}

/* ─── Cursor glow (solo desktop) ─── */
function setupCursorGlow() {
  if (window.matchMedia('(pointer: coarse)').matches) return;

  const glow = document.createElement('div');
  glow.style.cssText = `
    position: fixed;
    width: 300px;
    height: 300px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(127,180,229,0.06) 0%, transparent 70%);
    pointer-events: none;
    z-index: 0;
    transform: translate(-50%, -50%);
    transition: opacity 0.3s;
    top: 0; left: 0;
  `;
  document.body.appendChild(glow);

  let mouseX = 0, mouseY = 0;
  document.addEventListener('mousemove', e => {
    mouseX = e.clientX;
    mouseY = e.clientY;
  });

  function animateGlow() {
    if (typeof gsap !== 'undefined') {
      gsap.set(glow, { x: mouseX, y: mouseY });
    }
    requestAnimationFrame(animateGlow);
  }
  animateGlow();

  /* Ocultar cuando el cursor sale */
  document.addEventListener('mouseleave', () => glow.style.opacity = '0');
  document.addEventListener('mouseenter', () => glow.style.opacity = '1');
}
