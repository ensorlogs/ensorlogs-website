#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Genera las páginas legales (privacidad, cookies, aviso legal, accesibilidad)
en la carpeta ``legal/`` reusando la cabecera y footer del sitio.

Cualquier `[PLACEHOLDER]` que aparezca en el resultado es un campo que el
titular debe completar a mano antes de publicar (nombre/razón social, RIF,
dirección postal, email de contacto). Marca legal: RGPD (UE) +
LOPDGDD/LSSI-CE (España) + Ley sobre Mensajes de Datos y Firmas Electrónicas
y Ley Especial Contra Delitos Informáticos (Venezuela).

Ejecución::

    .venv/bin/python scripts/render_legal_pages.py
"""
from __future__ import annotations

import re
import textwrap
from datetime import date
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SRC = ROOT / "about.html"
OUT = ROOT / "legal"
TODAY = date.today().strftime("%d/%m/%Y")

# Editables fácilmente. Lo que va entre [CORCHETES] el titular debe completarlo.
TITLE_OPERATOR = "Ensorlogs · Ensor Sánchez"
PLACEHOLDER_DATA = {
    "[NOMBRE_RAZON_SOCIAL]": "Ensor Sánchez",
    "[RIF_NIF]": "[RIF/NIF pendiente — añadir antes de publicar]",
    "[DOMICILIO]": "Caracas, Venezuela",
    "[EMAIL_CONTACTO]": "hola@ensorlogs.com",
    "[EMAIL_PRIVACIDAD]": "privacidad@ensorlogs.com",
    "[URL]": "https://ensorlogs.com",
}


def read_source() -> str:
    return SRC.read_text(encoding="utf-8")


def extract_head_and_chrome(html: str) -> tuple[str, str, str]:
    """Devuelve (head_html, header_html, footer_html)."""
    head_end = html.index("</head>") + len("</head>")
    body_start = html.index("<body", head_end)
    head_html = html[:head_end]

    main_start = html.index('<main class="app">', body_start)
    header_section_open = html.index("<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n    Start Header", main_start)
    header_section_close = html.index("<!--~~./ end Header", header_section_open)
    header_section_end = html.index("-->", header_section_close) + len("-->")
    header_html = html[main_start:header_section_end]

    # Footer + scripts hasta cierre body/html
    footer_marker = "<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n    Start Footer"
    if footer_marker in html:
        footer_start = html.index(footer_marker)
    else:
        footer_start = html.index("<footer")
    footer_html = html[footer_start:]
    return head_html, header_html, footer_html


def adjust_head_for_legal(head_html: str, page_title: str, page_desc: str, slug: str) -> str:
    head_html = re.sub(
        r'<title>[^<]+</title>',
        f'<title>{page_title} | Ensorlogs</title>',
        head_html,
    )
    head_html = re.sub(
        r'(<meta name="description"\s*\n?\s*content=")[^"]+(")',
        rf'\1{page_desc}\2',
        head_html,
    )
    head_html = re.sub(
        r'(<meta property="og:title" content=")[^"]+(")',
        rf'\1{page_title}\2',
        head_html,
    )
    head_html = re.sub(
        r'(<meta property="og:description" content=")[^"]+(")',
        rf'\1{page_desc}\2',
        head_html,
    )
    head_html = re.sub(
        r'(<meta property="og:url" content=")[^"]+(")',
        rf'\1https://ensorlogs.com/legal/{slug}.html\2',
        head_html,
    )
    head_html = head_html.replace(
        'href="https://ensorlogs.com/about.html"',
        f'href="https://ensorlogs.com/legal/{slug}.html"',
    )
    # Inyecta CSS comunes del sitio (a11y + cookies + branded legal page).
    extra_css = (
        '    <link rel="stylesheet" href="../assets/css/ensor-a11y.css">\n'
        '    <link rel="stylesheet" href="../assets/css/ensor-cookies.css">\n'
        '    <link rel="stylesheet" href="../assets/css/ensor-legal.css">\n'
        '    <meta name="ensor-cookies-url" content="../legal/cookies.html">\n'
    )
    head_html = head_html.replace(
        '    <link rel="stylesheet" href="assets/css/ensor-brand.css">',
        '    <link rel="stylesheet" href="assets/css/ensor-brand.css">\n' + extra_css.replace('../assets/', 'LEGAL_REL_'),
    )
    # Reescribe rutas relativas para legal/
    head_html = head_html.replace('LEGAL_REL_', '../assets/')
    head_html = head_html.replace('href="assets/', 'href="../assets/')
    head_html = head_html.replace('src="assets/', 'src="../assets/')
    return head_html


def adjust_chrome_for_legal(html: str) -> str:
    pairs = (
        ('href="index.html', 'href="../index.html'),
        ('href="about.html', 'href="../about.html'),
        ('href="blog.html', 'href="../blog.html'),
        ('href="blog-2.html', 'href="../blog-2.html'),
        ('href="projects.html', 'href="../projects.html'),
        ('href="services.html', 'href="../services.html'),
        ('href="contact.html', 'href="../contact.html'),
        ('href="credentials.html', 'href="../credentials.html'),
        ('href="assets/', 'href="../assets/'),
        ('src="assets/', 'src="../assets/'),
    )
    for old, new in pairs:
        html = html.replace(old, new)
    return html


# ----------------------------------------------------------------------- content

PRIVACY_BODY = """
<h2>1. Responsable del tratamiento</h2>
<p>El responsable del tratamiento de los datos personales recabados a través de este sitio web es <strong>[NOMBRE_RAZON_SOCIAL]</strong>, identificado con <strong>[RIF_NIF]</strong>, con domicilio en <strong>[DOMICILIO]</strong>. Para cualquier asunto relacionado con tus datos puedes escribir a <a href="mailto:[EMAIL_PRIVACIDAD]">[EMAIL_PRIVACIDAD]</a>.</p>

