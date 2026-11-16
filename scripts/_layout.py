"""Maquetación compartida de las páginas secundarias (servicios, guías, legales y 404).

Lo usan build_service_pages.py y build_legal_pages.py. Aquí viven la cabecera, el héroe, la tarjeta
de contacto, el pie, los iconos SVG y la barra de CTA móvil, para que las 9 páginas sean coherentes.
Los estilos de texto largo están en css/pages.css; el resto son utilidades de Tailwind
(tras cambiar clases ejecuta `npm run build:css`).
"""
import os
import re
import urllib.parse

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
SITE = 'https://www.ssomasafe.com'
WA_NUMBER = '51943704298'
EMAIL = 'comercial@ssomasafe.com'
PHONE = '+51 943 704 298'

# (slug, texto) — orden del menú del pie y del 404
SERVICE_LINKS = [
    ('auditoria-sst-sunafil', 'Auditoría SST y SUNAFIL'),
    ('matriz-iperc', 'Matriz IPERC'),
    ('vigilancia-ocupacional', 'Vigilancia ocupacional'),
    ('epp-certificados', 'EPP certificados'),
    ('consultoria-sst-chiclayo', 'Consultoría SST en Chiclayo'),
    ('ley-29783-obligaciones-empleador', 'Guía de la Ley 29783'),
]

_P = '<path stroke-linecap="round" stroke-linejoin="round" d="%s"/>'
_ICONS = {
    'check': _P % 'M5 13l4 4L19 7',
    'arrow-right': _P % 'M13 7l5 5m0 0l-5 5m5-5H6',
    'arrow-left': _P % 'M11 17l-5-5m0 0l5-5m-5 5h12',
    'shield': _P % 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
    'phone': _P % 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z',
    'mail': _P % 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    'pin': _P % 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z' + _P % 'M15 11a3 3 0 11-6 0 3 3 0 016 0z',
    'home': _P % 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
}
_WA_PATH = ('M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z')


def icon(name, cls='h-5 w-5'):
    if name == 'whatsapp':
        return f'<svg class="{cls}" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="{_WA_PATH}"/></svg>'
    return f'<svg class="{cls}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">{_ICONS[name]}</svg>'


def wa(text):
    return f'https://wa.me/{WA_NUMBER}?text=' + urllib.parse.quote(text)


def nbsp(html):
    """Evita que «N.° 29783» se parta entre líneas (solo en el <body>, no en metas ni JSON-LD)."""
    head_part, sep, body_part = html.partition('<body')
    return head_part + sep + body_part.replace('N.° ', 'N.°&nbsp;')


def clean(html):
    """Quita las clases de utilidad de <p>, <ul>, <h2> y <a> del contenido: css/pages.css las estiliza."""
    return re.sub(r'<(p|ul|h2|a)\s+class="[^"]*"', r'<\1', html)


def slugify(text):
    t = re.sub(r'<[^>]+>', '', text).lower()
    for a, b in zip('áéíóúñü', 'aeiounu'):
        t = t.replace(a, b)
    return re.sub(r'[^a-z0-9]+', '-', t).strip('-')


def add_ids(html):
    """Añade id a cada <h2> y devuelve (html, [(id, texto)]) para el índice."""
    toc = []

    def sub(m):
        text = re.sub(r'<[^>]+>', '', m.group(2)).strip()
        hid = slugify(text) or f's{len(toc) + 1}'
        toc.append((hid, text))
        return f'<h2{m.group(1)} id="{hid}">{m.group(2)}</h2>'
    return re.sub(r'<h2([^>]*)>(.*?)</h2>', sub, html, flags=re.S), toc


# ───────────────────────── bloques ─────────────────────────
BTN = ('inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-sm font-bold '
       'transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-light focus-visible:ring-offset-2 '
       'motion-reduce:transition-none')
BTN_PRIMARY = f'{BTN} bg-brand-primary text-white shadow-md hover:bg-blue-800 hover:shadow-lg'
BTN_WHITE = f'{BTN} bg-white text-brand-primary shadow-md hover:bg-blue-50'
BTN_OUTLINE_LIGHT = f'{BTN} border border-white/40 text-white hover:bg-white/10'
BTN_OUTLINE = f'{BTN} border border-brand-primary text-brand-primary hover:bg-white'


