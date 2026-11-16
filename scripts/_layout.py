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
    ('consultoria-sst-lima-chiclayo', 'Consultoría SST en Lima y Chiclayo'),
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
    'chevron-down': _P % 'M19 9l-7 7-7-7',
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
  <link rel="stylesheet" href="{pre}css/pages.css?v=2" />
</head>
'''


SERVICE_DESC = {
    'auditoria-sst-sunafil': 'Cumplimiento y preparación para SUNAFIL',
    'matriz-iperc': 'Peligros, riesgos y medidas de control',
    'vigilancia-ocupacional': 'Exámenes médicos ocupacionales',
    'epp-certificados': 'Equipos de protección personal',
    'consultoria-sst-lima-chiclayo': 'Atención en nuestras sedes de Lima y Chiclayo',
    'ley-29783-obligaciones-empleador': 'Qué debe cumplir tu empresa',
}
NAV_LINK = 'nav-link text-brand-dark hover:text-brand-primary text-[15px] font-semibold transition-colors'


def desktop_services_item(link_class=NAV_LINK, pre='', home=''):
    """<li> del menú de escritorio con el desplegable «Servicios». `home` = '' en index.html y '/' en el resto."""
    items = ''.join(
        f'<li><a href="{pre}{s}.html" class="block rounded-xl px-4 py-2.5 transition hover:bg-brand-gray"><span class="block text-sm font-bold text-brand-dark">{t}</span><span class="block text-xs text-gray-500">{SERVICE_DESC[s]}</span></a></li>'
        for s, t in SERVICE_LINKS)
    chev = icon('chevron-down', 'h-3.5 w-3.5 transition-transform group-hover:rotate-180 group-focus-within:rotate-180')
    return (
        '<li class="group relative">\n'
        f'          <a href="{home}#servicios" class="{link_class} inline-flex items-center gap-1" aria-haspopup="true">Servicios{chev}</a>\n'
        '          <div class="invisible absolute left-1/2 top-full z-50 w-80 -translate-x-1/2 pt-3 opacity-0 transition-opacity duration-150 group-focus-within:visible group-focus-within:opacity-100 group-hover:visible group-hover:opacity-100 motion-reduce:transition-none">\n'
        f'            <ul class="rounded-2xl border border-gray-200 bg-white p-2 text-left shadow-xl" role="list">{items}\n'
        f'              <li class="mt-1 border-t border-gray-100 pt-1"><a href="{home}#servicios" class="block rounded-xl px-4 py-2.5 text-sm font-semibold text-brand-primary transition hover:bg-brand-gray">Ver todos los servicios</a></li>\n'
        '            </ul>\n'
        '          </div>\n'
        '        </li>')


def mobile_services_list(pre='', onclick=''):
    """Sub-lista de servicios para el menú móvil."""
    oc = f' onclick="{onclick}"' if onclick else ''
    items = ''.join(
        f'<li><a href="{pre}{s}.html" class="block py-1 text-sm text-gray-600 hover:text-brand-primary"{oc}>{t}</a></li>'
        for s, t in SERVICE_LINKS)
    return f'<ul class="mt-2 space-y-1 border-l-2 border-gray-200 pl-4" role="list">{items}</ul>'


def header(pre, wa_text='Hola, quiero solicitar un diagnóstico SSOMA.'):
    """Cabecera de las páginas secundarias: mismo menú que la home (desplegable de servicios y hamburguesa en móvil)."""
    home = '/'
    intranet = f'<li><a href="https://rrhh.ssomasafe.com" target="_blank" rel="noopener nofollow" class="{NAV_LINK}">Intranet</a></li>'
    desktop = '\n        '.join([
        f'<li><a href="/#nosotros" class="{NAV_LINK}">Nosotros</a></li>',
        desktop_services_item(NAV_LINK, pre, home),
        f'<li><a href="/#beneficios" class="{NAV_LINK}">Beneficios</a></li>',
        f'<li><a href="/#legal" class="{NAV_LINK}">Marco Legal</a></li>',
        f'<li><a href="/#contacto" class="{NAV_LINK}">Contacto</a></li>',
        intranet,
    ])
    ml = 'block py-1.5 font-semibold text-brand-dark hover:text-brand-primary'
    mobile = '\n        '.join([
        f'<li><a href="/#nosotros" class="{ml}">Nosotros</a></li>',
        f'<li><a href="/#servicios" class="{ml}">Servicios</a>{mobile_services_list(pre)}</li>',
        f'<li><a href="/#beneficios" class="{ml}">Beneficios</a></li>',
        f'<li><a href="/#legal" class="{ml}">Marco Legal</a></li>',
        f'<li><a href="/#contacto" class="{ml}">Contacto</a></li>',
        f'<li><a href="https://rrhh.ssomasafe.com" target="_blank" rel="noopener nofollow" class="{ml}">Intranet</a></li>',
        f'<li class="pt-2"><a href="{wa(wa_text)}" target="_blank" rel="noopener noreferrer" class="{BTN_PRIMARY} w-full">{icon("whatsapp", "h-4 w-4")}<span>Solicitar diagnóstico</span></a></li>',
    ])
    skip = ('<a href="#contenido" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-[100] focus:rounded-lg '
            'focus:bg-white focus:px-4 focus:py-2 focus:font-semibold focus:text-brand-primary focus:shadow-lg">Saltar al contenido</a>')
    return f'''{skip}
