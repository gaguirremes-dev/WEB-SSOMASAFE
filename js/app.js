/* ═══════════════════════════════════════════
   SSOMA SAFE — app.js
   Inicialización principal y lazy loading
═══════════════════════════════════════════ */

document.addEventListener('DOMContentLoaded', () => {
  setupLazyImages();
  setupSkipLink();
  setupScrollProgress();
});

/* ─── Lazy loading de imágenes ─── */
function setupLazyImages() {
  if (!('IntersectionObserver' in window)) return;

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      const img = entry.target;
      const src = img.dataset.src;
      if (src) {
        img.src = src;
        img.removeAttribute('data-src');
        img.classList.remove('lazy');
      }
      observer.unobserve(img);
    });
  }, { rootMargin: '200px 0px' });

  document.querySelectorAll('img[data-src]').forEach(img => observer.observe(img));
}

/* ─── Skip to main content ─── */
function setupSkipLink() {
  const existing = document.querySelector('.skip-link');
  if (existing) return;

  const skip = document.createElement('a');
  skip.href = '#main-content';
  skip.className = 'skip-link';
  skip.textContent = 'Ir al contenido principal';
  document.body.insertBefore(skip, document.body.firstChild);
}

/* ─── Barra de progreso de scroll ─── */
function setupScrollProgress() {
  const bar = document.createElement('div');
  bar.style.cssText = `
    position: fixed;
    top: 0;
    left: 0;
    height: 3px;
    width: 0%;
    background: linear-gradient(90deg, #003F8C, #7FB4E5);
    z-index: 9999;
    transition: width 0.1s linear;
    pointer-events: none;
  `;
  bar.setAttribute('role', 'progressbar');
  bar.setAttribute('aria-label', 'Progreso de lectura');
  document.body.appendChild(bar);

  const updateProgress = () => {
    const scrollTop = window.scrollY;
    const docHeight = document.documentElement.scrollHeight - window.innerHeight;
    const pct = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
    bar.style.width = pct + '%';
  };

  window.addEventListener('scroll', updateProgress, { passive: true });
}
