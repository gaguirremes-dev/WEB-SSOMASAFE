"""Sincroniza el menú de «Servicios» de index.html con scripts/_layout.py.

Uso (desde la raíz del repo):  python scripts/sync_home_nav.py
Reemplaza el contenido entre los marcadores <!-- NAV-SERVICES-DESKTOP:start/end --> y
<!-- NAV-SERVICES-MOBILE:start/end --> de index.html por el desplegable generado desde
SERVICE_LINKS, y el bloque <!-- FOOTER:start/end --> por el pie único de _layout.footer(), para que la
home y las páginas secundarias muestren siempre el mismo menú de servicios y el mismo pie.
Para añadir o quitar un servicio del menú, edita SERVICE_LINKS y SERVICE_DESC en _layout.py.
"""
import os
import re
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import _layout as L  # noqa: E402

os.chdir(L.ROOT)
PATH = 'index.html'

s = open(PATH, encoding='utf-8', newline='').read()
crlf = '\r\n' in s
s = s.replace('\r\n', '\n')


def fill(text, name, content):
    pattern = re.compile(rf'(<!-- {name}:start -->)(.*?)(<!-- {name}:end -->)', re.S)
    if not pattern.search(text):
        raise SystemExit(f'Falta el marcador {name} en {PATH}')
    return pattern.sub(lambda m: f'{m.group(1)}\n        {content}\n        {m.group(3)}', text, count=1)


s = fill(s, 'NAV-SERVICES-DESKTOP', L.desktop_services_item(L.NAV_LINK, pre='', home=''))
s = fill(s, 'NAV-SERVICES-MOBILE', L.mobile_services_list(pre='', onclick='closeMobileMenu()'))

# Pie único: el mismo HTML que reciben las páginas secundarias (index.html usa pre='' y home='')
footer_pattern = re.compile(r'(<!-- FOOTER:start -->)(.*?)(<!-- FOOTER:end -->)', re.S)
if not footer_pattern.search(s):
    raise SystemExit(f'Falta el marcador FOOTER en {PATH}')
s = footer_pattern.sub(lambda m: f'{m.group(1)}\n  {L.footer(pre="", home="").rstrip()}\n  {m.group(3)}', s, count=1)

if crlf:
    s = s.replace('\n', '\r\n')
open(PATH, 'w', encoding='utf-8', newline='').write(s)
print('index.html: menú de servicios y pie sincronizados (%d servicios)' % len(L.SERVICE_LINKS))