<h2>2. Datos que tratamos y para qué</h2>
<ul>
  <li><strong>Formulario de contacto:</strong> nombre, correo electrónico, mensaje. Finalidad: responder a tu solicitud. Base jurídica: consentimiento al enviar el formulario (RGPD art. 6.1.a) y/o ejecución de medidas precontractuales (RGPD art. 6.1.b).</li>
  <li><strong>Newsletter / suscripción (cuando se active):</strong> correo electrónico y nombre opcional. Finalidad: enviar comunicaciones sobre los logs, proyectos y servicios. Base jurídica: consentimiento explícito.</li>
  <li><strong>Comentarios o interacciones en logs:</strong> nombre y correo electrónico para moderar. Base jurídica: interés legítimo del responsable en mantener una conversación útil y libre de spam.</li>
  <li><strong>Datos técnicos (cookies, registros de servidor):</strong> dirección IP truncada, agente de usuario, páginas visitadas, idioma, momento de visita. Finalidad: seguridad, prevención de fraude y medición agregada. Base jurídica: interés legítimo (cookies técnicas) y consentimiento (cookies analíticas/marketing).</li>
</ul>

<h2>3. Plazo de conservación</h2>
<p>Los datos se conservan mientras dure la relación o, en su defecto, durante los plazos legalmente exigibles. En particular: solicitudes de contacto durante 24 meses desde la última interacción; suscripciones al newsletter hasta que solicites la baja; registros de servidor hasta 12 meses.</p>

<h2>4. Destinatarios</h2>
<p>No vendemos ni cedemos tus datos. Únicamente podemos compartirlos con encargados del tratamiento estrictamente necesarios para prestar el servicio (alojamiento, correo, herramientas de analítica con consentimiento, plataformas de envío de newsletter). Todos los encargados están vinculados por contrato y, cuando corresponda, por las cláusulas contractuales tipo de la UE para transferencias internacionales.</p>

<h2>5. Tus derechos</h2>
<p>Puedes ejercer los siguientes derechos en cualquier momento:</p>
<ul>
  <li>Acceso, rectificación y supresión de tus datos.</li>
  <li>Limitación u oposición al tratamiento.</li>
  <li>Portabilidad de los datos a otro responsable.</li>
  <li>Retirada del consentimiento prestado, sin que afecte a la licitud del tratamiento previo.</li>
  <li>Presentar reclamación ante la autoridad de control competente (en España, <a href="https://www.aepd.es" target="_blank" rel="noopener noreferrer">AEPD</a>; en Venezuela, la autoridad competente conforme a la legislación local).</li>
