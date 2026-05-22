/* ═══════════════════════════════════════════
   SSOMA SAFE — animations.js
   GSAP + ScrollTrigger: Hero, reveals, counters,
   parallax, navbar y partículas
═══════════════════════════════════════════ */

function waitForGSAP(cb) {
  if (typeof gsap !== 'undefined' && typeof ScrollTrigger !== 'undefined') {
    cb();
  } else {
    setTimeout(() => waitForGSAP(cb), 80);
  }
}

waitForGSAP(initAnimations);

function initAnimations() {
  gsap.registerPlugin(ScrollTrigger);
  document.documentElement.classList.add('gsap-ready');

  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    /* Sin animaciones — revelar todo de una vez */
    gsap.set('.hero-badge,.hero-headline,.hero-sub,.hero-ctas,.hero-stats,.scroll-indicator,.reveal-up,.reveal-left,.reveal-right,.reveal-scale', {
      opacity: 1, x: 0, y: 0, scale: 1, clearProps: 'all'
    });
    return;
  }

  heroEntrance();
  setupRevealAnimations();
  setupCounters();
  setupParallax();
  setupNavbarScroll();
}

/* ─── Hero entrance ─── */
function heroEntrance() {
  const tl = gsap.timeline({ delay: 0.2 });

  tl.to('.hero-badge', {
    opacity: 1, y: 0, duration: 0.8, ease: 'power3.out'
  })
  .to('.hero-headline', {
    opacity: 1, y: 0, duration: 1, ease: 'power3.out'
  }, '-=0.4')
  .to('.hero-sub', {
    opacity: 1, y: 0, duration: 0.8, ease: 'power3.out'
  }, '-=0.5')
  .to('.hero-ctas', {
    opacity: 1, y: 0, duration: 0.7, ease: 'power3.out'
  }, '-=0.4')
  .to('.hero-stats', {
    opacity: 1, y: 0, duration: 0.7, ease: 'power3.out'
  }, '-=0.3')
  .to('.scroll-indicator', {
    opacity: 1, y: 0, duration: 0.6, ease: 'power2.out'
  }, '-=0.2');

  /* Parallax suave del video hero al scroll */
  gsap.to('#hero-video', {
    yPercent: 20,
    ease: 'none',
    scrollTrigger: {
      trigger: '#hero',
      start: 'top top',
      end: 'bottom top',
      scrub: 1.5,
    }
  });
}

/* ─── Reveal on scroll ─── */
function setupRevealAnimations() {
  /* reveal-up: todos los elementos con esa clase en secciones */
  ScrollTrigger.batch('.reveal-up', {
    onEnter: (els) => {
      gsap.to(els, {
        opacity: 1,
        y: 0,
        duration: 0.8,
        stagger: 0.12,
        ease: 'power3.out',
        overwrite: true,
      });
    },
    onLeaveBack: (els) => {
      gsap.to(els, {
        opacity: 0,
        y: 40,
        duration: 0.4,
        stagger: 0.06,
        ease: 'power2.in',
        overwrite: true,
      });
    },
    start: 'top 88%',
    end: 'bottom 10%',
  });

  /* Service cards — stagger especial */
  ScrollTrigger.create({
    trigger: '#servicios',
    start: 'top 75%',
    onEnter: () => {
      gsap.to('.service-card', {
        opacity: 1,
        y: 0,
        duration: 0.7,
        stagger: 0.08,
        ease: 'power3.out',
      });
    },
    once: true,
  });

  /* Benefit items */
  ScrollTrigger.create({
    trigger: '#beneficios',
    start: 'top 75%',
    onEnter: () => {
      gsap.to('.benefit-item', {
        opacity: 1,
        y: 0,
        duration: 0.6,
        stagger: 0.07,
        ease: 'power3.out',
      });
    },
    once: true,
  });

  /* Legal cards */
  ScrollTrigger.create({
    trigger: '#legal',
    start: 'top 75%',
    onEnter: () => {
      gsap.to('.legal-card', {
        opacity: 1,
        y: 0,
        duration: 0.7,
        stagger: 0.1,
        ease: 'power3.out',
      });
    },
    once: true,
  });
}

/* ─── Animated counters ─── */
function setupCounters() {
  const counters = document.querySelectorAll('.counter');
  if (!counters.length) return;

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      observer.unobserve(entry.target);

      const el = entry.target;
      const target = parseInt(el.dataset.target, 10);
      const suffix = el.dataset.suffix || '';
      const obj = { val: 0 };

      gsap.to(obj, {
        val: target,
        duration: 2,
        ease: 'power2.out',
        onUpdate: () => {
          el.textContent = Math.round(obj.val) + suffix;
        },
        onComplete: () => {
          el.textContent = target + suffix;
        }
      });
    });
  }, { threshold: 0.5 });

  counters.forEach(c => observer.observe(c));
}

/* ─── Parallax secciones ─── */
function setupParallax() {
  /* Fondo decorativo de sección nosotros */
  gsap.to('#nosotros .absolute', {
    yPercent: -15,
    ease: 'none',
    scrollTrigger: {
      trigger: '#nosotros',
      start: 'top bottom',
      end: 'bottom top',
      scrub: 2,
    }
  });

  /* Paralaje ligero en cards de servicios */
  gsap.utils.toArray('.service-card').forEach((card, i) => {
    const dir = i % 2 === 0 ? -8 : 8;
    gsap.to(card, {
      y: dir,
      ease: 'none',
      scrollTrigger: {
        trigger: card,
        start: 'top bottom',
        end: 'bottom top',
        scrub: 2,
      }
    });
  });
}

/* ─── Navbar al scroll ─── */
function setupNavbarScroll() {
  const navbar = document.getElementById('navbar');
  if (!navbar) return;

  ScrollTrigger.create({
    start: 80,
    onEnter: () => navbar.classList.add('scrolled'),
    onLeaveBack: () => navbar.classList.remove('scrolled'),
  });

  /* Active link highlight */
  const sections = document.querySelectorAll('section[id]');
  const navLinks = document.querySelectorAll('.nav-link');

  ScrollTrigger.create({
    trigger: 'body',
    start: 'top top',
    end: 'max',
    onUpdate: (self) => {
      let current = '';
      sections.forEach(sec => {
        const top = sec.getBoundingClientRect().top;
        if (top <= 120) current = sec.id;
      });
      navLinks.forEach(link => {
        link.classList.toggle('active', link.getAttribute('href') === `#${current}`);
      });
    }
  });
}

/* ─── Partículas hero ─── */
function setupParticles() {
  const container = document.getElementById('particles');
  if (!container) return;

  const count = window.innerWidth < 768 ? 12 : 24;

  for (let i = 0; i < count; i++) {
    const size = Math.random() * 4 + 2;
    const p = document.createElement('div');
    p.className = 'particle';
    p.style.cssText = `
      width: ${size}px;
      height: ${size}px;
      left: ${Math.random() * 100}%;
      top: ${Math.random() * 100}%;
      --duration: ${Math.random() * 6 + 5}s;
      --delay: ${Math.random() * 6}s;
      opacity: 0;
    `;
    container.appendChild(p);
  }
}