def head(title, desc, robots, canonical, pre, extra=''):
    canon = f'<link rel="canonical" href="{canonical}" />' if canonical else ''
    return f'''<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="theme-color" content="#003F8C" />
  <title>{title}</title>
  <meta name="description" content="{desc}" />
  <meta name="robots" content="{robots}" />
  {canon}
{extra}  <link rel="icon" href="{pre}favicon.ico" sizes="any" />
  <link rel="icon" type="image/png" sizes="32x32" href="{pre}assets/icons/favicon-32.png" />
  <link rel="apple-touch-icon" href="{pre}assets/icons/apple-touch-icon.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="{pre}css/tailwind.min.css?v=3" />
  <link rel="stylesheet" href="{pre}css/pages.css?v=1" />
</head>
'''


def header(pre, wa_text='Hola, quiero solicitar un diagnóstico SSOMA.'):
    return f'''<a href="#contenido" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-[100] focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:font-semibold focus:text-brand-primary focus:shadow-lg">Saltar al contenido</a>
<header class="sticky top-0 z-50 border-b border-gray-200 bg-white/95 backdrop-blur">
  <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-2.5 lg:px-8">
    <a href="/" aria-label="SSOMA SAFE — Inicio" class="shrink-0"><img src="{pre}assets/img/logo-ssoma-sm.png" alt="SSOMA SAFE" width="73" height="48" class="h-11 w-auto" /></a>
    <nav class="hidden items-center gap-8 text-[15px] font-semibold text-brand-dark md:flex" aria-label="Principal">
      <a href="/#nosotros" class="transition hover:text-brand-primary">Nosotros</a>
      <a href="/#servicios" class="transition hover:text-brand-primary">Servicios</a>
      <a href="/#contacto" class="transition hover:text-brand-primary">Contacto</a>
    </nav>
    <a href="{wa(wa_text)}" target="_blank" rel="noopener noreferrer" class="{BTN_PRIMARY}">{icon('whatsapp', 'h-4 w-4')}<span>Solicitar diagnóstico</span></a>
  </div>
</header>
'''


def footer(pre):
    svc = ''.join(f'<li><a href="{pre}{s}.html" class="text-gray-400 transition hover:text-white">{t}</a></li>' for s, t in SERVICE_LINKS)
    return f'''<footer class="bg-brand-dark text-white" role="contentinfo">
  <div class="mx-auto max-w-6xl px-5 py-14 lg:px-8">
    <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
      <div>
        <img src="{pre}assets/img/logo-ssoma-light-sm.png" alt="SSOMA SAFE" width="98" height="64" loading="lazy" class="h-16 w-auto" />
        <p class="mt-4 max-w-xs text-sm leading-relaxed text-gray-400">Consultoría especializada en Seguridad y Salud Ocupacional, Medio Ambiente e Insumos Industriales.</p>
      </div>
      <div>
        <p class="mb-4 text-xs font-bold uppercase tracking-widest text-gray-400">Servicios</p>
        <ul class="space-y-2.5 text-sm">{svc}</ul>
      </div>
      <div>
        <p class="mb-4 text-xs font-bold uppercase tracking-widest text-gray-400">Legal</p>
        <ul class="space-y-2.5 text-sm">
          <li><a href="{pre}politica-de-privacidad.html" class="text-gray-400 transition hover:text-white">Política de Privacidad</a></li>
          <li><a href="{pre}terminos-y-condiciones.html" class="text-gray-400 transition hover:text-white">Términos y Condiciones</a></li>
          <li><a href="{pre}Libro-reclamaciones/libro-de-reclamaciones.php" class="text-gray-400 transition hover:text-white">Libro de Reclamaciones</a></li>
        </ul>
      </div>
      <div>
        <p class="mb-4 text-xs font-bold uppercase tracking-widest text-gray-400">Contacto</p>
        <ul class="space-y-3 text-sm text-gray-400">
          <li class="flex gap-2.5">{icon('pin', 'mt-0.5 h-4 w-4 shrink-0 text-brand-light')}<span>Av. República de Chile 324, Lima</span></li>
          <li class="flex gap-2.5">{icon('pin', 'mt-0.5 h-4 w-4 shrink-0 text-brand-light')}<span>Lapoint 1221, Chiclayo</span></li>
          <li class="flex gap-2.5">{icon('phone', 'mt-0.5 h-4 w-4 shrink-0 text-brand-light')}<a href="tel:+{WA_NUMBER}" class="transition hover:text-white">{PHONE}</a></li>
          <li class="flex gap-2.5">{icon('mail', 'mt-0.5 h-4 w-4 shrink-0 text-brand-light')}<a href="mailto:{EMAIL}" class="transition hover:text-white">{EMAIL}</a></li>
        </ul>
      </div>
    </div>
    <div class="mt-12 flex flex-col items-center justify-between gap-3 border-t border-white/10 pt-6 text-xs text-gray-400 sm:flex-row">
      <p>&copy; 2026 SSOMA SAFE S.A.C. Todos los derechos reservados.</p>
      <p>RUC 20607018287 &nbsp;|&nbsp; Lima &amp; Chiclayo, Perú</p>
    </div>
  </div>
</footer>
'''


