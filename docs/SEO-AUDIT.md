# SEO-AUDIT — ssomasafe.com

Fecha: 2026-11-16 · Metodología: skill `seo-lanzamiento-web` · País/idioma objetivo: Perú, español.
Leyenda: ✅ ok · ⚠️ mejorar · ❌ falla · 👤 manual (requiere iniciar sesión)

## 1. Resumen

El sitio es una página única estática con buena base técnica. No hay bloqueos de rastreo (`robots.txt` correcto, sin `noindex` heredado). Los problemas reales eran de **medición, presencia y señales**: faltaba `og-image.jpg`, el manifest apuntaba a íconos inexistentes, el sitemap usaba anclas `#`, no había páginas legales ni 404, el formulario de contacto era una simulación y no hay GA4 ni Search Console. Lo pendiente principal es manual: Search Console, GA4 y Google Business Profile.

## 2. Estado por fase

| Fase | Elemento | Estado | Evidencia | Acción | Prio |
|---|---|---|---|---|---|
| F1 | Etiqueta GA4/GTM | ❌ | No hay `gtag`/GTM en `index.html` | 👤 Crear propiedad GA4 y pegar el ID (ver §4) | P1 |
| F1 | Eventos clave | ❌ | Sin analítica | 👤 Medir clic en WhatsApp, envío de formulario y reclamos | P2 |
| F2 | Search Console | 👤 | No verificable desde código | Verificar dominio por DNS y enviar sitemap | P1 |
| F2 | Google Business Profile | 👤 | No verificable | Crear/ajustar ficha (§4) | P1 |
| F3 | `robots.txt` | ✅ | `Allow: /`, bloquea `/lib/`, `/Libro-reclamaciones/reclamaciones/`, `config.smtp.php`, `*.md`; declara sitemap | — | — |
| F3 | `noindex` heredado | ✅ | Solo `404.html` lleva `noindex` (correcto) | — | — |
| F3 | Redirecciones | ✅ | `.htaccess`: http→https y no-www→www en **un solo salto** | Probar en producción (§5) | P2 |
| F3 | Canonical / hreflang | ✅ | Canónico `https://www.ssomasafe.com/`, `es-PE` + `x-default` | — | — |
| F3 | Sitemap | ✅ | Home (con imágenes), Libro, Privacidad, Términos; XML válido | Enviar en Search Console | P1 |
| F4 | `<title>` | ✅ | 49 caracteres: «Consultoría SSOMA, SST y EPP en Perú \| SSOMA SAFE» | — | — |
| F4 | Meta description | ✅ | 139 caracteres, única | — | — |
| F4 | H1 | ✅ | 1 solo H1 por página. En la home, «SSOMA» está en el subtítulo y el título; el H1 de marca se mantiene | — | — |
| F4 | `meta keywords` | ✅ | Eliminado (Google lo ignora) | — | — |
| F4 | Open Graph / Twitter | ✅ | `og-image.jpg` 1200×630 creado, con tamaño y `alt` | Validar con el depurador de Facebook | P3 |
| F5 | Palabras clave | ✅ | 6 páginas nuevas, una palabra clave por página, enlazadas desde el footer y entre sí; están en el sitemap | Pedir indexación en Search Console; ampliar con consultas reales a las 4 semanas | P2 |
| F6 | Imágenes | ✅ | Fotos PNG de ~2 MB → WebP de 80–150 KB; `width`/`height` en todas; logos de 4260 px → 640 px | — | — |
| F6 | LCP | ✅ | `preload` del póster del hero | Medir en PageSpeed Insights | P2 |
| F6 | Tailwind | ✅ | CDN reemplazado por CSS compilado de 34 KB | — | — |
| F6 | Datos estructurados | ✅ | `ProfessionalService` (con `@id`, logo, contacto, regiones) + `WebSite` | Validar en Prueba de resultados enriquecidos | P2 |
| F6 | Páginas legales y 404 | ✅ | Privacidad, Términos, 404 con código 404 vía `ErrorDocument` | Revisión legal (§4) | P2 |
| F6 | Core Web Vitals reales | 👤 | Requiere datos de campo | Revisar informe en Search Console a las 2–4 semanas | P2 |
| F7 | Búsqueda con IA | ✅ | Sin requisitos extra; no se crea `llms.txt` (Google indica que no hace falta) | — | — |

## 3. Palabras clave (propuesta inicial)

Contenido redactado con hechos normativos verificables, sin cifras ni casos inventados. Conviene que SSOMA SAFE añada experiencia propia (casos, fotos reales) y valide los textos con su equipo técnico. Contrastar las palabras clave con Search Console.

