/* ═══════════════════════════════════════════
   SSOMA SAFE — catalogo.js
   Visor de catálogo PDF en ventana emergente.
   Render por canvas con PDF.js: sin barra de
   herramientas, sin botón de descarga ni impresión.
═══════════════════════════════════════════ */

/* PDF.js v3.11.174 servido desde el propio dominio: el worker
   necesita ser del mismo origen para correr fuera del hilo principal */
const PDFJS_LIB_URL = 'lib/pdfjs/pdf.min.js';
const PDFJS_WORKER_URL = 'lib/pdfjs/pdf.worker.min.js';

const ZOOM_MIN = 0.5;
const ZOOM_MAX = 3;
const ZOOM_STEP = 0.25;
const MAX_CANVAS_PIXELS = 18e6;   /* Techo de píxeles por página (memoria) */
const RENDER_MARGIN = '150% 0px'; /* Cuánto se adelanta el render al scroll */

/* Estado del visor */
const cat = {
  el: {},            /* Referencias al DOM */
  src: null,         /* Ruta del PDF cargado */
  doc: null,         /* Documento PDF.js */
  pages: [],         /* { num, wrap, canvas, width, height, rendered, rendering, task } */
  zoom: 1,
  fitScale: 1,
  isOpen: false,
  lastFocused: null,
  libPromise: null,
  observer: null,
  resizeTimer: null,
  scrollTick: false,
};

document.addEventListener('DOMContentLoaded', setupCatalogo);

/* ─── Inicialización ─── */
function setupCatalogo() {
  const modal = document.getElementById('catalogo-modal');
  if (!modal) return;

  cat.el = {
    modal,
    panel: document.getElementById('catalogo-panel'),
    backdrop: document.getElementById('catalogo-backdrop'),
    viewer: document.getElementById('catalogo-viewer'),
    pages: document.getElementById('catalogo-pages'),
    loader: document.getElementById('catalogo-loader'),
    error: document.getElementById('catalogo-error'),
    zoomLabel: document.getElementById('catalogo-zoom-level'),
    zoomIn: document.getElementById('catalogo-zoom-in'),
    zoomOut: document.getElementById('catalogo-zoom-out'),
    indicator: document.getElementById('catalogo-page-indicator'),
    close: document.getElementById('catalogo-close'),
  };

  /* Disparadores */
  document.querySelectorAll('.catalogo-trigger').forEach(btn => {
    btn.addEventListener('click', () => openCatalogo(btn.dataset.pdf, btn));
  });

  cat.el.close.addEventListener('click', closeCatalogo);
  cat.el.backdrop.addEventListener('click', closeCatalogo);
  cat.el.zoomIn.addEventListener('click', () => setZoom(cat.zoom + ZOOM_STEP));
  cat.el.zoomOut.addEventListener('click', () => setZoom(cat.zoom - ZOOM_STEP));

  /* Teclado: Escape cierra, Tab queda atrapado dentro del modal */
  document.addEventListener('keydown', e => {
    if (!cat.isOpen) return;
    if (e.key === 'Escape') {
      e.preventDefault();
      closeCatalogo();
    } else if (e.key === 'Tab') {
      trapFocus(e);
    } else if ((e.key === '+' || e.key === '-') && (e.ctrlKey || e.metaKey)) {
      e.preventDefault();
      setZoom(cat.zoom + (e.key === '+' ? ZOOM_STEP : -ZOOM_STEP));
    }
  });

  /* Zoom con Ctrl + rueda dentro del visor */
  cat.el.viewer.addEventListener('wheel', e => {
    if (!e.ctrlKey && !e.metaKey) return;
    e.preventDefault();
    setZoom(cat.zoom + (e.deltaY < 0 ? ZOOM_STEP : -ZOOM_STEP));
  }, { passive: false });

  /* Indicador de página */
  cat.el.viewer.addEventListener('scroll', () => {
    if (cat.scrollTick) return;
    cat.scrollTick = true;
    requestAnimationFrame(() => {
      cat.scrollTick = false;
      updatePageIndicator();
    });
  }, { passive: true });

  /* Sin menú contextual ni arrastre de las páginas */
  cat.el.viewer.addEventListener('contextmenu', e => e.preventDefault());
  cat.el.viewer.addEventListener('dragstart', e => e.preventDefault());

  /* Reajuste al cambiar el tamaño de la ventana */
  window.addEventListener('resize', () => {
    if (!cat.isOpen || !cat.doc) return;
    clearTimeout(cat.resizeTimer);
    cat.resizeTimer = setTimeout(() => {
      computeFitScale();
      relayout();
    }, 200);
  });
}