def mobile_cta(wa_text):
    return f'''<div class="no-print fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-white/95 p-3 backdrop-blur md:hidden">
  <a href="{wa(wa_text)}" target="_blank" rel="noopener noreferrer" class="{BTN_PRIMARY} w-full">{icon('whatsapp', 'h-5 w-5')}<span>Cotizar por WhatsApp</span></a>
</div>
'''


def contact_card(pre, wa_text):
    return f'''<aside class="lg:sticky lg:top-24 lg:self-start" aria-label="Contacto">
  <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
    <p class="font-display text-lg font-extrabold text-brand-dark">Habla con un especialista</p>
    <p class="mt-1.5 text-sm leading-relaxed text-gray-600">Cuéntanos tu actividad y te orientamos sin compromiso.</p>
    <a href="{wa(wa_text)}" target="_blank" rel="noopener noreferrer" class="{BTN_PRIMARY} mt-5 w-full">{icon('whatsapp', 'h-5 w-5')}<span>Escribir por WhatsApp</span></a>
    <ul class="mt-5 space-y-3 text-sm text-gray-700">
      <li class="flex gap-3">{icon('phone', 'mt-0.5 h-4 w-4 shrink-0 text-brand-primary')}<a href="tel:+{WA_NUMBER}" class="font-semibold hover:text-brand-primary">{PHONE}</a></li>
      <li class="flex gap-3">{icon('mail', 'mt-0.5 h-4 w-4 shrink-0 text-brand-primary')}<a href="mailto:{EMAIL}" class="break-all font-semibold hover:text-brand-primary">{EMAIL}</a></li>
      <li class="flex gap-3">{icon('pin', 'mt-0.5 h-4 w-4 shrink-0 text-brand-primary')}<span>Lima: Av. República de Chile 324</span></li>
      <li class="flex gap-3">{icon('pin', 'mt-0.5 h-4 w-4 shrink-0 text-brand-primary')}<span>Chiclayo: Lapoint 1221</span></li>
    </ul>
  </div>
  <div class="mt-4 rounded-2xl bg-brand-dark p-5 text-center">
    <img src="{pre}assets/img/iso45001-blanco.png" alt="Certificación ISO 45001 – Sistema de Gestión de Seguridad y Salud en el Trabajo" width="900" height="441" loading="lazy" class="mx-auto h-auto w-44" />
    <p class="mt-2 text-xs text-gray-400">Certificación ISO 45001</p>
  </div>
</aside>
'''


def cta_band(wa_text):
    return f'''<section class="no-print bg-gradient-to-br from-[#001f4d] via-[#003F8C] to-[#001a3d] text-white" aria-labelledby="cta-final">
  <div class="mx-auto flex max-w-6xl flex-col items-start justify-between gap-6 px-5 py-12 lg:flex-row lg:items-center lg:px-8">
    <div>
      <h2 id="cta-final" class="font-display text-2xl font-extrabold sm:text-3xl">¿Hablamos de tu empresa?</h2>
      <p class="mt-2 max-w-xl text-blue-100/90">Cuéntanos tu actividad y te orientamos sin compromiso.</p>
    </div>
    <div class="flex flex-wrap gap-3">
      <a href="{wa(wa_text)}" target="_blank" rel="noopener noreferrer" class="{BTN_WHITE}">{icon('whatsapp', 'h-5 w-5')}<span>Escribir por WhatsApp</span></a>
      <a href="/#contacto" class="{BTN_OUTLINE_LIGHT}"><span>Solicitar diagnóstico</span>{icon('arrow-right', 'h-4 w-4')}</a>
    </div>
  </div>
</section>
'''