| Página | Palabra clave principal | Intención | Estado |
|---|---|---|---|
| Home | consultoría SSOMA Perú | Comercial | Existe |
| Servicio | auditoría SST SUNAFIL | Comercial | Creada: `auditoria-sst-sunafil.html` |
| Servicio | IPERC / matriz IPERC | Informativa-comercial | Creada: `matriz-iperc.html` |
| Servicio | vigilancia ocupacional / salud ocupacional empresas | Comercial | Creada: `vigilancia-ocupacional.html` |
| Servicio | EPP certificados para empresas | Transaccional | Creada: `epp-certificados.html` |
| Local | consultoría seguridad y salud en el trabajo Chiclayo | Comercial local | Creada: `consultoria-sst-chiclayo.html` |
| Contenido | Ley 29783: obligaciones de la empresa | Informativa | Creada: `ley-29783-obligaciones-empleador.html` |

## 4. Pendientes manuales 👤 (con pasos)

1. **Search Console** (search.google.com/search-console): Añadir propiedad → tipo **Dominio** `ssomasafe.com` → copiar el registro TXT al DNS (Cloudflare) → Verificar → Sitemaps → enviar `https://www.ssomasafe.com/sitemap.xml`.
2. **GA4** (analytics.google.com): Admin → Crear propiedad → flujo web `https://www.ssomasafe.com` → copiar el ID `G-XXXXXXX`. Pegar el ID en `index.html` (pedirme el cambio) y **actualizar la Política de Privacidad**: hoy declara que no hay analítica ni cookies de seguimiento. Después: Admin → Product links → Search Console links.
3. **Google Business Profile**: hay oficinas en Lima y Chiclayo. Si reciben clientes en la oficina con letrero, perfil de tienda por cada sede; si solo visitan al cliente, perfil de **servicio a domicilio** (dirección oculta, hasta 20 zonas de servicio). Nombre, dirección y teléfono idénticos a la web y al JSON-LD. Mantener un perfil por ubicación, sin apartados postales. Prepárate para la verificación por video.
4. **Registro de banco de datos personales**: las empresas que tratan datos personales deben inscribir sus bancos de datos en el Registro Nacional de Protección de Datos Personales (ANPD). No pude confirmar si SSOMA SAFE ya está inscrita; consúltalo con un abogado.
5. **Revisión legal**: la Política y los Términos son una base razonable según Ley N.° 29733 y su Reglamento (D.S. N.° 016-2024-JUS, en vigor desde el 31/03/2025), Ley N.° 29571 y D.S. N.° 101-2022-PCM. Sin cifras de plazos ARCO a propósito (no pude contrastarlas). No sustituyen asesoría legal.
6. **Libro de Reclamaciones**: la normativa indica que debe ser visible en la página de inicio y que debe existir un libro físico de respaldo. El footer ya lo muestra; confirma el libro físico y el plazo de respuesta vigente con INDECOPI.
7. **Bing Webmaster Tools** (opcional): importar desde Search Console.
8. **Verificar en producción**: cabeceras y redirecciones (§5).

## 5. Comprobaciones tras el despliegue

```bash
URL=https://www.ssomasafe.com
curl -sIL http://ssomasafe.com | grep -iE '^(HTTP|location)'   # esperado: 1 solo 301 hacia https://www.
curl -s $URL/robots.txt
curl -sI $URL/pagina-que-no-existe | head -1                    # esperado: 404
curl -sI $URL/css/tailwind.min.css | head -1                    # esperado: 200
curl -sI $URL/config.smtp.php | head -1                         # esperado: 403
curl -sI $URL/contexto.md | head -1                             # esperado: 403
```

Si usas Cloudflare en modo «Flexible» y aparece un bucle de redirecciones, cámbialo a «Full (strict)» o elimina la segunda regla de `.htaccess`.

## 6. Riesgos conocidos

- El formulario de contacto envía por WhatsApp (abre `wa.me`); no hay respaldo por correo. Si se quiere correo, hace falta `contacto.php` con SMTP y antispam.
- La animación del hero depende de GSAP (CDN). Con «reducir movimiento» activo se muestra todo de inmediato (verificado).
- No se pudo ejecutar el Libro de Reclamaciones (PHP) en local; solo se revisaron sus metadatos y el cambio de CSS.
- La Política declara «sin cookies de seguimiento»: actualizar al añadir GA4 u otra analítica.
- Los textos legales deben revisarse por un profesional antes de darlos por definitivos.

## 7. Próxima revisión

Cada 2–4 semanas al inicio: páginas indexadas, consultas y páginas con impresiones, Core Web Vitals.
