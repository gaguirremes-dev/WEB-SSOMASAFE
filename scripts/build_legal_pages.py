"""Genera politica-de-privacidad.html, terminos-y-condiciones.html y 404.html.

Uso (desde la raíz del repo):  python scripts/build_legal_pages.py
Edita los textos AQUÍ y vuelve a ejecutar; no edites los .html a mano.

Nota: 404.html usa rutas absolutas (/css/...) porque el servidor la entrega
en cualquier URL. Las otras páginas usan rutas relativas.
Después de generar, ejecuta `npm run build:css` si añadiste clases Tailwind nuevas.
"""
import os
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
os.chdir(ROOT)

HEAD = '''<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="theme-color" content="#003F8C" />
  <title>{title} | SSOMA SAFE</title>
  <meta name="description" content="{desc}" />
  <meta name="robots" content="{robots}" />
  {canon}
  <link rel="icon" href="/favicon.ico" sizes="any" />
  <link rel="icon" type="image/png" sizes="32x32" href="/assets/icons/favicon-32.png" />
  <link rel="apple-touch-icon" href="/assets/icons/apple-touch-icon.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="/css/tailwind.min.css?v=2" />
</head>
<body class="bg-brand-gray text-brand-dark font-sans antialiased">
  <header class="bg-white border-b border-gray-200">
    <div class="max-w-3xl mx-auto px-6 py-4 flex items-center justify-between">
      <a href="/" aria-label="SSOMA SAFE — Inicio"><img src="/assets/img/logo-ssoma-sm.png" alt="SSOMA SAFE" width="73" height="48" class="h-12 w-auto" /></a>
      <a href="/" class="text-sm font-semibold text-brand-primary hover:underline">&larr; Volver al inicio</a>
    </div>
  </header>
  <main class="max-w-3xl mx-auto px-6 py-12">
'''
FOOT = '''  </main>
  <footer class="border-t border-gray-200 bg-white">
    <div class="max-w-3xl mx-auto px-6 py-6 text-xs text-gray-500 flex flex-col sm:flex-row gap-2 justify-between">
      <p>&copy; 2026 SSOMA SAFE S.A.C. &middot; RUC 20607018287</p>
      <p><a href="/politica-de-privacidad.html" class="hover:underline">Privacidad</a> &middot; <a href="/terminos-y-condiciones.html" class="hover:underline">Términos</a></p>
    </div>
  </footer>
</body>
</html>
'''

def page(fn, title, desc, body, robots='index, follow'):
    canon = f'<link rel="canonical" href="https://www.ssomasafe.com/{fn}" />' if robots.startswith('index') else ''
    html = HEAD.format(title=title, desc=desc, robots=robots, canon=canon) + body + FOOT
    if fn != '404.html':  # rutas relativas: funcionan en el dominio y al abrir el archivo en local
        html = html.replace('"/css/', '"css/').replace('"/assets/', '"assets/').replace('"/favicon.ico"', '"favicon.ico"')
    open(fn, 'w', encoding='utf-8', newline='\n').write(html)

H2 = 'class="font-display font-extrabold text-xl mt-10 mb-3"'
P = 'class="text-gray-700 leading-relaxed mb-3"'
UL = 'class="list-disc pl-6 space-y-1.5 text-gray-700 leading-relaxed mb-3"'
A = 'class="text-brand-primary underline"'