def hero(crumb, eyebrow, h1, lead, wa_text, chips=True, compact=False):
    pad = 'py-10 lg:py-12' if compact else 'py-12 lg:py-16'
    chips_html = ''
    if chips:
        items = ''.join(f'<li class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-semibold text-blue-50">{icon("shield", "h-4 w-4 text-brand-light")}{c}</li>' for c in ('Ley 29783', 'SUNAFIL', 'ISO 45001'))
        chips_html = f'<ul class="mt-8 flex flex-wrap gap-2.5" aria-label="Marco normativo">{items}</ul>'
    ctas = ''
    if wa_text:
        ctas = f'''<div class="mt-8 flex flex-wrap gap-3">
        <a href="{wa(wa_text)}" target="_blank" rel="noopener noreferrer" class="{BTN_WHITE}">{icon('whatsapp', 'h-5 w-5')}<span>Cotizar por WhatsApp</span></a>
        <a href="/#contacto" class="{BTN_OUTLINE_LIGHT}"><span>Solicitar diagnóstico</span>{icon('arrow-right', 'h-4 w-4')}</a>
      </div>'''
    lead_html = f'<p class="mt-5 max-w-2xl text-lg leading-relaxed text-blue-50/90">{lead}</p>' if lead else ''
    eyebrow_html = f'<p class="mb-4 inline-flex rounded-full border border-white/25 bg-white/10 px-3 py-1 text-xs font-bold uppercase tracking-wider text-brand-light">{eyebrow}</p>' if eyebrow else ''
    return f'''<section class="relative overflow-hidden bg-gradient-to-br from-[#001f4d] via-[#003F8C] to-[#001a3d] text-white">
  <div class="pointer-events-none absolute inset-0 opacity-30" style="background-image:radial-gradient(circle at 85% 15%, #7FB4E5 0, transparent 45%)" aria-hidden="true"></div>
  <div class="relative mx-auto max-w-6xl px-5 {pad} lg:px-8">
    <nav aria-label="Ruta de navegación" class="mb-6 flex items-center gap-2 text-sm text-blue-100/80">
      <a href="/" class="inline-flex items-center gap-1.5 hover:text-white">{icon('home', 'h-4 w-4')}Inicio</a><span aria-hidden="true">&rsaquo;</span><span aria-current="page" class="text-white">{crumb}</span>
    </nav>
    {eyebrow_html}
    <h1 class="max-w-3xl font-display text-3xl font-extrabold leading-tight sm:text-4xl lg:text-[2.75rem]">{h1}</h1>
    {lead_html}
    {ctas}
    {chips_html}
  </div>
</section>
'''


