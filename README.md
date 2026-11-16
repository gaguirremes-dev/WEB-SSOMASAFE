# SSOMA SAFE — sitio web

Sitio estático (HTML + Tailwind compilado + JS) con un libro de reclamaciones en PHP.
Dominio canónico: `https://www.ssomasafe.com`.

## Estructura

| Ruta | Qué es |
|---|---|
| `index.html` | Página principal |
| `politica-de-privacidad.html`, `terminos-y-condiciones.html`, `404.html` | Generadas por `scripts/build_legal_pages.py` |
| `auditoria-sst-sunafil.html`, `matriz-iperc.html`, `vigilancia-ocupacional.html`, `epp-certificados.html`, `ley-29783-obligaciones-empleador.html`, `consultoria-sst-chiclayo.html` | Generadas por `scripts/build_service_pages.py` |
| `Libro-reclamaciones/` | Libro de reclamaciones (PHP, necesita `config.smtp.php`, que no se versiona) |
| `css/tailwind.min.css` | CSS compilado (se versiona) |
| `src/tailwind.css`, `tailwind.config.js` | Entrada y configuración de Tailwind 3 |
| `docs/SEO-AUDIT.md` | Informe SEO y pendientes manuales |

## Flujo de trabajo

```bash
npm install          # una vez
npm run build:css    # regenerar css/tailwind.min.css (obligatorio tras añadir clases Tailwind)
npm run build:pages  # regenerar las 9 páginas generadas (requiere Python 3)
npm run build        # ambos
```

Las páginas generadas NO se editan a mano: cambia el script y vuelve a generarlas
(así el header, el footer y los metadatos se mantienen iguales en todas).

## Despliegue

1. `npm run build`
2. Subir todo menos `node_modules/`, `.git/`, `.atl/`, `src/`, `docs/`, `scripts/`, `package*.json`
   y `tailwind.config.js` (el `.htaccess` ya bloquea estos archivos si se suben por error).
3. Comprobar tras desplegar (ver `docs/SEO-AUDIT.md`, sección 5).

## Antes de añadir analítica (GA4)

La Política de Privacidad declara que no hay cookies de seguimiento. Si se añade GA4,
actualiza el texto en `scripts/build_legal_pages.py` y vuelve a generar.
