"""Genera las 6 páginas de servicio/guía (auditoria-sst-sunafil, matriz-iperc,
vigilancia-ocupacional, epp-certificados, ley-29783-obligaciones-empleador,
consultoria-sst-chiclayo).

Uso (desde la raíz del repo):  python scripts/build_service_pages.py
Para añadir una página: agrega un dict a PAGES, ejecuta el script, añade su URL a
sitemap.xml y enlázala desde el footer de index.html. Luego `npm run build:css`.
Este script NO modifica sitemap.xml ni index.html.
"""
import os, re, json, urllib.parse
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
os.chdir(ROOT)
SITE = 'https://www.ssomasafe.com'
DATE = '2026-11-16'

P = 'class="text-gray-700 leading-relaxed mb-4"'
UL = 'class="list-disc pl-6 space-y-2 text-gray-700 leading-relaxed mb-4"'
H2 = 'class="font-display font-extrabold text-2xl mt-12 mb-4"'
H3 = 'class="font-display font-bold text-lg mt-6 mb-2"'
A = 'class="text-brand-primary underline"'

def wa(text):
    return 'https://wa.me/51943704298?text=' + urllib.parse.quote(text)

PAGES = [
 dict(
  slug='auditoria-sst-sunafil',
  title='Auditoría SST y cumplimiento SUNAFIL | SSOMA SAFE',
  desc='Auditoría del Sistema de Gestión de SST: revisamos tu cumplimiento de la Ley 29783 y su Reglamento, y te entregamos un plan de cierre de brechas.',
  h1='Auditoría SST y cumplimiento SUNAFIL en Perú',
  crumb='Auditoría SST',
  wa='Hola, quiero una auditoría SST para mi empresa.',
  lead='Una auditoría del Sistema de Gestión de Seguridad y Salud en el Trabajo (SGSST) compara lo que tu empresa hace con lo que exige la Ley N.° 29783 y su Reglamento (D.S. N.° 005-2012-TR). El resultado es un diagnóstico claro de brechas y un plan para cerrarlas antes de una inspección.',
  sections=[
   ('¿Qué revisamos?', f'''<ul {UL}>
      <li>Política de SST, objetivos y programa anual de actividades.</li>
      <li>Matriz IPERC y mapa de riesgos vigentes.</li>
      <li>Constitución y funcionamiento del Comité o Supervisor de SST.</li>
      <li>Reglamento Interno de SST (obligatorio a partir de 20 trabajadores).</li>
      <li>Capacitaciones, simulacros y registros obligatorios.</li>
      <li>Exámenes médicos ocupacionales y registro de accidentes e incidentes.</li>
    </ul>'''),
   ('Cómo trabajamos', f'''<p {P}>Revisamos documentos, hacemos visita a las áreas de trabajo y entrevistamos al personal clave. Después te entregamos un informe con cada hallazgo, su nivel de prioridad y la acción recomendada, para que sepas por dónde empezar.</p>
    <p {P}>La evidencia que se solicita suele coincidir con los registros referenciales que establece la R.M. N.° 050-2013-TR, que ayudan a demostrar el cumplimiento ante la autoridad.</p>'''),
   ('¿Por qué conviene auditar antes de una inspección?', f'''<p {P}>La SUNAFIL fiscaliza el cumplimiento de la normativa de seguridad y salud, y los incumplimientos pueden derivar en multas y paralización de actividades. Detectar las brechas antes permite corregirlas con calma y a menor costo.</p>'''),
  ],
  faq=[
   ('¿Quiénes deben tener un SGSST?', 'Todo empleador, de cualquier tamaño y sector, debe cumplir la Ley N.° 29783. Cambian las exigencias según el número de trabajadores y el nivel de riesgo.'),
   ('¿La auditoría reemplaza una inspección de la SUNAFIL?', 'No. Es una revisión preventiva que te prepara. Solo la autoridad realiza las inspecciones oficiales.'),
   ('¿Cuánto demora?', 'Depende del tamaño de la empresa, la cantidad de sedes y el estado de los registros. Lo definimos en el diagnóstico inicial, sin compromiso.'),
  ],
  related=['matriz-iperc','ley-29783-obligaciones-empleador','vigilancia-ocupacional'],
 ),
 dict(
  slug='matriz-iperc',
  title='Matriz IPERC: identificación de peligros | SSOMA SAFE',
  desc='Elaboramos y actualizamos tu matriz IPERC conforme a la Ley 29783: identificación de peligros, evaluación de riesgos y medidas de control por puesto.',
  h1='Matriz IPERC: identificación de peligros y evaluación de riesgos',
  crumb='Matriz IPERC',
  wa='Hola, necesito elaborar o actualizar mi matriz IPERC.',
  lead='El IPERC (Identificación de Peligros, Evaluación de Riesgos y Control) es la base del sistema de seguridad de cualquier empresa. Define qué puede dañar a tus trabajadores, qué tan grave es y qué medidas lo evitan.',
  sections=[
   ('¿Qué incluye una matriz IPERC?', f'''<ul {UL}>
      <li>Inventario de procesos, actividades y tareas, incluidas las rutinarias y las no rutinarias.</li>
      <li>Peligros identificados por tarea y los riesgos asociados.</li>
      <li>Evaluación de cada riesgo según su probabilidad y su severidad.</li>
      <li>Medidas de control priorizadas y responsables de implementarlas.</li>
      <li>Riesgo residual tras aplicar los controles.</li>
    </ul>'''),
   ('Jerarquía de controles', f'''<p {P}>La normativa peruana establece un orden de prioridad para controlar los riesgos: eliminar el peligro, sustituirlo, aplicar controles de ingeniería, usar señalización y controles administrativos y, como última barrera, entregar equipos de protección personal. Una buena matriz no se queda solo en el EPP.</p>'''),
   ('¿Cada cuánto se actualiza?', f'''<p {P}>Se recomienda revisarla al menos una vez al año, y siempre que cambien los procesos, las instalaciones o el equipo, o cuando ocurra un accidente o incidente. Una matriz desactualizada es una de las observaciones más comunes en una revisión.</p>'''),
   ('Lo que recibes', f'''<p {P}>Una matriz hecha con visita real al puesto de trabajo, en el formato que usa tu empresa y lista para integrarse al programa anual de SST. También te la explicamos al responsable de SST y al Comité para que puedan mantenerla.</p>'''),
  ],
  faq=[
   ('¿Cuál es la diferencia entre peligro y riesgo?', 'El peligro es la fuente que puede causar daño (por ejemplo, trabajo en altura). El riesgo es la combinación de qué tan probable es que ocurra el daño y qué tan grave sería.'),
   ('¿Se necesita una matriz por cada puesto?', 'Se hace por proceso y tarea. Los puestos con tareas similares pueden compartir la evaluación, pero deben quedar identificados.'),
   ('¿Sirve para obras y para oficinas?', 'Sí. Cambian los peligros y los controles, pero toda actividad laboral requiere su IPERC.'),
  ],
  related=['auditoria-sst-sunafil','epp-certificados','ley-29783-obligaciones-empleador'],
 ),
 dict(
  slug='vigilancia-ocupacional',
  title='Vigilancia de salud ocupacional para empresas | SSOMA SAFE',
  desc='Vigilancia de la salud de tus trabajadores: exámenes médicos ocupacionales de ingreso, periódicos y de retiro, según la normativa del MINSA.',
  h1='Vigilancia de salud ocupacional para empresas',
  crumb='Vigilancia ocupacional',
  wa='Hola, quiero información sobre vigilancia de salud ocupacional.',
  lead='La vigilancia de la salud permite saber si el trabajo está afectando a las personas y actuar a tiempo. En el Perú el empleador debe garantizar los exámenes médicos ocupacionales de sus trabajadores, conforme a la Ley N.° 29783 y al protocolo del Ministerio de Salud.',
  sections=[
   ('Tipos de examen médico ocupacional', f'''<ul {UL}>
      <li><strong>Pre-ocupacional (ingreso):</strong> establece el estado de salud al empezar y verifica la aptitud para el puesto.</li>
      <li><strong>Periódico:</strong> controla la salud durante la relación laboral, según los riesgos del puesto.</li>
      <li><strong>De retiro:</strong> documenta el estado de salud al terminar el vínculo laboral.</li>
    </ul>
    <p {P}>El contenido de los exámenes depende de la actividad y de los peligros a los que se expone cada trabajador, de acuerdo con la R.M. N.° 312-2011/MINSA y sus guías.</p>'''),
   ('¿Qué pasa con los resultados?', f'''<p {P}>Los resultados son confidenciales: la empresa recibe la conclusión de aptitud y las recomendaciones, y cada trabajador accede a su historia clínica. Con la información agregada se ajustan los controles y se orienta el programa de salud ocupacional.</p>'''),
   ('Cómo te apoyamos', f'''<ul {UL}>
      <li>Definimos qué exámenes corresponden a cada puesto según tu IPERC.</li>
      <li>Coordinamos la programación para que la operación no se detenga.</li>
      <li>Organizamos los resultados y el seguimiento de casos que requieren control.</li>
    </ul>'''),
  ],
  faq=[
   ('¿Quién paga los exámenes médicos ocupacionales?', 'La Ley N.° 29783 pone la obligación en el empleador. El trabajador no debe asumir ese costo.'),
   ('¿Con qué frecuencia son los periódicos?', 'Depende de los riesgos del puesto y de lo que indique el protocolo aplicable. Lo definimos al revisar tu matriz IPERC.'),
   ('¿Los resultados individuales se comparten con la empresa?', 'No. La información clínica es confidencial. La empresa recibe la conclusión de aptitud y las recomendaciones.'),
  ],
  related=['matriz-iperc','auditoria-sst-sunafil','ley-29783-obligaciones-empleador'],
 ),
 dict(
  slug='epp-certificados',
  title='EPP certificados para empresas en Perú | SSOMA SAFE',
  desc='Suministro de equipos de protección personal (EPP) para empresas: cascos, protección auditiva y respiratoria, guantes y más. Cotiza por WhatsApp.',
  h1='Suministro de EPP certificados para empresas',
  crumb='EPP certificados',
  wa='Hola, quiero cotizar equipos de protección personal (EPP).',
  lead='El equipo de protección personal es la última barrera entre el peligro y el trabajador, y por eso tiene que ser el adecuado para cada riesgo, estar en buen estado y usarse bien. Te ayudamos a elegirlo según tu actividad y a mantener la dotación al día.',
  sections=[
   ('Categorías que suministramos', f'''<ul {UL}>
      <li>Protección de la cabeza (cascos) y facial.</li>
      <li>Protección visual (lentes y caretas).</li>
      <li>Protección auditiva.</li>
      <li>Protección respiratoria.</li>
      <li>Guantes y protección de manos.</li>
      <li>Calzado de seguridad, ropa de trabajo y protección contra caídas.</li>
    </ul>'''),
   ('Cómo elegir el EPP correcto', f'''<p {P}>El punto de partida es tu matriz IPERC: el equipo se define según el peligro que debe controlar, no al revés. Una vez identificado, se revisa que cumpla la norma técnica que corresponde a ese tipo de equipo y que la talla y el ajuste sean los adecuados. Un EPP mal elegido o mal ajustado da una falsa sensación de seguridad.</p>'''),
   ('Obligaciones de la empresa', f'''<p {P}>Según la Ley N.° 29783, el empleador debe proporcionar a sus trabajadores los equipos de protección personal adecuados, sin costo, y capacitarlos en su uso y conservación. También debe llevar el registro de entrega.</p>'''),
   ('Cotiza con nosotros', f'''<p {P}>Cuéntanos tu actividad, la cantidad de trabajadores y los riesgos principales, y te armamos una propuesta de dotación. Las cotizaciones se coordinan por WhatsApp o correo.</p>'''),
  ],
  faq=[
   ('¿El EPP reemplaza otras medidas de seguridad?', 'No. Se usa cuando el peligro no pudo eliminarse o controlarse en su origen, y debe complementarse con otros controles.'),
   ('¿Con qué frecuencia se renuevan los EPP?', 'Depende del tipo de equipo, del desgaste y de las indicaciones del fabricante. Lo importante es inspeccionarlos y reponerlos cuando pierden su capacidad de protección.'),
   ('¿Atienden a empresas fuera de Lima?', 'Escríbenos con tu ubicación y coordinamos las condiciones de entrega.'),
  ],
  related=['matriz-iperc','ley-29783-obligaciones-empleador','auditoria-sst-sunafil'],
 ),
 dict(
  slug='ley-29783-obligaciones-empleador',
  title='Ley 29783: obligaciones del empleador en SST | SSOMA SAFE',
  desc='Guía práctica de la Ley 29783 de Seguridad y Salud en el Trabajo: qué debe cumplir tu empresa, documentos obligatorios y errores frecuentes.',
  h1='Ley 29783: qué debe cumplir tu empresa en seguridad y salud en el trabajo',
  crumb='Ley 29783',
  wa='Hola, quiero saber qué debe cumplir mi empresa de la Ley 29783.',
  lead='La Ley N.° 29783, Ley de Seguridad y Salud en el Trabajo, y su Reglamento (D.S. N.° 005-2012-TR) establecen las obligaciones de todo empleador en el Perú. Esta guía resume lo esencial; no reemplaza la lectura de la norma ni una asesoría específica.',
  sections=[
   ('Principios que guían la ley', f'''<p {P}>La ley se apoya, entre otros, en la <strong>prevención</strong> (el empleador garantiza un ambiente seguro y saludable), la <strong>responsabilidad</strong> (responde por las consecuencias de un accidente o enfermedad ocupacional) y la <strong>participación</strong> de los trabajadores y sus representantes.</p>'''),
   ('Obligaciones y documentos clave', f'''<ul {UL}>
      <li><strong>Política de SST</strong> y objetivos por escrito.</li>
      <li><strong>IPERC:</strong> identificación de peligros, evaluación de riesgos y controles, con <a {A} href="matriz-iperc.html">matriz IPERC</a>.</li>
      <li><strong>Comité de SST</strong> (a partir de 20 trabajadores) o <strong>Supervisor de SST</strong> (menos de 20).</li>
      <li><strong>Reglamento Interno de SST</strong> (a partir de 20 trabajadores).</li>
      <li><strong>Programa anual de actividades</strong> y registro de su cumplimiento.</li>
      <li><strong>Capacitaciones:</strong> como mínimo cuatro al año, y al ingreso, cambio de puesto o tecnología.</li>
      <li><strong>Exámenes médicos ocupacionales</strong>, ver <a {A} href="vigilancia-ocupacional.html">vigilancia ocupacional</a>.</li>
      <li><strong>Registros obligatorios:</strong> accidentes, incidentes, inspecciones, monitoreos, entrega de <a {A} href="epp-certificados.html">EPP</a>, entre otros.</li>
    </ul>'''),
   ('Errores frecuentes', f'''<ul {UL}>
      <li>Documentos copiados de otra empresa y que no reflejan los procesos reales.</li>
      <li>IPERC sin actualizar tras cambios en el trabajo.</li>
      <li>Comité constituido pero sin reuniones ni actas.</li>
      <li>Capacitaciones sin registros de asistencia ni evaluación.</li>
    </ul>'''),
   ('¿Y si no cumplo?', f'''<p {P}>La SUNAFIL puede sancionar los incumplimientos con multas, y los accidentes graves pueden generar responsabilidades adicionales. Antes de llegar a ese punto, una <a {A} href="auditoria-sst-sunafil.html">auditoría preventiva</a> permite ordenar lo pendiente.</p>'''),
  ],
  faq=[
   ('¿La ley aplica a microempresas?', 'Sí, aplica a todos los empleadores. Algunas obligaciones, como el Comité y el Reglamento Interno, dependen del número de trabajadores.'),
   ('¿Dónde encuentro los formatos de registros?', 'La R.M. N.° 050-2013-TR publica formatos referenciales de los registros obligatorios.'),
   ('¿Esta guía es asesoría legal?', 'No. Es información general. Para tu caso, consulta con un profesional.'),
  ],
  related=['auditoria-sst-sunafil','matriz-iperc','vigilancia-ocupacional'],
 ),
 dict(
  slug='consultoria-sst-chiclayo',
  title='Consultoría SST en Chiclayo | SSOMA SAFE',
  desc='Consultoría en seguridad y salud en el trabajo en Chiclayo: SGSST, IPERC, auditorías, capacitaciones y EPP. Oficina en Lapoint 1221, Chiclayo.',
  h1='Consultoría en seguridad y salud en el trabajo en Chiclayo',
  crumb='Chiclayo',
  wa='Hola, quiero consultoría SST en Chiclayo.',
  lead='SSOMA SAFE tiene oficina en Chiclayo (Lapoint 1221) y atiende a empresas de la región con consultoría en seguridad y salud en el trabajo, cumplimiento de la Ley N.° 29783 y suministro de EPP.',
  sections=[
   ('Servicios para empresas en Chiclayo', f'''<ul {UL}>
      <li><a {A} href="auditoria-sst-sunafil.html">Auditoría SST y preparación para SUNAFIL</a>.</li>
      <li><a {A} href="matriz-iperc.html">Matriz IPERC</a> y mapas de riesgos.</li>
      <li>Implementación del Sistema de Gestión de SST: política, programa anual, Comité o Supervisor, Reglamento Interno.</li>
      <li><a {A} href="vigilancia-ocupacional.html">Vigilancia de salud ocupacional</a>.</li>
      <li>Capacitaciones y simulacros.</li>
      <li><a {A} href="epp-certificados.html">Suministro de EPP</a>.</li>
    </ul>'''),
   ('Cómo contactarnos', f'''<ul {UL}>
      <li><strong>Dirección:</strong> Lapoint 1221, Chiclayo.</li>
      <li><strong>WhatsApp:</strong> +51 943 704 298.</li>
      <li><strong>Correo:</strong> <a {A} href="mailto:comercial@ssomasafe.com">comercial@ssomasafe.com</a>.</li>
    </ul>
    <p {P}>También atendemos en Lima (Av. República de Chile 324). Coordina con anticipación para programar visitas.</p>'''),
  ],
  faq=[
   ('¿Atienden fuera de la ciudad de Chiclayo?', 'Escríbenos con la ubicación de tu empresa y coordinamos la visita.'),
   ('¿Por dónde empiezo?', 'Con un diagnóstico inicial: revisamos qué tienes, qué falta y en qué orden conviene resolverlo.'),
  ],
  related=['auditoria-sst-sunafil','matriz-iperc','ley-29783-obligaciones-empleador'],
 ),
]
BY = {p['slug']: p for p in PAGES}