priv = f'''    <h1 class="font-display font-extrabold text-3xl sm:text-4xl mb-2">Política de Privacidad</h1>
    <p class="text-sm text-gray-500 mb-8">Última actualización: 16 de noviembre de 2026</p>

    <p {P}>SSOMA SAFE S.A.C. (RUC 20607018287), con domicilio en Av. República de Chile 324, Lima, y Lapoint 1221, Chiclayo, Perú (en adelante, «SSOMA SAFE»), es titular del sitio web www.ssomasafe.com y responsable del tratamiento de los datos personales que usted nos proporciona, conforme a la Ley N.° 29733, Ley de Protección de Datos Personales, y su Reglamento aprobado por el Decreto Supremo N.° 016-2024-JUS.</p>

    <h2 {H2}>1. Datos que recopilamos</h2>
    <ul {UL}>
      <li><strong>Formulario de contacto:</strong> nombre, empresa, correo electrónico, teléfono (opcional), servicio de interés y mensaje. Estos datos se envían a nuestro WhatsApp comercial cuando usted pulsa «Enviar».</li>
      <li><strong>Libro de Reclamaciones virtual:</strong> los datos exigidos por el Código de Protección y Defensa del Consumidor (Ley N.° 29571): identificación del consumidor, contacto, detalle del bien o servicio y del reclamo o queja.</li>
      <li><strong>WhatsApp y correo:</strong> los datos que usted decida compartir al escribirnos por estos canales.</li>
      <li><strong>Datos técnicos:</strong> en el Libro de Reclamaciones se guarda una huella (hash) irreversible de la dirección IP con fines antifraude; no se almacena la IP en claro.</li>
    </ul>

    <h2 {H2}>2. Finalidades</h2>
    <ul {UL}>
      <li>Responder consultas y preparar cotizaciones o diagnósticos de nuestros servicios.</li>
      <li>Atender y registrar reclamos y quejas, y cumplir con la normativa de protección al consumidor.</li>
      <li>Prevenir fraude y spam en nuestros formularios.</li>
    </ul>
    <p {P}>No vendemos ni cedemos sus datos a terceros con fines comerciales.</p>

    <h2 {H2}>3. Base legal y conservación</h2>
    <p {P}>Tratamos sus datos con su consentimiento libre, previo, expreso, informado e inequívoco, que usted otorga al enviar el formulario o marcar la casilla correspondiente; para la ejecución de las medidas precontractuales que usted solicita; y para cumplir obligaciones legales. Los conservamos durante el tiempo necesario para la finalidad que los originó y los plazos legales aplicables; luego los eliminamos o anonimizamos.</p>
    <p {P}>Este sitio está dirigido a empresas y profesionales y no recopila datos de menores de edad de forma intencional. La única excepción es el Libro de Reclamaciones: si el consumidor es menor de edad, la normativa de consumo exige registrar también los datos de su padre, madre o apoderado, que tratamos solo para atender el reclamo.</p>

    <h2 {H2}>4. Encargados y transferencias</h2>
    <p {P}>Para operar el sitio usamos proveedores que pueden tratar datos por nuestra cuenta: hosting, correo electrónico, Cloudflare (seguridad y red de entrega), hCaptcha (verificación antispam), Google Fonts y WhatsApp (Meta). Algunos pueden estar ubicados fuera del Perú, por lo que sus datos podrían ser objeto de flujo transfronterizo; en ese caso procuramos que el país de destino o el proveedor ofrezca un nivel adecuado de protección, conforme a la normativa vigente. Además, al cargar la página su navegador solicita recursos (tipografías y bibliotecas) a servidores de terceros, como Google Fonts, cdnjs y jsDelivr, que pueden registrar su dirección IP.</p>

    <h2 {H2}>5. Cookies</h2>
    <p {P}>Este sitio no usa cookies de publicidad ni de seguimiento propias. El Libro de Reclamaciones emplea una cookie de sesión técnica, necesaria para la seguridad del formulario.</p>

    <h2 {H2}>6. Sus derechos</h2>
    <p {P}>Usted puede ejercer sus derechos de acceso, rectificación, cancelación y oposición (ARCO), así como revocar su consentimiento, escribiendo a <a {A} href="mailto:comercial@ssomasafe.com">comercial@ssomasafe.com</a> con el asunto «Datos personales» e indicando su nombre y el derecho que desea ejercer. Atenderemos su solicitud dentro de los plazos que establece la normativa de protección de datos personales. Si considera que no la atendimos o que la respuesta fue insatisfactoria, puede presentar su reclamo ante la Autoridad Nacional de Protección de Datos Personales (Dirección de Protección de Datos Personales del Ministerio de Justicia y Derechos Humanos).</p>

    <h2 {H2}>7. Seguridad</h2>
    <p {P}>Aplicamos medidas técnicas y organizativas razonables para proteger sus datos contra pérdida, acceso o uso no autorizado.</p>

    <h2 {H2}>8. Cambios</h2>
    <p {P}>Podemos actualizar esta política; la versión vigente es la publicada en esta página, con su fecha de actualización.</p>
'''