</ul>
<p>Para ejercerlos, escríbenos a <a href="mailto:[EMAIL_PRIVACIDAD]">[EMAIL_PRIVACIDAD]</a> identificándote.</p>

<h2>6. Medidas de seguridad</h2>
<p>Aplicamos medidas técnicas y organizativas razonables para proteger tus datos: HTTPS forzado, cabeceras de seguridad (HSTS, X-Content-Type-Options, Referrer-Policy, Permissions-Policy), bloqueo de enumeración de usuarios, limitador de intentos de inicio de sesión, copias de seguridad cifradas y revisión periódica.</p>

<h2>7. Menores</h2>
<p>Los servicios no están dirigidos a menores de 14 años. Si detectamos datos de un menor sin consentimiento de quien ejerza la patria potestad, los eliminaremos.</p>

<h2>8. Marco legal aplicable</h2>
<p>Esta política se rige por el Reglamento (UE) 2016/679 (RGPD), la Ley Orgánica 3/2018 de Protección de Datos Personales y garantía de los derechos digitales (LOPDGDD), la Ley 34/2002 de Servicios de la Sociedad de la Información y Comercio Electrónico (LSSI-CE) y, en relación con visitantes desde Venezuela, la Ley sobre Mensajes de Datos y Firmas Electrónicas y la Ley Especial Contra los Delitos Informáticos.</p>

<h2>9. Actualizaciones</h2>
<p>Esta política se actualiza periódicamente. Cualquier cambio relevante se comunicará al menos con una notificación visible en el sitio. Última actualización: <strong>{TODAY}</strong>.</p>
"""

COOKIES_BODY = """
<h2>1. ¿Qué son las cookies?</h2>
<p>Las cookies son pequeños archivos que se almacenan en tu dispositivo cuando visitas un sitio. Permiten recordar preferencias y obtener información agregada del uso del sitio. Junto a las cookies pueden emplearse tecnologías similares como <em>localStorage</em>.</p>

<h2>2. Tipos de cookies que usamos</h2>
<ul>
  <li><strong>Técnicas (siempre activas).</strong> Imprescindibles para que el sitio funcione, recuerde el tema claro/oscuro, las preferencias de accesibilidad y tu propio consentimiento de cookies.</li>
  <li><strong>Analíticas (opcionales).</strong> Cuando las aceptas, nos ayudan a entender qué logs y proyectos funcionan mejor con datos agregados. Si no las aceptas, no se cargan.</li>
  <li><strong>Marketing (opcionales).</strong> Recordarían interacciones con campañas o CTAs. Hoy <strong>no se cargan</strong>; se reservaría su uso para colaboraciones puntuales y siempre previo consentimiento.</li>
</ul>

<h2>3. Detalle de cookies actualmente instaladas</h2>
<table>
  <thead>
    <tr><th>Cookie</th><th>Propietario</th><th>Categoría</th><th>Duración</th><th>Finalidad</th></tr>
  </thead>
  <tbody>
    <tr><td><code>ensorlogs_consent</code></td><td>Ensorlogs</td><td>Técnica</td><td>12 meses</td><td>Recordar tu decisión sobre cookies.</td></tr>
    <tr><td><code>theme</code> (localStorage)</td><td>Ensorlogs</td><td>Técnica</td><td>Permanente hasta borrarla</td><td>Recordar el modo claro/oscuro elegido.</td></tr>
    <tr><td><code>ensor_a11y_prefs_v1</code> (localStorage)</td><td>Ensorlogs</td><td>Técnica</td><td>Permanente hasta borrarla</td><td>Tamaño de texto, alto contraste y espaciado de accesibilidad.</td></tr>
  </tbody>
</table>

<h2>4. Cómo gestionar tu consentimiento</h2>
<p>Al entrar por primera vez verás un aviso con tres opciones: <strong>Aceptar todo</strong>, <strong>Rechazar todo</strong> o <strong>Personalizar</strong>. Puedes cambiar tu decisión cuando quieras con el enlace <button type="button" class="ensor-cookies-reopen">Preferencias de cookies</button> que aparece en el pie del sitio.</p>