<header class="sticky top-0 z-50 border-b border-gray-200 bg-white/95 backdrop-blur">
  <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-2.5 lg:px-8">
    <a href="/" aria-label="SSOMA SAFE — Inicio" class="shrink-0"><img src="{pre}assets/img/logo-ssoma-sm.png" alt="SSOMA SAFE" width="73" height="48" class="h-11 w-auto" /></a>
    <nav class="hidden lg:block" aria-label="Principal">
      <ul class="flex items-center gap-8" role="list">
        {desktop}
      </ul>
    </nav>
    <a href="{wa(wa_text)}" target="_blank" rel="noopener noreferrer" class="{BTN_PRIMARY} hidden lg:inline-flex">{icon('whatsapp', 'h-4 w-4')}<span>Solicitar diagnóstico</span></a>
    <details class="group/menu lg:hidden">
      <summary class="flex h-11 w-11 cursor-pointer items-center justify-center rounded-lg text-brand-dark hover:bg-brand-gray" aria-label="Abrir o cerrar el menú">
        <svg class="h-6 w-6 group-open/menu:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h10"/></svg>
        <svg class="hidden h-6 w-6 group-open/menu:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
      </summary>
      <div class="absolute inset-x-0 top-full max-h-[calc(100vh-4rem)] overflow-y-auto border-t border-gray-200 bg-white shadow-lg">
        <ul class="space-y-1 px-5 py-5" role="list">
        {mobile}
        </ul>
      </div>
    </details>
  </div>