/* ─── Abrir / cerrar ─── */
function openCatalogo(src, trigger) {
  if (!src || cat.isOpen) return;

  cat.lastFocused = trigger || document.activeElement;
  cat.isOpen = true;

  cat.el.modal.classList.remove('hidden');
  document.body.style.overflow = 'hidden';
  if (typeof lenis !== 'undefined' && lenis) lenis.stop();

  animateIn();
  cat.el.viewer.focus({ preventScroll: true });

  /* El documento ya cargado se reutiliza */
  if (cat.doc && cat.src === src) {
    computeFitScale();
    relayout();
    return;
  }
  loadDocument(src);
}

function closeCatalogo() {
  if (!cat.isOpen) return;
  cat.isOpen = false;

  animateOut(() => {
    cat.el.modal.classList.add('hidden');
  });

  document.body.style.overflow = '';
  if (typeof lenis !== 'undefined' && lenis) lenis.start();
  if (cat.lastFocused) cat.lastFocused.focus({ preventScroll: true });
}

function animateIn() {
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (typeof gsap === 'undefined' || reduce) {
    cat.el.backdrop.style.opacity = '1';
    cat.el.panel.style.opacity = '1';
    cat.el.panel.style.transform = 'none';
    return;
  }
  gsap.fromTo(cat.el.backdrop, { opacity: 0 }, { opacity: 1, duration: 0.3, ease: 'power2.out' });
  gsap.fromTo(cat.el.panel,
    { opacity: 0, y: 24, scale: 0.97 },
    { opacity: 1, y: 0, scale: 1, duration: 0.45, ease: 'power3.out' }
  );
}

function animateOut(done) {
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (typeof gsap === 'undefined' || reduce) {
    done();
    return;
  }
  gsap.to(cat.el.backdrop, { opacity: 0, duration: 0.25, ease: 'power2.in' });
  gsap.to(cat.el.panel, {
    opacity: 0, y: 16, scale: 0.98, duration: 0.25, ease: 'power2.in', onComplete: done
  });
}

/* Mantiene el foco dentro del modal mientras está abierto */
function trapFocus(e) {
  const focusables = cat.el.panel.querySelectorAll('button, [href], [tabindex]:not([tabindex="-1"])');
  if (!focusables.length) return;
  const first = focusables[0];
  const last = focusables[focusables.length - 1];

  if (e.shiftKey && document.activeElement === first) {
    e.preventDefault();
    last.focus();
  } else if (!e.shiftKey && document.activeElement === last) {
    e.preventDefault();
    first.focus();
  }
}

/* ─── Carga de PDF.js (bajo demanda) ─── */
function ensurePdfJs() {
  if (typeof pdfjsLib !== 'undefined') return Promise.resolve();
  if (cat.libPromise) return cat.libPromise;

  cat.libPromise = new Promise((resolve, reject) => {
    const script = document.createElement('script');
    script.src = PDFJS_LIB_URL;
    script.async = true;
    script.onload = () => {
      if (typeof pdfjsLib === 'undefined') {
        reject(new Error('PDF.js no se inicializó'));
        return;
      }
      pdfjsLib.GlobalWorkerOptions.workerSrc = PDFJS_WORKER_URL;
      resolve();
    };
    script.onerror = () => reject(new Error('No se pudo cargar PDF.js'));
    document.head.appendChild(script);
  });

  return cat.libPromise;
}