<h2>5. Bloqueo desde el navegador</h2>
<p>Además, puedes configurar tu navegador para bloquear cookies o eliminarlas:</p>
<ul>
  <li><a href="https://support.google.com/chrome/answer/95647" target="_blank" rel="noopener noreferrer">Google Chrome</a></li>
  <li><a href="https://support.mozilla.org/es/kb/cookies-informacion-que-los-sitios-web-guardan-en-" target="_blank" rel="noopener noreferrer">Mozilla Firefox</a></li>
  <li><a href="https://support.apple.com/es-es/guide/safari/sfri11471/mac" target="_blank" rel="noopener noreferrer">Apple Safari</a></li>
  <li><a href="https://support.microsoft.com/es-es/microsoft-edge/" target="_blank" rel="noopener noreferrer">Microsoft Edge</a></li>
</ul>

<h2>6. Cambios en esta política</h2>
<p>Si añadimos nuevas cookies (por ejemplo, analítica o de un partner) actualizaremos esta tabla y solicitaremos un nuevo consentimiento cuando sea necesario. Última actualización: <strong>{TODAY}</strong>.</p>
"""

LEGAL_BODY = """
<h2>1. Titular del sitio</h2>
<ul>
  <li><strong>Titular:</strong> [NOMBRE_RAZON_SOCIAL]</li>
  <li><strong>Identificación fiscal:</strong> [RIF_NIF]</li>
  <li><strong>Domicilio:</strong> [DOMICILIO]</li>
  <li><strong>Correo electrónico de contacto:</strong> <a href="mailto:[EMAIL_CONTACTO]">[EMAIL_CONTACTO]</a></li>
  <li><strong>Sitio web:</strong> <a href="[URL]" target="_blank" rel="noopener noreferrer">[URL]</a></li>
</ul>

<h2>2. Objeto</h2>
<p>Ensorlogs es una bitácora profesional dedicada a publicar artículos («logs») sobre tecnología, operaciones digitales y formación, además de presentar proyectos y servicios profesionales. El acceso al sitio es libre y gratuito, salvo en lo relativo al coste de la conexión a internet.</p>

<h2>3. Condiciones de uso</h2>
<p>El usuario se compromete a hacer un uso lícito de los contenidos. Queda prohibido utilizar el sitio para enviar comunicaciones no autorizadas, realizar ingeniería inversa de los servicios o intentar acceder a áreas restringidas. El incumplimiento podrá dar lugar a las acciones legales que correspondan.</p>

<h2>4. Propiedad intelectual</h2>
<p>Todos los textos, imágenes, diseños, código y demás elementos del sitio pertenecen al titular o se utilizan con autorización. Los logos, marcas y nombres comerciales mencionados son propiedad de sus respectivos titulares. Se permite la cita parcial con enlace a la fuente original; cualquier otro uso requiere consentimiento previo por escrito.</p>

<h2>5. Exclusión de garantías y responsabilidad</h2>
<p>El titular trabaja para que los contenidos sean precisos y útiles, pero no garantiza la ausencia de errores ni la actualidad permanente de la información. No se asume responsabilidad por decisiones tomadas exclusivamente con base en los contenidos publicados ni por interrupciones técnicas ajenas a su control. Los enlaces a terceros se ofrecen como cortesía y no implican apoyo a sus contenidos.</p>

<h2>6. Protección de datos</h2>
<p>Consulta nuestra <a href="privacidad.html">Política de privacidad</a> para conocer cómo tratamos los datos personales y cuáles son tus derechos.</p>

<h2>7. Legislación aplicable y jurisdicción</h2>
<p>Estas condiciones se rigen por la legislación española (sin perjuicio de las normas imperativas del país de residencia del usuario). Para cualquier controversia las partes se someten a los juzgados y tribunales de [DOMICILIO]. Los usuarios desde Venezuela podrán también acudir a la jurisdicción competente conforme a su legislación local.</p>

<p>Última actualización: <strong>{TODAY}</strong>.</p>
"""

ACCESS_BODY = """
<h2>Declaración de accesibilidad</h2>
<p>Ensorlogs se compromete a hacer accesibles sus contenidos digitales conforme a las pautas <strong>WCAG 2.2 nivel AA</strong> y la norma <strong>UNE-EN 301 549</strong>, en línea con la Directiva (UE) 2016/2102 y la Ley 11/2023 (Ley Europea de Accesibilidad / EAA).</p>