def render(p):
    url = f"{SITE}/{p['slug']}.html"
    faq_html = ''.join(
        f'''      <details class="group border-b border-gray-200 py-3"><summary class="cursor-pointer font-semibold">{q}</summary><p {P} style="margin-top:.5rem">{a}</p></details>\n''' for q, a in p['faq'])
    secs = ''.join(f'    <h2 {H2}>{h}</h2>\n    {b}\n' for h, b in p['sections'])
    rel = ''.join(f'<li><a {A} href="{r}.html">{BY[r]["h1"]}</a></li>' for r in p['related'])
    ld = {
      "@context": "https://schema.org",
      "@graph": [
        {"@type": "Service", "name": p['h1'], "url": url, "areaServed": {"@type": "Country", "name": "Perú"},
         "provider": {"@id": f"{SITE}/#organization"}, "description": p['desc'], "inLanguage": "es-PE"},
        {"@type": "BreadcrumbList", "itemListElement": [
          {"@type": "ListItem", "position": 1, "name": "Inicio", "item": f"{SITE}/"},
          {"@type": "ListItem", "position": 2, "name": p['crumb'], "item": url}]}
      ]}
    return f'''<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="theme-color" content="#003F8C" />
  <title>{p['title']}</title>
  <meta name="description" content="{p['desc']}" />
  <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
  <link rel="canonical" href="{url}" />
  <link rel="alternate" hreflang="es-PE" href="{url}" />
  <meta property="og:type" content="article" />
  <meta property="og:url" content="{url}" />
  <meta property="og:title" content="{p['title']}" />
  <meta property="og:description" content="{p['desc']}" />
  <meta property="og:image" content="{SITE}/assets/img/og-image.jpg" />
  <meta property="og:locale" content="es_PE" />
  <meta property="og:site_name" content="SSOMA SAFE" />
  <meta name="twitter:card" content="summary_large_image" />
  <link rel="icon" href="favicon.ico" sizes="any" />
  <link rel="icon" type="image/png" sizes="32x32" href="assets/icons/favicon-32.png" />
  <link rel="apple-touch-icon" href="assets/icons/apple-touch-icon.png" />
  <script type="application/ld+json">
  {json.dumps(ld, ensure_ascii=False, indent=2)}
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="css/tailwind.min.css?v=1" />
</head>
<body class="bg-brand-gray text-brand-dark font-sans antialiased">
  <header class="bg-white border-b border-gray-200">
    <div class="max-w-3xl mx-auto px-6 py-4 flex items-center justify-between">
      <a href="/" aria-label="SSOMA SAFE — Inicio"><img src="assets/img/logo-ssoma-sm.png" alt="SSOMA SAFE" width="73" height="48" class="h-12 w-auto" /></a>
      <a href="/" class="text-sm font-semibold text-brand-primary hover:underline">&larr; Volver al inicio</a>
    </div>
  </header>
  <main class="max-w-3xl mx-auto px-6 py-12">
    <nav aria-label="Ruta de navegación" class="text-xs text-gray-500 mb-6"><a href="/" class="hover:underline">Inicio</a> &rsaquo; {p['crumb']}</nav>
    <h1 class="font-display font-extrabold text-3xl sm:text-4xl leading-tight mb-5">{p['h1']}</h1>
    <p class="text-lg text-gray-700 leading-relaxed mb-8">{p['lead']}</p>
    <div class="flex flex-wrap gap-3 mb-4">
      <a href="{wa(p['wa'])}" target="_blank" rel="noopener noreferrer" class="px-6 py-3 rounded-2xl bg-brand-primary text-white font-bold hover:bg-blue-700 transition-colors">Cotizar por WhatsApp</a>
      <a href="/#contacto" class="px-6 py-3 rounded-2xl border border-brand-primary text-brand-primary font-semibold hover:bg-white transition-colors">Solicitar diagnóstico</a>
    </div>
{secs}
    <h2 {H2}>Preguntas frecuentes</h2>
    <div class="mb-4">
{faq_html}    </div>

    <h2 {H2}>También te puede interesar</h2>
    <ul {UL}>{rel}</ul>

    <div class="mt-12 rounded-3xl bg-brand-primary text-white p-8">
      <p class="font-display font-extrabold text-xl mb-2">¿Hablamos de tu empresa?</p>
      <p class="mb-5 text-white/80">Cuéntanos tu actividad y te orientamos sin compromiso.</p>
      <a href="{wa(p['wa'])}" target="_blank" rel="noopener noreferrer" class="inline-block px-6 py-3 rounded-2xl bg-white text-brand-primary font-bold hover:bg-brand-gray transition-colors">Escribir por WhatsApp</a>
    </div>
  </main>
  <footer class="border-t border-gray-200 bg-white">
    <div class="max-w-3xl mx-auto px-6 py-6 text-xs text-gray-500 flex flex-col sm:flex-row gap-2 justify-between">
      <p>&copy; 2026 SSOMA SAFE S.A.C. &middot; RUC 20607018287</p>
      <p><a href="politica-de-privacidad.html" class="hover:underline">Privacidad</a> &middot; <a href="terminos-y-condiciones.html" class="hover:underline">Términos</a></p>
    </div>
  </footer>
</body>
</html>
'''

for p in PAGES:
    t = len(re.sub(r'\s*\|.*', '', p['title'])); full = len(p['title']); d = len(p['desc'])
    print(f"{p['slug']}: title={full} desc={d}")
    open(f"{p['slug']}.html", 'w', encoding='utf-8', newline='\n').write(render(p))