/* ─── Carga del documento ─── */
async function loadDocument(src) {
  showLoader(true);
  showError(false);
  destroyPages();

  try {
    await ensurePdfJs();

    const task = pdfjsLib.getDocument({
      url: encodeURI(src),
      isEvalSupported: false,
      disableAutoFetch: false,
    });
    const doc = await task.promise;

    cat.doc = doc;
    cat.src = src;
    cat.zoom = 1;

    /* Dimensiones naturales de cada página (para reservar el alto real) */
    const nums = Array.from({ length: doc.numPages }, (_, i) => i + 1);
    const sizes = await Promise.all(nums.map(async num => {
      const page = await doc.getPage(num);
      const vp = page.getViewport({ scale: 1 });
      return { num, width: vp.width, height: vp.height };
    }));

    buildPages(sizes);
    computeFitScale();
    relayout();
    updateZoomLabel();
    updatePageIndicator();
    showLoader(false);

    /* Segunda pasada: la barra de scroll aparece recién con el alto real */
    requestAnimationFrame(() => {
      const previous = cat.fitScale;
      computeFitScale();
      if (Math.abs(previous - cat.fitScale) > 0.005) relayout();
    });
  } catch (err) {
    console.warn('[catálogo] No se pudo abrir el PDF:', src, err);
    showLoader(false);
    showError(true);
  }
}

/* ─── Construcción del DOM de páginas ─── */
function buildPages(sizes) {
  const frag = document.createDocumentFragment();

  cat.pages = sizes.map(size => {
    const wrap = document.createElement('div');
    wrap.className = 'catalogo-page';
    wrap.dataset.page = size.num;

    const canvas = document.createElement('canvas');
    canvas.className = 'catalogo-canvas';
    canvas.setAttribute('role', 'img');
    canvas.setAttribute('aria-label', `Página ${size.num} de ${sizes.length}`);
    wrap.appendChild(canvas);

    frag.appendChild(wrap);
    return { ...size, wrap, canvas, rendered: false, rendering: false, task: null };
  });

  cat.el.pages.appendChild(frag);

  /* Render anticipado de las páginas cercanas al viewport */
  if (cat.observer) cat.observer.disconnect();
  cat.observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      const item = cat.pages[Number(entry.target.dataset.page) - 1];
      if (item) renderPage(item);
    });
  }, { root: cat.el.viewer, rootMargin: RENDER_MARGIN });

  cat.pages.forEach(item => cat.observer.observe(item.wrap));
}

function destroyPages() {
  if (cat.observer) cat.observer.disconnect();
  cat.pages.forEach(item => {
    if (item.task) item.task.cancel();
    item.canvas.width = 0;
    item.canvas.height = 0;
  });
  cat.pages = [];
  cat.el.pages.innerHTML = '';
}

/* ─── Escala y layout ─── */
/* Escala base: la página completa entra en el visor ("ajustar a página") */
function computeFitScale() {
  const style = getComputedStyle(cat.el.pages);
  const padX = parseFloat(style.paddingLeft) + parseFloat(style.paddingRight);
  const padY = parseFloat(style.paddingTop) + parseFloat(style.paddingBottom);
  const availableW = cat.el.pages.clientWidth - padX - 2;
  const availableH = cat.el.viewer.clientHeight - padY;

  const widest = cat.pages.reduce((max, p) => Math.max(max, p.width), 0);
  const tallest = cat.pages.reduce((max, p) => Math.max(max, p.height), 0);
  if (widest <= 0 || tallest <= 0 || availableW <= 0) {
    cat.fitScale = 1;
    return;
  }

  const byWidth = availableW / widest;
  const byHeight = availableH > 0 ? availableH / tallest : byWidth;
  cat.fitScale = Math.min(byWidth, byHeight);
}

function currentScale() {
  return cat.fitScale * cat.zoom;
}