terms = f'''    <h1 class="font-display font-extrabold text-3xl sm:text-4xl mb-2">Términos y Condiciones</h1>
    <p class="text-sm text-gray-500 mb-8">Última actualización: 16 de noviembre de 2026</p>

    <p {P}>El acceso y uso de www.ssomasafe.com (el «Sitio») implica la aceptación de estos términos. El Sitio es operado por SSOMA SAFE S.A.C., RUC 20607018287, Lima y Chiclayo, Perú.</p>

    <h2 {H2}>1. Objeto del Sitio</h2>
    <p {P}>El Sitio presenta información sobre los servicios de consultoría en seguridad y salud ocupacional, vigilancia ocupacional y suministro de equipos de protección personal (EPP) de SSOMA SAFE, y permite solicitar contacto, cotizaciones y registrar reclamos.</p>

    <h2 {H2}>2. Carácter informativo</h2>
    <p {P}>La información publicada es de carácter general y no constituye asesoría legal ni técnica específica ni una oferta contractual vinculante. Los servicios y precios se formalizan mediante propuesta, cotización o contrato escrito.</p>

    <h2 {H2}>3. Uso adecuado</h2>
    <ul {UL}>
      <li>Debe proporcionar información veraz en los formularios.</li>
      <li>Queda prohibido el uso del Sitio para fines ilícitos, el envío de spam o intentos de vulnerar su seguridad.</li>
    </ul>

    <h2 {H2}>4. Propiedad intelectual</h2>
    <p {P}>Los textos, marcas, logotipos, imágenes y demás contenidos del Sitio pertenecen a SSOMA SAFE o a sus licenciantes y no pueden reproducirse sin autorización previa. Las marcas y logotipos de certificadoras o terceros pertenecen a sus titulares.</p>

    <h2 {H2}>5. Enlaces y servicios de terceros</h2>
    <p {P}>El Sitio puede enlazar a redes sociales y servicios de terceros (WhatsApp, Instagram, Facebook, TikTok, YouTube). SSOMA SAFE no controla ni responde por sus contenidos o políticas.</p>

    <h2 {H2}>6. Limitación de responsabilidad</h2>
    <p {P}>Nos esforzamos por mantener el Sitio disponible y actualizado, pero no garantizamos la ausencia de interrupciones o errores. SSOMA SAFE no responderá por daños derivados del uso del Sitio, salvo lo que la ley no permita excluir.</p>

    <h2 {H2}>7. Libro de Reclamaciones</h2>
    <p {P}>Conforme a la Ley N.° 29571, ponemos a su disposición un <a {A} href="/Libro-reclamaciones/libro-de-reclamaciones.php">Libro de Reclamaciones virtual</a>. Su uso no impide acudir directamente a INDECOPI ni a otras vías de protección del consumidor.</p>

    <h2 {H2}>8. Datos personales</h2>
    <p {P}>El tratamiento de sus datos se rige por nuestra <a {A} href="/politica-de-privacidad.html">Política de Privacidad</a>.</p>

    <h2 {H2}>9. Ley aplicable</h2>
    <p {P}>Estos términos se rigen por las leyes de la República del Perú. Cualquier controversia se someterá a los jueces y tribunales de Lima, sin perjuicio de los derechos del consumidor.</p>

    <h2 {H2}>10. Contacto</h2>
    <p {P}>Consultas: <a {A} href="mailto:comercial@ssomasafe.com">comercial@ssomasafe.com</a> · WhatsApp +51 943 704 298.</p>
'''

nf = '''    <div class="text-center py-16">
      <p class="font-display font-extrabold text-7xl text-brand-primary mb-4">404</p>
      <h1 class="font-display font-extrabold text-2xl mb-3">Página no encontrada</h1>
      <p class="text-gray-600 mb-8">La página que buscas no existe o fue movida.</p>
      <a href="/" class="inline-block px-8 py-3 rounded-2xl bg-brand-primary text-white font-bold hover:bg-blue-700 transition-colors">Ir al inicio</a>
    </div>
'''

page('politica-de-privacidad.html', 'Política de Privacidad', 'Política de Privacidad de SSOMA SAFE conforme a la Ley N.° 29733 de Protección de Datos Personales del Perú.', priv)
page('terminos-y-condiciones.html', 'Términos y Condiciones', 'Términos y condiciones de uso del sitio web de SSOMA SAFE, consultoría en seguridad y salud ocupacional en Perú.', terms)
page('404.html', 'Página no encontrada', 'La página que buscas no existe o fue movida. Vuelve al inicio de SSOMA SAFE.', nf, 'noindex, follow')
