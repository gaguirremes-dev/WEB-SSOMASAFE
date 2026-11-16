# SSOMA SAFE — sitio web

Sitio estático (HTML + Tailwind compilado + JS) con un libro de reclamaciones en PHP.
Dominio canónico: `https://www.ssomasafe.com`.

## Estructura

| Ruta | Qué es |
|---|---|
| `index.html` | Página principal |
| `politica-de-privacidad.html`, `terminos-y-condiciones.html`, `404.html` | Generadas por `scripts/build_legal_pages.py` |
| `auditoria-sst-sunafil.html`, `matriz-iperc.html`, `vigilancia-ocupacional.html`, `epp-certificados.html`, `ley-29783-obligaciones-empleador.html`, `consultoria-sst-lima-chiclayo.html` | Generadas por `scripts/build_service_pages.py` |
| `Libro-reclamaciones/` | Libro de reclamaciones (PHP, necesita `config.smtp.php`, que no se versiona) |
| `css/tailwind.min.css` | CSS compilado (se versiona) |
| `css/pages.css` | Estilos de texto largo, FAQ e índice de las páginas secundarias |
| `scripts/_layout.py` | Maquetación compartida de las 9 páginas generadas (cabecera, héroe, tarjeta de contacto, CTA, pie, iconos SVG) |
| `scripts/sync_home_nav.py` | Copia a `index.html` el desplegable «Servicios» y el pie de `_layout.py` (entre los marcadores `NAV-SERVICES-*` y `FOOTER`). **No edites el pie ni ese menú a mano en `index.html`** |
| `src/tailwind.css`, `tailwind.config.js` | Entrada y configuración de Tailwind 3 |
| `docs/SEO-AUDIT.md` | Informe SEO y pendientes manuales |

## Flujo de trabajo

```bash
npm install          # una vez
npm run build:css    # regenerar css/tailwind.min.css (obligatorio tras añadir clases Tailwind)
npm run build:pages  # regenerar las 9 páginas generadas (requiere Python 3; en Linux/macOS usa `python3 scripts/...` si `python` no existe)
npm run check:css    # comprueba que los CSS propios tengan las llaves balanceadas (un `}` que falte rompe estilos en silencio)
npm run build        # check:css + build:pages + build:css
```

Las páginas generadas NO se editan a mano: el contenido está en `scripts/build_service_pages.py` y
`scripts/build_legal_pages.py`; el diseño (cabecera, pie, héroe, botones) en `scripts/_layout.py` y `css/pages.css`.
Tras cambiar clases de Tailwind en `_layout.py`, ejecuta `npm run build` (páginas y CSS).
Para añadir un servicio al menú (home y páginas secundarias a la vez): editar `SERVICE_LINKS` y `SERVICE_DESC` en `_layout.py` y ejecutar `npm run build`.

## Despliegue (cPanel + GitHub)

El servidor se sincroniza con este repositorio desde cPanel (Git Version Control), así que
**lo que está en el repo es lo que se publica**.

1. `npm run build` (regenera `css/tailwind.min.css` y las páginas generadas) y commitear el resultado.
2. `git push` a GitHub.
3. En cPanel → Git Version Control → *Update from Remote* / *Deploy HEAD Commit*.
4. Comprobar tras desplegar (ver `docs/SEO-AUDIT.md`, sección 5).

Notas:
- `config.smtp.php` y `Libro-reclamaciones/reclamaciones/` NO están en el repo (secretos y datos personales):
  viven solo en el servidor y el `git pull` no los toca.
- Los archivos de desarrollo (`src/`, `scripts/`, `docs/`, `package*.json`, `tailwind.config.js`, `*.md`)
  llegan al servidor pero el `.htaccess` los bloquea con 403.
- Si cambias un vídeo o una imagen, usa un nombre de archivo nuevo: el servidor las cachea un año.

## Antes de añadir analítica (GA4)

La Política de Privacidad declara que no hay cookies de seguimiento. Si se añade GA4,
actualiza el texto en `scripts/build_legal_pages.py` y vuelve a generar.

## Imágenes: cómo se generaron los archivos optimizados

Los originales (PNG de 2 MB, logos de 4260 px, vídeos de 10–14 MB) NO están en el repo, para no desplegarlos:
se guardan en la carpeta `WEB-SSOMASAFE-originales/`, junto al repo, y en el historial de Git. Los de `assets/` son derivados.

Vídeos (ffmpeg, sin audio porque se reproducen en `muted`; si cambias uno, el nombre del archivo nuevo debe cambiar también, porque el servidor los cachea un año):

```bash
ffmpeg -i "../WEB-SSOMASAFE-originales/video/VIDEO HERO.mp4" -an -c:v libx264 -preset slow -crf 27 -profile:v high -pix_fmt yuv420p -movflags +faststart assets/video/hero.mp4
ffmpeg -i "../WEB-SSOMASAFE-originales/video/PARTÍCULAS ATMOSFÉRICAS.mp4" -an -c:v libx264 -preset slow -crf 27 -profile:v high -pix_fmt yuv420p -movflags +faststart assets/video/particulas.mp4
```

Imágenes (Python + Pillow):

- Fotos: `*.png` (1536×1024) → `*.webp` calidad 80.
- Logos: `logo-ssoma.png` y `logo-ssoma-light.png` → versiones `-sm` de 640 px de ancho.
- Íconos de `assets/icons/` y `favicon.ico`: el logo sobre círculo blanco.
- `og-image.jpg`: 1200×630.

Si cambias un original, vuelve a generar su derivado con el mismo tamaño y formato.