<h2>Medidas implantadas</h2>
<ul>
  <li>Estructura semántica del HTML con etiquetas <code>header</code>, <code>main</code>, <code>article</code>, <code>section</code>, <code>nav</code>, <code>footer</code> y atributos <code>role</code> donde procede.</li>
  <li>Enlace «Saltar al contenido principal» visible al recibir foco.</li>
  <li>Foco visible reforzado en enlaces, botones y campos.</li>
  <li>Toolbar de accesibilidad: tamaño de texto (3 niveles), espaciado de lectura, modo de alto contraste, persistente en el navegador.</li>
  <li>Soporte de <code>prefers-reduced-motion</code>: las animaciones se desactivan si tu sistema lo solicita.</li>
  <li>Logs con barra de progreso, índice navegable (TOC), filtros por audiencia y bloque «Comentario del autor» en audio con controles propios.</li>
  <li>Imágenes con texto alternativo y vídeos con leyendas cuando proceda.</li>
  <li>Contrastes mínimos AA verificados en colores de marca para texto sobre fondo claro y oscuro.</li>
</ul>

<h2>Contenido no plenamente accesible</h2>
<p>Algunos contenidos importados o embebidos de terceros (por ejemplo, redes sociales, embeds de vídeo) podrían no cumplir todos los criterios. Estamos revisando estos casos para sustituirlos por alternativas accesibles o añadir transcripciones / leyendas.</p>

<h2>¿Encontraste una barrera?</h2>
<p>Si te encuentras una dificultad para acceder a algún contenido, escríbenos a <a href="mailto:[EMAIL_CONTACTO]">[EMAIL_CONTACTO]</a> con: la URL, una breve descripción del problema y, si puedes, una captura. Intentaremos darte respuesta en un plazo razonable y, en cualquier caso, en menos de un mes.</p>

<h2>Compatibilidad y tecnologías</h2>
<p>El sitio se prueba en las últimas versiones estables de Chrome, Firefox, Safari y Edge sobre Windows, macOS, iOS y Android, con lectores de pantalla NVDA, VoiceOver y TalkBack.</p>

<p>Última actualización: <strong>{TODAY}</strong>.</p>
"""

PAGES = [
    {
        "slug": "privacidad",
        "title": "Política de privacidad",
        "desc": "Cómo trata Ensorlogs los datos personales: bases jurídicas, derechos, plazos y contactos.",
        "lead": "Esta política describe qué datos personales tratamos cuando interactúas con Ensorlogs, con qué finalidad, durante cuánto tiempo y qué derechos puedes ejercer en cualquier momento.",
        "tags": ["RGPD", "LOPDGDD", "LSSI-CE"],
        "body": PRIVACY_BODY,
    },
    {
        "slug": "cookies",
        "title": "Política de cookies",
        "desc": "Cookies usadas en Ensorlogs, finalidad, plazo y cómo gestionar tu consentimiento.",
        "lead": "Aquí explicamos qué cookies (y tecnologías equivalentes) usamos en Ensorlogs, para qué sirven y cómo puedes controlarlas en cualquier momento desde la propia web o tu navegador.",
        "tags": ["Cookies", "RGPD", "LSSI-CE"],
        "body": COOKIES_BODY,
    },
    {
        "slug": "aviso-legal",
        "title": "Aviso legal y condiciones de uso",
        "desc": "Información del titular del sitio, condiciones de uso, propiedad intelectual y jurisdicción.",
        "lead": "Información obligatoria del titular del sitio, las condiciones bajo las que se ofrece el servicio y el marco legal aplicable a cualquier persona que navegue por Ensorlogs.",
        "tags": ["Aviso legal", "Condiciones", "Propiedad intelectual"],
        "body": LEGAL_BODY,
    },
    {
        "slug": "accesibilidad",
        "title": "Declaración de accesibilidad",
        "desc": "Compromiso WCAG 2.2 AA y UNE-EN 301 549, medidas implantadas y cómo reportar barreras.",
        "lead": "Nuestro compromiso para que cualquier persona pueda leer, escuchar y usar Ensorlogs: medidas técnicas aplicadas, contenidos pendientes y cómo avisarnos si encuentras una barrera.",
        "tags": ["WCAG 2.2 AA", "UNE-EN 301 549", "EAA"],
        "body": ACCESS_BODY,
    },
]


# ----------------------------------------------------------------------- build

LEGAL_LAYOUT = """
<main class="ensor-legal-page mt-28 md:mt-32 lg:mt-40 mb-12" id="main-content">
    <section class="ensor-legal-hero" aria-labelledby="legal-title">
        <div class="ensor-legal-hero__inner container max-w-[1180px] mx-auto px-4">
            <p class="ensor-legal-hero__eyebrow">{eyebrow}</p>
            <h1 id="legal-title" class="ensor-legal-hero__title">{title}</h1>
            <p class="ensor-legal-hero__lead">{lead}</p>
            <div class="ensor-legal-hero__meta">
                <span class="ensor-legal-pill ensor-legal-pill--accent"><span class="ensor-legal-pill__dot"></span>Actualizado · {today}</span>
{tags_html}
            </div>
        </div>
    </section>

    <div class="ensor-legal-wrap container max-w-[1180px] mx-auto px-4">
        <article class="ensor-legal-body">
{body_html}
        </article>
    </div>

    <div class="ensor-legal-foot container max-w-[1180px] mx-auto px-4">
        <div class="ensor-legal-cta-card">
            <div class="ensor-legal-cta-card__text">
                <p class="ensor-legal-cta-card__title">¿Te queda alguna duda con este documento?</p>
                <p class="ensor-legal-cta-card__sub">Escríbenos a <a href="mailto:{email}">{email}</a> y te respondo personalmente. Si prefieres una conversación, puedes ir directo al formulario de contacto.</p>
            </div>
            <a class="ensor-legal-cta-card__action" href="../contact.html">
                <span>Hablemos</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
            </a>
        </div>
        <nav class="ensor-legal-cross" aria-label="Otros documentos legales">
            <p class="ensor-legal-cross__title">Documentos relacionados</p>
{cross_links}
        </nav>
    </div>
