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
| F3 | `robots.txt` | ✅ | `Allow: /`, `Disallow: /*.md$` y la línea `Sitemap`. A propósito NO lista rutas sensibles (`lib/`, reclamaciones, `config.smtp.php`): las bloquea `.htaccess` | — | — |
| F3 | `noindex` heredado | ✅ | Solo `404.html` lleva `noindex` (correcto) | — | — |
| F3 | Redirecciones | ✅ | `.htaccess`: http→https y no-www→www en **un solo salto** | Probar en producción (§5) | P2 |
| F3 | Canonical / hreflang | ✅ | Canónico `https://www.ssomasafe.com/`, `es-PE` + `x-default` | — | — |
| F3 | Sitemap | ✅ | Home (con imágenes), Libro, Privacidad, Términos y las 6 páginas de servicio/guía; XML válido | Enviar en Search Console | P1 |
| F4 | `<title>` | ✅ | 49 caracteres: «Consultoría SSOMA, SST y EPP en Perú \| SSOMA SAFE» | — | — |
| F4 | Meta description | ✅ | 139 caracteres, única | — | — |
| F4 | H1 | ✅ | Un solo H1 por página. El H1 de la home es de marca («Blindamos la Seguridad y Continuidad Operativa de tu Empresa») y no lleva «SSOMA»; esa palabra clave está en `<title>`, descripción y subtítulo | — | — |
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
2. **GA4** (analytics.google.com): Admin → Crear propiedad → flujo web `https://www.ssomasafe.com` → copiar el ID `G-XXXXXXX`. Añadir el script de GA4 en el `<head>` de `index.html` y en `HEAD` de los generadores de `scripts/` y **actualizar la Política de Privacidad**: hoy declara que no hay analítica ni cookies de seguimiento. Después: Admin → Product links → Search Console links.
3. **Google Business Profile**: hay oficinas en Lima y Chiclayo. Si reciben clientes en la oficina con letrero, perfil de tienda por cada sede; si solo visitan al cliente, perfil de **servicio a domicilio** (dirección oculta, hasta 20 zonas de servicio). Nombre, dirección y teléfono idénticos a la web y al JSON-LD. Mantener un perfil por ubicación, sin apartados postales. La verificación puede pedir un video (letrero, interior, vehículos).
4. **Registro de banco de datos personales**: las empresas que tratan datos personales deben inscribir sus bancos de datos en el Registro Nacional de Protección de Datos Personales (ANPD). Estado desconocido: confirmar con un abogado si SSOMA SAFE ya está inscrita.
5. **Revisión legal**: la Política y los Términos son una base razonable según Ley N.° 29733 y su Reglamento (D.S. N.° 016-2024-JUS, en vigor desde el 31/03/2025), Ley N.° 29571 y D.S. N.° 101-2022-PCM. Sin cifras de plazos ARCO a propósito (no contrastadas con el texto oficial). No sustituyen asesoría legal.
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
- Libro de Reclamaciones (PHP): las funciones de guardado atómico, liberación de cupo y marca de notificación se probaron con PHP CLI (14 casos). El flujo completo (POST + CSRF + hCaptcha + SMTP real) no se probó de extremo a extremo.
- La Política declara «sin cookies de seguimiento»: actualizar al añadir GA4 u otra analítica.
- Los textos legales deben revisarse por un profesional antes de darlos por definitivos.
- **Lenis (scroll suave):** la URL antigua devolvía 404 y nunca cargó. Corregida a `/dist/lenis.min.js` con SRI y verificada en tiempo real (anclas con offset de 80 px, rueda, bloqueo con el visor del catálogo).
- **Revisión de código (2026-11-16, dos rondas con 4 lentes: riesgo, resiliencia, legibilidad, fiabilidad):** corregidos los críticos (PDF.js bloqueado por `.htaccess`, hero invisible sin GSAP, contadores en «0» sin animación, guardado no atómico de `records.json`, resultado de escritura no comprobado, fallo de SMTP silencioso) y los avisos: consentimiento exigido y registrado en servidor y en el formulario, etiqueta real del servicio, fallback si WhatsApp está bloqueado, SRI, redirecciones de destino fijo, bloqueo de carpetas internas, carreras del visor del catálogo, bloqueo de scroll compartido, parallax acotado, JSON-LD de servicio con proveedor completo, CSS/JS muerto, y archivos sin uso retirados del repo (respaldo en `WEB-SSOMASAFE-originales/`).
- **Cifras sin respaldo en el repo:** «+500 empresas», «98 % cumplimiento SUNAFIL», «10+ años» y «100 % respaldo normativo» (index.html), y frases como «Cumplimiento 100 % Legal» o «Evita multas millonarias». Confirmar que son ciertas y demostrables o retirarlas: hacen falta para no inducir a error al consumidor.
- **Plazo de respuesta del Libro de Reclamaciones:** el sitio y los correos dicen «30 días calendario». Una fuente consultada habla de 15 días hábiles (Ley 31435); no se pudo confirmar. Verificar con INDECOPI y corregir el texto del PHP si cambia.
- **Retención de datos del Libro de Reclamaciones:** `records.json`, sus copias `.bak`/`records.corrupt-*.json` y los PDF en `Libro-reclamaciones/reclamaciones/` contienen datos personales y no se borran solos. Definir un plazo de conservación (y de eliminación de copias de seguridad) y aplicarlo; mientras tanto están protegidos del acceso web.
- **Vídeos (resuelto):** `hero.mp4` pasó de 13,7 MB a 1,9 MB (−86 %) y `particulas.mp4` de 9,8 MB a 2,9 MB (−71 %): H.264 CRF 27, sin pista de audio (se reproducen `muted`), mismo 1280×720 a 24 fps. SSIM 0,98 y 0,95 frente al original; a 100 % de zoom no se distinguen. Los archivos tienen nombre nuevo para saltarse la caché de un año; los originales están en `WEB-SSOMASAFE-originales/video/` (fuera del repo).
- **`.htaccess` sin `IfModule` para `mod_rewrite`/`mod_authz_core`:** si el hosting no los tuviera, devolvería 500. Son estándar en Apache 2.4; solo importa si se cambia de hosting.

## 7. Próxima revisión

Cada 2–4 semanas al inicio: páginas indexadas, consultas y páginas con impresiones, Core Web Vitals.
