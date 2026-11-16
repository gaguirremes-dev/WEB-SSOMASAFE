"""Comprueba que los CSS propios (css/*.css, sin el compilado) tengan llaves y comentarios balanceados.

Uso:  python scripts/check_css.py   (se ejecuta dentro de `npm run build`)
Motivo: una `}` que falte en un @media deja TODAS las reglas siguientes dentro de ese bloque, y los estilos
solo se aplican cuando se cumple la condición (p. ej. «reducir movimiento»). Es un fallo silencioso.
"""
import glob
import os
import re
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
bad = False
for path in sorted(glob.glob(os.path.join(ROOT, 'css', '*.css'))):
    name = os.path.basename(path)
    if name.endswith('.min.css'):
        continue
    css = open(path, encoding='utf-8').read()
    css = re.sub(r'/\*.*?\*/', '', css, flags=re.S)          # fuera comentarios
    css = re.sub(r'"[^"]*"|\'[^\']*\'', '""', css)           # fuera cadenas (url("data:..."))
    opens, closes = css.count('{'), css.count('}')
    if opens != closes:
        print(f'ERROR {name}: {opens} «{{» y {closes} «}}»: bloque sin cerrar o sobrante')
        bad = True
    else:
        print(f'ok    {name}: {opens} bloques balanceados')
sys.exit(1 if bad else 0)