</header>
'''


SOCIALS = [('https://wa.me/51943704298', 'WhatsApp de SSOMA SAFE', 'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z', 'hover:bg-green-600'), ('https://www.instagram.com/somos.ssoma.peru', 'Instagram de SSOMA SAFE', 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zm0 10.162a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z', 'hover:bg-brand-primary'), ('https://www.facebook.com/somos.ssoma.peru', 'Facebook de SSOMA SAFE', 'M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z', 'hover:bg-brand-primary'), ('https://www.tiktok.com/@somos.ssoma.peru', 'TikTok de SSOMA SAFE', 'M16.6 5.82s.51.5 0 0A4.278 4.278 0 0115.54 3h-3.09v12.4a2.592 2.592 0 01-2.59 2.5c-1.42 0-2.6-1.16-2.6-2.6 0-1.72 1.66-3.01 3.37-2.48V9.66c-3.45-.46-6.47 2.22-6.47 5.64 0 3.33 2.76 5.7 5.69 5.7 3.14 0 5.69-2.55 5.69-5.7V9.01a7.35 7.35 0 004.3 1.38V7.3s-1.88.09-3.24-1.48z', 'hover:bg-brand-primary'), ('https://www.youtube.com/@somos_ssoma_peru', 'YouTube de SSOMA SAFE', 'M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z', 'hover:bg-brand-primary')]

# (destino, texto). Un destino que empieza por '#' es un ancla de la home; el resto es un archivo .html de la raíz.
FOOTER_SERVICES = [
    ('#servicios', 'Consultoría SST'),
    ('matriz-iperc.html', 'Matriz IPERC'),
    ('auditoria-sst-sunafil.html', 'Auditorías SST'),
    ('vigilancia-ocupacional.html', 'Vigilancia Ocupacional'),
    ('epp-certificados.html', 'Suministro EPPs'),
    ('#servicios', 'Capacitaciones'),
    ('consultoria-sst-lima-chiclayo.html', 'Consultoría SST en Lima y Chiclayo'),
    ('ley-29783-obligaciones-empleador.html', 'Guía Ley 29783'),
]

_FLINK = 'text-gray-400 hover:text-white text-sm transition-colors'
_FHEAD = 'font-display font-semibold text-sm uppercase tracking-widest text-gray-400 mb-5'


def footer(pre='', home='/'):
    """Pie único del sitio. index.html lo recibe con pre='' y home='' (lo sincroniza sync_home_nav.py);
    las páginas secundarias con home='/'; el 404 usa pre='/' porque se sirve en cualquier URL."""
    social = ''.join(
        f'''
            <a href="{href}" target="_blank" rel="noopener noreferrer" class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center {hover} transition-colors" aria-label="{label}">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="{d}"/></svg>
            </a>'''
        for href, label, d, hover in SOCIALS)
    services = ''.join(
        f'\n            <li><a href="{(home + t) if t.startswith("#") else (pre + t)}" class="{_FLINK}">{label}</a></li>'
        for t, label in FOOTER_SERVICES)
    return f'''<footer class="bg-brand-dark text-white" role="contentinfo">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 py-16">
      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-12">

        <!-- Marca -->
        <div class="lg:col-span-2">
          <div class="mb-6">
            <img src="{pre}assets/img/logo-ssoma-light-sm.png" alt="SSOMA SAFE" class="h-24 w-auto object-contain" width="147" height="96" loading="lazy" />
          </div>
          <p class="text-gray-400 text-sm leading-relaxed max-w-xs mb-6">
            Consultoría especializada en Seguridad y Salud Ocupacional, Medio Ambiente e Insumos Industriales. Tu aliado definitivo en cumplimiento normativo.
          </p>
          <div class="flex gap-3">{social}
          </div>
          <img src="{pre}assets/img/iso45001-blanco.png"
               alt="Certificación ISO 45001 – Sistema de Gestión de Seguridad y Salud en el Trabajo"
               width="900" height="441" loading="lazy" decoding="async"
               class="mt-8 block h-auto w-56" />
        </div>

        <!-- Servicios -->
        <div>
          <p class="{_FHEAD}">Servicios</p>
          <ul class="space-y-3" role="list">{services}
          </ul>
        </div>

        <!-- Legal -->
        <div>
          <p class="{_FHEAD}">Legal</p>
          <ul class="space-y-3" role="list">
            <li><a href="{pre}politica-de-privacidad.html" class="{_FLINK}">Política de Privacidad</a></li>
            <li><a href="{pre}terminos-y-condiciones.html" class="{_FLINK}">Términos y Condiciones</a></li>
            <li><a href="mailto:{EMAIL}" class="{_FLINK}">{EMAIL}</a></li>
            <li class="pt-2">
              <a href="{pre}Libro-reclamaciones/libro-de-reclamaciones.php" title="Libro de Reclamaciones Virtual — INDECOPI" class="rec-card">
                <picture>
                  <source srcset="{pre}assets/img/libro-reclamaciones-card.webp" type="image/webp" />
                  <img src="{pre}assets/img/libro-reclamaciones-card.png" alt="Libro de Reclamaciones virtual – INDECOPI" width="600" height="600" loading="lazy" decoding="async" />
                </picture>
              </a>
              <p class="text-gray-400 text-xs mt-2 leading-tight">
                <a href="mailto:reclamaciones@ssomasafe.com" class="hover:text-white transition-colors break-all">reclamaciones@ssomasafe.com</a>
              </p>
            </li>
          </ul>
        </div>
      </div>

      <!-- Bottom bar -->
      <div class="mt-12 pt-8 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-4">
        <p class="text-gray-400 text-sm">© 2026 SSOMA SAFE. Todos los derechos reservados.</p>
        <p class="text-gray-400 text-xs">RUC: 20607018287 &nbsp;|&nbsp; Lima &amp; Chiclayo, Perú</p>
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