</main>
"""


CROSS_LINKS = [
    ("aviso-legal",   "Aviso legal"),
    ("privacidad",    "Privacidad"),
    ("cookies",       "Cookies"),
    ("accesibilidad", "Accesibilidad"),
]


def render_tags(tags: list[str]) -> str:
    return "\n".join(
        f'                <span class="ensor-legal-pill">{t}</span>'
        for t in tags
    )


def render_cross_links(current_slug: str) -> str:
    items = []
    for slug, label in CROSS_LINKS:
        if slug == current_slug:
            items.append(
                f'            <a href="{slug}.html" class="is-current" aria-current="page">{label}</a>'
            )
        else:
            items.append(f'            <a href="{slug}.html">{label}</a>')
    return "\n".join(items)


def fill_placeholders(text: str) -> str:
    for k, v in PLACEHOLDER_DATA.items():
        text = text.replace(k, v)
    return text


def build_pages():
    src = read_source()
    head, header, footer = extract_head_and_chrome(src)
    OUT.mkdir(parents=True, exist_ok=True)

    for page in PAGES:
        head_p = adjust_head_for_legal(head, page["title"], page["desc"], page["slug"])
        chrome_top = adjust_chrome_for_legal(header)
        chrome_bot = adjust_chrome_for_legal(footer)
        body = page["body"].strip().replace("{TODAY}", TODAY)
        body = fill_placeholders(body)
        # Indenta el contenido del body para que case con la sangría del layout.
        body = "\n".join("            " + line if line.strip() else line
                         for line in body.split("\n"))
        layout = LEGAL_LAYOUT.format(
            eyebrow=f"Documento legal · Ensorlogs",
            title=page["title"],
            lead=page["lead"],
            today=TODAY,
            tags_html=render_tags(page["tags"]),
            body_html=body,
            email=PLACEHOLDER_DATA["[EMAIL_CONTACTO]"],
            cross_links=render_cross_links(page["slug"]),
        )
        full = head_p + "\n<body class=\"relative bg-[#F5F7F9] dark:bg-powerBlack\">\n" + chrome_top + "\n" + layout + "\n" + chrome_bot
        (OUT / f"{page['slug']}.html").write_text(full, encoding="utf-8")
        print("→", OUT / f"{page['slug']}.html")


if __name__ == "__main__":
    build_pages()
    try:
        import inject_globals  # type: ignore
        inject_globals.main()
    except Exception as e:  # pragma: no cover
        print("inject_globals omitido:", e)