/* Reserva el tamaño de cada página e invalida lo ya dibujado */
function relayout() {
  const scale = currentScale();
  const viewer = cat.el.viewer;
  const ratio = viewer.scrollHeight > 0 ? viewer.scrollTop / viewer.scrollHeight : 0;

  cat.pages.forEach(item => {
    item.wrap.style.width = `${Math.round(item.width * scale)}px`;
    item.wrap.style.height = `${Math.round(item.height * scale)}px`;
    if (item.task) item.task.cancel();
    item.task = null;
    item.rendering = false;
    item.rendered = false;
  });

  /* El imán entre páginas solo estorba cuando hay zoom */
  viewer.classList.toggle('snap-pages', cat.zoom <= 1);

  viewer.scrollTop = ratio * viewer.scrollHeight;
  renderVisiblePages();
  updatePageIndicator();
}

/* Dibuja las páginas que están (o casi) en pantalla */
function renderVisiblePages() {
  const rect = cat.el.viewer.getBoundingClientRect();
  const margin = rect.height;

  cat.pages.forEach(item => {
    const box = item.wrap.getBoundingClientRect();
    const visible = box.bottom > rect.top - margin && box.top < rect.bottom + margin;
    if (visible) renderPage(item);
  });
}

/* ─── Render de una página ─── */
async function renderPage(item) {
  if (!cat.doc || item.rendered || item.rendering) return;
  item.rendering = true;

  const scale = currentScale();

  try {
    const page = await cat.doc.getPage(item.num);
    const viewport = page.getViewport({ scale });

    /* Nitidez según la pantalla, con techo de memoria */
    let dpr = Math.min(window.devicePixelRatio || 1, 2);
    while (dpr > 1 && viewport.width * viewport.height * dpr * dpr > MAX_CANVAS_PIXELS) {
      dpr -= 0.25;
    }

    const canvas = item.canvas;
    canvas.width = Math.floor(viewport.width * dpr);
    canvas.height = Math.floor(viewport.height * dpr);

    const ctx = canvas.getContext('2d', { alpha: false });
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    item.task = page.render({
      canvasContext: ctx,
      viewport,
      transform: dpr !== 1 ? [dpr, 0, 0, dpr, 0, 0] : null,
    });

    await item.task.promise;
    item.task = null;
    item.rendered = true;
    item.wrap.classList.add('is-rendered');
  } catch (err) {
    if (!err || err.name !== 'RenderingCancelledException') {
      console.warn(`[catálogo] Error al dibujar la página ${item.num}:`, err);
    }
  } finally {
    item.rendering = false;
  }
}

/* ─── Zoom ─── */
function setZoom(value) {
  const next = Math.min(ZOOM_MAX, Math.max(ZOOM_MIN, Math.round(value * 100) / 100));
  if (next === cat.zoom || !cat.doc) return;
  cat.zoom = next;
  updateZoomLabel();
  relayout();
}

function updateZoomLabel() {
  if (!cat.el.zoomLabel) return;
  cat.el.zoomLabel.textContent = `${Math.round(cat.zoom * 100)}%`;
  cat.el.zoomOut.disabled = cat.zoom <= ZOOM_MIN;
  cat.el.zoomIn.disabled = cat.zoom >= ZOOM_MAX;
  [cat.el.zoomOut, cat.el.zoomIn].forEach(btn => {
    btn.classList.toggle('opacity-30', btn.disabled);
    btn.classList.toggle('cursor-not-allowed', btn.disabled);
  });
}

/* ─── Indicador de página ─── */
function updatePageIndicator() {
  if (!cat.el.indicator || !cat.pages.length) return;

  const rect = cat.el.viewer.getBoundingClientRect();
  const center = rect.top + rect.height / 2;

  let current = 1;
  let closest = Infinity;
  cat.pages.forEach(item => {
    const box = item.wrap.getBoundingClientRect();
    const distance = Math.abs(box.top + box.height / 2 - center);
    if (distance < closest) {
      closest = distance;
      current = item.num;
    }
  });

  cat.el.indicator.textContent = `${current} / ${cat.pages.length}`;
}

/* ─── Estados ─── */
function showLoader(visible) {
  cat.el.loader.classList.toggle('hidden', !visible);
}

function showError(visible) {
  cat.el.error.classList.toggle('hidden', !visible);
}