# ───────────────────────── páginas completas ─────────────────────────
def service_page(*, url, title, desc, jsonld, crumb, eyebrow, h1, lead, wa_text, sections_html, faq, related):
    """Página de servicio/guía. `related` = [(slug, titulo)]. `faq` = [(pregunta, respuesta)]."""
    pre = ''
    extra = (f'  <link rel="alternate" hreflang="es-PE" href="{url}" />\n'
             f'  <meta property="og:type" content="article" />\n  <meta property="og:url" content="{url}" />\n'
             f'  <meta property="og:title" content="{title}" />\n  <meta property="og:description" content="{desc}" />\n'
             f'  <meta property="og:image" content="{SITE}/assets/img/og-image.jpg" />\n  <meta property="og:locale" content="es_PE" />\n'
             f'  <meta property="og:site_name" content="SSOMA SAFE" />\n  <meta name="twitter:card" content="summary_large_image" />\n'
             f'  <script type="application/ld+json">\n  {jsonld}\n  </script>\n')
    faq_html = ''.join(f'      <details class="faq"><summary>{q}</summary><p>{a}</p></details>\n' for q, a in faq)
    rel_html = ''.join(
        f'<a href="{s}.html" class="group flex items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white p-4 font-semibold text-brand-dark transition hover:border-brand-primary hover:shadow-md motion-reduce:transition-none"><span>{t}</span>{icon("arrow-right", "h-4 w-4 shrink-0 text-brand-primary transition group-hover:translate-x-1 motion-reduce:transform-none")}</a>'
        for s, t in related)
    body = f'''{header(pre, wa_text)}<main id="contenido">
{hero(crumb, eyebrow, h1, lead, wa_text)}
  <div class="mx-auto grid max-w-6xl gap-10 px-5 py-12 lg:grid-cols-[minmax(0,1fr)_330px] lg:gap-14 lg:px-8 lg:py-16">
    <article class="min-w-0">
      <div class="prose-ssoma">
{sections_html}
        <h2>Preguntas frecuentes</h2>
{faq_html}      </div>
      <h2 class="mb-4 mt-12 font-display text-xl font-extrabold text-brand-dark">También te puede interesar</h2>
      <div class="grid gap-3 sm:grid-cols-3">{rel_html}</div>
    </article>
    {contact_card(pre, wa_text)}
  </div>
{cta_band(wa_text)}</main>
{footer(pre)}</body>
</html>
'''
    html = head(title, desc, 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1', url, pre, extra)
    return nbsp(html + '<body class="bg-brand-gray text-brand-dark antialiased">\n' + body)


def legal_page(*, filename, title, desc, body_html):
    """Política / Términos. El h1 y la fecha se extraen del cuerpo para ponerlos en el héroe."""
    pre = ''
    m = re.search(r'<h1[^>]*>(.*?)</h1>', body_html, re.S)
    h1 = m.group(1).strip() if m else title
    body_html = re.sub(r'<h1[^>]*>.*?</h1>\s*', '', body_html, count=1, flags=re.S)
    m = re.search(r'<p[^>]*>(Última actualización:[^<]*)</p>', body_html)
    updated = m.group(1) if m else ''
    body_html = re.sub(r'<p[^>]*>Última actualización:[^<]*</p>\s*', '', body_html, count=1)
    body_html, toc = add_ids(clean(body_html))
    toc_html = ''.join(f'<a href="#{i}">{t}</a>' for i, t in toc)
    url = f'{SITE}/{filename}'
    html = head(f'{title} | SSOMA SAFE', desc, 'index, follow', url, pre)
    body = f'''{header(pre)}<main id="contenido">
{hero(title, 'Documento legal', h1, updated, None, chips=False, compact=True)}
  <div class="mx-auto grid max-w-6xl gap-10 px-5 py-12 lg:grid-cols-[250px_minmax(0,1fr)] lg:gap-14 lg:px-8 lg:py-16">
    <nav class="toc no-print hidden lg:sticky lg:top-24 lg:block lg:self-start" aria-label="Índice de esta página">
      <p class="mb-3 text-xs font-bold uppercase tracking-widest text-gray-500">En esta página</p>
      {toc_html}
    </nav>
    <article class="prose-ssoma min-w-0 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-10">
{body_html}
    </article>
  </div>
  <section class="no-print border-t border-gray-200 bg-white">
    <div class="mx-auto flex max-w-6xl flex-col items-start justify-between gap-4 px-5 py-8 sm:flex-row sm:items-center lg:px-8">
      <p class="text-sm text-gray-600">¿Dudas sobre este documento? Escríbenos a <a class="font-semibold text-brand-primary underline underline-offset-2" href="mailto:{EMAIL}">{EMAIL}</a>.</p>
      <a href="/" class="{BTN_OUTLINE}">{icon('arrow-left', 'h-4 w-4')}<span>Volver al inicio</span></a>
    </div>
  </section>
</main>
{footer(pre)}</body>
</html>
'''
    return nbsp(html + '<body class="bg-brand-gray text-brand-dark antialiased">\n' + body)


def not_found_page():
    """404: rutas absolutas porque el servidor la entrega en cualquier URL."""
    pre = '/'
    cards = ''.join(
        f'<a href="/{s}.html" class="group flex items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white p-4 text-left font-semibold text-brand-dark transition hover:border-brand-primary hover:shadow-md"><span>{t}</span>{icon("arrow-right", "h-4 w-4 shrink-0 text-brand-primary transition group-hover:translate-x-1")}</a>'
        for s, t in SERVICE_LINKS)
    html = head('Página no encontrada | SSOMA SAFE', 'La página que buscas no existe o fue movida. Vuelve al inicio de SSOMA SAFE.', 'noindex, follow', '', pre)
    return html + f'''<body class="bg-brand-gray text-brand-dark antialiased">
{header(pre)}<main id="contenido" class="mx-auto max-w-3xl px-5 py-16 text-center lg:py-24">
  <p class="text-gradient-brand font-display text-8xl font-extrabold leading-none sm:text-9xl">404</p>
  <h1 class="mt-6 font-display text-2xl font-extrabold sm:text-3xl">No encontramos esta página</h1>
  <p class="mx-auto mt-3 max-w-md text-gray-600">Puede que el enlace esté desactualizado o que la página se haya movido. Prueba con alguno de nuestros servicios.</p>
  <div class="mt-8 flex flex-wrap justify-center gap-3">
    <a href="/" class="{BTN_PRIMARY}">{icon('home', 'h-4 w-4')}<span>Ir al inicio</span></a>
    <a href="{wa('Hola, necesito ayuda con SSOMA SAFE.')}" target="_blank" rel="noopener noreferrer" class="{BTN_OUTLINE}">{icon('whatsapp', 'h-4 w-4')}<span>Escribir por WhatsApp</span></a>
  </div>
  <div class="mt-12 grid gap-3 sm:grid-cols-2">{cards}</div>
</main>
{footer(pre)}</body>
</html>
'''
